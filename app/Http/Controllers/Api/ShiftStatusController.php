<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use Illuminate\Http\JsonResponse;

class ShiftStatusController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/shift/statuses",
     *     summary="Obtener el estado actual de turnos por línea de producción",
     *     description="Retorna, para cada línea de producción, el último evento de turno registrado (tipo y acción).",
     *     tags={"Shifts"},
     *     @OA\Response(
     *         response=200,
     *         description="Listado de estados de turno por línea",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="line_id", type="integer", example=3),
     *                 @OA\Property(
     *                     property="last_shift",
     *                     type="object",
     *                     nullable=true,
     *                     @OA\Property(property="type", type="string", example="DAY"),
     *                     @OA\Property(property="action", type="string", example="START")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getStatuses(): JsonResponse
    {
        $productionLines = ProductionLine::with('lastShiftHistory')->get();
        
        $statuses = $productionLines->map(function($line) {
            return [
                'line_id' => $line->id,
                'last_shift' => $line->lastShiftHistory ? [
                    'type' => $line->lastShiftHistory->type,
                    'action' => $line->lastShiftHistory->action
                ] : null
            ];
        });

        return response()->json($statuses);
    }
}
