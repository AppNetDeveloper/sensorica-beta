<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupplierOrder;
use App\Models\SupplierOrderReference;

/**
 * @OA\Schema(
 *     schema="Refer",
 *     type="object",
 *     required={"id", "company_name", "descrip", "value", "measure"},
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         example="Z465",
 *         description="Código único de la referencia"
 *     ),
 *     @OA\Property(
 *         property="company_name",
 *         type="string",
 *         example="Nombre Proveedor",
 *         description="Nombre del proveedor"
 *     ),
 *     @OA\Property(
 *         property="descrip",
 *         type="string",
 *         example="Descripción de la materia prima",
 *         description="Descripción detallada de la materia prima"
 *     ),
 *     @OA\Property(
 *         property="value",
 *         type="number",
 *         format="float",
 *         example=300,
 *         description="Valor o peso asignado a la referencia"
 *     ),
 *     @OA\Property(
 *         property="measure",
 *         type="string",
 *         example="Kg",
 *         description="Unidad de medida del valor"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SupplierOrder",
 *     type="object",
 *     required={"supplierOrderId", "orderLine", "quantity", "unit", "barcode", "refer"},
 *     @OA\Property(
 *         property="supplierOrderId",
 *         type="integer",
 *         example=231167,
 *         description="Identificador del pedido que se realiza al proveedor"
 *     ),
 *     @OA\Property(
 *         property="orderLine",
 *         type="string",
 *         example="231167.1",
 *         description="Línea del pedido (número de orden concatenado con el número de línea)"
 *     ),
 *     @OA\Property(
 *         property="quantity",
 *         type="integer",
 *         example=1,
 *         description="Cantidad de unidades solicitadas para esa línea"
 *     ),
 *     @OA\Property(
 *         property="unit",
 *         type="string",
 *         example="Palet",
 *         description="Unidad de medida en la que se expresa la cantidad"
 *     ),
 *     @OA\Property(
 *         property="barcode",
 *         type="string",
 *         example="ean 128",
 *         description="Código de barras o identificación RFID asociado al pedido"
 *     ),
 *     @OA\Property(
 *         property="refer",
 *         ref="#/components/schemas/Refer"
 *     )
 * )
 */
class SupplierOrderController extends Controller
{
    /**
     * Endpoint para registrar pedidos de proveedores.
     *
     * URL: https://domain/api/supplier-order/store
     *
     * Ejemplo de JSON:
     * {
     *   "supplierOrderId": 231167,
     *   "orderLine": "231167.1",
     *   "quantity": 1,
     *   "unit": "Palet",
     *   "barcode": "ean 128",
     *   "refer": {
     *     "id": "Z465",
     *     "company_name": "Nombre Proveedor",
     *     "descrip": "Descripción de la materia prima",
     *     "value": 300,
     *     "measure": "Kg"
     *   }
     * }
     *
     * Comentario de cada campo:
     * - supplierOrderId: Identificador del pedido que se realiza al proveedor.
     * - orderLine: Línea del pedido (número de orden concatenado con el número de línea).
     * - quantity: Cantidad de unidades solicitadas.
     * - unit: Unidad de medida, en este caso "Palet".
     * - barcode: Código de barras o identificación RFID.
     * - refer: Objeto con información adicional sobre la materia prima.
     *
     * @OA\Post(
     *     path="/api/supplier-order/store",
     *     summary="Registrar pedido de proveedor",
     *     tags={"Supplier Order"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para registrar el pedido de proveedor",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierOrder")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Pedido de proveedor registrado exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/SupplierOrder")
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
            'supplierOrderId'      => 'required|integer',
            'orderLine'            => 'required|string',
            'quantity'             => 'required|integer',
            'unit'                 => 'required|string',
            'barcode'              => 'required|string',
            'refer.id'             => 'required|string',
            'refer.company_name'   => 'required|string',
            'refer.descrip'        => 'required|string',
            'refer.value'          => 'required|numeric',
            'refer.measure'        => 'required|string',
        ]);

        // Creamos o actualizamos la referencia (materia prima)
        $reference = SupplierOrderReference::updateOrCreate(
            ['id' => $validated['refer']['id']],
            $validated['refer']
        );

        // Preparamos los datos para el pedido, asociando la referencia creada
        $orderData = [
            'supplier_order_id' => $validated['supplierOrderId'],
            'order_line'        => $validated['orderLine'],
            'quantity'          => $validated['quantity'],
            'unit'              => $validated['unit'],
            'barcode'           => $validated['barcode'],
            'refer_id'          => $reference->id,
        ];

        // Creamos el pedido
        $supplierOrder = SupplierOrder::create($orderData);

        // Cargamos la relación de la referencia para devolverla en la respuesta
        $supplierOrder->load('reference');

        return response()->json($supplierOrder, 201);
    }
}
