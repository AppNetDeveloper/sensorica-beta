@extends('layouts.admin')

@section('title', __('Original Orders'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Original Orders') }}</li>
    </ul>
@endsection

@section('content')
<div class="oo-container">
    {{-- Header Principal --}}
    <div class="oo-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-4 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="oo-header-icon me-3">
                        <i class="ti ti-package"></i>
                    </div>
                    <div>
                        <h4 class="oo-title mb-1">{{ __('Original Orders') }}</h4>
                        <p class="oo-subtitle mb-0">{{ $customer->name }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    @can('original-order-delete')
                    <button id="bulk-delete" class="oo-btn oo-btn-danger d-none">
                        <i class="ti ti-trash"></i>
                        <span>{{ __('Delete Selected') }}</span>
                    </button>
                    @endcan
                    @can('original-order-list')
                    <a href="{{ route('customers.original-orders.finished-processes.view', $customer) }}" class="oo-btn oo-btn-outline">
                        <i class="ti ti-chart-line"></i>
                        <span>@lang('Procesos finalizados')</span>
                    </a>
                    @endcan
                    @can('original-order-create')
                    <a href="#" id="import-orders-btn" class="oo-btn oo-btn-success">
                        <i class="ti ti-refresh"></i>
                        <span>@lang('Importar ahora')</span>
                    </a>
                    <a href="#" id="create-cards-btn" class="oo-btn oo-btn-info">
                        <i class="ti ti-id"></i>
                        <span>@lang('Crear Tarjetas')</span>
                    </a>
                    <a href="{{ route('customers.original-orders.create', $customer->id) }}" class="oo-btn oo-btn-light">
                        <i class="ti ti-plus"></i>
                        <span>@lang('New Order')</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
        {{-- Buscador --}}
        <div class="row mt-4">
            <div class="col-lg-6 col-md-8">
                <div class="oo-search-box">
                    <i class="ti ti-search"></i>
                    <input type="text" id="searchInput" class="form-control" placeholder="@lang('Search orders...')">
                </div>
            </div>
        </div>
    </div>

    {{-- Toast de actualización --}}
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="update-time-toast" class="toast align-items-center text-white bg-info border-0" role="alert" aria-live="polite" aria-atomic="true" style="border-radius: 12px;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="ti ti-refresh me-2 oo-spin"></i> @lang('Actualizando...')
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
            <div class="oo-stats-card" data-filter="all">
                <div class="oo-stats-icon oo-stats-primary">
                    <i class="ti ti-package"></i>
                </div>
                <div class="oo-stats-info">
                    <h3 id="stats-total">-</h3>
                    <span>@lang('Total')</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="oo-stats-card" data-filter="finished">
                <div class="oo-stats-icon oo-stats-success">
                    <i class="ti ti-circle-check"></i>
                </div>
                <div class="oo-stats-info">
                    <h3 id="stats-finished">-</h3>
                    <span>@lang('Finalizados')</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="oo-stats-card" data-filter="started">
                <div class="oo-stats-icon oo-stats-info">
                    <i class="ti ti-player-play"></i>
                </div>
                <div class="oo-stats-info">
                    <h3 id="stats-started">-</h3>
                    <span>@lang('En proceso')</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="oo-stats-card" data-filter="planned">
                <div class="oo-stats-icon oo-stats-warning">
                    <i class="ti ti-clock"></i>
                </div>
                <div class="oo-stats-info">
                    <h3 id="stats-pending">-</h3>
                    <span>@lang('Pendientes')</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="oo-filters mb-4">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="oo-filter-label"><i class="ti ti-filter me-1"></i>@lang('Filter'):</span>
            <button class="oo-filter-btn active" data-filter="all">@lang('Todo')</button>
            <button class="oo-filter-btn" data-filter="finished">@lang('Finalizados')</button>
            <button class="oo-filter-btn" data-filter="started">@lang('Iniciados')</button>
            <button class="oo-filter-btn" data-filter="assigned">@lang('Asignados')</button>
            <button class="oo-filter-btn" data-filter="planned">@lang('Pendiente Planificar')</button>
            <button class="oo-filter-btn" data-filter="to-create">@lang('Pendiente Crear')</button>
        </div>
    </div>

    {{-- Loading --}}
    <div id="loading-container" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-3 text-muted">@lang('Loading orders...')</p>
    </div>

    {{-- Grid de Orders --}}
    <div class="row" id="orders-grid" style="display: none;">
        {{-- Las cards se cargan dinámicamente --}}
    </div>

    {{-- Empty State --}}
    <div id="empty-state" class="text-center py-5" style="display: none;">
        <div class="oo-empty-icon mb-3">
            <i class="ti ti-package-off"></i>
        </div>
        <h5 class="text-muted">@lang('No orders found')</h5>
        <p class="text-muted">@lang('Try adjusting your filters or search term')</p>
    </div>

    {{-- Paginación --}}
    <div id="pagination-container" class="d-flex justify-content-between align-items-center mt-4" style="display: none !important;">
        <div class="oo-pagination-info">
            <span id="pagination-info"></span>
        </div>
        <nav>
            <ul class="pagination oo-pagination mb-0" id="pagination">
            </ul>
        </nav>
    </div>

    {{-- Leyenda --}}
    <div class="oo-legend mt-4">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="oo-legend-card">
                    <div class="oo-legend-header">
                        <div class="oo-legend-icon">
                            <i class="ti ti-info-circle"></i>
                        </div>
                        <div>
                            <h6 class="oo-legend-title">@lang('Estados de procesos')</h6>
                            <small class="text-muted">@lang('Formato: Código (grupo)')</small>
                        </div>
                    </div>
                    <div class="oo-legend-items">
                        <span class="oo-legend-item"><span class="badge bg-success">PRO</span> @lang('Finalizado')</span>
                        <span class="oo-legend-item"><span class="badge bg-primary">PRO</span> @lang('En fabricación')</span>
                        <span class="oo-legend-item"><span class="badge bg-info">PRO</span> @lang('Asignado')</span>
                        <span class="oo-legend-item"><span class="badge bg-danger">PRO</span> @lang('Incidencia')</span>
                        <span class="oo-legend-item"><span class="badge bg-secondary">PRO</span> @lang('Sin asignar')</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="oo-legend-card">
                    <div class="oo-legend-header">
                        <div class="oo-legend-icon oo-legend-icon-orders">
                            <i class="ti ti-list-check"></i>
                        </div>
                        <div>
                            <h6 class="oo-legend-title">@lang('Estados de pedidos')</h6>
                        </div>
                    </div>
                    <div class="oo-legend-items">
                        <span class="oo-legend-item"><span class="badge bg-success">@lang('Fecha')</span> @lang('Finalizado')</span>
                        <span class="oo-legend-item"><span class="badge bg-primary">@lang('Iniciado')</span> @lang('En proceso')</span>
                        <span class="oo-legend-item"><span class="badge bg-info">@lang('Asignado')</span> @lang('A máquina')</span>
                        <span class="oo-legend-item"><span class="badge bg-secondary">@lang('Pendiente')</span> @lang('Planificar')</span>
                        <span class="oo-legend-item"><span class="badge bg-danger">@lang('Crear')</span> @lang('Sin stock')</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.oo-container {
    padding: 0;
}

/* Header */
.oo-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.oo-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.oo-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.oo-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Header Buttons */
.oo-btn {
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

.oo-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.oo-btn-light {
    background: white;
    color: #667eea;
}
.oo-btn-light:hover {
    background: #f8fafc;
    color: #5a67d8;
}

.oo-btn-success {
    background: #22c55e;
    color: white;
}
.oo-btn-success:hover {
    background: #16a34a;
    color: white;
}

.oo-btn-info {
    background: #0ea5e9;
    color: white;
}
.oo-btn-info:hover {
    background: #0284c7;
    color: white;
}

.oo-btn-danger {
    background: #ef4444;
    color: white;
}
.oo-btn-danger:hover {
    background: #dc2626;
    color: white;
}

.oo-btn-outline {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}
.oo-btn-outline:hover {
    background: rgba(255,255,255,0.25);
    color: white;
}

/* Search Box */
.oo-search-box {
    position: relative;
}

.oo-search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.6);
    font-size: 1.1rem;
}

.oo-search-box input {
    padding-left: 46px;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    color: white;
    height: 46px;
    font-size: 0.95rem;
}

.oo-search-box input::placeholder {
    color: rgba(255,255,255,0.6);
}

.oo-search-box input:focus {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    box-shadow: none;
    color: white;
}

/* Stats Cards */
.oo-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    cursor: pointer;
}

.oo-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.oo-stats-card.active {
    border: 2px solid #667eea;
}

.oo-stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.oo-stats-primary {
    background: rgba(102, 126, 234, 0.15);
    color: #667eea;
}

.oo-stats-success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.oo-stats-info {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.oo-stats-warning {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.oo-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.oo-stats-info span {
    color: #64748b;
    font-size: 0.85rem;
}

/* Filters */
.oo-filters {
    display: flex;
    align-items: center;
}

.oo-filter-label {
    color: #64748b;
    font-weight: 500;
    font-size: 0.9rem;
}

.oo-filter-btn {
    padding: 8px 16px;
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    background: white;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.oo-filter-btn:hover {
    border-color: #667eea;
    color: #667eea;
}

.oo-filter-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: white;
}

/* Order Cards */
.oo-order-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    animation: ooFadeInUp 0.4s ease forwards;
    opacity: 0;
}

.oo-order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

@keyframes ooFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.oo-order-header {
    padding: 16px 20px;
    color: white;
    position: relative;
}

.oo-order-header.finished {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.oo-order-header.started {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.oo-order-header.assigned {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
}

.oo-order-header.planned {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.oo-order-header.to-create {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.oo-order-id {
    font-weight: 700;
    font-size: 1rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.oo-order-client {
    font-size: 0.8rem;
    opacity: 0.9;
    margin-top: 4px;
}

.oo-order-checkbox {
    position: absolute;
    top: 16px;
    right: 16px;
}

.oo-order-body {
    padding: 20px;
}

.oo-order-processes {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.oo-order-processes .badge {
    font-size: 0.7rem;
    padding: 4px 8px;
    font-weight: 500;
}

.oo-order-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.oo-order-date {
    color: #64748b;
    font-size: 0.8rem;
}

.oo-order-status {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 600;
}

.oo-order-footer {
    padding: 12px 20px;
    background: #f8fafc;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}

.oo-action-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.oo-action-btn-view {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}
.oo-action-btn-view:hover {
    background: #0ea5e9;
    color: white;
}

.oo-action-btn-edit {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}
.oo-action-btn-edit:hover {
    background: #3b82f6;
    color: white;
}

.oo-action-btn-delete {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}
.oo-action-btn-delete:hover {
    background: #ef4444;
    color: white;
}

/* Empty State */
.oo-empty-icon {
    width: 80px;
    height: 80px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2.5rem;
    color: #94a3b8;
}

/* Pagination */
.oo-pagination-info {
    color: #64748b;
    font-size: 0.9rem;
}

.oo-pagination .page-item .page-link {
    border-radius: 8px;
    margin: 0 3px;
    border: none;
    background: #f1f5f9;
    color: #475569;
    padding: 8px 14px;
}

.oo-pagination .page-item .page-link:hover {
    background: #e2e8f0;
    color: #1e293b;
}

.oo-pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.oo-pagination .page-item.disabled .page-link {
    background: #f8fafc;
    color: #cbd5e1;
}

/* Legend */
.oo-legend {
    background: #f8fafc;
    border-radius: 16px;
    padding: 24px;
}

.oo-legend-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    height: 100%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.oo-legend-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.oo-legend-icon {
    width: 40px;
    height: 40px;
    background: rgba(102,126,234,0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #667eea;
    font-size: 1.2rem;
}

.oo-legend-icon-orders {
    background: rgba(34,197,94,0.15);
    color: #22c55e;
}

.oo-legend-title {
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    font-size: 0.95rem;
}

.oo-legend-items {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.oo-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #64748b;
}

/* Spinner */
.oo-spin {
    animation: oo-spin 1s linear infinite;
}

@keyframes oo-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Dark Mode */
[data-theme="dark"] .oo-stats-card,
[data-theme="dark"] .oo-order-card {
    background: #1e293b;
}

[data-theme="dark"] .oo-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .oo-filter-btn {
    background: #1e293b;
    border-color: #334155;
    color: #94a3b8;
}

[data-theme="dark"] .oo-filter-btn:hover {
    border-color: #667eea;
    color: #667eea;
}

[data-theme="dark"] .oo-order-body {
    background: #1e293b;
}

[data-theme="dark"] .oo-order-footer {
    background: #0f172a;
}

[data-theme="dark"] .oo-order-meta {
    border-color: #334155;
}

[data-theme="dark"] .oo-legend {
    background: #0f172a;
}

[data-theme="dark"] .oo-legend-card {
    background: #1e293b;
}

[data-theme="dark"] .oo-legend-title {
    color: #f1f5f9;
}

/* Responsive */
@media (max-width: 991.98px) {
    .oo-btn span {
        display: none;
    }
    .oo-btn {
        padding: 10px 14px;
    }
}

@media (max-width: 767.98px) {
    .oo-header {
        padding: 16px;
    }
    .oo-header-icon {
        width: 48px;
        height: 48px;
        font-size: 1.4rem;
    }
    .oo-title {
        font-size: 1.25rem;
    }
    .oo-filter-btn {
        padding: 6px 12px;
        font-size: 0.8rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentFilter = 'all';
    let currentSearch = '';
    let perPage = 12;
    let totalOrders = 0;
    let ordersData = [];

    // Inicializar
    loadOrders();

    // Event: Búsqueda
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        currentSearch = $(this).val();
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadOrders();
        }, 500);
    });

    // Event: Filtros
    $('.oo-filter-btn').on('click', function() {
        $('.oo-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentFilter = $(this).data('filter');
        currentPage = 1;
        loadOrders();
    });

    // Event: Stats cards (filter click)
    $('.oo-stats-card').on('click', function() {
        const filter = $(this).data('filter');
        if (filter) {
            $('.oo-filter-btn').removeClass('active');
            $(`.oo-filter-btn[data-filter="${filter}"]`).addClass('active');
            currentFilter = filter;
            currentPage = 1;
            loadOrders();
        }
    });

    // Cargar pedidos
    function loadOrders() {
        $('#loading-container').show();
        $('#orders-grid').hide();
        $('#empty-state').hide();
        $('#pagination-container').hide();

        $.ajax({
            url: '{{ route("customers.original-orders.index", $customer) }}',
            type: 'GET',
            data: {
                page: currentPage,
                per_page: perPage,
                status_filter: currentFilter,
                search: currentSearch
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(response) {
                $('#loading-container').hide();

                if (response.data && response.data.length > 0) {
                    renderOrders(response.data);
                    renderPagination(response);
                    updateStats(response);
                    $('#orders-grid').show();
                    $('#pagination-container').css('display', 'flex');
                } else {
                    $('#empty-state').show();
                    updateStats({ stats: { total: 0, finished: 0, started: 0, pending: 0 }});
                }
            },
            error: function(xhr) {
                $('#loading-container').hide();
                $('#empty-state').show();
                console.error('Error loading orders:', xhr);
            }
        });
    }

    // Renderizar pedidos
    function renderOrders(orders) {
        const grid = $('#orders-grid');
        grid.empty();

        orders.forEach((order, index) => {
            const statusClass = getStatusClass(order);
            const statusBadge = getStatusBadge(order);
            const processes = renderProcesses(order.processes_html || order.processes);

            const card = `
                <div class="col-xl-3 col-lg-4 col-md-6 mb-4" style="animation-delay: ${index * 0.05}s">
                    <div class="oo-order-card">
                        <div class="oo-order-header ${statusClass}">
                            <div class="oo-order-id">
                                <i class="ti ti-package"></i>
                                ${order.order_id}
                            </div>
                            <div class="oo-order-client">${order.client_number}</div>
                            @can('original-order-delete')
                            <div class="oo-order-checkbox">
                                <input type="checkbox" class="form-check-input order-checkbox" value="${order.id}">
                            </div>
                            @endcan
                        </div>
                        <div class="oo-order-body">
                            <div class="oo-order-processes">
                                ${processes}
                            </div>
                            <div class="oo-order-meta">
                                <span class="oo-order-date">
                                    <i class="ti ti-calendar me-1"></i>${order.created_at}
                                </span>
                                ${statusBadge}
                            </div>
                        </div>
                        <div class="oo-order-footer">
                            @can('original-order-list')
                            <a href="{{ route('customers.original-orders.index', $customer) }}/${order.id}" class="oo-action-btn oo-action-btn-view" title="@lang('View')">
                                <i class="ti ti-eye"></i>
                            </a>
                            @endcan
                            @can('original-order-edit')
                            <a href="{{ route('customers.original-orders.index', $customer) }}/${order.id}/edit" class="oo-action-btn oo-action-btn-edit" title="@lang('Edit')">
                                <i class="ti ti-edit"></i>
                            </a>
                            @endcan
                            @can('original-order-delete')
                            <button class="oo-action-btn oo-action-btn-delete delete-order" data-id="${order.id}" title="@lang('Delete')">
                                <i class="ti ti-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            `;
            grid.append(card);
        });
    }

    // Helpers
    function getStatusClass(order) {
        if (order.finished_at && order.finished_at !== '-' && !order.finished_at.includes('Pendiente')) {
            return 'finished';
        }
        if (order.status === 'started' || order.finished_at?.includes('Iniciado')) return 'started';
        if (order.status === 'assigned' || order.finished_at?.includes('Asignado')) return 'assigned';
        if (order.status === 'to-create' || order.finished_at?.includes('Crear')) return 'to-create';
        return 'planned';
    }

    function getStatusBadge(order) {
        const status = order.finished_at || '@lang("Pendiente")';
        let badgeClass = 'bg-secondary';

        if (status.includes('Finalizado') || (status.match(/\d{4}-\d{2}-\d{2}/) && !status.includes('Pendiente'))) {
            badgeClass = 'bg-success';
        } else if (status.includes('Iniciado')) {
            badgeClass = 'bg-primary';
        } else if (status.includes('Asignado')) {
            badgeClass = 'bg-info';
        } else if (status.includes('Crear')) {
            badgeClass = 'bg-danger';
        }

        return `<span class="badge ${badgeClass} oo-order-status">${status}</span>`;
    }

    function renderProcesses(processesHtml) {
        if (typeof processesHtml === 'string') {
            return processesHtml;
        }
        return '<span class="badge bg-secondary">-</span>';
    }

    // Paginación
    function renderPagination(response) {
        const pagination = $('#pagination');
        pagination.empty();

        const lastPage = response.last_page || 1;
        const currentPageNum = response.current_page || 1;

        // Info
        $('#pagination-info').text(
            `@lang('Showing') ${response.from || 0} @lang('to') ${response.to || 0} @lang('of') ${response.total || 0}`
        );

        // Previous
        pagination.append(`
            <li class="page-item ${currentPageNum === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPageNum - 1}">
                    <i class="ti ti-chevron-left"></i>
                </a>
            </li>
        `);

        // Pages
        let startPage = Math.max(1, currentPageNum - 2);
        let endPage = Math.min(lastPage, currentPageNum + 2);

        if (startPage > 1) {
            pagination.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) {
                pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            pagination.append(`
                <li class="page-item ${i === currentPageNum ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
            pagination.append(`<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a></li>`);
        }

        // Next
        pagination.append(`
            <li class="page-item ${currentPageNum === lastPage ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPageNum + 1}">
                    <i class="ti ti-chevron-right"></i>
                </a>
            </li>
        `);
    }

    // Event: Paginación
    $(document).on('click', '.oo-pagination .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadOrders();
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    });

    // Stats
    function updateStats(response) {
        if (response.stats) {
            $('#stats-total').text(response.stats.total || 0);
            $('#stats-finished').text(response.stats.finished || 0);
            $('#stats-started').text(response.stats.started || 0);
            $('#stats-pending').text(response.stats.pending || 0);
        } else if (response.total !== undefined) {
            $('#stats-total').text(response.total);
        }
    }

    // Delete order
    $(document).on('click', '.delete-order', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            text: '{{ __("You will not be able to recover this order!") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '{{ __("Yes, delete it!") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("customers.original-orders.index", $customer) }}/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        Swal.fire('{{ __("Deleted!") }}', '{{ __("Order deleted successfully.") }}', 'success');
                        loadOrders();
                    },
                    error: function() {
                        Swal.fire('{{ __("Error") }}', '{{ __("Could not delete order.") }}', 'error');
                    }
                });
            }
        });
    });

    // Bulk delete
    $(document).on('change', '.order-checkbox', function() {
        updateBulkDeleteButton();
    });

    function updateBulkDeleteButton() {
        const count = $('.order-checkbox:checked').length;
        if (count > 0) {
            $('#bulk-delete').removeClass('d-none').find('span').text('{{ __("Delete") }} (' + count + ')');
        } else {
            $('#bulk-delete').addClass('d-none');
        }
    }

    $('#bulk-delete').on('click', function() {
        const ids = [];
        $('.order-checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) return;

        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            text: '{{ __("You will not be able to recover these orders!") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: '{{ __("Yes, delete!") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("customers.original-orders.bulk-delete", $customer->id) }}',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}', ids: ids },
                    success: function(response) {
                        Swal.fire('{{ __("Deleted!") }}', response.message, 'success');
                        loadOrders();
                        $('#bulk-delete').addClass('d-none');
                    },
                    error: function() {
                        Swal.fire('{{ __("Error") }}', '{{ __("Could not delete orders.") }}', 'error');
                    }
                });
            }
        });
    });

    // Import
    $('#import-orders-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="ti ti-loader oo-spin"></i>');

        $.ajax({
            url: '{{ route("customers.original-orders.import", $customer) }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                showToast(response.message || '@lang("Import completed")', response.success ? 'success' : 'error');
                if (response.success) loadOrders();
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || '@lang("Import failed")', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Create cards
    $('#create-cards-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<i class="ti ti-loader oo-spin"></i>');

        $.ajax({
            url: '{{ route("customers.original-orders.create-cards", $customer) }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                showToast(response.message || '@lang("Cards created")', response.success ? 'success' : 'error');
                if (response.success) loadOrders();
            },
            error: function(xhr) {
                showToast(xhr.responseJSON?.message || '@lang("Failed to create cards")', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Toast
    function showToast(message, type) {
        const toast = $('#update-time-toast');
        toast.removeClass('bg-info bg-success bg-danger');
        toast.addClass(type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info');
        toast.find('.toast-body').html(`<i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : 'info-circle'} me-2"></i>${message}`);
        new bootstrap.Toast(toast[0]).show();
    }

    // Auto refresh
    setInterval(function() {
        loadOrders();
    }, 60000);
});
</script>
@endpush
