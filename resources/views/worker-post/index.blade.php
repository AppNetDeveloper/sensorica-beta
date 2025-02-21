@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Relaciones Trabajador - Puesto')

{{-- Migas de pan (opcional) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Worker Post') }}</li>
    </ul>
@endsection

{{-- Contenido --}}
@section('content')
    <div class="card">
        <div class="card-body">
            <!-- Contenedor responsive con padding para separar la tabla del borde -->
            <div class="table-responsive p-3">
                <table id="workerPostTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>RFID</th>
                            <th>Sensor</th>
                            <th>Báscula</th>
                            <th>Contador</th>
                            <th>Confeccion</th>
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
    <!-- CSS DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <!-- Responsive extension CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <!-- CSS Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- FontAwesome (para ícono QR y otros) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <style>
        /* Espaciado entre selects en el modal */
        .swal2-container .swal2-popup .swal2-html-container select,
        .swal2-container .swal2-popup .swal2-html-container .select2 {
            margin-bottom: 1em !important;
        }
        /* Estilo personalizado para los selects */
        .custom-select-style {
            width: 88%;
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
        /* Ícono QR en el campo de búsqueda de Select2 */
        .select2-container--default .select2-search--dropdown .select2-search__field {
            position: relative;
            padding-right: 2em;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field::after {
            content: "\f029";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #a56f6f;
            pointer-events: none;
            font-size: 1.2em;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__placeholder {
            text-align: center !important;
            width: 100%;
        }
        /* Para select2 de selección única */
        .select2-container--default .select2-selection--single {
            height: 50px; /* Ajusta la altura según lo que necesites */
            padding: 6px 12px;
            line-height: 50px; /* Centra verticalmente el texto */
        }

        /* Para select2 de selección múltiple */
        .select2-container--default .select2-selection--multiple {
            min-height: 50px; /* Ajusta la altura mínima */
            padding: 6px; /* Opcional: modifica el padding si lo requieres */
        }

        /* Aumenta el tamaño del contenedor de la flecha en un select2 de selección única */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            width: 20px;  /* Ajusta el ancho según necesites */
        }

        /* Ajusta el tamaño de la flecha en sí (la flecha se dibuja con bordes) */
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-width: 14px 11px 0 11px; /* Modifica estos valores para agrandar la flecha */
        }

        /* Mover la flecha hacia la izquierda y ajustar su tamaño */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            width: 50px;              /* Ancho deseado */
            margin-left: -50px;       /* Mueve la flecha 10px hacia la izquierda; ajusta este valor según convenga */
        }

        /* Plantilla para cada opción (tarjeta) */
        .rfid-option-card {
            display: grid;
            grid-template-columns: auto 1fr;
            align-items: center;
            gap: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 2px 0;
        }
        .rfid-option-card .rfid-icon {
            font-size: 1.5em;
            /* Color base; se sobreescribe en línea según data-color */
            color: #007bff;
        }
        .rfid-option-card .rfid-text {
            font-size: 0.9em;
        }
        /* Opcional: resaltar las opciones seleccionadas en el dropdown */
        .select2-container--default .select2-results__option--selected {
            background-color: #DADADA!important;
            color: #fff !important;
        }
        /* Estilos para los "tags" (opciones seleccionadas) con color info */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #395458 !important;
            border: 1px solid #17a2b8 !important;
            color: #fff !important;
        }
        /* Dropdown en dos columnas con scroll vertical */
        .select2-container--default .select2-results__options {
            display: flex;
            flex-wrap: wrap;
            max-height: 500px;
            overflow-y: auto;
        }
        .select2-container--default .select2-results__option {
            width: 50%;
            box-sizing: border-box;
        }
        .btn-custom-success {
            background-color: green !important;
            border-color: green !important;
            color: #fff !important;
        }

        .btn-custom-danger {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
            color: #fff !important;
        }
        .rfid-option-card {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 2px 0;
        }
        .my-swal-popup {
            height: 500px !important; /* Ajusta el valor según lo que necesites */
        }
                /* Estilos para el texto en el desplegable de Select2 */
        .select2-container--default .select2-results__option {
            font-size: 17px;  /* Ajusta el tamaño según necesites */
            font-weight: bold; /* Para que se muestre en negrita */
        }
        .select2-container--default .select2-results__options {
            display: flex;
            flex-wrap: wrap;
            max-height: 500px;
            overflow-y: auto;
        }

        .select2-container--default .select2-results__option {
            width: 33.33%;
            box-sizing: border-box;
        }
        .select2-container--default .select2-results__options {
            min-height: 10px; /* Ajusta este valor según lo que necesites */
            max-height: 400px !important; /* Ajusta este valor según lo que necesites */
            overflow-y: auto;
        }

        .swal2-deny-button i {
            vertical-align: middle;
        }

        /* Asegúrate de que el contenedor de acciones utilice flexbox */
        .swal2-actions {
        display: flex;
        flex-direction: row;
        }

        /* Asigna un orden a cada botón: 
        - Cancel se muestra a la izquierda (order: 1)
        - Confirm en medio (order: 2)
        - Deny a la derecha (order: 3)
        */
        .swal2-cancel {
        order: 2;
        }
        .swal2-confirm {
        order: 1;
        }
        .swal2-deny {
        order: 3;
        }
        @media (max-width: 576px) {
            .select2-container--default .select2-results__option {
                width: 50%;
            }
        }

    </style>
@endpush

@push('scripts')
    <!-- jQuery y demás librerías -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
        // Configurar CSRF para AJAX
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Rutas base y URL de la API
        const baseUrl = "{{ rtrim(config('app.url'), '/') }}";
        const apiIndexUrl = `${baseUrl}/worker-post/api`;
        const storeUrl = `${baseUrl}/worker-post`;
        let updateUrlTemplate = `${baseUrl}/worker-post/:id`;
        let deleteUrlTemplate = `${baseUrl}/worker-post/:id`;

        // Datos pasados desde el controlador
        const operators = @json($operators);
        const rfids     = @json($rfids);
        const sensors   = @json($sensors);
        const modbuses  = @json($modbuses);

        // Funciones de formateo en grid (tarjetas)
        function formatOption(option) {
            if (!option.id) return option.text;
            let color = option.element ? option.element.getAttribute('data-color') : '';
            let colorMap = {
                red: "#dc3545",
                blue: "#007bff",
                yellow: "#ffc107",
                green: "#28a745"
            };
            let iconColor = colorMap[color] || "#007bff";
            return $(`
                <div class="rfid-option-card">
                    <div class="rfid-icon" style="color: ${iconColor};">
                        <i class="fa fa-id-card"></i>
                    </div>
                    <div class="rfid-text">${option.text}</div>
                </div>
            `);
        }
        function formatSelection(option) {
            return option.text;
        }
        function formatOperatorOption(option) {
            if (!option.id) return option.text;
            return $(`
                <div class="rfid-option-card">
                    <div class="rfid-icon" style="color: #17a2b8;">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="rfid-text">${option.text}</div>
                </div>
            `);
        }
        function formatOperatorSelection(option) {
            return option.text;
        }

        // Función para iniciar el escáner QR (se activa con el botón DENY)
        function startQrScanner() {
            if (window.html5QrCodeInstance) return;
            window.html5QrCodeInstance = new Html5Qrcode("qr-reader");
            $('#qr-reader').show();
            const config = { fps: 10, qrbox: 250 };

            window.html5QrCodeInstance.start(
                { facingMode: "environment" },
                config,
                qrMessage => {
                    console.log("QR detectado:", qrMessage);
                    // Asigna el valor leído al select RFID
                    $('#rfidId').val(qrMessage).trigger('change');
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
                                    <select id="rfidId" class="swal2-input custom-select-style">
                                        <option value="">Seleccione Tarjeta RFID</option>
                                        ${rfids.map(rfid => `<option value="${rfid.id}" data-color="${rfid.color ? rfid.color.toLowerCase() : ''}">${rfid.name}</option>`).join('')}
                                    </select>
                                    <select id="sensorId" class="swal2-input custom-select-style" >
                                        <option value="">Seleccione Sensor Conteo</option>
                                        ${sensors.map(sensor => `<option value="${sensor.id}" data-color="${sensor.color ? sensor.color.toLowerCase() : ''}">${sensor.name}</option>`).join('')}
                                    </select>
                                    <select id="modbusId" class="swal2-input custom-select-style" >
                                        <option value="">Seleccione una Báscula</option>
                                        ${modbuses.map(modbus => `<option value="${modbus.id}" data-color="${modbus.color ? modbus.color.toLowerCase() : ''}">${modbus.name}</option>`).join('')}
                                    </select>
                                    <!-- Div para el lector QR (se muestra al escanear) -->
                                    <div id="qr-reader" style="width:300px; margin: 1em auto; display: none;"></div>
                                `,
                                showCancelButton: true,
                                showDenyButton: true,
                                confirmButtonText: '<i class="bi bi-check-square"></i> &nbsp;AÑADIR',
                                cancelButtonText: '<i class="bi bi-x-square"></i> &nbsp;CANCELAR',
                                denyButtonText: '<i class="bi bi-qr-code"></i> &nbsp;ESCANEAR',
                                customClass: {
                                    confirmButton: 'btn btn-success',
                                    denyButton: 'btn btn-primary',
                                    cancelButton: 'btn btn-danger'
                                },
                                buttonsStyling: false,
                                preDeny: () => {
                                    startQrScanner();
                                    return false; // Evita el cierre del modal
                                },
                                // Dentro del didOpen del Swal.fire en "Añadir Relación"
                                didOpen: () => {
                                    // Inicializar Select2 para cada select
                                    $('#operatorId').select2({
                                        dropdownParent: Swal.getPopup(),
                                        width: 'resolve',
                                        placeholder: 'Seleccione Trabajador',
                                        templateResult: formatOperatorOption,
                                        templateSelection: formatOperatorSelection
                                    });
                                    $('#rfidId').select2({
                                        dropdownParent: Swal.getPopup(),
                                        width: 'resolve',
                                        placeholder: 'Seleccione Tarjeta RFID',
                                        templateResult: formatOption,
                                        templateSelection: formatSelection
                                    });
                                    $('#sensorId').select2({
                                        dropdownParent: Swal.getPopup(),
                                        width: 'resolve',
                                        placeholder: 'Seleccione Sensor Conteo',
                                        templateResult: formatOption,
                                        templateSelection: formatSelection
                                    });
                                    $('#modbusId').select2({
                                        dropdownParent: Swal.getPopup(),
                                        width: 'resolve',
                                        placeholder: 'Seleccione una Báscula',
                                        templateResult: formatOption,
                                        templateSelection: formatSelection
                                    });

                                    // Ocultar el select si no tiene opciones válidas (excluyendo el placeholder)
                                    if ($('#rfidId option[value!=""]').length === 0) {
                                        $('#rfidId').closest('.select2-container').hide();
                                    }
                                    if ($('#sensorId option[value!=""]').length === 0) {
                                        $('#sensorId').closest('.select2-container').hide();
                                    }
                                    if ($('#modbusId option[value!=""]').length === 0) {
                                        $('#modbusId').closest('.select2-container').hide();
                                    }

                                    // Eventos: al seleccionar un valor en uno, deshabilitar los otros dos
                                    $('#rfidId').on('change', function() {
                                        if ($(this).val()) {
                                            $('#sensorId').prop('disabled', true);
                                            $('#modbusId').prop('disabled', true);
                                        } else {
                                            $('#sensorId').prop('disabled', false);
                                            $('#modbusId').prop('disabled', false);
                                        }
                                    });
                                    $('#sensorId').on('change', function() {
                                        if ($(this).val()) {
                                            $('#rfidId').prop('disabled', true);
                                            $('#modbusId').prop('disabled', true);
                                        } else {
                                            $('#rfidId').prop('disabled', false);
                                            $('#modbusId').prop('disabled', false);
                                        }
                                    });
                                    $('#modbusId').on('change', function() {
                                        if ($(this).val()) {
                                            $('#rfidId').prop('disabled', true);
                                            $('#sensorId').prop('disabled', true);
                                        } else {
                                            $('#rfidId').prop('disabled', false);
                                            $('#sensorId').prop('disabled', false);
                                        }
                                    });
                                },


                                preConfirm: () => {
                                    const data = {
                                        operator_id: $('#operatorId').val(),
                                        rfid_reading_id: $('#rfidId').val(),
                                        sensor_id: $('#sensorId').val(),
                                        modbus_id: $('#modbusId').val()
                                    };
                                    if (!data.operator_id ) {
                                        Swal.showValidationMessage('Debe seleccionar un operador y al menos una de las ubicaciones.');
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
                    }
                ],
                order: [[7, 'desc'], [0, 'desc'], [1, 'desc']],
                ajax: {
                    url: apiIndexUrl,
                    dataSrc: 'data',
                    error: function (xhr) {
                        Swal.fire('Error', 'Error al cargar datos.', 'error');
                    }
                },
                columns: [
                    { data: 'operator.name', defaultContent: '<i class="bi bi-ban"></i>' },
                    { data: 'rfid_reading.name', defaultContent: '<i class="bi bi-ban"></i>' },
                    { data: 'sensor.name', defaultContent: '<i class="bi bi-ban"></i>' },
                    { data: 'modbus.name', defaultContent: '<i class="bi bi-ban"></i>' },
                    { data: 'count', defaultContent: '0' },
                    {
                        data: 'product_list',
                        defaultContent: 'Sin asignar',
                        render: function(data, type, row) {
                            if(data && data.name) {
                                return '<b>' + data.name + '</b>';
                            }
                            return '<span style="color: red;"><i class="bi bi-ban"></i></span>';
                        }
                    },
                    {    
                        data: 'created_at',
                        render: function (data) {
                            return data ? moment(data).format('DD/MM/YYYY HH:mm:ss') : '';
                        }
                    },
                    { data: 'finish_at', defaultContent: '<b>En curco</b>' },
                    {
                        data: null,
                        render: function (data) {
                            return `@role('admin')
                                        <button data-id="${data.id}" class="btn-sm btn btn-warning edit-btn">Editar</button>
                                        <button data-id="${data.id}" class="btn-sm btn btn-danger delete-btn">Eliminar</button>
                                    @else
                                        <!-- Sin acciones -->
                                    @endrole`;
                        }
                    }
                ],
                responsive: true,
                scrollX: true
            });
    
            // Eliminar registro
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
    
            // Editar registro
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
                        <select id="rfidId" class="swal2-input custom-select-style" multiple>
                            <option value="">Seleccione un RFID</option>
                            ${rfids.map(rfid => `<option value="${rfid.id}" data-color="${rfid.color ? rfid.color.toLowerCase() : ''}" ${(rowData.rfid_reading && rfid.id == rowData.rfid_reading.id) ? 'selected' : ''}>${rfid.name}</option>`).join('')}
                        </select>
                        <label for="sensorId">Sensor:</label>
                        <select id="sensorId" class="swal2-input custom-select-style" multiple>
                            <option value="">Seleccione un Sensor</option>
                            ${sensors.map(sensor => `<option value="${sensor.id}" data-color="${sensor.color ? sensor.color.toLowerCase() : ''}" ${(rowData.sensor && sensor.id == rowData.sensor.id) ? 'selected' : ''}>${sensor.name}</option>`).join('')}
                        </select>
                        <label for="modbusId">Modbus:</label>
                        <select id="modbusId" class="swal2-input custom-select-style" multiple>
                            <option value="">Seleccione una Báscula</option>
                            ${modbuses.map(modbus => `<option value="${modbus.id}" data-color="${modbus.color ? modbus.color.toLowerCase() : ''}" ${(rowData.modbus && modbus.id == rowData.modbus.id) ? 'selected' : ''}>${modbus.name}</option>`).join('')}
                        </select>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    didOpen: () => {
                        $('#operatorId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve',
                            placeholder: 'Seleccione Trabajador',
                            templateResult: formatOperatorOption,
                            templateSelection: formatOperatorSelection
                        });
                        $('#rfidId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve',
                            placeholder: 'Seleccione RFID',
                            templateResult: formatOption,
                            templateSelection: formatSelection
                        });
                        $('#sensorId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve',
                            placeholder: 'Seleccione Sensor',
                            templateResult: formatOption,
                            templateSelection: formatSelection
                        });
                        $('#modbusId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve',
                            placeholder: 'Seleccione Báscula',
                            templateResult: formatOption,
                            templateSelection: formatSelection
                        });
                        $('#operatorId').val(rowData.operator ? rowData.operator.id : '').trigger('change');
                        // Suponemos que los valores para RFID, Sensor y Modbus se reciben como arrays;
                        // si no, se deberán transformar a array.
                        $('#rfidId').val(rowData.rfid_reading ? rowData.rfid_reading : []).trigger('change');
                        $('#sensorId').val(rowData.sensor ? rowData.sensor : []).trigger('change');
                        $('#modbusId').val(rowData.modbus ? rowData.modbus : []).trigger('change');
                    },
                    preConfirm: () => {
                        const id = $('#relationId').val();
                        const operator_id = $('#operatorId').val();
                        const rfid_reading_ids = $('#rfidId').val();
                        const sensor_ids = $('#sensorId').val();
                        const modbus_ids = $('#modbusId').val();
    
                        if (!id || !operator_id || (!rfid_reading_ids && !sensor_ids && !modbus_ids)) {
                            Swal.showValidationMessage('Operador y al menos una de RFID, Sensor o Báscula son obligatorios.');
                            return false;
                        }
                        return {
                            id: parseInt(id),
                            operator_id: parseInt(operator_id),
                            rfid_reading_ids: rfid_reading_ids ? rfid_reading_ids.map(x => parseInt(x)) : [],
                            sensor_ids: sensor_ids ? sensor_ids.map(x => parseInt(x)) : [],
                            modbus_ids: modbus_ids ? modbus_ids.map(x => parseInt(x)) : [],
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
    </script>
@endpush
