<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modbus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
//anadir carbon
use Carbon\Carbon;

class ReadModbus extends Command
{
    protected $signature = 'modbus:read-ant';
    protected $description = 'Read data from Modbus API and publish to MQTT';

    protected $mqttService;

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
{
    pcntl_async_signals(true);

    // Manejar señales para una terminación controlada
    pcntl_signal(SIGTERM, function () {
        $this->shouldContinue = false;
    });

    pcntl_signal(SIGINT, function () {
        $this->shouldContinue = false;
    });

    $this->shouldContinue = true;

    $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
    $this->subscribeToAllTopics($mqtt);

    // Bucle principal para verificar y suscribirse a nuevos tópicos
    while ($this->shouldContinue) {
        // Verificar y suscribir a nuevos tópicos
        $this->checkAndSubscribeNewTopics($mqtt);

        // Mantener la conexión activa y procesar mensajes MQTT
        $mqtt->loop(true);

        // Permitir que el sistema maneje señales
        pcntl_signal_dispatch();

        // Reducir la carga del sistema esperando un corto período
        //usleep(100000); // Esperar 0.1 segundos
    }

    // Desconectar el cliente MQTT de forma segura
    $mqtt->disconnect();
    $this->info("[" . Carbon::now()->toDateTimeString() . "]MQTT Subscriber stopped gracefully.");
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
        $topics = Modbus::whereNotNull('mqtt_topic_modbus')
            ->where('mqtt_topic_modbus', '!=', '')
            ->pluck('mqtt_topic_modbus')
            ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    $this->processCallApi($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        $currentTopics = Modbus::pluck('mqtt_topic_modbus')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topic, function ($topic, $message) {
                  $this->processCallApi($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Subscribed to new topic: {$topic}");
            }
        }
    }



    public function processCallApi($topic, $data)
    {
        //$this->info("[" . Carbon::now()->toDateTimeString() . "]Llamada a la API externa para el Modbus ID: {$config->id} y valor: {$value}");
    
        // Construir la URL de la API
        $appUrl = rtrim(env('LOCAL_SERVER'), '/');
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
        $this->info("[" . Carbon::now()->toDateTimeString() . "]Datos enviados a la API: $apiUrl, con id: $id");
       // $this->info("[" . Carbon::now()->toDateTimeString() . "]Datos enviados a la API: {$apiUrl}, con los siguientes datos: " . json_encode($dataToSend, JSON_PRETTY_PRINT));
       // $this->info("[" . Carbon::now()->toDateTimeString() . "]Datos enviados a la API: " . json_encode($dataToSend, JSON_PRETTY_PRINT));
    
        try {
            // Enviar solicitud POST y manejar respuesta en la promesa
            $promise = $client->postAsync($apiUrl, [
                'json' => $dataToSend,
            ]);
    
            // Manejar el resultado de la promesa
            $promise->then(
                function ($response) use ($topic) {
                    $responseBody = $response->getBody()->getContents(); // Captura la respuesta
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]Respuesta de la API para el Modbus ID {$topic}: {$responseBody}");
                },
                function ($exception) use ($topic) {
                    $this->error("[" . Carbon::now()->toDateTimeString() . "]Error en la llamada a la API para el Modbus ID {$topic}: " . $exception->getMessage());
                }
            );
    
            // Resolver la promesa en el siguiente ciclo del event loop
            $promise->wait(false);
    
        } catch (\Exception $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al intentar llamar a la API: " . $e->getMessage());
        }
    }
}
