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
        {{-- Contenedor para controles adicionales (Switch) --}}
        {{-- Este div se moverá con JS a la posición correcta --}}
        <div class="history-switch-container d-flex justify-content-end mb-2" style="display: none;"> {{-- Oculto inicialmente --}}
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
            .top-controls-row .dt-buttons-placeholder, /* Usar los placeholders del DOM */
            .top-controls-row .switch-placeholder {
                width: 100%; /* Ocupar todo el ancho */
                text-align: left; /* Alinear a la izquierda */
                margin-bottom: 0.5em; /* Espacio entre filas de controles */
            }
             .top-controls-row .switch-placeholder {
                 justify-content: flex-start !important; /* Alinear switch a la izquierda */
                 padding-left: 0; /* Quitar padding si es necesario */
             }
             .dt-buttons .btn {
                 margin-right: 5px; /* Espacio entre botones */
                 margin-bottom: 5px;
             }
             /* Ajustar filtro y length menu en móviles */
             .filter-length-row .col-md-6 {
                 width: 100%;
                 text-align: left;
                 margin-bottom: 0.5em;
             }
             .dataTables_filter {
                 float: none !important; /* Quitar float */
             }
             .dataTables_length {
                 float: none !important; /* Quitar float */
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
            font-family: "Font Awesome 6 Free"; /* Asume que FA6 está cargado por el layout */
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

        /* Layout de columnas auto-adaptable */
        .select2-container--default .select2-results__options {
            display: grid; /* Usar Grid */
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); /* Columnas automáticas */
            gap: 8px; /* Espacio entre tarjetas */
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
            background-color: #6c757d !important; /* Color secundario de Bootstrap */
            border-color: #6c757d !important;
            color: #fff !important;
        }
        .dt-button.buttons-columnVisibility.active {
            background-color: #5a6268 !important; /* Un poco más oscuro cuando activo */
            border-color: #545b62 !important;
        }
        .dt-button-collection .dt-button {
            min-width: 150px; /* Ancho mínimo para cada opción de columna */
        }
        /* Estilo para el contenedor del length menu, filtro y controles personalizados */
        .dataTables_wrapper .top-controls-row { margin-bottom: 1em; } /* Fila para botones y switch */
        .dataTables_wrapper .filter-length-row { margin-bottom: 1em; } /* Fila para length y filter */

        .dataTables_wrapper .dataTables_length label,
        .dataTables_wrapper .dataTables_filter label {
             margin-right: 0.5em; /* Espacio entre etiqueta y control */
             margin-bottom: 0; /* Evitar margen extra */
        }
         .dataTables_wrapper .dataTables_length select {
             width: auto; /* Ajusta el ancho del select */
             display: inline-block; /* Permite que esté en línea con la etiqueta */
             padding: 0.375rem 1.75rem 0.375rem 0.75rem; /* Ajusta padding */
             border: 1px solid #ced4da;
             border-radius: 0.25rem;
             vertical-align: middle; /* Alinear verticalmente */
         }
         /* Ancho fijo al input de búsqueda */
         .dataTables_filter input.form-control {
             width: 300px !important; /* Ajusta este valor según necesites */
             display: inline-block !important; /* Para que no ocupe toda la fila si hay espacio */
             vertical-align: middle; /* Alinear verticalmente */
         }
         /* Estilo para el switch */
         .form-switch .form-check-input {
             cursor: pointer;
             width: 3em; /* Ancho del switch */
             height: 1.5em; /* Alto del switch */
             margin-left: -0.5em; /* Ajuste de posición */
         }
         .form-switch .form-check-label {
             padding-left: 1em; /* Espacio entre switch y etiqueta */
             vertical-align: middle;
         }
         /* Alineación de botones y switch */
         .dt-buttons .btn { margin-right: 0.25em;} /* Pequeño espacio entre botones */
         .switch-placeholder { /* Contenedor del switch */
            display: flex;
            justify-content: flex-end;
            align-items: center;
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
            z-index: 1050; /* Asegura que esté por encima de otros elementos */
            font-size: 1.5rem; /* Tamaño del icono */
         }

         /* **MODIFICADO**: Estilo para el contador RFID */
         .rfid-counter-display {
             font-size: 1.6em;
             color: #6c757d; /* Color secundario de Bootstrap */
             /* margin-left: 6%; */ /* Quitar margen izquierdo */
             /* margin-top: -0.8em; */ /* Quitar margen superior negativo */
             margin-bottom: 1em; /* Mantener espacio inferior */
             text-align: center; /* Centrar el texto */
             height: 1em; /* Evitar saltos de layout */
             display: block; /* Asegurar que sea un bloque */
             width: 100%; /* Ocupar todo el ancho disponible */
         }
         /* Contenedor para el contador (opcional, para mejor control) */
         .modal-counter-container {
             margin-bottom: 1em; /* Espacio antes del primer select */
             padding-top: 0.5em; /* Pequeño espacio superior */
         }

         /* Ajustar responsividad del contador si es necesario */
         /* @media (max-width: 576px) {
             .rfid-counter-display { text-align: left; margin-left: 0; }
         } */
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
    <script src="https://unpkg.com/html5-qrcode"></script>


    <script>
        // --- **CORREGIDO**: Definir permisos en JavaScript (Método seguro) ---
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
                    table.ajax.reload(function() {
                         if ($.fn.DataTable.isDataTable('#relationsTable')) { // Volver a comprobar por si se destruyó mientras tanto
                            table.search(searchVal).page(pageInfo.page).draw('page');
                         }
                    }, false);
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

        // Variables globales para opciones
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
            const productsPromise = $.get(productsApiUrl).done(data => { productListOptions = data.map(p => `<option value="${p.id}">${p.name}</option>`).join(''); }).fail(() => console.error('Error al cargar productos.'));
            const rfidsPromise = $.get(rfidsApiUrl).done(data => { rfidOptions = data.map(r => `<option value="${r.id}" data-color="${r.rfid_color?.name?.toLowerCase() || ''}">${r.name}</option>`).join(''); }).fail(() => console.error('Error al cargar RFIDs.'));
            const modbusesPromise = $.get(modbusesApiUrl).done(data => { modbusOptions = data.map(m => `<option value="${m.id}">${m.name}</option>`).join(''); }).fail(() => console.error('Error al cargar básculas.'));
            const sensorsPromise = $.get(sensorsApiUrl).done(data => { sensorOptions = data.map(s => `<option value="${s.id}">${s.name}</option>`).join(''); }).fail(() => console.error('Error al cargar sensores.'));
            return $.when(productsPromise, rfidsPromise, modbusesPromise, sensorsPromise);
        }

        // --- Document Ready ---
        $(document).ready(function() {

            // Filtro personalizado para mostrar/ocultar historial
            $.fn.dataTable.ext.search.push(
                function( settings, data, dataIndex, rowData, counter ) {
                    if (settings.nTable.id !== 'relationsTable') { return true; }
                    var showHistory = $('#showHistorySwitch').is(':checked');
                    var finishDate = rowData.finish_at;
                    if (showHistory) { return true; }
                    else { return finishDate === null || finishDate === undefined || finishDate === ''; }
                }
            );

            // Carga las opciones primero
            loadSelectOptions().always(function() {
                // Inicializa DataTable DESPUÉS de cargar las opciones
                table = $('#relationsTable').DataTable({
                    responsive: true,
                    pageLength: 300,
                    lengthMenu: [ [10, 20, 50, 300, -1], [10, 20, 50, 300,  "All"] ],
                    stateSave: true,
                    ajax: {
                        url: relationsApiUrl,
                        dataSrc: '',
                        error: function(xhr, error, thrown) {
                            if (error === 'abort') {
                                console.warn('Petición AJAX de DataTables abortada.'); return;
                            }
                            let errorMsg = 'Error desconocido al cargar datos.';
                            if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message;
                            else if (xhr.responseText) { try { errorMsg = JSON.parse(xhr.responseText).message || xhr.responseText.substring(0,200); } catch(e){ errorMsg = xhr.responseText.substring(0,200); } }
                            console.error("Error AJAX DataTable:", xhr.status, error, thrown, xhr.responseText);
                            Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Error al cargar datos.', timer: 1500, showConfirmButton: false, timerProgressBar: true });
                        }
                    },
                    columns: [
                        { data: 'product_list.name', defaultContent: 'Sin asignar' }, // 0
                        { // 1: RFID
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
                        { data: 'modbus.name', defaultContent: 'N/A', render: (d, t, r) => r.modbus?.name || 'N/A' }, // 2
                        { data: 'sensor.name', defaultContent: 'N/A', render: (d, t, r) => r.sensor?.name || 'N/A' }, // 3
                        { data: 'operator_name', name: 'operator_name', defaultContent: 'Sin asignar' }, // 4
                        { // 5: Fecha Inicio
                            data: 'created_at',
                            render: function(data) { if (!data) return 'N/A'; try { const date = new Date(data); return !isNaN(date.getTime()) ? date.toLocaleString('es-ES') : 'Fecha inválida'; } catch(e) { return 'Fecha inválida'; } }
                        },
                        { // 6: Fecha Fin
                            data: 'finish_at',
                            render: function(data) { if (!data) return 'En curso'; try { const date = new Date(data); return !isNaN(date.getTime()) ? date.toLocaleString('es-ES') : 'Fecha inválida'; } catch(e) { return 'Fecha inválida'; } }
                        },
                        { // 7: Acciones (**CORREGIDO**: Usar variables JS para permisos)
                            data: null, orderable: false, searchable: false,
                            render: function(data, type, row) {
                                let buttonsHtml = '';
                                // Usar las variables JS definidas al inicio del script
                                if (canEditRfidPost) {
                                    buttonsHtml += `<button class="btn btn-sm btn-secondary edit-btn" data-id="${row.id}" data-product_list_id="${row.product_list_id || ''}" data-rfid_reading_id="${row.rfid_reading_id || ''}" data-modbus_id="${row.modbus_id || ''}" data-sensor_id="${row.sensor_id || ''}" style="margin-right: 5px;"><i class="bi bi-pencil-square"></i> Editar</button>`;
                                }
                                if (canDeleteRfidPost) {
                                    buttonsHtml += `<button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}"><i class="bi bi-trash"></i> Eliminar</button>`;
                                }
                                return buttonsHtml; // Devolver el HTML generado
                            }
                        }
                    ],
                    dom: "<'row top-controls-row'<'col-sm-12 col-md-6 dt-buttons-placeholder'><'col-sm-12 col-md-6 switch-placeholder'>>" +
                         "<'row filter-length-row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    buttons: [
                        {
                            text: '<i class="bi bi-plus-circle"></i> Asignar Confección',
                            className: 'btn btn-primary',
                            action: function(e, dt, node, config) {
                                // --- Código del modal de Asignar ---
                                // **MODIFICADO**: Añadir contenedor para el contador al principio
                                let modalHtml = `<div class="modal-counter-container"><div id="rfidCounterAdd" class="rfid-counter-display"></div></div>`;
                                modalHtml += `<div class="select-block"><label for="productListId">Producto:</label><select id="productListId" class="swal2-input custom-select-style"><option value="" disabled selected>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                                if (rfidOptions.trim() !== '') {
                                    // **MODIFICADO**: Quitar el div del contador de aquí
                                    modalHtml += `<div class="select-block">
                                                    <label for="rfidReadingId">Puesto / tarjeta</label>
                                                    <select id="rfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select>
                                                 </div>`;
                                }
                                if (modbusOptions.trim() !== '') modalHtml += `<div class="select-block"><label for="modbusId">Báscula:</label><select id="modbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`;
                                if (sensorOptions.trim() !== '') modalHtml += `<div class="select-block"><label for="sensorId">Sensor:</label><select id="sensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`;
                                modalHtml += `<div id="qr-reader" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                                Swal.fire({
                                    title: 'Asignar Confección', width: '80%', html: modalHtml,
                                    showCancelButton: true,
                                    showDenyButton: rfidOptions.trim() !== '', // Mostrar botón escanear SOLO si hay RFIDs
                                    confirmButtonText: '<i class="bi bi-check-square"></i> AÑADIR',
                                    cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR',
                                    denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR RFID',
                                    customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' },
                                    buttonsStyling: false,
                                    preDeny: () => { if (rfidOptions.trim() !== '') startQrScanner('rfidReadingId'); else Swal.showValidationMessage('No hay RFIDs disponibles.'); return false; },
                                    didOpen: () => {
                                        selectedRfidColor = null;
                                        stopRefreshInterval();
                                        let rfidScrollPosition = 0;
                                        $('#productListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection });
                                        if (rfidOptions.trim() !== '') {
                                            const $rfidSelect = $('#rfidReadingId');
                                            const $rfidCounter = $('#rfidCounterAdd'); // Referencia al contador

                                            $rfidSelect.select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione RFID --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter });

                                            // Evento change para actualizar contador y filtro de color
                                            $rfidSelect.on('change', function() {
                                                const resultsContainer = $('.select2-results__options');
                                                if (resultsContainer.length > 0) rfidScrollPosition = resultsContainer.scrollTop();
                                                let selectedValues = $(this).val() || [];

                                                // Actualizar contador
                                                $rfidCounter.text(selectedValues.length > 0 ? `${selectedValues.length} Puestos  Seleccionados` : '');

                                                // Lógica filtro color
                                                if (selectedValues.length > 0) {
                                                    if (selectedRfidColor === null) {
                                                        let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`);
                                                        if(firstSelectedOption.length) selectedRfidColor = firstSelectedOption.data('color');
                                                    }
                                                } else {
                                                    selectedRfidColor = null;
                                                }
                                                if ($(this).data('select2')?.isOpen()) {
                                                    $(this).select2('close').select2('open');
                                                }
                                            });
                                            $rfidSelect.on('select2:open', function() { setTimeout(() => { const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) resultsContainer.scrollTop(rfidScrollPosition); }, 0); });
                                            $rfidSelect.trigger('change'); // Disparar change inicial
                                        }
                                        if (modbusOptions.trim() !== '') { $('#modbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }); $('#modbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                                        if (sensorOptions.trim() !== '') { $('#sensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }); $('#sensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                                        $('#productListId').val(null).trigger('change');
                                    },
                                    preConfirm: () => {
                                        const pId = $('#productListId').val(); const rIds = rfidOptions.trim() !== '' ? ($('#rfidReadingId').val() || []) : []; const mIds = modbusOptions.trim() !== '' ? ($('#modbusId').val() || []) : []; const sIds = sensorOptions.trim() !== '' ? ($('#sensorId').val() || []) : [];
                                        if (!pId) { Swal.showValidationMessage('Producto obligatorio.'); $('#productListId').select2('open'); return false; }
                                        if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) { Swal.showValidationMessage('Seleccione RFID, Báscula o Sensor.'); if (rfidOptions.trim() !== '') $('#rfidReadingId').select2('open'); else if (modbusOptions.trim() !== '') $('#modbusId').select2('open'); else if (sensorOptions.trim() !== '') $('#sensorId').select2('open'); return false; }
                                        return { client_id: parseInt(pId), rfid_reading_ids: rIds.length ? rIds.map(id => +id) : [], modbus_ids: mIds.length ? mIds.map(id => +id) : [], sensor_ids: sIds.length ? sIds.map(id => +id) : [] };
                                    },
                                    didClose: () => {
                                        selectedRfidColor = null;
                                        stopQrScanner();
                                        startRefreshInterval();
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $.ajax({
                                            url: relationsApiUrl, method: 'POST', contentType: 'application/json', data: JSON.stringify(result.value),
                                            success: function(response) { Swal.fire({ title: 'Éxito', text: response.message || 'Relación añadida.', icon: 'success', timer: 2000, showConfirmButton: false }); table.ajax.reload(); },
                                            error: function(xhr) { let errorMsg = 'Error al añadir.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error POST:", xhr); Swal.fire('Error', errorMsg, 'error'); }
                                        });
                                        // No es necesario reanudar aquí, didClose lo hace
                                    }
                                });
                                // --- Fin Código del modal de Asignar ---
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
                        { extend: 'colvis', text: '<i class="bi bi-eye"></i> Columnas', className: 'btn btn-secondary', titleAttr: 'Mostrar/Ocultar columnas' },
                        { text: '<i class="bi bi-broadcast"></i> Live Rfid', className: 'btn btn-info', action: function () { window.open('/live-rfid/', '_blank'); }, titleAttr: 'Ver RFID en tiempo real' }
                    ],
                    order: [[6, 'desc'], [5, 'desc'], [0, 'asc']], // Orden inicial: Fecha Fin desc, Fecha Inicio desc, Confección asc
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json',
                        search: "_INPUT_",
                        searchPlaceholder: "Buscar..."
                    },
                    initComplete: function(settings, json) {
                        const api = this.api();
                        api.buttons().container().appendTo('.dt-buttons-placeholder');
                        $('.history-switch-container').appendTo('.switch-placeholder').show();
                        $('.dataTables_filter input').addClass('form-control');
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                        table.draw();
                        $('#showHistorySwitch').on('change', function() { table.draw(); });
                        startRefreshInterval();
                    }
                });

                // --- Delegación de eventos para botones dentro de la tabla ---
                // Editar Relación
                $('#relationsTable tbody').on('click', '.edit-btn', function() {
                    // **CORREGIDO**: Verificar permiso usando variable JS antes de abrir
                    if (!canEditRfidPost) {
                         Swal.fire('Acceso denegado', 'No tiene permiso para editar relaciones.', 'warning');
                         return;
                    }

                    const $button = $(this); const currentId = $button.data('id');
                    const getIdsArray = (data) => (data || '').toString().split(',').map(id => id.trim()).filter(Boolean);
                    const currentProductListId = $button.data('product_list_id'); const currentRfidReadingIds = getIdsArray($button.data('rfid_reading_id')); const currentModbusIds = getIdsArray($button.data('modbus_id')); const currentSensorIds = getIdsArray($button.data('sensor_id'));

                    // **MODIFICADO**: Añadir contenedor para el contador al principio
                    let modalHtmlEdit = `<div class="modal-counter-container"><div id="rfidCounterEdit" class="rfid-counter-display"></div></div>`;
                    modalHtmlEdit += `<input type="hidden" id="editRelationId" value="${currentId}">`;
                    modalHtmlEdit += `<div class="select-block"><label for="editProductListId">Producto:</label><select id="editProductListId" class="swal2-input custom-select-style"><option value="" disabled>-- Seleccione Producto --</option>${productListOptions}</select></div>`;
                    if (rfidOptions.trim() !== '') {
                        // **MODIFICADO**: Quitar el div del contador de aquí
                        modalHtmlEdit += `<div class="select-block">
                                            <label for="editRfidReadingId">Puesto/tarjeta:</label>
                                            <select id="editRfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select>
                                         </div>`;
                    }
                    if (modbusOptions.trim() !== '') modalHtmlEdit += `<div class="select-block"><label for="editModbusId">Báscula:</label><select id="editModbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select></div>`;
                    if (sensorOptions.trim() !== '') modalHtmlEdit += `<div class="select-block"><label for="editSensorId">Sensor:</label><select id="editSensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select></div>`;
                    modalHtmlEdit += `<div id="qr-reader-edit" style="width:300px; max-width: 100%; margin: 1em auto; display: none; border: 1px solid #ccc; border-radius: 5px;"></div>`;

                    Swal.fire({
                        title: 'Editar Relación', width: '80%', html: modalHtmlEdit,
                        showCancelButton: true,
                        showDenyButton: rfidOptions.trim() !== '',
                        confirmButtonText: '<i class="bi bi-save"></i> ACTUALIZAR', cancelButtonText: '<i class="bi bi-x-square"></i> CANCELAR', denyButtonText: '<i class="bi bi-qr-code"></i> ESCANEAR RFID',
                        customClass: { confirmButton: 'btn btn-success mx-1', denyButton: 'btn btn-info mx-1', cancelButton: 'btn btn-danger mx-1' }, buttonsStyling: false,
                        preDeny: () => { if (rfidOptions.trim() !== '') startQrScanner('editRfidReadingId'); else Swal.showValidationMessage('No hay RFIDs disponibles.'); return false; },
                        didOpen: () => {
                            stopRefreshInterval();
                            let editRfidScrollPosition = 0;
                            $('#editProductListId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', templateResult: formatProductOption, templateSelection: formatProductSelection }).val(currentProductListId).trigger('change');
                            if (rfidOptions.trim() !== '') {
                                const $editRfidSelect = $('#editRfidReadingId');
                                const $editRfidCounter = $('#rfidCounterEdit'); // Referencia al contador

                                $editRfidSelect.select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione RFID --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatRfidSelection, matcher: rfidMatcher, sorter: numericSorter }).val(currentRfidReadingIds).trigger('change'); // Seleccionar valores actuales

                                // Evento change para actualizar contador y filtro de color
                                $editRfidSelect.on('change', function() {
                                    const resultsContainer = $('.select2-results__options');
                                    if (resultsContainer.length > 0) editRfidScrollPosition = resultsContainer.scrollTop();
                                    let selectedValues = $(this).val() || [];

                                    // Actualizar contador
                                    $editRfidCounter.text(selectedValues.length > 0 ? `${selectedValues.length} RFID(s) seleccionado(s)` : '');

                                    // Lógica filtro color
                                    if (selectedValues.length > 0) {
                                        let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`);
                                        if(firstSelectedOption.length) selectedRfidColor = firstSelectedOption.data('color');
                                        else selectedRfidColor = null; // Limpiar si la opción ya no existe
                                    } else {
                                        selectedRfidColor = null;
                                    }
                                    if ($(this).data('select2')?.isOpen()) {
                                        $(this).select2('close').select2('open');
                                    }
                                });
                                $editRfidSelect.on('select2:open', function() { setTimeout(() => { const resultsContainer = $('.select2-results__options'); if (resultsContainer.length > 0) resultsContainer.scrollTop(editRfidScrollPosition); }, 0); });
                                $editRfidSelect.trigger('change'); // Disparar change inicial para mostrar contador con preseleccionados
                            }
                            if (modbusOptions.trim() !== '') { $('#editModbusId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Báscula --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentModbusIds).trigger('change'); $('#editModbusId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                            if (sensorOptions.trim() !== '') { $('#editSensorId').select2({ dropdownParent: Swal.getPopup(), width: 'resolve', placeholder: '-- Seleccione Sensor --', closeOnSelect: false, templateResult: formatOption, templateSelection: formatSelection }).val(currentSensorIds).trigger('change'); $('#editSensorId').on('select2:closing', e => { if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) e.preventDefault(); }); }
                        },
                        preConfirm: () => {
                            const pId = $('#editProductListId').val(); const rIds = rfidOptions.trim() !== '' ? ($('#editRfidReadingId').val() || []) : []; const mIds = modbusOptions.trim() !== '' ? ($('#editModbusId').val() || []) : []; const sIds = sensorOptions.trim() !== '' ? ($('#editSensorId').val() || []) : [];
                            if (!pId) { Swal.showValidationMessage('Producto obligatorio.'); $('#editProductListId').select2('open'); return false; }
                            if (rIds.length === 0 && mIds.length === 0 && sIds.length === 0) { Swal.showValidationMessage('Seleccione RFID, Báscula o Sensor.'); if (rfidOptions.trim() !== '') $('#editRfidReadingId').select2('open'); else if (modbusOptions.trim() !== '') $('#editModbusId').select2('open'); else if (sensorOptions.trim() !== '') $('#editSensorId').select2('open'); return false; }
                            return { product_list_id: parseInt(pId), rfid_reading_ids: rIds.map(id => parseInt(id)), modbus_ids: mIds.map(id => parseInt(id)), sensor_ids: sIds.map(id => parseInt(id)) };
                        },
                        didClose: () => {
                            selectedRfidColor = null;
                            stopQrScanner();
                            startRefreshInterval();
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const relationId = $('#editRelationId').val();
                            $.ajax({
                                url: `${relationsApiUrl}/${relationId}`, method: 'PUT', contentType: 'application/json', data: JSON.stringify(result.value),
                                success: function(response) { Swal.fire({ title: 'Éxito', text: response.message || 'Relación actualizada.', icon: 'success', timer: 2000, showConfirmButton: false }); table.ajax.reload(null, false); },
                                error: function(xhr) { let errorMsg = 'Error al actualizar.'; if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message; console.error("Error PUT/PATCH:", xhr); Swal.fire('Error', errorMsg, 'error'); }
                            });
                        }
                    });
                });

                // Eliminar Relación
                $('#relationsTable tbody').on('click', '.delete-btn', function() {
                     // **CORREGIDO**: Verificar permiso usando variable JS antes de mostrar confirmación
                    if (!canDeleteRfidPost) {
                         Swal.fire('Acceso denegado', 'No tiene permiso para eliminar relaciones.', 'warning');
                         return;
                    }

                    const button = this;
                    const id = $(button).data('id');
                    stopRefreshInterval(); // Pausar antes de confirmar
                    Swal.fire({
                        title: '¿Estás seguro?', text: "¡No se puede deshacer!", icon: 'warning',
                        showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="bi bi-trash"></i> Sí, eliminar', cancelButtonText: '<i class="bi bi-x-circle"></i> Cancelar',
                        customClass: { confirmButton: 'btn btn-danger mx-1', cancelButton: 'btn btn-secondary mx-1' }, buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `${relationsApiUrl}/${id}`, method: 'DELETE',
                                success: function(response) {
                                     Swal.fire({ title: 'Eliminado', text: response.message || 'Relación eliminada.', icon: 'success', timer: 2000, showConfirmButton: false });
                                     table.row($(button).closest('tr')).remove().draw(false);
                                     startRefreshInterval(); // Reanudar después de éxito
                                 },
                                error: function(xhr) {
                                    let errorMsg = 'Error al eliminar.';
                                    if (xhr.responseJSON?.message) errorMsg = xhr.responseJSON.message;
                                    console.error("Error DELETE:", xhr); Swal.fire('Error', errorMsg, 'error');
                                    startRefreshInterval(); // Reanudar también en caso de error
                                 }
                            });
                        } else {
                            startRefreshInterval(); // Reanudar si se cancela
                        }
                    });
                });

                // Event listener para el botón flotante de refrescar
                $('#refreshTableBtn').on('click', function() {
                    console.log('Refrescando tabla manualmente...');
                    if (table && $.fn.DataTable.isDataTable('#relationsTable')) {
                        let pageInfo = table.page.info();
                        let searchVal = table.search();
                        table.ajax.reload(function() {
                            if ($.fn.DataTable.isDataTable('#relationsTable')) {
                                table.search(searchVal).page(pageInfo.page).draw('page');
                            }
                        }, false);
                        const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true });
                        toastMixin.fire({ icon: 'info', title: 'Tabla actualizada' });
                    }
                });

            }); // Fin de .always() de loadSelectOptions

            // Limpiar el intervalo cuando la página se descarga/oculta
            $(window).on('unload pagehide', function() {
                stopRefreshInterval();
            });
             // Reanudar intervalo si la página vuelve a ser visible
            $(window).on('pageshow', function(event) {
                if (event.originalEvent.persisted === false && !refreshIntervalId) {
                    startRefreshInterval();
                }
            });
             // Pausar/Reanudar con foco
            $(window).on('blur', function() { if (refreshIntervalId) stopRefreshInterval(); });
            $(window).on('focus', function() { if (!refreshIntervalId) startRefreshInterval(); });


        }); // Fin de $(document).ready()

        // --- Funciones Escáner QR ---
        let html5QrCode = null;

        function startQrScanner(targetSelectId) {
            const qrReaderId = targetSelectId.startsWith('edit') ? 'qr-reader-edit' : 'qr-reader';
            const qrReaderElement = document.getElementById(qrReaderId);
            if (!qrReaderElement) { console.error(`Elemento ${qrReaderId} no encontrado.`); return; }
            if (html5QrCode && html5QrCode.isScanning) { console.warn("El escáner ya está activo."); return; }
            qrReaderElement.style.display = 'block';
            if (!html5QrCode) { html5QrCode = new Html5Qrcode(qrReaderId); }
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            const toastMixin = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timerProgressBar: true });
            html5QrCode.start( { facingMode: "environment" }, config,
                (decodedText, decodedResult) => {
                    stopQrScanner();
                    const $targetSelect = $(`#${targetSelectId}`);
                    const $option = $targetSelect.find('option').filter((i, opt) => $(opt).text().trim() === decodedText.trim());
                    if ($option.length > 0) {
                        const optionValue = $option.val(); let currentValues = $targetSelect.val() || []; if (!Array.isArray(currentValues)) { currentValues = [currentValues]; }
                        if (!currentValues.includes(optionValue)) { currentValues.push(optionValue); $targetSelect.val(currentValues).trigger('change'); toastMixin.fire({ icon: 'success', title: `RFID ${decodedText} añadido`, timer: 1500 }); }
                        else { toastMixin.fire({ icon: 'info', title: `RFID ${decodedText} ya seleccionado`, timer: 1500 }); }
                    } else { toastMixin.fire({ icon: 'warning', title: `RFID ${decodedText} no encontrado en la lista`, timer: 2000 }); }
                }, (errorMessage) => { /* Ignorar */ }
            ).catch((err) => { console.error("Error al iniciar escáner:", err); Swal.fire('Error', 'No se pudo iniciar el escáner QR.', 'error'); qrReaderElement.style.display = 'none'; });
        }

        function stopQrScanner() {
            const qrReaderEdit = document.getElementById('qr-reader-edit'); const qrReaderAdd = document.getElementById('qr-reader');
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => { console.log("Escáner detenido."); if (qrReaderAdd) qrReaderAdd.style.display = 'none'; if (qrReaderEdit) qrReaderEdit.style.display = 'none'; }).catch(err => { console.error("Error al detener escáner:", err); if (qrReaderAdd) qrReaderAdd.style.display = 'none'; if (qrReaderEdit) qrReaderEdit.style.display = 'none'; });
            } else { if (qrReaderAdd) qrReaderAdd.style.display = 'none'; if (qrReaderEdit) qrReaderEdit.style.display = 'none'; }
        }
    </script>
@endpush
