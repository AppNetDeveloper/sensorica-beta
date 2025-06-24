<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon;
use App\Models\ProductionOrder;

class MqttSubscriberLocal extends Command
{
    protected $signature = 'mqtt:subscribe-local';
    protected $description = 'Subscribe to MQTT topics and update order notices';

    // Default topic to subscribe to
    protected const DEFAULT_TOPIC = 'barcoder/prod_order_notice';
    
    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
    {
        // Habilitar señales para poder detener el proceso limpiamente
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldContinue = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldContinue = false;
        });
        
    
        while ($this->shouldContinue) {
            try {
                $timestamp = Carbon::now()->format('Y-m-d H:i:s');
                $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
                // 🔹 Limpiar la lista de tópicos suscritos después de reconectar
                $this->subscribedTopics = [];
                $this->subscribeToAllTopics($mqtt);
    
                while ($this->shouldContinue) {
                    $mqtt->loop(true);
                    usleep(100000);
                }
    
                $mqtt->disconnect();
                $this->info("[{$timestamp}]MQTT Subscriber stopped gracefully.");
    
            } catch (\Exception $e) {
                $timestamp = Carbon::now()->format('Y-m-d H:i:s');
                $this->error("[{$timestamp}]Error connecting or processing MQTT client: " . $e->getMessage());
                // Esperar un poco antes de intentar reconectar
                sleep(0.5);
                $this->info("[{$timestamp}]Reconnecting to MQTT...");
            }
        }
    }
    


    private function initializeMqttClient($server, $port)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $this->info("[{$timestamp}] Subscribed en server: {$server} y port: {$port}");
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME',""));
        $connectionSettings->setPassword(env('MQTT_PASSWORD', ""));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true);

        return $mqtt;
    }

    private function subscribeToTopic(MqttClient $mqtt, string $topic)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        if (!in_array($topic, $this->subscribedTopics)) {
            $mqtt->subscribe($topic, function ($topic, $message) {
                $this->processMessage($topic, $message);
            }, 0);

            $this->subscribedTopics[] = $topic;
            $this->info("[{$timestamp}]Subscribed to topic: {$topic}");
        }
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        
        // Subscribe to all barcode topics
        $topics = Barcode::pluck('mqtt_topic_barcodes')
            ->map(function ($topic) {
                return $topic . "/prod_order_notice";
            })
            ->toArray();

        // Add default topic
        $topics[] = self::DEFAULT_TOPIC;
        $topics = array_unique($topics);

        foreach ($topics as $topic) {
            $this->subscribeToTopic($mqtt, $topic);
        }

        $this->info("[{$timestamp}] Subscribed to initial topics including default topic: " . self::DEFAULT_TOPIC);
    }

    private function cleanAndValidateJson($rawJson)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');

        // Eliminar comillas iniciales y finales, si las hay
        $trimmedJson = trim($rawJson, '"');

        // Reemplazar barras invertidas
        $cleanedJson = str_replace('\\', '', $trimmedJson);

        // Validar que el JSON es válido
        $decodedJson = json_decode($cleanedJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("[{$timestamp}] El JSON proporcionado no es válido: " . json_last_error_msg());
            return null;
        }

        // Reconvertir a JSON limpio para su almacenamiento
        return json_encode($decodedJson, JSON_UNESCAPED_SLASHES);
    }

    private function processMessage($topic, $message)
    {
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $isDefaultTopic = ($topic === self::DEFAULT_TOPIC);

        // Limpiar y validar el JSON
        $cleanMessageJson = $this->cleanAndValidateJson($message);
        if ($cleanMessageJson === null) {
            return; // JSON inválido, salir
        }

        // Decode the message to check if it's a production order
        $messageData = json_decode($cleanMessageJson, true);
        
        if ($isDefaultTopic && isset($messageData['orderId'])) {
            // Handle default topic message (production order)
            $this->handleProductionOrder($messageData, $timestamp);
            return;
        }

        // Handle barcode topic message
        $originalTopic = str_replace('/prod_order_notice', '', $topic);
        $barcodes = Barcode::where('mqtt_topic_barcodes', $originalTopic)->get();

        if ($barcodes->isEmpty()) {
            $this->error("[{$timestamp}] No barcodes found for topic: {$topic}");
            return;
        }
        
        $this->info("[{$timestamp}] Barcodes found for topic: {$topic}");

        foreach ($barcodes as $barcode) {
            $this->info("[{$timestamp}] Verificando barcode ID: {$barcode->id}, sended: {$barcode->sended}");

            // Guardar el aviso de pedido
            $barcode->order_notice = $cleanMessageJson;
            $barcode->sended = 0; // Después de guardar, poner `sended` a 0
            try {
                $barcode->save();
                $this->info("[{$timestamp}] Código de barras guardado correctamente: {$barcode->id}");
            } catch (\Exception $e) {
                $this->error("[{$timestamp}] Error al guardar el código de barras: {$e->getMessage()} json: {$cleanMessageJson}");
            }
        }
    }
    
    /**
     * Get the default barcode ID to use for production orders
     * 
     * @return int|null
     */
    private function getDefaultBarcodeId()
    {
        try {
            // Try to find a default barcode
            $defaultBarcode = \App\Models\Barcode::first();
            return $defaultBarcode ? $defaultBarcode->id : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Handle production order messages from the default topic
     * 
     * @param array $messageData
     * @param string $timestamp
     * @return void
     */
    private function handleProductionOrder(array $messageData, string $timestamp)
    {
        try {
            // Log the received message for debugging
            $this->info("[" . $timestamp . "] Processing production order: " . json_encode($messageData));

            // Get default barcode ID
            $barcoderId = $this->getDefaultBarcodeId(); // Asegúrate de que este método exista y funcione

            if (!$barcoderId) {
                throw new \Exception("No se pudo encontrar un código de barras por defecto");
            }
            // 1. Busca si ya existe una orden con el mismo order_id.
            $existingOrder = ProductionOrder::where('order_id', $messageData['orderId'])->first();

            // 2. Si existe, modifica la orden anterior para "archivarla".
            if ($existingOrder) {
                // Genera un nuevo ID para la orden antigua añadiendo la fecha y hora.
                // Formato: 'orderIdOriginal-AñoMesDía-HoraMinutoSegundo'
                $newIdForOldOrder = $existingOrder->order_id . '-' . $existingOrder->process_category;
                $this->info("[" . $timestamp . "] Archived existing order: " . $existingOrder->order_id);
                // Actualiza el order_id de la orden existente.
                $existingOrder->order_id = $newIdForOldOrder;

                // Opcional: Puedes marcarla de alguna otra forma si tienes un campo de estado.
                // $existingOrder->status = 'archivado';

                $existingOrder->save();
            }

            // Prepare data for update or create
            $orderData = [
                'order_id'                  => $messageData['orderId'],
                'barcoder_id'               => $barcoderId,
                'production_line_id'        => null, // Set production_line_id to null for default topic
                // *** CAMBIO CLAVE AQUÍ: Asigna el array directamente, no lo decodifiques de nuevo ***
                'json'                      => $messageData,
                'status'                    => '0',
                // Asegúrate de que 'orden' sea un campo que realmente quieres auto-incrementar así,
                // o que sea un campo con un valor predeterminado si es un valor de ordenamiento.
                // Podría haber condiciones de carrera si muchos procesos intentan crear al mismo tiempo.
                'orden'                     => ProductionOrder::max('orden') + 1,
                'theoretical_time'          => isset($messageData['theoretical_time']) ? floatval($messageData['theoretical_time']) : null,
                'process_category'          => $messageData['process_category'] ?? null,
                'delivery_date'             => isset($messageData['delivery_date']) ? Carbon::parse($messageData['delivery_date']) : null,
                'customerId'                => $messageData['refer']['customerId'] ?? 'Sin Cliente',
                'original_order_id'         => $messageData['original_order_id'] ?? null,
                'grupo_numero'              => $messageData['grupo_numero'] ?? null,
                'processes_to_do'           => $messageData['processes_to_do'] ?? null,
                'processes_done'            => $messageData['processes_done'] ?? null,
                'original_order_process_id' => $messageData['original_order_process_id'] ?? null,
            ];

            $productionOrder = ProductionOrder::create($orderData);

            // Log de depuración para ver la orden creada
            $this->info("[" . $timestamp . "] Saved new production order with ID: " . $productionOrder->id . ", OrderID: " . $productionOrder->order_id . ", Status: " . $productionOrder->status);
            $this->info("[{$timestamp}] Production order processed: {$messageData['orderId']}");
    
        } catch (\Throwable $e) { // <-- CAMBIO CLAVE AQUÍ
            $this->error("[{$timestamp}] FATAL ERROR processing production order: " . $e->getMessage());
            $this->error($e->getTraceAsString()); // Esto es crucial para ver dónde falla
        }
    }
}
