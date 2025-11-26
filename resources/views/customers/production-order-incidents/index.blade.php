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
<div class="poi-container">
    {{-- Header Principal --}}
    <div class="poi-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="poi-header-icon me-3">
                        <i class="ti ti-alert-triangle"></i>
                    </div>
                    <div>
                        <h4 class="poi-title mb-1">{{ __('Production Order Incidents') }}</h4>
                        <p class="poi-subtitle mb-0">{{ $customer->name }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    @if(!empty(config('services.ai.url')) && !empty(config('services.ai.token')))
                    <button type="button" class="poi-btn poi-btn-dark" id="btn-ai-open" data-bs-toggle="modal" data-bs-target="#aiPromptModal">
                        <i class="ti ti-sparkles"></i>
                        <span>{{ __('Análisis IA') }}</span>
                    </button>
                    @endif
                    <a href="{{ route('customers.order-organizer', $customer->id) }}" class="poi-btn poi-btn-outline">
                        <i class="ti ti-layout-kanban"></i>
                        <span>{{ __('Order Organizer') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-x me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="poi-stats-card">
                <div class="poi-stats-icon poi-stats-primary">
                    <i class="ti ti-alert-circle"></i>
                </div>
                <div class="poi-stats-info">
                    <h3>{{ $incidents->count() }}</h3>
                    <span>{{ __('Total Incidencias') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="poi-stats-card">
                <div class="poi-stats-icon poi-stats-danger">
                    <i class="ti ti-flame"></i>
                </div>
                <div class="poi-stats-info">
                    <h3>{{ $incidents->filter(fn($i) => optional($i->productionOrder)->status == 3)->count() }}</h3>
                    <span>{{ __('Activas') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="poi-stats-card">
                <div class="poi-stats-icon poi-stats-success">
                    <i class="ti ti-circle-check"></i>
                </div>
                <div class="poi-stats-info">
                    <h3>{{ $incidents->filter(fn($i) => optional($i->productionOrder)->status != 3)->count() }}</h3>
                    <span>{{ __('Resueltas') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="poi-stats-card">
                <div class="poi-stats-icon poi-stats-info">
                    <i class="ti ti-building-factory"></i>
                </div>
                <div class="poi-stats-info">
                    <h3>{{ $lines->count() }}</h3>
                    <span>{{ __('Líneas Afectadas') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card poi-filter-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="poi-filter-label">{{ __('Fecha desde') }}</label>
                    <div class="poi-filter-input">
                        <i class="ti ti-calendar"></i>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <label class="poi-filter-label">{{ __('Fecha hasta') }}</label>
                    <div class="poi-filter-input">
                        <i class="ti ti-calendar"></i>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="poi-filter-label">{{ __('Línea de producción') }}</label>
                    <select name="line_id" class="form-select">
                        <option value="">{{ __('Todas') }}</option>
                        @foreach($lines as $line)
                            <option value="{{ $line->id }}" @selected(($filters['line_id'] ?? '') == $line->id)>{{ $line->name ?? ('#'.$line->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <label class="poi-filter-label">{{ __('Trabajador') }}</label>
                    <select name="operator_id" class="form-select">
                        <option value="">{{ __('Todos') }}</option>
                        @foreach($operators as $u)
                            <option value="{{ $u->id }}" @selected(($filters['operator_id'] ?? '') == $u->id)>{{ $u->name ?? ('#'.$u->id) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <button type="submit" class="poi-btn poi-btn-filter w-100">
                        <i class="ti ti-filter"></i>
                        <span>{{ __('Aplicar') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card poi-table-card">
        <div class="card-header poi-card-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <i class="ti ti-list me-2"></i>
                    <span class="poi-card-title">{{ __('Listado de Incidencias') }}</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="incidents-table" class="table poi-table w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('ORDER ID') }}</th>
                            <th>{{ __('Motivo') }}</th>
                            <th>{{ __('Estado') }}</th>
                            <th>{{ __('Info') }}</th>
                            <th>{{ __('Trabajador') }}</th>
                            <th>{{ __('Fecha') }}</th>
                            <th>{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($incidents as $index => $incident)
                            @php
                                $status = optional($incident->productionOrder)->status;
                                $rowClass = '';
                                if ($status === 3) { $rowClass = 'poi-row-danger'; }
                                elseif ($status === 2) { $rowClass = 'poi-row-success'; }
                                elseif ($status === 1) { $rowClass = 'poi-row-warning'; }
                            @endphp
                            <tr class="{{ $rowClass }}"
                                data-line-id="{{ optional($incident->productionOrder)->production_line_id }}"
                                data-operator-id="{{ optional($incident->createdBy)->id }}"
                                data-created-at="{{ optional($incident->created_at)->format('Y-m-d') }}">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="poi-order-id">#{{ $incident->productionOrder->order_id ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="poi-reason" title="{{ $incident->reason }}">
                                        {{ \Illuminate\Support\Str::limit($incident->reason, 40) }}
                                    </span>
                                </td>
                                <td>
                                    @if(optional($incident->productionOrder)->status == 3)
                                        <span class="poi-badge poi-badge-danger">
                                            <i class="ti ti-flame me-1"></i>{{ __('Activa') }}
                                        </span>
                                    @else
                                        <span class="poi-badge poi-badge-secondary">
                                            <i class="ti ti-check me-1"></i>{{ __('Finalizada') }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="poi-info-badges">
                                        @if(optional($incident->productionOrder)->productionLine)
                                            <span class="poi-mini-badge poi-mini-primary" title="{{ __('Línea') }}">
                                                <i class="ti ti-building-factory"></i>
                                                {{ $incident->productionOrder->productionLine->name ?? ('L#'.optional($incident->productionOrder->productionLine)->id) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="poi-operator">
                                        <i class="ti ti-user"></i>
                                        {{ $incident->createdBy ? $incident->createdBy->name : __('Sistema') }}
                                    </div>
                                </td>
                                <td>
                                    <span class="poi-date">{{ $incident->created_at->format('Y-m-d H:i') }}</span>
                                </td>
                                <td>
                                    <div class="poi-actions">
                                        <a href="{{ route('customers.production-order-incidents.show', [$customer->id, $incident->id]) }}"
                                           class="poi-action-btn poi-action-view" title="{{ __('View') }}">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                        @can('productionline-delete')
                                        <form action="{{ route('customers.production-order-incidents.destroy', [$customer->id, $incident->id]) }}"
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="poi-action-btn poi-action-delete"
                                                    title="{{ __('Delete') }}"
                                                    onclick="return confirm('{{ __('Are you sure you want to delete this incident?') }}')">
                                                <i class="ti ti-trash"></i>
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

    {{-- Leyendas --}}
    <div class="row mt-4 g-3">
        <div class="col-lg-6">
            <div class="poi-legend-card">
                <div class="poi-legend-header">
                    <div class="poi-legend-icon">
                        <i class="ti ti-info-circle"></i>
                    </div>
                    <div>
                        <h6 class="poi-legend-title">{{ __('Estado del Pedido') }}</h6>
                    </div>
                </div>
                <div class="poi-legend-items">
                    <span class="poi-legend-item">
                        <span class="poi-legend-swatch poi-swatch-danger"></span>
                        {{ __('Incidencia (status 3)') }}
                    </span>
                    <span class="poi-legend-item">
                        <span class="poi-legend-swatch poi-swatch-success"></span>
                        {{ __('Finalizado (status 2)') }}
                    </span>
                    <span class="poi-legend-item">
                        <span class="poi-legend-swatch poi-swatch-warning"></span>
                        {{ __('En curso (status 1)') }}
                    </span>
                    <span class="poi-legend-item">
                        <span class="poi-legend-swatch poi-swatch-light"></span>
                        {{ __('Pendiente (status 0)') }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="poi-legend-card">
                <div class="poi-legend-header">
                    <div class="poi-legend-icon poi-legend-icon-alt">
                        <i class="ti ti-alert-triangle"></i>
                    </div>
                    <div>
                        <h6 class="poi-legend-title">{{ __('Estado de Incidencia') }}</h6>
                    </div>
                </div>
                <div class="poi-legend-items">
                    <span class="poi-legend-item">
                        <span class="poi-badge poi-badge-danger">{{ __('Activa') }}</span>
                        {{ __('Incidencia sin resolver') }}
                    </span>
                    <span class="poi-legend-item">
                        <span class="poi-badge poi-badge-secondary">{{ __('Finalizada') }}</span>
                        {{ __('Incidencia resuelta') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
/* Container */
.poi-container {
    padding: 0;
}

/* Header */
.poi-header {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.poi-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.poi-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.poi-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Header Buttons */
.poi-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.poi-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.poi-btn-dark {
    background: rgba(0,0,0,0.3);
    color: white;
}
.poi-btn-dark:hover {
    background: rgba(0,0,0,0.4);
    color: white;
}

.poi-btn-outline {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}
.poi-btn-outline:hover {
    background: rgba(255,255,255,0.25);
    color: white;
}

.poi-btn-filter {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}
.poi-btn-filter:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
}

/* Stats Cards */
.poi-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.poi-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.poi-stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.poi-stats-primary {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.poi-stats-danger {
    background: rgba(220, 38, 38, 0.15);
    color: #dc2626;
}

.poi-stats-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.poi-stats-info {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.poi-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.poi-stats-info span {
    color: #64748b;
    font-size: 0.85rem;
}

/* Filter Card */
.poi-filter-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.poi-filter-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 6px;
    display: block;
}

.poi-filter-input {
    position: relative;
}

.poi-filter-input i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.poi-filter-input input {
    padding-left: 38px;
}

/* Table Card */
.poi-table-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.poi-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
}

.poi-card-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

/* Table Styles */
.poi-table {
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
}

.poi-table thead th {
    background: #f8fafc;
    border: none;
    padding: 14px 12px;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

.poi-table tbody td {
    padding: 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
    color: #334155;
}

.poi-table tbody tr {
    transition: all 0.2s ease;
}

.poi-table tbody tr:hover {
    background: #f8fafc;
}

/* Row status colors */
.poi-row-danger {
    background: rgba(239, 68, 68, 0.08) !important;
    border-left: 3px solid #ef4444;
}

.poi-row-success {
    background: rgba(34, 197, 94, 0.08) !important;
    border-left: 3px solid #22c55e;
}

.poi-row-warning {
    background: rgba(245, 158, 11, 0.08) !important;
    border-left: 3px solid #f59e0b;
}

/* Order ID */
.poi-order-id {
    font-weight: 600;
    color: #1e293b;
}

/* Reason */
.poi-reason {
    color: #475569;
}

/* Badges */
.poi-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
}

.poi-badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

.poi-badge-secondary {
    background: rgba(100, 116, 139, 0.15);
    color: #475569;
}

/* Mini badges for info column */
.poi-info-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.poi-mini-badge {
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.poi-mini-primary {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

/* Operator */
.poi-operator {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #64748b;
    font-size: 0.85rem;
}

/* Date */
.poi-date {
    color: #64748b;
    font-size: 0.8rem;
}

/* Action Buttons */
.poi-actions {
    display: flex;
    gap: 6px;
}

.poi-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.poi-action-view {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}
.poi-action-view:hover {
    background: #0ea5e9;
    color: white;
}

.poi-action-delete {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}
.poi-action-delete:hover {
    background: #ef4444;
    color: white;
}

/* Legend Cards */
.poi-legend-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.poi-legend-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.poi-legend-icon {
    width: 40px;
    height: 40px;
    background: rgba(239, 68, 68, 0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ef4444;
    font-size: 1.2rem;
}

.poi-legend-icon-alt {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.poi-legend-title {
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    font-size: 0.95rem;
}

.poi-legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
}

.poi-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748b;
}

.poi-legend-swatch {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.poi-swatch-danger { background: #ef4444; }
.poi-swatch-success { background: #22c55e; }
.poi-swatch-warning { background: #f59e0b; }
.poi-swatch-light { background: #e2e8f0; border: 1px solid #cbd5e1; }

/* DataTables Overrides */
.poi-table-card .dataTables_wrapper {
    padding: 0;
}

.poi-table-card .dataTables_filter {
    margin-bottom: 16px;
    padding: 0 20px;
}

.poi-table-card .dataTables_filter input {
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    padding: 8px 16px;
    font-size: 0.9rem;
}

.poi-table-card .dataTables_filter input:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.poi-table-card .dataTables_length {
    padding: 0 20px;
    margin-bottom: 16px;
}

.poi-table-card .dataTables_info {
    padding: 16px 20px;
    color: #64748b;
    font-size: 0.85rem;
}

.poi-table-card .dataTables_paginate {
    padding: 16px 20px;
}

.poi-table-card .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    margin: 0 2px;
    border: none !important;
}

.poi-table-card .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    color: white !important;
    border: none !important;
}

.poi-table-card .dataTables_paginate .paginate_button:hover {
    background: #fee2e2 !important;
    color: #ef4444 !important;
    border: none !important;
}

/* Responsive Scroll */
.poi-table-card .table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
}

.poi-table-card .table-responsive::-webkit-scrollbar {
    height: 8px;
}

.poi-table-card .table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.poi-table-card .table-responsive::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 4px;
}

@media (max-width: 991.98px) {
    .poi-table {
        min-width: 900px !important;
    }

    .poi-table thead th,
    .poi-table tbody td {
        padding: 10px 8px !important;
        font-size: 0.75rem !important;
        white-space: nowrap !important;
    }
}

@media (max-width: 767.98px) {
    .poi-header {
        padding: 16px;
    }

    .poi-header-icon {
        width: 48px;
        height: 48px;
        font-size: 1.4rem;
    }

    .poi-title {
        font-size: 1.25rem;
    }

    .poi-btn span {
        display: none;
    }

    .poi-btn {
        padding: 10px 14px;
    }

    .poi-stats-card {
        padding: 14px;
    }

    .poi-stats-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .poi-stats-info h3 {
        font-size: 1.25rem;
    }
}

/* Dark Mode */
[data-theme="dark"] .poi-stats-card,
[data-theme="dark"] .poi-filter-card,
[data-theme="dark"] .poi-table-card,
[data-theme="dark"] .poi-legend-card {
    background: #1e293b;
}

[data-theme="dark"] .poi-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .poi-card-header {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .poi-card-title {
    color: #f1f5f9;
}

[data-theme="dark"] .poi-table thead th {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}

[data-theme="dark"] .poi-table tbody td {
    border-color: #334155;
    color: #e2e8f0;
}

[data-theme="dark"] .poi-table tbody tr:hover {
    background: #334155;
}

[data-theme="dark"] .poi-order-id {
    color: #f1f5f9;
}

[data-theme="dark"] .poi-legend-title {
    color: #f1f5f9;
}

[data-theme="dark"] .poi-filter-label {
    color: #94a3b8;
}

[data-theme="dark"] .poi-table-card .dataTables_filter input {
    background: #334155;
    border-color: #475569;
    color: #f1f5f9;
}

[data-theme="dark"] .poi-table-card .table-responsive::-webkit-scrollbar-track {
    background: #1e293b;
}

/* ===== AI Modal Styles ===== */
.ai-result-content {
    font-size: 1rem;
    line-height: 1.6;
    color: #333;
    max-height: 65vh;
    overflow-y: auto;
    padding: 1rem;
    background: white;
    border-radius: 8px;
}

.ai-result-content table {
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border-radius: 6px;
    overflow: hidden;
}

.ai-result-content table thead th {
    background-color: #ef4444;
    color: white;
    font-weight: 600;
    padding: 0.75rem;
    border: none;
}

.ai-result-content table tbody td {
    padding: 0.65rem 0.75rem;
    border-top: 1px solid #dee2e6;
}

.ai-result-content h1, .ai-result-content h2, .ai-result-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: #212529;
}

.ai-result-content h1 { font-size: 1.8rem; border-bottom: 2px solid #ef4444; padding-bottom: 0.3rem; }
.ai-result-content h2 { font-size: 1.5rem; color: #ef4444; }
.ai-result-content h3 { font-size: 1.3rem; color: #495057; }

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

.scroll-progress-bar {
    position: absolute;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #ef4444 0%, #f59e0b 100%);
    width: 0%;
    transition: width 0.1s ease;
    z-index: 1050;
}

#btnScrollTop {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 45px;
    height: 45px;
    border-radius: 50% !important;
    background: #ef4444;
    color: white;
    border: none;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
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
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#incidents-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() == "es" ? "es-ES" : "en-GB" }}.json' },
        order: [[6, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'csv', text: '<i class="ti ti-file-text me-1"></i>CSV', className: 'btn btn-sm btn-outline-secondary' },
            { extend: 'excel', text: '<i class="ti ti-file-spreadsheet me-1"></i>Excel', className: 'btn btn-sm btn-outline-success' },
            { extend: 'print', text: '<i class="ti ti-printer me-1"></i>{{ __("Print") }}', className: 'btn btn-sm btn-outline-primary' },
        ]
    });

    // === AI Integration ===
    const AI_URL = @json(config('services.ai.url'));
    const AI_TOKEN = @json(config('services.ai.token'));

    if (window.marked) {
        marked.setOptions({ breaks: true, gfm: true, headerIds: true, mangle: false, sanitize: false });
    }

    function collectCurrentRows() {
        const nodes = table.rows({ search: 'applied' }).nodes();
        let csv = 'Index,Order_ID,Reason,Status,Info,Operator,Created_At,Line_ID,Operator_ID\n';
        let count = 0;
        const maxRows = 200;

        table.rows({ search: 'applied' }).every(function(rowIdx){
            if (count >= maxRows) return false;
            const tr = $(nodes[rowIdx]);
            const cells = $(this.node()).find('td');

            const cleanCsvValue = (val) => {
                if (!val) return '';
                val = val.toString().trim().replace(/\s+/g, ' ').replace(/"/g, '""').replace(/[\r\n]+/g, ' ');
                return val.includes(',') || val.includes('"') ? `"${val}"` : val;
            };

            csv += `${cleanCsvValue($(cells[0]).text())},${cleanCsvValue($(cells[1]).text())},${cleanCsvValue($(cells[2]).text())},${cleanCsvValue($(cells[3]).text())},${cleanCsvValue($(cells[4]).text())},${cleanCsvValue($(cells[5]).text())},${cleanCsvValue($(cells[6]).text())},${cleanCsvValue(tr.data('line-id') || '')},${cleanCsvValue(tr.data('operator-id') || '')}\n`;
            count++;
        });

        return { csv, rowCount: count };
    }

    const defaultPrompt = `Eres un experto en gestión de incidencias de producción. Analiza las incidencias proporcionadas y genera un informe ejecutivo con:

1. **Resumen Ejecutivo**: Total de incidencias, distribución por estado
2. **Causas Recurrentes (Top 5)**: Motivos más frecuentes
3. **Líneas de Producción Más Afectadas**: Top 5 líneas
4. **Análisis de Trabajadores**: Distribución por operador
5. **Análisis Temporal**: Patrones por fecha
6. **Acciones Recomendadas**: 3-5 acciones concretas

Utiliza formato Markdown con tablas cuando sea apropiado.`;

    $('#aiPromptModal').on('shown.bs.modal', function(){
        const $ta = $('#aiPrompt');
        if (!$ta.val()) {
            const payload = collectCurrentRows();
            $ta.val(`${defaultPrompt}\n\n=== Datos CSV ===\n${payload.csv}\n\nTotal: ${payload.rowCount} incidencias`);
        }
        $ta.trigger('focus');
    });

    $('#btn-ai-reset').on('click', function(){
        const payload = collectCurrentRows();
        $('#aiPrompt').val(`${defaultPrompt}\n\n=== Datos CSV ===\n${payload.csv}\n\nTotal: ${payload.rowCount} incidencias`);
    });

    $('#btn-ai-send').on('click', async function(){
        if (!AI_URL || !AI_TOKEN) { alert('AI config missing'); return; }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>{{ __("Processing...") }}');

        try {
            const prompt = $('#aiPrompt').val().trim();
            const fd = new FormData();
            fd.append('prompt', prompt);
            fd.append('agent', 'data_analysis');

            const startResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                method: 'POST', headers: { 'Authorization': `Bearer ${AI_TOKEN}` }, body: fd
            });
            if (!startResp.ok) throw new Error('start failed');
            const startData = await startResp.json();
            const taskId = (startData && startData.task && (startData.task.id || startData.task.uuid)) || startData.id || startData.task_id || startData.uuid;
            if (!taskId) throw new Error('no id');

            let done = false, last;
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

            const content = (last && last.task && last.task.response != null) ? last.task.response : last;
            let rawText;
            try {
                rawText = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
            } catch {
                rawText = String(content);
            }
            $('#aiResultText').text(rawText);

            if (window.marked && window.DOMPurify) {
                let html = marked.parse(rawText);
                html = html.replace(/<table>/g, '<table class="table table-striped table-bordered">');
                $('#aiResultHtml').html(DOMPurify.sanitize(html));
            } else {
                $('#aiResultHtml').text(rawText);
            }

            $('#aiResultTimestamp').text(new Date().toLocaleString('es-ES'));
            $('#aiPromptModal').modal('hide');
            new bootstrap.Modal(document.getElementById('aiResultModal')).show();
        } catch (err) {
            console.error(err);
            alert('{{ __("An error occurred") }}: ' + err.message);
        } finally {
            btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i>{{ __("Send") }}');
        }
    });

    // AI Result Modal actions
    $('#btnCopyResult').on('click', function() {
        navigator.clipboard.writeText($('#aiResultText').text()).then(() => showToast('{{ __("Copied!") }}'));
    });

    $('#btnDownloadResult').on('click', function() {
        const blob = new Blob([$('#aiResultText').text()], { type: 'text/markdown' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `analisis-incidencias-${new Date().toISOString().split('T')[0]}.md`;
        a.click();
    });

    $('#btnPrintResult').on('click', function() {
        const w = window.open('', '_blank');
        w.document.write(`<html><head><title>Análisis IA</title></head><body>${$('#aiResultHtml').html()}</body></html>`);
        w.document.close();
        w.print();
    });

    function showToast(msg) {
        const toast = $(`<div class="copy-toast"><i class="ti ti-check me-2"></i>${msg}</div>`);
        $('body').append(toast);
        setTimeout(() => toast.remove(), 3000);
    }
});
</script>

<!-- AI Prompt Modal -->
<div class="modal fade" id="aiPromptModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-sparkles me-2"></i>{{ __('Análisis IA') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('¿Qué necesitas analizar?') }}</label>
                <textarea class="form-control" id="aiPrompt" rows="8"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="btn-ai-reset">{{ __('Reset') }}</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-danger" id="btn-ai-send"><i class="ti ti-send me-1"></i>{{ __('Send') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- AI Result Modal -->
<div class="modal fade" id="aiResultModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ __('Resultado IA') }}</h5>
                    <small class="text-muted"><i class="ti ti-clock me-1"></i><span id="aiResultTimestamp"></span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body position-relative">
                <div class="scroll-progress-bar" id="aiScrollProgress"></div>
                <div class="ai-toolbar">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" id="btnCopyResult"><i class="ti ti-copy me-1"></i>{{ __('Copy') }}</button>
                        <button class="btn btn-outline-success" id="btnDownloadResult"><i class="ti ti-download me-1"></i>{{ __('Download') }}</button>
                        <button class="btn btn-outline-info" id="btnPrintResult"><i class="ti ti-printer me-1"></i>{{ __('Print') }}</button>
                    </div>
                </div>
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#aiResultRendered">{{ __('Formatted') }}</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#aiResultRaw">{{ __('Plain Text') }}</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="aiResultRendered">
                        <div id="aiResultHtml" class="ai-result-content"></div>
                    </div>
                    <div class="tab-pane fade" id="aiResultRaw">
                        <pre id="aiResultText" class="bg-light p-3 rounded" style="white-space: pre-wrap;"></pre>
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
