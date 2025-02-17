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
        <!-- Puedes agregar botones o título adicional si lo deseas -->
    </div>
    <div class="card-body">
        <!-- Contenedor con padding para separar la tabla del borde -->
        <div class="table-responsive p-3">
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
</div>
@endsection

@push('style')
    <!-- CSS DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <!-- Responsive extension CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <!-- CSS Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- FontAwesome (para ícono QR) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <style>
        /* Espaciado entre selects en el modal */
        .swal2-container .swal2-popup .swal2-html-container select,
        .swal2-container .swal2-popup .swal2-html-container .select2 {
            margin-bottom: 1em !important;
        }
        /* Estilo personalizado para los selects */
        .custom-select-style {
            width: 550px;
            background: transparent;
            color: black;
            text-shadow: 1px 1px 7px white;
            border: 1px solid #ccc;
            padding: 0.5em;
            border-radius: 4px;
        }
        /* Responsive: en pantallas pequeñas */
        @media (max-width: 576px) {
            .swal2-popup {
                width: 95% !important;
                max-width: none !important;
            }
            .custom-select-style {
                width: 100% !important;
            }
        }
        /* Ícono QR en el campo de búsqueda de Select2 usando FontAwesome */
        .select2-container--default .select2-search--dropdown .select2-search__field {
            position: relative;
            padding-right: 2em; /* Espacio para el ícono */
        }
        .select2-container--default .select2-search--dropdown .select2-search__field::after {
            content: "\f029"; /* fa-qrcode */
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #333;
            pointer-events: none;
            font-size: 1.2em;
        }
    </style>
@endpush

