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
                        <h5 class="mb-0 text-white">@lang('Incidencias QC') - {{ $customer->name }}</h5>
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
                            <input type="date" name="date_from" id="filter-date-from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Fecha hasta')</label>
                            <input type="date" name="date_to" id="filter-date-to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Línea de producción')</label>
                            <select name="line_id" id="filter-line" class="form-select form-select-sm">
                                <option value="">@lang('Todas')</option>
                                @foreach($lines as $line)
                                    <option value="{{ $line->id }}" @selected(($filters['line_id'] ?? '') == $line->id)>{{ $line->name ?? ('#'.$line->id) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label mb-1">@lang('Operador')</label>
                            <select name="operator_id" id="filter-operator" class="form-select form-select-sm">
                                <option value="">@lang('Todos')</option>
                                @foreach($operators as $op)
                                    <option value="{{ $op->id }}" @selected(($filters['operator_id'] ?? '') == $op->id)>{{ $op->name ?? ('#'.$op->id) }}</option>
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
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <style>
        /* ===== ESTILOS PARA MODAL DE RESULTADOS IA ===== */
        /* Contenido del resultado */
        .ai-result-content {
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            transition: font-size 0.2s ease;
            max-height: 65vh;
            overflow-y: auto;
            padding: 1rem;
            background: white;
            border-radius: 8px;
        }

        /* Tablas Markdown con estilos Bootstrap */
        .ai-result-content table {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 6px;
            overflow: hidden;
        }

        .ai-result-content table thead th {
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            border: none;
            text-align: left;
            white-space: nowrap;
        }

        .ai-result-content table tbody td {
            padding: 0.65rem 0.75rem;
            border-top: 1px solid #dee2e6;
            vertical-align: top;
        }

        .ai-result-content table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .ai-result-content table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.15s ease-in-out;
        }

        /* Encabezados */
        .ai-result-content h1, .ai-result-content h2, .ai-result-content h3,
        .ai-result-content h4, .ai-result-content h5, .ai-result-content h6 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #212529;
        }

        .ai-result-content h1 { font-size: 1.8rem; border-bottom: 2px solid #0d6efd; padding-bottom: 0.3rem; }
        .ai-result-content h2 { font-size: 1.5rem; color: #0d6efd; }
        .ai-result-content h3 { font-size: 1.3rem; color: #495057; }
        .ai-result-content h4 { font-size: 1.1rem; }
        .ai-result-content h5 { font-size: 1rem; }

        /* Listas */
        .ai-result-content ul, .ai-result-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .ai-result-content li {
            margin-bottom: 0.35rem;
        }

        /* Párrafos y separadores */
        .ai-result-content p {
            margin-bottom: 1rem;
        }

        .ai-result-content hr {
            margin: 1.5rem 0;
            border: none;
            border-top: 2px solid #e9ecef;
        }

        /* Código */
        .ai-result-content code {
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #d63384;
        }

        .ai-result-content pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            border-left: 4px solid #0d6efd;
        }

        .ai-result-content pre code {
            background: none;
            padding: 0;
            color: #212529;
        }

        /* Blockquotes */
        .ai-result-content blockquote {
            padding: 0.5rem 1rem;
            margin: 1rem 0;
            border-left: 4px solid #0dcaf0;
            background-color: #f8f9fa;
            font-style: italic;
        }

        /* Enlaces */
        .ai-result-content a {
            color: #0d6efd;
            text-decoration: none;
        }

        .ai-result-content a:hover {
            text-decoration: underline;
        }

        /* Barra de progreso de scroll */
        .scroll-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
            width: 0%;
            transition: width 0.1s ease;
            z-index: 1050;
            border-radius: 0 2px 2px 0;
        }

        /* Botón volver arriba */
        #btnScrollTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border-radius: 50% !important;
            background: #0d6efd;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1055;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #btnScrollTop.show {
            opacity: 1;
            visibility: visible;
        }

        #btnScrollTop:hover {
            background: #0b5ed7;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
        }

        /* Controles de fuente */
        .font-controls .btn {
            font-family: monospace;
            font-weight: bold;
            min-width: 36px;
        }

        /* Modal en fullscreen personalizado */
        .modal-fullscreen-custom {
            max-width: 100% !important;
            width: 100% !important;
            height: 100vh;
            margin: 0 !important;
        }

        .modal-fullscreen-custom .modal-content {
            height: 100vh;
            border-radius: 0 !important;
        }

        .modal-fullscreen-custom .ai-result-content {
            max-height: calc(100vh - 200px);
        }

        /* Tabs personalizados */
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-radius: 6px 6px 0 0;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            background-color: #f8f9fa;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            background-color: white;
            border-color: #dee2e6 #dee2e6 #fff;
            color: #0d6efd;
        }

        /* Toolbar de acciones */
        .ai-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .ai-toolbar .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Toast personalizado */
        .copy-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #198754;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Metadatos del análisis */
        .ai-metadata {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .ai-metadata i {
            color: #0d6efd;
        }

        /* Responsive ajustes */
        @media (max-width: 768px) {
            .modal-dialog[style*="80%"] {
                max-width: 95% !important;
                width: 95% !important;
            }

            .ai-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .ai-toolbar .btn-group {
                width: 100%;
                justify-content: center;
            }

            .ai-result-content {
                font-size: 0.9rem;
            }

            .ai-result-content table {
                font-size: 0.85rem;
            }

            #btnScrollTop {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
            }
        }
    </style>
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

    <!-- Librerías para parsing de Markdown y seguridad -->
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
    <script>
        // Configurar marked.js para mejor compatibilidad con Markdown
        if (window.marked) {
            marked.setOptions({
                breaks: true,        // Convertir saltos de línea en <br>
                gfm: true,          // GitHub Flavored Markdown
                headerIds: true,    // Generar IDs para encabezados
                mangle: false,      // No modificar emails
                sanitize: false     // No sanitizar (lo haremos con DOMPurify)
            });
        }
    </script>

    <script>
        $(document).ready(function() {
            // Enable Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });

            const table = $('#qc-incidents-table').DataTable({
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

            // === AI integration (same behavior as maintenances) ===
            const AI_URL = @json(config('services.ai.url'));
            const AI_TOKEN = @json(config('services.ai.token'));

            function collectCurrentRows() {
                const table = $('#qc-incidents-table').DataTable();
                const rows = table.rows({ search: 'applied' }).nodes();

                // Encabezado CSV
                let csv = 'Index,Original_Order,Original_Order_QC,Process,Info,Reason,Created_At,Line_ID,Operator_ID\n';
                let count = 0;
                const maxRows = 200;

                table.rows({ search: 'applied' }).every(function(rowIdx){
                    if (count >= maxRows) return false;

                    const tr = $(rows[rowIdx]);
                    const cells = $(this.node()).find('td');

                    // Limpiar valores para CSV (escapar comillas y saltos de línea)
                    const cleanCsvValue = (val) => {
                        if (!val) return '';
                        val = val.toString().trim(); // Trim inicial
                        val = val.replace(/\s+/g, ' '); // Reemplazar múltiples espacios con uno solo
                        val = val.replace(/"/g, '""'); // Escapar comillas dobles
                        val = val.replace(/[\r\n]+/g, ' '); // Reemplazar saltos de línea con espacio
                        // Si contiene comas, comillas o saltos de línea, envolver en comillas
                        if (val.includes(',') || val.includes('"') || val.includes('\n')) {
                            return `"${val}"`;
                        }
                        return val;
                    };

                    const index = cleanCsvValue($(cells[0]).text().trim());
                    const original_order = cleanCsvValue($(cells[1]).text().trim());
                    const original_order_qc = cleanCsvValue($(cells[2]).text().trim());
                    const process = cleanCsvValue($(cells[3]).text().trim());
                    const info = cleanCsvValue($(cells[4]).text().trim());
                    const reason = cleanCsvValue($(cells[5]).text().trim());
                    const created_at = cleanCsvValue($(cells[6]).text().trim());
                    const line_id = cleanCsvValue((tr.data('line-id') || '').toString());
                    const operator_id = cleanCsvValue((tr.data('operator-id') || '').toString());

                    csv += `${index},${original_order},${original_order_qc},${process},${info},${reason},${created_at},${line_id},${operator_id}\n`;
                    count++;
                });

                const filters = {
                    line: $('#filter-line').val() || '',
                    operator: $('#filter-operator').val() || '',
                    date_from: $('#filter-date-from').val() || '',
                    date_to: $('#filter-date-to').val() || ''
                };

                console.log('[AI][QC] CSV generado con', count, 'filas');

                return { csv, filters, rowCount: count };
            }

            function showLoading(show) {
                $('#btn-ai-send').prop('disabled', !!show).toggleClass('disabled', !!show);
                $('#aiLoadingIndicator').toggleClass('d-none', !show);
            }

            async function startAiTask(prompt) {
                if (!AI_URL || !AI_TOKEN) { alert('AI config missing'); return; }
                showLoading(true);
                try {
                    // El prompt ya contiene todo (se generó al abrir el modal)
                    console.log('[AI][QC Incidents] Enviando prompt completo');
                    console.log('[AI] Prompt length:', prompt.length);
                    console.log('[AI] Prompt preview:', prompt.substring(0, 500));
                    const fd = new FormData();
                    fd.append('prompt', prompt);
                    fd.append('agent', 'data_analysis');

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

                    // Mostrar resultado
                    const content = (last && last.task && last.task.response != null) ? last.task.response : last;

                    let rawText;
                    try {
                        rawText = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
                    } catch {
                        rawText = String(content);
                    }

                    // Establecer metadatos del análisis
                    const now = new Date();
                    const timestamp = now.toLocaleString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    $('#aiResultTimestamp').text(timestamp);

                    // Calcular estadísticas del texto
                    const wordCount = (rawText || '').trim().split(/\s+/).filter(w => w.length > 0).length;
                    const lineCount = (rawText || '').split('\n').length;
                    const charCount = (rawText || '').length;
                    $('#aiResultStats').text(`${wordCount} palabras, ${lineCount} líneas, ${charCount} caracteres`);

                    // Establecer texto plano
                    $('#aiResultText').text(rawText || '');

                    // Convertir Markdown a HTML con marked.js
                    const htmlTarget = $('#aiResultHtml');
                    if (window.marked && window.DOMPurify) {
                        try {
                            console.log('[AI] Parseando Markdown con marked.js...');

                            // Convertir Markdown a HTML
                            let htmlContent = marked.parse(rawText || '');
                            console.log('[AI] Markdown parseado correctamente');

                            // Agregar clases de Bootstrap a las tablas
                            htmlContent = htmlContent.replace(/<table>/g, '<table class="table table-striped table-bordered table-hover">');

                            // Sanitizar el HTML con DOMPurify
                            const sanitized = DOMPurify.sanitize(htmlContent, {
                                ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'style', 'src', 'alt', 'title', 'colspan', 'rowspan'],
                                ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                                              'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
                                              'a', 'code', 'pre', 'blockquote', 'hr', 'span', 'div']
                            });

                            htmlTarget.html(sanitized);
                            console.log('[AI] HTML sanitizado e inyectado en el DOM');
                        } catch (err) {
                            console.error('[AI] Error al parsear Markdown:', err);
                            htmlTarget.html('<p class="text-danger">Error al procesar el contenido Markdown.</p>');
                        }
                    } else {
                        console.warn('[AI] marked.js o DOMPurify no disponible, mostrando texto plano');
                        htmlTarget.text(rawText || '');
                    }

                    // Mostrar la tab de "Vista Formateada" por defecto
                    const renderedTabTrigger = document.getElementById('ai-tab-rendered');
                    if (renderedTabTrigger && bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(renderedTabTrigger).show();
                    }

                    // Inicializar funcionalidades del modal (copiar, descargar, imprimir, etc.)
                    initAIResultModalFeatures(rawText, prompt);

                    // Mostrar modal
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
            const defaultPromptQI = `Eres un experto en control de calidad (QC) y metodologías Six Sigma/Lean. Analiza las incidencias de calidad proporcionadas y genera un informe detallado para mejorar los procesos.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas), donde cada fila representa una incidencia de calidad:
- Index: Número de orden en la tabla
- Original_Order: Referencia de la orden original
- Original_Order_QC: ID del registro QC
- Process: Proceso donde ocurrió la incidencia
- Info: Información detallada de la incidencia
- Reason: Motivo/razón de la incidencia
- Created_At: Fecha y hora de detección
- Line_ID: ID de la línea de producción
- Operator_ID: ID del operador involucrado

IMPORTANTE: Procesa TODAS las filas del CSV para obtener análisis completo. Ignora filas con valores vacíos.

ANÁLISIS REQUERIDO:

1. **Resumen Ejecutivo**:
   - Total de incidencias QC analizadas
   - Tasa de defectos (incidencias / total órdenes si disponible)
   - Distribución temporal: tendencia diaria/semanal

2. **Agrupación por Línea de Producción**:
   - Top 5 líneas con más incidencias QC
   - Para cada línea: cantidad, % del total, procesos más problemáticos
   - Líneas críticas que requieren atención inmediata

3. **Agrupación por Operador**:
   - Operadores con mayor número de incidencias reportadas
   - Distribución: ¿hay concentración o está disperso?
   - Identificar si hay necesidad de capacitación específica

4. **Análisis de Causas Raíz (Top 5)**:
   - Razones más frecuentes de incidencias QC
   - Para cada razón: cantidad, % del total, procesos afectados
   - Clasificación por tipo: material, proceso, mano de obra, máquina, método

5. **Análisis de Procesos**:
   - Procesos con mayor tasa de incidencias
   - Identificar cuellos de botella en calidad
   - Procesos que requieren revisión urgente

6. **Acciones Correctivas Priorizadas**:
   - 5 acciones concretas con mayor impacto
   - Para cada acción: impacto estimado (% reducción esperada), urgencia (Alta/Media/Baja), recursos necesarios
   - Quick wins: mejoras rápidas y de alto impacto

7. **KPIs Recomendados**:
   - Métricas clave para monitorear calidad
   - Objetivos sugeridos basados en los datos actuales

FORMATO DE SALIDA:
Estructura tu respuesta con:
- Tablas comparativas en formato Markdown
- Números y porcentajes concretos
- Priorización clara de acciones
- Métricas cuantificables

Utiliza encabezados, listas, negritas y tablas para mejor legibilidad.`;
            $('#aiPromptModal').on('shown.bs.modal', function(){
                const $ta = $('#aiPrompt');

                // Si el textarea está vacío, generar el prompt completo con CSV
                if (!$ta.val()) {
                    const payload = collectCurrentRows();
                    const fullPrompt = `${defaultPromptQI}

=== Datos para analizar (CSV) ===
${payload.csv}

=== Filtros Aplicados ===
Línea: ${payload.filters.line || 'Todas'}
Operador: ${payload.filters.operator || 'Todos'}
Fecha desde: ${payload.filters.date_from || 'Sin límite'}
Fecha hasta: ${payload.filters.date_to || 'Sin límite'}

Total de incidencias: ${payload.rowCount}`;

                    $ta.val(fullPrompt);
                    console.log('[AI][QC] Prompt completo generado con', payload.rowCount, 'filas de CSV');
                }
                $ta.trigger('focus');
            });

            $('#btn-ai-reset').on('click', function(){
                // Al resetear, regenerar con datos actuales
                const payload = collectCurrentRows();
                const fullPrompt = `${defaultPromptQI}

=== Datos para analizar (CSV) ===
${payload.csv}

=== Filtros Aplicados ===
Línea: ${payload.filters.line || 'Todas'}
Operador: ${payload.filters.operator || 'Todos'}
Fecha desde: ${payload.filters.date_from || 'Sin límite'}
Fecha hasta: ${payload.filters.date_to || 'Sin límite'}

Total de incidencias: ${payload.rowCount}`;

                $('#aiPrompt').val(fullPrompt);
            });

            /**
             * Inicializa las funcionalidades interactivas del modal de resultados IA
             * @param {string} rawText - Texto sin procesar del análisis
             * @param {string} analysisType - Tipo de análisis realizado
             */
            function initAIResultModalFeatures(rawText, analysisType) {
                console.log('[AI Modal] Inicializando funcionalidades interactivas...');

                let currentFontSize = 100;

                $('#btnCopyResult').off('click').on('click', function() {
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(rawText).then(() => {
                            showToast('✓ Copiado al portapapeles', 'success');
                        }).catch(() => showToast('✗ Error al copiar', 'danger'));
                    } else {
                        const textarea = document.createElement('textarea');
                        textarea.value = rawText;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);
                        textarea.select();
                        try {
                            document.execCommand('copy');
                            showToast('✓ Copiado al portapapeles', 'success');
                        } catch (err) {
                            showToast('✗ Error al copiar', 'danger');
                        }
                        document.body.removeChild(textarea);
                    }
                });

                $('#btnDownloadResult').off('click').on('click', function() {
                    try {
                        const timestamp = new Date().toISOString().replace(/[:]/g, '-').split('.')[0];
                        const filename = `analisis-qc-${timestamp}.md`;
                        const blob = new Blob([rawText], { type: 'text/markdown;charset=utf-8' });
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                        showToast('✓ Archivo descargado', 'success');
                    } catch (err) {
                        showToast('✗ Error al descargar', 'danger');
                    }
                });

                $('#btnPrintResult').off('click').on('click', function() {
                    try {
                        const printWindow = window.open('', '_blank');
                        const htmlContent = $('#aiResultHtml').html();
                        printWindow.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Análisis QC - ${analysisType}</title><style>body{font-family:Arial,sans-serif;line-height:1.6;padding:20px;max-width:1200px;margin:0 auto;}h1,h2,h3,h4,h5,h6{margin-top:1.5rem;margin-bottom:0.75rem;color:#212529;}table{width:100%;border-collapse:collapse;margin-bottom:1.5rem;font-size:0.9rem;}table thead th{background-color:#0d6efd;color:white;padding:0.75rem;border:1px solid #0d6efd;text-align:left;}table tbody td{padding:0.65rem;border:1px solid #dee2e6;}table tbody tr:nth-child(odd){background-color:#f8f9fa;}pre{background-color:#f8f9fa;padding:1rem;border-radius:4px;overflow-x:auto;}code{background-color:#f8f9fa;padding:0.2rem 0.4rem;border-radius:3px;font-family:'Courier New',monospace;}@media print{body{padding:10px;}table{page-break-inside:auto;}tr{page-break-inside:avoid;page-break-after:auto;}}</style></head><body><h1>Análisis de Incidencias QC</h1><p><strong>Generado:</strong> ${new Date().toLocaleString('es-ES')}</p><hr>${htmlContent}</body></html>`);
                        printWindow.document.close();
                        printWindow.focus();
                        setTimeout(() => printWindow.print(), 250);
                    } catch (err) {
                        showToast('✗ Error al imprimir', 'danger');
                    }
                });

                $('#btnFullscreen').off('click').on('click', function() {
                    const dialog = $('#aiResultModalDialog');
                    const icon = $(this).find('i');
                    if (dialog.hasClass('modal-fullscreen-custom')) {
                        dialog.removeClass('modal-fullscreen-custom');
                        icon.removeClass('fa-compress').addClass('fa-expand');
                    } else {
                        dialog.addClass('modal-fullscreen-custom');
                        icon.removeClass('fa-expand').addClass('fa-compress');
                    }
                });

                function updateFontSize() {
                    $('.ai-result-content').css('font-size', currentFontSize + '%');
                }

                $('#btnFontDecrease').off('click').on('click', function() {
                    if (currentFontSize > 70) {
                        currentFontSize -= 10;
                        updateFontSize();
                        showToast(`Tamaño: ${currentFontSize}%`, 'info');
                    }
                });

                $('#btnFontReset').off('click').on('click', function() {
                    currentFontSize = 100;
                    updateFontSize();
                    showToast('Tamaño: 100% (normal)', 'info');
                });

                $('#btnFontIncrease').off('click').on('click', function() {
                    if (currentFontSize < 150) {
                        currentFontSize += 10;
                        updateFontSize();
                        showToast(`Tamaño: ${currentFontSize}%`, 'info');
                    }
                });

                const scrollContainers = $('.ai-result-content, #aiResultText');
                const btnScrollTop = $('#btnScrollTop');

                scrollContainers.off('scroll').on('scroll', function() {
                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight - $(this).outerHeight();
                    const scrollPercent = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;
                    $('#aiScrollProgress').css('width', scrollPercent + '%');
                    if (scrollTop > 300) {
                        btnScrollTop.addClass('show');
                    } else {
                        btnScrollTop.removeClass('show');
                    }
                });

                btnScrollTop.off('click').on('click', function() {
                    scrollContainers.animate({ scrollTop: 0 }, 400);
                });

                $('#aiResultModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    currentFontSize = 100;
                    $('.ai-result-content').css('font-size', '100%');
                    $('#aiResultModalDialog').removeClass('modal-fullscreen-custom');
                    $('#btnFullscreen').find('i').removeClass('fa-compress').addClass('fa-expand');
                    btnScrollTop.removeClass('show');
                    $('#aiScrollProgress').css('width', '0%');
                    $('#btnCopyResult, #btnDownloadResult, #btnPrintResult, #btnFullscreen').off('click');
                    $('#btnFontDecrease, #btnFontReset, #btnFontIncrease').off('click');
                    scrollContainers.off('scroll');
                    btnScrollTop.off('click');
                });
            }

            function showToast(message, type = 'success') {
                const bgColor = type === 'success' ? '#198754' : type === 'danger' ? '#dc3545' : '#0dcaf0';
                const toast = $(`<div class="copy-toast" style="background: ${bgColor};"><i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i><span>${message}</span></div>`);
                $('body').append(toast);
                setTimeout(() => toast.remove(), 3000);
            }

            // Send from modal
            $('#btn-ai-send').on('click', function(){
                const prompt = ($('#aiPrompt').val() || '').trim() || defaultPromptQI;
                startAiTask(prompt);
            });
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
                    <div id="aiLoadingIndicator" class="d-none me-auto d-flex align-items-center text-primary small">
                        <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                        <span>{{ __('Analizando con IA...') }}</span>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">@lang('Limpiar prompt por defecto')</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send">@lang('Enviar a IA')</button>
                </div>
            </div>
        </div>
    </div>
    <!-- AI Result Modal (Mejorado) -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" id="aiResultModalDialog" style="max-width: 80%; width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-1">@lang('Resultado IA')</h5>
                        <small class="text-muted ai-metadata">
                            <i class="fas fa-clock me-1"></i><span id="aiResultTimestamp"></span>
                            <span class="mx-2">|</span>
                            <i class="fas fa-align-left me-1"></i><span id="aiResultStats"></span>
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body position-relative">
                    <!-- Barra de progreso de scroll -->
                    <div class="scroll-progress-bar" id="aiScrollProgress"></div>

                    <!-- Barra de herramientas -->
                    <div class="ai-toolbar">
                        <!-- Control de tamaño de fuente -->
                        <div class="btn-group btn-group-sm font-controls" role="group" aria-label="Controles de fuente">
                            <button type="button" class="btn btn-outline-secondary" id="btnFontDecrease" title="Reducir tamaño">
                                <i class="fas fa-minus"></i> A-
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFontReset" title="Tamaño normal">
                                A
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFontIncrease" title="Aumentar tamaño">
                                <i class="fas fa-plus"></i> A+
                            </button>
                        </div>

                        <!-- Botones de acción -->
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                            <button type="button" class="btn btn-outline-primary" id="btnCopyResult" title="Copiar al portapapeles">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <button type="button" class="btn btn-outline-success" id="btnDownloadResult" title="Descargar como archivo">
                                <i class="fas fa-download"></i> Descargar
                            </button>
                            <button type="button" class="btn btn-outline-info" id="btnPrintResult" title="Imprimir o guardar como PDF">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFullscreen" title="Pantalla completa">
                                <i class="fas fa-expand"></i> Pantalla completa
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="aiResultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ai-tab-rendered" data-bs-toggle="tab" data-bs-target="#aiResultRendered" type="button" role="tab" aria-controls="aiResultRendered" aria-selected="true">
                                <i class="fas fa-eye me-1"></i>Vista Formateada
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab-raw" data-bs-toggle="tab" data-bs-target="#aiResultRaw" type="button" role="tab" aria-controls="aiResultRaw" aria-selected="false">
                                <i class="fas fa-file-alt me-1"></i>Texto Plano
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de las tabs -->
                    <div class="tab-content" id="aiResultTabContent">
                        <!-- Tab: Vista Formateada (Markdown parseado) -->
                        <div class="tab-pane fade show active" id="aiResultRendered" role="tabpanel" aria-labelledby="ai-tab-rendered">
                            <div id="aiResultHtml" class="ai-result-content"></div>
                        </div>

                        <!-- Tab: Texto Plano -->
                        <div class="tab-pane fade" id="aiResultRaw" role="tabpanel" aria-labelledby="ai-tab-raw">
                            <pre id="aiResultText" class="bg-light p-3 rounded" style="white-space: pre-wrap; min-height: 200px; overflow: auto;"></pre>
                        </div>
                    </div>

                    <!-- Botón flotante "Volver arriba" -->
                    <button type="button" id="btnScrollTop" class="btn btn-primary" title="Volver arriba">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
@endpush
