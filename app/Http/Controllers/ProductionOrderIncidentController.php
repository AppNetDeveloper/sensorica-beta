<?php

namespace App\Http\Controllers;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderIncident;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionOrderIncidentController extends Controller
{
    /**
     * Constructor para aplicar middlewares de permisos.
     */
    public function __construct()
    {
        $this->middleware('permission:productionline-orders', ['only' => ['index', 'show']]);
        $this->middleware('permission:productionline-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the incidents.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function index(Customer $customer, Request $request)
    {
        $baseQuery = ProductionOrderIncident::with(['productionOrder.productionLine', 'createdBy', 'customer'])
            ->where('customer_id', $customer->id);

        // Opciones para filtros (todas las incidencias del cliente)
        $allIncidents = (clone $baseQuery)->get();
        $lines = $allIncidents->pluck('productionOrder.productionLine')->filter()->unique('id')->values();
        $operators = $allIncidents->pluck('createdBy')->filter()->unique('id')->values();

        // Aplicar filtros en base a la petición
        $incidentsQuery = (clone $baseQuery);

        if ($request->filled('date_from')) {
            $incidentsQuery->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $incidentsQuery->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('line_id')) {
            $incidentsQuery->whereHas('productionOrder', function ($query) use ($request) {
                $query->where('production_line_id', $request->input('line_id'));
            });
        }

        if ($request->filled('operator_id')) {
            $incidentsQuery->where('created_by', $request->input('operator_id'));
        }

        $incidents = $incidentsQuery->latest()->get();

        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'line_id' => $request->input('line_id'),
            'operator_id' => $request->input('operator_id'),
        ];

        return view('customers.production-order-incidents.index', compact('customer', 'incidents', 'lines', 'operators', 'filters'));
    }

    /**
     * Display the specified incident.
     *
     * @param  \App\Models\Customer  $customer
     * @param  \App\Models\ProductionOrderIncident  $incident
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer, ProductionOrderIncident $incident)
    {
        // Verificar que la incidencia pertenece al cliente
        if ($incident->customer_id != $customer->id) {
            abort(403, 'Unauthorized action.');
        }
        
        // Cargar relaciones necesarias
        $incident->load(['productionOrder', 'createdBy']);
        
        return view('customers.production-order-incidents.show', compact('customer', 'incident'));
    }

    /**
     * Remove the specified incident from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @param  \App\Models\ProductionOrderIncident  $incident
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer, ProductionOrderIncident $incident)
    {
        // Verificar que la incidencia pertenece al cliente
        if ($incident->customer_id != $customer->id) {
            abort(403, 'Unauthorized action.');
        }
        
        $incident->delete();
        
        return redirect()->route('customers.production-order-incidents.index', $customer->id)
            ->with('success', __('Incident deleted successfully'));
    }
}