@push('scripts')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables & Buttons -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <!-- DataTables Responsive -->
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- html5-qrcode -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // Rutas API
        const relationsApiUrl = '/api/product-list-selecteds';
        const productsApiUrl  = '/api/product-lists/list-all';
        const rfidsApiUrl     = '/api/rfid-readings';
        const modbusesApiUrl  = '/api/product-list-selecteds/modbuses';
        const sensorsApiUrl   = '/api/product-list-selecteds/sensors';

        let productListOptions = '';
        let rfidOptions = '';
        let modbusOptions = '';
        let sensorOptions = '';
        let table;

        function loadSelectOptions() {
            $.get(productsApiUrl)
                .done((data) => {
                    productListOptions = data.map(product =>
                        `<option value="${product.id}">${product.name}</option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar productos.', 'error'); });

            $.get(rfidsApiUrl)
                .done((data) => {
                    rfidOptions = data.map(rfid =>
                        `<option value="${rfid.id}">${rfid.name}</option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar RFIDs.', 'error'); });

            $.get(modbusesApiUrl)
                .done((data) => {
                    modbusOptions = data.map(mb =>
                        `<option value="${mb.id}">${mb.name}</option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar Modbuses.', 'error'); });

            $.get(sensorsApiUrl)
                .done((data) => {
                    sensorOptions = data.map(sn =>
                        `<option value="${sn.id}">${sn.name}</option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar Sensores.', 'error'); });
        }

        $(document).ready(function() {
            loadSelectOptions();

            table = $('#relationsTable').DataTable({
                responsive: true,
                ajax: {
                    url: relationsApiUrl,
                    dataSrc: '',
                    error: function(xhr) {
                        let errorMsg = xhr.responseJSON ? JSON.stringify(xhr.responseJSON) : xhr.responseText;
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar datos',
                            text: errorMsg || 'Error desconocido'
                        });
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'product_list.name', defaultContent: 'Sin asignar' },
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
                    { data: 'created_at', render: data => new Date(data).toLocaleString('es-ES') },
                    { data: 'finish_at', render: data => !data ? 'En curso' : new Date(data).toLocaleString('es-ES') },
                    { data: 'modbus.name', defaultContent: 'N/A', render: (data, type, row) => row.modbus ? data : 'N/A' },
                    { data: 'sensor.name', defaultContent: 'N/A', render: (data, type, row) => row.sensor ? data : 'N/A' },
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
                            Swal.fire({
                                title: 'Añadir Relación',
                                width: '800px',
                                padding: '2em',
                                html: `
                                    <select id="productListId" class="swal2-input custom-select-style">
                                        <option value="" disabled selected>-- Seleccione Producto --</option>
                                        ${productListOptions}
                                    </select>
                                    <div style="position: relative;">
                                        <select id="rfidReadingId" class="swal2-input custom-select-style">
                                            <option value="" disabled selected>-- Seleccione RFID --</option>
                                            ${rfidOptions}
                                        </select>
                                        <!-- Botón QR sobrepuesto -->
                                        <button id="scanQrBtn" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; cursor: pointer;">
                                            <i class="fa fa-qrcode" style="font-size: 1.5em; color: #333;"></i>
                                        </button>
                                    </div>
                                    <div id="checkboxes" style="display:none; margin: 10px 0;">
                                        <div>
                                            <label>
                                                <input type="checkbox" id="modifyAll" style="margin-right: 10px;">
                                                Modificar para todas las tarjetas del mismo color.
                                            </label>
                                        </div>
                                        <div>
                                            <label>
                                                <input type="checkbox" id="modifyLine" style="margin-right: 10px;" disabled>
                                                Solo en esta Línea de production (RFID).
                                            </label>
                                        </div>
                                    </div>
                                    <select id="modbusId" class="swal2-input custom-select-style">
                                        <option value="" disabled selected>-- Seleccione Báscula --</option>
                                        ${modbusOptions}
                                    </select>
                                    <div id="checkboxModbus" style="display:none; margin: 10px 0;">
                                        <label>
                                            <input type="checkbox" id="modifyAllModbusLine" style="margin-right: 10px;">
                                            Todas las básculas de la misma línea.
                                        </label>
                                    </div>
                                    <select id="sensorId" class="swal2-input custom-select-style">
                                        <option value="" disabled selected>-- Seleccione Sensor --</option>
                                        ${sensorOptions}
                                    </select>
                                    <div id="checkboxSensor" style="display:none; margin: 10px 0;">
                                        <label>
                                            <input type="checkbox" id="modifyAllSensorLine" style="margin-right: 10px;">
                                            Todos los sensores de la misma línea.
                                        </label>
                                    </div>
                                    <!-- Contenedor para el escáner QR (oculto inicialmente) -->
                                    <div id="qr-reader" style="width:300px; margin: 1em auto; display: none;"></div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'Añadir',
                                didOpen: () => {
                                    // Inicializar Select2 en los selects del modal
                                    $('#productListId, #rfidReadingId, #modbusId, #sensorId').select2({
                                        dropdownParent: Swal.getPopup(),
                                        width: 'resolve'
                                    });

                                    function disableOthers(selected, others) {
                                        if (selected.val()) {
                                            others.prop('disabled', true).val(null).trigger('change');
                                        } else {
                                            others.prop('disabled', false);
                                        }
                                    }

                                    // Si se selecciona RFID, deshabilitar Báscula y Sensor
                                    $('#rfidReadingId').on('change', function() {
                                        if ($(this).val()) {
                                            $('#checkboxes').show();
                                            disableOthers($(this), $('#modbusId, #sensorId'));
                                        } else {
                                            $('#checkboxes').hide();
                                            $('#modifyAll, #modifyLine').prop('checked', false);
                                            $('#modifyLine').prop('disabled', true);
                                            $('#modbusId, #sensorId').prop('disabled', false);
                                        }
                                    });

                                    // Si se selecciona una Báscula, deshabilitar RFID y Sensor
                                    $('#modbusId').on('change', function() {
                                        if ($(this).val()) {
                                            $('#checkboxModbus').show();
                                            disableOthers($(this), $('#rfidReadingId, #sensorId'));
                                        } else {
                                            $('#checkboxModbus').hide();
                                            $('#modifyAllModbusLine').prop('checked', false);
                                            $('#rfidReadingId, #sensorId').prop('disabled', false);
                                        }
                                    });

                                    // Si se selecciona un Sensor, deshabilitar RFID y Báscula
                                    $('#sensorId').on('change', function() {
                                        if ($(this).val()) {
                                            $('#checkboxSensor').show();
                                            disableOthers($(this), $('#rfidReadingId, #modbusId'));
                                        } else {
                                            $('#checkboxSensor').hide();
                                            $('#modifyAllSensorLine').prop('checked', false);
                                            $('#rfidReadingId, #modbusId').prop('disabled', false);
                                        }
                                    });

                                    // Control para RFID: si se marca "Modificar para todas", habilitar "Solo en esta Línea"
                                    $('#modifyAll').on('change', function() {
                                        if ($(this).is(':checked')) {
                                            $('#modifyLine').prop('disabled', false);
                                        } else {
                                            $('#modifyLine').prop('checked', false).prop('disabled', true);
                                        }
                                    });

                                    // Evento para el botón "Escanear QR"
                                    $('#scanQrBtn').on('click', function(e) {
                                        e.preventDefault();
                                        startQrScanner();
                                    });
                                },
                                preConfirm: () => {
                                    const product_list_id = $('#productListId').val();
                                    const rfid_reading_id = $('#rfidReadingId').val();
                                    const modifyAll = $('#modifyAll').is(':checked');
                                    const modifyLine = $('#modifyLine').is(':checked');
                                    const modbus_id = $('#modbusId').val();
                                    const sensor_id = $('#sensorId').val();
                                    const modifyAllModbusLine = $('#modifyAllModbusLine').is(':checked');
                                    const modifyAllSensorLine = $('#modifyAllSensorLine').is(':checked');

                                    if (!product_list_id) {
                                        Swal.showValidationMessage('Producto es obligatorio.');
                                        return false;
                                    }

                                    return {
                                        client_id: parseInt(product_list_id),
                                        rfid_reading_id: rfid_reading_id ? parseInt(rfid_reading_id) : null,
                                        modify_all: modifyAll,
                                        modify_line: modifyAll ? modifyLine : false,
                                        modbus_id: modbus_id ? parseInt(modbus_id) : null,
                                        sensor_id: sensor_id ? parseInt(sensor_id) : null,
                                        modify_all_modbus_line: modbus_id ? modifyAllModbusLine : false,
                                        modify_all_sensor_line: sensor_id ? modifyAllSensorLine : false
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
                                            let errorMsg = xhr.responseJSON ? JSON.stringify(xhr.responseJSON) : xhr.responseText;
                                            Swal.fire('Error', errorMsg, 'error');
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
                        exportOptions: { columns: [0,1,2,3,4,5,6] },
                    },
                    {
                        text: 'QR Puesto',
                        className: 'btn btn-info',
                        action: function(e, dt, node, config) {
                            window.location.href = "{{ route('scan-post.index') }}";
                        }
                    }
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
                            error: function(xhr) {
                                let errorMsg = xhr.responseJSON ? JSON.stringify(xhr.responseJSON) : xhr.responseText;
                                Swal.fire('Error', errorMsg, 'error');
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
                const currentModbusId = $(this).data('modbus_id');
                const currentSensorId = $(this).data('sensor_id');

                Swal.fire({
                    title: 'Editar Relación',
                    html: `
                        <input id="relationId" class="swal2-input custom-select-style" value="${currentId}" readonly>
                        <label for="productListId">Producto:</label>
                        <select id="productListId" class="swal2-input custom-select-style">
                            ${productListOptions}
                        </select>
                        <label for="rfidReadingId">RFID:</label>
                        <select id="rfidReadingId" class="swal2-input custom-select-style">
                            ${rfidOptions}
                        </select>
                        <label for="modbusId">Modbus:</label>
                        <select id="modbusId" class="swal2-input custom-select-style">
                            <option value="" disabled>-- Seleccione --</option>
                            ${modbusOptions}
                        </select>
                        <label for="sensorId">Sensor:</label>
                        <select id="sensorId" class="swal2-input custom-select-style">
                            <option value="" disabled>-- Seleccione --</option>
                            ${sensorOptions}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    didOpen: () => {
                        $('#productListId, #rfidReadingId, #modbusId, #sensorId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve'
                        });
                        $('#productListId').val(currentProductListId).trigger('change');
                        $('#rfidReadingId').val(currentRfidReadingId).trigger('change');
                        $('#modbusId').val(currentModbusId).trigger('change');
                        $('#sensorId').val(currentSensorId).trigger('change');
                    },
                    preConfirm: () => {
                        const id = $('#relationId').val();
                        const product_list_id = $('#productListId').val();
                        const rfid_reading_id = $('#rfidReadingId').val();
                        const modbus_id = $('#modbusId').val();
                        const sensor_id = $('#sensorId').val();

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
                            error: function(xhr) {
                                let errorMsg = xhr.responseJSON ? JSON.stringify(xhr.responseJSON) : xhr.responseText;
                                Swal.fire('Error', errorMsg, 'error');
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

        // Función para iniciar el escáner QR con html5-qrcode
        function startQrScanner() {
            // Si ya existe una instancia, no la iniciamos de nuevo
            if(window.html5QrCodeInstance) return;
            window.html5QrCodeInstance = new Html5Qrcode("qr-reader");
            $('#qr-reader').show();
            const config = { fps: 10, qrbox: 250 };

            window.html5QrCodeInstance.start(
                { facingMode: "environment" },
                config,
                qrMessage => {
                    console.log("QR detectado:", qrMessage);
                    // Abrir el dropdown y rellenar el campo de búsqueda del select RFID
                    $('#rfidReadingId').select2('open');
                    let searchField = $('.select2-container--open .select2-search__field');
                    searchField.val(qrMessage);
                    searchField.trigger('input');

                    window.html5QrCodeInstance.stop().then(() => {
                        $('#qr-reader').hide();
                        window.html5QrCodeInstance = null;
                    }).catch(err => {
                        console.error("Error al detener el escáner QR:", err);
                        window.html5QrCodeInstance = null;
                    });
                },
                errorMessage => {
                    console.warn("Error de lectura QR:", errorMessage);
                }
            ).catch(err => {
                console.error("Error al iniciar el escáner QR:", err);
                window.html5QrCodeInstance = null;
            });
        }

        // Evento para el botón "Escanear QR"
        $(document).on('click', '#scanQrBtn', function(e) {
            e.preventDefault();
            startQrScanner();
        });
    </script>
@endpush
