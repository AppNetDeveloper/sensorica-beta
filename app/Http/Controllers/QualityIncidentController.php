<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QualityIssue;
use Carbon\Carbon;
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
    public function index(Customer $customer, Request $request)
    {
        $baseQuery = QualityIssue::query()
            ->with(['productionOrder.productionLine', 'originalOrder', 'originalOrderQc', 'productionLine', 'operator'])
            ->where(function ($q) use ($customer) {
                $q->whereHas('originalOrder', function ($qq) use ($customer) {
                    $qq->where('customer_id', $customer->id);
                })
                ->orWhereHas('originalOrderQc', function ($qq) use ($customer) {
                    $qq->where('customer_id', $customer->id);
                });
            });

        $allIncidents = (clone $baseQuery)->get();
        $lines = $allIncidents->pluck('productionLine')->filter()->unique('id')->values();
        $operators = $allIncidents->pluck('operator')->filter()->unique('id')->values();

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$dateFrom) {
            $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        }

        if (!$dateTo) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $incidentsQuery = (clone $baseQuery)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($request->filled('line_id')) {
            $lineId = $request->input('line_id');
            $incidentsQuery->where(function ($query) use ($lineId) {
                $query->where('production_line_id', $lineId)
                    ->orWhereHas('productionOrder', function ($qq) use ($lineId) {
                        $qq->where('production_line_id', $lineId);
                    });
            });
        }

        if ($request->filled('operator_id')) {
            $incidentsQuery->where('operator_id', $request->input('operator_id'));
        }

        $incidents = $incidentsQuery->latest()->get();

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'line_id' => $request->input('line_id'),
            'operator_id' => $request->input('operator_id'),
        ];

        return view('customers.quality-incidents.index', compact('customer', 'incidents', 'lines', 'operators', 'filters'));
    }
}
