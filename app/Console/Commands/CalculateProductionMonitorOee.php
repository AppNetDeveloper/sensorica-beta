<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\MonitorOee;
use Carbon\Carbon;
use App\Models\SensorCount;
use App\Models\OrderStat;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList; // Asegúrate de que la ruta del modelo sea la correcta
use App\Models\ShiftHistory; // Asegúrate de que la ruta del modelo sea la correcta
use App\Models\OrderMac; // Asegúrate de que la ruta del modelo sea la correcta
use App\Services\OrderTimeService;

class CalculateProductionMonitorOee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-monitor-oee-vieja';
    protected $orderTimeService;

    public function __construct(OrderTimeService $orderTimeService)
    {
        parent::__construct();
        $this->orderTimeService = $orderTimeService;
    }
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcular y gestionar el monitoreo de la producción para sensores y modbuses basado en las reglas de la tabla monitor_oee.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        while (true) {
            $this->info("Iniciando el monitoreo de producción...");

            // Obtener todos los registros de monitor_oee
            $monitors = MonitorOee::all();

            foreach ($monitors as $monitor) {
                //sacamos la linea de produccion
                $productionLineId = $monitor->production_line_id;
                //ahora por production_line_id sacamos en shift_history la ultima linea de la tabla shift_history usando production_line_id como filtro
                $lastShiftHistory = ShiftHistory::where('production_line_id', $monitor->production_line_id)
                    ->latest('created_at')
                    ->first();
                    //$timeData = $this->orderTimeService->getTimeOrder($productionLineId);
                   // $this->info("Tiempo en segundos: {$timeData['timeOnSeconds']}");
                    //$this->info("Tiempo en formato: {$timeData['timeOnFormatted']}");

                if ($lastShiftHistory && (
                    ($lastShiftHistory->type === 'stop' && $lastShiftHistory->action === 'end') ||
                    ($lastShiftHistory->type === 'shift' && $lastShiftHistory->action === 'start')
                )) {
                    $totalRealProductionPerMinute = 0;
                    $totalTheoreticalProductionPerMinute = 0;
    
                    // Si sensor_active es 1, obtener todos los sensores de la línea de producción con sensor_type = 0
                    if ($monitor->sensor_active == 1) {
                        $this->info("Obteniendo sensores con sensor_type = 0 para la línea de producción ID {$monitor->production_line_id}");
    
                        $result = Sensor::where('production_line_id', $monitor->production_line_id)
                        ->where('sensor_type', '!=', 0)
                        ->selectRaw('SUM(downtime_count) as total_downtime, SUM(count_order_0) as total_count_order')
                        ->first();
                       
                        
                        $numberSensorStop = $result->total_count_order;
    
                        // Obtener el último registro de order_stats para la línea de producción
                        $orderStat = OrderStat::where('production_line_id', $monitor->production_line_id)
                        ->latest('id') // Obtener el último registro
                        ->first();
    
                        if ($orderStat) {
                        $downTimeSensorStop = $orderStat->down_time; // Obtener el downtime acumulado
                        $this->info("Downtime: {$downTimeSensorStop}, Number of sensor stops: {$numberSensorStop}.");
                        } else {
                        $this->error("No order_stats record found for production line {$monitor->production_line_id}.");
                        $downTimeSensorStop = 0;
                        }
    
    
                        // Filtrar los sensores por production_line_id y sensor_type = 0
                        $sensors = Sensor::where('production_line_id', $monitor->production_line_id)
                            ->where('sensor_type', 0)
                            ->get();
    
                        foreach ($sensors as $sensor) {
                            // Procesar y acumular los datos por minuto para cada sensor
                            [$realProductionPerMinute, $theoreticalProductionPerMinute] = $this->processSensorData($sensor);
    
                            // Acumular la producción real y teórica por minuto
                            $totalRealProductionPerMinute += $realProductionPerMinute;
                            $totalTheoreticalProductionPerMinute += $theoreticalProductionPerMinute;
                        }
    
                        // Calcular la producción acumulada y enviar los mensajes MQTT
                        $this->sendAccumulatedProductionMessages($monitor, $totalRealProductionPerMinute, $totalTheoreticalProductionPerMinute);
                    } else {
                        $this->info("Cálculos de sensores omitidos para la línea de producción ID {$monitor->production_line_id} (sensor_active es 0).");
                    }
    
                    // Si modbus_active es 1, obtener todos los modbuses de la línea de producción
                    if ($monitor->modbus_active == 1) {
                        $this->info("Obteniendo modbuses para la línea de producción ID {$monitor->production_line_id}");
                        // Procesar modbuses (si es necesario)
                    } else {
                        $this->info("Cálculos de modbus omitidos para la línea de producción ID {$monitor->production_line_id} (modbus_active es 0).");
                    }
    
                    //calcular inactividad por turno total
                    $this->calcInactiveTimeShift($monitor, $downTimeSensorStop);
                    //calcular inactividad por otder total
                    $this->calcInactiveTimeOrder($monitor, $downTimeSensorStop, $numberSensorStop);
                    //calcula UDS semana y turno
                    $this->calcUdsShiftAndWeek($monitor);
                }

            }
            // Esperar 1 segundo antes de volver a ejecutar la lógica
            $this->info("Esperando 1 segundo antes de la siguiente ejecución...");
            sleep(1); // Pausar 1 segundo
        }

        return 0;
    }

