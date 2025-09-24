<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderArticle;
use App\Models\Process;
use App\Models\RouteName;

/**
 * @OA\Info(
 *     title="API de Webhooks para Órdenes",
 *     version="1.0.0",
 *     description="API para recibir órdenes desde sistemas externos"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token"
 * )
 */
class IncomingOrdersController extends Controller
{
    /**
     * Extrae el token del cliente de Authorization: Bearer <token> o cabecera X-Customer-Token
     */
    protected function getCustomerFromRequest(Request $request): ?Customer
    {
        $token = null;
        $auth = $request->header('Authorization');
        if ($auth && stripos($auth, 'Bearer ') === 0) {
            $token = trim(substr($auth, 7));
        }
        if (!$token) {
            $token = $request->header('X-Customer-Token');
        }
        if (!$token) {
            $token = $request->query('token');
        }
        if (!$token) {
            return null;
        }
        return Customer::where('token', $token)->first();
    }

    /**
     * Crea o actualiza (upsert) una OriginalOrder y sus hijos a partir de un JSON estándar.
     * Si reprocess=true y la orden existe, se elimina totalmente y se vuelve a crear con el payload.
     *
     * @OA\Post(
     *   path="/api/incoming/original-orders",
     *   summary="Crear o actualizar una OriginalOrder (webhook)",
     *   tags={"Incoming Orders"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="reprocess", in="query", required=false, @OA\Schema(type="boolean"), description="Si true, borra la orden existente y la recrea desde cero"),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"order_id","grupos"},
     *       @OA\Property(property="order_id", type="string"),
     *       @OA\Property(property="client_number", type="string"),
     *       @OA\Property(property="route_name", type="string", description="Nombre de la ruta logística. Si existe se usa su ID; si no, se crea automáticamente."),
     *       @OA\Property(property="delivery_date", type="string", format="date"),
     *       @OA\Property(property="fecha_pedido_erp", type="string", format="date"),
     *       @OA\Property(property="in_stock", type="integer", enum={0,1}),
     *       @OA\Property(property="grupos", type="array", @OA\Items(
     *         @OA\Property(property="grupoNum", type="string"),
     *         @OA\Property(property="servicios", type="array", @OA\Items(
     *           required={"process_code","time_seconds"},
     *           @OA\Property(property="process_code", type="string"),
     *           @OA\Property(property="time_seconds", type="integer"),
     *           @OA\Property(property="box", type="integer"),
     *           @OA\Property(property="units_box", type="integer"),
     *           @OA\Property(property="number_of_pallets", type="integer")
     *         )),
     *         @OA\Property(property="articulos", type="array", @OA\Items(
     *           required={"codigo_articulo"},
     *           @OA\Property(property="codigo_articulo", type="string"),
     *           @OA\Property(property="descripcion_articulo", type="string"),
     *           @OA\Property(property="in_stock", type="integer", enum={0,1})
     *         ))
     *       ))
     *     )
     *   ),
     *   @OA\Response(response=200, description="OK"),
     *   @OA\Response(response=400, description="Bad request"),
     *   @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function store(Request $request)
    {
        $customer = $this->getCustomerFromRequest($request);
        if (!$customer) {
            return response()->json(['message' => 'Unauthorized: missing/invalid token'], 401);
        }

        $data = $request->json()->all();
        if (!isset($data['order_id'])) {
            return response()->json(['message' => 'order_id is required'], 400);
        }

        $reprocess = filter_var($request->query('reprocess', false), FILTER_VALIDATE_BOOLEAN);

        $order = OriginalOrder::where('order_id', $data['order_id'])->first();

        try {
            return DB::transaction(function () use ($customer, $data, $order, $reprocess) {
                if ($order && $reprocess) {
                    // Borrar completamente la orden y sus hijos
                    $this->deleteOrderCascade($order);
                    $order = null;
                }

                if (!$order) {
                    // Crear nueva orden
                    $order = new OriginalOrder();
                    $order->order_id = $data['order_id'];
                    $order->customer_id = $customer->id;
                    $order->client_number = $data['client_number'] ?? null;
                    // Resolver route_name si viene
                    if (!empty($data['route_name'])) {
                        $order->route_name_id = $this->resolveRouteName($customer->id, (string)$data['route_name']);
                    }
                    $order->delivery_date = $data['delivery_date'] ?? null;
                    $order->fecha_pedido_erp = $data['fecha_pedido_erp'] ?? null;
                    if (isset($data['in_stock'])) {
                        $order->in_stock = (int) !!$data['in_stock'];
                    }
                    $order->processed = 0;
                    $order->order_details = $data; // guardamos payload completo para trazabilidad
                    $order->save();

                    // Crear procesos y artículos asociados
                    $created = $this->createProcessesAndArticles($order, $data);
                    if ($created === 0) {
                        // Si no hay procesos válidos, eliminar la orden (criterio actual)
                        $order->delete();
                        return response()->json([
                            'message' => 'Order created but removed due to no valid processes',
                            'order_id' => $data['order_id']
                        ], 200);
                    }
                } else {
                    // Orden existente sin reprocess: solo actualizaciones ligeras + guardar detalles
                    $order->client_number = $data['client_number'] ?? $order->client_number;
                    if (!empty($data['route_name'])) {
                        $order->route_name_id = $this->resolveRouteName($order->customer_id, (string)$data['route_name']);
                    }
                    if (isset($data['delivery_date'])) { $order->delivery_date = $data['delivery_date']; }
                    if (isset($data['fecha_pedido_erp'])) { $order->fecha_pedido_erp = $data['fecha_pedido_erp']; }
                    if (isset($data['in_stock'])) { $order->in_stock = (int) !!$data['in_stock']; }
                    $order->order_details = $data;
                    $order->save();
                }

                return response()->json([
                    'message' => $order->wasRecentlyCreated ? 'Order created' : 'Order updated',
                    'order_id' => $order->order_id,
                    'id' => $order->id
                ], 200);
            });
        } catch (\Throwable $e) {
            Log::error('Incoming order store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal error'], 500);
        }
    }

    /**
     * Elimina una OriginalOrder por order_id (y todos sus hijos)
     *
     * @OA\Delete(
     *   path="/api/incoming/original-orders/{order_id}",
     *   summary="Borrar una OriginalOrder por order_id",
     *   tags={"Incoming Orders"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="order_id", in="path", required=true, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Deleted"),
     *   @OA\Response(response=401, description="Unauthorized"),
     *   @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Request $request, string $order_id)
    {
        $customer = $this->getCustomerFromRequest($request);
        if (!$customer) {
            return response()->json(['message' => 'Unauthorized: missing/invalid token'], 401);
        }

        $order = OriginalOrder::where('order_id', $order_id)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        try {
            DB::transaction(function () use ($order) {
                $this->deleteOrderCascade($order);
            });
            return response()->json(['message' => 'Order deleted', 'order_id' => $order_id], 200);
        } catch (\Throwable $e) {
            Log::error('Incoming order delete failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Internal error'], 500);
        }
    }

    /**
     * Crea procesos desde grupos.servicios y artículos desde grupos.articulos
     * Usa process_code y time_seconds directamente (sin mapeos personalizados)
     */
    protected function createProcessesAndArticles(OriginalOrder $order, array $payload): int
    {
        $totalProcessesCreated = 0;
        if (!isset($payload['grupos']) || !is_array($payload['grupos'])) {
            return 0;
        }
        foreach ($payload['grupos'] as $grupo) {
            $grupoNum = isset($grupo['grupoNum']) ? (string)$grupo['grupoNum'] : null;
            $services = isset($grupo['servicios']) && is_array($grupo['servicios']) ? $grupo['servicios'] : [];
            foreach ($services as $svc) {
                $proc = $this->createSingleProcess($order, $grupoNum, $svc);
                if ($proc) {
                    $totalProcessesCreated++;
                    // artículos asociados al grupo
                    $articles = isset($grupo['articulos']) && is_array($grupo['articulos']) ? $grupo['articulos'] : [];
                    foreach ($articles as $art) {
                        $this->createSingleArticle($proc->id, $grupoNum, $art);
                    }
                }
            }
        }
        return $totalProcessesCreated;
    }

