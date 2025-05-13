<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon;
use App\Models\MonitorOee;
use Illuminate\Support\Facades\Log;
use App\Models\OrderStat;
use App\Models\Barcode;
use App\Models\SensorHistory;
use App\Models\ModbusHistory;
use App\Models\Operator;
use App\Models\RfidDetail;
use App\Models\ShiftHistory; // Importa el modelo ShiftControl
use App\Models\OperatorPost;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class MqttShiftSubscriber extends Command
{
    protected $signature = 'mqtt:shiftsubscribe';
    protected $description = 'Subscribe to MQTT topics and update shift control information from sensors';

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
    
        $this->shouldContinue = true;
        $retryDelay = 1; // Tiempo de espera inicial en segundos
    
        while ($this->shouldContinue) {
            try {
                $this->info("[". Carbon::now()->toDateTimeString() . "]Intentando conectar con MQTT...");
    
                // Inicializar el cliente MQTT
                $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
                // 🔹 Limpiar la lista de tópicos suscritos después de reconectar
                $this->subscribedTopics = [];
                // Suscribirse a los tópicos actuales
                $this->subscribeToAllTopics($mqtt);
    
                // Resetear el tiempo de espera después de una conexión exitosa
                $retryDelay = 1;
    
                // Bucle principal para procesar los mensajes MQTT
                while ($this->shouldContinue) {
                    $this->checkAndSubscribeNewTopics($mqtt);
                    $mqtt->loop(true);
    
                    // Manejo de señales para cierre seguro
                    pcntl_signal_dispatch();
    
                    // Reducir la carga del sistema
                    usleep(100000);
                }
    
                $mqtt->disconnect();
                $this->info("[". Carbon::now()->toDateTimeString() . "]MQTT Subscriber detenido correctamente.");
    
            } catch (\Exception $e) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error en la conexión MQTT: " . $e->getMessage());
    
                // Espera antes de reintentar (con aumento progresivo hasta un máximo de 30s)
                sleep($retryDelay);
                $retryDelay = min($retryDelay * 2, 30); // Incremento progresivo hasta 30s máximo
    
                $this->warn("Intentando reconectar en {$retryDelay} segundos...");
            }
        }
    }
    


    private function initializeMqttClient($server, $port)
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME'));
        $connectionSettings->setPassword(env('MQTT_PASSWORD'));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true); // Limpia la sesión

        return $mqtt;
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        // Obtener los tópicos desde la tabla BArcode
        $topics = Barcode::pluck('mqtt_topic_barcodes')->toArray();

        foreach ($topics as $topic) {
            $topicWithShift = "{$topic}/timeline_event"; // Añadir '/timeline_event' al tópico

            if (!in_array($topicWithShift, $this->subscribedTopics)) {
                $mqtt->subscribe($topicWithShift, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topicWithShift;
                $this->info("[". Carbon::now()->toDateTimeString() . "]Subscribed to topic: {$topicWithShift}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        // Obtener tópicos actuales desde Barcode
        $currentTopics = Barcode::pluck('mqtt_topic_barcodes')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            $topicWithShift = "{$topic}/timeline_event"; // Añadir '/timeline_event' al tópico

            if (!in_array($topicWithShift, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topicWithShift, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topicWithShift;
                $this->info("[". Carbon::now()->toDateTimeString() . "]Subscribed to new topic: {$topicWithShift}");
            }
        }
    }

    private function processMessage(string $topic, string $message)
    {
        $this->info("[". Carbon::now()->toDateTimeString() . "] Received message on topic '{$topic}'");

        // Decodificamos el payload JSON que viene del broker
        $payload = json_decode($message, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("[". Carbon::now()->toDateTimeString() . "] MQTT payload no válido: {$message}");
            return;
        }

        // Construimos la URL de nuestro controlador
        $baseUrl = rtrim(env('LOCAL_SERVER'), '/');
        $url     = "{$baseUrl}/api/shift-process-events";

        // Configuramos el cliente Guzzle
        $client = new Client([
            'timeout'     => 0.1,
            'http_errors' => false,
            'verify'      => false,
        ]);

        // Despachamos la petición POST de forma asíncrona
        $promise = $client->postAsync($url, [
            'json' => [
                'topic'   => $topic,
                'payload' => $payload,
            ],
        ]);

        $promise->then(
            function ($response) use ($url, $topic) {
                Log::info(sprintf(
                    "[%s][SHIFT-PROC] POST %s → %d (topic: %s)",
                    Carbon::now()->toDateTimeString(),
                    $url,
                    $response->getStatusCode(),
                    $topic
                ));
            },
            function ($e) use ($url, $topic) {
                Log::error(sprintf(
                    "[%s][SHIFT-PROC] Error POST %s (topic: %s): %s",
                    Carbon::now()->toDateTimeString(),
                    $url,
                    $topic,
                    $e->getMessage()
                ));
            }
        );

        // No bloqueamos el loop principal
        $promise->wait(false);
    }
}
