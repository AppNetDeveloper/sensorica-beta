<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modbus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Exceptions\DataTransferException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Http;
use App\Models\ControlWeight;
use App\Models\ApiQueuePrint;
use Rawilk\Printing\Facades\Printing;
use Picqer\Barcode\BarcodeGeneratorException;
use Picqer\Barcode\BarcodeGeneratorPNG;
use App\Models\Printer;
//use App\Helpers\MqttHelper; // Importa el helper
use App\Services\MqttService; //importar el servicio
//use App\Helpers\MqttPersistentHelper; //helper mqtt persistent

class MqttSubscriberModbus extends Command
{
    protected $signature = 'modbus:test';
    protected $description = 'Lee datos de MQTT y procesa cada tema con sus reglas específicas';

    protected $mqttClients = [];
    protected $subscribedTopics = [];
    protected $configHashes = []; // Guardará los hashes de configuración

    protected $mqttService; // Añade la propiedad para el servicio de MQTT

    public function __construct(MqttService $mqttService)
    {
        parent::__construct();
        $this->mqttService = $mqttService;
        //MqttPersistentHelper::init();
    }

    public function handle()
    {
        // Ciclo principal que verifica y gestiona las conexiones MQTT
        while (true) {
            $this->checkAndSubscribeNewTopics();
            foreach ($this->mqttClients as $identifier => $client) {
                try {
                    Log::info("Procesando cliente MQTT: $identifier");
                    $client->loop(true); // Mantener la conexión activa y procesar mensajes
                } catch (DataTransferException $e) {
                    Log::error("Error de transferencia de datos en el cliente $identifier: {$e->getMessage()}. Reintentando conexión.");
                    $this->reconnectClient($identifier);
                } catch (\Exception $e) {
                    Log::error("Error inesperado en el cliente $identifier: {$e->getMessage()}. Reintentando conexión.");
                    $this->reconnectClient($identifier);
                }
            }
            // Esperar 0.1 segundos después de procesar todos los clientes
            Log::info("Esperando 0.1 segundos después de procesar todos los clientes.");
            usleep(100000); // 100000 microsegundos = 0.1 segundos
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

    private function checkAndSubscribeNewTopics()
{
    $modbusConfigs = Modbus::all()->keyBy('id');
    $knownIdentifiers = [];

    foreach ($modbusConfigs as $config) {
        $identifier = "{$config->mqtt_server}:{$config->mqtt_port}";
        $configHash = md5($identifier . $config->mqtt_topic_modbus);

        $knownIdentifiers[$identifier] = true;

        // Check if we need to initialize or update the client
        if (!isset($this->mqttClients[$identifier]) || (isset($this->configHashes[$config->id]) && $this->configHashes[$config->id] !== $configHash)) {
            $this->initializeOrUpdateClient($config, $identifier, $configHash);
        }

        if (!isset($this->subscribedTopics[$identifier][$config->id])) {
            $this->mqttClients[$identifier]->subscribe($config->mqtt_topic_modbus, function ($topic, $message) use ($config) {
                $this->processMessage($config, $topic, $message);
            }, 0);
            $this->subscribedTopics[$identifier][$config->id] = $config->mqtt_topic_modbus;
            $this->info("Subscribed to new topic: {$config->mqtt_topic_modbus} for line ID {$config->id}");
        }
    }

    // Cleanup old clients and topics
    foreach ($this->subscribedTopics as $identifier => $topics) {
        foreach ($topics as $id => $subscribedTopic) {
            if (!isset($modbusConfigs[$id])) {
                if (isset($this->mqttClients[$identifier])) {
                    $this->mqttClients[$identifier]->unsubscribe($subscribedTopic);
                }
                unset($this->subscribedTopics[$identifier][$id]);
                $this->info("Unsubscribed from topic: {$subscribedTopic} for line ID {$id}");
            }
        }
        if (empty($this->subscribedTopics[$identifier])) {
            $this->cleanupClient($identifier);
        }
    }

    // Remove stale hashes
    foreach ($this->configHashes as $id => $hash) {
        if (!isset($modbusConfigs[$id])) {
            unset($this->configHashes[$id]);
        }
    }
}


    private function initializeOrUpdateClient($config, $identifier, $configHash)
    {
        if (isset($this->mqttClients[$identifier])) {
            $this->mqttClients[$identifier]->disconnect();
            unset($this->mqttClients[$identifier], $this->subscribedTopics[$identifier]);
            $this->info("Reinitialized MQTT client for $identifier due to configuration change for line ID {$config->id}");
        } else {
            $this->info("Initialized MQTT client for $identifier with topic {$config->mqtt_topic_modbus}");
        }

        $this->mqttClients[$identifier] = $this->initializeMqttClient($config->mqtt_server, $config->mqtt_port);
        $this->subscribedTopics[$identifier] = [];
        $this->configHashes[$config->id] = $configHash;

        Log::info("Initialized or updated MQTT client for $identifier with topic {$config->mqtt_topic_modbus}");
    }

    private function cleanupClient($identifier)
{
    if (isset($this->mqttClients[$identifier])) {
        $this->mqttClients[$identifier]->disconnect();
        unset($this->mqttClients[$identifier], $this->subscribedTopics[$identifier]);
        $this->info("Disconnected MQTT client and cleaned up topics for $identifier.");
        Log::info("Disconnected MQTT client and cleaned up topics for $identifier.");
    }
}


    private function reconnectClient($identifier)
    {
        if (isset($this->mqttClients[$identifier])) {
            try {
                $this->mqttClients[$identifier]->disconnect();
            } catch (\Exception $e) {
                Log::warning("No se pudo desconectar el cliente $identifier: {$e->getMessage()}");
            }
            unset($this->mqttClients[$identifier]);
        }
        $config = Modbus::where('mqtt_server', $identifier)->first();
        if ($config) {
            $this->initializeOrUpdateClient($config, $identifier, md5($identifier . $config->mqtt_topic_modbus));
        }
    }

    private function processMessage($configMqttClient, $topic, $message)
    {
        $lineId = $configMqttClient->id;
        $config = Modbus::find($lineId);

        $data = json_decode($message, true);
        if (is_null($data)) {
            Log::error("Error: El mensaje recibido no es un JSON válido.");
            return;
        }

        // Verificar si la configuración no existe
        if (is_null($config)) {
            Log::error("Error: No se encontró la configuración para line ID {$lineId}. La línea puede haber sido eliminada.");

            // Reiniciar el cliente MQTT
            $identifier = $this->findIdentifierByTopic($topic);
            if ($identifier) {
                $this->reconnectClient($identifier);
            }

            return;
        }

        Log::info("Contenido del line ID {$lineId} JSON: " . print_r($data, true));

        $value = null;
        if (empty($config->json_api)) {
            $value = $data['value'] ?? null;
            if ($value === null) {
                Log::error("Error: No se encontró 'value' en el JSON cuando json_api está vacío.");
                return;
            }
        } else {
            $jsonPath = $config->json_api;
            $value = $this->getValueFromJson($data, $jsonPath);
            if ($value === null) {
                Log::warning("Advertencia: No se encontró la clave '$jsonPath' en la respuesta JSON, buscando el valor directamente.");
                $value = $data['value'] ?? null;
                if ($value === null) {
                    Log::error("Error: No se encontró 'value' en el JSON.");
                    return;
                }
            }
        }

        Log::info("Mensaje: {$config->name} (ID: {$config->id}) // topic: {$topic} // value: {$value}");

        switch ($config->model_name) {
            case 'weight':
                $this->processWeightModel($config, $value);
                break;
            case 'height':
                $this->processHeightModel($config, $value);
                break;
            case 'sensor':
                $this->processSensorModel($config, $value);
                break;
            default:
                Log::warning("Modelo desconocido: {$config->model_name}");
                break;
        }
    }

    private function findIdentifierByTopic($topic)
    {
        foreach ($this->subscribedTopics as $identifier => $topics) {
            if (in_array($topic, $topics)) {
                return $identifier;
            }
        }
        return null;
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

    public function processWeightModel($config, $value)
    {
        $updatedValue = $value / 10;
        $mqttTopic = $config->mqtt_topic_gross;

        $this->processWeightData($config, $updatedValue);

       // Obtiene el último valor guardado
    $lastValue = $config->last_value;

    // Actualiza el valor en la base de datos si ha cambiado
    if ($updatedValue != $lastValue) {
        $updateResponse = $config->update(['last_value' => $updatedValue]);

        // Logea la respuesta de la actualización
        if ($updateResponse) {
            Log::info("Actualización exitosa. Valor original: {$lastValue}, Valor actualizado: {$updatedValue}");
        } else {
            Log::error("Error en la actualización de last_value. Valor original: {$lastValue}, Valor intentado actualizar: {$updatedValue}");
        }

        // Construye el mensaje
        $message = [
            'value' => $updatedValue,
            'time' => date('c')
        ];

        Log::info("Mensaje MQTT: " . json_encode($message));

        // Publica el mensaje MQTT
        $this->publishMqttMessage($mqttTopic, $message);
        } else {
            // Logea que el valor no ha cambiado y no se envía el mensaje MQTT
            Log::info("Mismo valor no se manda MQTT: " . json_encode(['value' => $lastValue, 'time' => date('c')]));

        }
    }

    // Implementar funciones para otros modelos
    public function processHeightModel($config, $value)
    {
        // Lógica para procesar datos de altura
        Log::info("Procesando modelo de altura. Valor: {$value}");
        // Aquí iría la lógica específica para altura
    }

    public function processSensorModel($config, $value)
    {
        // Lógica para procesar datos del sensor
        Log::info("Procesando modelo de sensor. Valor: {$value}");
        // Aquí iría la lógica específica para sensores
    }

    private function processWeightData(Modbus $config, $value)
    {
    // Obtener valores actuales de la base de datos
    $maxKg = intval($config->max_kg);
    $repNumber = intval($config->rep_number);
    $minKg = intval($config->min_kg);
    $lastKg = intval($config->last_kg);
    $lastRep = intval($config->last_rep);
    $variacionNumber = intval($config->variacion_number);
    $topic_control = $config->mqtt_topic_control;
    $topic_box_control = $config->mqtt_topic_boxcontrol;
    $dimensionFinal = intval($config->dimension);
    //Log::debug("({$minKg} kg)");

    // Inicializar la variable para el número de cajas
    $newBoxNumber = intval($config->rec_box);


        // Lógica de control de peso y repeticiones
        if ($value >= $minKg) { // Si el valor actual es mayor o igual al mínimo
            Log::debug("Valor actual ({$value} kg) es mayor o igual al mínimo ({$minKg} kg)"); // Logging detallado

            if (abs($value - $lastKg) <= $variacionNumber) { // Si la variación está dentro del rango permitido
                Log::debug("Valor estable dentro del rango de variación.");
                $lastRep++; // Incrementar el contador de repeticiones

                if ($lastRep >= $repNumber && $value >= $minKg && $value > $maxKg) { // Si se alcanza el número de repeticiones requerido, pero el valor es mas grande que minimo permitido y que el valor es mas grande que maxKG
                    Log::debug("Número de repeticiones alcanzado. Nuevo máximo: {$value} kg");
                    $maxKg = $value; // Actualizar el valor máximo
                    $lastRep = 0; // Reiniciar el contador de repeticiones
                }
            } else {
                Log::debug("Valor fuera del rango de variación. Reiniciando repeticiones.");
                $lastRep = 0; // Reiniciar el contador de repeticiones si la variación está fuera del rango permitido
            }

            $lastKg = $value; // Actualizar el último valor con el valor actual
        } else if ($maxKg > $minKg && $value < $minKg) { // Si el valor es menor que el mínimo y $maxKg no es nulo
            Log::debug("Valor por debajo del mínimo. Enviando mensaje de control de peso: {$maxKg} kg");

            $messageControl = [
                        'type' => "NoEPC",
                        'unit' => "Kg",
                        'value' => $maxKg,
                        'excess' => "0",
                        'total_excess' => "0",
                        'rating' => "1",
                        'time' => date('c'),
                        'check' => "1"
                ];
            $this->publishMqttMessage($topic_control, $messageControl); // Enviar mensaje de control


            // Incrementar el recuento de cajas en rec_box
            $newBoxNumber++;

            // Generar un número de barcoder único
            $uniqueBarcoder = uniqid('bar_', true);

            // Intentar guardar los datos en la tabla control_weight
        try {
            $controlWeight = ControlWeight::create([
                'modbus_id' => $config->id,
                'last_control_weight' => $maxKg,
                'last_dimension' => null, //no tenemos medidor por momento
                'last_box_number' => $newBoxNumber,
                'last_barcoder' => $uniqueBarcoder,
                'last_final_barcoder' => null,
            ]);

            // Log informativo de los datos guardados
            Log::info("Datos guardados en control_weight", [
                'modbus_id' => $controlWeight->modbus_id,
                'last_control_weight' => $controlWeight->last_control_weight,
                'last_dimension' => $controlWeight->last_dimension,
                'last_box_number' => $controlWeight->last_box_number,
                'last_barcoder' => $controlWeight->last_barcoder,
                'last_final_barcoder' => $controlWeight->last_final_barcoder,
            ]);
        } catch (\Exception $e) {
            // Log de errores al intentar guardar los datos
            Log::error("Error al guardar datos en control_weight", [
                'error' => $e->getMessage(),
                'modbus_id' => $config->id,
                'last_control_weight' => $maxKg,
                'last_dimension' => null, //no tenemos medidor por momento
                'last_box_number' => $newBoxNumber,
                'last_barcoder' => $uniqueBarcoder,
            ]);
        }


            $maxKg = 0; // Reiniciar el valor máximo
            $lastKg = 0; // Reiniciar el último valor
            $lastRep = 0; // Reiniciar el contador de repeticiones

            //llamar mqtt recuento de bultos cajas
            $messageBoxNumber = [
                    'value' => $newBoxNumber,
                    'status' => '0'
                ];
            $this->publishMqttMessage($topic_box_control, $messageBoxNumber); // Enviar mensaje de control

                //llamar a la api externa si se ha pedido desde el cliente, esto comprueba si el cliente nos ha mandado valor en api para devolverle las info

            $apiQueue = ApiQueuePrint::where('modbus_id', $config->id)
                ->where('used', false)
                ->oldest()
                ->first();

            if ($apiQueue) {
                $this->callExternalApi($apiQueue, $config, $newBoxNumber, $maxKg, $dimensionFinal, $uniqueBarcoder);
            }

            //llamar a la impresora local para imprimir si es un bulto anonimo para habilitar bultos anonimos tenemos que anadir una impresora a la modbus si impresora no existe no se imprime, el printer_id tiene que no estar null con 0 o vacio
            if (!is_null($config->printer_id) && trim($config->printer_id)) {
                $this->printLabel($config, $uniqueBarcoder);
            } else {
                Log::info('No hay configuración para imprimir una etiqueta.');
            }
        }

        $config->update([
            'rec_box' => $newBoxNumber,
            'max_kg' => $maxKg,
            'last_kg' => $lastKg,
            'last_rep' => $lastRep
        ]);
    }
    private function printLabel($config, $uniqueBarcoder)
    {
        // Buscar la impresora en la base de datos (una sola vez)
        $printer = Printer::find($config->printer_id);

        if (!$printer) {
            // Manejo de caso donde la impresora no se encuentra
            error_log('Impresora no encontrada con el ID: ' . $config->printer_id);
            return; // Salir de la función si no hay impresora
        }

        if ($printer->type == 0) { // Impresión local (CUPS)
            $generator = new BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode($uniqueBarcoder, $generator::TYPE_CODE_128);

            // Convertir a Base64
            $base64Image = base64_encode($barcodeData);

            try {
                $printJob = Printing::newPrintTask()
                    ->printer($printer->name)
                    ->content($base64Image)
                    ->send();

                Log::info('Etiqueta impresa correctamente.');
            } catch (\Exception $e) {
                Log::error('Error al imprimir la etiqueta: ' . $e->getMessage());
                // Opcional: Mostrar mensaje de error al usuario
            }
        } else {
             // Impresión mediante API de Python
            $response = Http::post($printer->api_printer, [
                'barcode' => $uniqueBarcoder,
            ]);

            if ($response->failed()) {
                error_log('Error al llamar a la API de Python: ' . $response->body());
            }
        }
    }


    private function callExternalApi($apiQueue, $config, $newBoxNumber, $maxKg, $dimensionFinal, $uniqueBarcoder)
    {
        Log::info("Llamada a la API externa para el Modbus ID: {$config->id}");

        $dataToSend = [
            'token' => $apiQueue->token_back,
            'rec_box' => $newBoxNumber,
            'max_kg' => $maxKg,
            'last_dimension' => $dimensionFinal,
            'last_barcoder' => $uniqueBarcoder
        ];

        try {
            $response = Http::post($apiQueue->url_back, $dataToSend);

            if ($response->successful()) {
                Log::info("Respuesta exitosa de la API externa para el Modbus ID: {$config->id}", [
                    'response' => $response->json(),
                ]);
            } else {
                Log::error("Error en la respuesta de la API externa para el Modbus ID: {$config->id}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error al llamar a la API externa para el Modbus ID: {$config->id}", [
                'error' => $e->getMessage(),
            ]);
        }

        $apiQueue->used = true;
        $apiQueue->save();

        $apiQueue->delete();
    }


    private function publishMqttMessage($topic, $message)
    {
        $server = env('MQTT_SERVER');
        $port = intval(env('MQTT_PORT'));
        $this->mqttService->publishMessage($topic, $message, $server, $port);
    }
}
