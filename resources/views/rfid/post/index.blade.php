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
@endsection

@section('content')
    <div class="card border-0 shadow">
        <div class="card-header">
 
        </div>
        <div class="card-body">
            <table id="relationsTable" class="display table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>RFID</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Modbus</th>
                        <th>Sensor</th>
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

    {{-- DataTables + Botones de exportación --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Ajusta a tus rutas API
        const relationsApiUrl = '/api/product-list-selecteds'; // CRUD principal
        const productsApiUrl  = '/api/product-lists/list-all';
        const rfidsApiUrl     = '/api/rfid-readings';
        const modbusesApiUrl  = '/api/product-list-selecteds/modbuses';
        const sensorsApiUrl   = '/api/product-list-selecteds/sensors';

        let productListOptions = '';
        let rfidOptions = '';
        let modbusOptions = '';
        let sensorOptions = '';
        let table; // referencia a la DataTable

        // Cargar opciones de Producto, RFID, Modbus y Sensor
        function loadSelectOptions() {
            // Productos
            $.get(productsApiUrl)
                .done((data) => {
                    productListOptions = data.map(product =>
                        `<option value="${product.id}">${product.name}</option>`
                    ).join('');
                })
                .fail(() => {
                    Swal.fire('Error', 'No se pudieron cargar productos.', 'error');
                });

            // RFID
            $.get(rfidsApiUrl)
                .done((data) => {
                    rfidOptions = data.map(rfid =>
                        `<option value="${rfid.id}">${rfid.name}</option>`
                    ).join('');
                })
                .fail(() => {
                    Swal.fire('Error', 'No se pudieron cargar RFIDs.', 'error');
                });

            // Modbuses
            $.get(modbusesApiUrl)
                .done((data) => {
                    modbusOptions = data.map(mb =>
                        `<option value="${mb.id}">${mb.name}</option>`
                    ).join('');
                })
                .fail(() => {
                    Swal.fire('Error', 'No se pudieron cargar Modbuses.', 'error');
                });

            // Sensors
            $.get(sensorsApiUrl)
                .done((data) => {
                    sensorOptions = data.map(sn =>
                        `<option value="${sn.id}">${sn.name}</option>`
                    ).join('');
                })
                .fail(() => {
                    Swal.fire('Error', 'No se pudieron cargar Sensores.', 'error');
                });
        }

        $(document).ready(function() {
            // Cargar listas de opciones
            loadSelectOptions();

            // Inicializar la DataTable
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
                    }
                },
                columns: [
                    { data: 'id' },
                    { 
                        data: 'product_list.name',
                        defaultContent: 'Sin asignar'
                    },
                    { 
                        data: 'rfid_reading',
                        render: function(data) {
                            if (data && data.name && data.rfid_color && data.rfid_color.name) {
                                return `${data.name} - ${data.rfid_color.name}`;
                            }
                            return 'Sin asignar';
                        },
                        defaultContent: 'Sin asignar'
                    },
                    { // Fecha inicio (created_at)
                        data: 'created_at',
                        render: function(data) {
                            const date = new Date(data);
                            return date.toLocaleString('es-ES');
                        }
                    },
                    { // Fecha fin (updated_at)
                        data: 'updated_at',
                        render: function(data) {
                            if (!data) return 'En curso';
                            const date = new Date(data);
                            return date.toLocaleString('es-ES');
                        }
                    },
                    { 
                        data: 'modbus.name',
                        defaultContent: 'N/A',
                        render: function(data, type, row) {
                            return row.modbus ? data : 'N/A';
                        }
                    },
                    { 
                        data: 'sensor.name',
                        defaultContent: 'N/A',
                        render: function(data, type, row) {
                            return row.sensor ? data : 'N/A';
                        }
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `
                                <button class="btn btn-sm btn-secondary edit-btn"
                                    data-id="${data.id}"
                                    data-product_list_id="${data.product_list_id || ''}"
                                    data-rfid_reading_id="${data.rfid_reading_id || ''}"
                                    data-modbus_id="${data.modbus_id || ''}"
                                    data-sensor_id="${data.sensor_id || ''}">
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
                        // Formulario de creación con SweetAlert
                        Swal.fire({
                            title: 'Añadir Relación',
                            width: '800px',
                            padding: '2em',
                            html: `
                                <select id="productListId" class="swal2-input" style="
                                    width: 550px; 
                                    background: transparent; 
                                    color: black; 
                                    text-shadow: 1px 1px 2px white; 
                                    border: 1px solid #ccc;
                                    padding: 0.5em;
                                    border-radius: 4px;
                                ">
                                <option value="" disabled selected>-- Seleccione Producto --</option>
                                ${productListOptions}</select>
                                <select id="rfidReadingId" class="swal2-input" style="
                                    width: 550px; 
                                    background: transparent; 
                                    color: black; 
                                    text-shadow: 1px 1px 2px white; 
                                    border: 1px solid #ccc;
                                    padding: 0.5em;
                                    border-radius: 4px;
                                ">
                                <option value="" disabled selected>-- Seleccione RFID --</option>
                                ${rfidOptions}
                                </select>
                                <div id="modifyAllColor" style="display:none;">
                                    <label>
                                        <input type="checkbox" id="modifyAll" style="margin-right: 10px;">
                                        Modificar para todas las tarjetas del mismo color
                                    </label>
                                </div>
                                <select id="modbusId" class="swal2-input" style="
                                    width: 550px; 
                                    background: transparent; 
                                    color: black; 
                                    text-shadow: 1px 1px 2px white; 
                                    border: 1px solid #ccc;
                                    padding: 0.5em;
                                    border-radius: 4px;
                                ">
                                    <option value="" disabled selected>-- Seleccione Báscula --</option>
                                    ${modbusOptions}
                                </select>

                                <select id="sensorId" class="swal2-input" style="
                                    width: 550px; 
                                    background: transparent; 
                                    color: black; 
                                    text-shadow: 1px 1px 2px white; 
                                    border: 1px solid #ccc;
                                    padding: 0.5em;
                                    border-radius: 4px;
                                ">
                                    <option value="" disabled selected>-- Seleccione Sensor --</option>
                                    ${sensorOptions}
                                </select>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Añadir',
                            didOpen: () => {
                                $('#rfidReadingId').on('change', function() {
                                    if ($(this).val()) {
                                        $('#modifyAllColor').show();
                                    } else {
                                        $('#modifyAllColor').hide();
                                        $('#modifyAll').prop('checked', false);
                                    }
                                });
                            },
                            preConfirm: () => {
                                const product_list_id  = $('#productListId').val();
                                const rfid_reading_id  = $('#rfidReadingId').val();
                                const modifyAll        = $('#modifyAll').is(':checked');
                                const modbus_id        = $('#modbusId').val();
                                const sensor_id        = $('#sensorId').val();

                                if (!product_list_id || !rfid_reading_id) {
                                    Swal.showValidationMessage('Producto y RFID son obligatorios.');
                                    return false;
                                }
                                return {
                                    client_id: parseInt(product_list_id),
                                    rfid_reading_id: parseInt(rfid_reading_id),
                                    modify_all: modifyAll,
                                    modbus_id: modbus_id ? parseInt(modbus_id) : null,
                                    sensor_id: sensor_id ? parseInt(sensor_id) : null
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
                            columns: [0,1,2,3,4,5,6],
                        },
                    },
                ],
                order: [[0, 'desc']],
                responsive: true
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
                            error: function() {
                                Swal.fire('Error', 'No se pudo eliminar la relación.', 'error');
                            }
                        });
                    }
                });
            });

            // Editar
            $('#relationsTable tbody').on('click', '.edit-btn', function() {
                const currentId            = $(this).data('id');
                const currentProductListId = $(this).data('product_list_id');
                const currentRfidReadingId = $(this).data('rfid_reading_id');
                const currentModbusId      = $(this).data('modbus_id');
                const currentSensorId      = $(this).data('sensor_id');

                Swal.fire({
                    title: 'Editar Relación',
                    html: `
                        <input id="relationId" class="swal2-input" value="${currentId}" readonly>

                        <label for="productListId">Producto:</label>
                        <select id="productListId" class="swal2-input">${productListOptions}</select>

                        <label for="rfidReadingId">RFID:</label>
                        <select id="rfidReadingId" class="swal2-input">${rfidOptions}</select>

                        <label for="modbusId">Modbus:</label>
                        <select id="modbusId" class="swal2-input">
                            <option value="" disabled>-- Seleccione --</option>
                            ${modbusOptions}
                        </select>

                        <label for="sensorId">Sensor:</label>
                        <select id="sensorId" class="swal2-input">
                            <option value="" disabled>-- Seleccione --</option>
                            ${sensorOptions}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    didOpen: () => {
                        // Setear valores preseleccionados
                        $('#productListId').val(currentProductListId);
                        $('#rfidReadingId').val(currentRfidReadingId);
                        $('#modbusId').val(currentModbusId);
                        $('#sensorId').val(currentSensorId);
                    },
                    preConfirm: () => {
                        const id              = $('#relationId').val();
                        const product_list_id = $('#productListId').val();
                        const rfid_reading_id = $('#rfidReadingId').val();
                        const modbus_id       = $('#modbusId').val();
                        const sensor_id       = $('#sensorId').val();

                        if (!id || !product_list_id || !rfid_reading_id) {
                            Swal.showValidationMessage('Producto, RFID e ID son obligatorios.');
                            return false;
                        }

                        return {
                            id: parseInt(id),
                            product_list_id: parseInt(product_list_id),
                            rfid_reading_id: parseInt(rfid_reading_id),
                            modbus_id: modbus_id ? parseInt(modbus_id) : null,
                            sensor_id: sensor_id ? parseInt(sensor_id) : null,
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
                            error: function() {
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