@php
    // Asegúrate de que estas variables se pasan correctamente desde tu controlador
    // $operators, $rfids, $sensors, $modbuses
@endphp
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
             {{-- **NUEVO**: Contenedor para controles adicionales (Switch) --}}
             {{-- Este div se moverá con JS a la posición correcta --}}
             <div class="history-switch-container d-flex justify-content-end mb-2" style="display: none;"> {{-- Oculto inicialmente --}}
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="showHistorySwitchWorker"> {{-- ID único para el switch --}}
                    <label class="form-check-label" for="showHistorySwitchWorker">Mostrar historial</label>
                  </div>
             </div>
            <div class="table-responsive p-3">
                <table id="workerPostTable" class="table table-bordered table-striped" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Trabajador</th>   {{-- 0 --}}
                            <th>Puesto</th>       {{-- 1 --}}
                            <th>Sensor</th>       {{-- 2 --}}
                            <th>Báscula</th>      {{-- 3 --}}
                            <th>Contador</th>     {{-- 4 --}}
                            <th>Confeccion</th>   {{-- 5 --}}
                            <th>Hora Inicio</th>  {{-- 6 --}}
                            <th>Hora Fin</th>     {{-- 7 --}}
                            <th>Acciones</th>     {{-- 8 --}}
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- **NUEVO**: Botón flotante para refrescar --}}
    <button id="refreshWorkerPostTableBtn" class="btn btn-primary btn-float" title="Refrescar Tabla">
        <i class="bi bi-arrow-clockwise"></i>
    </button>
@endsection

