<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sensor;
use App\Models\Modbus;
use Carbon\Carbon;
use App\Models\MonitorOee;
use Illuminate\Support\Facades\Log;
use App\Models\OrderStat;
use App\Models\Barcode;
use App\Models\SensorHistory;
use App\Models\ModbusHistory;
use App\Models\Operator;
use App\Models\RfidDetail;
use App\Models\ShiftHistory; 
use App\Models\OperatorPost;
use Illuminate\Support\Str;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreShiftEventRequest;

class ShiftProcessEventController extends Controller
{
 
    /**
     * @OA\Post(
     *     path="/api/shift-process-events",
     *     summary="Registrar evento de proceso de turno",
     *     description="Registra un evento de proceso de turno y actualiza sensores, modbus y contadores relacionados con la línea de producción.",
     *     tags={"Shifts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic","payload"},
     *             @OA\Property(property="topic", type="string", example="production/line/123/timeline_event"),
     *             @OA\Property(property="payload", type="object",
     *                 @OA\Property(property="type", type="string", enum={"shift","stop"}, example="shift"),
     *                 @OA\Property(property="action", type="string", enum={"start","end"}, example="start"),
     *                 @OA\Property(property="description", type="string", example="Manual"),
     *                 @OA\Property(property="operator_id", type="integer", example=1),
     *                 @OA\Property(property="shift_list_id", type="integer", example=3, nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Evento aceptado y procesado",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="accepted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Datos inválidos o error en la solicitud",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Datos inválidos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Topic desconocido o barcode no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Topic desconocido")
     *         )
     *     )
     * )
     */
    public function store(StoreShiftEventRequest $request): JsonResponse
    {
        ignore_user_abort(true);
    
        // 1) Extraemos todo lo validado de una vez
        $data = $request->validated();
    
        // 2) Asignamos directamente desde ese array
        $topic   = $data['topic'];    // ya validado como string
        $payload = $data['payload'];  // ya validado como array
    
        Log::info('['.now()."] Processing topic $topic");
    
        $response = $this->processMessage($topic, $payload);
        if ($response instanceof JsonResponse && $response->status() === 400) {
            return $response;
        }
    
        return response()->json(['status' => 'accepted'], 202);
    }

