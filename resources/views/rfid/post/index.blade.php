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
        <!-- Puedes agregar botones o título adicional si lo deseas -->
    </div>
    <div class="card-body">
        <!-- Contenedor con padding para separar la tabla del borde -->
        <div class="table-responsive p-3">
            <table id="relationsTable" class="display table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Confección</th>
                        <th>Puesto</th>
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
            background-color: #ffffff !important;
            border: 1px solid #808080 !important;
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
            max-height: 400px;
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
        // Variable global para guardar el color del primer RFID seleccionado
        let selectedRfidColor = null;

        // Función matcher para filtrar las opciones según el color seleccionado
        function rfidMatcher(params, data) {
            // Asegurarse de que el elemento exista
            if (!data.element) {
                return data;
            }
            // Si ya se seleccionó un color, filtrar las opciones que no coincidan
            if (selectedRfidColor) {
                let optionColor = $(data.element).data('color');
                if (!optionColor || optionColor.toLowerCase() !== selectedRfidColor.toLowerCase()) {
                    return null;
                }
            }
            // Si se escribe algo en la búsqueda, aplicar también el filtro por texto
            if ($.trim(params.term) === '') {
                return data;
            }
            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                return data;
            }
            return null;
        }

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

        // Funciones para formatear las opciones para multiselect (RFID, Básculas y Sensores)
        function formatOption(option) {
            if (!option.id) return option.text;
            let color = option.element ? option.element.getAttribute('data-color') : '';
            let colorMap = {
                red: "#dc3545",
                blue: "#007bff",
                yellow: "#ffc107",
                green: "#28a745"
            };
            if (colorMap.hasOwnProperty(color)) {
                var iconColor = colorMap[color];
                return $(`
                    <div class="rfid-option-card">
                        <div class="rfid-icon" style="color: ${iconColor};">
                            <i class="fa fa-id-card"></i>
                        </div>
                        <div class="rfid-text">${option.text}</div>
                    </div>
                `);
            } else {
                return $(`
                    <div class="rfid-option-card">
                        <div class="rfid-icon" style="color: #007bff;">
                            <i class="fa fa-id-card"></i>
                        </div>
                        <div class="rfid-text">${option.text} - ${color}</div>
                    </div>
                `);
            }
        }
        function formatSelection(option) {
            return option.text;
        }
        // Para Producto (single select) con icono distinto (fa-box)
        function formatProductOption(option) {
            if (!option.id) return option.text;
            return $(`
                <div class="rfid-option-card">
                    <div class="rfid-icon" style="color: #17a2b8;">
                        <i class="fa fa-box"></i>
                    </div>
                    <div class="rfid-text">${option.text}</div>
                </div>
            `);
        }
        function formatProductSelection(option) {
            return option.text;
        }
        // Función para formatear la selección del RFID con el color correspondiente
        function formatRfidSelection(option) {
            if (!option.id) return option.text;
            let color = option.element ? option.element.getAttribute('data-color') : '';
            let colorMap = {
                red: "#dc3545",
                blue: "#007bff",
                yellow: "#ffc107",
                green: "#28a745"
            };
            let iconColor = colorMap[color] || "#007bff"; // Color predeterminado si no existe
            return $(`
                <span style="color: ${iconColor};">
                    <i class="fa fa-id-card"></i> ${option.text}
                </span>
            `);
        }

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
                        `<option value="${rfid.id}" data-color="${rfid.rfid_color && rfid.rfid_color.name ? rfid.rfid_color.name.toLowerCase() : ''}">
                            ${rfid.name}
                        </option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar RFIDs.', 'error'); });

            $.get(modbusesApiUrl)
                .done((data) => {
                    modbusOptions = data.map(mb =>
                        `<option value="${mb.id}">${mb.name}</option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar básculas.', 'error'); });

            $.get(sensorsApiUrl)
                .done((data) => {
                    sensorOptions = data.map(sn =>
                        `<option value="${sn.id}">${sn.name}</option>`
                    ).join('');
                })
                .fail(() => { Swal.fire('Error', 'No se pudieron cargar sensores.', 'error'); });
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
                    { data: 'product_list.name', defaultContent: 'Sin asignar' },
                    {
                        data: 'rfid_reading',
                        render: function(data) {
                            if (data && data.name && data.rfid_color && data.rfid_color.name) {
                                var colorMap = {
                                    red: "#dc3545",
                                    blue: "#007bff",
                                    yellow: "#ffc107",
                                    green: "#28a745"
                                };
                                var colorName = data.rfid_color.name.toLowerCase();
                                if (colorMap.hasOwnProperty(colorName)) {
                                    return `<div class="rfid-option-card">
                                                <div class="rfid-text">${data.name}</div>
                                                <div class="rfid-icon" style="color: ${colorMap[colorName]};">
                                                    <i class="fa fa-id-card"></i>
                                                </div>
                                            </div>`;
                                } else {
                                    return `<div class="rfid-option-card">
                                                <div class="rfid-text">${data.name} - ${data.rfid_color.name}</div>
                                                <div class="rfid-icon" style="color: #007bff;">
                                                    <i class="fa fa-id-card"></i>
                                                </div>
                                            </div>`;
                                }
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
                                @can('rfid-post-edit')
                                    <button class="btn btn-sm btn-secondary edit-btn"
                                        data-id="${data.id}"
                                        data-product_list_id="${data.product_list_id || ''}"
                                        data-rfid_reading_id="${data.rfid_reading_id || ''}"
                                        data-modbus_id="${data.modbus_id || ''}"
                                        data-sensor_id="${data.sensor_id || ''}">
                                        Editar
                                    </button>
                                @endcan
                                @can('rfid-post-delete')
                                    <button class="btn btn-sm btn-danger delete-btn"
                                        data-id="${data.id}">
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
                        text: 'Asignar Confección',
                        className: 'btn btn-primary',
                        action: function(e, dt, node, config) {
                            // Construimos el HTML del modal de forma dinámica
                            var productSelectHtml = `<select id="productListId" class="swal2-input custom-select-style">
                                                        <option value="" disabled selected style="text-align: left; text-align-last: left;">-- Seleccione Producto --</option>
                                                        ${productListOptions}
                                                     </select>`;
                            var rfidSelectHtml = rfidOptions.trim() !== '' ?
                                `<select id="rfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select>` : '';
                            var modbusSelectHtml = modbusOptions.trim() !== '' ?
                                `<select id="modbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select>` : '';
                            var sensorSelectHtml = sensorOptions.trim() !== '' ?
                                `<select id="sensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select>` : '';

                                Swal.fire({
                                    title: 'Asignar Confección',
                                    width: '800px',
                                    padding: '2em',
                                    html: `
                                        ${productSelectHtml}
                                        ${rfidSelectHtml}
                                        ${modbusSelectHtml}
                                        ${sensorSelectHtml}
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
                                        cancelButton: 'btn btn-danger',
                                    },
                                    buttonsStyling: false,
                                    preDeny: () => {
                                        startQrScanner();
                                        return false;
                                    },
                                    didOpen: () => {
                                        $('#productListId').select2({
                                            dropdownParent: Swal.getPopup(),
                                            width: 'resolve',
                                            templateResult: formatProductOption,
                                            templateSelection: formatProductSelection
                                        });
                                        if(rfidSelectHtml !== '') {
                                            $('#rfidReadingId').select2({
                                                dropdownParent: Swal.getPopup(),
                                                width: 'resolve',
                                                placeholder: '-- Seleccione --',
                                                closeOnSelect: false,
                                                hideSelected: true,
                                                templateResult: formatOption,
                                                templateSelection: formatRfidSelection,
                                                matcher: rfidMatcher
                                            });
                                            $('#rfidReadingId').on('select2:closing', function(e) {
                                                if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) {
                                                    e.preventDefault();
                                                }
                                            });
                                            $('#rfidReadingId').on('change', function() {
                                                let selectedValues = $(this).val();
                                                if (selectedValues && selectedValues.length > 0) {
                                                    let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`);
                                                    selectedRfidColor = firstSelectedOption.data('color');
                                                } else {
                                                    selectedRfidColor = null;
                                                }
                                                if ($(this).data('select2').isOpen()) {
                                                    $(this).select2('close').select2('open');
                                                }
                                            });
                                        }
                                        if(modbusSelectHtml !== '') {
                                            $('#modbusId').select2({
                                                dropdownParent: Swal.getPopup(),
                                                width: 'resolve',
                                                placeholder: '-- Seleccione --',
                                                closeOnSelect: false,
                                                hideSelected: true,
                                                templateResult: formatOption,
                                                templateSelection: formatSelection
                                            });
                                            $('#modbusId').on('select2:closing', function(e) {
                                                if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) {
                                                    e.preventDefault();
                                                }
                                            });
                                        }
                                        if(sensorSelectHtml !== '') {
                                            $('#sensorId').select2({
                                                dropdownParent: Swal.getPopup(),
                                                width: 'resolve',
                                                placeholder: '-- Seleccione --',
                                                closeOnSelect: false,
                                                hideSelected: true,
                                                templateResult: formatOption,
                                                templateSelection: formatSelection
                                            });
                                            $('#sensorId').on('select2:closing', function(e) {
                                                if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) {
                                                    e.preventDefault();
                                                }
                                            });
                                        }
                                        $('#productListId').val('').trigger('change');
                                    },
                                    preConfirm: () => {
                                        const product_list_id = $('#productListId').val();
                                        const rfid_reading_ids = rfidSelectHtml !== '' ? $('#rfidReadingId').val() : [];
                                        const modbus_ids = modbusSelectHtml !== '' ? $('#modbusId').val() : [];
                                        const sensor_ids = sensorSelectHtml !== '' ? $('#sensorId').val() : [];
                                        if (!product_list_id) {
                                            Swal.showValidationMessage('Producto es obligatorio.');
                                            return false;
                                        }
                                        if (
                                            (!rfid_reading_ids || rfid_reading_ids.length === 0) &&
                                            (!modbus_ids || modbus_ids.length === 0) &&
                                            (!sensor_ids || sensor_ids.length === 0)
                                        ) {
                                            Swal.showValidationMessage('Debe seleccionar al menos una báscula, o un sensor, o un RFID.');
                                            return false;
                                        }

                                        return {
                                            client_id: parseInt(product_list_id),
                                            rfid_reading_ids: rfid_reading_ids.map(id => parseInt(id)),
                                            modbus_ids: modbus_ids ? modbus_ids.map(id => parseInt(id)) : [],
                                            sensor_ids: sensor_ids ? sensor_ids.map(id => parseInt(id)) : []
                                        };
                                    },
                                    didClose: () => {
                                        // Reiniciamos el color al cerrar el modal
                                        selectedRfidColor = null;
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        $.ajax({
                                            url: relationsApiUrl,
                                            method: 'POST',
                                            contentType: 'application/json',
                                            data: JSON.stringify(result.value),
                                            success: function() {
                                                // Reiniciamos el color al cerrar el modal
                                                selectedRfidColor = null;
                                                Swal.fire({
                                                    title: 'Éxito',
                                                    text: 'Relación añadida.',
                                                    icon: 'success',
                                                    timer: 2000,             // Tiempo en milisegundos (2000 ms = 2 segundos)
                                                    showConfirmButton: false // Oculta el botón de confirmación para que se cierre automáticamente
                                                });
                                                table.ajax.reload();
                                            },
                                            error: function(xhr) {
                                                // Reiniciamos el color al cerrar el modal
                                                selectedRfidColor = null;
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
                        className: 'btn btn-success'
                    }
                ],
                order: [[3, 'desc'],[1, 'desc']],
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

                // Construir dinámicamente los selects, omitiendo aquellos sin opciones.
                var productSelectHtml = `<select id="productListId" class="swal2-input custom-select-style">
                                              <option value="" disabled selected>-- Seleccione Producto --</option>
                                              ${productListOptions}
                                          </select>`;
                var rfidSelectHtml = rfidOptions.trim() !== '' ?
                    `<select id="rfidReadingId" class="swal2-input custom-select-style" multiple>${rfidOptions}</select>` : '';
                var modbusSelectHtml = modbusOptions.trim() !== '' ?
                    `<select id="modbusId" class="swal2-input custom-select-style" multiple>${modbusOptions}</select>` : '';
                var sensorSelectHtml = sensorOptions.trim() !== '' ?
                    `<select id="sensorId" class="swal2-input custom-select-style" multiple>${sensorOptions}</select>` : '';

                Swal.fire({
                    title: 'Editar Relación',
                    html: `
                        <input id="relationId" class="swal2-input custom-select-style" value="${currentId}" readonly>
                        <label for="productListId">Producto:</label>
                        ${productSelectHtml}
                        ${rfidSelectHtml !== '' ? '<label for="rfidReadingId">RFID:</label>' + rfidSelectHtml : ''}
                        ${modbusSelectHtml !== '' ? '<label for="modbusId">Báscula:</label>' + modbusSelectHtml : ''}
                        ${sensorSelectHtml !== '' ? '<label for="sensorId">Sensor:</label>' + sensorSelectHtml : ''}
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar',
                    didOpen: () => {
                        $('#productListId').select2({
                            dropdownParent: Swal.getPopup(),
                            width: 'resolve',
                            templateResult: formatProductOption,
                            templateSelection: formatProductSelection
                        });
                        if(rfidSelectHtml !== '') {
                            $('#rfidReadingId').select2({
                                dropdownParent: Swal.getPopup(),
                                width: 'resolve',
                                placeholder: '-- Seleccione --',
                                closeOnSelect: false,
                                hideSelected: true,
                                templateResult: formatOption,
                                templateSelection: formatRfidSelection,
                                matcher: rfidMatcher
                            });
                            $('#rfidReadingId').on('select2:closing', function(e) {
                                if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) {
                                    e.preventDefault();
                                }
                            });
                            $('#rfidReadingId').on('change', function() {
                                let selectedValues = $(this).val();
                                if (selectedValues && selectedValues.length > 0) {
                                    let firstSelectedOption = $(this).find(`option[value="${selectedValues[0]}"]`);
                                    selectedRfidColor = firstSelectedOption.data('color');
                                } else {
                                    selectedRfidColor = null;
                                }
                                if ($(this).data('select2').isOpen()) {
                                    $(this).select2('close').select2('open');
                                }
                            });
                        }
                        if(modbusSelectHtml !== '') {
                            $('#modbusId').select2({
                                dropdownParent: Swal.getPopup(),
                                width: 'resolve',
                                placeholder: '-- Seleccione --',
                                closeOnSelect: false,
                                hideSelected: true,
                                templateResult: formatOption,
                                templateSelection: formatSelection
                            });
                            $('#modbusId').on('select2:closing', function(e) {
                                if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) {
                                    e.preventDefault();
                                }
                            });
                        }
                        if(sensorSelectHtml !== '') {
                            $('#sensorId').select2({
                                dropdownParent: Swal.getPopup(),
                                width: 'resolve',
                                placeholder: '-- Seleccione --',
                                closeOnSelect: false,
                                hideSelected: true,
                                templateResult: formatOption,
                                templateSelection: formatSelection
                            });
                            $('#sensorId').on('select2:closing', function(e) {
                                if (e.originalEvent && $(e.originalEvent.target).closest('.select2-results__option').length) {
                                    e.preventDefault();
                                }
                            });
                        }
                        $('#productListId').val(currentProductListId).trigger('change');
                        if(rfidSelectHtml !== '') {
                            let rfidValue = currentRfidReadingId;
                            if (!Array.isArray(rfidValue)) {
                                rfidValue = rfidValue ? [rfidValue] : [];
                            }
                            $('#rfidReadingId').val(rfidValue).trigger('change');
                        }
                        if(modbusSelectHtml !== '') {
                            let modbusValue = currentModbusId;
                            if (!Array.isArray(modbusValue)) {
                                modbusValue = modbusValue ? [modbusValue] : [];
                            }
                            $('#modbusId').val(modbusValue).trigger('change');
                        }
                        if(sensorSelectHtml !== '') {
                            let sensorValue = currentSensorId;
                            if (!Array.isArray(sensorValue)) {
                                sensorValue = sensorValue ? [sensorValue] : [];
                            }
                            $('#sensorId').val(sensorValue).trigger('change');
                        }
                    },
                    preConfirm: () => {
                        const id = $('#relationId').val();
                        const product_list_id = $('#productListId').val();
                        const rfid_reading_ids = rfidSelectHtml !== '' ? $('#rfidReadingId').val() : [];
                        const modbus_ids = modbusSelectHtml !== '' ? $('#modbusId').val() : [];
                        const sensor_ids = sensorSelectHtml !== '' ? $('#sensorId').val() : [];
                        if (!id || !product_list_id || (rfidSelectHtml !== '' && (!rfid_reading_ids || rfid_reading_ids.length === 0))) {
                            Swal.showValidationMessage('Producto y al menos un RFID son obligatorios.');
                            return false;
                        }
                        return {
                            id: parseInt(id),
                            product_list_id: parseInt(product_list_id),
                            rfid_reading_ids: rfid_reading_ids.map(id => parseInt(id)),
                            modbus_ids: modbus_ids ? modbus_ids.map(id => parseInt(id)) : [],
                            sensor_ids: sensor_ids ? sensor_ids.map(id => parseInt(id)) : []
                        };
                    },
                    didClose: () => {
                        // Reiniciamos el color al cerrar el modal
                        selectedRfidColor = null;
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
            if (window.html5QrCodeInstance) return;
            window.html5QrCodeInstance = new Html5Qrcode("qr-reader");
            $('#qr-reader').show();
            const config = { fps: 10, qrbox: 250 };

            window.html5QrCodeInstance.start(
                { facingMode: "environment" },
                config,
                qrMessage => {
                    console.log("QR detectado:", qrMessage);
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

        $(document).on('click', '#scanQrBtn', function(e) {
            e.preventDefault();
            startQrScanner();
        });
    </script>
@endpush
