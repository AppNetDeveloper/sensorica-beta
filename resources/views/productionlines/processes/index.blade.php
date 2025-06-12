@extends('layouts.admin')

@section('title', __('Processes de la Línia de Producció'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => $productionLine->customer_id]) }}">{{ __('Línies de Producció') }}</a>
        </li>
        <li class="breadcrumb-item">{{ $productionLine->name }} - {{ __('Processos') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mt-3">
        <div class="col-lg-12">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Processos de la Línia de Producció') }}: {{ $productionLine->name }}</h5>
                        <div>
                            @can('productionline-process-create')
                            <a href="{{ route('productionlines.processes.create', $productionLine->id) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-plus"></i> {{ __('Afegir Procés') }}
                            </a>
                            @endcan
                            <a href="{{ route('productionlines.index', ['customer_id' => $productionLine->customer_id]) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> {{ __('Tornar') }}
                            </a>
                        </div>
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
                        <table id="processes-table" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th width="10%" class="text-center">{{ __('Ordre') }}</th>
                                    <th width="20%">{{ __('Codi') }}</th>
                                    <th width="40%">{{ __('Nom') }}</th>
                                    <th width="30%" class="text-center">{{ __('Accions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($processes as $process)
                                    <tr>
                                        <td class="text-center">{{ $process->pivot->order }}</td>
                                        <td>{{ $process->code }}</td>
                                        <td>{{ $process->name }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                @can('productionline-process-edit')
                                                <a href="{{ route('productionlines.processes.edit', [$productionLine->id, $process->id]) }}" 
                                                   class="btn btn-sm btn-warning mx-1" 
                                                   title="{{ __('Editar ordre') }}">
                                                    <i class="fas fa-edit"></i> {{ __('Editar') }}
                                                </a>
                                                @endcan
                                                
                                                @can('productionline-process-delete')
                                                <form action="{{ route('productionlines.processes.destroy', [$productionLine->id, $process->id]) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('{{ __('Esteu segur que voleu eliminar aquesta associació?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger mx-1" title="{{ __('Eliminar associació') }}">
                                                        <i class="fas fa-trash"></i> {{ __('Eliminar') }}
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3">{{ __('No s\'han trobat processos associats.') }}</td>
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

@push('style')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    {{-- Extensión Responsive de DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

    <style>
        /* Estilos de la tabla */
        .table th, .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }
        
        /* Hacer la tabla más ancha y con bordes más visibles */
        .table-bordered {
            border: 1px solid #dee2e6;
        }
        
        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
        }
        
        /* Encabezados de tabla más destacados */
        .table thead th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        /* Mejorar el espaciado entre botones */
        .btn-group .btn {
            margin: 0 3px;
        }
        
        /* Espacio entre botones DataTables y la tabla */
        .dt-buttons {
            margin-bottom: 1rem;
        }
        
        /* Clase para que la tabla se muestre más angosta y centrada */
        .my-narrow-table {
            max-width: 90%;
            margin: 0 auto;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- DataTables núcleo --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    {{-- Extensiones DataTables: Buttons, JSZip, etc. --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    {{-- Extensión Responsive de DataTables --}}
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

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
                    { orderable: true, targets: [0, 1, 2] },
                    { orderable: false, targets: [3], searchable: false }
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'pageLength',
                        className: 'btn btn-secondary'
                    }
                ],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('Tots') }}"]],
                pageLength: 10
            });

            // Actualizar la tabla si hay un mensaje de éxito o error
            @if(session('success') || session('error'))
                table.draw(false);
            @endif
        });
    </script>
@endpush
