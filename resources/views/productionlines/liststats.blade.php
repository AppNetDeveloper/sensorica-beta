@extends('layouts.admin')

@section('title', 'Estadísticas de Líneas de Producción')

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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
    <div class="container-fluid py-4">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filtros de Búsqueda</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="modbusSelect" class="form-label">Línea de Producción</label>
                        <select id="modbusSelect" class="form-select">
                            <option value="">Cargando...</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="startDate" class="form-label">Fecha Inicio</label>
                        <input type="datetime-local" id="startDate" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label">Fecha Fin</label>
                        <input type="datetime-local" id="endDate" class="form-control">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button id="fetchData" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Resumen -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-start border-primary border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Registros</h6>
                                <h3 class="mb-0" id="totalRecords">0</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-database text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
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
            <div class="col-md-3">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Unidades</h6>
                                <h3 class="mb-0" id="totalUnits">0</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-boxes text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-start border-info border-3 h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Estado</h6>
                                <span class="badge bg-success" id="connectionStatus">
                                    <i class="fas fa-circle me-1"></i> Conectado
                                </span>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-sync-alt text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Datos -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Datos de Producción</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="controlWeightTable" class="table table-hover table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Línea</th>
                                <th>Orden</th>
                                <th>Caja</th>
                                <th>Unidades</th>
                                <th>UPM Real</th>
                                <th>UPM Teórico</th>
                                <th>OEE</th>
                                <th>Estado</th>
                                <th>Actualizado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Datos dinámicos -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
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
                            <div class="card">
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
                                        <label class="fw-bold">Última actualización:</label>
                                        <span id="modal-updated-at"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">OEE</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="oeeChart" height="200"></canvas>
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
    <style>
        .dataTables_wrapper {
            overflow-x: auto;
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

    <script>
        const token = new URLSearchParams(window.location.search).get('token');
        console.log("Token obtenido:", token);

        async function fetchProductionLines() {
            try {
                console.log("Intentando obtener líneas de producción...");
                const response = await fetch(`/api/production-lines/${token}`);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Líneas de producción recibidas:", data);

                const modbusSelect = $('#modbusSelect');
                modbusSelect.empty();
                data.forEach(line => {
                    modbusSelect.append(`<option value="${line.token}">${line.name}</option>`);
                });
            } catch (error) {
                console.error("Error al cargar líneas de producción:", error);
            }
        }

        async function fetchOrderStats(lineToken, startDate, endDate) {
            try {
                const url = `/api/order-stats-all?token=${lineToken}&start_date=${startDate}&end_date=${endDate}`;
                console.log("URL de datos de estadísticas:", url);

                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Datos de estadísticas recibidos:", data);

                // Procesar los datos para asegurar que tienen la estructura correcta
                const processedData = data.map(item => ({
                    id: item.id || '-',
                    production_line_name: item.production_line_name || '-',
                    order_id: item.order_id || '-',
                    box: item.box || '-',
                    units: parseInt(item.units) || 0,
                    units_per_minute_real: parseFloat(item.units_per_minute_real) || 0,
                    units_per_minute_theoretical: parseFloat(item.units_per_minute_theoretical) || 0,
                    oee: parseFloat(item.oee) || 0,
                    status: item.status || 'unknown',
                    updated_at: item.updated_at || null
                }));
                
                // Actualizar los KPIs
                updateKPIs(processedData);
                
                const table = $('#controlWeightTable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        { 
                            extend: 'excelHtml5', 
                            text: '<i class="fas fa-file-excel me-1"></i> Exportar a Excel', 
                            className: 'btn btn-sm btn-success',
                            title: 'Datos de Producción - ' + new Date().toLocaleDateString()
                        }
                    ],
                    scrollX: true,
                    responsive: true,
                    data: processedData,
                    destroy: true,
                    columns: [
                        { data: 'id', title: 'ID' },
                        { data: 'production_line_name', title: 'Línea' },
                        { data: 'order_id', title: 'Orden' },
                        { data: 'box', title: 'Caja' },
                        { data: 'units', title: 'Unidades' },
                        { 
                            data: 'units_per_minute_real', 
                            title: 'UPM Real',
                            render: function(data) {
                                return data.toFixed(2);
                            }
                        },
                        { 
                            data: 'units_per_minute_theoretical', 
                            title: 'UPM Teórico',
                            render: function(data) {
                                return data.toFixed(2);
                            }
                        },
                        { 
                            data: 'oee', 
                            title: 'OEE',
                            render: function(data) {
                                // El OEE ya viene como porcentaje (ej: 94.24)
                                const oeeValue = parseFloat(data);
                                // Verificar si el valor ya es un porcentaje (>1) o decimal (<1)
                                const percentage = oeeValue > 1 ? oeeValue.toFixed(2) : (oeeValue * 100).toFixed(2);
                                let colorClass = 'text-danger';
                                if (percentage >= 80) colorClass = 'text-success';
                                else if (percentage >= 60) colorClass = 'text-warning';
                                return `<span class="${colorClass}">${percentage}%</span>`;
                            }
                        },
                        { 
                            data: 'status', 
                            title: 'Estado',
                            render: function(data) {
                                const statusMap = {
                                    'in_progress': '<span class="badge bg-warning">En Progreso</span>',
                                    'completed': '<span class="badge bg-success">Completado</span>',
                                    'paused': '<span class="badge bg-secondary">Pausado</span>',
                                    'error': '<span class="badge bg-danger">Error</span>',
                                    'pending': '<span class="badge bg-info">Pendiente</span>',
                                    'unknown': '<span class="badge bg-light text-dark">Desconocido</span>'
                                };
                                return statusMap[data] || statusMap['unknown'];
                            }
                        },
                        { 
                            data: 'updated_at', 
                            title: 'Actualizado',
                            render: function(data) {
                                return data ? new Date(data).toLocaleString() : '-';
                            }
                        },
                        {
                            data: null,
                            title: 'Acciones',
                            orderable: false,
                            className: 'text-center',
                            render: function(data, type, row) {
                                return `
                                    <button class="btn btn-sm btn-info btn-view" data-id="${row.id}" title="Ver detalles" onclick="showDetailsModal(${JSON.stringify(row).replace(/"/g, '&quot;')})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                `;
                            }
                        }
                    ],
                    order: [[0, 'desc']],
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
            // Total de registros
            const totalRecords = data.length;
            $('#totalRecords').text(totalRecords);
            
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
            
            // Total de unidades
            let totalUnits = 0;
            data.forEach(item => {
                if (item.units && !isNaN(item.units)) {
                    totalUnits += parseInt(item.units);
                }
            });
            
            $('#totalUnits').text(totalUnits.toLocaleString());
            
            // Actualizar estado de conexión
            $('#connectionStatus').html('<i class="fas fa-circle me-1"></i> Conectado');
            $('#connectionStatus').removeClass('bg-danger bg-warning').addClass('bg-success');
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
            console.log("Mostrando detalles de la fila:", row);
            
            // Actualizar datos en el modal
            $('#modal-line-name').text(row.production_line_name);
            $('#modal-order-id').text(row.order_id);
            $('#modal-units').text(row.units.toLocaleString());
            $('#modal-upm-real').text(row.units_per_minute_real.toFixed(2));
            $('#modal-upm-theoretical').text(row.units_per_minute_theoretical.toFixed(2));
            
            // Actualizar estado
            const statusMap = {
                'active': { text: 'Activo', class: 'bg-success' },
                'inactive': { text: 'Inactivo', class: 'bg-danger' },
                'paused': { text: 'Pausado', class: 'bg-warning' },
                'maintenance': { text: 'Mantenimiento', class: 'bg-info' },
                'unknown': { text: 'Desconocido', class: 'bg-secondary' }
            };
            
            const status = statusMap[row.status] || statusMap.unknown;
            $('#modal-status').text(status.text).removeClass().addClass(`badge ${status.class}`);
            
            // Actualizar fecha
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
            const oeeValue = parseFloat(row.oee);
            const oeePercentage = oeeValue > 1 ? oeeValue : oeeValue * 100;
            
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
            
            // Añadir texto en el centro del gráfico
            Chart.register({
                id: 'doughnutCenterText',
                afterDraw: function(chart) {
                    if (chart.config.type === 'doughnut') {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        
                        ctx.restore();
                        const fontSize = (height / 114).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        
                        const text = `${oeePercentage.toFixed(2)}%`;
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

            $('#fetchData').click(() => {
                const lineToken = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                console.log("Parámetros seleccionados:", { lineToken, startDate, endDate });

                if (lineToken && startDate && endDate) {
                    // Mostrar indicador de carga
                    $('#connectionStatus').html('<i class="fas fa-sync fa-spin me-1"></i> Cargando...');
                    $('#connectionStatus').removeClass('bg-success bg-danger').addClass('bg-warning');
                    
                    fetchOrderStats(lineToken, startDate, endDate);
                } else {
                    alert("Por favor, completa todos los campos.");
                }
            });
        });
    </script>
@endpush