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
        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $token)->first();

        if (!$productionLine) {
            return response()->json(['message' => 'Línea de producción no encontrada'], 404);
        }

        // Buscar la última entrada en shift_history para la línea de producción
        // Cargando las relaciones operator y shiftList
        $shiftHistory = ShiftHistory::with(['operator', 'shiftList'])
            ->where('production_line_id', $productionLine->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$shiftHistory) {
            return response()->json(['message' => 'No se encontró historial de turnos para esta línea de producción'], 404);
        }

        // --- MODIFICACIÓN INICIO ---
        // Si el último registro no es de type "shift" y action "start"...
        if ($shiftHistory->type !== 'shift' || $shiftHistory->action !== 'start') {
            // ...buscar el último "shift_start"
            $shiftStart = ShiftHistory::with(['operator', 'shiftList']) // Cargar relaciones aquí también si las necesitas del shiftStart
                ->where('production_line_id', $productionLine->id)
                ->where('type', 'shift')
                ->where('action', 'start')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$shiftStart) {
                // No podemos calcular pausas si no hay un inicio de turno previo
                return response()->json(['message' => 'No se encontró un registro de inicio de turno (shift start) previo para calcular pausas.'], 404);
            }

            // Calcular la duración total de las pausas desde el último shiftStart hasta ahora
            $totalPauseSeconds = $this->calculateTotalPauseDuration(
                $productionLine->id,
                $shiftStart->created_at,
                now() // Calcular hasta el momento actual
            );

            // Devolver el último historial, la fecha del último inicio de turno y la duración total de la pausa
            return response()->json([
                'data' => $shiftHistory,
                'shift_start_date' => $shiftStart->created_at,
                'total_pause_duration_seconds' => $totalPauseSeconds // Nuevo campo
            ]);
        }
        // --- MODIFICACIÓN FIN ---

        // Si el último registro SÍ es un 'shift start', devolverlo directamente
        return response()->json([
            'data' => $shiftHistory,
            'shift_start_date' => $shiftHistory->created_at,
            'total_pause_duration_seconds' => 0
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