<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MqttSendServer2;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\ConnectionException;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\Exceptions\ConnectingToBrokerFailedException;
use Illuminate\Support\Facades\Log;
//anadir carbon
use Carbon\Carbon;

class PublishToMqttServer2 extends Command
{
    protected $signature = 'mqtt:publish-server2';
    protected $description = 'Publica datos en MQTT Server 2 basándose en la tabla mqtt_send_server2';

    /** @var MqttClient|null */
    protected $mqtt = null;

    /** @var bool */
    protected $shouldStop = false;

    public function __construct()
    {
        parent::__construct();

        // Registrar el manejo de señales para una terminación limpia
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }

    /**
     * Maneja las señales de terminación.
     *
     * @param int $signal
     */
    public function handleSignal(int $signal)
    {
        if ($this->output) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Señal de terminación recibida ($signal). Cerrando el proceso...");
        } else {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Señal de terminación recibida ($signal). Cerrando el proceso...");
        }
        $this->shouldStop = true;
    }

    public function handle()
    {
        if (!$this->initializeMqttClient()) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]No se pudo establecer la conexión inicial con MQTT Server 2. Abortando...");
            return 1;
        }

        // Bucle principal controlado por la variable $shouldStop
        while (!$this->shouldStop) {
            try {
                // Procesamos los registros en bloques para evitar cargas excesivas de memoria
                MqttSendServer2::chunk(100, function ($mqttEntries) {
                    foreach ($mqttEntries as $entry) {
                        if ($this->shouldStop) {
                            return false; // Sale del chunk si se recibe la señal de cierre
                        }

                        if ($this->publishToMqtt($entry)) {
                            $entry->delete();
                        } else {
                            // Forzamos una reconexión si ocurre un error en la publicación
                            throw new \Exception("Error al publicar el mensaje en el tópico: {$entry->topic}");
                        }
                    }
                });

                usleep(100000); // Pausa de 100ms para evitar saturar la base de datos y el broker

            } catch (\Exception $e) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error en el loop de publicación a MQTT Server 2: " . $e->getMessage());
                $this->reconnectClient();
            }
        }

        $this->disconnectMqttClient();

        return 0;
    }

    /**
     * Inicializa la conexión con el broker MQTT.
     *
     * @return bool
     */
    private function initializeMqttClient(): bool
    {
        $connectionSettings = (new ConnectionSettings())
            ->setKeepAliveInterval(20)
            ->setUseTls(false)
            ->setTlsSelfSignedAllowed(false);

        // Se recomienda centralizar la configuración (por ejemplo, en config/mqtt.php)
        $host = config('mqtt.server2.host', env('MQTT_SENSORICA_SERVER'));
        $port = intval(config('mqtt.server2.port', env('MQTT_SENSORICA_PORT')));
        $clientId = uniqid('mqtt_client_', true);

        $this->mqtt = new MqttClient($host, $port, $clientId);

        try {
            $this->mqtt->connect($connectionSettings, true);
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Conectado a MQTT Server 2 en {$host}:{$port}");
            return true;
        } catch (ConnectionException $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Error de conexión con MQTT Server 2: " . $e->getMessage());
            return false;
        } catch (ConnectingToBrokerFailedException $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Fallo al establecer conexión con MQTT Server 2: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publica un mensaje en el broker MQTT.
     *
     * @param MqttSendServer2 $entry
     * @return bool
     */
    private function publishToMqtt(MqttSendServer2 $entry): bool
    {
        try {
            // Publica el mensaje con QoS 0
            $this->mqtt->publish($entry->topic, $entry->json_data, 0, true);
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Mensaje enviado - Tópico: {$entry->topic} | Datos: {$entry->json_data}");
            return true;
        } catch (DataTransferException $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Error de transferencia de datos en MQTT Server 2: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desconecta el cliente MQTT, verificando si está conectado.
     */
    private function disconnectMqttClient(): void
    {
        if ($this->mqtt) {
            try {
                // Comprueba si el método isConnected existe y si el cliente está conectado
                if (method_exists($this->mqtt, 'isConnected') && !$this->mqtt->isConnected()) {
                    $this->info("[" . Carbon::now()->toDateTimeString() . "]El cliente MQTT ya está desconectado.");
                    return;
                }
                $this->mqtt->disconnect();
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Desconectado de MQTT Server 2");
            } catch (\Exception $e) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al desconectar el cliente MQTT: " . $e->getMessage());
            }
        }
    }

    /**
     * Intenta reconectar el cliente MQTT tras un fallo.
     */
    private function reconnectClient(): void
    {
        $this->disconnectMqttClient();
        sleep(5); // Espera 5 segundos antes de reconectar

        if ($this->initializeMqttClient()) {
            $this->info("[" . Carbon::now()->toDateTimeString() . "]Reconectado exitosamente a MQTT Server 2");
        } else {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]No se pudo reconectar a MQTT Server 2. Se intentará nuevamente en el siguiente ciclo.");
        }
    }

    /**
     * Destructor para asegurar la desconexión del cliente MQTT.
     */
    public function __destruct()
    {
        try {
            $this->disconnectMqttClient();
        } catch (\Exception $e) {
            // Se ignoran los errores en el destructor al finalizar el script
        }
    }
}
