<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Post(
 *     path="/api/order-notice/store",
 *     tags={"OrderNotice"},
 *     summary="Recibir un order notice",
 *     description="Recibe un JSON con la información del pedido",
 *     @OA\RequestBody(
 *         required=true,
 *         description="JSON con la información del order notice",
 *         @OA\JsonContent(
 *             type="object",
 *             required={
 *                 "orderId", "quantity", "unit", "refer"
 *             },
 *             @OA\Property(property="orderId", type="integer", description="ID del pedido"),
 *             @OA\Property(property="customerOrderId", type="string", description="ID del pedido del cliente"),
 *             @OA\Property(property="customerReferenceId", type="string", description="ID de referencia del cliente"),
 *             @OA\Property(property="barcode", type="string", description="Código de barras"),
 *             @OA\Property(property="quantity", type="integer", description="Cantidad de productos"),
 *             @OA\Property(property="unit", type="string", description="Unidad de medida"),
 *             @OA\Property(property="isAuto", type="integer", description="Indica si es un pedido automático"),
 *             @OA\Property(property="refer", type="object", description="Información adicional del pedido",
 *                 @OA\Property(property="_id", type="string", description="ID de referencia"),
 *                 @OA\Property(property="company_name", type="string", description="Nombre de la empresa"),
 *                 @OA\Property(property="id", type="integer", description="ID del pedido en la base de datos"),
 *                 @OA\Property(property="families", type="string", description="Familias relacionadas"),
 *                 @OA\Property(property="customerId", type="string", description="ID del cliente"),
 *                 @OA\Property(property="eanCode", type="string", description="Código EAN"),
 *                 @OA\Property(property="rfidCode", type="string", description="Código RFID"),
 *                 @OA\Property(property="descrip", type="string", description="Descripción del producto"),
 *                 @OA\Property(property="value", type="number", format="float", description="Valor del producto"),
 *                 @OA\Property(property="magnitude", type="string", description="Magnitud (por ejemplo, Masa)"),
 *                 @OA\Property(property="envase", type="string", description="Envase del producto"),
 *                 @OA\Property(property="envase_value", type="string", description="Valor del envase"),
 *                 @OA\Property(property="measure", type="string", description="Unidad de medida del producto"),
 *                 @OA\Property(property="groupLevel", type="array", description="Detalles de los niveles de grupo",
 *                     @OA\Items(
 *                         @OA\Property(property="id", type="string", description="ID del grupo"),
 *                         @OA\Property(property="level", type="integer", description="Nivel del grupo"),
 *                         @OA\Property(property="uds", type="integer", description="Unidades por nivel"),
 *                         @OA\Property(property="total", type="number", format="float", description="Total de la medida"),
 *                         @OA\Property(property="measure", type="string", description="Unidad de medida del grupo"),
 *                         @OA\Property(property="eanCode", type="string", description="Código EAN del grupo"),
 *                         @OA\Property(property="envase", type="string", description="Envase del grupo")
 *                     )
 *                 ),
 *                 @OA\Property(property="standardTime", type="array", description="Tiempos estándar de producción",
 *                     @OA\Items(
 *                         @OA\Property(property="value", type="string", description="Valor del tiempo estándar"),
 *                         @OA\Property(property="magnitude1", type="string", description="Magnitud 1 (por ejemplo, Uds/hr)"),
 *                         @OA\Property(property="measure1", type="string", description="Medida 1 (por ejemplo, uds)"),
 *                         @OA\Property(property="magnitude2", type="string", description="Magnitud 2"),
 *                         @OA\Property(property="measure2", type="string", description="Medida 2"),
 *                         @OA\Property(property="machineId", type="array", description="IDs de máquinas relacionadas",
 *                             @OA\Items(type="string", description="ID de la máquina")
 *                         )
 *                     )
 *                 ),
 *                 @OA\Property(property="createdAt", type="string", format="date-time", description="Fecha de creación"),
 *                 @OA\Property(property="updatedAt", type="string", format="date-time", description="Fecha de última actualización")
 *             )
 *         )
 *     ),
 *     @OA\Parameter(
 *         name="Authorization",
 *         in="header",
 *         required=true,
 *         description="Token de la línea de producción",
 *         @OA\Schema(
 *             type="string",
 *             example="Bearer your-production-line-token"
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Order notice recibido correctamente",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Order notice processed successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Error de validación",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="object")
 *         )
 *     )
 * )
 */

class OrderNoticeController extends Controller
{
    public function store(Request $request)
    {

        // Log para ver todos los encabezados de la solicitud
        Log::info('Request Headers:', ['headers' => $request->headers->all()]);

        // Obtener el token del encabezado Authorization
        $token = $request->bearerToken();
        Log::info('Received token:', ['token' => $token]);

        // Verificar si el token existe en la tabla production_lines
        $productionLine = ProductionLine::where('token', $token)->first();

        if (!$productionLine) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid or missing token'
            ], 401);
        }

        // Si el token es válido, extraemos la información de la línea de producción
        Log::info('Production Line data:', ['production_line' => $productionLine]);

        // Validar los datos del pedido
        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer',
            'quantity' => 'required|integer',
            'unit' => 'required|string',
            'refer.descrip' => 'required|string',
            'refer.groupLevel' => 'required|array',
            'refer.groupLevel.*.id' => 'nullable|string',
            'refer.groupLevel.*.level' => 'required|integer',
            'refer.groupLevel.*.uds' => 'required|integer',
            'refer.groupLevel.*.total' => 'required|numeric',
            'refer.groupLevel.*.measure' => 'required|string',
            'refer.groupLevel.*.eanCode' => 'nullable|string',
            'refer.groupLevel.*.envase' => 'nullable|string',
            'refer.standardTime' => 'nullable|array',
            'refer.standardTime.*.value' => 'nullable|string',
            'refer.standardTime.*.magnitude1' => 'nullable|string',
            'refer.standardTime.*.measure1' => 'nullable|string',
            'refer.standardTime.*.magnitude2' => 'nullable|string',
            'refer.standardTime.*.measure2' => 'nullable|string',
        ]);

        // Validación fallida
        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 400);
        }

        // Si la validación es correcta, procesar los datos
        Log::info('Received Order Notice', ['data' => $request->all()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Order notice processed successfully'
        ], 200);
    }
}
