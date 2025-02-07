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


class TransferExternalDbController extends Controller
{
    /**
     * Transferir Datos a la Base de Datos Externa
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
            'barcodeId' => 'required|integer',
            'orderId' => 'required|string',
        ]);

        $barcodeId = $request->input('barcodeId');
        $orderId = trim($request->input('orderId'));

        Log::info("Iniciando transferencia de datos para barcode ID {$barcodeId} y orderId {$orderId}");

        try {
            // Iniciar transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // Conexión a la base de datos externa
            $externalConnection = DB::connection('external');
            Log::info("Conexión a la base de datos externa establecida.");

            // Obtener sensores y modbuses filtrados por barcodeId
            $sensors = Sensor::where('barcoder_id', $barcodeId)->get();
            Log::info("Se encontraron {$sensors->count()} sensores para barcode ID {$barcodeId}.");

            $modbuses = Modbus::where('barcoder_id', $barcodeId)->get();
            Log::info("Se encontraron {$modbuses->count()} modbuses para barcode ID {$barcodeId}.");

            if ($sensors->isEmpty() && $modbuses->isEmpty()) {
                Log::error("No se encontraron sensores ni modbuses para barcode ID {$barcodeId}.");
                DB::rollBack();
                return response()->json(['error' => 'No se encontraron sensores ni modbuses para el barcodeId proporcionado.'], 404);
            }

            // Obtener orderStats
            $orderStats = OrderStat::where('order_id', $orderId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($orderStats->isEmpty()) {
                Log::error("No se encontraron registros en `order_stats` para orderId={$orderId}.");
                DB::rollBack();
                return response()->json(['error' => 'No se encontraron registros en order_stats para el orderId proporcionado.'], 404);
            }
            Log::info("Se encontraron {$orderStats->count()} registros en order_stats para orderId={$orderId}.");

            // Obtener productionOrder
            $productionOrder = ProductionOrder::where('order_id', $orderId)->first();
            if (!$productionOrder) {
                Log::error("No se encontró un registro en `production_orders` para orderId={$orderId}.");
                DB::rollBack();
                return response()->json(['error' => 'No se encontró production_order para el orderId proporcionado.'], 404);
            }
            Log::info("Se encontró un registro en production_orders para orderId={$orderId}.");

            // Decodificar JSON
            $jsonData = $this->decodeJson($productionOrder->json);
            if (!$jsonData) {
                DB::rollBack();
                return response()->json(['error' => 'JSON de production_order inválido.'], 400);
            }

            // Extraer valores del JSON
            [$modelUnit, $modelEnvase, $modelMeasure, $unitValue, $totalWeight] = $this->extractJsonValues($jsonData);

            // Calcular diferencias de tiempo
            [$totalDifferenceInSeconds, $totalDifferenceFormatted] = $this->calculateTimeDifferences($orderStats);

            // Obtener tiempos de la orden
            [$startAt, $finishAt, $totalTime, $totalTimeFomatted] = $this->getOrderTime($orderId, $totalDifferenceInSeconds);


            //obtener idLinea y idReference
            [$idLinea, $idReference] = $this->getIdLineaAndIdReference($orderStats);       

            // Calcular ShiftCount
            $shiftCount = $this->calculateShiftCountByEvents($orderStats);
            
            //extract de order_starts
            [$orderUnit, $orderUnitSensors, $orderUnitModbuses, $slowTime, $downTime, $slowTimeFormat ,$downTimeFormat] = $this->extractOrderUnit($orderStats);
            //filtrar si no hay basculas que use la sensorica
            $senorUnit = $orderUnitModbuses ? $orderUnitModbuses : $orderUnitSensors;

            //extraer de order_mac por orderId el campo quantity y que el action = 1
            $orderUnit = $this->getOrderMacUmaQuantity($orderId);
            
            // Procesar y transferir sensores y modbuses
            $this->processSensors($sensors, $externalConnection, $startAt, $finishAt, $totalTime, $modelEnvase, $modelMeasure, $unitValue, $totalWeight, $orderId);
            $this->processModbuses($modbuses, $externalConnection, $startAt, $finishAt, $totalTime, $modelUnit, $modelMeasure, $totalWeight, $orderId);

            // Insertar datos ficticios en linea_por_orden utilizando Eloquent
            $this->insertLineaPorOrden(
                $externalConnection,
                $idLinea,                 // IdLinea (ficticio)
                $orderId,                  // IdOrden
                $idReference,          // IdReference (ficticio)
                $shiftCount,                         // ShiftCount (ficticio)
                $orderUnit,                    // OrderCount (ficticio)
                $modelUnit,                // OrderUnit (ficticio)
                $senorUnit,                     // SensorCount (ficticio)
                $orderUnit,                     // UmaCount (ficticio)
                $startAt,                  // StartAt
                $finishAt,                 // FinishAt
                $totalTimeFomatted,                // TimeON (ficticio)
                $downTimeFormat,                // TimeDown (ficticio)
                $slowTimeFormat                 // TimeSlow (ficticio)
            );

            DB::commit();
            Log::info("Transferencia completada exitosamente para barcode ID {$barcodeId} y orderId {$orderId}.");

            return response()->json(['message' => 'Transferencia completada exitosamente.'], 200);
        } catch (\Exception $e) {
            Log::error("Error durante la transferencia: " . $e->getMessage());
            DB::rollBack();
            return response()->json(['error' => 'Ocurrió un error durante la transferencia de datos.'. $e->getMessage()], 500);
        }
    }

    /**
     * Obtener idLinea y idReference usando las relaciones Eloquent
     *
     * @param \Illuminate\Support\Collection $orderStats
     * @return array
     */
    private function getIdLineaAndIdReference($orderStats)
    {
        $firstOrderStat = $orderStats->first();

        // Verificar que $firstOrderStat no sea null
        if (!$firstOrderStat) {
            Log::error("No hay registros en orderStats para obtener idLinea e idReference.");
            return ['Desconocido', 'Desconocido'];
        }

        // Acceder a las relaciones definidas
        $idLinea = $firstOrderStat->productionLine->name ?? 'Desconocido';
        $idReference = $firstOrderStat->productList->client_id ?? 'Desconocido';

        return [$idLinea, $idReference];
    }