@push('style')
    {{-- Links CSS específicos para esta vista --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    {{-- Font Awesome y Bootstrap Icons son cargados por el layout principal --}}

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
        @media (max-width: 767px) { /* Ajustado breakpoint */
            .swal2-popup {
                width: 95% !important;
                max-width: none !important;
            }
            .custom-select-style {
                width: 100% !important;
            }
            /* Ajustar posición del switch y botones en móviles */
            .top-controls-row .dt-buttons-placeholder,
            .top-controls-row .switch-placeholder {
                width: 100%; text-align: left; margin-bottom: 0.5em;
            }
             .top-controls-row .switch-placeholder { justify-content: flex-start !important; padding-left: 0; }
             .dt-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
             /* Ajustar filtro y length menu en móviles */
             .filter-length-row .col-md-6 { width: 100%; text-align: left; margin-bottom: 0.5em; }
             .dataTables_filter { float: none !important; }
             .dataTables_length { float: none !important; }
             /* Ajustar botón flotante en móviles */
             .btn-float { bottom: 15px; right: 15px; width: 45px; height: 45px; }
        }
        /* Ícono QR en el campo de búsqueda de Select2 */
        .select2-container--default .select2-search--dropdown .select2-search__field {
            position: relative; padding-right: 2em;
        }
        .select2-container--default .select2-search--dropdown .select2-search__field::after {
            content: "\f029"; font-family: "Font Awesome 6 Free"; font-weight: 900;
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            color: #a56f6f; pointer-events: none; font-size: 1.2em;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__placeholder {
            text-align: center !important; width: 100%;
        }
        /* Para select2 de selección única */
        .select2-container--default .select2-selection--single {
            height: 50px; padding: 6px 12px; line-height: 36px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        /* Para select2 de selección múltiple */
        .select2-container--default .select2-selection--multiple {
            min-height: 50px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;
        }
        /* Flecha select2 única */
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 48px; width: 30px; right: 1px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-width: 6px 6px 0 6px; margin-left: -6px; margin-top: -3px;
        }
        /* Plantilla para cada opción (tarjeta) */
        .rfid-option-card {
            display: grid; grid-template-columns: auto 1fr; /* Icono a la izquierda */
            align-items: center; gap: 10px; padding: 5px;
            border: 1px solid #ccc; border-radius: 4px; margin: 2px 0; min-height: 40px;
        }
        .rfid-option-card .rfid-icon { font-size: 1.5em; color: #007bff; }
        .rfid-option-card .rfid-text { font-size: 1.1em; font-weight: bold; text-align: left; }
        /* Opción seleccionada en dropdown */
        .select2-container--default .select2-results__option--selected {
            background-color: #e9ecef !important; color: #495057 !important;
        }
        /* Estilos para los "tags" seleccionados */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
             background-color: #f8f9fa !important; border: 1px solid #dee2e6 !important; color: #212529 !important;
             padding: 5px 10px; margin: 2px; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice .rfid-selected-text { color: black !important; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice .fa-id-card { margin-right: 0; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            background-color: transparent !important; border: none !important; color: #dc3545 !important;
            font-size: 1.1em !important; font-weight: bold !important; padding: 0 4px !important;
            margin-left: 4px !important; cursor: pointer; order: 2;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { color: #a71d2a !important; background-color: transparent !important; }
        /* Layout de columnas auto-adaptable para dropdown Select2 */
        .select2-container--default .select2-results__options {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px; padding: 5px; max-height: 40vh !important; overflow-y: auto;
        }
        .select2-container--default .select2-results__option {
            box-sizing: border-box; display: block; white-space: normal; font-size: 1rem; font-weight: normal;
        }
        /* Botones personalizados */
        .btn-custom-success { background-color: green !important; border-color: green !important; color: #fff !important; }
        .btn-custom-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; color: #fff !important; }
        /* Iconos y orden botones Swal */
        .swal2-confirm i, .swal2-deny i, .swal2-cancel i { vertical-align: middle; margin-right: 0.3em; }
        .swal2-actions { display: flex; flex-direction: row; gap: 10px; }
        .swal2-confirm { order: 1; } .swal2-cancel { order: 2; } .swal2-deny { order: 3; }
        @media (max-width: 576px) { .swal2-actions { flex-direction: column; } }
        /* Estilo tabla DataTable */
        #workerPostTable { border-collapse: collapse; }
        #workerPostTable th, #workerPostTable td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        #workerPostTable th { background-color: #f2f2f2; }
        /* Input readonly edición */
        #relationId { background-color: #e9ecef; cursor: not-allowed; }
        /* Etiquetas modal */
        .swal2-html-container label { display: block; margin-top: 0.8em; margin-bottom: 0.2em; font-weight: bold; text-align: left; margin-left: 6%; }
        @media (max-width: 576px) { .swal2-html-container label { margin-left: 0; } }
        /* Estilos para el botón de visibilidad de columnas */
        .dt-button.buttons-collection { background-color: #6c757d !important; border-color: #6c757d !important; color: #fff !important; }
        .dt-button.buttons-columnVisibility.active { background-color: #5a6268 !important; border-color: #545b62 !important; }
        .dt-button-collection .dt-button { min-width: 150px; }
        /* Estilo para el contenedor del length menu, filtro y controles personalizados */
        .dataTables_wrapper .top-controls-row { margin-bottom: 1em; }
        .dataTables_wrapper .filter-length-row { margin-bottom: 1em; }
        .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_filter label { margin-right: 0.5em; margin-bottom: 0; }
        .dataTables_wrapper .dataTables_length select {
             width: auto; display: inline-block; padding: 0.375rem 1.75rem 0.375rem 0.75rem;
             border: 1px solid #ced4da; border-radius: 0.25rem; vertical-align: middle;
         }
         /* Ancho fijo al input de búsqueda */
         .dataTables_filter input.form-control {
             width: 300px !important; display: inline-block !important; vertical-align: middle;
         }
         /* Estilo para el switch */
         .form-switch .form-check-input { cursor: pointer; width: 3em; height: 1.5em; margin-left: -0.5em; }
         .form-switch .form-check-label { padding-left: 1em; vertical-align: middle; }
         /* Alineación de botones y switch */
         .dt-buttons .btn { margin-right: 0.25em;}
         .switch-placeholder { display: flex; justify-content: flex-end; align-items: center; }
         /* Estilos para el botón flotante */
         .btn-float {
            position: fixed; width: 50px; height: 50px; bottom: 30px; right: 30px;
            border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.2); display: flex;
            align-items: center; justify-content: center; z-index: 1050; font-size: 1.5rem;
         }
    </style>
@endpush

@push('scripts')
    {{-- Links JS específicos para esta vista (jQuery es cargado por el layout) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script> {{-- JS para ColVis --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

    <script>
        // Variable para el intervalo de actualización
        let refreshIntervalIdWorker = null;
        const REFRESH_INTERVAL_WORKER = 6000; // 6 segundos

        // Función para iniciar el intervalo de refresco
        function startRefreshIntervalWorker() {
            if (refreshIntervalIdWorker) clearInterval(refreshIntervalIdWorker);
            refreshIntervalIdWorker = setInterval(function() {
                if (tableWorker) {
                    console.log('Actualizando tabla Worker-Post automáticamente...');
                    tableWorker.ajax.reload(null, false);
                }
            }, REFRESH_INTERVAL_WORKER);
            console.log('Intervalo de refresco Worker-Post iniciado.');
        }

        // Función para detener el intervalo de refresco
        function stopRefreshIntervalWorker() {
            if (refreshIntervalIdWorker) {
                clearInterval(refreshIntervalIdWorker);
                refreshIntervalIdWorker = null;
                console.log('Intervalo de refresco Worker-Post detenido.');
            }
        }

        // Configurar CSRF para AJAX (Asume que meta tag existe en layout)
        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        // Rutas base y URL de la API
        const baseUrlWorker = window.location.origin;
        const apiIndexUrlWorker = `${baseUrlWorker}/worker-post/api`;
        const storeUrlWorker = `${baseUrlWorker}/worker-post`;
        let updateUrlTemplateWorker = `${baseUrlWorker}/worker-post/:id`;
        let deleteUrlTemplateWorker = `${baseUrlWorker}/worker-post/:id`;

        // Datos pasados desde el controlador (Asegúrate que existan)
        const operatorsWorker = @json($operators ?? []); // Usar ?? [] como fallback
        const rfidsWorker     = @json($rfids ?? []);
        const sensorsWorker   = @json($sensors ?? []);
        const modbusesWorker  = @json($modbuses ?? []);

        let tableWorker; // Variable para la instancia de DataTable

        // --- Funciones de formato para Select2 (Reutilizadas con nombres diferentes si es necesario) ---
        function formatOptionWorker(option) { /* ... (código de formatOption) ... */
            if (!option.id) return option.text;
            let color = option.element ? option.element.getAttribute('data-color') : '';
            let colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
            let iconColor = colorMap[color] || "#6c757d";
            let iconClass = 'fa-id-card'; // Icono por defecto

            // Determinar icono basado en el ID del select padre
            const parentId = option.element?.parentElement?.id;
            if (parentId === 'sensorId' || parentId === 'editSensorId') iconClass = 'fa-wave-square';
            if (parentId === 'modbusId' || parentId === 'editModbusId') iconClass = 'fa-weight-hanging';

            return $(`
                <div class="rfid-option-card">
                    <div class="rfid-icon" style="color: ${iconColor};">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="rfid-text">${option.text}</div>
                </div>
            `);
        }
        function formatSelectionWorker(option) { return option.text; }
        function formatOperatorOptionWorker(option) { /* ... (código de formatOperatorOption) ... */
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
        function formatOperatorSelectionWorker(option) { /* ... (código de formatOperatorSelection) ... */
             if (!option.id) return option.text;
             return $(`<span><i class="fas fa-user" style="color: #17a2b8; margin-right: 5px;"></i>${option.text}</span>`);
        }

        // Función para iniciar el escáner QR
        let html5QrCodeInstanceWorker = null;
        function startQrScannerWorker(targetSelectId) {
            const qrReaderId = targetSelectId.startsWith('edit') ? 'qr-reader-edit' : 'qr-reader'; // Asume IDs qr-reader y qr-reader-edit
            const qrReaderElement = document.getElementById(qrReaderId);
             if (!qrReaderElement) { console.error(`Elemento ${qrReaderId} no encontrado.`); return; }
             if (html5QrCodeInstanceWorker && html5QrCodeInstanceWorker.isScanning) { console.warn("El escáner QR ya está activo."); return; }

             qrReaderElement.style.display = 'block';
             if (!html5QrCodeInstanceWorker) { html5QrCodeInstanceWorker = new Html5Qrcode(qrReaderId); }
             const config = { fps: 10, qrbox: 250 };
             const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timerProgressBar: true });

             html5QrCodeInstanceWorker.start({ facingMode: "environment" }, config,
                 (decodedText, decodedResult) => {
                     stopQrScannerWorker(); // Detener al detectar
                     const $targetSelect = $(`#${targetSelectId}`);
                     const $option = $targetSelect.find('option').filter(function() { return $(this).text().trim() === decodedText.trim(); });

                     if ($option.length > 0) {
                         $targetSelect.val($option.val()).trigger('change');
                         $targetSelect.select2('close'); // Cerrar dropdown al seleccionar con QR
                         toastMixin.fire({ icon: 'success', title: `RFID ${decodedText} seleccionado`, timer: 1500 });
                     } else {
                         toastMixin.fire({ icon: 'warning', title: `RFID ${decodedText} no encontrado`, timer: 2000 });
                         $targetSelect.select2('open'); // Reabrir para búsqueda manual
                         let searchField = $('.select2-container--open .select2-search__field');
                         if (searchField.length) searchField.val(decodedText).trigger('input');
                     }
                 }, (errorMessage) => { /* Ignorar errores continuos */ }
             ).catch(err => {
                 console.error("Error al iniciar escáner QR:", err);
                 Swal.fire('Error','No se pudo iniciar el escáner QR. Revisa permisos.','error');
                 stopQrScannerWorker(); // Asegurarse de detener si falla
             });
        }

        // Función para detener el escáner QR
        function stopQrScannerWorker() {
             const qrReaderElement = document.getElementById('qr-reader');
             const qrReaderEditElement = document.getElementById('qr-reader-edit');
             if (html5QrCodeInstanceWorker?.isScanning) {
                 html5QrCodeInstanceWorker.stop().then(() => {
                     console.log("Escáner QR detenido.");
                     if(qrReaderElement) qrReaderElement.style.display = 'none';
                     if(qrReaderEditElement) qrReaderEditElement.style.display = 'none';
                 }).catch(err => {
                     console.error("Error al detener el escáner QR:", err);
                 }).finally(() => {
                     html5QrCodeInstanceWorker = null; // Limpiar instancia
                     if(qrReaderElement) qrReaderElement.style.display = 'none';
                     if(qrReaderEditElement) qrReaderEditElement.style.display = 'none';
                 });
             } else {
                  if(qrReaderElement) qrReaderElement.style.display = 'none';
                  if(qrReaderEditElement) qrReaderEditElement.style.display = 'none';
             }
        }


        $(document).ready(function () {
            // **NUEVO**: Filtro personalizado para mostrar/ocultar historial
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex, rowData, counter ) {
                    // Aplicar solo a esta tabla
                    if (settings.nTable.id !== 'workerPostTable') { return true; }

                    var showHistory = $('#showHistorySwitchWorker').is(':checked');
                    // Índice 7 corresponde a 'Hora Fin' según tu thead
                    var finishDateStr = data[7] || ''; // Obtener el texto renderizado

                    if (showHistory) {
                        return true; // Mostrar todo si el switch está ON
                    } else {
                        // Mostrar solo si la celda contiene 'En curso' (o está vacía, por si acaso)
                        return finishDateStr.includes('En curso') || finishDateStr.trim() === 'N/A' || finishDateStr.trim() === '';
                    }
                }
            );

            tableWorker = $('#workerPostTable').DataTable({ // Usar la variable global tableWorker
                responsive: true,
                pageLength: 300,
                lengthMenu: [ [10, 20, 50, 300, -1], [10, 20, 50, 300,  "All"] ], // **NUEVO**: Length Menu
                stateSave: true, // **NUEVO**: Guardar estado
                stateDuration: -1,
                // **NUEVO**: DOM modificado para controles
                dom: "<'row top-controls-row'<'col-sm-12 col-md-6 dt-buttons-placeholder'><'col-sm-12 col-md-6 switch-placeholder'>>" +
                     "<'row filter-length-row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [ // **NUEVO**: Definición de botones
                    {
                        text: '<i class="bi bi-plus-circle"></i> Añadir Relación',
                        className: 'btn btn-primary', // Quitado mb-2 para controlarlo con CSS
                        action: function (e, dt, node, config) {
                            // --- Lógica Modal Añadir ---
                            let modalHtml = `<div class="select-block"><label for="operatorId">Trabajador:</label><select id="operatorId" class="swal2-input custom-select-style"><option value="">Seleccione Trabajador</option>${operatorsWorker.map(op => `<option value="${op.id}">${op.name}</option>`).join('')}</select></div>`;
                            if (rfidsWorker && rfidsWorker.length > 0) modalHtml += `<div class="select-block"><label for="rfidId">RFID:</label><select id="rfidId" class="swal2-input custom-select-style"><option value="">Seleccione Tarjeta RFID</option>${[...new Map(rfidsWorker.map(item => [item['name'], item])).values()].map(rfid => `<option value="${rfid.id}" data-color="${rfid.color ? rfid.color.toLowerCase() : ''}">${rfid.name}</option>`).join('')}</select></div>`;
                            if (sensorsWorker && sensorsWorker.length > 0) modalHtml += `<div class="select-block"><label for="sensorId">Sensor:</label><select id="sensorId" class="swal2-input custom-select-style"><option value="">Seleccione Sensor Conteo</option>${sensorsWorker.map(sensor => `<option value="${sensor.id}" data-color="${sensor.color ? sensor.color.toLowerCase() : ''}">${sensor.name}</option>`).join('')}</select></div>`;
                            if (modbusesWorker && modbusesWorker.length > 0) modalHtml += `<div class="select-block"><label for="modbusId">Báscula:</label><select id="modbusId" class="swal2-input custom-select-style"><option value="">Seleccione una Báscula</option>${modbusesWorker.map(modbus => `<option value="${modbus.id}" data-color="${modbus.color ? modbus.color.toLowerCase() : ''}">${modbus.name}</option>`).join('')}</select></div>`;
                            modalHtml += `<div id="qr-reader" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                            Swal.fire({
                                title: 'Añadir Relación', width: '80%', html: modalHtml,
                                showCancelButton: true, showDenyButton: (rfidsWorker && rfidsWorker.length > 0), // Mostrar Deny solo si hay RFIDs
                                confirmButtonText: '<i class="bi bi-check-square"></i> AÑADIR',
                                cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR',
                                denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR',
                                customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' },
                                buttonsStyling: false,
                                preDeny: () => { startQrScannerWorker('rfidId'); return false; }, // Llamar a la función correcta
                                didOpen: () => {
                                    stopRefreshIntervalWorker(); // **NUEVO**: Pausar refresco
                                    $('#operatorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Trabajador', templateResult: formatOperatorOptionWorker, templateSelection: formatOperatorSelectionWorker });
                                    if (rfidsWorker && rfidsWorker.length > 0) { $('#rfidId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Tarjeta RFID', templateResult: formatOptionWorker, templateSelection: formatSelectionWorker }); }
                                    if (sensorsWorker && sensorsWorker.length > 0) { $('#sensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Sensor Conteo', templateResult: formatOptionWorker, templateSelection: formatSelectionWorker }); }
                                    if (modbusesWorker && modbusesWorker.length > 0) { $('#modbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione una Báscula', templateResult: formatOptionWorker, templateSelection: formatSelectionWorker }); }

                                    // Lógica para deshabilitar otros selects al seleccionar uno
                                    const $rfidSelect = $('#rfidId'); const $sensorSelect = $('#sensorId'); const $modbusSelect = $('#modbusId');
                                    if ($rfidSelect.length) $rfidSelect.on('change', function() { const d = !!$(this).val(); if ($sensorSelect.length) $sensorSelect.prop('disabled', d).trigger('change.select2'); if ($modbusSelect.length) $modbusSelect.prop('disabled', d).trigger('change.select2'); });
                                    if ($sensorSelect.length) $sensorSelect.on('change', function() { const d = !!$(this).val(); if ($rfidSelect.length) $rfidSelect.prop('disabled', d).trigger('change.select2'); if ($modbusSelect.length) $modbusSelect.prop('disabled', d).trigger('change.select2'); });
                                    if ($modbusSelect.length) $modbusSelect.on('change', function() { const d = !!$(this).val(); if ($rfidSelect.length) $rfidSelect.prop('disabled', d).trigger('change.select2'); if ($sensorSelect.length) $sensorSelect.prop('disabled', d).trigger('change.select2'); });
                                },
                                preConfirm: () => {
                                    // --- Lógica preConfirm ---
                                    const operatorId = $('#operatorId').val(); const operatorName = $('#operatorId option:selected').text();
                                    const rfidVal = $('#rfidId').val(); const sensorVal = $('#sensorId').val(); const modbusVal = $('#modbusId').val();
                                    let selectedLocation = ''; let locationType = '';
                                    if (rfidVal) { selectedLocation = $('#rfidId option:selected').text(); locationType = 'RFID'; }
                                    else if (sensorVal) { selectedLocation = $('#sensorId option:selected').text(); locationType = 'Sensor'; }
                                    else if (modbusVal) { selectedLocation = $('#modbusId option:selected').text(); locationType = 'Báscula'; }
                                    if (!operatorId) { Swal.showValidationMessage('Debe seleccionar un trabajador.'); return false; }
                                    const confirmMessage = selectedLocation ? `Asignar Operario: <strong>${operatorName}</strong> a ${locationType}: <strong>${selectedLocation}</strong>. <br>¿Continuar?` : `Desvincular todos los puestos del Operario: <strong>${operatorName}</strong>. <br>¿Continuar?`;
                                    return Swal.fire({ title: 'Confirmar Asignación', html: confirmMessage, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar', customClass: { confirmButton: 'btn btn-success mx-1', cancelButton: 'btn btn-secondary mx-1' }, buttonsStyling: false })
                                    .then(confirmation => {
                                        if (!confirmation.isConfirmed) return Swal.showValidationMessage('Asignación cancelada');
                                        return { operator_id: operatorId, rfid_reading_id: rfidVal || null, sensor_id: sensorVal || null, modbus_id: modbusVal || null };
                                    });
                                    // --- Fin Lógica preConfirm ---
                                },
                                didClose: () => {
                                    stopQrScannerWorker(); // Detener escáner si estaba activo
                                    startRefreshIntervalWorker(); // **NUEVO**: Reanudar refresco
                                }
                            }).then((result) => {
                                if (result.isConfirmed && result.value) {
                                    $.post(storeUrlWorker, result.value)
                                        .done(response => { if (response.success) { Swal.fire({ title: 'Guardado', text: response.message, icon: 'success', timer: 2000, showConfirmButton: false }); tableWorker.ajax.reload(); } else { Swal.fire('Error', response.message || 'No se pudo guardar.', 'error'); } })
                                        .fail(xhr => { let errorMsg = xhr.responseJSON?.message || 'Error al guardar.'; Swal.fire('Error', errorMsg, 'error'); });
                                }
                            });
                        }
                    },
                        // Botón para exportar a Excel
                        {
                            extend: 'excelHtml5',
                            text: '<i class="bi bi-file-earmark-excel"></i> Excel',
                            className: 'btn btn-success',
                            titleAttr: 'Exportar a Excel',
                            exportOptions: {
                                columns: ':visible' // Exportar solo columnas visibles
                            }
                        },
                        // ** MODIFICADO **: Botón para imprimir (solo columnas visibles)
                        {
                            extend: 'print',
                            text: '<i class="bi bi-printer"></i> Imprimir',
                            className: 'btn btn-secondary',
                            titleAttr: 'Imprimir tabla',
                            exportOptions: {
                                columns: ':visible' // Asegurar que solo se impriman las columnas visibles
                            }
                        },
                    { extend: 'colvis', text: '<i class="bi bi-eye"></i> Columnas', className: 'btn btn-secondary', titleAttr: 'Mostrar/Ocultar columnas' }, // **NUEVO**: Botón ColVis
                    { text: '<i class="bi bi-broadcast"></i> Live Rfid', className: 'btn btn-info', action: function () { window.open('/live-rfid/', '_blank'); }, titleAttr: 'Ver lecturas RFID en tiempo real' } // Quitado mb-2
                ],
                order: [[7, 'desc'], [6, 'desc'], [0, 'asc']], // Orden por Hora Fin (desc), Hora Inicio (desc), Trabajador (asc)
                ajax: {
                    url: apiIndexUrlWorker,
                    dataSrc: 'data', // Asegúrate que tu API devuelva { "data": [...] }
                    error: function (xhr) {
                        // **MODIFICADO**: Ignorar errores 'abort'
                        if (xhr.statusText === 'abort') {
                             console.warn('Petición AJAX de Worker-Post abortada.');
                             return;
                        }
                        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Error al cargar datos.', timer: 3000, showConfirmButton: false, timerProgressBar: true });
                    }
                },
                columns: [
                    { data: 'operator.name', defaultContent: 'N/A' }, // 0
                    { data: 'rfid_reading.name', defaultContent: 'N/A' }, // 1
                    { data: 'sensor.name', defaultContent: 'N/A' }, // 2
                    { data: 'modbus.name', defaultContent: 'N/A' }, // 3
                    { data: 'count', defaultContent: '0' }, // 4
                    { data: 'product_list.name', defaultContent: '<span style="color: red;">Sin asignar</span>', render: (d) => d ? `<b>${d}</b>` : '<span style="color: red;">Sin asignar</span>' }, // 5
                    { data: 'created_at', render: data => data ? moment(data).format('DD/MM/YYYY HH:mm:ss') : 'N/A' }, // 6
                    { data: 'finish_at', render: data => data ? moment(data).format('DD/MM/YYYY HH:mm:ss') : '<b>En curso</b>' }, // 7
                    { // 8
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
                ],
                language: { // **NUEVO**: Idioma Español
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                    search: "_INPUT_", // Quitar etiqueta "Buscar:"
                    searchPlaceholder: "Buscar..."
                },
                initComplete: function(settings, json) {
                    console.log("DataTable inicializado.");
                    const api = this.api();

                    // Añadir un pequeño retraso antes de mover los botones y switches
                    setTimeout(function() {
                        // Volver a comprobar si la tabla todavía existe (por si se destruyó)
                        if ($.fn.DataTable.isDataTable('#workerPostTable')) {
                            console.log("Moviendo botones y switches tras el retraso...");

                            // Mover controles a los placeholders
                            api.buttons().container().appendTo('.dt-buttons-placeholder');
                            $('.history-switch-container').appendTo('.switch-placeholder').show();
                        } else {
                            console.warn("La tabla ya no existe al intentar mover botones/switches.");
                        }
                    }, 2000); // 100 milisegundos de retraso (ajustable si es necesario)

                    // Estilos adicionales
                    $('.dataTables_filter input').addClass('form-control'); // Tamaño estándar
                    $('.dataTables_length select').addClass('form-select form-select-sm');

                    // Aplicar filtro inicial
                     tableWorker.draw();

                     // Event listener para el switch
                     $('#showHistorySwitchWorker').on('change', function() {
                         tableWorker.draw();
                     });

                     // Iniciar refresco automático
                     startRefreshIntervalWorker();
                }
            });

            // Eliminar registro
            $('#workerPostTable tbody').on('click', '.delete-btn', function () {
                const button = this; // Guardar referencia
                const id = $(this).data('id');
                const deleteUrl = deleteUrlTemplateWorker.replace(':id', id);
                Swal.fire({
                    title: 'Eliminar Relación', text: '¿Estás seguro? Esta acción no se puede deshacer.', icon: 'warning',
                    showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
                    customClass: { confirmButton: 'btn btn-danger mx-1', cancelButton: 'btn btn-secondary mx-1' }, buttonsStyling: false
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({ url: deleteUrl, method: 'DELETE' })
                            .done(response => { if (response.success) { Swal.fire('Eliminado', response.message, 'success'); tableWorker.row($(button).closest('tr')).remove().draw(false); } else { Swal.fire('Error', response.message, 'error'); } })
                            .fail((xhr) => { Swal.fire('Error', xhr.responseJSON?.message || 'No se pudo eliminar.', 'error'); });
                    }
                });
            });

            // Editar registro
            $('#workerPostTable tbody').on('click', '.edit-btn', function () {
                const id = $(this).data('id');
                const rowData = tableWorker.row($(this).closest('tr')).data(); // Usar tableWorker
                const updateUrl = updateUrlTemplateWorker.replace(':id', id);

                let modalHtmlEdit = `<input type="hidden" id="editRelationId" value="${id}">`;
                modalHtmlEdit += `<div class="select-block"><label for="editOperatorId">Trabajador:</label><select id="editOperatorId" class="swal2-input custom-select-style"><option value="">Seleccione Trabajador</option>${operatorsWorker.map(op => `<option value="${op.id}" ${rowData.operator && op.id == rowData.operator.id ? 'selected' : ''}>${op.name}</option>`).join('')}</select></div>`;
                if (rfidsWorker && rfidsWorker.length > 0) modalHtmlEdit += `<div class="select-block"><label for="editRfidId">RFID:</label><select id="editRfidId" class="swal2-input custom-select-style"><option value="">Seleccione Tarjeta RFID</option>${[...new Map(rfidsWorker.map(item => [item['name'], item])).values()].map(rfid => `<option value="${rfid.id}" data-color="${rfid.color ? rfid.color.toLowerCase() : ''}" ${rowData.rfid_reading && rfid.id == rowData.rfid_reading.id ? 'selected' : ''}>${rfid.name}</option>`).join('')}</select></div>`;
                if (sensorsWorker && sensorsWorker.length > 0) modalHtmlEdit += `<div class="select-block"><label for="editSensorId">Sensor:</label><select id="editSensorId" class="swal2-input custom-select-style"><option value="">Seleccione Sensor Conteo</option>${sensorsWorker.map(sensor => `<option value="${sensor.id}" data-color="${sensor.color ? sensor.color.toLowerCase() : ''}" ${rowData.sensor && sensor.id == rowData.sensor.id ? 'selected' : ''}>${sensor.name}</option>`).join('')}</select></div>`;
                if (modbusesWorker && modbusesWorker.length > 0) modalHtmlEdit += `<div class="select-block"><label for="editModbusId">Báscula:</label><select id="editModbusId" class="swal2-input custom-select-style"><option value="">Seleccione una Báscula</option>${modbusesWorker.map(modbus => `<option value="${modbus.id}" data-color="${modbus.color ? modbus.color.toLowerCase() : ''}" ${rowData.modbus && modbus.id == rowData.modbus.id ? 'selected' : ''}>${modbus.name}</option>`).join('')}</select></div>`;

                Swal.fire({
                    title: 'Editar Relación', width: '80%', html: modalHtmlEdit,
                    showCancelButton: true, confirmButtonText: '<i class="bi bi-save"></i> Actualizar', cancelButtonText: '<i class="bi bi-x-square"></i> Cancelar',
                    customClass: { confirmButton: 'btn btn-success mx-1', cancelButton: 'btn btn-danger mx-1' }, buttonsStyling: false,
                    didOpen: () => {
                        stopRefreshIntervalWorker(); // **NUEVO**: Pausar refresco
                        $('#editOperatorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatOperatorOptionWorker, templateSelection: formatOperatorSelectionWorker });
                        if (rfidsWorker && rfidsWorker.length > 0) { $('#editRfidId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione RFID', templateResult: formatOptionWorker, templateSelection: formatSelectionWorker }); }
                        if (sensorsWorker && sensorsWorker.length > 0) { $('#editSensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Sensor', templateResult: formatOptionWorker, templateSelection: formatSelectionWorker }); }
                        if (modbusesWorker && modbusesWorker.length > 0) { $('#editModbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: 'Seleccione Báscula', templateResult: formatOptionWorker, templateSelection: formatSelectionWorker }); }

                        // Lógica para deshabilitar otros selects
                        const $editRfidSelect = $('#editRfidId'); const $editSensorSelect = $('#editSensorId'); const $editModbusSelect = $('#editModbusId');
                        if ($editRfidSelect.length) $editRfidSelect.on('change', function() { const d = !!$(this).val(); if ($editSensorSelect.length) $editSensorSelect.prop('disabled', d).trigger('change.select2'); if ($editModbusSelect.length) $editModbusSelect.prop('disabled', d).trigger('change.select2'); });
                        if ($editSensorSelect.length) $editSensorSelect.on('change', function() { const d = !!$(this).val(); if ($editRfidSelect.length) $editRfidSelect.prop('disabled', d).trigger('change.select2'); if ($editModbusSelect.length) $editModbusSelect.prop('disabled', d).trigger('change.select2'); });
                        if ($editModbusSelect.length) $editModbusSelect.on('change', function() { const d = !!$(this).val(); if ($editRfidSelect.length) $editRfidSelect.prop('disabled', d).trigger('change.select2'); if ($editSensorSelect.length) $editSensorSelect.prop('disabled', d).trigger('change.select2'); });
                        // Disparar change inicial para aplicar lógica de deshabilitado
                        if ($editRfidSelect.length) $editRfidSelect.trigger('change');
                        if ($editSensorSelect.length) $editSensorSelect.trigger('change');
                        if ($editModbusSelect.length) $editModbusSelect.trigger('change');
                    },
                    preConfirm: () => {
                        const operator_id = $('#editOperatorId').val(); const rfid_reading_id = $('#editRfidId').val(); const sensor_id = $('#editSensorId').val(); const modbus_id = $('#editModbusId').val();
                        if (!operator_id) { Swal.showValidationMessage('Debe seleccionar un operador.'); return false; }
                        return { operator_id: parseInt(operator_id), rfid_reading_id: rfid_reading_id ? parseInt(rfid_reading_id) : null, sensor_id: sensor_id ? parseInt(sensor_id) : null, modbus_id: modbus_id ? parseInt(modbus_id) : null };
                    },
                    didClose: () => {
                        startRefreshIntervalWorker(); // **NUEVO**: Reanudar refresco
                    }
                }).then((result) => {
                    if (result.isConfirmed && result.value) {
                        $.ajax({
                            url: updateUrl, method: 'PUT', contentType: 'application/json', data: JSON.stringify(result.value),
                            success: function(response) { Swal.fire('Éxito', response.message || 'Relación actualizada.', 'success'); tableWorker.ajax.reload(null, false); },
                            error: function(xhr) { let errorMsg = xhr.responseJSON?.message || 'Error al actualizar.'; /* Swal.fire('Error', errorMsg, 'error'); */ }
                        });
                    }
                });
            });

             // **NUEVO**: Event listener para el botón flotante de refrescar
             $('#refreshWorkerPostTableBtn').on('click', function() {
                 console.log('Refrescando tabla Worker-Post manualmente...');
                 if (tableWorker) {
                     tableWorker.ajax.reload(null, false); // Recarga sin resetear paginación
                     const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
                     toastMixin.fire({ icon: 'info', title: 'Tabla actualizada' });
                 }
             });

            // Limpiar el intervalo cuando la página se descarga
            $(window).on('unload', function() {
                stopRefreshIntervalWorker();
            });

        }); // Fin $(document).ready()
    </script>
@endpush
