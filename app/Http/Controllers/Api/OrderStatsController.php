<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\OrderStat;
use App\Http\Controllers\Controller;
//anadir carbon
use Carbon\Carbon;


class OrderStatsController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/order-stats/last",
     *     summary="Obtener la última estadística de un pedido",
     *     description="Retorna la última estadística de pedidos para una línea de producción usando un token.",
     *     tags={"OrderStats"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="some-production-line-token"),
     *             @OA\Property(property="production_line_id", type="integer", example=1),
     *             @OA\Property(property="order_id", type="string", example="ORD12345"),
     *             @OA\Property(property="units", type="integer", example=100),
     *             @OA\Property(property="box", type="integer", example=10),
     *             @OA\Property(property="units_box", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Última estadística de pedido obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="production_line_name", type="string", example="Linea 1"),
     *             @OA\Property(property="order_id", type="string", example="ORD12345"),
     *             @OA\Property(property="units", type="integer", example=100),
     *             @OA\Property(property="created_at", type="string", example="2024-10-15 14:35:12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token es requerido",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Token is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Línea de producción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Production line not found")
     *         )
     *     )
     * )
     */
    public function getLastOrderStat(Request $request)
    {
        if ($request->isMethod('post')) {
            // Validar los datos de entrada
            $request->validate([
                'production_line_id' => 'required|exists:production_lines,id',
                'order_id' => 'required|string|max:255',
                'units' => 'required|integer',
                'box' => 'nullable|integer',
                'units_box' => 'nullable|integer',
            ]);

            // Crear una nueva instancia de OrderStat
            $orderStat = new OrderStat();
            $orderStat->production_line_id = $request->input('production_line_id');
            $orderStat->order_id = $request->input('order_id');
            $orderStat->units = $request->input('units');
            $orderStat->box = $request->input('box');
            $orderStat->units_box = $request->input('units_box');

            // Guardar la nueva OrderStat
            $orderStat->save();

            // Devolver la respuesta en formato JSON
            return response()->json($orderStat, 201);
        }

    // Obtención del token y búsqueda de la línea de producción
    $token = $request->input('token');

    if (!$token) {
        return response()->json(['error' => 'Token is required'], 400);
    }

    $productionLine = ProductionLine::where('token', $token)->first();

    if (!$productionLine) {
        return response()->json(['error' => 'Production line not found'], 404);
    }

    // Obtiene el último OrderStat
    $lastOrderStat = OrderStat::with('productList')
        ->where('production_line_id', $productionLine->id)
        ->orderBy('id', 'desc')
        ->first();

    if (!$lastOrderStat) {
        return response()->json(['error' => 'No order stats found for the production line'], 404);
    }
    
    // Convierte los datos de OrderStat a array
    $response = $lastOrderStat->toArray();
    
    // Añade el nombre de la línea de producción
    $response['production_line_name'] = $productionLine->name;
    
    // Verifica si existe la relación y añade los campos requeridos
    if (isset($response['product_list']) && !empty($response['product_list'])) {
        $response['optimal_production_time'] = $response['product_list']['optimal_production_time'];
        $response['optimalproductionTime_weight'] = $response['product_list']['optimalproductionTime_weight'];
        
        // Si no necesitas mostrar toda la información de product_list, la puedes eliminar:
        unset($response['product_list']);
    } else {
        // Opcionalmente, manejar el caso en que no se encuentre la información de product_list
        $response['optimal_production_time'] = null;
        $response['optimalproductionTime_weight'] = null;
    }
    
    return response()->json($response, 200);
}
    /**
     * @OA\Post(
     *     path="/api/order-stats/between-dates",
     *     summary="Obtener estadísticas de pedidos entre fechas",
     *     description="Obtiene estadísticas de pedidos entre dos fechas dadas para una línea de producción específica, utilizando el token de la línea.",
     *     tags={"OrderStats"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="some-production-line-token"),
     *             @OA\Property(property="start_date", type="string", example="2024-10-01"),
     *             @OA\Property(property="end_date", type="string", example="2024-10-20")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas de pedidos entre fechas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="production_line_name", type="string", example="Linea 1"),
     *             @OA\Property(property="order_id", type="string", example="ORD12345"),
     *             @OA\Property(property="units", type="integer", example=100),
     *             @OA\Property(property="created_at", type="string", example="2024-10-15 14:35:12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Línea de producción no encontrada o sin estadísticas",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Production line not found or no order stats found for the specified dates")
     *         )
     *     )
     * )
     */
    public function getOrderStatsBetweenDates(Request $request)
    {
        // Log todos los parámetros recibidos para depuración
        \Log::info('OrderStats - Parámetros recibidos:', $request->all());
        
        // Obtiene el token o tokens de la línea de producción desde la solicitud.
        $tokenInput = $request->input('token');
        \Log::info('OrderStats - Token input recibido:', ['token' => $tokenInput]);
    
        if (!$tokenInput) {
            \Log::warning('OrderStats - Token no proporcionado');
            return response()->json(['error' => 'Token is required'], 400);
        }
        
        // Dividir los tokens si vienen separados por comas
        $tokens = [];
        if (is_array($tokenInput)) {
            // Si ya viene como array (desde formularios con múltiple selección)
            $tokens = $tokenInput;
            \Log::info('OrderStats - Tokens recibidos como array:', $tokens);
        } else {
            // Si viene como string separado por comas
            $tokens = explode(',', $tokenInput);
            \Log::info('OrderStats - Tokens después de explode:', $tokens);
            
            // Si solo hay un token pero contiene comas internas (jQuery puede enviar así los valores)
            if (count($tokens) === 1 && strpos($tokens[0], ',') !== false) {
                $tokens = explode(',', $tokens[0]);
                \Log::info('OrderStats - Tokens después de segundo explode:', $tokens);
            }
        }
        
        // Filtrar tokens vacíos
        $tokens = array_filter($tokens, function($token) {
            return !empty($token);
        });
        \Log::info('OrderStats - Tokens después de filtrar:', $tokens);
        
        // Validar los datos de entrada y establecer fechas predeterminadas si no existen
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        \Log::info('OrderStats - Fechas recibidas:', ['start' => $startDate, 'end' => $endDate]);
    
        if (!$startDate || !$endDate) {
            $today = now()->format('Y-m-d');
            $startDate = $today . ' 00:00:00';
            $endDate = $today . ' 23:59:59';
            \Log::info('OrderStats - Usando fechas predeterminadas:', ['start' => $startDate, 'end' => $endDate]);
        } else {
            // Formatear las fechas en caso de que no estén en el formato correcto
            $startDate = Carbon::parse($startDate)->format('Y-m-d H:i:s');
            $endDate = Carbon::parse($endDate)->format('Y-m-d H:i:s');
            \Log::info('OrderStats - Fechas formateadas:', ['start' => $startDate, 'end' => $endDate]);
        }
        
        // Encontrar todas las líneas de producción por los tokens
        \Log::info('OrderStats - Buscando líneas de producción con tokens:', $tokens);
        $productionLines = ProductionLine::whereIn('token', $tokens)->get();
        \Log::info('OrderStats - Líneas encontradas:', ['count' => $productionLines->count()]);
        
        if ($productionLines->isEmpty()) {
            \Log::warning('OrderStats - No se encontraron líneas de producción para los tokens proporcionados');
            return response()->json(['error' => 'No production lines found for the provided tokens: ' . implode(',', $tokens)], 404);
        }
        
        $productionLineIds = $productionLines->pluck('id')->toArray();
        $productionLineNames = $productionLines->pluck('name', 'id')->toArray();
        \Log::info('OrderStats - IDs de líneas encontradas:', $productionLineIds);
        
        // Consultar las estadísticas de pedidos entre las fechas especificadas para todas las líneas seleccionadas
        \Log::info('OrderStats - Consultando estadísticas con parámetros:', [
            'production_line_ids' => $productionLineIds,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        $orderStats = OrderStat::whereIn('production_line_id', $productionLineIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
        
        \Log::info('OrderStats - Estadísticas encontradas:', ['count' => $orderStats->count()]);
    
        if ($orderStats->isEmpty()) {
            \Log::warning('OrderStats - No se encontraron estadísticas para las fechas y líneas especificadas');
            return response()->json(['error' => 'No order stats found for the specified dates and production lines'], 404);
        }
    
        $response = $orderStats->map(function ($orderStat) use ($productionLineNames) {
            $orderStatArray = $orderStat->toArray();
            $orderStatArray['production_line_name'] = $productionLineNames[$orderStat->production_line_id] ?? 'Unknown';
            
            // Buscar la orden de producción correspondiente para obtener el estado
            $orderId = $orderStat->order_id;
            $productionOrder = \App\Models\ProductionOrder::where('order_id', $orderId)->first();
            
            // Determinar el estado basado en finished_at y status
            if ($productionOrder) {
                \Log::info('OrderStats - Orden encontrada:', [
                    'order_id' => $orderId,
                    'finished_at' => $productionOrder->finished_at,
                    'status' => $productionOrder->status
                ]);
                
                if ($productionOrder->finished_at) {
                    $orderStatArray['status'] = 'completed'; // Finalizada (tiene fecha de finalización)
                } else {
                    // Mapear el status numérico a un string para la UI
                    switch ($productionOrder->status) {
                        case 0:
                            $orderStatArray['status'] = 'pending'; // Pendiente
                            break;
                        case 1:
                            $orderStatArray['status'] = 'in_progress'; // Tarjeta en curso
                            break;
                        case 2:
                            $orderStatArray['status'] = 'completed'; // Finalizada
                            break;
                        case 3:
                        case 4:
                        case 5:
                            $orderStatArray['status'] = 'error'; // En incidencia
                            break;
                        default:
                            $orderStatArray['status'] = 'unknown'; // Desconocido
                    }
                }
            } else {
                \Log::warning('OrderStats - No se encontró la orden de producción:', ['order_id' => $orderId]);
                $orderStatArray['status'] = 'unknown'; // No se encontró la orden
            }
            
            return $orderStatArray;
        });
        
        \Log::info('OrderStats - Respuesta generada con éxito:', ['count' => count($response)]);
    
        return response()->json($response, 200);
    }
    
}