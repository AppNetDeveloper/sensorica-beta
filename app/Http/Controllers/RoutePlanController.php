<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RouteName;
use App\Models\CustomerClient;
use App\Models\RouteDayAssignment;
use App\Models\RouteClientVehicleAssignment;
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

        // Cargar asignaciones específicas para la semana actual
        $weekParam = request()->get('week');
        $monday = $weekParam ? \Carbon\Carbon::parse($weekParam)->startOfWeek(\Carbon\Carbon::MONDAY) : now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $sunday = (clone $monday)->addDays(6);
        
        $routeAssignments = RouteDayAssignment::where('customer_id', $customer->id)
            ->whereBetween('assignment_date', [$monday, $sunday])
            ->with(['fleetVehicle'])
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

        return view('customers.routes.index', compact('customer', 'routeNames', 'customerClients', 'fleetVehicles', 'routeAssignments', 'clientVehicleAssignments'));
    }

    public function assignVehicle(Request $request, Customer $customer)
    {
        try {
            \Log::info('AssignVehicle request received', [
                'customer_id' => $customer->id,
                'request_data' => $request->all()
            ]);

            $data = $request->validate([
                'route_name_id' => 'required|exists:route_names,id',
                'fleet_vehicle_id' => 'required|exists:fleet_vehicles,id',
                'day_index' => 'required|integer|min:0|max:6',
                'week' => 'required|date_format:Y-m-d',
            ]);

            \Log::info('Validation passed', ['validated_data' => $data]);

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
                $existingAssignment->update(['active' => true]);
                $assignment = $existingAssignment;
            } else {
                // No existe, crear nuevo
                $assignment = RouteDayAssignment::create([
                    'customer_id' => $customer->id,
                    'route_name_id' => $data['route_name_id'],
                    'fleet_vehicle_id' => $data['fleet_vehicle_id'],
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
}
