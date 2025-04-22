@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Listado de asignacion')

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
        {{-- Contenido del header si es necesario --}}
    </div>
    <div class="card-body">
        <div class="table-responsive p-3">
            <table id="relationsTable" class="display table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Confección</th>
                        <th>Puesto</th>
                        <th>Operario</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Báscula</th>
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
    {{-- Links CSS --}}
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
            font-family: "Font Awesome 6 Free"; /* Actualizado a FA6 */
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
            grid-template-columns: 1fr auto; /* Texto a la izquierda, icono a la derecha */
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
            color: #007bff; /* Color base por defecto */
        }
        /* --- MODIFICADO: Tamaño de fuente para el número --- */
        .rfid-option-card .rfid-text {
            font-size: 1.1em; /* Aumentado el tamaño */
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
        /* Estilo específico para el texto dentro del tag */
        .select2-container--default .select2-selection--multiple .select2-selection__choice .rfid-selected-text {
            color: black !important;
        }
        /* Estilo para el icono dentro del tag */
        .select2-container--default .select2-selection--multiple .select2-selection__choice .fa-id-card {
             margin-right: 0;
        }

        /* Estilo para el botón 'X' de quitar selección */
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

        /* --- MODIFICADO: Layout de columnas auto-adaptable --- */
        .select2-container--default .select2-results__options {
            display: grid; /* Usar Grid */
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Columnas automáticas */
            gap: 8px; /* Espacio entre tarjetas */
            padding: 5px;
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
        /* --- FIN MODIFICACIÓN --- */


        /* Botones personalizados */
        .btn-custom-success { background-color: green !important; border-color: green !important; color: #fff !important; }
        .btn-custom-danger { background-color: #dc3545 !important; border-color: #dc3545 !important; color: #fff !important; }


        /* Iconos botones Swal */
        .swal2-confirm i, .swal2-deny i, .swal2-cancel i {
            vertical-align: middle;
            margin-right: 0.3em;
        }
        /* Orden botones Swal */
        .swal2-actions { display: flex; flex-direction: row; gap: 10px; }
        .swal2-confirm { order: 1; } .swal2-cancel { order: 2; } .swal2-deny { order: 3; }

        /* Responsividad botones Swal */
        @media (max-width: 576px) {
            .swal2-actions { flex-direction: column; }
        }

        /* Estilo tabla DataTable */
        #relationsTable { border-collapse: collapse; }
        #relationsTable th, #relationsTable td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        #relationsTable th { background-color: #f2f2f2; }
        #relationsTable .rfid-option-card { border: none; padding: 0; margin: 0; gap: 5px; grid-template-columns: 1fr auto; }
        #relationsTable .rfid-option-card .rfid-text { font-size: inherit; font-weight: normal; text-align: left; }
        #relationsTable .rfid-option-card .rfid-icon { font-size: 1.2em; }
        /* Input readonly edición */
        #relationId { background-color: #e9ecef; cursor: not-allowed; }
        /* Etiquetas modal */
        .swal2-html-container label { display: block; margin-top: 0.8em; margin-bottom: 0.2em; font-weight: bold; text-align: left; margin-left: 6%; }
        @media (max-width: 576px) { .swal2-html-container label { margin-left: 0; } }
    </style>
@endpush

@push('scripts')
    {{-- Links JS --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        // Variable global para guardar el color del primer RFID seleccionado
        let selectedRfidColor = null;

        // Función matcher para filtrar opciones por color y texto
        function rfidMatcher(params, data) {
            if (!data.element) { return data; }
            const optionColor = $(data.element).data('color');
            if (selectedRfidColor && optionColor && optionColor.toLowerCase() !== selectedRfidColor.toLowerCase()) {
                return null;
            }
            if ($.trim(params.term) === '') { return data; }
            if (typeof data.text === 'undefined' || data.text.toLowerCase().indexOf(params.term.toLowerCase()) === -1) {
                return null;
            }
            return data;
        }

        // Función sorter para ordenar numéricamente las opciones del dropdown RFID
        function numericSorter(data) {
            return data.sort(function(a, b) {
                const numA = parseInt(a.text, 10);
                const numB = parseInt(b.text, 10);
                if (!isNaN(numA) && !isNaN(numB)) { return numA - numB; }
                return a.text.localeCompare(b.text);
            });
        }

        // Rutas API
        const relationsApiUrl = '/api/product-list-selecteds';
        const productsApiUrl  = '/api/product-lists/list-all';
        const rfidsApiUrl     = '/api/rfid-readings';
        const modbusesApiUrl  = '/api/product-list-selecteds/modbuses';
        const sensorsApiUrl   = '/api/product-list-selecteds/sensors';

        // Variables globales para opciones
        let productListOptions = '';
        let rfidOptions = '';
        let modbusOptions = '';
        let sensorOptions = '';
        let table;

        // --- Funciones de formato para Select2 ---
        function formatOption(option) { // Opciones dropdown
            if (!option.id || !option.element) return option.text;
            let color = option.element.getAttribute('data-color') || '';
            let text = option.text;
            let colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
            let iconColor = colorMap[color.toLowerCase()] || "#6c757d";
            return $(`<div class="rfid-option-card"><div class="rfid-text">${text}</div><div class="rfid-icon" style="color: ${iconColor};"><i class="fas fa-id-card"></i></div></div>`);
        }
        function formatSelection(option) { return option.text; } // Selección Modbus/Sensor
        function formatProductOption(option) { // Opción Producto dropdown
            if (!option.id) return option.text;
            return $(`<div class="rfid-option-card"><div class="rfid-text">${option.text}</div><div class="rfid-icon" style="color: #17a2b8;"><i class="fas fa-box"></i></div></div>`);
        }
        function formatProductSelection(option) { // Selección Producto
             if (!option.id) return option.text;
             return $(`<span><i class="fas fa-box" style="color: #17a2b8; margin-right: 5px;"></i>${option.text}</span>`);
        }
        function formatRfidSelection(option) { // Selección RFID (tag)
            if (!option.id || !option.element) return option.text;
            let color = option.element.getAttribute('data-color') || '';
            let text = option.text;
            let colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
            let iconColor = colorMap[color.toLowerCase()] || "#6c757d";
            return $(`<span><i class="fas fa-id-card" style="color: ${iconColor};"></i> <span class="rfid-selected-text">${text}</span></span>`);
        }

        // Función para cargar opciones de los selects vía AJAX
        function loadSelectOptions() {
            const productsPromise = $.get(productsApiUrl).done(data => {
                productListOptions = data.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
            }).fail(() => console.error('Error al cargar productos.'));

            const rfidsPromise = $.get(rfidsApiUrl).done(data => {
                rfidOptions = data.map(r => `<option value="${r.id}" data-color="${r.rfid_color?.name?.toLowerCase() || ''}">${r.name}</option>`).join('');
            }).fail(() => console.error('Error al cargar RFIDs.'));

            const modbusesPromise = $.get(modbusesApiUrl).done(data => {
                modbusOptions = data.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
            }).fail(() => console.error('Error al cargar básculas.'));

            const sensorsPromise = $.get(sensorsApiUrl).done(data => {
                sensorOptions = data.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
            }).fail(() => console.error('Error al cargar sensores.'));

            return $.when(productsPromise, rfidsPromise, modbusesPromise, sensorsPromise);
        }

        // --- Document Ready ---
        $(document).ready(function() {
            loadSelectOptions().always(function() {
                table = $('#relationsTable').DataTable({
                    responsive: true,
                    pageLength: 20,
                    lengthMenu: [ [10, 20, 50, -1], [10, 20, 50, "All"] ],
                    ajax: {
                        url: relationsApiUrl,
                        dataSrc: '',
                        error: function(xhr) {
                            let errorMsg = 'Error desconocido al cargar datos.';
                            if (xhr.responseJSON?.message) { 
                                errorMsg = xhr.responseJSON.message; 
                            } else if (xhr.responseText) { 
                                try { 
                                    errorMsg = JSON.parse(xhr.responseText).message || xhr.responseText.substring(0,200); 
                                } catch(e){ 
                                    errorMsg = xhr.responseText.substring(0,200); 
                                }
                            }
                            console.error("Error AJAX DataTable:", xhr);
                            Swal.fire({ icon: 'error', title: 'Error al Cargar Datos', text: errorMsg });
                        }
                    },
                    columns: [
                        { data: 'product_list.name', defaultContent: 'Sin asignar' },
                        { data: 'rfid_reading', render: function(data) {
                                if (data?.name && data?.rfid_color?.name) {
                                    const colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
                                    const colorName = data.rfid_color.name.toLowerCase();
                                    const iconColor = colorMap[colorName] || "#6c757d";
                                    return `<div class="rfid-option-card"><div class="rfid-text">${data.name}</div><div class="rfid-icon" style="color: ${iconColor};"><i class="fas fa-id-card"></i></div></div>`;
                                }
                                return 'Sin asignar';
                            },
                          defaultContent: 'Sin asignar'
                        },
                        { data: 'operator_name', name: 'operator_name', defaultContent: 'Sin asignar' },
                        { data: 'created_at', render: data => data ? new Date(data).toLocaleString('es-ES') : 'N/A' },
                        { data: 'finish_at', render: data => !data ? 'En curso' : new Date(data).toLocaleString('es-ES') },
                        { data: 'modbus.name', defaultContent: 'N/A', render: (d, t, r) => r.modbus?.name || 'N/A' },
                        { data: 'sensor.name', defaultContent: 'N/A', render: (d, t, r) => r.sensor?.name || 'N/A' },
                        { 
                            data: null, orderable: false, searchable: false,
                            render: function(data, type, row) {
                                return `
                                    @can('rfid-post-edit')
                                        <button class="btn btn-sm btn-secondary edit-btn"
                                                data-id="${row.id}"
                                                data-product_list_id="${row.product_list_id || ''}"
                                                data-rfid_reading_id="${row.rfid_reading_id || ''}"
                                                data-modbus_id="${row.modbus_id || ''}"
                                                data-sensor_id="${row.sensor_id || ''}"
                                                style="margin-right: 5px;">
                                            Editar
                                        </button>
                                    @endcan
                                    @can('rfid-post-delete')
                                        <button class="btn btn-sm btn-danger delete-btn"
                                                data-id="${row.id}">
                                            Eliminar
                                        </button>
                                    @endcan
                                `;
                            }
                        }
                    ],
                    dom: 'Bfrtip',
                    buttons: [
                         {
                            text: '<i class="bi bi-plus-circle"></i> Asignar Confección',
                            className: 'btn btn-primary mb-2',
                            action: function(e, dt, node, config) {
                                let modalHtml = `<div class="select-block"><label for="productListId">Producto:</label><select id="productListId" class="swal2-input custom-select-style"><option value="" disabled selected>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                                if (rfidOptions.trim() !== '') {
                                    modalHtml += `<div class="select-block"><label for="rfidReadingId">RFID:</label><select id="rfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select></div>`;
                                }
                                if (modbusOptions.trim() !== '') {
                                    modalHtml += `<div class="select-block"><label for="modbusId">Báscula:</label><select id="modbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`;
                                }
                                if (sensorOptions.trim() !== '') {
                                    modalHtml += `<div class="select-block"><label for="sensorId">Sensor:</label><select id="sensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`;
                                }
                                modalHtml += `<div id="qr-reader" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                                Swal.fire({
                                    title: 'Asignar Confección', width: '80%', html: modalHtml,
                                    showCancelButton: true, showDenyButton: true,
                                    confirmButtonText: '<i class="bi bi-check-square"></i> AÑADIR',
                                    cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR',
                                    denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR',
                                    customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' },
                                    buttonsStyling: false,
                                    preDeny: () => {
                                        if (rfidOptions.trim() !== '') {
                                            startQrScanner('rfidReadingId');
                                        } else {
                                            Swal.showValidationMessage('No hay RFIDs disponibles para escanear.');
                                        }
                                        return false;
                                     },
                                    didOpen: () => {
                                        $('#productListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection });
                                        if (rfidOptions.trim() !== '') {
                                            $('#rfidReadingId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione RFID --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter });
                                            $('#rfidReadingId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); });
                                            $('#rfidReadingId').on('change', function() {
                                                let selectedValues = $(this).val();
                                                if (selectedValues && selectedValues.length > 0) {
                                                    if (selectedRfidColor === null) {
                                                        let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`);
                                                        selectedRfidColor = firstSelectedOption.data('color');
                                                    }
                                                } else {
                                                    selectedRfidColor = null;
                                                }
                                                if ($(this).data('select2')?.isOpen()) {
                                                    $(this).select2('close').select2('open');
                                                }
                                            });
                                        }
                                        if (modbusOptions.trim() !== '') {
                                            $('#modbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection });
                                            $('#modbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); });
                                        }
                                        if (sensorOptions.trim() !== '') {
                                            $('#sensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection });
                                            $('#sensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); });
                                        }
                                        $('#productListId').val(null).trigger('change');
                                    },
                                    preConfirm: () => {
                                        const pId = $('#productListId').val();
                                        const rIds = rfidOptions.trim() !== '' ? ($('#rfidReadingId').val() || []) : [];
                                        const mIds = modbusOptions.trim() !== '' ? ($('#modbusId').val() || []) : [];
                                        const sIds = sensorOptions.trim() !== '' ? ($('#sensorId').val() || []) : [];
                                        if (!pId) { 
                                            Swal.showValidationMessage('Producto obligatorio.');
                                            $('#productListId').select2('open');
                                            return false;
                                        }
                                        if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) {
                                            Swal.showValidationMessage('Seleccione RFID, Báscula o Sensor.');
                                            if (rfidOptions.trim() !== '')
                                                $('#rfidReadingId').select2('open');
                                            else if (modbusOptions.trim() !== '')
                                                $('#modbusId').select2('open');
                                            else if (sensorOptions.trim() !== '')
                                                $('#sensorId').select2('open');
                                            return false;
                                        }
                                        return { 
                                            client_id:       parseInt(pId),                             // renombrado
                                            rfid_reading_ids: rIds.length  ? rIds.map(id => +id) : [],  // siempre array
                                            modbus_ids:       mIds.length  ? mIds.map(id => +id) : [],  
                                            sensor_ids:       sIds.length  ? sIds.map(id => +id) : []
                                        };

                                    },
                                    didClose: () => {
                                        console.log('Modal Asignar cerrado');
                                        selectedRfidColor = null;
                                        console.log('Borrado color seleccionado');
                                        stopQrScanner();
                                        console.log('Desactivado scanner');
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Antes de la llamada, loguea URL y payload:
                                        console.log("URL a la que llama:", relationsApiUrl);
                                        console.log("JSON que envía:", result.value);
                                        $.ajax({
                                            url: relationsApiUrl,
                                            method: 'POST',
                                            contentType: 'application/json',
                                            data: JSON.stringify(result.value),
                                            success: function(response) {
                                                console.log('Modal Asignar cerrado');
                                                selectedRfidColor = null;
                                                console.log('Borrado color seleccionado');
                                                stopQrScanner();
                                                console.log('Desactivado scanner');
                                                Swal.fire({ title: 'Éxito', text: response.message || 'Relación añadida.', icon: 'success', timer: 2000, showConfirmButton: false });
                                                table.ajax.reload();
                                            },
                                            error: function(xhr) {
                                                console.log('Modal Asignar cerrado');
                                                selectedRfidColor = null;
                                                console.log('Borrado color seleccionado');
                                                stopQrScanner();
                                                console.log('Desactivado scanner');
                                                let errorMsg = 'Error al añadir.';
                                                if (xhr.responseJSON?.message)
                                                    errorMsg = xhr.responseJSON.message;
                                                console.error("Error POST:", xhr);
                                                Swal.fire('Error', errorMsg, 'error');
                                            }
                                        });
                                    } else {
                                        console.log('Modal Asignar cerrado');
                                        selectedRfidColor = null;
                                        console.log('Borrado color seleccionado');
                                        stopQrScanner();
                                        console.log('Desactivado scanner');
                                    }
                                });
                            }
                         },
                         { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Exportar a Excel', className: 'btn btn-success mb-2', titleAttr: 'Exportar tabla a Excel' },
                         { text: '<i class="bi bi-broadcast"></i> Live Rfid', className: 'btn btn-info mb-2', action: function () { window.open('/live-rfid/', '_blank'); }, titleAttr: 'Ver lecturas RFID en tiempo real' }
                    ],
                    order: [[4, 'desc'], [2, 'asc'], [1, 'asc']],
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json', search: "_INPUT_", searchPlaceholder: "Buscar..." },
                    initComplete: function() {
                        $('.dataTables_filter input').addClass('form-control form-control-sm');
                        $('.dt-buttons').addClass('mb-2');
                    }
                });

                // --- Delegación de eventos ---
                // Editar Relación
                $('#relationsTable tbody').on('click', '.edit-btn', function() {
                    const $button = $(this);
                    const currentId = $button.data('id');
                    const getIdsArray = (data) => (data || '').toString().split(',').map(id => id.trim()).filter(Boolean);
                    const currentProductListId = $button.data('product_list_id');
                    const currentRfidReadingIds = getIdsArray($button.data('rfid_reading_id'));
                    const currentModbusIds = getIdsArray($button.data('modbus_id'));
                    const currentSensorIds = getIdsArray($button.data('sensor_id'));

                    let modalHtmlEdit = `<input type="hidden" id="editRelationId" value="${currentId}">`;
                    modalHtmlEdit += `<div class="select-block"><label for="editProductListId">Producto:</label><select id="editProductListId" class="swal2-input custom-select-style"><option value="" disabled>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                    if (rfidOptions.trim() !== '') {
                        modalHtmlEdit += `<div class="select-block"><label for="editRfidReadingId">RFID:</label><select id="editRfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select></div>`;
                    }
                    if (modbusOptions.trim() !== '') {
                        modalHtmlEdit += `<div class="select-block"><label for="editModbusId">Báscula:</label><select id="editModbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`;
                    }
                    if (sensorOptions.trim() !== '') {
                        modalHtmlEdit += `<div class="select-block"><label for="editSensorId">Sensor:</label><select id="editSensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`;
                    }
                    modalHtmlEdit += `<div id="qr-reader-edit" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                    Swal.fire({
                        title: 'Editar Relación', width: '80%', html: modalHtmlEdit,
                        showCancelButton: true, showDenyButton: true,
                        confirmButtonText: '<i class="bi bi-save"></i> ACTUALIZAR',
                        cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR',
                        denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR',
                        customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' },
                        buttonsStyling: false,
                        preDeny: () => {
                            if (rfidOptions.trim() !== '') {
                                startQrScanner('editRfidReadingId');
                            } else {
                                Swal.showValidationMessage('No hay RFIDs disponibles para escanear.');
                            }
                            return false;
                         },
                        didOpen: () => {
                            $('#editProductListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection }).val(currentProductListId).trigger('change');
                            if (rfidOptions.trim() !== '') {
                                $('#editRfidReadingId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione RFID --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter }).val(currentRfidReadingIds).trigger('change');
                                $('#editRfidReadingId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); });
                                $('#editRfidReadingId').on('change', function() {
                                    let selectedValues = $(this).val();
                                    if (selectedValues && selectedValues.length > 0) {
                                        let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`);
                                        selectedRfidColor = firstSelectedOption.data('color');
                                    } else {
                                        selectedRfidColor = null;
                                    }
                                    if ($(this).data('select2')?.isOpen()) {
                                        $(this).select2('close').select2('open');
                                    }
                                });
                                $('#editRfidReadingId').trigger('change');
                            }
                            if (modbusOptions.trim() !== '') {
                                $('#editModbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentModbusIds).trigger('change');
                                $('#editModbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); });
                            }
                            if (sensorOptions.trim() !== '') {
                                $('#editSensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentSensorIds).trigger('change');
                                $('#editSensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); });
                            }
                        },
                        preConfirm: () => {
                            const pId = $('#editProductListId').val();
                            const rIds = rfidOptions.trim() !== '' ? ($('#editRfidReadingId').val() || []) : [];
                            const mIds = modbusOptions.trim() !== '' ? ($('#editModbusId').val() || []) : [];
                            const sIds = sensorOptions.trim() !== '' ? ($('#editSensorId').val() || []) : [];
                            if (!pId) {
                                Swal.showValidationMessage('Producto obligatorio.');
                                $('#editProductListId').select2('open');
                                return false;
                            }
                            if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) {
                                Swal.showValidationMessage('Seleccione RFID, Báscula o Sensor.');
                                if (rfidOptions.trim() !== '')
                                    $('#editRfidReadingId').select2('open');
                                else if (modbusOptions.trim() !== '')
                                    $('#editModbusId').select2('open');
                                else if (sensorOptions.trim() !== '')
                                    $('#editSensorId').select2('open');
                                return false;
                            }
                            return {
                                product_list_id: parseInt(pId),
                                rfid_reading_ids: rIds.map(id => parseInt(id)),
                                modbus_ids: mIds.map(id => parseInt(id)),
                                sensor_ids: sIds.map(id => parseInt(id))
                            };
                        },
                        didClose: () => {
                            selectedRfidColor = null;
                            stopQrScanner();
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const relationId = $('#editRelationId').val();
                            $.ajax({
                                url: `${relationsApiUrl}/${relationId}`,
                                method: 'PUT',
                                contentType: 'application/json',
                                data: JSON.stringify(result.value),
                                success: function(response) {
                                    Swal.fire({ title: 'Éxito', text: response.message || 'Relación actualizada.', icon: 'success', timer: 2000, showConfirmButton: false });
                                    table.ajax.reload();
                                },
                                error: function(xhr) {
                                    let errorMsg = 'Error al actualizar.';
                                    if (xhr.responseJSON?.message)
                                        errorMsg = xhr.responseJSON.message;
                                    console.error("Error PUT:", xhr);
                                   // Swal.fire('Error', errorMsg, 'error');
                                }
                            });
                        } else {
                            console.log('Modal Editar cerrado');
                        }
                    });
                });

                // Eliminar Relación
                $('#relationsTable tbody').on('click', '.delete-btn', function() {
                    const id = $(this).data('id');
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "¡Esta acción no se puede deshacer!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar',
                        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                        customClass: { confirmButton: 'btn btn-danger mx-1', cancelButton: 'btn btn-secondary mx-1' },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `${relationsApiUrl}/${id}`,
                                method: 'DELETE',
                                success: function(response) {
                                    Swal.fire({ title: 'Eliminado', text: response.message || 'Relación eliminada.', icon: 'success', timer: 2000, showConfirmButton: false });
                                    table.ajax.reload();
                                },
                                error: function(xhr) {
                                    let errorMsg = 'Error al eliminar.';
                                    if (xhr.responseJSON?.message)
                                        errorMsg = xhr.responseJSON.message;
                                    console.error("Error DELETE:", xhr);
                                    Swal.fire('Error', errorMsg, 'error');
                                }
                            });
                        }
                    });
                });
            });
        });

        // --- Funciones Escáner QR ---
        let html5QrCode = null; // Instancia del escáner

        function startQrScanner(targetSelectId) {
            const qrReaderId = targetSelectId.startsWith('edit') ? 'qr-reader-edit' : 'qr-reader';
            const qrReaderElement = document.getElementById(qrReaderId);
            if (!qrReaderElement) { console.error(`Elemento ${qrReaderId} no encontrado.`); return; }
            if (html5QrCode?.isScanning) { console.warn("El escáner ya está activo."); return; }
            qrReaderElement.style.display = 'block';
            if (!html5QrCode) { html5QrCode = new Html5Qrcode(qrReaderId); }
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timerProgressBar: true });
            html5QrCode.start( { facingMode: "environment" }, config,
                (decodedText, decodedResult) => {
                    console.log(`QR detectado: ${decodedText}`);
                    stopQrScanner();
                    const $targetSelect = $(`#${targetSelectId}`);
                    const $option = $targetSelect.find('option').filter((i, opt) => $(opt).text().trim() === decodedText.trim());
                    if ($option.length > 0) {
                        const optionValue = $option.val();
                        let currentValues = $targetSelect.val() || [];
                        if (!Array.isArray(currentValues)) { currentValues = [currentValues]; }
                        if (!currentValues.includes(optionValue)) {
                            currentValues.push(optionValue);
                            $targetSelect.val(currentValues).trigger('change');
                            toastMixin.fire({ icon: 'success', title: `RFID ${decodedText} añadido`, timer: 1500 });
                        } else {
                            toastMixin.fire({ icon: 'info', title: `RFID ${decodedText} ya seleccionado`, timer: 1500 });
                        }
                    } else {
                        toastMixin.fire({ icon: 'warning', title: `RFID ${decodedText} no encontrado`, timer: 2000 });
                    }
                },
                (errorMessage) => { /* Ignorar error continuo */ }
            ).catch((err) => {
                console.error("Error al iniciar el escáner QR:", err);
                Swal.fire('Error', 'No se pudo iniciar el escáner QR. Verifica los permisos de la cámara.', 'error');
                qrReaderElement.style.display = 'none';
                html5QrCode = null;
            });
        }

        function stopQrScanner() {
            const qrReaderEdit = document.getElementById('qr-reader-edit');
            const qrReaderAdd = document.getElementById('qr-reader');
            if (html5QrCode?.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("Escáner QR detenido.");
                    if (qrReaderAdd) qrReaderAdd.style.display = 'none';
                    if (qrReaderEdit) qrReaderEdit.style.display = 'none';
                }).catch(err => {
                    console.error("Error al detener el escáner QR:", err);
                    if (qrReaderAdd) qrReaderAdd.style.display = 'none';
                    if (qrReaderEdit) qrReaderEdit.style.display = 'none';
                });
            } else {
                if (qrReaderAdd) qrReaderAdd.style.display = 'none';
                if (qrReaderEdit) qrReaderEdit.style.display = 'none';
            }
        }
    </script>
@endpush
