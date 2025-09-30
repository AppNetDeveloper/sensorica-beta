<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RouteName;
use App\Models\CustomerClient;
use App\Models\RouteDayAssignment;
use App\Models\RouteClientVehicleAssignment;
use App\Models\OriginalOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoutePlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        $this->middleware('permission:routes-view')->only(['index']);
        $this->middleware('permission:routes-view')->only(['assignVehicle', 'removeVehicle']);
    }

    public function index(Request $request, Customer $customer)
    {
        // Vista semanal simple: la vista calcula la semana y renderiza 7 columnas.
        // Cargar nombres de ruta con days_mask y clientes asociados a rutas
        $routeNames = RouteName::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id','name','days_mask']);

        $customerClients = CustomerClient::with(['pendingDeliveries' => function ($query) {
                $query->select('id','customer_client_id','order_id','delivery_date','estimated_delivery_date','finished_at');
            }])
            ->where('customer_id', $customer->id)
            ->where('active', 1)
            ->whereHas('pendingDeliveries')
            ->orderBy('name')
            ->get(['id','name','route_name_id','customer_id']);

        $fleetVehicles = \App\Models\FleetVehicle::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('plate')
            ->get(['id','plate','vehicle_type','default_route_name_id']);

        // Obtener usuarios disponibles para asignar como conductores
        $availableDrivers = \App\Models\User::orderBy('name')->get(['id','name','email']);

        // Cargar asignaciones específicas para la semana actual
        $weekParam = request()->get('week');
        $monday = $weekParam ? \Carbon\Carbon::parse($weekParam)->startOfWeek(\Carbon\Carbon::MONDAY) : now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $sunday = (clone $monday)->addDays(6);
        
        $routeAssignments = RouteDayAssignment::where('customer_id', $customer->id)
            ->whereBetween('assignment_date', [$monday, $sunday])
            ->with(['fleetVehicle', 'driver'])
            ->get();

        // Cargar asignaciones cliente-vehículo para la semana
        $clientVehicleAssignments = RouteClientVehicleAssignment::where('customer_id', $customer->id)
            ->whereBetween('assignment_date', [$monday, $sunday])
            ->with([
                'customerClient.pendingDeliveries' => function ($query) {
                    $query->select('id','customer_client_id','order_id','delivery_date','estimated_delivery_date','finished_at');
                },
                'fleetVehicle',
                'orderAssignments' => function ($query) {
                    $query->orderBy('sort_order', 'asc');
                },
                'orderAssignments.originalOrder' => function ($query) {
                    $query->select('id','order_id','delivery_date','estimated_delivery_date','finished_at');
                }
            ])
            ->get();

        \Log::info('Route assignments loaded', [
            'customer_id' => $customer->id,
            'week_range' => [$monday->format('Y-m-d'), $sunday->format('Y-m-d')],
            'vehicle_assignments_count' => $routeAssignments->count(),
            'client_vehicle_assignments_count' => $clientVehicleAssignments->count()
        ]);

        return view('customers.routes.index', compact(
            'customer',
            'routeNames',
            'customerClients',
            'fleetVehicles',
            'availableDrivers',
            'routeAssignments',
            'clientVehicleAssignments'
        ));
    }

    public function assignVehicle(Request $request, Customer $customer)
    {
        try {
            \Log::info('Attempting to assign vehicle', [
                'customer_id' => $customer->id,
                'request_data' => $request->all()
            ]);

            $data = $request->validate([
                'assignment_id' => 'nullable|exists:route_day_assignments,id',
                'route_name_id' => 'nullable|exists:route_names,id',
                'fleet_vehicle_id' => 'nullable|exists:fleet_vehicles,id',
                'user_id' => 'nullable|exists:users,id',
                'day_index' => 'nullable|integer|min:0|max:6',
                'week' => 'nullable|date_format:Y-m-d',
            ]);

            \Log::info('Validation passed', ['validated_data' => $data]);

            // Si viene assignment_id, solo actualizar el conductor
            if (isset($data['assignment_id'])) {
                $assignment = RouteDayAssignment::where('customer_id', $customer->id)
                    ->where('id', $data['assignment_id'])
                    ->firstOrFail();
                
                $assignment->update([
                    'user_id' => $data['user_id'] ?? null,
                ]);

                \Log::info('Driver updated', ['assignment_id' => $assignment->id, 'user_id' => $data['user_id']]);

                return response()->json(['success' => true, 'message' => __('Driver updated successfully')]);
            }

            // Si no, crear/actualizar asignación completa
            $monday = \Carbon\Carbon::parse($data['week'])->startOfWeek(\Carbon\Carbon::MONDAY);
            $assignmentDate = (clone $monday)->addDays($data['day_index']);

            \Log::info('Calculated dates', [
                'monday' => $monday->format('Y-m-d'),
                'assignment_date' => $assignmentDate->format('Y-m-d')
            ]);

            // Verificar si ya existe esta asignación exacta
            $existingAssignment = RouteDayAssignment::where([
                'customer_id' => $customer->id,
                'route_name_id' => $data['route_name_id'],
                'fleet_vehicle_id' => $data['fleet_vehicle_id'],
                'assignment_date' => $assignmentDate,
                'day_of_week' => $data['day_index'],
            ])->first();
            
            if ($existingAssignment) {
                // Ya existe, solo actualizar
                $existingAssignment->update([
                    'active' => true,
                    'user_id' => $data['user_id'] ?? null,
                ]);
                $assignment = $existingAssignment;
            } else {
                // No existe, crear nuevo
                $assignment = RouteDayAssignment::create([
                    'customer_id' => $customer->id,
                    'route_name_id' => $data['route_name_id'],
                    'fleet_vehicle_id' => $data['fleet_vehicle_id'],
                    'user_id' => $data['user_id'] ?? null,
                    'assignment_date' => $assignmentDate,
                    'day_of_week' => $data['day_index'],
                    'active' => true,
                ]);
            }

            \Log::info('Assignment created/updated', ['assignment_id' => $assignment->id]);

            return response()->json(['success' => true, 'message' => __('Vehicle assigned successfully')]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in assignVehicle', ['errors' => $e->errors()]);
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in assignVehicle', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'customer_id' => $customer->id
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Devuelve los pedidos pendientes del cliente con procesos y artículos para el modal de detalle.
     */
    public function clientDetails(Customer $customer, CustomerClient $client, Request $request): JsonResponse
    {
        abort_unless($client->customer_id === $customer->id, 404);

        $orders = OriginalOrder::where('customer_id', $customer->id)
            ->where('customer_client_id', $client->id)
            ->whereNull('actual_delivery_date')
            ->with([
                'routeName:id,name',
                'orderProcesses' => function ($query) {
                    $query->with(['process:id,name'])
                        ->with(['articles' => function ($articlesQuery) {
                            $articlesQuery->select('id', 'original_order_process_id', 'codigo_articulo', 'descripcion_articulo', 'grupo_articulo', 'in_stock');
                        }])
                        ->orderBy('grupo_numero');
                }
            ])
            ->orderByDesc('finished_at')
            ->get();

        $mappedOrders = $orders->map(function (OriginalOrder $order) {
            return [
                'id' => $order->id,
                'order_id' => $order->order_id,
                'route' => $order->routeName ? [
                    'id' => $order->routeName->id,
                    'name' => $order->routeName->name,
                ] : null,
                'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                'estimated_delivery_date' => $order->estimated_delivery_date?->format('Y-m-d'),
                'finished_at' => $order->finished_at?->format('Y-m-d H:i'),
                'in_stock' => $order->in_stock,
                'processes' => $order->orderProcesses->map(function ($process) {
                    return [
                        'id' => $process->id,
                        'process_id' => $process->process_id,
                        'name' => $process->process?->name,
                        'grupo_numero' => $process->grupo_numero,
                        'box' => $process->box,
                        'units_box' => $process->units_box,
                        'number_of_pallets' => $process->number_of_pallets,
                        'time' => $process->time,
                        'in_stock' => $process->in_stock,
                        'articles' => $process->articles->map(function ($article) {
                            return [
                                'id' => $article->id,
                                'codigo_articulo' => $article->codigo_articulo,
                                'descripcion_articulo' => $article->descripcion_articulo,
                                'grupo_articulo' => $article->grupo_articulo,
                                'in_stock' => $article->in_stock,
                            ];
                        })->values(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'address' => $client->address,
                'phone' => $client->phone,
            ],
            'orders' => $mappedOrders,
        ]);
    }

    public function removeVehicle(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'assignment_id' => 'required|exists:route_day_assignments,id',
            ]);

            $assignment = RouteDayAssignment::where('customer_id', $customer->id)
                ->where('id', $data['assignment_id'])
                ->first();

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => __('Assignment not found')], 404);
            }

            \Log::info('Removing vehicle assignment', [
                'assignment_id' => $assignment->id,
                'customer_id' => $customer->id,
                'route_name_id' => $assignment->route_name_id,
                'assignment_date' => $assignment->assignment_date->format('Y-m-d')
            ]);

            // Obtener todos los clientes asignados a este vehículo en este día
            $clientAssignments = RouteClientVehicleAssignment::where('fleet_vehicle_id', $assignment->fleet_vehicle_id)
                ->whereDate('assignment_date', $assignment->assignment_date)
                ->where('customer_id', $customer->id)
                ->get();

            // Para cada cliente, limpiar estimated_delivery_date de sus pedidos
            foreach ($clientAssignments as $clientAssignment) {
                $orderAssignments = \App\Models\RouteOrderAssignment::where('route_client_vehicle_assignment_id', $clientAssignment->id)->get();
                
                foreach ($orderAssignments as $orderAssignment) {
                    $order = $orderAssignment->originalOrder;
                    if ($order) {
                        $order->estimated_delivery_date = null;
                        $order->save();
                    }
                }
                
                // Eliminar la asignación del cliente (cascade eliminará route_order_assignments)
                $clientAssignment->delete();
            }

            // Eliminar la asignación del vehículo
            $assignment->delete();

            return response()->json(['success' => true, 'message' => __('Vehicle removed successfully')]);

        } catch (\Exception $e) {
            \Log::error('Error in removeVehicle', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function assignClientToVehicle(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'customer_client_id' => 'required|exists:customer_clients,id',
                'fleet_vehicle_id' => 'required|exists:fleet_vehicles,id',
                'route_name_id' => 'required|exists:route_names,id',
                'day_index' => 'required|integer|min:0|max:6',
                'week' => 'required|date_format:Y-m-d',
            ]);

            $monday = \Carbon\Carbon::parse($data['week'])->startOfWeek(\Carbon\Carbon::MONDAY);
            $assignmentDate = (clone $monday)->addDays($data['day_index']);

            \Log::info('Assigning client to vehicle', [
                'customer_id' => $customer->id,
                'client_id' => $data['customer_client_id'],
                'vehicle_id' => $data['fleet_vehicle_id'],
                'route_id' => $data['route_name_id'],
                'assignment_date' => $assignmentDate->format('Y-m-d')
            ]);

            // Crear o actualizar asignación cliente-vehículo
            $assignment = RouteClientVehicleAssignment::updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'customer_client_id' => $data['customer_client_id'],
                    'fleet_vehicle_id' => $data['fleet_vehicle_id'],
                    'assignment_date' => $assignmentDate,
                ],
                [
                    'route_name_id' => $data['route_name_id'],
                    'day_of_week' => $data['day_index'],
                    'active' => true,
                ]
            );

            // Obtener pedidos pendientes del cliente
            $pendingOrders = \App\Models\OriginalOrder::where('customer_client_id', $data['customer_client_id'])
                ->whereNotNull('finished_at')
                ->whereNull('actual_delivery_date')
                ->get();

            // Crear route_order_assignments para cada pedido y actualizar estimated_delivery_date
            foreach ($pendingOrders as $index => $order) {
                // Crear asignación de pedido a camión
                \App\Models\RouteOrderAssignment::updateOrCreate(
                    [
                        'route_client_vehicle_assignment_id' => $assignment->id,
                        'original_order_id' => $order->id,
                    ],
                    [
                        'active' => true,
                        'sort_order' => $index + 1,
                    ]
                );

                // Actualizar estimated_delivery_date en la orden
                $order->estimated_delivery_date = $assignmentDate;
                $order->save();
            }

            return response()->json([
                'success' => true,
                'message' => __('Client assigned to vehicle successfully'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in assignClientToVehicle', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function removeClientFromVehicle(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'assignment_id' => 'required|exists:route_client_vehicle_assignments,id',
            ]);

            $assignment = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('id', $data['assignment_id'])
                ->first();

            if (!$assignment) {
                return response()->json(['success' => false, 'message' => __('Client assignment not found')], 404);
            }

            \Log::info('Removing client from vehicle', [
                'assignment_id' => $assignment->id,
                'customer_id' => $customer->id,
                'client_id' => $assignment->customer_client_id,
                'vehicle_id' => $assignment->fleet_vehicle_id,
                'assignment_date' => $assignment->assignment_date->format('Y-m-d')
            ]);

            $clientId = $assignment->customer_client_id;
            $routeId = $assignment->route_name_id;
            $dayIndex = (int)$assignment->day_of_week;
            $assignmentDate = $assignment->assignment_date?->format('Y-m-d');

            // Limpiar estimated_delivery_date de todos los pedidos asociados
            $orderAssignments = \App\Models\RouteOrderAssignment::where('route_client_vehicle_assignment_id', $assignment->id)->get();
            foreach ($orderAssignments as $orderAssignment) {
                $order = $orderAssignment->originalOrder;
                if ($order) {
                    $order->estimated_delivery_date = null;
                    $order->save();
                }
            }

            // Eliminar asignación (cascade eliminará route_order_assignments automáticamente)
            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => __('Client removed from vehicle successfully'),
                'client_id' => $clientId,
                'route_name_id' => $routeId,
                'day_index' => $dayIndex,
                'assignment_date' => $assignmentDate,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in removeClientFromVehicle', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function reorderClients(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'route_name_id' => 'required|exists:route_names,id',
                'fleet_vehicle_id' => 'required|exists:fleet_vehicles,id',
                'day_index' => 'required|integer|min:0|max:6',
                'week' => 'required|date_format:Y-m-d',
                'ordered_assignment_ids' => 'required|array',
                'ordered_assignment_ids.*' => 'integer|exists:route_client_vehicle_assignments,id',
            ]);

            $monday = \Carbon\Carbon::parse($data['week'])->startOfWeek(\Carbon\Carbon::MONDAY);
            $assignmentDate = (clone $monday)->addDays($data['day_index']);

            \DB::transaction(function () use ($customer, $data, $assignmentDate) {
                foreach ($data['ordered_assignment_ids'] as $index => $assignmentId) {
                    RouteClientVehicleAssignment::where('customer_id', $customer->id)
                        ->where('id', $assignmentId)
                        ->where('route_name_id', $data['route_name_id'])
                        ->where('fleet_vehicle_id', $data['fleet_vehicle_id'])
                        ->whereDate('assignment_date', $assignmentDate->format('Y-m-d'))
                        ->update(['sort_order' => $index + 1]);
                }
            });

            return response()->json(['success' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in reorderClients', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function moveClientAssignment(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'assignment_id' => 'required|integer|exists:route_client_vehicle_assignments,id',
                'route_name_id' => 'required|exists:route_names,id',
                'fleet_vehicle_id' => 'required|exists:fleet_vehicles,id',
                'day_index' => 'required|integer|min:0|max:6',
                'week' => 'required|date_format:Y-m-d',
            ]);

            $monday = \Carbon\Carbon::parse($data['week'])->startOfWeek(\Carbon\Carbon::MONDAY);
            $assignmentDate = (clone $monday)->addDays($data['day_index']);

            $assignment = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('id', $data['assignment_id'])
                ->firstOrFail();

            // Next sort order in target list
            $maxSort = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('route_name_id', $data['route_name_id'])
                ->where('fleet_vehicle_id', $data['fleet_vehicle_id'])
                ->whereDate('assignment_date', $assignmentDate->format('Y-m-d'))
                ->max('sort_order');

            $assignment->update([
                'route_name_id' => $data['route_name_id'],
                'fleet_vehicle_id' => $data['fleet_vehicle_id'],
                'day_of_week' => $data['day_index'],
                'assignment_date' => $assignmentDate,
                'sort_order' => (int)$maxSort + 1,
            ]);

            return response()->json(['success' => true]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Log::error('Error in moveClientAssignment', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function toggleOrderActive(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'order_assignment_id' => 'required|exists:route_order_assignments,id',
            ]);

            $orderAssignment = \App\Models\RouteOrderAssignment::find($data['order_assignment_id']);

            if (!$orderAssignment) {
                return response()->json(['success' => false, 'message' => __('Order assignment not found')], 404);
            }

            // Verificar que pertenece al customer
            $clientAssignment = $orderAssignment->routeClientVehicleAssignment;
            if ($clientAssignment->customer_id !== $customer->id) {
                return response()->json(['success' => false, 'message' => __('Unauthorized')], 403);
            }

            // Toggle active
            $orderAssignment->active = !$orderAssignment->active;
            $orderAssignment->save();

            \Log::info('Order assignment toggled', [
                'order_assignment_id' => $orderAssignment->id,
                'original_order_id' => $orderAssignment->original_order_id,
                'active' => $orderAssignment->active,
            ]);

            return response()->json([
                'success' => true,
                'active' => $orderAssignment->active,
                'message' => $orderAssignment->active 
                    ? __('Order activated for loading') 
                    : __('Order deactivated from loading'),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in toggleOrderActive', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function reorderOrders(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'client_assignment_id' => 'required|exists:route_client_vehicle_assignments,id',
                'ordered_ids' => 'required|array',
                'ordered_ids.*' => 'integer|exists:route_order_assignments,id',
            ]);

            $clientAssignment = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('id', $data['client_assignment_id'])
                ->first();

            if (!$clientAssignment) {
                return response()->json(['success' => false, 'message' => __('Client assignment not found')], 404);
            }

            \DB::transaction(function () use ($data) {
                foreach ($data['ordered_ids'] as $index => $orderAssignmentId) {
                    \App\Models\RouteOrderAssignment::where('id', $orderAssignmentId)
                        ->update(['sort_order' => $index + 1]);
                }
            });

            \Log::info('Orders reordered', [
                'client_assignment_id' => $data['client_assignment_id'],
                'new_order' => $data['ordered_ids'],
            ]);

            return response()->json(['success' => true, 'message' => __('Orders reordered successfully')]);

        } catch (\Exception $e) {
            \Log::error('Error in reorderOrders', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function copyFromPreviousWeek(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'route_name_id' => 'required|exists:route_names,id',
                'fleet_vehicle_id' => 'required|exists:fleet_vehicles,id',
                'day_index' => 'required|integer|min:0|max:6',
                'week' => 'required|date_format:Y-m-d',
            ]);

            $currentMonday = \Carbon\Carbon::parse($data['week'])->startOfWeek(\Carbon\Carbon::MONDAY);
            $previousMonday = (clone $currentMonday)->subWeek();
            
            $currentDate = (clone $currentMonday)->addDays($data['day_index']);
            $previousDate = (clone $previousMonday)->addDays($data['day_index']);

            // Buscar asignaciones de la semana anterior
            $previousAssignments = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('route_name_id', $data['route_name_id'])
                ->where('fleet_vehicle_id', $data['fleet_vehicle_id'])
                ->whereDate('assignment_date', $previousDate)
                ->with('customerClient')
                ->orderBy('sort_order')
                ->get();

            if ($previousAssignments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No assignments found for this route in the previous week')
                ], 404);
            }

            $copiedCount = 0;
            foreach ($previousAssignments as $index => $prevAssignment) {
                // Verificar que el cliente tenga pedidos pendientes ACTUALES
                $hasPendingOrders = \App\Models\OriginalOrder::where('customer_client_id', $prevAssignment->customer_client_id)
                    ->whereNotNull('finished_at')
                    ->whereNull('actual_delivery_date')
                    ->exists();

                if (!$hasPendingOrders) {
                    continue; // Saltar si no tiene pedidos pendientes
                }

                // Crear nueva asignación
                $newAssignment = RouteClientVehicleAssignment::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'customer_client_id' => $prevAssignment->customer_client_id,
                        'fleet_vehicle_id' => $data['fleet_vehicle_id'],
                        'assignment_date' => $currentDate,
                    ],
                    [
                        'route_name_id' => $data['route_name_id'],
                        'day_of_week' => $data['day_index'],
                        'sort_order' => $index + 1,
                        'active' => true,
                    ]
                );

                // Obtener pedidos pendientes ACTUALES del cliente
                $pendingOrders = \App\Models\OriginalOrder::where('customer_client_id', $prevAssignment->customer_client_id)
                    ->whereNotNull('finished_at')
                    ->whereNull('actual_delivery_date')
                    ->get();

                // Crear route_order_assignments y actualizar estimated_delivery_date
                foreach ($pendingOrders as $orderIndex => $order) {
                    \App\Models\RouteOrderAssignment::updateOrCreate(
                        [
                            'route_client_vehicle_assignment_id' => $newAssignment->id,
                            'original_order_id' => $order->id,
                        ],
                        [
                            'active' => true,
                            'sort_order' => $orderIndex + 1,
                        ]
                    );

                    $order->estimated_delivery_date = $currentDate;
                    $order->save();
                }

                $copiedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => __('Copied :count client(s) from previous week', ['count' => $copiedCount]),
                'copied_count' => $copiedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in copyFromPreviousWeek', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function printRouteSheet(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'assignment_id' => 'required|exists:route_day_assignments,id',
            ]);

            $assignment = RouteDayAssignment::with([
                'fleetVehicle',
                'routeName'
            ])->where('customer_id', $customer->id)
              ->where('id', $data['assignment_id'])
              ->firstOrFail();

            // Obtener clientes asignados a este vehículo en este día
            $clientAssignments = RouteClientVehicleAssignment::where('fleet_vehicle_id', $assignment->fleet_vehicle_id)
                ->whereDate('assignment_date', $assignment->assignment_date)
                ->where('customer_id', $customer->id)
                ->with([
                    'customerClient',
                    'orderAssignments' => function ($query) {
                        $query->where('active', true)->orderBy('sort_order', 'asc');
                    },
                    'orderAssignments.originalOrder' => function ($query) {
                        $query->select('id','order_id','customer_client_id','delivery_date','estimated_delivery_date','finished_at','order_details');
                    }
                ])
                ->orderBy('sort_order', 'asc')
                ->get();

            return view('customers.routes.print', compact('customer', 'assignment', 'clientAssignments'));

        } catch (\Exception $e) {
            \Log::error('Error in printRouteSheet', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error generating route sheet');
        }
    }

    public function exportToExcel(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'assignment_id' => 'required|exists:route_day_assignments,id',
            ]);

            $assignment = RouteDayAssignment::where('customer_id', $customer->id)
                ->where('id', $data['assignment_id'])
                ->firstOrFail();

            $fileName = 'hoja_ruta_' . $assignment->fleetVehicle->plate . '_' . $assignment->assignment_date->format('Y-m-d') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\RouteSheetExport($data['assignment_id'], $customer->id),
                $fileName
            );

        } catch (\Exception $e) {
            \Log::error('Error in exportToExcel', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error generating Excel file');
        }
    }

    public function copyEntireRouteFromPreviousWeek(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'route_name_id' => 'required|exists:route_names,id',
                'day_index' => 'required|integer|min:0|max:6',
                'week' => 'required|date_format:Y-m-d',
            ]);

            $currentMonday = \Carbon\Carbon::parse($data['week'])->startOfWeek(\Carbon\Carbon::MONDAY);
            $previousMonday = (clone $currentMonday)->subWeek();
            
            $currentDate = (clone $currentMonday)->addDays($data['day_index']);
            $previousDate = (clone $previousMonday)->addDays($data['day_index']);

            // Buscar TODAS las asignaciones de esa ruta en la semana anterior (todos los vehículos)
            $previousAssignments = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('route_name_id', $data['route_name_id'])
                ->whereDate('assignment_date', $previousDate)
                ->with('customerClient', 'fleetVehicle')
                ->orderBy('fleet_vehicle_id')
                ->orderBy('sort_order')
                ->get();

            if ($previousAssignments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('No assignments found for this route in the previous week')
                ], 404);
            }

            $copiedCount = 0;
            $vehiclesProcessed = [];

            foreach ($previousAssignments as $index => $prevAssignment) {
                // Verificar que el cliente tenga pedidos pendientes ACTUALES
                $hasPendingOrders = \App\Models\OriginalOrder::where('customer_client_id', $prevAssignment->customer_client_id)
                    ->whereNotNull('finished_at')
                    ->whereNull('actual_delivery_date')
                    ->exists();

                if (!$hasPendingOrders) {
                    continue;
                }

                // Asegurar que el vehículo esté asignado a la ruta este día
                $vehicleAssignment = RouteDayAssignment::firstOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'route_name_id' => $data['route_name_id'],
                        'fleet_vehicle_id' => $prevAssignment->fleet_vehicle_id,
                        'assignment_date' => $currentDate,
                    ],
                    [
                        'day_of_week' => $data['day_index'],
                        'active' => true,
                    ]
                );

                $vehiclesProcessed[$prevAssignment->fleet_vehicle_id] = true;

                // Crear nueva asignación cliente-vehículo
                $newAssignment = RouteClientVehicleAssignment::updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'customer_client_id' => $prevAssignment->customer_client_id,
                        'fleet_vehicle_id' => $prevAssignment->fleet_vehicle_id,
                        'assignment_date' => $currentDate,
                    ],
                    [
                        'route_name_id' => $data['route_name_id'],
                        'day_of_week' => $data['day_index'],
                        'sort_order' => $prevAssignment->sort_order,
                        'active' => true,
                    ]
                );

                // Obtener y asignar pedidos pendientes ACTUALES
                $pendingOrders = \App\Models\OriginalOrder::where('customer_client_id', $prevAssignment->customer_client_id)
                    ->whereNotNull('finished_at')
                    ->whereNull('actual_delivery_date')
                    ->get();

                foreach ($pendingOrders as $orderIndex => $order) {
                    \App\Models\RouteOrderAssignment::updateOrCreate(
                        [
                            'route_client_vehicle_assignment_id' => $newAssignment->id,
                            'original_order_id' => $order->id,
                        ],
                        [
                            'active' => true,
                            'sort_order' => $orderIndex + 1,
                        ]
                    );

                    $order->estimated_delivery_date = $currentDate;
                    $order->save();
                }

                $copiedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => __('Copied :count client(s) and :vehicles vehicle(s) from previous week', [
                    'count' => $copiedCount,
                    'vehicles' => count($vehiclesProcessed)
                ]),
                'copied_count' => $copiedCount,
                'vehicles_count' => count($vehiclesProcessed)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in copyEntireRouteFromPreviousWeek', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function printEntireRoute(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'route_name_id' => 'required|exists:route_names,id',
                'day_date' => 'required|date_format:Y-m-d',
            ]);

            $routeName = RouteName::findOrFail($data['route_name_id']);
            $dayDate = \Carbon\Carbon::parse($data['day_date']);

            // Obtener todos los vehículos asignados a esta ruta en este día
            $vehicleAssignments = RouteDayAssignment::where('customer_id', $customer->id)
                ->where('route_name_id', $data['route_name_id'])
                ->whereDate('assignment_date', $dayDate)
                ->with('fleetVehicle')
                ->get();

            // Obtener todos los clientes asignados
            $clientAssignments = RouteClientVehicleAssignment::where('customer_id', $customer->id)
                ->where('route_name_id', $data['route_name_id'])
                ->whereDate('assignment_date', $dayDate)
                ->with([
                    'customerClient',
                    'fleetVehicle',
                    'orderAssignments' => function ($query) {
                        $query->where('active', true)->orderBy('sort_order', 'asc');
                    },
                    'orderAssignments.originalOrder'
                ])
                ->orderBy('fleet_vehicle_id')
                ->orderBy('sort_order', 'asc')
                ->get();

            return view('customers.routes.print-route', compact('customer', 'routeName', 'dayDate', 'vehicleAssignments', 'clientAssignments'));

        } catch (\Exception $e) {
            \Log::error('Error in printEntireRoute', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error generating route print');
        }
    }

    public function exportEntireRouteToExcel(Request $request, Customer $customer)
    {
        try {
            $data = $request->validate([
                'route_name_id' => 'required|exists:route_names,id',
                'day_date' => 'required|date_format:Y-m-d',
            ]);

            $routeName = RouteName::findOrFail($data['route_name_id']);
            $dayDate = \Carbon\Carbon::parse($data['day_date']);

            $fileName = 'ruta_completa_' . \Str::slug($routeName->name) . '_' . $dayDate->format('Y-m-d') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\EntireRouteExport($data['route_name_id'], $data['day_date'], $customer->id),
                $fileName
            );

        } catch (\Exception $e) {
            \Log::error('Error in exportEntireRouteToExcel', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, 'Error generating Excel file');
        }
    }
}
