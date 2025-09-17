@extends('layouts.admin')
@section('title', __('Production Lines'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item">{{ __('Production Lines') }}</li>
    </ul>
@endsection
@section('content')
    <div class="row">
        <div class="mb-3">
            @can('productionline-create')
            <a href="{{ route('productionlines.create', ['customer_id' => $customer_id]) }}" class="btn btn-primary">
                {{ __('Añadir Nueva Línea') }}
            </a>
            @endcan
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <!-- Tabla con clase 'data-table' y 'responsive' para DataTables -->
                    <div class="table-responsive py-5 pb-4 dropdown_2">
                        <div class="container-fluid">
                            <table class="table table-bordered data-table" style="width:100%">
                                <thead>
                                    <tr>
                                        {{-- Columna para el ícono + (child rows) --}}
                                        <th></th>
                                        <th>ID</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Token') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- DataTables cargará aquí los registros -->
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
    @include('layouts.includes.datatable_css')
    <!-- CSS de DataTables Responsive (si no lo tienes cargado en tu layout principal) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
@endpush

@push('scripts')
    @include('layouts.includes.datatable_js')
    <!-- JS de DataTables Responsive (si no lo tienes cargado en tu layout principal) -->
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <script type="text/javascript">
        $(function () {
            var customer_id = "{{ $customer_id }}";

            // Inicializamos la DataTable con modo responsive + columna "control"
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "/customers/" + customer_id + "/productionlinesjson",
                order: [[1, 'asc']], // Ordenar por la columna de nombre (índice 1) de forma ascendente
                responsive: {
                    details: {
                        type: 'column',      // Indica que la columna será la encargada de mostrar/ocultar detalles
                        target: 0           // La primera columna (índice 0) es donde estará el ícono "+"
                    }
                },
                // Definición de columnas
                columns: [
                    // Columna "control" para el ícono "+"
                    {
                        className: 'control', // Clave para que DataTables sepa que aquí va el "+"
                        orderable: false,
                        searchable: false,
                        data:  null,
                        defaultContent: ''
                    },
                    { 
                        data: 'id', 
                        name: 'id',
                        searchable: true,
                        orderable: true
                    },
                    { 
                        data: 'name', 
                        name: 'name',
                        searchable: true,
                        orderable: true
                    },
                    { 
                        data: 'token', 
                        name: 'token',
                        searchable: true,
                        orderable: true
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // OPCIONAL: si quisieras definir algunas columnas que se oculten automáticamente,
            // puedes usar columnDefs con responsivePriority, por ejemplo:
            /*
            columnDefs: [
                { responsivePriority: 1, targets: 1 }, // la columna ID siempre visible
                { responsivePriority: 2, targets: 2 }, // la columna Name con prioridad alta
                { responsivePriority: 3, targets: 3 },
            ],
            */
        });
    </script>
@endpush
