<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShiftList;
use App\Models\ProductionLine;  // Asegúrate de importar el modelo

class ShiftListController extends Controller
{
    /**
     * Retorna la lista de turnos filtrada por el token de la línea de producción.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @OA\Get(
     *     path="/api/shift-lists",
     *     summary="Listar turnos por línea de producción",
     *     description="Devuelve la lista de turnos para la línea de producción identificada por el token.",
     *     tags={"Shifts"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="Token de la línea de producción",
     *         @OA\Schema(type="string", example="abc123token")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de turnos obtenida correctamente",
     *         @OA\JsonContent(type="array", @OA\Items(type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="production_line_id", type="integer", example=3),
     *             @OA\Property(property="name", type="string", example="Turno Mañana"),
     *             @OA\Property(property="start_time", type="string", example="06:00:00"),
     *             @OA\Property(property="end_time", type="string", example="14:00:00")
     *         ))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token de línea de producción no proporcionado",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="Token de línea de producción no proporcionado"))
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró la línea de producción asociada al token",
     *         @OA\JsonContent(@OA\Property(property="error", type="string", example="No se encontró la línea de producción asociada al token"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Obtener el token desde el query parameter "token"
        $token = $request->input('token');

        if (!$token) {
            return response()->json(['error' => 'Token de línea de producción no proporcionado'], 400);
        }

        // Buscar el registro de production_line asociado al token
        $productionLine = ProductionLine::where('token', $token)->first();

        if (!$productionLine) {
            return response()->json(['error' => 'No se encontró la línea de producción asociada al token'], 404);
        }

        // Utilizar el id de la línea de producción para filtrar los registros en shift_lists
        $shiftLists = ShiftList::where('production_line_id', $productionLine->id)->get();

        return response()->json($shiftLists);
    }
}
