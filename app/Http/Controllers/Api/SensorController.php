<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Log;
use App\Models\Barcode;
use App\Models\SensorCount;
use App\Models\OperatorPost;
use App\Models\Operator;
use App\Models\OrderStat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ShiftHistory; // Asegúrate de que la ruta del modelo sea la correcta
 
class SensorController extends Controller
{
        /**
     * @OA\Get(
     *     path="/api/sensors/{token}",
     *     summary="Obtener datos del sensor por token",
     *     tags={"Sensores"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token del sensor",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos del sensor obtenidos correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Sensor 1"),
     *             @OA\Property(property="sensor_type", type="integer", example=0),
     *             @OA\Property(property="mqtt_topic_sensor", type="string", example="sensor/topic/1"),
     *             @OA\Property(property="count_total", type="integer", example=100),
     *             @OA\Property(property="token", type="string", example="abc123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sensor no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Sensor not found")
     *         )
     *     )
     * )
     */
    public function getByToken($token)
    {
        // Buscar el sensor por su token
        $sensor = Sensor::where('token', $token)->first();

        // Verificar si el sensor existe
        if (!$sensor) {
            return response()->json([
                'error' => 'Sensor not found'
            ], 404);
        }

        // Buscar el último registro de sensor_counts para este sensor
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)->latest()->first();

        // Obtener el valor 'value', por defecto 0 si no se encuentra registro
        $value = $sensorCount ? $sensorCount->value : 0;

        // Devolver los datos del sensor en formato JSON, incluyendo el nuevo campo 'value'
        $sensorData = $sensor->toArray();
        $sensorData['value'] = $value;