/**
 * Calculates production monitoring for sensors and modbuses based on the rules in the monitor_oee table.
 *
 * This command continuously executes, checking all MonitorOee records and calculating production data accordingly.
 *
 * For each MonitorOee record, it checks if sensor_active is 1. If so, it retrieves all sensors with type = 0
 * from the corresponding production line ID, processes their data per minute, accumulates real and theoretical production,
 * and sends accumulated production messages to MQTT. It also calculates inactivity by turn total and UDS week/turn.
 */

    /**
     * Procesar los datos del sensor y devolver la producción real y teórica por minuto
     */
    private function processSensorData($sensor)
    {
        $this->info("Procesando sensor: {$sensor->name}");

        if ($sensor->barcoder_id) {
            if ($sensor->orderId) {
                $orderId = $sensor->orderId;
                //$this->info("OrderId extraído: {$orderId} ");

                // Extraer unic_code_order, optimal_production_time, y count_order_1 del sensor
                $unicCodeOrder = $sensor->unic_code_order;
                $optimalProductionTime = $sensor->optimal_production_time ?? 30; // Tiempo óptimo por defecto
                $countOrder1 = $sensor->count_order_1; // El valor real es count_order_1

                // Buscar en sensor_counts la primera línea que coincida con el orderId y unic_code_order
                $firstSensorCount = SensorCount::where('orderId', $orderId)
                    ->where('unic_code_order', $unicCodeOrder)
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($firstSensorCount) {
                    $this->info("Se encontró un registro coincidente en sensor_counts.");

                    // Obtener el tiempo de creación del primer registro
                    $createdAt = Carbon::parse($firstSensorCount->created_at);
                    $now = Carbon::now();

                    // Calcular la diferencia de tiempo en segundos
                    $timeWorkOrderFromShift = $now->diffInSeconds($createdAt);
                    $this->info("Diferencia de tiempo (timeWorkOrderFromShift): {$timeWorkOrderFromShift} segundos");

                    // Convertir el tiempo de trabajo en minutos
                    $timeWorkOrderInMinutes = $timeWorkOrderFromShift / 60;

                    // Calcular cuántas cajas se deberían haber producido teóricamente al 100%
                    $theoreticalBoxes = floor($timeWorkOrderFromShift / $optimalProductionTime);

                    // Validación: Evitar valores extremadamente bajos en producción teórica
                    if ($theoreticalBoxes <= 0) {
                        $theoreticalBoxes = 1; // Para evitar división por 0 o valores incorrectos
                    }

                    // Calcular producción por minuto (real vs teórico)
                    $realProductionPerMinute = $timeWorkOrderInMinutes > 0 ? $countOrder1 / $timeWorkOrderInMinutes : 0;
                    $theoreticalProductionPerMinute = $timeWorkOrderInMinutes > 0 ? $theoreticalBoxes / $timeWorkOrderInMinutes : 0;

                    // Mostrar información por sensor
                    $this->info("Sensor '{$sensor->name}' - Producción por minuto (real): {$realProductionPerMinute}, Producción por minuto (teórica): {$theoreticalProductionPerMinute}");

                    // Devolver la producción real y teórica por minuto para acumulación
                    return [$realProductionPerMinute, $theoreticalProductionPerMinute];
                } else {
                    $this->info("No se encontró ningún registro coincidente en sensor_counts para orderId: {$orderId} y unic_code_order: {$unicCodeOrder}");
                }
            } else {
                $this->info("No se encontró un orderId .");
            }
        } else {
            $this->info("No se encontró un barcoder para sensor id: {$sensor->barcoder_id}");
        }

        // Si no se encuentran datos, devolver 0
        return [0, 0];
    }
