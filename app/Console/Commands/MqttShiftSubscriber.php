<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Carbon\Carbon;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\MonitorOee;
use Illuminate\Support\Facades\Log;
use App\Models\OrderStat;
use App\Models\Barcode;
use App\Models\SensorHistory;
use App\Models\ModbusHistory;
use App\Models\Operator;
use App\Models\RfidDetail;
use App\Models\ShiftHistory; // Importa el modelo ShiftControl
use App\Models\OperatorPost;

class MqttShiftSubscriber extends Command
{
    protected $signature = 'mqtt:shiftsubscribe';
    protected $description = 'Subscribe to MQTT topics and update shift control information from sensors';

    protected $subscribedTopics = [];
    protected $shouldContinue = true;

    public function handle()
    {
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldContinue = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldContinue = false;
        });
    
        $this->shouldContinue = true;
        $retryDelay = 1; // Tiempo de espera inicial en segundos
    
        while ($this->shouldContinue) {
            try {
                $this->info("[". Carbon::now()->toDateTimeString() . "]Intentando conectar con MQTT...");
    
                // Inicializar el cliente MQTT
                $mqtt = $this->initializeMqttClient(env('MQTT_SENSORICA_SERVER'), intval(env('MQTT_SENSORICA_PORT')));
                // 🔹 Limpiar la lista de tópicos suscritos después de reconectar
                $this->subscribedTopics = [];
                // Suscribirse a los tópicos actuales
                $this->subscribeToAllTopics($mqtt);
    
                // Resetear el tiempo de espera después de una conexión exitosa
                $retryDelay = 1;
    
                // Bucle principal para procesar los mensajes MQTT
                while ($this->shouldContinue) {
                    $this->checkAndSubscribeNewTopics($mqtt);
                    $mqtt->loop(true);
    
                    // Manejo de señales para cierre seguro
                    pcntl_signal_dispatch();
    
                    // Reducir la carga del sistema
                    usleep(100000);
                }
    
                $mqtt->disconnect();
                $this->info("[". Carbon::now()->toDateTimeString() . "]MQTT Subscriber detenido correctamente.");
    
            } catch (\Exception $e) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "]Error en la conexión MQTT: " . $e->getMessage());
    
                // Espera antes de reintentar (con aumento progresivo hasta un máximo de 30s)
                sleep($retryDelay);
                $retryDelay = min($retryDelay * 2, 30); // Incremento progresivo hasta 30s máximo
    
                $this->warn("Intentando reconectar en {$retryDelay} segundos...");
            }
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

    private function subscribeToAllTopics(MqttClient $mqtt)
    {
        // Obtener los tópicos desde la tabla BArcode
        $topics = Barcode::pluck('mqtt_topic_barcodes')->toArray();

        foreach ($topics as $topic) {
            $topicWithShift = "{$topic}/timeline_event"; // Añadir '/timeline_event' al tópico

            if (!in_array($topicWithShift, $this->subscribedTopics)) {
                $mqtt->subscribe($topicWithShift, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topicWithShift;
                $this->info("[". Carbon::now()->toDateTimeString() . "]Subscribed to topic: {$topicWithShift}");
            }
        }

        $this->info('Subscribed to initial topics.');
    }

    private function checkAndSubscribeNewTopics(MqttClient $mqtt)
    {
        // Obtener tópicos actuales desde Barcode
        $currentTopics = Barcode::pluck('mqtt_topic_barcodes')->toArray();

        // Comparar con los tópicos a los que ya estamos suscritos
        foreach ($currentTopics as $topic) {
            $topicWithShift = "{$topic}/timeline_event"; // Añadir '/timeline_event' al tópico

            if (!in_array($topicWithShift, $this->subscribedTopics)) {
                // Suscribirse al nuevo tópico
                $mqtt->subscribe($topicWithShift, function ($topic, $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $this->subscribedTopics[] = $topicWithShift;
                $this->info("[". Carbon::now()->toDateTimeString() . "]Subscribed to new topic: {$topicWithShift}");
            }
        }
    }

    private function processMessage($topic, $message)
    {
        $this->info("[". Carbon::now()->toDateTimeString() . "]Processing message for topic: {$topic}");

        // Decodificar el mensaje JSON
        $data = json_decode($message, true);

        // Verificar si el JSON es válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Failed to decode JSON: " . json_last_error_msg());
            return;
        }

        $this->info('[' . Carbon::now()->toDateTimeString() . '] Message decoded: ' . json_encode($data));

        // Buscar el modbus que coincide con el tópico (sin '/timeline_event')
        $baseTopic = str_replace('/timeline_event', '', $topic);

        $barcode = Barcode::where('mqtt_topic_barcodes', $baseTopic)->first();

                // Obtener el production_line_id desde barcode
                $productionLineId = $barcode->production_line_id;

                // Insertar el registro en shift_history con los datos recibidos
                // Asegúrate de que el JSON incluya 'type', 'action' y 'description'
                try {
                    ShiftHistory::create([
                        'production_line_id' => $productionLineId,
                        'type'               => $data['type'] ?? null,
                        'action'             => $data['action'] ?? null,
                        'description'        => $data['description'] ?? null,
                    ]);
                } catch (\Exception $e) {
                   $this->info("[". Carbon::now()->toDateTimeString() . "]Error al crear registro en shift_history: " . $e->getMessage());
                }
                

        if ($barcode) {
            $this->info("[". Carbon::now()->toDateTimeString() . "]Modbus found for topic: {$baseTopic}");

            // Obtener el production_line_id desde modbus
            $productionLineId = $barcode->production_line_id;

            // Obtener los sensores asociados a esta línea de producción
            $sensors = Sensor::where('production_line_id', $productionLineId)->get();

            // Procesar cada sensor encontrado pero ponemos si no hay sensores en la línea de producciónque ponga mesajes linea din sensores
            if ($sensors->isEmpty()) {
                $this->info("[". Carbon::now()->toDateTimeString() . "]No sensors found for production line ID {$productionLineId}");
                //pero reseteamos los contadores de usuarios y ponemos log 
                $this->resetOperators();
            }else {
                // Procesar cada sensor encontrado
                foreach ($sensors as $sensor) {
                    $this->info("[". Carbon::now()->toDateTimeString() . "]Processing sensor ID {$sensor->id}");

                    try {
                        // Verificar si el JSON contiene 'type'
                        if (isset($data['type'])) {
                            $sensor->shift_type = $data['type'];
                            $this->info("[". Carbon::now()->toDateTimeString() . "]Shift type set to: {$data['type']}");
                        } else {
                            $this->warn("Shift type missing in the message.");
                        }
                    
                        // Verificar si el JSON contiene 'action'
                        if (isset($data['action'])) {
                            $sensor->event = $data['action'];
                            $this->info("[". Carbon::now()->toDateTimeString() . "]Action set to: {$data['action']}");
                        } else {
                            $this->warn("Action missing in the message.");
                        }
                    
                        // Guardar los cambios en el sensor
                        $sensor->save();
                    } catch (\Exception $e) {
                        // Manejo de la excepción
                        $this->error("[" . Carbon::now()->toDateTimeString() . "]Error al procesar el sensor: " . $e->getMessage());
                        // Si es necesario, se puede relanzar la excepción:
                        // throw $e;
                    }
                    

                    // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetSensorCounters($sensor);
                        $this->resetOperators();
                        //$this->changeOrderStatus($sensor->production_line_id);
                        $this->sendMqttTo0($sensor);
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Sensor ID {$sensor->id} updated with type, action, and counters reset.");
                    } else {
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Sensor ID {$sensor->id} updated with type and action. No need to reset counters.");
                    }
                }
            }

            // Si el type se ha puesto shift y action es start, se cambia datatime de OEE
            if ($data['type'] == 'shift' && $data['action'] == 'start') {
                //$this->changeOrderStatus($productionLineId);
                //$this->info("[". Carbon::now()->toDateTimeString() . "]Cambios en ordeStatus para la linea de produccion: {$productionLineId}");
                $this->changeDataTimeOee($productionLineId);
                $this->info("[". Carbon::now()->toDateTimeString() . "]Cambios en OEE para la linea de produccion: {$productionLineId}");
            } else {
                $this->info("[". Carbon::now()->toDateTimeString() . "]Cambios NO realizados en ordeStatus y OEE. Para la linea de produccion: {$productionLineId}");
            }

             // Obtener los modbus asociados a esta línea de producción
             $mosbuses = Modbus::where('production_line_id', $productionLineId)->get();
            //ponemos filtro si no hay mosbuses que se ponga un log 
            if ($mosbuses->isEmpty()) {
                $this->info("[". Carbon::now()->toDateTimeString() . "]No modbus found for production line ID: {$productionLineId}");
                //pero reseteamos los contadores de usuarios y ponemos log 
                $this->resetOperators();
            } else {
                // Procesar cada modbus asociado a esta línea de producción
                foreach ($mosbuses as $modbus) {
                    $this->info("[". Carbon::now()->toDateTimeString() . "]Processing sensor ID {$modbus->id}");

                    // Verificar si el JSON contiene type y action
                    if (isset($data['type'])) {
                        $modbus->shift_type = $data['type'];
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Shift type set to: {$data['type']}");
                    } else {
                        $this->warn("Shift type missing in the message.");
                    }

                    if (isset($data['action'])) {
                        $modbus->event = $data['action'];
                        $this->info("[". Carbon::now()->toDateTimeString() . "]action set to: {$data['action']}");
                    } else {
                        $this->warn("action missing in the message.");
                    }

                    // Guardar los cambios en el sensor
                    $modbus->save();
                    
                        // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetModbusCounters($modbus);
                        $this->resetOperators();
                        //$this->sendMqttTo0($modbus);
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Modbus ID {$modbus->id} updated with type, action, and counters reset.");
                    } else {
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Modbus ID {$modbus->id} updated with type and action. No need to reset counters.");
                    }
                }
            }

                         // Obtener los modbus asociados a esta línea de producción
             $mosbuses = Modbus::where('production_line_id', $productionLineId)->get();
            //ponemos filtro si no hay mosbuses que se ponga un log 
            if ($mosbuses->isEmpty()) {
                $this->info("[". Carbon::now()->toDateTimeString() . "]No modbus found for production line ID: {$productionLineId}");
                //pero reseteamos los contadores de usuarios y ponemos log 
                $this->resetOperators();
            } else {
                // Procesar cada modbus asociado a esta línea de producción
                foreach ($mosbuses as $modbus) {
                    $this->info("[". Carbon::now()->toDateTimeString() . "]Processing sensor ID {$modbus->id}");

                    // Verificar si el JSON contiene type y action
                    if (isset($data['type'])) {
                        $modbus->shift_type = $data['type'];
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Shift type set to: {$data['type']}");
                    } else {
                        $this->warn("Shift type missing in the message.");
                    }

                    if (isset($data['action'])) {
                        $modbus->event = $data['action'];
                        $this->info("[". Carbon::now()->toDateTimeString() . "]action set to: {$data['action']}");
                    } else {
                        $this->warn("action missing in the message.");
                    }

                    // Guardar los cambios en el sensor
                    $modbus->save();
                    
                        // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetModbusCounters($modbus);
                        $this->resetOperators();
                        //$this->sendMqttTo0($modbus);
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Modbus ID {$modbus->id} updated with type, action, and counters reset.");
                    } else {
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Modbus ID {$modbus->id} updated with type and action. No need to reset counters.");
                    }
                }
            }

            // Procesar registros en la tabla rfid_details asociados a la línea de producción
            $rfidDetails = RfidDetail::where('production_line_id', $productionLineId)->get();

            if ($rfidDetails->isEmpty()) {
                $this->info("[". Carbon::now()->toDateTimeString() . "]No se encontraron registros en rfid_details para production line ID: {$productionLineId}");
                // Opcional: reiniciar contadores de operadores o realizar otra acción
                $this->resetOperators();
            } else {
                $this->info("[". Carbon::now()->toDateTimeString() . "]Se encontraron registros en rfid_details para production line ID: {$productionLineId}");
                foreach ($rfidDetails as $rfidDetail) {
                    $this->info("[". Carbon::now()->toDateTimeString() . "]Procesando rfid detail ID {$rfidDetail->id}");

                    // Verificar y asignar shift_type
                    if (isset($data['type'])) {
                        $rfidDetail->shift_type = $data['type'];
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Shift type establecido en: {$data['type']}");
                    } else {
                        $this->warn("Falta el shift type en el mensaje.");
                    }

                    // Verificar y asignar event
                    if (isset($data['action'])) {
                        $rfidDetail->event = $data['action'];
                        $this->info("[". Carbon::now()->toDateTimeString() . "]Action establecido en: {$data['action']}");
                    } else {
                        $this->warn("Falta la action en el mensaje.");
                    }
                    
                    // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetRfidDetail($rfidDetail);
                        $this->resetOperators();
                        //$this->sendMqttTo0($modbus);
                        $this->info("[". Carbon::now()->toDateTimeString() . "]rfindDetail ID {$rfidDetail->id} updated with type, action, and counters reset.");
                    } else {
                        $this->info("[". Carbon::now()->toDateTimeString() . "]rfindDetail ID {$rfidDetail->id} updated with type and action. No need to reset counters.");
                    }


                    

                    // Guardar los cambios
                    $rfidDetail->save();

                    $this->info("[". Carbon::now()->toDateTimeString() . "]RFID Detail ID {$rfidDetail->id} actualizado: shift_type, event y contadores reiniciados.");
                }
            }


        } else {
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Barcoder not found for topic: {$baseTopic}");
        }
    }

    //creamos resetRfidDetail($rfidDetail)
    private function resetRfidDetail($rfidDetail)
    {
        // Reiniciar los contadores
        $rfidDetail->count_shift_1 = 0;
        $rfidDetail->count_shift_0 = 0;
        $rfidDetail->downtime_count = 0;
        $rfidDetail->unic_code_order = uniqid(); // Generar un nuevo código único para el pedido
        // Guardar los cambios
        $rfidDetail->save();
    }

    // Función para resetear los contadores del sensor
    private function resetSensorCounters($sensor)
    {
        $this->info("[" . Carbon::now()->toDateTimeString() . "] Resetting counters for sensor ID {$sensor->id}.");
    
        // Intentar guardar la información actual del sensor en la tabla `sensor_history`
        try {
            SensorHistory::create([
                'sensor_id' => $sensor->id,
                'count_shift_1' => $sensor->count_shift_1,
                'count_shift_0' => $sensor->count_shift_0,
                'count_order_0' => $sensor->count_order_0,
                'count_order_1' => $sensor->count_order_1,
                'downtime_count' => $sensor->downtime_count,
                'unic_code_order' => $sensor->unic_code_order,
                'orderId' => $sensor->orderId,
            ]);
        } catch (\Exception $e) {
            $this->error("Error al guardar en sensor_history para sensor ID {$sensor->id}: " . $e->getMessage());
        }
    
        // Intentar resetear los contadores del sensor
        try {
            $sensor->count_shift_1 = 0;
            $sensor->count_shift_0 = 0;
            $sensor->downtime_count = 0;
            $sensor->unic_code_order = uniqid(); // Generar un nuevo código único para el pedido
            $sensor->save();
        } catch (\Exception $e) {
            $this->error("Error al resetear contadores para sensor ID {$sensor->id}: " . $e->getMessage());
        }
    }
    
    
    private function resetModbusCounters($modbus)
    {
        $this->info("[". Carbon::now()->toDateTimeString() . "]Resetting modbus counters for modbus ID {$modbus->id}.");
    
        // Guardar la información actual del modbus en la tabla `modbus_history`
        ModbusHistory::create([
            'modbus_id' => $modbus->id,
            'orderId' => $modbus->orderId,
            'rec_box_shift' => $modbus->rec_box_shift,
            'rec_box' => $modbus->rec_box,
            'downtime_count' => $modbus->downtime_count,
            'unic_code_order' => $modbus->unic_code_order,
            'total_kg_order' => $modbus->total_kg_order,
            'total_kg_shift' => $modbus->total_kg_shift,
        ]);
    
        // Reseteo de los contadores del modbus
        $modbus->rec_box_shift = 0;
       // $modbus->rec_box = 0;
        $modbus->downtime_count = 0;
        $modbus->unic_code_order = uniqid();
       // $modbus->total_kg_order = 0;
        $modbus->total_kg_shift = 0;
    
        // Guardar los cambios en el modbus
        $modbus->save();
    }

    public function resetOperators()
    {
        try {
            // Reseteamos todos los operadores a 0
            Operator::query()->update([
                'count_shift' => 0,
            ]);
    
            // Log para confirmar la operación
            Log::info("Todos los contadores de operadores han sido reseteados a 0.");
    
            // Buscar todos los operadores
            $operators = Operator::all();
    
            // Iterar sobre cada operador para procesar su correspondiente OperatorPost
            foreach ($operators as $operator) {
                // Buscar el registro OperatorPost correspondiente a este operador
                $operatorPost = OperatorPost::where('operator_id', $operator->id)->first();
    
                if ($operatorPost) {
                    // Verificar si el registro fue creado en los últimos 10 segundos
                    $timeDifference = Carbon::now()->diffInSeconds($operatorPost->created_at);
    
                    if ($timeDifference > 10) {
                        // Actualizar finish_at de la entrada encontrada
                        $operatorPost->update(['finish_at' => Carbon::now()]);
    
                        // Duplicar la entrada: copiamos los datos, eliminamos el id para crear un nuevo registro,
                        // dejamos finish_at en null y reiniciamos count a 0.
                        $newData = $operatorPost->toArray();
                        unset($newData['id']); // Aseguramos que se genere un nuevo ID.
                        $newData['finish_at'] = null;
                        $newData['count'] = 0;
    
                        // Añadimos el ID de la nueva asignación para este RFID
                        // Asegúrate de tener los valores correctos para estos campos
                        $newData['product_list_selected_id'] = $operatorPost->id;
                        $newData['product_list_id'] = $operatorPost->id;
    
                        // Crear el nuevo registro duplicado
                        OperatorPost::create($newData);
    
                        // Log para confirmar la duplicación
                        Log::info("Entrada duplicada para el operador {$operator->id} con nuevo producto.");
                    } else {
                        // Log para indicar que se ignoró la duplicación por el tiempo de creación
                        Log::info("Se ignoró la duplicación para el operador {$operator->id} debido a que la entrada fue creada hace menos de 10 segundos.");
                    }
                }
            }
    
            return response()->json([
                'message' => 'Todos los contadores de operadores han sido reseteados a 0.',
                'status' => 'success'
            ], 200);
    
        } catch (\Exception $e) {
            // Log del error en caso de fallo
            Log::error("Error al resetear los contadores de operadores: " . $e->getMessage());

        }
    }
    
    

    private function changeDataTimeOee($production_line_id)
    {
        try {
            // Información sobre la actualización de la hora de inicio de OEE
            $this->info("[". Carbon::now()->toDateTimeString() . "]Actualizando hora de OEE para la línea {$production_line_id}.");

            // Obtener todos los registros de MonitorOee relacionados con la línea de producción
            $oees = MonitorOee::where('production_line_id', $production_line_id)->get(); // Cargar los modelos

            if ($oees->isNotEmpty()) {
                foreach ($oees as $oee) {
                    $oee->time_start_shift = Carbon::now();
                    $oee->save();  // Esto disparará el evento 'updating'
                }

                $this->info("[". Carbon::now()->toDateTimeString() . "]Hora de inicio del turno actualizada para todos los monitores en la línea de producción {$production_line_id}.");
            } else {
                $this->warn("No se encontraron monitores para la línea de producción {$production_line_id}.");
            }


        } catch (\Exception $e) {
            // Capturar cualquier excepción y mostrar un mensaje de error
            $this->error("[" . Carbon::now()->toDateTimeString() . "]Ocurrió un error al actualizar la hora de OEE para la línea {$production_line_id}: " . $e->getMessage());
        }
    }

    private function sendMqttTo0($sensor){
        // Json enviar a MQTT conteo por orderId
        $processedMessage = json_encode([
            'value' => 0,
            'status' => 2
        ]);

        // Publicar el mensaje a través de MQTT
        $topic=$sensor->mqtt_topic_1 ;

        // Eliminar la parte '/mac/...' del tópico
        $topicWithoutMac = preg_replace('/\/mac\/[^\/]+/', '', $topic);

        // Añadir '/waitTime' al final del tópico
        $topicWaitTime = $topicWithoutMac . '/waitTime';
        $topicWaitTime2 = $topic . '/waitTime';
        $this->publishMqttMessage($topic, $processedMessage);
        $this->info("[". Carbon::now()->toDateTimeString() . "]Resetting mqtt Counter to 0 for sensor ID {$sensor->id}.");
        $this->publishMqttMessage($topicWaitTime, $processedMessage);
        $this->info("[". Carbon::now()->toDateTimeString() . "]Resetting mqtt counters waiTime to 0 for sensor ID {$sensor->id}.");
        $this->publishMqttMessage($topicWaitTime2, $processedMessage);
        $this->info("[". Carbon::now()->toDateTimeString() . "]Resetting mqtt counters waiTime to 0 for sensor ID {$sensor->id}.");
    }

    private function changeOrderStatus($productionLineId)
    {
        // Buscar la última entrada en 'order_stats' con el mismo 'production_line_id'
        $lastOrderStat = OrderStat::where('production_line_id', $productionLineId)->latest()->first();
        
        $createNewEntry = false; // Indicador para saber si necesitamos crear una nueva entrada
    
        if ($lastOrderStat) {
            // Verificar si el registro se creó hace más de 1 minuto
            $createdAt = Carbon::parse($lastOrderStat->created_at);
            $oneMinuteAgo = Carbon::now()->subMinute();
    
            // Si el registro tiene más de 1 minuto de antigüedad, establecemos que se debe crear una nueva entrada
            if ($createdAt->lessThan($oneMinuteAgo)) {
                $createNewEntry = true;
            }
        } else {
            // Si no hay ningún registro anterior, necesitamos crear una nueva entrada
            $createNewEntry = true;
        }
    
        // Si se determina que se debe crear una nueva entrada
        if ($createNewEntry) {
            // Calcular las unidades restantes (units - units_made_real)
            $unitsRemaining = $lastOrderStat ? $lastOrderStat->units - $lastOrderStat->units_made_real : 0;
    
            // Crear una nueva línea con los campos necesarios y los demás vacíos o en 0
            OrderStat::create([
                'production_line_id' => $productionLineId,
                'product_list_id'   => $lastOrderStat ? $lastOrderStat->product_list_id : null,
                'order_id' => $lastOrderStat ? $lastOrderStat->order_id : null,
                'box' => $lastOrderStat ? $lastOrderStat->box : null,
                'units_box' => $lastOrderStat ? $lastOrderStat->units_box : null,
                'units' => $unitsRemaining,  // Aquí asignamos lo que queda por fabricar
                'units_per_minute_real' => null,  // Dejar estos campos vacíos o nulos
                'units_per_minute_theoretical' => null,
                'seconds_per_unit_real' => null,
                'seconds_per_unit_theoretical' => null,
                'units_made_real' => 0,
                'units_made_theoretical' => 0,
                'sensor_stops_count' => 0,
                'sensor_stops_active' => 0,
                'sensor_stops_time' => 0,
                'production_stops_time' => 0,
                'units_made' => 0,
                'units_pending' => 0,
                'units_delayed' => 0,
                'slow_time' => 0,
                'oee' => null,  // Dejar vacío o nulo
            ]);
    
            $this->info("[". Carbon::now()->toDateTimeString() . "]Nueva entrada creada en order_stats para la línea de producción: {$productionLineId}");
        } else {
            $this->info("[". Carbon::now()->toDateTimeString() . "]No se creó nueva entrada en order_stats ya que el último registro es reciente para la línea de producción: {$productionLineId}");
        }
    }
    private function publishMqttMessage($topic, $message)
    {
       try {
        // Inserta en la tabla mqtt_send_server1
        MqttSendServer1::createRecord($topic, $message);

        // Inserta en la tabla mqtt_send_server2
        MqttSendServer2::createRecord($topic, $message);

        $this->info("[". Carbon::now()->toDateTimeString() . "]Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");

        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }
}
