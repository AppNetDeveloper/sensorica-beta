<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Importar Facade DB para la consulta
use Illuminate\Support\Facades\Cache;

class ProductionOrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/production-orders",
     *     summary="Listar órdenes de producción",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token único de la línea de producción",
     *         required=true,
     *         @OA\Schema(type="string", example="abcd1234")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado de la orden",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Columna para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", example="orden")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Dirección de ordenación (asc o desc)",
     *         required=false,
     *         @OA\Schema(type="string", example="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes de producción",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="status", type="integer"),
     *                 @OA\Property(property="box", type="integer"),
     *                 @OA\Property(property="units_box", type="integer"),
     *                 @OA\Property(property="orden", type="integer"),
     *             )),
     *             @OA\Property(property="total", type="integer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token no proporcionado o no válido"
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Validar que se proporcione el token
        $request->validate([
            'token' => 'required|string',
        ]);

        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $request->token)->first();

        if (!$productionLine) {
            return response()->json([
                'message' => 'Línea de producción no encontrada o token inválido'
            ], 400);
        }

        $query = ProductionOrder::where(function($q) use ($productionLine) {
            // Órdenes de esta línea que no son status 3
            $q->where('production_line_id', $productionLine->id)
              ->where('status', '!=', 3);
        })->orWhere(function($q) {
            // O bien, órdenes con status 3 (de cualquier línea)
            $q->where('status', 3);
        });

        // Aplicar filtro de status si se especifica
        $status = $request->input('status', 'all');
        
        if ($status !== 'all') {
            $query->where('status', $status);
            
            // Aplicar ordenamiento específico por status
            if (in_array($status, [0, 1])) {
                // Status 0 y 1: orden ascendente (más pequeño primero)
                $query->orderBy('orden', 'asc');
            } elseif (in_array($status, [2, 4, 5])) {
                // Status 2, 4 y 5: orden descendente (más grande primero)
                $query->orderBy('orden', 'desc');
            }
            // Status 3 no necesita orden específico
        } else {
            // Orden por defecto cuando no hay filtro de status
            $query->orderBy('status', 'asc')
                  ->orderBy('orden', 'asc');
        }
        
        // Paginación: 15 elementos por defecto, excepto para status 0 y 3 que son ilimitados
        if (in_array($status, [0, 3])) {
            $orders = $query->get();
            return response()->json([
                'data' => $orders,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $orders->count(),
                'total' => $orders->count(),
                'has_more_pages' => false,
                'next_page_url' => null,
            ]);
        }
        
        // Paginación normal para otros status
        $perPage = $request->input('per_page', 15);
        $orders = $query->paginate($perPage);

        // Incluir información de paginación en la respuesta
        return response()->json([
            'data' => $orders->items(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'has_more_pages' => $orders->hasMorePages(),
            'next_page_url' => $orders->nextPageUrl(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/kanban/orders",
     *     summary="Obtener órdenes optimizadas para el tablero Kanban",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token único de la línea de producción",
     *         required=true,
     *         @OA\Schema(type="string", example="abcd1234")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes optimizada para el Kanban",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="fecha_pedido_erp", type="string", format="date-time", nullable=true)
     *             ))
     *         )
     *     )
     * )
     */
    public function getKanbanOrders(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $request->token)->first();

        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'Línea de producción no encontrada o token inválido',
            ], 400);
        }

        // 1. Órdenes activas (status 0, 1)
        $activeOrders = ProductionOrder::where('production_line_id', $productionLine->id)
            ->whereIn('status', ['0', '1'])
            ->orderBy('orden', 'asc')
            ->get();

        // 2. Órdenes finalizadas (status 2) solo de las últimas 8 horas
        $eightHoursAgo = now()->subHours(8);
        $finishedOrders = ProductionOrder::where('production_line_id', $productionLine->id)
            ->where('status', 2)
            ->where('updated_at', '>=', $eightHoursAgo)
            ->orderBy('orden', 'asc')
            ->limit(30)
            ->get();

        // Obtener el process_id asociado a la línea de producción desde production_line_process
        $productionLineProcess = DB::table('production_line_process')
            ->where('production_line_id', $productionLine->id)
            ->first();
            
        // Inicializar variable para process_category
        $processCategory = null;
        
        if ($productionLineProcess && isset($productionLineProcess->process_id)) {
            // Obtener la descripción/categoría del proceso desde la tabla processes
            $process = DB::table('processes')
                ->where('id', $productionLineProcess->process_id)
                ->first();
                
            if ($process && isset($process->description)) {
                $processCategory = $process->description;
            }
        }
        
        // Si no se pudo obtener el process_category desde production_line_process,
        // intentar obtenerlo desde la última orden (método anterior)
        if (!$processCategory) {
            $lastOrder = ProductionOrder::where('production_line_id', $productionLine->id)
                ->orderBy('id', 'desc')
                ->first();
                
            if ($lastOrder && isset($lastOrder->process_category)) {
                $processCategory = $lastOrder->process_category;
            }
        }
        
        // Log para depuración
        //Log::info('Process Category para incidencias:', [
        //    'production_line_id' => $productionLine->id,
        //    'process_category' => $processCategory
        //]);
        
        // 3. Incidencias SIEMPRE (status 3)
        // Si tenemos process_category, filtramos por él, sino traemos todas las incidencias
        $incidentOrdersQuery = ProductionOrder::where('status', 3);
        
        if ($processCategory) {
            $incidentOrdersQuery->where('process_category', $processCategory);
        }
        
        $incidentOrders = $incidentOrdersQuery->orderBy('orden', 'asc')->get();
    
        // Log detallado para diagnóstico de incidencias
        //Log::info('Diagnóstico de incidencias:', [
        //    'production_line_id' => $productionLine->id,
        //    'production_line_name' => $productionLine->name ?? 'N/A',
        //    'incidencias_count' => $incidentOrders->count(),
        //    'incidencias' => $incidentOrders->toArray()
        //]);

        // Asegurarnos de que todas las colecciones son válidas
        if (!$activeOrders) $activeOrders = collect();
        if (!$finishedOrders) $finishedOrders = collect();
        if (!$incidentOrders) $incidentOrders = collect();
        
        // Combinar todas las órdenes y ordenar por el campo orden
        $orders = collect();
        $orders = $orders->concat($activeOrders)->concat($finishedOrders)->concat($incidentOrders);
        $orders = $orders->sortBy('orden')->values();

        // Añadir información de diagnóstico en la respuesta
        return response()->json([
            'success' => true,
            'data' => $orders,
            'debug' => [
                'production_line_id' => $productionLine->id,
                'production_line_name' => $productionLine->name ?? 'N/A',
                'active_orders_count' => $activeOrders->count(),
                'finished_orders_count' => $finishedOrders->count(),
                'incident_orders_count' => $incidentOrders->count(),
                'total_orders_count' => $orders->count(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'timezone' => config('app.timezone')
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/production-orders/{id}",
     *     summary="Obtener detalles de una orden específica",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la orden de producción",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la orden de producción",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="order_id", type="string"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(property="box", type="integer"),
     *             @OA\Property(property="units_box", type="integer"),
     *             @OA\Property(property="orden", type="integer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $order = ProductionOrder::find($id);

        if (!$order) {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }

        return response()->json($order);
    }

    /**
     * @OA\Patch(
     *     path="/api/production-orders/{id}",
     *     summary="Actualizar orden o estado de una orden",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la orden de producción",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="orden", type="integer", description="Nuevo valor del orden", example=5),
     *             @OA\Property(property="status", type="integer", description="Nuevo estado de la orden", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orden actualizada exitosamente."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="id", type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Orden no encontrada.")
     *         )
     *     )
     * )
     */
    public function updateOrder(Request $request, $id)
    {

        $request->validate([
            'token' => 'required|string',
            'orden' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|min:0|max:5',
        ]);
    
        $validatedData = $request->all();

        $order = ProductionOrder::find($id);
    
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }
    
        // 🔥 AGREGAR ESTA VALIDACIÓN:
        $productionLine = ProductionLine::where('token', $request->token)->first();
        
        if (!$productionLine) {
            return response()->json(['success' => false, 'message' => 'Token inválido.'], 400);
        }
        
        // 🔥 VERIFICAR PERMISOS PARA MODIFICAR LA ORDEN:
        // Caso 1: Si la orden pertenece a esta línea, permitir modificación
        // Caso 2: Si la orden es una incidencia (status 3) y tiene el mismo process_category, permitir modificación
        $lastOrders = ProductionOrder::where('production_line_id', $productionLine->id)->orderBy('id', 'desc')->take(1)->get();
        
        $canModify = false;
        
        // Caso 1: La orden pertenece a esta línea
        if ($order->production_line_id === $productionLine->id) {
            $canModify = true;
            Log::info("Orden ID {$id}: Permiso concedido - La orden pertenece a esta línea de producción");
        } 
        // Caso 2: Es una incidencia y tiene el mismo process_category que la línea
        elseif ($order->status == 3) {
            // Obtener el process_id asociado a la línea de producción desde production_line_process
            $productionLineProcess = DB::table('production_line_process')
                ->where('production_line_id', $productionLine->id)
                ->first();
            
            // Inicializar variable para process_category
            $processCategory = null;
            
            if ($productionLineProcess && isset($productionLineProcess->process_id)) {
                // Obtener la descripción/categoría del proceso desde la tabla processes
                $process = DB::table('processes')
                    ->where('id', $productionLineProcess->process_id)
                    ->first();
                    
                if ($process && isset($process->description)) {
                    $processCategory = $process->description;
                }
            }
            
            Log::info("Orden ID {$id}: Verificando incidencia - Process category de orden: {$order->process_category}, Process category de línea: {$processCategory}");
            
            if ($order->process_category == $processCategory) {
                $canModify = true;
                Log::info("Orden ID {$id}: Permiso concedido - Es una incidencia con el mismo process_category");
            }
        }
        
        // Caso 3: Si la línea no tiene órdenes previas, permitir modificación
        if (!$canModify) {
            $hasOrders = ProductionOrder::where('production_line_id', $productionLine->id)->exists();
            if (!$hasOrders) {
                $canModify = true;
                Log::info("Orden ID {$id}: Permiso concedido - La línea no tiene órdenes previas");
            }
        }
        
        if (!$canModify) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos para modificar esta orden.'], 403);
        }
        // --- VERIFICACIÓN DE CAMBIOS (LA PARTE CLAVE) ---
    
        // Comprobamos si los campos que nos importan vienen en la petición Y si su valor es diferente al actual.
        // Usamos `isset` para evitar el error si el campo no viene en el JSON.
        $hasStatusChanged = isset($validatedData['status']) && $order->status != $validatedData['status'];
        $hasOrderChanged = isset($validatedData['orden']) && $order->orden != $validatedData['orden'];
    
        // Si NADA ha cambiado, salimos inmediatamente.
        if (!$hasStatusChanged && !$hasOrderChanged) {
            Log::info("Orden ID {$id}: Sin cambios en status u orden. Actualización omitida.");
            return response()->json(['success' => true, 'message' => 'Sin cambios detectados, actualización omitida.']);
        }
    
        // --- SI LLEGAMOS AQUÍ, ES PORQUE SÍ HAY CAMBIOS ---
    
        Log::info("Orden ID {$id}: Cambios detectados. Procediendo con la actualización.");
    
        // El resto de la lógica de actualización...
        $productionLine = ProductionLine::where('token', $request->token)->first();
        if (!$productionLine) {
            return response()->json(['success' => false, 'message' => 'Línea de producción no encontrada o token inválido'], 400);
        }
        
        $currentProductionLineId = $productionLine->id;
    
        if ($hasStatusChanged && $validatedData['status'] == 3) {
            if (!$order->original_production_line_id) {
                $order->original_production_line_id = $order->production_line_id;
            }
        } 
        elseif ($order->status == 3 && $hasStatusChanged && $validatedData['status'] != 3) {
            $order->production_line_id = $currentProductionLineId;
        }
        // ❌ COMENTADO: Esta línea causaba que órdenes cambiaran de línea de producción
        // lo que podía resultar en duplicados de order_id
        // elseif ($order->production_line_id != $currentProductionLineId) {
        //     $order->production_line_id = $currentProductionLineId;
        // }
    
        // Actualizamos los valores en el modelo
        if ($hasOrderChanged) {
            $order->orden = $validatedData['orden'];
        }
        // Guardar el estado anterior antes de actualizar
        $previousStatus = null;
        if ($hasStatusChanged && isset($validatedData['status'])) {
            $previousStatus = $order->status; // Guardamos el estado anterior antes de actualizarlo
            $order->status = $validatedData['status'];
        }
    
        // Guardamos TODOS los cambios en la base de datos de una vez.
        $order->save();
        
        // --- LÓGICA MQTT ---
        // Solo si el estado ha cambiado, intentamos enviar el mensaje.
        if ($hasStatusChanged) {
            $lockKey = 'mqtt_lock_for_order_' . $order->order_id . '_line_' . $order->production_line_id;
            if (Cache::add($lockKey, true, 3)) {
                Log::info("Bloqueo de caché adquirido para [{$lockKey}]. Procesando envío MQTT.");
                try {
                    $order->refresh();
                    $currentStatus = (int)$order->status;
                    
                    // Asignar acción MQTT según el estado actual y el anterior
                    $action = match ($currentStatus) {
                        1 => 0,  // EN CURSO -> acción 0
                        2 => $previousStatus == 1 ? 1 : null,   // FINALIZADA -> acción 1
                        3 => $previousStatus == 1 ? 0 : null,  // Si viene de EN CURSO a INCIDENCIA, enviar acción 0
                        default => null,
                    };
                 
                    if ($action !== null) {
                        $barcoder = \App\Models\Barcode::where('production_line_id', $order->production_line_id)->first();
                        if ($barcoder && !empty($barcoder->mqtt_topic_barcodes)) {
                            // ... el resto de tu lógica MQTT ...
                            $topic = $barcoder->mqtt_topic_barcodes . '/prod_order_mac';
                            $messagePayload = json_encode([
                                "action"    => $action, 
                                "orderId"   => $order->order_id,
                                "quantity"  => 0,
                                "machineId" => $barcoder->machine_id ?? "", 
                                "opeId" => $barcoder->ope_id ?? "",
                            ]);
                            
                            // Si la orden se ha finalizado, activar la siguiente en la cola.
                            if ((int)$order->status === 2 || (int)$order->status === 3) {
                                $this->activateNextOrder($order, $barcoder);
                            }else{
                                $this->publishMqttMessage($topic, $messagePayload);
                            }
                            Log::info("Mensaje MQTT enviado para orden {$order->id}.");
                        } else {
                            Log::warning("Barcoder/Topic no encontrado para orden {$order->id}.");
                        }
            
                    }
                } catch (\Exception $e) {
                    Log::error("Error en MQTT para orden {$order->id}: " . $e->getMessage());
                }
            } else {
                Log::info("Envío MQTT omitido para orden {$order->id} por bloqueo de caché.");
            }
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Orden actualizada exitosamente.',
            'data' => $order,
        ]);
    }
    private function activateNextOrder(ProductionOrder $finishedOrder, $barcoder)
    {
        // ¡CORREGIDO! Usamos el modelo ProductionOrder, no 'self'.
        $nextOrderInLine = ProductionOrder::where('production_line_id', $finishedOrder->production_line_id)
                   // ->where('orden', '>', $finishedOrder->orden)
                    ->where('status', 0)
                    ->orderBy('orden', 'asc')
                    ->first();

        if ($nextOrderInLine) {
            Log::info("Orden [{$finishedOrder->id}] finalizada. Lógica para activar la siguiente orden [{$nextOrderInLine->id}] se ejecutaría aquí.");
            $topic = $barcoder->mqtt_topic_barcodes . '/prod_order_mac';
            $messagePayload = json_encode([
                "action"    => 0, 
                "orderId"   => $nextOrderInLine->order_id,
                "quantity"  => 0,
                "machineId" => $barcoder->machine_id ?? "", 
                "opeId" => $barcoder->ope_id ?? "",
            ]);
            //ponemos un sleep de 1 segundo para dar tiempo a que el sistema se actualice
            sleep(0.5);
            $this->publishMqttMessage($topic, $messagePayload);
        } else {
            Log::info("Orden [{$finishedOrder->id}] finalizada. No hay más órdenes en la cola para la línea [{$finishedOrder->production_line_id}].");
        }
    }
    /**
     * @OA\Post(
     *     path="/api/production-orders",
     *     summary="Crear una nueva orden de producción",
     *     tags={"ProductionOrders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="production_line_id", type="integer", example=1),
     *             @OA\Property(property="order_id", type="string", example="231118"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="box", type="integer", example=945),
     *             @OA\Property(property="units_box", type="integer", example=30),
     *             @OA\Property(property="units", type="integer", example=28350),
     *             @OA\Property(property="orden", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Orden creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orden creada exitosamente."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="id", type="integer"))
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'production_line_id' => 'required|integer|exists:production_lines,id',
            'order_id' => 'required|string',
            'status' => 'required|integer|min:0|max:5',
            'box' => 'required|integer',
            'units_box' => 'required|integer',
            'units' => 'required|integer',
            'orden' => 'required|integer|min:0',
        ]);

        $order = ProductionOrder::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Orden creada exitosamente.',
            'data' => $order,
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/production-orders/{id}",
     *     summary="Eliminar una orden de producción",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la orden de producción",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orden eliminada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada"
     *     )
     * )
     */
    public function destroy($id)
    {
        $order = ProductionOrder::find($id);

        if (!$order) {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Orden eliminada exitosamente.',
        ]);
}
    /**
     * Stores the MQTT message in two different server directories as JSON files.
     * This simulates publishing a message for later processing.
     *
     * @param string $topic The MQTT topic.
     * @param string $message The JSON message string.
     */
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Preparar los datos a almacenar, agregando la fecha y hora
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];
        
            // Convertir a JSON
            $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        
            // Sanitizar el topic para evitar creación de subcarpetas en el nombre del archivo
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generar un identificador único usando microtime para alta precisión
            $uniqueId = round(microtime(true) * 1000); // en milisegundos
        
            // Guardar en servidor 1
            $path1 = storage_path("app/mqtt/server1");
            if (!file_exists($path1)) {
                mkdir($path1, 0755, true);
            }
            $fileName1 = "{$path1}/{$sanitizedTopic}_{$uniqueId}.json";
            file_put_contents($fileName1, $jsonData . PHP_EOL);
            Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Comentado para reducir logs y carga del sistema
            // // Guardar en servidor 2
            // $path2 = storage_path("app/mqtt/server2");
            // if (!file_exists($path2)) {
            //     mkdir($path2, 0755, true);
            // }
            // $fileName2 = "{$path2}/{$sanitizedTopic}_{$uniqueId}.json";
            // file_put_contents($fileName2, $jsonData . PHP_EOL);
            // Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");

        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }

}
