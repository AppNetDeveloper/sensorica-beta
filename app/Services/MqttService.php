<?php

namespace App\Services;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

/**
 * Modo se implementacion
 * use App\Services\MqttService; //importar el servicio
 * dentro de la Class se introduce
 *
 * protected $mqttService; // Añade la propiedad para el servicio de MQTT
 *
 * depues se agrega esto:
 *
 * public function __construct(MqttService $mqttService)
 *   {
 *       parent::__construct();
 *       $this->mqttService = $mqttService;
 *  }
 * depues se llama asi en la function que tu quieres
 *
 * MqttHelper::publishMessage($topic, $message, env('MQTT_SERVER'), intval(env('MQTT_PORT')));
 *
 *
 *
 *
 *
 *
 * OJO : es un servisio singlestone
 *
 */


class MqttService
{
    protected $client;
    protected $server;
    protected $port;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->username = env('MQTT_USERNAME');
        $this->password = env('MQTT_PASSWORD');
    }

    protected function connect($server, $port)
    {
        if ($this->client && $this->server === $server && $this->port === $port) {
            return; // Ya conectado
        }

        $this->server = $server;
        $this->port = $port;
        $clientId = uniqid();
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);
        $connectionSettings->setUsername($this->username);
        $connectionSettings->setPassword($this->password);

        $this->client = new MqttClient($server, $port, $clientId);
        $this->client->connect($connectionSettings, true);

        Log::info('Connected to MQTT server.', [
            'server' => $server,
            'port' => $port,
            'clientId' => $clientId
        ]);
    }

    public function publishMessage($topic, $message, $server, $port)
    {
        $this->connect($server, $port);

        $maxRetries = 3;
        $retryDelay = 5; // seconds
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                $this->client->publish($topic, json_encode($message), 0);
                Log::info('Published MQTT message.', [
                    'topic' => $topic,
                    'message' => $message
                ]);
                return; // Exit function if successful
            } catch (\Exception $e) {
                Log::error('Failed to publish MQTT message. Attempt ' . ($attempt + 1) . ' of ' . $maxRetries, [
                    'error' => $e->getMessage(),
                    'topic' => $topic,
                    'message' => $message
                ]);

                $attempt++;
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);

                    // Intentar reconectar si la conexión se ha cerrado
                    if (!$this->client->isConnected()) {
                        $this->connect($server, $port);
                    }
                } else {
                    Log::critical('Exceeded maximum number of retries. Message could not be published.', [
                        'topic' => $topic,
                        'message' => $message
                    ]);
                }
            }
        }
    }

    public function disconnect()
    {
        if ($this->client) {
            $this->client->disconnect();
            $this->client = null;
            Log::info('Disconnected from MQTT server.');
        }
    }
}
