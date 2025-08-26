<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Log; // Agrega esta línea para usar Log
use Illuminate\Support\Facades\DB;
use App\Models\ProductionOrder;


class CustomerController extends Controller
{
    public function index()
    {
        return view('customers.index');
    }

    public function getCustomers(Request $request)
    {
        // Construye la consulta base para los clientes
        $query = Customer::query();

        // Usa DataTables para procesar la consulta y añadir las columnas adicionales
        return DataTables::of($query)
            ->addColumn('checkbox', function($customer) {
                // Esta columna se renderiza en el cliente con JavaScript
                return '';
            })
            ->addColumn('action', function ($customer) {
                // Solo mostrar el botón de expandir
                return "<button class='btn btn-sm btn-outline-primary toggle-actions' data-customer-id='{$customer->id}' title='" . __('Show Actions') . "'>
                    <i class='fas fa-chevron-down'></i> " . __('Actions') . "
                </button>";
            })
            ->addColumn('action_buttons', function ($customer) {
                // URLs para las diferentes acciones
                $editUrl = route('customers.edit', $customer->id);
                $productionLinesUrl = route('productionlines.index', ['customer_id' => $customer->id]);
                $deleteUrl = route('customers.destroy', $customer->id);
                $csrfToken = csrf_token();
                $liveViewUrl = secure_url('/modbuses/liststats/weight?token=' . $customer->token);
                $liveViewUrlProd = secure_url('/productionlines/liststats?token=' . $customer->token);

                // Construir botones organizados por grupos
                $buttons = [];
                
                // Grupo 1: Acciones básicas
                $basicActions = [];
                if (auth()->user()->can('productionline-kanban')) {
                    $orderOrganizerUrl = route('customers.order-organizer', $customer->id);
                    $basicActions[] = "<a href='{$orderOrganizerUrl}' class='btn btn-sm btn-primary me-1 mb-1' title='" . __('Kanban') . "'><i class='fas fa-tasks'></i> " . __('Kanban') . "</a>";
                }
                if (auth()->user()->can('productionline-edit')) {
                    $basicActions[] = "<a href='{$editUrl}' class='btn btn-sm btn-info me-1 mb-1' title='" . __('Edit') . "'><i class='fas fa-edit'></i> " . __('Edit') . "</a>";
                }
                if (auth()->user()->can('productionline-show')) {
                    $basicActions[] = "<a href='{$productionLinesUrl}' class='btn btn-sm btn-secondary me-1 mb-1' title='" . __('Production Lines') . "'><i class='fas fa-sitemap'></i> " . __('Lineas') . "</a>";
                }
                
                // Grupo 2: Órdenes y procesos
                $orderActions = [];
                if (auth()->user()->can('productionline-orders')) {
                    $originalOrdersUrl = route('customers.original-orders.index', $customer->id);
                    $orderActions[] = "<a href='{$originalOrdersUrl}' class='btn btn-sm btn-dark me-1 mb-1' title='" . __('Original Orders') . "'><i class='fas fa-clipboard-list'></i> " . __('Pedidos') . "</a>";
                }
                if (auth()->user()->can('original-order-list')) {
                    $finishedProcessesUrl = route('customers.original-orders.finished-processes.view', $customer->id);
                    $orderActions[] = "<a href='{$finishedProcessesUrl}' class='btn btn-sm btn-outline-dark me-1 mb-1' title='" . __('Finished Processes') . "'><i class='fas fa-chart-line'></i> " . __('Procesos finalizados') . "</a>";
                }
                if (auth()->user()->can('workcalendar-list')) {
                    $workCalendarUrl = route('customers.work-calendars.index', $customer->id);
                    $orderActions[] = "<a href='{$workCalendarUrl}' class='btn btn-sm btn-info me-1 mb-1' title='" . __('Work Calendar') . "'><i class='fas fa-calendar-alt'></i> " . __('Calendario') . "</a>";
                }
                
                // Grupo 3: Calidad e incidencias
                $qualityActions = [];
                if (auth()->user()->can('productionline-incidents')) {
                    $incidentsUrl = route('customers.production-order-incidents.index', $customer->id);
                    $qualityActions[] = "<a href='{$incidentsUrl}' class='btn btn-sm btn-danger me-1 mb-1' title='" . __('Production Order Incidents') . "'><i class='fas fa-exclamation-triangle'></i> " . __('Incidencias') . "</a>";
                    
                    $qcIncidentsUrl = route('customers.quality-incidents.index', $customer->id);
                    $qualityActions[] = "<a href='{$qcIncidentsUrl}' class='btn btn-sm btn-outline-danger me-1 mb-1' title='" . __('Quality Incidents (QC)') . "'><i class='fas fa-vial'></i> " . __('Incidencias QC') . "</a>";
                    
                    $qcConfirmationsUrl = route('customers.qc-confirmations.index', $customer->id);
                    $qualityActions[] = "<a href='{$qcConfirmationsUrl}' class='btn btn-sm btn-outline-primary me-1 mb-1' title='" . __('QC Confirmations') . "'><i class='fas fa-clipboard-check'></i> " . __('QC Confirmations') . "</a>";
                }
                
                // Grupo 4: Estadísticas
                $statsActions = [];
                if (auth()->user()->can('productionline-weight-stats')) {
                    $statsActions[] = "<a href='{$liveViewUrl}' target='_blank' class='btn btn-sm btn-success me-1 mb-1' title='" . __('Weight Stats') . "'><i class='fas fa-weight-hanging'></i> " . __('Weight Stats') . "</a>";
                }
                if (auth()->user()->can('productionline-production-stats')) {
                    $statsActions[] = "<a href='{$liveViewUrlProd}' target='_blank' class='btn btn-sm btn-warning me-1 mb-1' title='" . __('Production Stats') . "'><i class='fas fa-chart-line'></i> " . __('Production Stats') . "</a>";
                }
                
                // Grupo 5: Acciones peligrosas
                $dangerActions = [];
                if (auth()->user()->can('productionline-delete')) {
                    $dangerActions[] = "<form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit='return confirm(\"" . __('Are you sure?') . "\");'>
                                        <input type='hidden' name='_token' value='{$csrfToken}'>
                                        <input type='hidden' name='_method' value='DELETE'>
                                        <button type='submit' class='btn btn-sm btn-outline-danger me-1 mb-1' title='" . __('Delete') . "'><i class='fas fa-trash'></i> " . __('Delete') . "</button>
                                       </form>";
                }
                
                // Combinar todos los grupos con separadores visuales
                $allButtons = '';
                if (!empty($basicActions)) {
                    $allButtons .= "<div class='btn-group-section'>" . implode('', $basicActions) . "</div>";
                }
                if (!empty($orderActions)) {
                    $allButtons .= "<div class='btn-group-section'>" . implode('', $orderActions) . "</div>";
                }
                if (!empty($qualityActions)) {
                    $allButtons .= "<div class='btn-group-section'>" . implode('', $qualityActions) . "</div>";
                }
                if (!empty($statsActions)) {
                    $allButtons .= "<div class='btn-group-section'>" . implode('', $statsActions) . "</div>";
                }
                if (!empty($dangerActions)) {
                    $allButtons .= "<div class='btn-group-section'>" . implode('', $dangerActions) . "</div>";
                }
                
                return "<div class='action-buttons-row' style='display: none; padding: 10px; background-color: #f8f9fa; border-radius: 5px; margin-top: 5px;'>" . $allButtons . "</div>";
            })
            // Indica a DataTables que las columnas contienen HTML y no deben ser escapadas
            ->rawColumns(['action', 'action_buttons'])
            // Genera la respuesta JSON para DataTables
            ->make(true);
    }

     

