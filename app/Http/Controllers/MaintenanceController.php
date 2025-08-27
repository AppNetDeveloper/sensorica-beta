<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Maintenance;
use App\Models\ProductionLine;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'XSS']);
        $this->middleware('permission:maintenance-show')->only(['index']);
        $this->middleware('permission:maintenance-create')->only(['create', 'store']);
        $this->middleware('permission:maintenance-edit')->only(['edit', 'update']);
        $this->middleware('permission:maintenance-delete')->only(['destroy']);
    }

    public function index(Request $request, Customer $customer)
    {
        $query = Maintenance::with(['productionLine', 'operator', 'user'])
            ->where('customer_id', $customer->id);

        $lineId = $request->get('production_line_id');
        $operatorId = $request->get('operator_id');
        $userId = $request->get('user_id');
        $startFrom = $request->get('start_from');
        $startTo = $request->get('start_to');
        $endFrom = $request->get('end_from');
        $endTo = $request->get('end_to');

        if ($lineId) {
            $query->where('production_line_id', $lineId);
        }
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($startFrom) {
            $query->where('start_datetime', '>=', $startFrom . ' 00:00:00');
        }
        if ($startTo) {
            $query->where('start_datetime', '<=', $startTo . ' 23:59:59');
        }
        if ($endFrom) {
            $query->whereNotNull('end_datetime')->where('end_datetime', '>=', $endFrom . ' 00:00:00');
        }
        if ($endTo) {
            $query->whereNotNull('end_datetime')->where('end_datetime', '<=', $endTo . ' 23:59:59');
        }

        // Respuesta para DataTables (AJAX)
        if ($request->ajax()) {
            $query->orderByDesc('start_datetime');
            return DataTables::of($query)
                ->addColumn('production_line', function($m){ return optional($m->productionLine)->name; })
                ->editColumn('start_datetime', function($m){ return $m->start_datetime ? Carbon::parse($m->start_datetime)->format('Y-m-d H:i') : null; })
                ->editColumn('end_datetime', function($m){ return $m->end_datetime ? Carbon::parse($m->end_datetime)->format('Y-m-d H:i') : null; })
                ->addColumn('operator_name', function($m){ return optional($m->operator)->name; })
                ->addColumn('user_name', function($m){ return optional($m->user)->name; })
                ->addColumn('annotations_short', function($m){ return Str::limit($m->annotations, 60); })
                ->addColumn('operator_annotations_short', function($m){ return Str::limit($m->operator_annotations, 60); })
                ->addColumn('actions', function($m) use ($customer) {
                    $buttons = '';
                    if (auth()->user()->can('maintenance-edit')) {
                        $editUrl = route('customers.maintenances.edit', [$customer->id, $m->id]);
                        $buttons .= "<a href='{$editUrl}' class='btn btn-sm btn-info me-1'>" . __('Edit') . "</a>";
                    }
                    if (auth()->user()->can('maintenance-delete')) {
                        $deleteUrl = route('customers.maintenances.destroy', [$customer->id, $m->id]);
                        $csrf = csrf_token();
                        $buttons .= "<form action='{$deleteUrl}' method='POST' style='display:inline' onsubmit=\"return confirm('" . __('Are you sure?') . "')\">".
                                   "<input type='hidden' name='_token' value='{$csrf}'><input type='hidden' name='_method' value='DELETE'>".
                                   "<button type='submit' class='btn btn-sm btn-outline-danger'>" . __('Delete') . "</button></form>";
                    }
                    return $buttons ?: '-';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);
        $operators = Operator::orderBy('name')->get(['id','name']);
        $users = User::orderBy('name')->get(['id','name']);

        return view('customers.maintenances.index', compact('customer','lines','operators','users','lineId','operatorId','userId','startFrom','startTo','endFrom','endTo'));
    }

    public function create(Customer $customer)
    {
        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);
        $operators = Operator::orderBy('name')->get(['id','name']);
        $users = User::orderBy('name')->get(['id','name']);
        return view('customers.maintenances.create', compact('customer','lines','operators','users'));
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'annotations' => 'nullable|string',
            'operator_id' => 'nullable|exists:operators,id',
            'user_id' => 'nullable|exists:users,id',
        ]);
        // Ensure production line belongs to customer
        if (!$customer->productionLines()->where('id', $data['production_line_id'])->exists()) {
            abort(403, 'Invalid production line for this customer');
        }
        $data['customer_id'] = $customer->id;

        Maintenance::create($data);

        return redirect()->route('customers.maintenances.index', $customer->id)
            ->with('success', __('Maintenance created successfully'));
    }

    public function edit(Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);
        $operators = Operator::orderBy('name')->get(['id','name']);
        $users = User::orderBy('name')->get(['id','name']);
        return view('customers.maintenances.edit', compact('customer','maintenance','lines','operators','users'));
    }

    public function update(Request $request, Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);

        $data = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'start_datetime' => 'required|date',
            'end_datetime' => 'nullable|date|after_or_equal:start_datetime',
            'annotations' => 'nullable|string',
            'operator_id' => 'nullable|exists:operators,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        // Ensure production line belongs to customer
        if (!$customer->productionLines()->where('id', $data['production_line_id'])->exists()) {
            abort(403, 'Invalid production line for this customer');
        }

        $maintenance->update($data);

        return redirect()->route('customers.maintenances.index', $customer->id)
            ->with('success', __('Maintenance updated successfully'));
    }

    public function destroy(Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        $maintenance->delete();
        return redirect()->route('customers.maintenances.index', $customer->id)
            ->with('success', __('Maintenance deleted successfully'));
    }
}
