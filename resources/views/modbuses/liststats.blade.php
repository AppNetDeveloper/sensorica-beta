@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Estadísticas de Modbus')

{{-- Contenido principal --}}
@section('content')
    <div class="container-fluid py-4 px-1">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="fs-4 fw-bold mb-0">Estadísticas de Modbus</h1>
                <p class="text-muted">Visualización y análisis de datos de control de peso</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    
                    {{-- Filtros Mejorados --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h6 class="mb-0">
                                <i class="fas fa-filter me-2 text-primary"></i>
                                Filtros de Búsqueda
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="modbusSelect" class="form-label small fw-bold text-muted">
                                        <i class="fas fa-microchip me-1"></i>
                                        Dispositivo Modbus
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-server"></i>
                                        </span>
                                        <select id="modbusSelect" class="form-select">
                                            <option value="" selected disabled>Cargando dispositivos...</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="startDate" class="form-label small fw-bold text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        Fecha Inicio
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="far fa-calendar"></i>
                                        </span>
                                        <input type="datetime-local" id="startDate" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="endDate" class="form-label small fw-bold text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        Fecha Fin
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="far fa-calendar"></i>
                                        </span>
                                        <input type="datetime-local" id="endDate" class="form-control">
                                    </div>
                                </div>
                                
                                <div class="col-md-2 d-flex align-items-end">
                                    <button id="applyFilters" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Buscar
                                    </button>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                                        <label class="form-check-label small text-muted" for="autoRefresh">
                                            Actualización automática cada 30 segundos
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- KPI Cards --}}
                    <div class="card-body p-3 pt-0">
                        <div class="row g-4 mb-4">
                            <!-- Total Records Card -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-start border-primary border-4 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Total Registros</h6>
                                                <h2 class="mb-0" id="totalRecords">0</h2>
                                                <p class="text-muted mb-0 small" id="recordsRange">Últimos 7 días</p>
                                            </div>
                                            <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                                                <i class="fas fa-database fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                <i class="fas fa-arrow-up me-1"></i>
                                                <span id="recordsTrend">0%</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Average Weight Card -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-start border-success border-4 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Peso Promedio</h6>
                                                <h2 class="mb-0" id="avgWeight">0.00 <small class="fs-6">kg</small></h2>
                                                <p class="text-muted mb-0 small">Por registro</p>
                                            </div>
                                            <div class="bg-success bg-opacity-10 p-3 rounded-3">
                                                <i class="fas fa-weight-hanging fa-2x text-success"></i>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-arrow-up me-1"></i>
                                                <span id="weightTrend">0%</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Average Boxes Card -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-start border-warning border-4 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Cajas Promedio</h6>
                                                <h2 class="mb-0" id="avgBoxes">0</h2>
                                                <p class="text-muted mb-0 small">Por registro</p>
                                            </div>
                                            <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                                                <i class="fas fa-boxes fa-2x text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <span class="badge bg-warning bg-opacity-10 text-warning">
                                                <i class="fas fa-arrow-up me-1"></i>
                                                <span id="boxesTrend">0%</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Last Update Card -->
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card border-start border-info border-4 h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="text-uppercase text-muted mb-2 small fw-bold">Última Actualización</h6>
                                                <h2 class="mb-0" id="lastUpdate">-</h2>
                                                <p class="text-muted mb-0 small" id="updateStatus">Actualizando...</p>
                                            </div>
                                            <div class="position-relative">
                                                <div class="bg-info bg-opacity-10 p-3 rounded-3">
                                                    <i class="fas fa-sync-alt fa-2x text-info" id="updateIcon"></i>
                                                </div>
                                                <span class="position-absolute top-0 end-0 p-1 bg-success border border-white rounded-circle" id="connectionStatus">
                                                    <span class="visually-hidden">Estado de conexión</span>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="mt-2 d-flex justify-content-between align-items-center">
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <i class="fas fa-circle me-1"></i>
                                                <span id="connectionStatusText">Conectando...</span>
                                            </span>
                                            <button id="refreshData" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-sync-alt me-1"></i>Actualizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabla de Datos Mejorada --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <i class="fas fa-table me-2 text-primary"></i>
                                    Registros de Peso
                                </h6>
                                <div class="d-flex">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportExcel">
                                            <i class="fas fa-file-excel me-1"></i> Excel
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="exportPDF">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="printTable">
                                            <i class="fas fa-print me-1"></i> Imprimir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table id="controlWeightTable" class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" width="15%">
                                                <i class="fas fa-weight-hanging me-1 text-muted"></i>
                                                Peso (kg)
                                            </th>
                                            <th width="15%">
                                                <i class="fas fa-ruler-combined me-1 text-muted"></i>
                                                Dimensión
                                            </th>
                                            <th class="text-center" width="15%">
                                                <i class="fas fa-box me-1 text-muted"></i>
                                                N° Caja
                                            </th>
                                            <th width="20%">
                                                <i class="fas fa-barcode me-1 text-muted"></i>
                                                Código de Barras
                                            </th>
                                            <th width="20%">
                                                <i class="fas fa-barcode me-1 text-muted"></i>
                                                Código de Barras Final
                                            </th>
                                            <th class="text-end" width="15%">
                                                <i class="far fa-clock me-1 text-muted"></i>
                                                Fecha/Hora
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="d-flex flex-column align-items-center">
                                                    <div class="spinner-border text-primary mb-2" role="status">
                                                        <span class="visually-hidden">Cargando...</span>
                                                    </div>
                                                    <span class="text-muted">Cargando datos...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-light">
                                        <tr>
                                            <th colspan="6" class="text-muted small">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div id="tableInfo">Mostrando 0 registros</div>
                                                    <div id="tablePagination" class="d-flex"></div>
                                                </div>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            width: 100%;
        }
        .dt-buttons {
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background-color: #28a745;
            color: #fff !important;
            border-radius: 5px;
            margin: 0 2px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #0d6efd !important;
            color: white !important;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #218838;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 5px;
        }
        th {
            position: relative;
            padding-right: 20px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        .table-responsive {
            border-radius: 8px;
            background-color: white;
            overflow-x: auto;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05) !important;
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

@push('scripts')
    <!-- Required JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/locale/es.js"></script>
    
    <script>
        // Configuración global
        moment.locale('es');
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        let dataTable;
        let refreshInterval;
        let isAutoRefresh = true;
        
        // Inicialización cuando el documento está listo
        $(document).ready(function() {
            initializeDatePickers();
            loadModbusDevices();
            setupEventListeners();
            
            // Iniciar actualización automática
            startAutoRefresh();
        });
        
        // Inicializar selectores de fecha
        function initializeDatePickers() {
            const now = moment();
            const oneWeekAgo = moment().subtract(7, 'days');
            
            $('#startDate').val(oneWeekAgo.format('YYYY-MM-DDTHH:mm'));
            $('#endDate').val(now.format('YYYY-MM-DDTHH:mm'));
        }
        
        // Cargar dispositivos Modbus
        async function loadModbusDevices() {
            try {
                showLoading('#modbusSelect', 'Cargando dispositivos...');
                const response = await fetch(`/api/modbuses?token=${token}`);
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                const $modbusSelect = $('#modbusSelect');
                
                $modbusSelect.empty().append('<option value="">Seleccione un dispositivo</option>');
                
                data.forEach(device => {
                    $modbusSelect.append(`
                        <option value="${device.token}">
                            ${device.name}
                        </option>
                    `);
                });
                
                // Seleccionar el primer dispositivo por defecto si hay alguno
                if (data.length > 0) {
                    $modbusSelect.val(data[0].token).trigger('change');
                }
                
            } catch (error) {
                console.error('Error al cargar dispositivos Modbus:', error);
                showError('No se pudieron cargar los dispositivos Modbus');
            } finally {
                hideLoading('#modbusSelect');
            }
        }
        
        // Configurar event listeners
        function setupEventListeners() {
            // Aplicar filtros
            $('#applyFilters').on('click', applyFilters);
            
            // Actualizar datos
            $('#refreshData').on('click', refreshData);
            
            // Exportar datos
            $('#exportExcel').on('click', () => exportData('excel'));
            $('#exportPDF').on('click', () => exportData('pdf'));
            $('#printTable').on('click', () => exportData('print'));
            
            // Alternar actualización automática
            $('#autoRefresh').on('change', function() {
                isAutoRefresh = $(this).is(':checked');
                if (isAutoRefresh) {
                    startAutoRefresh();
                } else {
                    stopAutoRefresh();
                }
            });
            
            // Cambio de dispositivo
            $('#modbusSelect').on('change', function() {
                if ($(this).val()) {
                    applyFilters();
                }
            });
        }
        
        // Aplicar filtros y cargar datos
        function applyFilters() {
            const modbusToken = $('#modbusSelect').val();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            
            if (!modbusToken || !startDate || !endDate) {
                showWarning('Por favor complete todos los filtros');
                return;
            }
            
            loadData(modbusToken, startDate, endDate);
        }
        
        // Cargar datos de la tabla
        async function loadData(modbusToken, startDate, endDate) {
            try {
                showLoading('#controlWeightTable', 'Cargando datos...');
                updateConnectionStatus('Conectando...', 'warning');
                
                const url = `/api/control-weights/${modbusToken}/all?token=${token}&start_date=${startDate}&end_date=${endDate}`;
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                updateKPIs(data);
                initializeOrUpdateDataTable(data);
                updateConnectionStatus('Conectado', 'success');
                updateLastUpdateTime();
                
            } catch (error) {
                console.error('Error al cargar datos:', error);
                showError('Error al cargar los datos. Intente nuevamente.');
                updateConnectionStatus('Error de conexión', 'danger');
            } finally {
                hideLoading('#controlWeightTable');
            }
        }
        
        // Inicializar o actualizar la tabla DataTable
        function initializeOrUpdateDataTable(data) {
            const table = $('#controlWeightTable');
            
            // Destruir la tabla existente si ya está inicializada
            if ($.fn.DataTable.isDataTable(table)) {
                dataTable.clear().destroy();
                table.empty();
            }
            
            // Inicializar DataTable
            dataTable = table.DataTable({
                data: data,
                columns: [
                    { 
                        data: 'last_control_weight',
                        className: 'text-center fw-bold',
                        render: function(data) {
                            return data ? `${parseFloat(data).toFixed(2)} kg` : '-';
                        }
                    },
                    { 
                        data: 'last_dimension',
                        className: 'text-center',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    { 
                        data: 'last_box_number',
                        className: 'text-center',
                        render: function(data) {
                            return data ? `<span class="badge bg-info">${data}</span>` : '-';
                        }
                    },
                    { 
                        data: 'last_barcoder',
                        render: function(data) {
                            return data ? `<span class="badge bg-primary">${data}</span>` : '-';
                        }
                    },
                    { 
                        data: 'last_final_barcoder',
                        render: function(data) {
                            return data ? `<span class="badge bg-success">${data}</span>` : '-';
                        }
                    },
                    { 
                        data: 'created_at',
                        className: 'text-end',
                        render: function(data) {
                            if (!data) return '-';
                            const date = moment(data);
                            return `<span title="${date.format('LLLL')}">${date.format('DD/MM/YYYY HH:mm:ss')}</span>`;
                        }
                    }
                ],
                order: [[5, 'desc']],
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
                dom: '<"row"<"col-md-6"l><"col-md-6 d-flex justify-content-end"f>>' +
                     '<"row"<"col-12"tr>>' +
                     '<"row mt-3"<"col-md-5"i><"col-md-7"p>>',

                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                initComplete: function() {
                    // Mejorar la apariencia de los elementos del DataTable
                    $('.dataTables_filter input').addClass('form-control form-control-sm');
                    $('.dataTables_length select').addClass('form-select form-select-sm');
                    
                    // Actualizar información de paginación
                    updateTableInfo();
                },
                drawCallback: function() {
                    // Actualizar información de paginación en cada cambio de página
                    updateTableInfo();
                    
                    // Aplicar efectos de hover a las filas
                    $('tbody tr').addClass('table-hover');
                }
            });
            
            // Los botones ya están configurados en la inicialización del DataTable
        }
        
        // Actualizar KPIs
        function updateKPIs(data) {
            if (!data || data.length === 0) {
                $('#totalRecords').text('0');
                $('#avgWeight').text('0.00');
                $('#avgBoxes').text('0');
                return;
            }
            
            // Calcular métricas
            const total = data.length;
            const totalWeight = data.reduce((sum, item) => sum + (parseFloat(item.last_control_weight) || 0), 0);
            const avgWeight = total > 0 ? (totalWeight / total).toFixed(2) : 0;
            const totalBoxes = data.reduce((sum, item) => sum + (parseInt(item.last_box_number) || 0), 0);
            const avgBoxes = total > 0 ? Math.round(totalBoxes / total) : 0;
            
            // Actualizar UI
            $('#totalRecords').text(total.toLocaleString());
            $('#avgWeight').text(avgWeight);
            $('#avgBoxes').text(avgBoxes.toLocaleString());
            
            // Actualizar tendencias (ejemplo simple)
            updateTrends();
        }
        
        // Actualizar tendencias (ejemplo)
        function updateTrends() {
            // En una implementación real, aquí compararías con datos anteriores
            const trends = {
                records: Math.floor(Math.random() * 20) - 10,
                weight: Math.floor(Math.random() * 20) - 10,
                boxes: Math.floor(Math.random() * 20) - 10
            };
            
            updateTrendElement('#recordsTrend', trends.records);
            updateTrendElement('#weightTrend', trends.weight);
            updateTrendElement('#boxesTrend', trends.boxes);
        }
        
        function updateTrendElement(selector, value) {
            const element = $(selector);
            const parent = element.parent();
            
            parent.removeClass('bg-success bg-warning bg-danger bg-primary');
            
            if (value > 0) {
                parent.addClass('bg-success');
                element.html(`<i class="fas fa-arrow-up me-1"></i>${value}%`);
            } else if (value < 0) {
                parent.addClass('bg-danger');
                element.html(`<i class="fas fa-arrow-down me-1"></i>${Math.abs(value)}%`);
            } else {
                parent.addClass('bg-primary');
                element.html(`<i class="fas fa-equals me-1"></i>${value}%`);
            }
        }
        
        // Actualizar información de la tabla
        function updateTableInfo() {
            if (!dataTable) return;
            
            const pageInfo = dataTable.page.info();
            const total = pageInfo.recordsTotal;
            const start = pageInfo.start + 1;
            const end = pageInfo.end < total ? pageInfo.end : total;
            
            $('#tableInfo').text(`Mostrando ${start} a ${end} de ${total} registros`);
        }
        
        // Actualizar estado de conexión
        function updateConnectionStatus(text, type = 'success') {
            const status = $('#connectionStatus');
            const statusText = $('#connectionStatusText');
            
            status.removeClass('bg-success bg-warning bg-danger');
            status.addClass(`bg-${type}`);
            statusText.text(text);
            
            // Animar el ícono de actualización
            if (type === 'success') {
                $('#updateIcon').addClass('fa-spin');
                setTimeout(() => {
                    $('#updateIcon').removeClass('fa-spin');
                }, 1000);
            }
        }
        
        // Actualizar hora de última actualización
        function updateLastUpdateTime() {
            $('#lastUpdate').text(moment().format('DD/MM/YYYY HH:mm:ss'));
        }
        
        // Refrescar datos
        function refreshData() {
            const modbusToken = $('#modbusSelect').val();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            
            if (modbusToken && startDate && endDate) {
                loadData(modbusToken, startDate, endDate);
            }
        }
        
        // Exportar datos
        function exportData(type) {
            if (!dataTable) {
                showWarning('No hay datos para exportar');
                return;
            }
            
            switch (type) {
                case 'excel':
                    // Exportar a Excel manualmente
                    let csvContent = "data:text/csv;charset=utf-8,";
                    
                    // Encabezados
                    let headers = [];
                    $(dataTable.table().header()).find('th').each(function() {
                        headers.push('"' + $(this).text().trim() + '"');
                    });
                    csvContent += headers.join(",") + "\r\n";
                    
                    // Datos
                    dataTable.rows().every(function() {
                        let rowData = this.data();
                        let row = [];
                        
                        // Peso
                        row.push('"' + (rowData.last_control_weight ? parseFloat(rowData.last_control_weight).toFixed(2) + ' kg' : '-') + '"');
                        
                        // Dimensión
                        row.push('"' + (rowData.last_dimension || '-') + '"');
                        
                        // N° Caja
                        row.push('"' + (rowData.last_box_number || '-') + '"');
                        
                        // Código de Barras
                        row.push('"' + (rowData.last_barcoder || '-') + '"');
                        
                        // Código de Barras Final
                        row.push('"' + (rowData.last_final_barcoder || '-') + '"');
                        
                        // Fecha/Hora
                        row.push('"' + (rowData.created_at ? moment(rowData.created_at).format('DD/MM/YYYY HH:mm:ss') : '-') + '"');
                        
                        csvContent += row.join(",") + "\r\n";
                    });
                    
                    // Crear enlace de descarga
                    let encodedUri = encodeURI(csvContent);
                    let link = document.createElement("a");
                    link.setAttribute("href", encodedUri);
                    link.setAttribute("download", "Reporte_Pesos_" + moment().format('YYYY-MM-DD') + ".csv");
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    break;
                    
                case 'pdf':
                    // Implementar exportación a PDF si es necesario
                    showInfo('Exportar a PDF no implementado aún');
                    break;
                    
                case 'print':
                    // Imprimir manualmente
                    let printWindow = window.open('', '_blank');
                    let tableHtml = '<html><head><title>Reporte de Pesos</title>';
                    tableHtml += '<style>body{font-family:Arial,sans-serif;font-size:12px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}</style>';
                    tableHtml += '</head><body>';
                    tableHtml += '<h1>Reporte de Pesos</h1>';
                    tableHtml += '<p>Fecha: ' + moment().format('DD/MM/YYYY HH:mm:ss') + '</p>';
                    tableHtml += '<table>';
                    
                    // Encabezados
                    tableHtml += '<thead><tr>';
                    $(dataTable.table().header()).find('th').each(function() {
                        tableHtml += '<th>' + $(this).text().trim() + '</th>';
                    });
                    tableHtml += '</tr></thead>';
                    
                    // Datos
                    tableHtml += '<tbody>';
                    dataTable.rows().every(function() {
                        let rowData = this.data();
                        tableHtml += '<tr>';
                        
                        // Peso
                        tableHtml += '<td>' + (rowData.last_control_weight ? parseFloat(rowData.last_control_weight).toFixed(2) + ' kg' : '-') + '</td>';
                        
                        // Dimensión
                        tableHtml += '<td>' + (rowData.last_dimension || '-') + '</td>';
                        
                        // N° Caja
                        tableHtml += '<td>' + (rowData.last_box_number || '-') + '</td>';
                        
                        // Código de Barras
                        tableHtml += '<td>' + (rowData.last_barcoder || '-') + '</td>';
                        
                        // Código de Barras Final
                        tableHtml += '<td>' + (rowData.last_final_barcoder || '-') + '</td>';
                        
                        // Fecha/Hora
                        tableHtml += '<td>' + (rowData.created_at ? moment(rowData.created_at).format('DD/MM/YYYY HH:mm:ss') : '-') + '</td>';
                        
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
        
        // Iniciar actualización automática
        function startAutoRefresh() {
            stopAutoRefresh(); // Detener cualquier intervalo existente
            
            if (isAutoRefresh) {
                refreshInterval = setInterval(() => {
                    refreshData();
                }, 30000); // 30 segundos
                
                $('#autoRefreshLabel').text('Actualización automática (activa)');
            }
        }
        
        // Detener actualización automática
        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                $('#autoRefreshLabel').text('Actualización automática');
            }
        }
        
        // Mostrar loading
        function showLoading(selector, message = 'Cargando...') {
            const $element = $(selector);
            $element.addClass('position-relative');
            
            // Evitar múltiples loaders
            if ($element.find('.loading-overlay').length === 0) {
                $element.append(`
                    <div class="loading-overlay" style="
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(255, 255, 255, 0.8);
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        z-index: 1000;
                    ">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-2" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mb-0 text-muted">${message}</p>
                        </div>
                    </div>
                `);
            }
        }
        
        // Ocultar loading
        function hideLoading(selector) {
            const $element = $(selector);
            $element.removeClass('position-relative');
            $element.find('.loading-overlay').remove();
        }
        
        // Mostrar mensaje de error
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#4e73df',
            });
        }
        
        // Mostrar advertencia
        function showWarning(message) {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: message,
                confirmButtonColor: '#f6c23e',
            });
        }
        
        // Mostrar información
        function showInfo(message) {
            Swal.fire({
                icon: 'info',
                title: 'Información',
                text: message,
                confirmButtonColor: '#36b9cc',
            });
        }
    </script>
@endpush
