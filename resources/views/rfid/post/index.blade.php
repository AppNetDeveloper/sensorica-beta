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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js" integrity="sha512-r6rDA7W6ZeQhvl8S7yRVQUKVHdexq+GAlNkNNqVC7YyIV+NwqCTJe2hDWCiffTyRNOeGEzRRJ9ifvRm/HCzGYg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        // --- Definir permisos en JavaScript (Método seguro) ---
        const canEditRfidPost = {{ auth()->check() && auth()->user()->can('rfid-post-edit') ? 'true' : 'false' }};
        const canDeleteRfidPost = {{ auth()->check() && auth()->user()->can('rfid-post-delete') ? 'true' : 'false' }};


        // Variable global para guardar el color del primer RFID seleccionado
        let selectedRfidColor = null;
        // Variable para el intervalo de actualización
        let refreshIntervalId = null;
        const REFRESH_INTERVAL = 30000; // 30 segundos en milisegundos

        // Función para iniciar el intervalo de refresco
        function startRefreshInterval() {
            // Detener cualquier intervalo existente
            stopRefreshInterval();
            
            // Iniciar nuevo intervalo
            refreshIntervalId = setInterval(function() {
                // Solo ejecutar si la tabla está inicializada y no hay una petición pendiente
                if (table && !window.tableReloadInProgress && $.fn.DataTable.isDataTable('#relationsTable')) {
                    console.log('Actualizando tabla automáticamente...');
                    window.tableReloadInProgress = true;
                    
                    // Guardar el estado actual
                    const currentPage = table.page();
                    const searchVal = table.search();
                    const historyChecked = $('#showHistorySwitch').is(':checked');
                    const unassignedChecked = $('#showUnassignedSwitch').is(':checked');
                    
                    // Realizar la recarga
                    table.ajax.reload(function() {
                        if ($.fn.DataTable.isDataTable('#relationsTable')) {
                            // Restaurar estado
                            $('#showHistorySwitch').prop('checked', historyChecked);
                            $('#showUnassignedSwitch').prop('checked', unassignedChecked);
                            table.search(searchVal).page(currentPage).draw(false);
                        }
                        window.tableReloadInProgress = false;
                    }, false); // false para mantener la paginación
                }
            }, REFRESH_INTERVAL);
            console.log('Intervalo de refresco iniciado cada', REFRESH_INTERVAL + 'ms');
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
            const initialUnassignedState = localStorage.getItem(unassignedSwitchStorageKey) !== 'false';
            $('#showUnassignedSwitch').prop('checked', initialUnassignedState);
            console.log("Estado inicial 'Ocultar sin asignar':", initialUnassignedState);

            // Filtro personalizado para mostrar/ocultar historial
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex, rowData, counter ) {
                    // Asegurarse que el filtro solo aplica a la tabla correcta
                    if (settings.nTable.id !== 'relationsTable') { return true; }
                    const showHistory = $('#showHistorySwitch').is(':checked');
                    // Acceder a la fecha de fin desde el objeto rowData (el objeto original de la fila)
                    const finishDate = rowData.finish_at;
                    // Si se quiere mostrar historial, mostrar todas las filas
                    if (showHistory) { return true; }
                    // Si no, mostrar solo las filas sin fecha de fin (activas)
                    else { return finishDate === null || finishDate === undefined || finishDate === ''; }
                }
            );


            // Filtro para OCULTAR filas donde Operario, Báscula Y Sensor no están asignados
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex, rowData, counter ) {
                    // Asegurarse que el filtro solo aplica a la tabla correcta
                    if (settings.nTable.id !== 'relationsTable') { return true; }
                    const hideUnassigned = $('#showUnassignedSwitch').is(':checked'); // El switch significa "ocultar"

                    // Si el switch está apagado (no ocultar), mostrar todas las filas (no aplicar este filtro)
                    if (!hideUnassigned) {
                        return true;
                    }

                    // Si el switch está encendido (ocultar no asignados), verificar si ALGUNO tiene valor
                    // Acceder a los datos desde el objeto rowData original
                    const hasOperator = rowData.operator_name && rowData.operator_name.trim() !== '' && rowData.operator_name !== 'Sin asignar';
                    const hasModbus = rowData.modbus && rowData.modbus.id; // Verificar si existe el objeto modbus y tiene id
                    const hasSensor = rowData.sensor && rowData.sensor.id; // Verificar si existe el objeto sensor y tiene id

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
                    stateDuration: -1,
                    // ——— GUARDAR/RECUPERAR TODO EL ESTADO (incluye visibilidad de columnas) ———
                    stateSaveCallback: function(settings, data) {
                        const key = 'DataTables_' + window.location.pathname + '_' + settings.sInstance;
                        localStorage.setItem(key, JSON.stringify(data));
                    },
                    stateLoadCallback: function(settings) {
                        const key = 'DataTables_' + window.location.pathname + '_' + settings.sInstance;
                        const stored = localStorage.getItem(key);
                        return stored ? JSON.parse(stored) : null;
                    },
                    // —————————————————————————————————————————————————————————————————
                    ajax: {
                        url: relationsApiUrl,
                        dataSrc: '', // La respuesta API es directamente el array de datos
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
                        // Columna 0: Confección (Producto)
                        {
                            data: 'product_list.name', // Accede al nombre dentro del objeto product_list
                            defaultContent: 'Sin asignar' // Valor si product_list o su nombre es nulo
                        },
                        // Columna 1: Puesto (RFID)
                        {
                            data: 'rfid_reading', // El dato es el objeto rfid_reading completo
                            render: function(data, type, row) {
                                // 'data' aquí es el objeto row.rfid_reading
                                if (data && data.name && data.rfid_color && data.rfid_color.name) {
                                    const colorMap = { red: "#dc3545", blue: "#007bff", yellow: "#ffc107", green: "#28a745" };
                                    const colorName = data.rfid_color.name.toLowerCase();
                                    const iconColor = colorMap[colorName] || "#6c757d"; // Color por defecto si no coincide
                                    return `<div class="rfid-option-card"><div class="rfid-text">${data.name}</div><div class="rfid-icon" style="color: ${iconColor};"><i class="fas fa-id-card"></i></div></div>`;
                                }
                                return 'Sin asignar'; // Si no hay datos válidos
                            },
                            defaultContent: 'Sin asignar' // Valor por defecto general
                        },
                        // Columna 2: Báscula (Modbus)
                        {
                            data: 'modbus.name', // Accede al nombre dentro del objeto modbus
                            defaultContent: 'N/A' // Valor si modbus o su nombre es nulo
                        },
                        // Columna 3: Sensor
                        {
                            data: 'sensor.name', // Accede al nombre dentro del objeto sensor
                            defaultContent: 'N/A' // Valor si sensor o su nombre es nulo
                        },
                        // Columna 4: Operario
                        {
                            data: 'operator_name', // Nombre directo del operario
                            name: 'operator_name', // Nombre para referencia interna (opcional)
                            defaultContent: 'Sin asignar' // Valor si operator_name es nulo o vacío
                        },
                        // Columna 5: Fecha Inicio
                        {
                            data: 'created_at', // Fecha de creación
                            render: function(data) {
                                if (!data) return 'N/A'; // Si no hay fecha
                                try {
                                    const date = new Date(data);
                                    // Comprobar si la fecha es válida antes de formatear
                                    return !isNaN(date.getTime()) ? date.toLocaleString('es-ES') : 'Fecha inválida';
                                } catch(e) {
                                    return 'Fecha inválida'; // Capturar errores de formato
                                }
                            }
                        },
                        // Columna 6: Fecha Fin
                        {
                            data: 'finish_at', // Fecha de finalización
                            render: function(data) {
                                if (!data) return 'En curso'; // Si no hay fecha, está activo
                                try {
                                    const date = new Date(data);
                                    // Comprobar si la fecha es válida antes de formatear
                                    return !isNaN(date.getTime()) ? date.toLocaleString('es-ES') : 'Fecha inválida';
                                } catch(e) {
                                    return 'Fecha inválida'; // Capturar errores de formato
                                }
                            }
                        },
                        // Columna 7: Acciones
                        {
                            data: null, // No se basa en un dato específico de la fila
                            orderable: false, // No se puede ordenar por esta columna
                            searchable: false, // No se puede buscar en esta columna
                            render: function(data, type, row) {
                                // 'row' aquí es el objeto completo de la fila
                                let buttonsHtml = '';
                                // Botón Editar (si tiene permiso)
                                if (canEditRfidPost) {
                                    // Pasamos los IDs necesarios como data attributes
                                    buttonsHtml += `<button class="btn btn-sm btn-secondary edit-btn"
                                                        data-id="${row.id}"
                                                        data-product_list_id="${row.product_list?.id || ''}"
                                                        data-rfid_reading_id="${row.rfid_reading?.id || ''}"
                                                        data-modbus_id="${row.modbus?.id || ''}"
                                                        data-sensor_id="${row.sensor?.id || ''}"
                                                        style="margin-right: 5px;" title="Editar asignación">
                                                        <i class="bi bi-pencil-square"></i> Editar
                                                    </button>`;
                                }
                                // Botón Eliminar (si tiene permiso)
                                if (canDeleteRfidPost) {
                                    buttonsHtml += `<button class="btn btn-sm btn-danger delete-btn"
                                                        data-id="${row.id}" title="Eliminar asignación">
                                                        <i class="bi bi-trash"></i> Eliminar
                                                    </button>`;
                                }
                                return buttonsHtml; // Devuelve el HTML de los botones
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
                                // Código del modal de Asignar
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
                                    // --- INICIO: preConfirm con LOGS ---
                                    preConfirm: () => {
                                      const pId = $('#productListId').val();
                                      const rIds = rfidOptions.trim() !== '' ? ($('#rfidReadingId').val() || []).map(id => parseInt(id)) : [];
                                      const mIds = modbusOptions.trim() !== '' ? ($('#modbusId').val() || []).map(id => parseInt(id)) : [];
                                      const sIds = sensorOptions.trim() !== '' ? ($('#sensorId').val() || []).map(id => parseInt(id)) : [];

                                      // Validaciones básicas
                                      if (!pId) {
                                        Swal.showValidationMessage('Debe seleccionar un Producto.');
                                        $('#productListId').select2('open');
                                        return false;
                                      }
                                      if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) {
                                        Swal.showValidationMessage('Debe seleccionar al menos un Puesto (RFID), Báscula o Sensor.');
                                        if (rfidOptions.trim() !== '') $('#rfidReadingId').select2('open');
                                        else if (modbusOptions.trim() !== '') $('#modbusId').select2('open');
                                        else if (sensorOptions.trim() !== '') $('#sensorId').select2('open');
                                        return false;
                                      }

                                      // Comprobación de duplicado
                                      let yaAsignado = false;
                                      if (rIds.length > 0) {
                                          const rows = table.rows().data().toArray();
                                          const selectedProductId = parseInt(pId, 10);

                                          // --- LOGS INICIO ---
                                          console.log("--- Comprobación Duplicados ---");
                                          console.log("Producto Seleccionado ID:", selectedProductId);
                                          console.log("RFIDs Seleccionados IDs:", rIds);
                                          console.log("Comprobando", rows.length, "filas existentes...");
                                          // --- LOGS FIN ---

                                          yaAsignado = rows.some((row, index) => {
                                              const rowRfidId = row.rfid_reading?.id;
                                              const rowProductId = row.product_list?.client_id;
                                              const rowFinishAt = row.finish_at;
                                              const isActive = rowFinishAt === null || rowFinishAt === '';
                                              const rfidMatch = row.rfid_reading && rIds.includes(rowRfidId);
                                              const productMatch = row.product_list && rowProductId === selectedProductId;

                                              // --- LOGS INICIO ---
                                              console.log(`Fila ${index}: RFID=${rowRfidId}, Producto=${rowProductId}, Fin=${rowFinishAt}, Activa=${isActive}`);
                                              let isDuplicate = false;
                                              if (rfidMatch && productMatch && isActive) {
                                                  console.log(`--> ¡DUPLICADO ENCONTRADO en Fila ${index}!`);
                                                  isDuplicate = true;
                                              }
                                              // --- LOGS FIN ---
                                              return isDuplicate; // Devuelve true si esta fila es un duplicado activo
                                          });

                                          // --- LOGS INICIO ---
                                          console.log("Resultado final yaAsignado:", yaAsignado);
                                          console.log("--- Fin Comprobación Duplicados ---");
                                          // --- LOGS FIN ---
                                      }


                                      // Si se encontró una asignación duplicada activa
                                      if (yaAsignado) {
                                        // Mostrar el segundo Swal de confirmación
                                        return Swal.fire({
                                          title: '¡Atención!',
                                          text: 'Ya existe al menos un puesto selecionado para esta confeccion en curso. ¿Quieres continuar de todas formas?',
                                          icon: 'warning',
                                          showCancelButton: true,
                                          confirmButtonText: 'Sí, continuar',
                                          cancelButtonText: 'No, cambiar',
                                          customClass: {
                                            confirmButton: 'btn btn-warning mx-1',
                                            cancelButton: 'btn btn-secondary mx-1'
                                          },
                                          buttonsStyling: false
                                        }).then(result2 => {
                                          // Si el usuario confirma en el segundo Swal
                                          if (result2.isConfirmed) {
                                            // Devolver los datos para la creación
                                            return {
                                              client_id:        parseInt(pId),
                                              rfid_reading_ids: rIds,
                                              modbus_ids:       mIds,
                                              sensor_ids:       sIds
                                            };
                                          }
                                          // Si el usuario cancela en el segundo Swal, rechazar la promesa del primer Swal
                                          return Promise.reject('Operación cancelada por el usuario debido a duplicado.');
                                        });
                                      }

                                      // Si no hay duplicados, devolver directamente los datos para la creación
                                      return {
                                        client_id:        parseInt(pId),
                                        rfid_reading_ids: rIds,
                                        modbus_ids:       mIds,
                                        sensor_ids:       sIds
                                      };
                                    },
                                    // --- FIN: preConfirm con LOGS ---
                                    didClose: () => { selectedRfidColor = null; stopQrScanner(); startRefreshInterval(); }
                                }).then((result) => {
                                    // Este bloque solo se ejecuta si preConfirm se resuelve (no se rechaza)
                                    if (result.isConfirmed) {
                                        // Enviar datos al servidor
                                        $.ajax({
                                            url: relationsApiUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify(result.value),
                                            success: function(response) { Swal.fire({ title: 'Éxito', text: response.message || 'Asignación creada correctamente.', icon: 'success', timer: 2000, showConfirmButton: false }); table.ajax.reload(); },
                                            error: function(xhr) { let errorMsg = 'Error al crear la asignación.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error POST:", xhr); Swal.fire('Error', errorMsg, 'error'); }
                                        });
                                        if (!refreshIntervalId) {
                                            startRefreshInterval();
                                        }
                                    }
                                }).catch(reason => {
                                    // Capturar rechazos de preConfirm (incluido el de duplicado cancelado)
                                    if (reason === 'Operación cancelada por el usuario debido a duplicado.') {
                                        console.warn('Creación cancelada por el usuario debido a duplicado.');
                                        // Mostrar mensaje de validación para guiar al usuario
                                        Swal.showValidationMessage('Modifica tu selección para evitar duplicados.');
                                    } else if (reason && reason !== 'cancel' && reason !== 'backdrop' && reason !== 'esc') {
                                        // Loggear otros errores inesperados si no son cancelaciones estándar de Swal
                                        console.error('Error en la confirmación del Swal:', reason);
                                    }
                                    // Asegurarse de reiniciar el intervalo si el modal se cierra por cualquier motivo
                                    if (!refreshIntervalId) {
                                        startRefreshInterval();
                                    }
                                });
                            }
                        },
                        { extend: 'excelHtml5', text: '<i class="bi bi-file-earmark-excel"></i> Excel', className: 'btn btn-success', titleAttr: 'Exportar a Excel', exportOptions: { columns: ':visible' } },
                        { extend: 'print', text: '<i class="bi bi-printer"></i> Imprimir', className: 'btn btn-secondary', titleAttr: 'Imprimir tabla', exportOptions: { columns: ':visible' } },
                        { extend: 'colvis', text: '<i class="bi bi-eye"></i> Columnas', className: 'btn btn-secondary', titleAttr: 'Mostrar/Ocultar columnas' },
                        { text: '<i class="bi bi-broadcast"></i> Live Rfid', className: 'btn btn-info', action: function () { window.open('/live-rfid/', '_blank'); }, titleAttr: 'Ver RFID en tiempo real' },
                        { text: '<i class="bi bi-card-checklist"></i> Listado ', className: 'btn btn-info', action: function () { window.open('/confeccion-puesto-listado/', '_blank'); }, titleAttr: 'Ver Confeciones Asignadas' }
                    ],
                    order: [[6, 'desc'], [5, 'desc'], [0, 'asc']], // Orden inicial por Fecha Fin (desc), Fecha Inicio (desc), Confección (asc)
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json', search: "_INPUT_", searchPlaceholder: "Buscar en la tabla..." },
                    initComplete: function(settings, json) {
                        console.log("DataTable inicializado.");
                        const api = this.api();
                        
                        // Función para mover controles
                        const moveControls = function() {
                            try {
                                // Mover botones
                                const buttonsContainer = api.buttons().container();
                                if (buttonsContainer.length) {
                                    buttonsContainer.appendTo('.dt-buttons-placeholder');
                                    console.log("Botones movidos al placeholder");
                                }
                                
                                // Mover switches
                                const $switches = $('.switches-container');
                                if ($switches.length) {
                                    $switches.appendTo('.switch-placeholder').show();
                                    console.log("Switches movidos al placeholder");
                                }
                                
                                // Asegurar que los botones sean visibles
                                $('.dt-buttons').show();
                                return true;
                            } catch (e) {
                                console.error("Error moviendo controles:", e);
                                return false;
                            }
                        };

                        // Intentar mover controles inmediatamente
                        if (!moveControls()) {
                            // Si falla, reintentar después de un breve retraso
                            console.log("Reintentando mover controles...");
                            setTimeout(moveControls, 500);
                        }
                        
                        // Iniciar el refresco después de asegurar que todo está listo
                        setTimeout(startRefreshInterval, 1000);

                        // Añadir clases de Bootstrap a los controles de DataTable
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

                // --- Delegación de eventos para botones Editar/Eliminar ---
                $('#relationsTable tbody').on('click', '.edit-btn', function() {
                    // Comprobar permiso
                    if (!canEditRfidPost) { Swal.fire('Acceso denegado', 'No tiene permiso para editar asignaciones.', 'warning'); return; }

                    // Obtener datos de la fila desde los atributos data-* del botón
                    const $button = $(this);
                    const currentId = $button.data('id');
                    const currentProductListId = $button.data('product_list_id');
                    // Obtener IDs como strings, asegurándose de que sean arrays aunque solo haya uno o ninguno
                    const currentRfidReadingIds = ($button.data('rfid_reading_id') || '').toString().split(',').map(id => id.trim()).filter(Boolean);
                    const currentModbusIds = ($button.data('modbus_id') || '').toString().split(',').map(id => id.trim()).filter(Boolean);
                    const currentSensorIds = ($button.data('sensor_id') || '').toString().split(',').map(id => id.trim()).filter(Boolean);


                    // Construir HTML del modal de edición
                    let modalHtmlEdit = `<div class="modal-counter-container"><div id="rfidCounterEdit" class="rfid-counter-display"></div></div>`;
                    modalHtmlEdit += `<input type="hidden" id="editRelationId" value="${currentId}">`; // Guardar el ID de la relación a editar
                    modalHtmlEdit += `<div class="select-block"><label for="editProductListId">Producto:</label><select id="editProductListId" class="swal2-input custom-select-style"><option value="" disabled>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                    if (rfidOptions.trim() !== '') { modalHtmlEdit += `<div class="select-block"><label for="editRfidReadingId">Puesto/tarjeta:</label><select id="editRfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select></div>`; }
                    if (modbusOptions.trim() !== '') { modalHtmlEdit += `<div class="select-block"><label for="editModbusId">Báscula:</label><select id="editModbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`; }
                    if (sensorOptions.trim() !== '') { modalHtmlEdit += `<div class="select-block"><label for="editSensorId">Sensor:</label><select id="editSensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`; }
                    modalHtmlEdit += `<div id="qr-reader-edit" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`; // Contenedor para el escáner QR

                    // Mostrar modal de edición con SweetAlert2
                    Swal.fire({
                        title: 'Editar Asignación', width: '80%', html: modalHtmlEdit, showCancelButton: true, showDenyButton: rfidOptions.trim() !== '', // Mostrar botón escanear si hay RFIDs
                        confirmButtonText: '<i class="bi bi-save"></i> ACTUALIZAR', cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR', denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR RFID',
                        customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' }, buttonsStyling: false,
                        // Acción al pulsar "Escanear RFID"
                        preDeny: () => { if (rfidOptions.trim() !== '') { startQrScanner('editRfidReadingId'); } else { Swal.showValidationMessage('No hay RFIDs disponibles para escanear.'); } return false; }, // Evita que se cierre el modal
                        // Al abrir el modal
                        didOpen: () => {
                            stopRefreshInterval(); // Pausar refresco automático
                            let editRfidScrollPosition = 0; // Para restaurar scroll en Select2 RFID

                            // Inicializar Select2 para Producto y seleccionar valor actual
                            $('#editProductListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection }).val(currentProductListId).trigger('change');

                            // Inicializar Select2 para RFID (si hay opciones)
                            if (rfidOptions.trim() !== '') {
                                const $editRfidSelect = $('#editRfidReadingId'); const $editRfidCounter = $('#rfidCounterEdit');
                                $editRfidSelect.select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Puesto/Tarjeta --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter }).val(currentRfidReadingIds).trigger('change');

                                // Actualizar contador y color al cambiar selección RFID
                                $editRfidSelect.on('change', function() {
                                    const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) editRfidScrollPosition = resultsContainer.scrollTop();
                                    let selectedValues = $(this).val() || [];
                                    $editRfidCounter.text(selectedValues.length > 0 ? `${selectedValues.length} Puesto(s) seleccionado(s)` : '');
                                    // Actualizar color base solo si cambia la selección o estaba vacío
                                    if (selectedValues.length > 0) { if (selectedRfidColor === null || $(this).data('previousValues')?.length === 0) { let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`); if(firstSelectedOption.length) selectedRfidColor = firstSelectedOption.data('color'); else selectedRfidColor = null; } }
                                    else { selectedRfidColor = null; }
                                    $(this).data('previousValues', selectedValues); // Guardar valores para la próxima comparación
                                    // Reabrir dropdown si estaba abierto para aplicar filtro de color
                                    if ($(this).data('select2')?.isOpen()) { $(this).select2('close').select2('open'); }
                                });
                                // Restaurar posición de scroll al abrir dropdown RFID
                                $editRfidSelect.on('select2:open', function() { setTimeout(() => { const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) resultsContainer.scrollTop(editRfidScrollPosition); }, 0); });
                                $editRfidSelect.trigger('change'); // Disparar change inicial para contador y color
                            }

                            // Inicializar Select2 para Báscula (si hay opciones)
                            if (modbusOptions.trim() !== '') { $('#editModbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentModbusIds).trigger('change'); $('#editModbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); } // Evitar cierre al seleccionar
                            // Inicializar Select2 para Sensor (si hay opciones)
                            if (sensorOptions.trim() !== '') { $('#editSensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentSensorIds).trigger('change'); $('#editSensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); } // Evitar cierre al seleccionar
                        },
                        // Antes de confirmar la edición
                        preConfirm: () => {
                            // Recoger valores seleccionados
                            const pId = $('#editProductListId').val();
                            const rIds = rfidOptions.trim() !== '' ? ($('#editRfidReadingId').val() || []).map(id => parseInt(id)) : [];
                            const mIds = modbusOptions.trim() !== '' ? ($('#editModbusId').val() || []).map(id => parseInt(id)) : [];
                            const sIds = sensorOptions.trim() !== '' ? ($('#editSensorId').val() || []).map(id => parseInt(id)) : [];

                            // Validaciones básicas
                            if (!pId) { Swal.showValidationMessage('Debe seleccionar un Producto.'); $('#editProductListId').select2('open'); return false; }
                            if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) { Swal.showValidationMessage('Debe seleccionar al menos un Puesto (RFID), Báscula o Sensor.'); if (rfidOptions.trim() !== '') $('#editRfidReadingId').select2('open'); else if (modbusOptions.trim() !== '') $('#editModbusId').select2('open'); else if (sensorOptions.trim() !== '') $('#editSensorId').select2('open'); return false; }

                            // Nota: La lógica de comprobación de duplicados podría añadirse aquí también si es necesario para la edición.
                            //       Habría que excluir la fila actual (row.id !== currentId) de la comprobación.

                            // Devolver los datos a enviar
                            return { client_id: parseInt(pId), rfid_reading_ids: rIds, modbus_ids: mIds, sensor_ids: sIds };
                        },
                        // Al cerrar el modal
                        didClose: () => { selectedRfidColor = null; stopQrScanner(); startRefreshInterval(); } // Limpiar, detener escáner, reanudar refresco
                    }).then((result) => {
                        // Si se confirmó la edición
                        if (result.isConfirmed) {
                            const relationId = $('#editRelationId').val(); // ID de la relación a editar
                            // Enviar petición PUT/PATCH al servidor
                            $.ajax({
                                url: `${relationsApiUrl}/${relationId}`, method: 'PUT', contentType: 'application/json', data: JSON.stringify(result.value),
                                success: function(response) { Swal.fire({ title: 'Éxito', text: response.message || 'Asignación actualizada.', icon: 'success', timer: 2000, showConfirmButton: false }); table.ajax.reload(null, false); }, // Recargar tabla sin resetear paginación
                                error: function(xhr) { let errorMsg = 'Error al actualizar la asignación.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error PUT/PATCH:", xhr); Swal.fire('Error', errorMsg, 'error'); }
                            });
                        }
                    });
                });


                $('#relationsTable tbody').on('click', '.delete-btn', function() {
                    // Comprobar permiso
                    if (!canDeleteRfidPost) { Swal.fire('Acceso denegado', 'No tiene permiso para eliminar asignaciones.', 'warning'); return; }

                    const button = this;
                    const id = $(button).data('id'); // ID de la relación a eliminar
                    stopRefreshInterval(); // Pausar refresco

                    // Mostrar modal de confirmación para eliminar
                    Swal.fire({
                        title: '¿Estás seguro?', text: "¡Esta acción no se puede deshacer!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar', cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                        customClass: { confirmButton: 'btn btn-danger mx-1', cancelButton: 'btn btn-secondary mx-1' }, buttonsStyling: false
                    }).then((result) => {
                        // Si se confirma la eliminación
                        if (result.isConfirmed) {
                            // Enviar petición DELETE al servidor
                            $.ajax({
                                url: `${relationsApiUrl}/${id}`, method: 'DELETE',
                                success: function(response) { Swal.fire({ title: 'Eliminado', text: response.message || 'Asignación eliminada.', icon: 'success', timer: 2000, showConfirmButton: false }); table.row($(button).closest('tr')).remove().draw(false); startRefreshInterval(); }, // Eliminar fila de la tabla y reanudar refresco
                                error: function(xhr) { let errorMsg = 'Error al eliminar la asignación.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error DELETE:", xhr); Swal.fire('Error', errorMsg, 'error'); startRefreshInterval(); } // Mostrar error y reanudar refresco
                            });
                        } else {
                            startRefreshInterval(); // Si se cancela, reanudar refresco
                        }
                    });
                });


                // Botón flotante de refrescar manualmente
                $('#refreshTableBtn').on('click', function() {
                    console.log('Refrescando tabla manualmente...');
                    if (table && $.fn.DataTable.isDataTable('#relationsTable')) {
                        // Guardar estado actual
                        let pageInfo = table.page.info();
                        let searchVal = table.search();
                        const historyChecked = $('#showHistorySwitch').is(':checked');
                        const unassignedChecked = $('#showUnassignedSwitch').is(':checked');

                        // Recargar datos AJAX
                        table.ajax.reload(function() {
                            // Callback después de recargar: restaurar estado
                            if ($.fn.DataTable.isDataTable('#relationsTable')) {
                                $('#showHistorySwitch').prop('checked', historyChecked);
                                $('#showUnassignedSwitch').prop('checked', unassignedChecked);
                                table.search(searchVal).page(pageInfo.page).draw('page'); // Aplicar búsqueda y paginación guardadas
                            }
                        }, false); // false para no resetear paginación

                        // Mostrar notificación toast
                        const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
                        toastMixin.fire({ icon: 'info', title: 'Tabla actualizada' });
                    }
                });

            }); // Fin de .always() de loadSelectOptions

            // --- Manejo del intervalo de refresco con visibilidad de la página ---
            // Manejo de visibilidad de la página
            let isPageVisible = true;
            
            // Usar Page Visibility API si está disponible
            if (typeof document.hidden !== 'undefined') {
                document.addEventListener('visibilitychange', function() {
                    isPageVisible = !document.hidden;
                    if (isPageVisible) {
                        console.log('Página visible, reanudando actualizaciones...');
                        startRefreshInterval();
                    } else {
                        console.log('Página oculta, pausando actualizaciones...');
                        stopRefreshInterval();
                    }
                });
            }
            
            // Manejo de eventos tradicionales para compatibilidad
            $(window).on({
                'unload pagehide': function() {
                    stopRefreshInterval();
                },
                'blur': function() {
                    if (refreshIntervalId) {
                        console.log('Ventana perdió foco, pausando refresco.');
                        stopRefreshInterval();
                    }
                },
                'focus': function() {
                    if (!refreshIntervalId) {
                        console.log('Ventana recuperó foco, reanudando refresco.');
                        startRefreshInterval();
                    }
                }
            });

        }); // Fin de $(document).ready()

        // --- Funciones Escáner QR ---
        let html5QrCode = null; // Variable para la instancia del escáner

        // Inicia el escáner QR
        function startQrScanner(targetSelectId) {
            const qrReaderId = targetSelectId.startsWith('edit') ? 'qr-reader-edit' : 'qr-reader'; // ID del div contenedor del escáner
            const qrReaderElement = document.getElementById(qrReaderId);
            if (!qrReaderElement) { console.error(`Elemento ${qrReaderId} no encontrado.`); return; } // Salir si no existe el div
            if (html5QrCode && html5QrCode.isScanning) { console.warn("El escáner QR ya está activo."); return; } // Evitar iniciar múltiples veces

            qrReaderElement.style.display = 'block'; // Mostrar el contenedor del escáner
            if (!html5QrCode) { html5QrCode = new Html5Qrcode(qrReaderId); } // Crear instancia si no existe

            const config = { fps: 10, qrbox: { width: 250, height: 250 } }; // Configuración del escáner
            const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timerProgressBar: true }); // Para notificaciones

            // Iniciar escaneo
            html5QrCode.start(
                { facingMode: "environment" }, // Usar cámara trasera preferentemente
                config,
                // Callback al detectar QR
                (decodedText, decodedResult) => {
                    console.log(`QR detectado: ${decodedText}`);
                    stopQrScanner(); // Detener escáner después de detectar
                    const $targetSelect = $(`#${targetSelectId}`); // Select2 al que añadir la opción
                    // Buscar la opción por texto (ignorando espacios extra)
                    const $option = $targetSelect.find('option').filter((i, opt) => $(opt).text().trim() === decodedText.trim());

                    if ($option.length > 0) { // Si se encuentra la opción
                        const optionValue = $option.val();
                        let currentValues = $targetSelect.val() || []; // Obtener valores actuales (asegurarse que sea array)
                        if (!Array.isArray(currentValues)) { currentValues = [currentValues]; }

                        // Añadir valor si no está ya seleccionado
                        if (!currentValues.includes(optionValue)) {
                            currentValues.push(optionValue);
                            $targetSelect.val(currentValues).trigger('change'); // Seleccionar y disparar evento change
                            toastMixin.fire({ icon: 'success', title: `Puesto ${decodedText} añadido`, timer: 1500 });
                        } else {
                            toastMixin.fire({ icon: 'info', title: `Puesto ${decodedText} ya estaba seleccionado`, timer: 1500 });
                        }
                    } else { // Si no se encuentra la opción
                        toastMixin.fire({ icon: 'warning', title: `Puesto ${decodedText} no encontrado en la lista`, timer: 2500 });
                    }
                },
                // Callback de error (ignorar errores menores como QR no encontrado en frame)
                (errorMessage) => { /* console.debug(`QR error: ${errorMessage}`); */ }
            ).catch((err) => {
                // Capturar errores graves al iniciar (permisos, cámara no encontrada)
                console.error("Error grave al iniciar escáner QR:", err);
                let errorMsg = 'No se pudo iniciar el escáner QR.';
                if (err.name === 'NotAllowedError' || (typeof err === 'string' && err.includes('Permission denied'))) { errorMsg = 'Permiso de cámara denegado.'; }
                else if (err.name === 'NotFoundError') { errorMsg = 'No se encontró una cámara compatible.'; }
                Swal.fire('Error de Cámara', errorMsg, 'error');
                qrReaderElement.style.display = 'none'; // Ocultar contenedor si falla
            });
        }

        // Detiene el escáner QR si está activo
        function stopQrScanner() {
            const qrReaderEdit = document.getElementById('qr-reader-edit');
            const qrReaderAdd = document.getElementById('qr-reader');

            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log("Escáner QR detenido correctamente.");
                    // Ocultar ambos contenedores por si acaso
                    if (qrReaderAdd) qrReaderAdd.style.display = 'none';
                    if (qrReaderEdit) qrReaderEdit.style.display = 'none';
                }).catch(err => {
                    console.error("Error al detener escáner QR:", err);
                    // Asegurarse de ocultar los contenedores incluso si hay error al detener
                    if (qrReaderAdd) qrReaderAdd.style.display = 'none';
                    if (qrReaderEdit) qrReaderEdit.style.display = 'none';
                });
            } else {
                // Si no estaba escaneando, simplemente asegurarse de que los divs estén ocultos
                if (qrReaderAdd) qrReaderAdd.style.display = 'none';
                if (qrReaderEdit) qrReaderEdit.style.display = 'none';
            }
        }

    </script>
@endpush
