<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderIncident;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionOrderIncidentController extends Controller
{
    /**
     * Obtener todas las incidencias de una orden de producción
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($orderId)
    {
        $incidents = ProductionOrderIncident::with(['createdBy:id,name'])
            ->where('production_order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $incidents
        ]);
    }

    /**
     * Registrar una nueva incidencia
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $orderId)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string'
        ]);

        // Verificar que la orden existe
        $order = ProductionOrder::findOrFail($orderId);
        
        // Obtener el cliente a través de la línea de producción
        $productionLine = $order->productionLine;
        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la línea de producción asociada a esta orden'
            ], 404);
        }
        
        $customerId = $productionLine->customer_id;

        // Obtener el ID del operador del cuerpo de la solicitud o usar 1 como valor por defecto
        $operatorId = $request->input('operator_id', 1);
        
        $incident = new ProductionOrderIncident([
            'production_order_id' => $order->id,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => $operatorId,
            'customer_id' => $customerId
        ]);
        
        $incident->save();

        // Cargar la relación createdBy para la respuesta
        $incident->load('createdBy:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Incidencia registrada correctamente',
            'data' => $incident
        ], 201);
    }
}
