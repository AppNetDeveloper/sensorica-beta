<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;
use Illuminate\Support\Facades\Log; // Cambié el uso de Log aquí
use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Exception\ConnectionException;

class DebugController extends Controller
{
    private function publishMqttMessage($topic, $message)
    {
        $server = env('MQTT_SERVER');
        $port = intval(env('MQTT_PORT'));
        $clientId = uniqid();

        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME'));
        $connectionSettings->setPassword(env('MQTT_PASSWORD'));

        try {
            $mqtt = new MqttClient($server, $port, $clientId);
            $mqtt->connect($connectionSettings, true);

            Log::info('Connected to MQTT server.', [
                'server' => $server,
                'port' => $port,
                'clientId' => $clientId
            ]);

            $mqtt->publish($topic, json_encode($message), 0);

            Log::info('Published MQTT message.', [
                'topic' => $topic,
                'message' => $message
            ]);

            $mqtt->disconnect();

            Log::info('Disconnected from MQTT server.');
        } catch (\Exception $e) {
            Log::error('Failed to publish MQTT message.', [
                'error' => $e->getMessage(),
                'topic' => $topic,
                'message' => $message
            ]);
        }
    }


    
    public function index(Request $request)
    {
        
    }
}