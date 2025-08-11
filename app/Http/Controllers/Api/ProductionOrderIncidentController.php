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
     * @OA\Get(
     *     path="/api/production-orders/{order}/incidents",
     *     summary="Listar incidencias de una orden de producción",
     *     description="Devuelve el listado de incidencias asociadas a la orden especificada, ordenadas por fecha desc.",
     *     tags={"Production Orders","Incidents"},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID de la orden de producción",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado obtenido correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="production_order_id", type="integer", example=123),
     *                     @OA\Property(property="reason", type="string", example="Falta de material"),
     *                     @OA\Property(property="notes", type="string", nullable=true, example="Esperando reposición"),
     *                     @OA\Property(property="created_by", type="integer", example=10),
     *                     @OA\Property(property="customer_id", type="integer", example=5),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 )
     *             )
     *         )
     *     )
     * )
     */
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
     * @OA\Post(
     *     path="/api/production-orders/{order}/incidents",
     *     summary="Crear incidencia para una orden de producción",
     *     description="Registra una incidencia asociada a la orden indicada.",
     *     tags={"Production Orders","Incidents"},
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         description="ID de la orden de producción",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", maxLength=255, example="Rotura de máquina"),
     *             @OA\Property(property="notes", type="string", nullable=true, example="Se detuvo a las 10:35"),
     *             @OA\Property(property="operator_id", type="integer", nullable=true, example=42, description="ID del operador que registra la incidencia. Por defecto 1 si no se envía.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Incidencia creada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Incidencia registrada correctamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=10),
     *                 @OA\Property(property="production_order_id", type="integer", example=123),
     *                 @OA\Property(property="reason", type="string", example="Rotura de máquina"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example="Se detuvo a las 10:35"),
     *                 @OA\Property(property="created_by", type="integer", example=42),
     *                 @OA\Property(property="customer_id", type="integer", example=5),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Orden o línea de producción no encontrada")
     * )
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
