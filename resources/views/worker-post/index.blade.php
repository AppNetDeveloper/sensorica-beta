@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Relaciones Operador - Puesto')

{{-- Migas de pan (opcional) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Worker Post') }}</li>
    </ul>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- Contenedor responsive con padding para separar la tabla del borde -->
            <div class="table-responsive p-3">
                <table id="workerPostTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trabajador</th>
                            <th>RFID</th>
                            <th>Sensor</th>
                            <th>Bascula</th>
                            <th>Contador</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
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
    <!-- Meta CSRF -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- CSS DataTables y Buttons -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <!-- Responsive extension CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <!-- CSS Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- FontAwesome (para ícono QR) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <style>
        /* Margen inferior para separar los selects en el modal */
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
        /* Ajuste responsive: en pantallas pequeñas */
        @media (max-width: 576px) {
            .swal2-popup {
                width: 95% !important;
                max-width: none !important;
            }
            .custom-select-style {
                width: 100% !important;
            }
        }
        /* Personalizar el campo de búsqueda de Select2 para RFID con ícono QR */
        .select2-container--default .select2-search--dropdown .select2-search__field {
            position: relative;
            padding-right: 2em; /* Espacio para el ícono */
        }
        .select2-container--default .select2-search--dropdown .select2-search__field::after {
            content: "\f029"; /* Código Unicode de fa-qrcode */
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
    <!-- DataTables y sus extensiones -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- html5-qrcode -->
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // Configurar CSRF para AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Rutas y URLs base
        const baseUrl = "{{ rtrim(config('app.url'), '/') }}";
        const apiIndexUrl = `${baseUrl}/worker-post/api`;
        const storeUrl = `${baseUrl}/worker-post`;
        let updateUrlTemplate = `${baseUrl}/worker-post/:id`;
        let deleteUrlTemplate = `${baseUrl}/worker-post/:id`;

        // Datos para los selects (pasados desde el controlador)
        const operators = @json($operators);
        const rfids = @json($rfids);
        const sensors = @json($sensors);
        const modbuses = @json($modbuses);

        // Funciones para selección mutuamente exclusiva
        function validateSelection(selectedType) {
            if (selectedType === 'rfid') {
                $('#sensorId').prop('disabled', true);
                $('#modbusId').prop('disabled', true);
            } else if (selectedType === 'sensor') {
                $('#rfidId').prop('disabled', true);
                $('#modbusId').prop('disabled', true);
            } else if (selectedType === 'modbus') {
                $('#rfidId').prop('disabled', true);
                $('#sensorId').prop('disabled', true);
            }
        }
        function resetSelection() {
            $('#sensorId').prop('disabled', false);
            $('#rfidId').prop('disabled', false);
            $('#modbusId').prop('disabled', false);
        }

        $(document).ready(function () {
            // Inicializar DataTable para Worker Post
            const table = $('#workerPostTable').DataTable({
                responsive: true,
                scrollX: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Añadir Relación',
                        className: 'btn btn-primary',
                        action: function (e, dt, node, config) {
                            Swal.fire({
                                title: 'Añadir Relación',
                                width: '800px',
                                padding: '2em',
                                html: `
                                    <select id="operatorId" class="swal2-input custom-select-style">
                                        <option value="">Seleccione Trabajador</option>
                                        ${operators.map(op => `<option value="${op.id}">${op.name}</option>`).join('')}
                                    </select>
                                    <div style="position: relative;">
                                        <select id="rfidId" class="swal2-input custom-select-style">
                                            <option value="">Seleccione Tarjeta RFID</option>
                                            ${rfids.map(rfid => `<option value="${rfid.id}">${rfid.name}</option>`).join('')}
                                        </select>
                                        <!-- Botón para escanear QR, sobrepuesto al select RFID -->
                                        <button id="scanQrBtn" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; cursor: pointer;">
                                            <i class="fa fa-qrcode" style="font-size: 1.5em; color: #333;"></i>
                                        </button>
                                    </div>
                                    <select id="sensorId" class="swal2-input custom-select-style">
                                        <option value="">Seleccione Sensor Conteo</option>
                                        ${sensors.map(sensor => `<option value="${sensor.id}">${sensor.name}</option>`).join('')}
                                    </select>
                                    <select id="modbusId" class="swal2-input custom-select-style">
                                        <option value="">Seleccione una Báscula</option>
                                        ${modbuses.map(modbus => `<option value="${modbus.id}">${modbus.name}</option>`).join('')}
                                    </select>
                                    <!-- Div para el escáner QR (dentro del modal) -->
                                    <div id="qr-reader" style="width:100%; height:250px; display: none;"></div>
                                `,
                                showCancelButton: true,
                                confirmButtonText: 'Guardar',
                                didOpen: () => {
                                    // Inicializar Select2 en los selects
                                    $('#operatorId, #rfidId, #sensorId, #modbusId').select2({
                                        dropdownParent: Swal.getPopup(),
                                        width: 'resolve'
                                    });
    
                                    // Manejo de selección mutuamente exclusiva
                                    $('#rfidId, #sensorId, #modbusId').change(function () {
                                        resetSelection();
                                        validateSelection($(this).attr('id').replace('Id', ''));
                                    });
    
                                    // Vincular el evento para el botón QR dentro del modal
                                    $('#scanQrBtn').on('click', function(e) {
                                        e.preventDefault();
                                        startQrScanner();
                                    });
                                },
                                preConfirm: () => {
                                    const data = {
                                        operator_id: $('#operatorId').val(),
                                        rfid_reading_id: $('#rfidId').val(),
                                        sensor_id: $('#sensorId').val(),
                                        modbus_id: $('#modbusId').val()
                                    };
                                    if (!data.operator_id || (!data.rfid_reading_id && !data.sensor_id && !data.modbus_id)) {
                                        Swal.showValidationMessage('Debe seleccionar un operador y al menos una ubicación.');
                                        return false;
                                    }
                                    return $.post(storeUrl, data)
                                        .done(response => {
                                            if (response.success) {
                                                Swal.fire('Guardado', response.message, 'success');
                                                table.ajax.reload();
                                            } else {
                                                Swal.fire('Error', response.message, 'error');
                                            }
                                        })
                                        .fail(xhr => {
                                            Swal.showValidationMessage(xhr.responseJSON?.message || 'Error');
                                        });
                                }
                            });
                        }
                    },
                    {
                        extend: 'excel',
                        text: 'Exportar a Excel',
                        className: 'btn btn-success'
                    },
                    {
                        text: 'QR Puesto',
                        className: 'btn btn-info',
                        action: function (e, dt, node, config) {
                            window.location.href = "{{ route('scan-post.index') }}";
                        }
                    }
                ],
                order: [[0, 'desc']],
                ajax: {
                    url: apiIndexUrl,
                    dataSrc: 'data',
                    error: function (xhr) {
                        Swal.fire('Error', 'Error al cargar datos.', 'error');
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'operator.name', defaultContent: 'Sin asignar' },
                    { data: 'rfid_reading.name', defaultContent: 'Sin asignar' },
                    { data: 'sensor.name', defaultContent: 'Sin asignar' },
                    { data: 'modbus.name', defaultContent: 'Sin asignar' },
                    { data: 'count', defaultContent: '0' },
                    { data: 'created_at' },
                    { data: 'updated_at', defaultContent: 'En curso' },
                    {
                        data: null,
                        render: function (data) {
                            return `@role('admin')
                                        <button data-id="${'${data.id}'}" class="btn-sm btn btn-warning edit-btn">Editar</button>
                                        <button data-id="${'${data.id}'}" class="btn-sm btn btn-danger delete-btn">Eliminar</button>
                                    @else
                                        <!-- Sin acciones -->
                                    @endrole`;
                        }
                    }
                ],
                responsive: true,
                scrollX: true
            });
    
            // Evento: Eliminar
            $('#workerPostTable').on('click', '.delete-btn', function () {
                const id = $(this).data('id');
                const deleteUrl = deleteUrlTemplate.replace(':id', id);
                Swal.fire({
                    title: 'Eliminar Relación',
                    text: '¿Estás seguro? Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            method: 'DELETE'
                        }).done(response => {
                            if (response.success) {
                                Swal.fire('Eliminado', response.message, 'success');
                                table.ajax.reload();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }).fail(() => {
                            Swal.fire('Error', 'No se pudo eliminar.', 'error');
                        });
                    }
                });
            });
    
            // Evento: Editar
            $('#workerPostTable').on('click', '.edit-btn', function () {
                const id = $(this).data('id');
                const rowData = table.row($(this).closest('tr')).data();
                const updateUrl = updateUrlTemplate.replace(':id', id);
    
                Swal.fire({
                    title: 'Editar Relación',
                    html: `
                        <input id="relationId" class="swal2-input custom-select-style" value="${id}" readonly>
                        <label for="operatorId">Operador:</label>
                        <select id="operatorId" class="swal2-input custom-select-style">
                            <option value="">Seleccione un operador</option>
                            ${operators.map(op => `<option value="${op.id}" ${(rowData.operator && op.id == rowData.operator.id) ? 'selected' : ''}>${op.name}</option>`).join('')}
                        </select>
                        <label for="rfidId">RFID:</label>
                        <select id="rfidId" class="swal2-input custom-select-style">
                            <option value="">Seleccione un RFID</option>
                            ${rfids.map(rfid => `<option value="${rfid.id}" ${(rowData.rfid_reading && rfid.id == rowData.rfid_reading.id) ? 'selected' : ''}>${rfid.name}</option>`).join('')}
                        </select>
                        <label for="sensorId">Sensor:</label>
                        <select id="sensorId" class="swal2-input custom-select-style">
                            <option value="">Seleccione un Sensor</option>
                            ${sensors.map(sensor => `<option value="${sensor.id}" ${(rowData.sensor && sensor.id == rowData.sensor.id) ? 'selected' : ''}>${sensor.name}</option>`).join('')}
                        </select>
                        <label for="modbusId">Modbus:</label>
                        <select id="modbusId" class="swal2-input custom-select-style">
                            <option value="">Seleccione un Modbus</option>
                            ${modbuses.map(modbus => `<option value="${modbus.id}" ${(rowData.modbus && modbus.id == rowData.modbus.id) ? 'selected' : ''}>${modbus.name}</option>`).join('')}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    didOpen: () => {
                        $('#operatorId, #rfidId, #sensorId, #modbusId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve'
                        });
                        $('#operatorId').val(rowData.operator ? rowData.operator.id : '').trigger('change');
                        $('#rfidId').val(rowData.rfid_reading ? rowData.rfid_reading.id : '').trigger('change');
                        $('#sensorId').val(rowData.sensor ? rowData.sensor.id : '').trigger('change');
                        $('#modbusId').val(rowData.modbus ? rowData.modbus.id : '').trigger('change');
                    },
                    preConfirm: () => {
                        const id = $('#relationId').val();
                        const operator_id = $('#operatorId').val();
                        const rfid_reading_id = $('#rfidId').val();
                        const sensor_id = $('#sensorId').val();
                        const modbus_id = $('#modbusId').val();
    
                        if (!id || !operator_id || !rfid_reading_id) {
                            Swal.showValidationMessage('Operador, RFID e ID son obligatorios.');
                            return false;
                        }
    
                        return {
                            id: parseInt(id),
                            operator_id: parseInt(operator_id),
                            rfid_reading_id: parseInt(rfid_reading_id),
                            sensor_id: sensor_id ? parseInt(sensor_id) : null,
                            modbus_id: modbus_id ? parseInt(modbus_id) : null,
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: updateUrl,
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
    
            // Recargar la tabla cada 10 segundos (opcional)
            setInterval(() => {
                table.ajax.reload(null, false);
            }, 10000);
        });
    
        // Función para iniciar el escáner QR usando html5-qrcode
        function startQrScanner() {
            // Evitar iniciar si ya existe una instancia
            if (window.html5QrCodeInstance) return;
            window.html5QrCodeInstance = new Html5Qrcode("qr-reader");
            $('#qr-reader').show();
            const config = { fps: 10, qrbox: 250 };
    
            window.html5QrCodeInstance.start(
                { facingMode: "environment" },
                config,
                qrMessage => {
                    console.log("QR detectado:", qrMessage);
                    // Asignar el valor escaneado al select RFID
                    $('#rfidId').val(qrMessage).trigger('change');
                    // Abrir el dropdown para que se vea el valor en el campo de búsqueda
                    $('#rfidId').select2('open');
                    let searchField = $('.select2-container--open .select2-search__field');
                    if (searchField.length) {
                        searchField.val(qrMessage);
                        searchField.trigger('input');
                    }
    
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
    
        // Vincular el evento del botón "Escanear QR"
        $(document).on('click', '#scanQrBtn', function(e) {
            e.preventDefault();
            startQrScanner();
        });
    </script>
@endpush
