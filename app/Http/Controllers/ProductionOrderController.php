<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Models\Barcode;
use Carbon\Carbon;

class ProductionOrderController extends Controller
{
    public function index()
    {
        return view('productionorder.index'); // Cargar el Blade de producción
    }
    
    /**
     * Actualiza múltiples órdenes de producción en lote
     * Recibe un array de órdenes con sus nuevos valores de production_line_id, orden y status
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBatch(Request $request)
    {
       // Log::info('=== INICIO: Actualización por lotes de órdenes de producción ===');
        
        try {
            // Validar la solicitud
            // Log::debug('Validando datos de entrada', ['request_data' => $request->all()]);
            //ponemos que si production_line_id es vacio lo ponemo null
            $orders = $request->input('orders', []);

            // Bucle para limpiar los datos ANTES de validar
            foreach ($orders as $key => $orderData) {
                // Manejar production_line_id: convertir a null si está vacío, no es numérico o es 0
                if (isset($orderData['production_line_id'])) {
                    $value = $orderData['production_line_id'];
                    if ($value === '' || $value === '0' || trim($value) === '' || !is_numeric($value)) {
                        $orders[$key]['production_line_id'] = null;
                    } else {
                        // Asegurar que sea un entero válido
                        $orders[$key]['production_line_id'] = (int)$value;
                    }
                }
                
                // Asegurar que otros campos numéricos sean enteros
                if (isset($orderData['id'])) {
                    $orders[$key]['id'] = (int)$orderData['id'];
                }
                if (isset($orderData['orden'])) {
                    $orders[$key]['orden'] = (int)$orderData['orden'];
                }
                if (isset($orderData['status'])) {
                    $orders[$key]['status'] = (int)$orderData['status'];
                }
            }
        
            // Reemplazar los datos de la petición con los datos ya limpios
            $request->merge(['orders' => $orders]);

            $validated = $request->validate([
                'orders' => 'required|array|min:1',
                'orders.*.id' => 'required|integer|exists:production_orders,id',
                'orders.*.production_line_id' => [
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) {
                        // Solo validar contra la base de datos si no es null
                        if ($value !== null && !\App\Models\ProductionLine::where('id', $value)->exists()) {
                            $fail('La línea de producción seleccionada no existe.');
                        }
                    },
                ],
                'orders.*.orden' => 'required|integer|min:0',
                'orders.*.status' => 'required|integer|min:0|max:5',
            ]);
            
            $ordersCount = count($validated['orders']);
            //Log::info("Validación exitosa", ['total_orders' => $ordersCount]);
            
            $updatedCount = 0;
            $errors = [];
            
            // Iniciar una transacción para asegurar que todas las actualizaciones se realicen o ninguna
            DB::beginTransaction();
            //Log::debug('Transacción de base de datos iniciada');
            
            foreach ($request->orders as $index => $orderData) {
                $orderId = $orderData['id'];
                $logPrefix = "[Orden {$index}/{$ordersCount} ID:{$orderId}]";
                
                //Log::debug("$logPrefix Procesando orden");
                
                try {
                    $order = ProductionOrder::lockForUpdate()->find($orderId);
                    
                    if (!$order) {
                        $errorMsg = "$logPrefix No encontrada en la base de datos";
                        Log::error($errorMsg);
                        $errors[] = $errorMsg;
                        continue;
                    }
                    // <--- CAMBIO CLAVE: Guardar el estado original ANTES de actualizar
                    $originalStatus = $order->status;
                    $originalproduction_line_id = $order->production_line_id;
                    $newStatus = (int)$orderData['status'];
                    
                    // Registrar el estado actual antes de la actualización
                    Log::debug("$logPrefix Estado actual", [
                        'production_line_id' => $order->production_line_id,
                        'orden' => $order->orden,
                        'status' => $order->status,
                        'updated_at' => $order->updated_at
                    ]);
                    
                    // Preparar datos de actualización
                    $updateData = [
                        'production_line_id' => $orderData['production_line_id'] ?? null,
                        'orden' => $orderData['orden'],
                        'status' => $orderData['status'],
                    ];
                    
                    Log::debug("$logPrefix Aplicando cambios", $updateData);
                    
                    // Actualizar el modelo
                    $order->update($updateData);
                    $order->refresh();
                    $updatedCount++;
                    
                    // <--- CAMBIO CLAVE: Comprobar si el estado ha cambiado para activar la lógica MQTT
                    $statusHasChanged = $originalStatus !== $newStatus;

                    if ($statusHasChanged) {
                        Log::info("$logPrefix El estado ha cambiado de {$originalStatus} a {$newStatus}. Se evaluará el envío de MQTT.");
                        
                        $action = match ($newStatus) {
                            1 => 0,  // EN CURSO -> acción 0 (siempre)
                            2 => $originalStatus == 1 ? 1 : null,   // FINALIZADA -> acción 1 (solo si viene de EN CURSO)
                            3 => $originalStatus == 1 ? 0 : null,  // Si viene de EN CURSO a INCIDENCIA, enviar acción 0
                            4 => $originalStatus == 1 ? 0 : null,  // Si viene de EN CURSO a INCIDENCIA, enviar acción 0
                            default => null,
                        };

                        // Si hay una acción MQTT definida (estados 1, 2, 3 desde estado 1, etc.), procedemos
                        if ($action !== null) {
                            // Debug para verificar que se está procesando correctamente
                            Log::debug("$logPrefix Procesando acción MQTT: {$action} para cambio de estado {$originalStatus} -> {$newStatus}");

                                Log::info("$logPrefix Procesando envío MQTT para orden {$order->id}.");
                                try {
                                    // Usamos el production_line_id con el que se acaba de actualizar la orden
                                    // Si no está en $orderData, usamos el de la orden (importante para incidencias)
                                    $productionLineIdForMqtt = $orderData['production_line_id'] ?? $originalproduction_line_id;

                                    if ($productionLineIdForMqtt) {
                                        $barcoder = Barcode::where('production_line_id', $productionLineIdForMqtt)->first();
                                        
                                        if ($barcoder && !empty($barcoder->mqtt_topic_barcodes)) {
                                            $topic = $barcoder->mqtt_topic_barcodes . '/prod_order_mac';
                                            $messagePayload = json_encode([
                                                "action"    => $action, 
                                                "orderId"   => $order->order_id, // Usar el campo correcto (e.g., order_id o id)
                                                "quantity"  => 0,
                                                "machineId" => $barcoder->machine_id ?? "", 
                                                "opeId"     => $barcoder->ope_id ?? "",
                                            ]);


                                            // Si la orden se ha finalizado (status 2), activar la siguiente
                                            if ($newStatus === 2 || $newStatus === 3 || $newStatus === 4 || $newStatus === 5) {
                                                $lockKey = 'mqtt_lock_for_order_' . $order->order_id . '_line_' . $order->production_line_id;
                                                if (Cache::add($lockKey, true, 5)) { // Bloqueo de 5 segundos para evitar duplicados
                                                    $this->activateNextOrder($productionLineIdForMqtt, $barcoder);
                                                    Log::info("$logPrefix Llamada a activateNextOrder ejecutada.");
                                                } else {
                                                    Log::info("$logPrefix Envío MQTT omitido para orden {$order->id} porque ya hay un proceso en curso (bloqueo de caché activo).");
                                                }
                                            }else{
                                                $lockKey = 'mqtt_lock_for_order_' . $order->order_id . '_line_' . $order->production_line_id;
                                                if (Cache::add($lockKey, true, 5)) { // Bloqueo de 5 segundos para evitar duplicados
                                                    $this->publishMqttMessage($topic, $messagePayload);
                                                    Log::info("$logPrefix Mensaje MQTT enviado a tópico [{$topic}]");
                                                } else {
                                                    Log::info("$logPrefix Envío MQTT omitido para orden {$order->id} porque ya hay un proceso en curso (bloqueo de caché activo).");
                                                }
                                            }
                                        } else {
                                            Log::warning("$logPrefix No se encontró Barcoder o topic MQTT para la línea de producción ID: {$productionLineIdForMqtt}. No se envió el mensaje.");
                                        }
                                    } else {
                                         Log::warning("$logPrefix La orden no tiene una línea de producción asignada. No se envió el mensaje MQTT.");
                                    }
                                } catch (\Exception $e) {
                                    // El error de MQTT se registra, pero no detiene la transacción principal
                                    Log::error("$logPrefix Error durante el envío de MQTT: " . $e->getMessage(), [
                                        'exception' => $e->getTraceAsString()
                                    ]);
                                }
                        }
                    } // Fin de la lógica MQTT

                    Log::debug("$logPrefix Actualización exitosa", [
                        'nuevo_production_line_id' => $order->production_line_id,
                        'nuevo_orden' => $order->orden,
                        'nuevo_status' => $order->status
                    ]);
                    
                } catch (\Exception $e) {
                    $errorMsg = "$logPrefix Error: " . $e->getMessage();
                    Log::error($errorMsg, [
                        'exception' => $e->getTraceAsString(),
                        'order_data' => $orderData
                    ]);
                    $errors[] = $errorMsg;
                }
            }
            
            // Si llegamos aquí, todas las actualizaciones fueron exitosas
            DB::commit();
            
            $successMessage = "Actualización completada. Actualizadas: $updatedCount/$ordersCount órdenes.";
            
            if (!empty($errors)) {
                Log::warning("Proceso completado con errores parciales", [
                    'total_solicitadas' => $ordersCount,
                    'actualizadas' => $updatedCount,
                    'errores' => count($errors),
                    'errores_detalle' => $errors
                ]);
                
                $response = [
                    'success' => $updatedCount > 0,
                    'message' => $successMessage . " Errores: " . count($errors),
                    'updated' => $updatedCount,
                    'total' => $ordersCount,
                    'has_errors' => true,
                    'errors' => $errors
                ];
                
                return response()->json($response, $updatedCount > 0 ? 207 : 400);
            }
            
            // Éxito total
            Log::info("Actualización por lotes completada con éxito", [
                'total_procesadas' => $updatedCount
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'updated' => $updatedCount,
                'total' => $ordersCount
            ]);
            
        } catch (\Throwable $e) {
            // Hacer rollback en caso de cualquier excepción
            DB::rollBack();
            
            $errorContext = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ];
            
            Log::error("ERROR CRÍTICO en actualización por lotes", $errorContext);
            
            // Si estamos en entorno local o desarrollo, incluir más detalles
            $errorMessage = config('app.env') === 'local' 
                ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
                : 'Ocurrió un error inesperado. Por favor revise los logs del sistema.';
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error' => $errorContext
            ], 500);
        } finally {
            // Registrar métricas de rendimiento
            $executionTime = microtime(true) - LARAVEL_START;
            Log::debug("Tiempo de ejecución: " . round($executionTime, 3) . " segundos");
        }
    }
    private function activateNextOrder($prodductionLineId, $barcoder)
    {
        Log::info("Llamada a activateNextOrder ejecutada.");
        $baseQuery = ProductionOrder::where('production_line_id', $prodductionLineId)
            ->where('status', 0);

        if (Config::get('production.filter_not_ready_machine_kanban', true)) {
            $nowMadrid = Carbon::now('Europe/Madrid');
            $baseQuery->where(function ($query) use ($nowMadrid) {
                $query->whereNull('ready_after_datetime')
                    ->orWhere('ready_after_datetime', '<=', $nowMadrid);
            });
        }

        $nextOrderInLine = $baseQuery->orderBy('orden', 'asc')->first();

        if ($nextOrderInLine) {
            Log::info("Activando siguiente orden pendiente [{$nextOrderInLine->id}] en la línea [{$prodductionLineId}].");
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
            Log::info("No hay órdenes pendientes disponibles (según configuración) en la línea [{$prodductionLineId}].");
        }
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
        
            // Guardar en servidor 2
            $path2 = storage_path("app/mqtt/server2");
            if (!file_exists($path2)) {
                mkdir($path2, 0755, true);
            }
            $fileName2 = "{$path2}/{$sanitizedTopic}_{$uniqueId}.json";
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");

        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }

    /**
     * Cambia el estado de prioridad de una orden de producción
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePriority(Request $request)
    {
        try {
            // Validar la solicitud
            $request->validate([
                'order_id' => 'required|exists:production_orders,id',
            ]);
            
            $orderId = $request->input('order_id');
            $order = ProductionOrder::findOrFail($orderId);
            
            // Invertir el valor actual de is_priority
            $order->is_priority = !$order->is_priority;
            $order->save();
            
            return response()->json([
                'success' => true,
                'is_priority' => $order->is_priority,
                'message' => $order->is_priority ? 'Orden marcada como prioritaria' : 'Prioridad eliminada de la orden'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al cambiar prioridad de orden: ' . $e->getMessage(), [
                'order_id' => $request->input('order_id'),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza las anotaciones de una orden de producción
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNote(Request $request)
    {
        try {
            // Validar la solicitud
            $request->validate([
                'order_id' => 'required|exists:production_orders,id',
                'note' => 'nullable|string',
            ]);
            
            $orderId = $request->input('order_id');
            $note = $request->input('note');
            
            $order = ProductionOrder::findOrFail($orderId);
            $order->note = $note;
            $order->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Anotaciones actualizadas correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar anotaciones de orden: ' . $e->getMessage(), [
                'order_id' => $request->input('order_id'),
                'exception' => $e
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ], 500);
        }
    }
}
