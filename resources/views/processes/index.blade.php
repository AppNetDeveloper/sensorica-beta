@extends('layouts.admin')

@section('title', __('Process Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Process Management') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('List of Processes')</h5>
                        @can('process-create')
                        <a href="{{ route('processes.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> @lang('New Process')
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="processes-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('CÓDIGO')</th>
                                    <th class="text-uppercase">@lang('NOMBRE')</th>
                                    <th class="text-uppercase">@lang('FACTOR')</th>
                                    <th class="text-uppercase">@lang('SECUENCIA')</th>
                                    <th class="text-uppercase">@lang('DESCRIPCIÓN')</th>
                                    <th class="text-uppercase">@lang('ACCIONES')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($processes as $index => $process)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $process->code }}</td>
                                        <td>{{ $process->name }}</td>
                                        <td class="text-center">{{ number_format($process->factor_correccion, 2) }}</td>
                                        <td>{{ $process->sequence }}</td>
                                        <td>{{ $process->description ?? 'N/A' }}</td>
                                        <td>
                                            @can('process-show')
                                            <a href="{{ route('processes.show', $process) }}" class="btn btn-sm btn-info" title="@lang('View')">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @endcan
                                            
                                            @can('process-edit')
                                            <a href="{{ route('processes.edit', $process) }}" class="btn btn-sm btn-warning" title="@lang('Edit')">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endcan
                                            
                                            @can('process-delete')
                                            <form action="{{ route('processes.destroy', $process) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="@lang('Delete')" onclick="return confirm('@lang('Are you sure you want to delete this process?')')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        #processes-table_wrapper .dt-buttons {
            margin-bottom: 10px;
        }
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 10px;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .card-body {
            padding: 1.25rem;
        }
        #processes-table_wrapper {
            width: 100%;
        }
        .dataTables_paginate {
            margin-top: 0.5rem;
        }
        .page-item.active .page-link {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .page-link {
            color: #6c757d;
        }
        .page-link:hover {
            color: #5a6268;
        }
        .dataTables_info {
            padding-top: 0.5rem;
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }
        .dataTables_length select {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
        }
        .dataTables_filter input {
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid #ced4da;
        }
        /* Ocultar los íconos de ordenación predeterminados de DataTables */
        .sorting:before, .sorting:after, .sorting_asc:before, .sorting_asc:after, .sorting_desc:before, .sorting_desc:after {
            display: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.5rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #6c757d;
            color: white !important;
            border: 1px solid #6c757d;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #5a6268;
            color: white !important;
            border: 1px solid #5a6268;
        }
        /* Mejora el aspecto de la tabla */
        #processes-table {
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.25rem;
            overflow: hidden;
        }
        #processes-table thead th {
            border-top: none;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .card-header {
            border-bottom: 0;
        }
    </style>
        .container-fluid.px-0 {
            width: 100%;
            max-width: 100%;
        }
        .row.mx-0 {
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            const table = $('#processes-table').DataTable({
                responsive: {
                    details: false  // Deshabilitar detalles responsivos para evitar duplicación
                },
                scrollX: false, // Desactivar scrollX para evitar duplicación de encabezados
                pagingType: 'simple_numbers',
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('No entries to show') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    paginate: {
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: '»',
                        previous: '«'
                    },
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}",
                    infoPostFix: ""
                },
                dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
                autoWidth: false, // Evitar cálculo automático de ancho
                order: [[0, 'asc']],
                columnDefs: [
                    { 
                        orderable: true, 
                        targets: [0, 1, 2, 3, 4],
                        className: 'text-center'
                    },
                    { 
                        orderable: false, 
                        targets: [5], 
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('All') }}"]],
                pageLength: 10,
                initComplete: function() {
                    // Mejora el aspecto de la tabla después de inicializar
                    $('#processes-table_wrapper').addClass('pb-3');
                    $('#processes-table_length label').addClass('font-weight-normal');
                    $('#processes-table_filter label').addClass('font-weight-normal');
                    $('#processes-table_paginate').addClass('mt-3');
                    
                    // Añade íconos a los botones de ordenación
                    setTimeout(function() {
                        // Limpiar cualquier ícono existente primero
                        $('.sorting i, .sorting_asc i, .sorting_desc i').remove();
                        
                        // Añadir nuevos íconos
                        $('.sorting').append(' <i class="fas fa-sort text-muted"></i>');
                        $('.sorting_asc').append(' <i class="fas fa-sort-up"></i>');
                        $('.sorting_desc').append(' <i class="fas fa-sort-down"></i>');
                    }, 100);
                }
            });

            // Actualizar la tabla si hay un mensaje de éxito o error
            @if(session('success') || session('error'))
                table.draw(false);
            @endif
        });
    </script>
@endpush
