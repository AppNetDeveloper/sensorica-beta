<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Barcode;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;

class TcpClient extends Command
{
    protected $signature = 'tcp:client';
    protected $description = 'Connect to multiple TCP servers and read messages continuously';

    protected $processes = [];
    protected $previousBarcodes = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Matar todos los procesos existentes antes de iniciar
        $this->terminateAllProcesses();

        // Inicializar el bucle principal
        while (true) {
            // Obtener todos los barcodes de la base de datos
            $barcodes = Barcode::all();

            // Comprobar cambios en los barcodes
            $this->checkForChanges($barcodes);

            // Esperar un tiempo antes de volver a verificar
            sleep(10); // Esperar 10 segundos antes de la próxima verificación
        }
    }

    private function terminateAllProcesses()
    {
        foreach ($this->processes as $id => $pid) {
            $this->info("Stopping TCP client for barcode ID: $id");
            if (posix_kill($pid, SIGTERM)) {
                unset($this->processes[$id]);
                $this->info("Successfully stopped process for barcode ID: $id");
            } else {
                $this->error("Failed to stop process for barcode ID: $id with PID: $pid");
            }
        }
        $this->info("Stopping all TCP client processes");
        exec("ps aux | grep 'artisan tcp:client' | grep -v grep | awk '{print $2}' | xargs kill -9");
    }

    protected function checkForChanges($currentBarcodes)
    {
        // Convertir la colección a un array de IDs para fácil comparación
        $currentIds = $currentBarcodes->pluck('id')->toArray();
        $previousIds = array_keys($this->previousBarcodes);

        // Encontrar IDs que han sido eliminados
        $removedIds = array_diff($previousIds, $currentIds);
        foreach ($removedIds as $id) {
            // Terminar el proceso hijo asociado
            $this->info("Stopping TCP client for removed barcode ID: $id");
            if (isset($this->processes[$id])) {
                if (posix_kill($this->processes[$id], SIGTERM)) {
                    unset($this->processes[$id]);
                    $this->info("Successfully stopped process for removed barcode ID: $id");
                } else {
                    $this->error("Failed to stop process for removed barcode ID: $id with PID: {$this->processes[$id]}");
                }
            }
        }

        // Encontrar o iniciar procesos para nuevos barcodes y reiniciar los procesos si hay cambios
        foreach ($currentBarcodes as $barcode) {
            $hasChanged = false;

            // Verificar si el barcode ya existe en la lista previa
            if (isset($this->previousBarcodes[$barcode->id])) {
                $previousBarcode = $this->previousBarcodes[$barcode->id];
                // Comprobar si algún atributo importante ha cambiado
                if (
                    $previousBarcode['ip_barcoder'] !== $barcode->ip_barcoder ||
                    $previousBarcode['ip_zerotier'] !== $barcode->ip_zerotier ||
                    $previousBarcode['port_barcoder'] !== $barcode->port_barcoder ||
                    $previousBarcode['conexion_type'] !== $barcode->conexion_type
                ) {
                    $hasChanged = true;
                    $this->info("Configuration change detected for barcode ID: {$barcode->id}");
                }
            }

            if (!isset($this->previousBarcodes[$barcode->id]) || $hasChanged) {
                // Si es un nuevo barcode o ha cambiado la configuración, iniciar un nuevo proceso,

                // Si ya hay un proceso para este barcode, terminarlo primero
                if (isset($this->processes[$barcode->id])) {
                    $this->info("Restarting TCP client for barcode ID: {$barcode->id}");
                    if (posix_kill($this->processes[$barcode->id], SIGTERM)) {
                        unset($this->processes[$barcode->id]);
                        $this->info("Successfully stopped process for barcode ID: {$barcode->id}");
                    } else {
                        $this->error("Failed to stop process for barcode ID: {$barcode->id} with PID: {$this->processes[$barcode->id]}");
                    }
                } else {
                    $this->info("Starting TCP client for new barcode ID: {$barcode->id}");
                }

                // Verificar si los valores de IP y puerto son válidos
                $ip = $barcode->conexion_type == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
                $port = $barcode->port_barcoder;

                if (empty($ip) || empty($port)) {
                    $this->info("Ignoring TCP client for barcode ID: {$barcode->id} due to empty IP or port.");
                    continue;
                }

                $pid = pcntl_fork();
                if ($pid == -1) {
                    $this->error("Error al crear un proceso hijo para barcode ID: {$barcode->id}");
                } elseif ($pid) {
                    // Proceso padre
                    $this->processes[$barcode->id] = $pid; // Guardar el PID del proceso hijo
                } else {
                    // Proceso hijo
                    $this->handleBarcode($barcode);
                    exit(0); // Terminar el proceso hijo después de su tarea
                }
            }
        }

        // Actualizar la lista de barcodes anteriores
        $this->previousBarcodes = $currentBarcodes->keyBy('id')->toArray();
    }

    protected function handleBarcode($barcode)
    {
        $conexionType = $barcode->conexion_type;

        if ($conexionType == 0) {
            $this->info("No TCP connection will be made for barcode ID {$barcode->id}.");
            return;
        }

        // Obtener la información de conexión
        $host = $conexionType == 1 ? $barcode->ip_barcoder : $barcode->ip_zerotier;
        $port = $barcode->port_barcoder;

        // Verificar si los valores de IP y puerto son válidos
        if (empty($host) || empty($port)) {
            $this->info("Ignoring TCP client for barcode ID: {$barcode->id} due to empty IP or port.");
            return;
        }

        while (true) {
            $this->info("Connecting to TCP server at $host:$port for barcode ID {$barcode->id}");
            // Crear un socket TCP/IP
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

            if ($socket === false) {
                $this->error("Error al crear el socket: " . socket_strerror(socket_last_error()));
                sleep(5); // Esperar 5 segundos antes de intentar nuevamente
                continue; // Volver a intentar la conexión
            }

            // Conectar al servidor TCP
            $result = @socket_connect($socket, $host, $port); // Usar @ para silenciar advertencias

            if ($result === false) {
                $errorCode = socket_last_error($socket);
                $this->error("Error al conectar al servidor: " . socket_strerror($errorCode) . " (Código de error: $errorCode)");
                socket_close($socket);
                sleep(5); // Esperar 5 segundos antes de intentar nuevamente
                continue; // Volver a intentar la conexión
            }

            $this->info("Conectado al servidor TCP en $host:$port para barcode ID {$barcode->id}");

            // Bucle para leer mensajes continuamente
            while (true) {
                $response = @socket_read($socket, 2048, PHP_NORMAL_READ);
                if ($response === false) {
                    $this->error("Error al leer del servidor: " . socket_strerror(socket_last_error($socket)));
                    break; // Salir para intentar reconectar
                }

                // Verifica si el servidor ha cerrado la conexión
                if ($response === '') {
                    $this->info("El servidor ha cerrado la conexión para barcode ID {$barcode->id}");
                    break; // Salir para intentar reconectar
                }

                // Mensaje de depuración cuando se recibe un mensaje
                $this->info("Mensaje recibido del servidor para barcode ID {$barcode->id}: $response");

                // Asegúrate de que el mensaje no esté vacío antes de procesarlo
                if (trim($response) !== '') {
                    // Procesar el mensaje recibido
                    $this->processMessage($barcode, $response);
                } else {
                    $this->info("Mensaje vacío recibido para barcode ID {$barcode->id}, ignorando.");
                }
            }

            // Cerrar el socket
            socket_close($socket);
            $this->info("Intentando reconectar en 5 segundos para barcode ID {$barcode->id}...");
            sleep(5); // Esperar 5 segundos antes de intentar reconectar
        }
    }

    protected function processMessage($barcode, $message)
    {
        // Mensaje recibido de depuración
        $this->info("Processing message for barcode ID {$barcode->id}: $message");

        // Verifica si el mensaje está vacío o solo contiene espacios
        if (trim($message) === '') {
            $this->info("Mensaje vacío para barcode ID {$barcode->id}, ignorando.");
            return; // Salir si el mensaje está vacío
        }
        // Procesar el comando
        $this->handleMqttCommands($barcode->id, $message);
    }

    protected function handleMqttCommands($id, $barcodeValue)
    {
        // Mensaje recibido de depuración
        $this->info("estoy aqui {$id}: $barcodeValue");
        $barcode= Barcode::where('id', $id)->first();
        $mqttTopicBarcodes = $barcode->mqtt_topic_barcodes;
        $mqttTopicOrders = $barcode->mqtt_topic_orders;
        $mqttTopicFinish = $barcode->mqtt_topic_finish;
        $mqttTopicPause = $barcode->mqtt_topic_pause;
        $opeId = $barcode->ope_id;
        $orderNotice = $barcode->order_notice;
        $lastBarcode = $barcode->last_barcode;
        $machineId = $barcode->machine_id;

        $orderNoticeData = json_decode($orderNotice, true);
        $orderId = $orderNoticeData['orderId'] ?? null;

        $comando = [];
        $mqttTopic = null; // Initialize to null to avoid errors
        $barcodeValue = trim($barcodeValue);

        if (in_array($lastBarcode, ['FINALIZAR', 'PAUSAR', null, '']) && $barcodeValue === 'INICIAR') {
            // Case 1: lastBarcode is FINALIZAR, PAUSAR, NULL, or empty, and barcodeValue is INICIAR
            $comando = [
                "action" => 0,
                "orderId" => $orderId,
                "machineId" => $machineId,
                "opeId" => "ENVASADO"
            ];
            $mqttTopic = $mqttTopicBarcodes;
            $this->publishMqttMessage($mqttTopic, $comando);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'INICIAR') {
            // Case 2: lastBarcode and barcodeValue are both INICIAR
            $comando = [
                "action" => 1,
                "orderId" => $orderId,
                "machineId" => $machineId,
                "opeId" => "ENVASADO"
            ];
            $mqttTopic = $mqttTopicBarcodes;

            Log::info('Preparing to publish first MQTT message.', [
                'topic' => $mqttTopic,
                'comando' => $comando
            ]);

            $this->publishMqttMessage($mqttTopic, $comando);
            Log::info('Mensaje MQTT enviado.', [
                'topic' => $mqttTopic,
                'comando' => $comando
            ]);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();

            sleep(3); // Wait 3 seconds

            // Re actualizar el last_barcode
            $updatedOrderNotice = json_decode($barcode->order_notice, true);
            $updatedOrderId = $updatedOrderNotice['orderId'] ?? null;

            $comando = [
                "action" => 0,
                "orderId" => $updatedOrderId, // Use the updated order ID
                "machineId" => $machineId,
                "opeId" => "ENVASADO"
            ];
            $mqttTopic = $mqttTopicBarcodes;

            $this->publishMqttMessage($mqttTopic, $comando);
            Log::info('Mensaje MQTT enviado.', [
                'topic' => $mqttTopic,
                'comando' => $comando
            ]);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'FINALIZAR') {
            // Case 3: lastBarcode is INICIAR and barcodeValue is FINALIZAR
            $comando = [
                "orderId" => $orderId
            ];
            $mqttTopic = $mqttTopicFinish;
            $this->publishMqttMessage($mqttTopic, $comando);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        } elseif ($lastBarcode === 'INICIAR' && $barcodeValue === 'PAUSAR') {
            // Case 4: lastBarcode is INICIAR and barcodeValue is PAUSAR
            $comando = [
                "orderId" => $orderId
            ];
            $mqttTopic = $mqttTopicPause;
            $this->publishMqttMessage($mqttTopic, $comando);

            $barcode->last_barcode = $barcodeValue;
            $barcode->save();
        }
    }

    // Funciones mqtt
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
}
