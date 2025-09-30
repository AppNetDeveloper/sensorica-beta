<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OriginalOrder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OriginalOrderApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/original-orders",
     *     summary="Obtener todos los pedidos de un cliente",
     *     description="Retorna todos los pedidos originales de un cliente con sus procesos y artículos asociados",
     *     tags={"Original Orders"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token de autenticación del cliente",
     *         required=true,
     *         @OA\Schema(type="string", example="062c129c5a9ee22de54991518b29ca69")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de pedidos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="customer",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Tablenova Murcia")
     *             ),
     *             @OA\Property(property="total_orders", type="integer", example=150),
     *             @OA\Property(
     *                 property="orders",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=882849),
     *                     @OA\Property(property="order_id", type="string", example="2511096"),
     *                     @OA\Property(property="client_number", type="string", example="COCINAS BOSSI S.COOP.V."),
     *                     @OA\Property(
     *                         property="customer_client",
     *                         type="object",
     *                         nullable=true,
     *                         description="Cliente final (dirección de entrega)",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="COCINAS BOSSI S.COOP.V."),
     *                         @OA\Property(property="address", type="string", example="Calle Principal 123"),
     *                         @OA\Property(property="phone", type="string", example="+34 600 123 456"),
     *                         @OA\Property(property="email", type="string", example="cliente@example.com"),
     *                         @OA\Property(property="tax_id", type="string", example="B12345678"),
     *                         @OA\Property(property="active", type="boolean", example=true)
     *                     ),
     *                     @OA\Property(
     *                         property="route",
     *                         type="object",
     *                         nullable=true,
     *                         description="Ruta de entrega asignada",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="CARTAGENA COSTA"),
     *                         @OA\Property(property="note", type="string", nullable=true, example="Ruta costera"),
     *                         @OA\Property(property="days_mask", type="integer", example=31, description="Máscara de días (bit flags)"),
     *                         @OA\Property(property="active", type="boolean", example=true)
     *                     ),
     *                     @OA\Property(property="order_details", type="object"),
     *                     @OA\Property(property="processed", type="boolean", example=false),
     *                     @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="delivery_date", type="string", format="date", nullable=true),
     *                     @OA\Property(property="estimated_delivery_date", type="string", format="date", nullable=true),
     *                     @OA\Property(property="actual_delivery_date", type="string", format="date", nullable=true),
     *                     @OA\Property(property="in_stock", type="integer", example=1),
     *                     @OA\Property(property="fecha_pedido_erp", type="string", format="date", nullable=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="processes",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=46881),
     *                             @OA\Property(property="process_id", type="integer", example=3),
     *                             @OA\Property(property="process_name", type="string", example="SERVICIO APLACADO LAMINADO"),
     *                             @OA\Property(property="time", type="string", example="60.00"),
     *                             @OA\Property(property="created", type="boolean", example=true),
     *                             @OA\Property(property="finished", type="boolean", example=false),
     *                             @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                             @OA\Property(property="grupo_numero", type="string", example="1"),
     *                             @OA\Property(property="box", type="integer", example=0),
     *                             @OA\Property(property="units_box", type="integer", example=0),
     *                             @OA\Property(property="number_of_pallets", type="integer", example=0),
     *                             @OA\Property(property="in_stock", type="integer", example=0),
     *                             @OA\Property(
     *                                 property="articles",
     *                                 type="array",
     *                                 @OA\Items(
     *                                     @OA\Property(property="id", type="integer", example=104160),
     *                                     @OA\Property(property="codigo_articulo", type="string", example="3.H3170ST12.08"),
     *                                     @OA\Property(property="descripcion_articulo", type="string", example="LAMINADO H3170 ST12/0,8MM"),
     *                                     @OA\Property(property="grupo_articulo", type="string", example="03.LAMINADO"),
     *                                     @OA\Property(property="in_stock", type="integer", example=0),
     *                                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token no proporcionado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token de cliente requerido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cliente no encontrado con el token proporcionado")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $token = $request->input('token');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de cliente requerido'
            ], 400);
        }
        
        // Buscar el cliente por token
        $customer = Customer::where('token', $token)->first();
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado con el token proporcionado'
            ], 404);
        }
        
        // Obtener todos los pedidos del cliente con sus relaciones
        $orders = OriginalOrder::where('customer_id', $customer->id)
            ->with([
                'customerClient', // Cliente final (dirección de entrega)
                'routeName', // Ruta asignada
                'orderProcesses.process', // Procesos con información del proceso
                'orderProcesses.articles' // Artículos de cada proceso
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name
            ],
            'total_orders' => $orders->count(),
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_id' => $order->order_id,
                    'client_number' => $order->client_number,
                    'customer_client' => $order->customerClient ? [
                        'id' => $order->customerClient->id,
                        'name' => $order->customerClient->name,
                        'address' => $order->customerClient->address,
                        'phone' => $order->customerClient->phone,
                        'email' => $order->customerClient->email,
                        'tax_id' => $order->customerClient->tax_id,
                        'active' => $order->customerClient->active,
                    ] : null,
                    'route' => $order->routeName ? [
                        'id' => $order->routeName->id,
                        'name' => $order->routeName->name,
                        'note' => $order->routeName->note,
                        'days_mask' => $order->routeName->days_mask,
                        'active' => $order->routeName->active,
                    ] : null,
                    'order_details' => $order->order_details,
                    'processed' => $order->processed,
                    'finished_at' => $order->finished_at?->format('Y-m-d H:i:s'),
                    'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                    'estimated_delivery_date' => $order->estimated_delivery_date?->format('Y-m-d'),
                    'actual_delivery_date' => $order->actual_delivery_date?->format('Y-m-d'),
                    'in_stock' => $order->in_stock,
                    'fecha_pedido_erp' => $order->fecha_pedido_erp?->format('Y-m-d'),
                    'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                    'processes' => $order->orderProcesses->map(function ($process) {
                        return [
                            'id' => $process->id,
                            'process_id' => $process->process_id,
                            'process_name' => $process->process?->name,
                            'time' => $process->time,
                            'created' => $process->created,
                            'finished' => $process->finished,
                            'finished_at' => $process->finished_at?->format('Y-m-d H:i:s'),
                            'grupo_numero' => $process->grupo_numero,
                            'box' => $process->box,
                            'units_box' => $process->units_box,
                            'number_of_pallets' => $process->number_of_pallets,
                            'in_stock' => $process->in_stock,
                            'articles' => $process->articles->map(function ($article) {
                                return [
                                    'id' => $article->id,
                                    'codigo_articulo' => $article->codigo_articulo,
                                    'descripcion_articulo' => $article->descripcion_articulo,
                                    'grupo_articulo' => $article->grupo_articulo,
                                    'in_stock' => $article->in_stock,
                                    'created_at' => $article->created_at->format('Y-m-d H:i:s'),
                                    'updated_at' => $article->updated_at->format('Y-m-d H:i:s'),
                                ];
                            })
                        ];
                    })
                ];
            })
        ], 200);
    }
    
    /**
     * @OA\Get(
     *     path="/api/original-orders/{order_id}",
     *     summary="Obtener un pedido específico",
     *     description="Retorna un pedido original específico con todos sus procesos y artículos asociados",
     *     tags={"Original Orders"},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         description="ID del pedido a consultar",
     *         required=true,
     *         @OA\Schema(type="string", example="2511096")
     *     ),
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         description="Token de autenticación del cliente",
     *         required=true,
     *         @OA\Schema(type="string", example="062c129c5a9ee22de54991518b29ca69")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Pedido obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="order",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=882849),
     *                 @OA\Property(property="order_id", type="string", example="2511096"),
     *                 @OA\Property(property="client_number", type="string", example="COCINAS BOSSI S.COOP.V."),
     *                 @OA\Property(
     *                     property="customer_client",
     *                     type="object",
     *                     nullable=true,
     *                     description="Cliente final (dirección de entrega)",
     *                     @OA\Property(property="id", type="integer", example=195),
     *                     @OA\Property(property="name", type="string", example="COCINAS BOSSI S.COOP.V."),
     *                     @OA\Property(property="address", type="string", nullable=true, example="Calle Principal 123"),
     *                     @OA\Property(property="phone", type="string", nullable=true, example="+34 600 123 456"),
     *                     @OA\Property(property="email", type="string", nullable=true, example="cliente@example.com"),
     *                     @OA\Property(property="tax_id", type="string", nullable=true, example="B12345678"),
     *                     @OA\Property(property="active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(
     *                     property="route",
     *                     type="object",
     *                     nullable=true,
     *                     description="Ruta de entrega asignada",
     *                     @OA\Property(property="id", type="integer", example=9),
     *                     @OA\Property(property="name", type="string", example="CARTAGENA COSTA"),
     *                     @OA\Property(property="note", type="string", nullable=true, example="Creada automáticamente desde comando CheckOrdersFromApi"),
     *                     @OA\Property(property="days_mask", type="integer", example=31, description="Máscara de días (bit flags)"),
     *                     @OA\Property(property="active", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="order_details", type="object", description="JSON con detalles completos del pedido"),
     *                 @OA\Property(property="processed", type="boolean", example=false),
     *                 @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="delivery_date", type="string", format="date", nullable=true, example="2025-10-10"),
     *                 @OA\Property(property="estimated_delivery_date", type="string", format="date", nullable=true),
     *                 @OA\Property(property="actual_delivery_date", type="string", format="date", nullable=true),
     *                 @OA\Property(property="delivery_signature", type="string", nullable=true, description="Firma digital en base64"),
     *                 @OA\Property(property="delivery_photos", type="array", @OA\Items(type="string"), nullable=true, description="Array de rutas de fotos"),
     *                 @OA\Property(property="delivery_notes", type="string", nullable=true, description="Notas del transportista"),
     *                 @OA\Property(property="in_stock", type="integer", example=1),
     *                 @OA\Property(property="fecha_pedido_erp", type="string", format="date", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="processes",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=46881),
     *                         @OA\Property(property="process_id", type="integer", example=3),
     *                         @OA\Property(property="process_name", type="string", example="SERVICIO APLACADO LAMINADO"),
     *                         @OA\Property(property="time", type="string", example="60.00", description="Tiempo en minutos"),
     *                         @OA\Property(property="created", type="boolean", example=true),
     *                         @OA\Property(property="finished", type="boolean", example=false),
     *                         @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                         @OA\Property(property="grupo_numero", type="string", example="1"),
     *                         @OA\Property(property="box", type="integer", example=0, description="Número de cajas"),
     *                         @OA\Property(property="units_box", type="integer", example=0, description="Unidades por caja"),
     *                         @OA\Property(property="number_of_pallets", type="integer", example=0, description="Número de pallets"),
     *                         @OA\Property(property="in_stock", type="integer", example=0, description="0=Sin stock, 1=En stock"),
     *                         @OA\Property(
     *                             property="articles",
     *                             type="array",
     *                             @OA\Items(
     *                                 @OA\Property(property="id", type="integer", example=104160),
     *                                 @OA\Property(property="codigo_articulo", type="string", example="3.H3170ST12.08"),
     *                                 @OA\Property(property="descripcion_articulo", type="string", example="LAMINADO H3170 ST12/0,8MM  3050X1310 Roble Kendal natural"),
     *                                 @OA\Property(property="grupo_articulo", type="string", example="03.LAMINADO"),
     *                                 @OA\Property(property="in_stock", type="integer", example=0, description="0=Sin stock, 1=En stock"),
     *                                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                                 @OA\Property(property="updated_at", type="string", format="date-time")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token no proporcionado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token de cliente requerido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente o pedido no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Pedido no encontrado")
     *         )
     *     )
     * )
     */
    public function show(Request $request, string $orderId): JsonResponse
    {
        $token = $request->input('token');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token de cliente requerido'
            ], 400);
        }
        
        // Buscar el cliente por token
        $customer = Customer::where('token', $token)->first();
        
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado con el token proporcionado'
            ], 404);
        }
        
        // Buscar el pedido específico del cliente
        $order = OriginalOrder::where('customer_id', $customer->id)
            ->where('order_id', $orderId)
            ->with([
                'customerClient', // Cliente final (dirección de entrega)
                'routeName', // Ruta asignada
                'orderProcesses.process',
                'orderProcesses.articles'
            ])
            ->first();
        
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'client_number' => $order->client_number,
                'customer_client' => $order->customerClient ? [
                    'id' => $order->customerClient->id,
                    'name' => $order->customerClient->name,
                    'address' => $order->customerClient->address,
                    'phone' => $order->customerClient->phone,
                    'email' => $order->customerClient->email,
                    'tax_id' => $order->customerClient->tax_id,
                    'active' => $order->customerClient->active,
                ] : null,
                'route' => $order->routeName ? [
                    'id' => $order->routeName->id,
                    'name' => $order->routeName->name,
                    'note' => $order->routeName->note,
                    'days_mask' => $order->routeName->days_mask,
                    'active' => $order->routeName->active,
                ] : null,
                'order_details' => $order->order_details,
                'processed' => $order->processed,
                'finished_at' => $order->finished_at?->format('Y-m-d H:i:s'),
                'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('Y-m-d'),
                'actual_delivery_date' => $order->actual_delivery_date?->format('Y-m-d'),
                'delivery_signature' => $order->delivery_signature,
                'delivery_photos' => $order->delivery_photos,
                'delivery_notes' => $order->delivery_notes,
                'in_stock' => $order->in_stock,
                'fecha_pedido_erp' => $order->fecha_pedido_erp?->format('Y-m-d'),
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
                'processes' => $order->orderProcesses->map(function ($process) {
                    return [
                        'id' => $process->id,
                        'process_id' => $process->process_id,
                        'process_name' => $process->process?->name,
                        'time' => $process->time,
                        'created' => $process->created,
                        'finished' => $process->finished,
                        'finished_at' => $process->finished_at?->format('Y-m-d H:i:s'),
                        'grupo_numero' => $process->grupo_numero,
                        'box' => $process->box,
                        'units_box' => $process->units_box,
                        'number_of_pallets' => $process->number_of_pallets,
                        'in_stock' => $process->in_stock,
                        'articles' => $process->articles->map(function ($article) {
                            return [
                                'id' => $article->id,
                                'codigo_articulo' => $article->codigo_articulo,
                                'descripcion_articulo' => $article->descripcion_articulo,
                                'grupo_articulo' => $article->grupo_articulo,
                                'in_stock' => $article->in_stock,
                                'created_at' => $article->created_at->format('Y-m-d H:i:s'),
                                'updated_at' => $article->updated_at->format('Y-m-d H:i:s'),
                            ];
                        })
                    ];
                })
            ]
        ], 200);
    }
}
