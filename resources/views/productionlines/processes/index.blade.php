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
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ __('Processos de la Línia de Producció') }}: {{ $productionLine->name }}</h5>
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
                                        <td class="text-center" colspan="4">{{ __('No s\'han trobat processos associats.') }}</td>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">

    <style>
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
        
        .container-fluid.px-0 {
            width: 100%;
            max-width: 100%;
        }
        
        .row.mx-0 {
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
        
        #processes-table_wrapper {
            width: 100%;
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    {{-- DataTables Core --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    {{-- DataTables Extensions --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    
    {{-- DataTables Responsive --}}
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    
    {{-- DataTables SearchPanes --}}
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>
    
    {{-- DataTables Select --}}
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

    <script>
        $(document).ready(function() {
            // Verificar si hay filas en la tabla
            const hasRows = $('#processes-table tbody tr').length > 0;
            
            // Configuración base de DataTables
            const tableConfig = {
                responsive: true,
                scrollX: true,
                // Habilitar búsqueda y paginación
                searching: true,
                paging: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('All') }}"]],
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('No records available') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}",
                    loadingRecords: "{{ __('Loading...') }}",
                    processing: "{{ __('Processing...') }}",
                    searchPlaceholder: "{{ __('Search...') }}",
                    paginate: {
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: "{{ __('Next') }}",
                        previous: "{{ __('Previous') }}"
                    }
                },
                // Configuración de columnas
                columnDefs: [
                    { 
                        width: '10%', 
                        targets: 0, 
                        className: 'text-center',
                        orderable: true // Permitir ordenar por orden
                    },
                    { 
                        width: '20%', 
                        targets: 1,
                        orderable: true // Permitir ordenar por código
                    },
                    { 
                        width: '40%', 
                        targets: 2,
                        orderable: true // Permitir ordenar por nombre
                    },
                    { 
                        width: '30%', 
                        targets: 3, 
                        orderable: false, // No permitir ordenar por acciones
                        className: 'text-center' 
                    }
                ],
                // Ordenar por la primera columna (orden) de forma ascendente por defecto
                order: [[0, 'asc']],
                // Configuración completa del DOM para mostrar todas las características
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex"B><"d-flex"f>>rt<"d-flex justify-content-between"<"d-flex"li><"d-flex"p>>',
                buttons: [
                    {
                        extend: 'pageLength',
                        className: 'btn btn-secondary btn-sm',
                        text: '<i class="fas fa-list"></i> {{ __("Show") }}'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> {{ __("Print") }}',
                        className: 'btn btn-info btn-sm',
                        exportOptions: {
                            columns: [0, 1, 2]
                        }
                    }
                ]
            };

            // Inicializar DataTables con la configuración adecuada según si hay datos o no
            let table;
            
            if (hasRows) {
                // Si hay filas en el HTML, inicializar con esos datos
                table = $('#processes-table').DataTable(tableConfig);
                console.log('DataTables inicializado con datos existentes');
            } else {
                // Si no hay filas, inicializar con datos vacíos pero manteniendo la estructura
                table = $('#processes-table').DataTable({
                    ...tableConfig,
                    data: [],
                    columns: [
                        { title: '{{ __("Ordre") }}', className: 'text-center' },
                        { title: '{{ __("Codi") }}' },
                        { title: '{{ __("Nom") }}' },
                        { title: '{{ __("Accions") }}', className: 'text-center' }
                    ]
                });
                console.log('DataTables inicializado con datos vacíos');
                // Mostrar mensaje de "no hay datos"
                table.clear().draw();
            }
            
            // Asegurarse de que la tabla es responsiva
            new $.fn.dataTable.Responsive(table);
        });
    </script>
@endpush
