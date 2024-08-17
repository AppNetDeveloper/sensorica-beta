<?php

namespace App\Helpers;

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

//Como usarlo
/**
 *
 * se agrega:
 * use App\Helpers\MqttPersistentHelper;
 * dentro de Class

 *    public function __construct()
 *    {
 *       parent::__construct();
 *       MqttPersistentHelper::init();
 *     }
 *
 *se llama con esto:
  *      $server = env('MQTT_SERVER');
  *      $port = intval(env('MQTT_PORT'));
  *      MqttPersistentHelper::publishMessage($topic, $message, $server, $port);
*/


class MqttPersistentHelper
{
    private static $client = null;
    private static $server;
    private static $port;
    private static $username;
    private static $password;

    public static function init()
    {
        self::$username = env('MQTT_USERNAME');
        self::$password = env('MQTT_PASSWORD');
    }

    public static function connect($server, $port)
    {
        if (self::$client && self::$server === $server && self::$port === $port) {
            return; // Ya conectado
        }

        self::$server = $server;
        self::$port = $port;
        $clientId = uniqid();
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);
        $connectionSettings->setUsername(self::$username);
        $connectionSettings->setPassword(self::$password);

        self::$client = new MqttClient($server, $port, $clientId);
        self::$client->connect($connectionSettings, true);

        Log::info('Connected to MQTT server.', [
            'server' => $server,
            'port' => $port,
            'clientId' => $clientId
        ]);
    }

    public static function publishMessage($topic, $message, $server, $port)
    {
        $maxRetries = 3;
        $retryDelay = 5; // seconds
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                // Intentar conectar si el cliente no está conectado o la conexión está cerrada
                if (!self::$client || !self::$client->isConnected()) {
                    self::connect($server, $port);
                }

                self::$client->publish($topic, json_encode($message), 0);
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
                } else {
                    Log::critical('Exceeded maximum number of retries. Message could not be published.', [
                        'topic' => $topic,
                        'message' => $message
                    ]);
                }
            }
        }
    }

    public static function disconnect()
    {
        if (self::$client) {
            self::$client->disconnect();
            self::$client = null;
            Log::info('Disconnected from MQTT server.');
        }
    }
}
