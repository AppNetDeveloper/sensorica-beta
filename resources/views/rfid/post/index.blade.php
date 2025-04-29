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
        {{-- Contenedor para controles adicionales (Switches) --}}
        {{-- Este div se moverá con JS a la posición correcta --}}
        <div class="switches-container d-flex justify-content-end flex-wrap mb-2" style="display: none;"> {{-- Oculto inicialmente --}}
             {{-- Switch para filtrar (ocultar) no asignados (Operario, Bascula, Sensor) --}}
             <div class="form-check form-switch me-3">
               <input class="form-check-input" type="checkbox" role="switch" id="showUnassignedSwitch">
               <label class="form-check-label" for="showUnassignedSwitch">Ocultar sin asignar</label> {{-- Etiqueta actualizada --}}
             </div>
             {{-- Switch existente para historial --}}
             <div class="form-check form-switch">
               <input class="form-check-input" type="checkbox" role="switch" id="showHistorySwitch">
               <label class="form-check-label" for="showHistorySwitch">Mostrar historial</label>
             </div>
        </div>
        <div class="table-responsive p-3">
            <table id="relationsTable" class="display table table-striped table-bordered" style="width:100%">
                {{-- Orden de las columnas en la cabecera --}}
                <thead>
                    <tr>
                        <th>Confección</th>  <th>Puesto</th>      <th>Báscula</th>     <th>Sensor</th>      <th>Operario</th>    <th>Fecha Inicio</th><th>Fecha Fin</th>   <th>Acciones</th>    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Botón flotante para refrescar --}}
