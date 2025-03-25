<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sensor;
use App\Models\Modbus;
use App\Models\OrderStat;
use App\Models\ProductionOrder;
use App\Models\OrderMac;
use App\Models\SensorCount;
use App\Models\DowntimeSensor;
use App\Models\ControlWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;
use App\Models\ModbusHistory;
use App\Models\SensorHistory;
use App\Models\ShiftList;
use App\Models\ShiftHistory;
use App\Models\ProductList;



class TransferExternalDbController extends Controller
{
    /**
     * Transferir Datos a la Base de Datos Externa
     *
     * @OA\Post(
     *     path="/api/transfer-external-db",
     *     summary="Transferir datos a la base de datos externa",
     *     tags={"TransferExternalDb"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos necesarios para la transferencia",
     *         @OA\JsonContent(
     *             required={"orderId"},
     *             @OA\Property(property="orderId", type="string", example="ORD123456"),
     *             @OA\Property(property="externalSend", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transferencia exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="orderId", type="string", example="ORD123456"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en el JSON o datos inválidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="JSON de production_order inválido.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró production_order para el orderId proporcionado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No se encontró production_order para el orderId proporcionado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Error en el procesamiento de la orden.")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferDataToExternal(Request $request)
    {
        // Ignorar desconexión del cliente
        ignore_user_abort(true);

        // Validar los datos de entrada
        $request->validate([
            'orderId' => 'required|string',
            'externalSend' => 'boolean',
        ]);
        $orderId = trim($request->input('orderId'));
        $externalSend = $request->input('externalSend');
       

        // Obtener los registros de order_stats para ese orderId, ordenados por created_at ascendente
        $orderStats = OrderStat::where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get();
        // Obtener productionOrder
        $productionOrder = ProductionOrder::where('order_id', $orderId)->first();
        if (!$productionOrder) {
            Log::error("No se encontró un registro en `production_orders` para orderId={$orderId}.");
            return response()->json(['error' => 'No se encontró production_order para el orderId proporcionado.'], 404);
        }
        Log::info("Se encontró un registro en production_orders para orderId={$orderId}.");

        // Decodificar JSON
        $jsonData = $this->decodeJson($productionOrder->json);
        if (!$jsonData) {
            return response()->json(['error' => 'JSON de production_order inválido.'], 400);
        }

        // Extraer valores del JSON
        [$modelUnit, $modelEnvase, $modelMeasure, $unitValue, $totalWeight] = $this->extractJsonValues($jsonData);
        try {
            // Llamar a la función que obtiene el tiempo y los shift_history
            $orderTimeData = $this->getOrder($orderStats, $orderId, $modelUnit, $modelEnvase, $modelMeasure, $unitValue, $totalWeight,  $externalSend);

            return response()->json([
                'success' => true,
                'orderId' => $orderId,
                'data'    => $orderTimeData,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en " . __METHOD__ . ": " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene el inicio y el fin de la orden, calcula la diferencia de tiempo
     * y suma el tiempo total de pausa (tipo stop) entre start y end. Si un stop
     * no tiene "end", se utiliza finishTime.
     *
     * Además, si se detecta que en shiftHistories existe algún evento con type "stop" y action "start",
     * se invoca (por el momento) una función de cálculo pendiente para timeOn.
     * Si no, se calcula timeOn = differenceInSeconds - totalStopSeconds.
     *
     * @param \Illuminate\Support\Collection $orderStats
     * @param string $orderId
     * @return array
     *
     * @throws \Exception
     */
    private function getOrder($orderStats, $orderId, $modelUnit, $modelEnvase, $modelMeasure, $unitValue, $totalWeight,  $externalSend)
    {
        // 1. Validar que existan registros en order_stats
        if ($orderStats->isEmpty()) {
            throw new \Exception("No se encontraron registros en order_stats para orderId={$orderId}.");
        }

        // 2. Definir el inicio de la orden (primer created_at de order_stats)
        $startTime = Carbon::parse($orderStats->first()->created_at);

        // 3. Obtener el tiempo de cierre desde OrderMac (action = 1)
        $orderMacFinish = OrderMac::where('orderId', $orderId)
            ->where('action', 1)
            ->first();
        if (!$orderMacFinish) {
            throw new \Exception("No se encontró un registro en OrderMac para orderId={$orderId} con action=1.");
        }
        $finishTime = Carbon::parse($orderMacFinish->created_at);
        Log::info("Rango de la orden: [{$startTime->format('Y-m-d H:i:s')} - {$finishTime->format('Y-m-d H:i:s')}]");

        // 4. Obtener los registros de shift_history para la línea de producción en el rango [startTime, finishTime]
        $productionLineId = $orderStats->first()->production_line_id;
        $shiftHistories = ShiftHistory::where('production_line_id', $productionLineId)
            ->whereBetween('created_at', [$startTime, $finishTime])
            ->orderBy('created_at', 'asc')
            ->get();

        // 5. Calcular el tiempo total de pausa (stop)
        $totalStopSeconds = 0;
        $stopStart = null;
        foreach ($shiftHistories as $event) {
            if ($event->type === 'stop') {
                if ($event->action === 'start') {
                    // Registrar inicio de pausa, usando max(startTime, eventTime)
                    $eventTime = Carbon::parse($event->created_at);
                    $stopStart = $eventTime->lt($startTime) ? $startTime->copy() : $eventTime->copy();
                } elseif ($event->action === 'end' && $stopStart) {
                    // Cerrar pausa, usando min(finishTime, eventTime)
                    $eventTime = Carbon::parse($event->created_at);
                    $stopEnd = $eventTime->gt($finishTime) ? $finishTime->copy() : $eventTime->copy();
                    $totalStopSeconds += $stopEnd->diffInSeconds($stopStart);
                    $stopStart = null;
                }
            }
        }
        // Si queda una pausa abierta sin "end", se usa finishTime para cerrarla.
        if ($stopStart) {
            $totalStopSeconds += $finishTime->diffInSeconds($stopStart);
        }
        $formattedStopTime = gmdate('H:i:s', $totalStopSeconds);
        Log::info("Tiempo total de pausa (stop): {$formattedStopTime} ({$totalStopSeconds} segundos)");

        // 6. Calcular la diferencia total entre start y finish
        $diffInSeconds = $finishTime->diffInSeconds($startTime);
        $formattedDiff = gmdate('H:i:s', $diffInSeconds);

        // 7. Determinar el cálculo del tiempo de producción (timeOn)
        // Verificar si existe al menos un evento "stop start" en shiftHistories
        $hasStopStart = $shiftHistories->contains(function ($event) {
            return $event->type === 'shift' && $event->action === 'start';
        });

        if ($hasStopStart) {
            
            //ahora buscamos si en la misma cadena hay  un type shift y action start  por created_at last  sin tener despues un type shift action end 
            //si se encuentra tenemos que asignarle como end el $finishTime->format('Y-m-d H:i:s') y
            //hacemos la diferencia entre el created_at del type shift y action start y $finishTime->format('Y-m-d H:i:s')

                $firstTypeShiftEventStart = $shiftHistories
                    ->where('type', 'shift')
                    ->where('action', 'start')
                    ->sortByDesc('created_at')
                    ->first();

                $useFinishTime = false;
                if ($firstTypeShiftEventStart) {
                    // Buscamos si hay algún evento "shift end" con created_at mayor al de este último "shift start"
                    $followingShiftEnd = $shiftHistories->filter(function($event) use ($firstTypeShiftEventStart) {
                        return $event->type === 'shift' &&
                            $event->action === 'end' &&
                            Carbon::parse($event->created_at)->gt(Carbon::parse($firstTypeShiftEventStart->created_at));
                    })->first();
                    if (!$followingShiftEnd) {
                        // No hay un "shift end" posterior, entonces usaremos finishTime como fin del turno en curso.
                        $useFinishTime = true;
                    }
                }

            if ($firstTypeShiftEventStart) {

                // Si no hay un "shift end" posterior, usamos finishTime como fin
                if ($useFinishTime) {
                    $firstShiftStartCreatedAt = $firstTypeShiftEventStart->created_at;
                    $timeLastShift = Carbon::parse($firstShiftStartCreatedAt)->diffInSeconds(Carbon::parse($finishTime->format('Y-m-d H:i:s')));
                    $timeLastShiftFormatted = gmdate('H:i:s', $timeLastShift);
                }else {
                    // Si no se encuentra el evento "start stop", se asigna un mensaje indicando que se calculará en otra función.
                    //timeLastShift= lo ponemos a 0
                    $timeLastShift= 0;
                    $timeLastShiftFormatted = gmdate('H:i:s', $timeLastShift);
                }
            } else {
                // Si no se encuentra el evento "start stop", se asigna un mensaje indicando que se calculará en otra función.
                //timeLastShift= lo ponemos a 0
                $timeLastShift= 0;
                $timeLastShiftFormatted = gmdate('H:i:s', $timeLastShift);
            }
            // Si el primer stype shift y action end no tiene otro type shift y action end antes en esta cadena 
            //se pone $startTime->format('Y-m-d H:i:s')  como start y hacemos la diferencia entre $startTime->format('Y-m-d H:i:s') 
            //y el created_at del primero type shift y action end

            $firstTypeShiftEventEnd = $shiftHistories
                                    ->where('type', 'shift')
                                    ->where('action', 'end')
                                    ->sortBy('created_at')
                                    ->first();
            
            if ($firstTypeShiftEventEnd) {
                $firstShiftEndCreatedAt = $firstTypeShiftEventEnd->created_at;
                $timeFirstShiftEndCreatedAt = Carbon::parse($firstShiftEndCreatedAt)->diffInSeconds($startTime->format('Y-m-d H:i:s'));
                $timeFirstShiftEndCreatedAtFormatted = gmdate('H:i:s', $timeFirstShiftEndCreatedAt);
            } else {
                // Si no se encuentra el evento "stop start", se asigna un mensaje indicando que se calculará en otra función.
                $timeFirstShiftEndCreatedAt = Carbon::parse($finishTime->format('Y-m-d H:i:s'))->diffInSeconds($startTime->format('Y-m-d H:i:s'));
                $timeFirstShiftEndCreatedAtFormatted = gmdate('H:i:s', $timeFirstShiftEndCreatedAt);
                
            }

            //ahora buscamos solo los type shift action start que tienen despues un type shift que tienen un action end en la cadena $shiftHistories
            // y si se encuantra sumamos la diferencia entre el created_at del type shift action start y el created_at del type shift action end
            //si hay varias despues sumamos todas las diferencias En $timeShiftCompleted y $timeShiftCompletedFormatted
            // --- Buscar todos los pares completos de "shift start" y "shift end" ---
            $timeShiftCompleted = 0;
            foreach ($shiftHistories as $event) {
                if ($event->type === 'shift' && $event->action === 'start') {
                    // Buscar el primer "shift end" posterior a este "shift start"
                    $correspondingShiftEnd = $shiftHistories->filter(function($e) use ($event) {
                        return $e->type === 'shift' &&
                            $e->action === 'end' &&
                            $e->created_at->gt($event->created_at);
                    })->sortBy('created_at')->first();
                    if ($correspondingShiftEnd) {
                        $timeShiftCompleted += $correspondingShiftEnd->created_at->diffInSeconds($event->created_at);
                    }
                }
            }
            //dd($timeShiftCompleted);

            $timeShiftCompletedFormatted = gmdate('H:i:s', $timeShiftCompleted);

            // Si no hay eventos de tipo "stop start", se calcula:
            $timeOnSeconds = $timeFirstShiftEndCreatedAt + $timeLastShift + $timeShiftCompleted - $totalStopSeconds;
          
            $timeOnFormatted = gmdate('H:i:s', $timeOnSeconds);
        } else {
            // Si no hay eventos de tipo "stop start", se calcula:
            $timeOnSeconds = $diffInSeconds - $totalStopSeconds ;
            $timeOnFormatted = gmdate('H:i:s', $timeOnSeconds);
        }
        // Por defecto, si no hay ningún evento "shift" con action "start", $shiftCount será 1.
        $shiftStartEvents = $shiftHistories->where('type', 'shift')->where('action', 'start');

        if ($shiftStartEvents->isEmpty()) {
            $shiftCount = 1;
        } else {
            // Si existen, se suma la cantidad de eventos encontrados más 1.
            $shiftCount = $shiftStartEvents->count() + 1;
        }

        // Obtener el nombre de la línea de producción y el cliente de referencia
        $productionLine = $orderStats->first()->productionLine->name;
        $productList = $orderStats->first()->productList->client_id;

        //obtener de orderStats el slow_time y la suma de 	down_time y production_stops_time  como es en segundos creamos  otra variable formated a 00:00:00
        $slow_time = $orderStats->first()->slow_time;
        if ($slow_time > (0.8 * $timeOnSeconds)) {
            $slow_time = 0.2 * $timeOnSeconds;
        }
        $slowTimeFormated = gmdate('H:i:s', $slow_time);
        $production_stops_time = $orderStats->first()->production_stops_time;
        $down_time = $orderStats->first()->down_time;
        $totalStopSeconds = $down_time + $production_stops_time;
        $stopTimeFormated = gmdate('H:i:s', $totalStopSeconds);

        //cajas realizadas por basculas:
        $boxModbuses = $orderStats->sum('weights_0_orderNumber');
        //cajas cajas en order_notice de la orden
        $orderBoxFromOrderNotice = $orderStats->sum('box');

        //unidades realizadas por sensores
        $unitsSensors = $orderStats->sum('units_made_real');

        //unidades en order_notice de la orden
        $orderUnits = $orderStats->sum('units');

        //usamos esto para tranfer por si no hay basculas que se pase los sensores
        $unitsNumberFromTransfer = ($orderBoxFromOrderNotice !== null && $orderBoxFromOrderNotice != 0) 
                                    ? $boxModbuses
                                    : $unitsSensors;
        $unitsFromOrnderNumberFromTransfer = ($orderBoxFromOrderNotice !== null && $orderBoxFromOrderNotice != 0) 
                                    ? $orderBoxFromOrderNotice 
                                    : $orderUnits;

        //sacamos el tiempo óptimo de producción de product_lists usando client_id=$productList y sacamos el optimal_production_time
        $optimalProductionTime = ProductList::where('client_id', $productList)->first()->optimal_production_time;

        //buscamos todos los sensores que se han usado en la linea de producción de la orden por production_line_id
        $sensorsUsed = Sensor::where('production_line_id', $orderStats->first()->production_line_id)->get();
        
        //buscamos todos los modbus que se han usado en la linea de producción de la orden por production_line_id
        //pero de modbuses quitamos el modbus que tiene nombre : Bascula Linea2-rectificar- el IMPERIO con Mperio  
        //mostrando todos los modbuses menos este para rectificar fallo de topflow con ajos
        //$modbusUsed = Modbus::where('production_line_id', $orderStats->first()->production_line_id)->get();
        $modbusUsed = Modbus::where('production_line_id', $orderStats->first()->production_line_id)
                            ->where('name', '!=', 'Bascula Linea2-rectificar- el IMPERIO con Mperio')
                            ->get();

        //hacemos un foreach para sensores y cada sensor en foreach se busca en sensor_history  por sensor_id y orderId 
        // sacamos todas las lineas encontradas sumamos el count_order_1  el downtime_count 
        //Inicia array sensorData para almacenar los datos del sensor

        //Antes de empezar con sensores Vamo a mandar los datos de la linea de production a la DB externa si se ha pedido 
        if ($externalSend === true){
            // Iniciar transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // Conexión a la base de datos externa
            $externalConnection = DB::connection('external');
            Log::info("Conexión a la base de datos externa establecida.");
                // Eliminar registros previos para el mismo OrderId
                
            $externalConnection->table('linea_por_orden')
                ->where('IdOrden', $orderId)
                ->delete();
            $externalConnection->table('sensores_por_orden')
                ->where('IdOrder', $orderId)
                ->delete();
            Log::info("Se eliminaron los registros previos para orderId={$orderId} en la DB externa.");

        }

        if ($externalSend === true){
            try {
                $data = [
                    'IdLinea' => $productionLine,
                    'IdOrden' => $orderId,
                    'IdReference' => $productList,
                    'ShiftCount' => $shiftCount,
                    'OrderCount' => $unitsFromOrnderNumberFromTransfer,
                    'OrderUnit' => $modelUnit,
                    'UnitsPerBox' => $orderStats->isNotEmpty() && $orderStats->first()->units_box
                                    ? number_format($orderStats->first()->units_box, 2) // Formatea a dos decimales
                                    : null,
                    'SensorCount' => $unitsNumberFromTransfer,
                    'UmaCount' => $this->getOrderMacUmaQuantity($orderId),
                    'StartAt' => $startTime->format('Y-m-d H:i:s'),
                    'FinishAt' => $finishTime->format('Y-m-d H:i:s'),
                    'TimeON' => $timeOnFormatted,
                    'TimeDown' => $stopTimeFormated,
                    'TimeSlow' => $slowTimeFormated,
                ];
    
    
                $externalConnection->table('linea_por_orden')->insert($data);
                Log::info("Datos insertados en linea_por_orden para IdOrden={$orderId}.");
            } catch (\Exception $e) {
                Log::error("Error al insertar en linea_por_orden para IdOrden={$orderId}: " . $e->getMessage());
                // Opcional: Manejar la excepción según tus necesidades
            }

        } else{ 
        DB::rollBack();
        }

        $sensorData = [];
        foreach ($sensorsUsed as $sensor) {
            
            try {
                //dd($sensor);
                // Buscar en sensor_history por sensor_id y order_id
                $sensorHistory = SensorHistory::where('sensor_id', $sensor->id)
                    ->where('orderId', $orderId)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $totalCountOrder = $sensorHistory ? $sensorHistory->count_order_1 : 0;
                $totalTimeDowntime = $sensorHistory ? $sensorHistory->downtime_count : 0;
            
                if ($totalCountOrder > 0) {
                    //TCAverage es realtime unit en segundos por unidad de orden y lo calculamos tiempo de trabajo (sin pausas programadas)
                    // - tiempo total de paradas ne programadas / total de boilsas mallas
                    $realTimeUnit = ($timeOnSeconds - $totalTimeDowntime) / $totalCountOrder;
                } else {
                    $realTimeUnit = 0; // o el valor que consideres adecuado
                }

                // Consultar la tabla sensor_count para obtener el tiempo de trabajo
                $aggregates = SensorCount::where('sensor_id', $sensor->id)
                                        ->whereBetween('created_at', [$startTime->format('Y-m-d H:i:s'), $finishTime->format('Y-m-d H:i:s')])
                                        ->selectRaw('MIN(time_00) AS min_time_00, MIN(time_11) AS min_time_11')
                                        ->first();
                // Evitamos valores nulos con ??
                $minTime00 = $aggregates->min_time_00 ?? 0;
                $minTime11 = $aggregates->min_time_11 ?? 0;

                // Elegimos qué mínimo usar dependiendo del sensor_type
                if ((int)$sensor->sensor_type === 0) {
                $minTime = max($minTime11, (int)env('PRODUCTION_MIN_TIME', 1));
                } else {
                $minTime = $minTime00;
                }
                
                $grossWeightTotal=number_format(($totalCountOrder * $unitValue), 2, '.', '');

                if($sensor->sensor_type === 0) {
                    $formateado = "optimal_production_time";
                } else {
                    $formateado = "optimalproductionTime_sensorType_" . $sensor->sensor_type;
                }

                
                $optimalProductionTime = $orderStats->first()->productList->$formateado;
                
                if($sensor->sensor_type === 0) {
                    if($optimalProductionTime > $realTimeUnit){
                        $optimalProductionTime = round($realTimeUnit, 2);
                        $slowTime=0.0;
                        $minTime=round($realTimeUnit, 1);
                    }else{
                        $slowTime=($timeOnSeconds - $totalTimeDowntime) -($optimalProductionTime * $totalCountOrder);
                    }
                    
                } else {
                    $slowTime=0.0;
                }

                if($minTime<1){
                    $realTimeUnit=0;
                }
                if($totalCountOrder < 1) {
                    $realTimeUnit=0;
                    $minTime=0;
                }
                if($sensor->sensor_type > 0) {
                    //si es sensor de tipo 1 o 2 $unitValue es igual a 0
                    $unitValue=0;
                }

                

                //dd($realTimeUnit);
                // Si no hay registros, no se suma nada (se mantienen los acumulados)
                $sensorData[] = [
                    'id'                      => $sensor->id,
                    'name'                    => $sensor->name,
                    'productionLine'          => $productionLine,
                    'orderId'                 => $orderId,
                    'startAt'                 => $startTime->format('Y-m-d H:i:s'),
                    'finishAt'                => $finishTime->format('Y-m-d H:i:s'),
                    'totalTime'               => $timeOnSeconds,
                    'totalTimeFormatted'      => gmdate('H:i:s', $timeOnSeconds),
                    'productList'             => $productList,
                    'IdClient'                => "Por definir",
                    'TcTheoretical'           => $optimalProductionTime ,
                    'TcUnit'                  => "segundos",
                    'TcAverage'               => round($realTimeUnit, 2),
                    'TcMin'                   => $minTime,
                    'totalCountOrder'         => $totalCountOrder,
                    'TimeSlow'                => $slowTime,
                    'TimeSlowFormated'        => gmdate('H:i:s', $slowTime),
                    'totalTimeDowntime'       => $totalTimeDowntime,
                    'totalTimeDowntimeFormatted'         => gmdate('H:i:s', $totalTimeDowntime),
                    'timeOnSeconds'           => $timeOnSeconds - $totalTimeDowntime,
                    'timeOnFormatted'         => gmdate('H:i:s', $timeOnSeconds - $totalTimeDowntime),
                    'SensorUnitCount'         => $modelEnvase,
                    'SensorWeight'            => $grossWeightTotal,
                    'SensorUnitWeight'        => $modelMeasure,
                    'GrossWeight'             => $unitValue, // Nuevo campo
                    // Puedes agregar aquí más información o incluso los registros de sensorHistory si lo necesitas
                ];


                if ($externalSend === true){
                        // Preparar datos para la base de datos externa
                    $data = [
                        'IdOrder'                 => $orderId,
                        'IdReference'             => $productList,
                        'startAt'                 => $startTime->format('Y-m-d H:i:s'),
                        'finishAt'                => $finishTime->format('Y-m-d H:i:s'),
                        'IdClient'                => "Por definir",
                        'IdSensor'                => $sensor->name,
                        'TcTheoretical'           => $optimalProductionTime ,
                        'TcUnit'                  => "segundos",
                        'TcAverage'               => round($realTimeUnit, 2),
                        'TcMin'                   => $minTime,
                        'IdLine'                  => $productionLine,
                        'TimeOn'                  => gmdate('H:i:s', $timeOnSeconds),
                        'TimeDown'                => gmdate('H:i:s', $totalTimeDowntime),
                        'TimeSlow'                => gmdate('H:i:s', $slowTime),
                        'SensorCount'             => $totalCountOrder,
                        'SensorUnitCount'         => $modelEnvase,
                        'SensorWeight'            => $grossWeightTotal,
                        'SensorUnitWeight'        => $modelMeasure,
                        'GrossWeight01'           => $unitValue, // Nuevo campo
                        'GrossWeight02'           => '0.0',  // Nuevo campo
                    ];

                    // Insertar datos
                    $externalConnection->table('sensores_por_orden')->insert($data);
                    Log::info("Sensor ID {$sensor->id} transferido a la base de datos externa.");
                } else{ 
                    DB::rollBack();
                }
            } catch (\Exception $e) {
                Log::error('Error al procesar el sensor: ' . $sensor->name);
                Log::error($e->getMessage());
            }
        }

        //sacamos con foreach todos los modbuses igual como a sensores sacamos name  y 
        //despue vamos a sacar de modbus_history por modbus_id que es id del modbus en modbuses 
        //y por orderId , y sumamos rec_box y downtime_count
        //inicamos array modbusData para almacenar los datos de modbus
        $modbusData = [];
        foreach ($modbusUsed as $modbus) {
            
            try {
                // Obtener el historial de modbus para este módulo
                $modbusHistory = ModbusHistory::where('modbus_id', $modbus->id)
                    ->where('orderId', $orderId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $totalCountOrder = $modbusHistory ? $modbusHistory->rec_box : 0;
                $totalTimeDowntime = $modbusHistory ? $modbusHistory->downtime_count : 0;
                $totalWeightModbus = $modbusHistory ? $modbusHistory->total_kg_order : 0;

                if ($totalCountOrder > 0) {
                    $realTimeUnit = ($timeOnSeconds - $totalTimeDowntime) / $totalCountOrder;
                } else {
                    $realTimeUnit = 0; // o el valor que consideres adecuado
                }

                $timeDifferences = ControlWeight::selectRaw('TIMESTAMPDIFF(SECOND, LAG(created_at) OVER (ORDER BY created_at), created_at) AS interval_diff')
                    ->where('modbus_id', $modbus->id)
                    ->whereBetween('created_at', [$startTime->format('Y-m-d H:i:s'), $finishTime->format('Y-m-d H:i:s')])
                    ->pluck('interval_diff')
                    ->filter(fn($value) => !is_null($value) && $value > env('PRODUCTION_MIN_TIME_WEIGHT', 30));

                $minInterval = $timeDifferences->min() ?? env('PRODUCTION_MIN_TIME_WEIGHT', 30);


                //sacamos por $orderStats->first()->product_list_id y ahorasacamos la linea product_list where id=product_list_id
  
                if ($modbus->model_type < 1) {
                    $optimalProductionTime = $orderStats->first()->productList->optimalproductionTime_weight;
                } else {
                    $formateado = "optimalproductionTime_weight_" . $modbus->model_type;
                    $optimalProductionTime = $orderStats->first()->productList->$formateado;
                }
                
                if($totalCountOrder < 1) {
                    $realTimeUnit=0;
                    $minTime=0;
                }

                //si el model_type es 1 2 3 4  el $totalWeight es 0
                if ($modbus->model_type > 0) {
                    $totalWeight = 0;
                }

                $modbusData[] = [
                    'id'                      => $modbus->id,
                    'name'                    => $modbus->name,
                    'productionLine'          => $productionLine,
                    'orderId'                 => $orderId,
                    'startAt'                 => $startTime->format('Y-m-d H:i:s'),
                    'finishAt'                => $finishTime->format('Y-m-d H:i:s'),
                    'totalTime'               => $timeOnSeconds,
                    'totalTimeFormatted'      => gmdate('H:i:s', $timeOnSeconds),
                    'productList'             => $productList,
                    'IdClient'                => "Por definir",
                    'TcTheoretical'           => $optimalProductionTime,
                    'TcUnit'                  => "segundos",
                    'TcAverage'               => round($realTimeUnit, 2),
                    'TcMin'                   => $minInterval,
                    'totalCountOrder'         => $totalCountOrder,
                    'TimeSlow'                => ($timeOnSeconds - $totalTimeDowntime) -($minInterval* $totalCountOrder),
                    'TimeSlowFormated'        => gmdate('H:i:s', ($timeOnSeconds - $totalTimeDowntime) -($minInterval * $totalCountOrder)),
                    'totalTimeDowntime'       => $totalTimeDowntime,
                    'totalTimeDowntimeFormatted'         => gmdate('H:i:s', $totalTimeDowntime),
                    'timeOnSeconds'           => $timeOnSeconds - $totalTimeDowntime,
                    'timeOnFormatted'         => gmdate('H:i:s', $timeOnSeconds - $totalTimeDowntime),
                    'SensorUnitCount'         => $modelUnit,
                    'SensorWeight'            => $totalWeightModbus, // lo formateamos con maximo 2 digitos decimales
                    'SensorUnitWeight'        => $modelMeasure,
                    'GrossWeight'             => round($totalWeight, 2),  // Nuevo campo
                    // Más datos según lo requieras
                ];
                if ($externalSend === true){
                    // Preparar datos para la base de datos externa
                    $data = [
                        'IdOrder'                 => $orderId,
                        'IdReference'             => $productList,
                        'startAt'                 => $startTime->format('Y-m-d H:i:s'),
                        'finishAt'                => $finishTime->format('Y-m-d H:i:s'),
                        'IdClient'                => "Por definir",
                        'IdSensor'                => $modbus->name,
                        'TcTheoretical'           => $optimalProductionTime ,
                        'TcUnit'                  => "segundos",
                        'TcAverage'               => round($realTimeUnit, 2),
                        'TcMin'                   => $minInterval,
                        'IdLine'                  => $productionLine,
                        'TimeOn'                  => gmdate('H:i:s', $timeOnSeconds),
                        'TimeDown'                => gmdate('H:i:s', $totalTimeDowntime),
                        'TimeSlow'                => gmdate('H:i:s', ($timeOnSeconds - $totalTimeDowntime) -($minInterval * $totalCountOrder)),
                        'SensorCount'             => $totalCountOrder,
                        'SensorUnitCount'         => $modelUnit,
                        'SensorWeight'            => $totalWeightModbus, // lo formateamos con maximo 2 digitos decimales
                        'SensorUnitWeight'        => $modelMeasure,
                        'GrossWeight01'           => '0.0', // Nuevo campo
                        'GrossWeight02'           => round($totalWeight, 2),  // Nuevo campo
                    ];

                    // Insertar datos
                    $externalConnection->table('sensores_por_orden')->insert($data);
                    Log::info("Modbus ID {$modbus->id} transferido a la base de datos externa.");
                } else{ 
                    DB::rollBack();
                }
                
            } catch (\Exception $e) {
                // Manejo de excepciones si ocurre algún error al obtener el historial de modbus
                Log::error('Error al obtener el historial de modbus: ' . $e->getMessage());
                // Puedes agregar aquí lógica para manejar el error de manera adecuada
                Log::info('Modbus ID: ' . $modbus->id);
            }
        }

        //Salvar todo en db externa
        if ($externalSend === true){
            try{
               DB::commit(); 
            } catch (\Exception $e) {
                Log::error('Error al guardar en la base de datos externa: ' . $e->getMessage());
                DB::rollBack();
                return response()->json(['error' => 'Error al guardar en la base de datos externa'], 500);
            }
            
        } else{ 
            DB::rollBack();
        }

        // 8. Retornar los datos, incluyendo la información de timeOn
        return [
            'orderId'                 => $orderId,
            'startAt'                 => $startTime->format('Y-m-d H:i:s'),
            'finishAt'                => $finishTime->format('Y-m-d H:i:s'),
            'totalTime'               => $diffInSeconds,
            'totalTimeFormatted'      => $formattedDiff,
            'totalStopSeconds'        => $totalStopSeconds,
            'totalStopFormatted'      => $formattedStopTime,
            'timeOnSeconds'           => $timeOnSeconds,
            'timeOnFormatted'         => $timeOnFormatted,
            'shiftCount'              => $shiftCount,
            'productionLine'          => $productionLine,
            'productList'             => $productList,
            'slowTime'                => $slow_time,
            'slowTimeFormatted'       => $slowTimeFormated,
            'downTime'                => $totalStopSeconds,
            'totalDownTimeFormated'   => $stopTimeFormated,
            'boxModbuses'             => $boxModbuses,
            'orderBoxFromOrderNotice' => $orderBoxFromOrderNotice,
            'unitsSensors'            => $unitsSensors,
            'unitsFronOrderNotice'    => $orderUnits,
            'unitsNumberFromTransfer' => $unitsNumberFromTransfer,
            'unitsFromOrderNumberFromTransfer' => $unitsFromOrnderNumberFromTransfer,
            'shiftHistories'          => $shiftHistories,
            'sensorsUsed' => $sensorData,
            'modbusUsed'  => $modbusData,
        ];
    }

    private function extractJsonValues($jsonData)
    {
        $modelUnit = $jsonData['unit'] ?? 'Desconocido';
        $modelEnvase = $jsonData['refer']['envase'] ?? 'Desconocido';
        $modelMeasure = $jsonData['refer']['measure'] ?? 'Desconocido';
        $unitValue = $jsonData['refer']['value'] ?? 'Desconocido';
        $totalWeight = $jsonData['refer']['groupLevel'][0]['total'] ?? 'Desconocido';

        $fields = [
            'unit' => $modelUnit,
            'envase' => $modelEnvase,
            'measure' => $modelMeasure,
            'value' => $unitValue,
            'total' => $totalWeight,
        ];

        foreach ($fields as $key => $value) {
            if ($value === 'Desconocido') {
                Log::error("El campo `{$key}` no está disponible o tiene un valor nulo en el JSON.");
            } else {
                Log::info("`{$key}` extraído correctamente: {$value}");
            }
        }

        return [$modelUnit, $modelEnvase, $modelMeasure, $unitValue, $totalWeight];
    }

    private function decodeJson($json)
    {
        // Verificar si ya está decodificado
        if (is_array($json)) {
            Log::info("JSON ya estaba decodificado.");
            return $json;
        }

        // Intentar decodificar si es una cadena JSON
        if (is_string($json)) {
            $jsonData = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Error al decodificar JSON: " . json_last_error_msg());
                return null;
            }
            Log::info("JSON decodificado correctamente.");
            return $jsonData;
        }

        Log::error("Tipo de dato inesperado para JSON: " . gettype($json));
        return null;
    }

    private function getOrderMacUmaQuantity($orderId)
    {
        $orderMac = OrderMac::where('orderId', $orderId)
            ->where('action', 1)
            ->first();
    
        return $orderMac ? $orderMac->quantity : '0';
    }
}
