<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\OrderStat;
use App\Models\OrderMac;
use App\Models\ShiftHistory;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList; // Asegúrate de que la ruta del modelo sea la correcta


class OrderTimeService
{
    public function getTimeOrder($productionLineId)
    {
        $orderStats = OrderStat::where('production_line_id', $productionLineId)
        ->orderBy('created_at', 'desc')
        ->first();

        if (!$orderStats) {
            // Manejar el caso en que no se encuentre la orden
            return [
                'timeOnSeconds' => 0,
                'timeOnFormatted' => '00:00:00'
            ];
        }

        // 2. Definir el inicio de la orden (primer created_at de order_stats)
        $startTime = Carbon::parse($orderStats->created_at);
        Log::info("Inicio de la orden: {$startTime}");
        //ponemos $finishTime como la fecha actual
        // 3. Obtener el tiempo de cierre desde OrderMac (action = 1)
        $orderId = $orderStats->order_id;
        $orderMacFinish = OrderMac::where('orderId', $orderId)
            ->where('action', 1)
            ->first();
        if (!$orderMacFinish) {
            $finishTime = Carbon::now();
        }else{
            $finishTime = Carbon::parse($orderMacFinish->created_at);
        }



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
            Log::info("Tiempo total de producción (timeOn)!!: {$timeFirstShiftEndCreatedAt} - {$timeLastShift} - {$timeShiftCompleted} - {$totalStopSeconds} segundos)");
            
            $timeOnFormatted = gmdate('H:i:s', $timeOnSeconds);
        } else {
            // Si no hay eventos de tipo "stop start", se calcula:
            $timeOnSeconds = $diffInSeconds - $totalStopSeconds ;
            $timeOnFormatted = gmdate('H:i:s', $timeOnSeconds);
            Log::info("Tiempo total de producción! (timeOn): {$timeOnFormatted} ({$timeOnSeconds} segundos)");
        }
        Log::info("Tiempo total de producción (timeOn): {$timeOnFormatted} ({$timeOnSeconds} segundos)");
        return [
            'timeOnSeconds' => $timeOnSeconds,
            'timeOnFormatted' => $timeOnFormatted
        ];
    }
}
