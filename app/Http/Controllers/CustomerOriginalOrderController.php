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
use App\Models\RouteName;
use App\Models\WorkCalendar;
use Symfony\Component\Process\Process as SymfonyProcess;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
        // Permisos para production-times y sus endpoints
        $this->middleware('permission:original-order-list', ['only' => ['productionTimesView', 'productionTimesData', 'productionTimesSummary', 'productionTimesOrderDetail']]);
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
            'route_name' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'required|array|min:1',
            'processes.*' => 'exists:processes,id',
            'process_times' => 'required|array',
            'process_times.*' => 'required|numeric|min:0.01',
        ]);

        // Resolver route_name_id si se proporciona
        $routeNameId = $this->resolveRouteName($customer, $validated['route_name'] ?? null);

        $originalOrder = $customer->originalOrders()->create([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'route_name_id' => $routeNameId,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'in_stock' => $request->boolean('in_stock'),
            'order_details' => $validated['order_details'],
            'processed' => false,
        ]);

        // Attach processes con cálculo de tiempo
        $processData = [];
        $processTimes = $request->input('process_times', []);
        $orderDetails = json_decode($validated['order_details'], true);
        
        foreach ($validated['processes'] as $uniqueId => $processId) {
            $process = Process::findOrFail($processId);
            $time = isset($processTimes[$uniqueId]) ? (float) $processTimes[$uniqueId] : 0;

            if ($time <= 0 && isset($orderDetails['grupos'])) {
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

            if ($time <= 0) {
                $time = (float) env('DEFAULT_PROCESS_TIME', 1);
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
            $query->withPivot('id', 'time', 'created', 'finished', 'finished_at', 'in_stock', 'box', 'units_box', 'number_of_pallets');
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
            $query->withPivot('id', 'time', 'created', 'finished', 'finished_at', 'box', 'units_box', 'number_of_pallets');
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
            5 => 'box',
            6 => 'units_box',
            7 => 'number_of_pallets',
            // Nota: total_units es calculado, no columna directa
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
                'box' => $item->box,
                'units_box' => $item->units_box,
                'total_units' => ($item->box && $item->units_box) ? ($item->box * $item->units_box) : null,
                'number_of_pallets' => $item->number_of_pallets,
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

    public function productionTimesView(Customer $customer)
    {
        $tz = config('app.timezone');
        $defaultEnd = Carbon::now($tz);
        $defaultStart = (clone $defaultEnd)->subDays(30);

        $processOptions = Process::orderBy('sequence')->get(['id', 'code', 'description']);
        $grupoOptions = OriginalOrderProcess::query()
            ->whereHas('originalOrder', fn($q) => $q->where('customer_id', $customer->id))
            ->select('grupo_numero')
            ->whereNotNull('grupo_numero')
            ->groupBy('grupo_numero')
            ->orderBy('grupo_numero')
            ->pluck('grupo_numero');

        return view('customers.original-orders.production-times', [
            'customer' => $customer,
            'processOptions' => $processOptions,
            'grupoOptions' => $grupoOptions,
            'defaultStart' => $defaultStart->format('Y-m-d'),
            'defaultEnd' => $defaultEnd->format('Y-m-d'),
        ]);
    }

    public function productionTimesData(Request $request, Customer $customer)
    {
        $filters = $this->validateProductionTimeFilters($request);

        $ordersQuery = $this->buildProductionTimesBaseQuery($customer, $filters);

        $recordsTotal = (clone $ordersQuery)->count();

        $orders = $ordersQuery
            ->with(['customerClient:id,name', 'routeName:id,name', 'originalOrderProcesses' => function ($query) use ($filters) {
                $query->with(['process:id,code,description,sequence', 'productionOrders:id,original_order_process_id,status,finished_at'])
                    ->orderBy('grupo_numero')
                    ->orderBy('id');

                if (!empty($filters['process_ids'])) {
                    $query->whereIn('process_id', $filters['process_ids']);
                }

                if (!empty($filters['grupo_numeros'])) {
                    $query->whereIn('grupo_numero', $filters['grupo_numeros']);
                }
            }])
            ->orderByDesc('finished_at')
            ->orderByDesc('created_at')
            ->get();

        $transformed = $this->transformProductionTimeOrders($orders, $filters);

        return response()->json([
            'data' => $transformed['rows'],
            'recordsTotal' => $recordsTotal,
            'summary' => $transformed['summary'],
        ]);
    }

    public function productionTimesSummary(Request $request, Customer $customer)
    {
        $filters = $this->validateProductionTimeFilters($request);

        $ordersQuery = $this->buildProductionTimesBaseQuery($customer, $filters);

        $orders = $ordersQuery
            ->with(['routeName:id,name', 'originalOrderProcesses' => function ($query) use ($filters) {
                $query->select('id', 'original_order_id', 'process_id', 'grupo_numero', 'finished_at', 'created', 'created_at', 'time')
                    ->orderBy('grupo_numero')
                    ->orderBy('id');

                if (!empty($filters['process_ids'])) {
                    $query->whereIn('process_id', $filters['process_ids']);
                }

                if (!empty($filters['grupo_numeros'])) {
                    $query->whereIn('grupo_numero', $filters['grupo_numeros']);
                }
            }, 'originalOrderProcesses.process:id,code,description'])
            ->orderByDesc('finished_at')
            ->orderByDesc('created_at')
            ->get();

        $summary = $this->buildProductionTimesSummary($orders, $filters);

        return response()->json($summary);
    }

    public function productionTimesOrderDetail(Customer $customer, OriginalOrder $originalOrder, Request $request)
    {
        $filters = $this->validateProductionTimeFilters($request, false);

        abort_unless($originalOrder->customer_id === $customer->id, 404);

        $originalOrder->load(['originalOrderProcesses' => function ($query) use ($filters) {
            $query->with(['process:id,code,description,sequence'])
                ->with(['productionOrders:id,original_order_process_id,status,finished_at'])
                ->orderBy('grupo_numero')
                ->orderBy('id');

            if (!empty($filters['process_ids'])) {
                $query->whereIn('process_id', $filters['process_ids']);
            }

            if (!empty($filters['grupo_numeros'])) {
                $query->whereIn('grupo_numero', $filters['grupo_numeros']);
            }
        }, 'customerClient:id,name']);

        $detail = $this->transformSingleOrderProductionTimes($originalOrder, $filters);
        $detail['average_timeline'] = $this->computeAverageTimeline($customer, $filters);
        $detail['median_timeline'] = $this->computeMedianTimeline($customer, $filters);
        $detail['average_timeline_working'] = $this->computeAverageTimelineWorkingDays($customer, $filters);
        $detail['median_timeline_working'] = $this->computeMedianTimelineWorkingDays($customer, $filters);
        $detail['use_actual_delivery'] = (bool)($filters['use_actual_delivery'] ?? false);

        \Log::info('PT order_detail payload', [
            'order_id' => $originalOrder->id,
            'has_order_timeline' => !empty($detail['order_timeline']),
            'has_average_timeline' => !empty($detail['average_timeline']),
            'has_median_timeline' => !empty($detail['median_timeline']),
            'use_actual_delivery' => $detail['use_actual_delivery'],
        ]);

        return response()->json($detail);
    }

    protected function validateProductionTimeFilters(Request $request, bool $requireDates = true): array
    {
        $rules = [
            'date_start' => $requireDates ? ['required', 'date'] : ['nullable', 'date'],
            'date_end' => $requireDates ? ['required', 'date'] : ['nullable', 'date'],
            'process_ids' => ['nullable', 'array'],
            'process_ids.*' => ['integer', 'exists:processes,id'],
            'grupo_numeros' => ['nullable', 'array'],
            'grupo_numeros.*' => ['integer'],
            'use_actual_delivery' => ['nullable', 'boolean'],
            'filter_delivery_dates' => ['nullable', 'boolean'],
            'exclude_incomplete_orders' => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules);
        $tz = config('app.timezone');

        $dateStart = isset($validated['date_start'])
            ? Carbon::parse($validated['date_start'], $tz)->startOfDay()
            : Carbon::now($tz)->subDays(30)->startOfDay();
        $dateEnd = isset($validated['date_end'])
            ? Carbon::parse($validated['date_end'], $tz)->endOfDay()
            : Carbon::now($tz)->endOfDay();

        if ($dateEnd->diffInDays($dateStart) > 180) {
            $dateStart = (clone $dateEnd)->subDays(180);
        }

        return [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'process_ids' => $validated['process_ids'] ?? [],
            'grupo_numeros' => $validated['grupo_numeros'] ?? [],
            'use_actual_delivery' => (bool)($validated['use_actual_delivery'] ?? false),
            'filter_delivery_dates' => (bool)($validated['filter_delivery_dates'] ?? false),
            'exclude_incomplete_orders' => (bool)($validated['exclude_incomplete_orders'] ?? true),
        ];
    }

    protected function buildProductionTimesBaseQuery(Customer $customer, array $filters)
    {
        $query = OriginalOrder::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('finished_at')
            ->whereBetween('finished_at', [
                $filters['date_start'],
                $filters['date_end'],
            ]);

        if (!empty($filters['filter_delivery_dates'])) {
            if (!empty($filters['use_actual_delivery'])) {
                $query->where(function ($q) use ($filters) {
                    $q->whereNull('actual_delivery_date')
                      ->orWhereBetween('actual_delivery_date', [$filters['date_start'], $filters['date_end']]);
                });
            } else {
                $query->where(function ($q) use ($filters) {
                    $q->whereNull('delivery_date')
                      ->orWhereBetween('delivery_date', [$filters['date_start'], $filters['date_end']]);
                });
            }
        }

        // Excluir órdenes con fechas incompletas (ERP, creado o finalizado) si se solicita
        if (!empty($filters['exclude_incomplete_orders'])) {
            $query->whereNotNull('fecha_pedido_erp')
                  ->whereNotNull('created_at')
                  ->whereNotNull('finished_at');
        }

        return $query;
    }

    protected function transformProductionTimeOrders(Collection $orders, array $filters): array
    {
        $rows = [];
        $orderSummary = new ProductionTimeSummary();

        foreach ($orders as $order) {
            $row = $this->transformSingleOrderProductionTimes($order, $filters);

            $rows[] = $row;

            $orderSummary->pushOrder($row);

            if (!empty($row['processes'])) {
                foreach ($row['processes'] as $processRow) {
                    $orderSummary->pushProcess($processRow);
                }
            }
        }

        return [
            'rows' => $rows,
            'summary' => $orderSummary->toArray(),
        ];
    }

    protected function transformSingleOrderProductionTimes(OriginalOrder $order, array $filters): array
    {
        $tz = config('app.timezone');
        $erpDate = $order->fecha_pedido_erp ? Carbon::parse($order->fecha_pedido_erp, $tz) : null;
        $createdAt = $order->created_at ? $order->created_at->copy()->timezone($tz) : null;
        $finishedAt = $order->finished_at ? $order->finished_at->copy()->timezone($tz) : null;
        $selectedDelivery = ($filters['use_actual_delivery'] ?? false) ? $order->actual_delivery_date : $order->delivery_date;
        $deliveryDate = $selectedDelivery ? $selectedDelivery->copy()->timezone($tz)->endOfDay() : null;

        $timeline = new ProductionTimeTimeline($erpDate ?? $createdAt ?? Carbon::now($tz));

        if ($erpDate) {
            $timeline->addMilestone('ERP', $erpDate);
        }

        if ($createdAt) {
            $timeline->addMilestone('CREATED', $createdAt);
        }

        if ($finishedAt) {
            $timeline->addMilestone('FINISHED', $finishedAt);
        }

        $processRows = [];
        $groupMetrics = [];
        $processesByGroup = $order->originalOrderProcesses
            ->groupBy(fn($process) => $process->grupo_numero ?? 'SIN_GRUPO');

        foreach ($processesByGroup as $grupoNumero => $groupProcesses) {
            $previousFinished = $createdAt;
            $groupDurationSum = 0;
            $groupGapSum = 0;
            $groupProcCount = 0;
            $groupFirstFinish = null;
            $groupLastFinish = null;

            foreach ($groupProcesses->sortBy('process.sequence') as $pivot) {
                $process = $pivot->process;
                $pivotFinished = $pivot->finished_at ? Carbon::parse($pivot->finished_at, $tz) : null;

                $durationSeconds = $pivot->time ? (int)$pivot->time : null;

                if (!$durationSeconds && $previousFinished && $pivotFinished) {
                    $durationSeconds = $previousFinished->diffInSeconds($pivotFinished, false);
                }

                $gapSeconds = null;
                if ($previousFinished && $pivotFinished) {
                    $gapSeconds = $previousFinished->diffInSeconds($pivotFinished, false) - ($durationSeconds ?? 0);
                    if ($gapSeconds < 0) {
                        $gapSeconds = 0;
                    }
                }

                if ($durationSeconds !== null) { $groupDurationSum += $durationSeconds; }
                if ($gapSeconds !== null) { $groupGapSum += $gapSeconds; }
                $groupProcCount++;
                if ($pivotFinished) {
                    if (!$groupFirstFinish || $pivotFinished->lt($groupFirstFinish)) { $groupFirstFinish = $pivotFinished; }
                    if (!$groupLastFinish || $pivotFinished->gt($groupLastFinish)) { $groupLastFinish = $pivotFinished; }
                }

                $erpToProcess = ($erpDate && $pivotFinished)
                    ? $erpDate->diffInSeconds($pivotFinished, false)
                    : null;
                $createdToProcess = ($createdAt && $pivotFinished)
                    ? $createdAt->diffInSeconds($pivotFinished, false)
                    : null;

                $processRows[] = [
                    'id' => $pivot->id,
                    'process_code' => $process?->code,
                    'process_name' => $process?->description,
                    'grupo_numero' => $pivot->grupo_numero,
                    'finished_at' => optional($pivotFinished)->format('Y-m-d H:i:s'),
                    'finished_at_ts' => optional($pivotFinished)?->timestamp,
                    'duration_seconds' => $durationSeconds,
                    'gap_seconds' => $gapSeconds,
                    'duration_formatted' => $this->formatSeconds($durationSeconds),
                    'gap_formatted' => $this->formatSeconds($gapSeconds),
                    'erp_to_process_seconds' => $erpToProcess,
                    'erp_to_process_formatted' => $this->formatSeconds($erpToProcess),
                    'created_to_process_seconds' => $createdToProcess,
                    'created_to_process_formatted' => $this->formatSeconds($createdToProcess),
                ];

                if ($pivotFinished) {
                    $timeline->addMilestone('PROC_'.$pivot->id, $pivotFinished);
                }

                if ($pivotFinished) {
                    $previousFinished = $pivotFinished;
                }
            }

            $groupSpanSeconds = ($groupFirstFinish && $groupLastFinish)
                ? max(0, $groupFirstFinish->diffInSeconds($groupLastFinish, false))
                : null;
            $groupMetrics[(string)$grupoNumero] = [
                'process_count' => $groupProcCount,
                'duration_sum_seconds' => $groupDurationSum,
                'gap_sum_seconds' => $groupGapSum,
                'span_seconds' => $groupSpanSeconds,
                'first_finished_at' => optional($groupFirstFinish)->format('Y-m-d H:i:s'),
                'last_finished_at' => optional($groupLastFinish)->format('Y-m-d H:i:s'),
            ];
        }

        $diff = function ($start, $end) {
            if (!$start || !$end) {
                return null;
            }

            $seconds = $start->diffInSeconds($end, false);
            return $seconds < 0 ? 0 : $seconds;
        };

        $erpToCreated = $diff($erpDate, $createdAt);
        $erpToFinished = $diff($erpDate, $finishedAt);
        $createdToFinished = $diff($createdAt, $finishedAt);
        $erpToDelivery = $diff($erpDate, $deliveryDate);
        $createdToDelivery = $diff($createdAt, $deliveryDate);
        $finishedToDelivery = $diff($finishedAt, $deliveryDate);

        // Calcular días laborables usando el calendario del cliente
        $customerId = $order->customer_id;
        $erpToCreatedWorkingDays = ($erpDate && $createdAt) ? WorkCalendar::getWorkingDaysBetween($customerId, $erpDate, $createdAt) : null;
        $erpToCreatedNonWorkingDays = ($erpDate && $createdAt) ? WorkCalendar::getNonWorkingDaysBetween($customerId, $erpDate, $createdAt) : null;

        $erpToFinishedWorkingDays = ($erpDate && $finishedAt) ? WorkCalendar::getWorkingDaysBetween($customerId, $erpDate, $finishedAt) : null;
        $erpToFinishedNonWorkingDays = ($erpDate && $finishedAt) ? WorkCalendar::getNonWorkingDaysBetween($customerId, $erpDate, $finishedAt) : null;

        $createdToFinishedWorkingDays = ($createdAt && $finishedAt) ? WorkCalendar::getWorkingDaysBetween($customerId, $createdAt, $finishedAt) : null;
        $createdToFinishedNonWorkingDays = ($createdAt && $finishedAt) ? WorkCalendar::getNonWorkingDaysBetween($customerId, $createdAt, $finishedAt) : null;

        $erpToDeliveryWorkingDays = ($erpDate && $deliveryDate) ? WorkCalendar::getWorkingDaysBetween($customerId, $erpDate, $deliveryDate) : null;
        $erpToDeliveryNonWorkingDays = ($erpDate && $deliveryDate) ? WorkCalendar::getNonWorkingDaysBetween($customerId, $erpDate, $deliveryDate) : null;

        $createdToDeliveryWorkingDays = ($createdAt && $deliveryDate) ? WorkCalendar::getWorkingDaysBetween($customerId, $createdAt, $deliveryDate) : null;
        $createdToDeliveryNonWorkingDays = ($createdAt && $deliveryDate) ? WorkCalendar::getNonWorkingDaysBetween($customerId, $createdAt, $deliveryDate) : null;

        $finishedToDeliveryWorkingDays = ($finishedAt && $deliveryDate) ? WorkCalendar::getWorkingDaysBetween($customerId, $finishedAt, $deliveryDate) : null;
        $finishedToDeliveryNonWorkingDays = ($finishedAt && $deliveryDate) ? WorkCalendar::getNonWorkingDaysBetween($customerId, $finishedAt, $deliveryDate) : null;

        if ($erpDate && !$finishedAt) {
            $timeline->addMilestone('NOW', Carbon::now($tz));
        }

        // Order-level timeline payload
        $boundsStartTs = $erpDate?->timestamp ?? $createdAt?->timestamp ?? null;
        $boundsEndTs = $deliveryDate?->timestamp ?? $finishedAt?->timestamp ?? null;
        $bounds = null;
        if ($boundsStartTs && $boundsEndTs && $boundsEndTs > $boundsStartTs) {
            $bounds = [
                'start' => $boundsStartTs,
                'end' => $boundsEndTs,
                'range' => $boundsEndTs - $boundsStartTs,
                'start_label' => $erpDate?->format('Y-m-d H:i:s') ?? $createdAt?->format('Y-m-d H:i:s'),
                'end_label' => $deliveryDate?->format('Y-m-d H:i:s') ?? $finishedAt?->format('Y-m-d H:i:s'),
            ];
        }

        $orderTimeline = [
            'bounds' => $bounds,
            'erp_start_ts' => $erpDate?->timestamp,
            'created_end_ts' => $createdAt?->timestamp,
            'created_start_ts' => $createdAt?->timestamp,
            'finished_end_ts' => $finishedAt?->timestamp,
            'finished_start_ts' => $finishedAt?->timestamp,
            'delivery_end_ts' => $deliveryDate?->timestamp,
            'erp_to_created_seconds' => $erpToCreated,
            'erp_to_created_formatted' => $this->formatSeconds($erpToCreated),
            'created_to_finished_seconds' => $createdToFinished,
            'created_to_finished_formatted' => $this->formatSeconds($createdToFinished),
            'finished_to_delivery_seconds' => $finishedToDelivery,
            'finished_to_delivery_formatted' => $this->formatSeconds($finishedToDelivery),
        ];

        \Log::info('PT order_timeline', [
            'order_id' => $order->id,
            'use_actual_delivery' => (bool)($filters['use_actual_delivery'] ?? false),
            'bounds_present' => (bool)$bounds,
            'erp_ts' => $orderTimeline['erp_start_ts'],
            'created_ts' => $orderTimeline['created_end_ts'],
            'finished_ts' => $orderTimeline['finished_end_ts'],
            'delivery_ts' => $orderTimeline['delivery_end_ts'],
        ]);

        $processAggregates = collect($processRows)
            ->groupBy(fn($row) => $row['process_code'] ?? 'SIN_CODIGO')
            ->map(function ($rows) {
                $durations = collect($rows)->pluck('duration_seconds')->filter();
                $gaps = collect($rows)->pluck('gap_seconds')->filter();
                return [
                    'count' => $rows->count(),
                    'total_duration' => (int)$durations->sum(),
                    'total_gap' => (int)$gaps->sum(),
                    'avg_duration' => $durations->avg(),
                    'avg_gap' => $gaps->avg(),
                ];
            })
            ->toArray();

        $plannedDelivery = $order->delivery_date ? $order->delivery_date->copy()->timezone($tz)->endOfDay() : null;
        $actualDelivery = $order->actual_delivery_date ? $order->actual_delivery_date->copy()->timezone($tz)->endOfDay() : null;
        $deliveryDelaySeconds = ($plannedDelivery && $actualDelivery)
            ? max(0, $plannedDelivery->diffInSeconds($actualDelivery, false))
            : null;
        $deliveryDelaySigned = ($plannedDelivery && $actualDelivery)
            ? $plannedDelivery->diffInSeconds($actualDelivery, false)
            : null;

        return [
            'id' => $order->id,
            'order_id' => $order->order_id,
            'client_number' => $order->client_number,
            'customer_client_name' => optional($order->customerClient)->name,
            'route_name' => optional($order->routeName)->name,
            'fecha_pedido_erp' => optional($erpDate)->format('Y-m-d H:i:s'),
            'fecha_pedido_erp_ts' => optional($erpDate)?->timestamp,
            'created_at' => optional($createdAt)->format('Y-m-d H:i:s'),
            'created_at_ts' => optional($createdAt)?->timestamp,
            'finished_at' => optional($finishedAt)->format('Y-m-d H:i:s'),
            'finished_at_ts' => optional($finishedAt)?->timestamp,
            'delivery_date' => optional($deliveryDate)->format('Y-m-d H:i:s'),
            'delivery_date_ts' => optional($deliveryDate)?->timestamp,
            'delivery_date_planned' => optional($plannedDelivery)->format('Y-m-d H:i:s'),
            'delivery_date_planned_ts' => optional($plannedDelivery)?->timestamp,
            'actual_delivery_date' => optional($actualDelivery)->format('Y-m-d H:i:s'),
            'actual_delivery_date_ts' => optional($actualDelivery)?->timestamp,
            'erp_to_created_seconds' => $erpToCreated,
            'erp_to_created_formatted' => $this->formatSeconds($erpToCreated),
            'erp_to_created_working_days' => $erpToCreatedWorkingDays,
            'erp_to_created_non_working_days' => $erpToCreatedNonWorkingDays,
            'erp_to_created_calendar_days' => $erpToCreated ? round($erpToCreated / 86400, 1) : null,
            'erp_to_finished_seconds' => $erpToFinished,
            'erp_to_finished_formatted' => $this->formatSeconds($erpToFinished),
            'erp_to_finished_working_days' => $erpToFinishedWorkingDays,
            'erp_to_finished_non_working_days' => $erpToFinishedNonWorkingDays,
            'erp_to_finished_calendar_days' => $erpToFinished ? round($erpToFinished / 86400, 1) : null,
            'created_to_finished_seconds' => $createdToFinished,
            'created_to_finished_formatted' => $this->formatSeconds($createdToFinished),
            'created_to_finished_working_days' => $createdToFinishedWorkingDays,
            'created_to_finished_non_working_days' => $createdToFinishedNonWorkingDays,
            'created_to_finished_calendar_days' => $createdToFinished ? round($createdToFinished / 86400, 1) : null,
            'erp_to_delivery_seconds' => $erpToDelivery,
            'erp_to_delivery_formatted' => $this->formatSeconds($erpToDelivery),
            'erp_to_delivery_working_days' => $erpToDeliveryWorkingDays,
            'erp_to_delivery_non_working_days' => $erpToDeliveryNonWorkingDays,
            'erp_to_delivery_calendar_days' => $erpToDelivery ? round($erpToDelivery / 86400, 1) : null,
            'created_to_delivery_seconds' => $createdToDelivery,
            'created_to_delivery_formatted' => $this->formatSeconds($createdToDelivery),
            'created_to_delivery_working_days' => $createdToDeliveryWorkingDays,
            'created_to_delivery_non_working_days' => $createdToDeliveryNonWorkingDays,
            'created_to_delivery_calendar_days' => $createdToDelivery ? round($createdToDelivery / 86400, 1) : null,
            'finished_to_delivery_seconds' => $finishedToDelivery,
            'finished_to_delivery_formatted' => $this->formatSeconds($finishedToDelivery),
            'finished_to_delivery_working_days' => $finishedToDeliveryWorkingDays,
            'finished_to_delivery_non_working_days' => $finishedToDeliveryNonWorkingDays,
            'finished_to_delivery_calendar_days' => $finishedToDelivery ? round($finishedToDelivery / 86400, 1) : null,
            'processes' => $processRows,
            'timeline' => $timeline->toArray(),
            'order_timeline' => $orderTimeline,
            'group_metrics' => $groupMetrics,
            'process_aggregates' => $processAggregates,
            'order_delivery_delay_seconds' => $deliveryDelaySigned,
            'order_delivery_delay_formatted' => $this->formatSeconds($deliveryDelaySigned),
            'use_actual_delivery' => (bool)($filters['use_actual_delivery'] ?? false),
        ];
    }

    protected function computeAverageTimeline(Customer $customer, array $filters): array
    {
        $tz = config('app.timezone');
        $useActual = (bool)($filters['use_actual_delivery'] ?? false);
        $orders = $this->buildProductionTimesBaseQuery($customer, $filters)
            ->select('id', 'fecha_pedido_erp', 'created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
            ->get();

        $sumErpCreated = 0; $cntErpCreated = 0;
        $sumCreatedFinished = 0; $cntCreatedFinished = 0;
        $sumFinishedDelivery = 0; $cntFinishedDelivery = 0;

        foreach ($orders as $o) {
            $erp = $o->fecha_pedido_erp ? Carbon::parse($o->fecha_pedido_erp, $tz) : null;
            $cr = $o->created_at ? $o->created_at->copy()->timezone($tz) : null;
            $fi = $o->finished_at ? $o->finished_at->copy()->timezone($tz) : null;
            $delBase = $useActual ? $o->actual_delivery_date : $o->delivery_date;
            $de = $delBase ? $delBase->copy()->timezone($tz)->endOfDay() : null;

            $diff = function ($start, $end) {
                if (!$start || !$end) return null;
                $s = $start->diffInSeconds($end, false);
                return $s < 0 ? 0 : $s;
            };

            $d1 = $diff($erp, $cr);
            if ($d1 !== null) { $sumErpCreated += $d1; $cntErpCreated++; }

            $d2 = $diff($cr, $fi);
            if ($d2 !== null) { $sumCreatedFinished += $d2; $cntCreatedFinished++; }

            $d3 = $diff($fi, $de);
            if ($d3 !== null) { $sumFinishedDelivery += $d3; $cntFinishedDelivery++; }
        }

        $avg1 = $cntErpCreated ? intdiv($sumErpCreated, $cntErpCreated) : 0;
        $avg2 = $cntCreatedFinished ? intdiv($sumCreatedFinished, $cntCreatedFinished) : 0;
        $avg3 = $cntFinishedDelivery ? intdiv($sumFinishedDelivery, $cntFinishedDelivery) : 0;

        $total = max($avg1 + $avg2 + $avg3, 1);

        $erpStart = 0;
        $createdEnd = $avg1;
        $createdStart = $createdEnd;
        $finishedEnd = $createdEnd + $avg2;
        $finishedStart = $finishedEnd;
        $deliveryEnd = $finishedEnd + $avg3;

        $avg = [
            'bounds' => [
                'start' => 0,
                'end' => $total,
                'range' => $total,
                'start_label' => '0s',
                'end_label' => $this->formatSeconds($total),
            ],
            'erp_start_ts' => $erpStart,
            'created_end_ts' => $createdEnd,
            'created_start_ts' => $createdStart,
            'finished_end_ts' => $finishedEnd,
            'finished_start_ts' => $finishedStart,
            'delivery_end_ts' => $deliveryEnd,
            'erp_to_created_seconds' => $avg1,
            'erp_to_created_formatted' => $this->formatSeconds($avg1),
            'created_to_finished_seconds' => $avg2,
            'created_to_finished_formatted' => $this->formatSeconds($avg2),
            'finished_to_delivery_seconds' => $avg3,
            'finished_to_delivery_formatted' => $this->formatSeconds($avg3),
        ];

        \Log::info('PT average_timeline', [
            'orders_count' => count($orders),
            'use_actual_delivery' => $useActual,
            'avg1' => $avg1,
            'avg2' => $avg2,
            'avg3' => $avg3,
            'total' => $total,
        ]);

        return $avg;
    }

    protected function computeMedianTimeline(Customer $customer, array $filters): array
    {
        $tz = config('app.timezone');
        $useActual = (bool)($filters['use_actual_delivery'] ?? false);
        $orders = $this->buildProductionTimesBaseQuery($customer, $filters)
            ->select('id', 'fecha_pedido_erp', 'created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
            ->get();

        $erpCreatedValues = [];
        $createdFinishedValues = [];
        $finishedDeliveryValues = [];

        foreach ($orders as $o) {
            $erp = $o->fecha_pedido_erp ? Carbon::parse($o->fecha_pedido_erp, $tz) : null;
            $cr = $o->created_at ? $o->created_at->copy()->timezone($tz) : null;
            $fi = $o->finished_at ? $o->finished_at->copy()->timezone($tz) : null;
            $delBase = $useActual ? $o->actual_delivery_date : $o->delivery_date;
            $de = $delBase ? $delBase->copy()->timezone($tz)->endOfDay() : null;

            $diff = function ($start, $end) {
                if (!$start || !$end) return null;
                $s = $start->diffInSeconds($end, false);
                return $s < 0 ? 0 : $s;
            };

            $d1 = $diff($erp, $cr);
            if ($d1 !== null) $erpCreatedValues[] = $d1;

            $d2 = $diff($cr, $fi);
            if ($d2 !== null) $createdFinishedValues[] = $d2;

            $d3 = $diff($fi, $de);
            if ($d3 !== null) $finishedDeliveryValues[] = $d3;
        }

        // Función auxiliar para calcular la mediana
        $calculateMedian = function ($values) {
            if (empty($values)) return 0;
            sort($values);
            $count = count($values);
            $middle = floor($count / 2);
            if ($count % 2 == 0) {
                return intval(($values[$middle - 1] + $values[$middle]) / 2);
            } else {
                return intval($values[$middle]);
            }
        };

        $med1 = $calculateMedian($erpCreatedValues);
        $med2 = $calculateMedian($createdFinishedValues);
        $med3 = $calculateMedian($finishedDeliveryValues);

        $total = max($med1 + $med2 + $med3, 1);

        $erpStart = 0;
        $createdEnd = $med1;
        $createdStart = $createdEnd;
        $finishedEnd = $createdEnd + $med2;
        $finishedStart = $finishedEnd;
        $deliveryEnd = $finishedEnd + $med3;

        $median = [
            'bounds' => [
                'start' => 0,
                'end' => $total,
                'range' => $total,
                'start_label' => '0s',
                'end_label' => $this->formatSeconds($total),
            ],
            'erp_start_ts' => $erpStart,
            'created_end_ts' => $createdEnd,
            'created_start_ts' => $createdStart,
            'finished_end_ts' => $finishedEnd,
            'finished_start_ts' => $finishedStart,
            'delivery_end_ts' => $deliveryEnd,
            'erp_to_created_seconds' => $med1,
            'erp_to_created_formatted' => $this->formatSeconds($med1),
            'created_to_finished_seconds' => $med2,
            'created_to_finished_formatted' => $this->formatSeconds($med2),
            'finished_to_delivery_seconds' => $med3,
            'finished_to_delivery_formatted' => $this->formatSeconds($med3),
        ];

        \Log::info('PT median_timeline', [
            'orders_count' => count($orders),
            'use_actual_delivery' => $useActual,
            'med1' => $med1,
            'med2' => $med2,
            'med3' => $med3,
            'total' => $total,
        ]);

        return $median;
    }

    protected function computeAverageTimelineWorkingDays(Customer $customer, array $filters): array
    {
        $tz = config('app.timezone');
        $useActual = (bool)($filters['use_actual_delivery'] ?? false);
        $orders = $this->buildProductionTimesBaseQuery($customer, $filters)
            ->select('id', 'customer_id', 'fecha_pedido_erp', 'created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
            ->get();

        $sumErpCreatedDays = 0; $cntErpCreated = 0;
        $sumCreatedFinishedDays = 0; $cntCreatedFinished = 0;
        $sumFinishedDeliveryDays = 0; $cntFinishedDelivery = 0;

        $sumErpCreatedNonWorkingDays = 0;
        $sumCreatedFinishedNonWorkingDays = 0;
        $sumFinishedDeliveryNonWorkingDays = 0;

        foreach ($orders as $o) {
            $erp = $o->fecha_pedido_erp ? Carbon::parse($o->fecha_pedido_erp, $tz) : null;
            $cr = $o->created_at ? $o->created_at->copy()->timezone($tz) : null;
            $fi = $o->finished_at ? $o->finished_at->copy()->timezone($tz) : null;
            $delBase = $useActual ? $o->actual_delivery_date : $o->delivery_date;
            $de = $delBase ? $delBase->copy()->timezone($tz)->endOfDay() : null;

            // ERP → Created
            if ($erp && $cr) {
                $workingDays = WorkCalendar::getWorkingDaysBetween($o->customer_id, $erp, $cr);
                $nonWorkingDays = WorkCalendar::getNonWorkingDaysBetween($o->customer_id, $erp, $cr);
                $sumErpCreatedDays += $workingDays;
                $sumErpCreatedNonWorkingDays += $nonWorkingDays;
                $cntErpCreated++;
            }

            // Created → Finished
            if ($cr && $fi) {
                $workingDays = WorkCalendar::getWorkingDaysBetween($o->customer_id, $cr, $fi);
                $nonWorkingDays = WorkCalendar::getNonWorkingDaysBetween($o->customer_id, $cr, $fi);
                $sumCreatedFinishedDays += $workingDays;
                $sumCreatedFinishedNonWorkingDays += $nonWorkingDays;
                $cntCreatedFinished++;
            }

            // Finished → Delivery
            if ($fi && $de) {
                $workingDays = WorkCalendar::getWorkingDaysBetween($o->customer_id, $fi, $de);
                $nonWorkingDays = WorkCalendar::getNonWorkingDaysBetween($o->customer_id, $fi, $de);
                $sumFinishedDeliveryDays += $workingDays;
                $sumFinishedDeliveryNonWorkingDays += $nonWorkingDays;
                $cntFinishedDelivery++;
            }
        }

        $avgDays1 = $cntErpCreated ? round($sumErpCreatedDays / $cntErpCreated, 1) : 0;
        $avgDays2 = $cntCreatedFinished ? round($sumCreatedFinishedDays / $cntCreatedFinished, 1) : 0;
        $avgDays3 = $cntFinishedDelivery ? round($sumFinishedDeliveryDays / $cntFinishedDelivery, 1) : 0;

        $avgNonWorkingDays1 = $cntErpCreated ? round($sumErpCreatedNonWorkingDays / $cntErpCreated, 1) : 0;
        $avgNonWorkingDays2 = $cntCreatedFinished ? round($sumCreatedFinishedNonWorkingDays / $cntCreatedFinished, 1) : 0;
        $avgNonWorkingDays3 = $cntFinishedDelivery ? round($sumFinishedDeliveryNonWorkingDays / $cntFinishedDelivery, 1) : 0;

        // Convertir días a horas para la visualización
        $avg1Hours = $avgDays1 * 24;
        $avg2Hours = $avgDays2 * 24;
        $avg3Hours = $avgDays3 * 24;

        $totalHours = max($avg1Hours + $avg2Hours + $avg3Hours, 1);

        $erpStart = 0;
        $createdEnd = $avg1Hours;
        $createdStart = $createdEnd;
        $finishedEnd = $createdEnd + $avg2Hours;
        $finishedStart = $finishedEnd;
        $deliveryEnd = $finishedEnd + $avg3Hours;

        // Calcular totales acumulativos
        $erpToFinishedDays = $avgDays1 + $avgDays2;
        $erpToFinishedHours = $erpToFinishedDays * 24;
        $erpToFinishedNonWorkingDays = $avgNonWorkingDays1 + $avgNonWorkingDays2;

        $erpToDeliveryDays = $avgDays1 + $avgDays2 + $avgDays3;
        $erpToDeliveryHours = $erpToDeliveryDays * 24;
        $erpToDeliveryNonWorkingDays = $avgNonWorkingDays1 + $avgNonWorkingDays2 + $avgNonWorkingDays3;

        return [
            'bounds' => [
                'start' => 0,
                'end' => $totalHours,
                'range' => $totalHours,
                'start_label' => '0h',
                'end_label' => round($totalHours, 1) . 'h',
            ],
            'erp_start_ts' => $erpStart,
            'created_end_ts' => $createdEnd,
            'created_start_ts' => $createdStart,
            'finished_end_ts' => $finishedEnd,
            'finished_start_ts' => $finishedStart,
            'delivery_end_ts' => $deliveryEnd,
            // Segmentos individuales
            'erp_to_created_hours' => $avg1Hours,
            'erp_to_created_days' => $avgDays1,
            'erp_to_created_non_working_days' => $avgNonWorkingDays1,
            'created_to_finished_hours' => $avg2Hours,
            'created_to_finished_days' => $avgDays2,
            'created_to_finished_non_working_days' => $avgNonWorkingDays2,
            'finished_to_delivery_hours' => $avg3Hours,
            'finished_to_delivery_days' => $avgDays3,
            'finished_to_delivery_non_working_days' => $avgNonWorkingDays3,
            // Totales acumulativos
            'erp_to_finished_hours' => $erpToFinishedHours,
            'erp_to_finished_days' => $erpToFinishedDays,
            'erp_to_finished_non_working_days' => $erpToFinishedNonWorkingDays,
            'erp_to_delivery_hours' => $erpToDeliveryHours,
            'erp_to_delivery_days' => $erpToDeliveryDays,
            'erp_to_delivery_non_working_days' => $erpToDeliveryNonWorkingDays,
        ];
    }

    protected function computeMedianTimelineWorkingDays(Customer $customer, array $filters): array
    {
        $tz = config('app.timezone');
        $useActual = (bool)($filters['use_actual_delivery'] ?? false);
        $orders = $this->buildProductionTimesBaseQuery($customer, $filters)
            ->select('id', 'customer_id', 'fecha_pedido_erp', 'created_at', 'finished_at', 'delivery_date', 'actual_delivery_date')
            ->get();

        $erpCreatedWorkingDays = [];
        $createdFinishedWorkingDays = [];
        $finishedDeliveryWorkingDays = [];

        $erpCreatedNonWorkingDays = [];
        $createdFinishedNonWorkingDays = [];
        $finishedDeliveryNonWorkingDays = [];

        foreach ($orders as $o) {
            $erp = $o->fecha_pedido_erp ? Carbon::parse($o->fecha_pedido_erp, $tz) : null;
            $cr = $o->created_at ? $o->created_at->copy()->timezone($tz) : null;
            $fi = $o->finished_at ? $o->finished_at->copy()->timezone($tz) : null;
            $delBase = $useActual ? $o->actual_delivery_date : $o->delivery_date;
            $de = $delBase ? $delBase->copy()->timezone($tz)->endOfDay() : null;

            // ERP → Created
            if ($erp && $cr) {
                $workingDays = WorkCalendar::getWorkingDaysBetween($o->customer_id, $erp, $cr);
                $nonWorkingDays = WorkCalendar::getNonWorkingDaysBetween($o->customer_id, $erp, $cr);
                $erpCreatedWorkingDays[] = $workingDays;
                $erpCreatedNonWorkingDays[] = $nonWorkingDays;
            }

            // Created → Finished
            if ($cr && $fi) {
                $workingDays = WorkCalendar::getWorkingDaysBetween($o->customer_id, $cr, $fi);
                $nonWorkingDays = WorkCalendar::getNonWorkingDaysBetween($o->customer_id, $cr, $fi);
                $createdFinishedWorkingDays[] = $workingDays;
                $createdFinishedNonWorkingDays[] = $nonWorkingDays;
            }

            // Finished → Delivery
            if ($fi && $de) {
                $workingDays = WorkCalendar::getWorkingDaysBetween($o->customer_id, $fi, $de);
                $nonWorkingDays = WorkCalendar::getNonWorkingDaysBetween($o->customer_id, $fi, $de);
                $finishedDeliveryWorkingDays[] = $workingDays;
                $finishedDeliveryNonWorkingDays[] = $nonWorkingDays;
            }
        }

        // Función auxiliar para calcular la mediana
        $calculateMedian = function ($values) {
            if (empty($values)) return 0;
            sort($values);
            $count = count($values);
            $middle = floor($count / 2);
            if ($count % 2 == 0) {
                return ($values[$middle - 1] + $values[$middle]) / 2;
            } else {
                return $values[$middle];
            }
        };

        $medDays1 = $calculateMedian($erpCreatedWorkingDays);
        $medDays2 = $calculateMedian($createdFinishedWorkingDays);
        $medDays3 = $calculateMedian($finishedDeliveryWorkingDays);

        $medNonWorkingDays1 = $calculateMedian($erpCreatedNonWorkingDays);
        $medNonWorkingDays2 = $calculateMedian($createdFinishedNonWorkingDays);
        $medNonWorkingDays3 = $calculateMedian($finishedDeliveryNonWorkingDays);

        // Convertir días a horas para la visualización
        $med1Hours = $medDays1 * 24;
        $med2Hours = $medDays2 * 24;
        $med3Hours = $medDays3 * 24;

        $totalHours = max($med1Hours + $med2Hours + $med3Hours, 1);

        $erpStart = 0;
        $createdEnd = $med1Hours;
        $createdStart = $createdEnd;
        $finishedEnd = $createdEnd + $med2Hours;
        $finishedStart = $finishedEnd;
        $deliveryEnd = $finishedEnd + $med3Hours;

        // Calcular totales acumulativos
        $erpToFinishedDays = $medDays1 + $medDays2;
        $erpToFinishedHours = $erpToFinishedDays * 24;
        $erpToFinishedNonWorkingDays = $medNonWorkingDays1 + $medNonWorkingDays2;

        $erpToDeliveryDays = $medDays1 + $medDays2 + $medDays3;
        $erpToDeliveryHours = $erpToDeliveryDays * 24;
        $erpToDeliveryNonWorkingDays = $medNonWorkingDays1 + $medNonWorkingDays2 + $medNonWorkingDays3;

        return [
            'bounds' => [
                'start' => 0,
                'end' => $totalHours,
                'range' => $totalHours,
                'start_label' => '0h',
                'end_label' => round($totalHours, 1) . 'h',
            ],
            'erp_start_ts' => $erpStart,
            'created_end_ts' => $createdEnd,
            'created_start_ts' => $createdStart,
            'finished_end_ts' => $finishedEnd,
            'finished_start_ts' => $finishedStart,
            'delivery_end_ts' => $deliveryEnd,
            // Segmentos individuales
            'erp_to_created_hours' => $med1Hours,
            'erp_to_created_days' => $medDays1,
            'erp_to_created_non_working_days' => $medNonWorkingDays1,
            'created_to_finished_hours' => $med2Hours,
            'created_to_finished_days' => $medDays2,
            'created_to_finished_non_working_days' => $medNonWorkingDays2,
            'finished_to_delivery_hours' => $med3Hours,
            'finished_to_delivery_days' => $medDays3,
            'finished_to_delivery_non_working_days' => $medNonWorkingDays3,
            // Totales acumulativos
            'erp_to_finished_hours' => $erpToFinishedHours,
            'erp_to_finished_days' => $erpToFinishedDays,
            'erp_to_finished_non_working_days' => $erpToFinishedNonWorkingDays,
            'erp_to_delivery_hours' => $erpToDeliveryHours,
            'erp_to_delivery_days' => $erpToDeliveryDays,
            'erp_to_delivery_non_working_days' => $erpToDeliveryNonWorkingDays,
        ];
    }

    protected function buildProductionTimesSummary(Collection $orders, array $filters): array
    {
        $summary = new ProductionTimeSummary();

        foreach ($orders as $order) {
            $row = $this->transformSingleOrderProductionTimes($order, $filters);
            $summary->pushOrder($row);

            foreach ($row['processes'] as $processRow) {
                $summary->pushProcess($processRow);
            }
        }

        return $summary->toArray();
    }

    protected function formatSeconds(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $prefix = '';
        if ($seconds < 0) {
            $prefix = '-';
            $seconds = abs($seconds);
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%s%02d:%02d:%02d', $prefix, $hours, $minutes, $secs);
    }

    public function update(Request $request, Customer $customer, OriginalOrder $originalOrder)
    {
        // 1. Validar la petición.
        $validated = $request->validate([
            'order_id' => 'required|unique:original_orders,order_id,' . $originalOrder->id,
            'client_number' => 'nullable|string|max:255',
            'route_name' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'in_stock' => 'sometimes|boolean',
            'order_details' => 'required|json',
            'processes' => 'sometimes|array',
            'processes.*' => 'exists:processes,id',
            'process_times' => 'required_with:processes|array',
            'process_times.*' => 'required_with:processes|numeric|min:0.01',
            'finished' => 'sometimes|array',
            'processed' => 'nullable|boolean',
            'articles' => 'sometimes|array',
            'order_finished' => 'nullable|boolean',
            'finished_at' => 'nullable|date',
        ]);

        // 2. Resolver route_name_id si se proporciona
        $routeNameId = $this->resolveRouteName($customer, $validated['route_name'] ?? null);

        // 3. Actualizar los campos principales de la orden.
        $originalOrder->update([
            'order_id' => $validated['order_id'],
            'client_number' => $validated['client_number'] ?? null,
            'route_name_id' => $routeNameId,
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
        $processTimes = $request->input('process_times', []);
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
            $time = isset($processTimes[$uniqueId]) ? (float) $processTimes[$uniqueId] : 0;
            if ($time <= 0 && isset($orderDetails['grupos'])) {
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

            if ($time <= 0) {
                $time = (float) env('DEFAULT_PROCESS_TIME', 1);
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

    /**
     * Resuelve o crea un RouteName basado en el nombre proporcionado
     */
    private function resolveRouteName(Customer $customer, ?string $routeName): ?int
    {
        if (empty($routeName)) {
            return null;
        }

        // Buscar ruta existente para este cliente
        $route = RouteName::where('customer_id', $customer->id)
            ->where('name', $routeName)
            ->first();

        if (!$route) {
            // Crear nueva ruta
            $route = RouteName::create([
                'customer_id' => $customer->id,
                'name' => $routeName,
                'note' => 'Creada automáticamente desde orden original',
                'days_mask' => 0, // Sin días específicos por defecto
                'active' => true,
            ]);
        }

        return $route->id;
    }
}

class ProductionTimeSummary
{
    protected array $orders = [];
    protected array $processes = [];

    public function pushOrder(array $orderRow): void
    {
        $this->orders[] = $orderRow;
    }

    public function pushProcess(array $processRow): void
    {
        $this->processes[] = $processRow;
    }

    public function toArray(): array
    {
        $ordersCol = collect($this->orders);
        $processCol = collect($this->processes);

        $orderDurations = $ordersCol->pluck('created_to_finished_seconds')->filter()->values();
        $orderErpDurations = $ordersCol->pluck('erp_to_finished_seconds')->filter()->values();
        $orderErpCreated = $ordersCol->pluck('erp_to_created_seconds')->filter()->values();
        $orderErpToDelivery = $ordersCol->pluck('erp_to_delivery_seconds')->filter()->values();

        // Días laborables para las métricas principales
        $orderCreatedToFinishedWorkingDays = $ordersCol->pluck('created_to_finished_working_days')->filter()->values();
        $orderCreatedToFinishedNonWorkingDays = $ordersCol->pluck('created_to_finished_non_working_days')->filter()->values();

        $orderErpToFinishedWorkingDays = $ordersCol->pluck('erp_to_finished_working_days')->filter()->values();
        $orderErpToFinishedNonWorkingDays = $ordersCol->pluck('erp_to_finished_non_working_days')->filter()->values();

        $orderErpToCreatedWorkingDays = $ordersCol->pluck('erp_to_created_working_days')->filter()->values();
        $orderErpToCreatedNonWorkingDays = $ordersCol->pluck('erp_to_created_non_working_days')->filter()->values();

        $orderErpToDeliveryWorkingDays = $ordersCol->pluck('erp_to_delivery_working_days')->filter()->values();
        $orderErpToDeliveryNonWorkingDays = $ordersCol->pluck('erp_to_delivery_non_working_days')->filter()->values();

        $processDurations = $processCol->pluck('duration_seconds')->filter()->values();
        $processGaps = $processCol->pluck('gap_seconds')->filter()->values();

        $delaysOverDay = $processCol->filter(fn($row) => ($row['gap_seconds'] ?? 0) > 86400)->count();

        $processByCode = $processCol
            ->groupBy(fn($row) => $row['process_code'] ?? 'SIN_CODIGO')
            ->map(function ($group) {
                $dur = $group->pluck('duration_seconds')->filter()->values();
                $gap = $group->pluck('gap_seconds')->filter()->values();
                return [
                    'count' => $group->count(),
                    'avg_duration' => $dur->avg(),
                    'p50_duration' => self::percentile($dur, 0.5),
                    'p90_duration' => self::percentile($dur, 0.9),
                    'avg_gap' => $gap->avg(),
                    'p50_gap' => self::percentile($gap, 0.5),
                    'p90_gap' => self::percentile($gap, 0.9),
                ];
            })
            ->toArray();

        $deliveryDelays = $ordersCol->pluck('order_delivery_delay_seconds')->filter(function ($v) { return $v !== null; })->values();
        $slaTotal = $deliveryDelays->count();
        $slaOnTime = $deliveryDelays->filter(fn($d) => $d <= 0)->count();
        $slaRatio = $slaTotal > 0 ? $slaOnTime / $slaTotal : null;

        $groupLeadTimes = $ordersCol
            ->pluck('group_metrics')
            ->filter()
            ->reduce(function ($carry, $gm) {
                foreach ($gm as $groupKey => $vals) {
                    if (!isset($carry[$groupKey])) {
                        $carry[$groupKey] = [
                            'process_count' => 0,
                            'duration_sum_seconds' => 0,
                            'gap_sum_seconds' => 0,
                            'span_seconds_sum' => 0,
                            'orders' => 0,
                        ];
                    }
                    $carry[$groupKey]['process_count'] += (int)($vals['process_count'] ?? 0);
                    $carry[$groupKey]['duration_sum_seconds'] += (int)($vals['duration_sum_seconds'] ?? 0);
                    $carry[$groupKey]['gap_sum_seconds'] += (int)($vals['gap_sum_seconds'] ?? 0);
                    $carry[$groupKey]['span_seconds_sum'] += (int)($vals['span_seconds'] ?? 0);
                    $carry[$groupKey]['orders'] += 1;
                }
                return $carry;
            }, []);

        foreach ($groupLeadTimes as $k => $agg) {
            $ordersCnt = max(1, $agg['orders']);
            $groupLeadTimes[$k]['avg_duration_sum_seconds'] = (int)($agg['duration_sum_seconds'] / $ordersCnt);
            $groupLeadTimes[$k]['avg_gap_sum_seconds'] = (int)($agg['gap_sum_seconds'] / $ordersCnt);
            $groupLeadTimes[$k]['avg_span_seconds'] = (int)($agg['span_seconds_sum'] / $ordersCnt);
        }

        return [
            'orders_total' => $ordersCol->count(),
            'processes_total' => $processCol->count(),
            'orders_avg_created_to_finished' => $orderDurations->avg(),
            'orders_p50_created_to_finished' => self::percentile($orderDurations, 0.5),
            'orders_p90_created_to_finished' => self::percentile($orderDurations, 0.9),
            'orders_avg_created_to_finished_working_days' => $orderCreatedToFinishedWorkingDays->avg(),
            'orders_p50_created_to_finished_working_days' => self::percentile($orderCreatedToFinishedWorkingDays, 0.5),
            'orders_avg_created_to_finished_non_working_days' => $orderCreatedToFinishedNonWorkingDays->avg(),
            'orders_p50_created_to_finished_non_working_days' => self::percentile($orderCreatedToFinishedNonWorkingDays, 0.5),
            'orders_avg_erp_to_finished' => $orderErpDurations->avg(),
            'orders_p50_erp_to_finished' => self::percentile($orderErpDurations, 0.5),
            'orders_avg_erp_to_finished_working_days' => $orderErpToFinishedWorkingDays->avg(),
            'orders_p50_erp_to_finished_working_days' => self::percentile($orderErpToFinishedWorkingDays, 0.5),
            'orders_avg_erp_to_finished_non_working_days' => $orderErpToFinishedNonWorkingDays->avg(),
            'orders_p50_erp_to_finished_non_working_days' => self::percentile($orderErpToFinishedNonWorkingDays, 0.5),
            'orders_avg_erp_to_created' => $orderErpCreated->avg(),
            'orders_p50_erp_to_created' => self::percentile($orderErpCreated, 0.5),
            'orders_avg_erp_to_created_working_days' => $orderErpToCreatedWorkingDays->avg(),
            'orders_p50_erp_to_created_working_days' => self::percentile($orderErpToCreatedWorkingDays, 0.5),
            'orders_avg_erp_to_created_non_working_days' => $orderErpToCreatedNonWorkingDays->avg(),
            'orders_p50_erp_to_created_non_working_days' => self::percentile($orderErpToCreatedNonWorkingDays, 0.5),
            'orders_avg_erp_to_delivery' => $orderErpToDelivery->avg(),
            'orders_p50_erp_to_delivery' => self::percentile($orderErpToDelivery, 0.5),
            'orders_avg_erp_to_delivery_working_days' => $orderErpToDeliveryWorkingDays->avg(),
            'orders_p50_erp_to_delivery_working_days' => self::percentile($orderErpToDeliveryWorkingDays, 0.5),
            'orders_avg_erp_to_delivery_non_working_days' => $orderErpToDeliveryNonWorkingDays->avg(),
            'orders_p50_erp_to_delivery_non_working_days' => self::percentile($orderErpToDeliveryNonWorkingDays, 0.5),
            'process_avg_duration' => $processDurations->avg(),
            'process_p50_duration' => self::percentile($processDurations, 0.5),
            'process_p90_duration' => self::percentile($processDurations, 0.9),
            'process_avg_gap' => $processGaps->avg(),
            'process_p50_gap' => self::percentile($processGaps, 0.5),
            'process_p90_gap' => self::percentile($processGaps, 0.9),
            'process_delays_over_day' => $delaysOverDay,
            'process_by_code' => $processByCode,
            'sla_on_time_ratio' => $slaRatio,
            'sla_on_time_count' => $slaOnTime,
            'sla_total' => $slaTotal,
            'group_lead_times' => $groupLeadTimes,
        ];
    }

    protected static function percentile($values, float $p)
    {
        $arr = collect($values)->filter()->sort()->values()->all();
        $n = count($arr);
        if ($n === 0) return null;
        if ($p <= 0) return $arr[0];
        if ($p >= 1) return $arr[$n - 1];
        $rank = $p * ($n - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);
        if ($low === $high) return $arr[$low];
        $weight = $rank - $low;
        return $arr[$low] * (1 - $weight) + $arr[$high] * $weight;
    }
}

class ProductionTimeTimeline
{
    protected Carbon $origin;
    protected array $milestones = [];

    public function __construct(Carbon $origin)
    {
        $this->origin = $origin;
    }

    public function addMilestone(string $key, Carbon $timestamp): void
    {
        $this->milestones[] = [
            'key' => $key,
            'timestamp' => $timestamp->toIso8601String(),
            'seconds_from_origin' => $this->origin->diffInSeconds($timestamp, false),
        ];
    }

    public function toArray(): array
    {
        return [
            'origin' => $this->origin->toIso8601String(),
            'milestones' => $this->milestones,
        ];
    }
}
