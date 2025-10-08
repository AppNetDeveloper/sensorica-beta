<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QcConfirmation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QcConfirmationWebController extends Controller
{
    public function __construct()
    {
        // Reuse similar permission as incidents
        $this->middleware('permission:productionline-incidents', ['only' => ['index']]);
    }

    /**
     * List QC confirmations for a given customer.
     */
    public function index(Customer $customer, Request $request)
    {
        $baseQuery = QcConfirmation::query()
            ->with(['productionOrder.productionLine', 'productionLine', 'operator', 'originalOrder'])
            ->whereHas('originalOrder', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            });

        $allConfirmations = (clone $baseQuery)->get();
        $lines = $allConfirmations->pluck('productionLine')->filter()->unique('id')->values();
        $operators = $allConfirmations->pluck('operator')->filter()->unique('id')->values();

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$dateFrom) {
            $dateFrom = Carbon::now()->subDays(7)->format('Y-m-d');
        }

        if (!$dateTo) {
            $dateTo = Carbon::now()->format('Y-m-d');
        }

        $confirmationsQuery = (clone $baseQuery)
            ->whereDate('confirmed_at', '>=', $dateFrom)
            ->whereDate('confirmed_at', '<=', $dateTo);

        if ($request->filled('line_id')) {
            $lineId = $request->input('line_id');
            $confirmationsQuery->where(function ($query) use ($lineId) {
                $query->where('production_line_id', $lineId)
                    ->orWhereHas('productionOrder', function ($qq) use ($lineId) {
                        $qq->where('production_line_id', $lineId);
                    });
            });
        }

        if ($request->filled('operator_id')) {
            $confirmationsQuery->where('operator_id', $request->input('operator_id'));
        }

        $confirmations = $confirmationsQuery->latest('confirmed_at')->get();

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'line_id' => $request->input('line_id'),
            'operator_id' => $request->input('operator_id'),
        ];

        return view('customers.qc-confirmations.index', compact('customer', 'confirmations', 'lines', 'operators', 'filters'));
    }
}
