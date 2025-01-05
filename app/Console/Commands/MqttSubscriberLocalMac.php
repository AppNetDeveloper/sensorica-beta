<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\ProductionOrder;
use App\Models\OrderStat;
use App\Models\OrderMac;
use Carbon\Carbon;
use Normalizer;
use App\Models\ProductList; 
use Exception;

class MqttSubscriberLocalMac extends Command
{
    protected $signature = 'mqtt:subscribe-local-ordermac';
    protected $description = 'Subscribe to MQTT topics and update production orders';

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldContinue = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldContinue = false;
        });

        while ($this->shouldContinue) {
            try {
                $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
                $this->subscribeToAllTopics($mqtt);

                while ($this->shouldContinue) {
                    $mqtt->loop(true);
                    usleep(100000);
                }

                $mqtt->disconnect();
                $this->logInfo("MQTT Subscriber stopped gracefully.");
            } catch (Exception $e) {
                $this->logError("Error connecting or processing MQTT client: " . $e->getMessage());
                sleep(5);
                $this->logInfo("Reconnecting to MQTT...");
            }
        }
    }

    private function initializeMqttClient($server, $port)
    {
        $this->logInfo("Subscribed en server: {$server} y port: {$port}");
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME', ""));
        $connectionSettings->setPassword(env('MQTT_PASSWORD', ""));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true);

        return $mqtt;
    }

    private function subscribeToTopic(MqttClient $mqtt, string $topic)
    {
        if (!in_array($topic, $this->subscribedTopics)) {
            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->processMessage($topic, $message);
            }, 0);

            $this->subscribedTopics[] = $topic;
            $this->logInfo("Subscribed to topic: {$topic}");
        }
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        $topics = Barcode::pluck('mqtt_topic_barcodes')->map(function ($topic) {
            return $topic . "/prod_order_mac";
        })->toArray();

        foreach ($topics as $topic) {
            $this->subscribeToTopic($mqtt, $topic);
        }

        $this->logInfo('Subscribed to initial topics.');
    }

    private function processMessage($topic, $message)
    {
        $cleanMessage = json_decode($message, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError("El JSON proporcionado no es válido: " . json_last_error_msg());
            return;
        }
    
        $originalTopic = str_replace('/prod_order_mac', '', $topic);
        $barcodes = Barcode::where('mqtt_topic_barcodes', $originalTopic)->get();
    
        if ($barcodes->isEmpty()) {
            $this->logError("No barcodes found for topic: {$topic}");
            return;
        }
    
        foreach ($barcodes as $barcode) {
            $this->logInfo("---------------------------");
            $this->logInfo("Json recibido: " . json_encode($cleanMessage, JSON_PRETTY_PRINT));
            $this->logInfo("Procesando barcode ID: {$barcode->id}, topic: {$topic}, type: {$barcode->type}");
    
            if ($barcode->type === 0 || $barcode->type === 3) {
                $this->logInfo("Ignorando barcode ID {$barcode->id} con type {$barcode->type}.");
                continue;
            }
    
            $action = $cleanMessage['action'] ?? null;
    
            if ($action === 1) { // Finalizar orden
                $this->processOrderClose($cleanMessage, $barcode);
                $this->saveOrderMac($barcode, $cleanMessage);
                $this->logInfo("Orden cerrada para barcode ID: {$barcode->id}");
 
                
            } elseif ($action === 0) { // Abrir orden
                $this->logInfo("Iniciar Abrir orden para barcode ID: {$barcode->id}");
                $this->saveOrderMac($barcode, $cleanMessage);
                $this->logInfo("Sacar OrderId para barcode ID: {$barcode->id}");
                $this->logInfo("Contenido completo de cleanMessage: " . json_encode($cleanMessage, JSON_PRETTY_PRINT));

                if (!isset($cleanMessage['orderId'])) {
                    $this->logError("El campo 'orderId' no existe en cleanMessage.");
                    return; // Detener el proceso si falta 'orderId'
                }
                
                if (!is_numeric($cleanMessage['orderId'])) {
                    $this->logError("El campo 'orderId' no es numérico. Valor actual: " . json_encode($cleanMessage['orderId']));
                    return; // Detener el proceso si 'orderId' no es un número válido
                }
                
                $orderId = trim((string) $cleanMessage['orderId']); // Convertir a cadena para asegurar consistencia
                $this->logInfo("OrderId extraído correctamente: {$orderId}");
                
                if ($orderId) {
                    // Buscar el JSON completo en production_orders por order_id
                    $productionOrder = ProductionOrder::whereRaw('LOWER(order_id) = ?', [strtolower(trim($orderId))])->first();
                    $this->logInfo("ProductionOrder encontrado para orderId={$orderId}");
                    if ($productionOrder) {
                        $orderJson = $productionOrder->json;
    
                        if (isset($orderJson['quantity'], $orderJson['refer']['groupLevel'][0]['uds'])) {
                            $box = (int) $orderJson['quantity'];
                            $units = (int) $orderJson['refer']['groupLevel'][0]['uds'];
    
                            if ($box > 0 && $units > 0) {
                                $this->createOrderStat($barcode, $orderId, $box, $units, $productionOrder->id);
                            } else {
                                $this->logError("Valores inválidos en JSON de ProductionOrder: box={$box}, units={$units}, orderId={$orderId}");
                            }
                        } else {
                            $this->logError("Faltan claves en el JSON de ProductionOrder. orderId={$orderId}, JSON=" . json_encode($orderJson, JSON_PRETTY_PRINT));
                        }
                    } else {
                        $this->logError("ProductionOrder no encontrada para orderId={$orderId}");
                    }
                } else {
                    $this->logError("orderId no encontrado en el JSON recibido.");
                }
            } else {
                $this->logError("Acción desconocida recibida: {$action}");
            }
        }
    }
    

    private function processOrderClose($message, $barcode)
    {
        $orderId =isset($message['orderId']) ? trim($message['orderId']) : null;

        if (!$orderId) {
            $this->logError("orderId no encontrado en el JSON.");
            return;
        }

        $order = ProductionOrder::where('order_id', $orderId)->first();

        if (!$order) {
            $this->logError("OrderId {$orderId} no encontrado en ProductionOrders.");
            return;
        }

        $jsonData = json_decode($order->json, true);
        $this->logInfo("Orden encontrada. JSON almacenado: " . json_encode($jsonData, JSON_PRETTY_PRINT));
    }

    private function saveOrderMac($barcode, $message)
    {
        // Crear el registro en OrderMac
        OrderMac::create([
            'barcoder_id' => $barcode->id,
            'production_line_id' => $barcode->production_line_id,
            'json' => $message,
        ]);
    
        $this->logInfo("OrderMac creada para barcode ID: {$barcode->id}");

        // Extraer valores del JSON
        $orderId = isset($message['orderId']) ? trim($message['orderId']) : null;
        $this->logInfo("OrderId extraido: {$orderId}");
        // Buscar el JSON completo en production_orders por order_id
        $productionOrder = ProductionOrder::whereRaw('LOWER(order_id) = ?', [strtolower(trim($orderId))])->first();

        if (!$productionOrder) {
            $this->logError("ProductionOrder no encontrada para orderId={$orderId}");
            return; // Detener el proceso si no se encuentra
        }else{
            $this->logInfo("ProductionOrder encontrada para orderId={$orderId}");
        }

        try {
            // Detectar si el campo ya está decodificado
            if (is_array($productionOrder->json) || is_object($productionOrder->json)) {
                // Ya está decodificado
                $jsonNew = $productionOrder->json;
                $this->logInfo("JSON ya estaba decodificado. Procesando directamente.");
            } elseif (is_string($productionOrder->json)) {
                // Intentar decodificar si es una cadena JSON
                $jsonNew = json_decode($productionOrder->json, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
                }
                $this->logInfo("JSON decodificado correctamente.");
            } else {
                // Tipo inesperado
                throw new Exception("El campo 'json' tiene un tipo inesperado: " . gettype($productionOrder->json));
            }
        
            // Procesar el JSON decodificado (array o objeto)
            $this->logInfo("Contenido del JSON procesado: " . json_encode($jsonNew));
        
        } catch (Exception $e) {
            // Manejar cualquier error en el proceso
            $this->logError("Excepción capturada al procesar JSON: " . $e->getMessage());
            return; // Detener el proceso si hay un error
        }
        

        $this->logInfo("Json extraido"); // Este log solo se ejecutará si `json_decode` no falla silenciosamente

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError("Error al decodificar JSON de ProductionOrder: " . json_last_error_msg());
            return; // Detener el proceso si el JSON no es válido
        }
        $this->logInfo("JSON decodificado de ProductionOrder: " . json_encode($jsonNew, JSON_PRETTY_PRINT));
        // Extraer y validar `quantity`
        $quantity = isset($jsonNew['quantity']) && is_numeric($jsonNew['quantity']) 
            ? (int) $jsonNew['quantity'] 
            : null;

        // Extraer y validar `uds` de `groupLevel`
        $uds = isset($jsonNew['refer']['groupLevel'][0]['uds']) && is_numeric($jsonNew['refer']['groupLevel'][0]['uds']) 
            ? (int) $jsonNew['refer']['groupLevel'][0]['uds'] 
            : null;

        // Extraer y validar `total` de `groupLevel`
        $boxKg = isset($jsonNew['refer']['groupLevel'][0]['total']) && is_numeric($jsonNew['refer']['groupLevel'][0]['total'])
                ? round((float)$jsonNew['refer']['groupLevel'][0]['total'], 2)
                : null;

        // Extraer y normalizar `envase`
        $envase = isset($jsonNew['refer']['descrip']) && $jsonNew['refer']['descrip'] !== '' 
            ? trim(Normalizer::normalize($jsonNew['refer']['descrip'], Normalizer::FORM_C)) 
            : null;

        // Extraer y normalizar `envase`
        $referId = isset($jsonNew['refer']['id']) && $jsonNew['refer']['id'] !== '' 
            ? trim(Normalizer::normalize($jsonNew['refer']['id'], Normalizer::FORM_C)) 
            : null;

        // Registrar los valores procesados
        $this->logInfo("Valores procesados desde ProductionOrder: quantity={$quantity}, uds={$uds}, envase={$envase}");

        if ($orderId && $quantity && $uds && $envase) {
            // Actualizar barcodes con el nuevo campo `envase`
            $barcode->update(['ope_id' => $envase]);
            $this->logInfo("Envase actualizado en ope_id para barcode ID: {$barcode->id}");
    
            // Procesar product_lists y obtener optimal_production_time
            $optimalProductionTime = $this->processProductList($referId, $envase, $boxKg);

            if ($optimalProductionTime === null) {
                $this->logError("optimalProductionTime es null después de llamar a processProductList");
            } else {
                $this->logInfo("optimalProductionTime obtenido: {$optimalProductionTime}");
            }
            
            // Reseteo de sensores y modbuses con los nuevos valores
            $this->resetSensors($barcode->id, $optimalProductionTime, $orderId, $quantity, $uds, $referId);
            $this->resetModbuses($barcode->id, $optimalProductionTime, $orderId, $quantity, $uds, $referId);
        } else {
            $this->logError("Faltan campos en el JSON recibido para procesar sensores y modbuses. Valores recibidos: orderId={$orderId}, quantity={$quantity}, uds={$uds}, envase={$envase}");
        }
    }
    private function processProductList($referId, $envase, $boxKg)
    {
        if (!$referId || !$envase) {
            $this->logError("Faltan datos para procesar product_lists: referId={$referId}, envase={$envase}");
            return null;
        }

        try {
            // Buscar el registro en product_lists
            $productList = ProductList::where('client_id', $referId)->first();

            if (!$productList) {
                // Crear un nuevo registro si no existe
                $productList = ProductList::create([
                    'client_id' => $referId,
                    'name' => $envase,
                    'optimal_production_time' => 2, // Valor predeterminado
                    'box_kg' => $boxKg,
                ]);

                $this->logInfo("Nuevo registro creado en product_lists: client_id={$referId}, name={$envase}");
            } else {
                $this->logInfo("Registro existente encontrado en product_lists: client_id={$referId}, name={$productList->name}");
            }
            $this->logInfo("Retornando optimal_production_time={$productList->optimal_production_time}");
            return $productList->optimal_production_time;
            
        } catch (Exception $e) {
            $this->logError("Error al procesar product_lists: " . $e->getMessage());
            return null;
        }
    }
    

    private function createOrderStat($barcode, $orderId, $box, $units, $productionOrderId)
    {
        $productionLineId = $barcode->production_line_id;

        try {
            $orderStat = OrderStat::create([
                'production_line_id' => $productionLineId,
                'order_id' => $orderId,
                'box' => $box,
                'units_box' => $units,
                'units' => $box * $units,
                'units_per_minute_real' => null,
                'units_per_minute_theoretical' => null,
                'seconds_per_unit_real' => null,
                'seconds_per_unit_theoretical' => null,
                'units_made_real' => 0,
                'units_made_theoretical' => 0,
                'sensor_stops_count' => 0,
                'sensor_stops_time' => 0,
                'production_stops_time' => 0,
                'units_made' => 0,
                'units_pending' => 0,
                'units_delayed' => 0,
                'slow_time' => 0,
                'oee' => null,
            ]);

            $this->logInfo("OrderStat creada correctamente para orderId: {$orderId}");

            $this->updateProductionOrderStatus( 1, $barcode->id, $productionOrderId);
        } catch (Exception $e) {
            $this->logError("Error creando OrderStat: " . $e->getMessage());
        }
    }

    private function updateProductionOrderStatus($status, $barcodeId, $productionOrderId)
    {
        try {
            $order = ProductionOrder::find($productionOrderId);
            if ($order) {
                $order->update(['status' => $status]);
                $this->logInfo("ProductionOrder ID {$productionOrderId} actualizado a status {$status}");
            } else {
                $this->logError("ProductionOrder ID {$productionOrderId} no encontrado.");
            }
        } catch (Exception $e) {
            $this->logError("Error actualizando ProductionOrder status: " . $e->getMessage());
        }
    }

    private function logInfo($message)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $this->info("[{$timestamp}] {$message}");
    }

    private function logError($message)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $this->error("[{$timestamp}] {$message}");
    }

    private function resetSensors($barcodeId, $optimalProductionTime, $orderId, $quantity, $uds, $referId)
    {
        $this->info("Iniciar reset de sensores con optimal_production_time={$optimalProductionTime}, orderId={$orderId}, quantity={$quantity}, uds={$uds} para barcode ID {$barcodeId}");

        try {
            $updated = Sensor::where('barcoder_id', $barcodeId)->update([
                'count_order_0' => 0,
                'count_order_1' => 0,
                'downtime_count' => 0,
                'optimal_production_time' => $optimalProductionTime, // Asignar el valor
                'orderId' => $orderId,
                'quantity' => $quantity,
                'uds' => $uds,
                'productName' => $referId,
            ]);
    
            if ($updated > 0) {
                $this->info("Reset realizado para {$updated} sensores con optimal_production_time={$optimalProductionTime}, orderId={$orderId}, quantity={$quantity}, uds={$uds} para barcode ID {$barcodeId}");
            } else {
                $this->error("Sensor no encontrado para barcode ID: {$barcodeId}");
            }
        } catch (Exception $e) {
            $this->error("Error actualizando sensores: " . $e->getMessage());
        }
    }    

    private function resetModbuses($barcodeId, $optimalProductionTime, $orderId, $quantity, $uds, $referId)
    {
        $this->info("Iniciar reset de modbuses con optimal_production_time={$optimalProductionTime}, orderId={$orderId}, quantity={$quantity}, uds={$uds} para barcode ID {$barcodeId}");
        try {
            $updated = Modbus::where('barcoder_id', $barcodeId)->update([
                'rec_box' => 0,
                'total_kg_order' => 0,
                'downtime_count' => 0,
                'optimal_production_time' => $optimalProductionTime, // Asignar el valor
                'orderId' => $orderId,
                'quantity' => $quantity,
                'uds' => $uds,
                'productName' => $referId,
            ]);
    
            if ($updated > 0) {
                $this->info("Reset realizado para {$updated} modbuses con optimal_production_time={$optimalProductionTime}, orderId={$orderId}, quantity={$quantity}, uds={$uds} para barcode ID {$barcodeId}");
            } else {
                $this->error("Modbus no encontrado para barcode ID: {$barcodeId}");
            }
        } catch (Exception $e) {
            $this->error("Error actualizando Modbus: " . $e->getMessage());
        }
    }     
}
