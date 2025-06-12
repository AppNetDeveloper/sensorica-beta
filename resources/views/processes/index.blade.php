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
                                    <tr>
                                        <td colspan="6" class="text-center">@lang('No processes found.')</td>
                                    </tr>
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
                responsive: true,
                scrollX: true,
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('Showing 0 to 0 of 0 entries') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    paginate: {
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: "{{ __('Next') }}",
                        previous: "{{ __('Previous') }}"
                    },
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}"
                },
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: true, targets: [0, 1, 2, 3, 4] },
                    { orderable: false, targets: [5], searchable: false }
                ],
                dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
                buttons: [
                    {
                        extend: 'pageLength',
                        className: 'btn btn-secondary'
                    }
                ],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('All') }}"]],
                pageLength: 10
            });

            // Actualizar la tabla si hay un mensaje de éxito o error
            @if(session('success') || session('error'))
                table.draw(false);
            @endif
        });
    </script>
@endpush
