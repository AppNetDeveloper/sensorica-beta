<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\MonitorOee;
use Carbon\Carbon;
use App\Models\OrderStat;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftHistory; // Asegúrate de que la ruta del modelo sea la correcta
use App\Services\OrderTimeService;
use App\Models\ProductList;
use App\Models\OptimalProductionTime;
use App\Models\OptimalSensorTime;
use App\Models\ProductionOrder;
use App\Models\ProductionLine;
use App\Models\SensorCount;
use App\Models\DowntimeSensor;

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
                   $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo en segundos: {$timeData['timeOnSeconds']}");
                    //$this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo en formato: {$timeData['timeOnFormatted']}");
                   // $orderTimeActivitySeconds  = $timeData['timeOnSeconds'];
                   // $orderTimeActivityFormatted = $timeData['timeOnFormatted'];

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


                        $this->calculateOeeForSensors( $currentOrder, $countSensors, $monitor);

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

    private function calculateOeeForSensors($currentOrder, $countSensors, $monitor)
    {
        try {
            // Validar que los parámetros de entrada sean válidos
            if (!$currentOrder || !is_object($currentOrder)) {
                throw new \Exception("Orden actual no válida");
            }
            
            //sacamos con un try  $productionOrder = ProductionOrder::where('order_id', $currentOrder->order_id)->first();
            try {
                $productionOrder = ProductionOrder::where('order_id', $currentOrder->order_id)->first();
            } catch (\Exception $e) {
                $this->error("Error al obtener el production_order: " . $e->getMessage());
                $productionOrder = null;
            }
            // Inicializar variables con valores por defecto
            $oee = $oeeModbus = $oeeRfid = $unitsMadeTheoretical = 0;
            $secondsPerUnitReal = $secondsPerUnitTheoretical = 0;
            $unitsMadeReal = $currentOrder->units_made_real ?? 0;
            $downTime = $currentOrder->down_time ?? 0;
            $prepairTime = $currentOrder->prepair_time ?? 0;
            $productionStopTime = $currentOrder->production_stops_time ?? 0;
            
            // Validar y obtener el tiempo de actividad
            $orderTimeActivitySeconds = (int)($currentOrder->on_time ?? 0) + 1;
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Calculando OEE para orden #{$currentOrder->id} con tiempo total: {$orderTimeActivitySeconds} segundos");

            //obtenemos el ultimos regustro del shift_history por linea y con type= shift y action=start
            $shiftHistory = ShiftHistory::where('production_line_id', $currentOrder->production_line_id)
                ->where('type', 'shift')
                ->where('action', 'start')
                ->orderBy('id', 'desc')
                ->first();
            
            // Validar y calcular secondsPerUnitReal
            $numerator = $orderTimeActivitySeconds - ($downTime + $productionStopTime + $prepairTime);
            if ($unitsMadeReal <= 0 || $numerator <= 0) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] No se puede calcular secondsPerUnitReal. Unidades reales: {$unitsMadeReal}, Tiempo neto: {$numerator}");
                $secondsPerUnitReal = 0;
            } else {
                $secondsPerUnitReal = max(0, $numerator / $unitsMadeReal); // Aseguramos que no sea negativo
                $this->info("[" . Carbon::now()->toDateTimeString() . "] secondsPerUnitReal calculado: {$secondsPerUnitReal} segundos (tiempo: {$numerator} / unidades: {$unitsMadeReal})");
            }
            
            if ($countSensors <= 0) {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] El número de sensores es 0 o negativo, no se puede calcular secondsPerUnitTheoretical.");
                $secondsPerUnitTheoretical = 0;
                $optimalProductionTime = 0;
            } else {
                // Buscar datos del producto y obtener el tiempo óptimo de producción
                $productList = ProductList::find($currentOrder->product_list_id);
                //sacamos de optimal_production_times con un where production_line_id = $productList->production_line_id y 
                // sensor_type= 0 y product_list_id = $currentOrder->product_list_id y sumamos de las lineas que se quedan
                //el optimal_time y partimos por numero de lineas encontradas 

                $optimalProductionTime = $productList ? $productList->optimal_production_time : 1000;
                try {
                    $optimalTimes = OptimalSensorTime::where('production_line_id', $currentOrder->production_line_id)
                        ->where('sensor_type', 0)
                        ->where('product_list_id', $currentOrder->product_list_id)
                        ->whereNotNull('sensor_id')
                        ->get();
                
                    $totalOptimalTime = $optimalTimes->sum('optimal_time');
                    $linesCount = $optimalTimes->count();
                    $this->info('Total optimal time: ' . $totalOptimalTime);
                    $this->info('Lines count: ' . $linesCount);
                    if ($linesCount === 0) {
                        throw new \Exception('No optimal times found.');
                    }
                
                    $secondsPerUnitTheoretical = ($totalOptimalTime / $linesCount) / $linesCount;
                    $this->info('Seconds per unit theoretical: ' . $secondsPerUnitTheoretical);
                
                } catch (\Exception $e) {
                    // Usamos el fallback si algo falla en la lógica anterior
                    $secondsPerUnitTheoretical = $optimalProductionTime / $countSensors;
                
                    // Opcionalmente puedes registrar el error
                    $this->error("[" . Carbon::now()->toDateTimeString() . "]Error calculating secondsPerUnitTheoretical: " . $e->getMessage());
                }

            }
            
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades reales: {$unitsMadeReal}");
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Optimal production time: {$optimalProductionTime} segundos");
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Seconds per unit real: {$secondsPerUnitReal} segundos");
            
            // Para evitar problemas de precedencia, se usan paréntesis (verifica la fórmula deseada)
            if ($secondsPerUnitTheoretical > 0) {
                $unitsMadeTheoretical = ($orderTimeActivitySeconds - ($downTime + $productionStopTime)) / $secondsPerUnitTheoretical;
                $unitsMadeTheoreticalPerMinute = 60 / $secondsPerUnitTheoretical;
            } else {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] secondsPerUnitTheoretical es 0, no se pueden calcular las unidades teóricas.");
                $unitsMadeTheoretical = 0;
                $unitsMadeTheoreticalPerMinute = 0;
            }

            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades teóricas: {$unitsMadeTheoretical}");
            

            

            
            // Calcular la diferencia entre unidades teóricas y reales
            $unitsDelayed = max(0, $unitsMadeTheoretical - $unitsMadeReal);
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades retrasadas: {$unitsDelayed} (Teóricas: {$unitsMadeTheoretical}, Reales: {$unitsMadeReal})");
            
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades teóricas: $unitsMadeTheoretical");
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades reales: $unitsMadeReal");
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades retrasadas: $unitsDelayed");
            // Calcular la diferencia entre unidades teóricas y reales para slow time , esto se quita tambien el tiempo de parada de sensores y no identificadas


            // Validar y calcular OEE
            $oee = 0;
            if ($unitsMadeTheoretical > 0 && $unitsMadeReal >= 0) {
                $oeeValue = ($unitsMadeReal / $unitsMadeTheoretical) * 100;
                $oee = number_format(min(100, max(0, $oeeValue)), 2); // Asegurar que esté entre 0 y 100
                $this->info("[" . Carbon::now()->toDateTimeString() . "] OEE calculado: {$oee}% (Real: {$unitsMadeReal}, Teórico: {$unitsMadeTheoretical})");
            } else {
                $this->error("[" . Carbon::now()->toDateTimeString() . "] No se pueden calcular OEE. Unidades reales: {$unitsMadeReal}, Unidades teóricas: {$unitsMadeTheoretical}");
            }
            
            // Calcular tiempos de finalización en función de las unidades pendientes
            $unitsPending = $currentOrder ? $currentOrder->units_pending : 0;
            $unitsMadeTheoreticalEnd = $unitsPending * $secondsPerUnitTheoretical;
           // $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo finalización teórica para pendientes: $unitsMadeTheoreticalEnd segundos");
            $unitsMadeRealEnd = $unitsPending * $secondsPerUnitReal;
            
            // Calcular el slow time
            $unitsDelayedSForSlowTime = $secondsPerUnitTheoretical > 0 ? ($orderTimeActivitySeconds - $downTime - $productionStopTime) / $secondsPerUnitTheoretical : 0;
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Unidades retrasadas para slow time: $unitsDelayedSForSlowTime");
            $unitsDelayedSlowTime = $unitsDelayedSForSlowTime - ($unitsMadeReal * $secondsPerUnitTheoretical);
            //$this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo retrasado para slow time: $unitsDelayedSlowTime");


            $slowTime = $unitsDelayed * $secondsPerUnitTheoretical;

            


            //log slowtime
            $this->info("[" . Carbon::now()->toDateTimeString() . "] Slow Time: {$slowTime} segundos, Calculo por {$secondsPerUnitTheoretical} * {$unitsDelayed} unidades");

            $oeeModbus = $currentOrder->oee_modbus ?? 0;
            $oeeRfid = $currentOrder->oee_rfid ?? 0;

            // Inicializamos el contador con 1 para el oee principal.
            $monitorOee = 1;

            if ($oeeModbus > 0) {
                $monitorOee++;
            }

            if ($oeeRfid > 0) {
                $monitorOee++;
            }

            // Calcular OEE total con validación
            $totalOee = 0;
            if ($monitorOee > 0) {
                $totalOee = ($oee + $oeeModbus + $oeeRfid) / $monitorOee;
                $totalOee = min(100, max(0, $totalOee)); // Asegurar que esté entre 0 y 100
            }
            $this->info("[" . Carbon::now()->toDateTimeString() . "] OEE Total calculado: {$totalOee}% (OEE: {$oee}%, Modbus: {$oeeModbus}%, RFID: {$oeeRfid}%)");
            // Actualizar la orden si existe
            if ($currentOrder) {
                // Inicializar $slowTimeDif para evitar errores de variable indefinida
                $slowTimeDif = 0;

                // Asegurarse de que $unitsMadeTheoretical sea un valor numérico válido
                $unitsMadeTheoretical = is_numeric($unitsMadeTheoretical) ? $unitsMadeTheoretical : 0;
                $unitsMadeTheoretical = ($totalOee / 100) * $unitsMadeTheoretical;
                $currentOrder->units_per_minute_real = $unitsMadeTheoretical;
                $currentOrder->units_per_minute_theoretical = $unitsMadeTheoreticalPerMinute;
                $currentOrder->seconds_per_unit_real = $secondsPerUnitReal;
                $currentOrder->seconds_per_unit_theoretical = $secondsPerUnitTheoretical;
                $currentOrder->units_made_theoretical = $unitsMadeTheoretical;
                $currentOrder->units_delayed = $unitsDelayed;

                if ($productionOrder && $productionOrder->theoretical_time && $productionOrder->theoretical_time > 0) {
                    try {
                        // Usar eager loading para reducir consultas a la base de datos
                        $sensor = Sensor::where('production_line_id', $productionOrder->production_line_id)
                            ->where('sensor_type', 0)
                            ->first();
                            
                        if ($sensor) {
                            $sensorCount = SensorCount::where('sensor_id', $sensor->id)
                                ->latest()
                                ->first();
                                
                            if ($sensorCount) {
                                $timeSinceLastCount = Carbon::parse($sensorCount->created_at)->diffInSeconds(now());
                                $optimalTime = $sensor->optimal_production_time;
                                $maxSlowTime = $optimalTime * $sensor->reduced_speed_time_multiplier;
                                $realMaxSlowtime = $maxSlowTime - $optimalTime;
                                
                                // Verificar si estamos en el rango de "slow time"
                                if ($timeSinceLastCount > $optimalTime && $timeSinceLastCount < $maxSlowTime) {
                                    $currentOrder->slow_time = $currentOrder->slow_time + 1;
                                    $slowTimeDif = 1;
                                    $this->info("Incrementando slow_time por inactividad del sensor ({$timeSinceLastCount}s)");
                                } else {
                                    if ($timeSinceLastCount >= $maxSlowTime) {
                                        //sacamos todos los sensores de la linea de production y buscamos por sensor_id en downtime_sensors  por created_at = now si uno de los sensores tiene linea que aparece 
                                        //si cumple en doentime_sensors , semnifica que tenemos downtime abierto en este momento y pasamos el slowtime anterior 
                                        // y pasamos el $maxSlowTime de slowtime , haciendo slow_time actual - $maxSlowTime y sumamos a down_time = $maxSlowTime 
                                           // Verificar si se acaba de crear un registro de downtime para este sensor
                                        // Obtener todos los sensores de la línea de producción
                                        $sensorsAll = Sensor::where('production_line_id', $productionOrder->production_line_id)->get();

                                        // Buscar si algún sensor tiene un downtime reciente
                                        $recentDowntime = null;
                                        $downtimeSensor = null;

                                        foreach ($sensorsAll as $lineSensor) {
                                            $tempDowntime = DowntimeSensor::where('sensor_id', $lineSensor->id)
                                                ->whereNull('end_time')
                                                ->whereRaw('TIMESTAMPDIFF(SECOND, created_at, NOW()) BETWEEN 0 AND 1')->first();
                                                
                                            if ($tempDowntime) {
                                                //buscamos por su sensor_id en sensores si el sensor es sensor_type 0 o mayor 
                                                $sensor = Sensor::where('id', $tempDowntime->sensor_id)->first();
                                                if ($sensor->sensor_type == 0) {
                                                    if($shiftHistory->slow_time >= $realMaxSlowtime){
                                                        $shiftHistory->slow_time = $shiftHistory->slow_time - $realMaxSlowtime;
                                                        $shiftHistory->down_time = $shiftHistory->down_time + $realMaxSlowtime;
                                                        $shiftHistory->save();
                                                    }
                                                    $this->info("Se actualizó slow_time y down_time para la línea de producción {$productionOrder->production_line_id}");
                                                    //ahora hacemos lo mismo con currentorder
                                                    if($currentOrder->slow_time >= $realMaxSlowtime){
                                                        $currentOrder->slow_time = $currentOrder->slow_time - $realMaxSlowtime;
                                                        $currentOrder->down_time = $currentOrder->down_time + $realMaxSlowtime;
                                                        $currentOrder->save();
                                                    }
                                                    $this->info("Se actualizó slow_time y down_time para la orden {$productionOrder->id}");
                                                }else{
                                                    if($shiftHistory->slow_time >= $realMaxSlowtime){
                                                        $shiftHistory->slow_time = $shiftHistory->slow_time - $realMaxSlowtime;
                                                        $shiftHistory->production_stops_time = $shiftHistory->production_stops_time + $realMaxSlowtime;
                                                        $shiftHistory->save();
                                                        $this->info("Se actualizó production_stops_time para la línea de producción {$productionOrder->production_line_id}");
                                                    }
                                                    if($currentOrder->slow_time >= $realMaxSlowtime){
                                                        $currentOrder->slow_time = $currentOrder->slow_time - $realMaxSlowtime;
                                                        $currentOrder->production_stops_time = $currentOrder->production_stops_time + $realMaxSlowtime;
                                                        $currentOrder->save();
                                                        $this->info("Se actualizó production_stops_time para la orden {$productionOrder->id}");
                                                    }
                                                }
                                                
                                            }
                                        }
                                       
                                    }else{
                                        $this->info("No se incrementa slow_time por inactividad del sensor ({$timeSinceLastCount}s)");
                                        $slowTimeDif = 0;
                                        //$currentOrder->slow_time = $slowTime;
                                    }

                                }
                            } else {
                                $this->warn("No se encontraron registros para el sensor ID {$sensor->id}");
                                $slowTimeDif = 0;
                                //$currentOrder->slow_time = $slowTime;
                            }
                        } else {
                            $this->warn("No se encontró sensor de tipo 0 para la línea de producción {$productionOrder->production_line_id}");
                            $slowTimeDif = 0;
                            //$currentOrder->slow_time = $slowTime;
                        }
                    } catch (\Exception $e) {
                        $this->error("Error al calcular slow_time: " . $e->getMessage());
                        $slowTimeDif = 0;
                        //$currentOrder->slow_time = $slowTime;
                    }
                } else {
                    $slowTimeDif = $slowTime - $currentOrder->slow_time;
                    $currentOrder->slow_time = $slowTime;
                }
                

                if ($productionOrder && $productionOrder->theoretical_time && $productionOrder->theoretical_time > 0) {
                    // Solo actualizamos si ya tenemos un valor establecido y es mayor que 0
                    if ($currentOrder->theoretical_end_time > 0) {
                        // Restamos un segundo en cada iteración para crear un contador hacia atrás
                        $currentOrder->theoretical_end_time = max(0, $currentOrder->theoretical_end_time - 1);
                        $currentOrder->fast_time = max(0, $currentOrder->theoretical_end_time - 1);
                        
                        $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo teórico restante: {$currentOrder->theoretical_end_time} segundos");
                    } else{
                        // Incrementamos el out_time actual del currentOrder
                        $currentOrder->out_time = ($currentOrder->out_time ?? 0) + 1;
                        $this->info("[" . Carbon::now()->toDateTimeString() . "] Tiempo extra: {$currentOrder->out_time} segundos");
                    }

                    // 3. Lectura de tiempos
                    $P2 = $currentOrder->on_time;      // Tiempo planificado (s)
                    $D2 = $currentOrder->down_time + $currentOrder->production_stops_time;    // Tiempo de paradas (s)
                    $S2 = $currentOrder->slow_time;    // Tiempo lento (s)

                    // 4. Cálculo del tiempo productivo (no negativo)
                    $productiveOEE = max($P2 - $D2 - $S2, 0);

                    // 5. Cálculo del OEE en porcentaje
                    if ($P2 > 0) {
                        $oeePercentOEE = round(($productiveOEE / $P2) * 100, 2);  // 2 decimales
                    } else {
                        $oeePercentOEE = 100;
                    }
                    $totalOee = $oeePercentOEE;
                    
                    // Si es 0, lo dejamos en 0 (no hacemos nada)
                    // No establecemos el valor inicial aquí, ya que mencionas que otro proceso lo hace
                } else {
                    // Si no hay theoretical_time en production_orders, usamos el cálculo normal
                    $currentOrder->theoretical_end_time = $unitsMadeTheoreticalEnd;
                }
                $currentOrder->real_end_time = $unitsMadeRealEnd;
                $currentOrder->oee_sensors = $oee;
                $currentOrder->oee = $totalOee;
                $currentOrder->on_time = $orderTimeActivitySeconds;
                $currentOrder->save();
            }else{
                $slowTimeDif= 0;
            }
 
            if($shiftHistory){
                $this->info("Incrementando on_time por actividad del sensor");
                $shiftHistory->on_time = $shiftHistory->on_time + 1;
                //$shiftHistory->oee = $totalOee;
                //mostrar en log el slowtimedif

                    $shiftHistory->slow_time = $shiftHistory->slow_time + $slowTimeDif; // Incrementamos en 1 segundo fijo


                // 3. Lectura de tiempos
                $P = $shiftHistory->on_time;      // Tiempo planificado (s)
                $D = $shiftHistory->down_time + $shiftHistory->production_stops_time;    // Tiempo de paradas (s)
                $S = $shiftHistory->slow_time;    // Tiempo lento (s)

                // 4. Cálculo del tiempo productivo (no negativo)
                $productive = max($P - $D - $S, 0);

                // 5. Cálculo del OEE en porcentaje
                if ($P > 0) {
                    $oeePercent = round(($productive / $P) * 100, 2);  // 2 decimales
                } else {
                    $oeePercent = 100;
                }

                // 6. Guardar en el modelo
                $shiftHistory->oee = $oeePercent;  // valor entre 0 y 100
                
                // Log de depuración para slow_time
                $this->info("[DEBUG] Saving ShiftHistory for Line: {$shiftHistory->production_line_id}. Values: on_time={$shiftHistory->on_time}, down_time={$shiftHistory->down_time}, slow_time={$shiftHistory->slow_time}, prod_stops_time={$shiftHistory->production_stops_time}");

                $shiftHistory->save();
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

        $jsonMonitorOEE=$this->preparedJsonValue($totalOee, 0);

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


    private function sendProductionDownTimeToMQTT($monitor, $downTime)
    {
        try {
            // Formateamos $downtime a 00:00.00
            $formattedDownTime = date('H:i:s', strtotime($downTime));
            
            // Calculamos la diferencia en segundos entre la hora actual y el inicio del turno
            $diff = $this->calcDiffInSecondsFromTwoDates($monitor->time_start_shift, Carbon::now());
            
            // Verificamos que $diff sea mayor que cero para evitar división por cero
            if ($diff <= 0) {
                Log::warning('División por cero evitada en sendProductionDownTimeToMQTT. Usando valor predeterminado para diff.', [
                    'monitor_id' => $monitor->id,
                    'time_start_shift' => $monitor->time_start_shift,
                    'current_time' => Carbon::now()->toDateTimeString()
                ]);
                $diff = 1; // Valor predeterminado para evitar división por cero
            }
            
            // Aseguramos que $downTime sea un número válido
            $downTime = is_numeric($downTime) ? (float)$downTime : 0;
            
            // Calculamos el porcentaje de inactividad (downtime)
            $downtime_percentage = ($downTime / $diff) * 100;
            
            // Limitamos el porcentaje a un máximo de 100%
            $downtime_percentage = min($downtime_percentage, 100);
            
            // Determinamos el status basado en el porcentaje de inactividad
            if ($downtime_percentage >= 40) {
                $status = 0; // Downtime mayor o igual a 40%
            } elseif ($downtime_percentage >= 20) {
                $status = 1; // Downtime mayor o igual a 20% y menor que 40%
            } else {
                $status = 2; // Downtime menor que 20%
            }
        } catch (\Exception $e) {
            Log::error('Error en sendProductionDownTimeToMQTT: ' . $e->getMessage(), [
                'monitor_id' => $monitor->id,
                'exception' => $e->getTraceAsString()
            ]);
            $formattedDownTime = '00:00:00';
            $status = 2; // Valor predeterminado en caso de error
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
        try {
            // Verificar si alguno de los tiempos es nulo
            if ($time1 === null || $time2 === null) {
                Log::warning('calcDiffInSecondsFromTwoDates: Uno de los tiempos es nulo', [
                    'time1' => $time1,
                    'time2' => $time2
                ]);
                return 100; // Valor predeterminado seguro
            }
            
            // Convertir a objetos Carbon si no lo son ya
            if (!($time1 instanceof Carbon)) {
                try {
                    $time1 = Carbon::parse($time1);
                } catch (\Exception $e) {
                    Log::warning('calcDiffInSecondsFromTwoDates: Error al parsear time1', [
                        'time1' => $time1,
                        'error' => $e->getMessage()
                    ]);
                    return 100;
                }
            }
            
            if (!($time2 instanceof Carbon)) {
                try {
                    $time2 = Carbon::parse($time2);
                } catch (\Exception $e) {
                    Log::warning('calcDiffInSecondsFromTwoDates: Error al parsear time2', [
                        'time2' => $time2,
                        'error' => $e->getMessage()
                    ]);
                    return 100;
                }
            }
            
            // Calcular la diferencia en segundos
            $diff = $time2->diffInSeconds($time1);
            
            // Verificar que la diferencia sea positiva y mayor que cero
            if ($diff <= 0) {
                Log::warning('calcDiffInSecondsFromTwoDates: Diferencia de tiempo negativa o cero', [
                    'time1' => $time1->toDateTimeString(),
                    'time2' => $time2->toDateTimeString(),
                    'diff' => $diff
                ]);
                return 100; // Valor predeterminado seguro
            }
            
            return $diff;
        } catch (\Exception $e) {
            Log::error('Error en calcDiffInSecondsFromTwoDates: ' . $e->getMessage(), [
                'time1' => $time1,
                'time2' => $time2,
                'exception' => $e->getTraceAsString()
            ]);
            return 100; // Valor predeterminado seguro en caso de error
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
            //Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}
