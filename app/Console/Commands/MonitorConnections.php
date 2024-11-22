<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonitorConnection;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\MonitorConnectionStatus;

class MonitorConnections extends Command
{
    protected $signature = 'monitor:connections';
    protected $description = 'Monitor MQTT topics for connections and update their status in the database';

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Configuración para manejar señales
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () {
                $this->shouldContinue = false;
            });
            pcntl_signal(SIGINT, function () {
                $this->shouldContinue = false;
            });

            $this->shouldContinue = true;

            // Inicializar el cliente MQTT
            $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));

            // Suscribirse a los tópicos
            $this->subscribeToAllTopics($mqtt);

            // Bucle principal
            while ($this->shouldContinue) {
                $mqtt->loop(true);
                pcntl_signal_dispatch();
                usleep(100000); // 0.1 segundos
            }

            $mqtt->disconnect();
            $this->info("MQTT monitoring stopped gracefully.");

        } catch (\Exception $e) {
            $this->error("Error in monitor:connections: " . $e->getMessage());
        }
    }

    private function initializeMqttClient($server, $port)
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(60);
        $connectionSettings->setUseTls(false);
        $connectionSettings->setUsername(env('MQTT_USERNAME'));
        $connectionSettings->setPassword(env('MQTT_PASSWORD'));

        $mqtt = new MqttClient($server, $port, uniqid());
        $mqtt->connect($connectionSettings, true);

        return $mqtt;
    }

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        $topics = MonitorConnection::whereNotNull('mqtt_topic')
            ->where('mqtt_topic', '!=', '')
            ->pluck('mqtt_topic')
            ->toArray();

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->subscribedTopics)) {
                $mqtt->subscribe($topic, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topic;
                $this->info("Subscribed to topic: {$topic}");
            }
        }

        $this->info('Subscribed to all monitor topics.');
    }

    private function processMessage($topic, $message)
    {
        $data = json_decode($message, true);
    
        if (is_null($data) || !isset($data['status'])) {
            $this->error("Invalid message received on topic {$topic}. Message must be a valid JSON with a 'status' key.");
            return;
        }
    
        $status = $data['status'];
    
        $monitor = MonitorConnection::where('mqtt_topic', $topic)->first();
    
        if (!$monitor) {
            $this->error("No monitor connection found for topic {$topic}.");
            return;
        }
    
        try {
            // Verificar si el último estado es igual al recibido
            $lastStatus = $monitor->statuses()->latest()->value('status');
    
            if ($lastStatus !== $status) {
                // Guardar el nuevo estado en monitor_connection_statuses
                $monitor->statuses()->create(['status' => $status]);
    
                // Actualizar el campo last_status en monitor_connections
                $monitor->update(['last_status' => $status]);
    
                $this->info("Updated status for monitor connection {$monitor->name} ({$monitor->id}): {$status}");
            } else {
                $this->info("No update needed for monitor connection {$monitor->name} ({$monitor->id}): status is unchanged.");
            }
        } catch (\Exception $e) {
            $this->error("Failed to process message for monitor connection {$monitor->id}: " . $e->getMessage());
        }
    }    
}