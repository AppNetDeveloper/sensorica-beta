<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\Process;
use Illuminate\Http\Request;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderArticle;
use Illuminate\Support\Facades\DB;
use App\Models\ProductionOrder;
use Symfony\Component\Process\Process as SymfonyProcess;
use Illuminate\Support\Facades\Log;

class CustomerOriginalOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:original-order-list|original-order-create|original-order-edit|original-order-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:original-order-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:original-order-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:original-order-delete', ['only' => ['destroy', 'bulkDelete']]);
        // Permisos para la nueva vista y su API
        $this->middleware('permission:original-order-list', ['only' => ['finishedProcessesView', 'finishedProcessesData']]);
    }

    /**
     * Eliminar múltiples órdenes originales
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request, Customer $customer)
    {
        try {
            $ids = $request->ids;
            if (!$ids || !is_array($ids) || count($ids) === 0) {
                return response()->json(['success' => false, 'message' => __('No hay órdenes seleccionadas para eliminar')], 400);
            }
            
            // Verificar que las órdenes pertenezcan al cliente actual
            $count = OriginalOrder::where('customer_id', $customer->id)
                ->whereIn('id', $ids)
                ->count();
                
            if ($count !== count($ids)) {
                return response()->json(['success' => false, 'message' => __('Algunas órdenes seleccionadas no pertenecen a este cliente')], 400);
            }
            
            // Eliminar órdenes y sus relaciones (procesos, artículos, etc.)
            $deleted = OriginalOrder::where('customer_id', $customer->id)
                ->whereIn('id', $ids)
                ->delete();
                
            if ($deleted) {
                return response()->json([
                    'success' => true, 
                    'message' => __(':count órdenes han sido eliminadas correctamente', ['count' => count($ids)])
                ]);
            } else {
                return response()->json(['success' => false, 'message' => __('Error al eliminar las órdenes')], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function import(Request $request, Customer $customer)
    {
        $this->authorize('original-order-create');

        $lockFile = storage_path('app/orders_check.lock');

        if (file_exists($lockFile)) {
            $lockTime = file_get_contents($lockFile);
            $lockAge = time() - (int)$lockTime;

            // Si el bloqueo tiene menos de 30 minutos, consideramos que otra instancia está en ejecución
            if ($lockAge < 1800) {
                $minutes = round($lockAge / 60);
                return response()->json([
                    'success' => false,
                    'message' => __('Ya hay una importación en curso desde hace {minutes} min. Por favor, inténtelo de nuevo más tarde.', ['minutes' => $minutes])
                ], 429); // HTTP 429: Too Many Requests
            }
        }

        // Ejecutamos el comando directamente en segundo plano
        try {
            // Registramos el inicio de la importación
            Log::info("Iniciando importación manual de pedidos para todos los clientes.");
            
            // Ejecutamos el comando artisan en segundo plano sin esperar respuesta
            // usando shell_exec con nohup para garantizar que siga ejecutándose
            $artisanPath = base_path('artisan');
            $logFile = storage_path('logs/orders-import-manual.log');
            $command = "nohup php {$artisanPath} orders:check > {$logFile} 2>&1 &";
            shell_exec($command);
            
            Log::info("Comando de importación iniciado en segundo plano: {$command}");
        } catch (\Exception $e) {
            Log::error("Error al ejecutar el comando de importación en segundo plano: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('Error al iniciar la importación: ') . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('La importación de pedidos se ha puesto en cola. Los datos se actualizarán en breve.')
        ]);
    }

    public function index(Request $request, Customer $customer)
    {
        // Si es una solicitud AJAX (desde DataTables)
        if ($request->ajax()) {
            // Log para depuración
            \Log::info('DataTables request', [
                'all' => $request->all(),
                'search' => $request->input('search'),
                'search.value' => $request->input('search.value'),
                'length' => $request->input('length'),
                'start' => $request->input('start'),
                'order' => $request->input('order'),
            ]);
            // Obtener parámetros de búsqueda y paginación
            $search = $request->input('search.value', ''); // Corregido: search.value es el parámetro correcto
            $perPage = $request->input('length', 10);
            $start = $request->input('start', 0);
            $orderColumnIndex = $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'asc');
            $statusFilter = $request->input('status_filter', 'all'); // Nuevo parámetro para filtrar por estado
            
            // Mapear columnas de la tabla a campos de la base de datos
            $columns = [
                0 => 'id',
                1 => 'order_id',
                2 => 'client_number',
                3 => 'processed',
                4 => 'finished_at',
                5 => 'created_at'
            ];
            
            // Iniciar la consulta con una carga más simple de relaciones
            $query = $customer->originalOrders()->with([
                'processes' => function($query) {
                    $query->withPivot('id', 'finished', 'finished_at', 'grupo_numero');
                    $query->orderBy('sequence', 'asc');
                }
            ]);
            
            // Obtener el total de registros sin filtrar
            $recordsTotal = $query->count();

            // Aplicar búsqueda si se proporciona
            if (!empty($search)) {
                $searchTerm = '%' . $search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('order_id', 'like', $searchTerm)
                      ->orWhere('client_number', 'like', $searchTerm);
                });
            }

            // Cargar todos los resultados tras búsqueda para evaluar estado por pedido
            $ordersAll = $query->get();
            \Log::info('OriginalOrders index: after search', ['status_filter' => $statusFilter, 'orders_count' => $ordersAll->count()]);

            // Cargar todas las órdenes de producción para todos los procesos de una vez (necesario para calcular estado)
            $processIdsAll = collect();
            foreach ($ordersAll as $o) {
                foreach ($o->processes as $p) {
                    $processIdsAll->push($p->pivot->id);
                }
            }
            $allProductionOrders = \App\Models\ProductionOrder::whereIn('original_order_process_id', $processIdsAll->toArray())
                ->select('id', 'original_order_process_id', 'production_line_id', 'status', 'accumulated_time')
                ->get()
                ->groupBy('original_order_process_id');

            // Función para calcular el nivel de estado de pedido (0 a 3)
            $computeStatusLevel = function($order) use ($allProductionOrders) {
                if ($order->finished_at) {
                    return 4; // Finalizado
                }
                $statusLevel = 0; // 0: Crear, 1: Planificar, 2: Asignado, 3: Iniciado/Finalizado
                foreach ($order->processes as $process) {
                    $pivotId = $process->pivot->id;
                    if ($process->pivot->finished) {
                        $statusLevel = 3;
                        break;
                    }
                    $pos = isset($allProductionOrders[$pivotId]) ? $allProductionOrders[$pivotId] : collect();
                    $isAssigned = false;
                    $isPlanned = false;
                    foreach ($pos as $po) {
                        if (isset($po->status) && $po->status === 1) {
                            $statusLevel = 3;
                            break 2;
                        }
                        if ($po->production_line_id) {
                            $isAssigned = true;
                        }
                        $isPlanned = true;
                    }
                    if ($isAssigned) {
                        $statusLevel = max($statusLevel, 2);
                    } elseif ($isPlanned) {
                        $statusLevel = max($statusLevel, 1);
                    }
                }
                return $statusLevel; // 0..3
            };

            // Aplicar filtro por estado con los nuevos valores del select
            $filtered = $ordersAll->filter(function($order) use ($statusFilter, $computeStatusLevel) {
                switch ($statusFilter) {
                    case 'finished':
                        return !is_null($order->finished_at);
                    case 'started':
                        return is_null($order->finished_at) && $computeStatusLevel($order) === 3;
                    case 'assigned':
                        return is_null($order->finished_at) && $computeStatusLevel($order) === 2;
                    case 'planned':
                        return is_null($order->finished_at) && $computeStatusLevel($order) === 1;
                    case 'to-create':
                        return is_null($order->finished_at) && $computeStatusLevel($order) === 0;
                    case 'all':
                    default:
                        return true;
                }
            });

            // Total filtrado tras aplicar estado
            $recordsFiltered = $filtered->count();
            \Log::info('OriginalOrders index: after filter', ['status_filter' => $statusFilter, 'filtered_count' => $recordsFiltered]);

            // Aplicar ordenamiento en colección
            $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'id';
            $sorted = $filtered->sortBy(function($order) use ($orderColumn) {
                // Para columnas no existentes en el modelo, fallback a id
                if ($orderColumn === 'finished_at') {
                    return $order->finished_at ? $order->finished_at->timestamp : 0;
                }
                return $order->{$orderColumn} ?? $order->id;
            }, SORT_REGULAR, $orderDir === 'desc');

            // Aplicar paginación en colección
            $orders = $sorted->slice($start, $perPage)->values();
            
            // Preparar los datos para DataTables
            $data = [];
            
            // Recolectar todos los IDs de procesos para cargar sus órdenes de producción de una vez
            $processIds = collect();
            foreach ($orders as $order) {
                foreach ($order->processes as $process) {
                    $processIds->push($process->pivot->id);
                }
            }
            
            // Cargar todas las órdenes de producción para los procesos de las órdenes paginadas (ya está cargado en $allProductionOrders)
            
            foreach ($orders as $index => $order) {
                // Generar HTML para los procesos - versión optimizada
                $processesHtml = '';
                foreach ($order->processes as $process) {
                    $pivot = $process->pivot;
                    
                    // Verificar si hay alguna orden de producción asignada o finalizada para este proceso
                    $hasAssignedOrders = isset($allProductionOrders[$pivot->id]) && $allProductionOrders[$pivot->id]->isNotEmpty();
                    
                    // Determinar la clase y título del badge según el estado original
                    if ($pivot->finished) {
                        $badgeClass = 'bg-success';
                        $statusTitle = $pivot->finished_at ? __('Finalizado') . ': ' . $pivot->finished_at->format('Y-m-d H:i') : __('Finalizado');
                    } else {
                        // Verificar si hay órdenes de producción y su estado
                        if ($hasAssignedOrders) {
                            $productionOrder = $allProductionOrders[$pivot->id]->first();
                            $status = $productionOrder ? $productionOrder->status : null;
                            $productionLineId = $productionOrder ? $productionOrder->production_line_id : null;
                            
                            if ($status === 0) {
                                if (is_null($productionLineId)) {
                                    $badgeClass = 'bg-secondary';
                                    $statusTitle = __('Sin asignar');
                                } else {
                                    $badgeClass = 'bg-info';
                                    // Añadir tiempo acumulado si existe
                                    $accumulatedTime = $productionOrder->accumulated_time ?? null;
                                    $statusTitle = __('Asignada a máquina');
                                    if ($accumulatedTime) {
                                        // Formatear el tiempo acumulado de segundos a HH:MM:SS
                                        $hours = floor($accumulatedTime / 3600);
                                        $minutes = floor(($accumulatedTime % 3600) / 60);
                                        $seconds = $accumulatedTime % 60;
                                        $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                        $statusTitle .= ' - ' . __('Tiempo acumulado') . ': ' . $formattedTime;
                                    }
                                }
                            } elseif ($status === 1) {
                                $badgeClass = 'bg-primary';
                                // Añadir tiempo acumulado si existe
                                $accumulatedTime = $productionOrder->accumulated_time ?? null;
                                $statusTitle = __('En fabricación');
                                if ($accumulatedTime) {
                                    // Formatear el tiempo acumulado de segundos a HH:MM:SS
                                    $hours = floor($accumulatedTime / 3600);
                                    $minutes = floor(($accumulatedTime % 3600) / 60);
                                    $seconds = $accumulatedTime % 60;
                                    $formattedTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                                    $statusTitle .= ' - ' . __('Tiempo acumulado') . ': ' . $formattedTime;
                                }
                            } elseif ($status > 2) {
                                $badgeClass = 'bg-danger';
                                $statusTitle = __('Con incidencia');
                            } else {
                                $badgeClass = 'bg-warning';
                                $statusTitle = __('Pendiente');
                            }
                        } else {
                            $badgeClass = 'bg-secondary';
                            $statusTitle = __('Sin asignar');
                        }
                    }
                    
                    // Crear el badge HTML con grupo_numero en formato (número)
                    $grupoNumero = $pivot->grupo_numero ? ' (' . $pivot->grupo_numero . ')' : '';
                    $processesHtml .= '<span class="badge ' . $badgeClass . ' me-1 mb-1" title="' . $statusTitle . '" data-bs-toggle="tooltip">' . $process->description . $grupoNumero . '</span>';
                }
                
                // Determinar el estado de la orden para la columna finished_at
                $finishedAtHtml = '';
                if ($order->finished_at) {
                    $finishedAtHtml = '<span class="badge bg-success">' . $order->finished_at->format('Y-m-d H:i') . '</span>';
                } else {
                    // Nueva lógica de estado del pedido con separación de 'Iniciado' y 'Asignado'
                    $hasProcessInFabricationOrFinished = false; // Azul fuerte
                    $hasProcessAssignedOnly = false; // Azul intermedio
                    $hasProcessPlannedOnly = false; // Celeste
                    $allProcessesUnplanned = true; // Gris

                    // Lógica de estado jerárquica basada en la lógica de los badges de proceso
                    $statusLevel = 0; // 0: Crear, 1: Planificar, 2: Asignado, 3: Iniciado/Finalizado

                    foreach ($order->processes as $process) {
                        $pivot = $process->pivot;

                        // Nivel 3: Proceso finalizado (prioridad máxima)
                        if ($pivot->finished) {
                            $statusLevel = 3;
                            break; // Encontramos el estado más alto, no es necesario seguir
                        }

                        $hasProductionOrder = isset($allProductionOrders[$pivot->id]) && $allProductionOrders[$pivot->id]->isNotEmpty();
                        if ($hasProductionOrder) {
                            $isAssigned = false;
                            $isPlanned = false;

                            foreach ($allProductionOrders[$pivot->id] as $po) {
                                // Nivel 3: En fabricación (status === 1)
                                if (isset($po->status) && $po->status === 1) {
                                    $statusLevel = 3;
                                    break 2; // Salir de ambos bucles, máxima prioridad encontrada
                                }
                                // Nivel 2: Asignado a máquina
                                if ($po->production_line_id) {
                                    $isAssigned = true;
                                }
                                $isPlanned = true;
                            }

                            if ($isAssigned) {
                                $statusLevel = max($statusLevel, 2);
                            } elseif ($isPlanned) {
                                // Nivel 1: Planificado pero no asignado
                                $statusLevel = max($statusLevel, 1);
                            }
                        }
                    }

                    // Asignar el badge según el nivel de estado final
                    if ($statusLevel == 3) {
                        $finishedAtHtml = '<span class="badge bg-primary">' . __('Pedido Iniciado') . '</span>';
                    } elseif ($statusLevel == 2) {
                        $finishedAtHtml = '<span class="badge bg-info">' . __('Asignado a máquina') . '</span>';
                    } elseif ($statusLevel == 1) {
                        $finishedAtHtml = '<span class="badge bg-secondary">' . __('Pendiente Planificar') . '</span>';
                    } else {
                        $finishedAtHtml = '<span class="badge bg-danger">' . __('Pendiente Crear') . '</span>';
                    }
                }
                
                $data[] = [
                    'id' => $order->id,
                    'checkbox' => '', // Campo vacío para la columna checkbox (se renderiza con el ID)
                    'DT_RowIndex' => $start + $index + 1,
                    'order_id' => $order->order_id,
                    'client_number' => $order->client_number,
                    'processes' => $processesHtml,
                    'finished_at' => $finishedAtHtml,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                    'actions' => view('customers.original-orders.partials.actions', ['customer' => $customer, 'order' => $order])->render()
                ];
            }
            
            // Preparar la respuesta para DataTables
            $response = [
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ];
            
            // Log para depurar la respuesta
            //\Log::info('DataTables response structure:', [
            //    'draw' => $response['draw'],
            //    'recordsTotal' => $response['recordsTotal'],
            //    'recordsFiltered' => $response['recordsFiltered'],
            //    'data_count' => count($response['data']),
            //    'first_item' => !empty($response['data']) ? json_encode($response['data'][0]) : 'No data'
            //]);
            
            try {
                // Intentar codificar a JSON para detectar errores
                $jsonResponse = json_encode($response);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    \Log::error('JSON encoding error: ' . json_last_error_msg());
                    // Si hay error de codificación, intentar identificar el problema
                    foreach ($data as $index => $item) {
                        $itemJson = json_encode($item);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            \Log::error('Error en item #' . $index . ': ' . json_last_error_msg());
                            // Intentar identificar qué campo causa el problema
                            foreach ($item as $key => $value) {
                                $fieldJson = json_encode($value);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    \Log::error('Campo problemático: ' . $key . ' - Error: ' . json_last_error_msg());
                                }
                            }
                        }
                    }
                    
                    // Devolver una respuesta simplificada en caso de error
                    return response()->json([
                        'draw' => intval($request->input('draw')),
                        'recordsTotal' => $recordsTotal,
                        'recordsFiltered' => $recordsFiltered,
                        'data' => [],
                        'error' => 'Error en la codificación JSON: ' . json_last_error_msg()
                    ]);
                }
                
                return response()->json($response);
            } catch (\Exception $e) {
                \Log::error('Exception en la respuesta JSON: ' . $e->getMessage());
                return response()->json([
                    'draw' => intval($request->input('draw')),
                    'recordsTotal' => $recordsTotal,
                    'recordsFiltered' => $recordsFiltered,
                    'data' => [],
                    'error' => 'Error en el servidor: ' . $e->getMessage()
                ]);
            }
        }
        
        // Para solicitudes normales, devolver la vista
        // No necesitamos cargar órdenes aquí ya que se cargarán vía AJAX
        return view('customers.original-orders.index', compact('customer'));
    }

    public function create(Customer $customer)
    {
        $processes = Process::all();
        return view('customers.original-orders.create', [
            'customer' => $customer,
            'processes' => $processes
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'order_id' => 'required|unique:original_orders,order_id',
            'client_number' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'required|array',
            'processes.*' => 'exists:processes,id',
        ]);

        $originalOrder = $customer->originalOrders()->create([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'order_details' => $validated['order_details'],
            'processed' => false,
        ]);

        // Attach processes con cálculo de tiempo
        $processData = [];
        $orderDetails = json_decode($validated['order_details'], true);
        
        foreach ($validated['processes'] as $processId) {
            $process = Process::findOrFail($processId);
            $time = 0;
            
            // Buscar la cantidad en los detalles del pedido
            if (isset($orderDetails['grupos'])) {
                foreach ($orderDetails['grupos'] as $grupo) {
                    foreach ($grupo['servicios'] ?? [] as $servicio) {
                        if ($servicio['CodigoArticulo'] === $process->code) {
                            $cantidad = (float) $servicio['Cantidad'];
                            $time = $cantidad * $process->factor_correccion;
                            break 2; // Salir de ambos bucles
                        }
                    }
                }
            }
            
            $processData[$processId] = [
                'time' => $time,
                'created' => false,
                'finished' => false,
            ];
        }
        $originalOrder->processes()->sync($processData);

        return redirect()->route('customers.original-orders.index', $customer->id)
            ->with('success', 'Original order created successfully');
    }

    public function show(Customer $customer, OriginalOrder $originalOrder)
    {
        // Cargar procesos con todos los campos pivot
        $originalOrder->load(['processes' => function($query) {
            $query->withPivot('id', 'time', 'created', 'finished', 'finished_at', 'in_stock');
        }]);
        
        // Cargar productionOrders para cada proceso
        $originalOrder->processes->each(function($process) {
            $process->pivot->load(['productionOrders' => function($query) {
                $query->select('id', 'original_order_process_id', 'status', 'production_line_id', 'accumulated_time', 'estimated_start_datetime', 'estimated_end_datetime');
            }]);
        });
        
        // Depurar los procesos cargados
        \Log::info('Procesos cargados para la orden ' . $originalOrder->id . ':');
        foreach ($originalOrder->processes as $process) {
            \Log::info("Proceso ID: {$process->id}, Código: {$process->code}, finished: " . 
                      ($process->pivot->finished ? 'true' : 'false') . 
                      ", finished_at: " . ($process->pivot->finished_at ?? 'null'));
        }
        
        return view('customers.original-orders.show', compact('customer', 'originalOrder'));
    }

    public function edit(Customer $customer, OriginalOrder $originalOrder)
    {
        $processes = Process::all();
        
        // Cargar procesos con todos los campos pivot explícitamente
        $originalOrder->load(['processes' => function($query) {
            $query->withPivot('id', 'time', 'created', 'finished', 'finished_at');
        }]);
        
        // Preparar los artículos para cada proceso
        $articlesData = [];
        
        foreach ($originalOrder->processes as $process) {
            $pivotId = $process->pivot->id;
            
            // Cargar los artículos para este proceso
            $articles = \App\Models\OriginalOrderArticle::where('original_order_process_id', $pivotId)->get();
            
            // Si hay artículos, los agregamos al array
            if ($articles->count() > 0) {
                $articlesData[$pivotId] = $articles->map(function($article) {
                    return [
                        'id' => $article->id,
                        'code' => $article->codigo_articulo,
                        'description' => $article->descripcion_articulo,
                        'group' => $article->grupo_articulo
                    ];
                })->toArray();
            }
        }
        
        // Depurar los procesos cargados
        \Log::info('Procesos cargados para edición de la orden ' . $originalOrder->id . ':');
        foreach ($originalOrder->processes as $process) {
            \Log::info("Proceso ID: {$process->id}, Código: {$process->code}, finished: " . 
                      ($process->pivot->finished ? 'true' : 'false') . 
                      ", finished_at: " . ($process->pivot->finished_at ?? 'null'));
        }
        
        $selectedProcesses = $originalOrder->processes->pluck('id')->toArray();
        
        return view('customers.original-orders.edit', [
            'customer' => $customer,
            'originalOrder' => $originalOrder,
            'processes' => $processes,
            'selectedProcesses' => $selectedProcesses,
            'articlesData' => json_encode($articlesData)
        ]);
    }

    /**
     * Vista: Procesos de pedidos originales finalizados por rango de fechas
     */
    public function finishedProcessesView(Request $request, Customer $customer)
    {
        return view('customers.original-orders.finished-processes', compact('customer'));
    }

    /**
     * DataTables API: Procesos finalizados filtrados por fecha de finalización
     */
    public function finishedProcessesData(Request $request, Customer $customer)
    {
        // Parámetros DataTables
        $search = $request->input('search.value', '');
        $perPage = (int) $request->input('length', 10);
        $start = (int) $request->input('start', 0);
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        $dateFrom = $request->input('date_from'); // formato YYYY-MM-DD
        $dateTo = $request->input('date_to');     // formato YYYY-MM-DD

        $columns = [
            0 => 'id',
            1 => 'finished_at',
            2 => 'order_id',
            3 => 'process_description',
            4 => 'grupo_numero',
        ];

        // Base query: procesos finalizados del cliente
        $baseQuery = OriginalOrderProcess::query()
            ->whereHas('originalOrder', function($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->whereNotNull('finished_at');

        $recordsTotal = (clone $baseQuery)->count();

        // Aplicar filtro de rango de fechas (si se proveen)
        if ($dateFrom) {
            $baseQuery->whereDate('finished_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseQuery->whereDate('finished_at', '<=', $dateTo);
        }

        // Búsqueda por texto en order_id o descripción de proceso
        if (!empty($search)) {
            $baseQuery->where(function($q) use ($search) {
                $q->whereHas('originalOrder', function($q2) use ($search) {
                    $q2->where('order_id', 'like', '%' . $search . '%');
                })
                ->orWhereHas('process', function($q3) use ($search) {
                    $q3->where('description', 'like', '%' . $search . '%')
                       ->orWhere('code', 'like', '%' . $search . '%');
                });
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();

        // Ordenar
        $orderColumn = $columns[$orderColumnIndex] ?? 'finished_at';
        if ($orderColumn === 'order_id') {
            // Orden por order_id via relación
            $baseQuery->join('original_orders as oo', 'oo.id', '=', 'original_order_processes.original_order_id')
                      ->orderBy('oo.order_id', $orderDir)
                      ->select('original_order_processes.*');
        } elseif ($orderColumn === 'process_description') {
            $baseQuery->join('processes as p', 'p.id', '=', 'original_order_processes.process_id')
                      ->orderBy('p.description', $orderDir)
                      ->select('original_order_processes.*');
        } else {
            $baseQuery->orderBy($orderColumn, $orderDir);
        }

        // Paginación
        $items = $baseQuery
            ->with(['originalOrder:id,order_id', 'process:id,description,code', 'articles'])
            ->skip($start)
            ->take($perPage)
            ->get();

        // Preparar respuesta
        $data = [];
        foreach ($items as $index => $item) {
            // Mapear artículos relacionados del pivot/modelo
            $articles = [];
            if (method_exists($item, 'articles')) {
                foreach ($item->articles as $article) {
                    $articles[] = [
                        'codigo_articulo' => $article->codigo_articulo ?? '',
                        'descripcion_articulo' => $article->descripcion_articulo ?? '',
                        'grupo_articulo' => $article->grupo_articulo ?? '',
                        'in_stock' => $article->in_stock ?? null,
                    ];
                }
            }
            $data[] = [
                'details' => '<button type="button" class="btn btn-sm btn-outline-secondary details-control" title="Artículos relacionados"><i class="fas fa-cubes"></i></button>',
                'DT_RowIndex' => $start + $index + 1,
                'finished_at' => optional($item->finished_at)->format('Y-m-d H:i'),
                'order_id' => optional($item->originalOrder)->order_id,
                'process' => ($item->process?->description ?? '-') . (isset($item->process?->code) ? ' ['.$item->process->code.']' : ''),
                'grupo_numero' => $item->grupo_numero,
                'articles' => $articles,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function update(Request $request, Customer $customer, OriginalOrder $originalOrder)
    {
        // 1. Validar la petición.
        $validated = $request->validate([
            'order_id' => 'required|unique:original_orders,order_id,' . $originalOrder->id,
            'client_number' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'sometimes|array',
            'processes.*' => 'exists:processes,id',
            'finished' => 'sometimes|array',
            'processed' => 'nullable|boolean',
            'articles' => 'sometimes|array',
            'order_finished' => 'nullable|boolean',
            'finished_at' => 'nullable|date',
        ]);

        // 2. Actualizar los campos principales de la orden.
        $originalOrder->update([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'order_details' => $validated['order_details'],
            'processed' => $request->boolean('processed'),
            'finished_at' => $request->boolean('order_finished') ? ($request->input('finished_at') ?: now()) : null,
        ]);

        // 3. Obtener datos del formulario.
        $selectedProcesses = $request->input('processes', []);
        $finishedProcesses = $request->input('finished', []);
        $articlesData = $request->input('articles', []);
        $orderDetails = json_decode($validated['order_details'], true);
        
        // Obtener los procesos actuales para preservar IDs
        $existingProcesses = $originalOrder->processes()->withPivot('id')->get()->keyBy('pivot.id');
        $existingProcessIds = $existingProcesses->pluck('id', 'pivot.id')->toArray();
        
        // Recolectar IDs para mantener, actualizar o eliminar
        $processIdsToKeep = [];
        $processesToUpdate = [];
        $processesToCreate = [];
        
        // Clasificar los procesos seleccionados
        foreach ($selectedProcesses as $uniqueId => $processId) {
            // Manejar IDs nuevos (con prefijo 'new_')
            if (!is_numeric($processId) && strpos($processId, 'new_') === 0) {
                $processId = substr($processId, 4);
            }
            
            $process = \App\Models\Process::find($processId);
            if (!$process) continue;
            
            // Calcular tiempo basado en los detalles del pedido
            $time = 0;
            if (isset($orderDetails['grupos'])) {
                foreach ($orderDetails['grupos'] as $grupo) {
                    foreach ($grupo['servicios'] ?? [] as $servicio) {
                        if ($servicio['CodigoArticulo'] === $process->code) {
                            $cantidad = (float) $servicio['Cantidad'];
                            $time = $cantidad * $process->factor_correccion;
                            break 2;
                        }
                    }
                }
            }
            
            $isFinished = isset($finishedProcesses[$uniqueId]);
            
            $pivotData = [
                'time' => $time,
                'created' => true,
                'finished' => $isFinished,
                'finished_at' => $isFinished ? now() : null,
                'updated_at' => now()
            ];
            
            // Si el uniqueId es numérico, podría ser un ID existente
            if (is_numeric($uniqueId) && isset($existingProcessIds[$uniqueId]) && $existingProcessIds[$uniqueId] == $processId) {
                // Es un proceso existente, actualizarlo
                $processIdsToKeep[] = $uniqueId;
                $processesToUpdate[$uniqueId] = $pivotData;
            } else {
                // Es un proceso nuevo, crear
                $processesToCreate[$uniqueId] = [
                    'process_id' => $processId,
                    'data' => array_merge($pivotData, ['created_at' => now()])
                ];
            }
        }
        
        // 1. Eliminar procesos que ya no están en la selección
        $idsToDelete = array_diff(array_keys($existingProcessIds), $processIdsToKeep);
        if (!empty($idsToDelete)) {
            \DB::table('original_order_processes')->whereIn('id', $idsToDelete)->delete();
        }
        
        // 2. Actualizar procesos existentes
        foreach ($processesToUpdate as $pivotId => $data) {
            \DB::table('original_order_processes')->where('id', $pivotId)->update($data);
        }
        
        // 3. Crear nuevos procesos
        $processedPivotIds = []; // Mapeará los IDs del formulario a los IDs de la BD
        foreach ($processesToCreate as $formId => $processData) {
            $pivotId = \DB::table('original_order_processes')->insertGetId(
                array_merge(
                    [
                        'original_order_id' => $originalOrder->id, 
                        'process_id' => $processData['process_id']
                    ],
                    $processData['data']
                )
            );
            $processedPivotIds[$formId] = $pivotId;
        }
        
        // Mantener IDs existentes en el mapeo
        foreach ($processIdsToKeep as $existingId) {
            $processedPivotIds[$existingId] = $existingId;
        }
        
        // 4. Recargar los procesos para manejar artículos
        $originalOrder->load(['processes' => function($query) {
            $query->withPivot('id');
        }]);
        
        // 5. Manejar artículos
        if (is_array($articlesData)) {
            foreach ($articlesData as $formUniqueId => $articles) {
                // Obtener el ID real del proceso (existente o nuevo)
                if (!isset($processedPivotIds[$formUniqueId])) {
                    continue;
                }
                
                $pivotId = $processedPivotIds[$formUniqueId];
                $processInstance = $originalOrder->processes->firstWhere('pivot.id', $pivotId);
                
                if (!$processInstance) continue;
                
                // Obtener artículos existentes para este proceso
                $existingArticles = $processInstance->pivot->articles()->get()->keyBy('id');
                $existingArticleIds = $existingArticles->pluck('id')->toArray();
                $articleIdsToKeep = [];
                
                // Procesar artículos
                foreach ($articles as $index => $articleData) {
                    if (empty($articleData['code'])) continue;
                    
                    $articleId = $articleData['id'] ?? null;
                    
                    // Si tiene ID y existe, actualizar
                    if ($articleId && isset($existingArticles[$articleId])) {
                        $articleIdsToKeep[] = $articleId;
                        $existingArticles[$articleId]->update([
                            'codigo_articulo' => $articleData['code'] ?? '',
                            'descripcion_articulo' => $articleData['description'] ?? '',
                            'grupo_articulo' => $articleData['group'] ?? ''
                        ]);
                    } else {
                        // Si no tiene ID o no existe, crear nuevo
                        $processInstance->pivot->articles()->create([
                            'codigo_articulo' => $articleData['code'] ?? '',
                            'descripcion_articulo' => $articleData['description'] ?? '',
                            'grupo_articulo' => $articleData['group'] ?? ''
                        ]);
                    }
                }
                
                // Eliminar artículos que ya no están en la lista
                $articleIdsToDelete = array_diff($existingArticleIds, $articleIdsToKeep);
                if (!empty($articleIdsToDelete)) {
                    \App\Models\OriginalOrderArticle::whereIn('id', $articleIdsToDelete)->delete();
                }
            }
        }
        
        return redirect()->route('customers.original-orders.index', $customer->id)
            ->with('success', 'Original order updated successfully');
    }

    public function destroy(Customer $customer, OriginalOrder $originalOrder)
    {
        // Usamos una transacción para asegurar que o todo funciona, o nada se borra.
        DB::transaction(function () use ($originalOrder) {
            
            // 1. PRIMERO: Busca y borra los "hijos" (ProductionOrders)
            //    (Esto asume que tienes la relación "productionOrders" en tu modelo OriginalOrder)
            $originalOrder->productionOrders()->delete();
    
            // 2. AHORA SÍ: Borra el "padre" (OriginalOrder)
            $originalOrder->delete();
    
        });
    
        return redirect()->route('customers.original-orders.index', $customer->id)
            ->with('success', 'Orden original y sus dependencias borradas con éxito');
    }
    
    /**
     * Ejecuta el comando Artisan orders:list-stock para crear tarjetas
     *
     * @param Customer $customer
     * @return \Illuminate\Http\JsonResponse
     */
    public function createCards(Customer $customer)
    {
        try {
            // Ejecutar el comando Artisan
            $exitCode = \Artisan::call('orders:list-stock');
            
            // Obtener la salida del comando
            $output = \Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => __('Tarjetas creadas correctamente'),
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error al crear tarjetas: ') . $e->getMessage()
            ], 500);
        }
    }
}
