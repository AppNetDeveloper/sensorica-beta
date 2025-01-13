<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon;

class MqttSubscriberLocal extends Command
{
    protected $signature = 'mqtt:subscribe-local';
    protected $description = 'Subscribe to MQTT topics and update order notices';


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
                sleep(5);
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
        $topics = Barcode::pluck('mqtt_topic_barcodes')->map(function ($topic) {
            return $topic . "/prod_order_notice";
        })->toArray();

        foreach ($topics as $topic) {
            $this->subscribeToTopic($mqtt, $topic);
        }

        $this->info("[{$timestamp}] Subscribed to initial topics.");
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

        // Limpiar y validar el JSON
        $cleanMessageJson = $this->cleanAndValidateJson($message);
        if ($cleanMessageJson === null) {
            return; // JSON inválido, salir
        }

        $originalTopic = str_replace('/prod_order_notice', '', $topic);
        $barcodes = Barcode::where('mqtt_topic_barcodes', $originalTopic)->get();

        if ($barcodes->isEmpty()) {
            $this->error("[{$timestamp}] No barcodes found for topic: {$topic}");
            return;
        } else {
            $this->info("[{$timestamp}] Barcodes found for topic: {$topic}");
        }

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
    
}
