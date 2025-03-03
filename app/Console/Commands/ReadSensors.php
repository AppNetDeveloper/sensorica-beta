<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon;

class ReadSensors extends Command
{
    protected $signature = 'sensors:read';
    protected $description = 'Read data from Sensors API and publish to MQTT';

    protected $mqttService;
    protected $subscribedTopics = [];

    public function __construct()
    {
        parent::__construct();
       // MqttPersistentHelper::init();
    }
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
    
        while ($this->shouldContinue) {
            try {
                // 🔹 Intentar conectar MQTT
                $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
                // 🔹 Limpiar la lista de tópicos suscritos después de reconectar
                $this->subscribedTopics = [];
                // 🔹 Suscribirse a los tópicos después de cada reconexión
                $this->subscribeToAllTopics($mqtt);
    
                // 🔹 Bucle para procesar mensajes
                while ($this->shouldContinue) {
                    $mqtt->loop(true);
    
                    // 🔹 Verificar señales del sistema
                    pcntl_signal_dispatch();
    
                    usleep(100000); // Espera 0.1s para reducir carga en CPU
                }
    
                // 🔹 Desconectar MQTT si el proceso se detiene
                $mqtt->disconnect();
                $this->info("[" . Carbon::now()->toDateTimeString() . "]MQTT Subscriber stopped gracefully.");
    
            } catch (\Exception $e) {
                // 🔹 Cambiar `$this->logError()` por `$this->error()`
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error en MQTT: " . $e->getMessage());
    
                // 🔹 Esperar 2 segundos antes de intentar reconectar
                sleep(2);
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Reintentando conexión a MQTT...");
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
        $topics = Sensor::whereNotNull('mqtt_topic_sensor')
            ->where('mqtt_topic_sensor', '!=', '')
            ->pluck('mqtt_topic_sensor')
            ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    // Sacamos el sensor completo para tener acceso a todas sus propiedades
                    $sensor = Sensor::where('mqtt_topic_sensor', $topic)->first();

                    // Validamos si el sensor existe
                    if (!$sensor) {
                        $this->error("[" . Carbon::now()->toDateTimeString() . "][" . now() . "] Error: No se encontró un sensor asociado al tópico {$topic}");
                        return;
                    }

                    // Extraemos el valor directamente del mensaje JSON
                    $data = json_decode($message, true);
                    $value = $data['value'] ?? null;

                    // Si el sensor es del tipo 0 y el valor es 0, no procesamos el mensaje
                    if ($sensor->sensor_type == 0 && $value == 0) {
                        $this->info("[" . Carbon::now()->toDateTimeString() . "][" . now() . "] Mensaje descartado para sensor {$sensor->id} (sensor_type=0, value=0)");
                        return;
                    }else{
                        // Continuamos procesando el mensaje si cumple con que no es sensor type 0 y valor 0
                    $this->info("[" . Carbon::now()->toDateTimeString() . "][" . now() . "] Mensaje procesado para sensor {$sensor->id} con valor {$message}");
                    $this->processMessage($sensor->id, $message);
                    //$this->info("[" . Carbon::now()->toDateTimeString() . "][" . now() . "] Mensaje finalizado para sensor {$sensor->id} con valor {$message}");
                    }

                    

                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function processMessage($id, $message)
    {
        $data = json_decode($message, true);
    
        if (is_null($data)) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Error: El mensaje recibido no es un JSON válido.");
            return;
        }
    
       // $this->info("[" . Carbon::now()->toDateTimeString() . "]Contenido del Sensor ID {$id} JSON: " . print_r($data, true));
    
        // Intentar obtener el valor directamente del JSON
        $value = $data['value'] ?? null;
    
        if ($value === null) {
            // Si no encontramos 'value', intentamos buscar con json_api
            $config = Sensor::find($id);
    
            if (!$config) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error: No se encontró un sensor con ID $id.");
                return;
            }
    
            if (!empty($config->json_api)) {
                $jsonPath = $config->json_api;
                $value = $this->getValueFromJson($data, $jsonPath);
    
                if ($value === null) {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Advertencia: No se encontró la clave '$jsonPath' en el JSON.");
                }
            }
    
            // Si aún no encontramos el valor, registramos un error
            if ($value === null) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error: No se encontró 'value' en el JSON.");
                return;
            }
        }
    
        // Llamada asíncrona a la API
        $appUrl = rtrim(env('LOCAL_SERVER'), '/');
        $apiUrl = $appUrl . '/api/sensor-insert';
    
        // Configurar cliente Guzzle para operaciones asíncronas
        $client = new \GuzzleHttp\Client([
            'timeout' => 0.1,
            'http_errors' => false,
            'verify' => false
        ]);
    
        // Datos para enviar
        $dataToSend = [
            'value' => $value,
            'id' => $id, // Usamos el ID directamente en lugar de mqtt_topic_sensor
        ];
    
        try {
            // Crear la promesa
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend
            ]);
    
            // Manejar la promesa de forma no bloqueante
            $promise->then(
                function ($response) use ($id) {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]API call success for sensor {$id}: " . $response->getStatusCode());
                },
                function ($exception) use ($id) {
                   $this->error("[" . Carbon::now()->toDateTimeString() . "]API call error for sensor {$id}: " . $exception->getMessage());
                }
            );
    
            // Permitir que la promesa se resuelva en el siguiente ciclo del event loop
            $promise->wait(false);
    
        } catch (\Exception $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al intentar llamar a la API: " . $e->getMessage());
        }
    
       // $this->info("[" . Carbon::now()->toDateTimeString() . "][" . now() . "] Mensaje procesado para sensor {$id} con valor {$value}");

    }
    


    private function getValueFromJson($data, $jsonPath)
    {
        
        $keys = explode(', ', $jsonPath);
        foreach ($keys as $key) {
            $key = trim($key);
            if (isset($data[$key])) {
                $value = isset($data[$key]['value']) ? $data[$key]['value'] : null;

                return $value;
            }
        }
        return null;
    }
}