<button id="refreshTableBtn" class="btn btn-primary btn-float" title="Refrescar Tabla">
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
        /* Estilo personalizado para los selects */
        .custom-select-style {
            width: 88%;
            min-height: 45px;
            line-height: normal;
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
        @media (max-width: 767px) { /* Ajustado breakpoint para mejor responsividad */
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
                flex-basis: 100%;
                text-align: left;
                margin-bottom: 0.5em;
            }
             .top-controls-row .switch-placeholder {
                 justify-content: flex-start !important;
                 padding-left: 0;
             }
             .dt-buttons .btn {
                 margin-right: 5px;
                 margin-bottom: 5px;
             }
             /* Ajustar filtro y length menu en móviles */
             .filter-length-row .col-md-6 {
                 width: 100%;
                 text-align: left;
                 margin-bottom: 0.5em;
             }
             .dataTables_filter {
                 float: none !important;
             }
             .dataTables_length {
                 float: none !important;
             }
             /* Ajustar botón flotante en móviles */
             .btn-float {
                bottom: 15px;
                right: 15px;
                width: 45px;
                height: 45px;
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
            grid-template-columns: 1fr auto;
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

        /* Layout de columnas auto-adaptable */
        .select2-container--default .select2-results__options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px;
            padding: 5px;
            max-height: 30vh !important;
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

        /* Estilos para el botón de visibilidad de columnas */
        .dt-button.buttons-collection {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: #fff !important;
        }
        .dt-button.buttons-columnVisibility.active {
            background-color: #5a6268 !important;
            border-color: #545b62 !important;
        }
        .dt-button-collection .dt-button {
            min-width: 150px;
        }
        /* Estilo para el contenedor del length menu, filtro y controles personalizados */
        .dataTables_wrapper .top-controls-row { margin-bottom: 1em; }
        .dataTables_wrapper .filter-length-row { margin-bottom: 1em; }

        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
             margin-right: 0.5em;
             margin-bottom: 0;
        }
         .dataTables_wrapper .dataTables_length select {
             width: auto;
             display: inline-block;
             padding: 0.375rem 1.75rem 0.375rem 0.75rem;
             border: 1px solid #ced4da;
             border-radius: 0.25rem;
             vertical-align: middle;
         }
         /* Ancho fijo al input de búsqueda */
         .dataTables_filter input.form-control {
             width: 300px !important;
             display: inline-block !important;
             vertical-align: middle;
         }
         /* Estilo para el switch */
         .form-switch .form-check-input {
             cursor: pointer;
             width: 3em;
             height: 1.5em;
             margin-left: -0.5em;
         }
         .form-switch .form-check-label {
             padding-left: 1em;
             vertical-align: middle;
         }
         /* Alineación de botones y switch */
         .dt-buttons .btn { margin-right: 0.25em;}
         .switch-placeholder {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            flex-wrap: wrap;
         }

         /* Estilos para el botón flotante */
         .btn-float {
            position: fixed;
            width: 50px;
            height: 50px;
            bottom: 30px;
            right: 30px;
            border-radius: 50%;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1050;
            font-size: 1.5rem;
         }

         /* Estilo para el contador RFID */
         .rfid-counter-display {
             font-size: 1.6em;
             color: #6c757d;
             margin-bottom: 1em;
             text-align: center;
             height: 1em;
             display: block;
             width: 100%;
         }
         /* Contenedor para el contador (opcional, para mejor control) */
         .modal-counter-container {
             margin-bottom: 1em;
             padding-top: 0.5em;
         }

    </style>
@endpush

@push('scripts')
    {{-- Links JS específicos para esta vista --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>


    <script>
        // --- Definir permisos en JavaScript (Método seguro) ---
        const canEditRfidPost = {{ auth()->check() && auth()->user()->can('rfid-post-edit') ? 'true' : 'false' }};
        const canDeleteRfidPost = {{ auth()->check() && auth()->user()->can('rfid-post-delete') ? 'true' : 'false' }};


        // Variable global para guardar el color del primer RFID seleccionado
        let selectedRfidColor = null;
        // Variable para el intervalo de actualización
        let refreshIntervalId = null;
        const REFRESH_INTERVAL = 3000; // 3 segundos en milisegundos

        // Función para iniciar el intervalo de refresco
        function startRefreshInterval() {
            if (refreshIntervalId) { clearInterval(refreshIntervalId); }
            refreshIntervalId = setInterval(function() {
                if (table && $.fn.DataTable.isDataTable('#relationsTable')) { // Comprobar si la tabla existe
                    console.log('Actualizando tabla automáticamente...');
                    let pageInfo = table.page.info();
                    let searchVal = table.search();
                    // Guardar estado de los switches antes de recargar
                    const historyChecked = $('#showHistorySwitch').is(':checked');
                    const unassignedChecked = $('#showUnassignedSwitch').is(':checked');
                    table.ajax.reload(function() {
                         if ($.fn.DataTable.isDataTable('#relationsTable')) { // Volver a comprobar por si se destruyó mientras tanto
                            // Restaurar estado de los switches visualmente (aunque el filtro se aplica por JS)
                            $('#showHistorySwitch').prop('checked', historyChecked);
                            $('#showUnassignedSwitch').prop('checked', unassignedChecked);
                            // Aplicar búsqueda y paginación
                            table.search(searchVal).page(pageInfo.page).draw('page');
                         }
                    }, false); // false para no resetear paginación
                }
            }, REFRESH_INTERVAL);
            console.log('Intervalo de refresco iniciado.');
        }

        // Función para detener el intervalo de refresco
        function stopRefreshInterval() {
            if (refreshIntervalId) {
                clearInterval(refreshIntervalId);
                refreshIntervalId = null;
                console.log('Intervalo de refresco detenido.');
            }
        }


        // Función matcher para filtrar opciones por color y texto
        function rfidMatcher(params, data) {
            if (!data.element) { return data; }
            const optionColor = $(data.element).data('color');
            if (selectedRfidColor && optionColor && optionColor.toLowerCase() !== selectedRfidColor.toLowerCase()) { return null; }
            if ($.trim(params.term) === '') { return data; }
            if (typeof data.text === 'undefined' || data.text.toLowerCase().indexOf(params.term.toLowerCase()) === -1) { return null; }
            return data;
        }

        // Función sorter para ordenar numéricamente las opciones del dropdown RFID
        function numericSorter(data) {
            return data.sort(function(a, b) {
                const numA = parseInt(a.text, 10); const numB = parseInt(b.text, 10);
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

        // Variables globales para opciones y tabla
        let productListOptions = ''; let rfidOptions = ''; let modbusOptions = ''; let sensorOptions = '';
        let table;

        // --- Funciones de formato para Select2 ---
        function formatOption(option) {
            if (!option.id || !option.element) return option.text;
            let color = option.element.getAttribute('data-color') || ''; let text = option.text;
            let colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
            let iconColor = colorMap[color.toLowerCase()] || "#6c757d"; let iconClass = "fas fa-id-card";
            return $(`<div class="rfid-option-card"><div class="rfid-text">${text}</div><div class="rfid-icon" style="color: ${iconColor};"><i class="${iconClass}"></i></div></div>`);
        }
        function formatSelection(option) { return option.text; }
        function formatProductOption(option) {
            if (!option.id) return option.text;
            return $(`<div class="rfid-option-card"><div class="rfid-text">${option.text}</div><div class="rfid-icon" style="color: #17a2b8;"><i class="fas fa-box"></i></div></div>`);
        }
        function formatProductSelection(option) {
             if (!option.id) return option.text;
             return $(`<span><i class="fas fa-box" style="color: #17a2b8; margin-right: 5px;"></i>${option.text}</span>`);
        }
        function formatRfidSelection(option) {
            if (!option.id || !option.element) return option.text;
            let color = option.element.getAttribute('data-color') || ''; let text = option.text;
            let colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
            let iconColor = colorMap[color.toLowerCase()] || "#6c757d";
            return $(`<span><i class="fas fa-id-card" style="color: ${iconColor};"></i> <span class="rfid-selected-text">${text}</span></span>`);
        }

        // Función para cargar opciones de los selects vía AJAX
        function loadSelectOptions() {
            console.log("Cargando opciones para Select2...");
            const productsPromise = $.get(productsApiUrl).done(data => { productListOptions = data.map(p => `<option value="${p.id}">${p.name}</option>`).join(''); console.log("Productos cargados:", data.length); }).fail(() => console.error('Error al cargar productos.'));
            const rfidsPromise = $.get(rfidsApiUrl).done(data => { rfidOptions = data.map(r => `<option value="${r.id}" data-color="${r.rfid_color?.name?.toLowerCase() || ''}">${r.name}</option>`).join(''); console.log("RFIDs cargados:", data.length); }).fail(() => console.error('Error al cargar RFIDs.'));
            const modbusesPromise = $.get(modbusesApiUrl).done(data => { modbusOptions = data.map(m => `<option value="${m.id}">${m.name}</option>`).join(''); console.log("Básculas cargadas:", data.length); }).fail(() => console.error('Error al cargar básculas.'));
            const sensorsPromise = $.get(sensorsApiUrl).done(data => { sensorOptions = data.map(s => `<option value="${s.id}">${s.name}</option>`).join(''); console.log("Sensores cargados:", data.length); }).fail(() => console.error('Error al cargar sensores.'));
            return $.when(productsPromise, rfidsPromise, modbusesPromise, sensorsPromise);
        }

        // --- Document Ready ---
        $(document).ready(function() {
            console.log("Documento listo. Inicializando...");

            // Clave para localStorage del switch de ocultar no asignados
            const unassignedSwitchStorageKey = 'dataTableHideUnassignedState';

            // Leer estado inicial del switch desde localStorage
            // Antes:
            // const initialUnassignedState = localStorage.getItem(unassignedSwitchStorageKey) === 'true';

            // Ahora:
            // **MODIFICADO**: Leer estado inicial del switch desde localStorage, default a TRUE si no existe
            // Si el valor guardado NO es 'false', entonces será true (incluyendo el caso de null/undefined)
            const initialUnassignedState = localStorage.getItem(unassignedSwitchStorageKey) !== 'false';
            $('#showUnassignedSwitch').prop('checked', initialUnassignedState);
            console.log("Estado inicial 'Ocultar sin asignar':", initialUnassignedState);

            // Filtro personalizado para mostrar/ocultar historial
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex, rowData, counter ) {
                    if (settings.nTable.id !== 'relationsTable') { return true; }
                    const showHistory = $('#showHistorySwitch').is(':checked');
                    const finishDate = rowData.finish_at;
                    if (showHistory) { return true; }
                    else { return finishDate === null || finishDate === undefined || finishDate === ''; }
                }
            );

            // **MODIFICADO**: Filtro para OCULTAR filas donde Operario, Báscula Y Sensor no están asignados
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex, rowData, counter ) {
                    if (settings.nTable.id !== 'relationsTable') { return true; }
                    const hideUnassigned = $('#showUnassignedSwitch').is(':checked'); // El switch significa "ocultar"

                    // Si el switch está apagado, no aplicar este filtro
                    if (!hideUnassigned) {
                        return true;
                    }

                    // Si el switch está encendido (ocultar no asignados), verificar si ALGUNO tiene valor
                    // **CAMBIO**: Verificar Operario en lugar de RFID/Puesto
                    const hasOperator = rowData.operator_name && rowData.operator_name.trim() !== '' && rowData.operator_name !== 'Sin asignar'; // Considerar cadena vacía también como "sin asignar"
                    const hasModbus = rowData.modbus && rowData.modbus.id;
                    const hasSensor = rowData.sensor && rowData.sensor.id;

                    // Mostrar la fila SOLO si AL MENOS UNO (Operario, Báscula, Sensor) tiene valor.
                    // Si todos son falsy/sin asignar, esto devolverá false (ocultar fila).
                    return hasOperator || hasModbus || hasSensor;
                }
            );

            // Carga las opciones primero y luego inicializa DataTable
            loadSelectOptions().always(function() {
                console.log("Opciones cargadas. Inicializando DataTable...");
                table = $('#relationsTable').DataTable({
                    responsive: true,
                    pageLength: 300,
                    lengthMenu: [ [10, 20, 50, 300, -1], [10, 20, 50, 300,  "Todas"] ],
                    stateSave: true,
                    ajax: {
                        url: relationsApiUrl,
                        dataSrc: '',
                        error: function(xhr, error, thrown) {
                            if (error === 'abort') { console.warn('Petición AJAX de DataTables abortada.'); return; }
                            let errorMsg = 'Error desconocido al cargar datos.';
                            if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message;
                            else if (xhr.responseText) { try { errorMsg = JSON.parse(xhr.responseText).message || xhr.responseText.substring(0,200); } catch(e){ errorMsg = xhr.responseText.substring(0,200); } }
                            console.error("Error AJAX DataTable:", xhr.status, error, thrown, xhr.responseText);
                            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Error al cargar datos.', timer: 1500, showConfirmButton: false, timerProgressBar: true });
                        }
                    },
                    columns: [
                        { data: 'product_list.name', defaultContent: 'Sin asignar' }, // 0: Confección
                        { // 1: Puesto (RFID) - No se usa en el filtro modificado, pero se muestra
                            data: 'rfid_reading',
                            render: function(data, type, row) {
                                if (data && data.name && data.rfid_color && data.rfid_color.name) {
                                    const colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
                                    const colorName = data.rfid_color.name.toLowerCase();
                                    const iconColor = colorMap[colorName] || "#6c757d";
                                    return `<div class="rfid-option-card"><div class="rfid-text">${data.name}</div><div class="rfid-icon" style="color: ${iconColor};"><i class="fas fa-id-card"></i></div></div>`;
                                } return 'Sin asignar';
                            }, defaultContent: 'Sin asignar'
                        },
                        { data: 'modbus.name', defaultContent: 'N/A', render: (d, t, r) => r.modbus?.name || 'N/A' }, // 2: Báscula
                        { data: 'sensor.name', defaultContent: 'N/A', render: (d, t, r) => r.sensor?.name || 'N/A' }, // 3: Sensor
                        { data: 'operator_name', name: 'operator_name', defaultContent: 'Sin asignar' }, // 4: Operario
                        { // 5: Fecha Inicio
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return 'N/A'; try { const date = new Date(data); return !isNaN(date.getTime()) ? date.toLocaleString('es-ES') : 'Fecha inválida'; } catch(e) { return 'Fecha inválida'; }
                            }
                        },
                        { // 6: Fecha Fin
                            data: 'finish_at',
                            render: function(data) {
                                if (!data) return 'En curso'; try { const date = new Date(data); return !isNaN(date.getTime()) ? date.toLocaleString('es-ES') : 'Fecha inválida'; } catch(e) { return 'Fecha inválida'; }
                            }
                        },
                        { // 7: Acciones
                            data: null, orderable: false, searchable: false,
                            render: function(data, type, row) {
                                let buttonsHtml = '';
                                if (canEditRfidPost) {
                                    buttonsHtml += `<button class="btn btn-sm btn-secondary edit-btn" data-id="${row.id}" data-product_list_id="${row.product_list_id || ''}" data-rfid_reading_id="${row.rfid_reading_id || ''}" data-modbus_id="${row.modbus_id || ''}" data-sensor_id="${row.sensor_id || ''}" style="margin-right: 5px;" title="Editar asignación"><i class="bi bi-pencil-square"></i> Editar</button>`;
                                }
                                if (canDeleteRfidPost) {
                                    buttonsHtml += `<button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}" title="Eliminar asignación"><i class="bi bi-trash"></i> Eliminar</button>`;
                                }
                                return buttonsHtml;
                            }
                        }
                    ],
                    dom: "<'row top-controls-row'<'col-sm-12 col-md-6 dt-buttons-placeholder'><'col-sm-12 col-md-6 switch-placeholder'>>" +
                         "<'row filter-length-row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    buttons: [
                        {
                            text: '<i class="bi bi-plus-circle"></i> Asignar Confección', className: 'btn btn-primary', titleAttr: 'Crear nueva asignación',
                            action: function(e, dt, node, config) {
                                // --- Código del modal de Asignar (sin cambios) ---
                                let modalHtml = `<div class="modal-counter-container"><div id="rfidCounterAdd" class="rfid-counter-display"></div></div>`;
                                modalHtml += `<div class="select-block"><label for="productListId">Producto:</label><select id="productListId" class="swal2-input custom-select-style"><option value="" disabled selected>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                                if (rfidOptions.trim() !== '') { modalHtml += `<div class="select-block"><label for="rfidReadingId">Puesto / tarjeta:</label><select id="rfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select></div>`; }
                                if (modbusOptions.trim() !== '') { modalHtml += `<div class="select-block"><label for="modbusId">Báscula:</label><select id="modbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`; }
                                if (sensorOptions.trim() !== '') { modalHtml += `<div class="select-block"><label for="sensorId">Sensor:</label><select id="sensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`; }
                                modalHtml += `<div id="qr-reader" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                                Swal.fire({
                                    title: 'Asignar Confección', width: '80%', html: modalHtml, showCancelButton: true, showDenyButton: rfidOptions.trim() !== '',
                                    confirmButtonText: '<i class="bi bi-check-square"></i> AÑADIR', cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR', denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR RFID',
                                    customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' }, buttonsStyling: false,
                                    preDeny: () => { if (rfidOptions.trim() !== '') { startQrScanner('rfidReadingId'); } else { Swal.showValidationMessage('No hay RFIDs disponibles para escanear.'); } return false; },
                                    didOpen: () => {
                                        selectedRfidColor = null; stopRefreshInterval(); let rfidScrollPosition = 0;
                                        $('#productListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection });
                                        if (rfidOptions.trim() !== '') {
                                            const $rfidSelect = $('#rfidReadingId'); const $rfidCounter = $('#rfidCounterAdd');
                                            $rfidSelect.select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Puesto/Tarjeta --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter });
                                            $rfidSelect.on('change', function() {
                                                const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) rfidScrollPosition = resultsContainer.scrollTop();
                                                let selectedValues = $(this).val() || [];
                                                $rfidCounter.text(selectedValues.length > 0 ? `${selectedValues.length} Puesto(s) seleccionado(s)` : '');
                                                if (selectedValues.length > 0) { if (selectedRfidColor === null) { let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`); if(firstSelectedOption.length) selectedRfidColor = firstSelectedOption.data('color'); } }
                                                else { selectedRfidColor = null; }
                                                if ($(this).data('select2')?.isOpen()) { $(this).select2('close').select2('open'); }
                                            });
                                            $rfidSelect.on('select2:open', function() { setTimeout(() => { const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) resultsContainer.scrollTop(rfidScrollPosition); }, 0); });
                                            $rfidSelect.trigger('change');
                                        }
                                        if (modbusOptions.trim() !== '') { $('#modbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }); $('#modbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                                        if (sensorOptions.trim() !== '') { $('#sensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }); $('#sensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                                        $('#productListId').val(null).trigger('change');
                                    },
                                    preConfirm: () => {
                                        const pId = $('#productListId').val(); const rIds = rfidOptions.trim() !== '' ? ($('#rfidReadingId').val() || []) : []; const mIds = modbusOptions.trim() !== '' ? ($('#modbusId').val() || []) : []; const sIds = sensorOptions.trim() !== '' ? ($('#sensorId').val() || []) : [];
                                        if (!pId) { Swal.showValidationMessage('Debe seleccionar un Producto.'); $('#productListId').select2('open'); return false; }
                                        if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) { Swal.showValidationMessage('Debe seleccionar al menos un Puesto (RFID), Báscula o Sensor.'); if (rfidOptions.trim() !== '') $('#rfidReadingId').select2('open'); else if (modbusOptions.trim() !== '') $('#modbusId').select2('open'); else if (sensorOptions.trim() !== '') $('#sensorId').select2('open'); return false; }
                                        return { product_list_id: parseInt(pId), rfid_reading_ids: rIds.length ? rIds.map(id => parseInt(id)) : [], modbus_ids: mIds.length ? mIds.map(id => parseInt(id)) : [], sensor_ids: sIds.length ? sIds.map(id => parseInt(id)) : [] };
                                    },
                                    didClose: () => { selectedRfidColor = null; stopQrScanner(); startRefreshInterval(); }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $.ajax({
                                            url: relationsApiUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify(result.value),
                                            success: function(response) { Swal.fire({ title: 'Éxito', text: response.message || 'Asignación creada correctamente.', icon: 'success', timer: 2000, showConfirmButton: false }); table.ajax.reload(); },
                                            error: function(xhr) { let errorMsg = 'Error al crear la asignación.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error POST:", xhr); Swal.fire('Error', errorMsg, 'error'); }
                                        });
                                    }
                                });
                            }
                        },
                        { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Excel', className: 'btn btn-success', titleAttr: 'Exportar a Excel', exportOptions: { columns: ':visible' } },
                        { extend: 'print', text: '<i class="bi bi-printer"></i> Imprimir', className: 'btn btn-secondary', titleAttr: 'Imprimir tabla', exportOptions: { columns: ':visible' } },
                        { extend: 'colvis', text: '<i class="bi bi-eye"></i> Columnas', className: 'btn btn-secondary', titleAttr: 'Mostrar/Ocultar columnas' },
                        { text: '<i class="bi bi-broadcast"></i> Live Rfid', className: 'btn btn-info', action: function () { window.open('/live-rfid/', '_blank'); }, titleAttr: 'Ver RFID en tiempo real' }
                    ],
                    order: [[6, 'desc'], [5, 'desc'], [0, 'asc']], // Orden inicial
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json', search: "_INPUT_", searchPlaceholder: "Buscar en la tabla..." },
                    initComplete: function(settings, json) {
                        console.log("DataTable inicializado.");
                        const api = this.api();
                        api.buttons().container().appendTo('.dt-buttons-placeholder');
                        $('.switches-container').appendTo('.switch-placeholder').show();
                        $('.dataTables_filter input').addClass('form-control');
                        $('.dataTables_length select').addClass('form-select form-select-sm');

                        // Listener para el switch de Ocultar sin asignar (Op/Bas/Sen)
                        $('#showUnassignedSwitch').on('change', function() {
                            const isChecked = $(this).is(':checked');
                            console.log("'Ocultar sin asignar (Op/Bas/Sen)' cambiado a:", isChecked);
                            localStorage.setItem(unassignedSwitchStorageKey, isChecked); // Guardar estado
                            table.draw(); // Redibujar para aplicar filtro
                        });

                        // Listener para el switch de historial
                        $('#showHistorySwitch').on('change', function() {
                            console.log("'Mostrar historial' cambiado a:", $(this).is(':checked'));
                            table.draw(); // Redibujar para aplicar filtro
                        });

                        // Dibujar tabla inicialmente para aplicar filtros guardados/iniciales
                        console.log("Aplicando filtros iniciales y dibujando tabla...");
                        table.draw();

                        // Iniciar refresco automático
                        startRefreshInterval();
                    }
                });

                // --- Delegación de eventos para botones Editar/Eliminar (sin cambios) ---
                $('#relationsTable tbody').on('click', '.edit-btn', function() {
                    if (!canEditRfidPost) { Swal.fire('Acceso denegado', 'No tiene permiso para editar asignaciones.', 'warning'); return; }
                    const $button = $(this); const currentId = $button.data('id');
                    const currentProductListId = $button.data('product_list_id');
                    const getIdsArray = (data) => (data || '').toString().split(',').map(id => id.trim()).filter(Boolean);
                    const currentRfidReadingIds = getIdsArray($button.data('rfid_reading_id'));
                    const currentModbusIds = getIdsArray($button.data('modbus_id'));
                    const currentSensorIds = getIdsArray($button.data('sensor_id'));

                    let modalHtmlEdit = `<div class="modal-counter-container"><div id="rfidCounterEdit" class="rfid-counter-display"></div></div>`;
                    modalHtmlEdit += `<input type="hidden" id="editRelationId" value="${currentId}">`;
                    modalHtmlEdit += `<div class="select-block"><label for="editProductListId">Producto:</label><select id="editProductListId" class="swal2-input custom-select-style"><option value="" disabled>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                    if (rfidOptions.trim() !== '') { modalHtmlEdit += `<div class="select-block"><label for="editRfidReadingId">Puesto/tarjeta:</label><select id="editRfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select></div>`; }
                    if (modbusOptions.trim() !== '') { modalHtmlEdit += `<div class="select-block"><label for="editModbusId">Báscula:</label><select id="editModbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`; }
                    if (sensorOptions.trim() !== '') { modalHtmlEdit += `<div class="select-block"><label for="editSensorId">Sensor:</label><select id="editSensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`; }
                    modalHtmlEdit += `<div id="qr-reader-edit" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                    Swal.fire({
                        title: 'Editar Asignación', width: '80%', html: modalHtmlEdit, showCancelButton: true, showDenyButton: rfidOptions.trim() !== '',
                        confirmButtonText: '<i class="bi bi-save"></i> ACTUALIZAR', cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR', denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR RFID',
                        customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' }, buttonsStyling: false,
                        preDeny: () => { if (rfidOptions.trim() !== '') { startQrScanner('editRfidReadingId'); } else { Swal.showValidationMessage('No hay RFIDs disponibles para escanear.'); } return false; },
                        didOpen: () => {
                            stopRefreshInterval(); let editRfidScrollPosition = 0;
                            $('#editProductListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection }).val(currentProductListId).trigger('change');
                            if (rfidOptions.trim() !== '') {
                                const $editRfidSelect = $('#editRfidReadingId'); const $editRfidCounter = $('#rfidCounterEdit');
                                $editRfidSelect.select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Puesto/Tarjeta --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter }).val(currentRfidReadingIds).trigger('change');
                                $editRfidSelect.on('change', function() {
                                    const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) editRfidScrollPosition = resultsContainer.scrollTop();
                                    let selectedValues = $(this).val() || [];
                                    $editRfidCounter.text(selectedValues.length > 0 ? `${selectedValues.length} Puesto(s) seleccionado(s)` : '');
                                    if (selectedValues.length > 0) { if (selectedRfidColor === null || $(this).data('previousValues')?.length === 0) { let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`); if(firstSelectedOption.length) selectedRfidColor = firstSelectedOption.data('color'); else selectedRfidColor = null; } }
                                    else { selectedRfidColor = null; }
                                    $(this).data('previousValues', selectedValues);
                                    if ($(this).data('select2')?.isOpen()) { $(this).select2('close').select2('open'); }
                                });
                                $editRfidSelect.on('select2:open', function() { setTimeout(() => { const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) resultsContainer.scrollTop(editRfidScrollPosition); }, 0); });
                                $editRfidSelect.trigger('change');
                            }
                            if (modbusOptions.trim() !== '') { $('#editModbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentModbusIds).trigger('change'); $('#editModbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                            if (sensorOptions.trim() !== '') { $('#editSensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentSensorIds).trigger('change'); $('#editSensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                        },
                        preConfirm: () => {
                            const pId = $('#editProductListId').val(); const rIds = rfidOptions.trim() !== '' ? ($('#editRfidReadingId').val() || []) : []; const mIds = modbusOptions.trim() !== '' ? ($('#editModbusId').val() || []) : []; const sIds = sensorOptions.trim() !== '' ? ($('#editSensorId').val() || []) : [];
                            if (!pId) { Swal.showValidationMessage('Debe seleccionar un Producto.'); $('#editProductListId').select2('open'); return false; }
                            if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) { Swal.showValidationMessage('Debe seleccionar al menos un Puesto (RFID), Báscula o Sensor.'); if (rfidOptions.trim() !== '') $('#editRfidReadingId').select2('open'); else if (modbusOptions.trim() !== '') $('#editModbusId').select2('open'); else if (sensorOptions.trim() !== '') $('#editSensorId').select2('open'); return false; }
                            return { product_list_id: parseInt(pId), rfid_reading_ids: rIds.map(id => parseInt(id)), modbus_ids: mIds.map(id => parseInt(id)), sensor_ids: sIds.map(id => parseInt(id)) };
                        },
                        didClose: () => { selectedRfidColor = null; stopQrScanner(); startRefreshInterval(); }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const relationId = $('#editRelationId').val();
                            $.ajax({
                                url: `${relationsApiUrl}/${relationId}`, method: 'PUT', contentType: 'application/json', data: JSON.stringify(result.value),
                                success: function(response) { Swal.fire({ title: 'Éxito', text: response.message || 'Asignación actualizada.', icon: 'success', timer: 2000, showConfirmButton: false }); table.ajax.reload(null, false); },
                                error: function(xhr) { let errorMsg = 'Error al actualizar la asignación.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error PUT/PATCH:", xhr); Swal.fire('Error', errorMsg, 'error'); }
                            });
                        }
                    });
                });

                $('#relationsTable tbody').on('click', '.delete-btn', function() {
                    if (!canDeleteRfidPost) { Swal.fire('Acceso denegado', 'No tiene permiso para eliminar asignaciones.', 'warning'); return; }
                    const button = this; const id = $(button).data('id');
                    stopRefreshInterval();
                    Swal.fire({
                        title: '¿Estás seguro?', text: "¡Esta acción no se puede deshacer!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar', cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                        customClass: { confirmButton: 'btn btn-danger mx-1', cancelButton: 'btn btn-secondary mx-1' }, buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `${relationsApiUrl}/${id}`, method: 'DELETE',
                                success: function(response) { Swal.fire({ title: 'Eliminado', text: response.message || 'Asignación eliminada.', icon: 'success', timer: 2000, showConfirmButton: false }); table.row($(button).closest('tr')).remove().draw(false); startRefreshInterval(); },
                                error: function(xhr) { let errorMsg = 'Error al eliminar la asignación.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error DELETE:", xhr); Swal.fire('Error', errorMsg, 'error'); startRefreshInterval(); }
                            });
                        } else { startRefreshInterval(); }
                    });
                });

                // Botón flotante de refrescar manualmente
                $('#refreshTableBtn').on('click', function() {
                    console.log('Refrescando tabla manualmente...');
                    if (table && $.fn.DataTable.isDataTable('#relationsTable')) {
                        let pageInfo = table.page.info(); let searchVal = table.search();
                        const historyChecked = $('#showHistorySwitch').is(':checked');
                        const unassignedChecked = $('#showUnassignedSwitch').is(':checked');
                        table.ajax.reload(function() {
                            if ($.fn.DataTable.isDataTable('#relationsTable')) {
                                $('#showHistorySwitch').prop('checked', historyChecked);
                                $('#showUnassignedSwitch').prop('checked', unassignedChecked);
                                table.search(searchVal).page(pageInfo.page).draw('page');
                            }
                        }, false);
                        const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
                        toastMixin.fire({ icon: 'info', title: 'Tabla actualizada' });
                    }
                });

            }); // Fin de .always() de loadSelectOptions

            // --- Manejo del intervalo de refresco con visibilidad de la página ---
            $(window).on('unload pagehide', function() { stopRefreshInterval(); });
            $(window).on('pageshow', function(event) { if (event.originalEvent.persisted === false && !refreshIntervalId) { startRefreshInterval(); } });
            $(window).on('blur', function() { if (refreshIntervalId) { console.log('Ventana perdió foco, pausando refresco.'); stopRefreshInterval(); } });
            $(window).on('focus', function() { if (!refreshIntervalId) { console.log('Ventana recuperó foco, reanudando refresco.'); startRefreshInterval(); } });

        }); // Fin de $(document).ready()

        // --- Funciones Escáner QR (sin cambios) ---
        let html5QrCode = null;

        function startQrScanner(targetSelectId) {
            const qrReaderId = targetSelectId.startsWith('edit') ? 'qr-reader-edit' : 'qr-reader';
            const qrReaderElement = document.getElementById(qrReaderId);
            if (!qrReaderElement) { console.error(`Elemento ${qrReaderId} no encontrado.`); return; }
            if (html5QrCode && html5QrCode.isScanning) { console.warn("El escáner QR ya está activo."); return; }
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
                        const optionValue = $option.val(); let currentValues = $targetSelect.val() || []; if (!Array.isArray(currentValues)) { currentValues = [currentValues]; }
                        if (!currentValues.includes(optionValue)) { currentValues.push(optionValue); $targetSelect.val(currentValues).trigger('change'); toastMixin.fire({ icon: 'success', title: `Puesto ${decodedText} añadido`, timer: 1500 }); }
                        else { toastMixin.fire({ icon: 'info', title: `Puesto ${decodedText} ya estaba seleccionado`, timer: 1500 }); }
                    } else { toastMixin.fire({ icon: 'warning', title: `Puesto ${decodedText} no encontrado en la lista`, timer: 2500 }); }
                }, (errorMessage) => { /* Ignorar errores menores */ }
            ).catch((err) => {
                console.error("Error grave al iniciar escáner QR:", err);
                let errorMsg = 'No se pudo iniciar el escáner QR.';
                if (err.name === 'NotAllowedError') { errorMsg = 'Permiso de cámara denegado.'; }
                else if (err.name === 'NotFoundError') { errorMsg = 'No se encontró una cámara compatible.'; }
                 else if (typeof err === 'string' && err.includes('Permission denied')) { errorMsg = 'Permiso de cámara denegado.'; }
                Swal.fire('Error de Cámara', errorMsg, 'error');
                qrReaderElement.style.display = 'none';
            });
        }

        function stopQrScanner() {
            const qrReaderEdit = document.getElementById('qr-reader-edit'); const qrReaderAdd = document.getElementById('qr-reader');
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("Escáner QR detenido correctamente.");
                    if (qrReaderAdd) qrReaderAdd.style.display = 'none'; if (qrReaderEdit) qrReaderEdit.style.display = 'none';
                }).catch(err => {
                    console.error("Error al detener escáner QR:", err);
                    if (qrReaderAdd) qrReaderAdd.style.display = 'none'; if (qrReaderEdit) qrReaderEdit.style.display = 'none';
                });
            } else {
                if (qrReaderAdd) qrReaderAdd.style.display = 'none'; if (qrReaderEdit) qrReaderEdit.style.display = 'none';
            }
        }
    </script>
@endpush