    protected function createSingleProcess(OriginalOrder $order, ?string $grupoNum, array $svc): ?OriginalOrderProcess
    {
        if (!isset($svc['process_code'])) { return null; }
        $code = (string)$svc['process_code'];
        $process = Process::where('code', $code)->first();
        if (!$process) { return null; }
        $rawTime = (int)($svc['time_seconds'] ?? 0);
        $calculatedTime = $rawTime * ($process->factor_correccion ?? 1);

        // Evitar duplicados por (order + process + grupo)
        $exists = OriginalOrderProcess::where('original_order_id', $order->id)
            ->where('process_id', $process->id)
            ->when($grupoNum !== null, function($q) use ($grupoNum) { $q->where('grupo_numero', $grupoNum); })
            ->first();
        if ($exists) { return $exists; }

        return OriginalOrderProcess::create([
            'original_order_id' => $order->id,
            'process_id' => $process->id,
            'time' => $calculatedTime,
            'grupo_numero' => $grupoNum,
            'created' => 0,
            'finished' => 0,
            'finished_at' => null,
            'box' => (int)($svc['box'] ?? 0),
            'units_box' => (int)($svc['units_box'] ?? 0),
            'number_of_pallets' => (int)($svc['number_of_pallets'] ?? 0),
        ]);
    }

