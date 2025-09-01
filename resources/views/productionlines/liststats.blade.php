@extends('layouts.admin')

@section('title', 'Estadísticas de Líneas de Producción')

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            width: 100%;
        }
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8f9fa;
        }
        .table-responsive {
            border-radius: 8px;
            background: white;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        .table > :not(:last-child) > :last-child > * {
            border-bottom-color: #dee2e6;
        }
        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
        }
        .progress {
            height: 20px;
            border-radius: 4px;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
        }
        .btn {
            border-radius: 6px;
            font-weight: 500;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 4px;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 4px !important;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0d6efd !important;
            color: white !important;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #0b5ed7 !important;
            color: white !important;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05) !important;
        }
        
        /* Mejoras de espaciado para DataTable */
        table.dataTable {
            border-spacing: 0 8px !important;
            border-collapse: separate !important;
            margin-top: 15px !important;
            margin-left: 10px !important;
            margin-right: 10px !important;
            width: calc(100% - 20px) !important;
        }
        
        .dataTables_wrapper {
            padding: 15px !important;
        }
        
        table.dataTable thead th {
            padding: 12px 10px;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        table.dataTable tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4 px-1">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-table me-2 text-primary"></i>
                    Datos de Producción
                </h6>
            </div>
            <div class="card-body py-2 px-3">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Líneas de Producción</label>
                        <select id="modbusSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Empleado</label>
                        <select id="operatorSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                            <!-- Opciones dinámicas de operarios -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Artículo</label>
                        <select class="form-select" disabled>
                            <option>Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="datetime-local" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Fin</label>
                        <input type="datetime-local" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary" id="fetchData" title="Buscar">
                            <i class="fas fa-search"></i>
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetFilters" title="Restablecer filtros">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-success border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Promedio OEE</h6>
                                <h4 class="mb-0" id="avgOEE">0%</h4>
                            </div>
                            <div class="bg-success bg-opacity-10 p-2 rounded">
                                <i class="fas fa-chart-line text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-primary border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Duración</h6>
                                <h4 class="mb-0" id="totalDuration">00:00:00</h4>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-2 rounded">
                                <i class="fas fa-clock text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Teórico Total</h6>
                                <h4 class="mb-0" id="totalTheoretical">00:00:00</h4>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded">
                                <i class="fas fa-balance-scale text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-secondary border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Preparación</h6>
                                <h4 class="mb-0" id="totalPrepairTime">00:00:00</h4>
                            </div>
                            <div class="bg-secondary bg-opacity-10 p-2 rounded">
                                <i class="fas fa-hand-paper text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Tiempo Lento</h6>
                                <h4 class="mb-0" id="totalSlowTime">00:00:00</h4>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded">
                                <i class="fas fa-tachometer-alt text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-danger border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Paradas</h6>
                                <h4 class="mb-0" id="totalProductionStopsTime">00:00:00</h4>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-2 rounded">
                                <i class="fas fa-tools text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-danger border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Falta Material</h6>
                                <h4 class="mb-0" id="totalDownTime">00:00:00</h4>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-2 rounded" style="opacity: 0.8;">
                                <i class="fas fa-exclamation-triangle text-danger" style="opacity: 0.8;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div class="card">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 Hideclass="mb-0">
                        <i class="fas fa-table me-2 text-primary" hidden></i>
                        
                    </h6>
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
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-success" id="exportExcel">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="exportPDF">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="printTable">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body py-2 px-3">
                <div class="table-responsive">
                    <table id="controlWeightTable" class="table table-hover table-striped" style="width:100%">
                        <!-- La tabla se generará dinámicamente con DataTables -->
                    </table>
                </div>
            </div>
        </div>
        
        @include('productionlines.status-legend')
        
        @include('productionlines.time-legend')
    </div>
    
    <!-- Modal para detalles de línea de producción -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 80%; width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Detalles de Línea de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Información General</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="40%">Línea</td>
                                                <td id="modal-line-name"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Orden</td>
                                                <td id="modal-order-id"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Caja</td>
                                                <td id="modal-box"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Unidades</td>
                                                <td id="modal-units"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">UPM Real</td>
                                                <td id="modal-upm-real"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">UPM Teórico</td>
                                                <td id="modal-upm-theoretical"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Estado</td>
                                                <td><span id="modal-status" class="badge"></span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo de inicio</td>
                                                <td id="modal-created-at"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Última actualización</td>
                                                <td id="modal-updated-at"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Básculas</h6>
                                </div>
                                <div class="card-body p-0">
                                    <h6 class="text-primary p-2 mb-0 bg-light border-bottom">Báscula Final de Línea</h6>
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="50%">Nº en Turno</td>
                                                <td id="modal-weights-0-shift-number"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kg en Turno</td>
                                                <td id="modal-weights-0-shift-kg"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Nº en Orden</td>
                                                <td id="modal-weights-0-order-number"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kg en Orden</td>
                                                <td id="modal-weights-0-order-kg"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <h6 class="text-danger p-2 mb-0 bg-light border-bottom border-top">Básculas de Rechazo</h6>
                                    <div id="weights-rejection-container" class="p-2">
                                        <!-- Aquí se insertarán dinámicamente las básculas de rechazo -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">OEE</h6>
                                </div>
                                <div class="card-body p-0 text-center">
                                    <div style="height: 402px; padding: 15px; display: flex; justify-content: center; align-items: center;">
                                        <canvas id="oeeChart" style="max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Tiempos de Producción</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="50%">Tiempo de producción</td>
                                                <td id="modal-on-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Ganado</td>
                                                <td id="modal-fast-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Lento</td>
                                                <td id="modal-slow-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo de Más</td>
                                                <td id="modal-out-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Parada Falta Material</td>
                                                <td id="modal-down-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Paradas No Justificadas</td>
                                                <td id="modal-production-stops-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Preparación</td>
                                                <td id="modal-prepair-time"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
  {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        .dataTables_wrapper {
            overflow-x: auto;
        }
        
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        
        .select2-container .select2-selection--multiple {
            min-height: 38px;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        /* Ajustar color del texto en las opciones seleccionadas */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #212529;
            font-weight: 500;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #495057;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #212529;
            background-color: #dde2e6;
        }
    </style>
@endpush

@push('scripts')
  {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/dashboard-animations.css') }}" rel="stylesheet">
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="{{ asset('js/dashboard-animations.js') }}?v={{ time() }}"></script>

    <script>
        $(document).ready(function() {
            console.log('Document ready, checking for DashboardAnimations class...');
            // La clase se inicializa automáticamente en el archivo JS
        });
        // Limitar el rango máximo de fechas a 7 días
        function ensureMaxRange7Days() {
            const startVal = $('#startDate').val();
            const endVal = $('#endDate').val();
            if (!startVal || !endVal) return;
            const start = new Date(startVal);
            const end = new Date(endVal);
            const sevenDaysMs = 7 * 24 * 60 * 60 * 1000;
            if ((end - start) > sevenDaysMs) {
                const newStart = new Date(end.getTime() - sevenDaysMs);
                const fmt = (d) => d.toISOString().slice(0,16);
                $('#startDate').val(fmt(newStart));
            }
        }
    </script>

    <script>
        // IA: Configuración y utilidades
        const AI_URL = "{{ config('services.ai.url') }}";
        const AI_TOKEN = "{{ config('services.ai.token') }}";

        function collectAiContext() {
            const table = $('#controlWeightTable').DataTable();
            const rows = table ? table.rows({ page: 'current' }).data().toArray() : [];
            const filters = {
                lines: $('#modbusSelect').val() || [],
                operators: $('#operatorSelect').val() || [],
                startDate: $('#startDate').val(),
                endDate: $('#endDate').val()
            };
            return { rows, filters, page: 'productionlines/liststats' };
        }

        function showAiLoading(show) {
            const btn = document.getElementById('btn-ai-send');
            if (!btn) return;
            btn.disabled = !!show;
            btn.innerText = show ? '{{ __('Enviando...') }}' : '{{ __('Enviar a IA') }}';
        }

        async function startAiTask(prompt) {
            try {
                showAiLoading(true);
                const payload = collectAiContext();
                console.log('[AI][Prod Lines] Context rows:', payload.rows.length, 'Filters:', payload.filters);
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

                const resp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${AI_TOKEN}` },
                    body: fd
                });
                if (!resp.ok) {
                    const t = await resp.text();
                    throw new Error(`AI create failed ${resp.status}: ${t}`);
                }
                const created = await resp.json();
                const taskId = (created && (created.id || created.task_id || created.taskId)) || created;
                if (!taskId) throw new Error('No task id');

                let done = false; let last;
                while (!done) {
                    await new Promise(r => setTimeout(r, 5000));
                    const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                        headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                    });
                    if (pollResp.status === 404) {
                        try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {}
                        return;
                    }
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
                showAiLoading(false);
            }
        }

        $(function(){
            const defaultPromptPL = {!! json_encode(__('Analiza las líneas de producción mostradas (OEE, tiempos, paradas y operadores) para detectar patrones en los últimos días.')) !!};
            $('#aiPromptModal').on('shown.bs.modal', function(){
                const $ta = $('#aiPrompt');
                if (!$ta.val()) $ta.val(defaultPromptPL);
                $ta.trigger('focus');
            });
            $('#btn-ai-reset').on('click', function(){ $('#aiPrompt').val(defaultPromptPL); });
            $('#btn-ai-send').on('click', function(){
                const prompt = ($('#aiPrompt').val() || '').trim() || defaultPromptPL;
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
                    <textarea class="form-control" id="aiPrompt" rows="4" placeholder="@lang('Describe qué análisis quieres sobre las líneas mostradas')"></textarea>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const token = new URLSearchParams(window.location.search).get('token');
        console.log("Token obtenido:", token);

        // Función para formatear segundos a HH:MM:SS
        function formatTime(seconds) {
            if (seconds === null || seconds === undefined || isNaN(seconds) || seconds === 0) return '00:00:00';
            seconds = parseInt(seconds);
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        async function fetchProductionLines() {
            try {
                console.log("Intentando obtener líneas de producción...");
                const response = await fetch(`/api/production-lines/${token}`);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Líneas de producción recibidas:", data);

                const modbusSelect = $('#modbusSelect');
                modbusSelect.empty();
                
                // Ordenar las líneas de producción alfabéticamente por nombre
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                data.forEach(line => {
                    modbusSelect.append(`<option value="${line.token}">${line.name}</option>`);
                });
                
                // Inicializar Select2 para líneas de producción
                modbusSelect.select2({
                    placeholder: "Seleccionar líneas",
                    allowClear: true
                });
                
                // Cargar operarios
                fetchOperators();
            } catch (error) {
                console.error("Error al cargar líneas de producción:", error);
            }
        }
        
        // Función para cargar los operarios disponibles
        async function fetchOperators() {
            try {
                console.log("Intentando obtener operarios con IDs internos...");
                const response = await fetch('/api/operators/internal');
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const operators = await response.json();
                console.log("Operarios con IDs internos recibidos:", operators);
                
                const operatorSelect = $('#operatorSelect');
                operatorSelect.empty();
                
                // Ordenar los operarios alfabéticamente por nombre
                if (Array.isArray(operators)) {
                    operators.sort((a, b) => a.name.localeCompare(b.name));
                    
                    operators.forEach(operator => {
                        operatorSelect.append(`<option value="${operator.id}">${operator.name}</option>`);
                    });
                } else {
                    console.error("El formato de datos de operarios no es válido:", operators);
                }
                
                // Inicializar Select2 para operarios
                operatorSelect.select2({
                    placeholder: "Seleccionar empleados",
                    allowClear: true
                });
            } catch (error) {
                console.error("Error al cargar operarios:", error);
            }
        }

        async function fetchOrderStats(lineTokens, startDate, endDate) {
            try {
                const tokensArray = Array.isArray(lineTokens) ? lineTokens : [lineTokens];
                const filteredTokens = tokensArray.filter(token => token && token.trim() !== '');
                const selectedOperators = $('#operatorSelect').val();
                
                // Determinar el modo de filtrado basado en la selección de líneas y operadores
                let filterMode = 'line_only'; // Por defecto, filtrar solo por línea
                
                if (selectedOperators && selectedOperators.length > 0) {
                    // Si hay operadores seleccionados, priorizar el filtrado por operador
                    filterMode = 'operator_only';
                }
                
                if (filteredTokens.length === 0) {
                    throw new Error('No hay tokens válidos seleccionados');
                }
                
                const tokenParam = filteredTokens.join(',');
                let url = `/api/order-stats-all?token=${tokenParam}&start_date=${startDate}&end_date=${endDate}`;
                
                // Añadir operadores seleccionados a la URL si hay alguno
                if (selectedOperators && selectedOperators.length > 0) {
                    const operatorParam = selectedOperators.join(',');
                    url += `&operators=${operatorParam}`;
                    
                    // Añadir el modo de filtrado a la URL
                    url += `&filter_mode=${filterMode}`;
                }
                
                const fullUrl = window.location.origin + url;
                console.log("URL COMPLETA de la API:", fullUrl);
                console.log("==================================================");
                console.log("COPIABLE:", fullUrl);
                console.log("==================================================");

                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Datos de estadísticas recibidos:", data);

                // Procesar los datos para asegurar que tienen la estructura correcta
                const processedData = data.map(item => {
                    console.log('Procesando item:', item.id, 'down_time:', item.down_time, 'production_stops_time:', item.production_stops_time);
                    return {
                        id: item.id || '-',
                        production_line_name: item.production_line_name || '-',
                        order_id: item.order_id || '-',
                        box: item.box || '-',
                        units: parseInt(item.units) || 0,
                        units_per_minute_real: parseFloat(item.units_per_minute_real) || 0,
                        units_per_minute_theoretical: parseFloat(item.units_per_minute_theoretical) || 0,
                        oee: parseFloat(item.oee) || 0,
                        status: item.status || 'unknown',
                        created_at: item.created_at || null,
                        updated_at: item.updated_at || null,
                        down_time: item.down_time !== undefined ? parseFloat(item.down_time) : 0,
                        production_stops_time: item.production_stops_time !== undefined ? parseFloat(item.production_stops_time) : 0,
                        on_time: item.on_time || null,
                        operator_names: item.operator_names || [],
                        fast_time: item.fast_time || null,
                        slow_time: item.slow_time || null,
                        out_time: item.out_time || null,
                        prepair_time: item.prepair_time || null
                    }});
                
                // Actualizar los KPIs
                updateKPIs(processedData);
                
                // Limpiar cualquier estado de carga previo
                $('#loadingIndicator').hide();
                $('#controlWeightTable').show();
                
                // Destruir la tabla existente de forma segura antes de reinicializar
                if ($.fn.DataTable.isDataTable('#controlWeightTable')) {
                    $('#controlWeightTable').DataTable().destroy();
                }
                // Limpiar el contenido HTML para evitar conflictos
                $('#controlWeightTable').empty();

                const table = $('#controlWeightTable').DataTable({
                    dom: 'lfrtip',
                    buttons: [],
                    scrollX: true,
                    responsive: true,
                    data: processedData,
                    // 'destroy: true' ya no es necesario gracias al manejo manual
                    columns: [
                        { data: 'production_line_name', title: 'Línea', className: 'text-truncate', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Línea: ${cellData}`);
                        }},
                        { data: 'order_id', title: 'Orden', className: 'text-truncate', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Orden: ${cellData}`);
                        }},
                        { data: 'operator_names', title: 'Empleados', className: 'text-truncate', render: function(data, type, row) {
                            if (!data || data.length === 0) return '<span class="text-muted">Sin asignar</span>';
                            // Limitar a mostrar máximo 2 nombres y un contador si hay más
                            const names = Array.isArray(data) ? data : [data];
                            const displayNames = names.slice(0, 2).join(', ');
                            const remaining = names.length > 2 ? ` +${names.length - 2} más` : '';
                            return `<span title="${names.join(', ')}">${displayNames}${remaining}</span>`;
                        }},
                        { data: 'oee', title: 'OEE', render: data => `${Math.round(data)}%`, createdCell: function(td, cellData, rowData) {
                            const color = cellData >= 80 ? 'text-success' : cellData >= 60 ? 'text-warning' : 'text-danger';
                            $(td).html(`<span class="${color} fw-bold">${Math.round(cellData)}%</span>`);
                            $(td).attr('title', `OEE: ${Math.round(cellData)}%\nEstado: ${cellData >= 80 ? 'Excelente' : cellData >= 60 ? 'Aceptable' : 'Necesita mejora'}`);
                        }},
                        { data: 'status', title: 'Estado', render: data => {
                            const statusMap = {
                                'active': '<span class="badge bg-success">Activo</span>',
                                'paused': '<span class="badge bg-warning">Pausado</span>',
                                'error': '<span class="badge bg-danger">Incidencia</span>',
                                'completed': '<span class="badge bg-primary">Completado</span>',
                                'in_progress': '<span class="badge bg-info">En Progreso</span>',
                                'pending': '<span class="badge bg-secondary">Planificada</span>'
                            };
                            return statusMap[data] || '<span class="badge bg-secondary">Desconocido</span>';
                        }, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Estado actual: ${cellData}`);
                        }},
                        { data: 'created_at', title: 'Iniciado', render: data => new Date(data).toLocaleString(), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Inicio: ${new Date(data).toLocaleString()}`);
                        }},
                        { data: 'updated_at', title: 'Última actualización', render: data => data ? new Date(data).toLocaleString() : '-', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Última actualización: ${data ? new Date(data).toLocaleString() : '-'}`);
                        }},
                        { data: 'on_time', title: 'DURACIÓN', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Duración: ${formatTime(cellData)}`);
                        }},
                        { data: null, title: 'Teórico', render: function(data, type, row) {
                            if (row.fast_time && parseInt(row.fast_time) > 0) {
                                return '<span class="badge bg-success">' + formatTime(row.fast_time) + '</span>';
                            } else if (row.out_time && parseInt(row.out_time) > 0) {
                                return '<span class="badge bg-danger">' + formatTime(row.out_time) + '</span>';
                            } else {
                                return '';
                            }
                        }, createdCell: function(td, cellData, rowData) {
                            if (rowData.fast_time && parseInt(rowData.fast_time) > 0) {
                                $(td).attr('title', `Tiempo ganado: ${formatTime(rowData.fast_time)}`);
                            } else if (rowData.out_time && parseInt(rowData.out_time) > 0) {
                                $(td).attr('title', `Tiempo de más: ${formatTime(rowData.out_time)}`);
                            }
                        }},
                        { data: 'prepair_time', title: 'Preparación', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Tiempo de preparación: ${formatTime(cellData)}`);
                        }},
                        { data: 'slow_time', title: 'Lento', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Tiempo en velocidad lenta: ${formatTime(cellData)}`);
                        }},
                        { data: 'down_time', title: 'Paradas', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Paradas no justificadas: ${formatTime(cellData)}`);
                        }},
                        { data: 'production_stops_time', title: 'Falta material', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Parada falta material: ${formatTime(cellData)}`);
                        }},
                        { data: null, title: 'Acciones', orderable: false, render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-primary" onclick="showDetailsModal(${JSON.stringify(row).replace(/"/g, '&quot;')})">
                                <i class="fas fa-eye"></i> Ver
                            </button>`;
                        }, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', 'Ver detalles completos');
                        }}
                    ],
                    order: [[1, 'desc']], // Ordenar por Orden (ahora es la segunda columna)
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    searching: true,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    initComplete: function() {
                        // Añadir clases de Bootstrap a los elementos de DataTables
                        $('.dataTables_filter input').addClass('form-control form-control-sm');
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                    }
                });
            } catch (error) {
                console.error("Error al cargar datos:", error);
            }
        }

        // Función para actualizar los KPIs
        function updateKPIs(data) {
            // Total de duración (suma de on_time)
            let totalDurationSeconds = 0;
            data.forEach(item => {
                if (item.on_time && !isNaN(item.on_time)) {
                    totalDurationSeconds += parseInt(item.on_time);
                }
            });
            
            // Formatear la duración total en formato HH:MM:SS
            $('#totalDuration').text(formatTime(totalDurationSeconds));
            
            // Promedio de OEE
            let totalOEE = 0;
            let validOEECount = 0;
            
            data.forEach(item => {
                if (item.oee && !isNaN(item.oee)) {
                    const oeeValue = parseFloat(item.oee);
                    // Verificar si el valor ya es un porcentaje (>1) o decimal (<1)
                    totalOEE += oeeValue > 1 ? oeeValue : oeeValue * 100;
                    validOEECount++;
                }
            });
            
            const avgOEE = validOEECount > 0 ? totalOEE / validOEECount : 0;
            $('#avgOEE').text(`${Math.round(avgOEE)}%`);
            
            // Cambiar color según el valor
            if (avgOEE >= 80) {
                $('#avgOEE').removeClass('text-danger text-warning').addClass('text-success');
            } else if (avgOEE >= 60) {
                $('#avgOEE').removeClass('text-danger text-success').addClass('text-warning');
            } else {
                $('#avgOEE').removeClass('text-success text-warning').addClass('text-danger');
            }
            
            // Calcular suma teórica neta (tiempo ganado vs. tiempo de más)
            let totalFastTime = 0;
            let totalOutTime = 0; // Tiempo de más
            let totalDownTime = 0;
            let totalProductionStopsTime = 0;
            
            // Calcular por separado los tiempos de parada y falta material
            data.forEach(item => {
                if (item.down_time) {
                    totalDownTime += parseInt(item.down_time);
                }
                if (item.production_stops_time) {
                    totalProductionStopsTime += parseInt(item.production_stops_time);
                }
                if (item.out_time) {
                    totalOutTime += parseInt(item.out_time);
                }
                if (item.fast_time) {
                    totalFastTime += parseInt(item.fast_time);
                }
            });
            
            // Actualizar los valores en las tarjetas separadas
            $('#totalDownTime').text(formatTime(totalDownTime));
            $('#totalProductionStopsTime').text(formatTime(totalProductionStopsTime));
            
            let netTheoreticalTime = 0;
            let isPositive = false;
            
            if (totalFastTime >= totalOutTime) {
                netTheoreticalTime = totalFastTime - totalOutTime;
                isPositive = true;
                $('#totalTheoretical').removeClass('text-danger').addClass('text-success');
            } else {
                netTheoreticalTime = totalOutTime - totalFastTime;
                isPositive = false;
                $('#totalTheoretical').removeClass('text-success').addClass('text-danger');
            }
            
            // Mostrar el resultado formateado
            $('#totalTheoretical').text(formatTime(netTheoreticalTime));
            
            // Calcular suma total de tiempos de preparación
            let totalPrepairTime = 0;
            data.forEach(item => {
                if (item.prepair_time && !isNaN(item.prepair_time)) {
                    totalPrepairTime += parseInt(item.prepair_time);
                }
            });
            
            // Mostrar el total de tiempo de preparación
            $('#totalPrepairTime').text(formatTime(totalPrepairTime));
            
            // Calcular suma total de tiempo lento
            let totalSlowTime = 0;
            data.forEach(item => {
                if (item.slow_time && !isNaN(item.slow_time)) {
                    totalSlowTime += parseInt(item.slow_time);
                }
            });
            
            // Mostrar el total de tiempo lento
            $('#totalSlowTime').text(formatTime(totalSlowTime));
            
            // Calcular suma total de tiempos de paradas y falta de material por separado
            // Reutilizamos las variables ya declaradas anteriormente
            
            // Reiniciamos los contadores para asegurar cálculos correctos
            totalDownTime = 0;
            totalProductionStopsTime = 0;
            
            data.forEach(item => {
                // Calcular falta material (down_time)
                if (item.down_time && !isNaN(item.down_time)) {
                    totalDownTime += parseInt(item.down_time);
                }
                // Calcular paradas no justificadas (production_stops_time)
                if (item.production_stops_time && !isNaN(item.production_stops_time)) {
                    totalProductionStopsTime += parseInt(item.production_stops_time);
                }
            });
            
            // Mostrar los totales en tarjetas separadas
            $('#totalDownTime').text(formatTime(totalDownTime));
            $('#totalProductionStopsTime').text(formatTime(totalProductionStopsTime));
        }

        // Inicializar fechas por defecto
        function initializeDates() {
            const now = new Date();
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(now.getDate() - 7);
            
            // Formato YYYY-MM-DDThh:mm
            const formatDate = (date) => {
                return date.toISOString().slice(0, 16);
            };
            
            $('#startDate').val(formatDate(oneWeekAgo));
            $('#endDate').val(formatDate(now));
        }

        // Función para mostrar detalles en el modal
        function showDetailsModal(row) {
            console.log('Mostrando detalles de la fila:', row);
            console.log('OEE de la fila:', row.oee);
            
            // Actualizar datos generales en el modal
            $('#modal-line-name').text(row.production_line_name || '-');
            $('#modal-order-id').text(row.order_id || '-');
            $('#modal-box').text(row.box || '-');
            $('#modal-units').text(row.units ? row.units.toLocaleString() : '0');
            $('#modal-upm-real').text(row.units_per_minute_real ? parseFloat(row.units_per_minute_real).toFixed(2) : '0.00');
            $('#modal-upm-theoretical').text(row.units_per_minute_theoretical ? parseFloat(row.units_per_minute_theoretical).toFixed(2) : '0.00');
            
            // Actualizar tiempos de producción
            $('#modal-on-time').text(row.on_time !== null && row.on_time !== undefined ? formatTime(row.on_time) : '-');
            $('#modal-fast-time').text(row.fast_time !== null && row.fast_time !== undefined ? formatTime(row.fast_time) : '-');
            $('#modal-slow-time').text(row.slow_time !== null && row.slow_time !== undefined ? formatTime(row.slow_time) : '-');
            $('#modal-out-time').text(row.out_time !== null && row.out_time !== undefined ? formatTime(row.out_time) : '-');
            $('#modal-down-time').text(row.down_time !== null && row.down_time !== undefined ? formatTime(row.down_time) : '-');
            $('#modal-production-stops-time').text(row.production_stops_time !== null && row.production_stops_time !== undefined ? formatTime(row.production_stops_time) : '-');
            $('#modal-prepair-time').text(row.prepair_time !== null && row.prepair_time !== undefined ? formatTime(row.prepair_time) : '-');
            
            // Función auxiliar para verificar si un valor tiene datos reales
            const hasRealData = (value) => {
                return value !== null && value !== undefined && value !== '' && value !== '-' && value !== 0 && value !== '0';
            };
            
            // Verificar si hay datos en básculas
            const hasMainScaleData = (
                hasRealData(row.weights_0_shiftNumber) ||
                hasRealData(row.weights_0_shiftKg) ||
                hasRealData(row.weights_0_orderNumber) ||
                hasRealData(row.weights_0_orderKg)
            );
            
            // Variable para verificar si hay datos en básculas de rechazo
            let hasRejectionScaleData = false;
            
            // Actualizar datos de báscula final de línea (weights_0)
            $('#modal-weights-0-shift-number').text(row.weights_0_shiftNumber !== null && row.weights_0_shiftNumber !== undefined ? row.weights_0_shiftNumber : '-');
            $('#modal-weights-0-shift-kg').text(row.weights_0_shiftKg !== null && row.weights_0_shiftKg !== undefined ? row.weights_0_shiftKg : '-');
            $('#modal-weights-0-order-number').text(row.weights_0_orderNumber !== null && row.weights_0_orderNumber !== undefined ? row.weights_0_orderNumber : '-');
            $('#modal-weights-0-order-kg').text(row.weights_0_orderKg !== null && row.weights_0_orderKg !== undefined ? row.weights_0_orderKg : '-');
            
            // Actualizar básculas de rechazo (weights_1, weights_2, weights_3)
            const rejectionWeightsContainer = $('#weights-rejection-container');
            rejectionWeightsContainer.empty(); // Limpiar contenedor
            
            // Comprobar y mostrar básculas de rechazo (1-3)
            for (let i = 1; i <= 3; i++) {
                const shiftNumber = row[`weights_${i}_shiftNumber`];
                const shiftKg = row[`weights_${i}_shiftKg`];
                const orderNumber = row[`weights_${i}_orderNumber`];
                const orderKg = row[`weights_${i}_orderKg`];
                
                // Solo mostrar si hay al menos un valor real
                if (hasRealData(shiftNumber) || hasRealData(shiftKg) || hasRealData(orderNumber) || hasRealData(orderKg)) {
                    hasRejectionScaleData = true;
                    const weightHtml = `
                        <div class="mb-3">
                            <h6 class="text-secondary">Báscula ${i}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="fw-bold">Nº en Turno:</label>
                                    <span>${shiftNumber !== null && shiftNumber !== undefined ? shiftNumber : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Kg en Turno:</label>
                                    <span>${shiftKg !== null && shiftKg !== undefined ? shiftKg : '-'}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="fw-bold">Nº en Orden:</label>
                                    <span>${orderNumber !== null && orderNumber !== undefined ? orderNumber : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Kg en Orden:</label>
                                    <span>${orderKg !== null && orderKg !== undefined ? orderKg : '-'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    rejectionWeightsContainer.append(weightHtml);
                }
            }
            
            // Si no hay básculas de rechazo, mostrar mensaje
            if (rejectionWeightsContainer.children().length === 0) {
                rejectionWeightsContainer.html('<p class="text-muted">No hay datos de básculas de rechazo</p>');
            }
            
            // Ocultar o mostrar la sección completa de básculas según si hay datos
            const scaleCard = $('.card:has(.card-header:contains("Básculas"))');
            if (!hasMainScaleData && !hasRejectionScaleData) {
                scaleCard.hide();
            } else {
                scaleCard.show();
            }
            
            // Actualizar estado
            const statusMap = {
                'active': { text: 'Activo', class: 'bg-success' },
                'paused': { text: 'Pausado', class: 'bg-warning' },
                'error': { text: 'Incidencia', class: 'bg-danger' },
                'completed': { text: 'Completado', class: 'bg-primary' },
                'in_progress': { text: 'En Progreso', class: 'bg-info' },
                'pending': { text: 'Planificada', class: 'bg-secondary' }
            };
            const status = statusMap[row.status] || { text: 'Iniciada Anterior', class: 'bg-secondary' };
            $('#modal-status').text(status.text).removeClass().addClass('badge ' + status.class);
            
            // Asegurar que el OEE se pase correctamente al gráfico
            const oeeData = {
                oee: row.oee,
                units_per_minute_real: row.units_per_minute_real,
                units_per_minute_theoretical: row.units_per_minute_theoretical,
                ...row
            };
            
            // Crear gráfica de OEE
            createOEEChart(oeeData);
            
            // Actualizar fechas
            $('#modal-created-at').text(row.created_at ? new Date(row.created_at).toLocaleString() : '-');
            $('#modal-updated-at').text(row.updated_at ? new Date(row.updated_at).toLocaleString() : '-');
            
            // Mostrar el modal usando jQuery (compatible con la versión de Bootstrap del sistema)
            $('#detailsModal').modal('show');
            
            // Configurar el botón de cierre manualmente
            $('.btn-close, .btn-secondary').on('click', function() {
                $('#detailsModal').modal('hide');
            });
            
            // Asegurarse de que el canvas exista y sea visible antes de crear la gráfica
            $('#detailsModal').on('shown.bs.modal', function() {
                console.log('Modal mostrado, creando gráfica...');
                createOEEChart(row);
            });
        }
        
        // Función para crear la gráfica de OEE
        function createOEEChart(row) {
            console.log('Intentando crear gráfica OEE...');
            
            // Verificar si el canvas existe
            const canvas = document.getElementById('oeeChart');
            if (!canvas) {
                console.error('No se encontró el elemento canvas para la gráfica');
                // Intentar crear el canvas si no existe
                const chartContainer = document.querySelector('.card-body');
                if (chartContainer) {
                    console.log('Recreando el canvas...');
                    const canvasElement = document.createElement('canvas');
                    canvasElement.id = 'oeeChart';
                    canvasElement.height = 200;
                    chartContainer.appendChild(canvasElement);
                    return setTimeout(() => createOEEChart(row), 100); // Intentar de nuevo
                }
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('No se pudo obtener el contexto 2d del canvas');
                return;
            }
            
            console.log('Canvas encontrado, creando gráfica...');
            
            // Destruir gráfica anterior si existe
            if (window.oeeChartInstance) {
                window.oeeChartInstance.destroy();
            }
            
            // Calcular OEE como porcentaje
            console.log('Datos de OEE recibidos:', row.oee, typeof row.oee);
            
            // Usar el valor de OEE directamente desde la API sin cálculos
            let oeePercentage = 0;
            if (row.oee !== null && row.oee !== undefined && !isNaN(row.oee)) {
                oeePercentage = parseFloat(row.oee);
            } else {
                oeePercentage = 0;
            }
            
            console.log('OEE directo desde API:', oeePercentage);
            
            // Crear nueva gráfica
            window.oeeChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['OEE', 'Restante'],
                    datasets: [{
                        data: [oeePercentage, 100 - oeePercentage],
                        backgroundColor: [
                            oeePercentage >= 80 ? '#28a745' : (oeePercentage >= 60 ? '#ffc107' : '#dc3545'),
                            '#f0f2f5'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${Math.round(context.raw)}%`;
                                }
                            }
                        }
                    },
                    elements: {
                        center: {
                            text: `${Math.round(oeePercentage)}%`,
                            color: '#000',
                            fontStyle: 'Arial',
                            sidePadding: 20,
                            minFontSize: 20,
                            lineHeight: 25
                        }
                    }
                }
            });
            
            // Añadir texto en el centro del gráfico usando el valor actual del gráfico
            Chart.register({
                id: 'doughnutCenterText',
                afterDraw: function(chart) {
                    if (chart.config.type === 'doughnut' && chart.data.datasets[0]) {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        
                        ctx.restore();
                        const fontSize = (height / 114).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        
                        // Obtener el valor OEE del dataset actual
                        const oeeValue = chart.data.datasets[0].data[0] || 0;
                        const text = `${Math.round(oeeValue)}%`;
                        const textX = Math.round((width - ctx.measureText(text).width) / 2);
                        const textY = height / 2;
                        
                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }
            });
        }

        $(document).ready(() => {
            initializeDates();
            fetchProductionLines();
            setupDateFilters();

            // Botón de refrescar datos
            $('#refreshData').click(function() {
                const selectedLines = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const selectedOperators = $('#operatorSelect').val();
                console.log("Parámetros seleccionados (refresh):", { selectedLines, startDate, endDate, selectedOperators });
                
                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    ensureMaxRange7Days();
                    $('#loadingIndicator').show();
                    $('#controlWeightTable').hide();
                    $(this).find('i').addClass('fa-spin');
                    fetchOrderStats(selectedLines, startDate, endDate);
                    setTimeout(() => { $(this).find('i').removeClass('fa-spin'); }, 1200);
                } else {
                    alert('Por favor selecciona líneas y fechas válidas.');
                }
            });

            $('#fetchData').click(() => {
                const selectedLines = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const selectedOperators = $('#operatorSelect').val();
                console.log("Parámetros seleccionados:", { selectedLines, startDate, endDate, selectedOperators });

                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    ensureMaxRange7Days();
                    // Mostrar indicador de carga
                    $('#loadingIndicator').show();
                    $('#controlWeightTable').hide();
                    
                    fetchOrderStats(selectedLines, startDate, endDate);
                } else {
                    alert('Por favor selecciona líneas y fechas válidas.');
                }
            });

            // Resetear filtros
            $('#resetFilters').click(() => {
                initializeDates();
                $('#modbusSelect').val([]).trigger('change');
            });

            // Configurar eventos para los botones de exportación
            $('#exportExcel').on('click', () => exportData('excel'));
            $('#exportPDF').on('click', () => exportData('pdf'));
            $('#printTable').on('click', () => exportData('print'));
        });

        // Función para configurar filtros de fecha
        function setupDateFilters() {
            // Configuración de Select2 para el select múltiple
            $('#modbusSelect').select2({
                placeholder: 'Selecciona líneas de producción...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }    

        // Función para exportar datos
        function exportData(type) {
            const table = $('#controlWeightTable').DataTable();
            if (!table) {
                alert('No hay datos para exportar');
                return;
            }          
            switch (type) {
                case 'excel':
                    // Exportar a Excel usando SheetJS (XLSX)
                    const wb = XLSX.utils.book_new();
                    const wsData = [];
                    
                    // Encabezados
                    const headers = [];
                    $(table.table().header()).find('th').each(function() {
                        headers.push($(this).text().trim());
                    });
                    wsData.push(headers);
                    
                    // No necesitamos inicializar animaciones durante la exportación
                    
                    // Datos
                    table.rows().every(function() {
                        const rowData = this.data();
                        const row = [];
                        
                        // ID
                        row.push(rowData.id || '-');
                        
                        // Línea
                        row.push(rowData.production_line_name || '-');
                        
                        // Orden
                        row.push(rowData.order_id || '-');
                        
                        // Caja
                        row.push(rowData.box || '-');
                        
                        // Unidades
                        row.push(rowData.units ? rowData.units.toLocaleString() : '-');
                        
                        // UPM Real
                        row.push(rowData.units_per_minute_real ? rowData.units_per_minute_real.toFixed(2) : '-');
                        
                        // UPM Teórico
                        row.push(rowData.units_per_minute_theoretical ? rowData.units_per_minute_theoretical.toFixed(2) : '-');
                        
                        // OEE
                        row.push(rowData.oee ? Math.round(rowData.oee) + '%' : '-');
                        
                        // Estado
                        const statusMap = {
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'paused': 'Pausado',
                            'error': 'Error',
                            'pending': 'Pendiente',
                            'unknown': 'Desconocido'
                        };
                        row.push(statusMap[rowData.status] || statusMap['unknown']);
                        
                        // Actualizado
                        row.push(rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-');
                        
                        wsData.push(row);
                    });
                    
                    const ws = XLSX.utils.aoa_to_sheet(wsData);
                    XLSX.utils.book_append_sheet(wb, ws, "Datos de Producción");
                    
                    // Guardar archivo
                    XLSX.writeFile(wb, "Datos_Produccion_" + new Date().toLocaleDateString() + ".xlsx");
                    break;
                    
                case 'pdf':
                    // Exportar a PDF usando jsPDF
                    const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
                    
                    // Título del documento
                    doc.setFontSize(18);
                    doc.text('Datos de Producción', 14, 22);
                    doc.setFontSize(11);
                    doc.text('Fecha: ' + new Date().toLocaleString(), 14, 30);
                    
                    // Preparar datos para la tabla
                    const pdfHeaders = [];
                    $(table.table().header()).find('th').each(function() {
                        pdfHeaders.push({ title: $(this).text().trim(), dataKey: $(this).text().trim() });
                    });
                    
                    const pdfData = [];
                    table.rows().every(function() {
                        const rowData = this.data();
                        const row = {};
                        
                        // Asignar datos a las columnas
                        row[pdfHeaders[0].dataKey] = rowData.id || '-';
                        row[pdfHeaders[1].dataKey] = rowData.production_line_name || '-';
                        row[pdfHeaders[2].dataKey] = rowData.order_id || '-';
                        row[pdfHeaders[3].dataKey] = rowData.box || '-';
                        row[pdfHeaders[4].dataKey] = rowData.units ? rowData.units.toLocaleString() : '-';
                        row[pdfHeaders[5].dataKey] = rowData.units_per_minute_real ? rowData.units_per_minute_real.toFixed(2) : '-';
                        row[pdfHeaders[6].dataKey] = rowData.units_per_minute_theoretical ? rowData.units_per_minute_theoretical.toFixed(2) : '-';
                        row[pdfHeaders[7].dataKey] = rowData.oee ? Math.round(rowData.oee) + '%' : '-';
                        
                        // Estado
                        const statusMap = {
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'paused': 'Pausado',
                            'error': 'Error',
                            'pending': 'Pendiente',
                            'unknown': 'Desconocido'
                        };
                        row[pdfHeaders[8].dataKey] = statusMap[rowData.status] || statusMap['unknown'];
                        
                        // Actualizado
                        row[pdfHeaders[9].dataKey] = rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-';
                        
                        pdfData.push(row);
                    });
                    
                    // Generar tabla en PDF
                    doc.autoTable({
                        head: [pdfHeaders.map(h => h.title)],
                        body: pdfData.map(row => pdfHeaders.map(h => row[h.dataKey])),
                        startY: 40,
                        margin: { top: 40 },
                        styles: { overflow: 'linebreak', fontSize: 8 },
                        headStyles: { fillColor: [41, 128, 185], textColor: 255 },
                        alternateRowStyles: { fillColor: [245, 245, 245] }
                    });
                    
                    // Guardar PDF
                    doc.save("Datos_Produccion_" + new Date().toLocaleDateString() + ".pdf");
                    break;
                    
                case 'print':
                    // Imprimir manualmente
                    let printWindow = window.open('', '_blank');
                    let tableHtml = '<html><head><title>Datos de Producción</title>';
                    tableHtml += '<style>body{font-family:Arial,sans-serif;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>';
                    tableHtml += '</head><body>';
                    tableHtml += '<h1>Datos de Producción</h1>';
                    tableHtml += '<p>Fecha: ' + new Date().toLocaleString() + '</p>';
                    tableHtml += '<table>';
                    
                    // Encabezados
                    tableHtml += '<thead><tr>';
                    $(table.table().header()).find('th').each(function() {
                        tableHtml += '<th>' + $(this).text().trim() + '</th>';
                    });
                    tableHtml += '</tr></thead>';
                    
                    // Datos
                    tableHtml += '<tbody>';
                    table.rows().every(function() {
                        let rowData = this.data();
                        tableHtml += '<tr>';
                        
                        // ID
                        tableHtml += '<td>' + (rowData.id || '-') + '</td>';
                        
                        // Línea
                        tableHtml += '<td>' + (rowData.production_line_name || '-') + '</td>';
                        
                        // Orden
                        tableHtml += '<td>' + (rowData.order_id || '-') + '</td>';
                        
                        // Caja
                        tableHtml += '<td>' + (rowData.box || '-') + '</td>';
                        
                        // Unidades
                        tableHtml += '<td>' + (rowData.units ? rowData.units.toLocaleString() : '-') + '</td>';
                        
                        // UPM Real
                        tableHtml += '<td>' + (rowData.units_per_minute_real ? rowData.units_per_minute_real.toFixed(2) : '-') + '</td>';
                        
                        // UPM Teórico
                        tableHtml += '<td>' + (rowData.units_per_minute_theoretical ? rowData.units_per_minute_theoretical.toFixed(2) : '-') + '</td>';
                        
                        // OEE
                        tableHtml += '<td>' + (rowData.oee ? Math.round(rowData.oee) + '%' : '-') + '</td>';
                        
                        // Estado
                        const statusMap = {
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'paused': 'Pausado',
                            'error': 'Error',
                            'pending': 'Pendiente',
                            'unknown': 'Desconocido'
                        };
                        tableHtml += '<td>' + (statusMap[rowData.status] || statusMap['unknown']) + '</td>';
                        
                        // Actualizado
                        tableHtml += '<td>' + (rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-') + '</td>';
                        
                        tableHtml += '</tr>';
                    });
                    tableHtml += '</tbody></table>';
                    tableHtml += '</body></html>';
                    
                    printWindow.document.write(tableHtml);
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(function() {
                        printWindow.print();
                        printWindow.close();
                    }, 500);
                    break;
            }
        }
    </script>
@endpush