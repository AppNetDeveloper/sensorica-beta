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

        $customerClients = CustomerClient::where('customer_id', $customer->id)
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id','name','route_name_id']);

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
            ->with(['customerClient', 'fleetVehicle'])
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
            RouteClientVehicleAssignment::updateOrCreate(
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

            return response()->json(['success' => true, 'message' => __('Client assigned to vehicle successfully')]);

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
}
