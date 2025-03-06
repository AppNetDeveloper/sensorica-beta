<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrderTopflowApi;
use Illuminate\Http\Request;


class ProductionOrderTopflowApiController extends Controller
{
    /**
     * Crea una nueva orden de producción.
     *
     * @OA\Post(
     *     path="/api/topflow-production-order",
     *     summary="Crea una nueva orden de producción",
     *     tags={"ProductionOrder"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para crear una orden de producción",
     *         @OA\JsonContent(
     *             required={"mongoId", "client_id", "code", "deliveryDate", "referId", "quantity", "paletsQtty"},
     *             @OA\Property(
     *                 property="mongoId",
     *                 type="string",
     *                 example="646b247f4fd52c364ceaee6b",
     *                 description="Identificador interno (se asignará al campo _id)"
     *             ),
     *             @OA\Property(
     *                 property="client_id",
     *                 type="string",
     *                 example="LineaPedido.X",
     *                 description="Número de Pedido Cliente en XGest + numerador de línea de pedido"
     *             ),
     *             @OA\Property(
     *                 property="customerOrderId",
     *                 type="string",
     *                 example="NumPedidoCliente",
     *                 description="Número de Pedido del Cliente"
     *             ),
     *             @OA\Property(
     *                 property="clientId",
     *                 type="string",
     *                 example="ClienteID",
     *                 description="Referencia del Pedido del Cliente"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 example="1231231231231",
     *                 description="Código de Barras / RFID"
     *             ),
     *             @OA\Property(
     *                 property="deliveryDate",
     *                 type="string",
     *                 format="date",
     *                 example="2023-05-30",
     *                 description="Fecha de Expedición"
     *             ),
     *             @OA\Property(
     *                 property="referId",
     *                 type="string",
     *                 example="ArticuloId",
     *                 description="Referencia o Artículo"
     *             ),
     *             @OA\Property(
     *                 property="quantity",
     *                 type="integer",
     *                 example=25,
     *                 description="Cantidad"
     *             ),
     *             @OA\Property(
     *                 property="paletsQtty",
     *                 type="integer",
     *                 example=3,
     *                 description="Número de palets"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Orden de producción creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="LineaPedido.X", description="Número de Pedido Cliente transformado"),
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
        $validated = $request->validate([
            '_id'           => 'required|string|unique:production_orders_topflow_api,_id',
            'client_id'         => 'required|string',
            'customerOrderId'   => 'nullable|string',
            'clientId'          => 'nullable|string',
            'code'              => 'required|string',
            'deliveryDate'      => 'required|date',
            'referId'           => 'required|string',
            'quantity'          => 'required|integer',
            'paletsQtty'        => 'required|integer',
        ]);



        $order = ProductionOrderTopflowApi::create($validated);

        // En la respuesta, transformamos "client_id" a "id" y ocultamos "client_id" y "_id"
        $data = $order->toArray();
        $data['id'] = $data['client_id'];
        unset($data['client_id']);

        return response()->json($data, 201);
    }

    /**
     * Obtiene una orden de producción específica y transforma "client_id" a "id".
     *
     * @OA\Get(
     *     path="/api/topflow-production-order/{mongoId}",
     *     summary="Obtiene una orden de producción específica",
     *     tags={"ProductionOrder"},
     *     @OA\Parameter(
     *         name="_id",
     *         in="path",
     *         required=true,
     *         description="Identificador interno (MongoId) de la orden de producción",
     *         @OA\Schema(type="string", example="646b247f4fd52c364ceaee6b")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la orden de producción",
     *         @OA\JsonContent(
     *             @OA\Property(property="_id", type="string", example="LineaPedido.X", description="Número de Pedido Cliente transformado"),
     *             @OA\Property(property="customerOrderId", type="string", example="NumPedidoCliente"),
     *             @OA\Property(property="id", type="string", example="ClienteID"),
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
     *         description="Orden de producción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Orden de producción no encontrada.")
     *         )
     *     )
     * )
     *
     * @param string $mongoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($_id)
    {
        $order = ProductionOrderTopflowApi::where('_id', $_id)->firstOrFail();
        $data = $order->toArray();
        unset($data['id']); //quitamos el id original de mi tabla para poder mostrar el client_id
        $data['id'] = $data['client_id'];
        unset($data['client_id']);
        return response()->json($data);
    }
}