    public function testCustomers()
    {
        $customers = Customer::all();
        dd($customers); 
    }
    
    /**
     * Eliminar múltiples clientes seleccionados
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->ids;
            
            // Verificar si hay IDs válidos
            if (!$ids || !is_array($ids) || count($ids) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('No customers selected for deletion')
                ], 400);
            }
            
            // Eliminar los clientes seleccionados
            $deleted = Customer::whereIn('id', $ids)->delete();
            
            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => __(':count customers have been deleted successfully', ['count' => count($ids)])
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete customers')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Muestra el organizador de órdenes para un cliente
     *
     * @param Customer $customer
     * @return \Illuminate\View\View
     */
    public function showOrderOrganizer(Customer $customer)
    {
        // Cargar las líneas de producción del cliente con sus procesos
        $productionLines = $customer->productionLines()
            ->with('processes')
            ->get()
            ->filter(function($item) {
                return $item->processes->isNotEmpty(); // Filtra solo líneas con procesos
            });
            
        // Obtener procesos únicos con sus líneas
        $uniqueProcesses = collect();
        
        foreach ($productionLines as $line) {
            $process = $line->processes->first();
            if ($process) {
                $description = $process->description ?: 'Sin descripción';
                if (!$uniqueProcesses->has($description)) {
                    $uniqueProcesses->put($description, [
                        'process' => $process,
                        'lines' => collect()
                    ]);
                }
                $uniqueProcesses[$description]['lines']->push($line);
            }
        }
        
        // Ordenar por la descripción del proceso
        $sortedProcesses = $uniqueProcesses->sortBy(function($item) {
            return $item['process']->description ?: '';
        });
            
        return view('customers.order-organizer', [
            'customer' => $customer,
            'groupedProcesses' => $sortedProcesses,
            'totalLines' => $productionLines->count()
        ]);
    }
    