    private function processMessage(string $topic, array $data): ?JsonResponse
    {

        Log::info('[' . Carbon::now()->toDateTimeString() . '] Message decoded: ' . json_encode($data));
    
        // Buscar el modbus que coincide con el tópico (sin '/timeline_event')
        $baseTopic = str_replace('/timeline_event', '', $topic);

        $barcode = Barcode::where('mqtt_topic_barcodes', $baseTopic)->first();
        if (! $barcode) {
            Log::error("Barcode no encontrado para topic: {$baseTopic}");
            return response()->json(['error'=>'Topic desconocido'], 404);
        }
                // Obtener el production_line_id desde barcode
        $productionLineId = $barcode->production_line_id;

        $shiftListId = $data['shift_list_id'] ?? null; // Asegurarse de que exista la clave
        Log::info('[' . Carbon::now()->toDateTimeString() . '] shift_list_id: ' . $shiftListId);
        if ($shiftListId === null || empty($shiftListId)) {
            $shiftListId = null;
        }
        if ($shiftListId === 'especial') {
            $shiftListId = null;
        }
            
        



                // Insertar el registro en shift_history con los datos recibidos
                // Asegúrate de que el JSON incluya 'type', 'action' y 'description'
                try {
                    ShiftHistory::create([
                        'production_line_id' => $productionLineId,
                        'type'               => $data['type'] ?? null,
                        'action'             => $data['action'] ?? null,
                        'description'        => $data['description'] ?? null,
                        'operator_id'        => $data['operator_id'] ?? null,
                        'shift_list_id'        => $shiftListId ?? null,
                    ]);
                } catch (\Exception $e) {
                   Log::info("[". Carbon::now()->toDateTimeString() . "]Error al crear registro en shift_history: " . $e->getMessage());
                }
                

        if ($barcode) {
            Log::info("[". Carbon::now()->toDateTimeString() . "]Modbus found for topic: {$baseTopic}");

            // Obtener el production_line_id desde modbus
            $productionLineId = $barcode->production_line_id;

            // Obtener los sensores asociados a esta línea de producción
            $sensors = Sensor::where('production_line_id', $productionLineId)->get();

            // Procesar cada sensor encontrado pero ponemos si no hay sensores en la línea de producciónque ponga mesajes linea din sensores
            if ($sensors->isEmpty()) {
                Log::info("[". Carbon::now()->toDateTimeString() . "]No sensors found for production line ID {$productionLineId}");
                //pero reseteamos los contadores de usuarios y ponemos log 
               // $this->resetOperators();
            }else {
                // Procesar cada sensor encontrado
                foreach ($sensors as $sensor) {
                    Log::info("[". Carbon::now()->toDateTimeString() . "]Processing sensor ID {$sensor->id}");

                    try {
                        // Verificar si el JSON contiene 'type'
                        if (isset($data['type'])) {
                            $sensor->shift_type = $data['type'];
                            Log::info("[". Carbon::now()->toDateTimeString() . "]Shift type set to: {$data['type']}");
                        } else {
                            Log::error("Shift type missing in the message.");
                        }
                    
                        // Verificar si el JSON contiene 'action'
                        if (isset($data['action'])) {
                            $sensor->event = $data['action'];
                            Log::info("[". Carbon::now()->toDateTimeString() . "]Action set to: {$data['action']}");
                        } else {
                            Log::error("Action missing in the message.");
                        }
                    
                        // Guardar los cambios en el sensor
                        $sensor->save();
                    } catch (\Exception $e) {
                        // Manejo de la excepción
                        Log::error("[" . Carbon::now()->toDateTimeString() . "]Error al procesar el sensor: " . $e->getMessage());
                        // Si es necesario, se puede relanzar la excepción:
                        // throw $e;
                    }
                    

                    // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetSensorCounters($sensor);
                        $this->resetOperators();
                        //$this->changeOrderStatus($sensor->production_line_id);
                        $this->sendMqttTo0($sensor);
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Sensor ID {$sensor->id} updated with type, action, and counters reset.");
                    } else {
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Sensor ID {$sensor->id} updated with type and action. No need to reset counters.");
                    }
                }
            }

            // Si el type se ha puesto shift y action es start, se cambia datatime de OEE
            if ($data['type'] == 'shift' && $data['action'] == 'start') {
                //$this->changeOrderStatus($productionLineId);
                //Log::info("[". Carbon::now()->toDateTimeString() . "]Cambios en ordeStatus para la linea de produccion: {$productionLineId}");
                $this->changeDataTimeOee($productionLineId);
                Log::info("[". Carbon::now()->toDateTimeString() . "]Cambios en OEE para la linea de produccion: {$productionLineId}");
            } else {
                Log::info("[". Carbon::now()->toDateTimeString() . "]Cambios NO realizados en ordeStatus y OEE. Para la linea de produccion: {$productionLineId}");
            }

             // Obtener los modbus asociados a esta línea de producción
             $mosbuses = Modbus::where('production_line_id', $productionLineId)->get();
            //ponemos filtro si no hay mosbuses que se ponga un log 
            if ($mosbuses->isEmpty()) {
                Log::info("[". Carbon::now()->toDateTimeString() . "]No modbus found for production line ID: {$productionLineId}");
                //pero reseteamos los contadores de usuarios y ponemos log 
                //$this->resetOperators();
            } else {
                // Procesar cada modbus asociado a esta línea de producción
                foreach ($mosbuses as $modbus) {
                    Log::info("[". Carbon::now()->toDateTimeString() . "]Processing sensor ID {$modbus->id}");

                    // Verificar si el JSON contiene type y action
                    if (isset($data['type'])) {
                        $modbus->shift_type = $data['type'];
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Shift type set to: {$data['type']}");
                    } else {
                        Log::error("Shift type missing in the message.");
                    }

                    if (isset($data['action'])) {
                        $modbus->event = $data['action'];
                        Log::info("[". Carbon::now()->toDateTimeString() . "]action set to: {$data['action']}");
                    } else {
                        Log::error("action missing in the message.");
                    }

                    // Guardar los cambios en el sensor
                    $modbus->save();
                    
                        // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetModbusCounters($modbus);
                        $this->resetOperators();
                        //$this->sendMqttTo0($modbus);
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Modbus ID {$modbus->id} updated with type, action, and counters reset.");
                    } else {
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Modbus ID {$modbus->id} updated with type and action. No need to reset counters.");
                    }
                }
            }


            // Procesar registros en la tabla rfid_details asociados a la línea de producción
            $rfidDetails = RfidDetail::where('production_line_id', $productionLineId)->get();

