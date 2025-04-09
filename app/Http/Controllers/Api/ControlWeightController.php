<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Modbus;
use App\Models\ControlWeight;
use Illuminate\Http\Request;
use App\Models\OrderStat;
use App\Models\SupplierOrder;
use App\Models\ProductList;  // Asegurarse de que el modelo ProductList exista

class ControlWeightController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/control-weight/{token}",
     *     summary="Get latest control weight data by token",
     *     tags={"Control Weight"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="gross_weight", type="number"),
     *             @OA\Property(property="dimension", type="string"),
     *             @OA\Property(property="box_number", type="integer"),
     *             @OA\Property(property="last_control_weight", type="number"),
     *             @OA\Property(property="last_dimension", type="number"),
     *             @OA\Property(property="last_box_number", type="integer"),
     *             @OA\Property(property="last_barcoder", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Modbus not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getDataByToken(Request $request, $token)
    {
        // Obtener el Modbus correspondiente por token
        $modbus = Modbus::where('token', $token)->first();

        if (!$modbus) {
            return response()->json(['error' => 'Modbus not found'], 404);
        }

        // Obtener el último control weight para el Modbus especificado
        $latestControlWeight = ControlWeight::where('modbus_id', $modbus->id)
            ->latest('created_at')
            ->first();

        // Obtener datos adicionales de order_stats usando production_line_id
        $orderStats = OrderStat::where('production_line_id', $modbus->production_line_id)
            ->latest('created_at')
            ->first();

        // Obtener productName desde Modbus para buscar en product_lists
        $productName = $modbus->productName; // Asumiendo que productName existe en Modbus

        // Buscar en product_lists el registro que coincida con client_id = productName
        $productList = ProductList::where('client_id', $productName)->first();

        // Si no se encuentra un registro, asignar 0 o el valor por defecto deseado
        $teoreticoWeight = $productList ? (float) $productList->box_kg : 0;

        // Preparar los datos para la respuesta
        $response = [
            'name' => (string) ($modbus->name ?? 'NoName'),
            'gross_weight' => (float) ($modbus->last_value ?? 0),
            'dimension' => (float) ($modbus->dimension ?? 0),
            'box_number' => (int) ($modbus->rec_box + 1 ?? 0),
            // Asigna teoretico_weight basado en box_kg obtenido de product_lists
            'teoretico_weight' => $teoreticoWeight,
            'last_control_weight' => (float) ($latestControlWeight->last_control_weight ?? 0),
            'last_dimension' => (float) ($latestControlWeight->last_dimension ?? 0),
            'box_m3' => (float) ($latestControlWeight->box_m3 ?? 0),
            'last_box_number' => (int) ($modbus->rec_box ?? 0),
            'last_barcoder' => (string) ($latestControlWeight->last_barcoder ?? ''),
            'box_type' => (string) ($modbus->box_type ?? 'Cultos'),
            'order_id' => (string) ($orderStats->order_id ?? ''),
            'created_at' => (string) ($orderStats->created_at ?? ''),
            'updated_at' => (string) ($orderStats->updated_at ?? ''),
        ];

        return response()->json($response);
    }
    /**
     * @OA\Get(
     *     path="/api/control-weights/{token}/all",
     *     summary="Get all control weight data by token",
     *     tags={"Control Weight"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date-time"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="last_control_weight", type="number"),
     *                 @OA\Property(property="last_dimension", type="number"),
     *                 @OA\Property(property="last_box_number", type="integer"),
     *                 @OA\Property(property="last_barcoder", type="string"),
     *                 @OA\Property(property="last_final_barcoder", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Modbus not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
       // Método para obtener todos los registros de control weight con filtrado por rango de fechas
    public function getAllDataByToken(Request $request, $token)
    {
        // Obtener el Modbus correspondiente por token
        $modbus = Modbus::where('token', $token)->first();

        if (!$modbus) {
            return response()->json(['error' => 'Modbus not found'], 404);
        }

        // Obtener parámetros de fecha del request
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Consultar los datos filtrados por fecha
        $query = ControlWeight::where('modbus_id', $modbus->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Obtener los resultados
        $controlWeights = $query->orderBy('created_at', 'desc')->get();

        // Prepara los datos para la respuesta
        $response = $controlWeights->map(function ($weight) {
            return [
                'last_control_weight' => (float) ($weight->last_control_weight ?? 0),
                'last_dimension' => (float) ($weight->last_dimension ?? 0),
                'last_box_number' => (int) ($weight->last_box_number ?? 0),
                'last_barcoder' => (string) ($weight->last_barcoder ?? ''),
                'last_final_barcoder' => (string) ($weight->last_final_barcoder ?? ''),
                'created_at' => $weight->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($response);
    }
    /**
     * Muestra la información consolidada de control_weight para un pedido de proveedor.
     *
     * @OA\Get(
     *     path="/api/control_weight/{supplierOrderId}",
     *     summary="Obtener datos consolidados de control_weight por pedido de proveedor",
     *     tags={"Control Weight", "Supplier Order"},
     *     @OA\Parameter(
     *         name="supplierOrderId",
     *         in="path",
     *         description="Identificador del pedido de proveedor",
     *         required=true,
     *         @OA\Schema(type="integer", example=231167)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Datos consolidados del control de peso",
     *         @OA\JsonContent(
     *             @OA\Property(property="supplierOrderId", type="integer", example=231167),
     *             @OA\Property(property="totalPallets", type="integer", example=3),
     *             @OA\Property(property="totalWeight", type="number", example=900),
     *             @OA\Property(
     *                 property="pallets",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="palletNumber", type="string", example="231167.1"),
     *                     @OA\Property(property="weight", type="number", example=300),
     *                     @OA\Property(property="unit", type="string", example="Kg")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Pedido de proveedor no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Supplier order not found.")
     *         )
     *     )
     * )
     *
     * @param int $supplierOrderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($supplierOrderId)
    {
        // Buscar el pedido usando el identificador externo supplier_order_id
        $supplierOrder = SupplierOrder::where('supplier_order_id', $supplierOrderId)->first();

        if (!$supplierOrder) {
            return response()->json([
                'error' => 'Supplier order not found.'
            ], 404);
        }

        // Obtener todos los registros de control_weight asociados al pedido a través de la relación bidireccional
        $palletRecords = $supplierOrder->controlWeights; // Nota: si la relación en SupplierOrder es hasMany o belongsToMany, ajústala según la lógica

        $totalPallets = $palletRecords->count();
        // Se asume que cada registro tiene 'last_control_weight' con el peso del palet
        $totalWeight = $palletRecords->sum('last_control_weight');

        // Formatear cada palet con su número, peso y unidad
        $pallets = $palletRecords->map(function ($pallet) use ($supplierOrder) {
            return [
                'palletNumber' => $supplierOrder->supplier_order_id . '.' . $pallet->last_box_number,
                'weight'       => $pallet->last_control_weight,
                'unit'         => 'Kg'
            ];
        })->toArray();

        return response()->json([
            'supplierOrderId' => $supplierOrder->supplier_order_id,
            'totalPallets'    => $totalPallets,
            'totalWeight'     => $totalWeight,
            'pallets'         => $pallets,
        ]);
    }
}
