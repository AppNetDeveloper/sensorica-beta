@extends('layouts.admin')

@section('title', 'Estadísticas de Líneas de Producción')

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            width: 100%;
        }
        .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background-color: #f8f9fa;
        }
        .table-responsive {
            border-radius: 8px;
            background: white;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        .table > :not(:last-child) > :last-child > * {
            border-bottom-color: #dee2e6;
        }
        .badge {
            font-weight: 500;
            padding: 0.4em 0.8em;
        }
        .progress {
            height: 20px;
            border-radius: 4px;
        }
        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
        }
        .btn {
            border-radius: 6px;
            font-weight: 500;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 4px;
            border: 1px solid #ced4da;
            padding: 0.375rem 0.75rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 4px !important;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0d6efd !important;
            color: white !important;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #0b5ed7 !important;
            color: white !important;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05) !important;
        }
        
        /* Mejoras de espaciado para DataTable */
        table.dataTable {
            border-spacing: 0 8px !important;
            border-collapse: separate !important;
            margin-top: 15px !important;
            margin-left: 10px !important;
            margin-right: 10px !important;
            width: calc(100% - 20px) !important;
        }
        
        .dataTables_wrapper {
            padding: 15px !important;
        }
        
        table.dataTable thead th {
            padding: 12px 10px;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }
        
        table.dataTable tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4 px-1">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">
                    <i class="fas fa-table me-2 text-primary"></i>
                    Datos de Producción
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Líneas de Producción</label>
                        <select id="modbusSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="datetime-local" class="form-control" id="startDate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Fecha Fin</label>
                        <input type="datetime-local" class="form-control" id="endDate">
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary flex-fill" id="fetchData">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                        <button type="button" class="btn btn-outline-info" id="refreshData" title="Refrescar datos">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="btn btn-secondary" id="resetFilters" title="Restablecer filtros">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-start border-success border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Promedio OEE</h6>
                                <h3 class="mb-0" id="avgOEE">0%</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-chart-line text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-start border-primary border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Duración</h6>
                                <h3 class="mb-0" id="totalDuration">00:00:00</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-clock text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Teórico Total</h6>
                                <h3 class="mb-0" id="totalTheoretical">00:00:00</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-balance-scale text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-start border-secondary border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Preparación</h6>
                                <h3 class="mb-0" id="totalPrepairTime">00:00:00</h3>
                            </div>
                            <div class="bg-secondary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-tools text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Tiempo Lento</h6>
                                <h3 class="mb-0" id="totalSlowTime">00:00:00</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-tachometer-alt text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card border-start border-danger border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Paradas / Falta Material</h6>
                                <h3 class="mb-0" id="totalStopsTime">00:00:00</h3>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="fas fa-hand-paper text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div class="card">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 Hideclass="mb-0">
                        <i class="fas fa-table me-2 text-primary" hidden></i>
                        
                    </h6>
                    <div class="d-flex">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-success" id="exportExcel">
                                <i class="fas fa-file-excel me-1"></i> Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="exportPDF">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="printTable">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="controlWeightTable" class="table table-hover table-striped" style="width:100%">
                        <!-- La tabla se generará dinámicamente con DataTables -->
                    </table>
                </div>
            </div>
        </div>
        
        @include('productionlines.status-legend')
        
        @include('productionlines.time-legend')
    </div>
    
    <!-- Modal para detalles de línea de producción -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Detalles de Línea de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Información General</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="fw-bold">Línea:</label>
                                        <span id="modal-line-name"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Orden:</label>
                                        <span id="modal-order-id"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Caja:</label>
                                        <span id="modal-box"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Unidades:</label>
                                        <span id="modal-units"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">UPM Real:</label>
                                        <span id="modal-upm-real"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">UPM Teórico:</label>
                                        <span id="modal-upm-theoretical"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Estado:</label>
                                        <span id="modal-status" class="badge"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Tiempo de inicio:</label>
                                        <span id="modal-created-at"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="fw-bold">Última actualización:</label>
                                        <span id="modal-updated-at"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Básculas</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <h6 class="text-primary">Báscula Final de Línea</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="fw-bold">Nº en Turno:</label>
                                                <span id="modal-weights-0-shift-number"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="fw-bold">Kg en Turno:</label>
                                                <span id="modal-weights-0-shift-kg"></span>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <label class="fw-bold">Nº en Orden:</label>
                                                <span id="modal-weights-0-order-number"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="fw-bold">Kg en Orden:</label>
                                                <span id="modal-weights-0-order-kg"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="mb-2">
                                        <h6 class="text-danger">Básculas de Rechazo</h6>
                                        <div id="weights-rejection-container">
                                            <!-- Aquí se insertarán dinámicamente las básculas de rechazo -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">OEE</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="oeeChart" height="200"></canvas>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Tiempos de Producción</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <label class="fw-bold">Tiempo de producción:</label>
                                                <span id="modal-on-time"></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold">Tiempo Ganado:</label>
                                                <span id="modal-fast-time"></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold">Tiempo Lento:</label>
                                                <span id="modal-slow-time"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <label class="fw-bold">Tiempo de Más:</label>
                                                <span id="modal-out-time"></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold">Parada Falta Material:</label>
                                                <span id="modal-down-time"></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold">Paradas No Justificadas:</label>
                                                <span id="modal-production-stops-time"></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="fw-bold">Tiempo Preparación:</label>
                                                <span id="modal-prepair-time"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
  {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        .dataTables_wrapper {
            overflow-x: auto;
        }
        
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        
        .select2-container .select2-selection--multiple {
            min-height: 38px;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        /* Ajustar color del texto en las opciones seleccionadas */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #212529;
            font-weight: 500;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #495057;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #212529;
            background-color: #dde2e6;
        }
    </style>
@endpush

@push('scripts')
  {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        const token = new URLSearchParams(window.location.search).get('token');
        console.log("Token obtenido:", token);

        // Función para formatear segundos a HH:MM:SS
        function formatTime(seconds) {
            if (seconds === null || seconds === undefined || isNaN(seconds)) return '-';
            seconds = parseInt(seconds);
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        async function fetchProductionLines() {
            try {
                console.log("Intentando obtener líneas de producción...");
                const response = await fetch(`/api/production-lines/${token}`);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Líneas de producción recibidas:", data);

                const modbusSelect = $('#modbusSelect');
                modbusSelect.empty();
                
                // Ordenar las líneas de producción alfabéticamente por nombre
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                data.forEach(line => {
                    modbusSelect.append(`<option value="${line.token}">${line.name}</option>`);
                });
            } catch (error) {
                console.error("Error al cargar líneas de producción:", error);
            }
        }

        async function fetchOrderStats(lineTokens, startDate, endDate) {
            try {
                const tokensArray = Array.isArray(lineTokens) ? lineTokens : [lineTokens];
                const filteredTokens = tokensArray.filter(token => token && token.trim() !== '');
                
                if (filteredTokens.length === 0) {
                    throw new Error('No hay tokens válidos seleccionados');
                }
                
                const tokenParam = filteredTokens.join(',');
                const url = `/api/order-stats-all?token=${tokenParam}&start_date=${startDate}&end_date=${endDate}`;
                const fullUrl = window.location.origin + url;
                console.log("URL COMPLETA de la API:", fullUrl);
                console.log("==================================================");
                console.log("COPIABLE:", fullUrl);
                console.log("==================================================");

                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Datos de estadísticas recibidos:", data);

                // Procesar los datos para asegurar que tienen la estructura correcta
                const processedData = data.map(item => {
                    console.log('Procesando item:', item.id, 'down_time:', item.down_time, 'production_stops_time:', item.production_stops_time);
                    return {
                        id: item.id || '-',
                        production_line_name: item.production_line_name || '-',
                        order_id: item.order_id || '-',
                        box: item.box || '-',
                        units: parseInt(item.units) || 0,
                        units_per_minute_real: parseFloat(item.units_per_minute_real) || 0,
                        units_per_minute_theoretical: parseFloat(item.units_per_minute_theoretical) || 0,
                        oee: parseFloat(item.oee) || 0,
                        status: item.status || 'unknown',
                        created_at: item.created_at || null,
                        updated_at: item.updated_at || null,
                        down_time: item.down_time !== undefined ? parseFloat(item.down_time) : 0,
                        production_stops_time: item.production_stops_time !== undefined ? parseFloat(item.production_stops_time) : 0,
                        on_time: item.on_time || null,
                        fast_time: item.fast_time || null,
                        slow_time: item.slow_time || null,
                        out_time: item.out_time || null,
                        prepair_time: item.prepair_time || null
                    }});
                
                // Actualizar los KPIs
                updateKPIs(processedData);
                
                // Limpiar cualquier estado de carga previo
                $('#loadingIndicator').hide();
                $('#controlWeightTable').show();
                
                const table = $('#controlWeightTable').DataTable({
                    dom: 'lfrtip',
                    buttons: [],
                    scrollX: true,
                    responsive: true,
                    data: processedData,
                    destroy: true,
                    columns: [
                        { data: 'production_line_name', title: 'Línea', className: 'text-truncate', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Línea: ${cellData}`);
                        }},
                        { data: 'order_id', title: 'Orden', className: 'text-truncate', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Orden: ${cellData}`);
                        }},
                        { data: 'oee', title: 'OEE', render: data => `${data.toFixed(2)}%`, createdCell: function(td, cellData, rowData) {
                            const color = cellData >= 80 ? 'text-success' : cellData >= 60 ? 'text-warning' : 'text-danger';
                            $(td).html(`<span class="${color} fw-bold">${cellData.toFixed(2)}%</span>`);
                            $(td).attr('title', `OEE: ${cellData.toFixed(2)}%\nEstado: ${cellData >= 80 ? 'Excelente' : cellData >= 60 ? 'Aceptable' : 'Necesita mejora'}`);
                        }},
                        { data: 'status', title: 'Estado', render: data => {
                            const statusMap = {
                                'active': '<span class="badge bg-success">Activo</span>',
                                'paused': '<span class="badge bg-warning">Pausado</span>',
                                'error': '<span class="badge bg-danger">Incidencia</span>',
                                'completed': '<span class="badge bg-primary">Completado</span>',
                                'in_progress': '<span class="badge bg-info">En Progreso</span>'
                            };
                            return statusMap[data] || '<span class="badge bg-secondary">Desconocido</span>';
                        }, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Estado actual: ${cellData}`);
                        }},
                        { data: 'created_at', title: 'Iniciado', render: data => new Date(data).toLocaleString(), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Inicio: ${new Date(data).toLocaleString()}`);
                        }},
                        { data: 'updated_at', title: 'Última actualización', render: data => data ? new Date(data).toLocaleString() : '-', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Última actualización: ${data ? new Date(data).toLocaleString() : '-'}`);
                        }},
                        { data: 'on_time', title: 'DURACIÓN', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Duración: ${formatTime(cellData)}`);
                        }},
                        { data: null, title: 'Teórico', render: function(data, type, row) {
                            if (row.fast_time && parseInt(row.fast_time) > 0) {
                                return '<span class="badge bg-success">' + formatTime(row.fast_time) + '</span>';
                            } else if (row.out_time && parseInt(row.out_time) > 0) {
                                return '<span class="badge bg-danger">' + formatTime(row.out_time) + '</span>';
                            } else {
                                return '';
                            }
                        }, createdCell: function(td, cellData, rowData) {
                            if (rowData.fast_time && parseInt(rowData.fast_time) > 0) {
                                $(td).attr('title', `Tiempo ganado: ${formatTime(rowData.fast_time)}`);
                            } else if (rowData.out_time && parseInt(rowData.out_time) > 0) {
                                $(td).attr('title', `Tiempo de más: ${formatTime(rowData.out_time)}`);
                            }
                        }},
                        { data: 'prepair_time', title: 'Preparación', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Tiempo de preparación: ${formatTime(cellData)}`);
                        }},
                        { data: 'slow_time', title: 'Lento', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Tiempo en velocidad lenta: ${formatTime(cellData)}`);
                        }},
                        { data: 'down_time', title: 'Paradas', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Paradas no justificadas: ${formatTime(cellData)}`);
                        }},
                        { data: 'production_stops_time', title: 'Falta material', render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Parada falta material: ${formatTime(cellData)}`);
                        }},
                        { data: null, title: 'Acciones', orderable: false, render: function(data, type, row) {
                            return `<button class="btn btn-sm btn-primary" onclick="showDetailsModal(${JSON.stringify(row).replace(/"/g, '&quot;')})">
                                <i class="fas fa-eye"></i> Ver
                            </button>`;
                        }, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', 'Ver detalles completos');
                        }}
                    ],
                    order: [[1, 'desc']], // Ordenar por Orden (ahora es la segunda columna)
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    searching: true,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    initComplete: function() {
                        // Añadir clases de Bootstrap a los elementos de DataTables
                        $('.dataTables_filter input').addClass('form-control form-control-sm');
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                    }
                });
            } catch (error) {
                console.error("Error al cargar datos:", error);
            }
        }

        // Función para actualizar los KPIs
        function updateKPIs(data) {
            // Total de duración (suma de on_time)
            let totalDurationSeconds = 0;
            data.forEach(item => {
                if (item.on_time && !isNaN(item.on_time)) {
                    totalDurationSeconds += parseInt(item.on_time);
                }
            });
            
            // Formatear la duración total en formato HH:MM:SS
            $('#totalDuration').text(formatTime(totalDurationSeconds));
            
            // Promedio de OEE
            let totalOEE = 0;
            let validOEECount = 0;
            
            data.forEach(item => {
                if (item.oee && !isNaN(item.oee)) {
                    const oeeValue = parseFloat(item.oee);
                    // Verificar si el valor ya es un porcentaje (>1) o decimal (<1)
                    totalOEE += oeeValue > 1 ? oeeValue : oeeValue * 100;
                    validOEECount++;
                }
            });
            
            const avgOEE = validOEECount > 0 ? totalOEE / validOEECount : 0;
            $('#avgOEE').text(`${avgOEE.toFixed(2)}%`);
            
            // Cambiar color según el valor
            if (avgOEE >= 80) {
                $('#avgOEE').removeClass('text-danger text-warning').addClass('text-success');
            } else if (avgOEE >= 60) {
                $('#avgOEE').removeClass('text-danger text-success').addClass('text-warning');
            } else {
                $('#avgOEE').removeClass('text-success text-warning').addClass('text-danger');
            }
            
            // Calcular suma teórica neta (tiempo ganado vs. tiempo de más)
            let totalFastTime = 0;
            let totalOutTime = 0;
            
            data.forEach(item => {
                if (item.fast_time && !isNaN(item.fast_time)) {
                    totalFastTime += parseInt(item.fast_time);
                }
                if (item.out_time && !isNaN(item.out_time)) {
                    totalOutTime += parseInt(item.out_time);
                }
            });
            
            // Calcular la diferencia neta
            let netTheoreticalTime = 0;
            let isPositive = false;
            
            if (totalFastTime >= totalOutTime) {
                netTheoreticalTime = totalFastTime - totalOutTime;
                isPositive = true;
                $('#totalTheoretical').removeClass('text-danger').addClass('text-success');
            } else {
                netTheoreticalTime = totalOutTime - totalFastTime;
                isPositive = false;
                $('#totalTheoretical').removeClass('text-success').addClass('text-danger');
            }
            
            // Mostrar el resultado formateado
            $('#totalTheoretical').text(formatTime(netTheoreticalTime));
            
            // Calcular suma total de tiempos de preparación
            let totalPrepairTime = 0;
            data.forEach(item => {
                if (item.prepair_time && !isNaN(item.prepair_time)) {
                    totalPrepairTime += parseInt(item.prepair_time);
                }
            });
            
            // Mostrar el total de tiempo de preparación
            $('#totalPrepairTime').text(formatTime(totalPrepairTime));
            
            // Calcular suma total de tiempo lento
            let totalSlowTime = 0;
            data.forEach(item => {
                if (item.slow_time && !isNaN(item.slow_time)) {
                    totalSlowTime += parseInt(item.slow_time);
                }
            });
            
            // Mostrar el total de tiempo lento
            $('#totalSlowTime').text(formatTime(totalSlowTime));
            
            // Calcular suma total de tiempos de paradas y falta de material
            let totalStopsTime = 0;
            data.forEach(item => {
                // Sumar down_time (falta material)
                if (item.down_time && !isNaN(item.down_time)) {
                    totalStopsTime += parseInt(item.down_time);
                }
                // Sumar production_stops_time (paradas no justificadas)
                if (item.production_stops_time && !isNaN(item.production_stops_time)) {
                    totalStopsTime += parseInt(item.production_stops_time);
                }
            });
            
            // Mostrar el total de tiempos de paradas
            $('#totalStopsTime').text(formatTime(totalStopsTime));
        }

        // Inicializar fechas por defecto
        function initializeDates() {
            const now = new Date();
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(now.getDate() - 7);
            
            // Formato YYYY-MM-DDThh:mm
            const formatDate = (date) => {
                return date.toISOString().slice(0, 16);
            };
            
            $('#startDate').val(formatDate(oneWeekAgo));
            $('#endDate').val(formatDate(now));
        }

        // Función para mostrar detalles en el modal
        function showDetailsModal(row) {
            console.log('Mostrando detalles de la fila:', row);
            console.log('OEE de la fila:', row.oee);
            
            // Actualizar datos generales en el modal
            $('#modal-line-name').text(row.production_line_name || '-');
            $('#modal-order-id').text(row.order_id || '-');
            $('#modal-box').text(row.box || '-');
            $('#modal-units').text(row.units ? row.units.toLocaleString() : '0');
            $('#modal-upm-real').text(row.units_per_minute_real ? parseFloat(row.units_per_minute_real).toFixed(2) : '0.00');
            $('#modal-upm-theoretical').text(row.units_per_minute_theoretical ? parseFloat(row.units_per_minute_theoretical).toFixed(2) : '0.00');
            
            // Actualizar tiempos de producción
            $('#modal-on-time').text(row.on_time !== null && row.on_time !== undefined ? formatTime(row.on_time) : '-');
            $('#modal-fast-time').text(row.fast_time !== null && row.fast_time !== undefined ? formatTime(row.fast_time) : '-');
            $('#modal-slow-time').text(row.slow_time !== null && row.slow_time !== undefined ? formatTime(row.slow_time) : '-');
            $('#modal-out-time').text(row.out_time !== null && row.out_time !== undefined ? formatTime(row.out_time) : '-');
            $('#modal-down-time').text(row.down_time !== null && row.down_time !== undefined ? formatTime(row.down_time) : '-');
            $('#modal-production-stops-time').text(row.production_stops_time !== null && row.production_stops_time !== undefined ? formatTime(row.production_stops_time) : '-');
            $('#modal-prepair-time').text(row.prepair_time !== null && row.prepair_time !== undefined ? formatTime(row.prepair_time) : '-');
            
            // Actualizar datos de báscula final de línea (weights_0)
            $('#modal-weights-0-shift-number').text(row.weights_0_shiftNumber !== null && row.weights_0_shiftNumber !== undefined ? row.weights_0_shiftNumber : '-');
            $('#modal-weights-0-shift-kg').text(row.weights_0_shiftKg !== null && row.weights_0_shiftKg !== undefined ? row.weights_0_shiftKg : '-');
            $('#modal-weights-0-order-number').text(row.weights_0_orderNumber !== null && row.weights_0_orderNumber !== undefined ? row.weights_0_orderNumber : '-');
            $('#modal-weights-0-order-kg').text(row.weights_0_orderKg !== null && row.weights_0_orderKg !== undefined ? row.weights_0_orderKg : '-');
            
            // Actualizar básculas de rechazo (weights_1, weights_2, weights_3)
            const rejectionWeightsContainer = $('#weights-rejection-container');
            rejectionWeightsContainer.empty(); // Limpiar contenedor
            
            // Comprobar y mostrar básculas de rechazo (1-3)
            for (let i = 1; i <= 3; i++) {
                const shiftNumber = row[`weights_${i}_shiftNumber`];
                const shiftKg = row[`weights_${i}_shiftKg`];
                const orderNumber = row[`weights_${i}_orderNumber`];
                const orderKg = row[`weights_${i}_orderKg`];
                
                // Solo mostrar si hay al menos un valor no nulo
                if (shiftNumber !== null || shiftKg !== null || orderNumber !== null || orderKg !== null) {
                    const weightHtml = `
                        <div class="mb-3">
                            <h6 class="text-secondary">Báscula ${i}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="fw-bold">Nº en Turno:</label>
                                    <span>${shiftNumber !== null && shiftNumber !== undefined ? shiftNumber : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Kg en Turno:</label>
                                    <span>${shiftKg !== null && shiftKg !== undefined ? shiftKg : '-'}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="fw-bold">Nº en Orden:</label>
                                    <span>${orderNumber !== null && orderNumber !== undefined ? orderNumber : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Kg en Orden:</label>
                                    <span>${orderKg !== null && orderKg !== undefined ? orderKg : '-'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    rejectionWeightsContainer.append(weightHtml);
                }
            }
            
            // Si no hay básculas de rechazo, mostrar mensaje
            if (rejectionWeightsContainer.children().length === 0) {
                rejectionWeightsContainer.html('<p class="text-muted">No hay datos de básculas de rechazo</p>');
            }
            
            // Actualizar estado
            const statusMap = {
                'active': { text: 'Activo', class: 'bg-success' },
                'paused': { text: 'Pausado', class: 'bg-warning' },
                'error': { text: 'Incidencia', class: 'bg-danger' },
                'completed': { text: 'Completado', class: 'bg-primary' },
                'in_progress': { text: 'En Progreso', class: 'bg-info' }
            };
            const status = statusMap[row.status] || { text: 'Desconocido', class: 'bg-secondary' };
            $('#modal-status').text(status.text).removeClass().addClass('badge ' + status.class);
            
            // Asegurar que el OEE se pase correctamente al gráfico
            const oeeData = {
                oee: row.oee,
                units_per_minute_real: row.units_per_minute_real,
                units_per_minute_theoretical: row.units_per_minute_theoretical,
                ...row
            };
            
            // Crear gráfica de OEE
            createOEEChart(oeeData);
            
            // Actualizar fechas
            $('#modal-created-at').text(row.created_at ? new Date(row.created_at).toLocaleString() : '-');
            $('#modal-updated-at').text(row.updated_at ? new Date(row.updated_at).toLocaleString() : '-');
            
            // Mostrar el modal usando jQuery (compatible con la versión de Bootstrap del sistema)
            $('#detailsModal').modal('show');
            
            // Configurar el botón de cierre manualmente
            $('.btn-close, .btn-secondary').on('click', function() {
                $('#detailsModal').modal('hide');
            });
            
            // Asegurarse de que el canvas exista y sea visible antes de crear la gráfica
            $('#detailsModal').on('shown.bs.modal', function() {
                console.log('Modal mostrado, creando gráfica...');
                createOEEChart(row);
            });
        }
        
        // Función para crear la gráfica de OEE
        function createOEEChart(row) {
            console.log('Intentando crear gráfica OEE...');
            
            // Verificar si el canvas existe
            const canvas = document.getElementById('oeeChart');
            if (!canvas) {
                console.error('No se encontró el elemento canvas para la gráfica');
                // Intentar crear el canvas si no existe
                const chartContainer = document.querySelector('.card-body');
                if (chartContainer) {
                    console.log('Recreando el canvas...');
                    const canvasElement = document.createElement('canvas');
                    canvasElement.id = 'oeeChart';
                    canvasElement.height = 200;
                    chartContainer.appendChild(canvasElement);
                    return setTimeout(() => createOEEChart(row), 100); // Intentar de nuevo
                }
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('No se pudo obtener el contexto 2d del canvas');
                return;
            }
            
            console.log('Canvas encontrado, creando gráfica...');
            
            // Destruir gráfica anterior si existe
            if (window.oeeChartInstance) {
                window.oeeChartInstance.destroy();
            }
            
            // Calcular OEE como porcentaje
            console.log('Datos de OEE recibidos:', row.oee, typeof row.oee);
            
            // Usar el valor de OEE directamente desde la API sin cálculos
            let oeePercentage = 0;
            if (row.oee !== null && row.oee !== undefined && !isNaN(row.oee)) {
                oeePercentage = parseFloat(row.oee);
            } else {
                oeePercentage = 0;
            }
            
            console.log('OEE directo desde API:', oeePercentage);
            
            // Crear nueva gráfica
            window.oeeChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['OEE', 'Restante'],
                    datasets: [{
                        data: [oeePercentage, 100 - oeePercentage],
                        backgroundColor: [
                            oeePercentage >= 80 ? '#28a745' : (oeePercentage >= 60 ? '#ffc107' : '#dc3545'),
                            '#f0f2f5'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${context.raw.toFixed(2)}%`;
                                }
                            }
                        }
                    },
                    elements: {
                        center: {
                            text: `${oeePercentage.toFixed(2)}%`,
                            color: '#000',
                            fontStyle: 'Arial',
                            sidePadding: 20,
                            minFontSize: 20,
                            lineHeight: 25
                        }
                    }
                }
            });
            
            // Añadir texto en el centro del gráfico usando el valor actual del gráfico
            Chart.register({
                id: 'doughnutCenterText',
                afterDraw: function(chart) {
                    if (chart.config.type === 'doughnut' && chart.data.datasets[0]) {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        
                        ctx.restore();
                        const fontSize = (height / 114).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        
                        // Obtener el valor OEE del dataset actual
                        const oeeValue = chart.data.datasets[0].data[0] || 0;
                        const text = `${oeeValue.toFixed(2)}%`;
                        const textX = Math.round((width - ctx.measureText(text).width) / 2);
                        const textY = height / 2;
                        
                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }
            });
        }

        $(document).ready(() => {
            initializeDates();
            fetchProductionLines();
            setupDateFilters();

            // Botón de refrescar datos
            $('#refreshData').click(function() {
                const selectedLines = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    $('#loadingIndicator').show();
                    $('#controlWeightTable').hide();
                    $(this).find('i').addClass('fa-spin');
                    fetchOrderStats(selectedLines, startDate, endDate);
                    setTimeout(() => { $(this).find('i').removeClass('fa-spin'); }, 1200);
                } else {
                    alert('Por favor selecciona líneas y fechas válidas.');
                }
            });

            $('#fetchData').click(() => {
                const selectedLines = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                console.log("Parámetros seleccionados:", { selectedLines, startDate, endDate });

                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    // Mostrar indicador de carga
                    $('#loadingIndicator').show();
                    $('#controlWeightTable').hide();
                    
                    fetchOrderStats(selectedLines, startDate, endDate);
                } else {
                    alert('Por favor selecciona líneas y fechas válidas.');
                }
            });

            // Resetear filtros
            $('#resetFilters').click(() => {
                initializeDates();
                $('#modbusSelect').val([]).trigger('change');
            });

            // Configurar eventos para los botones de exportación
            $('#exportExcel').on('click', () => exportData('excel'));
            $('#exportPDF').on('click', () => exportData('pdf'));
            $('#printTable').on('click', () => exportData('print'));
        });

        // Función para configurar filtros de fecha
        function setupDateFilters() {
            // Configuración de Select2 para el select múltiple
            $('#modbusSelect').select2({
                placeholder: 'Selecciona líneas de producción...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }    

        // Función para exportar datos
        function exportData(type) {
            const table = $('#controlWeightTable').DataTable();
            if (!table) {
                alert('No hay datos para exportar');
                return;
            }          
            switch (type) {
                case 'excel':
                    // Exportar a Excel usando SheetJS (XLSX)
                    const wb = XLSX.utils.book_new();
                    const wsData = [];
                    
                    // Encabezados
                    const headers = [];
                    $(table.table().header()).find('th').each(function() {
                        headers.push($(this).text().trim());
                    });
                    wsData.push(headers);
                    
                    // Datos
                    table.rows().every(function() {
                        const rowData = this.data();
                        const row = [];
                        
                        // ID
                        row.push(rowData.id || '-');
                        
                        // Línea
                        row.push(rowData.production_line_name || '-');
                        
                        // Orden
                        row.push(rowData.order_id || '-');
                        
                        // Caja
                        row.push(rowData.box || '-');
                        
                        // Unidades
                        row.push(rowData.units ? rowData.units.toLocaleString() : '-');
                        
                        // UPM Real
                        row.push(rowData.units_per_minute_real ? rowData.units_per_minute_real.toFixed(2) : '-');
                        
                        // UPM Teórico
                        row.push(rowData.units_per_minute_theoretical ? rowData.units_per_minute_theoretical.toFixed(2) : '-');
                        
                        // OEE
                        row.push(rowData.oee ? rowData.oee.toFixed(2) + '%' : '-');
                        
                        // Estado
                        const statusMap = {
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'paused': 'Pausado',
                            'error': 'Error',
                            'pending': 'Pendiente',
                            'unknown': 'Desconocido'
                        };
                        row.push(statusMap[rowData.status] || statusMap['unknown']);
                        
                        // Actualizado
                        row.push(rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-');
                        
                        wsData.push(row);
                    });
                    
                    const ws = XLSX.utils.aoa_to_sheet(wsData);
                    XLSX.utils.book_append_sheet(wb, ws, "Datos de Producción");
                    
                    // Guardar archivo
                    XLSX.writeFile(wb, "Datos_Produccion_" + new Date().toLocaleDateString() + ".xlsx");
                    break;
                    
                case 'pdf':
                    // Exportar a PDF usando jsPDF
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF('l', 'mm', 'a4'); // Landscape para más columnas
                    
                    // Título del documento
                    doc.setFontSize(18);
                    doc.text('Datos de Producción', 14, 22);
                    doc.setFontSize(11);
                    doc.text('Fecha: ' + new Date().toLocaleString(), 14, 30);
                    
                    // Preparar datos para la tabla
                    const pdfHeaders = [];
                    $(table.table().header()).find('th').each(function() {
                        pdfHeaders.push({ title: $(this).text().trim(), dataKey: $(this).text().trim() });
                    });
                    
                    const pdfData = [];
                    table.rows().every(function() {
                        const rowData = this.data();
                        const row = {};
                        
                        // Asignar datos a las columnas
                        row[pdfHeaders[0].dataKey] = rowData.id || '-';
                        row[pdfHeaders[1].dataKey] = rowData.production_line_name || '-';
                        row[pdfHeaders[2].dataKey] = rowData.order_id || '-';
                        row[pdfHeaders[3].dataKey] = rowData.box || '-';
                        row[pdfHeaders[4].dataKey] = rowData.units ? rowData.units.toLocaleString() : '-';
                        row[pdfHeaders[5].dataKey] = rowData.units_per_minute_real ? rowData.units_per_minute_real.toFixed(2) : '-';
                        row[pdfHeaders[6].dataKey] = rowData.units_per_minute_theoretical ? rowData.units_per_minute_theoretical.toFixed(2) : '-';
                        row[pdfHeaders[7].dataKey] = rowData.oee ? rowData.oee.toFixed(2) + '%' : '-';
                        
                        // Estado
                        const statusMap = {
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'paused': 'Pausado',
                            'error': 'Error',
                            'pending': 'Pendiente',
                            'unknown': 'Desconocido'
                        };
                        row[pdfHeaders[8].dataKey] = statusMap[rowData.status] || statusMap['unknown'];
                        
                        // Actualizado
                        row[pdfHeaders[9].dataKey] = rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-';
                        
                        pdfData.push(row);
                    });
                    
                    // Generar tabla en PDF
                    doc.autoTable({
                        head: [pdfHeaders.map(h => h.title)],
                        body: pdfData.map(row => pdfHeaders.map(h => row[h.dataKey])),
                        startY: 40,
                        margin: { top: 40 },
                        styles: { overflow: 'linebreak', fontSize: 8 },
                        headStyles: { fillColor: [41, 128, 185], textColor: 255 },
                        alternateRowStyles: { fillColor: [245, 245, 245] }
                    });
                    
                    // Guardar PDF
                    doc.save("Datos_Produccion_" + new Date().toLocaleDateString() + ".pdf");
                    break;
                    
                case 'print':
                    // Imprimir manualmente
                    let printWindow = window.open('', '_blank');
                    let tableHtml = '<html><head><title>Datos de Producción</title>';
                    tableHtml += '<style>body{font-family:Arial,sans-serif;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>';
                    tableHtml += '</head><body>';
                    tableHtml += '<h1>Datos de Producción</h1>';
                    tableHtml += '<p>Fecha: ' + new Date().toLocaleString() + '</p>';
                    tableHtml += '<table>';
                    
                    // Encabezados
                    tableHtml += '<thead><tr>';
                    $(table.table().header()).find('th').each(function() {
                        tableHtml += '<th>' + $(this).text().trim() + '</th>';
                    });
                    tableHtml += '</tr></thead>';
                    
                    // Datos
                    tableHtml += '<tbody>';
                    table.rows().every(function() {
                        let rowData = this.data();
                        tableHtml += '<tr>';
                        
                        // ID
                        tableHtml += '<td>' + (rowData.id || '-') + '</td>';
                        
                        // Línea
                        tableHtml += '<td>' + (rowData.production_line_name || '-') + '</td>';
                        
                        // Orden
                        tableHtml += '<td>' + (rowData.order_id || '-') + '</td>';
                        
                        // Caja
                        tableHtml += '<td>' + (rowData.box || '-') + '</td>';
                        
                        // Unidades
                        tableHtml += '<td>' + (rowData.units ? rowData.units.toLocaleString() : '-') + '</td>';
                        
                        // UPM Real
                        tableHtml += '<td>' + (rowData.units_per_minute_real ? rowData.units_per_minute_real.toFixed(2) : '-') + '</td>';
                        
                        // UPM Teórico
                        tableHtml += '<td>' + (rowData.units_per_minute_theoretical ? rowData.units_per_minute_theoretical.toFixed(2) : '-') + '</td>';
                        
                        // OEE
                        tableHtml += '<td>' + (rowData.oee ? rowData.oee.toFixed(2) + '%' : '-') + '</td>';
                        
                        // Estado
                        const statusMap = {
                            'in_progress': 'En Progreso',
                            'completed': 'Completado',
                            'paused': 'Pausado',
                            'error': 'Error',
                            'pending': 'Pendiente',
                            'unknown': 'Desconocido'
                        };
                        tableHtml += '<td>' + (statusMap[rowData.status] || statusMap['unknown']) + '</td>';
                        
                        // Actualizado
                        tableHtml += '<td>' + (rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-') + '</td>';
                        
                        tableHtml += '</tr>';
                    });
                    tableHtml += '</tbody></table>';
                    tableHtml += '</body></html>';
                    
                    printWindow.document.write(tableHtml);
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(function() {
                        printWindow.print();
                        printWindow.close();
                    }, 500);
                    break;
            }
        }
    </script>
@endpush