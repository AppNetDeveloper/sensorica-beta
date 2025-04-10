<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use App\Models\ShiftHistory;
use Illuminate\Http\Request;

class ShiftHistoryController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/shift-history/production-line/{token}/last",
     *     summary="Obtiene la última entrada de shift_history para una línea de producción por token, incluyendo los datos del operario",
     *     tags={"Shift History"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de la línea de producción",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *              @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró la línea de producción o el historial de turnos"
     *     )
     * )
     *
     * Obtiene la última entrada en shift_history asociada a una línea de producción a partir del token
     * e incluye la información del operario relacionado.
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
        // Usamos with('operator') para cargar la relación del operario
        $shiftHistory = ShiftHistory::with('operator')
            ->where('production_line_id', $productionLine->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$shiftHistory) {
            return response()->json(['message' => 'No se encontró historial de turnos para esta línea de producción'], 404);
        }

        return response()->json(['data' => $shiftHistory]);
    }
}
