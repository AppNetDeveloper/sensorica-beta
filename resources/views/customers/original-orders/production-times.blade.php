@extends('layouts.admin')

@section('title', __('Lead Time'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Lead Time') }}</li>
    </ul>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #0d6efd !important;">
    <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filters-collapse" aria-expanded="false" aria-controls="filters-collapse">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-sliders-h me-2"></i>{{ __('Filtros de búsqueda') }}
                            </h5>
                            <div id="filter-summary" class="mt-1">
                                <small class="text-dark">
                                    <i class="fas fa-hand-pointer me-1"></i>
                                    <span id="filter-summary-text">{{ __('Click para modificar filtros') }}</span>
                                </small>
                            </div>
                        </div>
                        <button class="btn btn-sm btn-light rounded-circle" type="button" style="width: 32px; height: 32px; padding: 0;">
                            <i class="fas fa-chevron-down" id="filter-toggle-icon"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="filters-collapse">
                    <div class="card-body bg-light">
                        <form id="filters-form" class="row gy-3 gx-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-2 fw-semibold text-primary" for="date_start"><i class="fas fa-calendar-alt me-1"></i>{{ __('Desde') }}</label>
                                <input type="date" class="form-control shadow-sm border-primary" id="date_start" value="{{ $defaultStart }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-2 fw-semibold text-primary" for="date_end"><i class="fas fa-calendar-check me-1"></i>{{ __('Hasta') }}</label>
                                <input type="date" class="form-control shadow-sm border-primary" id="date_end" value="{{ $defaultEnd }}">
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="button" id="apply-filters" class="btn btn-primary btn-lg w-100 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                    <i class="fas fa-search me-2"></i>{{ __('Filtrar') }}
                                </button>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-white shadow-sm p-3 h-100">
                                    <label class="form-label fw-semibold text-dark mb-2">
                                        <i class="fas fa-calendar-alt me-2 text-primary"></i>{{ __('Tipo de Fecha de Entrega') }}
                                    </label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="use_actual_delivery" style="width: 3em; height: 1.5em;">
                                        <label class="form-check-label ms-2" for="use_actual_delivery">
                                            <span class="fw-semibold">{{ __('Usar fecha real de entrega (Logística)') }}</span><br>
                                            <small class="text-muted">{{ __('Si está desactivado, usa fecha programada (ERP)') }}</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-white shadow-sm p-3 h-100">
                                    <label class="form-label fw-semibold text-dark mb-2">
                                        <i class="fas fa-filter me-2 text-info"></i>{{ __('Filtrado de Datos') }}
                                    </label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="exclude_incomplete_orders" checked>
                                        <label class="form-check-label fw-semibold" for="exclude_incomplete_orders">
                                            {{ __('Excluir órdenes sin fechas completas') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4" id="kpi-cards">
                {{-- KPI 1: Promedio Tiempo Espera --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Promedio Tiempo Espera Op/Máq') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-gap">-</h3>
                    </div>
                </div>
                {{-- KPI 2: Mediana Tiempo Espera --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-muted">
                                <i class="fas fa-equals"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Mediana Tiempo Espera Op/Máq') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-gap-median">-</h3>
                    </div>
                </div>
                {{-- KPI 3: Promedio Pedido → Fin Producción --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card" data-bs-toggle="collapse" data-bs-target="#kpi-erp-finish-details">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-info">
                                <i class="fas fa-stopwatch"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Prom. Pedido → Fin Prod.') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-erp-finish">-</h3>
                        <div class="pt-kpi-badges">
                            <span class="pt-kpi-badge pt-kpi-badge-primary" id="kpi-erp-finish-total-days" title="{{ __('Total días') }}"><i class="fas fa-calendar"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-success" id="kpi-erp-finish-working-days" title="{{ __('Días laborables') }}"><i class="fas fa-briefcase"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-secondary" id="kpi-erp-finish-non-working-days" title="{{ __('No laborables') }}"><i class="fas fa-calendar-times"></i> -</span>
                        </div>
                        <div class="collapse pt-kpi-details" id="kpi-erp-finish-details">
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-finish-total-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-business-time me-1"></i>{{ __('Laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-finish-working-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-bed me-1"></i>{{ __('No laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-finish-non-working-hours">-</span></div>
                        </div>
                        <p class="pt-kpi-hint"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para detalles') }}</p>
                    </div>
                </div>
                {{-- KPI 4: Mediana Pedido → Fin Producción --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card" data-bs-toggle="collapse" data-bs-target="#kpi-erp-finish-median-details">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-secondary">
                                <i class="fas fa-clock"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Med. Pedido → Fin Prod.') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-erp-finish-median">-</h3>
                        <div class="pt-kpi-badges">
                            <span class="pt-kpi-badge pt-kpi-badge-primary" id="kpi-erp-finish-median-total-days" title="{{ __('Total días') }}"><i class="fas fa-calendar"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-success" id="kpi-erp-finish-median-working-days" title="{{ __('Días laborables') }}"><i class="fas fa-briefcase"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-secondary" id="kpi-erp-finish-median-non-working-days" title="{{ __('No laborables') }}"><i class="fas fa-calendar-times"></i> -</span>
                        </div>
                        <div class="collapse pt-kpi-details" id="kpi-erp-finish-median-details">
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-finish-median-total-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-business-time me-1"></i>{{ __('Laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-finish-median-working-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-bed me-1"></i>{{ __('No laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-finish-median-non-working-hours">-</span></div>
                        </div>
                        <p class="pt-kpi-hint"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para detalles') }}</p>
                    </div>
                </div>
                {{-- KPI 5: Promedio Lanzamiento → Fin --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card" data-bs-toggle="collapse" data-bs-target="#kpi-created-finish-details">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-success">
                                <i class="fas fa-industry"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Prom. Lanzamiento → Fin') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-created-finish">-</h3>
                        <div class="pt-kpi-badges">
                            <span class="pt-kpi-badge pt-kpi-badge-primary" id="kpi-created-finish-total-days" title="{{ __('Total días') }}"><i class="fas fa-calendar"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-success" id="kpi-created-finish-working-days" title="{{ __('Días laborables') }}"><i class="fas fa-briefcase"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-secondary" id="kpi-created-finish-non-working-days" title="{{ __('No laborables') }}"><i class="fas fa-calendar-times"></i> -</span>
                        </div>
                        <div class="collapse pt-kpi-details" id="kpi-created-finish-details">
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}</span><span class="pt-kpi-detail-value" id="kpi-created-finish-total-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-business-time me-1"></i>{{ __('Laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-created-finish-working-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-bed me-1"></i>{{ __('No laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-created-finish-non-working-hours">-</span></div>
                        </div>
                        <p class="pt-kpi-hint"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para detalles') }}</p>
                    </div>
                </div>
                {{-- KPI 6: Mediana Lanzamiento → Fin --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card" data-bs-toggle="collapse" data-bs-target="#kpi-created-finish-median-details">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-info">
                                <i class="fas fa-stopwatch"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Med. Lanzamiento → Fin') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-created-finish-median">-</h3>
                        <div class="pt-kpi-badges">
                            <span class="pt-kpi-badge pt-kpi-badge-primary" id="kpi-created-finish-median-total-days" title="{{ __('Total días') }}"><i class="fas fa-calendar"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-success" id="kpi-created-finish-median-working-days" title="{{ __('Días laborables') }}"><i class="fas fa-briefcase"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-secondary" id="kpi-created-finish-median-non-working-days" title="{{ __('No laborables') }}"><i class="fas fa-calendar-times"></i> -</span>
                        </div>
                        <div class="collapse pt-kpi-details" id="kpi-created-finish-median-details">
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}</span><span class="pt-kpi-detail-value" id="kpi-created-finish-median-total-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-business-time me-1"></i>{{ __('Laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-created-finish-median-working-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-bed me-1"></i>{{ __('No laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-created-finish-median-non-working-hours">-</span></div>
                        </div>
                        <p class="pt-kpi-hint"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para detalles') }}</p>
                    </div>
                </div>
                {{-- KPI 7: Promedio Pedido → Entregado --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card" data-bs-toggle="collapse" data-bs-target="#kpi-erp-delivery-details">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-warning">
                                <i class="fas fa-truck"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Prom. Pedido → Entregado') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-erp-delivery">-</h3>
                        <div class="pt-kpi-badges">
                            <span class="pt-kpi-badge pt-kpi-badge-primary" id="kpi-erp-delivery-total-days" title="{{ __('Total días') }}"><i class="fas fa-calendar"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-success" id="kpi-erp-delivery-working-days" title="{{ __('Días laborables') }}"><i class="fas fa-briefcase"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-secondary" id="kpi-erp-delivery-non-working-days" title="{{ __('No laborables') }}"><i class="fas fa-calendar-times"></i> -</span>
                        </div>
                        <div class="collapse pt-kpi-details" id="kpi-erp-delivery-details">
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-delivery-total-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-business-time me-1"></i>{{ __('Laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-delivery-working-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-bed me-1"></i>{{ __('No laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-delivery-non-working-hours">-</span></div>
                        </div>
                        <p class="pt-kpi-hint"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para detalles') }}</p>
                    </div>
                </div>
                {{-- KPI 8: Mediana Pedido → Entregado --}}
                <div class="col-6 col-lg-3">
                    <div class="pt-kpi-card" data-bs-toggle="collapse" data-bs-target="#kpi-erp-delivery-median-details">
                        <div class="pt-kpi-header">
                            <div class="pt-kpi-icon pt-kpi-icon-danger">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <p class="pt-kpi-title">{{ __('Med. Pedido → Entregado') }}</p>
                        </div>
                        <h3 class="pt-kpi-value" id="kpi-erp-delivery-median">-</h3>
                        <div class="pt-kpi-badges">
                            <span class="pt-kpi-badge pt-kpi-badge-primary" id="kpi-erp-delivery-median-total-days" title="{{ __('Total días') }}"><i class="fas fa-calendar"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-success" id="kpi-erp-delivery-median-working-days" title="{{ __('Días laborables') }}"><i class="fas fa-briefcase"></i> -</span>
                            <span class="pt-kpi-badge pt-kpi-badge-secondary" id="kpi-erp-delivery-median-non-working-days" title="{{ __('No laborables') }}"><i class="fas fa-calendar-times"></i> -</span>
                        </div>
                        <div class="collapse pt-kpi-details" id="kpi-erp-delivery-median-details">
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-delivery-median-total-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-business-time me-1"></i>{{ __('Laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-delivery-median-working-hours">-</span></div>
                            <div class="pt-kpi-detail-row"><span class="pt-kpi-detail-label"><i class="fas fa-bed me-1"></i>{{ __('No laborables') }}</span><span class="pt-kpi-detail-value" id="kpi-erp-delivery-median-non-working-hours">-</span></div>
                        </div>
                        <p class="pt-kpi-hint"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para detalles') }}</p>
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
                            <div class="btn-group me-2" role="group">
                                <button type="button" class="btn btn-lg dropdown-toggle shadow-lg position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Análisis con IA') }}" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; font-weight: 600; padding: 12px 24px; transition: all 0.3s ease;">
                                    <i class="bi bi-stars me-2" style="font-size: 1.2em; animation: sparkle 2s infinite;"></i>
                                    <span>{{ __('Análisis IA') }}</span>
                                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7em; padding: 3px 8px; animation: pulse 2s infinite;">PRO</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 380px; max-height: 600px; overflow-y: auto;">
                                    <li><h6 class="dropdown-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: -0.5rem -0.5rem 0.5rem -0.5rem; padding: 0.75rem 1rem;">
                                        <i class="fas fa-brain me-2"></i>{{ __("Análisis Inteligente con IA") }}
                                        <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7em;">PRO</span>
                                    </h6></li>

                                    <!-- SECCIÓN 1: Análisis de Tiempos y Eficiencia -->
                                    <li><h6 class="dropdown-header text-primary"><i class="fas fa-clock me-1"></i> {{ __("Tiempos y Eficiencia") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="erp-to-created">
                                        <i class="fas fa-hourglass-start text-info me-2"></i>{{ __("Tiempo Recepción → Fabricación") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="created-to-finished">
                                        <i class="fas fa-industry text-success me-2"></i>{{ __("Tiempo Fabricación → Finalizado") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="finish-to-delivery">
                                        <i class="fas fa-truck-loading text-warning me-2"></i>{{ __("Tiempo Finalizado → Entrega") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="working-days-efficiency">
                                        <i class="fas fa-briefcase text-success me-2"></i>{{ __("Eficiencia Días Laborables") }}
                                        <span class="badge bg-success ms-1" style="font-size: 0.65em;">NUEVO</span>
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="calendar-impact">
                                        <i class="fas fa-calendar-times text-danger me-2"></i>{{ __("Impacto Calendario Laboral") }}
                                        <span class="badge bg-success ms-1" style="font-size: 0.65em;">NUEVO</span>
                                    </a></li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- SECCIÓN 2: Análisis de Procesos -->
                                    <li><h6 class="dropdown-header text-warning"><i class="fas fa-cogs me-1"></i> {{ __("Procesos y Gaps") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="process-gaps">
                                        <i class="fas fa-project-diagram text-warning me-2"></i>{{ __("Gaps entre Procesos") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="gap-alerts">
                                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>{{ __("Alertas de Brechas") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="slow-processes">
                                        <i class="fas fa-turtle text-danger me-2"></i>{{ __("Procesos Lentos") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="bottleneck-analysis">
                                        <i class="fas fa-compress-arrows-alt text-danger me-2"></i>{{ __("Detección de Cuellos de Botella") }}
                                        <span class="badge bg-success ms-1" style="font-size: 0.65em;">NUEVO</span>
                                    </a></li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- SECCIÓN 3: Análisis Comparativos -->
                                    <li><h6 class="dropdown-header text-secondary"><i class="fas fa-chart-bar me-1"></i> {{ __("Análisis Comparativos") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="by-client">
                                        <i class="fas fa-users text-primary me-2"></i>{{ __("Por Cliente") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="order-type-critical">
                                        <i class="fas fa-cubes text-secondary me-2"></i>{{ __("Por Tipo de Orden") }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="top-bottom">
                                        <i class="fas fa-balance-scale text-secondary me-2"></i>{{ __("Comparativa Top/Bottom") }}
                                    </a></li>

                                    <li><hr class="dropdown-divider"></li>

                                    <!-- SECCIÓN 4: Análisis Avanzado -->
                                    <li><h6 class="dropdown-header text-dark"><i class="fas fa-rocket me-1"></i> {{ __("Análisis Avanzado") }}</h6></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="capacity-planning">
                                        <i class="fas fa-tasks text-info me-2"></i>{{ __("Planificación de Capacidad") }}
                                        <span class="badge bg-success ms-1" style="font-size: 0.65em;">NUEVO</span>
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="predictive-delays">
                                        <i class="fas fa-chart-line text-warning me-2"></i>{{ __("Predicción de Retrasos") }}
                                        <span class="badge bg-success ms-1" style="font-size: 0.65em;">NUEVO</span>
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" data-analysis="full">
                                        <i class="fas fa-layer-group text-dark me-2"></i>{{ __("Análisis Completo") }}
                                    </a></li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive p-3">
                        <table class="table table-striped table-hover w-100 mb-0" id="production-times-table">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>{{ __('Pedido') }}</th>
                                    <th>{{ __('Cliente') }}</th>
                                    <th>{{ __('Fecha Pedido Cliente') }}</th>
                                    <th>{{ __('Fecha Lanzamiento Producción') }}</th>
                                    <th>{{ __('Fecha Fin Producción') }}</th>
                                    <th>{{ __('Tiempo Pedido Cliente → Lanzamiento Producción') }}</th>
                                    <th>{{ __('Tiempo Lanzamiento Producción → Fin Producción') }}</th>
                                    <th>{{ __('Tiempo Pedido Cliente → Fin Producción') }}</th>
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
                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-clipboard-list"></i></span>{{ __('Tiempo Pedido Cliente → Lanzamiento Producción') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo desde que el pedido se registra en el ERP hasta que entra en producción. Se representa en azul en el timeline interactivo y alimenta el cálculo promedio.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-success bg-opacity-10 text-success me-2"><i class="fas fa-flag-checkered"></i></span>{{ __('Tiempo Pedido Cliente → Fin Producción') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Duración total del pedido desde el ERP hasta la finalización. Ayuda a identificar cuellos de botella globales y se refleja como suma de los tramos azul y verde.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-10 text-primary me-2"><i class="fas fa-industry"></i></span>{{ __('Tiempo Pedido Cliente → Fin Producción') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Tiempo neto de fabricación desde la creación en producción hasta la finalización. En el timeline aparece en verde y mide la eficiencia interna de la planta.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-secondary bg-opacity-10 text-secondary me-2"><i class="fas fa-cogs"></i></span>{{ __('Procesos registrados') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Número de procesos detectados en la orden. Alimenta el timeline por proceso y la comparativa "Duración / Gap".') }}</dd>

                                <dt class="col-sm-12 mb-2 mt-3"><h6 class="text-info mb-0"><i class="fas fa-calendar-alt me-2"></i>{{ __('Iconos de días en KPIs') }}</h6></dt>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-primary bg-opacity-25 text-primary me-2"><i class="fas fa-calendar"></i></span>{{ __('Total días naturales') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Días totales transcurridos entre dos fechas (incluye todos los días del calendario, laborables y no laborables). Ejemplo: 9d = 9 días completos.') }}</dd>

                                <dt class="col-sm-5 mb-3"><span class="badge bg-success bg-opacity-25 text-success me-2"><i class="fas fa-briefcase"></i></span>{{ __('Días laborables') }}</dt>
                                <dd class="col-sm-7 mb-3">{{ __('Días en los que se trabaja según el calendario laboral configurado (lunes-viernes o calendario personalizado del cliente). Estos son días productivos.') }}</dd>

                                <dt class="col-sm-5 mb-0"><span class="badge bg-secondary bg-opacity-25 text-secondary me-2"><i class="fas fa-calendar-times"></i></span>{{ __('Días no laborables') }}</dt>
                                <dd class="col-sm-7 mb-0">{{ __('Días en los que NO se trabaja: fines de semana, festivos, vacaciones o paradas de mantenimiento. Suma: Total = Laborables + No laborables.') }}</dd>
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
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* ===== KPI Cards Compactas ===== */
        .pt-kpi-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.2s ease;
            cursor: pointer;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .pt-kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .pt-kpi-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 12px;
        }
        .pt-kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .pt-kpi-icon-warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .pt-kpi-icon-muted { background: rgba(100, 116, 139, 0.15); color: #64748b; }
        .pt-kpi-icon-info { background: rgba(14, 165, 233, 0.15); color: #0ea5e9; }
        .pt-kpi-icon-secondary { background: rgba(100, 116, 139, 0.15); color: #64748b; }
        .pt-kpi-icon-success { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .pt-kpi-icon-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .pt-kpi-icon-primary { background: rgba(102, 126, 234, 0.15); color: #667eea; }

        .pt-kpi-title {
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.3;
            margin: 0;
        }
        .pt-kpi-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 8px 0;
        }
        .pt-kpi-badges {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .pt-kpi-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .pt-kpi-badge-primary { background: #dbeafe; color: #2563eb; }
        .pt-kpi-badge-success { background: #dcfce7; color: #16a34a; }
        .pt-kpi-badge-secondary { background: #f1f5f9; color: #64748b; }

        .pt-kpi-badge i { font-size: 0.65rem; }

        .pt-kpi-details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .pt-kpi-detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            padding: 4px 0;
        }
        .pt-kpi-detail-label { color: #64748b; }
        .pt-kpi-detail-value { font-weight: 600; color: #334155; }

        .pt-kpi-hint {
            font-size: 0.65rem;
            color: #94a3b8;
            margin-top: 8px;
            text-align: center;
        }

        /* Hover lift legacy */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        /* Responsive KPIs */
        @media (max-width: 992px) {
            .pt-kpi-value { font-size: 1.2rem; }
            .pt-kpi-icon { width: 36px; height: 36px; font-size: 1rem; }
        }
        @media (max-width: 768px) {
            .pt-kpi-card { padding: 14px; }
            .pt-kpi-header { gap: 10px; margin-bottom: 10px; }
            .pt-kpi-title { font-size: 0.65rem; }
            .pt-kpi-value { font-size: 1.15rem; }
            .pt-kpi-badges { gap: 4px; }
            .pt-kpi-badge { padding: 3px 6px; font-size: 0.6rem; }
            .pt-kpi-hint { font-size: 0.6rem; margin-top: 6px; }
        }
        @media (max-width: 576px) {
            .pt-kpi-card { padding: 12px; }
            .pt-kpi-icon { width: 32px; height: 32px; font-size: 0.9rem; }
            .pt-kpi-title { font-size: 0.6rem; }
            .pt-kpi-value { font-size: 1rem; margin-bottom: 6px; }
            .pt-kpi-badges { gap: 3px; }
            .pt-kpi-badge { padding: 2px 5px; font-size: 0.55rem; }
            .pt-kpi-badge i { font-size: 0.5rem; }
            .pt-kpi-details { margin-top: 8px; padding-top: 8px; }
            .pt-kpi-detail-row { font-size: 0.65rem; padding: 3px 0; }
            .pt-kpi-hint { display: none; }
        }
        @media (max-width: 400px) {
            #kpi-cards { gap: 8px !important; }
            .pt-kpi-card { padding: 10px; }
            .pt-kpi-header { flex-direction: column; gap: 6px; }
            .pt-kpi-icon { width: 28px; height: 28px; font-size: 0.8rem; }
            .pt-kpi-value { font-size: 0.95rem; }
            .pt-kpi-badges { flex-direction: column; gap: 2px; }
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


        /* Animaciones para el botón PRO de IA */
        @keyframes sparkle {
            0%, 100% {
                opacity: 1;
                transform: scale(1);
            }
            50% {
                opacity: 0.7;
                transform: scale(1.2);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 0 0 4px rgba(255, 193, 7, 0);
            }
        }

        /* Efecto hover para el botón de IA */
        .btn-lg.dropdown-toggle:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5) !important;
        }

        /* === Estilos para el resultado de IA con Markdown === */
        .ai-result-content {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 0.5rem;
            line-height: 1.8;
        }

        .ai-result-content h1,
        .ai-result-content h2,
        .ai-result-content h3,
        .ai-result-content h4,
        .ai-result-content h5,
        .ai-result-content h6 {
            color: #2c3e50;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .ai-result-content h1 { font-size: 2rem; border-bottom: 2px solid #e9ecef; padding-bottom: 0.5rem; }
        .ai-result-content h2 { font-size: 1.75rem; border-bottom: 1px solid #e9ecef; padding-bottom: 0.4rem; }
        .ai-result-content h3 { font-size: 1.5rem; color: #495057; }
        .ai-result-content h4 { font-size: 1.25rem; color: #495057; }

        .ai-result-content p {
            margin-bottom: 1rem;
            color: #495057;
        }

        .ai-result-content strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .ai-result-content ul,
        .ai-result-content ol {
            margin-bottom: 1rem;
            padding-left: 2rem;
        }

        .ai-result-content li {
            margin-bottom: 0.5rem;
            color: #495057;
        }

        .ai-result-content code {
            background-color: #f8f9fa;
            color: #e83e8c;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            font-size: 0.9em;
        }

        .ai-result-content pre {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
            margin-bottom: 1rem;
        }

        .ai-result-content pre code {
            background-color: transparent;
            color: #212529;
            padding: 0;
        }

        .ai-result-content blockquote {
            border-left: 4px solid #0d6efd;
            padding-left: 1rem;
            margin: 1rem 0;
            color: #6c757d;
            font-style: italic;
        }

        .ai-result-content hr {
            margin: 1.5rem 0;
            border: 0;
            border-top: 2px solid #e9ecef;
        }

        /* Tablas con estilo Bootstrap mejorado */
        .ai-result-content table {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .ai-result-content table thead th {
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
            text-align: left;
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 2px solid #0a58ca;
        }

        .ai-result-content table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }

        .ai-result-content table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .ai-result-content table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Zebra striping para tablas */
        .ai-result-content table.table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Mejor legibilidad en tablas con mucho contenido */
        .ai-result-content table td strong {
            color: #0d6efd;
        }

        /* Responsive tables */
        @media (max-width: 768px) {
            .ai-result-content {
                padding: 1rem;
                font-size: 0.9rem;
            }

            .ai-result-content table {
                font-size: 0.85rem;
            }

            .ai-result-content table thead th,
            .ai-result-content table tbody td {
                padding: 0.5rem;
            }
        }

        /* === Barra de progreso de scroll === */
        .scroll-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
            width: 0%;
            transition: width 0.1s ease;
            z-index: 1000;
            border-radius: 0 3px 3px 0;
        }

        /* === Botón volver arriba === */
        #btnScrollTop {
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
            transform: translateY(10px);
        }

        #btnScrollTop.show {
            opacity: 1;
            transform: translateY(0);
        }

        #btnScrollTop:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2) !important;
        }

        /* === Modal en pantalla completa === */
        .modal-fullscreen-custom {
            max-width: 100% !important;
            width: 100% !important;
            margin: 0 !important;
            height: 100vh;
        }

        .modal-fullscreen-custom .modal-content {
            height: 100vh;
            border-radius: 0;
        }

        /* === Animación para botones === */
        .btn-toolbar .btn {
            transition: all 0.2s ease;
        }

        .btn-toolbar .btn:hover {
            transform: translateY(-2px);
        }

        .btn-toolbar .btn:active {
            transform: translateY(0);
        }

        /* === Toast de confirmación === */
        .copy-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: #198754;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s;
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
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <script>
        // Configurar marked para usar clases de Bootstrap en las tablas
        marked.setOptions({
            breaks: true,
            gfm: true
        });

        $(function () {
            const routes = {
                data: "{{ route('customers.production-times.data', $customer) }}",
                summary: "{{ route('customers.production-times.summary', $customer) }}",
                orderDetail: (orderId) => "{{ route('customers.production-times.order', [$customer, ':orderId']) }}".replace(':orderId', orderId)
            };

            const i18n = {
                erpToCreated: @json(__('Tiempo Pedido Cliente → Lanzamiento Producción')),
                erpToFinished: @json(__('Tiempo Pedido Cliente → Fin Producción')),
                createdToFinished: @json(__('Tiempo Pedido Cliente → Fin Producción')),
                processes: @json(__('Procesos')),
                erpRegistered: @json(__('Pedido Cliente')),
                noErpDate: @json(__('Sin fecha de recepción registrada')),
                createdAt: @json(__('Lanzamiento Producción')),
                finishedAt: @json(__('Fin Producción')),
                position: @json(__('Posición')),
                duration: @json(__('Duración')),
                gap: @json(__('Gap')),
                erpToProcess: @json(__('Pedido Cliente → Proceso')),
                createdToProcess: @json(__('Lanzamiento Producción → Proceso')),
                timelineTitle: @json(__('Timeline de procesos')),
                timelineDetail: @json(__('Detalle de procesos')),
                noProcesses: @json(__('Sin procesos registrados para esta orden')),
                chartNoData: @json(__('Sin datos de procesos para generar el gráfico')),
                chartUnavailable: @json(__('No se pudo inicializar el gráfico')),
                loadingChart: @json(__('Generando gráfico...')),
                minutesSuffix: @json(__('min')),
                orderId: @json(__('Pedido')),
                timelineOrdersTitle: @json(__('Cronología de fechas')),
                timelineFromErp: @json(__('Ruta desde Pedido Cliente')),
                timelineFromCreated: @json(__('Ruta desde Lanzamiento Producción')),
                timelineLegendErpCreated: @json(__('Tiempo Pedido Cliente →  Lanzamiento Producción')),
                timelineLegendCreatedFinished: @json(__('Tiempo Pedido Cliente → Fin Producción')),
                timelineLegendFinishedDelivery: @json(__('Fin Producción → Entrega')),
                timelineLegendCreatedProcess: @json(__('Tiempo Lanzamiento Producción → Proceso')),
                timelineLegendProcessDelivery: @json(__('Tiempo Proceso → Entrega')),
                timelineProcessPath: @json(__('Ruta al proceso')),
                timelineNoData: @json(__('Sin datos suficientes para mostrar la cronología')),
                timelineStart: @json(__('Inicio')),
                timelineEnd: @json(__('Fin')),
                erpDateLabel: @json(__('Fecha Pedido Cliente')),
                createdDateLabel: @json(__('Lanzamiento Producción')),
                finishedDateLabel: @json(__('Fecha Fin Producción')),
                deliveryDateLabel: @json(__('Fecha de entrega prevista')),
                actualDeliveryLabel: @json(__('Fecha de entrega real')),
                toggleActualDeliveryLabel: @json(__('Usar fecha real de entrega (actual_delivery_date) en lugar de fecha ERP programada')),
                timelineLegendFinishedActualDelivery: @json(__('Fin Producción → Entrega real')),
                timelineLegendProcessActualDelivery: @json(__('Proceso → Entrega real')),
                timelineOrdersAverageTitle: @json(__('Promedio del rango')),
                timelineOrdersMedianTitle: @json(__('Mediana del rango'))
            };

            const normalizeTimelineLabel = (raw) => {
                if (!raw || typeof raw !== 'string') return '-';
                return raw
                    .replace('ERP → Creado', '{{ __('Tiempo Pedido Cliente → Lanzamiento Producción') }}')
                    .replace('Tiempo Pedido Cliente → Fin Producción', '{{ __('Tiempo Pedido Cliente → Fin Producción') }}')
                    .replace('Fin → Entrega real', '{{ __('Fin Producción → Entrega real') }}')
                    .replace('Fin → Entrega', '{{ __('Fin Producción → Entrega') }}');
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
                    points.push({ x: '📥 → 🏭 Pedido Cliente → Lanzamiento Producción', y: [erp * 1000, created * 1000], fillColor: '#118DFF' });
                }
                if (typeof created === 'number' && typeof finished === 'number' && finished > created) {
                    points.push({ x: '📥 → ✅ Pedido Cliente → Fin Producción', y: [created * 1000, finished * 1000], fillColor: '#21A366' });
                }
                if (typeof finished === 'number' && typeof delivery === 'number' && delivery > finished) {
                    points.push({ x: (useActual ? '✅ → 🚚 Fin Producción → Entrega real' : '✅ → 🚚 Fin Producción → Entrega'), y: [finished * 1000, delivery * 1000], fillColor: '#F2C811' });
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
                if (c1e > c1s) points.push({ x: '📥 → 🏭 Pedido Cliente → Lanzamiento Producción', y: [c1s, c1e], fillColor: '#118DFF' });
                if (c2e > c2s) points.push({ x: '📥 → ✅ Pedido Cliente → Fin Producción', y: [c2s, c2e], fillColor: '#21A366' });
                if (c3e > c3s) points.push({ x: (useActual ? '✅ → 🚚 Fin Producción → Entrega real' : '✅ → 🚚 Fin Producción → Entrega'), y: [c3s, c3e], fillColor: '#F2C811' });

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
                if (typeof c1e === 'number' && c1e > c1s) points.push({ x: '📥 → 🏭 Pedido Cliente → Lanzamiento Producción', y: [c1s, c1e], fillColor: '#118DFF' });
                if (typeof c2e === 'number' && c2e > c2s) points.push({ x: '📥 → ✅ Pedido Cliente → Fin Producción', y: [c2s, c2e], fillColor: '#21A366' });
                if (typeof c3e === 'number' && c3e > c3s) points.push({ x: (useActual ? '✅ → 🚚 Fin Producción → Entrega real' : '✅ → 🚚 Fin Producción → Entrega'), y: [c3s, c3e], fillColor: '#F2C811' });

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

            function renderAvgRangeBarWorking(detail) {
                const key = detail.id ?? detail.order_id;
                const el = document.querySelector(`#avg-rangebar-working-${key}`);
                if (!el || typeof ApexCharts === 'undefined') return;

                const avgWorking = detail.average_timeline_working ?? {};
                const useActual = !!(detail.use_actual_delivery);

                // Data comes in hours, not timestamps
                const c1s = 0;
                const c1e = typeof avgWorking.erp_to_created_hours === 'number' ? avgWorking.erp_to_created_hours : null;
                const c2s = c1e;
                const c2e = typeof avgWorking.erp_to_finished_hours === 'number' ? avgWorking.erp_to_finished_hours : null;
                const c3s = c2e;
                const c3e = typeof avgWorking.erp_to_delivery_hours === 'number' ? avgWorking.erp_to_delivery_hours : null;

                const points = [];
                if (typeof c1e === 'number' && c1e > c1s) {
                    points.push({
                        x: 'Tiempo Pedido Cliente → Lanzamiento Producción (lab.)',
                        y: [c1s, c1e],
                        fillColor: '#118DFF'
                    });
                }
                if (typeof c2e === 'number' && c2e > c2s) {
                    points.push({
                        x: 'Tiempo Pedido Cliente → Fin Producción (lab.)',
                        y: [c2s, c2e],
                        fillColor: '#21A366'
                    });
                }
                if (typeof c3e === 'number' && c3e > c3s) {
                    points.push({
                        x: (useActual ? 'Fin Producción → Entrega real (lab.)' : 'Fin Producción → Entrega (lab.)'),
                        y: [c3s, c3e],
                        fillColor: '#F2C811'
                    });
                }

                try { console.log('[RB-Working] avg points', { key, pointsCount: points.length, points }); } catch(e) {}

                if (!points.length) {
                    el.innerHTML = '<div class="text-muted small">' + i18n.timelineNoData + '</div>';
                    return;
                }

                const options = {
                    chart: {
                        type: 'rangeBar',
                        height: 240,
                        width: '100%',
                        id: `avg-rangebar-working-${key}`,
                        toolbar: {
                            show: true,
                            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                            export: { csv: { filename: 'timeline_promedio_laborable' }, svg: { filename: 'timeline_promedio_laborable' }, png: { filename: 'timeline_promedio_laborable' } }
                        },
                        animations: { enabled: true }
                    },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 4 } },
                    series: [{ name: 'Promedio (días lab.)', data: points }],
                    xaxis: {
                        type: 'numeric',
                        labels: {
                            formatter: function (val) {
                                const hours = Number(val || 0);
                                const days = Math.floor(hours / 24);
                                const remainingHours = Math.floor(hours % 24);
                                if (days > 0) {
                                    return days + 'd ' + remainingHours + 'h';
                                }
                                return remainingHours + 'h';
                            }
                        },
                        title: { text: 'Tiempo (días laborables)' }
                    },
                    dataLabels: { enabled: false },
                    grid: { strokeDashArray: 3 },
                    tooltip: {
                        custom: function({ seriesIndex, dataPointIndex, w }) {
                            const point = w?.config?.series?.[seriesIndex]?.data?.[dataPointIndex];
                            if (!point) return '';
                            const label = normalizeTimelineLabel(point.x);
                            const hours = (point.y?.[1] || 0) - (point.y?.[0] || 0);
                            const days = Math.floor(hours / 24);
                            const remainingHours = Math.floor(hours % 24);
                            const duration = days > 0 ? `${days}d ${remainingHours}h` : `${remainingHours}h`;
                            return `<div class="p-2"><strong>${label}</strong><br/><strong>${duration}</strong> (solo días laborables)</div>`;
                        }
                    }
                };

                const chart = new ApexCharts(el, options);
                chart.render();
            }

            function renderMedianRangeBarWorking(detail) {
                const key = detail.id ?? detail.order_id;
                const el = document.querySelector(`#median-rangebar-working-${key}`);
                if (!el || typeof ApexCharts === 'undefined') return;

                const medianWorking = detail.median_timeline_working ?? {};
                const useActual = !!(detail.use_actual_delivery);

                // Data comes in hours
                const c1s = 0;
                const c1e = typeof medianWorking.erp_to_created_hours === 'number' ? medianWorking.erp_to_created_hours : null;
                const c2s = c1e;
                const c2e = typeof medianWorking.erp_to_finished_hours === 'number' ? medianWorking.erp_to_finished_hours : null;
                const c3s = c2e;
                const c3e = typeof medianWorking.erp_to_delivery_hours === 'number' ? medianWorking.erp_to_delivery_hours : null;

                const points = [];
                if (typeof c1e === 'number' && c1e > c1s) {
                    points.push({
                        x: 'Tiempo Pedido Cliente → Lanzamiento Producción (lab.)',
                        y: [c1s, c1e],
                        fillColor: '#118DFF'
                    });
                }
                if (typeof c2e === 'number' && c2e > c2s) {
                    points.push({
                        x: 'Tiempo Pedido Cliente → Fin Producción (lab.)',
                        y: [c2s, c2e],
                        fillColor: '#21A366'
                    });
                }
                if (typeof c3e === 'number' && c3e > c3s) {
                    points.push({
                        x: (useActual ? 'Fin Producción → Entrega real (lab.)' : 'Fin Producción → Entrega (lab.)'),
                        y: [c3s, c3e],
                        fillColor: '#F2C811'
                    });
                }

                try { console.log('[MRB-Working] median points', { key, pointsCount: points.length, points }); } catch(e) {}

                if (!points.length) {
                    el.innerHTML = '<div class="text-muted small">' + i18n.timelineNoData + '</div>';
                    return;
                }

                const options = {
                    chart: {
                        type: 'rangeBar',
                        height: 240,
                        width: '100%',
                        id: `median-rangebar-working-${key}`,
                        toolbar: {
                            show: true,
                            tools: { download: true, selection: true, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true },
                            export: { csv: { filename: 'timeline_mediana_laborable' }, svg: { filename: 'timeline_mediana_laborable' }, png: { filename: 'timeline_mediana_laborable' } }
                        },
                        animations: { enabled: true }
                    },
                    plotOptions: { bar: { horizontal: true, barHeight: '70%', borderRadius: 4 } },
                    series: [{ name: 'Mediana (días lab.)', data: points }],
                    xaxis: {
                        type: 'numeric',
                        labels: {
                            formatter: function(val) {
                                const hours = Number(val || 0);
                                const days = Math.floor(hours / 24);
                                const remainingHours = Math.floor(hours % 24);
                                if (days > 0) {
                                    return days + 'd ' + remainingHours + 'h';
                                }
                                return remainingHours + 'h';
                            }
                        },
                        title: { text: 'Tiempo (días laborables)' }
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
                            const hours = (point.y?.[1] || 0) - (point.y?.[0] || 0);
                            const days = Math.floor(hours / 24);
                            const remainingHours = Math.floor(hours % 24);
                            const duration = days > 0 ? `${days}d ${remainingHours}h` : `${remainingHours}h`;
                            return `<div class="p-2"><strong>${label}</strong><br/><strong>${duration}</strong> (solo días laborables)</div>`;
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
                        console.error('ApexCharts median working render error', error);
                        el.innerHTML = '<div class="text-danger small">Error al renderizar gráfico de mediana (días lab.)</div>';
                    }
                };

                ensureRendered();
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
                                renderAvgRangeBarWorking(detail);
                                renderMedianRangeBarWorking(detail);
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
                // Los KPIs de tiempo de espera ya se actualizan más abajo en las líneas 1900-1901

                // ERP to Finish (promedio)
                const erpFinishSeconds = summary?.orders_avg_erp_to_finished ?? 0;
                const erpFinishWorkingDays = Math.round(summary?.orders_avg_erp_to_finished_working_days ?? 0);
                const erpFinishNonWorkingDays = Math.round(summary?.orders_avg_erp_to_finished_non_working_days ?? 0);
                const erpFinishTotalDays = erpFinishWorkingDays + erpFinishNonWorkingDays;
                const erpFinishTotalHours = Math.round(erpFinishSeconds / 3600);
                const erpFinishWorkingHours = erpFinishWorkingDays * 24;
                const erpFinishNonWorkingHours = erpFinishNonWorkingDays * 24;

                $('#kpi-erp-finish').text(formatSeconds(erpFinishSeconds));
                $('#kpi-erp-finish-total-days').html('<i class="fas fa-calendar fa-lg me-2"></i>' + erpFinishTotalDays + 'd');
                $('#kpi-erp-finish-working-days').html('<i class="fas fa-briefcase fa-lg me-2"></i>' + erpFinishWorkingDays + 'd');
                $('#kpi-erp-finish-non-working-days').html('<i class="fas fa-calendar-times fa-lg me-2"></i>' + erpFinishNonWorkingDays + 'd');
                $('#kpi-erp-finish-total-hours').text(erpFinishTotalHours + 'h');
                $('#kpi-erp-finish-working-hours').text(erpFinishWorkingHours + 'h (' + erpFinishWorkingDays + 'd × 24h)');
                $('#kpi-erp-finish-non-working-hours').text(erpFinishNonWorkingHours + 'h (' + erpFinishNonWorkingDays + 'd × 24h)');

                // ERP to Finish (mediana)
                const erpFinishMedianSeconds = summary?.orders_p50_erp_to_finished ?? 0;
                const erpFinishMedianWorkingDays = Math.round(summary?.orders_p50_erp_to_finished_working_days ?? 0);
                const erpFinishMedianNonWorkingDays = Math.round(summary?.orders_p50_erp_to_finished_non_working_days ?? 0);
                const erpFinishMedianTotalDays = erpFinishMedianWorkingDays + erpFinishMedianNonWorkingDays;
                const erpFinishMedianTotalHours = Math.round(erpFinishMedianSeconds / 3600);
                const erpFinishMedianWorkingHours = erpFinishMedianWorkingDays * 24;
                const erpFinishMedianNonWorkingHours = erpFinishMedianNonWorkingDays * 24;

                $('#kpi-erp-finish-median').text(formatSeconds(erpFinishMedianSeconds));
                $('#kpi-erp-finish-median-total-days').html('<i class="fas fa-calendar fa-lg me-2"></i>' + erpFinishMedianTotalDays + 'd');
                $('#kpi-erp-finish-median-working-days').html('<i class="fas fa-briefcase fa-lg me-2"></i>' + erpFinishMedianWorkingDays + 'd');
                $('#kpi-erp-finish-median-non-working-days').html('<i class="fas fa-calendar-times fa-lg me-2"></i>' + erpFinishMedianNonWorkingDays + 'd');
                $('#kpi-erp-finish-median-total-hours').text(erpFinishMedianTotalHours + 'h');
                $('#kpi-erp-finish-median-working-hours').text(erpFinishMedianWorkingHours + 'h (' + erpFinishMedianWorkingDays + 'd × 24h)');
                $('#kpi-erp-finish-median-non-working-hours').text(erpFinishMedianNonWorkingHours + 'h (' + erpFinishMedianNonWorkingDays + 'd × 24h)');

                // Created to Finish (promedio)
                const createdFinishSeconds = summary?.orders_avg_created_to_finished ?? 0;
                const createdFinishWorkingDays = Math.round(summary?.orders_avg_created_to_finished_working_days ?? 0);
                const createdFinishNonWorkingDays = Math.round(summary?.orders_avg_created_to_finished_non_working_days ?? 0);
                const createdFinishTotalDays = createdFinishWorkingDays + createdFinishNonWorkingDays;
                const createdFinishTotalHours = Math.round(createdFinishSeconds / 3600);
                const createdFinishWorkingHours = createdFinishWorkingDays * 24;
                const createdFinishNonWorkingHours = createdFinishNonWorkingDays * 24;

                $('#kpi-created-finish').text(formatSeconds(createdFinishSeconds));
                $('#kpi-created-finish-total-days').html('<i class="fas fa-calendar fa-lg me-2"></i>' + createdFinishTotalDays + 'd');
                $('#kpi-created-finish-working-days').html('<i class="fas fa-briefcase fa-lg me-2"></i>' + createdFinishWorkingDays + 'd');
                $('#kpi-created-finish-non-working-days').html('<i class="fas fa-calendar-times fa-lg me-2"></i>' + createdFinishNonWorkingDays + 'd');
                $('#kpi-created-finish-total-hours').text(createdFinishTotalHours + 'h');
                $('#kpi-created-finish-working-hours').text(createdFinishWorkingHours + 'h (' + createdFinishWorkingDays + 'd × 24h)');
                $('#kpi-created-finish-non-working-hours').text(createdFinishNonWorkingHours + 'h (' + createdFinishNonWorkingDays + 'd × 24h)');

                // Created to Finish (mediana)
                const createdFinishMedianSeconds = summary?.orders_p50_created_to_finished ?? 0;
                const createdFinishMedianWorkingDays = Math.round(summary?.orders_p50_created_to_finished_working_days ?? 0);
                const createdFinishMedianNonWorkingDays = Math.round(summary?.orders_p50_created_to_finished_non_working_days ?? 0);
                const createdFinishMedianTotalDays = createdFinishMedianWorkingDays + createdFinishMedianNonWorkingDays;
                const createdFinishMedianTotalHours = Math.round(createdFinishMedianSeconds / 3600);
                const createdFinishMedianWorkingHours = createdFinishMedianWorkingDays * 24;
                const createdFinishMedianNonWorkingHours = createdFinishMedianNonWorkingDays * 24;

                $('#kpi-created-finish-median').text(formatSeconds(createdFinishMedianSeconds));
                $('#kpi-created-finish-median-total-days').html('<i class="fas fa-calendar fa-lg me-2"></i>' + createdFinishMedianTotalDays + 'd');
                $('#kpi-created-finish-median-working-days').html('<i class="fas fa-briefcase fa-lg me-2"></i>' + createdFinishMedianWorkingDays + 'd');
                $('#kpi-created-finish-median-non-working-days').html('<i class="fas fa-calendar-times fa-lg me-2"></i>' + createdFinishMedianNonWorkingDays + 'd');
                $('#kpi-created-finish-median-total-hours').text(createdFinishMedianTotalHours + 'h');
                $('#kpi-created-finish-median-working-hours').text(createdFinishMedianWorkingHours + 'h (' + createdFinishMedianWorkingDays + 'd × 24h)');
                $('#kpi-created-finish-median-non-working-hours').text(createdFinishMedianNonWorkingHours + 'h (' + createdFinishMedianNonWorkingDays + 'd × 24h)');

                // ERP to Delivery (promedio)
                const erpDeliverySeconds = summary?.orders_avg_erp_to_delivery ?? 0;
                const erpDeliveryWorkingDays = Math.round(summary?.orders_avg_erp_to_delivery_working_days ?? 0);
                const erpDeliveryNonWorkingDays = Math.round(summary?.orders_avg_erp_to_delivery_non_working_days ?? 0);
                const erpDeliveryTotalDays = erpDeliveryWorkingDays + erpDeliveryNonWorkingDays;
                const erpDeliveryTotalHours = Math.round(erpDeliverySeconds / 3600);
                const erpDeliveryWorkingHours = erpDeliveryWorkingDays * 24;
                const erpDeliveryNonWorkingHours = erpDeliveryNonWorkingDays * 24;

                $('#kpi-erp-delivery').text(formatSeconds(erpDeliverySeconds));
                $('#kpi-erp-delivery-total-days').html('<i class="fas fa-calendar fa-lg me-2"></i>' + erpDeliveryTotalDays + 'd');
                $('#kpi-erp-delivery-working-days').html('<i class="fas fa-briefcase fa-lg me-2"></i>' + erpDeliveryWorkingDays + 'd');
                $('#kpi-erp-delivery-non-working-days').html('<i class="fas fa-calendar-times fa-lg me-2"></i>' + erpDeliveryNonWorkingDays + 'd');
                $('#kpi-erp-delivery-total-hours').text(erpDeliveryTotalHours + 'h');
                $('#kpi-erp-delivery-working-hours').text(erpDeliveryWorkingHours + 'h (' + erpDeliveryWorkingDays + 'd × 24h)');
                $('#kpi-erp-delivery-non-working-hours').text(erpDeliveryNonWorkingHours + 'h (' + erpDeliveryNonWorkingDays + 'd × 24h)');

                // ERP to Delivery (mediana)
                const erpDeliveryMedianSeconds = summary?.orders_p50_erp_to_delivery ?? 0;
                const erpDeliveryMedianWorkingDays = Math.round(summary?.orders_p50_erp_to_delivery_working_days ?? 0);
                const erpDeliveryMedianNonWorkingDays = Math.round(summary?.orders_p50_erp_to_delivery_non_working_days ?? 0);
                const erpDeliveryMedianTotalDays = erpDeliveryMedianWorkingDays + erpDeliveryMedianNonWorkingDays;
                const erpDeliveryMedianTotalHours = Math.round(erpDeliveryMedianSeconds / 3600);
                const erpDeliveryMedianWorkingHours = erpDeliveryMedianWorkingDays * 24;
                const erpDeliveryMedianNonWorkingHours = erpDeliveryMedianNonWorkingDays * 24;

                $('#kpi-erp-delivery-median').text(formatSeconds(erpDeliveryMedianSeconds));
                $('#kpi-erp-delivery-median-total-days').html('<i class="fas fa-calendar fa-lg me-2"></i>' + erpDeliveryMedianTotalDays + 'd');
                $('#kpi-erp-delivery-median-working-days').html('<i class="fas fa-briefcase fa-lg me-2"></i>' + erpDeliveryMedianWorkingDays + 'd');
                $('#kpi-erp-delivery-median-non-working-days').html('<i class="fas fa-calendar-times fa-lg me-2"></i>' + erpDeliveryMedianNonWorkingDays + 'd');
                $('#kpi-erp-delivery-median-total-hours').text(erpDeliveryMedianTotalHours + 'h');
                $('#kpi-erp-delivery-median-working-hours').text(erpDeliveryMedianWorkingHours + 'h (' + erpDeliveryMedianWorkingDays + 'd × 24h)');
                $('#kpi-erp-delivery-median-non-working-hours').text(erpDeliveryMedianNonWorkingHours + 'h (' + erpDeliveryMedianNonWorkingDays + 'd × 24h)');

                // Gap metrics
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
                                            { label: '<i class="fas fa-inbox text-primary"></i> → <i class="fas fa-check-circle text-success"></i> Pedido Cliente → Fin Producción', value: formatSeconds(fe), color: 'segment-success' },
                                            { label: useActualDelivery ? '<i class="fas fa-inbox text-primary"></i> → <i class="fas fa-truck text-warning"></i> Pedido Cliente → Entrega a Cliente' : '<i class="fas fa-inbox text-primary"></i> → <i class="fas fa-truck text-warning"></i> Pedido Cliente → Entrega a Cliente', value: formatSeconds(de), color: 'segment-warning' },
                                            { label: '<i class="fas fa-industry text-info"></i> → <i class="fas fa-check-circle text-success"></i> Tiempo Lanzamiento Producción → Fin Producción', value: formatSeconds(Math.max(0, fe - ce)), color: 'segment-success' },
                                            { label: useActualDelivery ? '<i class="fas fa-industry text-info"></i> → <i class="fas fa-truck text-warning"></i> Lanzamiento Producción → Entrega a Cliente' : '<i class="fas fa-industry text-info"></i> → <i class="fas fa-truck text-warning"></i> Tiempo Lanzamiento Producción → Entrega a Cliente', value: formatSeconds(Math.max(0, de - ce)), color: 'segment-warning' }
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
                                            { label: '<i class="fas fa-inbox text-primary"></i> → <i class="fas fa-check-circle text-success"></i> Tiempo Pedido Cliente → Fin Producción', value: formatSeconds(fe), color: 'segment-success' },
                                            { label: useActualDelivery ? '<i class="fas fa-inbox text-primary"></i> → <i class="fas fa-truck text-warning"></i> Pedido Cliente → Entrega a Cliente' : '<i class="fas fa-inbox text-primary"></i> → <i class="fas fa-truck text-warning"></i> Pedido Cliente → Entrega a Cliente', value: formatSeconds(de), color: 'segment-warning' },
                                            { label: '<i class="fas fa-industry text-info"></i> → <i class="fas fa-check-circle text-success"></i> Tiempo Lanzamiento Producción → Fin Producción', value: formatSeconds(Math.max(0, fe - ce)), color: 'segment-success' },
                                            { label: useActualDelivery ? '<i class="fas fa-industry text-info"></i> → <i class="fas fa-truck text-warning"></i> Tiempo Lanzamiento Producción → Entrega a Cliente' : '<i class="fas fa-industry text-info"></i> → <i class="fas fa-truck text-warning"></i> Tiempo Lanzamiento Producción → Entrega a Cliente', value: formatSeconds(Math.max(0, de - ce)), color: 'segment-warning' }
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

                // Nueva sección: Análisis de Días Laborables
                const avgTimelineWorking = detail.average_timeline_working ?? {};
                const medianTimelineWorking = detail.median_timeline_working ?? {};
                const avgWorkingBounds = avgTimelineWorking.bounds ?? null;
                const medianWorkingBounds = medianTimelineWorking.bounds ?? null;

                if (avgWorkingBounds || medianWorkingBounds) {
                    html += `
                        <div class="mt-4">
                            <div class="alert alert-info border-0">
                                <h5 class="mb-2"><i class="fas fa-briefcase me-2"></i>Análisis de Tiempos (Solo Días Laborables)</h5>
                                <p class="mb-0 small">Las siguientes gráficas muestran los tiempos calculados usando <strong>únicamente días laborables</strong>, excluyendo fines de semana, festivos y paradas de mantenimiento según el calendario configurado.</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="timeline-card">
                                        <div class="timeline-header d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0">💼 Promedio del rango (días laborables)</h6>
                                            <div class="timeline-legend">
                                                <span><span class="legend-dot segment-primary"></span>${i18n.timelineLegendErpCreated}</span>
                                                <span><span class="legend-dot segment-success"></span>${i18n.timelineLegendCreatedFinished}</span>
                                                <span><span class="legend-dot segment-warning"></span>${useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery}</span>
                                            </div>
                                        </div>
                                        <div id="avg-rangebar-working-${detail.id ?? detail.order_id}" style="height: 240px;"></div>
                                        ${(() => {
                                            const awb = avgWorkingBounds;
                                            if (!awb) return '<div class="text-muted small text-center py-3">No hay datos suficientes</div>';
                                            const items = [
                                                { label: '🟢 Días laborables Pedido Cliente → Fin Producción', value: `${(avgTimelineWorking.erp_to_finished_days ?? 0).toFixed(1)}d (${Math.round(avgTimelineWorking.erp_to_finished_hours ?? 0)}h)`, color: 'segment-success' },
                                                { label: '🔘 Días no laborables Pedido Cliente → Fin Producción', value: `${(avgTimelineWorking.erp_to_finished_non_working_days ?? 0).toFixed(1)}d`, color: 'segment-secondary' },
                                                { label: '🟢 Días laborables Puesto en fabricación → Fin Producción', value: `${(avgTimelineWorking.created_to_finished_days ?? 0).toFixed(1)}d (${Math.round(avgTimelineWorking.created_to_finished_hours ?? 0)}h)`, color: 'segment-success' },
                                                { label: '🔘 Días no laborables Puesto en fabricación → Fin Producción', value: `${(avgTimelineWorking.created_to_finished_non_working_days ?? 0).toFixed(1)}d`, color: 'segment-secondary' }
                                            ];
                                            return `<div class="timeline-summary-inline mt-3">${items.map(item => `
                                                <div class="timeline-chip">
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
                                            <h6 class="mb-0">💼 Mediana del rango (días laborables)</h6>
                                            <div class="timeline-legend">
                                                <span><span class="legend-dot segment-primary"></span>${i18n.timelineLegendErpCreated}</span>
                                                <span><span class="legend-dot segment-success"></span>${i18n.timelineLegendCreatedFinished}</span>
                                                <span><span class="legend-dot segment-warning"></span>${useActualDelivery ? i18n.timelineLegendFinishedActualDelivery : i18n.timelineLegendFinishedDelivery}</span>
                                            </div>
                                        </div>
                                        <div id="median-rangebar-working-${detail.id ?? detail.order_id}" style="height: 240px;"></div>
                                        ${(() => {
                                            const mwb = medianWorkingBounds;
                                            if (!mwb) return '<div class="text-muted small text-center py-3">No hay datos suficientes</div>';
                                            const items = [
                                                { label: '🟢 Días laborables Pedido Cliente → Fin Producción', value: `${(medianTimelineWorking.erp_to_finished_days ?? 0).toFixed(1)}d (${Math.round(medianTimelineWorking.erp_to_finished_hours ?? 0)}h)`, color: 'segment-success' },
                                                { label: '🔘 Días no laborables Pedido Cliente → Fin Producción', value: `${(medianTimelineWorking.erp_to_finished_non_working_days ?? 0).toFixed(1)}d`, color: 'segment-secondary' },
                                                { label: '🟢 Días laborables Puesto en fabricación → Fin Producción', value: `${(medianTimelineWorking.created_to_finished_days ?? 0).toFixed(1)}d (${Math.round(medianTimelineWorking.created_to_finished_hours ?? 0)}h)`, color: 'segment-success' },
                                                { label: '🔘 Días no laborables Puesto en fabricación → Fin Producción', value: `${(medianTimelineWorking.created_to_finished_non_working_days ?? 0).toFixed(1)}d`, color: 'segment-secondary' }
                                            ];
                                            return `<div class="timeline-summary-inline mt-3">${items.map(item => `
                                                <div class="timeline-chip">
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
                }

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
                if (!raw) return 0;
                if (/^-?\d+$/.test(raw)) return parseInt(raw, 10);
                const match = raw.match(/^(-)?(\d{1,3}):(\d{2}):(\d{2})$/);
                if (!match) return 0;
                const sign = match[1] === '-' ? -1 : 1;
                const hours = parseInt(match[2], 10) || 0;
                const minutes = parseInt(match[3], 10) || 0;
                const seconds = parseInt(match[4], 10) || 0;
                return sign * (hours * 3600 + minutes * 60 + seconds);
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

            // ============================================
            // VALIDACIÓN DE DATOS PARA IA
            // ============================================

            /**
             * Verifica si una fila tiene los datos mínimos requeridos para un análisis específico
             * Solo se incluyen filas con datos completos y válidos
             */
            function isValidRowForAnalysis(row, requiredFields) {
                for (const field of requiredFields) {
                    const value = row[field];
                    // Rechazar si es null, undefined, vacío, o fecha inválida
                    if (value === null || value === undefined) return false;
                    if (typeof value === 'string') {
                        const trimmed = value.trim();
                        if (!trimmed || trimmed === '-' || trimmed === '--' ||
                            trimmed === '0000-00-00' || trimmed === '0000-00-00 00:00:00' ||
                            trimmed.toLowerCase() === 'null') return false;
                    }
                    // Rechazar números inválidos o cero en campos de tiempo
                    if (field.includes('_seconds') && (typeof value !== 'number' || value <= 0)) return false;
                }
                return true;
            }

            /**
             * Formatea las métricas con nombres legibles para la IA
             */
            function formatMetricsForAI(metrics, analysisType) {
                const labels = {
                    // Comunes
                    ordersTotal: 'Total de órdenes analizadas',
                    dateRange: 'Período analizado',
                    processesTotal: 'Total de procesos',
                    // ERP → Created
                    avgErpToCreated: 'Tiempo promedio (pedido → lanzamiento)',
                    medianErpToCreated: 'Tiempo mediana (pedido → lanzamiento)',
                    avgErpToCreatedWorkingDays: 'Promedio días laborables',
                    avgErpToCreatedNonWorkingDays: 'Promedio días no laborables',
                    medianErpToCreatedWorkingDays: 'Mediana días laborables',
                    medianErpToCreatedNonWorkingDays: 'Mediana días no laborables',
                    // Created → Finished
                    avgCreatedToFinish: 'Tiempo promedio ciclo producción',
                    medianCreatedToFinish: 'Tiempo mediana ciclo producción',
                    // Delivery
                    avgFinishToDelivery: 'Tiempo promedio (fin → entrega)',
                    deliveriesDelayed: 'Entregas con retraso',
                    slaOnTime: 'Cumplimiento SLA (a tiempo / total)',
                    slaRate: 'Tasa de cumplimiento SLA',
                    deliveryReference: 'Fecha de referencia usada',
                    // Gaps
                    avgGap: 'Tiempo promedio de espera entre procesos',
                    // Otros
                    avgErpToFinish: 'Tiempo promedio total (pedido → fin)',
                    delayedTotal: 'Total de órdenes retrasadas',
                    worstType: 'Tipo de producto con más retrasos',
                    worstAvgDelay: 'Retraso promedio del peor tipo',
                    ordersOverThreshold: 'Órdenes sobre umbral de espera',
                    threshold: 'Umbral de alerta'
                };

                let formatted = '';
                for (const [key, value] of Object.entries(metrics)) {
                    if (key === 'dateRange') continue; // Se muestra aparte como PERÍODO
                    const label = labels[key] || key;
                    formatted += `- ${label}: ${value}\n`;
                }
                return formatted;
            }

            // ============================================
            // DICCIONARIO DE CAMPOS PARA LA IA
            // ============================================
            const fieldDictionaries = {
                'erp-to-created': `
DICCIONARIO DE CAMPOS:
- ID: Identificador numérico secuencial (1, 2, 3...)
- Cliente: Nombre del cliente que realizó el pedido (usar para agrupar por cliente)
- Tiempo_Horas: Tiempo transcurrido en horas decimales (ej: 2.50 = 2h 30min)
- Dias_Laborables: Días hábiles (lunes-viernes, excluyendo festivos)
- Dias_No_Laborables: Fines de semana y festivos
- Dias_Totales: Total de días calendario`,

                'created-to-finished': `
DICCIONARIO DE CAMPOS:
- ID: Identificador numérico secuencial (1, 2, 3...)
- Cliente: Nombre del cliente (usar para agrupar por cliente)
- Tiempo_Horas: Duración del ciclo de producción en horas decimales
- Dias_Laborables: Días hábiles de producción
- Dias_No_Laborables: Días no laborables (fines de semana y festivos)`,

                'finish-to-delivery': `
DICCIONARIO DE CAMPOS:
- ID: Identificador numérico secuencial (1, 2, 3...)
- Cliente: Nombre del cliente (usar para agrupar por cliente)
- Tiempo_Entrega_Horas: Tiempo desde fin de producción hasta entrega en horas decimales
- Retraso_Horas: Diferencia vs fecha planificada en horas (positivo=tarde, negativo=adelantado)
- Es_Retraso: 1 si hay retraso, 0 si está a tiempo o adelantado`,

                'process-gaps': `
DICCIONARIO DE CAMPOS:
- ID: Identificador de la orden
- Proceso: Código y nombre del proceso de fabricación
- Espera_Horas: Tiempo de espera ANTES de este proceso (tiempo no productivo)
- Duracion_Horas: Tiempo de ejecución del proceso (tiempo productivo)`,

                'by-client': `
DICCIONARIO DE CAMPOS:
- Cliente: Nombre del cliente
- Num_Ordenes: Cantidad de órdenes procesadas
- Tiempo_Total_Promedio: Lead time promedio (desde pedido hasta fin producción)
- Tiempo_Produccion_Promedio: Tiempo promedio solo en producción`,

                'bottleneck-analysis': `
DICCIONARIO DE CAMPOS:
- Proceso: Código y nombre del proceso
- Ocurrencias: Veces que aparece este proceso
- Tiempo_Promedio: Duración promedio del proceso
- Espera_Promedio: Tiempo de espera promedio antes del proceso
- Carga_Total: Suma de todos los tiempos de este proceso`,

                'predictive-delays': `
DICCIONARIO DE CAMPOS:
- ID: Identificador de la orden
- Cliente: Nombre del cliente
- Dias_En_Proceso: Días desde que inició la producción
- Fecha_Compromiso: Fecha de entrega prometida
- Dias_Restantes: Días hasta la fecha compromiso (negativo = vencido)
- Procesos_Pendientes: Número de procesos sin completar
- Riesgo: Nivel de riesgo de retraso`
            };

            // Análisis 1: Tiempo Pedido Cliente → Lanzamiento Producción
            function collectErpToCreatedData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Tiempo Pedido Cliente → Lanzamiento Producción' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgErpToCreated: latestSummary?.orders_avg_erp_to_created ? formatSeconds(latestSummary.orders_avg_erp_to_created) : '-',
                    medianErpToCreated: latestSummary?.orders_p50_erp_to_created ? formatSeconds(latestSummary.orders_p50_erp_to_created) : '-',
                    avgErpToCreatedWorkingDays: Math.round(latestSummary?.orders_avg_erp_to_created_working_days ?? 0) + 'd',
                    medianErpToCreatedWorkingDays: Math.round(latestSummary?.orders_p50_erp_to_created_working_days ?? 0) + 'd',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV con columnas numéricas para análisis estadístico (incluye Cliente para agrupar)
                let csv = 'ID,Cliente,Tiempo_Horas,Dias_Laborables,Dias_No_Laborables,Dias_Totales\n';
                let count = 0;
                let skipped = 0;
                const maxRows = 100;

                const rowsData = table.rows({search: 'applied'}).data();

                if (rowsData.length === 0) {
                    return {
                        metrics,
                        csv: csv,
                        type: 'Tiempo Pedido Cliente → Lanzamiento Producción',
                        note: 'Sin datos disponibles'
                    };
                }

                rowsData.each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar TODOS los campos (incluyendo cliente no nulo)
                    if (!row.order_id || !row.customer_client_name || row.customer_client_name.trim() === '') {
                        skipped++;
                        return;
                    }

                    // Validar tiempo numérico
                    const tiempoSec = typeof row.erp_to_created_seconds === 'number' ? row.erp_to_created_seconds : 0;
                    if (tiempoSec <= 0) {
                        skipped++;
                        return;
                    }

                    const diasLab = typeof row.erp_to_created_working_days === 'number' ? row.erp_to_created_working_days : 0;
                    const diasNoLab = typeof row.erp_to_created_non_working_days === 'number' ? row.erp_to_created_non_working_days : 0;
                    const diasTotales = typeof row.erp_to_created_calendar_days === 'number' ? row.erp_to_created_calendar_days : 0;

                    // Convertir a horas
                    const tiempoHoras = (tiempoSec / 3600).toFixed(2);

                    // Limpiar nombre de cliente para CSV (escapar comas y comillas)
                    const clienteClean = cleanValue(row.customer_client_name.trim());

                    csv += `${count + 1},${clienteClean},${tiempoHoras},${diasLab},${diasNoLab},${diasTotales}\n`;
                    count++;
                });

                let note = `${count} órdenes - columnas numéricas para análisis`;
                if (skipped > 0) note += ` (${skipped} omitidas)`;

                return { metrics, csv, type: 'Tiempo Pedido Cliente → Lanzamiento Producción', note };
            }

            // Análisis 2: Tiempo Lanzamiento Producción → Fin Producción
            function collectCreatedToFinishedData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Tiempo Lanzamiento Producción → Fin Producción' };
                }

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgCreatedToFinish: $('#kpi-erp-finish').text() || '-',
                    medianCreatedToFinish: latestSummary?.orders_p50_created_to_finished ? formatSeconds(latestSummary.orders_p50_created_to_finished) : '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV con columnas numéricas para análisis (incluye Cliente para agrupar)
                let csv = 'ID,Cliente,Tiempo_Horas,Dias_Laborables,Dias_No_Laborables\n';
                let count = 0;
                let skipped = 0;
                const maxRows = 100;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar TODOS los campos requeridos (incluyendo cliente)
                    if (!row.order_id || !row.customer_client_name || row.customer_client_name.trim() === '') {
                        skipped++;
                        return;
                    }

                    // Validar tiempos numéricos
                    const tiempoSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;
                    const diasLab = typeof row.created_to_finished_working_days === 'number' ? row.created_to_finished_working_days : 0;
                    const diasNoLab = typeof row.created_to_finished_non_working_days === 'number' ? row.created_to_finished_non_working_days : 0;

                    // Solo incluir si tiene tiempo válido
                    if (tiempoSec <= 0) {
                        skipped++;
                        return;
                    }

                    // Convertir a horas
                    const tiempoHoras = (tiempoSec / 3600).toFixed(2);

                    // Limpiar nombre de cliente para CSV (escapar comas y comillas)
                    const clienteClean = cleanValue(row.customer_client_name.trim());

                    csv += `${count + 1},${clienteClean},${tiempoHoras},${diasLab},${diasNoLab}\n`;
                    count++;
                });

                let note = `${count} órdenes - columnas numéricas para análisis`;
                if (skipped > 0) note += ` (${skipped} omitidas)`;

                return { metrics, csv, type: 'Tiempo Lanzamiento Producción → Fin Producción', note };
            }

            // Análisis adicional: Fin Producción → Entrega
            function collectFinishToDeliveryData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Fin Producción → Entrega' };
                }

                const rows = table.rows({search: 'applied'}).data();
                if (!rows || rows.length === 0) {
                    return { metrics: {}, csv: '', type: 'Fin Producción → Entrega', note: 'Sin datos disponibles' };
                }

                // CSV con columnas numéricas para análisis (incluye Cliente para agrupar)
                let csv = 'ID,Cliente,Tiempo_Entrega_Horas,Retraso_Horas,Es_Retraso\n';
                let count = 0;
                let skipped = 0;
                const maxRows = 100;
                let delayedCount = 0;

                rows.each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar datos (cliente no nulo)
                    if (!row.order_id || !row.customer_client_name || row.customer_client_name.trim() === '') {
                        skipped++;
                        return;
                    }

                    // Tiempo fin a entrega en segundos
                    const tiempoSec = typeof row.finished_to_delivery_seconds === 'number' ? row.finished_to_delivery_seconds : 0;
                    const retrasoSec = typeof row.order_delivery_delay_seconds === 'number' ? row.order_delivery_delay_seconds : 0;

                    // Solo incluir si tiene tiempo válido
                    if (tiempoSec === 0 && retrasoSec === 0) {
                        skipped++;
                        return;
                    }

                    const esRetraso = retrasoSec > 0 ? 1 : 0;
                    if (esRetraso) delayedCount++;

                    // Convertir a horas (retraso puede ser negativo = adelantado)
                    const tiempoHoras = (tiempoSec / 3600).toFixed(2);
                    const retrasoHoras = (retrasoSec / 3600).toFixed(2);

                    // Limpiar nombre de cliente para CSV (escapar comas y comillas)
                    const clienteClean = cleanValue(row.customer_client_name.trim());

                    csv += `${count + 1},${clienteClean},${tiempoHoras},${retrasoHoras},${esRetraso}\n`;
                    count++;
                });

                const metrics = {
                    ordersTotal: count,
                    deliveriesDelayed: delayedCount,
                    delayedPct: count > 0 ? ((delayedCount / count) * 100).toFixed(1) : 0,
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let note = `${count} órdenes - columnas numéricas (Es_Retraso: 1=sí, 0=no)`;
                if (skipped > 0) note += ` - ${skipped} omitidas`;

                return { metrics, csv, type: 'Fin Producción → Entrega', note };
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

                // CSV con SOLO columnas numéricas para correlaciones
                let csv = 'ID,Tiempo_Total_Horas,Tiempo_Produccion_Horas,Tiempo_Admin_Horas,Ratio_Produccion\n';
                let count = 0;
                let skipped = 0;
                const maxRows = 100;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar datos mínimos
                    if (!row.order_id || !row.customer_client_name || String(row.customer_client_name).trim() === '') {
                        skipped++;
                        return;
                    }

                    // Solo incluir si tiene tiempo total válido
                    const tiempoTotalSec = typeof row.erp_to_finished_seconds === 'number' ? row.erp_to_finished_seconds : null;
                    if (tiempoTotalSec === null || tiempoTotalSec <= 0) {
                        skipped++;
                        return;
                    }

                    const tiempoProduccionSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;
                    const tiempoAdminSec = typeof row.erp_to_created_seconds === 'number' ? row.erp_to_created_seconds : 0;

                    // Convertir a horas con decimales
                    const totalHoras = (tiempoTotalSec / 3600).toFixed(2);
                    const produccionHoras = (tiempoProduccionSec / 3600).toFixed(2);
                    const adminHoras = (tiempoAdminSec / 3600).toFixed(2);
                    const ratioProduccion = tiempoTotalSec > 0 ? ((tiempoProduccionSec / tiempoTotalSec) * 100).toFixed(2) : '0.00';

                    csv += `${count + 1},${totalHoras},${produccionHoras},${adminHoras},${ratioProduccion}\n`;
                    count++;
                });

                let note = `${count} registros - columnas numéricas (horas)`;
                if (skipped > 0) note += ` (${skipped} omitidos)`;

                return { metrics, csv, type: 'Rendimiento de Órdenes', note };
            }

            // Análisis adicional: Órdenes críticas por tipo de producto
            function collectOrderTypeCriticalData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Órdenes críticas por tipo' };
                }

                const rows = table.rows({search: 'applied'}).data();
                if (!rows || rows.length === 0) {
                    return { metrics: {}, csv: '', type: 'Órdenes críticas por tipo', note: 'Sin datos disponibles' };
                }

                // CSV con columnas numéricas para análisis
                let csv = 'ID,Retraso_Horas,Es_Retraso,Tiempo_Total_Horas\n';
                const maxRows = 100;
                let count = 0;
                let skipped = 0;
                let delayedTotal = 0;

                rows.each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar datos (cliente no nulo)
                    if (!row.order_id || !row.customer_client_name || row.customer_client_name.trim() === '') {
                        skipped++;
                        return;
                    }

                    const delaySec = typeof row.order_delivery_delay_seconds === 'number' ? row.order_delivery_delay_seconds : 0;
                    const tiempoTotalSec = typeof row.erp_to_finished_seconds === 'number' ? row.erp_to_finished_seconds : 0;

                    // Solo incluir si tiene tiempo total válido
                    if (tiempoTotalSec <= 0) {
                        skipped++;
                        return;
                    }

                    const esRetraso = delaySec > 0 ? 1 : 0;
                    if (esRetraso) delayedTotal++;

                    // Convertir a horas
                    const retrasoHoras = (delaySec / 3600).toFixed(2);
                    const tiempoTotalHoras = (tiempoTotalSec / 3600).toFixed(2);

                    csv += `${count + 1},${retrasoHoras},${esRetraso},${tiempoTotalHoras}\n`;
                    count++;
                });

                const metrics = {
                    ordersTotal: count,
                    delayedTotal,
                    delayedPct: count > 0 ? ((delayedTotal / count) * 100).toFixed(1) : 0,
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let note = `${count} órdenes - columnas numéricas (Es_Retraso: 1=sí, 0=no)`;
                if (skipped > 0) note += ` - ${skipped} omitidas`;

                return { metrics, csv, type: 'Órdenes críticas por tipo', note };
            }

            // Análisis adicional: Alertas de brechas acumuladas
            function collectGapAlertsData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Alertas de brechas' };
                }

                const rows = table.rows({search: 'applied'}).data();
                if (!rows || rows.length === 0) {
                    return { metrics: {}, csv: '', type: 'Alertas de brechas', note: 'Sin datos disponibles' };
                }

                // CSV con SOLO columnas numéricas para correlaciones
                let csv = 'ID,Procesos,Espera_Total_Horas,Espera_Max_Horas,Espera_Promedio_Horas\n';
                const thresholdSeconds = 2 * 3600; // 2 horas
                const maxRows = 100;
                let count = 0;
                let skipped = 0;
                let ordersOverThreshold = 0;

                rows.each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar datos requeridos
                    if (!row.order_id || !row.customer_client_name || row.customer_client_name.trim() === '') {
                        skipped++;
                        return;
                    }

                    const processes = Array.isArray(row.processes) ? row.processes : [];
                    if (!processes.length) {
                        skipped++;
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
                            if (gapSeconds > maxGap) maxGap = gapSeconds;
                        }
                    });

                    if (gapsCount === 0) {
                        skipped++;
                        return;
                    }

                    if (totalGap >= thresholdSeconds) {
                        ordersOverThreshold++;
                    }

                    // Convertir a horas con decimales
                    const totalHoras = (totalGap / 3600).toFixed(2);
                    const maxHoras = (maxGap / 3600).toFixed(2);
                    const avgHoras = gapsCount > 0 ? (totalGap / gapsCount / 3600).toFixed(2) : '0.00';

                    csv += `${count + 1},${gapsCount},${totalHoras},${maxHoras},${avgHoras}\n`;
                    count++;
                });

                const metrics = {
                    ordersTotal: count,
                    ordersOverThreshold,
                    threshold: '2h',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let note = `${count} registros - columnas numéricas (horas decimales)`;
                if (skipped > 0) note += ` (${skipped} omitidos)`;

                return { metrics, csv, type: 'Alertas de brechas', note };
            }

            // Análisis 4: Tiempos de espera por Proceso
            function collectProcessGapsData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Tiempos de espera por Proceso' };
                }

                const metrics = {
                    avgGap: $('#kpi-gap').text() || '-',
                    processesTotal: $('#kpi-processes-total').text() || '0',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV con columnas numéricas para análisis
                let csv = 'ID,Espera_Horas,Duracion_Horas,Ratio_Espera\n';
                let count = 0;
                let skipped = 0;
                const maxRows = 100;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar orden y cliente
                    if (!row.order_id || !row.customer_client_name) {
                        skipped++;
                        return;
                    }

                    const processes = row.processes || [];
                    if (!Array.isArray(processes) || processes.length === 0) {
                        skipped++;
                        return;
                    }

                    processes.forEach(proc => {
                        if (count >= maxRows) return;

                        // Solo procesos con duración válida
                        const duracionSec = typeof proc.duration_seconds === 'number' ? proc.duration_seconds : 0;
                        const esperaSec = typeof proc.gap_seconds === 'number' ? proc.gap_seconds : 0;

                        if (duracionSec <= 0) return;

                        // Convertir a horas
                        const esperaHoras = (esperaSec / 3600).toFixed(2);
                        const duracionHoras = (duracionSec / 3600).toFixed(2);
                        // Ratio: qué porcentaje del tiempo es espera vs trabajo
                        const ratioEspera = duracionSec > 0 ? ((esperaSec / (esperaSec + duracionSec)) * 100).toFixed(1) : 0;

                        csv += `${count + 1},${esperaHoras},${duracionHoras},${ratioEspera}\n`;
                        count++;
                    });
                });

                let note = `${count} procesos - columnas numéricas para análisis`;
                if (skipped > 0) note += ` - ${skipped} órdenes omitidas`;

                return { metrics, csv, type: 'Tiempos de espera por Proceso', note };
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
                let skipped = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    // Validar datos mínimos
                    if (!row.order_id || !row.customer_client_name || String(row.customer_client_name).trim() === '') {
                        skipped++;
                        return;
                    }

                    // Requiere tiempo total válido
                    const tiempoTotalSec = typeof row.erp_to_finished_seconds === 'number' ? row.erp_to_finished_seconds : null;
                    if (tiempoTotalSec === null || tiempoTotalSec <= 0) {
                        skipped++;
                        return;
                    }

                    const cliente = String(row.customer_client_name).trim();
                    if (!clientData[cliente]) {
                        clientData[cliente] = {
                            count: 0,
                            sumaTotalSec: 0,
                            sumaProduccionSec: 0,
                            ordenesConProduccion: 0
                        };
                    }

                    clientData[cliente].count++;
                    clientData[cliente].sumaTotalSec += tiempoTotalSec;

                    const tiempoProduccionSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;
                    if (tiempoProduccionSec > 0) {
                        clientData[cliente].sumaProduccionSec += tiempoProduccionSec;
                        clientData[cliente].ordenesConProduccion++;
                    }

                    totalOrders++;
                });

                // CSV con SOLO columnas numéricas para correlaciones
                let csv = 'ID,Num_Ordenes,Promedio_Total_Horas,Promedio_Produccion_Horas,Ratio_Produccion\n';
                let clientCount = 0;

                for (const [cliente, data] of Object.entries(clientData)) {
                    const promedioTotalHoras = ((data.sumaTotalSec / data.count) / 3600).toFixed(2);
                    const promedioProduccionHoras = data.ordenesConProduccion > 0
                        ? ((data.sumaProduccionSec / data.ordenesConProduccion) / 3600).toFixed(2)
                        : '0.00';
                    const ratioProduccion = data.sumaTotalSec > 0
                        ? ((data.sumaProduccionSec / data.sumaTotalSec) * 100).toFixed(2)
                        : '0.00';

                    csv += `${clientCount + 1},${data.count},${promedioTotalHoras},${promedioProduccionHoras},${ratioProduccion}\n`;
                    clientCount++;
                }

                let note = `${totalOrders} órdenes de ${clientCount} clientes - columnas numéricas`;
                if (skipped > 0) note += ` (${skipped} omitidos)`;

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

                // Recolectar procesos con datos válidos
                const allProcesses = [];

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (!row.order_id) return;

                    const processes = row.processes || [];
                    if (!Array.isArray(processes)) return;

                    processes.forEach(proc => {
                        // Solo incluir procesos con duración válida > 0
                        const durationSec = typeof proc.duration_seconds === 'number' ? proc.duration_seconds : 0;
                        if (durationSec <= 0) return;

                        // Requiere al menos código o nombre de proceso
                        if (!proc.process_code && !proc.process_name) return;

                        // Tiempo de espera en segundos
                        const gapSec = typeof proc.gap_seconds === 'number' ? proc.gap_seconds : 0;

                        allProcesses.push({
                            orderId: row.order_id,
                            proceso: proc.process_code || proc.process_name,
                            durationSec: durationSec,
                            gapSec: gapSec
                        });
                    });
                });

                // Ordenar por duración (descendente) y tomar top 30
                allProcesses.sort((a, b) => b.durationSec - a.durationSec);
                const slowest = allProcesses.slice(0, 30);

                // CSV con valores numéricos (horas con decimales) - sin columnas de texto
                let csv = 'ID,Duracion_Horas,Espera_Horas,Ratio_Espera\n';
                let csvCount = 0;
                slowest.forEach(proc => {
                    const duracionHoras = (proc.durationSec / 3600).toFixed(2);
                    const esperaHoras = (proc.gapSec / 3600).toFixed(2);
                    // Ratio: porcentaje de espera respecto al total (espera + duración)
                    const ratioEspera = proc.durationSec > 0 ? ((proc.gapSec / (proc.gapSec + proc.durationSec)) * 100).toFixed(1) : '0.0';
                    csv += `${csvCount + 1},${duracionHoras},${esperaHoras},${ratioEspera}\n`;
                    csvCount++;
                });

                const note = `Top 30 procesos más lentos de ${allProcesses.length} válidos (tiempos en horas)`;
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

                // Solo incluir órdenes con datos completos
                const allOrders = [];
                let skipped = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (!row.order_id || !row.customer_client_name || String(row.customer_client_name).trim() === '') {
                        skipped++;
                        return;
                    }

                    const tiempoTotalSec = typeof row.erp_to_finished_seconds === 'number' ? row.erp_to_finished_seconds : null;
                    const tiempoProduccionSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;
                    const tiempoAdminSec = typeof row.erp_to_created_seconds === 'number' ? row.erp_to_created_seconds : 0;

                    if (tiempoTotalSec === null || tiempoTotalSec <= 0) {
                        skipped++;
                        return;
                    }

                    allOrders.push({
                        tiempoTotalSec: tiempoTotalSec,
                        tiempoProduccionSec: tiempoProduccionSec,
                        tiempoAdminSec: tiempoAdminSec
                    });
                });

                // Ordenar por tiempo total y tomar Top 10 y Bottom 10
                allOrders.sort((a, b) => a.tiempoTotalSec - b.tiempoTotalSec);
                const top10 = allOrders.slice(0, 10);
                const bottom10 = allOrders.slice(-10).reverse();

                // CSV con SOLO columnas numéricas para correlaciones
                let csv = 'ID,Es_Top,Tiempo_Total_Horas,Tiempo_Produccion_Horas,Tiempo_Admin_Horas,Ratio_Produccion\n';
                let count = 0;

                top10.forEach(o => {
                    const totalHoras = (o.tiempoTotalSec / 3600).toFixed(2);
                    const prodHoras = (o.tiempoProduccionSec / 3600).toFixed(2);
                    const adminHoras = (o.tiempoAdminSec / 3600).toFixed(2);
                    const ratio = o.tiempoTotalSec > 0 ? ((o.tiempoProduccionSec / o.tiempoTotalSec) * 100).toFixed(2) : '0.00';
                    csv += `${count + 1},1,${totalHoras},${prodHoras},${adminHoras},${ratio}\n`;
                    count++;
                });

                bottom10.forEach(o => {
                    const totalHoras = (o.tiempoTotalSec / 3600).toFixed(2);
                    const prodHoras = (o.tiempoProduccionSec / 3600).toFixed(2);
                    const adminHoras = (o.tiempoAdminSec / 3600).toFixed(2);
                    const ratio = o.tiempoTotalSec > 0 ? ((o.tiempoProduccionSec / o.tiempoTotalSec) * 100).toFixed(2) : '0.00';
                    csv += `${count + 1},0,${totalHoras},${prodHoras},${adminHoras},${ratio}\n`;
                    count++;
                });

                let note = `Top 10 vs Bottom 10 de ${allOrders.length} órdenes - columnas numéricas`;
                if (skipped > 0) note += ` (${skipped} omitidos)`;

                return { metrics, csv, type: 'Comparativa Top/Bottom', note };
            }

            // NUEVO: Análisis de Eficiencia con Días Laborables
            function collectWorkingDaysEfficiencyData() {
                const table = $('#production-times-table').DataTable();
                if (!table) return { metrics: {}, csv: '', type: 'Eficiencia Días Laborables' };

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgWorkingDays: Math.round(latestSummary?.orders_avg_created_to_finished_working_days ?? 0) + 'd',
                    avgCalendarDays: Math.round(latestSummary?.orders_avg_created_to_finished_calendar_days ?? 0) + 'd',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV con TODAS las columnas numéricas para correlaciones
                let csv = 'ID,Dias_Calendario,Dias_Laborables,Dias_No_Laborables,Tiempo_Produccion_Horas,Eficiencia_Pct\n';
                let count = 0;
                let skipped = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= 100) return false;

                    // Validar datos requeridos
                    if (!row.order_id || !row.customer_client_name) {
                        skipped++;
                        return;
                    }

                    const diasCal = typeof row.created_to_finished_calendar_days === 'number' ? row.created_to_finished_calendar_days : 0;
                    const diasLab = typeof row.created_to_finished_working_days === 'number' ? row.created_to_finished_working_days : 0;
                    const diasNoLab = typeof row.created_to_finished_non_working_days === 'number' ? row.created_to_finished_non_working_days : 0;
                    const tiempoProdSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;

                    // Solo incluir órdenes con días válidos > 0
                    if (diasCal <= 0 || tiempoProdSec <= 0) {
                        skipped++;
                        return;
                    }

                    // Todas las columnas numéricas (sin símbolos %)
                    const eficienciaPct = ((diasLab / diasCal) * 100).toFixed(1);
                    const tiempoProdHoras = (tiempoProdSec / 3600).toFixed(2);

                    csv += `${count + 1},${diasCal},${diasLab},${diasNoLab},${tiempoProdHoras},${eficienciaPct}\n`;
                    count++;
                });

                let note = `${count} órdenes - todas columnas numéricas para análisis`;
                if (skipped > 0) note += ` (${skipped} omitidas)`;

                return { metrics, csv, type: 'Eficiencia Días Laborables', note };
            }

            // NUEVO: Impacto del Calendario Laboral
            function collectCalendarImpactData() {
                const table = $('#production-times-table').DataTable();
                if (!table) return { metrics: {}, csv: '', type: 'Impacto Calendario Laboral' };

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgNonWorkingDays: Math.round(
                        (latestSummary?.orders_avg_erp_to_created_non_working_days ?? 0) +
                        (latestSummary?.orders_avg_created_to_finished_non_working_days ?? 0)
                    ) + 'd',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV con TODAS las columnas numéricas para correlaciones
                let csv = 'ID,Dias_Laborables,Dias_No_Laborables,Dias_Totales,Tiempo_Total_Horas,Impacto_Pct\n';
                let count = 0;
                let skipped = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= 100) return false;

                    if (!row.order_id || !row.customer_client_name) {
                        skipped++;
                        return;
                    }

                    const erpCreNoLab = typeof row.erp_to_created_non_working_days === 'number' ? row.erp_to_created_non_working_days : 0;
                    const creFinNoLab = typeof row.created_to_finished_non_working_days === 'number' ? row.created_to_finished_non_working_days : 0;
                    const erpCreLab = typeof row.erp_to_created_working_days === 'number' ? row.erp_to_created_working_days : 0;
                    const creFinLab = typeof row.created_to_finished_working_days === 'number' ? row.created_to_finished_working_days : 0;
                    const tiempoTotalSec = typeof row.erp_to_finished_seconds === 'number' ? row.erp_to_finished_seconds : 0;

                    const totalNoLab = erpCreNoLab + creFinNoLab;
                    const totalLab = erpCreLab + creFinLab;
                    const totalDias = totalNoLab + totalLab;

                    // Solo incluir si hay datos válidos
                    if (totalDias <= 0 || tiempoTotalSec <= 0) {
                        skipped++;
                        return;
                    }

                    // Todas las columnas numéricas (sin símbolos %)
                    const impactoPct = ((totalNoLab / totalDias) * 100).toFixed(1);
                    const tiempoTotalHoras = (tiempoTotalSec / 3600).toFixed(2);

                    csv += `${count + 1},${totalLab},${totalNoLab},${totalDias},${tiempoTotalHoras},${impactoPct}\n`;
                    count++;
                });

                let note = `${count} órdenes - todas columnas numéricas para análisis`;
                if (skipped > 0) note += ` (${skipped} omitidas)`;

                return { metrics, csv, type: 'Impacto Calendario Laboral', note };
            }

            // NUEVO: Detección de Cuellos de Botella
            function collectBottleneckAnalysisData() {
                return collectProcessGapsData(); // Reutiliza análisis de tiempos de espera pero con contexto diferente
            }

            // NUEVO: Planificación de Capacidad
            function collectCapacityPlanningData() {
                const table = $('#production-times-table').DataTable();
                if (!table) return { metrics: {}, csv: '', type: 'Planificación de Capacidad' };

                // Agrupar órdenes por semana
                const periodData = {};
                let skipped = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    // Validar datos (cliente no nulo)
                    if (!row.order_id || !row.customer_client_name || !row.created_at) {
                        skipped++;
                        return;
                    }

                    const fechaCreado = row.created_at;
                    if (fechaCreado === '0000-00-00 00:00:00') {
                        skipped++;
                        return;
                    }

                    const date = new Date(fechaCreado);
                    if (isNaN(date.getTime())) {
                        skipped++;
                        return;
                    }

                    // Obtener semana (formato: YYYY-Wxx)
                    const year = date.getFullYear();
                    const weekNum = getWeekNumber(date);
                    const periodo = `${year}-W${String(weekNum).padStart(2, '0')}`;

                    if (!periodData[periodo]) {
                        periodData[periodo] = {
                            ordenes: 0,
                            tiempoTotalSec: 0,
                            ordenesConTiempo: 0
                        };
                    }

                    periodData[periodo].ordenes++;

                    // Sumar tiempo de producción solo si es válido
                    const tiempoSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;
                    if (tiempoSec > 0) {
                        periodData[periodo].tiempoTotalSec += tiempoSec;
                        periodData[periodo].ordenesConTiempo++;
                    }
                });

                // CSV con columnas numéricas
                let csv = 'Semana,Num_Ordenes,Tiempo_Total_Horas,Tiempo_Promedio_Horas\n';

                const periodosOrdenados = Object.keys(periodData).sort();

                periodosOrdenados.forEach(periodo => {
                    const data = periodData[periodo];
                    const tiempoPromedioSec = data.ordenesConTiempo > 0 ? Math.round(data.tiempoTotalSec / data.ordenesConTiempo) : 0;

                    // Convertir a horas
                    const tiempoTotalHoras = (data.tiempoTotalSec / 3600).toFixed(2);
                    const tiempoPromedioHoras = (tiempoPromedioSec / 3600).toFixed(2);

                    // Extraer solo número de semana para análisis numérico
                    const semanaNum = parseInt(periodo.split('-W')[1], 10);

                    csv += `${semanaNum},${data.ordenes},${tiempoTotalHoras},${tiempoPromedioHoras}\n`;
                });

                const metrics = {
                    periodosAnalizados: periodosOrdenados.length,
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let note = `${periodosOrdenados.length} semanas - columnas numéricas para análisis`;
                if (skipped > 0) note += ` - ${skipped} omitidas`;

                return { metrics, csv, type: 'Planificación de Capacidad', note };
            }

            // Función auxiliar para calcular número de semana
            function getWeekNumber(date) {
                const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
                const dayNum = d.getUTCDay() || 7;
                d.setUTCDate(d.getUTCDate() + 4 - dayNum);
                const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            }

            // NUEVO: Predicción de Retrasos
            function collectPredictiveDelaysData() {
                const table = $('#production-times-table').DataTable();
                if (!table) return { metrics: {}, csv: '', type: 'Predicción de Retrasos' };

                const now = new Date();

                // CSV con columnas numéricas para análisis
                let csv = 'ID,Dias_En_Proceso,Retraso_Horas,Riesgo_Score,Tiempo_Produccion_Horas\n';

                let count = 0;
                let skipped = 0;
                const maxRows = 100;
                let highRiskCount = 0;
                let mediumRiskCount = 0;
                let lowRiskCount = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar datos (cliente no nulo)
                    if (!row.order_id || !row.customer_client_name || !row.created_at) {
                        skipped++;
                        return;
                    }

                    // Calcular días en proceso
                    const fechaCreado = new Date(row.created_at);
                    if (isNaN(fechaCreado.getTime())) {
                        skipped++;
                        return;
                    }

                    const diasEnProceso = Math.floor((now - fechaCreado) / (1000 * 60 * 60 * 24));

                    // Calcular riesgo numérico (0-100)
                    let riesgoScore = 0;

                    // Factor: días en proceso vs promedio
                    const promediosDias = latestSummary?.orders_avg_created_to_finished_calendar_days || 5;
                    if (diasEnProceso > promediosDias * 1.5) {
                        riesgoScore += 40;
                    } else if (diasEnProceso > promediosDias) {
                        riesgoScore += 20;
                    }

                    // Factor: retraso actual
                    const retrasoSec = typeof row.order_delivery_delay_seconds === 'number' ? row.order_delivery_delay_seconds : 0;
                    if (retrasoSec > 0) {
                        const diasRetraso = Math.floor(retrasoSec / 86400);
                        if (diasRetraso > 2) riesgoScore += 40;
                        else if (diasRetraso > 0) riesgoScore += 20;
                    }

                    // Contar por categoría
                    if (riesgoScore >= 60) highRiskCount++;
                    else if (riesgoScore >= 30) mediumRiskCount++;
                    else lowRiskCount++;

                    // Tiempo de producción
                    const tiempoProdSec = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : 0;

                    // Convertir a horas
                    const retrasoHoras = (retrasoSec / 3600).toFixed(2);
                    const tiempoProdHoras = (tiempoProdSec / 3600).toFixed(2);

                    csv += `${count + 1},${diasEnProceso},${retrasoHoras},${riesgoScore},${tiempoProdHoras}\n`;
                    count++;
                });

                const metrics = {
                    ordersAnalyzed: count,
                    highRisk: highRiskCount,
                    mediumRisk: mediumRiskCount,
                    lowRisk: lowRiskCount,
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let note = `${count} órdenes - Riesgo_Score: 0-100 (60+=alto, 30-59=medio, <30=bajo)`;
                if (skipped > 0) note += ` - ${skipped} omitidas`;

                return { metrics, csv, type: 'Predicción de Retrasos', note };
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
                    avgErpToFinish: $('#kpi-erp-finish').text() || '-',
                    avgGap: $('#kpi-gap').text() || '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV con SOLO columnas numéricas para correlaciones
                let csv = 'ID,Tiempo_Admin_Horas,Tiempo_Produccion_Horas,Tiempo_Total_Horas,Ratio_Admin,Ratio_Produccion\n';
                let count = 0;
                let skipped = 0;
                const maxRows = 100;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;

                    // Validar ID y cliente existen
                    if (!row.order_id || !row.customer_client_name || String(row.customer_client_name).trim() === '') {
                        skipped++;
                        return;
                    }

                    // Validar TODOS los tiempos sean numéricos
                    const tiempoAdmin = typeof row.erp_to_created_seconds === 'number' ? row.erp_to_created_seconds : null;
                    const tiempoProduccion = typeof row.created_to_finished_seconds === 'number' ? row.created_to_finished_seconds : null;
                    const tiempoTotal = typeof row.erp_to_finished_seconds === 'number' ? row.erp_to_finished_seconds : null;

                    // Solo incluir si TODOS los tiempos son válidos y > 0
                    if (tiempoAdmin === null || tiempoProduccion === null || tiempoTotal === null) {
                        skipped++;
                        return;
                    }
                    if (tiempoTotal <= 0) {
                        skipped++;
                        return;
                    }

                    // Convertir segundos a horas (con 2 decimales)
                    const adminHoras = (tiempoAdmin / 3600).toFixed(2);
                    const produccionHoras = (tiempoProduccion / 3600).toFixed(2);
                    const totalHoras = (tiempoTotal / 3600).toFixed(2);

                    // Calcular ratios (% del total)
                    const ratioAdmin = tiempoTotal > 0 ? ((tiempoAdmin / tiempoTotal) * 100).toFixed(2) : '0.00';
                    const ratioProduccion = tiempoTotal > 0 ? ((tiempoProduccion / tiempoTotal) * 100).toFixed(2) : '0.00';

                    csv += `${count + 1},${adminHoras},${produccionHoras},${totalHoras},${ratioAdmin},${ratioProduccion}\n`;
                    count++;
                });

                let note = `${count} registros - columnas numéricas (horas y porcentajes)`;
                if (skipped > 0) note += ` (${skipped} omitidos)`;

                return { metrics, csv, type: 'Análisis Total', note };
            }

            async function startAiTask(fullPrompt, userPromptForDisplay, agentType = 'supervisor') {
                try {
                    console.log('[AI][Production Times] Iniciando análisis:', userPromptForDisplay);
                    console.log('[AI] Prompt length:', fullPrompt.length, 'caracteres');
                    console.log('[AI] Agente seleccionado:', agentType);

                    // Mostrar modal de procesamiento
                    $('#aiProcessingTitle').text(userPromptForDisplay);
                    $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando solicitud a IA...');
                    const processingModal = new bootstrap.Modal(document.getElementById('aiProcessingModal'));
                    processingModal.show();

                    const fd = new FormData();
                    fd.append('prompt', fullPrompt);
                    fd.append('agent', agentType);

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
                    console.log('[AI] Iniciando polling cada 5 segundos (timeout: 10 minutos)...');

                    // Actualizar estado
                    $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... Esperando respuesta...');

                    // Timeout de 10 minutos (600 segundos = 120 polls de 5 segundos)
                    const MAX_POLL_COUNT = 120;
                    const POLL_INTERVAL = 5000;

                    let done = false; let last; let pollCount = 0;
                    while (!done) {
                        pollCount++;

                        // Verificar timeout
                        if (pollCount > MAX_POLL_COUNT) {
                            console.error('[AI] Timeout: La IA tardó más de 10 minutos en responder');
                            bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal'))?.hide();
                            alert('La solicitud ha excedido el tiempo máximo de espera (10 minutos). Por favor, intenta con menos datos o un análisis más simple.');
                            return;
                        }

                        const elapsedMinutes = Math.floor((pollCount * 5) / 60);
                        const elapsedSeconds = (pollCount * 5) % 60;
                        const timeDisplay = elapsedMinutes > 0
                            ? `${elapsedMinutes}m ${elapsedSeconds}s`
                            : `${elapsedSeconds}s`;

                        console.log(`[AI] Polling #${pollCount} - Esperando 5 segundos...`);
                        $('#aiProcessingStatus').html(`<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... (${timeDisplay})`);
                        await new Promise(r => setTimeout(r, POLL_INTERVAL));
                        
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

                    // Convertir Markdown a HTML
                    const htmlTarget = $('#aiResultHtml');
                    if (window.marked && window.DOMPurify) {
                        try {
                            // Convertir Markdown a HTML
                            let htmlContent = marked.parse(rawText || '');

                            // Agregar clases de Bootstrap a las tablas
                            htmlContent = htmlContent.replace(/<table>/g, '<table class="table table-striped table-bordered table-hover">');

                            // Sanitizar el HTML
                            const sanitized = DOMPurify.sanitize(htmlContent, {
                                ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'style', 'src', 'alt', 'title', 'colspan', 'rowspan'],
                                ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                                              'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
                                              'a', 'code', 'pre', 'blockquote', 'hr', 'span', 'div']
                            });

                            htmlTarget.html(sanitized && sanitized.trim() ? sanitized : '<p class="text-muted mb-0">Sin contenido para mostrar.</p>');
                        } catch (err) {
                            console.error('[AI] Error al parsear Markdown:', err);
                            htmlTarget.html('<pre class="bg-light p-3 rounded">' + $('<div>').text(rawText).html() + '</pre>');
                        }
                    } else {
                        htmlTarget.html('<pre class="bg-light p-3 rounded">' + $('<div>').text(rawText).html() + '</pre>');
                    }

                    // Mostrar por defecto el tab de HTML Interpretado (ahora con Markdown parseado)
                    const renderedTabTrigger = document.getElementById('ai-tab-rendered');
                    if (renderedTabTrigger && bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(renderedTabTrigger).show();
                    }

                    // Actualizar timestamp y estadísticas
                    const now = new Date();
                    $('#aiResultTimestamp').text(now.toLocaleString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }));

                    // Calcular estadísticas del texto
                    const words = rawText.trim().split(/\s+/).length;
                    const lines = rawText.split('\n').length;
                    const chars = rawText.length;
                    $('#aiResultStats').text(`${words} palabras, ${lines} líneas, ${chars} caracteres`);

                    const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
                    resultModal.show();

                    // Inicializar funcionalidades del modal después de mostrarlo
                    setTimeout(() => {
                        initAIResultModalFeatures(rawText, userPromptForDisplay);
                    }, 100);
                } catch (err) {
                    console.error('[AI] Unexpected error:', err);
                    // Cerrar modal de procesamiento si está abierto
                    const procModal = bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal'));
                    if (procModal) procModal.hide();
                    alert('{{ __("Error al procesar solicitud de IA") }}');
                }
            }

            // Función para inicializar las funcionalidades del modal de resultados IA
            function initAIResultModalFeatures(rawText, analysisType) {
                let currentFontSize = 100; // Porcentaje

                // Copiar al portapapeles
                $('#btnCopyResult').off('click').on('click', function() {
                    navigator.clipboard.writeText(rawText).then(() => {
                        showToast('✓ Copiado al portapapeles', 'success');
                        $(this).html('<i class="fas fa-check"></i>');
                        setTimeout(() => {
                            $(this).html('<i class="fas fa-copy"></i>');
                        }, 2000);
                    }).catch(err => {
                        console.error('Error al copiar:', err);
                        showToast('Error al copiar', 'error');
                    });
                });

                // Descargar archivo
                $('#btnDownloadResult').off('click').on('click', function() {
                    const blob = new Blob([rawText], { type: 'text/markdown;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                    a.download = `analisis-ia-${timestamp}.md`;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    showToast('✓ Archivo descargado', 'success');
                });

                // Imprimir
                $('#btnPrintResult').off('click').on('click', function() {
                    const printWindow = window.open('', '', 'width=800,height=600');
                    const htmlContent = $('#aiResultHtml').html();
                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Análisis IA - ${analysisType}</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                            <style>
                                body { padding: 20px; font-family: Arial, sans-serif; }
                                table { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
                                table thead th { background-color: #0d6efd; color: white; padding: 0.5rem; }
                                table tbody td { padding: 0.5rem; border: 1px solid #dee2e6; }
                                h1, h2, h3 { margin-top: 1.5rem; color: #2c3e50; }
                                @media print {
                                    .no-print { display: none; }
                                }
                            </style>
                        </head>
                        <body>
                            <h1>Análisis IA: ${analysisType}</h1>
                            <p><small>Generado: ${new Date().toLocaleString('es-ES')}</small></p>
                            <hr>
                            ${htmlContent}
                        </body>
                        </html>
                    `);
                    printWindow.document.close();
                    setTimeout(() => {
                        printWindow.print();
                        printWindow.close();
                    }, 250);
                });

                // Pantalla completa
                let isFullscreen = false;
                $('#btnFullscreen').off('click').on('click', function() {
                    const dialog = $('#aiResultModalDialog');
                    if (!isFullscreen) {
                        dialog.addClass('modal-fullscreen-custom');
                        $(this).html('<i class="fas fa-compress"></i>');
                        isFullscreen = true;
                    } else {
                        dialog.removeClass('modal-fullscreen-custom');
                        $(this).html('<i class="fas fa-expand"></i>');
                        isFullscreen = false;
                    }
                });

                // Control de tamaño de fuente
                $('#btnFontIncrease').off('click').on('click', function() {
                    if (currentFontSize < 150) {
                        currentFontSize += 10;
                        updateFontSize();
                    }
                });

                $('#btnFontDecrease').off('click').on('click', function() {
                    if (currentFontSize > 70) {
                        currentFontSize -= 10;
                        updateFontSize();
                    }
                });

                $('#btnFontReset').off('click').on('click', function() {
                    currentFontSize = 100;
                    updateFontSize();
                });

                function updateFontSize() {
                    $('.ai-result-content').css('font-size', currentFontSize + '%');
                    $('#aiResultText').css('font-size', currentFontSize + '%');
                }

                // Barra de progreso de scroll
                const scrollContainers = $('#aiResultHtml, #aiResultText');
                scrollContainers.off('scroll').on('scroll', function() {
                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight - $(this).outerHeight();
                    const scrollPercent = (scrollTop / scrollHeight) * 100;
                    $('#aiScrollProgress').css('width', scrollPercent + '%');

                    // Mostrar/ocultar botón "volver arriba"
                    if (scrollTop > 300) {
                        $('#btnScrollTop').addClass('show');
                    } else {
                        $('#btnScrollTop').removeClass('show');
                    }
                });

                // Botón volver arriba
                $('#btnScrollTop').off('click').on('click', function() {
                    const activeTab = $('#aiResultTabs .nav-link.active').attr('data-bs-target');
                    $(activeTab + ' > *').animate({ scrollTop: 0 }, 400);
                });

                // Limpiar al cerrar modal
                $('#aiResultModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    currentFontSize = 100;
                    isFullscreen = false;
                    $('#aiResultModalDialog').removeClass('modal-fullscreen-custom');
                    $('#btnFullscreen').html('<i class="fas fa-expand"></i>');
                    $('.ai-result-content').css('font-size', '100%');
                    $('#aiResultText').css('font-size', '100%');
                    $('#aiScrollProgress').css('width', '0%');
                    $('#btnScrollTop').removeClass('show');
                });
            }

            // Función para mostrar toast de confirmación
            function showToast(message, type = 'success') {
                const bgColor = type === 'success' ? '#198754' : '#dc3545';
                const toast = $(`
                    <div class="copy-toast" style="background: ${bgColor};">
                        <i class="fas fa-${type === 'success' ? 'check' : 'times'}-circle me-2"></i>${message}
                    </div>
                `);
                $('body').append(toast);
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }

            // Configuración de prompts por tipo de análisis - OPTIMIZADOS PARA MODELOS 30B
            const analysisPrompts = {
                'erp-to-created': {
                    title: 'Tiempo Pedido Cliente → Lanzamiento Producción',
                    prompt: `Analiza el tiempo desde que el cliente hace un pedido hasta que se lanza en producción.

TAREA:
1. Lista las 5 órdenes más lentas (ID, Cliente, Tiempo_Horas, Dias_Laborables)
2. Agrupa por cliente: calcula promedio de días laborables por cliente
3. Identifica clientes con tiempo >20% sobre la media
4. Da 3 recomendaciones concretas para reducir estos tiempos

Responde con secciones numeradas y usa tablas para los datos.`
                },
                'created-to-finished': {
                    title: 'Tiempo Lanzamiento Producción → Fin Producción',
                    prompt: `Analiza el tiempo de ciclo de producción (desde inicio hasta fin).

TAREA:
1. Lista las 5 órdenes con ciclo más largo (ID, Cliente, Tiempo_Horas)
2. Calcula media y mediana del tiempo de producción
3. Identifica órdenes con tiempo >150% de la mediana (outliers)
4. Agrupa por cliente y detecta cuáles tienen ciclos más largos
5. Da 3 recomendaciones para reducir el tiempo de ciclo

Responde con secciones numeradas y datos concretos.`
                },
                'finish-to-delivery': {
                    title: 'Fin Producción → Entrega',
                    prompt: `Analiza el tiempo desde que termina la producción hasta la entrega al cliente.

TAREA:
1. Lista las 5 órdenes con mayor retraso (ID, Cliente, Retraso_Horas, Estado)
2. Calcula % de entregas a tiempo vs retrasadas
3. Identifica clientes con más retrasos
4. Suma total de horas de retraso acumuladas
5. Da 3 recomendaciones para mejorar el cumplimiento de entregas

Responde con secciones numeradas. Indica claramente el % de cumplimiento.`
                },
                'process-gaps': {
                    title: 'Tiempos de espera entre Procesos',
                    prompt: `Analiza los tiempos de espera (gaps) entre procesos de fabricación.

TAREA:
1. Lista los 10 procesos con mayor tiempo de espera (ID, Proceso, Espera_Horas, Duracion_Horas)
2. Calcula el ratio tiempo productivo vs tiempo de espera
3. Identifica qué procesos tienen gaps consistentemente altos
4. Da 3 recomendaciones para reducir tiempos de espera

Responde con tablas y datos concretos.`
                },
                'by-client': {
                    title: 'Análisis por Cliente',
                    prompt: `Analiza el rendimiento de producción por cliente.

TAREA:
1. Lista los 5 clientes con más órdenes y su tiempo promedio
2. Identifica el cliente más rápido y el más lento
3. Calcula la diferencia en días entre mejor y peor cliente
4. Da 3 recomendaciones para mejorar tiempos por cliente

Responde con tablas comparativas.`
                },
                'order-type-critical': {
                    title: 'Órdenes críticas por tipo',
                    prompt: `Analiza entregas por tipo de producto.

TAREA:
1. Agrupa por tipo de producto: cuenta órdenes y % con retraso
2. Lista los 3 tipos con más problemas de retraso
3. Muestra las 5 órdenes con mayor retraso (ID, Cliente, Tipo, Retraso)
4. Da recomendaciones específicas por tipo problemático

Responde con tablas por tipo de producto.`
                },
                'gap-alerts': {
                    title: 'Alertas de brechas acumuladas',
                    prompt: `Analiza órdenes con tiempos de espera críticos usando los datos CSV.

TAREA:
1. Clasifica por severidad según Espera_Total_Horas: Crítico si >8 horas | Alto si 4-8 horas | Medio si 2-4 horas
2. Lista las 5 órdenes con mayor Espera_Total_Horas
3. Identifica clientes que aparecen más de una vez
4. Da 3 recomendaciones para reducir esperas

Columnas del CSV: ID (identificador) | Procesos (cantidad) | Espera_Total_Horas (suma de esperas) | Espera_Max_Horas (máxima espera).
Responde con análisis estadístico de las columnas numéricas.`
                },
                'slow-processes': {
                    title: 'Procesos Lentos',
                    prompt: `Analiza los procesos más lentos del periodo.

TAREA:
1. Lista los 10 procesos más lentos (ID, Proceso, Duracion_Horas)
2. Identifica procesos que aparecen repetidamente
3. Compara tiempo de ejecución vs tiempo de espera
4. Da 3 recomendaciones para optimizar procesos lentos

Responde con tablas y tiempos en formato legible.`
                },
                'top-bottom': {
                    title: 'Comparativa Top/Bottom',
                    prompt: `Compara las 10 órdenes más rápidas vs las 10 más lentas.

TAREA:
1. Calcula promedios del grupo TOP (lead time, ciclo producción)
2. Calcula promedios del grupo BOTTOM
3. Calcula la diferencia en días entre ambos grupos
4. Identifica qué clientes están en cada grupo
5. Da 3 acciones para mejorar las órdenes lentas

Usa tabla comparativa TOP vs BOTTOM.`
                },
                'full': {
                    title: 'Análisis Total',
                    prompt: `Analiza el rendimiento completo de producción del periodo.

TAREA:
1. Resumen ejecutivo: total órdenes, lead time promedio, principal problema detectado
2. Métricas clave: media y mediana de lead time y ciclo producción
3. Top 3 clientes por volumen con su tiempo promedio
4. Identifica las 5 órdenes más problemáticas
5. Da 5 recomendaciones priorizadas para mejorar tiempos

Responde como informe ejecutivo con datos concretos.`
                },
                'working-days-efficiency': {
                    title: 'Eficiencia Días Laborables',
                    prompt: `Evalúa el aprovechamiento de días laborables vs calendario.

TAREA:
1. Calcula ratio promedio: días laborables / días calendario
2. Lista 5 órdenes con peor eficiencia (más días no laborables)
3. Identifica si hay patrones (ej: órdenes que cruzan fines de semana)
4. Da 3 recomendaciones para mejor planificación

Responde con ratios y porcentajes.`
                },
                'calendar-impact': {
                    title: 'Impacto Calendario Laboral',
                    prompt: `Cuantifica el impacto de festivos y fines de semana en producción.

TAREA:
1. Suma total de días perdidos por calendario
2. Lista 10 órdenes más afectadas por días no laborables
3. Identifica periodos problemáticos (vacaciones, festivos)
4. Da 3 estrategias para reducir el impacto del calendario

Responde con datos concretos y periodos específicos.`
                },
                'bottleneck-analysis': {
                    title: 'Detección de Cuellos de Botella',
                    prompt: `Identifica los cuellos de botella que limitan la producción.

TAREA:
1. Identifica los 3 procesos con mayor impacto (alta duración + muchas órdenes)
2. Para cada uno: nombre, duración promedio, órdenes afectadas
3. Calcula ratio espera/duración para detectar scheduling pobre
4. Da 3 soluciones específicas por cuello de botella

Responde con procesos específicos y tiempos concretos.`
                },
                'capacity-planning': {
                    title: 'Planificación de Capacidad',
                    prompt: `Analiza la utilización de capacidad por periodo.

TAREA:
1. Identifica periodos sobrecargados (>90%) y con baja utilización (<50%)
2. Detecta tendencias: ¿la carga aumenta o disminuye?
3. Lista los 3 periodos de mayor carga
4. Da 3 estrategias para redistribuir carga

Responde con porcentajes y periodos específicos.`
                },
                'predictive-delays': {
                    title: 'Predicción de Retrasos',
                    prompt: `Identifica órdenes en riesgo de retraso.

TAREA:
1. Clasifica por riesgo: Alto (>70%), Medio (40-70%), Bajo (<40%)
2. Lista órdenes de alto riesgo (ID, Cliente, Retraso, Riesgo)
3. Identifica patrones: clientes o procesos recurrentes
4. Da 3 acciones preventivas para evitar retrasos

Usa formato de semáforo (Alto/Medio/Bajo).`
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
                    case 'working-days-efficiency':
                        data = collectWorkingDaysEfficiencyData();
                        break;
                    case 'calendar-impact':
                        data = collectCalendarImpactData();
                        break;
                    case 'bottleneck-analysis':
                        data = collectBottleneckAnalysisData();
                        break;
                    case 'capacity-planning':
                        data = collectCapacityPlanningData();
                        break;
                    case 'predictive-delays':
                        data = collectPredictiveDelaysData();
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

                // Construir prompt final OPTIMIZADO para modelos 30B
                let finalPrompt = `${config.prompt}\n\n`;

                // Añadir diccionario de campos si existe
                const dictionary = fieldDictionaries[analysisType];
                if (dictionary) {
                    finalPrompt += dictionary + '\n\n';
                }

                // Período y métricas con nombres legibles
                finalPrompt += `PERÍODO: ${data.metrics.dateRange}\n\n`;
                finalPrompt += 'RESUMEN:\n';
                finalPrompt += formatMetricsForAI(data.metrics, analysisType);

                if (data.note) {
                    finalPrompt += `\nNOTA: ${data.note}\n`;
                }

                // CSV con formato claro
                finalPrompt += `\n=== DATOS CSV (${csvRows} filas) ===\n`;
                finalPrompt += data.csv;
                finalPrompt += `=== FIN DATOS ===`;
                
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
                
                // Obtener el agente seleccionado
                const selectedAgent = $('input[name="aiAgentType"]:checked').val() || 'supervisor';

                // Enviar a IA con el agente seleccionado
                startAiTask(editedPrompt, currentPromptData.title, selectedAgent).finally(() => {
                    $btn.prop('disabled', false);
                    $btn.html('{{ __('Enviar a IA') }}');
                });
            });

            $('#btn-ai-reset').on('click', function(){
                if (currentPromptData && currentPromptData.prompt) {
                    $('#aiPrompt').val(currentPromptData.prompt);
                }
            });

            // Animar el ícono del toggle del filtro y actualizar el resumen
            $('#filters-collapse').on('show.bs.collapse', function () {
                $('#filter-toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $('#filter-summary').fadeOut(200);
            });
            $('#filters-collapse').on('hide.bs.collapse', function () {
                $('#filter-toggle-icon').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                updateFilterSummary();
                setTimeout(() => $('#filter-summary').fadeIn(200), 100);
            });

            // Función para actualizar el resumen del filtro
            function updateFilterSummary() {
                const start = $('#date_start').val();
                const end = $('#date_end').val();
                const useActual = $('#use_actual_delivery').is(':checked');
                const excludeIncomplete = $('#exclude_incomplete_orders').is(':checked');

                let summaryText = '';

                if (start && end) {
                    summaryText = `${start} → ${end}`;
                    if (useActual) {
                        summaryText += ' • {{ __('Entrega real') }}';
                    }
                    if (excludeIncomplete) {
                        summaryText += ' • {{ __('Excluir incompletas') }}';
                    }
                } else {
                    summaryText = '{{ __('Click para configurar filtros de análisis') }}';
                }

                $('#filter-summary-text').text(summaryText);
            }

            // Actualizar el resumen cuando cambian los filtros
            $('#date_start, #date_end, #use_actual_delivery, #exclude_incomplete_orders').on('change', function() {
                updateFilterSummary();
            });

            // Inicializar el resumen al cargar
            updateFilterSummary();
        });
    </script>

    <!-- AI Prompt Modal -->
    <div class="modal fade" id="aiPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" style="max-width: 80%; width: 80%;">
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
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('Tipo de Agente IA') }}:</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check border rounded p-3 h-100" style="cursor: pointer;" onclick="$('#agentSupervisor').prop('checked', true);">
                                    <input class="form-check-input" type="radio" name="aiAgentType" id="agentSupervisor" value="supervisor" checked>
                                    <label class="form-check-label w-100" for="agentSupervisor" style="cursor: pointer;">
                                        <span class="fw-bold text-primary"><i class="fas fa-user-tie me-1"></i>Supervisor</span>
                                        <small class="text-muted d-block mt-1">{{ __('Respuestas más descriptivas y elaboradas. Ideal para informes ejecutivos.') }}</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-3 h-100" style="cursor: pointer;" onclick="$('#agentDataAnalysis').prop('checked', true);">
                                    <input class="form-check-input" type="radio" name="aiAgentType" id="agentDataAnalysis" value="data_analysis">
                                    <label class="form-check-label w-100" for="agentDataAnalysis" style="cursor: pointer;">
                                        <span class="fw-bold text-success"><i class="fas fa-chart-line me-1"></i>Data Analysis</span>
                                        <small class="text-muted d-block mt-1">{{ __('Respuestas técnicas y estrictas. Ideal para análisis estadístico.') }}</small>
                                    </label>
                                </div>
                            </div>
                        </div>
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
        <div class="modal-dialog modal-dialog-scrollable" style="max-width: 80%; width: 80%;" id="aiResultModalDialog">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-1">{{ __('Resultado IA') }}</h5>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i><span id="aiResultTimestamp"></span>
                            <span class="mx-2">|</span>
                            <i class="fas fa-align-left me-1"></i><span id="aiResultStats"></span>
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body position-relative">
                    <!-- Barra de herramientas -->
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <div>
                            <p class="text-muted mb-0"><strong>{{ __('Tipo de Análisis') }}:</strong> <span id="aiResultPrompt"></span></p>
                        </div>
                        <div class="btn-toolbar gap-2" role="toolbar">
                            <!-- Control de tamaño de fuente -->
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="btnFontDecrease" title="Reducir tamaño">
                                    <i class="fas fa-minus"></i> A
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnFontReset" title="Tamaño normal">
                                    A
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnFontIncrease" title="Aumentar tamaño">
                                    <i class="fas fa-plus"></i> A
                                </button>
                            </div>

                            <!-- Botones de acción -->
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" id="btnCopyResult" title="Copiar al portapapeles">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success" id="btnDownloadResult" title="Descargar">
                                    <i class="fas fa-download"></i>
                                </button>
                                <button type="button" class="btn btn-outline-info" id="btnPrintResult" title="Imprimir">
                                    <i class="fas fa-print"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnFullscreen" title="Pantalla completa">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="aiResultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ai-tab-rendered" data-bs-toggle="tab" data-bs-target="#aiResultRendered" type="button" role="tab" aria-controls="aiResultRendered" aria-selected="true">
                                <i class="fas fa-table me-1"></i>{{ __('Vista Formateada') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab-raw" data-bs-toggle="tab" data-bs-target="#aiResultRaw" type="button" role="tab" aria-controls="aiResultRaw" aria-selected="false">
                                <i class="fas fa-file-alt me-1"></i>{{ __('Texto Plano') }}
                            </button>
                        </li>
                    </ul>

                    <!-- Barra de progreso de scroll -->
                    <div class="scroll-progress-bar" id="aiScrollProgress"></div>

                    <!-- Contenido -->
                    <div class="tab-content" id="aiResultTabContent">
                        <div class="tab-pane fade show active" id="aiResultRendered" role="tabpanel" aria-labelledby="ai-tab-rendered">
                            <div id="aiResultHtml" class="ai-result-content" style="min-height: 200px; max-height: 70vh; overflow-y: auto;"></div>
                        </div>
                        <div class="tab-pane fade" id="aiResultRaw" role="tabpanel" aria-labelledby="ai-tab-raw">
                            <pre id="aiResultText" class="bg-light p-3 rounded" style="white-space: pre-wrap; min-height: 200px; max-height: 70vh; overflow-y: auto;"></pre>
                        </div>
                    </div>

                    <!-- Botón volver arriba -->
                    <button type="button" class="btn btn-primary btn-sm rounded-circle position-fixed"
                            id="btnScrollTop"
                            style="bottom: 100px; right: 30px; display: none; z-index: 1050; width: 40px; height: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"
                            title="Volver arriba">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>{{ __('Cerrar') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush
