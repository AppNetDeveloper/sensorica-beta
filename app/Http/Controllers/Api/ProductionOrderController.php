<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionLine; // Importar el modelo de líneas de producción
use Illuminate\Http\Request;

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
        // Validar que se proporcione el token
        $request->validate([
            'token' => 'required|string',
        ]);

        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $request->token)->first();

        if (!$productionLine) {
            return response()->json([
                'message' => 'Línea de producción no encontrada o token inválido'
            ], 400);
        }

        $query = ProductionOrder::where(function($q) use ($productionLine) {
            // Órdenes de esta línea que no son status 3
            $q->where('production_line_id', $productionLine->id)
              ->where('status', '!=', 3);
        })->orWhere(function($q) {
            // O bien, órdenes con status 3 (de cualquier línea)
            $q->where('status', 3);
        });

        // Aplicar filtro de status si se especifica
        $status = $request->input('status', 'all');
        
        if ($status !== 'all') {
            $query->where('status', $status);
            
            // Aplicar ordenamiento específico por status
            if (in_array($status, [0, 1])) {
                // Status 0 y 1: orden ascendente (más pequeño primero)
                $query->orderBy('orden', 'asc');
            } elseif (in_array($status, [2, 4, 5])) {
                // Status 2, 4 y 5: orden descendente (más grande primero)
                $query->orderBy('orden', 'desc');
            }
            // Status 3 no necesita orden específico
        } else {
            // Orden por defecto cuando no hay filtro de status
            $query->orderBy('status', 'asc')
                  ->orderBy('orden', 'asc');
        }
        
        // Paginación: 15 elementos por defecto, excepto para status 0 y 3 que son ilimitados
        if (in_array($status, [0, 3])) {
            $orders = $query->get();
            return response()->json([
                'data' => $orders,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $orders->count(),
                'total' => $orders->count(),
                'has_more_pages' => false,
                'next_page_url' => null,
            ]);
        }
        
        // Paginación normal para otros status
        $perPage = $request->input('per_page', 15);
        $orders = $query->paginate($perPage);

        // Incluir información de paginación en la respuesta
        return response()->json([
            'data' => $orders->items(),
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
            'has_more_pages' => $orders->hasMorePages(),
            'next_page_url' => $orders->nextPageUrl(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/kanban/orders",
     *     summary="Obtener órdenes optimizadas para el tablero Kanban",
     *     tags={"ProductionOrders"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token único de la línea de producción",
     *         required=true,
     *         @OA\Schema(type="string", example="abcd1234")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de órdenes optimizada para el Kanban",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     )
     * )
     */
    public function getKanbanOrders(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $request->token)->first();

        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'Línea de producción no encontrada o token inválido',
            ], 400);
        }

        $today = now()->format('Y-m-d');

        // Obtener solo las órdenes necesarias para el tablero Kanban
        $orders = ProductionOrder::whereIn('status', ['0', '1', '2', '3', '4', '5'])
            ->where(function($query) use ($productionLine, $today) {
                // Caso 1: Órdenes de esta línea que no son status 2 ni 3
                $query->where(function($q) use ($productionLine) {
                    $q->where('production_line_id', $productionLine->id)
                      ->where('status', '!=', 3)
                      ->where('status', '!=', 2);
                })
                // Caso 2: Órdenes con status 3 (de cualquier línea)
                ->orWhere('status', 3)
                // Caso 3: Órdenes finalizadas (status 2) solo de hoy
                ->orWhere(function($q) use ($productionLine, $today) {
                    $q->where('production_line_id', $productionLine->id)
                      ->where('status', 2)
                      ->whereDate('updated_at', $today);
                });
            })
            ->orderBy('orden', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
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

        // Validar el token
        $request->validate([
            'token' => 'required|string',
            'orden' => 'nullable|integer|min:0',
            'status' => 'nullable|integer|min:0|max:5',
        ]);

        // Buscar la línea de producción por token
        $productionLine = ProductionLine::where('token', $request->token)->first();
        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'Línea de producción no encontrada o token inválido',
            ], 400);
        }
        
        $validatedData = $request->all();
        $currentProductionLineId = $productionLine->id;
        
        // Si la orden cambia a estado 3 (Pausa/Incidencia)
        if (isset($validatedData['status']) && $validatedData['status'] == 3) {
            // Guardar la línea actual como original si no existe
            if (!$order->original_production_line_id) {
                $order->original_production_line_id = $order->production_line_id;
            }
        } 
        // Si la orden cambia DE estado 3 a otro estado
        elseif ($order->status == 3 && isset($validatedData['status']) && $validatedData['status'] != 3) {
            // Actualizar la línea de producción a la línea que está realizando el cambio
            $order->production_line_id = $currentProductionLineId;
        }
        // Para cualquier otro caso, actualizar la línea de producción si es necesario
        elseif ($order->production_line_id != $currentProductionLineId) {
            $order->production_line_id = $currentProductionLineId;
        }

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
                'production_line_id' => $currentProductionLineId,
                'original_production_line_id' => $order->original_production_line_id,
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