            if ($rfidDetails->isEmpty()) {
                Log::info("[". Carbon::now()->toDateTimeString() . "]No se encontraron registros en rfid_details para production line ID: {$productionLineId}");
                // Opcional: reiniciar contadores de operadores o realizar otra acción
               // $this->resetOperators();
            } else {
                Log::info("[". Carbon::now()->toDateTimeString() . "]Se encontraron registros en rfid_details para production line ID: {$productionLineId}");
                foreach ($rfidDetails as $rfidDetail) {
                    Log::info("[". Carbon::now()->toDateTimeString() . "]Procesando rfid detail ID {$rfidDetail->id}");

                    // Verificar y asignar shift_type
                    if (isset($data['type'])) {
                        $rfidDetail->shift_type = $data['type'];
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Shift type establecido en: {$data['type']}");
                    } else {
                        Log::error("Falta el shift type en el mensaje.");
                    }

                    // Verificar y asignar event
                    if (isset($data['action'])) {
                        $rfidDetail->event = $data['action'];
                        Log::info("[". Carbon::now()->toDateTimeString() . "]Action establecido en: {$data['action']}");
                    } else {
                        Log::error("Falta la action en el mensaje.");
                    }
                    
                    // Si el type se ha puesto shift y action es start, se resetean los contadores
                    if ($data['type'] == 'shift' && $data['action'] == 'start') {
                        $this->resetRfidDetail($rfidDetail);
                        $this->resetOperators();
                        //$this->sendMqttTo0($modbus);
                        Log::info("[". Carbon::now()->toDateTimeString() . "]rfindDetail ID {$rfidDetail->id} updated with type, action, and counters reset.");
                    } else {
                        Log::info("[". Carbon::now()->toDateTimeString() . "]rfindDetail ID {$rfidDetail->id} updated with type and action. No need to reset counters.");
                    }


                    

                    // Guardar los cambios
                    $rfidDetail->save();

                    Log::info("[". Carbon::now()->toDateTimeString() . "]RFID Detail ID {$rfidDetail->id} actualizado: shift_type, event y contadores reiniciados.");
                }
            }


        } else {
            Log::error("[" . Carbon::now()->toDateTimeString() . "]Barcoder not found for topic: {$baseTopic}");
        }
        
        try {
            if ($data['type'] == 'shift' && $data['action'] == 'end') {

                $this->sendFinishShiftEmails();
                Log::info("[". Carbon::now()->toDateTimeString() . "]Email enviado con exito.");
            }
        } catch (\Exception $e) {
            Log::error("[" . Carbon::now()->toDateTimeString() . "]Error envio email: " . $e->getMessage());
        }
        return null;
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
        Log::info("[" . Carbon::now()->toDateTimeString() . "] Resetting counters for sensor ID {$sensor->id}.");
    
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
                'optimal_production_time' => $sensor->optimal_production_time,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al guardar en sensor_history para sensor ID {$sensor->id}: " . $e->getMessage());
        }
    
        // Intentar resetear los contadores del sensor
        try {
            $sensor->count_shift_1 = 0;
            $sensor->count_shift_0 = 0;
            $sensor->downtime_count = 0;
            $sensor->unic_code_order = uniqid(); // Generar un nuevo código único para el pedido
            $sensor->save();
        } catch (\Exception $e) {
            Log::error("Error al resetear contadores para sensor ID {$sensor->id}: " . $e->getMessage());
        }
    }
    
    
    private function resetModbusCounters($modbus)
    {
        Log::info("[". Carbon::now()->toDateTimeString() . "]Resetting modbus counters for modbus ID {$modbus->id}.");
    
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
            'optimal_production_time' => $modbus->optimal_production_time,
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
            Log::info("[". Carbon::now()->toDateTimeString() . "]Actualizando hora de OEE para la línea {$production_line_id}.");

            // Obtener todos los registros de MonitorOee relacionados con la línea de producción
            $oees = MonitorOee::where('production_line_id', $production_line_id)->get(); // Cargar los modelos

            if ($oees->isNotEmpty()) {
                foreach ($oees as $oee) {
                    $oee->time_start_shift = Carbon::now();
                    $oee->save();  // Esto disparará el evento 'updating'
                }

                Log::info("[". Carbon::now()->toDateTimeString() . "]Hora de inicio del turno actualizada para todos los monitores en la línea de producción {$production_line_id}.");
            } else {
                Log::error("No se encontraron monitores para la línea de producción {$production_line_id}.");
            }


        } catch (\Exception $e) {
            // Capturar cualquier excepción y mostrar un mensaje de error
            Log::error("[" . Carbon::now()->toDateTimeString() . "]Ocurrió un error al actualizar la hora de OEE para la línea {$production_line_id}: " . $e->getMessage());
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
        Log::info("[". Carbon::now()->toDateTimeString() . "]Resetting mqtt Counter to 0 for sensor ID {$sensor->id}.");
        $this->publishMqttMessage($topicWaitTime, $processedMessage);
        Log::info("[". Carbon::now()->toDateTimeString() . "]Resetting mqtt counters waiTime to 0 for sensor ID {$sensor->id}.");
        $this->publishMqttMessage($topicWaitTime2, $processedMessage);
        Log::info("[". Carbon::now()->toDateTimeString() . "]Resetting mqtt counters waiTime to 0 for sensor ID {$sensor->id}.");
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
    
            Log::info("[". Carbon::now()->toDateTimeString() . "]Nueva entrada creada en order_stats para la línea de producción: {$productionLineId}");
        } else {
            Log::info("[". Carbon::now()->toDateTimeString() . "]No se creó nueva entrada en order_stats ya que el último registro es reciente para la línea de producción: {$productionLineId}");
        }
    }

    public function sendFinishShiftEmails()
    {
        // 1. Leemos y explodemos las dos listas
        $raw1  = trim(env('EMAIL_FINISH_SHIFT_LISTWORKERS', ''));
        $raw2  = trim(env('EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED', ''));
        $list1 = array_filter(array_map('trim', explode(',', $raw1)));
        $list2 = array_filter(array_map('trim', explode(',', $raw2)));
    
        // 2. Si ambas listas están vacías, no hacemos nada aquí
        if (empty($list1) && empty($list2)) {
            Log::info('sendFinishShiftEmails: No hay correos configurados en .env, abortando envío pero permitiendo continuar la ejecución externa.');
            return; // salimos de este método, pero el código que lo llamó sigue
        }
    
        // 3. Base URL limpia
        $appUrl = rtrim(env('LOCAL_SERVER'), '/');
    
        // 4. Configuramos los jobs
        $jobs = [
            [
                'emails'   => $list1,
                'endpoint' => $appUrl . '/api/workers-export/send-email',
                'log_key'  => 'report',
            ],
            [
                'emails'   => $list2,
                'endpoint' => $appUrl . '/api/workers-export/send-assignment-list',
                'log_key'  => 'assignment',
            ],
        ];
    
        // 5. Cliente Guzzle
        $client = new \GuzzleHttp\Client([
            'timeout'         => 5.0,   // 5 segundos de respuesta total
            'connect_timeout' => 2.0,   // 2 segundos de conexión
            'http_errors' => false,
            'verify'      => false,
        ]);
    
        // 6. Envío asíncrono
        foreach ($jobs as $job) {
            foreach ($job['emails'] as $email) {
                $url = $job['endpoint'] . '?email=' . urlencode($email);
                $promise = $client->getAsync($url);
    
                $promise->then(
                    function ($response) use ($url, $job) {
                        \Log::info(sprintf(
                            "[%s][%s] GET %s → %d",
                            Carbon::now()->toDateTimeString(),
                            $job['log_key'],
                            $url,
                            $response->getStatusCode()
                        ));
                    },
                    function ($e) use ($url, $job) {
                        \Log::error(sprintf(
                            "[%s][%s] Error GET %s: %s",
                            Carbon::now()->toDateTimeString(),
                            $job['log_key'],
                            $url,
                            $e->getMessage()
                        ));
                    }
                );
    
                $promise->wait(false);
            }
        }
    }
    
    private function publishMqttMessage($topic, $message)
    {

        try {
            // Preparar los datos a almacenar, agregando la fecha y hora
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];
        
            // Convertir a JSON
            $jsonData = json_encode($data);
        
            // Sanitizar el topic para evitar creación de subcarpetas
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generar un identificador único (por ejemplo, usando microtime)
            $uniqueId = round(microtime(true) * 1000); // milisegundos
        
            // Guardar en servidor 1
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            file_put_contents($fileName1, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Guardar en servidor 2
            //$fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            //if (!file_exists(dirname($fileName2))) {
            //    mkdir(dirname($fileName2), 0755, true);
            //}
            //file_put_contents($fileName2, $jsonData . PHP_EOL);
           // Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}
