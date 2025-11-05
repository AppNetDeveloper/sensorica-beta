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
            <div class="card border-0 shadow-sm mb-4" style="border-left: 4px solid #0d6efd !important;">
                <div class="card-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filters-collapse" aria-expanded="false" aria-controls="filters-collapse">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0 text-white">
                                <i class="fas fa-sliders-h me-2"></i>{{ __('Filtros de búsqueda') }}
                            </h5>
                            <div id="filter-summary" class="mt-1">
                                <small class="text-white-50">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <span id="filter-summary-text">{{ __('Click para configurar filtros de análisis') }}</span>
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

            <div class="row g-4 mb-4" id="kpi-cards">
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
                    <div class="card shadow-sm border-0 h-100 hover-lift" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#kpi-erp-finish-details" aria-expanded="false">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-stopwatch fa-2x text-info"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio Pedido Cliente → Fin Producción') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-finish">-</h2>
                            <div class="mt-3" id="kpi-erp-finish-days">
                                <span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" id="kpi-erp-finish-total-days" data-bs-toggle="tooltip" title="{{ __('Total días naturales') }}">
                                    <i class="fas fa-calendar fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-success text-white fs-6 py-2 px-3 me-2" id="kpi-erp-finish-working-days" data-bs-toggle="tooltip" title="{{ __('Días laborables') }}">
                                    <i class="fas fa-briefcase fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-secondary text-white fs-6 py-2 px-3" id="kpi-erp-finish-non-working-days" data-bs-toggle="tooltip" title="{{ __('Días no laborables') }}">
                                    <i class="fas fa-calendar-times fa-lg me-2"></i>-
                                </span>
                            </div>
                            <div class="collapse mt-3" id="kpi-erp-finish-details">
                                <hr class="my-2">
                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}:</span>
                                        <strong id="kpi-erp-finish-total-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-success"><i class="fas fa-business-time me-1"></i>{{ __('Horas laborables') }}:</span>
                                        <strong id="kpi-erp-finish-working-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary"><i class="fas fa-bed me-1"></i>{{ __('Horas no laborables') }}:</span>
                                        <strong id="kpi-erp-finish-non-working-hours">-</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>{{ __('Click para ocultar') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para más detalles') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#kpi-erp-finish-median-details" aria-expanded="false">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-clock fa-2x text-secondary"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Mediana Pedido Cliente → Fin Producción') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-finish-median">-</h2>
                            <div class="mt-3">
                                <span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" id="kpi-erp-finish-median-total-days" data-bs-toggle="tooltip" title="{{ __('Total días naturales') }}">
                                    <i class="fas fa-calendar fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-success text-white fs-6 py-2 px-3 me-2" id="kpi-erp-finish-median-working-days" data-bs-toggle="tooltip" title="{{ __('Días laborables') }}">
                                    <i class="fas fa-briefcase fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-secondary text-white fs-6 py-2 px-3" id="kpi-erp-finish-median-non-working-days" data-bs-toggle="tooltip" title="{{ __('Días no laborables') }}">
                                    <i class="fas fa-calendar-times fa-lg me-2"></i>-
                                </span>
                            </div>
                            <div class="collapse mt-3" id="kpi-erp-finish-median-details">
                                <hr class="my-2">
                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}:</span>
                                        <strong id="kpi-erp-finish-median-total-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-success"><i class="fas fa-business-time me-1"></i>{{ __('Horas laborables') }}:</span>
                                        <strong id="kpi-erp-finish-median-working-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary"><i class="fas fa-bed me-1"></i>{{ __('Horas no laborables') }}:</span>
                                        <strong id="kpi-erp-finish-median-non-working-hours">-</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>{{ __('Click para ocultar') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para más detalles') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#kpi-created-finish-details" aria-expanded="false">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-industry fa-2x text-success"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio lanzamiento producción → fin orden') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-created-finish">-</h2>
                            <div class="mt-3">
                                <span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" id="kpi-created-finish-total-days" data-bs-toggle="tooltip" title="{{ __('Total días naturales') }}">
                                    <i class="fas fa-calendar fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-success text-white fs-6 py-2 px-3 me-2" id="kpi-created-finish-working-days" data-bs-toggle="tooltip" title="{{ __('Días laborables') }}">
                                    <i class="fas fa-briefcase fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-secondary text-white fs-6 py-2 px-3" id="kpi-created-finish-non-working-days" data-bs-toggle="tooltip" title="{{ __('Días no laborables') }}">
                                    <i class="fas fa-calendar-times fa-lg me-2"></i>-
                                </span>
                            </div>
                            <div class="collapse mt-3" id="kpi-created-finish-details">
                                <hr class="my-2">
                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}:</span>
                                        <strong id="kpi-created-finish-total-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-success"><i class="fas fa-business-time me-1"></i>{{ __('Horas laborables') }}:</span>
                                        <strong id="kpi-created-finish-working-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary"><i class="fas fa-bed me-1"></i>{{ __('Horas no laborables') }}:</span>
                                        <strong id="kpi-created-finish-non-working-hours">-</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>{{ __('Click para ocultar') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para más detalles') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#kpi-created-finish-median-details" aria-expanded="false">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-stopwatch fa-2x text-info"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Mediana lanzamiento producción → fin orden') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-created-finish-median">-</h2>
                            <div class="mt-3">
                                <span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" id="kpi-created-finish-median-total-days" data-bs-toggle="tooltip" title="{{ __('Total días naturales') }}">
                                    <i class="fas fa-calendar fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-success text-white fs-6 py-2 px-3 me-2" id="kpi-created-finish-median-working-days" data-bs-toggle="tooltip" title="{{ __('Días laborables') }}">
                                    <i class="fas fa-briefcase fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-secondary text-white fs-6 py-2 px-3" id="kpi-created-finish-median-non-working-days" data-bs-toggle="tooltip" title="{{ __('Días no laborables') }}">
                                    <i class="fas fa-calendar-times fa-lg me-2"></i>-
                                </span>
                            </div>
                            <div class="collapse mt-3" id="kpi-created-finish-median-details">
                                <hr class="my-2">
                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}:</span>
                                        <strong id="kpi-created-finish-median-total-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-success"><i class="fas fa-business-time me-1"></i>{{ __('Horas laborables') }}:</span>
                                        <strong id="kpi-created-finish-median-working-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary"><i class="fas fa-bed me-1"></i>{{ __('Horas no laborables') }}:</span>
                                        <strong id="kpi-created-finish-median-non-working-hours">-</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>{{ __('Click para ocultar') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para más detalles') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#kpi-erp-delivery-details" aria-expanded="false">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-truck fa-2x text-warning"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Promedio Pedido Cliente → Pedido Entregado') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-delivery">-</h2>
                            <div class="mt-3">
                                <span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" id="kpi-erp-delivery-total-days" data-bs-toggle="tooltip" title="{{ __('Total días naturales') }}">
                                    <i class="fas fa-calendar fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-success text-white fs-6 py-2 px-3 me-2" id="kpi-erp-delivery-working-days" data-bs-toggle="tooltip" title="{{ __('Días laborables') }}">
                                    <i class="fas fa-briefcase fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-secondary text-white fs-6 py-2 px-3" id="kpi-erp-delivery-non-working-days" data-bs-toggle="tooltip" title="{{ __('Días no laborables') }}">
                                    <i class="fas fa-calendar-times fa-lg me-2"></i>-
                                </span>
                            </div>
                            <div class="collapse mt-3" id="kpi-erp-delivery-details">
                                <hr class="my-2">
                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}:</span>
                                        <strong id="kpi-erp-delivery-total-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-success"><i class="fas fa-business-time me-1"></i>{{ __('Horas laborables') }}:</span>
                                        <strong id="kpi-erp-delivery-working-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary"><i class="fas fa-bed me-1"></i>{{ __('Horas no laborables') }}:</span>
                                        <strong id="kpi-erp-delivery-non-working-hours">-</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>{{ __('Click para ocultar') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para más detalles') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card shadow-sm border-0 h-100 hover-lift" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#kpi-erp-delivery-median-details" aria-expanded="false">
                        <div class="card-body text-center">
                            <div class="mb-2">
                                <i class="fas fa-shipping-fast fa-2x text-danger"></i>
                            </div>
                            <h6 class="text-muted text-uppercase mb-2 small fw-bold">{{ __('Mediana Pedido Cliente → Pedido Entregado') }}</h6>
                            <h2 class="mb-0 text-dark fw-bold" id="kpi-erp-delivery-median">-</h2>
                            <div class="mt-3">
                                <span class="badge bg-primary text-white fs-6 py-2 px-3 me-2" id="kpi-erp-delivery-median-total-days" data-bs-toggle="tooltip" title="{{ __('Total días naturales') }}">
                                    <i class="fas fa-calendar fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-success text-white fs-6 py-2 px-3 me-2" id="kpi-erp-delivery-median-working-days" data-bs-toggle="tooltip" title="{{ __('Días laborables') }}">
                                    <i class="fas fa-briefcase fa-lg me-2"></i>-
                                </span>
                                <span class="badge bg-secondary text-white fs-6 py-2 px-3" id="kpi-erp-delivery-median-non-working-days" data-bs-toggle="tooltip" title="{{ __('Días no laborables') }}">
                                    <i class="fas fa-calendar-times fa-lg me-2"></i>-
                                </span>
                            </div>
                            <div class="collapse mt-3" id="kpi-erp-delivery-median-details">
                                <hr class="my-2">
                                <div class="text-start small">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted"><i class="fas fa-clock me-1"></i>{{ __('Total horas') }}:</span>
                                        <strong id="kpi-erp-delivery-median-total-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-success"><i class="fas fa-business-time me-1"></i>{{ __('Horas laborables') }}:</span>
                                        <strong id="kpi-erp-delivery-median-working-hours">-</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-secondary"><i class="fas fa-bed me-1"></i>{{ __('Horas no laborables') }}:</span>
                                        <strong id="kpi-erp-delivery-median-non-working-hours">-</strong>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>{{ __('Click para ocultar') }}</small>
                                </div>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><i class="fas fa-hand-pointer me-1"></i>{{ __('Click para más detalles') }}</small>
                            </div>
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
                <div class="card-body p-4">
                    <div class="table-responsive">
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

        /* === Mejoras sutiles para KPI cards === */
        #kpi-cards .card {
            border-left: 4px solid transparent;
        }
        #kpi-cards .card:nth-child(1) { border-left-color: #0d6efd; }
        #kpi-cards .card:nth-child(2) { border-left-color: #198754; }
        #kpi-cards .card:nth-child(3) { border-left-color: #0dcaf0; }
        #kpi-cards .card:nth-child(4) { border-left-color: #ffc107; }
        #kpi-cards .card:nth-child(5) { border-left-color: #6c757d; }
        #kpi-cards .card:nth-child(6) { border-left-color: #6f42c1; }
        #kpi-cards .card:nth-child(7) { border-left-color: #198754; }
        #kpi-cards .card:nth-child(8) { border-left-color: #0dcaf0; }

        /* Mejor espaciado */
        #kpi-cards .card-body h2 {
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
        }
        #kpi-cards .card-body h6 {
            line-height: 1.4;
        }

        /* Responsive */
        @media (max-width: 768px) {
            #kpi-cards .card {
                border-left-width: 3px;
            }
            #kpi-cards .card-body h2 {
                font-size: 1.75rem;
            }
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

        .dataTables_wrapper {
            padding: 15px !important;
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
                    // Nuevas métricas de días laborables
                    avgErpToCreatedWorkingDays: Math.round(latestSummary?.orders_avg_erp_to_created_working_days ?? 0) + 'd',
                    avgErpToCreatedNonWorkingDays: Math.round(latestSummary?.orders_avg_erp_to_created_non_working_days ?? 0) + 'd',
                    medianErpToCreatedWorkingDays: Math.round(latestSummary?.orders_p50_erp_to_created_working_days ?? 0) + 'd',
                    medianErpToCreatedNonWorkingDays: Math.round(latestSummary?.orders_p50_erp_to_created_non_working_days ?? 0) + 'd',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                // CSV mejorado con días laborables y no laborables
                let csv = 'Order_ID,Cliente,Fecha_Pedido_Cliente_ISO,Fecha_Lanzamiento_Produccion_ISO,Tiempo_Segundos,Tiempo_Formato,Dias_Laborables,Dias_No_Laborables,Dias_Totales\n';
                let count = 0;
                const maxRows = 150;

                console.log('[AI] Recolectando datos Pedido Cliente→Lanzamiento Producción...');
                const rowsData = table.rows({search: 'applied'}).data();
                console.log('[AI] Total rows disponibles:', rowsData.length);
                
                if (rowsData.length === 0) {
                    console.warn('[AI] No hay datos en la tabla. Asegúrate de haber aplicado los filtros primero.');
                    return {
                        metrics,
                        csv: 'Order_ID,Cliente,Fecha_Pedido_Cliente_ISO,Fecha_Lanzamiento_Produccion_ISO,Tiempo_Segundos,Tiempo_Formato,Dias_Laborables,Dias_No_Laborables,Dias_Totales\n',
                        type: 'Tiempo Pedido Cliente → Lanzamiento Producción',
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
                    // Nuevos campos de días laborables
                    const diasLaborables = cleanValue(safeValue(row.erp_to_created_working_days, '0'));
                    const diasNoLaborables = cleanValue(safeValue(row.erp_to_created_non_working_days, '0'));
                    const diasTotales = cleanValue(safeValue(row.erp_to_created_calendar_days, '0'));
                    csv += `${orderId},${cliente},${fechaErpIso},${fechaCreadoIso},${tiempoErpCreadoSegundos},${tiempoErpCreado},${diasLaborables},${diasNoLaborables},${diasTotales}\n`;
                    count++;
                });

                console.log(`[AI] CSV generado con ${count} filas`);
                console.log('[AI] Primeras 200 caracteres del CSV:', csv.substring(0, 200));
                
                const note = count >= maxRows ? `Mostrando primeras ${maxRows} órdenes` : `Total: ${count} órdenes`;
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

                // CSV: Order_ID, Cliente, Fecha_Lanzamiento_Produccion_ISO, Fecha_Fin_Produccion_ISO, Tiempo_Lanzamiento_a_Fin_Produccion_Segundos, Tiempo_Lanzamiento_a_Fin_Produccion_Formato
                let csv = 'Order_ID,Cliente,Fecha_Lanzamiento_Produccion_ISO,Fecha_Fin_Produccion_ISO,Tiempo_Lanzamiento_a_Fin_Produccion_Segundos,Tiempo_Lanzamiento_a_Fin_Produccion_Formato\n';
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
                return { metrics, csv, type: 'Tiempo Lanzamiento Producción → Fin Producción', note };
            }

            // Análisis adicional: Fin Producción → Entrega
            function collectFinishToDeliveryData() {
                const table = $('#production-times-table').DataTable();
                if (!table) {
                    console.error('[AI] DataTable no inicializada');
                    return { metrics: {}, csv: '', type: 'Fin Producción → Entrega' };
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
                    return { metrics, csv: header, type: 'Fin Producción → Entrega', note: 'Sin datos disponibles' };
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

                // CSV reducido: Order_ID, Cliente, Fecha_Pedido_Cliente_ISO, Tiempo_Pedido_Cliente_a_Fin_Produccion_Segundos, Tiempo_Pedido_Cliente_a_Fin_Produccion_Formato, Tiempo_Lanzamiento_a_Fin_Produccion_Segundos, Tiempo_Lanzamiento_a_Fin_Produccion_Formato
                let csv = 'Order_ID,Cliente,Fecha_Pedido_Cliente_ISO,Tiempo_Pedido_Cliente_a_Fin_Produccion_Segundos,Tiempo_Pedido_Cliente_a_Fin_Produccion_Formato,Tiempo_Lanzamiento_a_Fin_Produccion_Segundos,Tiempo_Lanzamiento_a_Fin_Produccion_Formato\n';
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

                const header = 'Order_ID,Cliente,Procesos_Afectados,Tiempo_Espera_Total_Segundos,Tiempo_Espera_Total_Formato,Tiempo_Espera_Maximo_Segundos,Tiempo_Espera_Maximo_Formato,Tiempo_Espera_Promedio_Segundos,Tiempo_Espera_Promedio_Formato\n';
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

                // CSV: Order_ID, Codigo_Proceso, Nombre_Proceso, Tiempo_Espera_Segundos, Tiempo_Espera_Formato, Duracion_Segundos, Duracion_Formato
                let csv = 'Order_ID,Codigo_Proceso,Nombre_Proceso,Tiempo_Espera_Segundos,Tiempo_Espera_Formato,Duracion_Segundos,Duracion_Formato\n';
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

                // CSV: Cliente, Cantidad_Ordenes, Ordenes_IDs, Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Segundos, Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Formato, Tiempo_Lanzamiento_a_Fin_Produccion_Promedio_Segundos, Tiempo_Lanzamiento_a_Fin_Produccion_Promedio_Formato
                let csv = 'Cliente,Cantidad_Ordenes,Ordenes_IDs,Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Segundos,Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Formato,Tiempo_Lanzamiento_a_Fin_Produccion_Promedio_Segundos,Tiempo_Lanzamiento_a_Fin_Produccion_Promedio_Formato\n';
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

                // CSV: Order_ID, Codigo_Proceso, Nombre_Proceso, Duracion_Segundos, Duracion_Formato, Tiempo_Espera_Segundos, Tiempo_Espera_Formato
                let csv = 'Order_ID,Codigo_Proceso,Nombre_Proceso,Duracion_Segundos,Duracion_Formato,Tiempo_Espera_Segundos,Tiempo_Espera_Formato\n';
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

                let csv = 'Tipo,Order_ID,Cliente,Tiempo_Pedido_Cliente_a_Fin_Produccion_Segundos,Tiempo_Pedido_Cliente_a_Fin_Produccion_Formato,Tiempo_Lanzamiento_a_Fin_Produccion_Segundos,Tiempo_Lanzamiento_a_Fin_Produccion_Formato\n';
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

            // NUEVO: Análisis de Eficiencia con Días Laborables
            function collectWorkingDaysEfficiencyData() {
                const table = $('#production-times-table').DataTable();
                if (!table) return { metrics: {}, csv: '', type: 'Eficiencia Días Laborables' };

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgWorkingDays: Math.round(latestSummary?.orders_avg_created_to_finished_working_days ?? 0) + 'd',
                    avgCalendarDays: Math.round(latestSummary?.orders_avg_created_to_finished_calendar_days ?? 0) + 'd',
                    efficiencyRatio: latestSummary?.orders_avg_created_to_finished_working_days > 0 && latestSummary?.orders_avg_created_to_finished_calendar_days > 0
                        ? `${((latestSummary.orders_avg_created_to_finished_working_days / latestSummary.orders_avg_created_to_finished_calendar_days) * 100).toFixed(1)}%`
                        : '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let csv = 'Order_ID,Cliente,Fecha_Creado_ISO,Fecha_Fin_ISO,Tiempo_Creado_a_Fin_Segundos,Tiempo_Creado_a_Fin_Formato,Dias_Calendario,Dias_Laborables,Eficiencia_Laborable\n';
                let count = 0;
                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= 150) return false;
                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const fechaCreadoIso = cleanValue(normalizeDateTime(row.created_at));
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const tiempoCreadoFinFormato = safeValue(row.created_to_finished_formatted, '00:00:00');
                    const tiempoCreadoFinSegundosRaw = durationToSeconds(tiempoCreadoFinFormato);
                    const tiempoCreadoFinSegundos = cleanValue(tiempoCreadoFinSegundosRaw !== '' ? tiempoCreadoFinSegundosRaw : '0');
                    const diasCalendario = cleanValue(safeValue(row.created_to_finished_calendar_days, '0'));
                    const diasLaborables = cleanValue(safeValue(row.created_to_finished_working_days, '0'));
                    const diasCal = parseInt(diasCalendario) || 0;
                    const diasLab = parseInt(diasLaborables) || 0;
                    const eficiencia = diasCal > 0 ? `${((diasLab / diasCal) * 100).toFixed(1)}%` : '0%';
                    csv += `${orderId},${cliente},${fechaCreadoIso},${fechaFinIso},${tiempoCreadoFinSegundos},${cleanValue(tiempoCreadoFinFormato)},${diasCalendario},${diasLaborables},${eficiencia}\n`;
                    count++;
                });

                return { metrics, csv, type: 'Eficiencia Días Laborables', note: `Analizando ${count} órdenes` };
            }

            // NUEVO: Impacto del Calendario Laboral
            function collectCalendarImpactData() {
                const table = $('#production-times-table').DataTable();
                if (!table) return { metrics: {}, csv: '', type: 'Impacto Calendario Laboral' };

                const metrics = {
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgNonWorkingDaysTotal: Math.round(
                        (latestSummary?.orders_avg_erp_to_created_non_working_days ?? 0) +
                        (latestSummary?.orders_avg_created_to_finished_non_working_days ?? 0)
                    ) + 'd',
                    avgWorkingDaysTotal: Math.round(
                        (latestSummary?.orders_avg_erp_to_created_working_days ?? 0) +
                        (latestSummary?.orders_avg_created_to_finished_working_days ?? 0)
                    ) + 'd',
                    impactRatio: ((latestSummary?.orders_avg_erp_to_created_non_working_days ?? 0) +
                                  (latestSummary?.orders_avg_created_to_finished_non_working_days ?? 0)) > 0
                        ? `${(((latestSummary.orders_avg_erp_to_created_non_working_days + latestSummary.orders_avg_created_to_finished_non_working_days) /
                             ((latestSummary.orders_avg_erp_to_created_working_days + latestSummary.orders_avg_created_to_finished_working_days) +
                              (latestSummary.orders_avg_erp_to_created_non_working_days + latestSummary.orders_avg_created_to_finished_non_working_days))) * 100).toFixed(1)}%`
                        : '-',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                let csv = 'Order_ID,Cliente,Fecha_Creado_ISO,Fecha_Fin_ISO,Dias_No_Laborables_Atravesados,Retraso_Atribuible_Calendario_Segundos,Retraso_Atribuible_Calendario_Formato,Impacto_Porcentaje\n';
                let count = 0;
                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= 150) return false;
                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));
                    const fechaCreadoIso = cleanValue(normalizeDateTime(row.created_at));
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const erpCreNoLab = parseInt(safeValue(row.erp_to_created_non_working_days, '0'));
                    const creFinNoLab = parseInt(safeValue(row.created_to_finished_non_working_days, '0'));
                    const erpCreLab = parseInt(safeValue(row.erp_to_created_working_days, '0'));
                    const creFinLab = parseInt(safeValue(row.created_to_finished_working_days, '0'));
                    const totalNoLab = erpCreNoLab + creFinNoLab;
                    const totalLab = erpCreLab + creFinLab;
                    const totalDias = totalNoLab + totalLab;
                    // Estimamos el retraso como días no laborables * 24 horas en segundos
                    const retrasoSegundos = totalNoLab * 24 * 3600;
                    const retrasoFormato = formatTime(retrasoSegundos);
                    const impact = totalDias > 0 ? `${((totalNoLab / totalDias) * 100).toFixed(1)}%` : '0%';
                    csv += `${orderId},${cliente},${fechaCreadoIso},${fechaFinIso},${totalNoLab},${retrasoSegundos},${retrasoFormato},${impact}\n`;
                    count++;
                });

                return { metrics, csv, type: 'Impacto Calendario Laboral', note: `Analizando ${count} órdenes con calendario laboral` };
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
                const clientsByPeriod = {};

                table.rows({search: 'applied'}).data().each(function(row) {
                    // Usar fecha de lanzamiento para agrupar
                    const fechaCreado = row.created_at;
                    if (!fechaCreado || fechaCreado === '0000-00-00 00:00:00') return;

                    const date = new Date(fechaCreado);
                    if (isNaN(date.getTime())) return;

                    // Obtener semana (formato: YYYY-Wxx)
                    const year = date.getFullYear();
                    const weekNum = getWeekNumber(date);
                    const periodo = `${year}-W${String(weekNum).padStart(2, '0')}`;

                    if (!periodData[periodo]) {
                        periodData[periodo] = {
                            ordenes: 0,
                            tiempoTotalSegundos: 0,
                            clientes: new Set()
                        };
                    }

                    periodData[periodo].ordenes++;

                    // Sumar tiempo de producción
                    const tiempoFormato = safeValue(row.created_to_finished_formatted, '00:00:00');
                    const tiempoSegundosRaw = durationToSeconds(tiempoFormato);
                    const tiempoSegundos = tiempoSegundosRaw !== '' ? parseInt(tiempoSegundosRaw, 10) : 0;
                    if (!isNaN(tiempoSegundos)) {
                        periodData[periodo].tiempoTotalSegundos += tiempoSegundos;
                    }

                    // Contar clientes únicos
                    const cliente = row.customer_client_name || 'Sin cliente';
                    periodData[periodo].clientes.add(cliente);
                });

                // Calcular capacidad estimada (asumimos 40 horas laborables por semana = 144000 segundos)
                const capacidadSemanalSegundos = 40 * 3600;

                // CSV: Periodo, Cantidad_Ordenes, Clientes_Unicos, Tiempo_Produccion_Total_Segundos, Tiempo_Produccion_Total_Formato, Tiempo_Promedio_Por_Orden_Segundos, Tiempo_Promedio_Por_Orden_Formato, Capacidad_Utilizada_Porcentaje, Capacidad_Disponible_Estimada_Porcentaje
                let csv = 'Periodo,Cantidad_Ordenes,Clientes_Unicos,Tiempo_Produccion_Total_Segundos,Tiempo_Produccion_Total_Formato,Tiempo_Promedio_Por_Orden_Segundos,Tiempo_Promedio_Por_Orden_Formato,Capacidad_Utilizada_Porcentaje,Capacidad_Disponible_Estimada_Porcentaje\n';

                // Ordenar periodos
                const periodosOrdenados = Object.keys(periodData).sort();

                periodosOrdenados.forEach(periodo => {
                    const data = periodData[periodo];
                    const tiempoTotal = data.tiempoTotalSegundos;
                    const tiempoTotalFormato = formatTime(tiempoTotal);
                    const tiempoPromedio = data.ordenes > 0 ? Math.round(tiempoTotal / data.ordenes) : 0;
                    const tiempoPromedioFormato = formatTime(tiempoPromedio);

                    // Estimar capacidad utilizada (tiempo total / capacidad semanal * 100)
                    const capacidadUtilizada = capacidadSemanalSegundos > 0
                        ? Math.min(100, ((tiempoTotal / capacidadSemanalSegundos) * 100)).toFixed(1)
                        : '0.0';
                    const capacidadDisponible = (100 - parseFloat(capacidadUtilizada)).toFixed(1);

                    csv += `${periodo},${data.ordenes},${data.clientes.size},${tiempoTotal},${tiempoTotalFormato},${tiempoPromedio},${tiempoPromedioFormato},${capacidadUtilizada}%,${capacidadDisponible}%\n`;
                });

                const metrics = {
                    periodosAnalizados: periodosOrdenados.length,
                    ordersTotal: $('#kpi-orders-total').text() || '0',
                    avgWorkingDaysPerOrder: Math.round(latestSummary?.orders_avg_created_to_finished_working_days ?? 0) + 'd',
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                return { metrics, csv, type: 'Planificación de Capacidad', note: `${periodosOrdenados.length} periodos semanales analizados` };
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

                // CSV: Order_ID, Cliente, Estado_Actual, Fecha_Inicio_Estimada_ISO, Fecha_Fin_Estimada_ISO, Progreso_Porcentaje, Retraso_Acumulado_Segundos, Retraso_Acumulado_Formato, Señales_Alerta, Probabilidad_Retraso_Porcentaje
                let csv = 'Order_ID,Cliente,Estado_Actual,Fecha_Inicio_Estimada_ISO,Fecha_Fin_Estimada_ISO,Progreso_Porcentaje,Retraso_Acumulado_Segundos,Retraso_Acumulado_Formato,Señales_Alerta,Probabilidad_Retraso_Porcentaje\n';

                let count = 0;
                const maxRows = 150;
                let highRiskCount = 0;
                let mediumRiskCount = 0;
                let lowRiskCount = 0;

                table.rows({search: 'applied'}).data().each(function(row) {
                    if (count >= maxRows) return false;

                    const orderId = cleanValue(safeValue(row.order_id, '0'));
                    const cliente = cleanValue(safeValue(row.customer_client_name, 'Sin cliente'));

                    // Determinar estado
                    const fechaFin = row.finished_at;
                    const tieneFinalizacion = fechaFin && fechaFin !== '0000-00-00 00:00:00';
                    let estadoActual = tieneFinalizacion ? 'Finalizado' : 'En proceso';

                    // Para órdenes finalizadas, analizar si tuvieron problemas (contexto predictivo)
                    const fechaCreadoIso = cleanValue(normalizeDateTime(row.created_at));
                    const fechaFinIso = cleanValue(normalizeDateTime(row.finished_at));
                    const fechaEntregaIso = cleanValue(normalizeDateTime(row.delivery_date));

                    // Calcular progreso (si está finalizado = 100%, si no, estimamos basado en tiempo transcurrido)
                    let progreso = 100;
                    if (!tieneFinalizacion && row.created_at) {
                        const fechaCreado = new Date(row.created_at);
                        if (!isNaN(fechaCreado.getTime())) {
                            // Estimar progreso basado en tiempo promedio
                            const tiempoTranscurrido = (now - fechaCreado) / 1000; // segundos
                            const tiempoPromedio = (latestSummary?.orders_avg_created_to_finished || 0);
                            if (tiempoPromedio > 0) {
                                progreso = Math.min(95, Math.round((tiempoTranscurrido / tiempoPromedio) * 100));
                                estadoActual = progreso < 30 ? 'Inicio' : (progreso < 70 ? 'En proceso' : 'Finalizando');
                            } else {
                                progreso = 50; // Valor por defecto
                            }
                        }
                    }

                    // Calcular retraso acumulado
                    let retrasoSegundos = 0;
                    let señales = [];

                    // Analizar gaps elevados
                    const processes = Array.isArray(row.processes) ? row.processes : [];
                    let totalGap = 0;
                    let maxGap = 0;
                    processes.forEach(proc => {
                        const gapSec = typeof proc.gap_seconds === 'number' ? proc.gap_seconds : 0;
                        totalGap += gapSec;
                        if (gapSec > maxGap) maxGap = gapSec;
                    });

                    if (totalGap > 7200) { // > 2 horas
                        señales.push('gaps_elevados');
                        retrasoSegundos += totalGap;
                    }

                    // Analizar procesos lentos
                    const duracionPromedio = processes.length > 0
                        ? processes.reduce((sum, p) => sum + (p.duration_seconds || 0), 0) / processes.length
                        : 0;
                    if (duracionPromedio > 3600) { // > 1 hora promedio
                        señales.push('procesos_lentos');
                    }

                    // Analizar retraso vs entrega planificada
                    const delayVsPlan = typeof row.order_delivery_delay_seconds === 'number' ? row.order_delivery_delay_seconds : 0;
                    if (delayVsPlan > 0) {
                        señales.push('retraso_vs_planificado');
                        retrasoSegundos += delayVsPlan;
                    }

                    // Calcular probabilidad de retraso
                    let probabilidad = 0;

                    // Factor 1: Retraso acumulado
                    if (retrasoSegundos > 86400) probabilidad += 30; // > 1 día
                    else if (retrasoSegundos > 43200) probabilidad += 20; // > 12 horas
                    else if (retrasoSegundos > 7200) probabilidad += 10; // > 2 horas

                    // Factor 2: Gaps elevados
                    if (totalGap > 14400) probabilidad += 25; // > 4 horas de gaps
                    else if (totalGap > 7200) probabilidad += 15; // > 2 horas de gaps

                    // Factor 3: Progreso vs tiempo
                    if (!tieneFinalizacion && progreso < 50) {
                        const tiempoTranscurrido = processes.length > 0 ?
                            processes.reduce((sum, p) => sum + (p.duration_seconds || 0) + (p.gap_seconds || 0), 0) : 0;
                        if (tiempoTranscurrido > latestSummary?.orders_avg_created_to_finished / 2) {
                            probabilidad += 20; // Lleva mucho tiempo y poco progreso
                            señales.push('progreso_lento');
                        }
                    }

                    // Factor 4: Procesos lentos
                    if (duracionPromedio > 7200) probabilidad += 15; // > 2 horas promedio

                    // Ajustar probabilidad final
                    probabilidad = Math.min(100, Math.max(0, probabilidad));

                    // Clasificar por riesgo
                    if (probabilidad > 70) highRiskCount++;
                    else if (probabilidad > 40) mediumRiskCount++;
                    else lowRiskCount++;

                    const retrasoFormato = formatTime(Math.abs(retrasoSegundos));
                    const señalesTexto = señales.length > 0 ? señales.join('; ') : 'ninguna';

                    csv += `${orderId},${cliente},${estadoActual},${fechaCreadoIso},${fechaFinIso || fechaEntregaIso},${progreso}%,${retrasoSegundos},${retrasoFormato},${señalesTexto},${probabilidad}%\n`;
                    count++;
                });

                const metrics = {
                    ordersAnalyzed: count,
                    highRisk: highRiskCount,
                    mediumRisk: mediumRiskCount,
                    lowRisk: lowRiskCount,
                    dateRange: `${$('#date_start').val()} a ${$('#date_end').val()}`
                };

                return {
                    metrics,
                    csv,
                    type: 'Predicción de Retrasos',
                    note: `${count} órdenes analizadas: ${highRiskCount} alto riesgo, ${mediumRiskCount} riesgo medio, ${lowRiskCount} bajo riesgo`
                };
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
                let csv = 'Order_ID,Cliente,Fecha_Pedido_Cliente_ISO,Fecha_Lanzamiento_Produccion_ISO,Fecha_Fin_Produccion_ISO,Tiempo_Pedido_Cliente_a_Lanzamiento_Segundos,Tiempo_Pedido_Cliente_a_Lanzamiento_Formato,Tiempo_Pedido_Cliente_a_Fin_Produccion_Segundos,Tiempo_Pedido_Cliente_a_Fin_Produccion_Formato,Tiempo_Lanzamiento_a_Fin_Produccion_Segundos,Tiempo_Lanzamiento_a_Fin_Produccion_Formato\n';
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
                    title: 'Tiempo Pedido Cliente → Lanzamiento Producción',
                    prompt: `Eres un analista de producción experto. Analiza los tiempos desde que el cliente hace el pedido hasta que se lanza la orden en fabricación.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Order_ID: Identificador único de la orden
- Cliente: Nombre del cliente
- Fecha_Pedido_Cliente_ISO: Fecha del pedido en formato ISO (YYYY-MM-DDTHH:MM:SS)
- Fecha_Lanzamiento_Produccion_ISO: Fecha de lanzamiento en formato ISO
- Tiempo_Segundos: Duración total en segundos (número entero)
- Tiempo_Formato: Duración en formato HH:MM:SS
- Dias_Laborables: Días laborables transcurridos (número entero)
- Dias_No_Laborables: Días no laborables transcurridos (número entero)
- Dias_Totales: Total de días calendario (número entero)

IMPORTANTE: Procesa TODAS las filas del CSV. Ignora filas con valores vacíos o "0000-00-00" en fechas.

ANÁLISIS REQUERIDO:
1. **Top 5 órdenes con mayores retrasos**: Identifica las 5 órdenes con mayor tiempo de espera. Para cada una indica: Order_ID, Cliente, Tiempo_Formato, Dias_Laborables, y días de retraso vs promedio.

2. **Patrones por cliente**: Agrupa por cliente y calcula:
   - Tiempo promedio de espera por cliente (en días laborables)
   - Clientes con >20% más tiempo que la media general
   - Tendencias: ¿hay clientes sistemáticamente lentos?

3. **Análisis de eficiencia**:
   - Calcula media y mediana de Tiempo_Segundos
   - Identifica outliers (órdenes con >150% de la mediana)
   - Calcula % de días laborables vs total

4. **Recomendaciones accionables**: Proporciona 3 acciones específicas priorizadas por impacto estimado para reducir estos tiempos.

FORMATO DE SALIDA:
Estructura tu respuesta en secciones claras con números y porcentajes concretos. Usa tablas cuando sea apropiado.`
                },
                'created-to-finished': {
                    title: 'Tiempo Lanzamiento Producción → Fin Producción',
                    prompt: `Eres un analista de operaciones manufactureras. Analiza el tiempo de ciclo real de producción (desde que la orden entra en planta hasta que sale terminada).

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Order_ID: Identificador único de la orden
- Cliente: Nombre del cliente
- Fecha_Lanzamiento_Produccion_ISO: Fecha de inicio en formato ISO (YYYY-MM-DDTHH:MM:SS)
- Fecha_Fin_Produccion_ISO: Fecha de finalización en formato ISO
- Tiempo_Lanzamiento_a_Fin_Produccion_Segundos: Duración del ciclo en segundos (número entero)
- Tiempo_Lanzamiento_a_Fin_Produccion_Formato: Duración en formato HH:MM:SS

IMPORTANTE: Procesa TODAS las filas del CSV. Las fechas vacías o "0000-00-00" indican órdenes aún en proceso.

ANÁLISIS REQUERIDO:
1. **Top 5 ciclos más largos**: Identifica las 5 órdenes con mayor tiempo de ciclo. Para cada una: Order_ID, Cliente, Tiempo_Formato, y % de desviación vs mediana.

2. **Distribución estadística**:
   - Media y mediana de Tiempo_Lanzamiento_a_Fin_Produccion_Segundos
   - Percentil 90 (P90) y percentil 95 (P95)
   - Coeficiente de variación (desviación estándar / media)
   - Identifica órdenes fuera de 2 desviaciones estándar

3. **Patrones temporales**:
   - Agrupa por semana o mes (según Fecha_Lanzamiento)
   - Detecta tendencias: ¿los ciclos aumentan o disminuyen?
   - Identifica periodos problemáticos con ciclos >120% del promedio

4. **Análisis por cliente**:
   - Clientes con ciclos consistentemente más largos
   - Variabilidad por cliente (std dev)

5. **Recomendaciones**: 3 medidas concretas priorizadas por ROI estimado para reducir el tiempo de ciclo promedio en al menos 15%.

FORMATO DE SALIDA:
Usa secciones numeradas con métricas cuantificadas. Incluye comparaciones porcentuales y valores absolutos.`
                },
                'finish-to-delivery': {
                    title: 'Fin Producción → Entrega',
                    prompt: `Eres un analista de logística y cumplimiento de entregas. Analiza el tiempo desde que la producción termina hasta que el producto llega al cliente.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Order_ID: Identificador único de la orden
- Cliente: Nombre del cliente
- Fecha_Fin_ISO: Fecha de finalización de producción en formato ISO (YYYY-MM-DDTHH:MM:SS)
- Fecha_Entrega_Usada_ISO: Fecha de entrega considerada para el análisis (real o planificada según configuración)
- Fecha_Entrega_Planificada_ISO: Fecha comprometida originalmente
- Fecha_Entrega_Real_ISO: Fecha de entrega efectiva (puede estar vacía si aún no se entregó)
- Tiempo_Fin_a_Entrega_Segundos: Duración post-producción en segundos (número entero)
- Tiempo_Fin_a_Entrega_Formato: Duración en formato HH:MM:SS
- Retraso_vs_Plan_Segundos: Diferencia vs planificado en segundos (positivo=retraso, negativo=adelanto)
- Retraso_vs_Plan_Formato: Diferencia en formato +/-HH:MM:SS

IMPORTANTE: Procesa TODAS las filas del CSV. Valores vacíos en fechas reales indican entregas pendientes.

ANÁLISIS REQUERIDO:
1. **Top 5 retrasos críticos**: Órdenes con mayor Retraso_vs_Plan_Segundos. Para cada una: Order_ID, Cliente, Tiempo_Fin_a_Entrega_Formato, Retraso_vs_Plan_Formato.

2. **Cumplimiento de SLA**:
   - % de órdenes entregadas a tiempo (Retraso_vs_Plan_Segundos ≤ 0)
   - % de órdenes con retrasos <24h, 24-48h, >48h
   - Retraso promedio solo de órdenes retrasadas
   - Impacto: suma total de horas/días de retraso acumulado

3. **Análisis por cliente**:
   - Clientes con tasa de retraso >30%
   - Top 3 clientes más afectados por volumen de retrasos
   - Clientes con entregas consistentemente adelantadas

4. **Patrones temporales**:
   - Días de la semana con más retrasos
   - Tendencia temporal de Tiempo_Fin_a_Entrega_Segundos

5. **Recomendaciones**: 3 acciones priorizadas para alcanzar ≥95% de cumplimiento SLA, con impacto estimado en días de reducción.

FORMATO DE SALIDA:
Inicia indicando si se usa fecha real o planificada. Estructura con métricas claras y porcentajes de cumplimiento.`
                },
                'process-gaps': {
                    title: 'Tiempos de espera entre Procesos',
                    prompt: `Eres un ingeniero de procesos lean manufacturing. Analiza los tiempos de espera (gaps) entre procesos consecutivos para identificar desperdicios y cuellos de botella.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Order_ID: Identificador de la orden
- Codigo_Proceso: Código único del proceso
- Nombre_Proceso: Descripción del proceso
- Tiempo_Espera_Segundos: Tiempo de espera ANTES de este proceso en segundos (número entero)
- Tiempo_Espera_Formato: Tiempo de espera en formato HH:MM:SS
- Duracion_Segundos: Duración de ejecución del proceso en segundos (número entero)
- Duracion_Formato: Duración en formato HH:MM:SS

IMPORTANTE: Procesa TODAS las filas del CSV. Cada fila representa un proceso dentro de una orden.

ANÁLISIS REQUERIDO:
1. **Top 10 gaps más largos**: Procesos con mayor Tiempo_Espera_Segundos. Para cada uno: Order_ID, Codigo_Proceso, Nombre_Proceso, Tiempo_Espera_Formato, Duracion_Formato.

2. **Ratio Value-Added vs Non-Value-Added**:
   - Suma total de Duracion_Segundos (tiempo productivo)
   - Suma total de Tiempo_Espera_Segundos (tiempo desperdiciado)
   - Ratio: Espera / Duración (idealmente <0.5)
   - % del tiempo total que es espera

3. **Análisis por tipo de proceso**:
   - Agrupa por Codigo_Proceso o Nombre_Proceso
   - Identifica procesos con gaps promedio >1 hora
   - Procesos con alta variabilidad en tiempos de espera (std dev)

4. **Impacto por orden**:
   - Agrupa por Order_ID
   - Órdenes con mayor gap acumulado total
   - Correlación entre número de procesos y gap total

5. **Recomendaciones**: 3 acciones específicas priorizadas por reducción potencial de lead time, indicando procesos específicos a optimizar y reducción esperada en horas/días.

FORMATO DE SALIDA:
Usa métricas lean (VA/NVA ratio, lead time reduction). Incluye códigos/nombres de procesos específicos en las recomendaciones.`
                },
                'by-client': {
                    title: 'Análisis por Cliente',
                    prompt: `Eres un analista de cuentas clave y operaciones. Analiza el rendimiento de producción segmentado por cliente para identificar patrones y oportunidades de mejora por cuenta.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Cliente: Nombre del cliente
- Cantidad_Ordenes: Número de órdenes procesadas (número entero)
- Ordenes_IDs: Lista de IDs de órdenes (separados por punto y coma o similar)
- Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Segundos: Lead time completo promedio en segundos
- Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Formato: Lead time en formato HH:MM:SS
- Tiempo_Lanzamiento_a_Fin_Produccion_Promedio_Segundos: Tiempo de ciclo promedio en segundos
- Tiempo_Lanzamiento_a_Fin_Produccion_Promedio_Formato: Tiempo de ciclo en formato HH:MM:SS

IMPORTANTE: Procesa TODAS las filas del CSV. Cada fila representa un cliente único con sus métricas agregadas.

ANÁLISIS REQUERIDO:
1. **Segmentación por volumen**:
   - Top 5 clientes por Cantidad_Ordenes (clientes estratégicos)
   - Clientes con 1-3 órdenes (clientes esporádicos)
   - Concentración: % de órdenes en top 3 clientes

2. **Performance por cliente**:
   - Cliente con mejor lead time (menor Tiempo_Pedido_Cliente_a_Fin_Produccion_Promedio_Segundos)
   - Cliente con peor lead time
   - Diferencia entre mejor y peor (en días)
   - Clientes con lead time >150% de la mediana general

3. **Análisis de eficiencia**:
   - Para cada cliente top 5, calcula:
     * Tiempo administrativo promedio = Lead time - Tiempo de ciclo
     * % de tiempo en producción vs administrativo
   - Identifica clientes con alta fricción administrativa

4. **Priorización estratégica**:
   - Clientes a mejorar urgente: alto volumen + mal performance
   - Clientes a estudiar: bajo volumen + excelente performance
   - Clientes estables: alto volumen + buen performance

5. **Recomendaciones**: 3 estrategias diferenciadas por segmento de cliente (ej: clientes de alto volumen con procesos dedicados, clientes esporádicos con slots estándar), con impacto esperado en días de reducción.

FORMATO DE SALIDA:
Usa tablas para comparar clientes. Incluye nombres de clientes específicos y métricas cuantificadas.`
                },
                'order-type-critical': {
                    title: 'Órdenes críticas por tipo',
                    prompt: `Eres un planner de producción. Analiza el desempeño de entregas segmentado por tipo de producto para identificar qué categorías presentan más problemas de cumplimiento.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Tipo_Producto: Categoría o ruta de producción
- Estado_Entrega: Estado actual (ej: "Entregado", "En proceso", "Retrasado")
- Fecha_Fin_ISO: Fecha de fin de producción en formato ISO
- Fecha_Entrega_Usada_ISO: Fecha de entrega utilizada para análisis
- Fecha_Entrega_Planificada_ISO: Fecha comprometida
- Fecha_Entrega_Real_ISO: Fecha efectiva de entrega
- Tiempo_Fin_a_Entrega_Segundos: Tiempo post-producción en segundos
- Tiempo_Fin_a_Entrega_Formato: Tiempo en formato HH:MM:SS
- Retraso_vs_Plan_Segundos: Diferencia vs plan en segundos (positivo=retraso)
- Retraso_vs_Plan_Formato: Diferencia en formato +/-HH:MM:SS

IMPORTANTE: Procesa TODAS las filas del CSV. Valores positivos en Retraso_vs_Plan_Segundos indican entregas tardías.

ANÁLISIS REQUERIDO:
1. **Ranking de tipos problemáticos**:
   - Agrupa por Tipo_Producto
   - Para cada tipo: cantidad total de órdenes, % con retraso, retraso promedio
   - Top 3 tipos con mayor incidencia de retrasos (por %)
   - Top 3 tipos con mayor retraso promedio (en horas)

2. **Análisis de severidad**:
   - Por cada tipo problemático:
     * Órdenes críticas (retraso >48h): cantidad y %
     * Retraso máximo registrado
     * Retraso acumulado total (suma de todos los retrasos)

3. **Casos críticos específicos**:
   - Top 5 órdenes con mayor retraso: Order_ID, Cliente, Tipo_Producto, Retraso_vs_Plan_Formato
   - Identifica si hay clientes específicos recurrentes

4. **Performance comparativa**:
   - Tipo con mejor cumplimiento (menor % retrasos)
   - Tipo con peor cumplimiento
   - Diferencia en días promedio entre mejor y peor tipo

5. **Recomendaciones por tipo**: Para cada uno de los 3 tipos más problemáticos, proporciona 1-2 acciones específicas (ej: "Tipo X: asignar slot dedicado en proceso Y, impacto esperado -2 días").

FORMATO DE SALIDA:
Usa tablas comparativas por tipo. Incluye nombres específicos de tipos de producto y casos críticos con Order_ID.`
                },
                'gap-alerts': {
                    title: 'Alertas de brechas acumuladas',
                    prompt: `Eres un analista de flow management. Identifica órdenes con tiempos de espera acumulados críticos que están impactando severamente el lead time.

FORMATO DE DATOS:
Recibirás un CSV con órdenes que superan el umbral de 2 horas de espera acumulada. Columnas:
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Procesos_Afectados: Número de procesos con espera significativa (número entero)
- Tiempo_Espera_Total_Segundos: Suma de todos los gaps de la orden en segundos
- Tiempo_Espera_Total_Formato: Suma en formato HH:MM:SS
- Tiempo_Espera_Maximo_Segundos: Gap más largo individual en segundos
- Tiempo_Espera_Maximo_Formato: Gap máximo en formato HH:MM:SS
- Tiempo_Espera_Promedio_Segundos: Gap promedio por proceso en segundos
- Tiempo_Espera_Promedio_Formato: Gap promedio en formato HH:MM:SS

IMPORTANTE: Procesa TODAS las filas del CSV. Todas las órdenes ya superan el umbral mínimo de 2 horas.

ANÁLISIS REQUERIDO:
1. **Clasificación de severidad**:
   - Crítico (>8h espera total): cantidad y lista de Order_ID
   - Alto (4-8h espera total): cantidad
   - Medio (2-4h espera total): cantidad
   - % de órdenes en cada categoría

2. **Top 5 órdenes más afectadas**:
   - Order_ID, Cliente, Tiempo_Espera_Total_Formato, Procesos_Afectados
   - Impacto: cuántos días de lead time representa ese gap

3. **Análisis de recurrencia**:
   - Clientes que aparecen 2+ veces en la lista
   - % de órdenes afectadas por cliente recurrente
   - Suma de gaps por cliente

4. **Patrones de gaps**:
   - Correlación entre Procesos_Afectados y Tiempo_Espera_Total
   - ¿Muchos gaps pequeños o pocos gaps grandes?
   - Ratio entre gap máximo y gap promedio por orden

5. **Recomendaciones**: 3 medidas priorizadas por impacto (ej: "Investigar proceso X que aparece en 70% de gaps críticos", "Implementar buffer management en cliente Y"), con reducción esperada en horas de espera.

FORMATO DE SALIDA:
Usa semáforo de criticidad (Crítico/Alto/Medio). Incluye Order_IDs y clientes específicos.`
                },
                'slow-processes': {
                    title: 'Procesos Lentos',
                    prompt: `Eres un ingeniero de métodos y tiempos. Analiza los procesos más lentos para identificar oportunidades de optimización y reducción de tiempos de ciclo.

FORMATO DE DATOS:
Recibirás un CSV con los 30 procesos individuales más lentos del periodo. Columnas:
- Order_ID: Identificador de la orden
- Codigo_Proceso: Código único del proceso
- Nombre_Proceso: Descripción del proceso
- Duracion_Segundos: Tiempo de ejecución real en segundos (número entero)
- Duracion_Formato: Duración en formato HH:MM:SS
- Tiempo_Espera_Segundos: Gap antes de este proceso en segundos
- Tiempo_Espera_Formato: Gap en formato HH:MM:SS

IMPORTANTE: Procesa TODAS las 30 filas del CSV. Estos son los casos individuales más extremos de duración larga.

ANÁLISIS REQUERIDO:
1. **Top 10 procesos más lentos**:
   - Order_ID, Codigo_Proceso, Nombre_Proceso, Duracion_Formato
   - Para cada uno: cuántas horas/días representa
   - % del lead time total que consume cada proceso

2. **Análisis de recurrencia**:
   - Agrupa por Codigo_Proceso o Nombre_Proceso
   - Procesos que aparecen 2+ veces en el top 30
   - Frecuencia de aparición: ¿es un problema sistemático o casos aislados?
   - Duración promedio por tipo de proceso recurrente

3. **Comparación Duración vs Gap**:
   - Para cada proceso top 10: ratio Gap/Duración
   - Procesos con gap mayor que su propia duración (indicador de scheduling pobre)
   - Suma total de tiempo productivo vs tiempo de espera en top 30

4. **Identificación de patrones**:
   - ¿Hay clientes específicos asociados a procesos lentos?
   - ¿Hay procesos específicos consistentemente lentos?
   - Variabilidad: compara instancias del mismo Codigo_Proceso

5. **Recomendaciones**: 3 acciones priorizadas por reducción potencial (ej: "Proceso X aparece 5 veces con promedio 8h, investigar setup time - impacto potencial -20h/orden", "Optimizar secuencia para reducir gaps en proceso Y").

FORMATO DE SALIDA:
Usa tablas con Order_ID y códigos de proceso específicos. Cuantifica impacto en horas/días.`
                },
                'top-bottom': {
                    title: 'Comparativa Top/Bottom',
                    prompt: `Eres un analista de benchmarking interno. Compara las órdenes más rápidas vs las más lentas para identificar qué hace diferentes a las mejores y cómo replicar esas prácticas.

FORMATO DE DATOS:
Recibirás un CSV con exactamente 20 filas (10 TOP + 10 BOTTOM). Columnas:
- Tipo: "TOP" para las 10 más rápidas, "BOTTOM" para las 10 más lentas
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Tiempo_Pedido_Cliente_a_Fin_Produccion_Segundos: Lead time completo en segundos
- Tiempo_Pedido_Cliente_a_Fin_Produccion_Formato: Lead time en formato HH:MM:SS
- Tiempo_Lanzamiento_a_Fin_Produccion_Segundos: Ciclo de producción en segundos
- Tiempo_Lanzamiento_a_Fin_Produccion_Formato: Ciclo en formato HH:MM:SS

IMPORTANTE: Procesa las 20 filas completas. Analiza por separado el grupo TOP y el grupo BOTTOM.

ANÁLISIS REQUERIDO:
1. **Métricas del grupo TOP (10 mejores)**:
   - Lead time promedio y rango (min-max)
   - Ciclo de producción promedio
   - Tiempo administrativo promedio = (Lead time - Ciclo)
   - Lista de clientes que aparecen en TOP

2. **Métricas del grupo BOTTOM (10 peores)**:
   - Lead time promedio y rango (min-max)
   - Ciclo de producción promedio
   - Tiempo administrativo promedio
   - Lista de clientes que aparecen en BOTTOM

3. **Diferencias cuantificadas**:
   - Diferencia en lead time: TOP vs BOTTOM en días
   - Diferencia en ciclo de producción: TOP vs BOTTOM en días
   - Diferencia en tiempo administrativo: TOP vs BOTTOM en días
   - Ratio: Lead time BOTTOM / Lead time TOP (ej: "2.5x más lento")

4. **Factores diferenciadores (3 clave)**:
   - ¿Hay clientes específicos solo en TOP o solo en BOTTOM?
   - ¿El ciclo de producción es similar pero el tiempo admin diferente?
   - ¿Ambos tiempos (ciclo y admin) son problemáticos en BOTTOM?

5. **Plan de replicación**: 3 acciones concretas para llevar órdenes BOTTOM al nivel TOP:
   - Basadas en las diferencias identificadas
   - Cuantifica el impacto esperado en días
   - Prioriza por facilidad de implementación

FORMATO DE SALIDA:
Usa formato comparativo (Tabla TOP vs BOTTOM). Incluye nombres de clientes y Order_IDs específicos.`
                },
                'full': {
                    title: 'Análisis Total',
                    prompt: `Eres un director de operaciones. Realiza un análisis ejecutivo integral de toda la cadena de producción, identificando oportunidades estratégicas de mejora.

FORMATO DE DATOS:
Recibirás un CSV con hasta 150 órdenes. Columnas:
- Order_ID, Cliente: Identificación básica
- Fecha_Pedido_Cliente_ISO, Fecha_Lanzamiento_Produccion_ISO, Fecha_Fin_Produccion_ISO: Timestamps en formato ISO
- Tiempo_Pedido_Cliente_a_Lanzamiento_Segundos/Formato: Tiempo administrativo pre-producción
- Tiempo_Pedido_Cliente_a_Fin_Produccion_Segundos/Formato: Lead time completo (pedido → fin)
- Tiempo_Lanzamiento_a_Fin_Produccion_Segundos/Formato: Ciclo de producción real

IMPORTANTE: Procesa TODAS las filas del CSV para obtener una visión completa del periodo.

ANÁLISIS REQUERIDO:
1. **Resumen Ejecutivo** (3-4 párrafos):
   - Estado general del periodo: volumen de órdenes, clientes atendidos
   - Lead time promedio actual vs objetivo (si puedes inferir)
   - Principal hallazgo: ¿dónde está el mayor problema?
   - Oportunidad principal de mejora cuantificada

2. **Métricas clave de performance**:
   - Lead time: media, mediana, P90, P95
   - Ciclo de producción: media, mediana, P90, P95
   - Tiempo administrativo: media y % del lead time total
   - Variabilidad: coeficiente de variación
   - Top 3 clientes por volumen y su lead time promedio

3. **Análisis de tendencias temporales**:
   - Agrupa por semana/mes según rango de fechas
   - ¿Los tiempos mejoran, empeoran o se mantienen estables?
   - Detecta periodos problemáticos específicos

4. **Identificación de 5 cuellos de botella críticos**:
   Para cada uno indica:
   - Dónde ocurre (tiempo admin, ciclo producción, entrega)
   - Magnitud del problema (horas/días)
   - % de órdenes afectadas
   - Impacto estimado en el lead time total

5. **Recomendaciones priorizadas** (5 acciones):
   Clasifica en:
   - Quick wins (implementación <1 mes, impacto medio)
   - Iniciativas estratégicas (implementación 1-3 meses, alto impacto)
   Para cada recomendación: impacto esperado en días de reducción

6. **Plan de acción inmediato** (3 acciones para implementar esta semana):
   - Específicas y accionables
   - Con responsable sugerido
   - Con métrica de éxito

FORMATO DE SALIDA:
Estructura tipo informe ejecutivo con secciones claras. Usa datos cuantificados y comparaciones. Prioriza insights accionables.`
                },
                'working-days-efficiency': {
                    title: 'Eficiencia Días Laborables',
                    prompt: `Eres un analista de productividad laboral. Evalúa qué tan eficientemente se aprovechan los días laborables de producción.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Fecha_Creado_ISO: Fecha de lanzamiento en formato ISO
- Fecha_Fin_ISO: Fecha de finalización en formato ISO
- Tiempo_Creado_a_Fin_Segundos: Duración total en segundos
- Tiempo_Creado_a_Fin_Formato: Duración en formato HH:MM:SS
- Dias_Calendario: Días calendario totales transcurridos (número entero)
- Dias_Laborables: Días laborables dentro del periodo (número entero)
- Eficiencia_Laborable: Ratio o % de aprovechamiento (puede ser número o texto con %)

IMPORTANTE: Procesa TODAS las filas del CSV. Dias_Laborables excluye fines de semana y festivos.

ANÁLISIS REQUERIDO:
1. **Métricas de eficiencia global**:
   - Promedio de Dias_Laborables por orden
   - Promedio de Dias_Calendario por orden
   - Ratio promedio: Dias_Laborables / Dias_Calendario
   - % de tiempo perdido en días no laborables

2. **Top 5 órdenes con peor eficiencia**:
   - Order_ID, Cliente, Dias_Laborables, Dias_Calendario, Eficiencia_Laborable
   - Para cada una: cuántos días adicionales vs promedio
   - ¿Qué tienen en común? (cliente, duración, fechas)

3. **Análisis de patrones de inactividad**:
   - Órdenes que atraviesan períodos largos de días no laborables
   - Identifica órdenes con alta proporción de días festivos/fines de semana
   - Detecta si hay inicio/fin de órdenes que caen sistemáticamente en viernes/lunes (indicador de planificación pobre)

4. **Análisis temporal**:
   - Agrupa por mes o semana
   - Identifica períodos con alta proporción de días no laborables (vacaciones, fiestas)
   - Impacto de cada periodo problemático

5. **Recomendaciones**: 3 acciones para maximizar uso de días laborables:
   - Ajustes en scheduling para evitar arranques antes de festivos
   - Optimización de lanzamientos considerando calendario
   - Posible implementación de turnos/días especiales
   - Impacto esperado: reducción en días calendario manteniendo días laborables

FORMATO DE SALIDA:
Usa ratios y porcentajes. Identifica periodos específicos con festivos. Cuantifica oportunidad de mejora en días.`
                },
                'calendar-impact': {
                    title: 'Impacto Calendario Laboral',
                    prompt: `Eres un analista de planificación estratégica. Cuantifica el impacto real del calendario laboral (festivos, fines de semana) en los tiempos de producción y entregas.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Fecha_Creado_ISO: Fecha de lanzamiento en formato ISO
- Fecha_Fin_ISO: Fecha de finalización en formato ISO
- Dias_No_Laborables_Atravesados: Número de días no laborables en el periodo (número entero)
- Retraso_Atribuible_Calendario_Segundos: Tiempo perdido por calendario en segundos
- Retraso_Atribuible_Calendario_Formato: Tiempo en formato HH:MM:SS
- Impacto_Porcentaje: % del lead time atribuible al calendario (puede incluir símbolo %)

IMPORTANTE: Procesa TODAS las filas del CSV. Este análisis aísla el impacto específico del calendario.

ANÁLISIS REQUERIDO:
1. **Impacto global del calendario**:
   - Total de órdenes analizadas
   - Suma total de Retraso_Atribuible_Calendario_Segundos (convertir a días)
   - Promedio de días no laborables por orden
   - Impacto_Porcentaje promedio: qué % del lead time se pierde en calendario
   - Órdenes con 0 impacto vs órdenes muy afectadas (>20% impacto)

2. **Top 10 órdenes más afectadas**:
   - Order_ID, Cliente, Dias_No_Laborables_Atravesados, Retraso_Atribuible_Calendario_Formato, Impacto_Porcentaje
   - Para cada una: cuántos días de retraso puro por calendario
   - ¿Hay clientes recurrentes?

3. **Análisis temporal de periodos problemáticos**:
   - Agrupa por mes o fecha según Fecha_Creado_ISO
   - Identifica meses con más órdenes afectadas
   - Periodos específicos: vacaciones de verano, Navidad, festivos locales
   - Cuantifica impacto de cada periodo (días perdidos totales)

4. **Patrones de afectación**:
   - Relación entre duración de orden y días no laborables atravesados
   - ¿Las órdenes largas sufren proporcionalmente más o menos?
   - Distribución: cuántas órdenes tienen 0-2 días, 3-5 días, >5 días no laborables

5. **Estrategias de mitigación** (3 priorizadas):
   - Basadas en los periodos problemáticos identificados
   - Ajustes de lanzamiento pre-festivos
   - Buffers de tiempo en estimaciones
   - Posibles esquemas de producción en días especiales
   - Cuantifica reducción esperada en días de lead time

FORMATO DE SALIDA:
Usa estadísticas agregadas y casos específicos. Identifica periodos/meses problemáticos con nombres (ej: "Agosto 2024", "Semana Santa").`
                },
                'bottleneck-analysis': {
                    title: 'Detección de Cuellos de Botella',
                    prompt: `Eres un ingeniero industrial especializado en Theory of Constraints. Identifica los cuellos de botella críticos que limitan la capacidad del sistema de producción.

FORMATO DE DATOS:
Recibirás un CSV con datos agregados por tipo de proceso. Columnas:
- Codigo_Proceso: Código único del proceso
- Nombre_Proceso: Descripción del proceso
- Ordenes_Afectadas: Número de órdenes que pasan por este proceso (número entero)
- Clientes_Afectados: Número de clientes distintos (número entero)
- Duracion_Promedio_Segundos: Tiempo promedio de ejecución en segundos
- Duracion_Promedio_Formato: Duración promedio en HH:MM:SS
- Duracion_Maxima_Segundos: Caso más lento registrado en segundos
- Duracion_Maxima_Formato: Duración máxima en HH:MM:SS
- Tiempo_Espera_Promedio_Segundos: Gap promedio antes de este proceso en segundos
- Tiempo_Espera_Promedio_Formato: Gap promedio en HH:MM:SS
- Tasa_Utilizacion_Porcentaje: % de utilización del recurso (puede incluir símbolo %)

IMPORTANTE: Procesa TODAS las filas del CSV. Cada fila representa un tipo de proceso con sus estadísticas agregadas.

ANÁLISIS REQUERIDO:
1. **Identificación de cuellos de botella (Top 3)**:
   Criterios combinados:
   - Alta tasa de utilización (idealmente >80%)
   - Alta duración promedio
   - Alto tiempo de espera subsecuente (indicador de cola)
   - Alto volumen de órdenes afectadas

   Para cada cuello de botella: Codigo_Proceso, Nombre_Proceso, Duracion_Promedio_Formato, Tasa_Utilizacion_Porcentaje, Ordenes_Afectadas

2. **Cuantificación del impacto**:
   Para cada cuello de botella:
   - Duración total acumulada: Duracion_Promedio × Ordenes_Afectadas (en días)
   - % del tiempo total de producción que consume
   - Clientes afectados directamente
   - Variabilidad: ratio Duracion_Maxima / Duracion_Promedio

3. **Análisis de correlación proceso-espera**:
   - Para cada proceso: ratio Tiempo_Espera_Promedio / Duracion_Promedio
   - Procesos con alto ratio (>1.0) indican scheduling pobre
   - Procesos con bajo ratio pero alta duración son cuellos de botella "puros"
   - Identifica si los gaps ocurren ANTES o DESPUÉS de los cuellos de botella

4. **Análisis de capacidad**:
   - Procesos al límite: Tasa_Utilizacion >90% (riesgo alto)
   - Procesos sobrecargados: 70-90% utilización (monitorear)
   - Procesos con capacidad: <70% utilización
   - Capacidad adicional necesaria estimada (en %)

5. **Soluciones específicas por cuello de botella** (3 para cada uno):
   - Soluciones operativas (paralelización, turnos, redistribución)
   - Soluciones de proceso (reducir setup time, mejorar métodos)
   - Inversiones (equipamiento adicional si justificado)
   - Para cada solución: impacto estimado en reducción de duración o aumento de capacidad

FORMATO DE SALIDA:
Prioriza por impacto operacional usando matriz (Impacto vs Esfuerzo). Usa códigos y nombres de procesos específicos. Cuantifica todo en horas/días.`
                },
                'capacity-planning': {
                    title: 'Planificación de Capacidad',
                    prompt: `Eres un planner de capacidad estratégica. Analiza la utilización de capacidad actual y proyecta necesidades futuras basadas en patrones históricos.

FORMATO DE DATOS:
Recibirás un CSV con datos agregados por periodo temporal. Columnas:
- Periodo: Identificador del periodo (semana, mes, etc.)
- Cantidad_Ordenes: Número de órdenes en el periodo (número entero)
- Clientes_Unicos: Clientes distintos atendidos (número entero)
- Tiempo_Produccion_Total_Segundos: Suma de tiempos de producción en segundos
- Tiempo_Produccion_Total_Formato: Suma en formato HH:MM:SS
- Tiempo_Promedio_Por_Orden_Segundos: Promedio por orden en segundos
- Tiempo_Promedio_Por_Orden_Formato: Promedio en HH:MM:SS
- Capacidad_Utilizada_Porcentaje: % de capacidad usada (puede incluir símbolo %)
- Capacidad_Disponible_Estimada_Porcentaje: % de capacidad libre

IMPORTANTE: Procesa TODAS las filas del CSV. Cada fila representa un periodo temporal distinto.

ANÁLISIS REQUERIDO:
1. **Identificación de periodos críticos**:
   - Periodos con sobrecarga (Capacidad_Utilizada >90%): listar con fecha/periodo
   - Periodos con baja utilización (<50%): listar con fecha/periodo
   - Periodos óptimos (70-85% utilización): listar
   - % de periodos en cada categoría

2. **Análisis de tendencias históricas**:
   - Tendencia de Cantidad_Ordenes: ¿aumenta, disminuye o es estable?
   - Tendencia de Capacidad_Utilizada: ¿mejora o empeora?
   - Variabilidad: desviación estándar de Cantidad_Ordenes
   - Estacionalidad: ¿hay patrones mensuales/trimestrales?

3. **Cálculo de capacidad óptima**:
   - Capacidad actual estimada (basada en periodos pico)
   - Utilización promedio actual
   - Utilización objetivo: 75-80% para permitir flexibilidad
   - Gap de capacidad: diferencia entre actual y óptimo

4. **Análisis de picos de demanda**:
   - Top 3 periodos de mayor carga: Periodo, Cantidad_Ordenes, Capacidad_Utilizada
   - Capacidad adicional necesaria para esos picos (en %)
   - Impacto si los picos se repiten: ¿cuánta capacidad extra se necesita?

5. **Estrategias de redistribución de carga** (3 priorizadas):
   - Nivelación de carga: mover órdenes de periodos sobrecargados a periodos con capacidad
   - Cuantifica: cuántas órdenes mover y a qué periodos
   - Anticipación: lanzar órdenes antes en periodos de baja utilización
   - Impacto estimado: mejora en % de utilización y reducción de sobrecarga

6. **Recomendaciones de capacidad adicional**:
   - Basada en tendencias y picos
   - Capacidad adicional necesaria (en % o en órdenes/periodo)
   - ROI estimado: impacto en lead time y cumplimiento
   - Priorización: ¿capacidad permanente o temporal?

FORMATO DE SALIDA:
Usa gráficos conceptuales de utilización por periodo. Cuantifica todo en % de capacidad y número de órdenes. Incluye nombres de periodos específicos.`
                },
                'predictive-delays': {
                    title: 'Predicción de Retrasos',
                    prompt: `Eres un analista de gestión de riesgos operacionales. Identifica órdenes activas en riesgo de retraso y patrones predictivos para actuar preventivamente.

FORMATO DE DATOS:
Recibirás un CSV con órdenes activas y su análisis de riesgo. Columnas:
- Order_ID: Identificador de la orden
- Cliente: Nombre del cliente
- Estado_Actual: Estado de la orden (ej: "En proceso", "Bloqueada", etc.)
- Fecha_Inicio_Estimada_ISO: Fecha de inicio esperada en formato ISO
- Fecha_Fin_Estimada_ISO: Fecha de fin planificada en formato ISO
- Progreso_Porcentaje: % de completitud actual (número o con símbolo %)
- Retraso_Acumulado_Segundos: Retraso actual respecto a lo esperado en segundos
- Retraso_Acumulado_Formato: Retraso en formato HH:MM:SS
- Señales_Alerta: Indicadores de riesgo (puede ser texto descriptivo o códigos)
- Probabilidad_Retraso_Porcentaje: Probabilidad estimada de retraso final (número o con %)

IMPORTANTE: Procesa TODAS las filas del CSV. Estas son órdenes ACTIVAS que aún pueden ser salvadas.

ANÁLISIS REQUERIDO:
1. **Clasificación de riesgo**:
   - Alto riesgo (Probabilidad >70%): cantidad y lista de Order_ID
   - Riesgo medio (40-70%): cantidad
   - Riesgo bajo (<40%): cantidad
   - % de órdenes activas en cada categoría

2. **Órdenes críticas que requieren acción inmediata**:
   Para cada orden con Probabilidad >70%:
   - Order_ID, Cliente, Progreso_Porcentaje, Retraso_Acumulado_Formato, Señales_Alerta
   - Días hasta Fecha_Fin_Estimada
   - Severidad del riesgo (alto progreso + alto retraso = crítico)

3. **Análisis de patrones predictivos**:
   - Señales de alerta más comunes en órdenes de alto riesgo
   - Correlación entre Progreso_Porcentaje y Probabilidad_Retraso
   - ¿Hay clientes específicos con múltiples órdenes en riesgo?
   - Estados que correlacionan con alto riesgo

4. **Análisis de señales tempranas**:
   - Órdenes con bajo progreso pero ya con retraso acumulado
   - Órdenes con Señales_Alerta específicas (ej: "gaps elevados", "proceso lento")
   - Patrones: ¿el retraso ocurre al inicio o se acumula gradualmente?

5. **Impacto proyectado**:
   - Suma de Retraso_Acumulado de órdenes de alto riesgo (en días)
   - Si todas las órdenes de alto riesgo se retrasan: cuántos días totales de retraso
   - Clientes más afectados por volumen de órdenes en riesgo

6. **Plan de acción preventivo** (3 acciones inmediatas priorizadas):
   - Para órdenes específicas: Order_ID, acción correctiva, impacto esperado
   - Acciones sistémicas: resolver señales de alerta recurrentes
   - Timeline: acciones para esta semana vs próximas 2 semanas
   - Métrica de éxito: reducir X órdenes de alto riesgo a medio/bajo riesgo

FORMATO DE SALIDA:
Usa semáforo de riesgo (Alto/Medio/Bajo). Lista Order_IDs específicos con recomendaciones. Cuantifica impacto en días de retraso evitados.`
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
        <div class="modal-dialog modal-dialog-scrollable" style="max-width: 80%; width: 80%;">
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