    /**
     * Muestra el tablero Kanban para un proceso específico
     *
     * @param  \App\Models\Customer  $customer
     * @param  \App\Models\Process  $process
     * @return \Illuminate\View\View
     */
    public function showOrderKanban(Customer $customer, \App\Models\Process $process)
    {
        // Guardar el proceso y cliente actuales en la sesión para que getKanbanData pueda acceder a ellos
        session(['current_process_id' => $process->id]);
        session(['current_customer_id' => $customer->id]);
        // Verificar que el proceso pertenece al cliente y obtener las líneas de producción
        $productionLines = $customer->productionLines()
            ->whereHas('processes', function($query) use ($process) {
                $query->where('process_id', $process->id);
            })
            ->with('processes')
            ->get();
            
        if ($productionLines->isEmpty()) {
            return redirect()->back()->with('error', 'No se encontraron líneas de producción para este proceso.');
        }
        
        // Obtener todas las órdenes para este proceso específico
    // Para status 0 y 1 (pendientes y en progreso) mostramos todas
    // Para status 2, 3, 4 y 5 (completadas, pausadas, canceladas e incidencias) solo mostramos las de los últimos 3 días
    $query = \App\Models\ProductionOrder::where('process_category', $process->description); // Filtrar por la categoría del proceso actual
    
    // Aplicamos filtros por status
    $query->where(function($q) {
        $fiveDaysAgo = now()->subDays(5)->startOfDay();
        
        // Status 0 y 1 (pendientes y en progreso) - mostrar todas
        $q->whereIn('status', [0, 1]);
        
        // Status 3, 4 y 5 (incidencias, pausadas, canceladas) - solo últimos 5 días
        $q->orWhere(function($subq) use ($fiveDaysAgo) {
            $subq->whereIn('status', [3, 4, 5])
                 ->where('updated_at', '>=', $fiveDaysAgo);
        });
    });

    // Ejecutamos la primera consulta para obtener órdenes con status 0, 1, 3, 4, 5
    $mainOrders = $query->get();

    // Consulta separada para status 2 (finalizadas) - últimos 5 días con límite de 100 tarjetas
    $status2Query = \App\Models\ProductionOrder::where('process_category', $process->description)
        ->where('status', 2)
        ->where('finished_at', '>=', now()->subDays(5)->startOfDay())
        ->orderBy('orden', 'desc')
        ->limit(100)
        ->get();

    // Unimos los resultados
    $processOrders = $mainOrders->merge($status2Query);
    
    // Ordenamos los resultados combinados
    $processOrders = $processOrders->sortBy('orden')->values()
            ->map(function($order){
                // Determinar el estado y color según el código de status
                $statusName = 'pending';
                $statusColor = '#6b7280'; // Gris por defecto
                
                switch ($order->status) {
                    case 0:
                        $statusName = 'pending'; // Pendiente
                        $statusColor = '#6b7280'; // Gris
                        break;
                    case 1:
                        $statusName = 'in_progress'; // En proceso
                        $statusColor = '#3b82f6'; // Azul
                        break;
                    case 2:
                        $statusName = 'completed'; // Finalizado
                        $statusColor = '#10b981'; // Verde
                        break;
                    case 3:
                        $statusName = 'paused'; // Pausado
                        $statusColor = '#f59e0b'; // Amarillo/ámbar
                        break;
                    case 4:
                        $statusName = 'cancelled'; // Cancelado
                        $statusColor = '#6b7280'; // Gris oscuro
                        break;
                    case 5:
                        $statusName = 'incidents'; // Con incidencia
                        $statusColor = '#ef4444'; // Rojo
                        break;
                }
                
                // 1. Preparamos la variable con el valor por defecto
                $tiempoTeoricoFormateado = 'Sin Tiempo Teórico';

                // 2. Si existe el tiempo teórico en segundos, lo convertimos
                if (isset($order->theoretical_time)) {
                    // *** CORRECCIÓN: Llamada al método estático con `self::` ***
                    $tiempoTeoricoFormateado = self::convertirSegundosA_H_M_S($order->theoretical_time);
                }
                
                // Obtener las descripciones de artículos asociados al proceso
                $articlesDescriptions = [];
                if ($order->original_order_process_id) {
                    $articles = \App\Models\OriginalOrderArticle::where('original_order_process_id', $order->original_order_process_id)
                        ->pluck('descripcion_articulo')
                        ->filter() // Filtrar valores nulos o vacíos
                        ->toArray();
                    $articlesDescriptions = $articles;
                }
                
                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'status' => $statusName,
                    'status_code' => $order->status,
                    'productionLineId' => $order->production_line_id,
                    'box' => $order->box ?? 0,
                    'units' => $order->units ?? 0,
                    'created_at' => $order->created_at,
                    'delivery_date' => $order->delivery_date,
                    'json' => $order->json ?? [],
                    'statusColor' => $statusColor,
                    'grupo_numero' => $order->grupo_numero ?? '0',
                    'processes_to_do' => $order->processes_to_do ?? 'Sin Procesos',
                    'processes_done' => $order->processes_done ?? '',
                    'theoretical_time' => $tiempoTeoricoFormateado,
                    'customerId' => $order->customerId ?? 'Sin Cliente',
                    'original_order_id' => $order->original_order_id ?? 'Sin Orden Original',
                    'articles_descriptions' => $articlesDescriptions,
                //en lugar de 0 por defecto aqui 'orden' => (int)($order->orden ?? '0') ponemos que sea por production_line_id el orden mas grande que existe y le damos +1
                'orden' => $order->production_line_id ? ProductionOrder::where('production_line_id', $order->production_line_id)->max('orden') + 1 : 0,
                'has_stock' => $order->has_stock ?? 1, // Añadimos el campo has_stock, por defecto 1 si no existe
                'is_priority' => $order->is_priority ?? false,
                'accumulated_time' => $order->accumulated_time ?? 0,
                'fecha_pedido_erp' => $order->fecha_pedido_erp,  
                'estimated_start_datetime' => $order->estimated_start_datetime,
                'estimated_end_datetime' => $order->estimated_end_datetime,
                'number_of_pallets' => $order->number_of_pallets ?? 0,
                ];
            });
        
