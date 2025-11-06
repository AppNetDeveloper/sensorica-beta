<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RouteDayAssignment;
use App\Models\RouteClientVehicleAssignment;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    /**
     * Mostrar las entregas del día para el transportista autenticado
     */
    public function myDeliveries(Request $request)
    {
        $user = auth()->user();
        $date = $request->get('date') ? Carbon::parse($request->get('date')) : now();

        // Obtener las asignaciones de vehículos para este usuario en esta fecha
        $assignments = RouteDayAssignment::where('user_id', $user->id)
            ->whereDate('assignment_date', $date)
            ->with([
                'routeName',
                'fleetVehicle',
                'customer'
            ])
            ->get();
        
        \Log::info('DeliveryController - User deliveries', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'date' => $date->format('Y-m-d'),
            'assignments_count' => $assignments->count()
        ]);

        // Obtener todos los clientes asignados a estos vehículos
        $deliveries = collect();
        
        foreach ($assignments as $assignment) {
            $clients = RouteClientVehicleAssignment::where('fleet_vehicle_id', $assignment->fleet_vehicle_id)
                ->whereDate('assignment_date', $date)
                ->with([
                    'customerClient',
                    'orderAssignments' => function ($query) {
                        $query->where('active', true)->orderBy('sort_order', 'asc');
                    },
                    'orderAssignments.originalOrder'
                ])
                ->orderBy('sort_order', 'asc')
                ->get();

            foreach ($clients as $client) {
                $deliveries->push([
                    'assignment' => $assignment,
                    'client' => $client,
                ]);
            }
        }

        return view('deliveries.my-deliveries', compact('user', 'date', 'assignments', 'deliveries'));
    }
    /**
     * Marcar uno o varios pedidos como entregados
     */
    public function markAsDelivered(Request $request)
    {
        try {
            // Verificar si es entrega múltiple o individual
            $isMultiple = $request->has('multiple') && $request->input('multiple') === 'true';

            if ($isMultiple) {
            // Validación para múltiples pedidos
            $request->validate([
                'order_ids' => 'required|string',
                'signature' => 'nullable|string',
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|image|max:5120', // 5MB max por foto
                'notes' => 'nullable|string|max:1000',
            ]);

            $orderIds = json_decode($request->input('order_ids'), true);

            if (!is_array($orderIds) || empty($orderIds)) {
                return response()->json(['success' => false, 'message' => 'Invalid order IDs'], 400);
            }

            $orders = \App\Models\OriginalOrder::whereIn('id', $orderIds)->get();

            if ($orders->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No orders found'], 404);
            }

            // Verificar acceso: el usuario debe tener asignaciones
            // Verificamos si el usuario tiene alguna asignación activa (sin restricción de fecha específica)
            $hasAccess = \App\Models\RouteDayAssignment::where('user_id', auth()->id())
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // Procesar fotos (si existen, se compartirán entre todos los pedidos)
            $photoPaths = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('deliveries/multiple_' . time(), 'public');
                    $photoPaths[] = $path;
                }
            }

            // Actualizar todos los pedidos
            foreach ($orders as $order) {
                $order->actual_delivery_date = now();
                $order->delivery_date = now();

                // Guardar firma si existe (misma firma para todos)
                if ($request->has('signature') && !empty($request->signature)) {
                    $order->delivery_signature = $request->signature;
                }

                // Guardar fotos (mismas fotos para todos)
                if (!empty($photoPaths)) {
                    $order->delivery_photos = $photoPaths;
                }

                // Guardar notas si existen (mismas notas para todos)
                if ($request->has('notes') && !empty($request->notes)) {
                    $order->delivery_notes = $request->notes;
                }

                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => __('Orders marked as delivered'),
                'count' => count($orders),
                'delivered_at' => now()->format('d/m/Y H:i')
            ]);

        } else {
            // Validación para pedido individual
            $request->validate([
                'order_id' => 'required|exists:original_orders,id',
                'signature' => 'nullable|string',
                'photos' => 'nullable|array',
                'photos.*' => 'nullable|image|max:5120', // 5MB max por foto
                'notes' => 'nullable|string|max:1000',
            ]);

            $orderId = $request->input('order_id');
            $order = \App\Models\OriginalOrder::findOrFail($orderId);

            // Verificar acceso: el usuario debe tener asignaciones
            // Verificamos si el usuario tiene alguna asignación activa (sin restricción de fecha específica)
            $hasAccess = \App\Models\RouteDayAssignment::where('user_id', auth()->id())
                ->exists();

            if (!$hasAccess) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            // Actualizar fechas de entrega
            $order->actual_delivery_date = now();
            $order->delivery_date = now();

            // Guardar firma si existe
            if ($request->has('signature') && !empty($request->signature)) {
                $order->delivery_signature = $request->signature;
            }

            // Guardar fotos si existen
            if ($request->hasFile('photos')) {
                $photoPaths = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('deliveries/' . $order->id, 'public');
                    $photoPaths[] = $path;
                }
                $order->delivery_photos = $photoPaths;
            }

            // Guardar notas si existen
            if ($request->has('notes') && !empty($request->notes)) {
                $order->delivery_notes = $request->notes;
            }

            $order->save();

            return response()->json([
                'success' => true,
                'message' => __('Order marked as delivered'),
                'delivered_at' => $order->actual_delivery_date->format('d/m/Y H:i')
            ]);
        }
        } catch (\Exception $e) {
            \Log::error('Error marking order as delivered', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles del pedido para el albarán
     */
    public function getOrderDetails(Request $request, $orderId)
    {
        try {
            // Cargar el pedido con todas sus relaciones
            $order = \App\Models\OriginalOrder::with([
                'processes' => function($query) {
                    $query->orderBy('grupo_numero');
                },
                'customerClient'
            ])->findOrFail($orderId);

            \Log::info('Order details requested', [
                'order_id' => $orderId,
                'user_id' => auth()->id(),
                'order_found' => true,
                'processes_count' => $order->processes->count()
            ]);

            // Obtener los procesos con sus artículos usando las relaciones de Eloquent
            $processesWithArticles = \App\Models\OriginalOrderProcess::where('original_order_id', $order->id)
                ->with('articles')
                ->orderBy('grupo_numero')
                ->get();

            // Agrupar artículos por grupo_numero
            $articlesByGroup = $processesWithArticles->groupBy('grupo_numero')->map(function($processes, $grupoNum) {
                $allArticles = collect();
                foreach ($processes as $process) {
                    $allArticles = $allArticles->merge($process->articles);
                }
                return [
                    'grupo_numero' => $grupoNum,
                    'items' => $allArticles->unique('codigo_articulo')
                ];
            })->values();

        return response()->json([
            'success' => true,
            'order' => [
                'order_id' => $order->order_id,
                'client_name' => $order->customerClient->name ?? 'N/A',
                'client_number' => $order->client_number,
                'delivery_date' => $order->delivery_date ? $order->delivery_date->format('d/m/Y') : null,
                'estimated_delivery_date' => $order->estimated_delivery_date ? $order->estimated_delivery_date->format('d/m/Y') : null,
                'in_stock' => $order->in_stock,
                'processes' => $order->processes->map(function($process) {
                    return [
                        'code' => $process->code,
                        'name' => $process->name,
                        'grupo_numero' => $process->pivot->grupo_numero,
                        'time' => $process->pivot->time,
                        'box' => $process->pivot->box,
                        'units_box' => $process->pivot->units_box,
                        'number_of_pallets' => $process->pivot->number_of_pallets,
                    ];
                }),
                'articles' => $articlesByGroup
            ]
        ]);
        
        } catch (\Exception $e) {
            \Log::error('Error getting order details', [
                'order_id' => $orderId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading order details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar albarán(es) por correo electrónico
     */
    public function sendDeliveryNoteEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'order_ids' => 'required|string',
            'mode' => 'required|in:single,multiple'
        ]);

        try {
            $orderIds = json_decode($request->input('order_ids'), true);
            $email = $request->input('email');
            $mode = $request->input('mode');

            // Cargar pedidos
            $orders = \App\Models\OriginalOrder::with([
                'processes' => function($query) {
                    $query->orderBy('grupo_numero');
                },
                'customerClient'
            ])->whereIn('id', $orderIds)->get();

            if ($orders->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No orders found'], 404);
            }

            // Generar PDF
            $pdf = \PDF::loadView('deliveries.pdf', [
                'orders' => $orders,
                'mode' => $mode,
                'date' => now()
            ]);

            // Enviar email
            \Mail::send('deliveries.email', ['orders' => $orders, 'mode' => $mode], function($message) use ($email, $pdf, $mode) {
                $message->to($email)
                    ->subject($mode === 'single' ? __('Delivery Note') : __('All Delivery Notes'))
                    ->attachData($pdf->output(), 'delivery-note.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Email sent successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error sending delivery note email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error sending email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar albarán(es) como PDF
     */
    public function downloadPDF(Request $request)
    {
        try {
            $orderIdsParam = $request->query('order_ids');
            $orderIds = explode(',', $orderIdsParam);
            $mode = $request->query('mode', 'single');

            // Cargar pedidos con sus relaciones
            $orders = \App\Models\OriginalOrder::with([
                'processes' => function($query) {
                    $query->orderBy('grupo_numero');
                },
                'customerClient'
            ])->whereIn('id', $orderIds)->get();

            if ($orders->isEmpty()) {
                abort(404, 'No orders found');
            }

            // Preparar datos para cada pedido con artículos agrupados
            $ordersData = [];
            foreach ($orders as $order) {
                $processesWithArticles = \App\Models\OriginalOrderProcess::where('original_order_id', $order->id)
                    ->with('articles')
                    ->orderBy('grupo_numero')
                    ->get();

                $articlesByGroup = $processesWithArticles->groupBy('grupo_numero')->map(function($processes, $grupoNum) {
                    $allArticles = collect();
                    foreach ($processes as $process) {
                        $allArticles = $allArticles->merge($process->articles);
                    }
                    return [
                        'grupo_numero' => $grupoNum,
                        'items' => $allArticles->unique('codigo_articulo')
                    ];
                })->values();

                $ordersData[] = [
                    'order' => $order,
                    'articles' => $articlesByGroup
                ];
            }

            // Generar PDF
            $pdf = \PDF::loadView('deliveries.pdf', [
                'ordersData' => $ordersData,
                'mode' => $mode,
                'date' => now()
            ]);

            $filename = $mode === 'single'
                ? 'albaran-' . $orders->first()->order_id . '.pdf'
                : 'albaranes-' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Error generating PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Vista de impresión para albarán(es)
     */
    public function printDeliveryNote(Request $request)
    {
        try {
            $orderIdsParam = $request->query('order_ids');
            $orderIds = explode(',', $orderIdsParam);
            $mode = $request->query('mode', 'single');

            // Cargar pedidos con sus relaciones
            $orders = \App\Models\OriginalOrder::with([
                'processes' => function($query) {
                    $query->orderBy('grupo_numero');
                },
                'customerClient'
            ])->whereIn('id', $orderIds)->get();

            if ($orders->isEmpty()) {
                abort(404, 'No orders found');
            }

            // Preparar datos para cada pedido con artículos agrupados
            $ordersData = [];
            foreach ($orders as $order) {
                $processesWithArticles = \App\Models\OriginalOrderProcess::where('original_order_id', $order->id)
                    ->with('articles')
                    ->orderBy('grupo_numero')
                    ->get();

                $articlesByGroup = $processesWithArticles->groupBy('grupo_numero')->map(function($processes, $grupoNum) {
                    $allArticles = collect();
                    foreach ($processes as $process) {
                        $allArticles = $allArticles->merge($process->articles);
                    }
                    return [
                        'grupo_numero' => $grupoNum,
                        'items' => $allArticles->unique('codigo_articulo')
                    ];
                })->values();

                $ordersData[] = [
                    'order' => $order,
                    'articles' => $articlesByGroup
                ];
            }

            // Retornar vista de impresión
            return view('deliveries.print', [
                'ordersData' => $ordersData,
                'mode' => $mode,
                'date' => now()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading print view', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            abort(500, 'Error loading print view: ' . $e->getMessage());
        }
    }
}
