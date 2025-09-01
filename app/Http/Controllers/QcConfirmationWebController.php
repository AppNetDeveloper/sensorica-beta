<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\QcConfirmation;
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
        // Default window to reduce initial load
        $from = $request->query('from');
        $to = $request->query('to');
        $fromDate = $from ? \Carbon\Carbon::parse($from)->startOfDay() : now()->subDays(7)->startOfDay();
        $toDate = $to ? \Carbon\Carbon::parse($to)->endOfDay() : now()->endOfDay();

        $confirmations = QcConfirmation::query()
            ->with(['productionOrder.productionLine', 'productionLine', 'operator', 'originalOrder'])
            ->whereHas('originalOrder', function ($q) use ($customer) {
                $q->where('customer_id', $customer->id);
            })
            ->whereBetween('confirmed_at', [$fromDate, $toDate])
            ->latest('confirmed_at')
            ->get();

        $lines = $confirmations->pluck('productionLine')->filter()->unique('id')->values();
        $operators = $confirmations->pluck('operator')->filter()->unique('id')->values();

        return view('customers.qc-confirmations.index', compact('customer', 'confirmations', 'lines', 'operators'));
    }
}
