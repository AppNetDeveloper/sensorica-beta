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
        
        // Obtener los operadores seleccionados si existen
        $selectedOperators = $request->input('operators');
        \Log::info('OrderStats - Operadores seleccionados:', ['operators' => $selectedOperators]);
        
        // Obtener los filtros de OEE
        $hideZeroOEE = $request->input('hide_zero_oee', false);
        $hide100OEE = $request->input('hide_100_oee', false);
        \Log::info('OrderStats - Filtros OEE:', ['hide_zero' => $hideZeroOEE, 'hide_100' => $hide100OEE]);
        
        // Procesar los operadores seleccionados si vienen como string
        $operatorIds = [];
        if ($selectedOperators) {
            if (is_array($selectedOperators)) {
                $operatorIds = $selectedOperators;
            } else {
                $operatorIds = explode(',', $selectedOperators);
            }
            
            // Filtrar IDs vacíos
            $operatorIds = array_filter($operatorIds, function($id) {
                return !empty($id);
            });
            
            \Log::info('OrderStats - IDs de operadores procesados:', $operatorIds);
        }
        
        // Consultar las estadísticas de pedidos entre las fechas especificadas para todas las líneas seleccionadas
        \Log::info('OrderStats - Consultando estadísticas con parámetros:', [
            'production_line_ids' => $productionLineIds,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'operator_ids' => $operatorIds
        ]);
        
        // Determinar si se debe filtrar por línea de producción, por operador, o ambos
        $filterByProductionLine = !empty($productionLineIds);
        $filterByOperator = !empty($operatorIds);
        
        // Iniciar la consulta base con el filtro de fechas que siempre se aplica
        $query = OrderStat::with('operators')
            ->whereBetween('created_at', [$startDate, $endDate]);
        
        // Verificar si se ha enviado el parámetro filter_mode
        $filterMode = $request->input('filter_mode', 'operator_only');
        
        // Aplicar filtros según lo que se haya seleccionado
        if ($filterByProductionLine && $filterByOperator) {
            if ($filterMode === 'or') {
                // Filtro OR: mostrar registros que cumplan con cualquiera de las condiciones
                $query->where(function($q) use ($productionLineIds, $operatorIds) {
                    $q->whereIn('production_line_id', $productionLineIds)
                      ->orWhereHas('operators', function($subq) use ($operatorIds) {
                          $subq->whereIn('operators.id', $operatorIds);
                      });
                });
                
                \Log::info('OrderStats - Aplicando filtro OR (línea O operador):', [
                    'production_line_ids' => $productionLineIds,
                    'operatorIds' => $operatorIds,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]);
            } else if ($filterMode === 'and') {
                // Filtro AND: mostrar registros que cumplan con ambas condiciones
                $query->whereIn('production_line_id', $productionLineIds)
                      ->whereHas('operators', function($q) use ($operatorIds) {
                          $q->whereIn('operators.id', $operatorIds);
                      });
                
                \Log::info('OrderStats - Aplicando filtro AND (línea Y operador):', [
                    'production_line_ids' => $productionLineIds,
                    'operatorIds' => $operatorIds,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]);
            } else if ($filterMode === 'operator_only') {
                // Filtrar solo por operador, ignorando las líneas seleccionadas
                $query->whereHas('operators', function($q) use ($operatorIds) {
                    $q->whereIn('operators.id', $operatorIds);
                });
                
                \Log::info('OrderStats - Aplicando filtro solo por operador:', [
                    'operatorIds' => $operatorIds,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]);
            } else { // 'line_only' o cualquier otro valor
                // Filtrar solo por línea, ignorando los operadores seleccionados
                $query->whereIn('production_line_id', $productionLineIds);
                
                \Log::info('OrderStats - Aplicando filtro solo por línea:', [
                    'production_line_ids' => $productionLineIds,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]);
            }
        } else {
            // Si solo hay un tipo de filtro, aplicarlo normalmente
            if ($filterByProductionLine) {
                $query->whereIn('production_line_id', $productionLineIds);
                \Log::info('OrderStats - Aplicando solo filtro de línea:', [
                    'production_line_ids' => $productionLineIds
                ]);
            }
            
            if ($filterByOperator) {
                $query->whereHas('operators', function($q) use ($operatorIds) {
                    $q->whereIn('operators.id', $operatorIds);
                });
                \Log::info('OrderStats - Aplicando solo filtro de operador:', [
                    'operatorIds' => $operatorIds
                ]);
            }
        }
        
        // Ejecutar la consulta
        $orderStats = $query->orderBy('created_at', 'desc')->get();
        
        \Log::info('OrderStats - Estadísticas encontradas:', ['count' => $orderStats->count()]);
    
        if ($orderStats->isEmpty()) {
            \Log::warning('OrderStats - No se encontraron estadísticas para las fechas y líneas especificadas');
            // Devolver un array vacío con código 200 en lugar de un error 404
            return response()->json([], 200);
        }
    
        $response = $orderStats->map(function ($orderStat) use ($productionLineNames) {
            $orderStatArray = $orderStat->toArray();
            $orderStatArray['production_line_name'] = $productionLineNames[$orderStat->production_line_id] ?? 'Unknown';
            
            // Extraer los nombres de los operadores asociados
            $operatorNames = [];
            if (isset($orderStatArray['operators']) && !empty($orderStatArray['operators'])) {
                foreach ($orderStatArray['operators'] as $operator) {
                    $operatorNames[] = $operator['name'];
                }
            }
            $orderStatArray['operator_names'] = $operatorNames;
            
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

        // Aplicar filtros de OEE si están activados
        if ($hideZeroOEE || $hide100OEE) {
            $response = $response->filter(function ($orderStat) use ($hideZeroOEE, $hide100OEE) {
                $oee = $orderStat['oee'] ?? 0;
                
                // Convertir a número si viene como string
                if (is_string($oee)) {
                    $oee = (float) $oee;
                }
                
                // Aplicar filtros
                if ($hideZeroOEE && $oee == 0) {
                    return false; // Excluir líneas con 0% OEE
                }
                
                if ($hide100OEE && $oee == 100) {
                    return false; // Excluir líneas con 100% OEE
                }
                
                return true; // Incluir la línea
            })->values(); // Reindexar el array después del filtrado
            
            \Log::info('OrderStats - Filtros OEE aplicados:', [
                'original_count' => $orderStats->count(),
                'filtered_count' => count($response),
                'hide_zero' => $hideZeroOEE,
                'hide_100' => $hide100OEE
            ]);
        }
        
        \Log::info('OrderStats - Respuesta generada con éxito:', ['count' => count($response)]);
    
        return response()->json($response, 200);
    }
    
}