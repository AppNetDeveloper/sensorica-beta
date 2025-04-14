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
