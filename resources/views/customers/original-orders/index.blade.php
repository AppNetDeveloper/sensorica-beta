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
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-end align-items-center">
                        @can('original-order-create')
                        <a href="{{ route('customers.original-orders.create', $customer->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus"></i> @lang('New Order')
                        </a>
                        @endcan
                    </div>
                </div>
                
                <!-- Notificación flotante simple de actualización -->
                <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
                    <div id="update-time-toast" class="toast align-items-center text-white bg-info border-0" role="alert" aria-live="polite" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">
                                <i class="fas fa-sync-alt me-2 fa-spin"></i> @lang('Actualizando...')
                            </div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
                        <table id="original-orders-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('ORDER ID')</th>
                                    <th class="text-uppercase">@lang('CLIENT NUMBER')</th>
                                    <th class="text-uppercase">@lang('PROCESSES')</th>
                                    <th class="text-uppercase">@lang('ORIGINAL ORDER STATUS')</th>
                                    <th class="text-uppercase">@lang('CREATED AT')</th>
                                    <th class="text-uppercase">@lang('ACTIONS')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán dinámicamente desde el servidor -->
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Leyenda de colores para los procesos -->
                    <div class="card-footer bg-light p-3 mt-3">
                        <h5>@lang('Leyenda de estados de procesos')</h5>
                        <p class="text-muted mb-2">@lang('Formato de tarjeta: Código de proceso (número de grupo)')</p>
                        <div class="d-flex flex-wrap gap-3">
                            <div>
                                <span class="badge bg-success me-1">COR (1)</span> @lang('Proceso finalizado')
                            </div>
                            <div>
                                <span class="badge bg-primary me-1">COR (2)</span> @lang('En fabricación')
                            </div>
                            <div>
                                <span class="badge bg-info me-1">COR (3)</span> @lang('Asignado a máquina')
                            </div>
                            <div>
                                <span class="badge bg-danger me-1">COR (4)</span> @lang('Con incidencia')
                            </div>

                            <div>
                                <span class="badge bg-secondary me-1">COR (6)</span> @lang('Sin asignar')
                            </div>
                        </div>
                        
                        <h5 class="mt-3">@lang('Leyenda de estados de pedidos')</h5>
                        <div class="d-flex flex-wrap gap-3">
                            <div>
                                <span class="badge bg-success me-1">@lang('Fecha')</span> @lang('Pedido finalizado')
                            </div>
                            <div>
                                <span class="badge bg-primary me-1">@lang('Pedido Iniciado')</span> @lang('Al menos un proceso asignado a máquina')
                            </div>
                            <div>
                                <span class="badge bg-info me-1">@lang('Pendiente de iniciar')</span> @lang('Con órdenes asignadas pero no iniciadas')
                            </div>
                            <div>
                                <span class="badge bg-secondary me-1">@lang('Pendiente de asignación')</span> @lang('Sin órdenes de producción asignadas')
                            </div>
                        </div>
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
        #original-orders-table_wrapper .dt-buttons {
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
        #original-orders-table_wrapper {
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            
            // Inicializar tooltips
            function initTooltips() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
            
            // Variable para almacenar la instancia de DataTable
            let dataTable;
            
            // Función para inicializar DataTables
            function initDataTable() {
                dataTable = $('#original-orders-table').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    ajax: '{{ route("customers.original-orders.index", $customer) }}',
                    columns: [
                        {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                        {data: 'order_id', name: 'order_id'},
                        {data: 'client_number', name: 'client_number'},
                        {data: 'processes', name: 'processes', orderable: false, searchable: false},
                        {data: 'finished_at', name: 'finished_at'},
                        {data: 'created_at', name: 'created_at'},
                        {data: 'actions', name: 'actions', orderable: false, searchable: false},
                    ],
                    order: [[1, 'desc']],
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
                    buttons: [
                        {
                            extend: 'pageLength',
                            className: 'btn btn-secondary'
                        }
                    ],
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    pageLength: 10,
                    drawCallback: function() {
                        // Inicializar tooltips para los badges y botones
                        setTimeout(function() {
                            $('[data-bs-toggle="tooltip"]').tooltip();
                        }, 100);
                    }
                });
            }
            
            // Inicializar DataTable
            initDataTable();
            initTooltips();
            
            // Inicializar el toast de actualización
            const updateTimeToast = new bootstrap.Toast(document.getElementById('update-time-toast'), {
                autohide: true,
                delay: 2000  // Solo mostrar por 2 segundos
            });
            
            // Función para actualizar la tabla manteniendo el estado actual
            function refreshTableKeepingState() {
                // Guardar el estado actual
                const currentPage = dataTable.page();
                const currentSearch = dataTable.search();
                const currentOrder = dataTable.order();
                const currentLength = dataTable.page.len();
                
                // Recargar los datos
                dataTable.ajax.reload(function() {
                    // Restaurar el estado después de recargar
                    dataTable.page(currentPage).draw('page');
                    if (currentSearch) {
                        dataTable.search(currentSearch).draw('page');
                    }
                    dataTable.order(currentOrder).draw('page');
                    dataTable.page.len(currentLength).draw('page');
                    
                    // Mostrar el toast de actualización
                    updateTimeToast.show();
                }, false); // false significa que no se resetea la paginación
            }
            
            // Configurar actualización automática cada minuto
            setInterval(refreshTableKeepingState, 60000); // 60000 ms = 1 minuto
        });
    </script>
@endpush
