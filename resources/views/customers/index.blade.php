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
            <div class="mb-3 d-flex justify-content-between">
                <div>
                    {{-- Botón para añadir clientes con un icono --}}
                    @can('productionline-create')
                        <a href="{{ route('customers.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> {{ __('Add Customers') }}
                        </a>
                    @endcan
                </div>
                <div>
                    {{-- Botón para eliminar clientes seleccionados --}}
                    @can('productionline-delete')
                        <button id="bulk-delete" class="btn btn-danger d-none">
                            <i class="fas fa-trash me-1"></i> {{ __('Delete Selected') }}
                        </button>
                    @endcan
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    {{-- *** CAMBIO: Añadido margen superior mt-4 aquí *** --}}
                    <div class="table-responsive mt-4">
                        <div class="container-fluid">
                            {{-- Añade la clase 'dt-responsive' y 'nowrap' para el correcto funcionamiento de DataTables Responsive --}}
                            <table class="table table-bordered data-table dt-responsive nowrap" style="width:100%" data-buttons-container=".dt-buttons">
                                <thead>
                                    <tr>
                                        @can('productionline-delete')
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="select-all">
                                            </div>
                                        </th>
                                        @endcan
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
    {{-- DataTables Buttons --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function () {
            // Inicializa DataTables con configuración para botones
            var table = $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('customers.getCustomers') }}",
                columns: [
                    @can('productionline-delete')
                    {
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false,
                        width: '5%',
                        render: function (data, type, row) {
                            return '<div class="form-check"><input type="checkbox" class="form-check-input row-checkbox" value="' + row.id + '"></div>';
                        }
                    },
                    @endcan
                    { data: 'id', name: 'id', width: '5%' },
                    { data: 'name', name: 'name', width: '20%' },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        width: '15%',
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
                        width: '15%'
                    },
                    { 
                        data: 'action_buttons', 
                        name: 'action_buttons', 
                        orderable: false, 
                        searchable: false,
                        visible: false
                    }
                ],
                columnDefs: [
                    @can('productionline-delete')
                    { responsivePriority: 1, targets: 2 }, // Prioridad 1 para el Nombre
                    { responsivePriority: 2, targets: -1 }, // Prioridad 2 para la última columna (Acciones)
                    { responsivePriority: 3, targets: 1 }, // Prioridad 3 para ID
                    { responsivePriority: 4, targets: 3 }, // Prioridad 4 para Fecha
                    @else
                    { responsivePriority: 1, targets: 1 }, // Prioridad 1 para el Nombre
                    { responsivePriority: 2, targets: -1 }, // Prioridad 2 para la última columna (Acciones)
                    { responsivePriority: 3, targets: 0 }, // Prioridad 3 para ID
                    { responsivePriority: 4, targets: 2 }, // Prioridad 4 para Fecha
                    @endcan
                    {
                        targets: -1, // Última columna (Acción)
                        render: function (data, type, full, meta) {
                            // Devuelve el HTML que viene del servidor para las acciones
                            return '<div class="d-flex flex-wrap justify-content-center gap-1">' + data + '</div>';
                        }
                    }
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" // URL para español
                },
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
            
            // Coloca los botones en el contenedor especificado
            table.buttons().container().appendTo('.dt-buttons');
            
            // Manejo de selección de checkboxes
            $('#select-all').on('click', function() {
                $('.row-checkbox').prop('checked', this.checked);
                updateBulkDeleteButton();
            });
            
            // Actualizar el estado del botón de eliminación masiva cuando se hace clic en un checkbox individual
            $(document).on('click', '.row-checkbox', function() {
                if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
                    $('#select-all').prop('checked', true);
                } else {
                    $('#select-all').prop('checked', false);
                }
                updateBulkDeleteButton();
            });
            
            // Función para actualizar el botón de eliminación masiva
            function updateBulkDeleteButton() {
                if ($('.row-checkbox:checked').length > 0) {
                    $('#bulk-delete').removeClass('d-none');
                } else {
                    $('#bulk-delete').addClass('d-none');
                }
            }
            
            // Manejo del botón de eliminación masiva
            $('#bulk-delete').on('click', function() {
                var selectedIds = [];
                $('.row-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                
                if (selectedIds.length === 0) {
                    Swal.fire({
                        title: '{{ __('Error') }}',
                        text: '{{ __('Please select at least one item to delete') }}',
                        icon: 'error'
                    });
                    return;
                }
                
                Swal.fire({
                    title: '{{ __('Are you sure?') }}',
                    text: '{{ __('You will not be able to recover these items!') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __('Yes, delete them!') }}',
                    cancelButtonText: '{{ __('No, cancel!') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('customers.bulk-delete') }}',
                            type: 'POST',
                            data: {
                                ids: selectedIds,
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire(
                                    '{{ __('Deleted!') }}',
                                    response.message,
                                    'success'
                                );
                                table.ajax.reload();
                                $('#select-all').prop('checked', false);
                                $('#bulk-delete').addClass('d-none');
                            },
                            error: function(xhr) {
                                Swal.fire(
                                    '{{ __('Error!') }}',
                                    xhr.responseJSON.message || '{{ __('Something went wrong!') }}',
                                    'error'
                                );
                            }
                        });
                    }
                });
            });
            
            // Funcionalidad para expandir/contraer filas de acciones
            $(document).on('click', '.toggle-actions', function() {
                var customerId = $(this).data('customer-id');
                var $button = $(this);
                var $icon = $button.find('i');
                var $row = $button.closest('tr');
                var $nextRow = $row.next('.action-row');
                
                // Si ya existe una fila expandida, la eliminamos
                if ($nextRow.length > 0) {
                    $nextRow.remove();
                    $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    $button.attr('title', '{{ __("Show Actions") }}');
                    return;
                }
                
                // Obtener los datos de la fila actual
                var rowData = table.row($row).data();
                
                // Crear nueva fila con los botones de acción
                var $newRow = $('<tr class="action-row"><td colspan="' + table.columns().count() + '">' + rowData.action_buttons + '</td></tr>');
                
                // Insertar la nueva fila después de la fila actual
                $row.after($newRow);
                
                // Mostrar la fila de botones con animación
                $newRow.find('.action-buttons-row').slideDown(300);
                
                // Cambiar el icono y tooltip
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                $button.attr('title', '{{ __("Hide Actions") }}');
            });
            
            // Cerrar filas expandidas cuando se recarga la tabla
            table.on('draw', function() {
                $('.action-row').remove();
                $('.toggle-actions i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
                $('.toggle-actions').attr('title', '{{ __("Show Actions") }}');
            });
        });
    </script>
    
    <style>
        .btn-group-section {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        .btn-group-label {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        
        .action-buttons-row {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 5px;
            border: 1px solid #dee2e6;
        }
        
        .action-row td {
            background-color: #ffffff;
            border-top: none !important;
            padding: 0 !important;
        }
        
        .toggle-actions {
            transition: all 0.3s ease;
        }
        
        .toggle-actions:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
@endpush