    protected function createSingleArticle(int $originalOrderProcessId, ?string $grupoNum, array $art): bool
    {
        if (!isset($art['codigo_articulo'])) { return false; }
        $codigo = (string)$art['codigo_articulo'];
        $inStock = isset($art['in_stock']) ? (int)!!$art['in_stock'] : 1;

        // Evitar duplicados por (process + codigo [+ grupo])
        $query = OriginalOrderArticle::where('original_order_process_id', $originalOrderProcessId)
            ->where('codigo_articulo', $codigo);
        if ($grupoNum !== null) { $query->where('grupo_articulo', $grupoNum); }
        $existing = $query->first();
        if ($existing) {
            if ($existing->in_stock !== $inStock) {
                $existing->in_stock = $inStock; $existing->save();
                return true;
            }
            return false;
        }

        $article = new OriginalOrderArticle([
            'original_order_process_id' => $originalOrderProcessId,
            'codigo_articulo' => $codigo,
            'descripcion_articulo' => (string)($art['descripcion_articulo'] ?? ''),
            'grupo_articulo' => $grupoNum ?? '',
            'in_stock' => $inStock,
        ]);
        return $article->save();
    }

    protected function deleteOrderCascade(OriginalOrder $order): void
    {
        // Borrar artículos asociados a procesos
        OriginalOrderArticle::whereHas('originalOrderProcess', function($q) use ($order) {
            $q->where('original_order_id', $order->id);
        })->delete();
        
        // Borrar procesos
        OriginalOrderProcess::where('original_order_id', $order->id)->delete();
        
        // Borrar la orden
        $order->delete();
    }

    /**
     * Resuelve o crea un RouteName para un customer y devuelve su ID
     */
    protected function resolveRouteName(int $customerId, string $routeName): int
    {
        $route = RouteName::where('customer_id', $customerId)
            ->where('name', $routeName)
            ->first();
        if ($route) {
            return (int)$route->id;
        }
        $route = RouteName::create([
            'customer_id' => $customerId,
            'name' => $routeName,
            'note' => 'Creada automáticamente vía API IncomingOrders',
            'days_mask' => 0,
            'active' => true,
        ]);
        return (int)$route->id;
    }
}
