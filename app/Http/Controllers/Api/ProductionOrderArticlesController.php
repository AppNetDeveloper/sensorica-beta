<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\OriginalOrderArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductionOrderArticlesController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/production-orders/{id}/articles",
     *     summary="Obtener artículos asociados a una orden de producción",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la orden de producción",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de artículos asociados a la orden de producción",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="codigo_articulo", type="string"),
     *                 @OA\Property(property="descripcion_articulo", type="string"),
     *                 @OA\Property(property="grupo_articulo", type="string"),
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada"
     *     )
     * )
     */
    public function getArticles($id)
    {
        // Buscar la orden de producción
        $order = ProductionOrder::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden de producción no encontrada'
            ], 404);
        }

        // Verificar si la orden tiene un original_order_process_id
        if (!$order->original_order_process_id) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Esta orden no tiene un proceso original asociado'
            ]);
        }

        // Obtener los artículos asociados al proceso original
        $articles = OriginalOrderArticle::where('original_order_process_id', $order->original_order_process_id)
            ->get(['id', 'codigo_articulo', 'descripcion_articulo', 'grupo_articulo']);

        return response()->json([
            'success' => true,
            'data' => $articles
        ]);
    }
}
