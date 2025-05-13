<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use App\Models\ShiftHistory;
use Illuminate\Http\Request;
use Carbon\Carbon; // Asegúrate de importar Carbon

class ShiftHistoryController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/shift-history/production-line/{token}/last",
     * summary="Obtiene la última entrada de shift_history para una línea de producción por token, incluyendo datos del operario y posible cálculo de pausa.",
     * tags={"Shift History"},
     * @OA\Parameter(
     * name="token",
     * in="path",
     * description="Token de la línea de producción",
     * required=true,
     * @OA\Schema(type="string")
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa. Puede incluir 'shift_start_date' y 'total_pause_duration_seconds' si el último registro no es un inicio de turno.",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="object", description="Último registro de ShiftHistory con relaciones cargadas."),
     * @OA\Property(property="shift_start_date", type="string", format="date-time", description="Timestamp del último inicio de turno (si aplica)."),
     * @OA\Property(property="total_pause_duration_seconds", type="integer", description="Duración total de las pausas en segundos desde el último inicio de turno (si aplica).")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="No se encontró la línea de producción, el historial de turnos o un inicio de turno previo."
     * )
     * )
     *
     * Obtiene la última entrada en shift_history asociada a una línea de producción a partir del token
     * e incluye la información del operario relacionado. Si el último registro no es un inicio de turno,
     * busca el último inicio de turno y calcula el tiempo total de pausa entre ese inicio y ahora.
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLastByProductionLineToken($token)
    {
        $productionLine = ProductionLine::where('token', $token)->first();
    
        if (!$productionLine) {
            return response()->json(['message' => 'Línea de producción no encontrada'], 404);
        }
    
        // 1) Obtengo el último registro
        $shiftHistory = ShiftHistory::with(['operator', 'shiftList'])
            ->where('production_line_id', $productionLine->id)
            ->orderBy('created_at', 'desc')
            ->first();
    
        if (!$shiftHistory) {
            return response()->json(['message' => 'No se encontró historial de turnos para esta línea de producción'], 404);
        }
    
        // 2) Compruebo si es 'shift start'
        $isShiftStart = $shiftHistory->type === 'shift' && $shiftHistory->action === 'start';
    
        // 3) Si no, busco el último 'shift start' para calcular pausas
        if (! $isShiftStart) {
            $shiftStart = ShiftHistory::where('production_line_id', $productionLine->id)
                ->where('type', 'shift')
                ->where('action', 'start')
                ->orderBy('created_at', 'desc')
                ->first();
    
            if (! $shiftStart) {
                return response()->json([
                    'message' => 'No se encontró un registro de inicio de turno (shift start) previo para calcular pausas.'
                ], 404);
            }
    
            $shiftStartDate = $shiftStart->created_at;
            $totalPause     = $this->calculateTotalPauseDuration(
                $productionLine->id,
                $shiftStartDate,
                now()
            );
            $onTime=$shiftStart->on_time;
            $downTime=$shiftStart->down_time;
            $oee=$shiftStart->oee;
            $slowTime=$shiftStart->slow_time;
            $prepareTime= $shiftStart->prepair_time;
    
        } else {
            $shiftStartDate = $shiftHistory->created_at;
            $totalPause = 0;
            $onTime= 0;
            $downTime=0;
            $oee=0;
            $slowTime=0;
            $prepareTime=0;
            
        }
    
        // 4) Transformo el modelo a array y sobreescribo shift_list si es null
        $data = $shiftHistory->toArray();
    
        if (is_null($data['shift_list'])) {
            $data['shift_list'] = [
                'id'    => 'especial',
                'start' => Carbon::parse($shiftStartDate)->format('H:i:s'),
                'end'   => Carbon::now()->format('H:i:s'),
            ];
        }
    
        // 5) Devuelvo la respuesta
        return response()->json([
            'data'                         => $data,
            'shift_start_date'            => $shiftStartDate,
            'total_pause_duration_seconds'=> $totalPause,
            'on_time'=> $onTime,
            'down_time'=> $downTime,
            'oee'=> $oee,
            'slow_time'=> $slowTime,
            'prepare_time'=> $prepareTime,
        ]);
    }
    

    /**
     * Calcula la duración total en segundos de las pausas (stop start/end)
     * entre dos momentos para una línea de producción específica.
     *
     * @param int $productionLineId
     * @param Carbon $startTime // Fecha/hora de inicio (usualmente el último shift start)
     * @param Carbon $endTime   // Fecha/hora de fin (usualmente now() o el shift end)
     * @return int Total de segundos de pausa
     */
    private function calculateTotalPauseDuration(int $productionLineId, Carbon $startTime, Carbon $endTime): int
    {
        // Obtener todos los registros de 'stop' (start y end) dentro del rango de tiempo, ordenados cronológicamente
        $stops = ShiftHistory::where('production_line_id', $productionLineId)
            ->where('type', 'stop')
            ->where('created_at', '>', $startTime) // Después del inicio del turno
            ->where('created_at', '<=', $endTime)  // Hasta el momento final considerado
            ->orderBy('created_at', 'asc')
            ->get();

        $totalPauseSeconds = 0;
        $lastStopStartTimestamp = null;

        foreach ($stops as $stop) {
            if ($stop->action === 'start') {
                // Si ya había un 'start' pendiente sin 'end', se ignora el anterior y se toma este nuevo.
                // Podrías querer registrar un aviso aquí si esto indica un problema de datos.
                $lastStopStartTimestamp = $stop->created_at;
            } elseif ($stop->action === 'end' && $lastStopStartTimestamp !== null) {
                // Se encontró un 'end' y había un 'start' pendiente: calcular duración
                $totalPauseSeconds += $stop->created_at->diffInSeconds($lastStopStartTimestamp);
                $lastStopStartTimestamp = null; // Resetear, ya que este par start/end está cerrado
            }
        }

        // Si después de recorrer todos los registros, quedó un 'start' pendiente (sin 'end' correspondiente *dentro del rango*)...
        if ($lastStopStartTimestamp !== null) {
            // ...calcular la duración desde ese último 'start' hasta el tiempo final ($endTime, que es now() en este caso)
            $totalPauseSeconds += $endTime->diffInSeconds($lastStopStartTimestamp);
        }

        return $totalPauseSeconds;
    }
}