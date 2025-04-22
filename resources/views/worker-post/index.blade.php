@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Relaciones Trabajador - Puesto')

{{-- Migas de pan (opcional) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Worker Post') }}</li>
    </ul>
@endsection

{{-- Contenido --}}
@section('content')
    <div class="card border-0 shadow">
        <div class="card-header">
            {{-- Puedes agregar botones o título adicional si lo deseas --}}
        </div>
        <div class="card-body">
            <div class="table-responsive p-3">
                <table id="workerPostTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Puesto/th>
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
    {{-- Links CSS (Asegurarse que Bootstrap Icons esté presente) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        /* Espaciado entre selects en el modal */
        .swal2-container .swal2-popup .swal2-html-container .select-block {
            margin-bottom: 1em !important;
        }
         .swal2-container .swal2-popup .swal2-html-container select,
         .swal2-container .swal2-popup .swal2-html-container .select2 {
             margin-bottom: 1em !important;
         }

        /* Estilo personalizado para los selects */
        .custom-select-style {
            width: 88%;
            background: transparent;
            color: black;
            border: 1px solid #ccc;
            padding: 0.5em;
            border-radius: 4px;
            display: block;
            margin-left: auto;
            margin-right: auto;
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
            content: "\f029"; /* Código FontAwesome para QR */
            font-family: "Font Awesome 6 Free";
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
            height: 50px;
            padding: 6px 12px;
            line-height: 36px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* Para select2 de selección múltiple */
        .select2-container--default .select2-selection--multiple {
            min-height: 50px;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        /* Flecha select2 única */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px;
            width: 30px;
            right: 1px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-width: 6px 6px 0 6px;
            margin-left: -6px;
            margin-top: -3px;
        }
        /* Plantilla para cada opción (tarjeta) */
        .rfid-option-card {
            display: grid;
            grid-template-columns: auto 1fr; /* Icono a la izquierda */
            align-items: center;
            gap: 10px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 2px 0;
            min-height: 40px;
        }
        .rfid-option-card .rfid-icon {
            font-size: 1.5em;
            color: #007bff;
        }
        /* Tamaño de fuente para el texto en tarjeta */
        .rfid-option-card .rfid-text {
            font-size: 1.1em;
            font-weight: bold;
            text-align: left;
        }
        /* Opción seleccionada en dropdown */
        .select2-container--default .select2-results__option--selected {
            background-color: #e9ecef !important;
            color: #495057 !important;
        }
        /* Estilos para los "tags" seleccionados */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
             background-color: #f8f9fa !important;
             border: 1px solid #dee2e6 !important;
             color: #212529 !important;
             padding: 5px 10px;
             margin: 2px;
             border-radius: 4px;
             display: inline-flex;
             align-items: center;
             gap: 5px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice .rfid-selected-text {
            color: black !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice .fa-id-card {
            margin-right: 0;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            background-color: transparent !important;
            border: none !important;
            color: #dc3545 !important;
            font-size: 1.1em !important;
            font-weight: bold !important;
            padding: 0 4px !important;
            margin-left: 4px !important;
            cursor: pointer;
            order: 2;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #a71d2a !important;
            background-color: transparent !important;
        }
        /* Layout de columnas auto-adaptable para dropdown Select2 */
        .select2-container--default .select2-results__options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px;
            padding: 5px;
            /* Aumentamos la altura del desplegable para que se muestren más opciones */
            max-height: 40vh !important;
            overflow-y: auto;
        }
        .select2-container--default .select2-results__option {
            box-sizing: border-box;
            display: block;
            white-space: normal;
            font-size: 1rem;
            font-weight: normal;
        }
        /* Botones personalizados */
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
        /* Iconos y orden botones Swal */
        .swal2-confirm i,
        .swal2-deny i,
        .swal2-cancel i {
            vertical-align: middle;
            margin-right: 0.3em;
        }
        .swal2-actions {
            display: flex;
            flex-direction: row;
            gap: 10px;
        }
        .swal2-confirm {
            order: 1;
        }
        .swal2-cancel {
            order: 2;
        }
        .swal2-deny {
            order: 3;
        }
        @media (max-width: 576px) {
            .swal2-actions {
                flex-direction: column;
            }
        }
        /* Estilo tabla DataTable */
        #workerPostTable {
            border-collapse: collapse;
        }
        #workerPostTable th,
        #workerPostTable td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        #workerPostTable th {
            background-color: #f2f2f2;
        }
        /* Input readonly edición */
        #relationId {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        /* Etiquetas modal */
        .swal2-html-container label {
            display: block;
            margin-top: 0.8em;
            margin-bottom: 0.2em;
            font-weight: bold;
            text-align: left;
            margin-left: 6%;
        }
        @media (max-width: 576px) {
            .swal2-html-container label {
                margin-left: 0;
            }
        }
    </style>
@endpush

@push('scripts')
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
                red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745"
            };
            let iconColor = colorMap[color] || "#6c757d";
            let iconClass = 'fa-id-card';

            if (option.element?.parentElement?.id === 'sensorId') iconClass = 'fa-wave-square';
            if (option.element?.parentElement?.id === 'modbusId') iconClass = 'fa-weight-hanging';

            return $(`
                <div class="rfid-option-card">
                    <div class="rfid-icon" style="color: ${iconColor};">
                        <i class="fas ${iconClass}"></i>
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
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="rfid-text">${option.text}</div>
                </div>
            `);
        }
        function formatOperatorSelection(option) {
             if (!option.id) return option.text;
             return $(`<span><i class="fas fa-user" style="color: #17a2b8; margin-right: 5px;"></i>${option.text}</span>`);
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
                    const $rfidSelect = $('#rfidId');
                    const $option = $rfidSelect.find('option').filter(function() {
                         return $(this).text().trim() === qrMessage.trim();
                    });

                    if ($option.length > 0) {
                        $rfidSelect.val($option.val()).trigger('change');
                        $rfidSelect.select2('close');
                         const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
                         toastMixin.fire({ icon: 'success', title: `RFID ${qrMessage} seleccionado` });
                    } else {
                         const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
                         toastMixin.fire({ icon: 'warning', title: `RFID ${qrMessage} no encontrado` });
                         $('#rfidId').select2('open');
                         let searchField = $('.select2-container--open .select2-search__field');
                         if (searchField.length) {
                             searchField.val(qrMessage).trigger('input');
                         }
                    }

                    window.html5QrCodeInstance.stop().then(() => {
                        $('#qr-reader').hide();
                        window.html5QrCodeInstance = null;
                    }).catch(err => {
                        console.error("Error al detener el escáner QR:", err);
                        window.html5QrCodeInstance = null;
                        $('#qr-reader').hide();
                    });
                },
                errorMessage => {
                    // Errores continuos de lectura QR se ignoran
                }
            ).catch(err => {
                console.error("Error al iniciar el escáner QR:", err);
                Swal.fire('Error','No se pudo iniciar el escáner QR. Revisa permisos.','error');
                window.html5QrCodeInstance = null;
                $('#qr-reader').hide();
            });
        }

        // Función para detener el escáner QR
        function stopQrScanner() {
             const qrReaderElement = document.getElementById('qr-reader');
             if (window.html5QrCodeInstance?.isScanning) {
                 window.html5QrCodeInstance.stop().then(() => {
                     console.log("Escáner QR detenido.");
                     if(qrReaderElement) qrReaderElement.style.display = 'none';
                 }).catch(err => {
                     console.error("Error al detener el escáner QR:", err);
                     if(qrReaderElement) qrReaderElement.style.display = 'none';
                 });
             } else {
                  if(qrReaderElement) qrReaderElement.style.display = 'none';
             }
        }


        $(document).ready(function () {
            const table = $('#workerPostTable').DataTable({
                responsive: true,
                pageLength: 20,
                lengthMenu: [ [10, 20, 50, -1], [10, 20, 50, "All"] ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: '<i class="bi bi-plus-circle"></i> Añadir Relación',
                        className: 'btn btn-primary mb-2',
                        action: function (e, dt, node, config) {
                            let modalHtml = `<div class="select-block"><label for="operatorId">Trabajador:</label><select id="operatorId" class="swal2-input custom-select-style"><option value="">Seleccione Trabajador</option>${operators.map(op => `<option value="${op.id}">${op.name}</option>`).join('')}</select></div>`;
                            if (rfids && rfids.length > 0) {
                                modalHtml += `<div class="select-block"><label for="rfidId">RFID:</label><select id="rfidId" class="swal2-input custom-select-style"><option value="">Seleccione Tarjeta RFID</option>${[...new Map(rfids.map(item => [item['name'], item])).values()].map(rfid => `<option value="${rfid.id}" data-color="${rfid.color ? rfid.color.toLowerCase() : ''}">${rfid.name}</option>`).join('')}</select></div>`;
                            }
                            if (sensors && sensors.length > 0) {
                                modalHtml += `<div class="select-block"><label for="sensorId">Sensor:</label><select id="sensorId" class="swal2-input custom-select-style"><option value="">Seleccione Sensor Conteo</option>${sensors.map(sensor => `<option value="${sensor.id}" data-color="${sensor.color ? sensor.color.toLowerCase() : ''}">${sensor.name}</option>`).join('')}</select></div>`;
                            }
                             if (modbuses && modbuses.length > 0) {
                                modalHtml += `<div class="select-block"><label for="modbusId">Báscula:</label><select id="modbusId" class="swal2-input custom-select-style"><option value="">Seleccione una Báscula</option>${modbuses.map(modbus => `<option value="${modbus.id}" data-color="${modbus.color ? modbus.color.toLowerCase() : ''}">${modbus.name}</option>`).join('')}</select></div>`;
                            }
                            modalHtml += `<div id="qr-reader" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                            Swal.fire({
                                title: 'Añadir Relación',
                                width: '80%',
                                html: modalHtml,
                                showCancelButton: true,
                                showDenyButton: (rfids && rfids.length > 0),
                                confirmButtonText: '<i class="bi bi-check-square"></i> AÑADIR',
                                cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR',
                                denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR',
                                customClass: {
                                    confirmButton: 'btn btn-success mx-1',
                                    denyButton: 'btn btn-info mx-1',
                                    cancelButton: 'btn btn-danger mx-1'
                                },
                                buttonsStyling: false,
                                preDeny: () => {
                                    startQrScanner();
                                    return false;
                                },
                                didOpen: () => {
                                    $('#operatorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Trabajador', templateResult: formatOperatorOption, templateSelection: formatOperatorSelection });
                                    if (rfids && rfids.length > 0) { $('#rfidId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Tarjeta RFID', templateResult: formatOption, templateSelection: formatSelection }); }
                                    if (sensors && sensors.length > 0) { $('#sensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Sensor Conteo', templateResult: formatOption, templateSelection: formatSelection }); }
                                    if (modbuses && modbuses.length > 0) { $('#modbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione una Báscula', templateResult: formatOption, templateSelection: formatSelection }); }

                                    const $rfidSelect = $('#rfidId');
                                    const $sensorSelect = $('#sensorId');
                                    const $modbusSelect = $('#modbusId');

                                    if ($rfidSelect.length) {
                                        $rfidSelect.on('change', function() {
                                            const disable = !!$(this).val();
                                            if ($sensorSelect.length) $sensorSelect.prop('disabled', disable).trigger('change.select2');
                                            if ($modbusSelect.length) $modbusSelect.prop('disabled', disable).trigger('change.select2');
                                        });
                                    }
                                    if ($sensorSelect.length) {
                                        $sensorSelect.on('change', function() {
                                            const disable = !!$(this).val();
                                            if ($rfidSelect.length) $rfidSelect.prop('disabled', disable).trigger('change.select2');
                                            if ($modbusSelect.length) $modbusSelect.prop('disabled', disable).trigger('change.select2');
                                        });
                                    }
                                    if ($modbusSelect.length) {
                                        $modbusSelect.on('change', function() {
                                            const disable = !!$(this).val();
                                            if ($rfidSelect.length) $rfidSelect.prop('disabled', disable).trigger('change.select2');
                                            if ($sensorSelect.length) $sensorSelect.prop('disabled', disable).trigger('change.select2');
                                        });
                                    }
                                },
                                preConfirm: () => {
                                    const operatorId = $('#operatorId').val();
                                    const operatorName = $('#operatorId option:selected').text();
                                    const rfidVal = $('#rfidId').val();
                                    const sensorVal = $('#sensorId').val();
                                    const modbusVal = $('#modbusId').val();
                                    let selectedLocation = '';
                                    let locationType = '';

                                    if (rfidVal) { selectedLocation = $('#rfidId option:selected').text(); locationType = 'RFID'; }
                                    else if (sensorVal) { selectedLocation = $('#sensorId option:selected').text(); locationType = 'Sensor'; }
                                    else if (modbusVal) { selectedLocation = $('#modbusId option:selected').text(); locationType = 'Báscula'; }

                                    if (!operatorId) {
                                        Swal.showValidationMessage('Debe seleccionar un trabajador.');
                                        return false;
                                    }

                                    const confirmMessage = selectedLocation
                                        ? `Asignar Operario: <strong>${operatorName}</strong> a ${locationType}: <strong>${selectedLocation}</strong>. <br>¿Continuar?`
                                        : `Desvincular todos los puestos del Operario: <strong>${operatorName}</strong>. <br>¿Continuar?`;

                                    return Swal.fire({
                                        title: 'Confirmar Asignación',
                                        html: confirmMessage,
                                        icon: 'question',
                                        showCancelButton: true,
                                        confirmButtonText: 'Sí, continuar',
                                        cancelButtonText: 'Cancelar',
                                        customClass: {
                                            confirmButton: 'btn btn-success mx-1',
                                            cancelButton: 'btn btn-secondary mx-1'
                                        },
                                        buttonsStyling: false
                                    }).then(confirmation => {
                                        if (!confirmation.isConfirmed) {
                                            return Swal.showValidationMessage('Asignación cancelada');
                                        }
                                        return {
                                            operator_id: operatorId,
                                            rfid_reading_id: rfidVal || null,
                                            sensor_id: sensorVal || null,
                                            modbus_id: modbusVal || null
                                        };
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    $.post(storeUrl, result.value)
                                        .done(response => {
                                            if (response.success) {
                                                Swal.fire({ title: 'Guardado', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false });
                                                table.ajax.reload();
                                            } else {
                                                Swal.fire('Error', response.message || 'No se pudo guardar la relación.', 'error');
                                            }
                                        })
                                        .fail(xhr => {
                                             let errorMsg = xhr.responseJSON?.message || 'Error al guardar la relación.';
                                             Swal.fire('Error', errorMsg, 'error');
                                        });
                                }
                            });
                        }
                    },
                    { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Exportar a Excel', className: 'btn btn-success mb-2', titleAttr: 'Exportar tabla a Excel' },
                    { text: '<i class="bi bi-broadcast"></i> Live Rfid', className: 'btn btn-info mb-2', action: function () { window.open('/live-rfid/', '_blank'); }, titleAttr: 'Ver lecturas RFID en tiempo real' }
                ],
                order: [[7, 'desc'], [6, 'desc'], [0, 'asc']],
                ajax: {
                    url: apiIndexUrl,
                    dataSrc: 'data',
                    error: function (xhr) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Error al cargar datos de relaciones.',
                            timer: 1000,
                            showConfirmButton: false,
                            timerProgressBar: true
                        });
                    }
                },
                columns: [
                    { data: 'operator.name', defaultContent: 'N/A' },
                    { data: 'rfid_reading.name', defaultContent: 'N/A' },
                    { data: 'sensor.name', defaultContent: 'N/A' },
                    { data: 'modbus.name', defaultContent: 'N/A' },
                    { data: 'count', defaultContent: '0' },
                    { data: 'product_list.name', defaultContent: '<span style="color: red;">Sin asignar</span>', render: (d) => d ? `<b>${d}</b>` : '<span style="color: red;">Sin asignar</span>' },
                    { data: 'created_at', render: data => data ? moment(data).format('DD/MM/YYYY HH:mm:ss') : 'N/A' },
                    { data: 'finish_at', render: data => data ? moment(data).format('DD/MM/YYYY HH:mm:ss') : '<b>En curso</b>' },
                    {
                        data: null, orderable: false, searchable: false,
                        render: function (data, type, row) {
                            return `
                                @can('workers-edit')
                                    <button data-id="${row.id}" class="btn-sm btn btn-warning edit-btn" style="margin-right: 5px;">Editar</button>
                                @endcan
                                @can('workers-delete')
                                    <button data-id="${row.id}" class="btn-sm btn btn-danger delete-btn">Eliminar</button>
                                @endcan
                            `;
                        }
                    }
                ]
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
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    customClass: { confirmButton: 'btn btn-danger mx-1', cancelButton: 'btn btn-secondary mx-1' },
                    buttonsStyling: false
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({ url: deleteUrl, method: 'DELETE' })
                            .done(response => {
                                if (response.success) {
                                    Swal.fire('Eliminado', response.message, 'success');
                                    table.ajax.reload();
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            })
                            .fail((xhr) => { 
                                Swal.fire('Error', xhr.responseJSON?.message || 'No se pudo eliminar.', 'error');
                            });
                    }
                });
            });

            // Editar registro
            $('#workerPostTable').on('click', '.edit-btn', function () {
                const id = $(this).data('id');
                const rowData = table.row($(this).closest('tr')).data();
                const updateUrl = updateUrlTemplate.replace(':id', id);

                let modalHtmlEdit = `<input type="hidden" id="editRelationId" value="${id}">`;
                modalHtmlEdit += `<div class="select-block"><label for="editOperatorId">Trabajador:</label><select id="editOperatorId" class="swal2-input custom-select-style"><option value="">Seleccione Trabajador</option>${operators.map(op => `<option value="${op.id}" ${rowData.operator && op.id == rowData.operator.id ? 'selected' : ''}>${op.name}</option>`).join('')}</select></div>`;
                if (rfids && rfids.length > 0) {
                     modalHtmlEdit += `<div class="select-block"><label for="editRfidId">RFID:</label><select id="editRfidId" class="swal2-input custom-select-style"><option value="">Seleccione Tarjeta RFID</option>${[...new Map(rfids.map(item => [item['name'], item])).values()].map(rfid => `<option value="${rfid.id}" data-color="${rfid.color ? rfid.color.toLowerCase() : ''}" ${rowData.rfid_reading && rfid.id == rowData.rfid_reading.id ? 'selected' : ''}>${rfid.name}</option>`).join('')}</select></div>`;
                }
                if (sensors && sensors.length > 0) {
                     modalHtmlEdit += `<div class="select-block"><label for="editSensorId">Sensor:</label><select id="editSensorId" class="swal2-input custom-select-style"><option value="">Seleccione Sensor Conteo</option>${sensors.map(sensor => `<option value="${sensor.id}" data-color="${sensor.color ? sensor.color.toLowerCase() : ''}" ${rowData.sensor && sensor.id == rowData.sensor.id ? 'selected' : ''}>${sensor.name}</option>`).join('')}</select></div>`;
                }
                 if (modbuses && modbuses.length > 0) {
                     modalHtmlEdit += `<div class="select-block"><label for="editModbusId">Báscula:</label><select id="editModbusId" class="swal2-input custom-select-style"><option value="">Seleccione una Báscula</option>${modbuses.map(modbus => `<option value="${modbus.id}" data-color="${modbus.color ? modbus.color.toLowerCase() : ''}" ${rowData.modbus && modbus.id == rowData.modbus.id ? 'selected' : ''}>${modbus.name}</option>`).join('')}</select></div>`;
                 }

                Swal.fire({
                    title: 'Editar Relación',
                    width: '80%',
                    html: modalHtmlEdit,
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-save"></i> Actualizar',
                    cancelButtonText: '<i class="bi bi-x-square"></i> Cancelar',
                    customClass: { confirmButton: 'btn btn-success mx-1', cancelButton: 'btn btn-danger mx-1' },
                    buttonsStyling: false,
                    didOpen: () => {
                        $('#editOperatorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatOperatorOption, templateSelection: formatOperatorSelection });
                        if (rfids && rfids.length > 0) { $('#editRfidId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione RFID', templateResult: formatOption, templateSelection: formatSelection }); }
                        if (sensors && sensors.length > 0) { $('#editSensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Sensor', templateResult: formatOption, templateSelection: formatSelection }); }
                        if (modbuses && modbuses.length > 0) { $('#editModbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Báscula', templateResult: formatOption, templateSelection: formatSelection }); }

                        const $editRfidSelect = $('#editRfidId');
                        const $editSensorSelect = $('#editSensorId');
                        const $editModbusSelect = $('#editModbusId');

                        if ($editRfidSelect.length) {
                            $editRfidSelect.on('change', function() {
                                const d = !!$(this).val();
                                if ($editSensorSelect.length) $editSensorSelect.prop('disabled', d).trigger('change.select2');
                                if ($editModbusSelect.length) $editModbusSelect.prop('disabled', d).trigger('change.select2');
                            });
                        }
                        if ($editSensorSelect.length) {
                            $editSensorSelect.on('change', function() {
                                const d = !!$(this).val();
                                if ($editRfidSelect.length) $editRfidSelect.prop('disabled', d).trigger('change.select2');
                                if ($editModbusSelect.length) $editModbusSelect.prop('disabled', d).trigger('change.select2');
                            });
                        }
                        if ($editModbusSelect.length) {
                            $editModbusSelect.on('change', function() {
                                const d = !!$(this).val();
                                if ($editRfidSelect.length) $editRfidSelect.prop('disabled', d).trigger('change.select2');
                                if ($editSensorSelect.length) $editSensorSelect.prop('disabled', d).trigger('change.select2');
                            });
                        }
                        if ($editRfidSelect.length) $editRfidSelect.trigger('change');
                        if ($editSensorSelect.length) $editSensorSelect.trigger('change');
                        if ($editModbusSelect.length) $editModbusSelect.trigger('change');
                    },
                    preConfirm: () => {
                        const operator_id = $('#editOperatorId').val();
                        const rfid_reading_id = $('#editRfidId').val();
                        const sensor_id = $('#editSensorId').val();
                        const modbus_id = $('#editModbusId').val();

                        if (!operator_id) {
                            Swal.showValidationMessage('Debe seleccionar un operador.');
                            return false;
                        }

                        return {
                            operator_id: parseInt(operator_id),
                            rfid_reading_id: rfid_reading_id ? parseInt(rfid_reading_id) : null,
                            sensor_id: sensor_id ? parseInt(sensor_id) : null,
                            modbus_id: modbus_id ? parseInt(modbus_id) : null,
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        $.ajax({
                            url: updateUrl,
                            method: 'PUT',
                            contentType: 'application/json',
                            data: JSON.stringify(result.value),
                            success: function(response) {
                                Swal.fire('Éxito', response.message || 'Relación actualizada.', 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                let errorMsg = xhr.responseJSON?.message || 'Error al actualizar la relación.';
                                //Swal.fire('Error', errorMsg, 'error');
                            }
                        });
                    }
                });
            });

            // Opcional: recargar la tabla cada 10 segundos
            setInterval(() => { if (table) table.ajax.reload(null, false); }, 10000);
        });
    </script>
@endpush
