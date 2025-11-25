@extends('layouts.admin')

@section('title', __('Procesos Finalizados'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.original-orders.index', $customer) }}">{{ __('Original Orders') }}</a></li>
        <li class="breadcrumb-item active">{{ __('Procesos Finalizados') }}</li>
    </ul>
@endsection

@section('content')
<div class="fp-container">
    {{-- Header Principal --}}
    <div class="fp-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-5 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="fp-header-icon me-3">
                        <i class="ti ti-clipboard-check"></i>
                    </div>
                    <div>
                        <h4 class="fp-title mb-1">{{ __('Procesos Finalizados') }}</h4>
                        <p class="fp-subtitle mb-0">{{ $customer->name }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    {{-- Filtros de fecha --}}
                    <div class="fp-date-filter">
                        <i class="ti ti-calendar"></i>
                        <input type="date" id="date_from" class="form-control" value="{{ date('Y-m-d', strtotime('-1 day')) }}">
                    </div>
                    <span class="fp-date-separator">{{ __('a') }}</span>
                    <div class="fp-date-filter">
                        <i class="ti ti-calendar"></i>
                        <input type="date" id="date_to" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <button id="apply-filters" class="fp-btn fp-btn-light">
                        <i class="ti ti-filter"></i>
                        <span>{{ __('Aplicar') }}</span>
                    </button>
                    <a href="{{ route('customers.original-orders.index', $customer) }}" class="fp-btn fp-btn-outline">
                        <i class="ti ti-arrow-left"></i>
                        <span>{{ __('Volver') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="fp-stats-card">
                <div class="fp-stats-icon fp-stats-primary">
                    <i class="ti ti-list-check"></i>
                </div>
                <div class="fp-stats-info">
                    <h3 id="stats-total">0</h3>
                    <span>{{ __('Total Procesos') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="fp-stats-card">
                <div class="fp-stats-icon fp-stats-success">
                    <i class="ti ti-box"></i>
                </div>
                <div class="fp-stats-info">
                    <h3 id="stats-boxes">0</h3>
                    <span>{{ __('Total Boxes') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="fp-stats-card">
                <div class="fp-stats-icon fp-stats-info">
                    <i class="ti ti-stack-2"></i>
                </div>
                <div class="fp-stats-info">
                    <h3 id="stats-units">0</h3>
                    <span>{{ __('Total Units') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="fp-stats-card">
                <div class="fp-stats-icon fp-stats-warning">
                    <i class="ti ti-forklift"></i>
                </div>
                <div class="fp-stats-info">
                    <h3 id="stats-pallets">0</h3>
                    <span>{{ __('Total Pallets') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card fp-table-card">
        <div class="card-header fp-card-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <i class="ti ti-table me-2"></i>
                    <span class="fp-card-title">{{ __('Detalle de Procesos') }}</span>
                </div>
                <button type="button" id="toggle-all-details" class="fp-btn-toggle" title="{{ __('Abrir/cerrar todos los artículos') }}">
                    <i class="ti ti-layout-rows"></i>
                    <span>{{ __('Expandir todos') }}</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="finished-processes-table" class="table fp-table w-100">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="text-center">
                                <i class="ti ti-list-details" title="{{ __('Artículos') }}"></i>
                            </th>
                            <th>#</th>
                            <th>{{ __('Fecha fin') }}</th>
                            <th>{{ __('ORDER ID') }}</th>
                            <th>{{ __('Proceso') }}</th>
                            <th>{{ __('Grupo') }}</th>
                            <th>{{ __('Boxes') }}</th>
                            <th>{{ __('Units/Box') }}</th>
                            <th>{{ __('Total Units') }}</th>
                            <th>{{ __('Pallets') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
/* Container */
.fp-container {
    padding: 0;
}

/* Header */
.fp-header {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.fp-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.fp-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.fp-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Date Filters */
.fp-date-filter {
    position: relative;
    display: flex;
    align-items: center;
}

.fp-date-filter i {
    position: absolute;
    left: 12px;
    color: rgba(255,255,255,0.6);
    font-size: 1rem;
    pointer-events: none;
}

.fp-date-filter input {
    padding-left: 38px;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    color: white;
    height: 42px;
    font-size: 0.9rem;
    min-width: 150px;
}

.fp-date-filter input:focus {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    box-shadow: none;
    color: white;
}

.fp-date-filter input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

.fp-date-separator {
    color: rgba(255,255,255,0.7);
    font-size: 0.9rem;
    padding: 0 4px;
}

/* Header Buttons */
.fp-btn {
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

.fp-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.fp-btn-light {
    background: white;
    color: #16a34a;
}
.fp-btn-light:hover {
    background: #f0fdf4;
    color: #15803d;
}

.fp-btn-outline {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}
.fp-btn-outline:hover {
    background: rgba(255,255,255,0.25);
    color: white;
}

/* Stats Cards */
.fp-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.fp-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.fp-stats-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.fp-stats-primary {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.fp-stats-success {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.fp-stats-info {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.fp-stats-warning {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.fp-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.fp-stats-info span {
    color: #64748b;
    font-size: 0.85rem;
}

/* Table Card */
.fp-table-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

.fp-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 16px 20px;
}

.fp-card-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 1rem;
}

.fp-btn-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    background: white;
    border: 1px solid #e2e8f0;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
}

.fp-btn-toggle:hover {
    background: #f1f5f9;
    color: #22c55e;
    border-color: #22c55e;
}

/* Table Styles */
.fp-table {
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
}

.fp-table thead th {
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

.fp-table tbody td {
    padding: 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.875rem;
    color: #334155;
}

.fp-table tbody tr {
    transition: all 0.2s ease;
}

.fp-table tbody tr:hover {
    background: #f8fafc;
}

.fp-table tbody tr.shown {
    background: #f0fdf4;
}

/* Details Control Button */
.fp-details-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.fp-details-btn:hover {
    background: #22c55e;
    border-color: #22c55e;
    color: white;
}

tr.shown .fp-details-btn {
    background: #22c55e;
    border-color: #22c55e;
    color: white;
}

/* Mini Articles Table */
.fp-articles-container {
    background: #f8fafc;
    padding: 16px 20px;
    border-radius: 0;
}

.fp-articles-title {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.9rem;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.fp-articles-title i {
    color: #22c55e;
}

.fp-mini-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.fp-mini-table table {
    margin-bottom: 0;
}

.fp-mini-table thead th {
    background: #f1f5f9;
    padding: 10px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    border: none;
}

.fp-mini-table tbody td {
    padding: 10px 12px;
    font-size: 0.8rem;
    border-bottom: 1px solid #f1f5f9;
}

.fp-mini-table tbody tr:last-child td {
    border-bottom: none;
}

/* Badges */
.fp-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.fp-badge-success {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
}

.fp-badge-danger {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

.fp-badge-secondary {
    background: rgba(100, 116, 139, 0.15);
    color: #475569;
}

/* DataTables Overrides */
.fp-table-card .dataTables_wrapper {
    padding: 0;
}

.fp-table-card .dataTables_filter {
    margin-bottom: 16px;
    padding: 0 20px;
}

.fp-table-card .dataTables_filter input {
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    padding: 8px 16px;
    font-size: 0.9rem;
}

.fp-table-card .dataTables_filter input:focus {
    border-color: #22c55e;
    box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.fp-table-card .dataTables_length {
    padding: 0 20px;
    margin-bottom: 16px;
}

.fp-table-card .dataTables_length select {
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    padding: 6px 12px;
}

.fp-table-card .dataTables_info {
    padding: 16px 20px;
    color: #64748b;
    font-size: 0.85rem;
}

.fp-table-card .dataTables_paginate {
    padding: 16px 20px;
}

.fp-table-card .dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    margin: 0 2px;
    border: none !important;
}

.fp-table-card .dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    color: white !important;
    border: none !important;
}

.fp-table-card .dataTables_paginate .paginate_button:hover {
    background: #f1f5f9 !important;
    color: #22c55e !important;
    border: none !important;
}

/* Responsive Scroll */
.fp-table-card .table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
}

.fp-table-card .table-responsive::-webkit-scrollbar {
    height: 8px;
}

.fp-table-card .table-responsive::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.fp-table-card .table-responsive::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 4px;
}

@media (max-width: 991.98px) {
    .fp-table {
        min-width: 900px !important;
    }

    .fp-table thead th,
    .fp-table tbody td {
        padding: 10px 8px !important;
        font-size: 0.75rem !important;
        white-space: nowrap !important;
    }
}

@media (max-width: 767.98px) {
    .fp-header {
        padding: 16px;
    }

    .fp-header-icon {
        width: 48px;
        height: 48px;
        font-size: 1.4rem;
    }

    .fp-title {
        font-size: 1.25rem;
    }

    .fp-date-filter input {
        min-width: 130px;
        font-size: 0.8rem;
    }

    .fp-btn span {
        display: none;
    }

    .fp-btn {
        padding: 10px 14px;
    }

    .fp-stats-card {
        padding: 14px;
    }

    .fp-stats-icon {
        width: 40px;
        height: 40px;
        font-size: 1.1rem;
    }

    .fp-stats-info h3 {
        font-size: 1.25rem;
    }
}

/* Dark Mode */
[data-theme="dark"] .fp-stats-card,
[data-theme="dark"] .fp-table-card {
    background: #1e293b;
}

[data-theme="dark"] .fp-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .fp-card-header {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .fp-card-title {
    color: #f1f5f9;
}

[data-theme="dark"] .fp-table thead th {
    background: #0f172a;
    color: #94a3b8;
    border-color: #334155;
}

[data-theme="dark"] .fp-table tbody td {
    border-color: #334155;
    color: #e2e8f0;
}

[data-theme="dark"] .fp-table tbody tr:hover {
    background: #334155;
}

[data-theme="dark"] .fp-table tbody tr.shown {
    background: rgba(34, 197, 94, 0.1);
}

[data-theme="dark"] .fp-articles-container {
    background: #0f172a;
}

[data-theme="dark"] .fp-mini-table {
    background: #1e293b;
}

[data-theme="dark"] .fp-mini-table thead th {
    background: #334155;
    color: #94a3b8;
}

[data-theme="dark"] .fp-mini-table tbody td {
    color: #e2e8f0;
    border-color: #334155;
}

[data-theme="dark"] .fp-btn-toggle {
    background: #334155;
    border-color: #475569;
    color: #94a3b8;
}

[data-theme="dark"] .fp-details-btn {
    background: #334155;
    border-color: #475569;
    color: #94a3b8;
}

[data-theme="dark"] .fp-table-card .dataTables_filter input {
    background: #334155;
    border-color: #475569;
    color: #f1f5f9;
}

[data-theme="dark"] .fp-table-card .dataTables_length select {
    background: #334155;
    border-color: #475569;
    color: #f1f5f9;
}

[data-theme="dark"] .fp-table-card .table-responsive::-webkit-scrollbar-track {
    background: #1e293b;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function () {
    function formatArticles(rowData) {
        const items = rowData.articles || [];
        if (!items.length) {
            return '<div class="fp-articles-container"><p class="text-muted mb-0"><i class="ti ti-info-circle me-2"></i>{{ __("No related articles") }}</p></div>';
        }
        let rows = items.map(a => `
            <tr>
                <td><strong>${a.codigo_articulo ?? '-'}</strong></td>
                <td>${a.descripcion_articulo ?? '-'}</td>
                <td>${a.grupo_articulo ?? '-'}</td>
                <td class="text-center">
                    ${a.in_stock === 0
                        ? '<span class="fp-badge fp-badge-danger">{{ __("Sin Stock") }}</span>'
                        : (a.in_stock === 1
                            ? '<span class="fp-badge fp-badge-success">{{ __("Con Stock") }}</span>'
                            : '<span class="fp-badge fp-badge-secondary">{{ __("N/E") }}</span>')}
                </td>
            </tr>
        `).join('');
        return `
            <div class="fp-articles-container">
                <div class="fp-articles-title">
                    <i class="ti ti-package"></i>
                    {{ __('Artículos Relacionados') }} (${items.length})
                </div>
                <div class="fp-mini-table">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th style="width:20%">{{ __('Código') }}</th>
                                <th style="width:45%">{{ __('Descripción') }}</th>
                                <th style="width:15%">{{ __('Grupo') }}</th>
                                <th style="width:20%" class="text-center">{{ __('Stock') }}</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>`;
    }

    let table = $('#finished-processes-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("customers.original-orders.finished-processes.data", $customer) }}',
            data: function(d) {
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
            },
            dataSrc: function(json) {
                // Actualizar estadísticas
                if (json.stats) {
                    $('#stats-total').text(json.stats.total || 0);
                    $('#stats-boxes').text(json.stats.boxes || 0);
                    $('#stats-units').text(json.stats.units || 0);
                    $('#stats-pallets').text(json.stats.pallets || 0);
                } else {
                    // Calcular desde los datos si no hay stats
                    $('#stats-total').text(json.recordsFiltered || 0);
                }
                return json.data;
            }
        },
        columns: [
            {
                data: 'details',
                orderable: false,
                searchable: false,
                className: 'text-center',
                defaultContent: '<button class="fp-details-btn details-control" title="{{ __("Ver artículos") }}"><i class="ti ti-chevron-down"></i></button>'
            },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'finished_at', name: 'finished_at', defaultContent: '-' },
            { data: 'order_id', name: 'order_id', defaultContent: '-' },
            { data: 'process', name: 'process_description', defaultContent: '-' },
            { data: 'grupo_numero', name: 'grupo_numero', defaultContent: '-' },
            { data: 'box', name: 'box', defaultContent: '-' },
            { data: 'units_box', name: 'units_box', defaultContent: '-' },
            { data: 'total_units', name: 'total_units', orderable: false, searchable: false, defaultContent: '-' },
            { data: 'number_of_pallets', name: 'number_of_pallets', defaultContent: '-' },
        ],
        order: [[2, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() == "es" ? "es-ES" : "en-GB" }}.json'
        },
        drawCallback: function() {
            // Actualizar icono del toggle
            const allExpanded = table.rows().every(function() {
                return this.child.isShown();
            });
        }
    });

    // Toggle abrir/cerrar todos
    $('#toggle-all-details').on('click', function () {
        const btn = $(this);
        const pageRows = table.rows({ page: 'current' });
        const anyClosed = pageRows.indexes().toArray().some(function (idx) {
            return !table.row(idx).child.isShown();
        });

        pageRows.every(function () {
            const row = this;
            const tr = $(row.node());
            if (anyClosed) {
                if (!row.child.isShown()) {
                    row.child(formatArticles(row.data())).show();
                    tr.addClass('shown');
                    tr.find('.fp-details-btn i').removeClass('ti-chevron-down').addClass('ti-chevron-up');
                }
            } else {
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    tr.find('.fp-details-btn i').removeClass('ti-chevron-up').addClass('ti-chevron-down');
                }
            }
        });

        // Actualizar texto del botón
        if (anyClosed) {
            btn.find('span').text('{{ __("Contraer todos") }}');
        } else {
            btn.find('span').text('{{ __("Expandir todos") }}');
        }
    });

    // Aplicar filtros
    $('#apply-filters').on('click', function() {
        table.ajax.reload();
    });

    // Toggle individual
    $('#finished-processes-table tbody').on('click', 'button.details-control', function () {
        const tr = $(this).closest('tr');
        const row = table.row(tr);
        const btn = $(this);

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            btn.find('i').removeClass('ti-chevron-up').addClass('ti-chevron-down');
        } else {
            row.child(formatArticles(row.data())).show();
            tr.addClass('shown');
            btn.find('i').removeClass('ti-chevron-down').addClass('ti-chevron-up');
        }
    });
});
</script>
@endpush
