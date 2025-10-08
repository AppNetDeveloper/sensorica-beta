@extends('layouts.admin')

@section('title', __('Production Order Incidents'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Production Order Incidents') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">@lang('Production Order Incidents') - {{ $customer->name }}</h5>
                        <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
                            @if(!empty(config('services.ai.url')) && !empty(config('services.ai.token')))
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
                    <form method="GET" class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha desde')</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha hasta')</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Línea de producción')</label>
                            <select name="line_id" class="form-select form-select-sm">
                                <option value="">@lang('Todas')</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}" @selected(($filters['line_id'] ?? '') == $line->id)>{{ $line->name ?? ('#'.$line->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Trabajador')</label>
                            <select name="operator_id" class="form-select form-select-sm">
                                <option value="">@lang('Todos')</option>
                                @foreach($operators as $u)
                                    <option value="{{ $u->id }}" @selected(($filters['operator_id'] ?? '') == $u->id)>{{ $u->name ?? ('#'.$u->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-filter me-1"></i>@lang('Aplicar filtros')
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="incidents-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('ORDER ID')</th>
                                    <th class="text-uppercase">@lang('REASON')</th>
                                    <th class="text-uppercase">@lang('Incident Status')</th>
                                    <th class="text-uppercase">@lang('INFO')</th>
                                    <th class="text-uppercase">@lang('Trabajador')</th>
                                    <th class="text-uppercase">@lang('Fecha de creación')</th>
                                    <th class="text-uppercase">@lang('ACTIONS')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($incidents as $index => $incident)
                                    @php
                                        $status = optional($incident->productionOrder)->status;
                                        $rowClass = '';
                                        if ($status === 3) { $rowClass = 'table-danger'; }
                                        elseif ($status === 2) { $rowClass = 'table-success'; }
                                        elseif ($status === 1) { $rowClass = 'table-warning'; }
                                        elseif ($status === 0) { $rowClass = 'table-light'; }
                                    @endphp
                                    <tr class="{{ $rowClass }}"
                                        data-line-id="{{ optional($incident->productionOrder)->production_line_id }}"
                                        data-operator-id="{{ optional($incident->createdBy)->id }}"
                                        data-created-at="{{ optional($incident->created_at)->format('Y-m-d') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            #{{ $incident->productionOrder->order_id }}
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($incident->reason, 50) }}</td>
                                        <td>
                                            @if($incident->productionOrder->status == 3)
                                                <span class="badge bg-danger">@lang('Incidencia activa')</span>
                                            @else
                                                <span class="badge bg-secondary">@lang('Incidencia finalizada')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(optional($incident->productionOrder)->productionLine)
                                                <span class="badge bg-primary me-1" title="@lang('Línea')">{{ $incident->productionOrder->productionLine->name ?? ('L#'.optional($incident->productionOrder->productionLine)->id) }}</span>
                                            @endif
                                            @if($incident->createdBy)
                                                <span class="badge bg-secondary" title="@lang('Trabajador')"><i class="fas fa-user"></i> {{ $incident->createdBy->name }}</span>
                                            @else
                                                <span class="badge bg-secondary" title="@lang('Trabajador')"><i class="fas fa-user"></i> Sistema</span>
                                            @endif
                                        </td>
                                        <td>{{ $incident->createdBy ? $incident->createdBy->name : 'Sistema' }}</td>
                                        <td>{{ $incident->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('customers.production-order-incidents.show', [$customer->id, $incident->id]) }}" 
                                                   class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="@lang('View')">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('delete', $customer)
                                                <form action="{{ route('customers.production-order-incidents.destroy', [$customer->id, $incident->id]) }}" 
                                                      method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            data-bs-toggle="tooltip" title="@lang('Delete')" 
                                                            onclick="return confirm('@lang('Are you sure you want to delete this incident?')')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3 mx-0">
            <div class="col-12 px-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <h6 class="text-uppercase text-muted fw-semibold mb-3">
                            <i class="fas fa-info-circle me-1"></i>@lang('Leyenda de colores Estado de pedido')
                        </h6>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <span class="legend-swatch bg-danger me-2"></span>
                                <span class="fw-semibold">@lang('Incidencia (status 3)')</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="legend-swatch bg-success me-2"></span>
                                <span class="fw-semibold">@lang('Finalizadas (status 2)')</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="legend-swatch bg-warning me-2"></span>
                                <span class="fw-semibold">@lang('En curso (status 1)')</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="legend-swatch bg-light border me-2"></span>
                                <span class="fw-semibold">@lang('Pendientes (status 0)')</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3 mx-0">
            <div class="col-12 px-0">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <h6 class="text-uppercase text-muted fw-semibold mb-3">
                            <i class="fas fa-info-circle me-1"></i>@lang('Leyenda de colores Estado de incidencia')
                        </h6>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="d-flex align-items-center">
                                <span class="legend-swatch bg-danger me-2"></span>
                                <span class="fw-semibold">@lang('Incident Status') &mdash; @lang('Incidencia activa')</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="legend-swatch bg-secondary me-2"></span>
                                <span class="fw-semibold">@lang('Incident Status') &mdash; @lang('Incidencia finalizada')</span>
                            </div>
                        </div>
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
            const table = $('#incidents-table').DataTable({
                responsive: true,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
                order: [[6, 'desc']],
                dom: 'Bfrt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-3 info-pagination-container"ip>>',
                buttons: [
                    { extend: 'csv', text: 'CSV', className: 'btn btn-sm btn-outline-secondary' },
                    { extend: 'excel', text: 'Excel', className: 'btn btn-sm btn-outline-success' },
                    { extend: 'print', text: 'Imprimir', className: 'btn btn-sm btn-outline-primary' },
                ]
            });

            // === AI integration (like Maintenances/QC) ===
            const AI_URL = @json(config('services.ai.url'));
            const AI_TOKEN = @json(config('services.ai.token'));

            function collectCurrentRows() {
                const dt = $('#incidents-table').DataTable();
                const nodes = dt.rows({ page: 'current' }).nodes();
                const out = [];
                dt.rows({ page: 'current' }).every(function(rowIdx){
                    const tr = $(nodes[rowIdx]);
                    const cells = $(this.node()).find('td');
                    out.push({
                        index: $(cells[0]).text().trim(),
                        order_id: $(cells[1]).text().trim(),
                        reason: $(cells[2]).text().trim(),
                        status: $(cells[3]).text().trim(),
                        info: $(cells[4]).text().trim(),
                        operator: $(cells[5]).text().trim(),
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

            function showLoading(show) { $('#btn-ai-send').prop('disabled', !!show).toggleClass('disabled', !!show); }

            async function startAiTask(prompt) {
                if (!AI_URL || !AI_TOKEN) { alert('AI config missing'); return; }
                showLoading(true);
                try {
                    const payload = collectCurrentRows();
                    console.log('[AI][PO Incidents] rows:', payload.rows.length, 'filters:', payload.filters);
                    let combinedPrompt;
                    try {
                        combinedPrompt = `${prompt}\n\n=== Datos para analizar (JSON) ===\n${JSON.stringify(payload, null, 2)}`;
                    } catch (e) {
                        combinedPrompt = `${prompt}\n\n=== Datos para analizar (JSON) ===\n[Error serializando datos]`;
                    }
                    const fd = new FormData();
                    fd.append('prompt', combinedPrompt);

                    const startResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                        method: 'POST', headers: { 'Authorization': `Bearer ${AI_TOKEN}` }, body: fd
                    });
                    if (!startResp.ok) throw new Error('start failed');
                    const startData = await startResp.json();
                    const taskId = (startData && startData.task && (startData.task.id || startData.task.uuid)) || startData.id || startData.task_id || startData.uuid;
                    if (!taskId) throw new Error('no id');

                    let done = false; let last;
                    while (!done) {
                        await new Promise(r => setTimeout(r, 5000));
                        const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                            headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                        });
                        if (pollResp.status === 404) { try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {} return; }
                        if (!pollResp.ok) throw new Error('poll failed');
                        last = await pollResp.json();
                        const task = last && last.task ? last.task : null;
                        if (!task) continue;
                        if (task.response == null) {
                            if (task.error && /processing/i.test(task.error)) { continue; }
                            if (task.error == null) { continue; }
                        }
                        if (task.error && !/processing/i.test(task.error)) { alert(task.error); return; }
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

            const defaultPromptPOI = {!! json_encode(__('Analiza estas incidencias de producción y resume: 1) causas recurrentes, 2) líneas/centros más afectados, 3) trabajadores implicados y 4) acciones recomendadas para reducir incidencias.')) !!};
            $('#aiPromptModal').on('shown.bs.modal', function(){
                const $ta = $('#aiPrompt'); if (!$ta.val()) $ta.val(defaultPromptPOI); $ta.trigger('focus');
            });
            $('#btn-ai-reset').on('click', function(){ $('#aiPrompt').val(defaultPromptPOI); });
            $('#btn-ai-send').on('click', function(){ const prompt = ($('#aiPrompt').val() || '').trim() || defaultPromptPOI; startAiTask(prompt); });
        });
    </script>

    <style>
        .legend-swatch {
            width: 18px;
            height: 18px;
            border-radius: 3px;
            display: inline-block;
        }

        .info-pagination-container {
            gap: 1rem;
        }

        .info-pagination-container .dataTables_info {
            margin-bottom: 0;
            white-space: nowrap;
        }

        .info-pagination-container .dataTables_paginate {
            margin-left: auto;
            margin-bottom: 0;
        }
    </style>

    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
