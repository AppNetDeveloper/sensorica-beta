@extends('layouts.admin')
@section('title', __('Production Centers'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Production Centers') }}</li>
    </ul>
@endsection

@section('content')
<div class="production-centers-container">
    {{-- Header con título, buscador y botón añadir --}}
    <div class="pc-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h4 class="pc-title mb-0">
                    <i class="ti ti-building-factory me-2"></i>{{ __('Production Centers') }}
                    <span class="badge bg-primary ms-2">{{ $customers->count() }}</span>
                </h4>
            </div>
            <div class="col-md-5">
                <div class="pc-search-box">
                    <i class="ti ti-search"></i>
                    <input type="text" id="searchCustomers" class="form-control" placeholder="{{ __('Search production centers...') }}">
                </div>
            </div>
            <div class="col-md-3 text-end">
                @can('productionline-create')
                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i> {{ __('Add Center') }}
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Grid de Production Centers --}}
    <div class="row" id="customersGrid">
        @forelse($customers as $customer)
        <div class="col-xl-6 col-lg-6 col-md-12 mb-4 customer-card-wrapper" data-name="{{ strtolower($customer->name) }}">
            <div class="card pc-card h-100">
                {{-- Header del card --}}
                <div class="pc-card-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pc-info">
                            <h5 class="pc-name mb-1">
                                <i class="ti ti-building-factory-2 me-2"></i>{{ $customer->name }}
                            </h5>
                            <small class="text-white-50">
                                <i class="ti ti-calendar me-1"></i>{{ __('Created') }}: {{ $customer->created_at->format('d/m/Y') }}
                            </small>
                        </div>
                        <div class="pc-actions-top">
                            @can('productionline-edit')
                            <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-sm btn-light" title="{{ __('Edit') }}">
                                <i class="ti ti-edit"></i>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- Mini KPIs --}}
                <div class="pc-kpis">
                    <div class="row g-2">
                        <div class="col-4">
                            <div class="pc-kpi-item">
                                <div class="pc-kpi-icon bg-success-light">
                                    <i class="ti ti-player-play text-success"></i>
                                </div>
                                <div class="pc-kpi-data">
                                    <span class="pc-kpi-value">{{ $customer->active_lines_count }}</span>
                                    <span class="pc-kpi-label">{{ __('Active') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="pc-kpi-item">
                                <div class="pc-kpi-icon bg-primary-light">
                                    <i class="ti ti-chart-line text-primary"></i>
                                </div>
                                <div class="pc-kpi-data">
                                    <span class="pc-kpi-value">{{ $customer->production_lines_count }}</span>
                                    <span class="pc-kpi-label">{{ __('Lines') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="pc-kpi-item">
                                <div class="pc-kpi-icon bg-warning-light">
                                    <i class="ti ti-clipboard-list text-warning"></i>
                                </div>
                                <div class="pc-kpi-data">
                                    <span class="pc-kpi-value">{{ $customer->pending_orders_count }}</span>
                                    <span class="pc-kpi-label">{{ __('Pending') }}</span>
                                </div>
                            </div>
                        </div>
                        @if($customer->pending_maintenance_count > 0)
                        <div class="col-4">
                            <div class="pc-kpi-item pc-kpi-alert">
                                <div class="pc-kpi-icon bg-danger-light">
                                    <i class="ti ti-tool text-danger"></i>
                                </div>
                                <div class="pc-kpi-data">
                                    <span class="pc-kpi-value text-danger">{{ $customer->pending_maintenance_count }}</span>
                                    <span class="pc-kpi-label">{{ __('Maint.') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($customer->assets_count > 0)
                        <div class="col-4">
                            <div class="pc-kpi-item">
                                <div class="pc-kpi-icon bg-info-light">
                                    <i class="ti ti-box text-info"></i>
                                </div>
                                <div class="pc-kpi-data">
                                    <span class="pc-kpi-value">{{ $customer->assets_count }}</span>
                                    <span class="pc-kpi-label">{{ __('Assets') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if($customer->completed_orders_count > 0)
                        <div class="col-4">
                            <div class="pc-kpi-item">
                                <div class="pc-kpi-icon bg-secondary-light">
                                    <i class="ti ti-circle-check text-secondary"></i>
                                </div>
                                <div class="pc-kpi-data">
                                    <span class="pc-kpi-value">{{ $customer->completed_orders_count }}</span>
                                    <span class="pc-kpi-label">{{ __('Done') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Acciones organizadas por categorías --}}
                <div class="pc-card-body">
                    {{-- FÁBRICA --}}
                    @if(auth()->user()->can('productionline-kanban') || auth()->user()->can('productionline-show') || auth()->user()->can('productionline-orders') || auth()->user()->can('workcalendar-list') || auth()->user()->can('original-order-list'))
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-building-factory"></i> {{ __('Factory') }}
                        </div>
                        <div class="pc-action-buttons">
                            @can('productionline-kanban')
                            <a href="{{ route('customers.order-organizer', $customer->id) }}" class="pc-btn pc-btn-primary" title="{{ __('Kanban') }}">
                                <i class="ti ti-layout-kanban"></i>
                                <span>Kanban</span>
                            </a>
                            @endcan
                            @can('productionline-show')
                            <a href="{{ route('productionlines.index', ['customer_id' => $customer->id]) }}" class="pc-btn pc-btn-secondary" title="{{ __('Production Lines') }}">
                                <i class="ti ti-chart-line"></i>
                                <span>{{ __('Lines') }}</span>
                            </a>
                            @endcan
                            @can('productionline-orders')
                            <a href="{{ route('customers.original-orders.index', $customer->id) }}" class="pc-btn pc-btn-dark" title="{{ __('Orders') }}">
                                <i class="ti ti-clipboard-list"></i>
                                <span>{{ __('Orders') }}</span>
                            </a>
                            @endcan
                            @can('original-order-list')
                            <a href="{{ route('customers.original-orders.finished-processes.view', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Finished Processes') }}">
                                <i class="ti ti-circle-check"></i>
                                <span>{{ __('Finished Processes') }}</span>
                            </a>
                            @endcan
                            @can('workcalendar-list')
                            <a href="{{ route('customers.work-calendars.index', $customer->id) }}" class="pc-btn pc-btn-info" title="{{ __('Work Calendar') }}">
                                <i class="ti ti-calendar"></i>
                                <span>{{ __('Calendar') }}</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                    @endif

                    {{-- ALMACÉN --}}
                    @if(auth()->user()->can('assets-view') || auth()->user()->can('asset-categories-view') || auth()->user()->can('asset-cost-centers-view') || auth()->user()->can('asset-locations-view') || auth()->user()->can('vendor-suppliers-view') || auth()->user()->can('vendor-items-view') || auth()->user()->can('vendor-orders-view'))
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-package"></i> {{ __('Warehouse') }}
                        </div>
                        <div class="pc-action-buttons">
                            @can('assets-view')
                            <a href="{{ route('customers.assets.index', $customer->id) }}" class="pc-btn pc-btn-primary" title="{{ __('Inventory') }}">
                                <i class="ti ti-box"></i>
                                <span>{{ __('Inventory') }}</span>
                            </a>
                            <a href="{{ route('customers.assets.inventory', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Available Assets') }}">
                                <i class="ti ti-chart-bar"></i>
                                <span>{{ __('Available Assets') }}</span>
                            </a>
                            @endcan
                            @can('asset-categories-view')
                            <a href="{{ route('customers.asset-categories.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Categories') }}">
                                <i class="ti ti-category"></i>
                                <span>{{ __('Categories') }}</span>
                            </a>
                            @endcan
                            @can('asset-cost-centers-view')
                            <a href="{{ route('customers.asset-cost-centers.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Cost Centers') }}">
                                <i class="ti ti-coin"></i>
                                <span>{{ __('Cost Centers') }}</span>
                            </a>
                            @endcan
                            @can('asset-locations-view')
                            <a href="{{ route('customers.asset-locations.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Locations') }}">
                                <i class="ti ti-map-pin"></i>
                                <span>{{ __('Locations') }}</span>
                            </a>
                            @endcan
                            @can('vendor-suppliers-view')
                            <a href="{{ route('customers.vendor-suppliers.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Suppliers') }}">
                                <i class="ti ti-building"></i>
                                <span>{{ __('Suppliers') }}</span>
                            </a>
                            @endcan
                            @can('vendor-items-view')
                            <a href="{{ route('customers.vendor-items.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Products') }}">
                                <i class="ti ti-package"></i>
                                <span>{{ __('Products') }}</span>
                            </a>
                            @endcan
                            @can('vendor-orders-view')
                            <a href="{{ route('customers.vendor-orders.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Supplier Orders') }}">
                                <i class="ti ti-file-invoice"></i>
                                <span>{{ __('Supplier Orders') }}</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                    @endif

                    {{-- MANTENIMIENTO --}}
                    @can('maintenance-show')
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-tool"></i> {{ __('Maintenance') }}
                        </div>
                        <div class="pc-action-buttons">
                            <a href="{{ route('customers.maintenances.index', $customer->id) }}" class="pc-btn {{ $customer->pending_maintenance_count > 0 ? 'pc-btn-danger' : 'pc-btn-primary' }}" title="{{ __('Maintenance') }}">
                                <i class="ti ti-tool"></i>
                                <span>{{ __('Maintenance') }}</span>
                                @if($customer->pending_maintenance_count > 0)
                                <span class="pc-badge">{{ $customer->pending_maintenance_count }}</span>
                                @endif
                            </a>
                        </div>
                    </div>
                    @endcan

                    {{-- LOGÍSTICA --}}
                    @if(auth()->user()->can('routes-view') || auth()->user()->can('fleet-view') || auth()->user()->can('customer-clients-view') || auth()->user()->can('route-names-view'))
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-truck"></i> {{ __('Logistics') }}
                        </div>
                        <div class="pc-action-buttons">
                            @can('routes-view')
                            <a href="{{ route('customers.routes.index', $customer->id) }}" class="pc-btn pc-btn-primary" title="{{ __('Routes') }}">
                                <i class="ti ti-route"></i>
                                <span>{{ __('Routes') }}</span>
                            </a>
                            @endcan
                            @can('fleet-view')
                            <a href="{{ route('customers.fleet-vehicles.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Fleet') }}">
                                <i class="ti ti-truck"></i>
                                <span>{{ __('Fleet') }}</span>
                            </a>
                            @endcan
                            @can('customer-clients-view')
                            <a href="{{ route('customers.clients.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Clients') }}">
                                <i class="ti ti-users"></i>
                                <span>{{ __('Clients') }}</span>
                            </a>
                            @endcan
                            @can('route-names-view')
                            <a href="{{ route('customers.route-names.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Route Dictionary') }}">
                                <i class="ti ti-list"></i>
                                <span>{{ __('Route Dictionary') }}</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                    @endif

                    {{-- ESTADÍSTICAS --}}
                    @if(auth()->user()->can('original-order-list') || auth()->user()->can('hourly-totals-view') || auth()->user()->can('productionline-weight-stats') || auth()->user()->can('productionline-production-stats'))
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-chart-pie"></i> {{ __('Statistics') }}
                        </div>
                        <div class="pc-action-buttons">
                            @can('original-order-list')
                            <a href="{{ route('customers.production-times.view', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Lead Time') }}">
                                <i class="ti ti-clock"></i>
                                <span>{{ __('Lead Time') }}</span>
                            </a>
                            @endcan
                            @can('hourly-totals-view')
                            <a href="{{ route('customers.hourly-totals', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Hourly Load') }}">
                                <i class="ti ti-chart-area"></i>
                                <span>{{ __('Hourly Load') }}</span>
                            </a>
                            @endcan
                            @can('productionline-weight-stats')
                            <a href="{{ secure_url('/modbuses/liststats/weight?token=' . $customer->token) }}" target="_blank" class="pc-btn pc-btn-success" title="{{ __('Weight Stats') }}">
                                <i class="ti ti-scale"></i>
                                <span>{{ __('Weight Stats') }}</span>
                            </a>
                            @endcan
                            @can('productionline-production-stats')
                            <a href="{{ secure_url('/productionlines/liststats?token=' . $customer->token) }}" target="_blank" class="pc-btn pc-btn-warning" title="{{ __('Production Stats') }}">
                                <i class="ti ti-chart-line"></i>
                                <span>{{ __('Production Stats') }}</span>
                            </a>
                            <a href="{{ route('customers.sensors.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Sensors') }}">
                                <i class="ti ti-cpu"></i>
                                <span>{{ __('Sensors') }}</span>
                            </a>
                            @endcan
                            @can('productionline-kanban')
                            <a href="{{ route('customers.optimal-sensor-times.index', $customer->id) }}" class="pc-btn pc-btn-outline-info" title="{{ __('Optimal Times') }}">
                                <i class="ti ti-clock"></i>
                                <span>{{ __('Optimal Times') }}</span>
                            </a>
                            @endcan
                        </div>
                    </div>
                    @endif

                    {{-- INCIDENCIAS Y CALIDAD --}}
                    @can('productionline-incidents')
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-alert-triangle"></i> {{ __('Quality & Incidents') }}
                        </div>
                        <div class="pc-action-buttons">
                            <a href="{{ route('customers.production-order-incidents.index', $customer->id) }}" class="pc-btn pc-btn-danger" title="{{ __('Incidents') }}">
                                <i class="ti ti-alert-triangle"></i>
                                <span>{{ __('Incidents') }}</span>
                            </a>
                            <a href="{{ route('customers.quality-incidents.index', $customer->id) }}" class="pc-btn pc-btn-outline-danger" title="{{ __('Quality Incidents') }}">
                                <i class="ti ti-flask"></i>
                                <span>{{ __('Quality Incidents') }}</span>
                            </a>
                            <a href="{{ route('customers.qc-confirmations.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Quality Control') }}">
                                <i class="ti ti-clipboard-check"></i>
                                <span>{{ __('Quality Control') }}</span>
                            </a>
                        </div>
                    </div>
                    @endcan

                    {{-- INTEGRACIONES --}}
                    @can('callbacks.view')
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-plug"></i> {{ __('Integrations') }}
                        </div>
                        <div class="pc-action-buttons">
                            <a href="{{ route('customers.callbacks.index', $customer->id) }}" class="pc-btn pc-btn-outline" title="{{ __('Callbacks') }}">
                                <i class="ti ti-webhook"></i>
                                <span>{{ __('Callbacks') }}</span>
                            </a>
                        </div>
                    </div>
                    @endcan

                    {{-- AJUSTES --}}
                    @can('productionline-edit')
                    <div class="pc-action-group">
                        <div class="pc-action-label">
                            <i class="ti ti-settings"></i> {{ __('Settings') }}
                        </div>
                        <div class="pc-action-buttons">
                            <a href="{{ route('customers.edit', $customer->id) }}" class="pc-btn pc-btn-info" title="{{ __('Edit Center') }}">
                                <i class="ti ti-edit"></i>
                                <span>{{ __('Edit') }}</span>
                            </a>
                        </div>
                    </div>
                    @endcan

                    {{-- CRÍTICO --}}
                    @can('productionline-delete')
                    <div class="pc-action-group pc-action-group-danger">
                        <div class="pc-action-label text-danger">
                            <i class="ti ti-alert-octagon"></i> {{ __('Critical') }}
                        </div>
                        <div class="pc-action-buttons">
                            <button type="button" class="pc-btn pc-btn-outline-danger btn-delete-customer"
                                    data-id="{{ $customer->id }}"
                                    data-name="{{ $customer->name }}"
                                    title="{{ __('Delete Center') }}">
                                <i class="ti ti-trash"></i>
                                <span>{{ __('Delete') }}</span>
                            </button>
                        </div>
                    </div>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card pc-empty-state">
                <div class="card-body text-center py-5">
                    <i class="ti ti-building-factory display-1 text-muted mb-3"></i>
                    <h4>{{ __('No production centers found') }}</h4>
                    <p class="text-muted">{{ __('Start by adding your first production center') }}</p>
                    @can('productionline-create')
                    <a href="{{ route('customers.create') }}" class="btn btn-primary mt-3">
                        <i class="ti ti-plus me-1"></i> {{ __('Add Production Center') }}
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.production-centers-container {
    padding: 0;
}

/* Header */
.pc-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
    margin-bottom: 24px;
}

.pc-title {
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
}

.pc-title .badge {
    font-size: 0.8rem;
    padding: 6px 12px;
}

/* Search Box */
.pc-search-box {
    position: relative;
}

.pc-search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #667eea;
    font-size: 1.1rem;
}

.pc-search-box input {
    padding-left: 48px;
    border-radius: 50px;
    border: none;
    height: 46px;
    font-size: 0.95rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.pc-search-box input:focus {
    box-shadow: 0 4px 20px rgba(102,126,234,0.3);
}

/* Card Principal */
.pc-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.pc-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

/* Card Header */
.pc-card-header {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    padding: 20px;
    color: white;
}

.pc-name {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: white;
}

.pc-actions-top .btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
}

.pc-actions-top .btn:hover {
    background: rgba(255,255,255,0.3);
    color: white;
}

/* Mini KPIs */
.pc-kpis {
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.pc-kpi-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.pc-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.pc-kpi-data {
    display: flex;
    flex-direction: column;
}

.pc-kpi-value {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.2;
    color: #1e293b;
}

.pc-kpi-label {
    font-size: 0.7rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.pc-kpi-alert {
    animation: pulse-alert 2s infinite;
}

@keyframes pulse-alert {
    0%, 100% { box-shadow: 0 2px 8px rgba(220,53,69,0.1); }
    50% { box-shadow: 0 2px 15px rgba(220,53,69,0.3); }
}

/* Background colors for KPI icons */
.bg-success-light { background: rgba(34,197,94,0.15); }
.bg-primary-light { background: rgba(59,130,246,0.15); }
.bg-warning-light { background: rgba(245,158,11,0.15); }
.bg-danger-light { background: rgba(239,68,68,0.15); }
.bg-info-light { background: rgba(14,165,233,0.15); }
.bg-secondary-light { background: rgba(100,116,139,0.15); }

/* Card Body - Acciones */
.pc-card-body {
    padding: 16px 20px;
}

.pc-action-group {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.pc-action-group:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.pc-action-group-config {
    background: #f8fafc;
    margin: 0 -20px -16px -20px;
    padding: 16px 20px;
    border-radius: 0 0 16px 16px;
}

.pc-action-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.pc-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

/* Botones de acción */
.pc-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    border: 1px solid transparent;
    position: relative;
}

.pc-btn i {
    font-size: 1rem;
}

.pc-btn span {
    white-space: nowrap;
}

.pc-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Variantes de botones */
.pc-btn-primary {
    background: #3b82f6;
    color: white;
}
.pc-btn-primary:hover {
    background: #2563eb;
    color: white;
}

.pc-btn-secondary {
    background: #64748b;
    color: white;
}
.pc-btn-secondary:hover {
    background: #475569;
    color: white;
}

.pc-btn-dark {
    background: #1e293b;
    color: white;
}
.pc-btn-dark:hover {
    background: #0f172a;
    color: white;
}

.pc-btn-info {
    background: #0ea5e9;
    color: white;
}
.pc-btn-info:hover {
    background: #0284c7;
    color: white;
}

.pc-btn-warning {
    background: #f59e0b;
    color: white;
}
.pc-btn-warning:hover {
    background: #d97706;
    color: white;
}

.pc-btn-danger {
    background: #ef4444;
    color: white;
}
.pc-btn-danger:hover {
    background: #dc2626;
    color: white;
}

.pc-btn-outline {
    background: white;
    color: #475569;
    border-color: #e2e8f0;
}
.pc-btn-outline:hover {
    background: #f8fafc;
    color: #1e293b;
    border-color: #cbd5e1;
}

.pc-btn-outline-dark {
    background: transparent;
    color: #64748b;
    border-color: #cbd5e1;
}
.pc-btn-outline-dark:hover {
    background: white;
    color: #1e293b;
}

.pc-btn-outline-danger {
    background: transparent;
    color: #ef4444;
    border-color: #fecaca;
}
.pc-btn-outline-danger:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #ef4444;
}

.pc-btn-success {
    background: #22c55e;
    color: white;
}
.pc-btn-success:hover {
    background: #16a34a;
    color: white;
}

.pc-btn-outline-info {
    background: transparent;
    color: #06b6d4;
    border-color: #a5f3fc;
}
.pc-btn-outline-info:hover {
    background: #ecfeff;
    color: #0891b2;
    border-color: #06b6d4;
}

/* Badge en botón */
.pc-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: white;
    color: #ef4444;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

/* Empty State */
.pc-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
}

/* Responsive */
@media (max-width: 991.98px) {
    .pc-header .row {
        gap: 16px;
    }
    .pc-header .col-md-4,
    .pc-header .col-md-5,
    .pc-header .col-md-3 {
        text-align: center !important;
    }
    .pc-title {
        justify-content: center;
    }
}

@media (max-width: 575.98px) {
    .pc-kpi-item {
        flex-direction: column;
        text-align: center;
        padding: 10px 6px;
    }
    .pc-kpi-icon {
        margin-bottom: 4px;
    }
    .pc-btn span {
        display: none;
    }
    .pc-btn {
        padding: 10px 12px;
    }
}

/* Dark mode support */
[data-theme="dark"] .pc-card {
    background: #1e293b;
}

[data-theme="dark"] .pc-kpis {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .pc-kpi-item {
    background: #1e293b;
}

[data-theme="dark"] .pc-kpi-value {
    color: #f1f5f9;
}

[data-theme="dark"] .pc-action-group {
    border-color: #334155;
}

[data-theme="dark"] .pc-action-group-config {
    background: #0f172a;
}

/* Animación de entrada */
.customer-card-wrapper {
    animation: fadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.customer-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.customer-card-wrapper:nth-child(2) { animation-delay: 0.15s; }
.customer-card-wrapper:nth-child(3) { animation-delay: 0.2s; }
.customer-card-wrapper:nth-child(4) { animation-delay: 0.25s; }
.customer-card-wrapper:nth-child(5) { animation-delay: 0.3s; }
.customer-card-wrapper:nth-child(6) { animation-delay: 0.35s; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Buscador de centros de producción
    $('#searchCustomers').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        $('.customer-card-wrapper').each(function() {
            var customerName = $(this).data('name');
            if (customerName.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Eliminar centro de producción - DOBLE CONFIRMACIÓN
    $(document).on('click', '.btn-delete-customer', function() {
        var customerId = $(this).data('id');
        var customerName = $(this).data('name');
        var $card = $(this).closest('.customer-card-wrapper');

        // PRIMERA CONFIRMACIÓN
        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            html: '{!! __("You are about to delete the production center") !!} <strong>' + customerName + '</strong>.<br><br>' +
                  '<span class="text-danger"><i class="ti ti-alert-triangle me-1"></i>{!! __("This will delete ALL associated data:") !!}</span><br>' +
                  '<small class="text-muted">• {{ __("Production lines") }}<br>• {{ __("Orders") }}<br>• {{ __("Maintenance records") }}<br>• {{ __("Assets") }}<br>• {{ __("And all related data") }}</small>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            confirmButtonText: '{{ __("Continue") }}',
            cancelButtonText: '{{ __("Cancel") }}',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // SEGUNDA CONFIRMACIÓN - Escribir nombre para confirmar
                Swal.fire({
                    title: '{{ __("Final confirmation") }}',
                    html: '<p class="mb-3">{!! __("To confirm deletion, type the name of the production center:") !!}</p>' +
                          '<p class="fw-bold text-danger mb-3">' + customerName + '</p>',
                    icon: 'error',
                    input: 'text',
                    inputPlaceholder: '{{ __("Type the name here...") }}',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocomplete: 'off'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '{{ __("Delete permanently") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    reverseButtons: true,
                    preConfirm: (inputValue) => {
                        if (inputValue !== customerName) {
                            Swal.showValidationMessage('{{ __("The name does not match. Please type it exactly.") }}');
                            return false;
                        }
                        return true;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Mostrar loading
                        Swal.fire({
                            title: '{{ __("Deleting...") }}',
                            html: '{{ __("Please wait while we delete the production center.") }}',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: '{{ url("customers") }}/' + customerId,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                // Animar y eliminar el card
                                $card.fadeOut(400, function() {
                                    $(this).remove();
                                    // Actualizar contador
                                    var count = $('.customer-card-wrapper:visible').length;
                                    $('.pc-title .badge').text(count);
                                });

                                Swal.fire({
                                    title: '{{ __("Deleted!") }}',
                                    text: '{{ __("The production center has been deleted.") }}',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            },
                            error: function(xhr) {
                                var message = xhr.responseJSON?.message || '{{ __("Something went wrong!") }}';
                                Swal.fire({
                                    title: '{{ __("Error!") }}',
                                    text: message,
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            }
        });
    });
});
</script>
@endpush