    /**
     * Obtener la cantidad de UMA de OrderMac donde action es 1
     *
     * @param string $orderId
     * @return float
     */
    private function getOrderMacUmaQuantity($orderId)
    {
        $orderMac = OrderMac::where('orderId', $orderId)
            ->where('action', 1)
            ->first();
    
        return $orderMac ? $orderMac->quantity : '0';
    }

    /**
     * Extract order unit from orderStats
     *
     * @param \Illuminate\Support\Collection $orderStats
     * @return array
     */
    private function extractOrderUnit($orderStats)
    {
        //suma de unidades del campo weights_0_orderNumber
        $orderUnitModbuses = $orderStats->sum('weights_0_orderNumber');
        //suma de unidades del campo units
        $orderUnitSensors = $orderStats->sum('units');
        //suma de la tiempo lento
        $slowTime = $orderStats->sum('slow_time');
        //suma de la tiempo lento
        $downTime = $orderStats->sum('down_time');
        //formateamos slowtime a hh:mm:ss
        $slowTimeFormat = gmdate("H:i:s", $slowTime);
        //formateamos downtime a hh:mm:ss
        $downTimeFormat = gmdate("H:i:s", $downTime);
    
        $firstOrderStat = $orderStats->first();
        $orderUnit = $firstOrderStat ? $firstOrderStat->units : 'Desconocido';
    
        return [
            $orderUnit,         // Índice 0: orderUnit (de firstOrderStat->units)
            $orderUnitSensors,  // Índice 1: orderUnitSensors (suma de 'units')
            $orderUnitModbuses, // Índice 2: orderUnitModbuses (suma de 'weights_0_shiftNumber')
            $slowTime,          // Índice 3: slowTime (suma de 'slow_time')
            $downTime,          // Índice 4: downTime (suma de 'down_time')
            $slowTimeFormat,    // Índice 5: slowTimeFormat (formato de 'slow_time')
            $downTimeFormat,    // Índice 6: downTimeFormat (formato de 'down_time')
        ];
    }

    /**
     * Decodificar y validar el JSON de ProductionOrder
     *
     * @param mixed $json
     * @return array|null
     */
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

    /**
     * Extraer valores necesarios del JSON
     *
     * @param array $jsonData
     * @return array
     */
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

