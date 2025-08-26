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
    public function index(Customer $customer)
    {
        // Obtener todas las incidencias relacionadas con órdenes de producción del cliente
        $incidents = ProductionOrderIncident::where('customer_id', $customer->id)
            ->with(['productionOrder.productionLine', 'createdBy', 'customer'])
            ->latest()
            ->get();
        // Listas distintas para filtros de UI
        $lines = $incidents->pluck('productionOrder.productionLine')->filter()->unique('id')->values();
        $operators = $incidents->pluck('createdBy')->filter()->unique('id')->values();

        return view('customers.production-order-incidents.index', compact('customer', 'incidents', 'lines', 'operators'));
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
