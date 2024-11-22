<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\Scada;
use App\Models\MonitorConnection;

class ProductionLineStatusController extends Controller
{
    /**
     * Obtener el estado de una línea de producción.
     *
     * @param string $token
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusByToken($token)
    {
        try {
            // Buscar el token en la tabla scada
            $scada = Scada::where('token', $token)->first();
    
            if ($scada) {
                // Si el token existe en scada, buscar la línea de producción asociada
                $productionLine = ProductionLine::find($scada->production_line_id);
            } else {
                // Si el token no existe en scada, buscarlo directamente en production_lines
                $productionLine = ProductionLine::where('token', $token)->first();
            }
    
            if (!$productionLine) {
                return response()->json([
                    'error' => 'Production line not found.',
                    'searched_token' => $token,
                ], 404);
            }
    
            // Obtener conexiones de monitor asociadas
            $monitorConnections = MonitorConnection::where('production_line_id', $productionLine->id)->get(['name', 'last_status']);
    
            if ($monitorConnections->isEmpty()) {
                return response()->json([
                    'error' => 'No monitor connections found for this production line.',
                    'searched_token' => $token,
                    'production_line_id' => $productionLine->id,
                ], 404);
            }
    
            // Construir la respuesta
            return response()->json([
                'production_line' => $productionLine->name,
                'monitor_connections' => $monitorConnections
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
                'searched_token' => $token,
            ], 500);
        }
    }    
}
