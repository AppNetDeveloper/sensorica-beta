<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductionLine;
use App\Models\ShiftHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionLineController extends Controller
{
    /**
     * Get the current status of all production lines for a specific customer
     * 
     * @param \Illuminate\Http\Request $request
     * @param int|null $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatuses(Request $request, $customerId = null)
    {
        // Si el ID no viene en la URL (GET), lo tomamos del body (POST)
        if (!$customerId) {
            $customerId = $request->input('customerId');
        }
        
        // Buscar el cliente por su ID
        $customer = Customer::find($customerId);
        
        if (!$customer) {
            return response()->json(['error' => 'Cliente no encontrado!'], 404);
        }
        
        // Obtener la última entrada de shift_history para cada línea de producción
        $latestShiftHistories = ShiftHistory::select(
                'production_line_id',
                'type',
                'action',
                'created_at',
                DB::raw('MAX(created_at) as latest_date')
            )
            ->groupBy('production_line_id')
            ->get()
            ->keyBy('production_line_id');
        
        // Obtener las líneas de producción asociadas al cliente
        $productionLines = ProductionLine::where('customer_id', $customer->id)->get();
        
        $statuses = [];
        
        foreach ($productionLines as $line) {
            // Verificar si hay un registro de shift_history para esta línea
            if (isset($latestShiftHistories[$line->id])) {
                $latestShift = ShiftHistory::where('production_line_id', $line->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($latestShift) {
                    // Obtener el nombre del operador si existe
                    $operatorName = null;
                    if ($latestShift->operator_id && $latestShift->operator) {
                        $operatorName = $latestShift->operator->name;
                    }
                    
                    $statuses[] = [
                        'production_line_id' => $line->id,
                        'production_line_name' => $line->name,
                        'type' => $latestShift->type,
                        'action' => $latestShift->action,
                        'operator_id' => $latestShift->operator_id,
                        'operator_name' => $operatorName,
                        'created_at' => $latestShift->created_at->format('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'statuses' => $statuses
        ]);
    }
}