    /**
     * Calcular diferencias de tiempo entre los registros de order_stats
     *
     * @param \Illuminate\Support\Collection $orderStats
     * @return array
     */
    private function calculateTimeDifferences($orderStats)
    {
        $totalDifferenceInSeconds = 0;
    
        for ($i = 0; $i < $orderStats->count() - 1; $i++) {
            $currentStat = $orderStats[$i];
            $nextStat = $orderStats[$i + 1];
    
            // Ajustar los timestamps de ambos registros antes de calcular diferencias
            $currentStat = $this->validateAndAdjustTimestamps(clone $currentStat);
            $nextStat = $this->validateAndAdjustTimestamps(clone $nextStat);
    
            $updatedAt = Carbon::parse($currentStat->updated_at);
            $createdAt = Carbon::parse($nextStat->created_at);
    
            $difference = $updatedAt->diffInSeconds($createdAt);
            $totalDifferenceInSeconds += $difference;
    
            Log::info("Diferencia entre ID {$currentStat->id} y ID {$nextStat->id}: {$difference} segundos.");
        }
    
        $totalDifferenceFormatted = gmdate('H:i:s', $totalDifferenceInSeconds);
        Log::info("Diferencia total de tiempo: {$totalDifferenceFormatted} ({$totalDifferenceInSeconds} segundos).");
    
        return [$totalDifferenceInSeconds, $totalDifferenceFormatted];
    }
    private function validateAndAdjustTimestamps($model)
    {
        $updatedAt = Carbon::parse($model->updated_at);
        $createdAt = Carbon::parse($model->created_at);
        $productionLineId = $model->production_line_id ?? null;

        if (!$productionLineId) {
            throw new \Exception("El modelo no tiene un production_line_id especificado.");
        }

        // Buscar el turno correspondiente basado en la fecha de created_at
        $applicableShift = ShiftList::where('production_line_id', $productionLineId)
            ->where('start', '<=', $createdAt->format('H:i:s'))
            ->where('end', '>=', $createdAt->format('H:i:s'))
            ->whereDate('created_at', '<=', $createdAt)
            ->orderByDesc('created_at')
            ->first();

        if ($applicableShift) {
            $shiftStart = Carbon::createFromFormat('H:i:s', $applicableShift->start);
            $shiftEnd = Carbon::createFromFormat('H:i:s', $applicableShift->end);

            // Ajustar created_at si está fuera del intervalo del turno
            if ($createdAt->isBefore($shiftStart)) {
                $model->created_at = $updatedAt->copy()->setTime($shiftStart->hour, $shiftStart->minute, $shiftStart->second);
            } elseif ($createdAt->isAfter($shiftEnd)) {
                $model->created_at = $updatedAt->copy()->setTime($shiftEnd->hour, $shiftEnd->minute, $shiftEnd->second);
            }

            // Ajustar updated_at si es mayor que end
            if ($updatedAt->isAfter($shiftEnd)) {
                $model->updated_at = $createdAt->copy()->setTime($shiftEnd->hour, $shiftEnd->minute, $shiftEnd->second);
            }

            // Asegurando que created_at no sea mayor que end después de ajustes
            if ($createdAt->gt($shiftEnd)) {
                $model->created_at = $updatedAt->copy()->setTime($shiftEnd->hour, $shiftEnd->minute, $shiftEnd->second);
            }
        } else {
            // Si no hay turnos aplicables, no hacemos ajustes
            Log::info("No se encontraron turnos aplicables para la línea de producción con id: {$productionLineId}. Los timestamps se mantienen sin cambios.");
        }

        return $model;
    }

