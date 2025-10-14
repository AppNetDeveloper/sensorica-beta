@extends('layouts.admin')

@section('title', __('Tiempos de fabricación'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Tiempos de fabricación') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-filter me-2 text-primary"></i>{{ __('Filtros de búsqueda') }}</h5>
                </div>
                <div class="card-body">
                    <form id="filters-form" class="row gy-3 gx-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label mb-2 fw-semibold" for="date_start"><i class="fas fa-calendar-alt me-1"></i>{{ __('Desde') }}</label>
                            <input type="date" class="form-control" id="date_start" value="{{ $defaultStart }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-2 fw-semibold" for="date_end"><i class="fas fa-calendar-check me-1"></i>{{ __('Hasta') }}</label>
                            <input type="date" class="form-control" id="date_end" value="{{ $defaultEnd }}">
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" id="apply-filters" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> {{ __('Filtrar') }}
                            </button>
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="use_actual_delivery">
                                    <label class="form-check-label" for="use_actual_delivery" id="use_actual_delivery_label">
                                        {{ __('Usar fecha real de entrega (actual_delivery_date) en lugar de fecha ERP programada') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="only_finished_orders" checked>
                                <label class="form-check-label" for="only_finished_orders">{{ __('Sólo órdenes finalizadas') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="only_finished_processes">
                                <label class="form-check-label" for="only_finished_processes">{{ __('Sólo procesos finalizados') }}</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4 mb-4" id="kpi-cards">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-box fa-2x text-primary"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Órdenes analizadas') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-orders-total">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-tasks fa-2x text-success"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Procesos analizados') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-processes-total">0</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-stopwatch fa-2x text-info"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio ERP → Fin orden') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-finish">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-chart-line fa-2x text-warning"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio gap procesos') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-gap">-</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-table me-2 text-primary"></i>{{ __('Detalle de órdenes') }}</h5>
                        <div class="btn-toolbar" role="toolbar">
                            @php($aiUrl = config('services.ai.url'))
                            @php($aiToken = config('services.ai.token'))
                            @if(!empty($aiUrl) && !empty($aiToken))
                            <div class="btn-group btn-group-sm me-2" role="group">
                                <button type="button" class="btn btn-dark" id="btn-ai-open" data-bs-toggle="modal" data-bs-target="#aiPromptModal" title="{{ __('Análisis con IA') }}">
                                    <i class="bi bi-stars me-1 text-white"></i><span class="d-none d-sm-inline">{{ __('Análisis IA') }}</span>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover w-100 mb-0" id="production-times-table">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>{{ __('ORDER ID') }}</th>
                                    <th>{{ __('Cliente') }}</th>
                                    <th>{{ __('Fecha ERP') }}</th>
                                    <th>{{ __('Creado') }}</th>
                                    <th>{{ __('Finalizado') }}</th>
                                    <th>{{ __('ERP → Creado') }}</th>
                                    <th>{{ __('ERP → Fin') }}</th>
                                    <th>{{ __('Creado → Fin') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>{{ __('Comparativa por proceso') }}</h5>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" data-bs-toggle="modal" data-bs-target="#processMetricsHelpModal">
                        <i class="fas fa-question-circle me-1"></i>{{ __('¿Qué significan duración y gap?') }}
                    </button>
                </div>
                <div class="card-body">
                    <div id="process-summary-chart" style="min-height: 320px;"></div>
                </div>
            </div>

            <div class="modal fade" id="processMetricsHelpModal" tabindex="-1" aria-labelledby="processMetricsHelpModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="processMetricsHelpModalLabel"><i class="fas fa-info-circle me-2 text-primary"></i>{{ __('Cómo interpretar Duración y Gap') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cerrar') }}"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">{{ __('La métrica de Duración muestra el tiempo efectivo en el que el proceso estuvo trabajando. Empieza cuando el operario inicia el proceso y termina cuando registra su finalización. Si un proceso dura 02:30 h significa que la máquina o el equipo estuvo activo durante dos horas y media continuas.') }}</p>
                            <p class="mb-0">{{ __('El Gap representa el tiempo que la orden permanece esperando hasta que comienza el siguiente proceso. Un gap alto indica que la orden estuvo parada fuera de producción (por ejemplo en almacén, esperando preparación o recursos). Analizar ambos valores juntos permite detectar procesos lentos y también cuellos de botella provocados por esperas intermedias.') }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal"><i class="fas fa-check me-2"></i>{{ __('Entendido') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2 text-primary"></i>{{ __('Leyenda de KPIs y timeline') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-clipboard-list me-2"></i>{{ __('KPIs de orden') }}</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-clipboard-list"></i></span>{{ __('ERP → Creado') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo desde que el pedido se registra en el ERP hasta que entra en producción. Se representa en azul en el timeline interactivo y alimenta el cálculo promedio.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-success bg-opacity-10 text-success me-2"><i class="fas fa-flag-checkered"></i></span>{{ __('ERP → Fin') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Duración total del pedido desde el ERP hasta la finalización. Ayuda a identificar cuellos de botella globales y se refleja como suma de los tramos azul y verde.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-industry"></i></span>{{ __('Creado → Fin') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo neto de fabricación desde la creación en producción hasta la finalización. En el timeline aparece en verde y mide la eficiencia interna de la planta.') }}</dd>

                                <dt class="col-sm-5 mb-0"><span class="badge bg-secondary bg-opacity-10 text-secondary me-2"><i class="fas fa-cogs"></i></span>{{ __('Procesos registrados') }}</dt>
                                <dd class="col-sm-7 mb-0">{{ __('Número de procesos detectados en la orden. Alimenta el timeline por proceso y la comparativa “Duración / Gap”.') }}</dd>
                            </dl>
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-chart-bar me-2"></i>{{ __('Timeline y detalle de procesos') }}</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-stopwatch"></i></span>{{ __('Duración del proceso') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo neto de actividad del proceso (desde que arranca hasta que finaliza). Se muestra en azul y se expresa en minutos.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-warning bg-opacity-10 text-warning me-2"><i class="fas fa-hourglass-half"></i></span>{{ __('Gap entre procesos') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo que la orden permanece en espera entre un proceso y el siguiente. Cuanto mayor es el gap, más tiempo estuvo detenida la orden fuera de la producción activa.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-info bg-opacity-10 text-info me-2"><i class="fas fa-project-diagram"></i></span>{{ __('ERP → Proceso / Creado → Proceso') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Indicadores adicionales que muestran cuánto tardó el proceso en arrancar desde el ERP y desde la creación en planta.') }}</dd>

                                <dt class="col-sm-5 mb-0"><span class="badge bg-light text-primary me-2"><i class="fas fa-layer-group"></i></span>{{ __('Posición en la secuencia') }}</dt>
                                <dd class="col-sm-7 mb-0">{{ __('El número de orden (1,2,3,...) indica la secuencia de ejecución. Se usa como eje horizontal en el timeline.') }}</dd>
                                <dt class="col-sm-5 mb-0 mt-3"><span class="badge bg-primary bg-opacity-25 text-primary me-2"><i class="fas fa-exchange-alt"></i></span>{{ __('Timelines interactivos') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Debajo de los KPIs se muestran dos gráficos tipo Power BI: el timeline de la orden (rangos azul, verde y amarillo) y el promedio del rango actual. Cada gráfico permite zoom, pan y exportación (CSV/SVG/PNG).') }}</dd>

                                <dt class="col-sm-5 mb-0"><span class="badge bg-warning bg-opacity-25 text-warning me-2"><i class="fas fa-toggle-on"></i></span>{{ __('Usar fecha real de entrega') }}</dt>
                                <dd class="col-sm-7 mb-0">{{ __('Al activar el switch “Usar fecha real de entrega (actual_delivery_date)” el tramo amarillo pasa a medir Fin → Entrega real. Si la fecha real no existe, el segmento aparece atenuado y el análisis usa la entrega programada.') }}</dd>
                            </dl>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>{{ __('Consejo') }}:</strong> {{ __('Los gaps elevados o procesos sin duración registrada pueden afectar al análisis y al gráfico. Revisa las órdenes con múltiples procesos para detectar discrepancias.') }}
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }
        #production-times-table_wrapper .dataTables_filter { 
            margin-bottom: 15px; 
        }
        #production-times-table_wrapper .dataTables_paginate { 
            margin-top: 1rem;
        }
        #production-times-table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem 0.75rem;
        }
        #production-times-table tbody tr {
            transition: background-color 0.15s ease;
        }
        #production-times-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        table.dataTable {
            border-spacing: 0 8px !important;
            border-collapse: separate !important;
            margin-top: 10px !important;
            width: 100% !important;
        }
        table.dataTable tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }
        .details-row {
            background-color: #f8f9fc;
            border-left: 4px solid #0d6efd;
            border-radius: 0.75rem;
        }
        .detail-kpi-card {
            background-color: #fff;
            border-radius: 0.75rem;
            padding: 1rem 1.25rem;
            border: 1px solid #edf2f7;
            box-shadow: 0 4px 20px rgba(13, 110, 253, 0.08);
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .detail-kpi-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(13,110,253,0.08), rgba(13,110,253,0));
            opacity: 0.8;
            pointer-events: none;
        }
        .detail-kpi-card .detail-icon {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            font-size: 1rem;
            margin-bottom: 0.75rem;
        }
        .detail-kpi-card h6 {
            font-size: 0.75rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #6c757d;
        }
        .detail-kpi-card .detail-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
        }
        .detail-kpi-card .detail-subtext {
            font-size: 0.85rem;
            color: #6c757d;
        }
        .timeline-card {
            background-color: #fff;
            border-radius: 0.75rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
            padding: 1.25rem;
            margin-top: 1.25rem;
        }
        .timeline-header h6 {
            font-size: 0.82rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6c757d;
        }
        .timeline-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.85rem;
            flex-wrap: wrap;
        }
        .timeline-row:last-child {
            margin-bottom: 0.5rem;
        }
        .timeline-label {
            min-width: 180px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .timeline-bar {
            position: relative;
            flex: 1;
            min-width: 200px;
            height: 10px;
            background: linear-gradient(90deg, #f1f3f5 0%, #e9ecef 100%);
            border-radius: 999px;
            overflow: hidden;
        }
        .timeline-segment {
            position: absolute;
            top: 0;
            height: 100%;
            border-radius: 999px;
            opacity: 0.95;
        }
        .segment-primary { background: linear-gradient(90deg, rgba(13,110,253,0.9), rgba(13,110,253,0.6)); }
        .segment-success { background: linear-gradient(90deg, rgba(25,135,84,0.9), rgba(25,135,84,0.6)); }
        .segment-warning { background: linear-gradient(90deg, rgba(255,193,7,0.9), rgba(255,193,7,0.6)); }
        .segment-info { background: linear-gradient(90deg, rgba(13,202,240,0.9), rgba(13,202,240,0.6)); }
        .segment-secondary { background: linear-gradient(90deg, rgba(108,117,125,0.9), rgba(108,117,125,0.6)); }
        .timeline-value {
            min-width: 90px;
            text-align: right;
            font-size: 0.78rem;
            font-weight: 600;
            color: #212529;
        }
        .timeline-axis {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .process-timeline {
            margin-top: 0.9rem;
            background: #fff;
            border: 1px dashed rgba(13, 110, 253, 0.2);
            border-radius: 0.6rem;
            padding: 0.75rem 0.85rem;
        }
        .process-timeline-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c757d;
            margin-bottom: 0.65rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .timeline-row.disabled .timeline-bar {
            opacity: 0.5;
            background: repeating-linear-gradient(135deg, #f1f3f5, #f1f3f5 6px, #e9ecef 6px, #e9ecef 12px);
        }
        .timeline-row.disabled .timeline-value {
            color: #adb5bd;
        }
        .timeline-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.75rem;
            font-size: 0.72rem;
            color: #6c757d;
        }
        .timeline-legend span {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .timeline-legend .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        .legend-dot.segment-primary { background: linear-gradient(90deg, rgba(13,110,253,0.9), rgba(13,110,253,0.6)); }
        .legend-dot.segment-success { background: linear-gradient(90deg, rgba(25,135,84,0.9), rgba(25,135,84,0.6)); }
        .legend-dot.segment-warning { background: linear-gradient(90deg, rgba(255,193,7,0.9), rgba(255,193,7,0.6)); }
        .legend-dot.segment-info { background: linear-gradient(90deg, rgba(13,202,240,0.9), rgba(13,202,240,0.6)); }
        .process-detail-wrapper {
            background-color: #fff;
            border-radius: 0.75rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            padding: 1.25rem;
        }
        .process-detail-wrapper h6 {
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6c757d;
        }
        .process-card {
            border-left: 4px solid #0d6efd;
            background-color: #f8f9fa;
            border-radius: 0.75rem;
            padding: 0.9rem 1rem;
            box-shadow: 0 6px 18px rgba(13, 110, 253, 0.08);
        }
        .process-card + .process-card {
            margin-top: 0.75rem;
        }
        .process-card .process-title {
            font-weight: 600;
            color: #212529;
        }
        .process-card .process-metadata {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .process-card .process-badges {
            gap: 0.5rem;
        }
        .process-card .badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .mini-process-chart {
            min-height: 240px;
        }
        .card-header {
            padding: 1rem 1.5rem;
        }
        .btn-primary {
            border-radius: 0.375rem;
            padding: 0.625rem 1.5rem;
            font-weight: 500;
        }
        .form-control, .form-select {
            border-radius: 0.375rem;
            border: 1px solid #dee2e6;
            padding: 0.625rem 0.75rem;
            font-size: 0.9375rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        @keyframes shimmer {
            0% {
                background-position: -468px 0;
        .dataTables_wrapper {
            padding: 15px !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/svg.js@2.6.6/dist/svg.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2/dist/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        $(function () {
            const routes = {
                data: "{{ route('customers.production-times.data', $customer) }}",
                summary: "{{ route('customers.production-times.summary', $customer) }}",
                orderDetail: (orderId) => "{{ route('customers.production-times.order', [$customer, ':orderId']) }}".replace(':orderId', orderId)
            };

            const i18n = {
                erpToCreated: @json(__('ERP → Creado')),
                erpToFinished: @json(__('ERP → Fin')),
                createdToFinished: @json(__('Creado → Fin')),
                processes: @json(__('Procesos')),
                erpRegistered: @json(__('Pedido ERP')),
                noErpDate: @json(__('Sin fecha ERP registrada')),
                createdAt: @json(__('Creado en producción')),
                finishedAt: @json(__('Finalizado')),
                position: @json(__('Posición')),
                duration: @json(__('Duración')),
                gap: @json(__('Gap')),
                erpToProcess: @json(__('ERP → Proceso')),
                createdToProcess: @json(__('Creado → Proceso')),
                timelineTitle: @json(__('Timeline de procesos')),
                timelineDetail: @json(__('Detalle de procesos')),
                noProcesses: @json(__('Sin procesos registrados para esta orden')),
                chartNoData: @json(__('Sin datos de procesos para generar el gráfico')),
                chartUnavailable: @json(__('No se pudo inicializar el gráfico')),
                loadingChart: @json(__('Generando gráfico...')),
                minutesSuffix: @json(__('min')),
                orderId: @json(__('ORDER ID')),
                timelineOrdersTitle: @json(__('Cronología de fechas')),
                timelineFromErp: @json(__('Ruta desde ERP')),
                timelineFromCreated: @json(__('Ruta desde creación')),
                timelineLegendErpCreated: @json(__('ERP → Creado')),
                timelineLegendCreatedFinished: @json(__('Creado → Fin')),
                timelineLegendFinishedDelivery: @json(__('Fin → Entrega')),
                timelineLegendCreatedProcess: @json(__('Creado → Proceso')),
                timelineLegendProcessDelivery: @json(__('Proceso → Entrega')),
                timelineProcessPath: @json(__('Ruta al proceso')),
                timelineNoData: @json(__('Sin datos suficientes para mostrar la cronología')),
                timelineStart: @json(__('Inicio')),
                timelineEnd: @json(__('Fin')),
                erpDateLabel: @json(__('Fecha ERP')),
                createdDateLabel: @json(__('Creado en producción')),
                finishedDateLabel: @json(__('Fecha fin producción')),
                deliveryDateLabel: @json(__('Fecha de entrega prevista')),
                actualDeliveryLabel: @json(__('Fecha de entrega real')),
                toggleActualDeliveryLabel: @json(__('Usar fecha real de entrega (actual_delivery_date) en lugar de fecha ERP programada')),
                timelineLegendFinishedActualDelivery: @json(__('Fin → Entrega real')),
                timelineLegendProcessActualDelivery: @json(__('Proceso → Entrega real')),
                timelineOrdersAverageTitle: @json(__('Promedio del rango'))
            };

            function renderOrderRangeBar(detail) {
                const key = detail.id ?? detail.order_id;
                const el = document.querySelector(`#order-rangebar-${key}`);
                if (!el || typeof ApexCharts === 'undefined') return;

                const tl = detail.order_timeline ?? {};
                const useActual = !!(detail.use_actual_delivery);

                const erp = (tl.erp_start_ts ?? detail.fecha_pedido_erp_ts);
                const created = (tl.created_end_ts ?? detail.created_at_ts);
                const finished = (tl.finished_end_ts ?? detail.finished_at_ts);
                const delivery = (tl.delivery_end_ts ?? detail.delivery_date_ts);

                const points = [];
                if (typeof erp === 'number' && typeof created === 'number' && created > erp) {
                    points.push({ x: 'ERP → Creado', y: [erp * 1000, created * 1000], fillColor: '#118DFF' });
                }
                if (typeof created === 'number' && typeof finished === 'number' && finished > created) {
                    points.push({ x: 'Creado → Fin', y: [created * 1000, finished * 1000], fillColor: '#21A366' });
                }
                if (typeof finished === 'number' && typeof delivery === 'number' && delivery > finished) {
                    points.push({ x: (useActual ? 'Fin → Entrega real' : 'Fin → Entrega'), y: [finished * 1000, delivery * 1000], fillColor: '#F2C811' });
                }

                try { console.log('[RB] order points', { key, pointsCount: points.length, points }); } catch(e) {}

                if (!points.length) {
                    el.innerHTML = '<div class="text-muted small">' + i18n.timelineNoData + '</div>';
                    return;
                }

                const options = {
                    chart: {
                        type: 'rangeBar',
                        height: 280,
                        width: '100%',
                        id: `order-rangebar-${key}`,
                        toolbar: {
                            show: true,
                            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                            export: { csv: { filename: 'timeline_pedido' }, svg: { filename: 'timeline_pedido' }, png: { filename: 'timeline_pedido' } }
                        },
                        animations: { enabled: true }
                    },
                    plotOptions: {
                        bar: { horizontal: true, barHeight: '70%', rangeBarGroupRows: true, borderRadius: 4 }
                    },
                    series: [{ name: 'Timeline', data: points }],
                    xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
                    dataLabels: { enabled: false },
                    grid: { strokeDashArray: 3 },
                    tooltip: {
                        x: { format: 'yyyy-MM-dd HH:mm' },
                        y: {
                            formatter: function(val, opts) {
                                const formatted = formatRangeTooltip(val, opts, true);
                                return formatted ?? '--:--:--';
                            }
                        }
                    }
                };

                const ensureRendered = () => {
                    const w = el.offsetWidth || 0;
                    if (w < 10) {
                        const n = parseInt(el.dataset.retry || '0', 10) + 1;
                        el.dataset.retry = String(n);
                        if (n <= 5) {
                            try { console.log('[RB] order retry due to zero width', { key, attempt: n, width: w }); } catch(e) {}
                            setTimeout(ensureRendered, 120);
                            return;
                        }
                    }
                    try {
                        const chart = new ApexCharts(el, options);
                        chart.render();
                        setTimeout(() => { try { chart.resize(); } catch(e) {} }, 150);
                    } catch (e) {
                        try { console.error('[RB] order render error', e); } catch(e2) {}
                        el.innerHTML = '<div class="text-danger small">{{ __('No se pudo renderizar el timeline') }}</div>';
                    }
                };
                ensureRendered();
            }

            function renderAvgRangeBar(detail) {
                const key = detail.id ?? detail.order_id;
                const el = document.querySelector(`#avg-rangebar-${key}`);
                if (!el || typeof ApexCharts === 'undefined') return;

                const avg = detail.average_timeline ?? {};
                const useActual = !!(detail.use_actual_delivery);

                const c1s = 0;
                const c1e = typeof avg.created_end_ts === 'number' ? avg.created_end_ts : null;
                const c2s = c1e;
                const c2e = typeof avg.finished_end_ts === 'number' ? avg.finished_end_ts : null;
                const c3s = c2e;
                const c3e = typeof avg.delivery_end_ts === 'number' ? avg.delivery_end_ts : null;

                const points = [];
                if (typeof c1e === 'number' && c1e > c1s) points.push({ x: 'ERP → Creado', y: [c1s, c1e], fillColor: '#118DFF' });
                if (typeof c2e === 'number' && c2e > c2s) points.push({ x: 'Creado → Fin', y: [c2s, c2e], fillColor: '#21A366' });
                if (typeof c3e === 'number' && c3e > c3s) points.push({ x: (useActual ? 'Fin → Entrega real' : 'Fin → Entrega'), y: [c3s, c3e], fillColor: '#F2C811' });

                try { console.log('[RB] avg points', { key, pointsCount: points.length, points }); } catch(e) {}

                if (!points.length) {
                    el.innerHTML = '<div class="text-muted small">' + i18n.timelineNoData + '</div>';
                    return;
                }

                const options = {
                    chart: {
                        type: 'rangeBar',
                        height: 240,
                        width: '100%',
                        id: `avg-rangebar-${key}`,
                        toolbar: {
                            show: true,
                            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                            export: { csv: { filename: 'timeline_promedio' }, svg: { filename: 'timeline_promedio' }, png: { filename: 'timeline_promedio' } }
                        },
                        animations: { enabled: true }
                    },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 4 } },
                    series: [{ name: 'Promedio', data: points }],
                    xaxis: {
                        type: 'numeric',
                        labels: {
                            formatter: function (val) {
                                const secs = Number(val || 0);
                                const h = Math.floor(secs / 3600); const m = Math.floor((secs % 3600) / 60); const s = Math.floor(secs % 60);
                                return h.toString().padStart(2,'0') + ':' + m.toString().padStart(2,'0') + ':' + s.toString().padStart(2,'0');
                            }
                        }
                    },
                    dataLabels: { enabled: false },
                    grid: { strokeDashArray: 3 },
                    tooltip: {
                        y: {
                            formatter: function(val, opts){
                                const formatted = formatRangeTooltip(val, opts, false);
                                return formatted ?? '--:--:--';
                            }
                        }
                    }
                };

                const chart = new ApexCharts(el, options);
                chart.render();
            }

            function getTimelineBounds(detail) {
                const start = detail.fecha_pedido_erp_ts ?? detail.created_at_ts ?? detail.finished_at_ts ?? detail.delivery_date_ts ?? null;
                const end = detail.delivery_date_ts ?? detail.finished_at_ts ?? detail.created_at_ts ?? detail.fecha_pedido_erp_ts ?? null;

                if (!start || !end || end <= start) {
                    return null;
                }

                return {
                    start,
                    end,
                    range: end - start,
                    startLabel: detail.fecha_pedido_erp ?? detail.created_at ?? detail.finished_at ?? detail.delivery_date ?? i18n.timelineStart,
                    endLabel: detail.delivery_date ?? detail.finished_at ?? detail.created_at ?? detail.fecha_pedido_erp ?? i18n.timelineEnd
                };
            }

            function buildTimelineRow(label, seconds, formatted, startTs, endTs, segmentClass, bounds) {
                const hasBounds = bounds && typeof bounds.start === 'number' && typeof bounds.range === 'number' && bounds.range > 0;
                const hasTimestamps = typeof startTs === 'number' && typeof endTs === 'number' && endTs > startTs;
                const valid = hasBounds && hasTimestamps && typeof seconds === 'number' && seconds >= 0;

                try { console.log('buildTimelineRow', { label, seconds, formatted, startTs, endTs, hasBounds, bounds, valid }); } catch(e) {}

                let row = `<div class="timeline-row ${valid ? '' : 'disabled'}">`;
                row += `<div class="timeline-label"><span class="legend-dot ${segmentClass}"></span>${label}</div>`;
                row += '<div class="timeline-bar">';

                if (valid) {
                    const offsetPercent = Math.min(Math.max(((startTs - bounds.start) / bounds.range) * 100, 0), 100);
                    const rawWidth = ((endTs - startTs) / bounds.range) * 100;
                    const widthPercent = Math.min(Math.max(rawWidth, 0), 100 - offsetPercent);
                    const finalWidth = Math.max(widthPercent, 3);
                    row += `<div class="timeline-segment ${segmentClass}" style="left:${offsetPercent}%;width:${finalWidth}%;"></div>`;
                }

                row += '</div>';
                row += `<div class="timeline-value">${formatted ?? '-'}</div>`;
                row += '</div>';

                return row;
            }

            function generateOrderTimeline(detail) {
                const bounds = getTimelineBounds(detail);

                if (!bounds) {
                    return `<div class="timeline-card"><div class="text-muted small">${i18n.timelineNoData}</div></div>`;
                }

                let html = '<div class="timeline-card">';
                html += `<div class="timeline-header d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">${i18n.timelineOrdersTitle}</h6>
                            <div class="timeline-legend">
                                <span><span class="legend-dot segment-primary"></span>${i18n.timelineLegendErpCreated}</span>
                                <span><span class="legend-dot segment-success"></span>${i18n.timelineLegendCreatedFinished}</span>
                                <span><span class="legend-dot segment-warning"></span>${i18n.timelineLegendFinishedDelivery}</span>
                            </div>
                         </div>`;

                html += buildTimelineRow(i18n.timelineLegendErpCreated, detail.erp_to_created_seconds, detail.erp_to_created_formatted, detail.fecha_pedido_erp_ts, detail.created_at_ts, 'segment-primary', bounds);
                html += buildTimelineRow(i18n.timelineLegendCreatedFinished, detail.created_to_finished_seconds, detail.created_to_finished_formatted, detail.created_at_ts, detail.finished_at_ts, 'segment-success', bounds);
                html += buildTimelineRow(i18n.timelineLegendFinishedDelivery, detail.finished_to_delivery_seconds, detail.finished_to_delivery_formatted, detail.finished_at_ts, detail.delivery_date_ts, 'segment-warning', bounds);

                html += `<div class="timeline-axis"><span>${bounds.startLabel}</span><span>${bounds.endLabel}</span></div>`;
                html += '</div>';

                return html;
            }

            function generateProcessTimeline(detail, process) {
                const bounds = getTimelineBounds(detail);
                if (!bounds) {
                    return '';
                }

                const rows = [];

                rows.push(buildTimelineRow(`${i18n.timelineProcessPath}: ${i18n.erpToProcess}`, process.erp_to_process_seconds, process.erp_to_process_formatted, detail.fecha_pedido_erp_ts, process.finished_at_ts, 'segment-primary', bounds));
                rows.push(buildTimelineRow(`${i18n.timelineProcessPath}: ${i18n.createdToProcess}`, process.created_to_process_seconds, process.created_to_process_formatted, detail.created_at_ts, process.finished_at_ts, 'segment-info', bounds));

                const deliveryTarget = detail.delivery_date_ts ?? bounds.end;
                rows.push(buildTimelineRow(i18n.timelineLegendProcessDelivery, deliveryTarget && process.finished_at_ts ? Math.max(0, deliveryTarget - process.finished_at_ts) : null, detail.delivery_date ? detail.delivery_date : '-', process.finished_at_ts, deliveryTarget, 'segment-warning', bounds));

                const hasValidRow = rows.some(row => !row.includes('timeline-row disabled'));

                if (!hasValidRow) {
                    return '';
                }

                return `<div class="process-timeline">
                            <div class="process-timeline-title"><i class="fas fa-stream text-primary"></i>${i18n.timelineProcessPath}</div>
                            ${rows.join('')}
                        </div>`;
            }

            const table = $('#production-times-table').DataTable({
                processing: true,
                serverSide: false,
                responsive: true,
                ajax: {
                    url: routes.data,
                    data: function () {
                        return collectFilters();
                    },
                    dataSrc: function (json) {
                        try { console.group('PT DataTables data'); console.log('response', json); console.log('summary', json.summary); console.groupEnd(); } catch(e) {}
                        updateSummary(json.summary);
                        updateChart(json.summary?.process_by_code || {});
                        return json.data || [];
                    }
                },
                columns: [
                    {
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: function () {
                            return '<button class="btn btn-sm btn-outline-secondary details-control"><i class="fas fa-chevron-down"></i></button>';
                        }
                    },
                    { data: 'order_id' },
                    { data: 'customer_client_name', defaultContent: '-' },
                    { data: 'fecha_pedido_erp', defaultContent: '-' },
                    { data: 'created_at', defaultContent: '-' },
                    { data: 'finished_at', defaultContent: '-' },
                    { data: 'erp_to_created_formatted', defaultContent: '-' },
                    { data: 'erp_to_finished_formatted', defaultContent: '-' },
                    { data: 'created_to_finished_formatted', defaultContent: '-' },
                ],
                order: [[5, 'desc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });


            $('#production-times-table tbody').on('click', 'button.details-control', function () {
                const tr = $(this).closest('tr');
                const row = table.row(tr);
                const icon = $(this).find('i');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                } else {
                    $.get(routes.orderDetail(row.data().id), collectFilters(), function (detail) {
                        try { console.log('PT OrderDetail AJAX', { id: row.data().id, detail }); } catch(e) {}
                        try {
                            const html = renderDetail(detail);
                            row.child(html).show();
                            setTimeout(() => {
                                renderOrderRangeBar(detail);
                                renderAvgRangeBar(detail);
                            }, 0);
                        } catch (err) {
                            try { console.error('renderDetail error', err); } catch(e2) {}
                            row.child('<div class="text-danger small">{{ __('Error al renderizar el detalle') }}</div>').show();
                        }
                        tr.addClass('shown');
                        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    }).fail(function (xhr) {
                        try { console.error('PT OrderDetail AJAX fail', xhr?.status, xhr?.responseText); } catch(e) {}
                    });
                }
            });

            $('#apply-filters').on('click', function () {
                const start = $('#date_start').val();
                const end = $('#date_end').val();

                if (start && end) {
                    const startDate = new Date(start);
                    const endDate = new Date(end);
                    const diffInMs = endDate - startDate;
                    const diffInDays = diffInMs / (1000 * 60 * 60 * 24);

                    if (diffInDays > 30) {
                        Swal.fire({
                            icon: 'info',
                            title: '{{ __('Rango de fechas extenso') }}',
                            html: `<p class="mb-2">{{ __('Has seleccionado un intervalo superior a 30 días.') }}</p>
                                   <p class="mb-2">{{ __('Las consultas tan amplias pueden generar respuestas incompletas de la IA debido al límite de tokens.') }}</p>
                                   <p class="mb-0">{{ __('¿Deseas continuar de todos modos?') }}</p>`,
                            showCancelButton: true,
                            confirmButtonText: '{{ __('Sí, continuar') }}',
                            cancelButtonText: '{{ __('Cancelar') }}'
                        }).then(result => {
                            if (result.isConfirmed) {
                                table.ajax.reload();
                            }
                        });
                        return;
                    }
                }

                table.ajax.reload();
            });

            function collectFilters() {
                return {
                    date_start: $('#date_start').val(),
                    date_end: $('#date_end').val(),
                    only_finished_orders: $('#only_finished_orders').is(':checked') ? 1 : 0,
                    only_finished_processes: $('#only_finished_processes').is(':checked') ? 1 : 0,
                    use_actual_delivery: $('#use_actual_delivery').is(':checked') ? 1 : 0
                };
            }

            function updateSummary(summary) {
                $('#kpi-orders-total').text(summary?.orders_total ?? 0);
                $('#kpi-processes-total').text(summary?.processes_total ?? 0);
                $('#kpi-erp-finish').text(formatSeconds(summary?.orders_avg_erp_to_finished));
                $('#kpi-gap').text(formatSeconds(summary?.process_avg_gap));
            }

            let processChart = null;

            function updateChart(processData) {
                const chartContainer = document.querySelector('#process-summary-chart');
                if (!chartContainer || typeof ApexCharts === 'undefined') {
                    return;
                }

                if (processChart) {
                    processChart.destroy();
                    processChart = null;
                }

                const items = Object.keys(processData).map(code => {
                    const d = Number(processData[code]?.avg_duration ?? 0) / 3600;
                    const g = Number(processData[code]?.avg_gap ?? 0) / 3600;
                    return { code, d, g, t: d + g };
                }).sort((a, b) => b.t - a.t);

                const categories = items.map(x => x.code);
                const avgDurations = items.map(x => Number(x.d.toFixed(3)));
                const avgGaps = items.map(x => Number(x.g.toFixed(3)));
                const chartHeight = Math.max(420, categories.length * 32 + 160);

                if (!categories.length) {
                    chartContainer.innerHTML = '<div class="text-muted text-center py-4">{{ __('Sin datos de procesos para el rango seleccionado') }}</div>';
                    return;
                }

                chartContainer.innerHTML = '';

                const options = {
                    chart: {
                        type: 'bar',
                        height: chartHeight,
                        stacked: false,
                        zoom: { enabled: true, type: 'xy', autoScaleYaxis: true },
                        toolbar: {
                            show: true,
                            offsetX: 0,
                            offsetY: 0,
                            tools: {
                                download: true,
                                selection: true,
                                zoom: true,
                                zoomin: true,
                                zoomout: true,
                                pan: true,
                                reset: true,
                            },
                            export: {
                                csv: { filename: 'comparativa_proceso' },
                                svg: { filename: 'comparativa_proceso' },
                                png: { filename: 'comparativa_proceso' }
                            }
                        },
                        animations: { enabled: true },
                        dropShadow: { enabled: false },
                        sparkline: { enabled: false },
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: '70%'
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function (val) {
                            if (val === null || typeof val === 'undefined') return '';
                            return (Math.round(val * 100) / 100) + ' h';
                        },
                        style: { fontSize: '11px', fontWeight: 600 }
                    },
                    states: {
                        normal: { filter: { type: 'none' } },
                        hover: { filter: { type: 'none' } },
                        active: { filter: { type: 'none' } },
                    },
                    stroke: { width: 1, colors: ['#fff'] },
                    fill: { type: 'solid' },
                    legend: { position: 'top', horizontalAlign: 'left', markers: { radius: 2 } },
                    grid: { strokeDashArray: 3 },
                    series: [
                        { name: '{{ __('Duración') }}', data: avgDurations },
                        { name: '{{ __('Gap') }}', data: avgGaps },
                    ],
                    colors: ['#118DFF', '#F2C811'],
                    xaxis: {
                        categories,
                        labels: { formatter: (val) => (val + ' h') }
                    },
                    yaxis: {
                        labels: {
                            style: { fontSize: '12px' }
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                if (val === null || typeof val === 'undefined') return '-';
                                const secs = Number(val) * 3600;
                                const h = Math.floor(secs / 3600);
                                const m = Math.floor((secs % 3600) / 60);
                                const s = Math.floor(secs % 60);
                                return (h.toString().padStart(2,'0') + ':' + m.toString().padStart(2,'0') + ':' + s.toString().padStart(2,'0'));
                            }
                        }
                    }
                };

                try {
                    processChart = new ApexCharts(chartContainer, options);
                    processChart.render();
                } catch (e) {
                    console.error('ApexCharts render error', e);
                    chartContainer.innerHTML = '<div class="text-danger small">{{ __('No se pudo renderizar el gráfico') }}</div>';
                }
            }

            function renderDetail(detail) {
                if (!detail) {
                    return '<div class="p-3 text-muted">{{ __('Sin datos de detalle') }}</div>';
                }

                try { console.group('PT Detail'); console.log('detail', detail); } catch(e) {}

                const erpDate = detail.fecha_pedido_erp ?? null;
                const createdAt = detail.created_at ?? null;
                const finishedAt = detail.finished_at ?? null;
                const processes = Array.isArray(detail.processes) ? detail.processes : [];
                const useActualDelivery = detail.use_actual_delivery ?? false;

                const kpiCards = [
                    {
                        icon: 'fa-clipboard-list',
                        title: i18n.erpToCreated,
                        value: detail.erp_to_created_formatted ?? '-',
                        subtext: erpDate ? `${i18n.erpRegistered}: ${erpDate}` : i18n.noErpDate
                    },
                    {
                        icon: 'fa-flag-checkered',
                        title: i18n.erpToFinished,
                        value: detail.erp_to_finished_formatted ?? '-',
                        subtext: finishedAt ? `${i18n.finishedAt}: ${finishedAt}` : i18n.noProcesses
                    },
                    {
                        icon: 'fa-industry',
                        title: i18n.createdToFinished,
                        value: detail.created_to_finished_formatted ?? '-',
                        subtext: createdAt ? `${i18n.createdAt}: ${createdAt}` : ''
                    },
                    {
                        icon: 'fa-cogs',
                        title: i18n.processes,
                        value: processes.length,
                        subtext: processes.length === 1 ? '{{ __('1 proceso registrado') }}' : `{{ __('%count% procesos registrados', ['count' => '']) }}`.replace('%count%', processes.length)
                    }
                ];

                let html = '<div class="details-row p-3 p-lg-4">';
                html += '<div class="row g-3 g-lg-4">';

                kpiCards.forEach(card => {
                    html += `
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="detail-kpi-card">
                                <div class="detail-icon"><i class="fas ${card.icon}"></i></div>
                                <h6>${card.title}</h6>
                                <div class="detail-value">${card.value}</div>
                                <div class="detail-subtext">${card.subtext ?? ''}</div>
                            </div>
                        </div>`;
                });

                html += '</div>';

                const timelineData = detail.order_timeline ?? {};
                const avgTimeline = detail.average_timeline ?? {};
                const orderBounds = timelineData.bounds ?? null;
                const avgBounds = avgTimeline.bounds ?? null;
                try { console.log('order_timeline', timelineData); console.log('average_timeline', avgTimeline); } catch(e) {}

                const ob = orderBounds || getTimelineBounds(detail);
                const orderTimelineRows = [
                    buildTimelineRow(
                        i18n.timelineLegendErpCreated,
                        timelineData.erp_to_created_seconds ?? detail.erp_to_created_seconds,
                        timelineData.erp_to_created_formatted ?? detail.erp_to_created_formatted,
                        timelineData.erp_start_ts ?? detail.fecha_pedido_erp_ts,
                        timelineData.created_end_ts ?? detail.created_at_ts,
                        'segment-primary',
                        ob
                    ),
                    buildTimelineRow(
                        i18n.timelineLegendCreatedFinished,
                        timelineData.created_to_finished_seconds ?? detail.created_to_finished_seconds,
                        timelineData.created_to_finished_formatted ?? detail.created_to_finished_formatted,
                        timelineData.created_start_ts ?? detail.created_at_ts,
                        timelineData.finished_end_ts ?? detail.finished_at_ts,
                        'segment-success',
                        ob
                    ),
                    buildTimelineRow(
                        useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery,
                        timelineData.finished_to_delivery_seconds ?? (detail.delivery_date_ts && detail.finished_at_ts ? Math.max(0, (detail.delivery_date_ts - detail.finished_at_ts)) : null),
                        timelineData.finished_to_delivery_formatted ?? (detail.delivery_date ?? '-'),
                        timelineData.finished_start_ts ?? detail.finished_at_ts,
                        timelineData.delivery_end_ts ?? detail.delivery_date_ts,
                        'segment-warning',
                        ob
                    ),
                ].join('');

                const avgTimelineRows = [
                    buildTimelineRow(i18n.timelineLegendErpCreated, avgTimeline.erp_to_created_seconds, avgTimeline.erp_to_created_formatted, avgTimeline.erp_start_ts, avgTimeline.created_end_ts, 'segment-primary', avgBounds),
                    buildTimelineRow(i18n.timelineLegendCreatedFinished, avgTimeline.created_to_finished_seconds, avgTimeline.created_to_finished_formatted, avgTimeline.created_start_ts, avgTimeline.finished_end_ts, 'segment-success', avgBounds),
                    buildTimelineRow(useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery, avgTimeline.finished_to_delivery_seconds, avgTimeline.finished_to_delivery_formatted, avgTimeline.finished_start_ts, avgTimeline.delivery_end_ts, 'segment-warning', avgBounds),
                ].join('');

                const orderAxis = ob
                    ? `<div class="timeline-axis"><span>${(orderBounds?.start_label ?? detail.fecha_pedido_erp ?? detail.created_at ?? i18n.timelineStart)}</span><span>${(orderBounds?.end_label ?? detail.delivery_date ?? detail.finished_at ?? i18n.timelineEnd)}</span></div>`
                    : `<div class="text-muted small">${i18n.timelineNoData}</div>`;

                const avgAxis = avgBounds
                    ? `<div class="timeline-axis"><span>${avgBounds.start_label ?? i18n.timelineStart}</span><span>${avgBounds.end_label ?? i18n.timelineEnd}</span></div>`
                    : `<div class="text-muted small">${i18n.timelineNoData}</div>`;

                html += `
                    <div class="mt-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="timeline-card">
                                    <div class="timeline-header d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">${i18n.timelineOrdersTitle}</h6>
                                        <div class="timeline-legend">
                                            <span><span class="legend-dot segment-primary"></span>${i18n.timelineLegendErpCreated}</span>
                                            <span><span class="legend-dot segment-success"></span>${i18n.timelineLegendCreatedFinished}</span>
                                            <span><span class="legend-dot segment-warning"></span>${useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery}</span>
                                        </div>
                                    </div>
                                    <div id="order-rangebar-${detail.id ?? detail.order_id}" style="height: 280px;"></div>
                                    <div class="css-timeline-rows d-none">${orderTimelineRows}${orderAxis}</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="timeline-card">
                                    <div class="timeline-header d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">${i18n.timelineOrdersAverageTitle}</h6>
                                        <div class="timeline-legend">
                                            <span><span class="legend-dot segment-primary"></span>${i18n.timelineLegendErpCreated}</span>
                                            <span><span class="legend-dot segment-success"></span>${i18n.timelineLegendCreatedFinished}</span>
                                            <span><span class="legend-dot segment-warning"></span>${useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery}</span>
                                        </div>
                                    </div>
                                    <div id="avg-rangebar-${detail.id ?? detail.order_id}" style="height: 240px;"></div>
                                    <div class="css-timeline-rows d-none">${avgTimelineRows}${avgAxis}</div>
                                    ${(() => {
                                        const ab = avgBounds;
                                        const ce = typeof avgTimeline.created_end_ts === 'number' ? avgTimeline.created_end_ts : null;
                                        const fe = typeof avgTimeline.finished_end_ts === 'number' ? avgTimeline.finished_end_ts : null;
                                        const de = typeof avgTimeline.delivery_end_ts === 'number' ? avgTimeline.delivery_end_ts : null;
                                        if (!ab || ce == null || fe == null || de == null) return '';
                                        const rows = [
                                            buildTimelineRow('ERP → Fin', fe, formatSeconds(fe), 0, fe, 'segment-success', ab),
                                            buildTimelineRow(useActualDelivery ? 'ERP → Entrega real' : 'ERP → Entrega', de, formatSeconds(de), 0, de, 'segment-warning', ab),
                                            buildTimelineRow('Creado → Fin', Math.max(0, fe - ce), formatSeconds(Math.max(0, fe - ce)), ce, fe, 'segment-success', ab),
                                            buildTimelineRow(useActualDelivery ? 'Creado → Entrega real' : 'Creado → Entrega', Math.max(0, de - ce), formatSeconds(Math.max(0, de - ce)), ce, de, 'segment-warning', ab),
                                        ].join('');
                                        return `<div class="mt-2">${rows}</div>`;
                                    })()}
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                try { console.groupEnd('PT Detail'); } catch(e) {}

                html += '<div class="row g-4 mt-1 align-items-stretch">';
                html += `<div class="col-lg-5">
                            <div class="process-detail-wrapper h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-uppercase">${i18n.timelineTitle}</h6>
                                    <span class="badge bg-light text-primary">${processes.length} ${i18n.processes.toLowerCase()}</span>
                                </div>
                                <div id="process-timeline-${detail.id ?? detail.order_id}" class="mini-process-chart d-flex justify-content-center align-items-center text-muted small">${i18n.loadingChart}</div>
                            </div>
                         </div>`;

                html += `<div class="col-lg-7">
                            <div class="process-detail-wrapper h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">${i18n.detailTitle}</h6>
                                    <span class="badge bg-primary bg-opacity-10 text-primary"><i class="fas fa-hashtag me-1"></i>${detail.order_id ?? '-'}</span>
                                </div>`;

                if (!processes.length) {
                    html += `<div class="text-center text-muted py-4">${i18n.noProcesses}</div>`;
                } else {
                    processes.forEach((process, index) => {
                        const badges = [];
                        if (process.duration_formatted) {
                            badges.push(`<span class="badge bg-primary bg-opacity-10 text-primary"><i class="fas fa-stopwatch me-1"></i>${process.duration_formatted}</span>`);
                        }
                        if (process.gap_formatted) {
                            badges.push(`<span class="badge bg-warning bg-opacity-10 text-warning"><i class="fas fa-hourglass-half me-1"></i>${process.gap_formatted}</span>`);
                        }

                        html += `
                            <div class="process-card">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <div class="process-title">${process.process_code ?? '-'} • ${process.process_name ?? '-'}</div>
                                        <div class="process-metadata mt-1">
                                            <i class="fas fa-layer-group me-1 text-primary"></i>${i18n.position}: ${index + 1}
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar-alt me-1 text-muted"></i>${process.finished_at ?? '-'}
                                        </div>
                                    </div>
                                    <div class="d-flex process-badges flex-wrap">${badges.join('')}</div>
                                </div>
                                <div class="process-metadata mt-2">
                                    <i class="fas fa-route me-1 text-success"></i>${i18n.erpToProcess}: <strong>${process.erp_to_process_formatted ?? '-'}</strong>
                                    <span class="mx-2">•</span>
                                    <i class="fas fa-project-diagram me-1 text-info"></i>${i18n.createdToProcess}: <strong>${process.created_to_process_formatted ?? '-'}</strong>
                                </div>
                                ${generateProcessTimeline(detail, process)}
                            </div>`;
                    });
                }

                html += '</div></div></div>';

                html += '<div class="mt-3"><small class="text-muted">' + i18n.orderId + `: ${detail.order_id ?? '-'}</small></div>`;
                html += '</div>';

                setTimeout(() => {
                    renderProcessChart(detail);
                }, 0);

                return html;
            }

            function renderProcessChart(detail) {
                if (typeof ApexCharts === 'undefined') {
                    console.warn('ApexCharts no disponible');
                    setTimeout(() => {
                        renderOrderRangeBar(detail);
                        renderAvgRangeBar(detail);
                        renderProcessTimelineChart(processes, `process-timeline-${detail.id ?? detail.order_id}`);
                    }, 0);
                    return;
                }

                const containerId = `process-timeline-${detail.id ?? detail.order_id}`;
                const container = document.getElementById(containerId);
                if (!container) {
                    return;
                }

                const processes = Array.isArray(detail.processes) ? detail.processes : [];
                if (!processes.length) {
                    container.innerHTML = `<div class="text-muted">${i18n.chartNoData}</div>`;
                    return;
                }

                const categories = processes.map((process, index) => `${index + 1}. ${process.process_code ?? '-'}`);
                const durations = processes.map(process => process.duration_seconds ?? null);
                const gaps = processes.map(process => process.gap_seconds ?? null);

                if (!durations.some(Boolean) && !gaps.some(Boolean)) {
                    container.innerHTML = `<div class="text-muted">${i18n.chartNoData}</div>`;
                    return;
                }

                container.innerHTML = '';

                const options = {
                    chart: {
                        type: 'bar',
                        height: 240,
                        stacked: false,
                        toolbar: {
                            show: true,
                            tools: {
                                download: true,
                                selection: true,
                                zoom: true,
                                zoomin: true,
                                zoomout: true,
                                pan: true,
                                reset: true
                            }
                        }
                    },
                    series: [
                        {
                            name: i18n.duration,
                            data: durations.map(value => value ? Number((value / 60).toFixed(2)) : 0)
                        },
                        {
                            name: i18n.gap,
                            data: gaps.map(value => value ? Number((value / 60).toFixed(2)) : 0)
                        }
                    ],
                    colors: ['#0d6efd', '#ffc107'],
                    dataLabels: { enabled: false },
                    xaxis: {
                        categories: categories,
                        labels: {
                            style: {
                                fontSize: '12px'
                            }
                        },
                        axisBorder: { show: false },
                        axisTicks: { show: false }
                    },
                    yaxis: {
                        labels: {
                            formatter: value => `${value}${i18n.minutesSuffix}`
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'left',
                        markers: {
                            radius: 12
                        }
                    },
                    grid: {
                        borderColor: '#f1f5f9',
                        strokeDashArray: 4
                    }
                };

                try {
                    const chart = new ApexCharts(container, options);
                    chart.render();
                } catch (error) {
                    console.error('ApexCharts timeline render error', error);
                    container.innerHTML = `<div class="text-danger small">${i18n.chartUnavailable}</div>`;
                }
            }

            const isFiniteNumber = (value) => typeof value === 'number' && Number.isFinite(value);

            const resolveMilliseconds = (value) => {
                if (value instanceof Date) {
                    const ms = value.getTime();
                    return Number.isFinite(ms) ? ms : null;
                }
                if (typeof value === 'string') {
                    const parsed = Date.parse(value);
                    if (Number.isFinite(parsed)) {
                        return parsed;
                    }
                    const numeric = Number(value);
                    return Number.isFinite(numeric) ? numeric : null;
                }
                if (typeof value === 'number') {
                    return Number.isFinite(value) ? value : null;
                }
                return null;
            };

            const formatDurationHms = (seconds) => {
                if (!isFiniteNumber(seconds)) {
                    return '--:--:--';
                }
                const negative = seconds < 0;
                const total = Math.abs(Math.round(seconds));
                const h = Math.floor(total / 3600);
                const m = Math.floor((total % 3600) / 60);
                const s = total % 60;
                const formatted = [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
                return negative ? `-${formatted}` : formatted;
            };

            const formatSeconds = (seconds) => {
                if (seconds === null || seconds === undefined) {
                    return '-';
                }
                return formatDurationHms(seconds);
            };

            const formatRangeTooltip = (val, opts, isDatetimeScale) => {
                try {
                    const series = opts?.w?.config?.series;
                    if (!series) return null;
                    const dataPoint = series?.[opts.seriesIndex]?.data?.[opts.dataPointIndex];
                    const y = dataPoint?.y;
                    if (Array.isArray(y) && y.length >= 2) {
                        const start = resolveMilliseconds(y[0]);
                        const end = resolveMilliseconds(y[1]);
                        if (isFiniteNumber(start) && isFiniteNumber(end) && end > start) {
                            const secs = (end - start) / 1000;
                            return formatDurationHms(secs);
                        }
                    }
                    if (!isDatetimeScale && isFiniteNumber(val)) {
                        return formatDurationHms(val);
                    }
                } catch (e) {}
                return null;
            };

            // ============================================
            // FUNCIONALIDAD DE ANÁLISIS IA
            // ============================================
            const AI_URL = "{{ config('services.ai.url') }}";
            const AI_TOKEN = "{{ config('services.ai.token') }}";

            function collectAiContext() {
                const table = $('#production-times-table').DataTable();
                
                const tableInfo = {
                    totalRecords: table ? table.page.info().recordsTotal : 0,
                    filteredRecords: table ? table.page.info().recordsDisplay : 0,
                    currentPage: table ? table.page.info().page + 1 : 1,
                    totalPages: table ? table.page.info().pages : 0
                };
                
                let tableData = [];
                let columnNames = [];
                
                if (table) {
                    table.columns().header().each(function(header) {
                        const colName = $(header).text().trim();
                        if (colName && !colName.toLowerCase().includes('acciones') && !colName.toLowerCase().includes('actions')) {
                            columnNames.push(colName);
                        }
                    });
                    
                    table.rows({search: 'applied'}).nodes().each(function(rowNode, index) {
                        const row = {};
                        const $row = $(rowNode);
                        let colIndexForData = 0;
                        
                        $row.find('td').each(function(colIndex) {
                            const headerText = $(table.columns().header()[colIndex]).text().trim();
                            
                            if (headerText && !headerText.toLowerCase().includes('acciones') && !headerText.toLowerCase().includes('actions')) {
                                if (colIndexForData < columnNames.length) {
                                    let cellValue = $(this).text().trim();
                                    if (!cellValue) {
                                        cellValue = $(this).attr('data-sort') || '';
                                    }
                                    row[columnNames[colIndexForData]] = cellValue;
                                    colIndexForData++;
                                }
                            }
                        });
                        
                        if (Object.keys(row).length > 0) {
                            tableData.push(row);
                        }
                    });
                    
                    if (tableData.length > 50) {
                        tableData = tableData.slice(0, 50);
                        tableInfo.note = `Mostrando solo las primeras 50 filas de ${tableInfo.filteredRecords} registros filtrados`;
                    }
                }
                
                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    processesTotal: $('#kpi-processes-total').text() || '0',
                    avgErpToFinish: $('#kpi-erp-finish').text() || '-',
                    avgGap: $('#kpi-gap').text() || '-'
                };
                
                const filters = {
                    dateStart: $('#date_start').val(),
                    dateEnd: $('#date_end').val(),
                    onlyFinishedOrders: $('#only_finished_orders').is(':checked'),
                    onlyFinishedProcesses: $('#only_finished_processes').is(':checked')
                };
                
                return { 
                    tableInfo, 
                    tableData,
                    columnNames,
                    metrics, 
                    filters, 
                    page: 'customers/production-times',
                    description: 'Vista de análisis de tiempos de fabricación con métricas de duración de órdenes y procesos, gaps entre procesos y filtros por fecha y tipo de proceso'
                };
            }

            function showAiLoading(show) {
                const btn = document.getElementById('btn-ai-send');
                if (!btn) return;
                btn.disabled = !!show;
                btn.innerText = show ? '{{ __('Enviando...') }}' : '{{ __('Enviar a IA') }}';
            }

            async function startAiTask(fullPrompt, userPromptForDisplay) {
                try {
                    showAiLoading(true);
                    const payload = collectAiContext();
                    console.log('[AI][Production Times] Context:', payload.tableInfo, 'Filters:', payload.filters);
                    let combinedPrompt;
                    try {
                        combinedPrompt = `${fullPrompt}\n\n=== Datos para analizar (JSON) ===\n${JSON.stringify(payload, null, 2)}`;
                    } catch (e) {
                        combinedPrompt = `${fullPrompt}\n\n=== Datos para analizar (JSON) ===\n[Error serializando datos]`;
                    }
                    console.log('[AI] Combined prompt length:', combinedPrompt.length);
                    
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

                    console.log('[AI] Tarea creada con ID:', taskId);

                    let done = false; let last; let pollCount = 0;
                    while (!done) {
                        pollCount++;
                        console.log(`[AI] Polling #${pollCount} - Esperando 5 segundos...`);
                        await new Promise(r => setTimeout(r, 5000));
                        
                        const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                            headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                        });
                        
                        if (pollResp.status === 404) {
                            console.log('[AI] Error: Tarea no encontrada (404)');
                            try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {}
                            return;
                        }
                        if (!pollResp.ok) {
                            throw new Error(`poll failed: ${pollResp.status}`);
                        }
                        
                        last = await pollResp.json();
                        
                        const task = last && last.task ? last.task : null;
                        if (!task) continue;
                        
                        if (task.response == null) {
                            if (task.error && /processing/i.test(task.error)) continue;
                            if (task.error == null) continue;
                        }
                        if (task.error && !/processing/i.test(task.error)) { 
                            alert(task.error); 
                            return; 
                        }
                        if (task.response != null) { 
                            done = true; 
                        }
                    }

                    $('#aiResultPrompt').text(userPromptForDisplay);
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

            // Prompt del sistema para análisis de tiempos de producción
            const systemPrompt = `Actúa como un analista de operaciones y eficiencia en manufactura experto.

**Tarea:** Realiza un **análisis exhaustivo y estructurado** de los datos de tiempos de fabricación proporcionados. La información incluye: **Tiempos ERP → Creado**, **Tiempos ERP → Finalizado**, **Tiempos Creado → Finalizado**, **Duraciones de Procesos**, **Gaps entre Procesos**, y **Grupos de Procesos**.

**Estructura del Informe de Análisis:**

1.  **Resumen Ejecutivo:**
    *   Indica la **tendencia general** de los tiempos de fabricación en el periodo analizado.
    *   Menciona los **principales hallazgos** (ej. "Los gaps entre procesos representan el 30% del tiempo total").

2.  **Análisis Detallado de Patrones:**
    *   **Tiempos de Orden:** Identifica patrones en los tiempos desde ERP hasta finalización. ¿Hay órdenes con tiempos excesivos?
    *   **Análisis de Procesos:**
        *   Determina los **procesos más lentos** y los que tienen mayor variabilidad.
        *   Identifica los **gaps más significativos** entre procesos.
        *   Analiza si hay **cuellos de botella** específicos.
    *   **Análisis por Grupo:** Compara el rendimiento entre diferentes grupos de procesos.

3.  **Propuestas de Soluciones Estratégicas:**
    *   **Reducción de Gaps:** Propón 2-3 acciones para reducir tiempos muertos entre procesos.
    *   **Optimización de Procesos:** Propón 2-3 acciones para mejorar procesos lentos.
    *   **Mejora de Flujo:** Propón acciones para mejorar el flujo general de producción.

4.  **Sugerencias de Próximas Preguntas:**
    *   Formula **tres preguntas clave** de seguimiento para profundizar el análisis.`;

            const defaultUserPrompt = 'Analiza los tiempos de fabricación y dame un informe con propuestas de mejora para reducir los tiempos de producción.';

            $('#aiPromptModal').on('shown.bs.modal', function(){
                const $ta = $('#aiPrompt');
                if (!$ta.val()) $ta.val(defaultUserPrompt);
                $ta.trigger('focus');
            });

            $('#btn-ai-reset').on('click', function(){ $('#aiPrompt').val(defaultUserPrompt); });

            $('#btn-ai-send').on('click', function(){
                const userPrompt = ($('#aiPrompt').val() || '').trim() || defaultUserPrompt;
                const finalPrompt = `${systemPrompt}\n\n**Consulta del Usuario:** ${userPrompt}`;
                startAiTask(finalPrompt, userPrompt);
            });
        });
    </script>

    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i>{{ __('Análisis IA') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6 class="mb-2 text-primary"><i class="fas fa-database me-1"></i>{{ __('Datos que enviamos a la IA con tu consulta') }}</h6>
                        <ul class="mb-0 ps-3">
                            <li><strong>{{ __('Filtros aplicados') }}:</strong> {{ __('rango de fechas, uso de fecha real de entrega y si solo se incluyen órdenes/procesos finalizados.') }}</li>
                            <li><strong>{{ __('KPIs principales') }}:</strong> {{ __('total de órdenes y procesos analizados, promedio ERP → Fin y promedio de gap entre procesos.') }}</li>
                            <li><strong>{{ __('Tabla resumen') }}:</strong> {{ __('primeras 50 filas visibles en la tabla (ORDER ID, cliente, fechas y duraciones clave).') }}</li>
                            <li><strong>{{ __('Detalles calculados') }}:</strong> {{ __('resultados del análisis por proceso (duraciones y gaps por código).') }}</li>
                        </ul>
                    </div>
                    <label class="form-label">{{ __('¿Qué necesitas analizar?') }}</label>
                    <textarea class="form-control" id="aiPrompt" rows="4" placeholder="{{ __('Describe qué insight quieres obtener (p. ej. comparativa de gaps, pedidos con mayor retraso, recomendaciones, etc.)') }}"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">{{ __('Limpiar prompt por defecto') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send">{{ __('Enviar a IA') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Result Modal -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Resultado IA') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted"><strong>{{ __('Prompt') }}:</strong> <span id="aiResultPrompt"></span></p>
                    <pre id="aiResultData" class="bg-light p-3 rounded" style="white-space: pre-wrap;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endpush
