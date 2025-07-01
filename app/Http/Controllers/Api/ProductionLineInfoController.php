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
