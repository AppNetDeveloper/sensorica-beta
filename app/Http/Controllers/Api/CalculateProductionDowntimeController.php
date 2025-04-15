<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\DowntimeSensor;
use App\Models\OrderStat;
use App\Models\SensorCount;
use Illuminate\Support\Facades\Log;
use Exception;


class CalculateProductionDowntimeController extends Controller
{
    /**
     * Endpoint para calcular downtime.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculateDowntime(Request $request)
    {
        ignore_user_abort(true);
        try {
            $this->calculateProductionDowntime();
            
            return response()->json(['message' => 'Downtime calculated successfully'], 200);
        } catch (Exception $e) {
            Log::error('Error in calculateDowntime: ' . $e->getMessage());
            return response()->json(['error' => 'Error processing downtime calculation'], 500);
        }
    }

    private function calculateProductionDowntime()
    {
        // Obtener todos los sensores donde el evento sea "start" y sensor_type sea 0
        $sensors = Sensor::where('sensor_type', 0)
                            ->where(function ($query) {
                                $query->where(function ($q) {
                                    $q->where('event', 'start')
                                    ->where('shift_type', 'shift');
                                })
                                ->orWhere(function ($q) {
                                    $q->where('event', 'end')
                                    ->where('shift_type', 'stop');
                                });
                            })
                            ->get();

    
        // Recorrer cada sensor y aplicar la lógica de downtime para sensor_type 0
        foreach ($sensors as $sensor) {
            $this->handleType0DowntimeLogic($sensor);
        }
        //PARA MONITOR POR LINEA DE DOWNTIME
        // Agrupar sensores por production_line_id
        $groupedSensors = $sensors->groupBy('production_line_id');

        // Para cada grupo de sensores, verificar si todos tienen en downtime_sensors
        // la última entrada con end_time == NULL (es decir, abiertos/parados)
        foreach ($groupedSensors as $productionLineId => $sensorsGroup) {
            $allStopped = true;

            foreach ($sensorsGroup as $sensor) {
                // Suponemos que tienes un modelo DowntimeSensor que representa la tabla downtime_sensors
                $lastDowntime = DowntimeSensor::where('sensor_id', $sensor->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Si no existe un registro o si el último registro tiene un end_time definido,
                // consideramos que el sensor no está en inactividad abierta.
                if (!$lastDowntime || $lastDowntime->end_time !== null) {
                    $allStopped = false;
                    break;
                }
            }

            if ($allStopped) {
                \Log::info("Todos los sensores de la línea de producción {$productionLineId} tienen el downtime abierto (end_time=NULL) y están parados.");
                $this->downTimeLine($productionLineId);
            }else{
                \Log::info("Línea de producción {$productionLineId} tiene al menos un sensor activo de conteo.");
            }
        }
        //FIN MONITOR DOWNTIME LINEA

        // Procesar sensores de tipo distinto a 0
        
        try {
            $sensorsNotType0 = Sensor::where('sensor_type', '>', 0)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('event', 'start')
                          ->where('shift_type', 'shift');
                    })
                    ->orWhere(function ($q) {
                        $q->where('event', 'end')
                          ->where('shift_type', 'stop');
                    });
                })
                ->get();
                foreach ($sensorsNotType0 as $sensor) {
                    \Log::info("Procesando sensor nonType 0: {$sensor->name} (ID: {$sensor->id})");
                $this->handleGenericDowntimeLogic($sensor);
                }
        } catch (\Exception $e) {
            // Manejar la excepción, por ejemplo:
            Log::error('Error al obtener sensores: ' . $e->getMessage());
            return response()->json(['error' => 'Error al procesar sensores'], 500);
        }
        
        
    }
    
    private function downTimeLine($productionLineId)
    {
        // Obtener únicamente los sensores non 0 de la línea recibida
        $sensorsNotType0 = Sensor::where('event', 'start')
                                  ->where('sensor_type', '>', 0)
                                  ->where('production_line_id', $productionLineId)
                                  ->get();
    
        $atLeastOneStopped = false;
        foreach ($sensorsNotType0 as $sensor) {
            $lastDowntime = DowntimeSensor::where('sensor_id', $sensor->id)
                                          ->orderBy('created_at', 'desc')
                                          ->first();
    
            // Si existe un registro y su end_time es NULL, se considera que el sensor está en downtime abierto
            if ($lastDowntime && $lastDowntime->end_time === null) {
                $atLeastOneStopped = true;
                break;
            }
        }
        
        // Obtener el último registro de OrderStats para la línea de producción
        $orderStat = OrderStat::where('production_line_id', $productionLineId)
                                ->orderBy('id', 'desc')
                                ->first();
    
        // Intervalo de tiempo a sumar (asumiendo que el script se ejecuta cada 1 segundo)
        $intervalInSeconds = 1;

        if ($orderStat) { // Asegúrate de que $orderStat existe antes de intentar usarlo
            if ($atLeastOneStopped) {
                \Log::info("La línea de producción con ID {$productionLineId} está parada con al menos un sensor de tipo non 0 activo. Sumando {$intervalInSeconds}s a down_time.");
                // --- LÍNEA CAMBIADA ---
                $orderStat->down_time += $intervalInSeconds;
                $orderStat->save();
                // --- FIN LÍNEA CAMBIADA ---
            } else {
                \Log::info("La línea de producción con ID {$productionLineId} está parada sin ningún sensor de tipo non 0 activo. Sumando {$intervalInSeconds}s a production_stops_time.");
                // --- LÍNEA CAMBIADA ---
                $orderStat->production_stops_time += $intervalInSeconds;
                $orderStat->save();
                // --- FIN LÍNEA CAMBIADA ---
            }
        } else {
             \Log::warning("No se encontró OrderStat para la línea de producción ID: {$productionLineId}");
        }
    }
    
    
 

    private function handleType0DowntimeLogic($sensor)
    {
        // Calcular el tiempo máximo permitido
        $optimalTime = $sensor->optimal_production_time ?? 30;
        $multiplier = $sensor->reduced_speed_time_multiplier ?? 1;
        $maxTime = $optimalTime * $multiplier;

        // Buscar el último registro en sensor_counts con value = 1
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)
            ->where('value', '1')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($sensorCount) {
            $lastEventTime = Carbon::parse($sensorCount->created_at);
            $now = Carbon::now();
            $timeDifference = $now->diffInSeconds($lastEventTime);

            if ($timeDifference > $maxTime) {
                // Se amplía la inactividad, mandamos mensaje con status=0
                \Log::info("Sensor {$sensor->name} is in downtime.");
                $this->incrementDowntime($sensor, $timeDifference - $maxTime);
                $this->sendMqttMessage($sensor, 0, 0); // status 0 para ampliado, tipo 0
                
                $this->sendMqttStatusMessage($sensor, 0);

            }elseif($timeDifference < $maxTime && $timeDifference > $optimalTime){
                //parrar la inactividad
                $this->closeDowntime($sensor);

                $this->sendMqttStatusMessage($sensor, 1);
            } else {
                // Estable (no se amplía), mandamos mensaje con status=2
                \Log::info("Sensor {$sensor->name} is stable.");
                $this->closeDowntime($sensor);
                $this->sendMqttMessage($sensor, 2, 0); // status 2 para estable, tipo 0
            }
        }
    }

    private function handleGenericDowntimeLogic($sensor)
    {
        // Buscar el último registro en sensor_counts con el sensor_id
        $sensorCount = SensorCount::where('sensor_id', $sensor->id)
        ->orderBy('created_at', 'desc')
        ->first();

        if ($sensorCount && $sensorCount->value == 0) {
        // El valor es 0, lo que significa que el sensor está en inactividad
        $lastEventTime = Carbon::parse($sensorCount->created_at);
        $now = Carbon::now();
        $timeDifference = $now->diffInSeconds($lastEventTime);

            // Se amplía la inactividad, mandamos mensaje con status=0
            // Log::info("Sensor  de typo non 0 :{$sensor->name} is in downtime.");
            $this->incrementDowntime($sensor, $timeDifference);  // Ajuste para no restar $maxTime aquí
            $this->sendMqttMessage($sensor, 0, $sensor->sensor_type); // status 0 para ampliado, tipo según el sensor
        } else {
            // El valor no es 0, lo que significa que el sensor está estable
            //Log::info("Sensor  de typo non 0 : {$sensor->name} is stable.");
            $this->closeDowntime($sensor);
            $this->sendMqttMessage($sensor, 2, $sensor->sensor_type); // status 2 para estable
        }

    }

    /**
     * Publica mensajes MQTT con valores específicos del sensor.
     */
    private function sendMqttStatusMessage($sensor, $status)
    {
        $topicBase = $sensor->mqtt_topic_1;

        $messageInfinite = json_encode([
            'value' => $sensor->count_total_1 ?? 0,
            'status' => $status,
        ]);

        $messageOrder = json_encode([
            'value' => $sensor->count_order_1 ?? 0,
            'status' => $status,
        ]);

        $this->publishMqttMessage($topicBase . "/infinite_counter", $messageInfinite);
        $this->publishMqttMessage($topicBase, $messageOrder);
    }
    private function incrementDowntime($sensor, $downtimeTime)
    {
        $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
            ->whereNull('end_time')
            ->first();

        $intervalInSeconds = 1;
        if ($downtime) {
            $downtime->count_time += $intervalInSeconds;
            $downtime->save();
            \Log::info("Updated downtime for sensor: {$sensor->name}. Incremented count_time by {$downtimeTime} seconds.");
        } else {
            DowntimeSensor::create([
                'sensor_id' => $sensor->id,
                'start_time' => Carbon::now(),
                'count_time' => $downtimeTime,
                'end_time' => null,
            ]);
            \Log::info("New downtime record created for sensor: {$sensor->name}.");
        }

        $sensor->downtime_count += $intervalInSeconds;
        $sensor->save();
        \Log::info("Downtime count for sensor {$sensor->name} incremented to {$sensor->downtime_count}.");
    }

