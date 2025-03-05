<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrderTopflowApi;
use Illuminate\Http\Request;

/**
 * @OA\Info(title="Topflow Production Order API", version="1.0")
 */
class ProductionOrderTopflowApiController extends Controller
{
    /**
     * Crea una nueva production order.
     *
     * @OA\Post(
     *     path="/api/topflow-production-order",
     *     summary="Crea una nueva production order",
     *     tags={"ProductionOrder-Topflow"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para crear una production order",
     *         @OA\JsonContent(
     *             required={"_id", "client_id", "code", "deliveryDate", "referId", "quantity", "paletsQtty"},
     *             @OA\Property(property="_id", type="string", example="646b247f4fd52c364ceaee6b"),
     *             @OA\Property(property="client_id", type="string", example="LineaPedido.X"),
     *             @OA\Property(property="customerOrderId", type="string", example="NumPedidoCliente"),
     *             @OA\Property(property="clientId", type="string", example="ClienteID"),
     *             @OA\Property(property="code", type="string", example="1231231231231"),
     *             @OA\Property(property="deliveryDate", type="string", format="date", example="2023-05-30"),
     *             @OA\Property(property="referId", type="string", example="ArticuloId"),
     *             @OA\Property(property="quantity", type="integer", example=25),
     *             @OA\Property(property="paletsQtty", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Production order creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="LineaPedido.X"),
     *             @OA\Property(property="customerOrderId", type="string", example="NumPedidoCliente"),
     *             @OA\Property(property="clientId", type="string", example="ClienteID"),
     *             @OA\Property(property="code", type="string", example="1231231231231"),
     *             @OA\Property(property="deliveryDate", type="string", format="date", example="2023-05-30"),
     *             @OA\Property(property="referId", type="string", example="ArticuloId"),
     *             @OA\Property(property="quantity", type="integer", example=25),
     *             @OA\Property(property="paletsQtty", type="integer", example=3),
     *             @OA\Property(property="createdAt", type="string", format="date-time", example="2023-05-22T08:14:55.219Z"),
     *             @OA\Property(property="updatedAt", type="string", format="date-time", example="2023-05-22T08:14:55.219Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados son inválidos.")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validamos los datos de entrada
        $validated = $request->validate([
            '_id'                => 'required|string|unique:production_orders_topflow_api,_id',
            'client_id'          => 'required|string',
            'customerOrderId'    => 'nullable|string',
            'clientId'           => 'nullable|string',
            'code'               => 'required|string',
            'deliveryDate'       => 'required|date',
            'referId'            => 'required|string',
            'quantity'           => 'required|integer',
            'paletsQtty'         => 'required|integer',
        ]);

        // Crear el registro
        $order = ProductionOrderTopflowApi::create($validated);

        // Convertimos "client_id" a "id" en la respuesta y ocultamos los campos internos
        $data = $order->toArray();
        $data['id'] = $data['client_id'];
        unset($data['client_id'], $data['_id']);

        return response()->json($data, 201);
    }

    /**
     * Obtiene una production order específica y transforma "client_id" a "id" ocultando campos internos.
     *
     * @OA\Get(
     *     path="/api/topflow-production-order/{_id}",
     *     summary="Obtiene una production order específica",
     *     tags={"ProductionOrder-Topflow""},
     *     @OA\Parameter(
     *         name="_id",
     *         in="path",
     *         required=true,
     *         description="Identificador interno de la production order",
     *         @OA\Schema(type="string", example="646b247f4fd52c364ceaee6b")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la production order",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="LineaPedido.X"),
     *             @OA\Property(property="customerOrderId", type="string", example="NumPedidoCliente"),
     *             @OA\Property(property="clientId", type="string", example="ClienteID"),
     *             @OA\Property(property="code", type="string", example="1231231231231"),
     *             @OA\Property(property="deliveryDate", type="string", format="date", example="2023-05-30"),
     *             @OA\Property(property="referId", type="string", example="ArticuloId"),
     *             @OA\Property(property="quantity", type="integer", example=25),
     *             @OA\Property(property="paletsQtty", type="integer", example=3),
     *             @OA\Property(property="createdAt", type="string", format="date-time", example="2023-05-22T08:14:55.219Z"),
     *             @OA\Property(property="updatedAt", type="string", format="date-time", example="2023-05-22T08:14:55.219Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Production order no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Production order no encontrada.")
     *         )
     *     )
     * )
     *
     * @param string $_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($_id)
    {
        $order = ProductionOrderTopflowApi::findOrFail($_id);
        $data = $order->toArray();
        // Transformamos "client_id" a "id" y eliminamos los campos internos
        $data['id'] = $data['client_id'];
        unset($data['client_id'], $data['_id']);
        return response()->json($data);
    }
}
