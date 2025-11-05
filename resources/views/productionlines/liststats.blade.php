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

        /* ===== ESTILOS PARA MODAL DE RESULTADOS IA ===== */
        /* Contenido del resultado */
        .ai-result-content {
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            transition: font-size 0.2s ease;
            max-height: 65vh;
            overflow-y: auto;
            padding: 1rem;
            background: white;
            border-radius: 8px;
        }

        /* Tablas Markdown con estilos Bootstrap */
        .ai-result-content table {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 6px;
            overflow: hidden;
        }

        .ai-result-content table thead th {
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            border: none;
            text-align: left;
            white-space: nowrap;
        }

        .ai-result-content table tbody td {
            padding: 0.65rem 0.75rem;
            border-top: 1px solid #dee2e6;
            vertical-align: top;
        }

        .ai-result-content table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .ai-result-content table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.15s ease-in-out;
        }

        /* Encabezados */
        .ai-result-content h1, .ai-result-content h2, .ai-result-content h3,
        .ai-result-content h4, .ai-result-content h5, .ai-result-content h6 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #212529;
        }

        .ai-result-content h1 { font-size: 1.8rem; border-bottom: 2px solid #0d6efd; padding-bottom: 0.3rem; }
        .ai-result-content h2 { font-size: 1.5rem; color: #0d6efd; }
        .ai-result-content h3 { font-size: 1.3rem; color: #495057; }
        .ai-result-content h4 { font-size: 1.1rem; }
        .ai-result-content h5 { font-size: 1rem; }

        /* Listas */
        .ai-result-content ul, .ai-result-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .ai-result-content li {
            margin-bottom: 0.35rem;
        }

        /* Párrafos y separadores */
        .ai-result-content p {
            margin-bottom: 1rem;
        }

        .ai-result-content hr {
            margin: 1.5rem 0;
            border: none;
            border-top: 2px solid #e9ecef;
        }

        /* Código */
        .ai-result-content code {
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #d63384;
        }

        .ai-result-content pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            border-left: 4px solid #0d6efd;
        }

        .ai-result-content pre code {
            background: none;
            padding: 0;
            color: #212529;
        }

        /* Blockquotes */
        .ai-result-content blockquote {
            padding: 0.5rem 1rem;
            margin: 1rem 0;
            border-left: 4px solid #0dcaf0;
            background-color: #f8f9fa;
            font-style: italic;
        }

        /* Enlaces */
        .ai-result-content a {
            color: #0d6efd;
            text-decoration: none;
        }

        .ai-result-content a:hover {
            text-decoration: underline;
        }

        /* Barra de progreso de scroll */
        .scroll-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
            width: 0%;
            transition: width 0.1s ease;
            z-index: 1050;
            border-radius: 0 2px 2px 0;
        }

        /* Botón volver arriba */
        #btnScrollTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border-radius: 50% !important;
            background: #0d6efd;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1055;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #btnScrollTop.show {
            opacity: 1;
            visibility: visible;
        }

        #btnScrollTop:hover {
            background: #0b5ed7;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
        }

        /* Controles de fuente */
        .font-controls .btn {
            font-family: monospace;
            font-weight: bold;
            min-width: 36px;
        }

        /* Modal en fullscreen personalizado */
        .modal-fullscreen-custom {
            max-width: 100% !important;
            width: 100% !important;
            height: 100vh;
            margin: 0 !important;
        }

        .modal-fullscreen-custom .modal-content {
            height: 100vh;
            border-radius: 0 !important;
        }

        .modal-fullscreen-custom .ai-result-content {
            max-height: calc(100vh - 200px);
        }

        /* Tabs personalizados */
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-radius: 6px 6px 0 0;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            background-color: #f8f9fa;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            background-color: white;
            border-color: #dee2e6 #dee2e6 #fff;
            color: #0d6efd;
        }

        /* Toolbar de acciones */
        .ai-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .ai-toolbar .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Toast personalizado */
        .copy-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #198754;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Metadatos del análisis */
        .ai-metadata {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .ai-metadata i {
            color: #0d6efd;
        }

        /* Responsive ajustes */
        @media (max-width: 768px) {
            .modal-dialog[style*="80%"] {
                max-width: 95% !important;
                width: 95% !important;
            }

            .ai-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .ai-toolbar .btn-group {
                width: 100%;
                justify-content: center;
            }

            .ai-result-content {
                font-size: 0.9rem;
            }

            .ai-result-content table {
                font-size: 0.85rem;
            }

            #btnScrollTop {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
            }
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
            <div class="card-body py-2 px-3">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Líneas de Producción</label>
                        <select id="modbusSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Empleado</label>
                        <select id="operatorSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                            <!-- Opciones dinámicas de operarios -->
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Artículo</label>
                        <select class="form-select" disabled>
                            <option>Todos</option>
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
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-primary" id="fetchData" title="Buscar">
                            <i class="fas fa-search"></i>
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
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-success border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Promedio OEE</h6>
                                <h4 class="mb-0" id="avgOEE">0%</h4>
                            </div>
                            <div class="bg-success bg-opacity-10 p-2 rounded">
                                <i class="fas fa-chart-line text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-primary border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Duración</h6>
                                <h4 class="mb-0" id="totalDuration">00:00:00</h4>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-2 rounded">
                                <i class="fas fa-clock text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Diferencia</h6>
                                <h4 class="mb-0" id="totalTheoretical" title="Suma neta: tiempo ganado (fast_time) menos tiempo de más (out_time)">00:00:00</h4>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded">
                                <i class="fas fa-balance-scale text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-secondary border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Total Preparación</h6>
                                <h4 class="mb-0" id="totalPrepairTime">00:00:00</h4>
                            </div>
                            <div class="bg-secondary bg-opacity-10 p-2 rounded">
                                <i class="fas fa-hand-paper text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-warning border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Tiempo Lento</h6>
                                <h4 class="mb-0" id="totalSlowTime">00:00:00</h4>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded">
                                <i class="fas fa-tachometer-alt text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-danger border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Paradas</h6>
                                <h4 class="mb-0" id="totalProductionStopsTime">00:00:00</h4>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-2 rounded">
                                <i class="fas fa-tools text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl mb-3">
                <div class="card border-start border-danger border-3 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1 small">Falta Material</h6>
                                <h4 class="mb-0" id="totalDownTime">00:00:00</h4>
                            </div>
                            <div class="bg-danger bg-opacity-10 p-2 rounded" style="opacity: 0.8;">
                                <i class="fas fa-exclamation-triangle text-danger" style="opacity: 0.8;"></i>
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
                    <div class="d-flex align-items-center gap-3">
                        <h6 class="mb-0">
                            <i class="fas fa-table me-2 text-primary"></i>
                            Filtros OEE
                        </h6>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hideZeroOEE">
                            <label class="form-check-label" for="hideZeroOEE">
                                Ocultar 0% OEE
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="hide100OEE">
                            <label class="form-check-label" for="hide100OEE">
                                Ocultar 100% OEE
                            </label>
                        </div>
                    </div>
                    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
                        @php($aiUrl = config('services.ai.url'))
                        @php($aiToken = config('services.ai.token'))
                        @if(!empty($aiUrl) && !empty($aiToken))
                        <div class="btn-group btn-group-sm me-2" role="group">
                            <button type="button" class="btn btn-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="@lang('Análisis con IA')">
                                <i class="bi bi-stars me-1 text-white"></i><span class="d-none d-sm-inline">@lang('Análisis IA')</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><h6 class="dropdown-header"><i class="fas fa-brain me-1"></i> Tipo de Análisis</h6></li>
                                <li><a class="dropdown-item" href="#" data-analysis="oee-general">
                                    <i class="fas fa-chart-line text-success me-2"></i>Análisis General de OEE
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="stops">
                                    <i class="fas fa-pause-circle text-danger me-2"></i>Análisis de Paradas
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="performance">
                                    <i class="fas fa-tachometer-alt text-primary me-2"></i>Rendimiento por Línea
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="operators">
                                    <i class="fas fa-users text-info me-2"></i>Eficiencia de Operadores
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-analysis="comparison">
                                    <i class="fas fa-balance-scale text-warning me-2"></i>Comparativa Top/Bottom
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="availability-performance">
                                    <i class="fas fa-exchange-alt text-primary me-2"></i>Disponibilidad vs Rendimiento
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="shift-variations">
                                    <i class="fas fa-user-clock text-info me-2"></i>Variaciones por Turno/Operador
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="shift-profitability">
                                    <i class="fas fa-money-bill-trend-up text-success me-2"></i>Rentabilidad por Turno
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="idle-time">
                                    <i class="fas fa-hourglass-half text-danger me-2"></i>Consumo de Tiempo Improductivo
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" data-analysis="full">
                                    <i class="fas fa-layer-group text-dark me-2"></i>Análisis Total (CSV extendido)
                                </a></li>
                            </ul>
                        </div>
                        @endif
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
            <div class="card-body py-2 px-3">
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
        <div class="modal-dialog" style="max-width: 80%; width: 80%;">
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
                                <div class="card-body p-0">
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="40%">Línea</td>
                                                <td id="modal-line-name"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Orden</td>
                                                <td id="modal-order-id"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Caja</td>
                                                <td id="modal-box"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Unidades</td>
                                                <td id="modal-units"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">UPM Real</td>
                                                <td id="modal-upm-real"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">UPM Teórico</td>
                                                <td id="modal-upm-theoretical"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Estado</td>
                                                <td><span id="modal-status" class="badge"></span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo de inicio</td>
                                                <td id="modal-created-at"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Última actualización</td>
                                                <td id="modal-updated-at"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Básculas</h6>
                                </div>
                                <div class="card-body p-0">
                                    <h6 class="text-primary p-2 mb-0 bg-light border-bottom">Báscula Final de Línea</h6>
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="50%">Nº en Turno</td>
                                                <td id="modal-weights-0-shift-number"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kg en Turno</td>
                                                <td id="modal-weights-0-shift-kg"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Nº en Orden</td>
                                                <td id="modal-weights-0-order-number"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kg en Orden</td>
                                                <td id="modal-weights-0-order-kg"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <h6 class="text-danger p-2 mb-0 bg-light border-bottom border-top">Básculas de Rechazo</h6>
                                    <div id="weights-rejection-container" class="p-2">
                                        <!-- Aquí se insertarán dinámicamente las básculas de rechazo -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">OEE</h6>
                                </div>
                                <div class="card-body p-0 text-center">
                                    <div style="height: 402px; padding: 15px; display: flex; justify-content: center; align-items: center;">
                                        <canvas id="oeeChart" style="max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Tiempos de Producción</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="50%">Tiempo de producción</td>
                                                <td id="modal-on-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Ganado</td>
                                                <td id="modal-fast-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Lento</td>
                                                <td id="modal-slow-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo de Más</td>
                                                <td id="modal-out-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Parada Falta Material</td>
                                                <td id="modal-down-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Paradas No Justificadas</td>
                                                <td id="modal-production-stops-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Preparación</td>
                                                <td id="modal-prepair-time"></td>
                                            </tr>
                                        </tbody>
                                    </table>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/dashboard-animations.css') }}" rel="stylesheet">
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="{{ asset('js/dashboard-animations.js') }}?v={{ time() }}"></script>

    <script>
        $(document).ready(function() {
            console.log('Document ready, checking for DashboardAnimations class...');
            // La clase se inicializa automáticamente en el archivo JS
        });
        // Limitar el rango máximo de fechas a 7 días
        function ensureMaxRange7Days() {
            const startVal = $('#startDate').val();
            const endVal = $('#endDate').val();
            if (!startVal || !endVal) return;
            const start = new Date(startVal);
            const end = new Date(endVal);
            const sevenDaysMs = 7 * 24 * 60 * 60 * 1000;
            if ((end - start) > sevenDaysMs) {
                const newStart = new Date(end.getTime() - sevenDaysMs);
                const fmt = (d) => d.toISOString().slice(0,16);
                $('#startDate').val(fmt(newStart));
            }
        }
    </script>

    <script>
        // IA: Configuración y utilidades
        const AI_URL = "{{ config('services.ai.url') }}";
        const AI_TOKEN = "{{ config('services.ai.token') }}";

        // Funciones auxiliares para normalización y CSV
        function cleanValue(value) {
            if (value === null || value === undefined) return '';
            let str = String(value).trim();
            if (str === '') return '';
            const needsQuoting = /[",\n\r]/.test(str);
            if (str.includes('"')) {
                str = str.replace(/"/g, '""');
            }
            return needsQuoting ? `"${str}"` : str;
        }

        function safeValue(value, fallback = '') {
            if (value === null || value === undefined) return fallback;
            const str = String(value).trim();
            if (!str || str === '-' || str === '--' || str.toLowerCase() === 'null' || str.toLowerCase() === 'undefined') {
                return fallback;
            }
            return str;
        }

        function normalizeDateTime(value) {
            const raw = safeValue(value, '');
            if (!raw || raw === '0000-00-00 00:00:00' || raw === '0000-00-00') return '';
            const trimmed = raw.trim();
            const isIso = /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(trimmed);
            const isSql = /\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/.test(trimmed);
            if (isIso) {
                return trimmed.length === 16 ? `${trimmed}:00` : trimmed;
            }
            if (isSql) {
                const dt = new Date(trimmed.replace(' ', 'T'));
                if (!Number.isNaN(dt.getTime())) {
                    return dt.toISOString();
                }
            }
            const parsed = new Date(trimmed);
            if (!Number.isNaN(parsed.getTime())) {
                return parsed.toISOString();
            }
            return '';
        }

        function durationToSeconds(value) {
            const raw = safeValue(value, '');
            if (!raw) return '';
            if (/^-?\d+$/.test(raw)) {
                return parseInt(raw, 10);
            }
            const match = raw.match(/(-?)(\d{1,2}):(\d{2}):(\d{2})/);
            if (!match) return '';
            const sign = match[1] === '-' ? -1 : 1;
            const hours = parseInt(match[2], 10) || 0;
            const minutes = parseInt(match[3], 10) || 0;
            const seconds = parseInt(match[4], 10) || 0;
            return sign * (hours * 3600 + minutes * 60 + seconds);
        }

        // === Turnos: helpers y caché ===
        const shiftCacheByToken = {};
        const shiftCacheByLineId = {};

        function parseTimeToMinutes(timeStr) {
            if (!timeStr || typeof timeStr !== 'string') return null;
            const parts = timeStr.split(':').map(p => parseInt(p, 10));
            if (parts.length < 2 || parts.some(Number.isNaN)) return null;
            return parts[0] * 60 + parts[1];
        }

        function findShiftForDate(dateStr, shifts) {
            if (!dateStr || !Array.isArray(shifts) || shifts.length === 0) return null;
            const d = new Date(dateStr);
            if (Number.isNaN(d.getTime())) return null;
            const minutes = d.getHours() * 60 + d.getMinutes();
            for (const shift of shifts) {
                const startMin = parseTimeToMinutes(shift.start);
                const endMin = parseTimeToMinutes(shift.end);
                if (startMin === null || endMin === null) continue;
                if (startMin <= endMin) {
                    if (minutes >= startMin && minutes < endMin) return shift;
                } else {
                    if (minutes >= startMin || minutes < endMin) return shift; // cruza medianoche
                }
            }
            return null;
        }

        async function ensureShiftsLoadedForTokens(tokens) {
            try {
                const toLoad = (tokens || []).filter(t => t && !shiftCacheByToken[t]);
                if (toLoad.length === 0) return;
                await Promise.all(toLoad.map(async (t) => {
                    const resp = await fetch(`/api/shift-lists?token=${encodeURIComponent(t)}`);
                    if (!resp.ok) { shiftCacheByToken[t] = []; return; }
                    const arr = await resp.json();
                    const list = Array.isArray(arr) ? arr : [];
                    shiftCacheByToken[t] = list;
                    list.forEach(s => {
                        const lid = s && s.production_line_id;
                        if (!lid) return;
                        if (!Array.isArray(shiftCacheByLineId[lid])) shiftCacheByLineId[lid] = [];
                        shiftCacheByLineId[lid].push(s);
                    });
                }));
                // deduplicate por id
                Object.keys(shiftCacheByLineId).forEach(lid => {
                    const seen = {};
                    shiftCacheByLineId[lid] = shiftCacheByLineId[lid].filter(s => {
                        if (!s || seen[s.id]) return false;
                        seen[s.id] = true;
                        return true;
                    });
                });
            } catch (e) {
                console.warn('[Shifts] Error precargando turnos', e);
            }
        }

        function resolveShiftForRow(item) {
            if (!item) return null;
            const lid = item.production_line_id || (item.production_line && item.production_line.id);
            const shifts = lid ? (shiftCacheByLineId[lid] || []) : [];
            const when = item.created_at || item.start || item.updated_at || null;
            const found = findShiftForDate(when, shifts);
            if (!found) return null;
            return {
                id: found.id,
                name: found.name || `Turno ${found.start}-${found.end}`,
                start: found.start,
                end: found.end,
                active: found.active
            };
        }

        // Análisis General de OEE
        function collectOEEGeneralData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'OEE General' };
            }
            
            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                totalDuration: $('#totalDuration').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };
            
            // CSV normalizado: Línea, Fecha_Inicio_ISO, Fecha_Fin_ISO, OEE_Porcentaje, Duracion_Segundos, Duracion_Formato
            let csv = 'Linea,Fecha_Inicio_ISO,Fecha_Fin_ISO,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato\n';
            let count = 0;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= 50) return false;
                const linea = cleanValue(row.production_line_name ?? row[1]);
                const startIso = cleanValue(normalizeDateTime(row.created_at ?? row[8]));
                const endIso = cleanValue(normalizeDateTime(row.updated_at ?? row[9]));

                const oeeRaw = row.oee ?? row[7];
                const oeeValue = oeeRaw !== null && oeeRaw !== undefined
                    ? (typeof oeeRaw === 'number' ? oeeRaw.toFixed(2) : safeValue(oeeRaw, '0'))
                    : '0';
                const oeePct = cleanValue(oeeValue);

                const durationSeconds = durationToSeconds(row.on_time ?? row[10]) || 0;
                const durationFormatted = cleanValue(formatTime(durationSeconds));

                csv += `${linea},${startIso},${endIso},${oeePct},${cleanValue(String(durationSeconds))},${durationFormatted}\n`;
                count++;
            });

            return { metrics, csv, type: 'OEE General' };
        }

        // Análisis de Paradas
        function collectStopsData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Paradas' };
            }
            
            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                totalStops: $('#totalProductionStopsTime').text() || '00:00:00',
                totalDownTime: $('#totalDownTime').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };
            
            // CSV normalizado: Línea, Paradas_Segundos, Paradas_Formato, Falta_Material_Segundos, Falta_Material_Formato, Preparacion_Segundos, Preparacion_Formato
            let csv = 'Linea,Paradas_Segundos,Paradas_Formato,Falta_Material_Segundos,Falta_Material_Formato,Preparacion_Segundos,Preparacion_Formato\n';
            let count = 0;
            
            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= 50) return false;

                const linea = cleanValue(row.production_line_name ?? row[1]);

                const paradasSeconds = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaMaterialSeconds = durationToSeconds(row.down_time ?? row[14]) || 0;
                const prepSeconds = durationToSeconds(row.prepair_time ?? row[11]) || 0;

                csv += `${linea},${cleanValue(String(paradasSeconds))},${cleanValue(formatTime(paradasSeconds))},${cleanValue(String(faltaMaterialSeconds))},${cleanValue(formatTime(faltaMaterialSeconds))},${cleanValue(String(prepSeconds))},${cleanValue(formatTime(prepSeconds))}\n`;
                count++;
            });

            return { metrics, csv, type: 'Paradas' };
        }

        // Análisis de Rendimiento
        function collectPerformanceData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Rendimiento' };
            }
            
            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                slowTime: $('#totalSlowTime').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };
            
            // CSV normalizado: Línea, OEE_Porcentaje, Tiempo_Lento_Segundos, Tiempo_Lento_Formato, UPM_Real, UPM_Teorico
            let csv = 'Linea,OEE_Porcentaje,Tiempo_Lento_Segundos,Tiempo_Lento_Formato,UPM_Real,UPM_Teorico\n';
            let count = 0;
            
            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= 50) return false;
                const linea = cleanValue(row.production_line_name ?? row[1]);
                const oeeRaw = row.oee ?? row[7];
                const oee = oeeRaw !== null && oeeRaw !== undefined
                    ? cleanValue(typeof oeeRaw === 'number' ? oeeRaw.toFixed(2) : safeValue(oeeRaw, '0'))
                    : '0';
                const lentoSeconds = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const upmReal = cleanValue(row.units_per_minute_real ?? row[5] ?? '0');
                const upmTeo = cleanValue(row.units_per_minute_theoretical ?? row[6] ?? '0');
                csv += `${linea},${oee},${cleanValue(String(lentoSeconds))},${cleanValue(formatTime(lentoSeconds))},${upmReal},${upmTeo}\n`;
                count++;
            });
            
            return { metrics, csv, type: 'Rendimiento' };
        }

        // Análisis de Operadores
        function collectOperatorsData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Operadores' };
            }
            
            const table = $('#controlWeightTable').DataTable();
            const selectedOps = $('#operatorSelect').select2('data').map(o => o.text).join(', ') || 'Todos';
            const metrics = {
                operators: selectedOps,
                avgOEE: $('#avgOEE').text() || '0%',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };
            
            // CSV normalizado: Empleados,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato,Tiempo_Ganado_Segundos,Tiempo_Ganado_Formato,Tiempo_Mas_Segundos,Tiempo_Mas_Formato
            let csv = 'Empleados,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato,Tiempo_Ganado_Segundos,Tiempo_Ganado_Formato,Tiempo_Mas_Segundos,Tiempo_Mas_Formato\n';
            let count = 0;

            const toTimeString = (raw) => {
                if (raw === null || raw === undefined) return '00:00:00';
                if (typeof raw === 'number') return formatTime(raw);
                if (typeof raw === 'string') {
                    const trimmed = raw.trim();
                     if (!trimmed) return '00:00:00';
                    if (/^\d{1,2}:\d{2}:\d{2}$/.test(trimmed)) return trimmed;
                    const parsed = parseInt(trimmed, 10);
                    if (!isNaN(parsed)) return formatTime(parsed);
                }
                return '00:00:00';
            };

            const formatOEE = (raw) => {
                if (raw === null || raw === undefined) return '0%';
                if (typeof raw === 'number') return `${raw.toFixed(2)}%`;
                if (typeof raw === 'string') {
                    const trimmed = raw.trim();
                    if (!trimmed) return '0%';
                    if (trimmed.endsWith('%')) return trimmed;
                    const parsed = parseFloat(trimmed);
                    if (!isNaN(parsed)) return `${parsed.toFixed(2)}%`;
                }
                return '0%';
            };

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= 50) return false;
                let empleadosRaw = '';
                if (Array.isArray(row.operator_names)) {
                    empleadosRaw = row.operator_names.join(' | ');
                } else if (row.operator_names) {
                    empleadosRaw = row.operator_names;
                } else {
                    empleadosRaw = 'Sin asignar';
                }
                const empleados = cleanValue(empleadosRaw);
                const oee = cleanValue(formatOEE(row.oee ?? row[7]));
                const durSeconds = durationToSeconds(row.on_time ?? row[10]) || 0;
                const ganadoSeconds = durationToSeconds(row.fast_time ?? row[16]) || 0;
                const masSeconds = durationToSeconds(row.out_time ?? row[17]) || 0;
                csv += `${empleados},${oee},${cleanValue(String(durSeconds))},${cleanValue(toTimeString(durSeconds))},${cleanValue(String(ganadoSeconds))},${cleanValue(toTimeString(ganadoSeconds))},${cleanValue(String(masSeconds))},${cleanValue(toTimeString(masSeconds))}\n`;
                count++;
            });
            
            return { metrics, csv, type: 'Operadores' };
        }

        // Comparativa Top/Bottom
        function collectComparisonData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Comparativa' };
            }
            
            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };
            
            // CSV reducido: top/bottom con métricas extendidas
            let csv = 'Tipo,Linea,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato,Preparacion_Segundos,Preparacion_Formato,Lento_Segundos,Lento_Formato,Paradas_Segundos,Paradas_Formato,Falta_Material_Segundos,Falta_Material_Formato\n';
            const allRows = [];
            
            table.rows({search: 'applied'}).data().each(function(row) {
                allRows.push({
                    linea: cleanValue(row.production_line_name ?? row[1]),
                    oee: cleanValue((row.oee ?? row[7]) ? (typeof (row.oee ?? row[7]) === 'number' ? (row.oee ?? row[7]).toFixed(2) : safeValue(row.oee ?? row[7], '0')) : '0'),
                    duracionSeconds: durationToSeconds(row.on_time ?? row[10]) || 0,
                    preparacionSeconds: durationToSeconds(row.prepair_time ?? row[11]) || 0,
                    lentoSeconds: durationToSeconds(row.slow_time ?? row[12]) || 0,
                    paradasSeconds: durationToSeconds(row.production_stops_time ?? row[13]) || 0,
                    faltaMaterialSeconds: durationToSeconds(row.down_time ?? row[14]) || 0
                });
            });
            
            // Top 10 y Bottom 10
            const top10 = allRows.slice(0, 10);
            const bottom10 = allRows.slice(-10);
            
            top10.forEach(r => {
                csv += `TOP,${r.linea},${r.oee},${cleanValue(String(r.duracionSeconds))},${cleanValue(formatTime(r.duracionSeconds))},${cleanValue(String(r.preparacionSeconds))},${cleanValue(formatTime(r.preparacionSeconds))},${cleanValue(String(r.lentoSeconds))},${cleanValue(formatTime(r.lentoSeconds))},${cleanValue(String(r.paradasSeconds))},${cleanValue(formatTime(r.paradasSeconds))},${cleanValue(String(r.faltaMaterialSeconds))},${cleanValue(formatTime(r.faltaMaterialSeconds))}\n`;
            });
            bottom10.forEach(r => {
                csv += `BOTTOM,${r.linea},${r.oee},${cleanValue(String(r.duracionSeconds))},${cleanValue(formatTime(r.duracionSeconds))},${cleanValue(String(r.preparacionSeconds))},${cleanValue(formatTime(r.preparacionSeconds))},${cleanValue(String(r.lentoSeconds))},${cleanValue(formatTime(r.lentoSeconds))},${cleanValue(String(r.paradasSeconds))},${cleanValue(formatTime(r.paradasSeconds))},${cleanValue(String(r.faltaMaterialSeconds))},${cleanValue(formatTime(r.faltaMaterialSeconds))}\n`;
            });
            
            return { metrics, csv, type: 'Comparativa' };
        }

        // Disponibilidad vs Rendimiento
        function collectAvailabilityPerformanceData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Disponibilidad vs Rendimiento' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            let csv = 'Linea,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato,Tiempo_Disponible_Segundos,Tiempo_Disponible_Formato,Tiempo_Incidencias_Segundos,Tiempo_Incidencias_Formato\n';
            let count = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const linea = cleanValue(row.production_line_name ?? row[1]);
                const oeeRaw = row.oee ?? row[7];
                const oee = cleanValue(oeeRaw !== null && oeeRaw !== undefined ? (typeof oeeRaw === 'number' ? oeeRaw.toFixed(2) : safeValue(oeeRaw, '0')) : '0');
                const duracionSeconds = durationToSeconds(row.on_time ?? row[10]) || 0;
                const paradasSeconds = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSeconds = durationToSeconds(row.down_time ?? row[14]) || 0;
                const prepSeconds = durationToSeconds(row.prepair_time ?? row[11]) || 0;

                const disponibleSeconds = Math.max(duracionSeconds - paradasSeconds - faltaSeconds, 0);
                const incidenciasSeconds = paradasSeconds + faltaSeconds + prepSeconds;

                csv += `${linea},${oee},${cleanValue(String(duracionSeconds))},${cleanValue(formatTime(duracionSeconds))},${cleanValue(String(disponibleSeconds))},${cleanValue(formatTime(disponibleSeconds))},${cleanValue(String(incidenciasSeconds))},${cleanValue(formatTime(incidenciasSeconds))}\n`;
                count++;
            });

            const note = count >= maxRows ? `Mostrando primeras ${maxRows} líneas` : `Total analizado: ${count} líneas`;
            return { metrics, csv, type: 'Disponibilidad vs Rendimiento', note };
        }

        // Variaciones por Turno/Operador
        function collectShiftVariationsData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Variaciones por Turno/Operador' };
            }

            const table = $('#controlWeightTable').DataTable();
            const selectedOps = $('#operatorSelect').select2('data').map(o => o.text).join(', ') || 'Todos';
            const metrics = {
                operators: selectedOps,
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            let csv = 'Linea,Turno,Operadores,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato,Tiempo_Lento_Segundos,Tiempo_Lento_Formato,Tiempo_Ganado_Segundos,Tiempo_Ganado_Formato\n';
            let count = 0;
            const maxRows = 120;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const linea = cleanValue(row.production_line_name ?? row[1]);
                const resolvedShift = resolveShiftForRow(row);
                const turno = cleanValue(row.shift_name ?? row.shift ?? row.turno ?? (resolvedShift ? resolvedShift.name : 'Sin turno'));
                let operadores = '';
                if (Array.isArray(row.operator_names)) {
                    operadores = row.operator_names.join(' | ');
                } else if (row.operator_names) {
                    operadores = row.operator_names;
                } else {
                    operadores = 'Sin asignar';
                }
                const operadoresClean = cleanValue(operadores);
                const oeeRaw = row.oee ?? row[7];
                const oee = cleanValue(oeeRaw !== null && oeeRaw !== undefined ? (typeof oeeRaw === 'number' ? oeeRaw.toFixed(2) : safeValue(oeeRaw, '0')) : '0');
                const durSeconds = durationToSeconds(row.on_time ?? row[10]) || 0;
                const lentoSeconds = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const ganadoSeconds = durationToSeconds(row.fast_time ?? row[16]) || 0;

                csv += `${linea},${turno},${operadoresClean},${oee},${cleanValue(String(durSeconds))},${cleanValue(formatTime(durSeconds))},${cleanValue(String(lentoSeconds))},${cleanValue(formatTime(lentoSeconds))},${cleanValue(String(ganadoSeconds))},${cleanValue(formatTime(ganadoSeconds))}\n`;
                count++;
            });

            const note = count >= maxRows ? `Mostrando primeras ${maxRows} registros por turno` : `Total analizado: ${count} registros`;
            return { metrics, csv, type: 'Variaciones por Turno/Operador', note };
        }

        // Rentabilidad por Turno
        function collectShiftProfitabilityData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Rentabilidad por Turno' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            const groups = {};
            table.rows({search: 'applied'}).data().each(function(row) {
                const resolvedShift = resolveShiftForRow(row);
                const shiftId = (row.shift_id ?? (resolvedShift && resolvedShift.id)) || 'N/A';
                const shiftName = cleanValue(row.shift_name ?? row.shift ?? row.turno ?? (resolvedShift ? resolvedShift.name : 'Sin turno'));
                const shiftStart = cleanValue((resolvedShift && resolvedShift.start) || '');
                const shiftEnd = cleanValue((resolvedShift && resolvedShift.end) || '');
                const linea = cleanValue(row.production_line_name ?? row[1]);
                const lineId = row.production_line_id || 'N/A';
                const key = `${lineId}|${shiftId}|${shiftName}`;

                if (!groups[key]) {
                    groups[key] = {
                        linea, shiftId, shiftName, shiftStart, shiftEnd,
                        orders: 0,
                        oeeSum: 0, oeeCount: 0,
                        durSum: 0,
                        slowSum: 0, stopsSum: 0, downSum: 0, prepairSum: 0,
                        kgSum: 0, numSum: 0
                    };
                }
                const g = groups[key];
                g.orders++;

                const oeeRaw = row.oee ?? row[7];
                const oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                g.oeeSum += oeeNum > 1 ? oeeNum : (oeeNum * 100);
                g.oeeCount++;

                const dur = durationToSeconds(row.on_time ?? row[10]) || 0;
                const lento = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const stops = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const falta = durationToSeconds(row.down_time ?? row[14]) || 0;
                const prepair = durationToSeconds(row.prepair_time ?? row[11]) || 0;
                g.durSum += dur;
                g.slowSum += lento;
                g.stopsSum += stops;
                g.downSum += falta;
                g.prepairSum += prepair;

                const kg = parseFloat(row.weights_0_shiftKg) || 0;
                const num = parseInt(row.weights_0_shiftNumber) || 0;
                g.kgSum += kg;
                g.numSum += num;
            });

            let csv = 'Linea,Turno_Id,Turno,Inicio_Turno,Fin_Turno,Ordenes,OEE_Promedio,Duracion_Total_Segundos,Duracion_Total_Formato,Improductivo_Segundos,Improductivo_Formato,Neto_Segundos,Neto_Formato,Lento_Segundos,Paradas_Segundos,Falta_Material_Segundos,Preparacion_Segundos,Kg_Turno_Main,Cajas_Turno_Main\n';
            Object.values(groups).forEach(g => {
                const improd = g.slowSum + g.stopsSum + g.downSum + g.prepairSum;
                const neto = Math.max(g.durSum - improd, 0);
                const oeeAvg = g.oeeCount > 0 ? g.oeeSum / g.oeeCount : 0;
                csv += [
                    cleanValue(g.linea),
                    cleanValue(String(g.shiftId)),
                    cleanValue(g.shiftName),
                    cleanValue(g.shiftStart),
                    cleanValue(g.shiftEnd),
                    cleanValue(String(g.orders)),
                    cleanValue(oeeAvg.toFixed(2)),
                    cleanValue(String(g.durSum)),
                    cleanValue(formatTime(g.durSum)),
                    cleanValue(String(improd)),
                    cleanValue(formatTime(improd)),
                    cleanValue(String(neto)),
                    cleanValue(formatTime(neto)),
                    cleanValue(String(g.slowSum)),
                    cleanValue(String(g.stopsSum)),
                    cleanValue(String(g.downSum)),
                    cleanValue(String(g.prepairSum)),
                    cleanValue(String(g.kgSum)),
                    cleanValue(String(g.numSum))
                ].join(',') + '\n';
            });

            const note = `Total grupos turno: ${Object.keys(groups).length}`;
            return { metrics, csv, type: 'Rentabilidad por Turno', note };
        }

        // Consumo de Tiempo Improductivo
        function collectIdleTimeData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Tiempo improductivo' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                totalSlowTime: $('#totalSlowTime').text() || '00:00:00',
                totalStopsTime: $('#totalProductionStopsTime').text() || '00:00:00',
                totalDownTime: $('#totalDownTime').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            let csv = 'Linea,OEE_Porcentaje,Tiempo_Lento_Segundos,Tiempo_Lento_Formato,Paradas_Segundos,Paradas_Formato,Falta_Material_Segundos,Falta_Material_Formato,Tiempo_Neto_Produccion_Segundos,Tiempo_Neto_Produccion_Formato\n';
            let count = 0;
            const maxRows = 120;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const linea = cleanValue(row.production_line_name ?? row[1]);
                const oeeRaw = row.oee ?? row[7];
                const oee = cleanValue(oeeRaw !== null && oeeRaw !== undefined ? (typeof oeeRaw === 'number' ? oeeRaw.toFixed(2) : safeValue(oeeRaw, '0')) : '0');
                const lentoSeconds = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const paradasSeconds = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSeconds = durationToSeconds(row.down_time ?? row[14]) || 0;
                const durSeconds = durationToSeconds(row.on_time ?? row[10]) || 0;
                const netoSeconds = Math.max(durSeconds - paradasSeconds - faltaSeconds - lentoSeconds, 0);

                csv += `${linea},${oee},${cleanValue(String(lentoSeconds))},${cleanValue(formatTime(lentoSeconds))},${cleanValue(String(paradasSeconds))},${cleanValue(formatTime(paradasSeconds))},${cleanValue(String(faltaSeconds))},${cleanValue(formatTime(faltaSeconds))},${cleanValue(String(netoSeconds))},${cleanValue(formatTime(netoSeconds))}\n`;
                count++;
            });

            const note = count >= maxRows ? `Mostrando primeras ${maxRows} líneas` : `Total analizado: ${count} líneas`;
            return { metrics, csv, type: 'Tiempo improductivo', note };
        }

        // Análisis Total extendido
        function collectFullAnalysisData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Análisis Total', note: 'Sin datos' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                totalDuration: $('#totalDuration').text() || '00:00:00',
                totalDifference: $('#totalTheoretical').text() || '00:00:00',
                totalPrepTime: $('#totalPrepairTime').text() || '00:00:00',
                totalSlowTime: $('#totalSlowTime').text() || '00:00:00',
                totalStopsTime: $('#totalProductionStopsTime').text() || '00:00:00',
                totalDownTime: $('#totalDownTime').text() || '00:00:00'
            };

            let csv = 'Linea,Orden,Empleados,Fecha_Inicio_ISO,Fecha_Fin_ISO,OEE_Porcentaje,Duracion_Segundos,Duracion_Formato,Diferencia_Teorica_Segundos,Diferencia_Teorica_Formato,Preparacion_Segundos,Preparacion_Formato,Lento_Segundos,Lento_Formato,Paradas_Segundos,Paradas_Formato,Falta_Material_Segundos,Falta_Material_Formato\n';
            let count = 0;
            const maxRows = 150;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const linea = cleanValue(row.production_line_name ?? row[1]);
                const orden = cleanValue(row.order_id ?? row[2]);
                let empleadosRaw = '';
                if (Array.isArray(row.operator_names)) {
                    empleadosRaw = row.operator_names.join(' | ');
                } else if (row.operator_names) {
                    empleadosRaw = row.operator_names;
                }
                const empleados = cleanValue(empleadosRaw);
                const startIso = cleanValue(normalizeDateTime(row.created_at ?? row[8]));
                const endIso = cleanValue(normalizeDateTime(row.updated_at ?? row[9]));

                const oeeRaw = row.oee ?? row[7];
                const oee = cleanValue(oeeRaw !== null && oeeRaw !== undefined ? (typeof oeeRaw === 'number' ? oeeRaw.toFixed(2) : safeValue(oeeRaw, '0')) : '0');

                const durSeconds = durationToSeconds(row.on_time ?? row[10]) || 0;
                const fastSeconds = durationToSeconds(row.fast_time ?? row[16]) || 0;
                const outSeconds = durationToSeconds(row.out_time ?? row[17]) || 0;
                const diffSeconds = outSeconds - fastSeconds;
                const diffFormatted = diffSeconds === 0 ? '00:00:00' : `${diffSeconds > 0 ? '+' : '-'}${formatTime(Math.abs(diffSeconds))}`;

                const prepSeconds = durationToSeconds(row.prepair_time ?? row[11]) || 0;
                const lentoSeconds = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const paradasSeconds = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSeconds = durationToSeconds(row.down_time ?? row[14]) || 0;

                csv += `${linea},${orden},${empleados},${startIso},${endIso},${oee},${cleanValue(String(durSeconds))},${cleanValue(formatTime(durSeconds))},${cleanValue(String(diffSeconds))},${cleanValue(diffFormatted)},${cleanValue(String(prepSeconds))},${cleanValue(formatTime(prepSeconds))},${cleanValue(String(lentoSeconds))},${cleanValue(formatTime(lentoSeconds))},${cleanValue(String(paradasSeconds))},${cleanValue(formatTime(paradasSeconds))},${cleanValue(String(faltaSeconds))},${cleanValue(formatTime(faltaSeconds))}\n`;
                count++;
            });

            const pageInfo = (typeof table.page?.info === 'function') ? table.page.info() : null;
            const note = count >= maxRows
                ? `Mostrando primeras ${maxRows} de ${pageInfo ? pageInfo.recordsDisplay : count} órdenes`
                : `Total analizado: ${count} órdenes`;

            return { metrics, csv, type: 'Análisis Total', note };
        }

        async function startAiTask(fullPrompt, userPromptForDisplay) {
            try {
                console.log('[AI] Iniciando análisis:', userPromptForDisplay);
                console.log('[AI] Prompt length:', fullPrompt.length, 'caracteres');
                
                // Mostrar modal de procesamiento
                $('#aiProcessingTitle').text(userPromptForDisplay);
                $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando solicitud a IA...');
                const processingModal = new bootstrap.Modal(document.getElementById('aiProcessingModal'));
                processingModal.show();
                
                const fd = new FormData();
                fd.append('prompt', fullPrompt);
                fd.append('agent', 'data_analysis');

                const resp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${AI_TOKEN}` },
                    body: fd
                });
                if (!resp.ok) {
                    const t = await resp.text();
                    throw new Error(`AI create failed ${resp.status}: ${t}`);
                }
                const created = await resp.json();
                const taskId = (created && (created.id || created.task_id || created.taskId)) || created;
                if (!taskId) throw new Error('No task id');

                console.log('[AI] Tarea creada con ID:', taskId);
                console.log('[AI] Iniciando polling cada 5 segundos...');
                
                // Actualizar estado
                $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... Esperando respuesta...');

                let done = false; let last; let pollCount = 0;
                while (!done) {
                    pollCount++;
                    console.log(`[AI] Polling #${pollCount} - Esperando 5 segundos...`);
                    $('#aiProcessingStatus').html(`<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... (${pollCount * 5}s)`);
                    await new Promise(r => setTimeout(r, 5000));
                    
                    console.log(`[AI] Polling #${pollCount} - Verificando estado de la tarea...`);
                    const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                        headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                    });
                    
                    if (pollResp.status === 404) {
                        console.log('[AI] Error: Tarea no encontrada (404)');
                        try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {}
                        return;
                    }
                    if (!pollResp.ok) {
                        console.log('[AI] Error en polling:', pollResp.status, pollResp.statusText);
                        throw new Error(`poll failed: ${pollResp.status}`);
                    }
                    
                    last = await pollResp.json();
                    console.log(`[AI] Polling #${pollCount} - Respuesta recibida:`, last);
                    
                    const task = last && last.task ? last.task : null;
                    if (!task) {
                        console.log(`[AI] Polling #${pollCount} - No hay objeto task, continuando...`);
                        continue;
                    }
                    
                    console.log(`[AI] Polling #${pollCount} - Estado de la tarea:`, {
                        hasResponse: task.response != null,
                        hasError: task.error != null,
                        error: task.error
                    });
                    
                    if (task.response == null) {
                        if (task.error && /processing/i.test(task.error)) { 
                            console.log(`[AI] Polling #${pollCount} - Tarea aún procesando...`);
                            continue; 
                        }
                        if (task.error == null) { 
                            console.log(`[AI] Polling #${pollCount} - Sin respuesta ni error, continuando...`);
                            continue; 
                        }
                    }
                    if (task.error && !/processing/i.test(task.error)) { 
                        console.log('[AI] Error en la tarea:', task.error);
                        alert(task.error); 
                        return; 
                    }
                    if (task.response != null) { 
                        console.log('[AI] ¡Respuesta recibida! Finalizando polling...');
                        done = true; 
                    }
                }

                // Cerrar modal de procesamiento
                bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal')).hide();

                // Mostrar resultado
                $('#aiResultPrompt').text(userPromptForDisplay);
                const content = (last && last.task && last.task.response != null) ? last.task.response : last;

                let rawText;
                try {
                    rawText = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
                } catch {
                    rawText = String(content);
                }

                // Establecer metadatos del análisis
                const now = new Date();
                const timestamp = now.toLocaleString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                $('#aiResultTimestamp').text(timestamp);

                // Calcular estadísticas del texto
                const wordCount = (rawText || '').trim().split(/\s+/).filter(w => w.length > 0).length;
                const lineCount = (rawText || '').split('\n').length;
                const charCount = (rawText || '').length;
                $('#aiResultStats').text(`${wordCount} palabras, ${lineCount} líneas, ${charCount} caracteres`);

                // Establecer texto plano
                $('#aiResultText').text(rawText || '');

                // Convertir Markdown a HTML con marked.js
                const htmlTarget = $('#aiResultHtml');
                if (window.marked && window.DOMPurify) {
                    try {
                        console.log('[AI] Parseando Markdown con marked.js...');

                        // Convertir Markdown a HTML
                        let htmlContent = marked.parse(rawText || '');
                        console.log('[AI] Markdown parseado correctamente');

                        // Agregar clases de Bootstrap a las tablas
                        htmlContent = htmlContent.replace(/<table>/g, '<table class="table table-striped table-bordered table-hover">');

                        // Sanitizar el HTML con DOMPurify
                        const sanitized = DOMPurify.sanitize(htmlContent, {
                            ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'style', 'src', 'alt', 'title', 'colspan', 'rowspan'],
                            ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                                          'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
                                          'a', 'code', 'pre', 'blockquote', 'hr', 'span', 'div']
                        });

                        htmlTarget.html(sanitized);
                        console.log('[AI] HTML sanitizado e inyectado en el DOM');
                    } catch (err) {
                        console.error('[AI] Error al parsear Markdown:', err);
                        htmlTarget.html('<p class="text-danger">Error al procesar el contenido Markdown.</p>');
                    }
                } else {
                    console.warn('[AI] marked.js o DOMPurify no disponible, mostrando texto plano');
                    htmlTarget.text(rawText || '');
                }

                // Mostrar la tab de "Vista Formateada" por defecto
                const renderedTabTrigger = document.getElementById('ai-tab-rendered');
                if (renderedTabTrigger && bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(renderedTabTrigger).show();
                }

                // Inicializar funcionalidades del modal (copiar, descargar, imprimir, etc.)
                initAIResultModalFeatures(rawText, userPromptForDisplay);

                // Mostrar modal
                const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
                resultModal.show();
            } catch (err) {
                console.error('[AI] Unexpected error:', err);
                // Cerrar modal de procesamiento si está abierto
                const procModal = bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal'));
                if (procModal) procModal.hide();
                alert('{{ __('Error al procesar solicitud de IA') }}');
            }
        }

        /**
         * Inicializa las funcionalidades interactivas del modal de resultados IA
         * @param {string} rawText - Texto sin procesar del análisis
         * @param {string} analysisType - Tipo de análisis realizado
         */
        function initAIResultModalFeatures(rawText, analysisType) {
            console.log('[AI Modal] Inicializando funcionalidades interactivas...');

            // Estado de tamaño de fuente (100% por defecto)
            let currentFontSize = 100;

            // ===== 1. COPIAR AL PORTAPAPELES =====
            $('#btnCopyResult').off('click').on('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(rawText).then(() => {
                        console.log('[AI Modal] Texto copiado al portapapeles');
                        showToast('✓ Copiado al portapapeles', 'success');
                    }).catch(err => {
                        console.error('[AI Modal] Error al copiar:', err);
                        showToast('✗ Error al copiar', 'danger');
                    });
                } else {
                    // Fallback para navegadores antiguos
                    const textarea = document.createElement('textarea');
                    textarea.value = rawText;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        showToast('✓ Copiado al portapapeles', 'success');
                    } catch (err) {
                        showToast('✗ Error al copiar', 'danger');
                    }
                    document.body.removeChild(textarea);
                }
            });

            // ===== 2. DESCARGAR ARCHIVO .MD =====
            $('#btnDownloadResult').off('click').on('click', function() {
                try {
                    const timestamp = new Date().toISOString().replace(/[:]/g, '-').split('.')[0];
                    const filename = `analisis-ia-${timestamp}.md`;

                    const blob = new Blob([rawText], { type: 'text/markdown;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    console.log('[AI Modal] Archivo descargado:', filename);
                    showToast('✓ Archivo descargado', 'success');
                } catch (err) {
                    console.error('[AI Modal] Error al descargar:', err);
                    showToast('✗ Error al descargar', 'danger');
                }
            });

            // ===== 3. IMPRIMIR / PDF =====
            $('#btnPrintResult').off('click').on('click', function() {
                try {
                    const printWindow = window.open('', '_blank');
                    const htmlContent = $('#aiResultHtml').html();

                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <title>Análisis IA - ${analysisType}</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    line-height: 1.6;
                                    padding: 20px;
                                    max-width: 1200px;
                                    margin: 0 auto;
                                }
                                h1, h2, h3, h4, h5, h6 {
                                    margin-top: 1.5rem;
                                    margin-bottom: 0.75rem;
                                    color: #212529;
                                }
                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin-bottom: 1.5rem;
                                    font-size: 0.9rem;
                                }
                                table thead th {
                                    background-color: #0d6efd;
                                    color: white;
                                    padding: 0.75rem;
                                    border: 1px solid #0d6efd;
                                    text-align: left;
                                }
                                table tbody td {
                                    padding: 0.65rem;
                                    border: 1px solid #dee2e6;
                                }
                                table tbody tr:nth-child(odd) {
                                    background-color: #f8f9fa;
                                }
                                pre {
                                    background-color: #f8f9fa;
                                    padding: 1rem;
                                    border-radius: 4px;
                                    overflow-x: auto;
                                }
                                code {
                                    background-color: #f8f9fa;
                                    padding: 0.2rem 0.4rem;
                                    border-radius: 3px;
                                    font-family: 'Courier New', monospace;
                                }
                                @media print {
                                    body { padding: 10px; }
                                    table { page-break-inside: auto; }
                                    tr { page-break-inside: avoid; page-break-after: auto; }
                                }
                            </style>
                        </head>
                        <body>
                            <h1>Análisis IA: ${analysisType}</h1>
                            <p><strong>Generado:</strong> ${new Date().toLocaleString('es-ES')}</p>
                            <hr>
                            ${htmlContent}
                        </body>
                        </html>
                    `);

                    printWindow.document.close();
                    printWindow.focus();

                    // Esperar a que se cargue el contenido antes de imprimir
                    setTimeout(() => {
                        printWindow.print();
                    }, 250);

                    console.log('[AI Modal] Ventana de impresión abierta');
                } catch (err) {
                    console.error('[AI Modal] Error al imprimir:', err);
                    showToast('✗ Error al imprimir', 'danger');
                }
            });

            // ===== 4. PANTALLA COMPLETA =====
            $('#btnFullscreen').off('click').on('click', function() {
                const dialog = $('#aiResultModalDialog');
                const icon = $(this).find('i');

                if (dialog.hasClass('modal-fullscreen-custom')) {
                    dialog.removeClass('modal-fullscreen-custom');
                    icon.removeClass('fa-compress').addClass('fa-expand');
                    $(this).attr('title', 'Pantalla completa');
                    console.log('[AI Modal] Saliendo de pantalla completa');
                } else {
                    dialog.addClass('modal-fullscreen-custom');
                    icon.removeClass('fa-expand').addClass('fa-compress');
                    $(this).attr('title', 'Salir de pantalla completa');
                    console.log('[AI Modal] Entrando en pantalla completa');
                }
            });

            // ===== 5. CONTROL DE TAMAÑO DE FUENTE =====
            function updateFontSize() {
                $('.ai-result-content').css('font-size', currentFontSize + '%');
                console.log('[AI Modal] Tamaño de fuente:', currentFontSize + '%');
            }

            $('#btnFontDecrease').off('click').on('click', function() {
                if (currentFontSize > 70) {
                    currentFontSize -= 10;
                    updateFontSize();
                    showToast(`Tamaño: ${currentFontSize}%`, 'info');
                }
            });

            $('#btnFontReset').off('click').on('click', function() {
                currentFontSize = 100;
                updateFontSize();
                showToast('Tamaño: 100% (normal)', 'info');
            });

            $('#btnFontIncrease').off('click').on('click', function() {
                if (currentFontSize < 150) {
                    currentFontSize += 10;
                    updateFontSize();
                    showToast(`Tamaño: ${currentFontSize}%`, 'info');
                }
            });

            // ===== 6. BARRA DE PROGRESO DE SCROLL Y BOTÓN "VOLVER ARRIBA" =====
            const scrollContainers = $('.ai-result-content, #aiResultText');
            const btnScrollTop = $('#btnScrollTop');

            scrollContainers.off('scroll').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight - $(this).outerHeight();
                const scrollPercent = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;

                $('#aiScrollProgress').css('width', scrollPercent + '%');

                // Mostrar/ocultar botón "Volver arriba"
                if (scrollTop > 300) {
                    btnScrollTop.addClass('show');
                } else {
                    btnScrollTop.removeClass('show');
                }
            });

            // Click en botón "Volver arriba"
            btnScrollTop.off('click').on('click', function() {
                scrollContainers.animate({ scrollTop: 0 }, 400);
                console.log('[AI Modal] Volviendo arriba');
            });

            // ===== 7. LIMPIEZA AL CERRAR EL MODAL =====
            $('#aiResultModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                console.log('[AI Modal] Modal cerrado, limpiando event handlers...');

                // Resetear tamaño de fuente
                currentFontSize = 100;
                $('.ai-result-content').css('font-size', '100%');

                // Quitar clase fullscreen si está activa
                $('#aiResultModalDialog').removeClass('modal-fullscreen-custom');
                $('#btnFullscreen').find('i').removeClass('fa-compress').addClass('fa-expand');

                // Ocultar botón "Volver arriba"
                btnScrollTop.removeClass('show');

                // Reset scroll progress
                $('#aiScrollProgress').css('width', '0%');

                // Limpiar event handlers
                $('#btnCopyResult, #btnDownloadResult, #btnPrintResult, #btnFullscreen').off('click');
                $('#btnFontDecrease, #btnFontReset, #btnFontIncrease').off('click');
                scrollContainers.off('scroll');
                btnScrollTop.off('click');
            });

            console.log('[AI Modal] Funcionalidades interactivas inicializadas correctamente');
        }

        /**
         * Muestra un toast de notificación temporal
         * @param {string} message - Mensaje a mostrar
         * @param {string} type - Tipo de toast: success, danger, info
         */
        function showToast(message, type = 'success') {
            const bgColor = type === 'success' ? '#198754' : type === 'danger' ? '#dc3545' : '#0dcaf0';
            const toast = $(`
                <div class="copy-toast" style="background: ${bgColor};">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `);

            $('body').append(toast);

            // Auto-remover después de 3 segundos
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        $(function(){
            // Prompts ultra-simplificados para agentes especialistas
            const analysisPrompts = {
                'oee-general': {
                    title: 'Análisis General de OEE',
                    prompt: `Eres un experto en manufactura lean y OEE (Overall Equipment Effectiveness). Analiza el rendimiento general de todas las líneas de producción.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas (separadas por comas):
- Linea: Nombre/ID de la línea de producción
- OEE_Porcentaje: OEE calculado en % (número decimal o con símbolo %)
- Duracion_Segundos: Duración total del periodo en segundos (número entero)
- Duracion_Formato: Duración en formato HH:MM:SS
- Disponibilidad_Porcentaje: % de disponibilidad operativa
- Rendimiento_Porcentaje: % de rendimiento vs velocidad teórica
- Calidad_Porcentaje: % de calidad (unidades buenas)

IMPORTANTE: Procesa TODAS las filas del CSV para obtener una visión completa.

ANÁLISIS REQUERIDO:
1. **Ranking de líneas**: Top 5 mejores y Bottom 5 peores por OEE. Para cada una: Linea, OEE_Porcentaje, y desviación vs media general.

2. **Estadísticas globales**:
   - OEE promedio, mediana, desviación estándar
   - % de líneas con OEE >85% (clase mundial), 70-85% (bueno), <70% (necesita mejora)

3. **Análisis de componentes OEE**:
   - Cuál componente (Disponibilidad, Rendimiento, Calidad) es el más débil en promedio
   - Líneas con disponibilidad baja (<80%)
   - Líneas con rendimiento bajo (<85%)
   - Líneas con problemas de calidad (<95%)

4. **Tendencia general**: Si los datos lo permiten, detecta si el OEE está mejorando, empeorando o estable.

5. **Recomendaciones**: 3 acciones prioritarias con impacto cuantificado (ej: "Mejorar disponibilidad en línea X puede aumentar OEE +8%").

FORMATO DE SALIDA:
Estructura en secciones con tablas cuando sea apropiado. Usa números y porcentajes concretos.`
                },
                'stops': {
                    title: 'Análisis de Paradas',
                    prompt: `Eres un especialista en análisis de downtime y optimización de flujo de producción. Identifica las causas principales de paradas y su impacto.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea: Nombre/ID de la línea
- Paradas_Cantidad: Número de paradas ocurridas (número entero)
- Paradas_Segundos: Tiempo total de paradas en segundos
- Paradas_Formato: Tiempo en formato HH:MM:SS
- Falta_Material_Segundos: Tiempo de paradas por falta de material
- Falta_Material_Formato: En formato HH:MM:SS
- OEE_Porcentaje: OEE resultante

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Top 5 líneas críticas**: Líneas con más tiempo de paradas. Para cada una: Linea, Paradas_Cantidad, Paradas_Formato, % del tiempo total.

2. **Análisis de frecuencia vs duración**:
   - Líneas con muchas paradas cortas (alta cantidad, baja duración promedio)
   - Líneas con pocas paradas largas (baja cantidad, alta duración promedio)
   - MTBF (Mean Time Between Failures) estimado

3. **Impacto de falta de material**:
   - % del total de paradas atribuible a falta de material
   - Líneas donde falta material >50% de paradas
   - Impacto en OEE por falta de material

4. **Correlación paradas-OEE**:
   - ¿Las líneas con más paradas tienen peor OEE?
   - Identificar líneas que recuperan bien a pesar de paradas

5. **Acciones correctivas**: 3 medidas priorizadas por ROI estimado (ej: "Mejorar suministro de material en línea X reduciría paradas en 30%").

FORMATO DE SALIDA:
Usa tablas comparativas. Cuantifica todo en horas/minutos y porcentajes.`
                },
                'performance': {
                    title: 'Análisis de Rendimiento',
                    prompt: `Eres un ingeniero de métodos y tiempos especializado en optimización de velocidad de líneas. Analiza las desviaciones de rendimiento.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea: Nombre/ID de la línea
- UPM_Real: Unidades por minuto reales (número decimal)
- UPM_Teorico: Unidades por minuto teóricas/diseño (número decimal)
- Rendimiento_Porcentaje: % de eficiencia de velocidad (UPM_Real/UPM_Teorico * 100)
- Tiempo_Lento_Segundos: Tiempo operando bajo velocidad óptima
- Tiempo_Lento_Formato: En formato HH:MM:SS
- OEE_Porcentaje: OEE resultante

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Desviaciones de rendimiento**: Top 5 líneas con mayor gap entre UPM_Real y UPM_Teorico. Para cada una: Linea, UPM_Real, UPM_Teorico, Gap absoluto, Gap %.

2. **Distribución de rendimientos**:
   - % de líneas con rendimiento >90% (óptimo)
   - % de líneas entre 80-90% (aceptable)
   - % de líneas <80% (crítico)

3. **Análisis de tiempo lento**:
   - Líneas con mayor Tiempo_Lento_Segundos
   - Correlación entre tiempo lento y rendimiento
   - Impacto estimado en producción (unidades perdidas)

4. **Potencial de mejora**:
   - Si todas las líneas operaran a UPM_Teorico, cuántas unidades adicionales/día
   - Líneas con mayor potencial de mejora (alto gap + alto volumen)

5. **Mejoras de velocidad**: 3 medidas priorizadas (ej: "Optimizar proceso en línea X puede aumentar UPM de 45 a 52, +15% rendimiento").

FORMATO DE SALIDA:
Usa tablas con comparaciones UPM Real vs Teórico. Cuantifica en unidades/minuto y unidades perdidas/día.`
                },
                'operators': {
                    title: 'Análisis de Operadores',
                    prompt: `Eres un especialista en gestión de talento y productividad laboral. Analiza el impacto de operadores en el rendimiento de las líneas.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea: Nombre/ID de la línea
- Operadores: Lista de operadores asignados (puede ser nombres o códigos)
- Turno: Turno de trabajo (ej: Mañana, Tarde, Noche)
- Tiempo_Ganado_Segundos: Tiempo ganado sobre estándar (positivo=adelanto)
- Tiempo_Ganado_Formato: En formato HH:MM:SS o +/-HH:MM:SS
- Tiempo_Perdido_Segundos: Tiempo perdido vs estándar (positivo=retraso)
- Tiempo_Perdido_Formato: En formato HH:MM:SS
- OEE_Porcentaje: OEE logrado

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Rendimiento por equipo**: Top 5 equipos con mejor balance Tiempo_Ganado vs Tiempo_Perdido. Incluye: Linea, Operadores, Turno, Balance neto.

2. **Patrones por turno**:
   - Turno con mejor desempeño promedio
   - Turno con más tiempo perdido
   - Diferencia entre mejor y peor turno

3. **Análisis de consistencia**:
   - Operadores/equipos con alta variabilidad (unas veces bien, otras mal)
   - Operadores/equipos consistentemente buenos
   - Operadores/equipos que necesitan soporte

4. **Impacto en OEE**:
   - Correlación entre tiempo ganado/perdido y OEE
   - Líneas donde el factor humano es crítico (alta variación con diferentes operadores)

5. **Recomendaciones de gestión**: 3 acciones priorizadas (ej: "Capacitar operador Y en línea Z puede reducir tiempo perdido en 40%", "Replicar mejores prácticas de turno X").

FORMATO DE SALIDA:
Usa tablas por turno y operador. Respeta la privacidad pero sé específico en identificar patrones accionables.`
                },
                'comparison': {
                    title: 'Comparativa Alto/Bajo',
                    prompt: `Eres un analista de benchmarking interno. Compara las líneas de mejor desempeño vs las de peor desempeño para identificar factores diferenciadores.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Tipo: "TOP" para las 10 mejores líneas, "BOTTOM" para las 10 peores
- Linea: Nombre/ID de la línea
- OEE_Porcentaje: OEE resultante
- Disponibilidad_Porcentaje, Rendimiento_Porcentaje, Calidad_Porcentaje: Componentes del OEE
- Duracion_Segundos, Duracion_Formato: Tiempo total analizado
- Paradas_Segundos, Tiempo_Lento_Segundos: Métricas de ineficiencia

IMPORTANTE: Procesa las 20 filas completas (10 TOP + 10 BOTTOM).

ANÁLISIS REQUERIDO:
1. **Métricas del grupo TOP (10 mejores)**:
   - OEE promedio y rango (min-max)
   - Disponibilidad, Rendimiento, Calidad promedio
   - Tiempo de paradas promedio
   - Tiempo lento promedio

2. **Métricas del grupo BOTTOM (10 peores)**:
   - OEE promedio y rango
   - Disponibilidad, Rendimiento, Calidad promedio
   - Tiempo de paradas promedio
   - Tiempo lento promedio

3. **Diferencias cuantificadas**:
   - Gap en OEE: TOP vs BOTTOM (en puntos porcentuales)
   - Gap en cada componente (Disponibilidad, Rendimiento, Calidad)
   - Ratio: OEE BOTTOM / OEE TOP (ej: "1.5x peor")

4. **Factores diferenciadores (3 clave)**:
   - ¿Qué hace diferente a las TOP? (ej: menos paradas, mejor velocidad, etc.)
   - ¿Cuál es el problema principal de las BOTTOM?
   - ¿Hay patrones comunes? (ej: todas las TOP tienen bajo tiempo lento)

5. **Plan de acción**: 3 pasos concretos para llevar líneas BOTTOM al nivel TOP, con impacto estimado en puntos de OEE.

FORMATO DE SALIDA:
Usa formato comparativo (Tabla TOP vs BOTTOM). Incluye nombres de líneas específicas.`
                },
                'availability-performance': {
                    title: 'Disponibilidad vs Rendimiento',
                    prompt: `Eres un analista de TPM (Total Productive Maintenance). Analiza la relación entre disponibilidad operativa y rendimiento para identificar si el problema es tiempo de operación o eficiencia durante la operación.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea: Nombre/ID de la línea
- OEE_Porcentaje: OEE resultante
- Disponibilidad_Porcentaje: % de tiempo disponible para producir
- Rendimiento_Porcentaje: % de eficiencia durante operación
- Duracion_Segundos, Duracion_Formato: Tiempo total del periodo
- Tiempo_Disponible_Segundos, Tiempo_Disponible_Formato: Tiempo efectivamente disponible
- Tiempo_Incidencias_Segundos, Tiempo_Incidencias_Formato: Tiempo perdido en incidencias

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Clasificación de líneas**:
   - Alta disponibilidad (>85%) + Alto rendimiento (>85%) = Clase mundial
   - Alta disponibilidad + Bajo rendimiento = Problema de velocidad/eficiencia
   - Baja disponibilidad + Alto rendimiento = Problema de paradas/incidencias
   - Baja disponibilidad + Bajo rendimiento = Crítico, múltiples problemas

2. **Top 5 líneas por impacto de incidencias**:
   - Líneas donde Tiempo_Incidencias reduce más el Tiempo_Disponible
   - % de tiempo total perdido en incidencias
   - Impacto estimado en OEE

3. **Análisis de correlación**:
   - ¿Hay correlación entre disponibilidad y rendimiento?
   - Líneas donde mejorar disponibilidad tendría mayor impacto
   - Líneas donde mejorar rendimiento tendría mayor impacto

4. **Desbalances críticos**:
   - Líneas con alto rendimiento pero baja disponibilidad (desperdicio de capacidad)
   - Líneas con alta disponibilidad pero bajo rendimiento (operación ineficiente)

5. **Acciones priorizadas**: 3 medidas para equilibrar disponibilidad y rendimiento, con impacto estimado en puntos de OEE.

FORMATO DE SALIDA:
Usa matriz 2x2 (Disponibilidad vs Rendimiento) para clasificar líneas. Cuantifica todo en % y tiempos.`
                },
                'shift-variations': {
                    title: 'Variaciones por Turno/Operador',
                    prompt: `Eres un especialista en análisis de turnos y variabilidad operativa. Identifica diferencias de rendimiento entre turnos para encontrar mejores prácticas y áreas de mejora.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea, Turno, Operadores
- OEE_Porcentaje
- Duracion_Segundos, Duracion_Formato
- Tiempo_Lento_Segundos, Tiempo_Lento_Formato
- Tiempo_Ganado_Segundos, Tiempo_Ganado_Formato

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Ranking por turno**: OEE promedio por turno (Mañana, Tarde, Noche). Mejor vs peor turno con gap cuantificado.

2. **Top 5 combinaciones exitosas**: Línea + Turno + Operadores con mejor OEE y menor tiempo lento.

3. **Identificación de patrones**:
   - Turnos con consistentemente más tiempo ganado
   - Turnos con más tiempo lento
   - Líneas donde la variación entre turnos es mayor (alta dependencia del factor humano)

4. **Análisis de causas**:
   - ¿El tiempo lento es por fatiga (peor en turnos largos)?
   - ¿Hay efecto aprendizaje (mejora en turnos específicos)?
   - ¿Hay equipos específicos que elevan o bajan el promedio?

5. **Acciones de homogeneización**: 3 recomendaciones para reducir variabilidad entre turnos en al menos 20%.

FORMATO DE SALIDA:
Usa tablas por turno. Identifica mejores prácticas específicas para replicar.`
                },
                'idle-time': {
                    title: 'Consumo de Tiempo Improductivo',
                    prompt: `Eres un especialista en análisis de desperdicios y optimización lean. Evalúa cómo se distribuye el tiempo improductivo (muda) en las líneas.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea
- OEE_Porcentaje
- Tiempo_Lento_Segundos, Tiempo_Lento_Formato
- Paradas_Segundos, Paradas_Formato
- Falta_Material_Segundos, Falta_Material_Formato
- Tiempo_Neto_Produccion_Segundos, Tiempo_Neto_Produccion_Formato

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Top 5 líneas con mayor tiempo improductivo total**: Suma de Tiempo_Lento + Paradas + Falta_Material. Para cada una: Linea, Total improductivo, % del tiempo total.

2. **Análisis de composición**:
   Para cada línea, calcular % de cada tipo:
   - % Tiempo lento vs total improductivo
   - % Paradas vs total improductivo
   - % Falta material vs total improductivo

3. **Clasificación por causa dominante**:
   - Líneas donde tiempo lento >50% del improductivo (problema de velocidad)
   - Líneas donde paradas >50% del improductivo (problema de confiabilidad)
   - Líneas donde falta material >50% del improductivo (problema de suministro)

4. **Ratio productivo/improductivo**:
   - Calcular Tiempo_Neto_Produccion / Tiempo_Improductivo_Total para cada línea
   - Benchmark: mejor y peor ratio

5. **Iniciativas de recuperación**: 3 acciones priorizadas por impacto potencial en horas productivas recuperadas/día.

FORMATO DE SALIDA:
Usa gráficos de composición (%). Cuantifica oportunidad en horas/día recuperables.`
                },
                'shift-profitability': {
                    title: 'Rentabilidad por Turno',
                    prompt: `Eres un analista financiero de operaciones. Evalúa la rentabilidad y eficiencia operativa por turno para identificar los turnos más y menos rentables.

FORMATO DE DATOS:
Recibirás un CSV con las siguientes columnas:
- Linea
- Turno_Id, Turno, Inicio_Turno, Fin_Turno
- Ordenes, OEE_Promedio
- Duracion_Total_Segundos, Duracion_Total_Formato
- Improductivo_Segundos, Improductivo_Formato (suma lento+paradas+falta material+preparación)
- Neto_Segundos, Neto_Formato (Duracion_Total - Improductivo)
- Lento_Segundos, Paradas_Segundos, Falta_Material_Segundos, Preparacion_Segundos
- Kg_Turno_Main, Cajas_Turno_Main

IMPORTANTE: Procesa TODAS las filas del CSV.

ANÁLISIS REQUERIDO:
1. **Ranking de rentabilidad por turno**: Para cada línea, ordenar turnos por:
   - Mayor Neto_Segundos (tiempo productivo neto)
   - Mayor OEE_Promedio
   - Mayor output (Kg_Turno_Main, Cajas_Turno_Main)

2. **Análisis de tiempo improductivo**:
   - % de Improductivo_Segundos vs Duracion_Total por turno
   - Desglose por causa dominante en cada turno (Lento/Paradas/Falta Material/Preparacion)

3. **Eficiencia comparativa**:
   - Turno más eficiente: mayor output con menor tiempo improductivo
   - Turno menos eficiente: menor output con mayor tiempo improductivo
   - Gap de productividad en Kg/hora entre mejor y peor turno

4. **Patrones por línea**:
   - Líneas donde hay gran variación entre turnos (>30% diferencia en OEE)
   - Líneas estables entre turnos (<10% variación)

5. **Acciones para mejorar rentabilidad**: 3 medidas priorizadas por línea y turno, con impacto estimado en % de mejora de tiempo neto y output.

FORMATO DE SALIDA:
Usa tablas comparativas por turno. Cuantifica en horas netas, Kg/h, y % de utilización.`
                },
                'full': {
                    title: 'Análisis Total (CSV extendido)',
                    prompt: `Eres un director de operaciones. Genera un análisis ejecutivo integral de todas las líneas de producción, identificando tendencias globales, riesgos críticos y oportunidades estratégicas.

FORMATO DE DATOS:
Recibirás un CSV extendido con todas las columnas disponibles del sistema.

IMPORTANTE: Procesa TODAS las filas del CSV para obtener una visión holística.

ANÁLISIS REQUERIDO:
1. **Resumen Ejecutivo** (3-4 párrafos):
   - Estado general del OEE: promedio, tendencia, benchmark
   - Principal hallazgo crítico
   - Principal oportunidad de mejora cuantificada

2. **Métricas clave globales**:
   - OEE: media, mediana, P90, P95
   - Disponibilidad, Rendimiento, Calidad: promedios
   - Tiempo improductivo total: horas/día perdidas
   - Top 3 líneas por volumen y su OEE

3. **Identificación de 5 riesgos críticos**:
   Para cada uno:
   - Dónde ocurre (línea, turno)
   - Magnitud del problema
   - % de líneas o tiempo afectado
   - Impacto estimado en el OEE global

4. **Identificación de 5 oportunidades estratégicas**:
   - Quick wins (implementación <1 mes, impacto medio)
   - Iniciativas estratégicas (1-3 meses, alto impacto)
   - Para cada una: impacto esperado en puntos de OEE

5. **Plan de acción inmediato**: 3 acciones para implementar esta semana, con responsable sugerido y métrica de éxito.

FORMATO DE SALIDA:
Estructura tipo informe ejecutivo con secciones claras. Usa datos cuantificados y comparaciones. Prioriza insights accionables.`
                }
            };

            // Variable global para guardar el prompt y título actual
            let currentPromptData = null;
            
            // Click en opciones del dropdown
            $('.dropdown-item[data-analysis]').on('click', function(e) {
                e.preventDefault();
                const analysisType = $(this).data('analysis');
                const config = analysisPrompts[analysisType];
                
                if (!config) return;
                
                // Recolectar datos según el tipo de análisis
                let data;
                switch(analysisType) {
                    case 'oee-general':
                        data = collectOEEGeneralData();
                        break;
                    case 'stops':
                        data = collectStopsData();
                        break;
                    case 'performance':
                        data = collectPerformanceData();
                        break;
                    case 'operators':
                        data = collectOperatorsData();
                        break;
                    case 'comparison':
                        data = collectComparisonData();
                        break;
                    case 'availability-performance':
                        data = collectAvailabilityPerformanceData();
                        break;
                    case 'shift-variations':
                        data = collectShiftVariationsData();
                        break;
                    case 'shift-profitability':
                        data = collectShiftProfitabilityData();
                        break;
                    case 'idle-time':
                        data = collectIdleTimeData();
                        break;
                    case 'full':
                        data = collectFullAnalysisData();
                        break;
                    default:
                        return;
                }
                
                // Verificar si hay datos
                if (!data.csv || data.csv.trim() === '' || data.csv.split('\n').length <= 1) {
                    alert('No hay datos disponibles para analizar. Por favor, ejecuta primero una búsqueda.');
                    return;
                }
                
                // Construir prompt final
                let finalPrompt = `${config.prompt}\n\n=== PERIODO ===\n${data.metrics.dateRange}\n\n`;
                
                // Añadir métricas específicas
                finalPrompt += '=== MÉTRICAS ===\n';
                Object.keys(data.metrics).forEach(key => {
                    if (key !== 'dateRange') {
                        finalPrompt += `${key}: ${data.metrics[key]}\n`;
                    }
                });
                
                finalPrompt += `\n=== DATOS (CSV) ===\n${data.csv}`;
                
                console.log(`[AI] Análisis: ${config.title}`);
                console.log(`[AI] Tamaño prompt: ${finalPrompt.length} caracteres`);
                
                // Guardar prompt y título para enviarlo después
                currentPromptData = {
                    prompt: finalPrompt,
                    title: config.title
                };
                
                // Mostrar modal de edición
                $('#aiEditModalTitle').text(config.title);
                $('#aiPromptEdit').val(finalPrompt);
                const editModal = new bootstrap.Modal(document.getElementById('aiPromptEditModal'));
                editModal.show();
            });
            
            // Click en botón de enviar después de editar
            $('#btn-ai-send-edited').on('click', function() {
                if (!currentPromptData) return;
                
                // Obtener el prompt editado
                const editedPrompt = $('#aiPromptEdit').val().trim();
                
                if (!editedPrompt) {
                    alert('El prompt no puede estar vacío');
                    return;
                }
                
                // Deshabilitar botón y mostrar spinner
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Enviando...');
                
                // Cerrar modal de edición
                bootstrap.Modal.getInstance(document.getElementById('aiPromptEditModal')).hide();
                
                // Enviar a IA
                startAiTask(editedPrompt, currentPromptData.title).finally(() => {
                    // Restaurar botón
                    $btn.prop('disabled', false);
                    $btn.html('<i class="fas fa-paper-plane me-1"></i>Enviar a IA');
                });
            });
        });
    </script>

    <!-- AI Prompt Edit Modal -->
    <div class="modal fade" id="aiPromptEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i><span id="aiEditModalTitle">Editar Prompt</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-bold">Prompt a enviar (puedes editarlo):</label>
                    <textarea class="form-control font-monospace" id="aiPromptEdit" rows="15" style="font-size: 0.9rem;"></textarea>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>
                        Este prompt incluye las instrucciones, métricas y datos CSV. Puedes modificarlo antes de enviar.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancelar')</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send-edited">
                        <i class="fas fa-paper-plane me-1"></i>@lang('Enviar a IA')
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Processing Modal -->
    <div class="modal fade" id="aiProcessingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i><span id="aiProcessingTitle">Procesando...</span></h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" id="aiProcessingStatus">
                        <i class="fas fa-spinner fa-spin me-2"></i>Procesando solicitud...
                    </p>
                    <small class="text-muted d-block mt-2">
                        Esto puede tardar varios segundos. Por favor, espere...
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Result Modal (Mejorado) -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" id="aiResultModalDialog" style="max-width: 80%; width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-1">@lang('Resultado IA')</h5>
                        <small class="text-muted ai-metadata">
                            <i class="fas fa-clock me-1"></i><span id="aiResultTimestamp"></span>
                            <span class="mx-2">|</span>
                            <i class="fas fa-align-left me-1"></i><span id="aiResultStats"></span>
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body position-relative">
                    <!-- Barra de progreso de scroll -->
                    <div class="scroll-progress-bar" id="aiScrollProgress"></div>

                    <!-- Tipo de análisis -->
                    <p class="text-muted mb-3"><strong>@lang('Tipo de Análisis'):</strong> <span id="aiResultPrompt"></span></p>

                    <!-- Barra de herramientas -->
                    <div class="ai-toolbar">
                        <!-- Control de tamaño de fuente -->
                        <div class="btn-group btn-group-sm font-controls" role="group" aria-label="Controles de fuente">
                            <button type="button" class="btn btn-outline-secondary" id="btnFontDecrease" title="Reducir tamaño">
                                <i class="fas fa-minus"></i> A-
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFontReset" title="Tamaño normal">
                                A
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFontIncrease" title="Aumentar tamaño">
                                <i class="fas fa-plus"></i> A+
                            </button>
                        </div>

                        <!-- Botones de acción -->
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                            <button type="button" class="btn btn-outline-primary" id="btnCopyResult" title="Copiar al portapapeles">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <button type="button" class="btn btn-outline-success" id="btnDownloadResult" title="Descargar como archivo">
                                <i class="fas fa-download"></i> Descargar
                            </button>
                            <button type="button" class="btn btn-outline-info" id="btnPrintResult" title="Imprimir o guardar como PDF">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFullscreen" title="Pantalla completa">
                                <i class="fas fa-expand"></i> Pantalla completa
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="aiResultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ai-tab-rendered" data-bs-toggle="tab" data-bs-target="#aiResultRendered" type="button" role="tab" aria-controls="aiResultRendered" aria-selected="true">
                                <i class="fas fa-eye me-1"></i>Vista Formateada
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab-raw" data-bs-toggle="tab" data-bs-target="#aiResultRaw" type="button" role="tab" aria-controls="aiResultRaw" aria-selected="false">
                                <i class="fas fa-file-alt me-1"></i>Texto Plano
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de las tabs -->
                    <div class="tab-content" id="aiResultTabContent">
                        <!-- Tab: Vista Formateada (Markdown parseado) -->
                        <div class="tab-pane fade show active" id="aiResultRendered" role="tabpanel" aria-labelledby="ai-tab-rendered">
                            <div id="aiResultHtml" class="ai-result-content"></div>
                        </div>

                        <!-- Tab: Texto Plano -->
                        <div class="tab-pane fade" id="aiResultRaw" role="tabpanel" aria-labelledby="ai-tab-raw">
                            <pre id="aiResultText" class="bg-light p-3 rounded" style="white-space: pre-wrap; min-height: 200px; overflow: auto;"></pre>
                        </div>
                    </div>

                    <!-- Botón flotante "Volver arriba" -->
                    <button type="button" id="btnScrollTop" class="btn btn-primary" title="Volver arriba">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Librerías para parsing de Markdown y seguridad -->
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
    <script>
        // Configurar marked.js para mejor compatibilidad con Markdown
        if (window.marked) {
            marked.setOptions({
                breaks: true,        // Convertir saltos de línea en <br>
                gfm: true,          // GitHub Flavored Markdown
                headerIds: true,    // Generar IDs para encabezados
                mangle: false,      // No modificar emails
                sanitize: false     // No sanitizar (lo haremos con DOMPurify)
            });
        }
    </script>

    <script>
        const token = new URLSearchParams(window.location.search).get('token');
        console.log("Token obtenido:", token);

        // Función para formatear segundos a HH:MM:SS
        function formatTime(seconds) {
            if (seconds === null || seconds === undefined || isNaN(seconds) || seconds === 0) return '00:00:00';
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
                
                // Inicializar Select2 para líneas de producción
                modbusSelect.select2({
                    placeholder: "Seleccionar líneas",
                    allowClear: true
                });
                
                // Cargar operarios
                fetchOperators();
            } catch (error) {
                console.error("Error al cargar líneas de producción:", error);
            }
        }
        
        // Función para cargar los operarios disponibles
        async function fetchOperators() {
            try {
                console.log("Intentando obtener operarios con IDs internos...");
                const response = await fetch('/api/operators/internal');
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const operators = await response.json();
                console.log("Operarios con IDs internos recibidos:", operators);
                
                const operatorSelect = $('#operatorSelect');
                operatorSelect.empty();
                
                // Ordenar los operarios alfabéticamente por nombre
                if (Array.isArray(operators)) {
                    operators.sort((a, b) => a.name.localeCompare(b.name));
                    
                    operators.forEach(operator => {
                        operatorSelect.append(`<option value="${operator.id}">${operator.name}</option>`);
                    });
                } else {
                    console.error("El formato de datos de operarios no es válido:", operators);
                }
                
                // Inicializar Select2 para operarios
                operatorSelect.select2({
                    placeholder: "Seleccionar empleados",
                    allowClear: true
                });
            } catch (error) {
                console.error("Error al cargar operarios:", error);
            }
        }

        async function fetchOrderStats(lineTokens, startDate, endDate) {
            try {
                const tokensArray = Array.isArray(lineTokens) ? lineTokens : [lineTokens];
                const filteredTokens = tokensArray.filter(token => token && token.trim() !== '');
                const selectedOperators = $('#operatorSelect').val();
                
                // Determinar el modo de filtrado basado en la selección de líneas y operadores
                let filterMode = 'line_only'; // Por defecto, filtrar solo por línea
                
                if (selectedOperators && selectedOperators.length > 0) {
                    // Si hay operadores seleccionados, priorizar el filtrado por operador
                    filterMode = 'operator_only';
                }
                
                if (filteredTokens.length === 0) {
                    throw new Error('No hay tokens válidos seleccionados');
                }
                
                const tokenParam = filteredTokens.join(',');
                let url = `/api/order-stats-all?token=${tokenParam}&start_date=${startDate}&end_date=${endDate}`;
                
                // Añadir operadores seleccionados a la URL si hay alguno
                if (selectedOperators && selectedOperators.length > 0) {
                    const operatorParam = selectedOperators.join(',');
                    url += `&operators=${operatorParam}`;
                    
                    // Añadir el modo de filtrado a la URL
                    url += `&filter_mode=${filterMode}`;
                }
                
                // Añadir filtros de OEE si están activados
                const hideZeroOEE = $('#hideZeroOEE').is(':checked');
                const hide100OEE = $('#hide100OEE').is(':checked');
                
                if (hideZeroOEE) {
                    url += `&hide_zero_oee=1`;
                }
                if (hide100OEE) {
                    url += `&hide_100_oee=1`;
                }
                
                await ensureShiftsLoadedForTokens(filteredTokens);
                const fullUrl = window.location.origin + url;
                console.log("URL COMPLETA de la API:", fullUrl);
                console.log("==================================================");
                console.log("COPIABLE:", fullUrl);
                console.log("==================================================");

                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Datos de estadísticas recibidos (ya filtrados por backend):", data);

                // Procesar los datos para asegurar que tienen la estructura correcta
                const processedData = data.map(item => {
                    console.log('Procesando item:', item.id, 'down_time:', item.down_time, 'production_stops_time:', item.production_stops_time);
                    return {
                        id: item.id || '-',
                        production_line_name: item.production_line_name || '-',
                        production_line_id: item.production_line_id || null,
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
                        operator_names: item.operator_names || [],
                        fast_time: item.fast_time || null,
                        slow_time: item.slow_time || null,
                        out_time: item.out_time || null,
                        prepair_time: item.prepair_time || null,
                        // Báscula final de línea (main)
                        weights_0_shiftNumber: item.weights_0_shiftNumber ?? null,
                        weights_0_shiftKg: item.weights_0_shiftKg ?? null,
                        weights_0_orderNumber: item.weights_0_orderNumber ?? null,
                        weights_0_orderKg: item.weights_0_orderKg ?? null,
                        // Básculas de rechazo (1-3)
                        weights_1_shiftNumber: item.weights_1_shiftNumber ?? null,
                        weights_1_shiftKg: item.weights_1_shiftKg ?? null,
                        weights_1_orderNumber: item.weights_1_orderNumber ?? null,
                        weights_1_orderKg: item.weights_1_orderKg ?? null,
                        weights_2_shiftNumber: item.weights_2_shiftNumber ?? null,
                        weights_2_shiftKg: item.weights_2_shiftKg ?? null,
                        weights_2_orderNumber: item.weights_2_orderNumber ?? null,
                        weights_2_orderKg: item.weights_2_orderKg ?? null,
                        weights_3_shiftNumber: item.weights_3_shiftNumber ?? null,
                        weights_3_shiftKg: item.weights_3_shiftKg ?? null,
                        weights_3_orderNumber: item.weights_3_orderNumber ?? null,
                        weights_3_orderKg: item.weights_3_orderKg ?? null
                    }});
                
                // Actualizar los KPIs con los datos ya filtrados del backend
                updateKPIs(processedData);
                
                // Limpiar cualquier estado de carga previo
                $('#loadingIndicator').hide();
                $('#controlWeightTable').show();
                
                // Destruir la tabla existente de forma segura antes de reinicializar
                if ($.fn.DataTable.isDataTable('#controlWeightTable')) {
                    $('#controlWeightTable').DataTable().destroy();
                }
                // Limpiar el contenido HTML para evitar conflictos
                $('#controlWeightTable').empty();

                const table = $('#controlWeightTable').DataTable({
                    dom: 'lfrtip',
                    buttons: [],
                    scrollX: true,
                    responsive: true,
                    data: processedData,
                    // 'destroy: true' ya no es necesario gracias al manejo manual
                    columns: [
                        { data: 'production_line_name', title: 'Línea', className: 'text-truncate', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Línea: ${cellData}`);
                        }},
                        { data: 'order_id', title: 'Orden', className: 'text-truncate', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Orden: ${cellData}`);
                        }},
                        { data: 'operator_names', title: 'Empleados', className: 'text-truncate', render: function(data, type, row) {
                            if (!data || data.length === 0) return '<span class="text-muted">Sin asignar</span>';
                            // Limitar a mostrar máximo 2 nombres y un contador si hay más
                            const names = Array.isArray(data) ? data : [data];
                            const displayNames = names.slice(0, 2).join(', ');
                            const remaining = names.length > 2 ? ` +${names.length - 2} más` : '';
                            return `<span title="${names.join(', ')}">${displayNames}${remaining}</span>`;
                        }},
                        { data: 'oee', title: 'OEE', render: data => `${Math.round(data)}%`, createdCell: function(td, cellData, rowData) {
                            const color = cellData >= 80 ? 'text-success' : cellData >= 60 ? 'text-warning' : 'text-danger';
                            $(td).html(`<span class="${color} fw-bold">${Math.round(cellData)}%</span>`);
                            $(td).attr('title', `OEE: ${Math.round(cellData)}%\nEstado: ${cellData >= 80 ? 'Excelente' : cellData >= 60 ? 'Aceptable' : 'Necesita mejora'}`);
                        }},
                        { data: 'status', title: 'Estado', render: data => {
                            const statusMap = {
                                'active': '<span class="badge bg-success">Activo</span>',
                                'paused': '<span class="badge bg-warning">Pausado</span>',
                                'error': '<span class="badge bg-danger">Incidencia</span>',
                                'completed': '<span class="badge bg-primary">Completado</span>',
                                'in_progress': '<span class="badge bg-info">En Progreso</span>',
                                'pending': '<span class="badge bg-secondary">Planificada</span>'
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
                        { data: null, title: 'Diferencia duración teórica', render: function(data, type, row) {
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

                // Configurar event listeners para los checkboxes de filtrado OEE
                $('#hideZeroOEE, #hide100OEE').off('change').on('change', function() {
                    // Recargar datos cuando cambien los filtros de OEE
                    $('#fetchData').click();
                });

            } catch (error) {
                console.error("Error al cargar datos:", error);
            }
        }

        // Función para actualizar los KPIs
        function updateKPIs(data) {
            console.log('Actualizando KPIs con', data.length, 'registros filtrados');

            const hasNumericValue = (value) => value !== null && value !== undefined && !isNaN(value);

            let totalDurationSeconds = 0;
            let totalOEE = 0;
            let validOEECount = 0;
            let totalFastTime = 0;
            let totalOutTime = 0;
            let totalDownTime = 0;
            let totalProductionStopsTime = 0;
            let totalPrepairTime = 0;
            let totalSlowTime = 0;

            data.forEach(item => {
                if (hasNumericValue(item.on_time)) {
                    totalDurationSeconds += Number(item.on_time);
                }

                if (hasNumericValue(item.oee)) {
                    const oeeValue = parseFloat(item.oee);
                    totalOEE += oeeValue > 1 ? oeeValue : oeeValue * 100;
                    validOEECount++;
                }

                if (hasNumericValue(item.fast_time)) {
                    totalFastTime += Number(item.fast_time);
                }

                if (hasNumericValue(item.out_time)) {
                    totalOutTime += Number(item.out_time);
                }

                if (hasNumericValue(item.down_time)) {
                    totalDownTime += Number(item.down_time);
                }

                if (hasNumericValue(item.production_stops_time)) {
                    totalProductionStopsTime += Number(item.production_stops_time);
                }

                if (hasNumericValue(item.prepair_time)) {
                    totalPrepairTime += Number(item.prepair_time);
                }

                if (hasNumericValue(item.slow_time)) {
                    totalSlowTime += Number(item.slow_time);
                }
            });

            $('#totalDuration').text(formatTime(totalDurationSeconds));

            const avgOEE = validOEECount > 0 ? totalOEE / validOEECount : 0;
            console.log('Cálculo OEE:', {
                totalOEE: totalOEE,
                validOEECount: validOEECount,
                avgOEE: avgOEE,
                roundedAvgOEE: Math.round(avgOEE)
            });
            $('#avgOEE').text(`${Math.round(avgOEE)}%`);

            if (avgOEE >= 80) {
                $('#avgOEE').removeClass('text-danger text-warning').addClass('text-success');
            } else if (avgOEE >= 60) {
                $('#avgOEE').removeClass('text-danger text-success').addClass('text-warning');
            } else {
                $('#avgOEE').removeClass('text-success text-warning').addClass('text-danger');
            }

            if (totalFastTime >= totalOutTime) {
                $('#totalTheoretical').removeClass('text-danger').addClass('text-success');
            } else {
                $('#totalTheoretical').removeClass('text-success').addClass('text-danger');
            }

            const netTheoreticalTime = Math.abs(totalFastTime - totalOutTime);
            $('#totalTheoretical').text(formatTime(netTheoreticalTime));

            $('#totalPrepairTime').text(formatTime(totalPrepairTime));
            $('#totalSlowTime').text(formatTime(totalSlowTime));

            // Paradas => down_time, Falta Material => production_stops_time (requerimiento previo)
            $('#totalProductionStopsTime').text(formatTime(totalDownTime));
            $('#totalDownTime').text(formatTime(totalProductionStopsTime));
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
            
            // Función auxiliar para verificar si un valor tiene datos reales
            const hasRealData = (value) => {
                return value !== null && value !== undefined && value !== '' && value !== '-' && value !== 0 && value !== '0';
            };
            
            // Verificar si hay datos en básculas
            const hasMainScaleData = (
                hasRealData(row.weights_0_shiftNumber) ||
                hasRealData(row.weights_0_shiftKg) ||
                hasRealData(row.weights_0_orderNumber) ||
                hasRealData(row.weights_0_orderKg)
            );
            
            // Variable para verificar si hay datos en básculas de rechazo
            let hasRejectionScaleData = false;
            
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
                
                // Solo mostrar si hay al menos un valor real
                if (hasRealData(shiftNumber) || hasRealData(shiftKg) || hasRealData(orderNumber) || hasRealData(orderKg)) {
                    hasRejectionScaleData = true;
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
            
            // Ocultar o mostrar la sección completa de básculas según si hay datos
            const scaleCard = $('.card:has(.card-header:contains("Básculas"))');
            if (!hasMainScaleData && !hasRejectionScaleData) {
                scaleCard.hide();
            } else {
                scaleCard.show();
            }
            
            // Actualizar estado
            const statusMap = {
                'active': { text: 'Activo', class: 'bg-success' },
                'paused': { text: 'Pausado', class: 'bg-warning' },
                'error': { text: 'Incidencia', class: 'bg-danger' },
                'completed': { text: 'Completado', class: 'bg-primary' },
                'in_progress': { text: 'En Progreso', class: 'bg-info' },
                'pending': { text: 'Planificada', class: 'bg-secondary' }
            };
            const status = statusMap[row.status] || { text: 'Iniciada Anterior', class: 'bg-secondary' };
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
                                    return `${context.label}: ${Math.round(context.raw)}%`;
                                }
                            }
                        }
                    },
                    elements: {
                        center: {
                            text: `${Math.round(oeePercentage)}%`,
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
                        const text = `${Math.round(oeeValue)}%`;
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
                const selectedOperators = $('#operatorSelect').val();
                console.log("Parámetros seleccionados (refresh):", { selectedLines, startDate, endDate, selectedOperators });
                
                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    ensureMaxRange7Days();
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
                const selectedOperators = $('#operatorSelect').val();
                console.log("Parámetros seleccionados:", { selectedLines, startDate, endDate, selectedOperators });

                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    ensureMaxRange7Days();
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
                    
                    // No necesitamos inicializar animaciones durante la exportación
                    
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
                        row.push(rowData.oee ? Math.round(rowData.oee) + '%' : '-');
                        
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
                    const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });
                    
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
                        row[pdfHeaders[7].dataKey] = rowData.oee ? Math.round(rowData.oee) + '%' : '-';
                        
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
                        tableHtml += '<td>' + (rowData.oee ? Math.round(rowData.oee) + '%' : '-') + '</td>';
                        
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