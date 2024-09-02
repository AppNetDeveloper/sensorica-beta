<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Cache;
use App\Helpers\MqttPersistentHelper;

class ReadSensors extends Command
{
    protected $signature = 'sensors:read';
    protected $description = 'Read data from Sensors API and publish to MQTT';

    protected $mqttService;
    protected $subscribedTopics = [];

    public function __construct()
    {
        parent::__construct();
        MqttPersistentHelper::init();
    }

    public function handle()
    {
        $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
        $this->subscribeToAllTopics($mqtt);

        // Bucle principal para verificar y suscribirse a nuevos tópicos
        while (true) {
            $this->checkAndSubscribeNewTopics($mqtt);
            $mqtt->loop(true); // Mantener la conexión activa y procesar mensajes

            // Permitir que Laravel maneje eventos internos mientras esperamos nuevos mensajes
            usleep(100000); // Esperar 0.1 segundos
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
        $topics = Sensor::whereNotNull('mqtt_topic_sensor')
            ->where('mqtt_topic_sensor', '!=', '')
            ->pluck('mqtt_topic_sensor')
            ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    // Sacamos el id para identificar la línea, pero solo id para no cargar la RAM
                    $id = Sensor::where('mqtt_topic_sensor', $topic)->value('id');
                    // Llamamos a procesar el mensaje
                    $this->processMessage($id, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        $currentTopics = Sensor::pluck('mqtt_topic_sensor')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topic, function ($topic, $message) {
                    // Sacamos id para identificar la línea pero solo id para no cargar la RAM
                    $id = Sensor::where('mqtt_topic_sensor', $topic)->value('id');
                    // Llamamos a procesar el mensaje
                    $this->processMessage($id, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to new topic: {$topic}");
            }
        }
    }

    private function processMessage($id, $message)
    {
        $config = Sensor::where('id', $id)->first();

        $data = json_decode($message, true);
        if (is_null($data)) {
            Log::error("Error: El mensaje recibido no es un JSON válido.");
            return;
        }

        // Verificar si la configuración no existe
        if (is_null($config)) {
            Log::error("Error: No se encontró la configuración para Sensor ID {$id}. El sensor puede haber sido eliminado.");
            return;
        }

        Log::info("Contenido del Sensor ID {$id} JSON: " . print_r($data, true));

        $value = null;
        if (empty($config->json_api)) {
            $value = $data['value'] ?? null;
            if ($value === null) {
                Log::error("Error: No se encontró 'value' en el JSON cuando json_api está vacío.");
                return;
            }
        } else {
            $jsonPath = $config->json_api;
            $value = $this->getValueFromJson($data, $jsonPath);
            if ($value === null) {
                Log::warning("Advertencia: No se encontró la clave '$jsonPath' en la respuesta JSON, buscando el valor directamente.");
                $value = $data['value'] ?? null;
                if ($value === null) {
                    Log::error("Error: No se encontró 'value' en el JSON.");
                    return;
                }
            }
        }

        Log::info("Mensaje: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}");
        // Procesar modelo de sensor
        $this->processModel($config, $value);
    }

    private function getValueFromJson($data, $jsonPath)
    {
        $keys = explode(', ', $jsonPath);
        foreach ($keys as $key) {
            $key = trim($key);
            if (isset($data[$key])) {
                return isset($data[$key]['value']) ? $data[$key]['value'] : null;
            }
        }
        return null;
    }

    private function processModel($config, $value)
    {
        // Implementar la lógica específica del procesamiento basado en el modelo de sensor
        switch ($config->model_name) {
            case 'weight':
                $this->processWeightModel($config, $value);
                break;
            case 'height':
                $this->processHeightModel($config, $value);
                break;
            case 'lifeTraficMonitor':
                $this->lifeTraficMonitor($config, $value);
                break;
            default:
                Log::warning("Modelo desconocido: {$config->model_name}");
                break;
        }
    }
}