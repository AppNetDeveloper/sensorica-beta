<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QualityIssue;
use Illuminate\Http\Request;

class QualityIncidentController extends Controller
{
    public function __construct()
    {
        // Reuse existing permission similar to production-order incidents
        $this->middleware('permission:productionline-incidents', ['only' => ['index']]);
    }

    /**
     * List QC quality incidences for a customer.
     */
    public function index(Customer $customer)
    {
        // Fetch issues linked either to the source original order or the duplicated QC order for this customer
        $incidents = QualityIssue::query()
            ->with(['productionOrder.productionLine', 'originalOrder', 'originalOrderQc', 'productionLine', 'operator'])
            ->where(function ($q) use ($customer) {
                $q->whereHas('originalOrder', function ($qq) use ($customer) {
                    $qq->where('customer_id', $customer->id);
                })
                ->orWhereHas('originalOrderQc', function ($qq) use ($customer) {
                    $qq->where('customer_id', $customer->id);
                });
            })
            ->latest()
            ->get();

        // Distintas líneas y operadores presentes en estas incidencias para filtros
        $lines = $incidents->pluck('productionLine')->filter()->unique('id')->values();
        $operators = $incidents->pluck('operator')->filter()->unique('id')->values();

        return view('customers.quality-incidents.index', compact('customer', 'incidents', 'lines', 'operators'));
    }
}