        return response()->json($sensorData);
    }
    /**
     * @OA\Get(
     *     path="/api/sensors",
     *     summary="Obtener todos los sensores",
     *     description="Obtiene una lista de todos los sensores, agrupados por línea de producción.",
     *     tags={"Sensores"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de sensores",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Sensor 1"),
     *                 @OA\Property(property="value", type="integer", example=0),
     *                 @OA\Property(property="production_line", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Línea 1")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAllSensors()
    {
        // Obtener todos los sensores, cargando la relación con la línea de producción
        $sensors = Sensor::with('productionLine')->get();

        // Iterar sobre cada sensor para obtener el último valor de sensor_counts
        $sensorsData = $sensors->map(function ($sensor) {
            // Buscar el último registro de sensor_counts para este sensor
            $sensorCount = SensorCount::where('sensor_id', $sensor->id)->latest()->first();
            $value = $sensorCount ? $sensorCount->value : 0;

            // Añadir el campo 'value' a los datos del sensor
            $sensorData = $sensor->toArray();
            $sensorData['value'] = $value;

            return $sensorData;
        });

        // Agrupar los sensores por el nombre de la línea de producción
        $groupedSensors = $sensorsData->groupBy(function ($sensor) {
            return $sensor['production_line']['name']; // Agrupar por el nombre de la línea de producción
        });

        // Devolver la lista de sensores organizados por nombre de la línea de producción
        return response()->json($groupedSensors);
    }

    /**
     * @OA\Post(
     *     path="/api/sensors/insert",
     *     summary="Insertar datos del sensor",
     *     description="Inserta datos en el sensor, verificando si necesita invertir el valor según su configuración.",
     *     tags={"Sensores"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="value", type="integer", example=1),
     *             @OA\Property(property="sensor", type="string", example="sensor/topic")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos procesados correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Datos procesados correctamente"),
     *             @OA\Property(property="sensor", type="string", example="Sensor 1")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sensor no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Sensor no encontrado")
     *         )
     *     )
     * )
     */
    public function sensorInsert(Request $request)
    {
            // Ignorar desconexión del cliente
        ignore_user_abort(true);
        // Validar los datos de entrada
        $validated = $request->validate([
            'value' => 'required|integer',
            'id' => 'required|integer',
        ]);

        // Buscar el sensor directamente usando el ID
        $sensor = Sensor::find($validated['id']);

        // Manejar el caso en que no se encuentre el sensor
        if (!$sensor) {
            return response()->json(['error' => 'Sensor no encontrado'], 404);
        }

        // Procesar el valor del sensor
        $value = $validated['value'];

        // Invertir el valor si corresponde
        if ($sensor->invers_sensors) {
            $value = 1 - $value;
        }



        try {
            // Procesar el sensor y el valor
            $this->processModel($sensor, $value);

            // Retornar la respuesta de éxito
            return response()->json([
                'message' => 'Datos procesados correctamente',
                'sensor' => $sensor->name,
            ], 200);

        } catch (\Exception $e) {
            // Manejar errores inesperados
            Log::error("Error al procesar el sensor ID {$sensor->id}: " . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    

    private function processModel($config, $value)
    {
         // Obtener el registro del barcode asociado
         $barcode = Barcode::find($config->barcoder_id);
        
         if (!$barcode || !$barcode->order_notice) {
             Log::warning("No se encontró el registro del barcode o el campo order_notice está vacío.");
             return;
         }

        // Implementar la lógica específica del procesamiento basado en el modelo de sensor
        switch ($value) {
            case '0':
                $this->process0Model($config, $value, $barcode);
                break;
            case '1':
                $this->process1Model($config, $value, $barcode);
                break;
            default:
            // Log::info("Valo no permitido: {$config->name} (ID: {$config->id}) // Tópico: {$config->mqtt_topic_sensor} // Valor: {$value}"); // Muestra el mensaje en la consola
                break;
        }
    }

    private function process0Model($config, $value, $barcode)
    {
        $this->processModelFinal($config, $value, $barcode, [
            'shift' => 'count_shift_0',
            'total' => 'count_total_0',
            'order' => 'count_order_0',
            'week' => 'count_week_0',
            'time_0' => 'time_00',
            'time_1' => 'time_10',
            'function_model' => $config->function_model_0,
            'sendFunction' => 'sendMqttValue0',
        ]);
    }
    
    private function process1Model($config, $value, $barcode)
    {
        $this->processModelFinal($config, $value, $barcode, [
            'shift' => 'count_shift_1',
            'total' => 'count_total_1',
            'order' => 'count_order_1',
            'week' => 'count_week_1',
            'time_0' => 'time_01',
            'time_1' => 'time_11',
            'function_model' => $config->function_model_1,
            'sendFunction' => 'sendMqttValue1',
        ]);
    }
    
    private function processModelFinal($config, $value, $barcode, $modelConfig)
    {
        
        $orderId = $config->orderId;
        $productName = $config->productName;

        if (!$orderId || !$productName) {
            Log::warning("No se encontró el ID de pedido o el nombre del producto.");
            //return;
        }
    
        //vaerificamos si el sensor es sensor_type 0 o superior
            // Proteger con una transacción si el sensor_type < 1 (o según tu regla)
        if ($config->sensor_type < 1) {
            DB::transaction(function () use ($orderId) {
                // Leemos el registro con lockForUpdate, para que otra transacción
                // no pueda leer valores obsoletos de la misma fila
                $orderStats = OrderStat::where('order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if ($orderStats) {
                    // Verificamos que 'units' sea numérico
                    $units = is_numeric($orderStats->units) ? $orderStats->units : 0;

                    // Incrementamos contadores de forma atómica
                    $orderStats->increment('units_made_real', 1);
                    $orderStats->increment('units_made', 1);
                    if ($orderStats->units_made_real === 1) {
                        $orderStats->prepair_time = Carbon::now()->diffInSeconds($orderStats->created_at);
                         //obtenemos el ultimos regustro del shift_history por linea y con type= shift y action=start
                        $shiftHistory = ShiftHistory::where('production_line_id', $orderStats->production_line_id)
                            ->where('type', 'shift')
                            ->where('action', 'start')
                            ->orderBy('id', 'desc')
                            ->first();

                            if($shiftHistory){
                                $shiftHistory->prepair_time = Carbon::now()->diffInSeconds($orderStats->created_at);
                                $shiftHistory->save();
                            }
                    }
                    $unitsPending = $units - $orderStats->units_made_real - 1;
                    if($unitsPending<1){
                        $unitsPending=0;
                    }

                    // Recalculamos las unidades pendientes con el valor recién incrementado
                    $orderStats->units_pending = $unitsPending;

                    // Guardamos los cambios
                    $orderStats->save();
                } else {
                    Log::warning("No se encontró un registro de OrderStat para order_id: {$orderId}");
                }
            });
        }
        
        // Incrementar los contadores
        try {
            $config->increment($modelConfig['shift']);
            $config->increment($modelConfig['total']);
            $config->increment($modelConfig['order']);
            $config->increment($modelConfig['week']);
            Log::info("Contadores incrementados correctamente para el modelo.");
        } catch (\Exception $e) {
            Log::error("Error al incrementar los contadores: " . $e->getMessage());
            return;
        }
    
        // Calcular tiempos
        try {
            $previousEntry0 = $this->getLastEntryByValue($config->id, 0);
            $previousEntry1 = $this->getLastEntryByValue($config->id, 1);

            $time_0 = $previousEntry0 ? now()->diffInRealSeconds($previousEntry0->created_at) : 300;
            $time_1 = $previousEntry1 ? now()->diffInRealSeconds($previousEntry1->created_at) : 300;
            

            Log::debug("Tiempos calculados: {$modelConfig['time_0']}: $time_0, {$modelConfig['time_1']}: $time_1");
        } catch (\Exception $e) {
            Log::error("Error al calcular los tiempos: " . $e->getMessage());
            return;
        }

        // Insertar en la tabla sensor_counts
        try {
            SensorCount::create([
                'name' => $config->name,
                'value' => $value,
                'sensor_id' => $config->id,
                'production_line_id' => $config->production_line_id,
                'model_product' => $productName,
                'orderId' => $orderId,
                'unic_code_order' => $config->unic_code_order,
                $modelConfig['total'] => $config->{$modelConfig['total']},
                $modelConfig['shift'] => $config->{$modelConfig['shift']},
                $modelConfig['order'] => $config->{$modelConfig['order']},
                $modelConfig['time_0'] => $time_0,
                $modelConfig['time_1'] => $time_1,
            ]);
    
            Log::info("Registro insertado correctamente en sensor_counts.");
        } catch (\Exception $e) {
            Log::error("Error al insertar en sensor_counts: " . $e->getMessage());
        }

                // Añadimos la lógica para buscar en operator_post y actualizar en operators
                try {
                    $operatorPost = OperatorPost::where('finish_at', null)
                        ->where('modbus_id', $config->id)
                        ->first();
        
                    if ($operatorPost) {
                        $operatorId = $operatorPost->operator_id;
        
                        // Buscar el operador por ID
                        $operator = Operator::find($operatorId);
        
                        if ($operator) {
                            // Incrementar los valores de count_shift y count_order
                            $operator->increment('count_shift');
                            $operator->increment('count_order');
        
                            Log::info("Operador actualizado: count_shift y count_order incrementados para el Operator ID: {$operatorId}");
                        } else {
                            Log::info("No se encontró el operador con ID: {$operatorId}");
                        }
                    } else {
                        Log::info("No se encontró ningún registro en operator_post con updated_at NULL y modbus_id: {$config->id}");
                    }
                } catch (\Exception $e) {
                    // Log de errores al intentar actualizar los datos
                    Log::info("Error al procesar datos de operator_post y operators para el Modbus ID: {$config->id}");
                }
    
        // Determinar la función a ejecutar basada en el modelo
        switch ($modelConfig['function_model']) {
            case 'none':
                $this->none($config, $value);
                break;
            case $modelConfig['sendFunction']:
                $this->{$modelConfig['sendFunction']}($config, $value);
                break;
            default:
                Log::warning("Función desconocida: {$modelConfig['function_model']}");
                break;
        }
    }
    
    private function getLastEntryByValue($sensorId, $value)
    {
        return SensorCount::where('sensor_id', $sensorId)
            ->where('value', $value)
            ->orderBy('created_at', 'desc')
            ->limit(1)
            ->first();
    }

    
    private function none($config, $value)
    {
        // No hace nada, función placeholder
    }

    private function sendMqttValue0($config, $value)
    {
        // Determinar el estado según el valor recibido
        $status = ($value === 1 || $value === "1") ? 2 : 0; // 2: buen estado, 0: parada
    
        // Crear mensajes JSON y publicar a MQTT
        $this->sendMqttMessages($config, $status, 'count_order_0', 'count_total_0', 'count_shift_0');
    }
    
    private function sendMqttValue1($config, $value)
    {
        // Obtener el último valor de time_11 desde la tabla sensor_counts
        $lastTime = SensorCount::where('sensor_id', $config->id)
            ->where('value', 1)
            ->orderBy('created_at', 'desc')
            ->value('time_11');
    
        // Determinar el estado basado en tiempos
        $optimalTime = $config->optimal_production_time;
        $reducedSpeedMultiplier = $config->reduced_speed_time_multiplier;
        Log::debug("Calculando estado para el sensor {$config->name} (ID: {$config->id}) con lastTime: $lastTime, optimalTime: $optimalTime, reducedSpeedMultiplier: $reducedSpeedMultiplier");
        $status = $this->determineStatus($lastTime, $optimalTime, $reducedSpeedMultiplier);
    
        // Crear mensajes JSON y publicar a MQTT
        $this->sendMqttMessages($config, $status, 'count_order_1', 'count_total_1', 'count_shift_1');
    }
    
    private function sendMqttMessages($config, $status, $orderCountKey, $totalCountKey, $shiftCountKey)
    {
        // Crear los mensajes JSON
        $processedMessage = json_encode([
            'value' => $config->{$orderCountKey},
            'status' => $status,
        ]);
    
        $processedMessageTotal = json_encode([
            'value' => $config->{$totalCountKey},
            'status' => $status,
        ]);
    
        $processedMessageTotalShift = json_encode([
            'value' => $config->{$shiftCountKey},
            'status' => $status,
        ]);
    
        // Publicar los mensajes a través de MQTT
        $this->publishMqttMessage($config->mqtt_topic_1 . '/infinite_counter', $processedMessageTotal);
        $this->publishMqttMessage($config->mqtt_topic_1, $processedMessage);
    
        //Log::info("Mensaje MQTT procesado y enviado al tópico {$config->mqtt_topic_1}: {$processedMessage}");
    }
    
    private function determineStatus($lastTime, $optimalTime, $reducedSpeedMultiplier)
    {
        Log::debug("Tiempos: lastTime: $lastTime, optimalTime: $optimalTime, reducedSpeedMultiplier: $reducedSpeedMultiplier");
        // Determinar el estado según los tiempos
        if (!is_numeric($lastTime)) {
            Log::warning("Tiempos no numéricos: lastTime: $lastTime, optimalTime: $optimalTime, reducedSpeedMultiplier: $reducedSpeedMultiplier");
            return 3; // Sin datos 
        }
    
        if ($lastTime <= $optimalTime) {
            Log::info("Buen estado: lastTime: $lastTime, optimalTime: $optimalTime, reducedSpeedMultiplier: $reducedSpeedMultiplier");
            return 2; // Buen estado
        }
    
        if ($lastTime <= $optimalTime * $reducedSpeedMultiplier) {
            Log::info("Velocidad reducida: lastTime: $lastTime, optimalTime: $optimalTime, reducedSpeedMultiplier: $reducedSpeedMultiplier");
            return 1; // Velocidad reducida
        }
    
        Log::info("Parada: lastTime: $lastTime, optimalTime: $optimalTime, reducedSpeedMultiplier: $reducedSpeedMultiplier");
        return 0; // Parada
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
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}