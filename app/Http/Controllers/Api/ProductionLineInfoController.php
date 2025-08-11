<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ProductionLineInfoController extends Controller
{
    /**
     * Obtiene información de la línea de producción y la hora actual del servidor
     * basado en el token proporcionado.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *     path="/api/production-line-info",
     *     summary="Información de línea de producción y hora del servidor",
     *     description="Obtiene información básica de la línea de producción identificada por token y la hora actual del servidor.",
     *     tags={"Production Lines"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="Token de la línea de producción",
     *         @OA\Schema(type="string", example="abcd1234")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información obtenida correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="production_line", type="object",
     *                 @OA\Property(property="id", type="integer", example=7),
     *                 @OA\Property(property="name", type="string", example="Línea A")
     *             ),
     *             @OA\Property(property="server_time", type="string", example="2025-08-11 12:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token no proporcionado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Token no proporcionado"),
     *             @OA\Property(property="server_time", type="string", example="2025-08-11 12:30:00")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Línea de producción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Línea de producción no encontrada"),
     *             @OA\Property(property="server_time", type="string", example="2025-08-11 12:30:00")
     *         )
     *     )
     * )
     */
    public function getInfo(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'error' => 'Token no proporcionado',
                'server_time' => Carbon::now()->format('Y-m-d H:i:s')
            ], 400);
        }

        // Buscar la línea de producción por el token
        $productionLine = ProductionLine::where('token', $token)->first();

        if (!$productionLine) {
            return response()->json([
                'error' => 'Línea de producción no encontrada',
                'server_time' => Carbon::now()->format('Y-m-d H:i:s')
            ], 404);
        }

        // Devolver la información solicitada
        return response()->json([
            'production_line' => [
                'id' => $productionLine->id,
                'name' => $productionLine->name,
            ],
            'server_time' => Carbon::now()->format('Y-m-d H:i:s')
        ]);
    }
}
