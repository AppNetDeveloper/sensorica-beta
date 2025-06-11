<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\Operator;
use App\Models\BarcodeScan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Barcode Scans",
 *     description="API para gestionar escaneos de códigos de barras"
 * )
 */
class BarcodeScansController extends Controller
{
    /**
     * Obtener el último código de barras escaneado para una línea de producción específica
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/api/barcode-scans",
     *     operationId="getLastBarcode",
     *     tags={"Barcode Scans"},
     *     summary="Obtener el último código de barras escaneado",
     *     description="Devuelve el último código de barras escaneado para una línea de producción específica identificada por su token",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="Token de la línea de producción",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="barcode", type="string", example="ABC123456789"),
     *                 @OA\Property(property="production_order_id", type="integer", example=1),
     *                 @OA\Property(property="operator_id", type="integer", example=5),
     *                 @OA\Property(property="scanned_at", type="string", format="date-time", example="2025-06-10T18:15:53+02:00"),
     *                 @OA\Property(property="barcode_data", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token de línea de producción requerido"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Línea de producción no encontrada con el token proporcionado")
     *         )
     *     )
     * )
     */
    public function getLastBarcode(Request $request)
    {
        // Validar que se proporcione el token
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de línea de producción requerido',
                'errors' => $validator->errors()
            ], 400);
        }

        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $request->token)->first();
        
        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'Línea de producción no encontrada con el token proporcionado'
            ], 404);
        }

        // Buscar el último código de barras escaneado para esta línea
        $lastBarcodeScan = BarcodeScan::where('production_line_id', $productionLine->id)
            ->orderBy('scanned_at', 'desc')
            ->first();

        if (!$lastBarcodeScan) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron códigos de barras escaneados para esta línea de producción'
            ], 404);
        }

        // Devolver el último código de barras escaneado
        return response()->json([
            'success' => true,
            'data' => [
                'barcode' => $lastBarcodeScan->barcode,
                'production_order_id' => $lastBarcodeScan->production_order_id,
                'operator_id' => $lastBarcodeScan->operator_id,
                'scanned_at' => $lastBarcodeScan->scanned_at,
                'barcode_data' => $lastBarcodeScan->barcode_data
            ]
        ]);
    }

    /**
     * Registrar un nuevo escaneo de código de barras
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/barcode-scans",
     *     operationId="storeBarcodeScans",
     *     tags={"Barcode Scans"},
     *     summary="Registrar un nuevo escaneo de código de barras",
     *     description="Almacena un nuevo código de barras escaneado para una línea de producción, orden y operador específicos",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token", "production_order_id", "operator_id", "barcode"},
     *             @OA\Property(property="token", type="string", example="abc123", description="Token de la línea de producción"),
     *             @OA\Property(property="production_order_id", type="integer", example=1, description="ID de la orden de producción"),
     *             @OA\Property(property="operator_id", type="integer", example=5, description="ID del operador"),
     *             @OA\Property(property="barcode", type="string", example="ABC123456789", description="Código de barras escaneado"),
     *             @OA\Property(property="barcode_data", type="string", format="json", example="{\"type\":\"product\",\"batch\":\"LOT123\"}", description="Datos adicionales del código de barras en formato JSON (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Código de barras registrado correctamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="production_order_id", type="integer", example=1),
     *                 @OA\Property(property="production_line_id", type="integer", example=3),
     *                 @OA\Property(property="operator_id", type="integer", example=5),
     *                 @OA\Property(property="barcode", type="string", example="ABC123456789"),
     *                 @OA\Property(property="barcode_data", type="object", nullable=true),
     *                 @OA\Property(property="scanned_at", type="string", format="date-time", example="2025-06-10T18:15:53+02:00"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-10T18:15:53+02:00"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-10T18:15:53+02:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Recurso no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Línea de producción no encontrada con el token proporcionado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al registrar el código de barras"),
     *             @OA\Property(property="error", type="string", example="Mensaje de error detallado")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'production_order_id' => 'required|exists:production_orders,id',
            'operator_id' => 'required|exists:operators,id',
            'barcode' => 'required|string',
            'barcode_data' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Buscar la línea de producción por token
            $productionLine = ProductionLine::where('token', $request->token)->first();
            
            if (!$productionLine) {
                return response()->json([
                    'success' => false,
                    'message' => 'Línea de producción no encontrada con el token proporcionado'
                ], 404);
            }

            // Crear el nuevo registro de escaneo
            $barcodeScan = BarcodeScan::create([
                'production_order_id' => $request->production_order_id,
                'production_line_id' => $productionLine->id,
                'operator_id' => $request->operator_id,
                'barcode' => $request->barcode,
                'barcode_data' => $request->barcode_data ? json_decode($request->barcode_data, true) : null,
                'scanned_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Código de barras registrado correctamente',
                'data' => $barcodeScan
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar código de barras: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el código de barras',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
