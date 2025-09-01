@extends('layouts.admin')

@section('title', __('Incidencias QC'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Incidencias QC') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-danger text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('Incidencias QC') - {{ $customer->name }}</h5>
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
                            @php($aiUrl = config('services.ai.url'))
                            @php($aiToken = config('services.ai.token'))
                            @if(!empty($aiUrl) && !empty($aiToken))
                            <div class="btn-group btn-group-sm me-2" role="group" aria-label="IA">
                                <button type="button" class="btn btn-dark" id="btn-ai-open" data-bs-toggle="modal" data-bs-target="#aiPromptModal" title="@lang('Análisis con IA')">
                                    <i class="bi bi-stars me-1 text-white"></i><span class="d-none d-sm-inline">@lang('Análisis IA')</span>
                                </button>
                            </div>
                            @endif
                            <div class="btn-group btn-group-sm" role="group" aria-label="Kanban">
                                <a href="{{ route('customers.order-organizer', $customer->id) }}" class="btn btn-light">
                                    <i class="fas fa-th me-1"></i><span class="d-none d-sm-inline">@lang('Order Organizer')</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    <!-- Filtros -->
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha desde')</label>
                            <input type="date" class="form-control form-control-sm" id="filter-date-from">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha hasta')</label>
                            <input type="date" class="form-control form-control-sm" id="filter-date-to">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Línea de producción')</label>
                            <select id="filter-line" class="form-select form-select-sm">
                                <option value="">@lang('Todas')</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name ?? ('#'.$line->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Operador')</label>
                            <select id="filter-operator" class="form-select form-select-sm">
                                <option value="">@lang('Todos')</option>
                                @foreach($operators as $op)
                                    <option value="{{ $op->id }}">{{ $op->name ?? ('#'.$op->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                    </div>

                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="qc-incidents-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('Original Order')</th>
                                    <th class="text-uppercase">@lang('Original Order QC')</th>
                                    <th class="text-uppercase">@lang('Process')</th>
                                    <th class="text-uppercase">@lang('Info')</th>
                                    <th class="text-uppercase">@lang('Reason')</th>
                                    <th class="text-uppercase">@lang('Created At')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incidents as $index => $issue)
                                    @php
                                        $status = optional($issue->productionOrder)->status;
                                        $rowClass = '';
                                        if ($status === 3) { // incidencias
                                            $rowClass = 'table-danger';
                                        } elseif ($status === 2) { // finalizada
                                            $rowClass = 'table-success';
                                        } elseif ($status === 1) { // en curso
                                            $rowClass = 'table-warning';
                                        } elseif ($status === 0) { // pendiente
                                            $rowClass = 'table-light';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}" data-line-id="{{ $issue->production_line_id ?? optional($issue->productionOrder)->production_line_id }}"
                                        data-operator-id="{{ $issue->operator_id ?? '' }}"
                                        data-created-at="{{ optional($issue->created_at)->format('Y-m-d') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if($issue->originalOrder)
                                                <a href="{{ route('customers.original-orders.show', ['customer' => $customer->id, 'originalOrder' => $issue->originalOrder->id]) }}" class="text-decoration-none">
                                                    #{{ $issue->originalOrder->order_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($issue->originalOrderQc)
                                                <a href="{{ route('customers.original-orders.show', ['customer' => $customer->id, 'originalOrder' => $issue->originalOrderQc->id]) }}" class="text-decoration-none">
                                                    #{{ $issue->originalOrderQc->order_id }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($issue->productionOrder)
                                                #{{ $issue->productionOrder->order_id }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($issue->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $issue->productionLine->name ?? ('L#'.$issue->productionLine->id) }}</span>
                                            @elseif(optional($issue->productionOrder)->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $issue->productionOrder->productionLine->name ?? ('L#'.$issue->productionOrder->productionLine->id) }}</span>
                                            @endif
                                            @if($issue->operator)
                                                <span class="badge bg-secondary" title="@lang('Operador')"><i class="fas fa-user"></i> {{ $issue->operator->name }}</span>
                                            @elseif($issue->operator_id)
                                                <span class="badge bg-secondary" title="@lang('Operador')"><i class="fas fa-user"></i> #{{ $issue->operator_id }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($issue->texto, 80) }}</td>
                                        <td>{{ optional($issue->created_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enable Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });
            // Set default date range to last 7 days
            const to = new Date();
            const from = new Date(to);
            from.setDate(from.getDate() - 7);
            const fmt = d => d.toISOString().slice(0,10);
            if (!$('#filter-date-from').val()) $('#filter-date-from').val(fmt(from));
            if (!$('#filter-date-to').val()) $('#filter-date-to').val(fmt(to));
            // Custom filter by date range, line, and operator
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
                const tr = $(settings.aoData[dataIndex].nTr);
                const lineFilter = $('#filter-line').val();
                const operatorFilter = $('#filter-operator').val();
                const dateFrom = $('#filter-date-from').val();
                const dateTo = $('#filter-date-to').val();

                const lineId = (tr.data('line-id') || '').toString();
                const operatorId = (tr.data('operator-id') || '').toString();
                const createdAt = (tr.data('created-at') || '').toString(); // 'YYYY-MM-DD'

                // Line filter
                if (lineFilter && lineId !== lineFilter) return false;
                // Operator filter
                if (operatorFilter && operatorId !== operatorFilter) return false;
                // Date range filter
                if (dateFrom && createdAt < dateFrom) return false;
                if (dateTo && createdAt > dateTo) return false;
                return true;
            });

            const table = $('#qc-incidents-table').DataTable({
                responsive: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[6, 'desc']],
                dom: 'Bfrtip',
                buttons: [
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'print', text: 'Imprimir', className: 'btn btn-sm btn-outline-primary' },
                ]
            });

            // Re-draw on filter changes
            $('#filter-line, #filter-operator, #filter-date-from, #filter-date-to').on('change keyup', function() {
                table.draw();
            });

            // === AI integration (same behavior as maintenances) ===
            const AI_URL = @json(config('services.ai.url'));
            const AI_TOKEN = @json(config('services.ai.token'));

            function collectCurrentRows() {
                const rows = $('#qc-incidents-table').DataTable().rows({ page: 'current' }).nodes();
                const out = [];
                $('#qc-incidents-table').DataTable().rows({ page: 'current' }).every(function(rowIdx){
                    const tr = $(rows[rowIdx]);
                    const cells = $(this.node()).find('td');
                    out.push({
                        index: $(cells[0]).text().trim(),
                        original_order: $(cells[1]).text().trim(),
                        original_order_qc: $(cells[2]).text().trim(),
                        process_or_po: $(cells[3]).text().trim(),
                        info: $(cells[4]).text().trim(),
                        reason: $(cells[5]).text().trim(),
                        created_at: $(cells[6]).text().trim(),
                        line_id: (tr.data('line-id') || '').toString(),
                        operator_id: (tr.data('operator-id') || '').toString(),
                        created_date: (tr.data('created-at') || '').toString()
                    });
                });
                const filters = {
                    line: $('#filter-line').val() || '',
                    operator: $('#filter-operator').val() || '',
                    date_from: $('#filter-date-from').val() || '',
                    date_to: $('#filter-date-to').val() || ''
                };
                return { rows: out, filters };
            }

            function showLoading(show) {
                $('#btn-ai-send').prop('disabled', !!show).toggleClass('disabled', !!show);
            }

            async function startAiTask(prompt) {
                if (!AI_URL || !AI_TOKEN) { alert('AI config missing'); return; }
                showLoading(true);
                try {
                    const payload = collectCurrentRows();
                    console.log('[AI][QC Incidents] Collected rows:', payload.rows.length, 'filters:', payload.filters);
                    let combinedPrompt;
                    try {
                        combinedPrompt = `${prompt}\n\n=== Datos para analizar (JSON) ===\n${JSON.stringify(payload, null, 2)}`;
                    } catch (e) {
                        combinedPrompt = `${prompt}\n\n=== Datos para analizar (JSON) ===\n[Error serializando datos]`;
                    }
                    console.log('[AI] Combined prompt length:', combinedPrompt.length);
                    console.log('[AI] Combined prompt preview:', combinedPrompt.substring(0, 500));
                    const fd = new FormData();
                    fd.append('prompt', combinedPrompt);

                    console.log('[AI] Starting task POST ...');
                    console.log('[AI] Using URL:', AI_URL);
                    console.log('[AI] Token present:', !!AI_TOKEN);
                    const startResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                        method: 'POST', headers: { 'Authorization': `Bearer ${AI_TOKEN}` }, body: fd
                    });
                    if (!startResp.ok) throw new Error('start failed');
                    const startData = await startResp.json();
                    console.log('[AI] Start response:', startData);
                    const taskId = (startData && startData.task && (startData.task.id || startData.task.uuid)) || startData.id || startData.task_id || startData.uuid;
                    if (!taskId) throw new Error('no id');

                    let done = false; let last;
                    while (!done) {
                        await new Promise(r => setTimeout(r, 5000));
                        console.log(`[AI] Polling task ${taskId} ...`);
                        const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                            headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                        });
                        if (pollResp.status === 404) {
                            try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {}
                            return;
                        }
                        if (!pollResp.ok) throw new Error('poll failed');
                        last = await pollResp.json();
                        console.log('[AI] Poll data:', last);
                        const task = last && last.task ? last.task : null;
                        if (!task) continue;
                        if (task.response == null) {
                            if (task.error && /processing/i.test(task.error)) { console.log('[AI] Task pending (processing):', task.error); continue; }
                            if (task.error == null) { console.log('[AI] Task pending (no response yet)'); continue; }
                        }
                        if (task.error && !/processing/i.test(task.error)) { console.error('[AI] Task failed:', task.error); alert(task.error); return; }
                        if (task.response != null) { done = true; }
                    }

                    $('#aiResultPrompt').text(prompt);
                    const content = (last && last.task && last.task.response != null) ? last.task.response : last;
                    try { $('#aiResultData').text(typeof content === 'string' ? content : JSON.stringify(content, null, 2)); } catch { $('#aiResultData').text(String(content)); }
                    const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
                    resultModal.show();
                } catch (err) {
                    console.error('[AI] Unexpected error:', err);
                    alert('{{ __('An error occurred') }}');
                } finally {
                    showLoading(false);
                }
            }

            // Default prompt + reset
            const defaultPromptQI = {!! json_encode(__('Analiza las incidencias de calidad mostradas, buscando motivos, líneas afectadas y tendencias en los últimos días.')) !!};
            $('#aiPromptModal').on('shown.bs.modal', function(){
                const $ta = $('#aiPrompt');
                if (!$ta.val()) $ta.val(defaultPromptQI);
                $ta.trigger('focus');
            });
            $('#btn-ai-reset').on('click', function(){
                $('#aiPrompt').val(defaultPromptQI);
            });

            // Send from modal
            $('#btn-ai-send').on('click', function(){
                const prompt = ($('#aiPrompt').val() || '').trim() || defaultPromptQI;
                startAiTask(prompt);
            });
        });
    </script>
    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i>@lang('Análisis IA')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">@lang('¿Qué necesitas analizar?')</label>
                    <textarea class="form-control" id="aiPrompt" rows="4" placeholder="@lang('Describe qué análisis quieres sobre las incidencias mostradas')"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">@lang('Limpiar prompt por defecto')</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send">@lang('Enviar a IA')</button>
                </div>
            </div>
        </div>
    </div>
    <!-- AI Result Modal -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Resultado IA')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted"><strong>@lang('Prompt'):</strong> <span id="aiResultPrompt"></span></p>
                    <pre id="aiResultData" class="bg-light p-3 rounded" style="white-space: pre-wrap;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
@endpush
