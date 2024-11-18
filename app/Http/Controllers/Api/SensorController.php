<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sensor;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
use Illuminate\Support\Facades\Log;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\Barcode;
use App\Models\SensorCount;
use Illuminate\Support\Facades\DB;


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
        // Validar que el cuerpo de la solicitud tenga los campos necesarios
        $validated = $request->validate([
            'value' => 'required|integer',
            'sensor' => 'required|string',
        ]);

        // Buscar el sensor usando el campo 'sensor'
        $topic = $request->input('sensor'); // Obtener el valor del campo 'sensor'
        $sensor = Sensor::where('mqtt_topic_sensor', $topic)->first();

        // Verificar si el sensor existe
        if (!$sensor) {
            return response()->json(['error' => 'Sensor no encontrado'], 404);
        }

        // Procesar el valor del sensor (lógica del proceso similar a la de MQTT)
        $value = $validated['value'];
        
        // Invertir sensor si está seleccionado
        $inversSensor = $sensor->invers_sensors;
        if ($inversSensor === true || $inversSensor === 1 || $inversSensor === "1") {
            $value = 1 - $value; // Invierte entre 0 y 1
        }

        // Buscar el último registro en sensor_counts para el sensor_id
        $lastSensorCount = DB::table('sensor_counts')
            ->where('sensor_id', $sensor->id)
            ->latest('id') // Ordenar por id descendente para obtener el último
            ->first();

        // Verificar si el último valor coincide
        if ($lastSensorCount && $lastSensorCount->value == $value) {
            return response()->json([
                'error' => 'El valor recibido ya está registrado como el último valor para este sensor',
                'sensor' => $sensor->name,
                'value' => $value,
            ], 400);
        }

        $this->processModel($sensor, $value);

        // Retornar la respuesta
        return response()->json(['message' => 'Datos procesados correctamente', 'sensor' => $sensor->name], 200);
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
        // Validar el campo order_notice
        if (!$barcode || !$barcode->order_notice) {
            Log::warning("El campo order_notice está vacío o no se encontró el barcode ID: {$barcode->id}");
            $orderId = $valuePerPackage = $productName = $unitsPerBox = $totalPerBox = "0";
        } else {
            // Decodificar el JSON y extraer valores
            $orderNotice = json_decode($barcode->order_notice, true);
    
            $orderId = $orderNotice['orderId'] ?? "0";
            $valuePerPackage = $orderNotice['refer']['value'] ?? "0";
            $productName = $orderNotice['refer']['groupLevel'][0]['id'] ?? "0";
            $unitsPerBox = $orderNotice['refer']['groupLevel'][0]['uds'] ?? "0";
            $totalPerBox = $orderNotice['refer']['groupLevel'][0]['total'] ?? "0";
    
            // Log para depuración de valores extraídos
            Log::debug("Valores extraídos del JSON: OrderId: $orderId, ValuePerPackage: $valuePerPackage, ProductName: $productName");
        }
    
        // Incrementar contadores dentro de una transacción
        try {
            DB::transaction(function () use ($config) {
                $config->increment('count_shift_0');
                $config->increment('count_total_0');
                $config->increment('count_order_0');
            });
            Log::info("Contadores incrementados correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al incrementar los contadores: " . $e->getMessage());
            return;
        }
    
        // Calcular time_00 y time_10 en una única consulta
        try {
            $previousEntries = SensorCount::where('sensor_id', $config->id)
                ->whereIn('value', [0, 1])
                ->orderBy('created_at', 'desc')
                ->get();
    
            $previousEntry0 = $previousEntries->firstWhere('value', 0);
            $previousEntry1 = $previousEntries->firstWhere('value', 1);
    
            $defaultTime = 300;
            $time_00 = $previousEntry0 ? now()->diffInSeconds($previousEntry0->created_at) : $defaultTime;
            $time_10 = $previousEntry1 ? now()->diffInSeconds($previousEntry1->created_at) : $defaultTime;
    
            Log::debug("Valores calculados: time_00: $time_00, time_10: $time_10");
        } catch (\Exception $e) {
            Log::error("Error al calcular los tiempos: " . $e->getMessage());
            return;
        }
    
        // Insertar el nuevo registro en la tabla sensor_counts
        try {
            SensorCount::create([
                'name' => $config->name,
                'value' => $value,
                'sensor_id' => $config->id,
                'production_line_id' => $config->production_line_id,
                'model_product' => $productName,
                'orderId' => $orderId,
                'count_total_0' => $config->count_total_0,
                'count_shift_0' => $config->count_shift_0,
                'count_order_0' => $config->count_order_0,
                'time_00' => $time_00,
                'time_10' => $time_10,
            ]);
            Log::info("Registro insertado en sensor_counts correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al insertar en sensor_counts: " . $e->getMessage());
        }
    
        // Determinar la función a ejecutar basada en function_model_0
        try {
            switch ($config->function_model_0) {
                case 'none':
                    $this->none($config, $value);
                    break;
                case 'sendMqttValue0':
                    $this->sendMqttValue($config, $value, 0);
                    break;
                default:
                    Log::warning("Función desconocida: {$config->function_model_0}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error al ejecutar la función dinámica: " . $e->getMessage());
        }
    }
    
    

    /**
     * Process the sensor data with model 1.
     *
     * @param \App\Models\Sensor $config Sensor configuration
     * @param string $value Sensor value (0 or 1)
     * @return void
     */
    private function process1Model($config, $value, $barcode)
    {
        // Validar el campo order_notice
        if (!$barcode || !$barcode->order_notice) {
            Log::warning("El campo order_notice está vacío o no se encontró el barcode ID: {$barcode->id}");
            $orderId = $valuePerPackage = $productName = $unitsPerBox = $totalPerBox = "0";
        } else {
            // Decodificar el JSON y extraer valores
            $orderNotice = json_decode($barcode->order_notice, true);
    
            $orderId = $orderNotice['orderId'] ?? "0";
            $valuePerPackage = $orderNotice['refer']['value'] ?? "0";
            $productName = $orderNotice['refer']['groupLevel'][0]['id'] ?? "0";
            $unitsPerBox = $orderNotice['refer']['groupLevel'][0]['uds'] ?? "0";
            $totalPerBox = $orderNotice['refer']['groupLevel'][0]['total'] ?? "0";
    
            // Log para depuración de valores extraídos
            Log::debug("Valores extraídos del JSON: OrderId: $orderId, ValuePerPackage: $valuePerPackage, ProductName: $productName");
        }
    
        // Incrementar contadores dentro de una transacción
        try {
            DB::transaction(function () use ($config) {
                $config->increment('count_shift_1');
                $config->increment('count_total_1');
                $config->increment('count_order_1');
            });
            Log::info("Contadores incrementados correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al incrementar los contadores: " . $e->getMessage());
            return;
        }
    
        // Calcular time_11 y time_01 en una única consulta
        try {
            $previousEntries = SensorCount::where('sensor_id', $config->id)
                ->whereIn('value', [0, 1])
                ->orderBy('created_at', 'desc')
                ->get();
    
            $previousEntry1 = $previousEntries->firstWhere('value', 1);
            $previousEntry0 = $previousEntries->firstWhere('value', 0);
    
            $defaultTime = 300;
            $time_11 = $previousEntry1 ? now()->diffInSeconds($previousEntry1->created_at) : $defaultTime;
            $time_01 = $previousEntry0 ? now()->diffInSeconds($previousEntry0->created_at) : $defaultTime;
    
            Log::debug("Valores calculados: time_11: $time_11, time_01: $time_01");
        } catch (\Exception $e) {
            Log::error("Error al calcular los tiempos: " . $e->getMessage());
            return;
        }
    
        // Insertar el nuevo registro en la tabla sensor_counts
        try {
            SensorCount::create([
                'name' => $config->name,
                'value' => $value,
                'sensor_id' => $config->id,
                'production_line_id' => $config->production_line_id,
                'model_product' => $productName,
                'orderId' => $orderId,
                'count_total_1' => $config->count_total_1,
                'count_shift_1' => $config->count_shift_1,
                'count_order_1' => $config->count_order_1,
                'time_11' => $time_11,
                'time_01' => $time_01,
                'unic_code_order' => $config->unic_code_order,
            ]);
            Log::info("Registro insertado en sensor_counts correctamente.");
        } catch (\Exception $e) {
            Log::error("Error al insertar en sensor_counts: " . $e->getMessage());
        }
    
        // Determinar la función a ejecutar basada en function_model_1
        try {
            switch ($config->function_model_1) {
                case 'none':
                    $this->none($config, $value);
                    break;
                case 'sendMqttValue1':
                    $this->sendMqttValue($config, $value, 1);
                    break;
                default:
                    Log::warning("Función desconocida: {$config->function_model_1}");
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error al ejecutar la función dinámica: " . $e->getMessage());
        }
    }
    

    private function none($config, $value)
    {
        // No hace nada, función placeholder
    }
    /**
     * Envía mensajes MQTT basados en el tipo y valor del sensor.
     *
     * @param \App\Models\Sensor $config Configuración del sensor.
     * @param mixed $value Valor recibido del sensor.
     * @param int $type Tipo de procesamiento (0 o 1).
     */
    private function sendMqttValue($config, $value, $type)
    {
        // Determinar el estado según el tipo y el valor del sensor.
        $status = $this->determineStatus($value, $type, $config);

        // Construir los mensajes JSON para enviar.
        $messages = [
            'order' => $this->createMqttMessage($config->{'count_order_' . $type}, $status),
            'total' => $this->createMqttMessage($config->{'count_total_' . $type}, $status),
            'shift' => $this->createMqttMessage($config->{'count_shift_' . $type}, $status),
        ];

        // Intentar publicar los mensajes MQTT utilizando el método existente.
        try {
            // Publica el mensaje del contador total en el tópico correspondiente.
            $this->publishMqttMessage("{$config->mqtt_topic_1}/infinite_counter", $messages['total']);

            // Publica el mensaje del contador de órdenes en el tópico principal.
            $this->publishMqttMessage($config->mqtt_topic_1, $messages['order']);

            // Registrar en el log que los mensajes fueron publicados correctamente.
            Log::info("Mensajes MQTT publicados correctamente para tipo {$type}: {$messages['order']}");
        } catch (\Exception $e) {
            // En caso de error, registrar el mensaje de error en el log.
            Log::error("Error publicando mensajes MQTT: " . $e->getMessage());
        }
    }

    /**
     * Determina el estado basado en el tipo y la configuración del sensor.
     *
     * @param mixed $value Valor recibido del sensor.
     * @param int $type Tipo de procesamiento (0 o 1).
     * @param \App\Models\Sensor $config Configuración del sensor.
     * @return int Estado calculado (0, 1, 2, o 3).
     */
    private function determineStatus($value, $type, $config)
    {
        if ($type === 0) {
            // Para tipo 0, el estado se basa directamente en el valor recibido.
            return ($value === 1 || $value === "1") ? 2 : 0;
        }

        if ($type === 1) {
            // Para tipo 1, se necesita calcular el estado basado en el tiempo transcurrido.
            // Obtener el último valor de time_11 desde sensor_counts.
            $lastTime = SensorCount::where('sensor_id', $config->id)
                ->where('value', 1)
                ->orderBy('created_at', 'desc')
                ->value('time_11');

            // Verificar si lastTime es numérico.
            if (is_numeric($lastTime)) {
                if ($lastTime <= $config->optimal_production_time) {
                    // Si el tiempo es menor o igual al tiempo óptimo, buen estado.
                    return 2; // Buen estado.
                } elseif ($lastTime <= $config->optimal_production_time * $config->reduced_speed_time_multiplier) {
                    // Si el tiempo está dentro del multiplicador de velocidad reducida, estado de velocidad reducida.
                    return 1; // Velocidad reducida.
                }
            }
            // Si no cumple ninguna condición anterior, se considera parada.
            return 0; // Parada.
        }

        // Si el tipo no es 0 ni 1, se retorna 'sin datos'.
        return 3; // Sin datos.
    }

    /**
     * Crea un mensaje JSON para enviar a través de MQTT.
     *
     * @param int $value Valor del contador a enviar.
     * @param int $status Estado calculado para el mensaje.
     * @return string Mensaje JSON listo para enviar.
     */
    private function createMqttMessage($value, $status)
    {
        // Construir el mensaje como un arreglo asociativo y convertirlo a JSON.
        return json_encode([
            'value' => $value,
            'status' => $status,
        ]);
    }

        


    private function publishMqttMessage($topic, $message)
    {
       try {
        // Inserta en la tabla mqtt_send_server1
        MqttSendServer1::createRecord($topic, $message);

        // Inserta en la tabla mqtt_send_server2
        MqttSendServer2::createRecord($topic, $message);

        Log::info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");

        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }
}
