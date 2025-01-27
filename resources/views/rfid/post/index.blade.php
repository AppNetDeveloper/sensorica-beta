@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Relaciones Productos y RFID')

{{-- Migas de pan (opcional) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">Dashboard</a>
        </li>
        <li class="breadcrumb-item active">RFID Post</li>
    </ul>
    <ul class="breadcrumb">
    </ul>
@endsection

@section('content')
    <div class="card border-0 shadow">
        <div class="card-header">
        </div>
        <div class="card-body">

            {{-- Tabla --}}
            <table id="relationsTable" class="table table-striped w-100">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>RFID</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('style')
    {{-- CSS de DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
@endpush

@push('scripts')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- DataTables JS + Botones de exportación --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ENDPOINTS (ajusta según tu back-end o tus rutas API)
        const relationsApiUrl = '/api/product-list-rfids';
        const productsApiUrl  = '/api/product-lists/list-all';
        const rfidsApiUrl     = '/api/rfid-readings';

        let productListOptions = '';
        let rfidOptions = '';
        let table; // Referencia a DataTable

        // Cargar lista de productos y lista de RFID para usarlos en los selects
        function loadSelectOptions() {
            // Productos
            $.get(productsApiUrl)
                .done((data) => {
                    productListOptions = data.map(product =>
                        `<option value="${product.id}">${product.name}</option>`
                    ).join('');
                })
                .fail((xhr) => {
                    Swal.fire('Error', 'No se pudieron cargar productos.', 'error');
                });

            // RFID
            $.get(rfidsApiUrl)
                .done((data) => {
                    rfidOptions = data.map(rfid =>
                        `<option value="${rfid.id}">${rfid.epc}</option>`
                    ).join('');
                })
                .fail((xhr) => {
                    Swal.fire('Error', 'No se pudieron cargar RFIDs.', 'error');
                });
        }

        $(document).ready(function() {
            // Cargar datos iniciales para los selects
            loadSelectOptions();

            // Inicializar DataTable
            table = $('#relationsTable').DataTable({
                ajax: {
                    url: relationsApiUrl,
                    dataSrc: '',
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar datos',
                            text: xhr.responseJSON?.error || 'Error desconocido'
                        });
                    },
                },
                columns: [
                    { data: 'id' },
                    { data: 'product_list.name', defaultContent: 'Sin asignar' },
                    { data: 'rfid_reading.epc', defaultContent: 'Sin asignar' },
                    {
                        data: 'created_at',
                        render: function(data) {
                            const date = new Date(data);
                            return date.toLocaleString('es-ES');
                        }
                    },
                    {
                        data: 'updated_at',
                        render: function(data) {
                            if (!data) return 'En curso';
                            const date = new Date(data);
                            return date.toLocaleString('es-ES');
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `
                                <button class="btn btn-sm btn-secondary edit-btn"
                                    data-id="${data.id}"
                                    data-product_list_id="${data.product_list_id}"
                                    data-rfid_reading_id="${data.rfid_reading_id}">
                                    Editar
                                </button>
                                <button class="btn btn-sm btn-danger delete-btn" 
                                    data-id="${data.id}">
                                    Eliminar
                                </button>
                            `;
                        }
                    }
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Añadir Relación',
                        className: 'btn btn-primary',
                        action: function(e, dt, node, config) {
                            // Lógica para mostrar SweetAlert y hacer POST
                            Swal.fire({
                                title: 'Añadir Relación',
                                html: `
                                    <label for="productListId">Producto:</label>
                                    <select id="productListId" class="swal2-input">${productListOptions}</select>
                                    <label for="rfidReadingId">RFID:</label>
                                    <select id="rfidReadingId" class="swal2-input">${rfidOptions}</select>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'Añadir',
                                preConfirm: () => {
                                    const product_list_id = $('#productListId').val();
                                    const rfid_reading_id = $('#rfidReadingId').val();
                                    if (!product_list_id || !rfid_reading_id) {
                                        Swal.showValidationMessage('Producto y RFID son obligatorios.');
                                        return false;
                                    }
                                    return {
                                        client_id: parseInt(product_list_id),
                                        rfid_reading_id: parseInt(rfid_reading_id),
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: relationsApiUrl,
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify(result.value),
                                        success: function() {
                                            Swal.fire('Éxito', 'Relación añadida.', 'success');
                                            table.ajax.reload();
                                        },
                                        error: function() {
                                            Swal.fire('Error', 'No se pudo añadir la relación.', 'error');
                                        }
                                    });
                                }
                            });
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Exportar a Excel',
                        className: 'btn btn-success',
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4],
                        },
                    }
                ],
                order: [[0, 'desc']],
                responsive: true
            });

            // Botón "Añadir Relación"
            $('#addRelation').on('click', function() {
                Swal.fire({
                    title: 'Añadir Relación',
                    html: `
                        <label for="productListId">Producto:</label>
                        <select id="productListId" class="swal2-input">
                            ${productListOptions}
                        </select>
                        <label for="rfidReadingId">RFID:</label>
                        <select id="rfidReadingId" class="swal2-input">
                            ${rfidOptions}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Añadir',
                    preConfirm: () => {
                        const product_list_id = $('#productListId').val();
                        const rfid_reading_id = $('#rfidReadingId').val();
                        if (!product_list_id || !rfid_reading_id) {
                            Swal.showValidationMessage('Producto y RFID son obligatorios.');
                            return false;
                        }
                        return {
                            client_id: parseInt(product_list_id),
                            rfid_reading_id: parseInt(rfid_reading_id),
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: relationsApiUrl,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(result.value),
                            success: function() {
                                Swal.fire('Éxito', 'Relación añadida.', 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', 'No se pudo añadir la relación.', 'error');
                            }
                        });
                    }
                });
            });

            // Eliminar
            $('#relationsTable tbody').on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${relationsApiUrl}/${id}`,
                            method: 'DELETE',
                            success: function() {
                                Swal.fire('Éxito', 'Relación eliminada.', 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', 'No se pudo eliminar la relación.', 'error');
                            }
                        });
                    }
                });
            });

            // Editar
            $('#relationsTable tbody').on('click', '.edit-btn', function() {
                const currentId = $(this).data('id');
                const currentProductListId = $(this).data('product_list_id');
                const currentRfidReadingId = $(this).data('rfid_reading_id');

                Swal.fire({
                    title: 'Editar Relación',
                    html: `
                        <input id="relationId" class="swal2-input" value="${currentId}" readonly>
                        <label for="productListId">Producto:</label>
                        <select id="productListId" class="swal2-input">${productListOptions}</select>
                        <label for="rfidReadingId">RFID:</label>
                        <select id="rfidReadingId" class="swal2-input">${rfidOptions}</select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    didOpen: () => {
                        $('#productListId').val(currentProductListId);
                        $('#rfidReadingId').val(currentRfidReadingId);
                    },
                    preConfirm: () => {
                        const id = $('#relationId').val();
                        const product_list_id = $('#productListId').val();
                        const rfid_reading_id = $('#rfidReadingId').val();
                        if (!id || !product_list_id || !rfid_reading_id) {
                            Swal.showValidationMessage('Todos los campos son obligatorios.');
                            return false;
                        }
                        return {
                            id: parseInt(id),
                            product_list_id: parseInt(product_list_id),
                            rfid_reading_id: parseInt(rfid_reading_id),
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${relationsApiUrl}/${result.value.id}`,
                            method: 'PUT',
                            contentType: 'application/json',
                            data: JSON.stringify(result.value),
                            success: function() {
                                Swal.fire('Éxito', 'Relación actualizada.', 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire('Error', 'No se pudo actualizar la relación.', 'error');
                            }
                        });
                    }
                });
            });

            // Recarga automática cada 10 segundos (opcional)
            setInterval(() => {
                table.ajax.reload(null, false);
            }, 10000);
        });
    </script>
@endpush