        // Registrar en el log para depuración
        \Log::info('Órdenes para el proceso ' . $process->description . ':', [
            'count' => $processOrders->count(),
            'process_id' => $process->id
        ]);
        
        // Preparar datos de líneas de producción para la vista
        $productionLinesData = $productionLines->map(function($line) {
            return [
                'id' => $line->id,
                'name' => $line->name,
                'token' => $line->token // Añadimos el token de la línea de producción
            ];
        })->toArray();
        
        // Registrar en el log para depuración
        \Log::info('Líneas de producción para el proceso ' . $process->id . ':', $productionLinesData);
        
        return view('customers.order-kanban', [
            'customer' => $customer,
            'process' => $process,
            'productionLines' => $productionLinesData,
            'processOrders' => $processOrders
        ]);
    }
    /**
     * Obtiene los datos del Kanban para actualización mediante AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getKanbanData()
    {
        // Recuperar el customer actual de la sesión
        $customerId = session('current_customer_id');
        $customer = \App\Models\Customer::findOrFail($customerId);
        // Recuperar el proceso actual de la sesión
        $processId = session('current_process_id');
        $process = \App\Models\Process::findOrFail($processId);
        
        // Obtener todas las órdenes para este proceso específico
        // Para status 0 y 1 (pendientes y en progreso) mostramos todas
        // Para status 2, 3, 4 y 5 (completadas, pausadas, canceladas e incidencias) solo mostramos las de los últimos 5 días
        $query = \App\Models\ProductionOrder::where('process_category', $process->description); // Filtrar por la categoría del proceso actual

        // Aplicamos filtros por status
        $query->where(function($q) {
            $fiveDaysAgo = now()->subDays(5)->startOfDay();
            
            // Status 0 y 1 (pendientes y en progreso) - mostrar todas
            $q->whereIn('status', [0, 1]);
            
            // Status 3, 4 y 5 (incidencias, pausadas, canceladas) - solo últimos 5 días
            $q->orWhere(function($subq) use ($fiveDaysAgo) {
                $subq->whereIn('status', [3, 4, 5])
                     ->where('updated_at', '>=', $fiveDaysAgo);
            });
        });

        // Ejecutamos la primera consulta para obtener órdenes con status 0, 1, 3, 4, 5
        $mainOrders = $query->get();
        
        // Consulta separada para status 2 (finalizadas) - últimos 5 días con límite de 100 tarjetas
        $status2Query = \App\Models\ProductionOrder::where('process_category', $process->description)
            ->where('status', 2)
            ->where('finished_at', '>=', now()->subDays(5)->startOfDay())
            ->orderBy('orden', 'desc')
            ->limit(100)
            ->get();

        // Unimos los resultados
        $processOrders = $mainOrders->merge($status2Query);
        
        // Ordenamos los resultados combinados
        $processOrders = $processOrders->sortBy('orden')->values()
                ->map(function($order){
                    // Determinar el estado y color según el código de status
                    $statusName = 'pending';
                    $statusColor = '#6b7280'; // Gris por defecto
                    
                    switch ($order->status) {
                        case 0:
                            $statusName = 'pending'; // Pendiente
                            $statusColor = '#6b7280'; // Gris
                            break;
                        case 1:
                            $statusName = 'in_progress'; // En proceso
                            $statusColor = '#3b82f6'; // Azul
                            break;
                        case 2:
                            $statusName = 'completed'; // Finalizado
                            $statusColor = '#10b981'; // Verde
                            break;
                        case 3:
                            $statusName = 'paused'; // Pausado
                            $statusColor = '#f59e0b'; // Amarillo/ámbar
                            break;
                        case 4:
                            $statusName = 'cancelled'; // Cancelado
                            $statusColor = '#6b7280'; // Gris oscuro
                            break;
                        case 5:
                            $statusName = 'incidents'; // Con incidencia
                            $statusColor = '#ef4444'; // Rojo
                            break;
                    }
                    
                    // 1. Preparamos la variable con el valor por defecto
                    $tiempoTeoricoFormateado = 'Sin Tiempo Teórico';

                    // 2. Si existe el tiempo teórico en segundos, lo convertimos
                    if (isset($order->theoretical_time)) {
                        $tiempoTeoricoFormateado = self::convertirSegundosA_H_M_S($order->theoretical_time);
                    }
                    
                    // Obtener las descripciones de artículos asociados al proceso
                    $articlesDescriptions = [];
                    if ($order->original_order_process_id) {
                        $articles = \App\Models\OriginalOrderArticle::where('original_order_process_id', $order->original_order_process_id)
                            ->pluck('descripcion_articulo')
                            ->filter() // Filtrar valores nulos o vacíos
                            ->toArray();
                        $articlesDescriptions = $articles;
                    }
                    
                    return [
                        'id' => $order->id,
                        'order_id' => $order->order_id,
                        'status' => $statusName,
                        'status_code' => $order->status,
                        'productionLineId' => $order->production_line_id,
                        'box' => $order->box ?? 0,
                        'units' => $order->units ?? 0,
                        'created_at' => $order->created_at,
                        'delivery_date' => $order->delivery_date,
                        'json' => $order->json ?? [],
                        'statusColor' => $statusColor,
                        'grupo_numero' => $order->grupo_numero ?? '0',
                        'processes_to_do' => $order->processes_to_do ?? 'Sin Procesos',
                        'processes_done' => $order->processes_done ?? '',
                        'theoretical_time' => $tiempoTeoricoFormateado,
                        'customerId' => $order->customerId ?? 'Sin Cliente',
                        'original_order_id' => $order->original_order_id ?? 'Sin Orden Original',
                        'articles_descriptions' => $articlesDescriptions,
                        'orden' => $order->orden ?? 0,
                        'has_stock' => $order->has_stock ?? 1,
                        'is_priority' => $order->is_priority ?? false,
                        'accumulated_time' => $order->accumulated_time ?? 0,
                        'fecha_pedido_erp' => $order->fecha_pedido_erp,
                        'estimated_start_datetime' => $order->estimated_start_datetime,
                        'estimated_end_datetime' => $order->estimated_end_datetime,
                        'number_of_pallets' => $order->number_of_pallets ?? 0,
                        'note' => $order->note,
                    ];
                });

        return response()->json([
            'processOrders' => $processOrders
        ]);
    }
    
    /**
     * Convierte un número total de segundos a formato HH:MM:SS.
     *
     * @param int $segundos El número total de segundos.
     * @return string El tiempo formateado como "H:i:s".
     */
    private function convertirSegundosA_H_M_S(int $segundos) {
        // Evita valores negativos o no numéricos
        if (!is_numeric($segundos) || $segundos < 0) {
            return '00:00:00';
        }

        // Calcula horas, minutos y segundos
        $horas = floor($segundos / 3600);
        $minutos = floor(($segundos % 3600) / 60);
        $segundos_restantes = $segundos % 60;

        // Formatea la salida para que siempre tenga dos dígitos (01, 02, etc.)
        return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos_restantes);
    }
    /**
     * Muestra el formulario para editar un cliente existente.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        try {
            // Cargar el cliente con sus mapeos de campos ordenados
            $customer = Customer::with([
                'fieldMappings' => function($query) {
                    $query->orderBy('id');
                },
                'processFieldMappings' => function($query) {
                    $query->orderBy('id');
                },
                'articleFieldMappings' => function($query) {
                    $query->orderBy('id');
                }
            ])->findOrFail($id);
            
            // Campos estándar que podríamos querer mapear para orders
            $standardFields = [
                'order_id' => 'ID del Pedido',
                'client_number' => 'Número de Cliente',
                'created_at' => 'Fecha de Creación',
                'delivery_date' => 'Fecha de Entrega',
                'fecha_pedido_erp' => 'Fecha de Creación en ERP',
                'in_stock' => 'En Stock (1/0)'
            ];
            
            // Campos estándar que podríamos querer mapear para procesos
            $processStandardFields = [
                'process_id' => 'ID del Proceso',
                'time' => 'Tiempo del Proceso',
                'box' => 'Caja',
                'units_box' => 'Unidades por Caja',
                'number_of_pallets' => 'Número de Palets'
            ];
            
            // Opciones de transformaciones disponibles
            $transformationOptions = [
                'trim' => 'Eliminar espacios',
                'uppercase' => 'Convertir a mayúsculas',
                'lowercase' => 'Convertir a minúsculas',
                'to_integer' => 'Convertir a entero',
                'to_float' => 'Convertir a decimal',
                'to_boolean' => 'Convertir a booleano (1/0)'
            ];
            
            // Define article standard fields
            $articleStandardFields = [
                'codigo_articulo' => 'Código de Artículo (Requerido)',
                'descripcion_articulo' => 'Descripción del Artículo',
                'grupo_articulo' => 'Grupo del Artículo',
                'in_stock' => 'En Stock (1/0)'
            ];
            
            return view('customers.edit', compact(
                'customer', 
                'standardFields', 
                'processStandardFields', 
                'articleStandardFields',
                'transformationOptions'
            ));
            
        } catch (\Exception $e) {
            \Log::error('Error al cargar el formulario de edición del cliente: ' . $e->getMessage());
            return redirect()->route('customers.index')
                ->with('error', 'Error al cargar el formulario de edición: ' . $e->getMessage());
        }
    }

    /**
     * Validate that the URL is valid, including URLs with {order_id} placeholders
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function validateUrlWithPlaceholder($attribute, $value, $parameters, $validator)
    {
        if (empty($value)) {
            return true;
        }
        
        // Replace the {order_id} placeholder with a valid ID for validation
        $testUrl = str_replace('{order_id}', '12345', $value);
        
        return filter_var($testUrl, FILTER_VALIDATE_URL) !== false;
    }
    
    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        // Validación personalizada
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'order_listing_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de listado de pedidos no es válido.');
                }
            }],
            'order_detail_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de detalle de pedido no es válido.');
                }
            }],
            'token' => 'nullable|string|max:255',
            'field_mappings' => 'nullable|array',
            'field_mappings.*.source_field' => 'required_with:field_mappings|string',
            'field_mappings.*.target_field' => 'required_with:field_mappings|string',
            'field_mappings.*.transformations' => 'nullable|array',
            'field_mappings.*.transformations.*' => 'string',
            'field_mappings.*.is_required' => 'nullable|boolean',
            'process_field_mappings' => 'nullable|array',
            'process_field_mappings.*.source_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.target_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.transformations' => 'nullable|array',
            'process_field_mappings.*.transformations.*' => 'string',
            'process_field_mappings.*.is_required' => 'nullable|boolean',
            'article_field_mappings' => 'nullable|array',
            'article_field_mappings.*.source_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.target_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.transformations' => 'nullable|array',
            'article_field_mappings.*.transformations.*' => 'string',
            'article_field_mappings.*.is_required' => 'nullable|boolean'
        ]);

        // Validar la solicitud
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        try {
            // Iniciar transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // Actualizar los datos básicos del cliente
            $customer->update([
                'name' => $validatedData['name'],
                'order_listing_url' => $validatedData['order_listing_url'] ?? null,
                'order_detail_url' => $validatedData['order_detail_url'] ?? null,
                'token' => $validatedData['token'] ?? null,
            ]);

            // Sincronizar los mapeos de campos de orders si existen
            if (isset($validatedData['field_mappings'])) {
                $updatedMappingIds = [];
                
                // Procesar cada mapeo
                foreach ($validatedData['field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->fieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformations' => $mappingData['transformations'] ?? [],
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->fieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformations' => $mappingData['transformations'] ?? [],
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedMappingIds)) {
                    $customer->fieldMappings()->whereNotIn('id', $updatedMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->fieldMappings()->delete();
            }

            // Sincronizar los mapeos de campos de procesos si existen
            if (isset($validatedData['process_field_mappings'])) {
                $updatedProcessMappingIds = [];
                
                // Procesar cada mapeo de proceso
                foreach ($validatedData['process_field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->processFieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformations' => $mappingData['transformations'] ?? [],
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedProcessMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->processFieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformations' => $mappingData['transformations'] ?? [],
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedProcessMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedProcessMappingIds)) {
                    $customer->processFieldMappings()->whereNotIn('id', $updatedProcessMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->processFieldMappings()->delete();
            }

            // Sincronizar los mapeos de campos de artículos si existen
            if (isset($validatedData['article_field_mappings'])) {
                $updatedArticleMappingIds = [];
                
                // Procesar cada mapeo de artículo
                foreach ($validatedData['article_field_mappings'] as $mappingData) {
                    $mappingId = $mappingData['id'] ?? null;
                    
                    if ($mappingId) {
                        // Actualizar mapeo existente
                        $mapping = $customer->articleFieldMappings()->find($mappingId);
                        if ($mapping) {
                            $mapping->update([
                                'source_field' => $mappingData['source_field'],
                                'target_field' => $mappingData['target_field'],
                                'transformations' => $mappingData['transformations'] ?? [],
                                'is_required' => $mappingData['is_required'] ?? false,
                            ]);
                            $updatedArticleMappingIds[] = $mapping->id;
                        }
                    } else {
                        // Crear nuevo mapeo
                        $mapping = $customer->articleFieldMappings()->create([
                            'source_field' => $mappingData['source_field'],
                            'target_field' => $mappingData['target_field'],
                            'transformations' => $mappingData['transformations'] ?? [],
                            'is_required' => $mappingData['is_required'] ?? false,
                        ]);
                        $updatedArticleMappingIds[] = $mapping->id;
                    }
                }
                
                // Eliminar mapeos que no están en la lista actualizada
                if (!empty($updatedArticleMappingIds)) {
                    $customer->articleFieldMappings()->whereNotIn('id', $updatedArticleMappingIds)->delete();
                }
            } else {
                // Si no hay mapeos, eliminar todos los existentes
                $customer->articleFieldMappings()->delete();
            }

            // Confirmar la transacción
            DB::commit();

            return redirect()->route('customers.edit', $customer->id)
                ->with('success', 'Cliente actualizado correctamente.');

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            \Log::error('Error al actualizar el cliente: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el cliente: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
    
    /**
     * Devuelve el HTML para una fila de mapeo de campos
     *
     * @param int $customerId
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function fieldMappingRow($customerId, Request $request)
    {
        try {
            $index = $request->input('index', 0);
            $type = $request->input('type', 'order'); // 'order', 'process' o 'article'
            
            if ($type === 'process') {
                // Campos estándar para procesos
                $standardFields = [
                    'process_id' => 'ID del Proceso',
                    'time' => 'Tiempo del Proceso',
                    'box' => 'Caja',
                    'units_box' => 'Unidades por Caja'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de procesos
                $html = view('customers.partials.process_field_mappings', [
                    'index' => $index,
                    'processStandardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
                
            } else if ($type === 'article') {
                // Campos estándar para artículos
                $standardFields = [
                    'codigo_articulo' => 'Código de Artículo (Requerido)',
                    'descripcion_articulo' => 'Descripción del Artículo',
                    'grupo_articulo' => 'Grupo del Artículo',
                    'in_stock' => 'En Stock (1/0)'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de artículos
                $html = view('customers.partials.article_field_mappings', [
                    'index' => $index,
                    'articleStandardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
                
            } else {
                // Usar el mismo array de campos estándar que en create/edit para orders
                $standardFields = [
                    'order_id' => 'ID del Pedido',
                    'client_number' => 'Número de Cliente',
                    'created_at' => 'Fecha de Creación',
                    'delivery_date' => 'Fecha de Entrega',
                    'fecha_pedido_erp' => 'Fecha de Creación en ERP',
                    'in_stock' => 'En Stock (1/0)'
                ];
                
                // Opciones de transformaciones disponibles
                $transformationOptions = [
                    'trim' => 'Eliminar espacios',
                    'uppercase' => 'Convertir a mayúsculas',
                    'lowercase' => 'Convertir a minúsculas',
                    'to_integer' => 'Convertir a entero',
                    'to_float' => 'Convertir a decimal',
                    'to_boolean' => 'Convertir a booleano (1/0)'
                ];
                
                // Renderizar la vista parcial para la fila de mapeo de orders
                $html = view('customers.partials.field_mappings', [
                    'index' => $index,
                    'standardFields' => $standardFields,
                    'transformationOptions' => $transformationOptions,
                    'mapping' => null
                ])->render();
            }
            
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en fieldMappingRow: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la fila de mapeo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        // Campos estándar que podríamos querer mapear
        $standardFields = [
            'order_id' => 'ID del Pedido',
            'client_number' => 'Número de Cliente',
            'created_at' => 'Fecha de Creación',
            'delivery_date' => 'Fecha de Entrega',
            'in_stock' => 'En Stock (1/0)'
        ];
        
        // Campos estándar para procesos
        $processStandardFields = [
            'process_id' => 'ID del Proceso',
            'time' => 'Tiempo del Proceso',
            'box' => 'Caja',
            'units_box' => 'Unidades por Caja',
            'number_of_pallets' => 'Número de Palets'
        ];
        
        // Campos estándar para artículos
        $articleStandardFields = [
            'codigo_articulo' => 'Código de Artículo (Requerido)',
            'descripcion_articulo' => 'Descripción del Artículo',
            'grupo_articulo' => 'Grupo del Artículo',
            'in_stock' => 'En Stock (1/0)'
        ];
        
        // Opciones de transformaciones disponibles
        $transformationOptions = [
            'trim' => 'Eliminar espacios',
            'uppercase' => 'Convertir a mayúsculas',
            'lowercase' => 'Convertir a minúsculas',
            'to_integer' => 'Convertir a entero',
            'to_float' => 'Convertir a decimal',
            'to_boolean' => 'Convertir a booleano (1/0)'
        ];
        
        return view('customers.create', compact(
            'standardFields', 
            'processStandardFields',
            'articleStandardFields',
            'transformationOptions'
        ));
    }

    public function store(Request $request)
    {
        // Validación personalizada
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token_zerotier' => 'required|string|max:255',
            'order_listing_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de listado de pedidos no es válido.');
                }
            }],
            'order_detail_url' => ['nullable', function($attribute, $value, $fail) {
                if (!empty($value) && !$this->validateUrlWithPlaceholder($attribute, $value, null, null)) {
                    $fail('El formato de la URL de detalle de pedido no es válido.');
                }
            }],
            'field_mappings' => 'nullable|array',
            'field_mappings.*.source_field' => 'required_with:field_mappings|string',
            'field_mappings.*.target_field' => 'required_with:field_mappings|string',
            'field_mappings.*.transformations' => 'nullable|array',
            'field_mappings.*.transformations.*' => 'string',
            'field_mappings.*.is_required' => 'nullable|boolean',
            'process_field_mappings' => 'nullable|array',
            'process_field_mappings.*.source_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.target_field' => 'required_with:process_field_mappings|string',
            'process_field_mappings.*.transformations' => 'nullable|array',
            'process_field_mappings.*.transformations.*' => 'string',
            'process_field_mappings.*.is_required' => 'nullable|boolean',
            'article_field_mappings' => 'nullable|array',
            'article_field_mappings.*.source_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.target_field' => 'required_with:article_field_mappings|string',
            'article_field_mappings.*.transformations' => 'nullable|array',
            'article_field_mappings.*.transformations.*' => 'string',
            'article_field_mappings.*.is_required' => 'nullable|boolean'
        ]);

        // Validar la solicitud
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        try {
            // Iniciar transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // Generar un token único
            $token = bin2hex(random_bytes(16));

            // Crear el cliente
            $customer = Customer::create([
                'name' => $validatedData['name'],
                'token_zerotier' => $validatedData['token_zerotier'],
                'token' => $token,
                'order_listing_url' => $validatedData['order_listing_url'] ?? null,
                'order_detail_url' => $validatedData['order_detail_url'] ?? null,
            ]);

            // Sincronizar los mapeos de campos si existen
            if (isset($validatedData['field_mappings'])) {
                foreach ($validatedData['field_mappings'] as $mappingData) {
                    $customer->fieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Sincronizar los mapeos de procesos si existen
            if (isset($validatedData['process_field_mappings'])) {
                foreach ($validatedData['process_field_mappings'] as $mappingData) {
                    $customer->processFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Sincronizar los mapeos de artículos si existen
            if (isset($validatedData['article_field_mappings'])) {
                foreach ($validatedData['article_field_mappings'] as $mappingData) {
                    $customer->articleFieldMappings()->create([
                        'source_field' => $mappingData['source_field'],
                        'target_field' => $mappingData['target_field'],
                        'transformations' => $mappingData['transformations'] ?? [],
                        'is_required' => $mappingData['is_required'] ?? false,
                    ]);
                }
            }

            // Confirmar la transacción
            DB::commit();

            return redirect()->route('customers.edit', $customer->id)
                ->with('success', 'Cliente creado correctamente.');

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            \Log::error('Error al crear el cliente: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error al crear el cliente: ' . $e->getMessage())
                ->withInput();
        }
    }
}
