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
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="filter-container">
                            <select id="order-status-filter" class="form-select form-select-sm">
                                <option value="all" selected>@lang('Todo')</option>
                                <option value="finished">@lang('Solo finalizados')</option>
                                <option value="in-progress">@lang('En curso')</option>
                            </select>
                        </div>
                        <div>
                            @can('original-order-create')
                            <a href="#" id="import-orders-btn" class="btn btn-outline-success btn-sm me-2">
                                <i class="fas fa-sync-alt"></i> @lang('Importar ahora')
                            </a>
                            <a href="#" id="create-cards-btn" class="btn btn-outline-info btn-sm me-2">
                                <i class="fas fa-id-card"></i> @lang('Crear Tarjetas')
                            </a>
                            <a href="{{ route('customers.original-orders.create', $customer->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus"></i> @lang('New Order')
                            </a>
                            @endcan
                        </div>
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
                                <span class="badge bg-primary me-1">@lang('Pedido Iniciado')</span> @lang('Al menos un proceso iniciado o finalizado')
                            </div>
                            <div>
                                <span class="badge bg-info me-1">@lang('Asignado a máquina')</span> @lang('Al menos un proceso asignado')
                            </div>
                            <div>
                                <span class="badge bg-secondary me-1">@lang('Pendiente Planificar')</span> @lang('Con todos los procesos pendientes de asignar')
                            </div>
                            <div>
                                <span class="badge bg-danger me-1">@lang('Pendiente Crear')</span> @lang('Sin procesos creados (falta de stock)')
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
        .badge.bg-cyan {
            background-color: #17a2b8;
            color: #fff;
        }
        /* Texto oscuro solo en la leyenda de estados de pedidos */
        .d-flex.flex-wrap.gap-3 .badge.bg-cyan {
            color: #212529;
        }
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
        
        /* Estilos para alinear la paginación a la derecha */
        .dataTables_paginate {
            float: right !important;
            width: 100%;
            text-align: right !important;
        }
        
        /* Añadir espacio entre la información y la paginación */
        .dataTables_info {
            padding-top: 8px;
            margin-bottom: 10px;
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
                // Destruir tooltips existentes antes de inicializar nuevos
                $('[data-bs-toggle="tooltip"]').tooltip('dispose');
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
                    ajax: {
                        url: '{{ route("customers.original-orders.index", $customer) }}',
                        data: function(d) {
                            // Añadir el parámetro de filtro de estado
                            d.status_filter = window.orderStatusFilter || 'all';
                        }
                    },
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
                    dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 text-end"p>>',
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
                            // Destruir tooltips existentes antes de inicializar nuevos
                            $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                            $('[data-bs-toggle="tooltip"]').tooltip();
                        }, 100);
                    }
                });
            }
            
            // Inicializar DataTable
            initDataTable();
            initTooltips();
            
            // Filtro por estado de pedido (server-side)
            $('#order-status-filter').on('change', function() {
                // Obtener el valor seleccionado
                const filterValue = $(this).val();
                
                // Almacenar el valor del filtro en una variable global para que DataTables lo use
                window.orderStatusFilter = filterValue;
                
                // Volver a cargar la tabla para aplicar el filtro
                dataTable.ajax.reload();
            });
            
            // Inicializar el toast de actualización
            const updateTimeToast = new bootstrap.Toast(document.getElementById('update-time-toast'), {
                autohide: true,
                delay: 2000  // Solo mostrar por 2 segundos
            });
            
            // Función para actualizar la tabla manteniendo el estado actual
            function refreshTableKeepingState() {
                // Destruir todos los tooltips antes de actualizar la tabla
                $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                
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
            
            // Función reutilizable para mostrar notificaciones (toasts)
            function showToast(message, type = 'info') {
                const toastEl = document.getElementById('update-time-toast');
                const toast = new bootstrap.Toast(toastEl);
                const toastBody = $('#update-time-toast .toast-body');
                const toastContainer = $('#update-time-toast');

                // Resetear clases de color y añadir la nueva
                toastContainer.removeClass('bg-info bg-success bg-danger');
                let icon = '';
                let colorClass = '';

                switch (type) {
                    case 'success':
                        icon = '<i class="fas fa-check-circle me-2"></i>';
                        colorClass = 'bg-success';
                        break;
                    case 'error':
                        icon = '<i class="fas fa-times-circle me-2"></i>';
                        colorClass = 'bg-danger';
                        break;
                    default: // info
                        icon = '<i class="fas fa-info-circle me-2"></i>';
                        colorClass = 'bg-info';
                        break;
                }
                
                toastContainer.addClass(colorClass);
                toastEl.querySelector('.toast-body').innerHTML = icon + message;
                toast.show();
            }

            // Configurar actualización automática cada minuto
            setInterval(refreshTableKeepingState, 60000); // 60000 ms = 1 minuto

            // Manejar clic en el botón de importar
            $('#import-orders-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const originalHtml = btn.html();

                // Deshabilitar botón y mostrar estado de carga
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Importando...")');

                $.ajax({
                    url: '{{ route("customers.original-orders.import", $customer) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(refreshTableKeepingState, 2000);
                        } else {
                            // Esto puede ocurrir si el servidor responde con 200 OK pero success: false
                            showToast(response.message || '@lang("Ocurrió un error durante la importación.")', 'error');
                        }
                    },
                    error: function(xhr) {
                        // Esto se dispara para respuestas de error HTTP (ej. 429, 500)
                        console.error('Error en la petición de importación:', xhr);
                        const errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '@lang("No se pudo iniciar la importación.")';
                        showToast(errorMsg, 'error');
                    },
                    complete: function() {
                        // Restaurar botón
                        btn.prop('disabled', false).html(originalHtml);
                        // Restaurar el toast de actualización a su estado original después de un tiempo
                        setTimeout(() => {
                            toastContainer = $('#update-time-toast');
                            toastContainer.removeClass('bg-success bg-danger').addClass('bg-info');
                            toastContainer.find('.toast-body').html('<i class="fas fa-sync-alt me-2 fa-spin"></i> @lang("Actualizando...")');
                        }, 5000);
                    }
                });
            });
            
            // Manejar clic en el botón de crear tarjetas
            $('#create-cards-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const originalHtml = btn.html();

                // Deshabilitar botón y mostrar estado de carga
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Creando tarjetas...")');

                $.ajax({
                    url: '{{ route("customers.original-orders.create-cards", $customer) }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(refreshTableKeepingState, 2000);
                        } else {
                            showToast(response.message || '@lang("Ocurrió un error al crear las tarjetas.")', 'error');
                        }
                    },
                    error: function(xhr) {
                        console.error('Error en la petición de crear tarjetas:', xhr);
                        const errorMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '@lang("No se pudieron crear las tarjetas.")';
                        showToast(errorMsg, 'error');
                    },
                    complete: function() {
                        // Restaurar botón
                        btn.prop('disabled', false).html(originalHtml);
                        // Restaurar el toast de actualización a su estado original después de un tiempo
                        setTimeout(() => {
                            toastContainer = $('#update-time-toast');
                            toastContainer.removeClass('bg-success bg-danger').addClass('bg-info');
                            toastContainer.find('.toast-body').html('<i class="fas fa-sync-alt me-2 fa-spin"></i> @lang("Actualizando...")');
                        }, 5000);
                    }
                });
            });
        });
    </script>
@endpush
