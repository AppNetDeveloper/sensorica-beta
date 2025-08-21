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
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 py-2">
                    <div class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label for="date_from" class="form-label mb-0 small">{{ __('Desde') }}</label>
                            <input type="date" id="date_from" class="form-control form-control-sm">
                        </div>
                        <div>
                            <label for="date_to" class="form-label mb-0 small">{{ __('Hasta') }}</label>
                            <input type="date" id="date_to" class="form-control form-control-sm">
                        </div>
                        <button id="apply-filters" class="btn btn-primary btn-sm mt-3 mt-sm-0">
                            <i class="fas fa-filter me-1"></i> {{ __('Aplicar') }}
                        </button>
                    </div>
                    <div>
                        <a href="{{ route('customers.original-orders.index', $customer) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> {{ __('Volver a pedidos') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="finished-processes-table" class="table table-striped table-hover w-100">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px;"></th>
                                    <th>#</th>
                                    <th>{{ __('Fecha fin') }}</th>
                                    <th>{{ __('ORDER ID') }}</th>
                                    <th>{{ __('Proceso') }}</th>
                                    <th>{{ __('Grupo') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
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
    <style>
        /* Margen inferior del buscador, igual que en index */
        #finished-processes-table_wrapper .dataTables_filter { margin-bottom: 10px; }
        /* Padding del card-body igual que index */
        .card-body {
            padding: 1.25rem;
        }
        /* Alinear paginación a la derecha (igual que index) */
        #finished-processes-table_wrapper .dataTables_paginate {
            float: right !important;
            width: 100%;
            text-align: right !important;
        }
        /* Espacio entre info y paginación (igual que index) */
        #finished-processes-table_wrapper .dataTables_info {
            padding-top: 8px;
            margin-bottom: 10px;
        }
        /* Asegurar espacio entre bordes de card y tabla */
        .card-body .table {
            margin-bottom: 0;
        }
        /* Estilos mini tabla artículos */
        .mini-articles {
            background: #fff;
            border-radius: .375rem;
            overflow: hidden;
        }
        .mini-articles thead th {
            background: #f8f9fa;
        }
        .details-control {
            padding: .15rem .35rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(function () {
            function formatArticles(rowData) {
                const items = rowData.articles || [];
                if (!items.length) {
                    return '<div class="p-3 text-muted">@lang('No related articles')</div>';
                }
                let rows = items.map(a => `
                    <tr>
                        <td>${a.codigo_articulo ?? ''}</td>
                        <td>${a.descripcion_articulo ?? ''}</td>
                        <td>${a.grupo_articulo ?? ''}</td>
                        <td class="text-center">${a.in_stock === 0 ? '<span class="badge bg-danger">@lang('Sin Stock')</span>' : (a.in_stock === 1 ? '<span class="badge bg-success">@lang('Con Stock')</span>' : '<span class="badge bg-secondary">@lang('No Especificado')</span>')}</td>
                    </tr>
                `).join('');
                return `
                    <div class="p-3">
                        <h6 class="mb-3"><i class="fas fa-cubes text-secondary me-2"></i>@lang('Related Articles')</h6>
                        <div class="table-responsive mini-articles">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width:25%">@lang('Article Code')</th>
                                        <th style="width:45%">@lang('Description')</th>
                                        <th style="width:15%">@lang('Group')</th>
                                        <th style="width:15%">@lang('Stock Status')</th>
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
                responsive: true,
                ajax: {
                    url: '{{ route('customers.original-orders.finished-processes.data', $customer) }}',
                    data: function(d) {
                        d.date_from = $('#date_from').val();
                        d.date_to = $('#date_to').val();
                    }
                },
                columns: [
                    { data: 'details', name: 'details', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'finished_at', name: 'finished_at' },
                    { data: 'order_id', name: 'order_id' },
                    { data: 'process', name: 'process_description' },
                    { data: 'grupo_numero', name: 'grupo_numero' },
                ],
                order: [[1, 'desc']],
                language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json' }
            });

            $('#apply-filters').on('click', function() {
                table.ajax.reload();
            });

            $('#finished-processes-table tbody').on('click', 'button.details-control', function () {
                const tr = $(this).closest('tr');
                const row = table.row(tr);
                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                } else {
                    row.child(formatArticles(row.data())).show();
                    tr.addClass('shown');
                }
            });
        });
    </script>
@endpush