/**
 * Enviar los mensajes MQTT acumulados para cada línea de producción, incluyendo time_start_shift
 */
    private function sendAccumulatedProductionMessages($monitor, $totalRealProductionPerMinute, $totalTheoreticalProductionPerMinute)
    {
        $totalRealCajas = 0; // Para almacenar el número total de cajas fabricadas
        $totalTimeUsed = 0;  // Tiempo total usado para la producción
        $totalOptimalProductionTime = 0; // Para almacenar la suma de todos los tiempos óptimos

        // Obtener los sensores para la línea de producción
        $sensors = Sensor::where('production_line_id', $monitor->production_line_id)
            ->where('sensor_type', 0)
            ->get();

            foreach ($sensors as $sensor) {
                // Obtener el registro de order_stats para la línea de producción y la orden del sensor
                $orderStat = OrderStat::where('production_line_id', $sensor->production_line_id)
                    ->where('order_id', $sensor->orderId)
                    ->orderBy('created_at', 'asc')
                    ->first();
            
                if ($orderStat) {
                    // Obtener la fecha de inicio a partir del created_at de order_stats
                    $firstBoxTime = Carbon::parse($orderStat->created_at);
                    $now = Carbon::now();
            
                    // Calcular el tiempo usado para este sensor
                    $timeUsedForSensor = $now->diffInSeconds($firstBoxTime);
                    $totalTimeUsed += $timeUsedForSensor;
            
                    // Sumar el número de cajas fabricadas (valor que ya tiene el sensor)
                    $totalRealCajas += $sensor->count_order_1;
            
                    // Sumar el tiempo óptimo de producción (si no está definido se usa 30 como valor por defecto)
                    $totalOptimalProductionTime += $sensor->optimal_production_time ?? 30;
                }
            }
            

        
        // Obtener la suma de downtime_count para los sensores de la línea de producción
        $totalDowntimeCountSensorType0 = Sensor::where('production_line_id', $monitor->production_line_id)
                                    ->where('sensor_type', 0)
                                    ->sum('downtime_count');
        
        // Inicializar variables de contadores para cada tipo de tiempo
        $totalGreaterThanMaxTime = 0;
        $totalBetweenOptimalAndMaxTime = 0;
        $totalLessThanOrEqualToOptimalTime = 0;

        foreach ($sensors as $sensor) {
            // Sacamos el campo optimal_production_time y reduced_speed_time_multiplier en dos variables
            $optimalProductionTime = $sensor->optimal_production_time ?? 30; // Tiempo óptimo por defecto
            $reducedSpeedTimeMultiplier = $sensor->reduced_speed_time_multiplier ?? 2; // Multiplicador de velocidad reducida por defecto
            $maxTimeProductionPermited = $optimalProductionTime * $reducedSpeedTimeMultiplier;

            // Obtener todas las líneas de sensor_counts que coincidan con el sensor y la orden
            $sensorCounts = SensorCount::where('sensor_id', $sensor->id)
                                    ->where('unic_code_order', $sensor->unic_code_order)
                                    ->get();

            // Iterar sobre los resultados para clasificar el tiempo de producción
            foreach ($sensorCounts as $sensorCount) {
                
                if ($sensorCount->time_11 > $maxTimeProductionPermited) {
                    // Si el tiempo es mayor al tiempo máximo permitido
                    $totalGreaterThanMaxTime++;
                } elseif ($sensorCount->time_11 <= $maxTimeProductionPermited && $sensorCount->time_11 > $optimalProductionTime) {
                    // Si el tiempo es mayor al tiempo óptimo pero menor o igual al tiempo máximo permitido
                    $totalBetweenOptimalAndMaxTime++;
                } elseif ($sensorCount->time_11 <= $optimalProductionTime) {
                    // Si el tiempo es menor o igual al tiempo óptimo
                    $totalLessThanOrEqualToOptimalTime++;
                }
            }
        }

        // Calcular los valores de segundos por caja
        $realSecondsPerBox = $totalRealProductionPerMinute > 0 ? number_format(60 / $totalRealProductionPerMinute, 2) : 0;
        $theoreticalSecondsPerBox = $totalTheoreticalProductionPerMinute > 0 ? number_format(60 / $totalTheoreticalProductionPerMinute, 2) : 0;

        //sacar el orderstats
        $orderStats = $this->getOrderStatsByProductionLineId($monitor->production_line_id);
        
        $totalCajasTeoricas = $this->getTotalBoxForProdLineFromOrder($totalTheoreticalProductionPerMinute, $monitor->production_line_id);
        
        //OEE monitor
        $valueMonitorOEE=$this->calcMonitorOEE($totalCajasTeoricas, $totalRealCajas);
        $jsonMonitorOEE=$this->preparedJsonValue(ceil($valueMonitorOEE), 0);

        //Calcular diferencia entre inicio turno y ahorra

        $diff = $this-> calcDiffInSecondsFromTwoDates($monitor->time_start_shift,Carbon::now());

        // Convertimos el tiempo a segundos
        $shiftTimeInSeconds = $this->shiftTimeToSeconds();
        
        //si orderstats existe y es diferente de valueMonitorOEE lo actualizamos y mandamos el mensaje MQTT
        if (
            $orderStats && $diff > 0 && $diff < $shiftTimeInSeconds ||
            $orderStats && $diff > 0 && $orderStats->units_made_real != $totalRealCajas
        ) {
            // Validamos que los valores sean numéricos antes de realizar cálculos
            $units = is_numeric($orderStats->units) ? $orderStats->units : 0;
            $realCajas = is_numeric($totalRealCajas) ? $totalRealCajas : 0;
            $theoreticalSecondsPerBox = is_numeric($theoreticalSecondsPerBox) ? $theoreticalSecondsPerBox : 0;
            $realSecondsPerBox = is_numeric($realSecondsPerBox) ? $realSecondsPerBox : 0;
            $totalDowntimeCountSensorType0 = is_numeric($totalDowntimeCountSensorType0) ? $totalDowntimeCountSensorType0 : 0;
            $totalLessThanOrEqualToOptimalTime = is_numeric($totalLessThanOrEqualToOptimalTime) ? $totalLessThanOrEqualToOptimalTime : 0;
            $totalBetweenOptimalAndMaxTime = is_numeric($totalBetweenOptimalAndMaxTime) ? $totalBetweenOptimalAndMaxTime : 0;
            $totalGreaterThanMaxTime = is_numeric($totalGreaterThanMaxTime) ? $totalGreaterThanMaxTime : 0;
        
            // Guardamos cambios
            $orderStats->oee = $valueMonitorOEE;
           // $orderStats->units_made_real = $realCajas;
            $orderStats->units_made_theoretical = $totalCajasTeoricas;
            $orderStats->units_per_minute_real = $totalRealProductionPerMinute;
            $orderStats->units_per_minute_theoretical = $totalTheoreticalProductionPerMinute;
            $orderStats->seconds_per_unit_real = $realSecondsPerBox;
            $orderStats->seconds_per_unit_theoretical = $theoreticalSecondsPerBox;
           // $orderStats->units_made = $realCajas;
           // $orderStats->units_pending = $units - $realCajas;
            $orderStats->units_delayed = $totalCajasTeoricas - $realCajas;
           // $orderStats->production_stops_time = floor($totalDowntimeCountSensorType0 / 60);
            $orderStats->fast_time = $totalLessThanOrEqualToOptimalTime;
            $orderStats->slow_time = $totalBetweenOptimalAndMaxTime;
            $orderStats->out_time = $totalGreaterThanMaxTime;
        
            // Calculamos valores teóricos y reales de finalización
            $orderStats->theoretical_end_time = (($units - $realCajas) * $theoreticalSecondsPerBox) / 60;
            $orderStats->real_end_time = (($units - $realCajas) * $realSecondsPerBox) / 60;
        
            $orderStats->save();
        
            // Publicar mensajes MQTT
            $this->publishMqttMessage($monitor->topic_oee . '/monitor_oee', $jsonMonitorOEE);
        }
        
        


        //----------------
        // Calcular el status basado en la producción real vs teórica
        $status = 0;
        if ($totalRealProductionPerMinute >= $totalTheoreticalProductionPerMinute) {
            $status = 2; // Real es igual o mayor al teórico
        } elseif ($totalRealProductionPerMinute >= 0.8 * $totalTheoreticalProductionPerMinute) {
            $status = 1; // Real es 80% o más del teórico
        }

        

        // Inicializar el estado
        $status2 = 0;

        // Comparar los valores reales con los teóricos
        if ($realSecondsPerBox >= $theoreticalSecondsPerBox) {
            $status2 = 2; // Real es igual o mayor al teórico
        } elseif ($realSecondsPerBox >= 0.8 * $theoreticalSecondsPerBox) {
            $status2 = 1; // Real es 80% o más del teórico
        }

        // Calcular el status basado en la met03 cajas reales vs cajas teoreticos
        $status3 = 0;
        if ($totalRealCajas >= $totalCajasTeoricas) {
            $status3 = 2; // Real es igual o mayor al teórico
        } elseif ($totalRealCajas >= 0.8 * $totalCajasTeoricas) {
            $status3 = 1; // Real es 80% o más del teórico
        }

        // Preparar el JSON para el mensaje 'real' (cajas por minuto)
        $realMessage = json_encode([
            'value' => number_format($totalRealProductionPerMinute, 2),
            'status' => $status
        ]);

        // Preparar el JSON para el mensaje segundos por cada caja real
        $realMessageMet02 = json_encode([
            'value' => $totalRealProductionPerMinute > 0 ? number_format(60 / $totalRealProductionPerMinute, 2) : 0,  // Segundos por caja real
            'status' => $status
        ]); 

        // Preparar el JSON para el mensaje 'teorica' (cajas por minuto)
        $theoreticalMessage = json_encode([
            'value' => number_format($totalTheoreticalProductionPerMinute, 2)
        ]);

        // Preparar el JSON para segundos por cada caja teórica
        $theoreticalMessageMet02 = json_encode([
            'value' => $totalTheoreticalProductionPerMinute > 0 ? number_format(60 / $totalTheoreticalProductionPerMinute, 2) : 0  // Segundos por caja teórica
        ]);

        // Preparar el JSON para el mensaje real (número de cajas fabricadas)
        $realMessageMet03 = json_encode([
            'value' => number_format($totalRealCajas, 0),
            'status' => $status3
        ]);

        // Preparar el JSON para el mensaje teórico (número de cajas que se deberían haber fabricado)
        $theoreticalMessageMet03 = json_encode([
            'value' => number_format($totalCajasTeoricas, 0)
        ]);



        // Publicar mensajes para número de cajas fabricadas por minuto (real y teórica)
        $mqttTopicReal = $monitor->mqtt_topic . '-met01/real';
        $mqttTopicTeorica = $monitor->mqtt_topic . '-met01/teorica';

        // Publicar mensajes para segundos por cada caja (real y teórica)
        $mqttTopicRealMet02 = $monitor->mqtt_topic . '-met02/real';
        $mqttTopicTeoricaMet02 = $monitor->mqtt_topic . '-met02/teorica';

        // Publicar mensajes para cajas totales (real y teórica)
        $mqttTopicRealMet03 = $monitor->mqtt_topic . '-met03/real';
        $mqttTopicTeoricaMet03 = $monitor->mqtt_topic . '-met03/teorica';

        // Publicar mensajes MQTT
        $this->publishMqttMessage($mqttTopicReal, $realMessage);
        $this->publishMqttMessage($mqttTopicTeorica, $theoreticalMessage);
        $this->publishMqttMessage($mqttTopicRealMet02, $realMessageMet02);
        $this->publishMqttMessage($mqttTopicTeoricaMet02, $theoreticalMessageMet02);
        $this->publishMqttMessage($mqttTopicRealMet03, $realMessageMet03);
        $this->publishMqttMessage($mqttTopicTeoricaMet03, $theoreticalMessageMet03);

        // ----------------------
        // Nueva lógica para mqtt_topic2 + time_start_shift (timestamp) en minutos sin decimales
        // ----------------------

        // Calcular la diferencia en minutos desde time_start_shift hasta ahora
        if ($monitor->time_start_shift) {
            $timeStartShift = Carbon::createFromTimestamp(strtotime($monitor->time_start_shift));
            $shiftTimeDifferenceMinutes = Carbon::now()->diffInMinutes($timeStartShift); // Diferencia en minutos, sin decimales

            // Preparar los mensajes para mqtt_topic2
            $realShiftMessage = json_encode([
                'value' => $shiftTimeDifferenceMinutes,
                'status' => 2 // El status es por defecto 2
            ]);

            // Publicar los mensajes MQTT en mqtt_topic2 para los valores 'real'
            $mqttTopic2Real = $monitor->mqtt_topic2 . '-met01/real';
            $mqttTopic2Real2 = $monitor->mqtt_topic2 . '-met02/real';

            $this->publishMqttMessage($mqttTopic2Real, $realShiftMessage);
            $this->publishMqttMessage($mqttTopic2Real2, $realShiftMessage);

            // Log para verificar la publicación de mensajes
            $this->info("Real (time_start_shift en minutos): {$realShiftMessage} en {$mqttTopic2Real}");
            $this->info("Real2 (time_start_shift en minutos): {$realShiftMessage} en {$mqttTopic2Real2}");

            // --------- Calcular el valor teórico para sensores con sensor_type 1 y 2 ---------
            // Obtener todos los sensores asociados a la línea de producción
            $sensors = Sensor::where('production_line_id', $monitor->production_line_id)->get();

            // Sumar el downtime_count de los sensores con sensor_type = 1 (en segundos) y convertir a minutos
            $totalDowntimeType1Seconds = $sensors->where('sensor_type', 1)->sum('downtime_count');
            $totalDowntimeType1Minutes = floor($totalDowntimeType1Seconds / 60); // Convertir a minutos y redondear hacia abajo

            // Sumar el downtime_count de los sensores con sensor_type = 2 (en segundos) y convertir a minutos
            $totalDowntimeType2Seconds = $sensors->where('sensor_type', 2)->sum('downtime_count');
            $totalDowntimeType2Minutes = floor($totalDowntimeType2Seconds / 60); // Convertir a minutos y redondear hacia abajo

            // Preparar el JSON para el mensaje teórico de tipo 1 (met01/teorica)
            $theoreticalShiftMessageMet01 = json_encode([
                'value' => $totalDowntimeType1Minutes // Valor del downtime en minutos
            ]);

            // Preparar el JSON para el mensaje teórico de tipo 2 (met02/teorica)
            $theoreticalShiftMessageMet02 = json_encode([
                'value' => $totalDowntimeType2Minutes // Valor del downtime en minutos
            ]);

            // Publicar los mensajes MQTT en mqtt_topic2 para los valores 'teorica'
            $mqttTopic2TeoricaMet01 = $monitor->mqtt_topic2 . '-met01/teorica';
            $mqttTopic2TeoricaMet02 = $monitor->mqtt_topic2 . '-met02/teorica';

            $this->publishMqttMessage($mqttTopic2TeoricaMet01, $theoreticalShiftMessageMet01);
            $this->publishMqttMessage($mqttTopic2TeoricaMet02, $theoreticalShiftMessageMet02);

            // Log para verificar la publicación de mensajes teóricos
            $this->info("Teorico (downtime_count en minutos, sensor_type 1): {$theoreticalShiftMessageMet01} en {$mqttTopic2TeoricaMet01}");
            $this->info("Teorico (downtime_count en minutos, sensor_type 2): {$theoreticalShiftMessageMet02} en {$mqttTopic2TeoricaMet02}");
        }


        // Log para verificar la publicación de otros mensajes
        $this->info("Mensajes publicados para la línea de producción {$monitor->production_line_id}:");
        $this->info("Real (cajas/minuto): {$realMessage} en {$mqttTopicReal}");
        $this->info("Teorica (cajas/minuto): {$theoreticalMessage} en {$mqttTopicTeorica}");
    }

    public function calcInactiveTimeShift($monitor, $downTime) {
        //sacar todo el tiempo de inactividad de la linea de produccion si no es null
        if ($monitor->production_line_id != null) {

             // Convertimos el tiempo de inactividad en formato de horas, minutos y segundos (H:i:s)
             $formattedDownTime = gmdate("H:i:s", $downTime);
             $this->info("Tiempo de inactividad: " . $formattedDownTime);


             // Calculamos la diferencia en segundos entre la hora actual y el inicio del turno
             $diff = $this-> calcDiffInSecondsFromTwoDates($monitor->time_start_shift,Carbon::now());

             // Aseguramos que el tiempo total del turno no sea cero para evitar división por cero
             if ($diff > 0) {
                 // Calculamos el porcentaje de inactividad (downtime)
                 $downtime_percentage = ($downTime / $diff) * 100;

                 // Determinamos el status basado en el porcentaje de inactividad
                 if ($downtime_percentage >= 40) {
                     $status = 0; // Downtime mayor o igual a 40%
                 } elseif ($downtime_percentage >= 20) {
                     $status = 1; // Downtime mayor o igual a 20% y menor que 40%
                 } else {
                     $status = 2; // Downtime menor que 20%
                 }

                 $this->info("Downtime: $formattedDownTime, Porcentaje de downtime: $downtime_percentage%, Status: $status");

                 // Creamos el JSON con el downtime acumulado y el status correspondiente
                 $json = json_encode(['value' => $formattedDownTime, 'status' => $status]);

                 // Obtenemos el valor original del mqtt_topic desde monitor_oee
                 $mqtt_topicKpi1 = $monitor->mqtt_topic;

                 // Expresión regular para eliminar todo lo que esté entre /sta/ y /metrics/
                 $pattern = '/\/sta\/.*\/metrics\/.*$/';

                 // Reemplazamos la parte que coincide con la expresión regular por la nueva parte
                 $mqtt_topic_modified = preg_replace($pattern, '', $mqtt_topicKpi1);

                 // Añadimos la nueva parte que necesitamos
                 $mqtt_topic_modified .= '/kpi1Value';

                 // Publicar el mensaje MQTT con el tópico modificado
                 $this->publishMqttMessage($mqtt_topic_modified, $json);

             } else {
                 $this->info("El tiempo total del turno es cero o no válido.");
             }
         }
    }
    //estoy aqui para sequir
    public function calcInactiveTimeOrder($monitor, $downTimeSensorStop, $numberSensorStop) {
        //sacar todo el tiempo de inactividad de la linea de produccion si no es null
        if ($monitor->production_line_id != null) {

             // Convertimos el tiempo de inactividad en formato de horas, minutos y segundos (H:i:s)
             $formattedDownTimeSensorStop = gmdate("H:i:s", $downTimeSensorStop);
             $this->info("Tiempo de inactividad: " . $formattedDownTimeSensorStop);

             // Calculamos la diferencia en segundos entre la hora actual y el inicio del turno
             $diff = $this-> calcDiffInSecondsFromTwoDates($monitor->time_start_shift,Carbon::now());
            
            
             // Aseguramos que el tiempo total del turno no sea cero para evitar división por cero
             if ($diff > 0) {
                 // Calculamos el porcentaje de inactividad (downtime)
                 $downtime_percentage = ($downTimeSensorStop / $diff) * 100;

                 // Determinamos el status basado en el porcentaje de inactividad
                 if ($downtime_percentage >= 40) {
                     $status = 0; // Downtime mayor o igual a 40%
                 } elseif ($downtime_percentage >= 20) {
                     $status = 1; // Downtime mayor o igual a 20% y menor que 40%
                 } else {
                     $status = 2; // Downtime menor que 20%
                 }

                 $this->info("Downtime: $formattedDownTimeSensorStop, Porcentaje de downtime: $downtime_percentage%, Status: $status");


                 //actualizamos en la tabla order_stats por el production_line_id  ultima linea de la tabla
                 //pero ponemos un si el turno ya tiene empezado mas del tiempo de SHIFT_TIME de .env ya no se actualiza

                // Convertimos el tiempo a segundos
                $shiftTimeInSeconds = $this->shiftTimeToSeconds();

                $orderStats = $this->getOrderStatsByProductionLineId($monitor->production_line_id);

                // Luego, comparas con $diff que ya está en segundos
                if ($diff <= $shiftTimeInSeconds) {
                     //ahora actualizamos el tiempo de inactividad sumado  que es el valor en order_stats - sensor_stops_time que tenemos que introducir $formattedDownTime
                     // Supongamos que $formattedDownTime está en el formato "00:00:00" (hh:mm:ss) lo traducimos a minutos
                    list($hours, $minutes, $seconds) = explode(':', $formattedDownTimeSensorStop);

                    // Convertimos todo a minutos
                    $inactiveTimeInMinutes = ($hours * 60) + $minutes + ($seconds / 60);

                    if ($orderStats) {
                        // Solo actualizamos si el valor es diferente
                        if ($orderStats->sensor_stops_time != $inactiveTimeInMinutes) {
                            // Actualizamos el tiempo de inactividad sumado en minutos y si es diferente se actualiza en caso contrario no se actualiza. Ponemos 1 en stop active para poner  que ya se ha contado 
                            $orderStats->sensor_stops_time = $inactiveTimeInMinutes;
                            $orderStats->sensor_stops_count = $numberSensorStop;
                            $orderStats->save();
                            $this->info("El valor de sensor_stops_time ha sido actualizado.");
                        } else {
                            // Si el valor es el mismo, no hacemos nada
                            $this->info("El valor de sensor_stops_time ya es el mismo que inactiveTimeInMinutes, no se necesita actualización.");
                        }
                    } else {
                        // Si no hay registro, mostramos un mensaje de advertencia o manejamos el caso
                        $this->info("No se encontró un registro en la tabla order_stats para production_line_id: " . $monitor->production_line_id);
                    }

                }else{
                    $this->info("El tiempo total del turno es menor al tiempo deSHIFT_TIME");
                }

             } else {
                 $this->info("El tiempo total del turno es cero o no válido.");
             }
         }
    }
    public function calcUdsShiftAndWeek($monitor)
    {
        // Inicializar contadores de unidades
        $totalUnitsShift = 0;
        $totalUnitsWeek = 0;
    
        // Obtener el ID de la línea de producción desde el monitor
        $production_line_id = $monitor->production_line_id;
    
        // Buscar todos los sensores asociados a la línea de producción
        $sensors = Sensor::where('production_line_id', $production_line_id)->get();
    
        // Validar si se encontraron sensores
        if ($sensors->isEmpty()) {
            $this->info("No se encontraron sensores para la línea de producción con ID: $production_line_id.");
            Log::warning("No se encontraron sensores para la línea de producción con ID: $production_line_id.");
            return;
        }
    
        // Procesar cada sensor encontrado
        foreach ($sensors as $sensor) {
            // Extraer valores de sensor
            $unitsPerBox = isset($sensor->uds) && is_numeric($sensor->uds) ? (int) $sensor->uds : 0;
            // Sumar las unidades producidas en este turno
            $totalUnitsShift += $sensor->count_shift_1;
            // Sumar las unidades producidas en esta semana
            $totalUnitsWeek += $sensor->count_week_1;
        }
    
        // Cálculo de cajas (paquetes) completas por turno y semana
        if ($unitsPerBox && $unitsPerBox > 0) {
            $boxesShift = floor($totalUnitsShift / $unitsPerBox); // Cajas completas en el turno
            $boxesWeek = floor($totalUnitsWeek / $unitsPerBox);   // Cajas completas en la semana
        } else {
            $this->info("Las unidades por caja (unitsPerBox) no están definidas o son inválidas.");
            return;
        }
    
        // Devolvemos o guardamos los resultados en una tabla o un log si es necesario
        $this->info("Unidades producidas en el turno: $totalUnitsShift Cajas producidas en el turno: $boxesShift Unidades producidas en la semana: $totalUnitsWeek Cajas producidas en la semana: $boxesWeek");
        

        // Crear los JSON con el downtime acumulado y el status correspondiente
        $jsonShift = json_encode(['value' => $totalUnitsShift, 'status' => 2]);
        $jsonWeek = json_encode(['value' => $totalUnitsWeek, 'status' => 2]);
    
        // Obtener el valor original del mqtt_topic desde monitor_oee
        $mqtt_topicKpi1 = $monitor->mqtt_topic;
    
        // Expresión regular para eliminar todo lo que esté entre /sta/ y /metrics/
        $pattern = '/\/sta\/.*\/metrics\/.*$/';
        $mqtt_topic_modified = preg_replace($pattern, '', $mqtt_topicKpi1);
    
        // Publicar el mensaje MQTT para el KPI 2 (por semana)
        $mqtt_topic_kpi2 = $mqtt_topic_modified . '/kpi2Value';
        $this->publishMqttMessage($mqtt_topic_kpi2, $jsonWeek);
    
        // Publicar el mensaje MQTT para el KPI 3 (por turno)
        $mqtt_topic_kpi3 = $mqtt_topic_modified . '/kpi3Value';
        $this->publishMqttMessage($mqtt_topic_kpi3, $jsonShift);
    }
    
    

    private function calcMonitorOEE($totalCajasTeoricas, $totalCajasReales) 
    {
        if (!is_numeric($totalCajasTeoricas) || !is_numeric($totalCajasReales)) {
            return 0; // o manejar el error
        }
        if ($totalCajasTeoricas == 0) {
            return 0;
        }
        $oee = ($totalCajasReales / $totalCajasTeoricas) * 100;

        // Si el valor es menor que 100, mostrar con 2 decimales
        if ($oee < 100) {
            return number_format($oee, 2); // Máximo 2 decimales
        }

        // Si el valor es 100 o mayor, mostrar solo el entero
        return intval($oee); // Solo el entero
    }
    

    private function getOrderStatsByProductionLineId($production_line_id)
    {
        // Buscamos por production_line_id la última línea de la tabla order_stats
        $orderStats = OrderStat::where('production_line_id', $production_line_id)
                                ->orderBy('id', 'desc')
                                ->first();

        //si el order_stats no existe lo ponemos en 0
        if (!$orderStats) {
            return 0;
        }else{
            return $orderStats;
        }  
    }
    /**
     * Publicar el mensaje MQTT en los servidores
     */
    private function preparedJsonValue($value, $status)
    {
        $realMessage = json_encode([
            'value' => $value,
            'status' => $status
        ]);
        return $realMessage;
    }
    private function getTotalBoxForProdLineFromOrder($totalTheoreticalProductionPerMinute, $productionLineId )
    {

        $timeData = $this->orderTimeService->getTimeOrder($productionLineId);
        //$this->info("Tiempo en segundos: {$timeData['timeOnSeconds']}");
        //$this->info("Tiempo en formato: {$timeData['timeOnFormatted']}");
        $timeOnSeconds = $timeData['timeOnSeconds'];
        $totalEffectiveMinutes =$timeOnSeconds / 60;
        
        // Calcular el total de cajas teóricas: producción teórica por minuto * minutos efectivos
        $totalCajasTeoricas = $totalTheoreticalProductionPerMinute * $totalEffectiveMinutes;

        return $totalCajasTeoricas;
    }


    //vuelve a mirar esto porque posiblemente puedes volver a mejorar todo
    private function shiftTimeToSeconds() {
        $shiftTime = env('SHIFT_TIME', '08:00:00'); // '00:00:00' es el valor por defecto si no se define en el archivo .env
        list($hours, $minutes, $seconds) = explode(':', $shiftTime);

        // Convertimos el tiempo a segundos
        $shiftTimeInSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
        return $shiftTimeInSeconds;
    }

    private function calcDiffInSecondsFromTwoDates($time1, $time2) {
        $diff = $time2->diffInSeconds(Carbon::parse($time1));
        return $diff;
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
