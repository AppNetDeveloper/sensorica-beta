<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionLine; // Importar el modelo de líneas de producción
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Production Orders API",
 *     version="1.0.0",
 *     description="API para gestionar órdenes de producción."
 * )
 *
 * @OA\Tag(
 *     name="ProductionOrders",
 *     description="Operaciones relacionadas con las órdenes de producción"
 * )
 */
class ProductionOrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/production-orders",
     *     summary="Listar órdenes de producción",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token único de la línea de producción",
     *         required=true,
     *         @OA\Schema(type="string", example="abcd1234")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado de la orden",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Columna para ordenar",
     *         required=false,
     *         @OA\Schema(type="string", example="orden")
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Dirección de ordenación (asc o desc)",
     *         required=false,
     *         @OA\Schema(type="string", example="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes de producción",
     *         @OA\JsonContent(
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="status", type="integer"),
     *                 @OA\Property(property="box", type="integer"),
     *                 @OA\Property(property="units_box", type="integer"),
     *                 @OA\Property(property="orden", type="integer"),
     *             )),
     *             @OA\Property(property="total", type="integer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token no proporcionado o no válido"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token es requerido.',
            ], 400);
        }

        $productionLine = ProductionLine::where('token', $token)->first();

        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'Línea de producción no encontrada para el token proporcionado.',
            ], 400);
        }

        $query = ProductionOrder::where('production_line_id', $productionLine->id);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('sort_by')) {
            $sortBy = $request->input('sort_by');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortBy, $sortDirection);
        }

        $orders = $query->paginate(100);

        return response()->json($orders);
    }

    /**
     * @OA\Get(
     *     path="/api/production-orders/{id}",
     *     summary="Obtener detalles de una orden específica",
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
     *         description="Detalles de la orden de producción",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="order_id", type="string"),
     *             @OA\Property(property="status", type="integer"),
     *             @OA\Property(property="box", type="integer"),
     *             @OA\Property(property="units_box", type="integer"),
     *             @OA\Property(property="orden", type="integer"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada"
     *     )
     * )
     */
    public function show($id)
    {
        $order = ProductionOrder::find($id);

        if (!$order) {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }

        return response()->json($order);
    }

    /**
     * @OA\Patch(
     *     path="/api/production-orders/{id}",
     *     summary="Actualizar orden o estado de una orden",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la orden de producción",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="orden", type="integer", description="Nuevo valor del orden", example=5),
     *             @OA\Property(property="status", type="integer", description="Nuevo estado de la orden", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orden actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Orden actualizada exitosamente."),
     *             @OA\Property(property="data", type="object", @OA\Property(property="id", type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Orden no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Orden no encontrada.")
     *         )
     *     )
     * )
     */
    public function updateOrder(Request $request, $id)
    {
        $order = ProductionOrder::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], 404);
        }

        $validatedData = $request->validate([
            'orden' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|min:0|max:5',
        ]);

        if (isset($validatedData['orden'])) {
            $order->orden = $validatedData['orden'];
        }

        if (isset($validatedData['status'])) {
            $order->status = $validatedData['status'];
        }

        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizada exitosamente.',
            'data' => [
                'id' => $order->id,
                'orden' => $order->orden,
                'status' => $order->status,
            ],
        ]);
    }
    /**
 * @OA\Post(
 *     path="/api/production-orders",
 *     summary="Crear una nueva orden de producción",
 *     tags={"ProductionOrders"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="production_line_id", type="integer", example=1),
 *             @OA\Property(property="order_id", type="string", example="231118"),
 *             @OA\Property(property="status", type="integer", example=1),
 *             @OA\Property(property="box", type="integer", example=945),
 *             @OA\Property(property="units_box", type="integer", example=30),
 *             @OA\Property(property="units", type="integer", example=28350),
 *             @OA\Property(property="orden", type="integer", example=0)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Orden creada exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Orden creada exitosamente."),
 *             @OA\Property(property="data", type="object", @OA\Property(property="id", type="integer"))
 *         )
 *     )
 * )
 */
public function store(Request $request)
{
    $validatedData = $request->validate([
        'production_line_id' => 'required|integer|exists:production_lines,id',
        'order_id' => 'required|string',
        'status' => 'required|integer|min:0|max:5',
        'box' => 'required|integer',
        'units_box' => 'required|integer',
        'units' => 'required|integer',
        'orden' => 'required|integer|min:0',
    ]);

    $order = ProductionOrder::create($validatedData);

    return response()->json([
        'success' => true,
        'message' => 'Orden creada exitosamente.',
        'data' => $order,
    ], 201);
}

/**
 * @OA\Delete(
 *     path="/api/production-orders/{id}",
 *     summary="Eliminar una orden de producción",
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
 *         description="Orden eliminada exitosamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Orden eliminada exitosamente.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Orden no encontrada"
 *     )
 * )
 */
public function destroy($id)
{
    $order = ProductionOrder::find($id);

    if (!$order) {
        return response()->json(['error' => 'Orden no encontrada'], 404);
    }

    $order->delete();

    return response()->json([
        'success' => true,
        'message' => 'Orden eliminada exitosamente.',
    ]);
}

}
