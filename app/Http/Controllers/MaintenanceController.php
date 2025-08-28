<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Maintenance;
use App\Models\ProductionLine;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
                ->addColumn('production_line_stop_label', function($m){
                    $stopped = (bool)($m->production_line_stop);
                    $label = $stopped ? __('Stopped') : __('Not stopped');
                    $badgeClass = $stopped ? 'bg-danger' : 'bg-success';
                    return "<span class='badge {$badgeClass}'>" . e($label) . "</span>";
                })
                ->addColumn('downtime_formatted', function($m){
                    if ($m->start_datetime && $m->end_datetime) {
                        $start = Carbon::parse($m->start_datetime);
                        $end = Carbon::parse($m->end_datetime);
                        $seconds = max(0, $start->diffInSeconds($end));
                        return gmdate('H:i:s', $seconds);
                    }
                    return '-';
                })
                ->addColumn('actions', function($m) use ($customer) {
                    $buttons = '';
                    // Finish is visible to all users if maintenance is still open
                    if (empty($m->end_datetime)) {
                        $finishUrl = route('customers.maintenances.finish.form', [$customer->id, $m->id]);
                        $buttons .= "<a href='{$finishUrl}' class='btn btn-sm btn-warning me-1'>" . __('Finish') . "</a>";
                    }

                    // Edit only for users with permission
                    if (auth()->user()->can('maintenance-edit')) {
                        $editUrl = route('customers.maintenances.edit', [$customer->id, $m->id]);
                        $buttons .= "<a href='{$editUrl}' class='btn btn-sm btn-info me-1'>" . __('Edit') . "</a>";
                    }

                    // Delete only for users with permission
                    if (auth()->user()->can('maintenance-delete')) {
                        $deleteUrl = route('customers.maintenances.destroy', [$customer->id, $m->id]);
                        $csrf = csrf_token();
                        $buttons .= "<form action='{$deleteUrl}' method='POST' style='display:inline' onsubmit=\"return confirm('" . __('Are you sure?') . "')\">".
                                   "<input type='hidden' name='_token' value='{$csrf}'><input type='hidden' name='_method' value='DELETE'>".
                                   "<button type='submit' class='btn btn-sm btn-outline-danger'>" . __('Delete') . "</button></form>";
                    }
                    return $buttons ?: '-';
                })
                ->rawColumns(['actions','production_line_stop_label'])
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
            'production_line_stop' => 'nullable|boolean',
        ]);
        // Ensure production line belongs to customer
        if (!$customer->productionLines()->where('id', $data['production_line_id'])->exists()) {
            abort(403, 'Invalid production line for this customer');
        }
        $data['customer_id'] = $customer->id;
        // Default: stop the line unless explicitly set
        if (!array_key_exists('production_line_stop', $data)) {
            $data['production_line_stop'] = 1;
        }

        $maintenance = Maintenance::create($data);

        // WhatsApp notification on maintenance creation
        try {
            $phones = array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_PHONE_MANTENIMIENTO', ''))));
            if (!empty($phones)) {
                $line = ProductionLine::find($data['production_line_id']);
                $operator = isset($data['operator_id']) ? Operator::find($data['operator_id']) : null;
                $stopped = !empty($data['production_line_stop']);
                $message = sprintf(
                    "Mantenimiento creado:\nCliente: %s\nLínea: %s\nInicio: %s\nOperario: %s\nParo de línea: %s",
                    $customer->name ?? ('ID '.$customer->id),
                    $line->name ?? ('ID '.$data['production_line_id']),
                    Carbon::parse($maintenance->start_datetime)->format('Y-m-d H:i'),
                    $operator->name ?? '-',
                    $stopped ? 'Sí' : 'No'
                );
                $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/send-message';
                foreach ($phones as $phone) {
                    Http::withoutVerifying()->get($apiUrl, [
                        'jid' => $phone . '@s.whatsapp.net',
                        'message' => $message,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Silent fail; optionally log if a logger is used
        }

        // Telegram notification on maintenance creation
        try {
            $peers = array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_MANTENIMIENTO_PEERS', ''))));
            if (!empty($peers)) {
                $line = isset($line) ? $line : ProductionLine::find($data['production_line_id']);
                $operator = isset($operator) ? $operator : (isset($data['operator_id']) ? Operator::find($data['operator_id']) : null);
                $stopped = !empty($data['production_line_stop']);
                $message = sprintf(
                    "Mantenimiento creado:\nCliente: %s\nLínea: %s\nInicio: %s\nOperario: %s\nParo de línea: %s",
                    $customer->name ?? ('ID '.$customer->id),
                    $line->name ?? ('ID '.$data['production_line_id']),
                    Carbon::parse($maintenance->start_datetime)->format('Y-m-d H:i'),
                    $operator->name ?? '-',
                    $stopped ? 'Sí' : 'No'
                );
                $baseUrl = 'http://localhost:3006';
                foreach ($peers as $peer) {
                    $peer = trim($peer);
                    $finalPeer = (str_starts_with($peer, '+') || str_starts_with($peer, '@')) ? $peer : ('+' . $peer);
                    $url = sprintf('%s/send-message/1/%s/%s', $baseUrl, rawurlencode($finalPeer), rawurlencode($message));
                    Http::post($url);
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }

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

    public function finishForm(Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        return view('customers.maintenances.finish', compact('customer','maintenance'));
    }

    public function finishStore(Request $request, Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        $data = $request->validate([
            'annotations' => 'nullable|string',
        ]);
        // Save note into annotations (requested behavior)
        $maintenance->annotations = $data['annotations'] ?? $maintenance->annotations;
        $maintenance->end_datetime = now();
        $maintenance->user_id = auth()->id();
        $maintenance->save();

        return redirect()->route('customers.maintenances.index', $customer->id)
            ->with('success', __('Maintenance finished successfully'));
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
            'production_line_stop' => 'nullable|boolean',
        ]);

        // Ensure production line belongs to customer
        if (!$customer->productionLines()->where('id', $data['production_line_id'])->exists()) {
            abort(403, 'Invalid production line for this customer');
        }

        // Detect if end_datetime is being set now and was previously empty
        $wasOpen = empty($maintenance->end_datetime);
        $isClosingNow = $wasOpen && !empty($data['end_datetime'] ?? null);

        $maintenance->fill($data);

        if ($isClosingNow) {
            $start = Carbon::parse($maintenance->start_datetime);
            $end = Carbon::parse($data['end_datetime']);
            $diff = max(0, $start->diffInSeconds($end));
            $maintenance->accumulated_maintenance_seconds = (int)($maintenance->accumulated_maintenance_seconds ?? 0) + $diff;
        }

        $maintenance->save();

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
