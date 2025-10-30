@extends('layouts.admin')

@php
    $canEditProcesses = auth()->user()?->can('process-edit') ?? false;
@endphp

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
                        <h5 class="mb-0 text-white">@lang('List of Processes')</h5>
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

                    @can('process-edit')
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn btn-outline-light text-primary btn-sm border-0 shadow-sm" id="bulk-edit-btn" data-bs-toggle="modal" data-bs-target="#bulkEditModal" disabled>
                                    <i class="fas fa-layer-group me-1"></i> @lang('Bulk edit selected')
                                </button>
                                <span class="text-muted small" id="selected-count">@lang('Selected processes: 0')</span>
                            </div>
                            <small class="text-muted">@lang('Select one or more processes to edit color, Kanban position, factor or sequence in bulk.')</small>
                        </div>
                    @endcan
                    
                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="processes-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    @can('process-edit')
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="select-all" class="form-check-input" title="@lang('Select all')">
                                        </th>
                                    @endcan
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('CÓDIGO')</th>
                                    <th class="text-uppercase">@lang('NOMBRE')</th>
                                    <th class="text-uppercase">@lang('FACTOR')</th>
                                    <th class="text-uppercase">@lang('SECUENCIA')</th>
                                    <th class="text-uppercase">@lang('COLOR')</th>
                                    <th class="text-uppercase">@lang('POS. KANBAN')</th>
                                    <th class="text-uppercase">@lang('DESCRIPCIÓN')</th>
                                    <th class="text-uppercase">@lang('ACCIONES')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($processes as $index => $process)
                                    <tr>
                                        @can('process-edit')
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input process-select" value="{{ $process->id }}"
                                                    data-name="{{ $process->name }}">
                                            </td>
                                        @endcan
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $process->code }}</td>
                                        <td>{{ $process->name }}</td>
                                        <td class="text-center">{{ number_format($process->factor_correccion, 2) }}</td>
                                        <td>{{ $process->sequence }}</td>
                                        <td class="text-center">
                                            @if($process->color)
                                                <span style="display: inline-block; width: 30px; height: 30px; background-color: {{ $process->color }}; border: 1px solid #ddd; border-radius: 4px;" title="{{ $process->color }}"></span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $process->posicion_kanban ?? '-' }}</td>
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

                    @can('process-edit')
                        <form id="bulk-edit-form" action="{{ route('processes.bulk-update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="process_ids" id="process-ids-input">

                            <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title" id="bulkEditModalLabel"><i class="fas fa-layer-group me-2"></i>@lang('Bulk edit processes')</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-3 text-muted">
                                                @lang('You can update the selected fields for all chosen processes. Any field left empty will remain unchanged.')
                                            </p>
                                            <div class="alert alert-info" role="alert">
                                                <strong>@lang('Selected processes'):</strong>
                                                <span id="bulk-selected-names" class="ms-1 text-dark fw-semibold">-</span>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="bulk-color" class="form-label">@lang('Color')</label>
                                                    <input type="color" class="form-control form-control-color" id="bulk-color" name="color">
                                                    <small class="text-muted">@lang('Set a color for all selected processes. Leave empty to keep current colors.')</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="bulk-posicion" class="form-label">@lang('Kanban Position')</label>
                                                    <input type="number" min="1" class="form-control" id="bulk-posicion" name="posicion_kanban">
                                                    <small class="text-muted">@lang('Set a position for the Kanban board. Leave empty to keep current positions.')</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="bulk-factor" class="form-label">@lang('Correction Factor')</label>
                                                    <input type="number" step="0.01" min="0.1" class="form-control" id="bulk-factor" name="factor_correccion">
                                                    <small class="text-muted">@lang('Example: 60.00. Leave empty to avoid changing the factor.')</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="bulk-sequence" class="form-label">@lang('Sequence')</label>
                                                    <input type="number" min="1" class="form-control" id="bulk-sequence" name="sequence">
                                                    <small class="text-muted">@lang('Defines the process order. Leave empty to keep the current value.')</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer d-flex justify-content-between">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>@lang('Cancel')
                                            </button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i>@lang('Apply changes')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endcan
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
            const canEdit = @json($canEditProcesses);

            const columnDefs = [];
            const defaultOrder = [0, 'asc'];

            if (canEdit) {
                columnDefs.push(
                    {
                        orderable: false,
                        targets: [0, 6, 8, 9],
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        orderable: true,
                        targets: [1, 2, 3, 4, 5, 7],
                        className: 'text-center'
                    }
                );
                defaultOrder[0] = 1; // Ordenar por la columna de índice cuando hay checkbox
            } else {
                columnDefs.push(
                    {
                        orderable: false,
                        targets: [0, 5, 7, 8],
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        orderable: true,
                        targets: [1, 2, 3, 4, 6],
                        className: 'text-center'
                    }
                );
            }

            const table = $('#processes-table').DataTable({
                responsive: {
                    details: false // Deshabilitar detalles responsivos para evitar duplicación
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
                order: [defaultOrder],
                columnDefs: columnDefs,
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

            if (!canEdit) {
                return;
            }

            const selectAll = $('#select-all');
            const bulkEditBtn = $('#bulk-edit-btn');
            const selectedCount = $('#selected-count');
            const selectedNamesSpan = $('#bulk-selected-names');
            const processIdsContainer = $('#process-ids-input');
            const modalElement = document.getElementById('bulkEditModal');
            const bulkForm = $('#bulk-edit-form');

            function updateSelectionState() {
                const checkedBoxes = $('.process-select:checked');
                const totalBoxes = $('.process-select');
                const ids = checkedBoxes.map(function() { return $(this).val(); }).get();
                const names = checkedBoxes.map(function() { return $(this).data('name'); }).get();

                selectAll.prop('checked', checkedBoxes.length && checkedBoxes.length === totalBoxes.length);
                bulkEditBtn.prop('disabled', checkedBoxes.length === 0);

                selectedCount.text("{{ __('Selected processes:') }} " + checkedBoxes.length);
                selectedNamesSpan.text(names.length ? names.join(', ') : '-');

                processIdsContainer.val(JSON.stringify(ids));
            }

            // Manejo de selección de filas
            $(document).on('change', '.process-select', updateSelectionState);

            selectAll.on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.process-select').prop('checked', isChecked);
                updateSelectionState();
            });

            // Limpiar inputs al cerrar el modal
            modalElement.addEventListener('hidden.bs.modal', function () {
                $('#bulk-color').val('');
                $('#bulk-posicion').val('');
                $('#bulk-factor').val('');
                $('#bulk-sequence').val('');
            });

            // Antes de enviar, transformar el valor JSON a campos ocultos tipo array
            bulkForm.on('submit', function(event) {
                const ids = JSON.parse(processIdsContainer.val() || '[]');

                if (!ids.length) {
                    event.preventDefault();
                    bulkEditBtn.prop('disabled', true);
                    return;
                }

                // Limpiar inputs anteriores
                bulkForm.find('input[name="process_ids[]"]').remove();

                ids.forEach(function(id) {
                    bulkForm.append('<input type="hidden" name="process_ids[]" value="' + id + '">');
                });
            });
        });
    </script>
@endpush
