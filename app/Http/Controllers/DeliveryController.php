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
     * Marcar un pedido como entregado
     */
    public function markAsDelivered(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:original_orders,id',
        ]);

        $order = \App\Models\OriginalOrder::findOrFail($data['order_id']);
        
        // Verificar que el usuario tiene acceso a este pedido
        $hasAccess = RouteDayAssignment::where('user_id', auth()->id())
            ->whereHas('customer.clientVehicleAssignments', function($q) use ($order) {
                $q->whereHas('orderAssignments', function($q2) use ($order) {
                    $q2->where('original_order_id', $order->id);
                });
            })
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $order->actual_delivery_date = now();
        $order->delivery_date = now(); // También actualizar delivery_date
        $order->save();

        return response()->json([
            'success' => true,
            'message' => __('Order marked as delivered'),
            'delivered_at' => $order->actual_delivery_date->format('d/m/Y H:i')
        ]);
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
}
