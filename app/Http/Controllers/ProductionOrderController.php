<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Barcode;

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
                if (isset($orderData['production_line_id']) && $orderData['production_line_id'] === '') {
                    $orders[$key]['production_line_id'] = null;
                }
            }
        
            // Reemplazar los datos de la petición con los datos ya limpios
            $request->merge(['orders' => $orders]);

            $validated = $request->validate([
                'orders' => 'required|array|min:1',
                'orders.*.id' => 'required|integer|exists:production_orders,id',
                'orders.*.production_line_id' => 'nullable|integer|exists:production_lines,id',
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
                            1 => 0, // Corresponde a "Iniciar"
                            2 => 1, // Corresponde a "Finalizar"
                            default => null,
                        };

                        // Si el nuevo estado es 1 o 2, procedemos con MQTT
                        if ($action !== null) {
                            $lockKey = 'mqtt_lock_for_order_' . $order->id;
                            if (Cache::add($lockKey, true, 5)) { // Bloqueo de 5 segundos para evitar duplicados
                                Log::info("$logPrefix Bloqueo de caché adquirido para [{$lockKey}]. Procesando envío MQTT.");
                                try {
                                    // Usamos el production_line_id con el que se acaba de actualizar la orden
                                    $productionLineIdForMqtt = $orderData['production_line_id'];

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

                                            $this->publishMqttMessage($topic, $messagePayload);
                                            Log::info("$logPrefix Mensaje MQTT enviado a tópico [{$topic}]");

                                            // Si la orden se ha finalizado (status 2), activar la siguiente
                                            if ($newStatus === 2) {
                                                $this->activateNextOrder($order, $barcoder);
                                                Log::info("$logPrefix Llamada a activateNextOrder ejecutada.");
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
                            } else {
                                Log::info("$logPrefix Envío MQTT omitido para orden {$order->id} porque ya hay un proceso en curso (bloqueo de caché activo).");
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
    private function activateNextOrder(ProductionOrder $finishedOrder, $barcoder)
    {
        // ¡CORREGIDO! Usamos el modelo ProductionOrder, no 'self'.
        $nextOrderInLine = ProductionOrder::where('production_line_id', $finishedOrder->production_line_id)
                    ->where('orden', '>', $finishedOrder->orden)
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

}
