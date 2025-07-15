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

        // Usa DataTables para procesar la consulta y añadir la columna de acción
        return DataTables::of($query)
            ->addColumn('action', function ($customer) {
                // URLs para las diferentes acciones
                $editUrl = route('customers.edit', $customer->id);
                // *** CAMBIO: Corregido el nombre del parámetro de 'customer' a 'customer_id' ***
                $productionLinesUrl = route('productionlines.index', ['customer_id' => $customer->id]); // Asegúrate que la ruta acepte el parámetro así
                $deleteUrl = route('customers.destroy', $customer->id);
                $csrfToken = csrf_token();
                // Genera URLs seguras si tu aplicación corre sobre HTTPS
                $liveViewUrl = secure_url('/modbuses/liststats/weight?token=' . $customer->token);
                $liveViewUrlProd = secure_url('/productionlines/liststats?token=' . $customer->token);

                // Construye el HTML para los botones con iconos (Font Awesome)
                // Añade un pequeño margen a la derecha del icono (me-1)
                // Inicializar botones
                $buttons = [];
                
                // Botón del organizador de órdenes (solo con permiso productionline-orders)
                if (auth()->user()->can('productionline-orders')) {
                    $orderOrganizerUrl = route('customers.order-organizer', $customer->id);
                    $orderOrganizerButton = "<a href='{$orderOrganizerUrl}' class='btn btn-sm btn-primary me-1' data-bs-toggle='tooltip' title='" . __('Order Organizer') . "'><i class='fas fa-tasks'></i></a>";
                    $buttons[] = $orderOrganizerButton;
                }

                // Botón de editar (solo con permiso productionline-edit)
                if (auth()->user()->can('productionline-edit')) {
                    $editButton = "<a href='{$editUrl}' class='btn btn-sm btn-info me-1' data-bs-toggle='tooltip' title='" . __('Edit') . "'><i class='fas fa-edit'></i></a>";
                    $buttons[] = $editButton;
                }

                // Botón de líneas de producción (solo con permiso productionline-show)
                if (auth()->user()->can('productionline-show')) {
                    $linesButton = "<a href='{$productionLinesUrl}' class='btn btn-sm btn-secondary me-1' data-bs-toggle='tooltip' title='" . __('Production Lines') . "'><i class='fas fa-sitemap'></i></a>";
                    $buttons[] = $linesButton;
                }

                // Botón de Órdenes Originales (solo con permiso productionline-orders)
                if (auth()->user()->can('productionline-orders')) {
                    $originalOrdersUrl = route('customers.original-orders.index', $customer->id);
                    $originalOrdersButton = "<a href='{$originalOrdersUrl}' class='btn btn-sm btn-dark me-1' data-bs-toggle='tooltip' title='" . __('Original Orders') . "'><i class='fas fa-clipboard-list'></i></a>";
                    $buttons[] = $originalOrdersButton;
                    
                    // Botón de Incidencias de Órdenes de Producción (con el mismo permiso que pedidos originales)
                    $incidentsUrl = route('customers.production-order-incidents.index', $customer->id);
                    $incidentsButton = "<a href='{$incidentsUrl}' class='btn btn-sm btn-danger me-1' data-bs-toggle='tooltip' title='" . __('Production Order Incidents') . "'><i class='fas fa-exclamation-triangle'></i></a>";
                    $buttons[] = $incidentsButton;
                }

                // Botón de estadísticas de peso
                $weightStatsButton = "<a href='{$liveViewUrl}' class='btn btn-sm btn-success me-1' data-bs-toggle='tooltip' title='" . __('Weight Stats') . "' target='_blank'><i class='fas fa-weight-hanging'></i></a>";
                $buttons[] = $weightStatsButton;
                
                // Botón de estadísticas de producción
                $productionStatsButton = "<a href='{$liveViewUrlProd}' class='btn btn-sm btn-warning me-1' data-bs-toggle='tooltip' title='" . __('Production Stats') . "' target='_blank'><i class='fas fa-chart-line'></i></a>";
                $buttons[] = $productionStatsButton;

                // Botón de eliminar (solo con permiso productionline-delete)
                if (auth()->user()->can('productionline-delete')) {
                    $deleteForm = "<form action='{$deleteUrl}' method='POST' style='display:inline;' onsubmit='return confirm(\"" . __('Are you sure?') . "\");'>
                                    <input type='hidden' name='_token' value='{$csrfToken}'>
                                    <input type='hidden' name='_method' value='DELETE'>
                                    <button type='submit' class='btn btn-sm btn-danger me-1' data-bs-toggle='tooltip' title='" . __('Delete') . "'><i class='fas fa-trash'></i></button>
                                   </form>";
                    $buttons[] = $deleteForm;
                }
                
                // Combinar todos los botones en un solo string HTML
                return "<div class='d-flex flex-wrap'>" . implode('', $buttons) . "</div>";
            })
            // Indica a DataTables que la columna 'action' contiene HTML y no debe ser escapada
            ->rawColumns(['action'])
            // Genera la respuesta JSON para DataTables
            ->make(true);
    }

     

    public function testCustomers()
    {
        $customers = Customer::all();
        dd($customers); 
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
        ->where('updated_at', '>=', now()->subDays(5)->startOfDay())
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
                'is_priority' => $order->is_priority ?? false
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
                'name' => $line->name
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
            ->where('updated_at', '>=', now()->subDays(5)->startOfDay())
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
                        'is_priority' => $order->is_priority ?? false
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
                'in_stock' => 'En Stock (1/0)'
            ];
            
            // Campos estándar que podríamos querer mapear para procesos
            $processStandardFields = [
                'process_id' => 'ID del Proceso',
                'time' => 'Tiempo del Proceso'
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
            $type = $request->input('type', 'order'); // 'order' o 'process'
            
            if ($type === 'process') {
                // Campos estándar para procesos
                $standardFields = [
                    'process_id' => 'ID del Proceso',
                    'time' => 'Tiempo del Proceso'
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
                
            } else {
                // Usar el mismo array de campos estándar que en create/edit para orders
                $standardFields = [
                    'order_id' => 'ID del Pedido',
                    'client_number' => 'Número de Cliente',
                    'created_at' => 'Fecha de Creación',
                    'delivery_date' => 'Fecha de Entrega',
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
            'time' => 'Tiempo del Proceso'
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
