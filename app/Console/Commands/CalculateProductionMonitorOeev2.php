<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\MonitorOee;
use Carbon\Carbon;
use App\Models\SensorCount;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
use App\Models\OrderStat;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList; // Asegúrate de que la ruta del modelo sea la correcta
use App\Models\ShiftHistory; // Asegúrate de que la ruta del modelo sea la correcta
use App\Models\OrderMac; // Asegúrate de que la ruta del modelo sea la correcta
use App\Services\OrderTimeService;
use App\Models\ProductList;

class CalculateProductionMonitorOeev2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:calculate-monitor-oee';
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

            $startTime = microtime(true); // Capturamos el inicio de la iteración
            // Obtener todos los registros de monitor_oee
            $monitors = MonitorOee::all();

            foreach ($monitors as $monitor) {
                //sacamos la linea de produccion
                $productionLineId = $monitor->production_line_id;
                $this->info("[" . Carbon::now()->toDateTimeString() . "] Iniciando el monitoreo de producción...Production Line: " . $productionLineId);


                //ahora por production_line_id sacamos en shift_history la ultima linea de la tabla shift_history usando production_line_id como filtro
                $lastShiftHistory = ShiftHistory::where('production_line_id', $productionLineId)
                    ->latest('created_at')
                    ->first();


                if ($lastShiftHistory && (
                    ($lastShiftHistory->type === 'stop' && $lastShiftHistory->action === 'end') ||
                    ($lastShiftHistory->type === 'shift' && $lastShiftHistory->action === 'start')
                )) {

                    //obtenemos el tiempo de cuando ha iniciado la orden quitando todo los de las pausas 
                    $timeData = $this->orderTimeService->getTimeOrder($productionLineId);
                    // $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo en segundos: {$timeData['timeOnSeconds']}");
                    //$this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo en formato: {$timeData['timeOnFormatted']}");
                    $orderTimeActivitySeconds  = $timeData['timeOnSeconds'];
                    $orderTimeActivityFormatted = $timeData['timeOnFormatted'];

                    //obtenemos el order en curso el ultimo por production_line_id
                    $currentOrder = OrderStat::where('production_line_id', $productionLineId)
                        ->latest()
                        ->first();
                    // Contar sensores cuyo sensor_type sea menor a 1
                    $countSensors = Sensor::where('sensor_type', '<', 1)
                        ->where('production_line_id', $productionLineId)
                        ->count();
                    
                    if($monitor->sensor_active == 1 ){

                        //obtenemos los sensores de la linea de produccion
                        $sensors = Sensor::where('production_line_id', $productionLineId)
                            ->get();
                        $totalUnitsShift = 0;
                        $totalUnitsWeek = 0;

                    // Sumar el downtime_count de los sensores con sensor_type = 1 (en segundos) y convertir a minutos
                    $totalDowntimeType1Seconds = $sensors->where('sensor_type', 1)->sum('downtime_count');
                    $totalDowntimeType1Minutes = floor($totalDowntimeType1Seconds / 60); // Convertir a minutos y redondear hacia abajo

                    // Sumar el downtime_count de los sensores con sensor_type = 2 (en segundos) y convertir a minutos
                    $totalDowntimeType2Seconds = $sensors->where('sensor_type', 2)->sum('downtime_count');
                    $totalDowntimeType2Minutes = floor($totalDowntimeType2Seconds / 60); // Convertir a minutos y redondear hacia abajo
                    
                        //ponemos un foreach para recorrer los sensores
                        foreach ($sensors as $sensor) {
                            
                            //separamos si es sensor_type= 0 o superior
                            if($sensor->sensor_type < 1){
                                //aqui sensores de conteo
                                
                                // Sumar las unidades producidas en este turno
                                $totalUnitsShift += $sensor->count_shift_1;
                                // Sumar las unidades producidas en esta semana
                                $totalUnitsWeek += $sensor->count_week_1;

                                $this->processSensorType0Data($sensor);
                                    
                            }else{
                                //aqui sensores que no son de conteo
                                $this->processSensorTypeNot0Data($sensor);
                            }
                           

                        }
                        //send diferencia de tiempo de turno
                        $this->calcDiffShiftTime($monitor);

                        //mandar mqtt con el tiempo de downtime de cada sensor_type superior a 0
                        $this->sendDowntimeToMQTT($monitor,$totalDowntimeType1Minutes,$totalDowntimeType2Minutes);  

                        //enviar downtime acumulado
                        //enviar datos a mqtt
                        $this->sendProductionDownTimeToMQTT($monitor,$currentOrder->down_time);

                        //llamar a la mqtt para enviar los datos de producción conteo semana y turno
                        $this->sendProductionDataToMQTT($totalUnitsShift, $totalUnitsWeek, $monitor);


                        //llamamos a un funciona para calcular el oee para sensores y le pasamos $orderTimeActivitySeconds  $currentOrder 
                        $this->calculateOeeForSensors($orderTimeActivitySeconds, $currentOrder, $countSensors, $monitor);

                    }else{
                        $this->info("[" . Carbon::now()->toDateTimeString() . "] Monitor Sensor desactivado para la Linea de production ".$productionLineId);
                    }
                    if($monitor->modbus_active == 1 ){
                        //obtenemos los modbuses de la linea de produccion  
                        $modbuses = Modbus::where('production_line_id', $productionLineId)
                            ->get();
                            //ponemos un foreach para recorrer los modbuses
                            foreach ($modbuses as $modbus) {

                                //separamos si es sensor_type= 0 o superior
                                if($modbus->model_type < 1){
                                    //procesamos basculas que son de final de linea conteo
                                    $this->processModbusType0Data($modbus);
                                }else{  
                                    //procesamos basculas que no son de final de linea pueden ser de rechazon etc
                                    $this->processModbusTypeNot0Data($modbus);
                                }
                            }
                    }else{
                        $this->info("[" . Carbon::now()->toDateTimeString() . "] Monitor Modbus desactivado para de la Linea de production ".$productionLineId);
                    }


                }else{
                    $this->info("[" . Carbon::now()->toDateTimeString() . "] No hay orden en curso para la Linea de production ".$productionLineId);
                }

            }
                        // Calculamos el tiempo transcurrido
                        $elapsed = microtime(true) - $startTime;
                        $sleepTime = 1 - $elapsed;
                
                        if ($sleepTime > 0) {
                            $this->info("[" . Carbon::now()->toDateTimeString() . "] Waiting for " . round($sleepTime, 3) . " seconds before the next run...");
                            usleep($sleepTime * 1000000); // usleep trabaja con microsegundos
                        } else {
                            $this->info("[" . Carbon::now()->toDateTimeString() . "] La iteración tomó {$elapsed} segundos, iniciando la siguiente sin pausa.");
                        }
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
    private function processSensorType0Data($sensor)
    {
        $this->info("[" . Carbon::now()->toDateTimeString() . "] Procesando sensor: {$sensor->name}");

    }
    private function processSensorTypeNot0Data($sensor)
    {
        $this->info("[" . Carbon::now()->toDateTimeString() . "] Procesando sensor: {$sensor->name}");

    }

    private function processModbusType0Data($modbus)
    {
        $this->info("[" . Carbon::now()->toDateTimeString() . "] Procesando modbus: {$modbus->name}");

    }
    private function processModbusTypeNot0Data($modbus)
    {
        $this->info("[" . Carbon::now()->toDateTimeString() . "] Procesando modbus: {$modbus->name}");

    }

    private function calculateOeeForSensors($orderTimeActivitySeconds, $currentOrder, $countSensors, $monitor)
    {
        try {
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Calculando OEE para sensores... Con tiempo total: {$orderTimeActivitySeconds} segundos");
            
            // Obtener datos de la orden
            $unitsMadeReal = $currentOrder ? $currentOrder->units_made_real : 0;
            $downTime = $currentOrder ? $currentOrder->down_time : 0;
            $productionStopTime = $currentOrder ? $currentOrder->production_stops_time : 0;
            
            // Validar que no se intente dividir por cero
            if ($unitsMadeReal <= 0) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] units_made_real es 0 o negativo, no se puede calcular secondsPerUnitReal.");
                $secondsPerUnitReal = 0;
            } else {
                $secondsPerUnitReal = $orderTimeActivitySeconds / $unitsMadeReal;
            }
            
            if ($countSensors <= 0) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] El número de sensores es 0 o negativo, no se puede calcular secondsPerUnitTheoretical.");
                $secondsPerUnitTheoretical = 0;
                $optimalProductionTime = 0;
            } else {
                // Buscar datos del producto y obtener el tiempo óptimo de producción
                $productList = ProductList::find($currentOrder->product_list_id);
                $optimalProductionTime = $productList ? $productList->optimal_production_time : 1000;
                $secondsPerUnitTheoretical = $optimalProductionTime / $countSensors;
            }
            
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades reales: {$unitsMadeReal}");
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Optimal production time: {$optimalProductionTime} segundos");
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Seconds per unit real: {$secondsPerUnitReal} segundos");
            
            // Para evitar problemas de precedencia, se usan paréntesis (verifica la fórmula deseada)
            if ($secondsPerUnitTheoretical > 0) {
                $unitsMadeTheoretical = ($orderTimeActivitySeconds - $downTime - $productionStopTime) / $secondsPerUnitTheoretical;
                $unitsMadeTheoreticalPerMinute = 60 / $secondsPerUnitTheoretical;
            } else {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] secondsPerUnitTheoretical es 0, no se pueden calcular las unidades teóricas.");
                $unitsMadeTheoretical = 0;
                $unitsMadeTheoreticalPerMinute = 0;
            }

            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades teóricas: {$unitsMadeTheoretical}");
            

            

            
            // Calcular la diferencia entre unidades teóricas y reales
            $unitsDelayed = $unitsMadeTheoretical - $unitsMadeReal;
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades teóricas: $unitsMadeTheoretical");
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades reales: $unitsMadeReal");
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades retrasadas: $unitsDelayed");
            // Calcular la diferencia entre unidades teóricas y reales para slow time , esto se quita tambien el tiempo de parada de sensores y no identificadas


            // Calcular OEE, validando que no se divida por cero
            if ($unitsMadeTheoretical > 0) {
                $oee = ($unitsMadeReal / $unitsMadeTheoretical) * 100;
                $oee = number_format($oee, 2);
            } else {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] No se pueden calcular OEE porque las unidades teóricas son 0.");
                $oee = 0;
            }
            
            // Calcular tiempos de finalización en función de las unidades pendientes
            $unitsPending = $currentOrder ? $currentOrder->units_pending : 0;
            $unitsMadeTheoreticalEnd = $unitsPending * $secondsPerUnitTheoretical;
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo finalización teórica para pendientes: $unitsMadeTheoreticalEnd segundos");
            $unitsMadeRealEnd = $unitsPending * $secondsPerUnitReal;
            
            // Calcular el slow time
            $unitsDelayedSForSlowTime = ($orderTimeActivitySeconds - $downTime - $productionStopTime) / $secondsPerUnitTheoretical;
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades retrasadas para slow time: $unitsDelayedSForSlowTime");
            $unitsDelayedSlowTime = $unitsDelayedSForSlowTime - ($unitsMadeReal * $secondsPerUnitTheoretical);
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo retrasado para slow time: $unitsDelayedSlowTime");
            $slowTime = $unitsDelayedSlowTime * $secondsPerUnitReal;

            //log slowtime
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Slow Time: {$slowTime} segundos");
            
            $this->info("[" . Carbon::now()->toDateTimeString() . "] OEE: {$oee}%");
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Bolsas teóricas: " . $unitsMadeTheoretical);
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Bolsas reales: " . $unitsMadeReal);
            
            // Actualizar la orden si existe
            if ($currentOrder) {
                $currentOrder->units_per_minute_real = $unitsMadeTheoretical;
                $currentOrder->units_per_minute_theoretical = $unitsMadeTheoreticalPerMinute;
                $currentOrder->seconds_per_unit_real = $secondsPerUnitReal;
                $currentOrder->seconds_per_unit_theoretical = $secondsPerUnitTheoretical;
                $currentOrder->units_made_theoretical = $unitsMadeTheoretical;
                $currentOrder->units_delayed = $unitsDelayed;
                $currentOrder->slow_time = $slowTime;
                $currentOrder->theoretical_end_time = $unitsMadeTheoreticalEnd;
                $currentOrder->real_end_time = $unitsMadeRealEnd;
                $currentOrder->oee = $oee;
                $currentOrder->save();
            }

        // Calcular el status basado en la producción real vs teórica
        $status = $this->calculateStatus($unitsMadeTheoretical, $unitsMadeTheoreticalPerMinute);

        // Comparar los valores reales con los teóricos (segundos por unidad)
        $status2 = $this->calculateStatus($secondsPerUnitReal, $secondsPerUnitTheoretical);

        // Calcular el status basado en las cajas reales vs teóricas
        $status3 = $this->calculateStatus($unitsMadeReal, $unitsMadeTheoretical);


        $realMessage = $this->preparedJsonValue(number_format($unitsMadeTheoretical, 2), $status);
        $realMessageMet02 = $this->preparedJsonValue($unitsMadeTheoretical > 0 ? number_format(60 / $unitsMadeTheoretical, 2) : 0, $status);
        $theoreticalMessage = $this->preparedJsonValue(number_format($unitsMadeTheoreticalPerMinute, 2), 0);
        $theoreticalMessageMet02 = $this->preparedJsonValue($unitsMadeTheoreticalPerMinute > 0 ? number_format(60 / $unitsMadeTheoreticalPerMinute, 2) : 0, 0);
        $realMessageMet03 = $this->preparedJsonValue(number_format($unitsMadeReal, 0), $status3);
        $theoreticalMessageMet03 = $this->preparedJsonValue(number_format($unitsMadeTheoretical, 0), 0);
        $theoreticalMessageMet03 = $this->preparedJsonValue(number_format($unitsMadeTheoretical, 0), 0);

        // Publicar mensajes para número de cajas fabricadas por minuto (real y teórica)
        $mqttTopicReal = $monitor->mqtt_topic . '-met01/real';
        $mqttTopicTeorica = $monitor->mqtt_topic . '-met01/teorica';

        // Publicar mensajes para segundos por cada caja (real y teórica)
        $mqttTopicRealMet02 = $monitor->mqtt_topic . '-met02/real';
        $mqttTopicTeoricaMet02 = $monitor->mqtt_topic . '-met02/teorica';

        // Publicar mensajes para cajas totales (real y teórica)
        $mqttTopicRealMet03 = $monitor->mqtt_topic . '-met03/real';
        $mqttTopicTeoricaMet03 = $monitor->mqtt_topic . '-met03/teorica';

        $jsonMonitorOEE=$this->preparedJsonValue($oee, 0);

        // Publicar mensajes MQTT
        $this->publishMqttMessage($monitor->topic_oee . '/monitor_oee', $jsonMonitorOEE);
        $this->publishMqttMessage($mqttTopicReal, $realMessage);
        $this->publishMqttMessage($mqttTopicTeorica, $theoreticalMessage);
        $this->publishMqttMessage($mqttTopicRealMet02, $realMessageMet02);
        $this->publishMqttMessage($mqttTopicTeoricaMet02, $theoreticalMessageMet02);
        $this->publishMqttMessage($mqttTopicRealMet03, $realMessageMet03);
        $this->publishMqttMessage($mqttTopicTeoricaMet03, $theoreticalMessageMet03);

        } catch (\Exception $e) {
            $this->error("[" . Carbon::now()->toDateTimeString() . "] Error al calcular OEE: " . $e->getMessage());
            // Aquí se puede decidir si continuar, registrar el error en un log adicional o realizar otras acciones
        }
    }
    
    private function sendProductionDataToMQTT($totalUnitsShift, $totalUnitsWeek, $monitor)
    {
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

    private function preparedJsonValue($value, $status)
    {
        $realMessage = json_encode([
            'value' => $value,
            'status' => $status
        ]);
        return $realMessage;
    }
    private function calculateStatus($actual, $theoretical)
    {
        // Evitar división por cero o comparaciones inválidas.
        if ($theoretical == 0) {
            return 0;
        }
        if ($actual >= $theoretical) {
            return 2;
        } elseif ($actual >= 0.8 * $theoretical) {
            return 1;
        }
        return 0;
    }


    private function sendProductionDownTimeToMQTT($monitor,$downTime)
    {
        //formateamos $downtime a 00:00.00
        $formattedDownTime = date('H:i:s', strtotime($downTime));
             // Calculamos la diferencia en segundos entre la hora actual y el inicio del turno
        $diff = $this-> calcDiffInSecondsFromTwoDates($monitor->time_start_shift,Carbon::now());

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

    }

    private function sendDowntimeToMQTT($monitor,$totalDowntimeType1Minutes,$totalDowntimeType2Minutes)
    {
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
    }

    private function calcDiffShiftTime($monitor)
    {
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
    }

    private function calcDiffInSecondsFromTwoDates($time1, $time2) {
        $diff = $time2->diffInSeconds(Carbon::parse($time1));
        return $diff;
    }
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Inserta en la tabla mqtt_send_server1
            MqttSendServer1::createRecord($topic, $message);

            // Inserta en la tabla mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);

            $this->info("[" . Carbon::now()->toDateTimeString() . "] Mensaje almacenado en mqtt_send_server1 y mqtt_send_server2 para el topic: {$topic}");
        } catch (\Exception $e) {
            Log::error("Error almacenando el mensaje en las bases de datos: " . $e->getMessage());
        }
    }
}
