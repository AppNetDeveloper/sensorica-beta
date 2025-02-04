<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MqttSendServer2;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConnectionException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Log;

class PublishToMqttServer2 extends Command
{
    protected $signature = 'mqtt:publish-server2';
    protected $description = 'Publishes data to MQTT Server 2 based on mqtt_send_server2 table';
    protected $mqtt; // Definición de la propiedad MQTT

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if ($this->initializeMqttClient()) { // Inicializar la conexión al inicio

            while (true) {
                try {
                    $mqttEntries = MqttSendServer2::all();

                    foreach ($mqttEntries as $entry) {
                        // Intentamos publicar, si falla, se marcará para reconectar
                        if ($this->publishToMqtt($entry)) {
                            $entry->delete(); // Elimina la entrada después de publicarla exitosamente
                        } else {
                            // Si falla la publicación, forzamos una reconexión y salimos del foreach para reiniciar el ciclo
                            throw new \Exception("Error al publicar el mensaje. Se requiere reconexión.");
                        }
                    }

                    usleep(100000); // Pausa para evitar sobrecarga

                } catch (\Exception $e) {
                    Log::error("Error en el loop de publicación a MQTT Server 2: " . $e->getMessage());
                    $this->reconnectClient(); // Intentar reconectar si hay un error
                }
            }

            //$this->disconnectMqttClient(); // Desconectar al final del proceso (no se alcanza en este loop infinito)
        }
    }

    private function initializeMqttClient()
    {
        $connectionSettings = new ConnectionSettings();
        $connectionSettings->setKeepAliveInterval(20); // Mantener viva la conexión
        $connectionSettings->setUseTls(false);
        $connectionSettings->setTlsSelfSignedAllowed(false);

        $this->mqtt = new MqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')), uniqid());

        try {
            $this->mqtt->connect($connectionSettings, true);
            $this->info("Conectado a MQTT Server 2");
            return true;
        } catch (ConnectionException $e) {
            $this->error("Error de conexión con MQTT Server 2: " . $e->getMessage());
            return false;
        }
    }

    private function publishToMqtt($entry)
    {
        try {
            $this->mqtt->publish($entry->topic, $entry->json_data, 0); // Publicar con QoS 0
            $this->info("Mensaje enviado a MQTT Server 2 - Tópico: " . $entry->topic . " | Datos: " . $entry->json_data);
            return true;
        } catch (DataTransferException $e) {
            $this->error("Error de transferencia de datos en MQTT Server 2: " . $e->getMessage());
            return false;
        }
    }

    private function disconnectMqttClient()
    {
        if ($this->mqtt) {
            try {
                $this->mqtt->disconnect();
                $this->info("Desconectado del servidor MQTT 2");
            } catch (\Exception $e) {
                $this->error("Error al desconectar el cliente MQTT: " . $e->getMessage());
            }
        }
    }

    private function reconnectClient()
    {
        try {
            $this->disconnectMqttClient();
            sleep(2); // Pausa antes de intentar reconectar
            if ($this->initializeMqttClient()) {
                $this->info("Reconectado exitosamente a MQTT Server 2");
            } else {
                $this->error("No se pudo reconectar a MQTT Server 2. Se intentará nuevamente.");
            }
        } catch (\Exception $e) {
            Log::error("Fallo al reconectar con MQTT Server 2: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        $this->disconnectMqttClient(); // Asegurar la desconexión al finalizar el script
    }
}
