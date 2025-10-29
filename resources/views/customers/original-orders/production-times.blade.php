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
                                        {{ __('Fecha de entrega teorica (ERP) / Fecha de entrega real(modulo Logistica)') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="exclude_incomplete_orders" checked>
                                <label class="form-check-label" for="exclude_incomplete_orders">{{ __('Excluir órdenes sin fechas completas') }}</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-4 mb-4" id="kpi-cards">
                <div class="col-12 col-sm-6 col-lg-3">
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
                <div class="col-12 col-sm-6 col-lg-3">
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
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-stopwatch fa-2x text-info"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio Recepción Pedido → Pedido Finalizado') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-finish">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-clock fa-2x text-secondary"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Mediana Recepción Pedido → Pedido Finalizado') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-finish-median">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-chart-line fa-2x text-warning"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio Tiempo de Espera Operacion / Máquina') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-gap">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-equals fa-2x text-muted"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Mediana Tiempo de Espera Operacion / Máquina') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-gap-median">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-industry fa-2x text-success"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio lanzamiento producción → fin orden') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-created-finish">-</h2>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-stopwatch fa-2x text-info"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Mediana lanzamiento producción → fin orden') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-created-finish-median">-</h2>
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
                                <button type="button" class="btn btn-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Análisis con IA') }}">
                                    <i class="bi bi-stars me-1 text-white"></i><span class="d-none d-sm-inline">{{ __('Análisis IA') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><h6 class="dropdown-header"><i class="fas fa-brain me-1"></i> {{ __("Tipo de Análisis") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="erp-to-created">
                                        <i class="fas fa-hourglass-start text-info me-2"></i>{{ __("Tiempo Recepción Pedido → Puesto en Fabricación") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="created-to-finished">
                                        <i class="fas fa-industry text-success me-2"></i>{{ __("Tiempo Puesto en Fabricación → Pedido Finalizado") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="finish-to-delivery">
                                        <i class="fas fa-truck-loading text-warning me-2"></i>{{ __("Pedido Finalizado → Entrega") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="process-gaps">
                                        <i class="fas fa-project-diagram text-warning me-2"></i>{{ __("Gaps entre Procesos") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="gap-alerts">
                                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>{{ __("Alertas de Brechas") }}
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="by-client">
                                        <i class="fas fa-users text-primary me-2"></i>{{ __("Análisis por Cliente") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="order-type-critical">
                                        <i class="fas fa-cubes text-secondary me-2"></i>{{ __("Órdenes por Tipo") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="slow-processes">
                                        <i class="fas fa-turtle text-danger me-2"></i>{{ __("Procesos Lentos") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="top-bottom">
                                        <i class="fas fa-balance-scale text-secondary me-2"></i>{{ __("Comparativa Top/Bottom") }}
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="full">
                                        <i class="fas fa-layer-group text-dark me-2"></i>{{ __("Análisis Total") }}
                                    </a></li>
                                </ul>
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
                                    <th>{{ __('Pedido') }}</th>
                                    <th>{{ __('Cliente') }}</th>
                                    <th>{{ __('Fecha Recepción Pedido') }}</th>
                                    <th>{{ __('Fecha Puesto en Fabricación') }}</th>
                                    <th>{{ __('Fecha Pedido Finalizado') }}</th>
                                    <th>{{ __('Tiempo Recepción Pedido → Puesto en Fabricación') }}</th>
                                    <th>{{ __('Tiempo Puesto en Fabricación → Pedido Finalizado') }}</th>
                                    <th>{{ __('Tiempo Recepción Pedido → Pedido Finalizado') }}</th>
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
                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-clipboard-list"></i></span>{{ __('Tiempo Recepción Pedido → Puesto en Fabricación') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo desde que el pedido se registra en el ERP hasta que entra en producción. Se representa en azul en el timeline interactivo y alimenta el cálculo promedio.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-success bg-opacity-10 text-success me-2"><i class="fas fa-flag-checkered"></i></span>{{ __('Tiempo Recepción Pedido → Pedido Finalizado') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Duración total del pedido desde el ERP hasta la finalización. Ayuda a identificar cuellos de botella globales y se refleja como suma de los tramos azul y verde.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-industry"></i></span>{{ __('Tiempo Recepción Pedido → Pedido Finalizado') }}</dt>
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

                                <dt class="col-sm-5 mb-3"><span class="badge bg-warning bg-opacity-10 text-warning me-2"><i class="fas fa-hourglass-half"></i></span>{{ __('Tiempo de espera entre procesos') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo de espera entre un proceso y el siguiente. Cuanto mayor es el tiempo de espera, más tiempo estuvo detenida la orden fuera de la producción activa.') }}</dd>

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
        .detail-kpi-inline {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            min-width: 220px;
        }
        .detail-kpi-inline .detail-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            font-size: 1rem;
        }
        .detail-kpi-inline .detail-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        .detail-kpi-inline .detail-title {
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .detail-kpi-inline .detail-value {
            font-weight: 700;
            color: #0f172a;
            font-size: 1.1rem;
        }
        .detail-kpi-inline .detail-subtext {
            font-size: 0.75rem;
            color: #64748b;
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
        .timeline-summary .timeline-row {
            flex-wrap: nowrap;
            gap: 0.5rem;
        }
        .timeline-summary .timeline-label {
            min-width: 160px;
            font-weight: 600;
            color: #0f172a;
        }
        .timeline-summary .timeline-bar {
            display: none;
        }
        .timeline-summary .timeline-value {
            font-weight: 700;
            color: #0f172a;
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
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.1.4/dist/purify.min.js"></script>
    <script>
        $(function () {
            const routes = {
                data: "{{ route('customers.production-times.data', $customer) }}",
                summary: "{{ route('customers.production-times.summary', $customer) }}",
                orderDetail: (orderId) => "{{ route('customers.production-times.order', [$customer, ':orderId']) }}".replace(':orderId', orderId)
            };

            const i18n = {
                erpToCreated: @json(__('Tiempo Recepción Pedido → Puesto en Fabricación')),
                erpToFinished: @json(__('Tiempo Recepción Pedido → Pedido Finalizado')),
                createdToFinished: @json(__('Tiempo Recepción Pedido → Pedido Finalizado')),
                processes: @json(__('Procesos')),
                erpRegistered: @json(__('Recepción Pedido')),
                noErpDate: @json(__('Sin fecha de recepción registrada')),
                createdAt: @json(__('Puesto en Fabricación')),
                finishedAt: @json(__('Pedido Finalizado')),
                position: @json(__('Posición')),
                duration: @json(__('Duración')),
                gap: @json(__('Gap')),
                erpToProcess: @json(__('Recepción Pedido → Proceso')),
                createdToProcess: @json(__('Puesto en Fabricación → Proceso')),
                timelineTitle: @json(__('Timeline de procesos')),
                timelineDetail: @json(__('Detalle de procesos')),
                noProcesses: @json(__('Sin procesos registrados para esta orden')),
                chartNoData: @json(__('Sin datos de procesos para generar el gráfico')),
                chartUnavailable: @json(__('No se pudo inicializar el gráfico')),
                loadingChart: @json(__('Generando gráfico...')),
                minutesSuffix: @json(__('min')),
                orderId: @json(__('Pedido')),
                timelineOrdersTitle: @json(__('Cronología de fechas')),
                timelineFromErp: @json(__('Ruta desde Recepción Pedido')),
                timelineFromCreated: @json(__('Ruta desde Puesto en Fabricación')),
                timelineLegendErpCreated: @json(__('Tiempo Recepción Pedido →  Puesto en Fabricación')),
                timelineLegendCreatedFinished: @json(__('Tiempo Recepción Pedido → Pedido Finalizado')),
                timelineLegendFinishedDelivery: @json(__('Pedido Finalizado → Entrega')),
                timelineLegendCreatedProcess: @json(__('Tiempo Puesto en Fabricación → Proceso')),
                timelineLegendProcessDelivery: @json(__('Tiempo Proceso → Entrega')),
                timelineProcessPath: @json(__('Ruta al proceso')),
                timelineNoData: @json(__('Sin datos suficientes para mostrar la cronología')),
                timelineStart: @json(__('Inicio')),
                timelineEnd: @json(__('Fin')),
                erpDateLabel: @json(__('Fecha Recepción Pedido')),
                createdDateLabel: @json(__('Puesto en Fabricación')),
                finishedDateLabel: @json(__('Fecha Pedido Finalizado')),
                deliveryDateLabel: @json(__('Fecha de entrega prevista')),
                actualDeliveryLabel: @json(__('Fecha de entrega real')),
                toggleActualDeliveryLabel: @json(__('Usar fecha real de entrega (actual_delivery_date) en lugar de fecha ERP programada')),
                timelineLegendFinishedActualDelivery: @json(__('Pedido Finalizado → Entrega real')),
                timelineLegendProcessActualDelivery: @json(__('Proceso → Entrega real')),
                timelineOrdersAverageTitle: @json(__('Promedio del rango')),
                timelineOrdersMedianTitle: @json(__('Mediana del rango'))
            };

            const normalizeTimelineLabel = (raw) => {
                if (!raw || typeof raw !== 'string') return '-';
                return raw
                    .replace('ERP → Creado', '{{ __('Tiempo Recepción Pedido → Puesto en Fabricación') }}')
                    .replace('Tiempo Recepción Pedido → Pedido Finalizado', '{{ __('Tiempo Recepción Pedido → Pedido Finalizado') }}')
                    .replace('Fin → Entrega real', '{{ __('Pedido Finalizado → Entrega real') }}')
                    .replace('Fin → Entrega', '{{ __('Pedido Finalizado → Entrega') }}');
            };

            const computeDurationLabel = (start, end, isDatetime) => {
                const s = Number(start);
                const e = Number(end);
                if (!Number.isFinite(s) || !Number.isFinite(e) || e <= s) {
                    return '{{ __('Sin datos disponibles') }}';
                }
                const diff = isDatetime ? (e - s) / 1000 : (e - s);
                return '{{ __('Duración') }}: ' + formatDurationHms(diff);
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
                    points.push({ x: 'Tiempo Recepción Pedido → Puesto en Fabricación', y: [erp * 1000, created * 1000], fillColor: '#118DFF' });
                }
                if (typeof created === 'number' && typeof finished === 'number' && finished > created) {
                    points.push({ x: 'Tiempo Recepción Pedido → Pedido Finalizado', y: [created * 1000, finished * 1000], fillColor: '#21A366' });
                }
                if (typeof finished === 'number' && typeof delivery === 'number' && delivery > finished) {
                    points.push({ x: (useActual ? 'Pedido Finalizado → Entrega real' : 'Pedido Finalizado → Entrega'), y: [finished * 1000, delivery * 1000], fillColor: '#F2C811' });
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
                        custom: function({ seriesIndex, dataPointIndex, w }) {
                            const point = w?.config?.series?.[seriesIndex]?.data?.[dataPointIndex];
                            if (!point || !Array.isArray(point.y) || point.y.length < 2) {
                                return null;
                            }
                            const label = normalizeTimelineLabel(point.x);
                            const duration = computeDurationLabel(point.y?.[0], point.y?.[1], true);
                            return `<div class="p-2"><strong>${label}</strong><br/>${duration}</div>`;
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

            function renderMedianRangeBar(detail) {
                const key = detail.id ?? detail.order_id;
                const el = document.querySelector(`#median-rangebar-${key}`);
                if (!el || typeof ApexCharts === 'undefined') return;

                const median = detail.median_timeline ?? {};
                const useActual = !!(detail.use_actual_delivery);

                const c1s = 0;
                const c1e = median.created_end_ts ?? 0;
                const c2s = median.created_start_ts ?? 0;
                const c2e = median.finished_end_ts ?? 0;
                const c3s = median.finished_start_ts ?? 0;
                const c3e = median.delivery_end_ts ?? 0;

                const points = [];
                if (c1e > c1s) points.push({ x: 'Tiempo Recepción Pedido → Puesto en Fabricación', y: [c1s, c1e], fillColor: '#118DFF' });
                if (c2e > c2s) points.push({ x: 'Tiempo Recepción Pedido → Pedido Finalizado', y: [c2s, c2e], fillColor: '#21A366' });
                if (c3e > c3s) points.push({ x: (useActual ? 'Pedido Finalizado → Entrega real' : 'Pedido Finalizado → Entrega'), y: [c3s, c3e], fillColor: '#F2C811' });

                try { console.log('[MRB] median points', { key, pointsCount: points.length, points }); } catch(e) {}

                if (!points.length) {
                    el.innerHTML = '<div class="text-muted small">' + i18n.timelineNoData + '</div>';
                    return;
                }

                const options = {
                    chart: {
                        type: 'rangeBar',
                        height: 240,
                        width: '100%',
                        id: `median-rangebar-${key}`,
                        toolbar: {
                            show: true,
                            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                            export: { csv: { filename: 'timeline_mediana' }, svg: { filename: 'timeline_mediana' }, png: { filename: 'timeline_mediana' } }
                        },
                        animations: { enabled: true }
                    },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 4 } },
                    series: [{ name: 'Mediana', data: points }],
                    xaxis: {
                        type: 'numeric',
                        labels: {
                            formatter: function(val) {
                                if (!val || val === 0) return '0s';
                                const h = Math.floor(val / 3600);
                                const m = Math.floor((val % 3600) / 60);
                                const s = Math.floor(val % 60);
                                return h.toString().padStart(2,'0') + ':' + m.toString().padStart(2,'0') + ':' + s.toString().padStart(2,'0');
                            }
                        },
                        title: { text: 'Tiempo ' }
                    },
                    dataLabels: { enabled: false },
                    grid: { strokeDashArray: 3 },
                    tooltip: {
                        custom: function({ seriesIndex, dataPointIndex, w }) {
                            const point = w?.config?.series?.[seriesIndex]?.data?.[dataPointIndex];
                            if (!point || !Array.isArray(point.y) || point.y.length < 2) {
                                return null;
                            }
                            const label = normalizeTimelineLabel(point.x);
                            const duration = computeDurationLabel(point.y?.[0], point.y?.[1], false);
                            return `<div class="p-2"><strong>${label}</strong><br/>${duration}</div>`;
                        }
                    }
                };

                const ensureRendered = () => {
                    const w = el.offsetWidth || 0;
                    if (w < 10) {
                        const n = parseInt(el.dataset.retry || '0', 10) + 1;
                        el.dataset.retry = String(n);
                        if (n <= 5) {
                            setTimeout(ensureRendered, 200);
                            return;
                        }
                    }
                    try {
                        const chart = new ApexCharts(el, options);
                        chart.render();
                    } catch (error) {
                        console.error('ApexCharts median render error', error);
                        el.innerHTML = '<div class="text-danger small">Error al renderizar gráfico de mediana</div>';
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
                if (typeof c1e === 'number' && c1e > c1s) points.push({ x: 'Tiempo Recepción Pedido → Puesto en Fabricación', y: [c1s, c1e], fillColor: '#118DFF' });
                if (typeof c2e === 'number' && c2e > c2s) points.push({ x: 'Tiempo Recepción Pedido → Pedido Finalizado', y: [c2s, c2e], fillColor: '#21A366' });
                if (typeof c3e === 'number' && c3e > c3s) points.push({ x: (useActual ? 'Pedido Finalizado → Entrega real' : 'Pedido Finalizado → Entrega'), y: [c3s, c3e], fillColor: '#F2C811' });

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
                        custom: function({ seriesIndex, dataPointIndex, w }) {
                            const point = w?.config?.series?.[seriesIndex]?.data?.[dataPointIndex];
                            if (!point) return '';
                            const label = normalizeTimelineLabel(point.x);
                            const duration = computeDurationLabel(point.y?.[0], point.y?.[1], false);
                            return `<div class="p-2"><strong>${label}</strong><br/>${duration}</div>`;
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

                const useActualDelivery = !!(detail.use_actual_delivery);
                const chosenDeliveryTs = useActualDelivery
                    ? (typeof detail.actual_delivery_date_ts === 'number' ? detail.actual_delivery_date_ts : null)
                    : (typeof detail.delivery_date_ts === 'number' ? detail.delivery_date_ts : null);
                const deliveryLabel = useActualDelivery ? i18n.timelineLegendProcessActualDelivery : i18n.timelineLegendProcessDelivery;

                const procToDeliverySeconds = (typeof chosenDeliveryTs === 'number' && typeof process.finished_at_ts === 'number')
                    ? Math.max(0, chosenDeliveryTs - process.finished_at_ts)
                    : null;
                const procToDeliveryFormatted = (typeof chosenDeliveryTs === 'number' && typeof process.finished_at_ts === 'number')
                    ? computeDurationLabel(process.finished_at_ts, chosenDeliveryTs, false)
                    : '-';
                rows.push(
                    buildTimelineRow(
                        deliveryLabel,
                        procToDeliverySeconds,
                        procToDeliveryFormatted,
                        process.finished_at_ts,
                        chosenDeliveryTs ?? 0,
                        'segment-warning',
                        bounds
                    )
                );

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
                    // Eliminar completamente el child para evitar estados intermedios
                    try { row.child.remove(); } catch(e) { row.child.hide(); }
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
                                renderMedianRangeBar(detail);
                                const $childRow = tr.next('tr');
                                const tooltipEls = $childRow.find('[data-bs-toggle="tooltip"]').toArray();
                                tooltipEls.forEach(el => { try { new bootstrap.Tooltip(el); } catch (e) {} });
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
                    use_actual_delivery: $('#use_actual_delivery').is(':checked') ? 1 : 0,
                    exclude_incomplete_orders: $('#exclude_incomplete_orders').is(':checked') ? 1 : 0
                };
            }

            function updateSummary(summary) {
                latestSummary = summary;
                $('#kpi-orders-total').text(summary?.orders_total ?? 0);
                $('#kpi-processes-total').text(summary?.processes_total ?? 0);
                $('#kpi-erp-finish').text(formatSeconds(summary?.orders_avg_erp_to_finished));
                $('#kpi-erp-finish-median').text(formatSeconds(summary?.orders_p50_erp_to_finished));
                $('#kpi-created-finish').text(formatSeconds(summary?.orders_avg_created_to_finished));
                $('#kpi-created-finish-median').text(formatSeconds(summary?.orders_p50_created_to_finished));
                $('#kpi-gap').text(formatSeconds(summary?.process_avg_gap));
                $('#kpi-gap-median').text(formatSeconds(summary?.process_p50_gap));
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
                html += '<div class="d-flex flex-wrap gap-3">';

                kpiCards.forEach(card => {
                    html += `
                        <div class="detail-kpi-inline">
                            <span class="detail-icon"><i class="fas ${card.icon}"></i></span>
                            <div class="detail-text">
                                <span class="detail-title">${card.title}</span>
                                <span class="detail-value">${card.value}</span>
                                <span class="detail-subtext">${card.subtext ?? ''}</span>
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
                                        const items = [
                                            { label: 'Recepción Pedido → Pedido Finalizado', value: formatSeconds(fe), color: 'segment-success' },
                                            { label: useActualDelivery ? 'Recepción Pedido → Entrega Pedido' : 'Recepción Pedido → Entrega Pedido', value: formatSeconds(de), color: 'segment-warning' },
                                            { label: 'Tiempo Puesto en Producción → Pedido Finalizado', value: formatSeconds(Math.max(0, fe - ce)), color: 'segment-success' },
                                            { label: useActualDelivery ? 'Puesto en Fabricación → Entrega Pedido' : 'Tiempo Puesto en Producción → Entrega Pedido', value: formatSeconds(Math.max(0, de - ce)), color: 'segment-warning' }
                                        ];
                                        return `<div class="timeline-summary-inline mt-3">${items.map(item => `
                                            <div class="timeline-chip">
                                                <span class="legend-dot ${item.color}"></span>
                                                <span class="chip-label">${item.label}</span>
                                                <span class="chip-value">${item.value}</span>
                                            </div>
                                        `).join('')}</div>`;
                                    })()}
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="timeline-card">
                                    <div class="timeline-header d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">${i18n.timelineOrdersMedianTitle}</h6>
                                        <div class="timeline-legend">
                                            <span><span class="legend-dot segment-primary"></span>${i18n.timelineLegendErpCreated}</span>
                                            <span><span class="legend-dot segment-success"></span>${i18n.timelineLegendCreatedFinished}</span>
                                            <span><span class="legend-dot segment-warning"></span>${useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery}</span>
                                        </div>
                                    </div>
                                    <div id="median-rangebar-${detail.id ?? detail.order_id}" style="height: 240px;"></div>
                                    ${(() => {
                                        const medianTimeline = detail.median_timeline ?? {};
                                        const mb = medianTimeline.bounds ?? null;
                                        const ce = typeof medianTimeline.created_end_ts === 'number' ? medianTimeline.created_end_ts : null;
                                        const fe = typeof medianTimeline.finished_end_ts === 'number' ? medianTimeline.finished_end_ts : null;
                                        const de = typeof medianTimeline.delivery_end_ts === 'number' ? medianTimeline.delivery_end_ts : null;
                                        if (!mb || ce == null || fe == null || de == null) return '';
                                        const items = [
                                            { label: 'Tiempo Recepción Pedido → Pedido Finalizado:', value: formatSeconds(fe), color: 'segment-success' },
                                            { label: useActualDelivery ? 'Recepción Pedido  → Entrega Pedido' : 'Recepción Pedido  → Entrega Pedido', value: formatSeconds(de), color: 'segment-warning' },
                                            { label: 'Tiempo Puesto en Producción → Pedido Finalizado: ', value: formatSeconds(Math.max(0, fe - ce)), color: 'segment-success' },
                                            { label: useActualDelivery ? 'Tiempo Puesto en Producción → Entrega Pedido' : 'Tiempo Puesto en Producción → Entrega Pedido:', value: formatSeconds(Math.max(0, de - ce)), color: 'segment-warning' }
                                        ];
                                        return `<div class="timeline-summary-inline mt-3">${items.map(item => `
                                            <div class="timeline-chip">
                                                <span class="legend-dot ${item.color}"></span>
                                                <span class="chip-label">${item.label}</span>
                                                <span class="chip-value">${item.value}</span>
                                            </div>
                                        `).join('')}</div>`;
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
                                </div>`;

                if (!processes.length) {
                    html += `<div class="text-center text-muted py-4">${i18n.noProcesses}</div>`;
                } else {
                    processes.forEach((process, index) => {
                        const badges = [];
                        if (process.duration_formatted) {
                            badges.push(`<span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" data-bs-toggle="tooltip" title="Duración del proceso"><i class="fas fa-stopwatch me-1"></i>${process.duration_formatted}</span>`);
                        }
                        if (process.gap_formatted) {
                            badges.push(`<span class="badge bg-warning text-white fs-6 py-2 px-3 me-2" data-bs-toggle="tooltip" title="Tiempo de espera entre procesos"><i class="fas fa-hourglass-half me-1"></i>${process.gap_formatted}</span>`);
                        }

                        html += `
                            <div class="process-card">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <div class="process-title">${process.process_code ?? '{{ __('Sin código') }}'} • ${process.process_name ?? '{{ __('Sin nombre de proceso') }}'}</div>
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
                        renderMedianRangeBar(detail);
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

            let latestSummary = null;
            
            // Rate limiting simple: max 10 solicitudes por minuto
            let aiRequestHistory = [];
            const MAX_AI_REQUESTS_PER_MINUTE = 10;
            
            function checkAiRateLimit() {
                const now = Date.now();
                const oneMinuteAgo = now - 60000;
                
                // Limpiar solicitudes antiguas
                aiRequestHistory = aiRequestHistory.filter(time => time > oneMinuteAgo);
                
                if (aiRequestHistory.length >= MAX_AI_REQUESTS_PER_MINUTE) {
                    return false;
                }
                
                aiRequestHistory.push(now);
                return true;
            }

            // Función auxiliar para limpiar y escapar valores CSV
            function cleanValue(value) {
                if (value === null || value === undefined) return '';
                let str = String(value).trim();
                if (str === '') return '';
                const needsQuoting = /[",\n\r]/.test(str);
                if (str.includes('"')) {
                    str = str.replace(/"/g, '""');
                }
                return needsQuoting ? `"${str}"` : str;
            }

            function safeValue(value, fallback = '') {
                if (value === null || value === undefined) return fallback;
                const str = String(value).trim();
                if (!str || str === '-' || str === '--' || str.toLowerCase() === 'null' || str.toLowerCase() === 'undefined') {
                    return fallback;
                }
                return str;
            }

            function safeDate(value) {
                const dateStr = safeValue(value, '0000-00-00 00:00:00');
                return dateStr || '0000-00-00 00:00:00';
            }

            // Función para formatear segundos a HH:MM:SS
            function formatTime(seconds) {
                if (seconds === null || seconds === undefined || isNaN(seconds) || seconds === 0) return '00:00:00';
                seconds = parseInt(seconds);
                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);
                const secs = seconds % 60;
                return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            }

            function normalizeDateTime(value) {
                const raw = safeValue(value, '');
                if (!raw || raw === '0000-00-00 00:00:00' || raw === '0000-00-00') return '';
                const trimmed = raw.trim();
                if (!trimmed) return '';
                const normalized = trimmed.replace(' ', 'T');
                if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/.test(normalized)) return normalized;
                if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return `${trimmed}T00:00:00`;
                return normalized;
            }

            function durationToSeconds(value) {
                const raw = safeValue(value, '');
                if (!raw) return '';
                if (/^-?\d+$/.test(raw)) return raw;
                const match = raw.match(/^(-)?(\d{1,3}):(\d{2}):(\d{2})$/);
                if (!match) return '';
                const sign = match[1] === '-' ? -1 : 1;
                const hours = parseInt(match[2], 10);
                const minutes = parseInt(match[3], 10);
                const seconds = parseInt(match[4], 10);
                if (Number.isNaN(hours) || Number.isNaN(minutes) || Number.isNaN(seconds)) return '';
                const total = sign * (hours * 3600 + minutes * 60 + seconds);
                return String(total);
            }

            function formatSignedSeconds(value) {
                const parsed = parseInt(value, 10);
                if (Number.isNaN(parsed) || parsed === 0) return '0';
                return parsed > 0 ? `+${parsed}` : `${parsed}`;
            }

            function formatSignedDuration(value) {
                const parsed = parseInt(value, 10);
                if (Number.isNaN(parsed) || parsed === 0) return '00:00:00';
                const prefix = parsed > 0 ? '+' : '-';
                return `${prefix}${formatTime(Math.abs(parsed))}`;
            }

            // Análisis 1: Tiempo Recepción Pedido → Puesto en Fabricación
            function collectErpToCreatedData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Tiempo Recepción Pedido → Puesto en Fabricación' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgErpToCreated: latestSummary?.orders_avg_erp_to_created ? formatSeconds(latestSummary.orders_avg_erp_to_created) : '-',
                    medianErpToCreated: latestSummary?.orders_p50_erp_to_created ? formatSeconds(latestSummary.orders_p50_erp_to_created) : '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV: Order_ID, Cliente, Fecha_ERP_ISO, Fecha_Creado_ISO, Tiempo_ERP_a_Creado_Segundos, Tiempo_ERP_a_Creado_Formato
                let csv = 'Order_ID,Cliente,Fecha_ERP_ISO,Fecha_Creado_ISO,Tiempo_ERP_a_Creado_Segundos,Tiempo_ERP_a_Creado_Formato\n';
                let count = 0;
                const maxRows = 150;

                console.log('[AI] Recolectando datos ERP→Creación...');
                const rowsData = table.rows({search: 'applied'}).data();
                console.log('[AI] Total rows disponibles:', rowsData.length);
                
                if (rowsData.length === 0) {
                    console.warn('[AI] No hay datos en la tabla. Asegúrate de haber aplicado los filtros primero.');
                    return { 
                        metrics, 
                        csv: 'Order_ID,Cliente,Fecha_ERP_ISO,Fecha_Creado_ISO,Tiempo_ERP_a_Creado_Segundos,Tiempo_ERP_a_Creado_Formato\n', 
                        type: 'Tiempo Recepción Pedido → Puesto en Fabricación', 
                        note: 'Sin datos disponibles'
                    };
                }

                rowsData.each(function(row, index) {
                    if (count >= maxRows) return false;
                    
                    // Debug primera fila
                    if (index === 0) {
                        console.log('[AI] Primera fila (muestra):', row);
                    }
                    
                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const fechaErpIso = cleanValue(normalizeDateTime(row.fecha_pedido_erp));
                    const fechaCreadoIso = cleanValue(normalizeDateTime(row.created_at));
                    const tiempoErpCreadoFormato = safeValue(row.erp_to_created_formatted, '00:00:00');
                    const tiempoErpCreadoSegundosRaw = durationToSeconds(tiempoErpCreadoFormato);
                    const tiempoErpCreadoSegundos = cleanValue(tiempoErpCreadoSegundosRaw !== '' ? tiempoErpCreadoSegundosRaw : '0');
                    const tiempoErpCreado = cleanValue(tiempoErpCreadoFormato);
                    csv += `${orderId},${cliente},${fechaErpIso},${fechaCreadoIso},${tiempoErpCreadoSegundos},${tiempoErpCreado}\n`;
                    count++;
                });

                console.log(`[AI] CSV generado con ${count} filas`);
                console.log('[AI] Primeras 200 caracteres del CSV:', csv.substring(0, 200));
                
                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes` : `Total: ${count} órdenes`;
                return { metrics, csv, type: 'Tiempo Recepción Pedido → Puesto en Fabricación', note };
            }

            // Análisis 2: Tiempo Puesto en Fabricación → Pedido Finalizado
            function collectCreatedToFinishedData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Tiempo Puesto en Fabricación → Pedido Finalizado' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgCreatedToFinish: $('#kpi-erp-finish').text() || '-',
                    medianCreatedToFinish: latestSummary?.orders_p50_created_to_finished ? formatSeconds(latestSummary.orders_p50_created_to_finished) : '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV: Order_ID, Cliente, Fecha_Creado_ISO, Fecha_Fin_ISO, Tiempo_Creado_a_Fin_Segundos, Tiempo_Creado_a_Fin_Formato
                let csv = 'Order_ID,Cliente,Fecha_Creado_ISO,Fecha_Fin_ISO,Tiempo_Creado_a_Fin_Segundos,Tiempo_Creado_a_Fin_Formato\n';
                let count = 0;
                const maxRows = 150;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;
                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const fechaCreadoIso = cleanValue(normalizeDateTime(row.created_at));
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const tiempoCreadoFinFormato = safeValue(row.created_to_finished_formatted, '00:00:00');
                    const tiempoCreadoFinSegundosRaw = durationToSeconds(tiempoCreadoFinFormato);
                    const tiempoCreadoFinSegundos = cleanValue(tiempoCreadoFinSegundosRaw !== '' ? tiempoCreadoFinSegundosRaw : '0');
                    const tiempoCreadoFin = cleanValue(tiempoCreadoFinFormato);
                    csv += `${orderId},${cliente},${fechaCreadoIso},${fechaFinIso},${tiempoCreadoFinSegundos},${tiempoCreadoFin}\n`;
                    count++;
                });

                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes` : `Total: ${count} órdenes`;
                return { metrics, csv, type: 'Tiempo Puesto en Fabricación → Pedido Finalizado', note };
            }

            // Análisis adicional: Pedido Finalizado → Entrega
            function collectFinishToDeliveryData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Pedido Finalizado → Entrega' };
                }

                const header = 'Order_ID,Cliente,Fecha_Fin_ISO,Fecha_Entrega_Usada_ISO,Fecha_Entrega_Planificada_ISO,Fecha_Entrega_Real_ISO,Tiempo_Fin_a_Entrega_Segundos,Tiempo_Fin_a_Entrega_Formato,Retraso_vs_Plan_Segundos,Retraso_vs_Plan_Formato\n';
                let csv = header;

                const rows = table.rows({search: 'applied'}).data();
                const deliveryReference = $('#use_actual_delivery').is(':checked')
                    ? 'Fecha real de entrega (actual_delivery_date)'
                    : 'Fecha ERP programada (delivery_date)';

                const onTimeTotalRaw = latestSummary?.sla_total;
                const onTimeCountRaw = latestSummary?.sla_on_time_count;
                let onTimeRatio = latestSummary?.sla_on_time_ratio;
                if (typeof onTimeRatio === 'number' && !Number.isNaN(onTimeRatio)) {
                    onTimeRatio = `${(onTimeRatio * 100).toFixed(1)}%`;
                } else {
                    onTimeRatio = '-';
                }

                if (!rows || rows.length === 0) {
                    const metrics = {
                        ordersTotal: $('#kpi-orders-total').text() || '0',
                        avgFinishToDelivery: '-',
                        deliveriesDelayed: 0,
                        slaOnTime: `${onTimeCountRaw ?? '-'} / ${onTimeTotalRaw ?? '-'}`,
                        slaRate: onTimeRatio,
                        deliveryReference,
                        dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                    };
                    return { metrics, csv: header, type: 'Pedido Finalizado → Entrega', note: 'Sin datos disponibles' };
                }

                let count = 0;
                const maxRows = 150;
                let totalSeconds = 0;
                let validSeconds = 0;
                let delayedCount = 0;

                rows.each(function(row) {
                    if (count >= maxRows) return false;

                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const fechaEntregaUsadaIso = cleanValue(normalizeDateTime(row.delivery_date));
                    const fechaEntregaPlanIso = cleanValue(normalizeDateTime(row.delivery_date_planned));
                    const fechaEntregaRealIso = cleanValue(normalizeDateTime(row.actual_delivery_date));

                    const tiempoFinEntregaFormato = safeValue(row.finished_to_delivery_formatted, '00:00:00');
                    const tiempoFinEntregaSegundosRaw = durationToSeconds(tiempoFinEntregaFormato);
                    const tiempoFinEntregaSegundos = tiempoFinEntregaSegundosRaw !== '' ? tiempoFinEntregaSegundosRaw : '0';

                    let delaySegundos = '0';
                    let delayFormato = '00:00:00';
                    const delayRaw = typeof row.order_delivery_delay_seconds === 'number' ? row.order_delivery_delay_seconds : null;
                    if (delayRaw !== null && !Number.isNaN(delayRaw)) {
                        delaySegundos = String(delayRaw);
                        delayFormato = formatSignedDuration(delayRaw);
                        if (delayRaw > 0) {
                            delayedCount++;
                        }
                    }

                    const parsedSeconds = parseInt(tiempoFinEntregaSegundosRaw, 10);
                    if (!Number.isNaN(parsedSeconds)) {
                        totalSeconds += parsedSeconds;
                        validSeconds++;
                    }

                    csv += `${orderId},${cliente},${fechaFinIso},${fechaEntregaUsadaIso},${fechaEntregaPlanIso},${fechaEntregaRealIso},${cleanValue(tiempoFinEntregaSegundos)},${cleanValue(tiempoFinEntregaFormato)},${delaySegundos},${cleanValue(delayFormato)}\n`;
                    count++;
                });

                const avgSeconds = validSeconds > 0 ? Math.round(totalSeconds / validSeconds) : 0;

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgFinishToDelivery: formatTime(avgSeconds),
                    deliveriesDelayed: delayedCount,
                    slaOnTime: `${onTimeCountRaw ?? '-'} / ${onTimeTotalRaw ?? '-'}`,
                    slaRate: onTimeRatio,
                    deliveryReference,
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes` : `Total: ${count} órdenes`;
                return { metrics, csv, type: 'Pedido Finalizado → Entrega', note };
            }

            // Análisis 3: Rendimiento de Órdenes (mantener como referencia)
            function collectOrdersOverviewData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Rendimiento de Órdenes' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    processesTotal: $('#kpi-processes-total').text() || '0',
                    avgErpToFinish: $('#kpi-erp-finish').text() || '-',
                    avgGap: $('#kpi-gap').text() || '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV reducido: Order_ID, Cliente, Fecha_ERP_ISO, Tiempo_ERP_a_Fin_Segundos, Tiempo_ERP_a_Fin_Formato, Tiempo_Creado_a_Fin_Segundos, Tiempo_Creado_a_Fin_Formato
                let csv = 'Order_ID,Cliente,Fecha_ERP_ISO,Tiempo_ERP_a_Fin_Segundos,Tiempo_ERP_a_Fin_Formato,Tiempo_Creado_a_Fin_Segundos,Tiempo_Creado_a_Fin_Formato\n';
                let count = 0;
                const maxRows = 150;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;
                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const fechaErpIso = cleanValue(normalizeDateTime(row.fecha_pedido_erp));
                    const tiempoErpFinFormato = safeValue(row.erp_to_finished_formatted, '00:00:00');
                    const tiempoErpFinSegundosRaw = durationToSeconds(tiempoErpFinFormato);
                    const tiempoErpFinSegundos = cleanValue(tiempoErpFinSegundosRaw !== '' ? tiempoErpFinSegundosRaw : '0');
                    const tiempoCreadoFinFormato = safeValue(row.created_to_finished_formatted, '00:00:00');
                    const tiempoCreadoFinSegundosRaw = durationToSeconds(tiempoCreadoFinFormato);
                    const tiempoCreadoFinSegundos = cleanValue(tiempoCreadoFinSegundosRaw !== '' ? tiempoCreadoFinSegundosRaw : '0');
                    csv += `${orderId},${cliente},${fechaErpIso},${tiempoErpFinSegundos},${cleanValue(tiempoErpFinFormato)},${tiempoCreadoFinSegundos},${cleanValue(tiempoCreadoFinFormato)}\n`;
                    count++;
                });

                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes` : `Total: ${count} órdenes`;
                return { metrics, csv, type: 'Rendimiento de Órdenes', note };
            }

            // Análisis adicional: Órdenes críticas por tipo de producto
            function collectOrderTypeCriticalData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Órdenes críticas por tipo' };
                }

                const header = 'Order_ID,Cliente,Tipo_Producto,Estado_Entrega,Fecha_Fin_ISO,Fecha_Entrega_Usada_ISO,Fecha_Entrega_Planificada_ISO,Fecha_Entrega_Real_ISO,Tiempo_Fin_a_Entrega_Segundos,Tiempo_Fin_a_Entrega_Formato,Retraso_vs_Plan_Segundos,Retraso_vs_Plan_Formato\n';
                let csv = header;
                const rows = table.rows({search: 'applied'}).data();

                if (!rows || rows.length === 0) {
                    const metrics = {
                        ordersTotal: $('#kpi-orders-total').text() || '0',
                        delayedTotal: 0,
                        worstType: '-',
                        worstAvgDelay: '-',
                        dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                    };
                    return { metrics, csv: header, type: 'Órdenes críticas por tipo', note: 'Sin datos disponibles' };
                }

                const typeStats = {};
                const maxRows = 150;
                let count = 0;
                let delayedTotal = 0;

                rows.each(function(row) {
                    if (count >= maxRows) return false;

                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const tipoProductoRaw = safeValue(row.route_name, 'Sin tipo');
                    const tipoProducto = cleanValue(tipoProductoRaw);
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const fechaEntregaUsadaIso = cleanValue(normalizeDateTime(row.delivery_date));
                    const fechaEntregaPlanIso = cleanValue(normalizeDateTime(row.delivery_date_planned));
                    const fechaEntregaRealIso = cleanValue(normalizeDateTime(row.actual_delivery_date));

                    const tiempoFinEntregaFormato = safeValue(row.finished_to_delivery_formatted, '00:00:00');
                    const tiempoFinEntregaSegundosStr = durationToSeconds(tiempoFinEntregaFormato);
                    const tiempoFinEntregaSegundos = tiempoFinEntregaSegundosStr !== '' ? parseInt(tiempoFinEntregaSegundosStr, 10) : 0;

                    let delaySegundos = 0;
                    let delayFormato = '00:00:00';
                    const delayRaw = typeof row.order_delivery_delay_seconds === 'number' ? row.order_delivery_delay_seconds : null;
                    if (delayRaw !== null && !Number.isNaN(delayRaw)) {
                        delaySegundos = delayRaw;
                        delayFormato = formatSignedDuration(delayRaw);
                    }

                    const estadoEntrega = delaySegundos > 0 ? 'Retraso' : (delaySegundos < 0 ? 'Adelantado' : 'A tiempo');
                    if (delaySegundos > 0) {
                        delayedTotal++;
                    }

                    if (!typeStats[tipoProductoRaw]) {
                        typeStats[tipoProductoRaw] = {
                            count: 0,
                            delayed: 0,
                            delaySum: 0,
                        };
                    }

                    typeStats[tipoProductoRaw].count++;
                    if (delaySegundos > 0) {
                        typeStats[tipoProductoRaw].delayed++;
                        typeStats[tipoProductoRaw].delaySum += delaySegundos;
                    }

                    csv += `${orderId},${cliente},${tipoProducto},${estadoEntrega},${fechaFinIso},${fechaEntregaUsadaIso},${fechaEntregaPlanIso},${fechaEntregaRealIso},${cleanValue(String(tiempoFinEntregaSegundos))},${cleanValue(tiempoFinEntregaFormato)},${cleanValue(String(delaySegundos))},${cleanValue(delayFormato)}\n`;
                    count++;
                });

                let worstType = '-';
                let worstAvgDelaySeconds = null;
                Object.entries(typeStats).forEach(([type, stats]) => {
                    if (stats.delayed > 0) {
                        const avgDelay = stats.delaySum / stats.delayed;
                        if (worstAvgDelaySeconds === null || avgDelay > worstAvgDelaySeconds) {
                            worstAvgDelaySeconds = avgDelay;
                            worstType = type;
                        }
                    }
                });

                const worstAvgDelay = worstAvgDelaySeconds !== null ? formatTime(Math.round(worstAvgDelaySeconds)) : '-';

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    delayedTotal,
                    worstType: cleanValue(worstType)?.replace(/^"|"$/g, '') || '-',
                    worstAvgDelay,
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes` : `Total: ${count} órdenes`;
                return { metrics, csv, type: 'Órdenes críticas por tipo', note };
            }

            // Análisis adicional: Alertas de brechas acumuladas
            function collectGapAlertsData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Alertas de brechas' };
                }

                const header = 'Order_ID,Cliente,Procesos_Afectados,Gap_Total_Segundos,Gap_Total_Formato,Gap_Maximo_Segundos,Gap_Maximo_Formato,Gap_Promedio_Segundos,Gap_Promedio_Formato\n';
                let csv = header;
                const rows = table.rows({search: 'applied'}).data();

                if (!rows || rows.length === 0) {
                    const metrics = {
                        ordersTotal: $('#kpi-orders-total').text() || '0',
                        ordersOverThreshold: 0,
                        threshold: '02:00:00',
                        dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                    };
                    return { metrics, csv: header, type: 'Alertas de brechas', note: 'Sin datos disponibles' };
                }

                const thresholdSeconds = 2 * 3600; // 2 horas
                const maxRows = 150;
                let count = 0;
                let ordersOverThreshold = 0;
                let processedOrders = 0;

                rows.each(function(row) {
                    if (count >= maxRows) return false;

                    const processes = Array.isArray(row.processes) ? row.processes : [];
                    if (!processes.length) {
                        return;
                    }

                    let totalGap = 0;
                    let maxGap = 0;
                    let gapsCount = 0;

                    processes.forEach(proc => {
                        let gapSeconds = null;
                        if (typeof proc.gap_seconds === 'number') {
                            gapSeconds = proc.gap_seconds;
                        } else if (proc.gap_formatted) {
                            const parsed = durationToSeconds(proc.gap_formatted);
                            gapSeconds = parsed !== '' ? parseInt(parsed, 10) : null;
                        }

                        if (gapSeconds !== null && !Number.isNaN(gapSeconds) && gapSeconds > 0) {
                            totalGap += gapSeconds;
                            gapsCount++;
                            if (gapSeconds > maxGap) {
                                maxGap = gapSeconds;
                            }
                        }
                    });

                    if (gapsCount === 0) {
                        return;
                    }

                    processedOrders++;

                    const avgGap = Math.round(totalGap / gapsCount);
                    if (totalGap >= thresholdSeconds) {
                        ordersOverThreshold++;
                    }

                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    csv += `${orderId},${cliente},${cleanValue(String(gapsCount))},${cleanValue(String(totalGap))},${cleanValue(formatTime(totalGap))},${cleanValue(String(maxGap))},${cleanValue(formatTime(maxGap))},${cleanValue(String(avgGap))},${cleanValue(formatTime(avgGap))}\n`;
                    count++;
                });

                const metrics = {
                    ordersTotal: processedOrders,
                    ordersOverThreshold,
                    threshold: formatTime(thresholdSeconds),
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                const note = count >= maxRows ? `Incluye primeras ${maxRows} órdenes evaluadas` : `Total: ${count} órdenes evaluadas`;
                return { metrics, csv, type: 'Alertas de brechas', note };
            }

            // Análisis 4: Gaps por Proceso
            function collectProcessGapsData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Gaps por Proceso' };
                }

                const metrics = {
                    avgGap: $('#kpi-gap').text() || '-',
                    processesTotal: $('#kpi-processes-total').text() || '0',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV: Order_ID, Codigo_Proceso, Nombre_Proceso, Gap_Segundos, Gap_Formato, Duracion_Segundos, Duracion_Formato
                let csv = 'Order_ID,Codigo_Proceso,Nombre_Proceso,Gap_Segundos,Gap_Formato,Duracion_Segundos,Duracion_Formato\n';
                let count = 0;
                const maxRows = 100;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;
                    const orderId = row.order_id || '-';
                    
                    // Acceder a los procesos si están disponibles en los datos
                    const processes = row.processes || [];
                    if (Array.isArray(processes) && processes.length > 0) {
                        processes.forEach(proc => {
                            const codigo = cleanValue(proc.process_code || '-');
                            const nombre = cleanValue(proc.process_name || '-');
                            const gapFormato = safeValue(proc.gap_formatted, '00:00:00');
                            const gapSegundosRaw = durationToSeconds(gapFormato);
                            const gapSegundos = cleanValue(gapSegundosRaw !== '' ? gapSegundosRaw : '0');
                            const duracionFormato = safeValue(proc.duration_formatted, '00:00:00');
                            const duracionSegundosRaw = durationToSeconds(duracionFormato);
                            const duracionSegundos = cleanValue(duracionSegundosRaw !== '' ? duracionSegundosRaw : '0');
                            csv += `${cleanValue(safeValue(orderId, '0'))},${cleanValue(safeValue(codigo, 'N/A'))},${cleanValue(safeValue(nombre, 'N/A'))},${gapSegundos},${cleanValue(gapFormato)},${duracionSegundos},${cleanValue(duracionFormato)}\n`;
                            count++;
                        });
                    }
                });

                const note = count >= maxRows ? `Mostrando primeros ${maxRows} procesos` : `Total: ${count} procesos`;
                return { metrics, csv, type: 'Gaps por Proceso', note };
            }

            // Análisis 5: Por Cliente
            function collectByClientData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Análisis por Cliente' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgErpToFinish: $('#kpi-erp-finish').text() || '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // Agrupar por cliente
                const clientData = {};
                let totalOrders = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    const cliente = row.customer_client_name || 'Sin cliente';
                    if (!clientData[cliente]) {
                        clientData[cliente] = {
                            count: 0,
                            orders: []
                        };
                    }
                    clientData[cliente].count++;
                    const tiempoTotalFormato = safeValue(row.erp_to_finished_formatted, '00:00:00');
                    const tiempoTotalSegundosRaw = durationToSeconds(tiempoTotalFormato);
                    const tiempoTotalSegundos = tiempoTotalSegundosRaw !== '' ? parseInt(tiempoTotalSegundosRaw, 10) : null;
                    const tiempoCreadoFormato = safeValue(row.created_to_finished_formatted, '00:00:00');
                    const tiempoCreadoSegundosRaw = durationToSeconds(tiempoCreadoFormato);
                    const tiempoCreadoSegundos = tiempoCreadoSegundosRaw !== '' ? parseInt(tiempoCreadoSegundosRaw, 10) : null;
                    clientData[cliente].orders.push({
                        orderId: row.order_id,
                        tiempoTotalSegundos,
                        tiempoTotalFormato,
                        tiempoCreadoSegundos,
                        tiempoCreadoFormato
                    });
                    totalOrders++;
                });

                // CSV: Cliente, Cantidad_Ordenes, Ordenes_IDs, Tiempo_ERP_a_Fin_Promedio_Segundos, Tiempo_ERP_a_Fin_Promedio_Formato, Tiempo_Creado_a_Fin_Promedio_Segundos, Tiempo_Creado_a_Fin_Promedio_Formato
                let csv = 'Cliente,Cantidad_Ordenes,Ordenes_IDs,Tiempo_ERP_a_Fin_Promedio_Segundos,Tiempo_ERP_a_Fin_Promedio_Formato,Tiempo_Creado_a_Fin_Promedio_Segundos,Tiempo_Creado_a_Fin_Promedio_Formato\n';
                for (const [cliente, data] of Object.entries(clientData)) {
                    const orderIds = data.orders.slice(0, 5).map(o => o.orderId).join(' | ');
                    const suffix = data.count > 5 ? ` (+${data.count - 5} más)` : '';
                    let sumaErp = 0; let cuentaErp = 0;
                    let sumaCreado = 0; let cuentaCreado = 0;
                    data.orders.forEach(o => {
                        if (typeof o.tiempoTotalSegundos === 'number') {
                            sumaErp += o.tiempoTotalSegundos;
                            cuentaErp++;
                        }
                        if (typeof o.tiempoCreadoSegundos === 'number') {
                            sumaCreado += o.tiempoCreadoSegundos;
                            cuentaCreado++;
                        }
                    });
                    const promedioErpSegundos = cuentaErp > 0 ? Math.round(sumaErp / cuentaErp) : 0;
                    const promedioCreadoSegundos = cuentaCreado > 0 ? Math.round(sumaCreado / cuentaCreado) : 0;
                    const promedioErpFormato = formatTime(promedioErpSegundos);
                    const promedioCreadoFormato = formatTime(promedioCreadoSegundos);
                    csv += `${cleanValue(cliente)},${data.count},${cleanValue(orderIds + suffix)},${promedioErpSegundos},${cleanValue(promedioErpFormato)},${promedioCreadoSegundos},${cleanValue(promedioCreadoFormato)}\n`;
                }

                const note = `${totalOrders} órdenes de ${Object.keys(clientData).length} clientes`;
                return { metrics, csv, type: 'Análisis por Cliente', note };
            }

            // Análisis 6: Procesos Lentos
            function collectSlowProcessesData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Procesos Lentos' };
                }

                const metrics = {
                    processesTotal: $('#kpi-processes-total').text() || '0',
                    avgGap: $('#kpi-gap').text() || '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // Recolectar todos los procesos y ordenar por duración
                const allProcesses = [];

                table.rows({search: 'applied'}).data().each(function(row) {
                    const orderId = row.order_id || '-';
                    const processes = row.processes || [];
                    
                    if (Array.isArray(processes) && processes.length > 0) {
                        processes.forEach(proc => {
                            // Extraer duración en segundos si está disponible
                            const durationSec = proc.duration_seconds || 0;
                            allProcesses.push({
                                orderId: orderId,
                                codigo: proc.process_code || '-',
                                nombre: proc.process_name || '-',
                                duracion: proc.duration_formatted || '-',
                                durationSec: durationSec,
                                gap: proc.gap_formatted || '-'
                            });
                        });
                    }
                });

                // Ordenar por duración (descendente) y tomar top 30
                allProcesses.sort((a, b) => b.durationSec - a.durationSec);
                const slowest = allProcesses.slice(0, 30);

                // CSV: Order_ID, Codigo_Proceso, Nombre_Proceso, Duracion_Segundos, Duracion_Formato, Gap_Segundos, Gap_Formato
                let csv = 'Order_ID,Codigo_Proceso,Nombre_Proceso,Duracion_Segundos,Duracion_Formato,Gap_Segundos,Gap_Formato\n';
                slowest.forEach(proc => {
                    const duracionSec = typeof proc.durationSec === 'number' ? String(proc.durationSec) : '0';
                    const gapSec = durationToSeconds(proc.gap || '00:00:00');
                    const gapSegundos = cleanValue(gapSec !== '' ? gapSec : '0');
                    csv += `${cleanValue(proc.orderId)},${cleanValue(proc.codigo)},${cleanValue(proc.nombre)},${cleanValue(duracionSec)},${cleanValue(proc.duracion)},${gapSegundos},${cleanValue(proc.gap || '00:00:00')}\n`;
                });

                const note = `Top 30 procesos más lentos de ${allProcesses.length} totales`;
                return { metrics, csv, type: 'Procesos Lentos', note };
            }

            // Análisis 7: Comparativa Top/Bottom
            function collectTopBottomData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Comparativa Top/Bottom' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgErpToFinish: $('#kpi-erp-finish').text() || '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                const allOrders = [];
                table.rows({search: 'applied'}).data().each(function(row) {
                    allOrders.push({
                        orderId: cleanValue(row.order_id || '-'),
                        cliente: cleanValue(row.customer_client_name || '-'),
                        tiempoTotal: cleanValue(row.erp_to_finished_formatted || '-'),
                        tiempoCreado: cleanValue(row.created_to_finished_formatted || '-')
                    });
                });

                // Top 10 y Bottom 10
                const top10 = allOrders.slice(0, 10);
                const bottom10 = allOrders.slice(-10);

                let csv = 'Tipo,Order_ID,Cliente,Tiempo_ERP_a_Fin_Segundos,Tiempo_ERP_a_Fin_Formato,Tiempo_Creado_a_Fin_Segundos,Tiempo_Creado_a_Fin_Formato\n';
                csv += '# TOP 10 (Más rápidas)\n';
                top10.forEach(o => {
                    const erpSegRaw = durationToSeconds(o.tiempoTotal || '00:00:00');
                    const erpSeg = erpSegRaw !== '' ? erpSegRaw : '0';
                    const creSegRaw = durationToSeconds(o.tiempoCreado || '00:00:00');
                    const creSeg = creSegRaw !== '' ? creSegRaw : '0';
                    csv += `TOP,${o.orderId},${o.cliente},${erpSeg},${o.tiempoTotal},${creSeg},${o.tiempoCreado}\n`;
                });
                csv += '# BOTTOM 10 (Más lentas)\n';
                bottom10.forEach(o => {
                    const erpSegRaw = durationToSeconds(o.tiempoTotal || '00:00:00');
                    const erpSeg = erpSegRaw !== '' ? erpSegRaw : '0';
                    const creSegRaw = durationToSeconds(o.tiempoCreado || '00:00:00');
                    const creSeg = creSegRaw !== '' ? creSegRaw : '0';
                    csv += `BOTTOM,${o.orderId},${o.cliente},${erpSeg},${o.tiempoTotal},${creSeg},${o.tiempoCreado}\n`;
                });

                const note = `Comparando ${allOrders.length} órdenes`;
                return { metrics, csv, type: 'Comparativa Top/Bottom', note };
            }

            // Análisis 8: Análisis Total (CSV extendido)
            function collectFullAnalysisData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Análisis Total' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    processesTotal: $('#kpi-processes-total').text() || '0',
                    avgErpToFinish: $('#kpi-erp-finish').text() || '-',
                    avgGap: $('#kpi-gap').text() || '-',
                    medianErpToFinish: latestSummary?.orders_p50_created_to_finished ? formatSeconds(latestSummary.orders_p50_created_to_finished) : '-',
                    medianGap: latestSummary?.process_p50_gap ? formatSeconds(latestSummary.process_p50_gap) : '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV completo con todas las columnas visibles (normalizadas)
                let csv = 'Order_ID,Cliente,Fecha_Pedido_ERP_ISO,Fecha_Creado_ISO,Fecha_Finalizado_ISO,Tiempo_ERP_a_Creado_Segundos,Tiempo_ERP_a_Creado_Formato,Tiempo_ERP_a_Fin_Segundos,Tiempo_ERP_a_Fin_Formato,Tiempo_Creado_a_Fin_Segundos,Tiempo_Creado_a_Fin_Formato\n';
                let count = 0;
                const maxRows = 150;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;
                    const orderId = cleanValue(row.order_id || '-');
                    const cliente = cleanValue(row.customer_client_name || '-');
                    const fechaErpIso = cleanValue(normalizeDateTime(row.fecha_pedido_erp));
                    const fechaCreadoIso = cleanValue(normalizeDateTime(row.created_at));
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const erpCreadoFormato = safeValue(row.erp_to_created_formatted, '00:00:00');
                    const erpCreadoSegundosRaw = durationToSeconds(erpCreadoFormato);
                    const erpCreadoSegundos = cleanValue(erpCreadoSegundosRaw !== '' ? erpCreadoSegundosRaw : '0');
                    const erpFinFormato = safeValue(row.erp_to_finished_formatted, '00:00:00');
                    const erpFinSegundosRaw = durationToSeconds(erpFinFormato);
                    const erpFinSegundos = cleanValue(erpFinSegundosRaw !== '' ? erpFinSegundosRaw : '0');
                    const creadoFinFormato = safeValue(row.created_to_finished_formatted, '00:00:00');
                    const creadoFinSegundosRaw = durationToSeconds(creadoFinFormato);
                    const creadoFinSegundos = cleanValue(creadoFinSegundosRaw !== '' ? creadoFinSegundosRaw : '0');
                    csv += `${orderId},${cliente},${fechaErpIso},${fechaCreadoIso},${fechaFinIso},${erpCreadoSegundos},${cleanValue(erpCreadoFormato)},${erpFinSegundos},${cleanValue(erpFinFormato)},${creadoFinSegundos},${cleanValue(creadoFinFormato)}\n`;
                    count++;
                });

                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes de ${table.page.info().recordsDisplay}` : `Total: ${count} órdenes`;
                return { metrics, csv, type: 'Análisis Total', note };
            }

            async function startAiTask(fullPrompt, userPromptForDisplay) {
                try {
                    console.log('[AI][Production Times] Iniciando análisis:', userPromptForDisplay);
                    console.log('[AI] Prompt length:', fullPrompt.length, 'caracteres');
                    
                    // Mostrar modal de procesamiento
                    $('#aiProcessingTitle').text(userPromptForDisplay);
                    $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando solicitud a IA...');
                    const processingModal = new bootstrap.Modal(document.getElementById('aiProcessingModal'));
                    processingModal.show();
                    
                    const fd = new FormData();
                    fd.append('prompt', fullPrompt);
                    fd.append('agent', 'data_analysis');

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
                    console.log('[AI] Iniciando polling cada 5 segundos...');
                    
                    // Actualizar estado
                    $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... Esperando respuesta...');

                    let done = false; let last; let pollCount = 0;
                    while (!done) {
                        pollCount++;
                        console.log(`[AI] Polling #${pollCount} - Esperando 5 segundos...`);
                        $('#aiProcessingStatus').html(`<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... (${pollCount * 5}s)`);
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
                            console.log('[AI] ¡Respuesta recibida! Finalizando polling...');
                            done = true; 
                        }
                    }

                    // Cerrar modal de procesamiento
                    bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal')).hide();
                    
                    // Mostrar resultado
                    $('#aiResultPrompt').text(userPromptForDisplay);
                    const content = (last && last.task && last.task.response != null) ? last.task.response : last;

                    let rawText;
                    try {
                        rawText = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
                    } catch {
                        rawText = String(content);
                    }

                    $('#aiResultText').text(rawText || '');

                    const htmlTarget = $('#aiResultHtml');
                    if (window.DOMPurify && typeof DOMPurify.sanitize === 'function') {
                        const sanitized = DOMPurify.sanitize(rawText || '', {
                            ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'style', 'src', 'alt', 'title'],
                            ALLOWED_TAGS: false
                        });
                        htmlTarget.html(sanitized && sanitized.trim() ? sanitized : '<p class="text-muted mb-0">Sin contenido HTML para mostrar.</p>');
                    } else {
                        htmlTarget.text(rawText || '');
                    }

                    const rawTabTrigger = document.getElementById('ai-tab-raw');
                    if (rawTabTrigger && bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(rawTabTrigger).show();
                    }

                    const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
                    resultModal.show();
                } catch (err) {
                    console.error('[AI] Unexpected error:', err);
                    // Cerrar modal de procesamiento si está abierto
                    const procModal = bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal'));
                    if (procModal) procModal.hide();
                    alert('{{ __("Error al procesar solicitud de IA") }}');
                }
            }

            // Configuración de prompts por tipo de análisis
            const analysisPrompts = {
                'erp-to-created': {
                    title: 'Tiempo Recepción Pedido → Puesto en Fabricación',
                    prompt: `Analiza los tiempos entre la recepcion del pedido y su puesta en fabricacion.

IMPORTANTE: A continuacion recibiras un archivo CSV completo. Lee TODAS las filas del CSV para realizar el analisis.

El CSV contiene las columnas normalizadas:
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Fecha_ERP_ISO: Marca temporal ISO del registro en ERP
- Fecha_Creado_ISO: Marca temporal ISO de la creacion en produccion
- Tiempo_ERP_a_Creado_Segundos: Diferencia en segundos entre ERP y creacion
- Tiempo_ERP_a_Creado_Formato: Mismo tiempo en formato HH:MM:SS

Objetivos del analisis:
1. Identificar ordenes con mayores retrasos en inicio de produccion (top 5)
2. Detectar patrones de retraso por cliente
3. Calcular tiempo promedio vs casos problematicos
4. Proponer 3 acciones especificas para acelerar el paso de ERP a produccion

Se breve, concreto y cuantifica los hallazgos usando TODOS los datos del CSV.`
                },
                'created-to-finished': {
                    title: 'Tiempo Puesto en Fabricación → Pedido Finalizado',
                    prompt: `Analiza los tiempos de ciclo de produccion (desde puesta en fabricacion hasta finalizacion del pedido).

IMPORTANTE: Lee TODAS las filas del CSV completo que recibiras a continuacion.

Columnas normalizadas del CSV:
- Order_ID, Cliente
- Fecha_Creado_ISO, Fecha_Fin_ISO
- Tiempo_Creado_a_Fin_Segundos, Tiempo_Creado_a_Fin_Formato

Foco del analisis:
1. Identificar ordenes con ciclos mas largos (top 5)
2. Comparar con promedio y mediana del periodo
3. Detectar tendencias o patrones temporales
4. Proponer 3 medidas especificas para reducir tiempo de ciclo

Prioriza hallazgos accionables usando TODOS los datos.`
                },
                'finish-to-delivery': {
                    title: 'Pedido Finalizado → Entrega',
                    prompt: `Analiza el tramo final desde la finalizacion del pedido hasta la entrega.

IMPORTANTE: Procesa TODAS las filas del CSV.

Columnas normalizadas:
- Order_ID, Cliente
- Fecha_Fin_ISO, Fecha_Entrega_Usada_ISO
- Fecha_Entrega_Planificada_ISO, Fecha_Entrega_Real_ISO
- Tiempo_Fin_a_Entrega_Segundos, Tiempo_Fin_a_Entrega_Formato
- Retraso_vs_Plan_Segundos, Retraso_vs_Plan_Formato

Objetivos:
1. Identificar pedidos con mayor retraso entre fin y entrega (top 5)
2. Comparar cumplimiento versus SLA (entregas a tiempo vs tarde)
3. Detectar clientes o tipos con mayor retraso recurrente
4. Sugerir 3 acciones para reducir retrasos post-produccion

Incluye una breve nota sobre si se esta usando fecha real o programada de entrega.`
                },
                'process-gaps': {
                    title: 'Gaps entre Procesos',
                    prompt: `Analiza los gaps (tiempos muertos) entre procesos consecutivos.

IMPORTANTE: Procesa TODAS las filas del CSV.

Columnas normalizadas:
- Order_ID, Codigo_Proceso, Nombre_Proceso
- Gap_Segundos, Gap_Formato
- Duracion_Segundos, Duracion_Formato

Focus del analisis:
1. Identificar procesos con mayores tiempos de espera (top 10)
2. Detectar patrones de gaps entre procesos especificos
3. Comparar duracion de proceso vs tiempo de espera
4. Proponer 3 acciones especificas para optimizar

Se conciso y cuantifica impacto usando TODOS los datos.`
                },
                'by-client': {
                    title: 'Análisis por Cliente',
                    prompt: `Analiza el rendimiento agrupado por cliente.

IMPORTANTE: Procesa TODAS las filas del CSV.

Columnas normalizadas:
- Cliente, Cantidad_Ordenes, Ordenes_IDs
- Tiempo_ERP_a_Fin_Promedio_Segundos, Tiempo_ERP_a_Fin_Promedio_Formato
- Tiempo_Creado_a_Fin_Promedio_Segundos, Tiempo_Creado_a_Fin_Promedio_Formato

Objetivos:
1. Identificar top 5 clientes con mas ordenes
2. Comparar tiempos promedio por cliente
3. Detectar clientes con patrones de retraso
4. Proponer 3 estrategias diferenciadas

Manten el analisis breve usando TODOS los datos.`
                },
                'order-type-critical': {
                    title: 'Órdenes críticas por tipo',
                    prompt: `Agrupa las ordenes por tipo de producto o ruta y detecta donde hay retrasos criticos.

IMPORTANTE: Analiza TODAS las filas del CSV.

Columnas normalizadas:
- Order_ID, Cliente, Tipo_Producto
- Estado_Entrega (Retraso/A tiempo/Adelantado)
- Fecha_Fin_ISO, Fecha_Entrega_Usada_ISO
- Tiempo_Fin_a_Entrega_Segundos, Tiempo_Fin_a_Entrega_Formato
- Retraso_vs_Plan_Segundos, Retraso_vs_Plan_Formato

Objetivos:
1. Identificar los 3 tipos de producto con mayor incidencia de retrasos
2. Cuantificar el retraso promedio por tipo (en segundos y formato HH:MM:SS)
3. Señalar casos criticos concretos (orden y cliente)
4. Sugerir acciones orientadas por tipo para mejorar el cumplimiento

Se breve y cuantifica siempre que sea posible.`
                },
                'gap-alerts': {
                    title: 'Alertas de brechas acumuladas',
                    prompt: `Detecta ordenes con brechas acumuladas elevadas entre procesos.

IMPORTANTE: Procesa TODAS las filas del CSV.

Columnas normalizadas:
- Order_ID, Cliente
- Procesos_Afectados
- Gap_Total_Segundos, Gap_Total_Formato
- Gap_Maximo_Segundos, Gap_Maximo_Formato
- Gap_Promedio_Segundos, Gap_Promedio_Formato

Analisis solicitado:
1. Identificar ordenes que superan el umbral definido (2 horas)
2. Destacar top 5 ordenes con mayor gap total
3. Analizar si hay clientes/procesos repetidos entre las alertas
4. Proponer 3 medidas para reducir estas brechas acumuladas

Entrega el analisis de forma concisa y priorizada.`
                },
                'slow-processes': {
                    title: 'Procesos Lentos',
                    prompt: `Analiza los procesos mas lentos del periodo.

IMPORTANTE: Procesa TODAS las filas del CSV (top 30 procesos mas lentos).
IMPORTANTE: Analiza TODAS las filas del CSV (top 30 procesos mas lentos).

Columnas normalizadas:
- Order_ID, Codigo_Proceso, Nombre_Proceso
- Duracion_Segundos, Duracion_Formato
- Gap_Segundos, Gap_Formato

Centra el analisis en:
1. Identificar top 10 procesos con mayor duracion
2. Detectar procesos que aparecen frecuentemente
3. Comparar duracion vs gaps asociados
4. Proponer 3 acciones especificas para optimizar

Se especifico y prioriza por impacto usando TODOS los datos.`
                },
                'top-bottom': {
                    title: 'Comparativa Top/Bottom',
                    prompt: `Compara las 10 ordenes mas rapidas vs las 10 mas lentas.

IMPORTANTE: El CSV contiene 20 filas (10 TOP + 10 BOTTOM). Lee TODAS.

Columnas normalizadas:
- Tipo, Order_ID, Cliente
- Tiempo_ERP_a_Fin_Segundos, Tiempo_ERP_a_Fin_Formato
- Tiempo_Creado_a_Fin_Segundos, Tiempo_Creado_a_Fin_Formato

Analisis requerido:
1. Identificar factores comunes en ordenes rapidas (TOP)
2. Identificar factores comunes en ordenes lentas (BOTTOM)
3. Detectar 3 diferencias clave entre grupos
4. Proponer como replicar practicas del TOP

Manten el analisis conciso usando TODOS los datos.`
                },
                'full': {
                    title: 'Análisis Total',
                    prompt: `Realiza un analisis integral de todos los datos de tiempos de produccion.

IMPORTANTE: El CSV contiene hasta 150 ordenes. Procesa TODAS las filas.

Columnas normalizadas:
- Order_ID, Cliente
- Fecha_Pedido_ERP_ISO, Fecha_Creado_ISO, Fecha_Finalizado_ISO
- Tiempo_ERP_a_Creado_Segundos, Tiempo_ERP_a_Creado_Formato
- Tiempo_ERP_a_Fin_Segundos, Tiempo_ERP_a_Fin_Formato
- Tiempo_Creado_a_Fin_Segundos, Tiempo_Creado_a_Fin_Formato

Incluye:
1. Resumen ejecutivo con hallazgos principales
2. Analisis de tendencias generales
3. Identificacion de 5 cuellos de botella criticos
4. 5 recomendaciones priorizadas (corto vs medio plazo)
5. 3 acciones inmediatas sugeridas

Genera un informe estructurado pero conciso usando TODOS los datos.`
                }
            };

            // Variable global para el prompt actual
            let currentPromptData = null;


            // Click en opciones del dropdown de análisis
            $('.dropdown-item[data-analysis]').on('click', function(e) {
                e.preventDefault();
                const analysisType = $(this).data('analysis');
                const config = analysisPrompts[analysisType];
                
                if (!config) {
                    console.error('[AI] Tipo de análisis no configurado:', analysisType);
                    return;
                }
                
                console.log('[AI] Tipo seleccionado:', analysisType, config.title);
                
                // Recolectar datos según el tipo de análisis
                let data;
                switch(analysisType) {
                    case 'erp-to-created':
                        data = collectErpToCreatedData();
                        break;
                    case 'created-to-finished':
                        data = collectCreatedToFinishedData();
                        break;
                    case 'finish-to-delivery':
                        data = collectFinishToDeliveryData();
                        break;
                    case 'process-gaps':
                        data = collectProcessGapsData();
                        break;
                    case 'by-client':
                        data = collectByClientData();
                        break;
                    case 'order-type-critical':
                        data = collectOrderTypeCriticalData();
                        break;
                    case 'gap-alerts':
                        data = collectGapAlertsData();
                        break;
                    case 'slow-processes':
                        data = collectSlowProcessesData();
                        break;
                    case 'top-bottom':
                        data = collectTopBottomData();
                        break;
                    case 'full':
                        data = collectFullAnalysisData();
                        break;
                    default:
                        console.error('[AI] Tipo desconocido:', analysisType);
                        return;
                }
                
                // Verificar si hay datos
                if (!data.csv || data.csv.trim() === '' || data.csv.split('\n').length <= 1) {
                    alert('No hay datos disponibles para analizar. Por favor, ejecuta primero una búsqueda con el botón "Aplicar Filtros".');
                    return;
                }
                
                console.log('[AI] Datos recolectados:', {
                    type: data.type,
                    csvLength: data.csv.length,
                    note: data.note
                });
                
                // Contar filas del CSV
                const csvLines = data.csv.split('\n').filter(line => line.trim() !== '');
                const csvRows = csvLines.length - 1; // -1 porque el primer elemento es el header
                
                // Construir prompt final con formato optimizado para agentes
                let finalPrompt = `${config.prompt}\n\n`;
                finalPrompt += `PERIODO: ${data.metrics.dateRange}\n\n`;
                
                // Añadir métricas específicas
                finalPrompt += 'METRICAS CLAVE:\n';
                Object.keys(data.metrics).forEach(key => {
                    if (key !== 'dateRange') {
                        finalPrompt += `- ${key}: ${data.metrics[key]}\n`;
                    }
                });
                
                if (data.note) {
                    finalPrompt += `\n${data.note}\n`;
                }
                
                // Información clara sobre el CSV
                finalPrompt += `\n--- INICIO DEL CSV (${csvRows} filas de datos) ---\n`;
                finalPrompt += data.csv;
                finalPrompt += `--- FIN DEL CSV ---\n`;
                finalPrompt += `\nATENCION: El CSV anterior contiene ${csvRows} filas de datos reales. Asegurate de procesar TODAS las filas para tu analisis.`;
                
                console.log(`[AI] Análisis: ${config.title}`);
                console.log(`[AI] Filas CSV: ${csvRows}`);
                console.log(`[AI] Tamaño prompt: ${finalPrompt.length} caracteres`);
                console.log(`[AI] Tamaño CSV: ${data.csv.length} caracteres`);
                
                // Guardar prompt y título para editarlo/enviarlo
                currentPromptData = {
                    prompt: finalPrompt,
                    title: config.title
                };
                
                // Mostrar modal de edición
                $('#aiPromptModalTitle').text(config.title);
                $('#aiPrompt').val(finalPrompt);
                const editModal = new bootstrap.Modal(document.getElementById('aiPromptModal'));
                editModal.show();
            });
            
            // Enviar prompt editado a la IA
            $('#btn-ai-send').on('click', function() {
                // Verificar rate limiting
                if (!checkAiRateLimit()) {
                    alert(`Has alcanzado el límite de ${MAX_AI_REQUESTS_PER_MINUTE} solicitudes por minuto. Por favor, espera un momento antes de intentarlo de nuevo.`);
                    return;
                }
                
                if (!currentPromptData) {
                    console.error('[AI] No hay datos de prompt');
                    return;
                }
                
                const editedPrompt = $('#aiPrompt').val().trim();
                
                if (!editedPrompt) {
                    alert('El prompt no puede estar vacío');
                    return;
                }
                
                // Validación de tamaño máximo (100KB aprox 100,000 caracteres)
                const maxPromptSize = 100000;
                if (editedPrompt.length > maxPromptSize) {
                    alert(`El prompt es demasiado grande (${editedPrompt.length} caracteres). Máximo permitido: ${maxPromptSize} caracteres. Reduce el número de filas o el contenido.`);
                    return;
                }
                
                // Deshabilitar botón durante el envío
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>{{ __('Enviando...') }}');
                
                // Cerrar modal de edición
                bootstrap.Modal.getInstance(document.getElementById('aiPromptModal')).hide();
                
                // Log del prompt que se enviará
                console.log('[AI] Enviando prompt de longitud:', editedPrompt.length, 'caracteres');
                console.log('[AI] Primeros 500 caracteres:', editedPrompt.substring(0, 500));
                console.log('[AI] Últimos 1000 caracteres:', editedPrompt.substring(editedPrompt.length - 1000));
                
                // Contar líneas CSV en el prompt editado
                const csvMatch = editedPrompt.match(/--- INICIO DEL CSV.*?---[\s\S]*?--- FIN DEL CSV ---/s);
                if (csvMatch) {
                    const csvSection = csvMatch[0];
                    const csvLinesInPrompt = csvSection.split('\n').filter(l => l.trim() && !l.includes('---')).length;
                    console.log(`[AI] Líneas detectadas en sección CSV del prompt: ${csvLinesInPrompt}`);
                }
                
                // Enviar a IA
                startAiTask(editedPrompt, currentPromptData.title).finally(() => {
                    $btn.prop('disabled', false);
                    $btn.html('{{ __('Enviar a IA') }}');
                });
            });

            $('#btn-ai-reset').on('click', function(){ 
                if (currentPromptData && currentPromptData.prompt) {
                    $('#aiPrompt').val(currentPromptData.prompt);
                }
            });
        });
    </script>

    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="aiPromptModalTitle"><i class="fas fa-robot me-2"></i>{{ __('Análisis IA') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h6 class="mb-2 text-primary"><i class="fas fa-database me-1"></i>{{ __('Datos que enviamos a la IA') }}</h6>
                        <ul class="mb-0 ps-3">
                            <li><strong>{{ __('Filtros') }}:</strong> {{ __('rango de fechas y filtros de órdenes/procesos finalizados') }}</li>
                            <li><strong>{{ __('KPIs') }}:</strong> {{ __('promedios y medianas de tiempos ERP → Fin, duraciones de procesos y gaps') }}</li>
                            <li><strong>{{ __('Datos detallados') }}:</strong> {{ __('hasta 150 órdenes en formato CSV con toda la información') }}</li>
                        </ul>
                    </div>
                    <label class="form-label fw-bold">{{ __('Prompt a enviar (puedes editarlo):') }}</label>
                    <textarea class="form-control font-monospace" id="aiPrompt" rows="12" style="font-size: 0.9rem;" placeholder="{{ __('Selecciona un tipo de análisis del dropdown...') }}"></textarea>
                    <div class="alert alert-warning mt-2 mb-0">
                        <small>
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>{{ __('Importante:') }}</strong> {{ __('El prompt incluye los datos en formato CSV entre "--- INICIO DEL CSV ---" y "--- FIN DEL CSV ---". NO elimines esta sección o la IA no podrá analizar los datos.') }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">{{ __('Restaurar prompt original') }}</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send">{{ __('Enviar a IA') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Processing Modal -->
    <div class="modal fade" id="aiProcessingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i><span id="aiProcessingTitle">{{ __('Procesando...') }}</span></h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">{{ __('Cargando...') }}</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" id="aiProcessingStatus">
                        <i class="fas fa-spinner fa-spin me-2"></i>{{ __('Procesando solicitud...') }}
                    </p>
                    <small class="text-muted d-block mt-2">
                        {{ __('Esto puede tardar varios segundos. Por favor, espere...') }}
                    </small>
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
                    <p class="text-muted"><strong>{{ __('Tipo de Análisis') }}:</strong> <span id="aiResultPrompt"></span></p>
                    <ul class="nav nav-tabs mb-3" id="aiResultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab-rendered" data-bs-toggle="tab" data-bs-target="#aiResultRendered" type="button" role="tab" aria-controls="aiResultRendered" aria-selected="false">
                                <i class="fas fa-code me-1"></i>{{ __('HTML Interpretado') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ai-tab-raw" data-bs-toggle="tab" data-bs-target="#aiResultRaw" type="button" role="tab" aria-controls="aiResultRaw" aria-selected="true">
                                <i class="fas fa-file-alt me-1"></i>{{ __('Texto Plano') }}
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content" id="aiResultTabContent">
                        <div class="tab-pane fade" id="aiResultRendered" role="tabpanel" aria-labelledby="ai-tab-rendered">
                            <div id="aiResultHtml" class="border rounded p-3 bg-light" style="min-height: 200px; overflow:auto;"></div>
                        </div>
                        <div class="tab-pane fade show active" id="aiResultRaw" role="tabpanel" aria-labelledby="ai-tab-raw">
                            <pre id="aiResultText" class="bg-light p-3 rounded" style="white-space: pre-wrap; min-height: 200px; overflow:auto;"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endpush
