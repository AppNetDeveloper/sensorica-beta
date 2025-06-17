@extends('layouts.admin')
@section('title', __('Customers'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Customers') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="mb-3">
                {{-- Botón para añadir clientes con un icono --}}
                <a href="{{ route('customers.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> {{ __('Add Customers') }}
                </a>
            </div>
            <div class="card">
                <div class="card-body">
                    {{-- *** CAMBIO: Añadido margen superior mt-4 aquí *** --}}
                    <div class="table-responsive mt-4">
                        <div class="container-fluid">
                            {{-- Añade la clase 'dt-responsive' y 'nowrap' para el correcto funcionamiento de DataTables Responsive --}}
                            <table class="table table-bordered data-table dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Created At') }}</th>
                                        <th>{{ __('Action') }}</th> {{-- Asegúrate que esta columna sea la última --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- El cuerpo se llena vía AJAX --}}
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
    {{-- DataTables Responsive CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    {{-- Font Awesome para iconos (Asegúrate de tenerlo cargado en tu layout principal o inclúyelo aquí) --}}
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" /> --}}
@endpush

@push('scripts')
    {{-- jQuery (Asegúrate que esté cargado antes de DataTables) --}}
    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> --}}
    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    {{-- DataTables Responsive JS --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    {{-- Moment.js para formatear fechas (si aún lo necesitas) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/es.js"></script> {{-- Locale español --}}

    <script type="text/javascript">
        $(function () {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Configura Moment.js al español
            moment.locale('es');

            var table = $('.data-table').DataTable({
                processing: true, // Muestra un indicador de procesamiento
                serverSide: true, // Habilita el procesamiento del lado del servidor
                responsive: true, // Habilita la funcionalidad responsive
                ajax: "/customers/getCustomers", // URL relativa para evitar Mixed Content
                columns: [
                    { 
                        data: 'id', 
                        name: 'id',
                        className: 'text-center',
                        width: '80px'
                    },
                    { 
                        data: 'name', 
                        name: 'name',
                        className: 'fw-semibold'
                    },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        className: 'text-center',
                        width: '150px',
                        render: function(data) {
                            return data ? moment(data).format('LL') : '-';
                        }
                    },
                    { 
                        data: 'action', 
                        name: 'action', 
                        orderable: false, 
                        searchable: false,
                        className: 'text-center',
                        width: '150px'
                    }
                ],
                columnDefs: [
                    { responsivePriority: 1, targets: 1 }, // Prioridad 1 para el Nombre
                    { responsivePriority: 2, targets: -1 }, // Prioridad 2 para la última columna (Acciones)
                    {
                        targets: -1, // Última columna (Acción)
                        render: function (data, type, full, meta) {
                            // Devuelve el HTML que viene del servidor para las acciones
                            return data;
                        }
                    },
                    { width: '10%', targets: 0 }, // Ancho para ID
                    { width: '40%', targets: 1 }, // Ancho para Nombre
                    { width: '20%', targets: 2 }  // Ancho para Fecha de creación
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" // URL para español
                }
            });
        });
    </script>
@endpush
