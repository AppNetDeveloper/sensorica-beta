<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Maintenance;
use App\Models\ProductionLine;
use App\Models\Operator;
use App\Models\User;
use App\Models\MaintenanceCause;
use App\Models\MaintenancePart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Exports\MaintenancesExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\MaintenanceChecklistTemplate;
use App\Models\MaintenanceChecklistResponse;
use App\Models\MaintenanceAuditLog;

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
        $query = Maintenance::with(['productionLine:id,name', 'operator:id,name', 'user:id,name', 'causes:id,name', 'parts:id,name'])
            ->select('maintenances.*')
            ->where('customer_id', $customer->id);

        $lineId = $request->get('production_line_id');
        $operatorId = $request->get('operator_id');
        $userId = $request->get('user_id');
        $createdFrom = $request->get('created_from');
        $createdTo = $request->get('created_to');
        $startFrom = $request->get('start_from');
        $startTo = $request->get('start_to');
        $endFrom = $request->get('end_from');
        $endTo = $request->get('end_to');

        // If no date filters are provided, default to last 7 days by CREATED date (not start_datetime)
        // This avoids hiding new rows that have NULL start_datetime (not started yet)
        $noDateFilters = empty($createdFrom) && empty($createdTo) && empty($startFrom) && empty($startTo) && empty($endFrom) && empty($endTo);
        if ($noDateFilters) {
            $createdFrom = Carbon::now()->subDays(7)->toDateString();
            // Limit by created_at only when no explicit date filters are provided
            $query->where('created_at', '>=', $createdFrom . ' 00:00:00');
        }

        if ($lineId) {
            $query->where('production_line_id', $lineId);
        }
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        // Explicit Created range filter
        if ($createdFrom) {
            $query->where('created_at', '>=', $createdFrom . ' 00:00:00');
        }
        if ($createdTo) {
            $query->where('created_at', '<=', $createdTo . ' 23:59:59');
        }

        // Apply start_datetime filters ONLY if the user actually provided them (not by our created default)
        $applyStartFilters = ($startFrom || $startTo);
        if ($applyStartFilters && $startFrom) {
            $query->where('start_datetime', '>=', $startFrom . ' 00:00:00');
        }
        if ($applyStartFilters && $startTo) {
            $query->where('start_datetime', '<=', $startTo . ' 23:59:59');
        }
        if ($endFrom) {
            $query->whereNotNull('end_datetime')->where('end_datetime', '>=', $endFrom . ' 00:00:00');
        }
        if ($endTo) {
            $query->whereNotNull('end_datetime')->where('end_datetime', '<=', $endTo . ' 23:59:59');
        }

        // (Removed created_at default limiter; we now prefill start_from and use the standard start_datetime filter)

        // Totals for current filters (AJAX lightweight endpoint)
        if ($request->ajax() && $request->boolean('totals')) {
            $now = Carbon::now();
            $stoppedTotal = 0; // created -> start (or created -> end/now if not started)
            $downtimeTotal = 0; // start -> end/now
            $overallTotal = 0; // created -> end/now

            foreach ((clone $query)->get() as $m) {
                $created = Carbon::parse($m->created_at);
                $end = $m->end_datetime ? Carbon::parse($m->end_datetime) : $now;
                $overallTotal += max(0, $created->diffInSeconds($end));

                if ($m->start_datetime) {
                    $start = Carbon::parse($m->start_datetime);
                    $stoppedTotal += max(0, $created->diffInSeconds($start));
                    $downtimeTotal += max(0, $start->diffInSeconds($end));
                } else {
                    // Never started: whole period counts as stopped, downtime 0
                    $stoppedTotal += max(0, $created->diffInSeconds($end));
                }
            }

            $fmt = function ($seconds) {
                $seconds = (int) max(0, $seconds);
                $h = floor($seconds / 3600);
                $m = floor(($seconds % 3600) / 60);
                $s = $seconds % 60;
                return sprintf('%02d:%02d:%02d', $h, $m, $s);
            };

            return response()->json([
                'stopped_before_start_seconds' => $stoppedTotal,
                'downtime_seconds' => $downtimeTotal,
                'total_time_seconds' => $overallTotal,
                'stopped_before_start' => $fmt($stoppedTotal),
                'downtime' => $fmt($downtimeTotal),
                'total_time' => $fmt($overallTotal),
            ]);
        }

        // Respuesta para DataTables (AJAX)
        if ($request->ajax()) {
            $query->orderByDesc('created_at');
            return DataTables::of($query)
                ->addColumn('production_line', function($m){
                    if ($m->relationLoaded('productionLine') && $m->productionLine) {
                        return $m->productionLine->name;
                    }
                    return $m->production_line_id ? __('Línea #:id', ['id' => $m->production_line_id]) : '-';
                })
                ->editColumn('created_at', function($m){ return $m->created_at ? Carbon::parse($m->created_at)->format('Y-m-d H:i') : null; })
                ->editColumn('start_datetime', function($m){ return $m->start_datetime ? Carbon::parse($m->start_datetime)->format('Y-m-d H:i') : null; })
                ->editColumn('end_datetime', function($m){ return $m->end_datetime ? Carbon::parse($m->end_datetime)->format('Y-m-d H:i') : null; })
                ->editColumn('operator_id', function($m){ return $m->operator_id ?? '-'; })
                ->addColumn('operator_name', function($m){ return optional($m->operator)->name; })
                ->editColumn('user_id', function($m){ return $m->user_id ?? '-'; })
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
                    // Downtime (avería): start -> end (or live until now if started and not ended)
                    if ($m->start_datetime) {
                        $start = Carbon::parse($m->start_datetime);
                        $end = $m->end_datetime ? Carbon::parse($m->end_datetime) : Carbon::now();
                        $seconds = max(0, $start->diffInSeconds($end));
                        $live = gmdate('H:i:s', $seconds);
                        $acc = (int)($m->accumulated_maintenance_seconds ?? 0);
                        if ($acc > 0) {
                            $accFmt = gmdate('H:i:s', $acc);
                            return $live . "<div class='text-muted small'>(" . __('Accumulated') . ": {$accFmt})</div>";
                        }
                        return $live;
                    }
                    return '-';
                })
                ->addColumn('stopped_formatted', function($m){
                    // Parada previa: created -> start (or live until now if not started and not ended)
                    $created = Carbon::parse($m->created_at);
                    if ($m->start_datetime) {
                        $start = Carbon::parse($m->start_datetime);
                        $seconds = max(0, $created->diffInSeconds($start));
                        $live = gmdate('H:i:s', $seconds);
                        $acc = (int)($m->accumulated_maintenance_seconds_stoped ?? 0);
                        if ($acc > 0) {
                            $accFmt = gmdate('H:i:s', $acc);
                            return $live . "<div class='text-muted small'>(" . __('Accumulated') . ": {$accFmt})</div>";
                        }
                        return $live;
                    }
                    // Not started yet: if still open, show live since created; if ended without start, show created -> end
                    $end = $m->end_datetime ? Carbon::parse($m->end_datetime) : Carbon::now();
                    $seconds = max(0, $created->diffInSeconds($end));
                    $live = gmdate('H:i:s', $seconds);
                    $acc = (int)($m->accumulated_maintenance_seconds_stoped ?? 0);
                    if ($acc > 0) {
                        $accFmt = gmdate('H:i:s', $acc);
                        return $live . "<div class='text-muted small'>(" . __('Accumulated') . ": {$accFmt})</div>";
                    }
                    return $live;
                })
                ->addColumn('total_time_formatted', function($m){
                    // Total ciclo: created -> end (or live until now if not ended)
                    $created = Carbon::parse($m->created_at);
                    $end = $m->end_datetime ? Carbon::parse($m->end_datetime) : Carbon::now();
                    $seconds = max(0, $created->diffInSeconds($end));
                    return gmdate('H:i:s', $seconds);
                })
                ->addColumn('causes_list', function($m){
                    return $m->causes ? $m->causes->pluck('name')->join(', ') : '';
                })
                ->addColumn('parts_list', function($m){
                    return $m->parts ? $m->parts->pluck('name')->join(', ') : '';
                })
                ->addColumn('status_badge', function($m){
                    if (empty($m->start_datetime)) {
                        return "<span class='badge bg-secondary'><i class='ti ti-clock me-1'></i>" . __('Pendiente') . "</span>";
                    } elseif (empty($m->end_datetime)) {
                        $created = Carbon::parse($m->created_at);
                        $now = Carbon::now();
                        $hoursOpen = $created->diffInHours($now);
                        $badgeClass = $hoursOpen > 24 ? 'bg-danger' : 'bg-warning';
                        $icon = $hoursOpen > 24 ? 'ti-alert-triangle' : 'ti-tool';
                        return "<span class='badge {$badgeClass}'><i class='ti {$icon} me-1'></i>" . __('En Curso') . "</span>";
                    } else {
                        return "<span class='badge bg-success'><i class='ti ti-check me-1'></i>" . __('Finalizado') . "</span>";
                    }
                })
                ->addColumn('actions', function($m) use ($customer) {
                    $buttons = '';
                    // Start button: if not started yet
                    if (empty($m->start_datetime)) {
                        $startUrl = route('customers.maintenances.start', [$customer->id, $m->id]);
                        $csrf = csrf_token();
                        $buttons .= "<form action='{$startUrl}' method='POST' style='display:inline' onsubmit=\"return confirm('" . __('Are you sure?') . "')\">".
                                   "<input type='hidden' name='_token' value='{$csrf}'>".
                                   "<button type='submit' class='btn btn-sm btn-success me-1'>" . __('Iniciar mantenimiento') . "</button></form>";
                    }
                    // Finish is visible only if maintenance has been STARTED and is still open
                    if (!empty($m->start_datetime) && empty($m->end_datetime)) {
                        $finishUrl = route('customers.maintenances.finish.form', [$customer->id, $m->id]);
                        $buttons .= "<a href='{$finishUrl}' class='btn btn-sm btn-warning me-1'>" . __('Finish') . "</a>";
                    }

                    // Details button (no permission required) only if finished (has end_datetime)
                    if (!empty($m->end_datetime)) {
                        $annotations = e((string) $m->annotations);
                        $opAnnotations = e((string) $m->operator_annotations);
                        $causes = e(($m->causes ? $m->causes->pluck('name')->join(', ') : ''));
                        $parts = e(($m->parts ? $m->parts->pluck('name')->join(', ') : ''));
                        $productionLineName = e(optional($m->productionLine)->name ?? '');
                        $operatorName = e(optional($m->operator)->name ?? '');
                        $userName = e(optional($m->user)->name ?? '');
                        $buttons .= "<button type='button' class='btn btn-sm btn-secondary me-1 btn-maint-details' data-bs-toggle='modal' data-bs-target='#maintenanceDetailsModal' "
                                   . "data-annotations='{$annotations}' "
                                   . "data-operator-annotations='{$opAnnotations}' "
                                   . "data-causes='{$causes}' "
                                   . "data-parts='{$parts}' "
                                   . "data-production-line='{$productionLineName}' "
                                   . "data-operator-name='{$operatorName}' "
                                   . "data-user-name='{$userName}'>" . __('Detalles') . "</button>";
                    }

                    // Audit history button
                    $auditUrl = route('customers.maintenances.audit', [$customer->id, $m->id]);
                    $buttons .= "<a href='{$auditUrl}' class='btn btn-sm btn-outline-secondary me-1' title='" . __('Historial') . "'><i class='ti ti-history'></i></a>";

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
                ->rawColumns(['actions','production_line_stop_label','stopped_formatted','downtime_formatted','status_badge'])
                ->make(true);
        }
        $lines = $customer->productionLines()->orderBy('name')->get(['id','name']);
        $operators = Operator::orderBy('name')->get(['id','name']);
        $users = User::orderBy('name')->get(['id','name']);

        return view('customers.maintenances.index', compact(
            'customer','lines','operators','users',
            'lineId','operatorId','userId',
            'createdFrom','createdTo','startFrom','startTo','endFrom','endTo'
        ));
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
        // Normalize empty datetime inputs to null before validation
        foreach (['start_datetime','end_datetime'] as $f) {
            if ($request->has($f) && trim((string)$request->input($f)) === '') {
                $request->merge([$f => null]);
            }
        }
        $data = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'start_datetime' => 'nullable|date',
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

        // Do not set start/end datetime on creation unless explicitly provided (avoid "00:00:00")
        if (!array_key_exists('start_datetime', $data) || empty($data['start_datetime'])) {
            unset($data['start_datetime']);
        }
        if (!array_key_exists('end_datetime', $data) || empty($data['end_datetime'])) {
            unset($data['end_datetime']);
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
                    Carbon::now()->format('Y-m-d H:i'),
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
        // Prevent finishing if it was not started
        if (empty($maintenance->start_datetime)) {
            return redirect()->route('customers.maintenances.index', $customer->id)
                ->with('error', __('Maintenance must be started before it can be finished'));
        }
        $causes = MaintenanceCause::where('customer_id', $customer->id)
            ->where('active', 1)
            ->where(function($q) use ($maintenance) {
                $q->whereNull('production_line_id')
                  ->orWhere('production_line_id', $maintenance->production_line_id);
            })
            ->orderBy('name')
            ->get(['id','name']);
        $parts = MaintenancePart::where('customer_id', $customer->id)
            ->where('active', 1)
            ->where(function($q) use ($maintenance) {
                $q->whereNull('production_line_id')
                  ->orWhere('production_line_id', $maintenance->production_line_id);
            })
            ->orderBy('name')
            ->get(['id','name']);
        $selectedCauseIds = $maintenance->causes()->pluck('maintenance_cause_id')->toArray();
        $selectedPartIds = $maintenance->parts()->pluck('maintenance_part_id')->toArray();
        
        // Load checklist template for this line
        $checklistTemplate = MaintenanceChecklistTemplate::with('items')
            ->where('customer_id', $customer->id)
            ->where('active', 1)
            ->where(function($q) use ($maintenance) {
                $q->whereNull('production_line_id')
                  ->orWhere('production_line_id', $maintenance->production_line_id);
            })
            ->first();
        
        // Load existing responses
        $existingResponses = $maintenance->checklistResponses()->pluck('checked', 'checklist_item_id')->toArray();
        
        return view('customers.maintenances.finish', compact('customer','maintenance','causes','parts','selectedCauseIds','selectedPartIds','checklistTemplate','existingResponses'));
    }

    public function finishStore(Request $request, Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        // Prevent finishing if it was not started
        if (empty($maintenance->start_datetime)) {
            return redirect()->route('customers.maintenances.index', $customer->id)
                ->with('error', __('Maintenance must be started before it can be finished'));
        }
        $data = $request->validate([
            'annotations' => 'nullable|string',
            'cause_ids' => 'nullable|array',
            'cause_ids.*' => 'integer',
            'part_ids' => 'nullable|array',
            'part_ids.*' => 'integer',
            'checklist' => 'nullable|array',
            'checklist.*' => 'boolean',
        ]);
        
        // Validate required checklist items
        $checklistTemplate = MaintenanceChecklistTemplate::with('items')
            ->where('customer_id', $customer->id)
            ->where('active', 1)
            ->where(function($q) use ($maintenance) {
                $q->whereNull('production_line_id')
                  ->orWhere('production_line_id', $maintenance->production_line_id);
            })
            ->first();
        
        if ($checklistTemplate) {
            $requiredItems = $checklistTemplate->items()->where('required', true)->pluck('id')->toArray();
            $checkedItems = array_keys(array_filter($data['checklist'] ?? []));
            $missingRequired = array_diff($requiredItems, $checkedItems);
            
            if (!empty($missingRequired)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['checklist' => __('Debe completar todos los elementos obligatorios del checklist')]);
            }
        }
        // Save note into annotations (requested behavior)
        $maintenance->annotations = $data['annotations'] ?? $maintenance->annotations;
        $maintenance->end_datetime = now();
        $maintenance->user_id = auth()->id();
        // Calculate incident (stopped-before-start) time
        $stoppedAccum = (int)($maintenance->accumulated_maintenance_seconds_stoped ?? 0);
        if (!empty($maintenance->start_datetime)) {
            $stoppedAccum += max(0, Carbon::parse($maintenance->created_at)->diffInSeconds(Carbon::parse($maintenance->start_datetime)));
        } else {
            // Never started: count whole period as stopped
            $stoppedAccum += max(0, Carbon::parse($maintenance->created_at)->diffInSeconds(Carbon::parse($maintenance->end_datetime)));
        }
        $maintenance->accumulated_maintenance_seconds_stoped = $stoppedAccum;
        $maintenance->save();

        // Sync causes and parts limited to this customer
        $allowedCauseIds = MaintenanceCause::where('customer_id', $customer->id)->pluck('id')->toArray();
        $allowedPartIds = MaintenancePart::where('customer_id', $customer->id)->pluck('id')->toArray();
        $syncCauseIds = array_values(array_intersect($allowedCauseIds, (array)($data['cause_ids'] ?? [])));
        $syncPartIds = array_values(array_intersect($allowedPartIds, (array)($data['part_ids'] ?? [])));
        $maintenance->causes()->sync($syncCauseIds);
        $maintenance->parts()->sync($syncPartIds);
        
        // Save checklist responses
        if ($checklistTemplate && !empty($data['checklist'])) {
            foreach ($checklistTemplate->items as $item) {
                MaintenanceChecklistResponse::updateOrCreate(
                    [
                        'maintenance_id' => $maintenance->id,
                        'checklist_item_id' => $item->id,
                    ],
                    [
                        'checked' => isset($data['checklist'][$item->id]) && $data['checklist'][$item->id],
                    ]
                );
            }
        }
        
        MaintenanceAuditLog::logAction(
            $maintenance, 
            'finished', 
            'Mantenimiento finalizado por ' . auth()->user()->name . 
            '. Causas: ' . ($maintenance->causes->pluck('name')->join(', ') ?: 'ninguna') . 
            '. Piezas: ' . ($maintenance->parts->pluck('name')->join(', ') ?: 'ninguna')
        );

        return redirect()->route('customers.maintenances.index', $customer->id)
            ->with('success', __('Maintenance finished successfully'));
    }

    // POST: start maintenance -> set start_datetime=now and toggle production_line_stop to 1 if was 0
    public function start(Request $request, Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        if (empty($maintenance->start_datetime)) {
            $maintenance->start_datetime = now();
        }
        if ((int)$maintenance->production_line_stop === 0) {
            $maintenance->production_line_stop = 1;
        }
        $maintenance->save();
        
        MaintenanceAuditLog::logAction($maintenance, 'started', 'Mantenimiento iniciado por ' . auth()->user()->name);
        
        return redirect()->route('customers.maintenances.index', $customer->id)
            ->with('success', __('Maintenance started'));
    }

    public function update(Request $request, Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);

        // Normalize empty datetime inputs to null before validation
        foreach (['start_datetime','end_datetime'] as $f) {
            if ($request->has($f) && trim((string)$request->input($f)) === '') {
                $request->merge([$f => null]);
            }
        }
        $data = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'start_datetime' => 'nullable|date',
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

    public function exportExcel(Request $request, Customer $customer)
    {
        $query = $this->buildQuery($request, $customer);
        $totals = $this->calculateTotals($query);
        
        return Excel::download(
            new MaintenancesExport(clone $query, $totals), 
            'mantenimientos_' . $customer->name . '_' . date('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf(Request $request, Customer $customer)
    {
        $query = $this->buildQuery($request, $customer);
        $maintenances = $query->get();
        $totals = $this->calculateTotals($query);
        
        $pdf = Pdf::loadView('customers.maintenances.pdf', [
            'customer' => $customer,
            'maintenances' => $maintenances,
            'totals' => $totals,
            'filters' => $request->all()
        ]);
        
        return $pdf->download('mantenimientos_' . $customer->name . '_' . date('Y-m-d') . '.pdf');
    }

    private function buildQuery(Request $request, Customer $customer)
    {
        $query = Maintenance::with(['productionLine', 'operator', 'user', 'causes:id,name', 'parts:id,name'])
            ->where('customer_id', $customer->id);

        $lineId = $request->get('production_line_id');
        $operatorId = $request->get('operator_id');
        $userId = $request->get('user_id');
        $createdFrom = $request->get('created_from');
        $createdTo = $request->get('created_to');
        $startFrom = $request->get('start_from');
        $startTo = $request->get('start_to');
        $endFrom = $request->get('end_from');
        $endTo = $request->get('end_to');

        $noDateFilters = empty($createdFrom) && empty($createdTo) && empty($startFrom) && empty($startTo) && empty($endFrom) && empty($endTo);
        if ($noDateFilters) {
            $createdFrom = Carbon::now()->subDays(7)->toDateString();
            $query->where('created_at', '>=', $createdFrom . ' 00:00:00');
        }

        if ($lineId) $query->where('production_line_id', $lineId);
        if ($operatorId) $query->where('operator_id', $operatorId);
        if ($userId) $query->where('user_id', $userId);
        if ($createdFrom) $query->where('created_at', '>=', $createdFrom . ' 00:00:00');
        if ($createdTo) $query->where('created_at', '<=', $createdTo . ' 23:59:59');
        
        $applyStartFilters = ($startFrom || $startTo);
        if ($applyStartFilters && $startFrom) $query->where('start_datetime', '>=', $startFrom . ' 00:00:00');
        if ($applyStartFilters && $startTo) $query->where('start_datetime', '<=', $startTo . ' 23:59:59');
        if ($endFrom) $query->whereNotNull('end_datetime')->where('end_datetime', '>=', $endFrom . ' 00:00:00');
        if ($endTo) $query->whereNotNull('end_datetime')->where('end_datetime', '<=', $endTo . ' 23:59:59');

        return $query->orderByDesc('created_at');
    }

    private function calculateTotals($query)
    {
        $now = Carbon::now();
        $stoppedTotal = 0;
        $downtimeTotal = 0;
        $overallTotal = 0;

        foreach ((clone $query)->get() as $m) {
            $created = Carbon::parse($m->created_at);
            $end = $m->end_datetime ? Carbon::parse($m->end_datetime) : $now;
            $overallTotal += max(0, $created->diffInSeconds($end));

            if ($m->start_datetime) {
                $start = Carbon::parse($m->start_datetime);
                $stoppedTotal += max(0, $created->diffInSeconds($start));
                $downtimeTotal += max(0, $start->diffInSeconds($end));
            } else {
                $stoppedTotal += max(0, $created->diffInSeconds($end));
            }
        }

        $fmt = function ($seconds) {
            $seconds = (int) max(0, $seconds);
            $h = floor($seconds / 3600);
            $m = floor(($seconds % 3600) / 60);
            $s = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        };

        return [
            'stopped_before_start_seconds' => $stoppedTotal,
            'downtime_seconds' => $downtimeTotal,
            'total_time_seconds' => $overallTotal,
            'stopped_before_start' => $fmt($stoppedTotal),
            'downtime' => $fmt($downtimeTotal),
            'total_time' => $fmt($overallTotal),
        ];
    }

    public function auditHistory(Customer $customer, Maintenance $maintenance)
    {
        abort_unless($maintenance->customer_id === $customer->id, 404);
        
        $logs = $maintenance->auditLogs()->with('user')->paginate(50);
        
        return view('customers.maintenances.audit', compact('customer', 'maintenance', 'logs'));
    }

    public function dashboard(Customer $customer)
    {
        $now = Carbon::now();
        $startDate = $now->copy()->subDays(30);
        
        // KPIs generales
        $totalMaintenances = Maintenance::where('customer_id', $customer->id)
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $openMaintenances = Maintenance::where('customer_id', $customer->id)
            ->whereNull('end_datetime')
            ->count();
        
        $avgDowntime = Maintenance::where('customer_id', $customer->id)
            ->whereNotNull('end_datetime')
            ->where('created_at', '>=', $startDate)
            ->avg('accumulated_maintenance_seconds');
        
        // Top causas
        $topCauses = MaintenanceCause::withCount(['maintenances' => function($q) use ($customer, $startDate) {
                $q->where('maintenances.customer_id', $customer->id)
                  ->where('maintenances.created_at', '>=', $startDate);
            }])
            ->where('maintenance_causes.customer_id', $customer->id)
            ->orderByDesc('maintenances_count')
            ->limit(10)
            ->get();

        // Top piezas
        $topParts = MaintenancePart::withCount(['maintenances' => function($q) use ($customer, $startDate) {
                $q->where('maintenances.customer_id', $customer->id)
                  ->where('maintenances.created_at', '>=', $startDate);
            }])
            ->where('maintenance_parts.customer_id', $customer->id)
            ->orderByDesc('maintenances_count')
            ->limit(10)
            ->get();
        // Mantenimientos por línea
        $byLine = Maintenance::selectRaw('production_line_id, COUNT(*) as total')
            ->where('customer_id', $customer->id)
            ->where('created_at', '>=', $startDate)
            ->groupBy('production_line_id')
            ->with('productionLine:id,name')
            ->get();
        
        // Tendencia últimos 30 días
        $trend = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $count = Maintenance::where('customer_id', $customer->id)
                ->whereDate('created_at', $date)
                ->count();
            $trend[] = [
                'date' => $date,
                'count' => $count
            ];
        }
        
        // Tiempo promedio por línea
        $avgTimeByLine = Maintenance::selectRaw('production_line_id, AVG(accumulated_maintenance_seconds) as avg_time')
            ->where('customer_id', $customer->id)
            ->whereNotNull('end_datetime')
            ->where('created_at', '>=', $startDate)
            ->groupBy('production_line_id')
            ->with('productionLine:id,name')
            ->get();
        
        return view('customers.maintenances.dashboard', compact(
            'customer',
            'totalMaintenances',
            'openMaintenances',
            'avgDowntime',
            'topCauses',
            'topParts',
            'byLine',
            'trend',
            'avgTimeByLine'
        ));
    }
}