    private function closeDowntime($sensor)
    {
        $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
            ->whereNull('end_time')
            ->first();

        if ($downtime) {
            $downtime->end_time = Carbon::now();
            $downtime->save();
            \Log::info("Downtime for sensor {$sensor->name} ended.");
        }
    }

    private function sendMqttMessage($sensor, $status, $sensorType)
    {
        // Extraer el production_line_id y mqtt_topic del sensor
        $productionLineId = $sensor->production_line_id;
        $mqttTopic = $sensor->mqtt_topic_1;

        // Formatear el mqtt_topic eliminando la parte '/mac/...'
        $topicBase = preg_replace('/\/mac\/[^\/]+$/', '', $mqttTopic);
        //\Log::info("Formatted MQTT topic: {$topicBase}");

        // Sumar downtime_count de todos los sensores con el mismo production_line_id y el sensor_type actual
        $totalDowntimeCount = Sensor::where('production_line_id', $productionLineId)
            ->where('sensor_type', $sensorType) // Filtrar por el tipo de sensor actual
            ->sum('downtime_count') * 1000; // Convertir a milisegundos

        // Crear el mensaje JSON
        $message = json_encode([
            'value' => $totalDowntimeCount,
            'status' => $status // status = 0 cuando se amplía, status = 2 cuando es estable
        ]);

        // Publicar el mensaje MQTT usando la función de publicación
        $this->publishMqttMessage($topicBase . "/waitTime", $message);
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