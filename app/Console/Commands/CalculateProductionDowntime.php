<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Sensor;
use App\Models\DowntimeSensor;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\SensorCount;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\OrderStat;

class CalculateProductionDowntime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-production-downtime';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate the production downtime for each sensor and handle downtime counts per shift, and send MQTT messages';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $downtimeStartTimes = []; // Array para guardar tiempos de inicio por línea de producción

    // Propiedades para almacenar los tiempos de inicio
    private $productionStopStartTimes = [];
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $this->info("Starting the calculation of production downtime...");

            $this->calculateProductionDowntime();

            // Esperar 1 segundo antes de volver a ejecutar la lógica
            $this->info("Waiting for 1 second before the next run...");
            sleep(1); // Pausar 1 segundos
        }

        return 0;
    }
    private function validateSensorData($sensor)
    {
        if (!$sensor->mqtt_topic_1) {
            $this->error("Sensor {$sensor->name} does not have an MQTT topic configured.");
            return false;
        }

        if (!$sensor->production_line_id) {
            $this->error("Sensor {$sensor->name} does not have a production line ID.");
            return false;
        }

        return true;
    }

    private function calculateProductionDowntime()
    {
        // Obtener todos los sensores donde el evento sea "start"
        $sensors = Sensor::where('event', 'start')->get();
    
        // Mostrar cuántos sensores se están procesando
        $this->info("Procesando {$sensors->count()} sensores...");
    
        // Agrupar sensores por línea de producción y tipo de sensor
        $sensorsByLineAndType = $sensors->groupBy(function ($sensor) {
            return $sensor->production_line_id . '_' . $sensor->sensor_type;
        });
    
        // Iterar por cada grupo de sensores agrupados por línea y tipo
        foreach ($sensorsByLineAndType as $groupKey => $lineSensors) {
            // Dividir el grupo clave en ID de línea de producción y tipo de sensor
            [$productionLineId, $sensorType] = explode('_', $groupKey);
    
            // Bandera para verificar si todos los sensores de tipo 0 están en downtime
            $allType0InDowntime = true;
    
            // Bandera para verificar si al menos un sensor de tipo mayor a 0 está en downtime
            $anyHigherTypeInDowntime = false;
    
            // Iterar sobre los sensores en el grupo actual
            foreach ($lineSensors as $sensor) {
                // Validar los datos del sensor antes de procesarlo
                if (!$this->validateSensorData($sensor)) {
                    // Mostrar mensaje de error y omitir sensor con datos inválidos
                    $this->error("Saltando el sensor {$sensor->name} debido a datos inválidos.");
                    continue; // Pasar al siguiente sensor
                }
    
                // Buscar si el sensor actual está en downtime (end_time es NULL)
                $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
                    ->whereNull('end_time')
                    ->first();
    
                // Verificar si algún sensor de tipo 0 no está en downtime
                if ($sensor->sensor_type == 0 && !$downtime) {
                    $allType0InDowntime = false; // Cambiar bandera si al menos uno no está en downtime
                }
    
                // Verificar si al menos un sensor de tipo mayor a 0 está en downtime
                if ($sensor->sensor_type > 0 && $downtime) {
                    $anyHigherTypeInDowntime = true; // Cambiar bandera si al menos uno está en downtime
                }
            }
    
            // Prioridad para updateOrderStatsDowntime
            if ($allType0InDowntime && !$anyHigherTypeInDowntime) {
                // Manejar downtime incremental
                if (!isset($this->downtimeStartTimes[$productionLineId])) {
                    $this->downtimeStartTimes[$productionLineId] = Carbon::now();
                    $this->info("Downtime iniciado para la línea {$productionLineId}. Esperando 2 segundos para confirmar.");
                } else {
                    $elapsedTime = Carbon::now()->diffInSeconds($this->downtimeStartTimes[$productionLineId]);
                    if ($elapsedTime >= 1) {
                        $this->updateOrderStatsDowntime($productionLineId, 1);
                        $this->downtimeStartTimes[$productionLineId] = Carbon::now(); // Reiniciar temporizador
                        $this->info("Incrementando downtime para la línea {$productionLineId} en +1 segundo.");
                    }
                }
    
                // Si se activa updateOrderStatsDowntime, desactivar producción detenida
                if (isset($this->productionStopStartTimes[$productionLineId])) {
                    unset($this->productionStopStartTimes[$productionLineId]);
                    $this->info("Producción detenida desactivada para la línea {$productionLineId} debido a downtime.");
                }
            } elseif ($allType0InDowntime && $anyHigherTypeInDowntime) {
                // Manejar producción detenida solo si updateOrderStatsDowntime no está activa
                if (!isset($this->productionStopStartTimes[$productionLineId])) {
                    $this->productionStopStartTimes[$productionLineId] = Carbon::now();
                    $this->info("Producción detenida iniciada para la línea {$productionLineId}. Esperando 2 segundos para confirmar.");
                } else {
                    $elapsedTime = Carbon::now()->diffInSeconds($this->productionStopStartTimes[$productionLineId]);
                    if ($elapsedTime >= 1) {
                        $this->updateProductionStopTime($productionLineId, 1);
                        $this->productionStopStartTimes[$productionLineId] = Carbon::now(); // Reiniciar temporizador
                        $this->info("Incrementando producción detenida para la línea {$productionLineId} en +1 segundo.");
                    }
                }
            } else {
                // Manejar el fin del downtime y producción detenida
                $this->handleDowntimeEnd($groupKey, $productionLineId, $sensorType);
    
                // Reiniciar el temporizador de downtime
                if (isset($this->downtimeStartTimes[$productionLineId])) {
                    unset($this->downtimeStartTimes[$productionLineId]);
                    $this->info("Downtime terminado para la línea {$productionLineId}.");
                }
    
                // Reiniciar el temporizador de producción detenida
                if (isset($this->productionStopStartTimes[$productionLineId])) {
                    unset($this->productionStopStartTimes[$productionLineId]);
                    $this->info("Producción detenida finalizada para la línea {$productionLineId}.");
                }
            }
        }
    }
    private function updateOrderStatsDowntime($productionLineId, $downtimeDuration)
    {

        //esto es parada de la linea no identificada down_time cuando se para la linea pero sin que los sensores de type1 2 3 4 que son no de conteo no son activos
        // Buscar el registro más reciente en order_stats para la línea de producción
        $orderStat = OrderStat::where('production_line_id', $productionLineId)
                              ->latest('id') // Ordenar por ID descendente y obtener el más reciente
                              ->first();
    
        if ($orderStat) {
            // Incrementar el tiempo de downtime con la duración calculada
            $orderStat->down_time += $downtimeDuration;
            $orderStat->save(); // Guardar cambios en la base de datos
            $this->info("Updated downtime in order_stats for production line {$productionLineId}: +{$downtimeDuration} seconds.");
        } else {
            // Mostrar error si no se encuentra un registro para la línea de producción
            $this->error("No order_stats record found for production line {$productionLineId}.");
        }
    }
    
    private function updateProductionStopTime($productionLineId, $stopDuration)
    {
        //esta es parada identificada cuando un sensor de falta de materia prima etc de type 1 2 3 4 5 pone que se para la producttion por camboar malla cajas etc
        
        // Buscar el registro más reciente en order_stats para la línea de producción
        $orderStat = OrderStat::where('production_line_id', $productionLineId)
                              ->latest('id') // Ordenar por ID descendente y obtener el más reciente
                              ->first();
    
        if ($orderStat) {
            // Incrementar el tiempo de producción detenida con la duración calculada
            $orderStat->production_stops_time += $stopDuration;
            $orderStat->save(); // Guardar cambios en la base de datos
            $this->info("Updated production_stops_time in order_stats for production line {$productionLineId}: +{$stopDuration} seconds.");
        } else {
            // Mostrar error si no se encuentra un registro para la línea de producción
            $this->error("No order_stats record found for production line {$productionLineId}.");
        }
    }

    private function handleDowntimeEnd($groupKey, $productionLineId, $sensorType)
    {
        // Este método es llamado cuando el downtime de tipo 0 o producción detenida termina
        $this->info("El downtime ha finalizado para el grupo {$groupKey} en la línea de producción {$productionLineId} y tipo de sensor {$sensorType}.");
        
        // Aquí puedes agregar lógica adicional si es necesario
        // Por ejemplo, reiniciar banderas o realizar otras operaciones relacionadas con el downtime
    }

    private function handleDowntimeStart($groupKey, $productionLineId, $sensorType)
    {
        // Este método es llamado cuando comienza un periodo de downtime
        $this->info("Downtime iniciado para el grupo {$groupKey} en la línea de producción {$productionLineId} y tipo de sensor {$sensorType}.");
        
        // Puedes incluir lógica adicional aquí si es necesario
        // Por ejemplo, registrar eventos en logs, actualizar registros de base de datos, etc.
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
                $this->info("Sensor {$sensor->name} is in downtime.");
                $this->incrementDowntime($sensor, $timeDifference - $maxTime);
                $this->sendMqttMessage($sensor, 0, 0); // status 0 para ampliado, tipo 0
                
                $this->sendMqttStatusMessage($sensor, 0);

            }elseif($timeDifference < $maxTime && $timeDifference > $optimalTime){
                //parrar la inactividad
                $this->closeDowntime($sensor);

                $this->sendMqttStatusMessage($sensor, 1);
            } else {
                // Estable (no se amplía), mandamos mensaje con status=2
                $this->info("Sensor {$sensor->name} is stable.");
                $this->closeDowntime($sensor);
                $this->sendMqttMessage($sensor, 2, 0); // status 2 para estable, tipo 0
            }
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
        $this->info("Sensor {$sensor->name} is in downtime.");
        $this->incrementDowntime($sensor, $timeDifference);  // Ajuste para no restar $maxTime aquí
        $this->sendMqttMessage($sensor, 0, $sensor->sensor_type); // status 0 para ampliado, tipo según el sensor
        } else {
        // El valor no es 0, lo que significa que el sensor está estable
        $this->info("Sensor {$sensor->name} is stable.");
        $this->closeDowntime($sensor);
        $this->sendMqttMessage($sensor, 2, $sensor->sensor_type); // status 2 para estable
        }

    }

    private function incrementDowntime($sensor, $downtimeTime)
    {
        $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
            ->whereNull('end_time')
            ->first();

        if ($downtime) {
            $downtime->count_time += $downtimeTime;
            $downtime->save();
            $this->info("Updated downtime for sensor: {$sensor->name}. Incremented count_time by {$downtimeTime} seconds.");
        } else {
            DowntimeSensor::create([
                'sensor_id' => $sensor->id,
                'start_time' => Carbon::now(),
                'count_time' => $downtimeTime,
                'end_time' => null,
            ]);
            $this->info("New downtime record created for sensor: {$sensor->name}.");
        }

        $sensor->downtime_count++;
        $sensor->save();
        $this->info("Downtime count for sensor {$sensor->name} incremented to {$sensor->downtime_count}.");
    }

    private function closeDowntime($sensor)
    {
        $downtime = DowntimeSensor::where('sensor_id', $sensor->id)
            ->whereNull('end_time')
            ->first();

        if ($downtime) {
            $downtime->end_time = Carbon::now();
            $downtime->save();
            $this->info("Downtime for sensor {$sensor->name} ended.");
        }
    }

    private function sendMqttMessage($sensor, $status, $sensorType)
    {
        // Extraer el production_line_id y mqtt_topic del sensor
        $productionLineId = $sensor->production_line_id;
        $mqttTopic = $sensor->mqtt_topic_1;

        // Formatear el mqtt_topic eliminando la parte '/mac/...'
        $topicBase = preg_replace('/\/mac\/[^\/]+$/', '', $mqttTopic);
        $this->info("Formatted MQTT topic: {$topicBase}");

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
            // Inserta en la tabla mqtt_send_server1
            MqttSendServer1::createRecord($topic, $message);

            // Inserta en la tabla mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);

            $this->info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables.");
        } catch (Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }
}
