<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modbus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

class ReadModbusGroup extends Command
{
    protected $signature = 'modbus:read {group}'; // Acepta un grupo como argumento
    protected $description = 'Read data from Modbus API and publish to MQTT for a specific group';
    

    protected $mqttService;

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
{
    pcntl_async_signals(true);

    $group = $this->argument('group'); // Obtener el grupo del argumento

    // Manejar señales para una terminación controlada
    pcntl_signal(SIGTERM, function () {
        $this->shouldContinue = false;
    });

    pcntl_signal(SIGINT, function () {
        $this->shouldContinue = false;
    });

    $this->shouldContinue = true;

    $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
    $this->subscribeToAllTopics($mqtt, $group);

    // Bucle principal para verificar y suscribirse a nuevos tópicos
    while ($this->shouldContinue) {
        // Verificar y suscribir a nuevos tópicos
        $this->checkAndSubscribeNewTopics($mqtt, $group);

        // Mantener la conexión activa y procesar mensajes MQTT
        $mqtt->loop(true);

        // Permitir que el sistema maneje señales
        pcntl_signal_dispatch();

        // Reducir la carga del sistema esperando un corto período
        usleep(300000); // Esperar 0.1 segundos
    }

    // Desconectar el cliente MQTT de forma segura
    $mqtt->disconnect();
    $this->info("MQTT Subscriber stopped gracefully.");
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

    private function subscribeToAllTopics(MqttClient $mqtt, $group)
    {
        $topics = Modbus::where('group', $group)
        ->whereNotNull('mqtt_topic_modbus')
        ->where('mqtt_topic_modbus', '!=', '')
        ->pluck('mqtt_topic_modbus')
        ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    $this->processCallApi($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt, $group)
    {
        $currentTopics = Modbus::where('group', $this->argument('group'))
        ->whereNotNull('mqtt_topic_modbus')
        ->where('mqtt_topic_modbus', '!=', '')
        ->pluck('mqtt_topic_modbus')
        ->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topic, function ($topic, $message) {
                  $this->processCallApi($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to new topic: {$topic}");
            }
        }
    }



    public function processCallApi($topic, $data)
    {
        //$this->info("Llamada a la API externa para el Modbus ID: {$config->id} y valor: {$value}");
    
        // Construir la URL de la API
        $appUrl = rtrim(env('APP_URL'), '/');
        $apiUrl = $appUrl . '/api/modbus-process-data-mqtt';
    
        // Configurar el cliente HTTP (Guzzle)
        $client = new \GuzzleHttp\Client([
            'timeout' => 0.1,
            'http_errors' => false,
            'verify' => false,
        ]);
        $config = Modbus::where('mqtt_topic_modbus', $topic)->first();
        $id = Modbus::where('mqtt_topic_modbus', $topic)->value('id');
        // Crear el JSON con los datos
        $dataToSend = [
            'id' => $id,
            'data' => json_decode($data, true), // Decodificar el JSON si es un string
        ];

      //  $this->info("Datos enviados a la API: $apiUrl, con id: $id");
       // $this->info("Datos enviados a la API: {$apiUrl}, con los siguientes datos: " . json_encode($dataToSend, JSON_PRETTY_PRINT));
       // $this->info("Datos enviados a la API: " . json_encode($dataToSend, JSON_PRETTY_PRINT));
    
       //comprobar que no se repiten los valores
       $decodedData = json_decode($data, true);
       $value = null;

       if (empty($config->json_api)) {
            // Si no hay configuración de json_api, buscar directamente la clave "value"
            $value = $decodedData['value'] ?? null;
        } else {
            // Si hay configuración de json_api, buscar el valor con la ruta especificada
            $jsonPath = $config->json_api;
            $value = $this->getValueFromJson($decodedData, $jsonPath);
        
            // Si no se encuentra el valor en la ruta especificada, intentar con "value"
            if ($value === null) {
                $value = $decodedData['value'] ?? null;
            }
        }
        $value /= $config->conversion_factor;

        // Verificamos que el model_name sea "weight"
        if ($config->model_name === 'weight') {
            // Define una tolerancia para valores cercanos a 0
            $tolerance = 0.0001;

            // Redondea el valor actual y el último valor para evitar problemas de precisión
            $roundedValue = round($value, 4);
            $roundedLastValue = round($config->last_value, 4);

            if (
                (abs($roundedValue) < $tolerance && abs($roundedLastValue) < $tolerance) || // Ambos valores son cercanos a 0
                ($roundedValue <= $config->variacion_number && abs($roundedLastValue) < $tolerance) || // Dentro de variación y last_value es cercano a 0
                ($config->last_rep >= $config->rep_number && $roundedValue === $roundedLastValue) // Se alcanzó el máximo de repeticiones
            ) {
                if ($roundedLastValue === $roundedValue) {
                    $this->info("Datos ignorados: id={$id}, valor={$roundedValue}.");
                    return;
                }
            }
        }
        
        // Si no se cumplen las condiciones, procedemos
        $start = "1";
        

        if ($start == "1") {
            try {
                // Enviar solicitud POST y manejar respuesta en la promesa
                $promise = $client->postAsync($apiUrl, [
                    'json' => $dataToSend,
                ]);
        
                // Manejar el resultado de la promesa
                $promise->then(
                    function ($response) use ($topic) {
                        $responseBody = $response->getBody()->getContents(); // Captura la respuesta
                        $this->info("Respuesta de la API para el Modbus ID {$topic}: {$responseBody}");
                    },
                    function ($exception) use ($topic) {
                        $this->error("Error en la llamada a la API para el Modbus ID {$topic}: " . $exception->getMessage());
                    }
                );
        
                // Resolver la promesa en el siguiente ciclo del event loop
                $promise->wait(false);
        
            } catch (\Exception $e) {
                $this->error("Error al intentar llamar a la API: " . $e->getMessage());
            }
        }
        
        
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
}