    /**
     * Obtener tiempos de inicio y fin de la orden
     *
     * @param string $orderId
     * @param int $totalDifferenceInSeconds
     * @return array
     */
    private function getOrderTime($orderId, $totalDifferenceInSeconds)
    {
        try {
            // Obtener el registro de OrderMac donde action es 0 para el startAt
            $orderMacStart = OrderMac::where('orderId', $orderId)->where('action', 0)->first();
            if (!$orderMacStart) {
                throw new Exception("No se encontró un registro en OrderMacs para orderId={$orderId} con action=0.");
            }

            $startAt = Carbon::parse($orderMacStart->created_at)->format('Y-m-d H:i:s');

            // Obtener el registro de OrderMac donde action es 1 para el finishAt
            $orderMacFinish = OrderMac::where('orderId', $orderId)->where('action', 1)->first();
            if (!$orderMacFinish) {
                throw new Exception("No se encontró un registro en OrderMacs para orderId={$orderId} con action=1.");
            }

            $finishAt = Carbon::parse($orderMacFinish->created_at)->format('Y-m-d H:i:s');
            Log::info("Se obtuvo finishAt de OrderMac con action=1: {$finishAt}");

            $carbonStartAt  = Carbon::parse($startAt);
            $carbonFinishAt = Carbon::parse($finishAt);

            $diffInSeconds = $carbonFinishAt->diffInSeconds($carbonStartAt);
            $totalTime     = $diffInSeconds - $totalDifferenceInSeconds;

            $formattedDiff = gmdate('H:i:s', $diffInSeconds);
            Log::info("Diferencia en formato HH:mm:ss: {$formattedDiff}");

            // Formatear totalTime a HH:MM:SS para TimeON
            $totalTimeFormatted = gmdate('H:i:s', $totalTime);
            Log::info("Total Time (TimeON) formateado: {$totalTimeFormatted}");

            return [$startAt, $finishAt, $totalTime, $totalTimeFormatted];
        } catch (Exception $e) {
            Log::error("Error al obtener tiempos de la orden: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calcular ShiftCount basado en el número de registros en orderStats
     *
     * @param \Illuminate\Support\Collection $orderStats
     * @return int
     */
    private function calculateShiftCountByEvents($orderStats)
    {
        return $orderStats->count();
    }

    /**
     * Procesar y transferir sensores a la base de datos externa
     *
     * @param \Illuminate\Support\Collection $sensors
     * @param \Illuminate\Database\Connection $externalConnection
     * @param string $startAt
     * @param string $finishAt
     * @param int $totalTime
     * @param string $modelEnvase
     * @param string $modelMeasure
     * @param mixed $unitValue
     * @param mixed $totalWeight
     * @return void
     */
    private function processSensors($sensors, $externalConnection, $startAt, $finishAt, $totalTime, $modelEnvase, $modelMeasure, $unitValue, $totalWeight, $orderId)
    {
        foreach ($sensors as $sensor) {
            try {

                


                //sumar cuantas veces se a activado el sensor por el orden usamos la sensor_history , para ser mas ligero en consulta.
                 // Hacemos una sola consulta para obtener ambos SUM
                $sums = SensorHistory::where('sensor_id', $sensor->id)
                    ->where('orderId', $orderId)
                    ->selectRaw('
                    SUM(count_order_1) as total_count_order_1,
                    SUM(downtime_count) as total_downtime_count
                    ')
                    ->first();

                // Asignamos valores, usando ?? 0 para evitar nulos si no hay registros
                $sensorCount = $sums->total_count_order_1 ?? 0;
                $timeDownSeconds = $sums->total_downtime_count ?? 0;

                Log::info("Tiempo de parada: {$timeDownSeconds} segundos.");
                Log::info("Contador activación sensor: {$sensorCount}");
                // Calcular tiempo sin pausas
                $timeDownSecondsWithNotStop = max(0, $timeDownSeconds - $totalTime);
                Log::info("Tiempo de parada sin pausa: {$timeDownSecondsWithNotStop} segundos para sensor {$sensor->name}");

                // Validar tiempo de parada
                if (is_null($timeDownSecondsWithNotStop) ||  $timeDownSecondsWithNotStop === '' ||  $timeDownSecondsWithNotStop > ($totalTime - $timeDownSecondsWithNotStop) || ($sensorCount == 0 && $sensor->sensor_type > 0))
                {
                    $timeDownSecondsWithNotStop = 0;
                }

                // Consultar la tabla sensor_count para obtener el tiempo de trabajo
                $aggregates = SensorCount::where('sensor_id', $sensor->id)
                                        ->whereBetween('created_at', [$startAt, $finishAt])
                                        ->selectRaw('MIN(time_00) AS min_time_00, MIN(time_11) AS min_time_11')
                                        ->first();

                // Evitamos valores nulos con ??
                $minTime00 = $aggregates->min_time_00 ?? 0;
                $minTime11 = $aggregates->min_time_11 ?? 0;

                // Elegimos qué mínimo usar dependiendo del sensor_type
                if ((int)$sensor->sensor_type === 0) {
                Log::info("Tiempo de trabajo quitado el tiempo de paradas noches fines de semana: {$totalTime} segundos.");
                $minTime = max($minTime11, (int)env('PRODUCTION_MIN_TIME', 1));
                } else {
                $minTime = $minTime00;
                }

                $optimalProductionTime = $sensor->optimal_production_time;
                $reducedSpeedTimeMultiplier = $sensor->reduced_speed_time_multiplier;
                $slowTime = $optimalProductionTime * $reducedSpeedTimeMultiplier;
                Log::info("Mayor que este tiempo en segundos es parada: {$slowTime}");

                $timeDownWithNotStop = gmdate('H:i:s', $timeDownSecondsWithNotStop);
                $realTimeUnit = $sensorCount > 0 ? ($totalTime - $timeDownSecondsWithNotStop) / $sensorCount : 0;
                $totalTimeFormatted = gmdate('H:i:s', max(0, $totalTime - $timeDownSecondsWithNotStop));
                $onlySlowTime = max(0, ($totalTime - $timeDownSecondsWithNotStop) - ($minTime * $sensorCount));
                $onlySlowTimeFormatted = gmdate('H:i:s', $onlySlowTime);

                $productionLineName = $sensor->productionLine->name ?? 'Desconocido';

                $brutoPeso=$sensorCount * $unitValue;
                $brutoPeso=number_format($brutoPeso, 2, '.', '');

                // Preparar datos para la base de datos externa
                $data = [
                    'IdOrder' => $orderId,
                    'IdReference' => $sensor->productName,
                    'StartAt' => $startAt,
                    'FinishAt' => $finishAt,
                    'IdClient' => "Por definir",
                    'IdSensor' => $sensor->name,
                    'TcTheoretical' => $sensor->optimal_production_time,
                    'TcUnit' => "segundos",
                    'TcAverage' => round($realTimeUnit, 2),
                    'TcMin' => $minTime,
                    'IdLine' => $productionLineName,
                    'TimeOn' => $totalTimeFormatted,
                    'TimeDown' => $timeDownWithNotStop,
                    'TimeSlow' => $onlySlowTimeFormatted,
                    'SensorCount' => $sensorCount,
                    'SensorUnitCount' => $modelEnvase,
                    'SensorWeight' => $brutoPeso, // calcularlo
                    'SensorUnitWeight' => $modelMeasure,
                    'GrossWeight01' => $unitValue, // Nuevo campo
                    'GrossWeight02' => '0.0',  // Nuevo campo
                ];

                // Insertar datos
                $externalConnection->table('sensores_por_orden')->insert($data);
                Log::info("Sensor ID {$sensor->id} transferido a la base de datos externa.");
            } catch (Exception $e) {
                Log::error("Error al procesar sensor ID {$sensor->id}: " . $e->getMessage());
                // Opcional: Continuar con el siguiente sensor o decidir si abortar la transacción
            }
        }
    }

    /**
     * Procesar y transferir modbuses a la base de datos externa
     *
     * @param \Illuminate\Support\Collection $modbuses
     * @param \Illuminate\Database\Connection $externalConnection
     * @param string $startAt
     * @param string $finishAt
     * @param int $totalTime
     * @param string $modelUnit
     * @param string $modelMeasure
     * @param mixed $totalWeight
     * @param string $orderId
     * @return void
     */
    private function processModbuses($modbuses, $externalConnection, $startAt, $finishAt, $totalTime, $modelUnit, $modelMeasure, $totalWeight, $orderId)
    {
        foreach ($modbuses as $modbus) {
            try {
                Log::info("Modbus ID {$modbus->id} iniciando cálculos para transferencia db externa.");


                $resultado = ModbusHistory::where('modbus_id', $modbus->id)
                    ->where('orderId', $orderId)
                    ->selectRaw('SUM(rec_box) as suma_rec_box, SUM(total_kg_order) as suma_total_kg_order')
                    ->first();

                $modbusCount =$resultado->suma_rec_box;// suma total del campo rec_box.
                $modbusSum = $resultado->suma_total_kg_order;// suma total del campo total_kg_order.

                $formattedSum = number_format($modbusSum, 2, '.', '');
                
                $timeDifferences = ControlWeight::selectRaw('TIMESTAMPDIFF(SECOND, LAG(created_at) OVER (ORDER BY created_at), created_at) AS interval_diff')
                    ->where('modbus_id', $modbus->id)
                    ->whereBetween('created_at', [$startAt, $finishAt])
                    ->pluck('interval_diff')
                    ->filter(fn($value) => !is_null($value) && $value > env('PRODUCTION_MIN_TIME_WEIGHT', 30));

                $minInterval = $timeDifferences->min() ?? env('PRODUCTION_MIN_TIME_WEIGHT', 30);
                $minIntervalHHMMSS = gmdate('H:i:s', $minInterval);

                $realTimeUnit = $modbusCount > 0 ? $totalTime / $modbusCount : 0;
                $minProductionTimeWeight = env('PRODUCTION_MIN_TIME_WEIGHT', 3);
                $timeDownModbus = max(0, $modbusCount * ($realTimeUnit - ($minInterval * $minProductionTimeWeight)));
                $timeSlowModbus = max(0, $totalTime - $timeDownModbus - ($minInterval * $modbusCount));

                $timeSlowFormattedModbus = gmdate('H:i:s', $timeSlowModbus);
                $timeDownFormattedModbus = gmdate('H:i:s', $timeDownModbus);
                $totalTimeFormattedModbus = gmdate('H:i:s', max(0, $totalTime));
                $productionLineName = $modbus->productionLine->name ?? 'Desconocido';
                // Preparar datos para la base de datos externa
                $data = [
                    'IdOrder' => $orderId,
                    'IdReference' => $modbus->productName,
                    'StartAt' => $startAt,
                    'FinishAt' => $finishAt,
                    'IdClient' => "Por definir",
                    'IdSensor' => $modbus->name,
                    'TcTheoretical' => $minInterval * 3.1,
                    'TcUnit' => "segundos",
                    'TcAverage' => round($realTimeUnit, 2),
                    'TcMin' => $minInterval,
                    'IdLine' => $productionLineName, // Asumiendo que no hay producción de línea asociada para modbuses
                    'TimeOn' => $totalTimeFormattedModbus,
                    'TimeDown' => $timeDownFormattedModbus,
                    'TimeSlow' => $timeSlowFormattedModbus,
                    'SensorCount' => $modbusCount,
                    'SensorUnitCount' => $modelUnit,
                    'SensorWeight' => $formattedSum,
                    'SensorUnitWeight' => $modelMeasure,
                    'GrossWeight01' => '0.0', // Nuevo campo
                    'GrossWeight02' => $totalWeight,  // Nuevo campo
                ];

                // Insertar datos
                $externalConnection->table('sensores_por_orden')->insert($data);
                Log::info("Modbus ID {$modbus->id} transferido a la base de datos externa.");
            } catch (Exception $e) {
                Log::error("Error al procesar Modbus ID {$modbus->id}: " . $e->getMessage());
                // Opcional: Continuar con el siguiente modbus o decidir si abortar la transacción
            }
        }
    }

    /**
     * Insertar datos ficticios en la tabla linea_por_orden utilizando Eloquent
     *
     * @param string $idLinea
     * @param string $idOrden
     * @param string $idReference
     * @param int $shiftCount
     * @param float $orderCount
     * @param string $orderUnit
     * @param float $sensorCount
     * @param float $umaCount
     * @param string $startAt
     * @param string $finishAt
     * @param string $timeON
     * @param string $timeDown
     * @param string $timeSlow
     * @return void
     */
    private function insertLineaPorOrden($externalConnection, $idLinea, $idOrden, $idReference, $shiftCount, $orderCount, $orderUnit, $sensorCount, $umaCount, $startAt, $finishAt, $timeON, $timeDown, $timeSlow)
    {
        try {
            $data = [
                'IdLinea' => $idLinea,
                'IdOrden' => $idOrden,
                'IdReference' => $idReference,
                'ShiftCount' => $shiftCount,
                'OrderCount' => $orderCount,
                'OrderUnit' => $orderUnit,
                'SensorCount' => $sensorCount,
                'UmaCount' => $umaCount,
                'StartAt' => $startAt,
                'FinishAt' => $finishAt,
                'TimeON' => $timeON,
                'TimeDown' => $timeDown,
                'TimeSlow' => $timeSlow,
            ];


            $externalConnection->table('linea_por_orden')->insert($data);
            Log::info("Datos insertados en linea_por_orden para IdOrden={$idOrden}.");
        } catch (Exception $e) {
            Log::error("Error al insertar en linea_por_orden para IdOrden={$idOrden}: " . $e->getMessage());
            // Opcional: Manejar la excepción según tus necesidades
        }
    }
}
