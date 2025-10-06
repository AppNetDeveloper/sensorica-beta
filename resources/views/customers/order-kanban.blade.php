@extends('layouts.admin')

@section('title', __('Order Kanban') . ' - ' . $customer->name)

@section('page-title', __('Order Kanban'))

@section('page-breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item">{{ __('Order Kanban') }}</li>

@endsection

@section('content')
<!-- Barra de Filtros y Controles -->
<div class="mb-3 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <a href="{{ route('customers.order-organizer', $customer) }}" class="btn btn-secondary me-2" id="backToProcessesBtn">
                <i class="ti ti-arrow-left me-1"></i> {{ __('Back to Processes') }}
            </a>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap flex-grow-1 justify-content-end">
            <div class="position-relative" style="width: 360px; max-width: 100%;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-gray-400"></i>
                <input type="text" id="searchInput" placeholder="{{ __('Search by order ID or customer...') }}"
                       class="form-control ps-5" style="width: 100%;">
            </div>
            <!-- Botón de IA eliminado -->
            <button id="fullscreenBtn" class="btn btn-light" title="{{ __('Fullscreen') }}">
                <i class="fas fa-expand-arrows-alt text-primary"></i>
            </button>
            <!-- Botones ocultos para mantener la funcionalidad de autoguardado y actualización automática -->
            <button id="saveChangesBtn" class="d-none"></button>
            <button id="refreshBtn" class="d-none"></button>

            <!-- Segunda línea: filtros rápidos a la derecha -->
            <div class="w-100 d-flex justify-content-end mt-2">
                <div class="filters-row d-flex align-items-center">
                    <div class="form-check form-switch mb-0 me-3 filters-switch">
                        <input class="form-check-input" type="checkbox" id="readyOnlyToggle">
                        <label class="form-check-label small mb-0" for="readyOnlyToggle">@lang('Mostrar solo listas')</label>
                    </div>
                    <div class="form-check form-switch mb-0 me-3 filters-switch">
                        <input class="form-check-input" type="checkbox" id="dimNotReadyToggle" checked>
                        <label class="form-check-label small mb-0" for="dimNotReadyToggle">@lang('Atenuar no listas')</label>
                    </div>
                    <div class="form-check form-switch mb-0 me-3 filters-switch">
                        <input class="form-check-input" type="checkbox" id="groupByCarToggle">
                        <label class="form-check-label small mb-0" for="groupByCarToggle">@lang('Agrupar por carro')</label>
                    </div>
                    <div class="form-check form-switch mb-0 me-2 filters-switch">
                        <input class="form-check-input" type="checkbox" id="autoSortToggle">
                        <label class="form-check-label small mb-0" for="autoSortToggle">@lang('Auto-orden (toggle)')</label>
                    </div>
                    <button id="applyAutoSortBtn" type="button" class="btn btn-primary btn-sm rounded-pill ms-1" onclick="window.handleSaveKanban && window.handleSaveKanban();">
                        <i class="fas fa-save me-1"></i> @lang('Guardar cambios')
                    </button>
                    <button id="assignPendingBtn" type="button" class="btn btn-warning btn-sm rounded-pill ms-2" onclick="window.applyAssignPendingOnly && window.applyAssignPendingOnly();">
                        <i class="fas fa-route me-1"></i> @lang('Mover pendientes a líneas')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contenedor del Kanban -->
<div id="kanbanContainer" class="position-relative">
    <div class="kanban-board" role="list" aria-label="{{ __('Kanban Board') }}"></div>
</div>

<!-- Leyenda visual para los iconos utilizados en las tarjetas -->
<div class="container-fluid mt-4 mb-4">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3"><i class="fas fa-info-circle"></i> @lang('Leyenda de indicadores')</h5>
            
            <!-- Indicadores de tiempo -->
            <h6 class="mt-3 mb-2">@lang('Indicadores de tiempo')</h6>
            <div class="d-flex flex-wrap gap-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-hourglass-half text-muted"></i></span>
                    <span>@lang('Tiempo de ocupación máquina')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary me-2"><i class="fas fa-hourglass-start"></i></span>
                    <span>@lang('Fecha estimada de inicio')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2"><i class="fas fa-hourglass-end"></i></span>
                    <span>@lang('Fecha estimada de fin')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning text-dark me-2"><i class="fas fa-lock"></i></span>
                    <span>@lang('Disponible en (countdown)')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="badge bg-success me-2"><i class="fas fa-unlock"></i></span>
                    <span>@lang('Ready desde (fecha)')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="far fa-clock text-muted"></i></span>
                    <span>@lang('Tiempo teórico')</span>
                </div>
            </div>
            
            <!-- Indicadores de fechas -->
            <h6 class="mt-3 mb-2">@lang('Indicadores de fechas')</h6>
            <div class="d-flex flex-wrap gap-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="far fa-calendar-alt text-muted"></i></span>
                    <span>@lang('Fecha de creación tarjeta')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-truck text-danger"></i></span>
                    <span>@lang('Fecha de entrega en instalación cliente')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-calendar-check text-muted"></i></span>
                    <span>@lang('Fecha creación pedido en ERP')</span>
                </div>
            </div>
            
            <!-- Indicadores de alerta -->
            <h6 class="mt-3 mb-2">@lang('Indicadores de alerta')</h6>
            <div class="d-flex flex-wrap gap-4 mb-3">
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-exclamation-triangle text-danger"></i></span>
                    <span>@lang('Orden urgente')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-exclamation-triangle text-primary"></i></span>
                    <span>@lang('Sin stock de materiales')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-exclamation-triangle text-warning"></i></span>
                    <span>@lang('Orden prioritaria')</span>
                </div>
            </div>
            
            <!-- Indicadores de cantidades -->
            <h6 class="mt-3 mb-2">@lang('Indicadores de cantidades')</h6>
            <div class="d-flex flex-wrap gap-4">
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-box text-muted"></i></span>
                    <span>@lang('Número de cajas')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-cubes text-muted"></i></span>
                    <span>@lang('Número de unidades')</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-2"><i class="fas fa-pallet text-muted"></i></span>
                    <span>@lang('Número de palets')</span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Bootstrap para el planificador de línea -->
<div class="modal fade" id="schedulerModal" tabindex="-1" aria-labelledby="schedulerModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 70%; width: 70%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="schedulerModalLabel">Planificación de disponibilidad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="schedulerModalBody">
        <div class="scheduler-container">
            <div class="mb-4">
                <h5>Planificación de disponibilidad para: <strong id="lineNameDisplay"></strong></h5>
                <p class="text-muted">Configure los turnos disponibles para cada día de la semana</p>
            </div>

            <form id="schedulerForm" onsubmit="return false;" method="POST">
                @csrf
                <input type="hidden" id="productionLineId" name="production_line_id" value="">
                <div class="scheduler-grid">
                    <div class="row mb-3 fw-bold">
                        <div class="col-3">Día</div>
                        <div class="col-9">Turnos disponibles</div>
                    </div>

                    <div id="schedulerDaysContainer">
                        <!-- Aquí se cargarán los días y turnos dinámicamente -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando datos de disponibilidad...</p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveScheduler">Guardar</button>
                </div>
            </form>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('style')
    <style>
        :root {
            --kanban-bg: #f9fafb; --column-bg: #f3f4f6; --column-border: #e5e7eb;
            --header-bg: #ffffff; --header-text: #374151; --card-bg: #ffffff;
            --card-text: #1f2937; --card-hover-bg: #f9fafb; --card-border: #e5e7eb;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.06); --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.1);
            --scrollbar-thumb: #d1d5db; --primary-color: #3b82f6; --danger-color: #ef4444; --warning-color: #f59e0b; --text-muted: #6b7280;
            --placeholder-bg: rgba(59, 130, 246, 0.2);
            --progress-bg: #e9ecef; --progress-bar-bg: #28a745;
        }
        

        body.dark {
            --kanban-bg: #0f172a; --column-bg: #1e293b; --column-border: #334155;
            --header-bg: #334155; --header-text: #f1f5f9; --card-bg: #2d3748;
            --card-text: #e2e8f0; --card-hover-bg: #334155; --card-border: #4a5568;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.2); --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.3);
            --scrollbar-thumb: #475569; --primary-color: #60a5fa; --danger-color: #f87171; --warning-color: #fca5a5; --text-muted: #94a3b8;
            --placeholder-bg: rgba(96, 165, 250, 0.2);
            --progress-bg: #4a5568; --progress-bar-bg: #48bb78;
        }

        #kanbanContainer { display: flex; flex-direction: column; height: calc(100vh - 220px); overflow: hidden; }
        .kanban-board { display: flex; gap: 1rem; padding: 1rem; overflow-x: auto; overflow-y: hidden; background-color: var(--kanban-bg); flex: 1; min-height: 0; align-items: stretch; }
        .kanban-board::-webkit-scrollbar { height: 10px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 10px; border: 2px solid var(--kanban-bg); }

        .kanban-column { flex: 0 0 340px; background-color: var(--column-bg); border-radius: 12px; min-width: 340px; display: flex; flex-direction: column; border: 1px solid var(--column-border); box-shadow: 0 1px 4px rgba(0,0,0,0.05); max-height: 100%; overflow: hidden; }
        .kanban-column.drag-over { border-color: var(--primary-color); }
        .column-header { padding: 0.75rem 1rem; position: sticky; top: 0; background-color: var(--header-bg); z-index: 10; border-bottom: 1px solid var(--column-border); transition: all 0.3s ease; min-height: 60px; flex-shrink: 0; height: auto; }
        .column-header-running { border-top: 3px solid #28a745 !important; }
        .column-header-paused { border-top: 3px solid #ffc107 !important; }
        .column-header-stopped { border-top: 3px solid #6c757d !important; }
        /* Estructura del header en dos líneas */
        .header-line-1 { display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem; }
        .header-line-2 { display: flex; align-items: center; margin-top: 0.25rem; }
        
        /* Estilos para el indicador de estado */
        .line-status-indicator { display: inline-flex; align-items: center; font-size: 0.8rem; }
        .line-status-indicator i { margin-right: 0.25rem; }
        .line-status-running { color: #28a745; }
        .line-status-paused { color: #ffc107; }
        .line-status-stopped { color: #6c757d; }
        
        /* Estilos para el operador */
        .line-operator { display: inline-flex; align-items: center; font-size: 0.75rem; color: var(--text-muted); margin-left: auto; }
        .line-operator i { margin-right: 0.25rem; }
        
        /* Estilos para el indicador de planificación */
        .line-schedule { display: inline-flex; align-items: center; font-size: 0.75rem; }
        .line-schedule i { margin-right: 0.25rem; }
        .line-schedule-planned { color: #28a745; }
        .line-schedule-unplanned { color: #ffc107; }
        .line-schedule-offshift { color: #6c757d; }
        .column-search-container { padding: 0 0.75rem; background-color: var(--header-bg); position: sticky; top: 50px; z-index: 9; border-bottom: 1px solid var(--column-border); }
        .column-title { font-weight: 600; color: var(--header-text); margin: 0; font-size: 1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .column-cards { padding: 0.75rem; overflow-y: auto; flex-grow: 1; display: flex; flex-direction: column; gap: 8px; min-height: 100px; }
        .column-cards::-webkit-scrollbar { width: 6px; }
        .column-cards::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 3px; }

        .placeholder { 
            background-color: var(--placeholder-bg); 
            border: 3px dashed var(--primary-color); 
            border-radius: 12px; 
            margin: 15px 0; 
            flex-shrink: 0; 
            transition: all 0.2s ease; 
            min-height: 120px; 
            opacity: 0.8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 600;
            pointer-events: none;
        }
        .column-header-stats { display: flex; align-items: center; gap: 0.5rem; }
        .card-count-badge, .time-sum-badge { background-color: rgba(0,0,0,0.08); color: var(--header-text); padding: 0.2rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 500; white-space: nowrap; }
        .time-sum-badge .fa-clock { margin-right: 0.25rem; }

        .final-states-container { display: flex; flex-direction: column; flex-grow: 1; overflow-y: auto; padding: 0.5rem; gap: 1rem; }
        .final-state-section { background-color: transparent; border-radius: 8px; border: 1px dashed var(--column-border); display: flex; flex-direction: column; flex: 1; min-height: 150px; overflow: hidden; transition: all 0.2s ease; }
        .final-state-section.drag-over { border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color); }
        .final-state-header { padding: 10px 12px; border-bottom: 1px solid var(--card-border); background-color: var(--header-bg); }
        .final-state-title { font-weight: 600; font-size: 0.9rem; color: var(--header-text); }

        .kanban-card { background-color: var(--card-bg); color: var(--card-text); border-radius: 10px; border: 1px solid var(--card-border); border-left: 5px solid; box-shadow: var(--card-shadow); flex-shrink: 0; overflow: hidden; width: 100%; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: grab; }
        .kanban-card.urgent { border: 1px solid var(--danger-color); box-shadow: 0 0 10px rgba(239, 68, 68, 0.2); }
        /* Estilo para órdenes prioritarias - solo un borde sutil */
        .kanban-card.priority-order { border: 1px solid #ffc107; }
        .kanban-card.collapsed .kanban-card-body,
        .kanban-card.collapsed .kanban-card-body .process-list,
        .kanban-card.collapsed .kanban-card-body .progress-container,
        .kanban-card.collapsed .kanban-card-body .d-flex.justify-content-between.align-items-center:not(:first-child) { 
            display: none; 
        }
        .kanban-card.dragging { opacity: 0; height: 0; padding: 0; margin: 0; border: none; overflow: hidden; }
        .kanban-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }

        .kanban-card-header { padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: flex-start; cursor: pointer; }
        .kanban-card-body { padding: 0 1.25rem 1.25rem 1.25rem; }
        /* Footer eliminado */
        .card-menu { font-size: 1rem; color: var(--text-muted); cursor: pointer; }
        .group-badge { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; color: white; font-size: 0.75rem; font-weight: bold; margin-left: 8px; }
        .progress-container { margin-top: 0.75rem; }
        .progress { height: 8px; background-color: var(--progress-bg); border-radius: 4px; overflow: hidden; }
        .progress-bar { height: 100%; background-color: var(--progress-bar-bg); border-radius: 4px; transition: width 0.3s ease; }
        
        .process-list { display: flex; flex-wrap: wrap; gap: 0.25rem; }
        .process-tag { padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 500; }
        .process-tag-done { background-color: #10b981; color: white; }
        .process-tag-pending { background-color: var(--warning-color); color: white; }

        /* Solución avanzada anti-parpadeo para el header del Kanban */
        .column-header {
            will-change: contents;
            transform: translateZ(0);
            backface-visibility: hidden;
            -webkit-font-smoothing: antialiased;
            transition: background-color 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        /* Estructura fija para los elementos del header */
        .header-line-1,
        .header-line-2 {
            position: relative;
            min-height: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
            margin-bottom: 4px;
        }
        
        /* Evitar que los elementos ocultos causen reflow */
        .line-status-indicator,
        .line-operator,
        .line-schedule {
            transition: opacity 0.3s ease, transform 0.3s ease, color 0.3s ease;
            opacity: 1;
            transform: translateZ(0);
            will-change: opacity, transform;
        }
        
        /* Ocultar elementos sin causar reflow */
        .line-status-indicator[style*="display: none"],
        .line-operator[style*="display: none"],
        .line-schedule[style*="display: none"] {
            opacity: 0 !important;
            position: absolute !important;
            visibility: hidden !important;
            pointer-events: none !important;
            transform: translateY(-5px) !important;
        }
        
        /* Asegurar que los elementos no cambien de tamaño */
        .line-status-indicator i,
        .line-operator i,
        .line-schedule i {
            display: inline-block;
            width: 16px;
            text-align: center;
            margin-right: 4px;
        }
        
        /* Transiciones para los estados de columna */
        .column-header-running,
        .column-header-paused,
        .column-header-stopped {
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        :fullscreen #kanbanContainer { height: 100vh; padding: 1rem; }
        :fullscreen .kanban-board { align-items: stretch; }
        
        /* Estilos para el loader visual */
        #kanban-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(2px);
            transition: opacity 0.3s ease;
        }
        
        #kanban-loader.fade-out {
            opacity: 0;
        }
        
        .loader-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--primary-color-light);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .loader-text {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--text-color);
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        :fullscreen .kanban-column { height: 100%; }
        .cursor-pointer { cursor: pointer; }
        
        .kanban-container {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            overflow-x: auto;
        }
        /* Atenuar tarjetas no listas */
        .kanban-card.dim-not-ready {
            opacity: 0.55;
        }
    </style>
    <style>
    /* Estilo compacto para la fila de filtros (segunda línea) */
    .filters-row {
        background: var(--header-bg);
        border: 1px solid var(--column-border);
        border-radius: 9999px;
        padding: 6px 10px;
        gap: 10px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        max-width: 100%;
        flex-wrap: wrap;
    }
    .filters-switch .form-check-input {
        transform: scale(0.9);
        margin-right: 6px;
        cursor: pointer;
    }
    .filters-switch .form-check-label {
        cursor: pointer;
        color: var(--text-muted);
    }
    .filters-row #applyAutoSortBtn {
        height: 30px;
        line-height: 1;
        padding: 0 12px;
    }
    @media (max-width: 1200px) {
        .filters-row { border-radius: 12px; }
    }
    @media (max-width: 768px) {
        .filters-row { justify-content: space-between; gap: 8px; }
        .filters-switch { margin-right: 0 !important; }
    }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. CONFIGURACIÓN INICIAL Y VARIABLES GLOBALES ---
        
        // Estado de las líneas de producción
        let productionLineStatuses = {};
        
        const kanbanBoard = document.querySelector('.kanban-board');
        const searchInput = document.getElementById('searchInput');
        let masterOrderList = @json($processOrders);
        const customerId = {{ $customer->id }};
        const productionLinesData = @json($productionLines);
        
        let hasUnsavedChanges = false;
        let draggedCard = null;
        let cachedDropPosition = null; // Cachear posición detectada durante dragOver
        let cachedTargetContainer = null; // Cachear contenedor objetivo
        let isRequestInProgress = false; // Variable para controlar si hay una solicitud en curso
        let autoSortDirty = false; // Se marca cuando el auto-orden ha cambiado el orden visual
        // Firma del último estado guardado para detectar cambios reales (orden, línea, estado)
        let lastSavedSignature = null;

        function computeCurrentSignature() {
            try {
                return JSON.stringify(masterOrderList.map((o, idx) => ({
                    id: Number(o.id),
                    orden: idx,
                    line: Number(o.productionLineId ?? o.production_line_id ?? null) || null,
                    status: o.status
                })));
            } catch (e) {
                console.warn('computeCurrentSignature error', e);
                return '';
            }
        }

        function updateUnsavedFlag() {
            try {
                const sig = computeCurrentSignature();
                hasUnsavedChanges = (lastSavedSignature !== null) && sig !== lastSavedSignature;
            } catch(_) {}
        }
        function scheduleUpdateUnsavedFlag() { setTimeout(updateUnsavedFlag, 0); }
        // Estado para el buscador de la columna Pendientes
        let pendingSearchValue = '';
        let pendingSearchFocused = false;
        let pendingSearchCaret = null;
        
        // Utilidades para capturar/restaurar estado del buscador de Pendientes
        function capturePendingSearchState() {
            const el = document.querySelector('#pending_assignment .pending-search-input');
            if (el) {
                pendingSearchValue = el.value;
                if (document.activeElement === el) {
                    pendingSearchFocused = true;
                    try { pendingSearchCaret = el.selectionStart; } catch(_) { pendingSearchCaret = null; }
                } else {
                    pendingSearchFocused = false;
                    pendingSearchCaret = null;
                }
            }
        }
        function diffMs(a, b) { return a.getTime() - b.getTime(); }
        function formatRelativeEs(ms) {
            const abs = Math.abs(ms);
            const totalSec = Math.floor(abs / 1000);
            const mins = Math.floor(totalSec / 60) % 60;
            const hours = Math.floor(totalSec / 3600) % 24;
            const days = Math.floor(totalSec / 86400);
            const parts = [];
            if (days) parts.push(days + 'd');
            if (hours) parts.push(hours + 'h');
            parts.push(mins + 'm');
            return parts.join(' ');
        }
        function isReady(dateInput) {
            if (!dateInput) return true; // si no hay restricción, consideramos listo
            let d = new Date(dateInput);
            if (isNaN(d)) {
                if (typeof dateInput === 'string') d = new Date(dateInput.replace(' ', 'T'));
            }
            if (isNaN(d)) return true;
            return Date.now() >= d.getTime();
        }
        // Actualiza estadísticas básicas de una columna (conteo de tarjetas). Seguro si no existe estructura.
        function updateColumnStats(columnElement, lightweight = false) {
            try {
                if (!columnElement) return;
                const count = columnElement.querySelectorAll('.column-cards > .kanban-card').length;
                // Intentar actualizar un contador visible si existe
                const headerCountEl = columnElement.querySelector('.column-header .column-count');
                if (headerCountEl) {
                    headerCountEl.textContent = count;
                }
                // Si hay atributos data para métricas, actualizarlos
                columnElement.dataset.cardCount = String(count);
                // lightweight: por ahora no distingue, pero dejamos el parámetro para compatibilidad
            } catch (e) {
                console.debug('updateColumnStats: no-op (estructura no encontrada)', e);
            }
        }
        // Formateador específico para ready_after con logs por tarjeta
        function formatReady(dateInput, orderId) {
            if (!dateInput) {
                console.debug('[Kanban] PO', orderId, 'ready_after_datetime vacío');
                return '';
            }
            const out = formatDateTimeEs(dateInput);
            console.debug('[Kanban] PO', orderId, 'ready_after_datetime =', dateInput, '=>', out);
            return out;
        }
        // Determina readiness de una orden usando payload o fallback a fecha
        function isOrderReadyForFilter(order) {
            if (!order) return true;
            if (typeof order.is_ready === 'boolean') return order.is_ready;
            return isReady(order.ready_after_datetime);
        }
        
        // --- FUNCIONES DE AGRUPACIÓN POR CARRO ---
        function isGroupByCarEnabled() {
            const el = document.getElementById('groupByCarToggle');
            return !!(el && el.checked);
        }
        
        function getLatestAfterItem(order) {
            try {
                const after = Array.isArray(order && order.after) ? order.after : [];
                if (!after.length) return null;
                const sorted = after.slice().sort(function(a,b){
                    const da = a && a.scanned_at ? new Date(String(a.scanned_at).replace(' ','T')).getTime() : 0;
                    const db = b && b.scanned_at ? new Date(String(b.scanned_at).replace(' ','T')).getTime() : 0;
                    return db - da;
                });
                return sorted[0] || null;
            } catch(e) { return null; }
        }
        
        function getOrderBarcoderId(order) {
            const latest = getLatestAfterItem(order);
            return latest ? (latest.barcoder_id || null) : null;
        }
        
        function getLatestAfterTimestamp(order) {
            const latest = getLatestAfterItem(order);
            if (!latest) return -1;
            try {
                return latest.scanned_at ? new Date(String(latest.scanned_at).replace(' ','T')).getTime() : -1;
            } catch(e) { return -1; }
        }
        
        function compactByCar(items, colType) {
            try {
                const list = Array.isArray(items) ? items.slice() : [];
                const newList = [];
                const added = new Set();
                const getId = function(o) { return Number(o && o.id); };
                
                if (colType === 'production' && list.length) {
                    const top = list[0];
                    if (top && top.status === 'in_progress') {
                        newList.push(top);
                        added.add(getId(top));
                    }
                }
                
                for (let i = 0; i < list.length; i++) {
                    const cur = list[i];
                    const curId = getId(cur);
                    if (added.has(curId)) continue;
                    
                    const carVal = getOrderBarcoderId(cur);
                    if (carVal === null || carVal === undefined || carVal === '') {
                        newList.push(cur);
                        added.add(curId);
                        continue;
                    }
                    
                    const car = String(carVal);
                    const group = [];
                    
                    for (let j = i; j < list.length; j++) {
                        const cand = list[j];
                        const candId = getId(cand);
                        if (added.has(candId)) continue;
                        const cVal = getOrderBarcoderId(cand);
                        if (cVal !== null && cVal !== undefined && cVal !== '' && String(cVal) === car) {
                            group.push(cand);
                            added.add(candId);
                        }
                    }
                    
                    group.sort(function(a, b) {
                        return getLatestAfterTimestamp(b) - getLatestAfterTimestamp(a);
                    });
                    
                    if (newList.length && colType === 'production' && newList[0] && newList[0].status === 'in_progress') {
                        const topId = getId(newList[0]);
                        group.forEach(function(o) {
                            if (getId(o) !== topId) newList.push(o);
                        });
                    } else {
                        newList.push.apply(newList, group);
                    }
                }
                
                return newList;
            } catch(e) { return items || []; }
        }
        
        // Devuelve si el autosort está activado desde el toggle
        function isAutoSortEnabled() {
            const el = document.getElementById('autoSortToggle');
            return !!(el && el.checked);
        }
        // Comparador de orden inteligente por readiness, prioridad, stock, proximidad
        function compareOrders(a, b) {
            // 1) Listas primero
            const aReady = isOrderReadyForFilter(a) ? 1 : 0;
            const bReady = isOrderReadyForFilter(b) ? 1 : 0;
            if (aReady !== bReady) return bReady - aReady; // ready (1) antes que not ready (0)

            // 2) Prioridad primero
            const aPrio = (a.is_priority === true || a.is_priority === 1) ? 1 : 0;
            const bPrio = (b.is_priority === true || b.is_priority === 1) ? 1 : 0;
            if (aPrio !== bPrio) return bPrio - aPrio;

            // 3) Con stock primero (has_stock != 0)
            const aStock = (a.has_stock === 0) ? 0 : 1;
            const bStock = (b.has_stock === 0) ? 0 : 1;
            if (aStock !== bStock) return bStock - aStock;

            // 4) Si no están listas, la que falta menos tiempo primero
            const aRis = typeof a.ready_in_seconds === 'number' ? a.ready_in_seconds : Number.MAX_SAFE_INTEGER;
            const bRis = typeof b.ready_in_seconds === 'number' ? b.ready_in_seconds : Number.MAX_SAFE_INTEGER;
            if (aRis !== bRis) return aRis - bRis;

            // 5) Fecha de entrega más próxima primero
            const aDel = a.delivery_date ? new Date(String(a.delivery_date).replace(' ', 'T')).getTime() : Number.MAX_SAFE_INTEGER;
            const bDel = b.delivery_date ? new Date(String(b.delivery_date).replace(' ', 'T')).getTime() : Number.MAX_SAFE_INTEGER;
            if (aDel !== bDel) return aDel - bDel;

            // 6) Antigüedad de creación
            const aCreated = a.created_at ? new Date(String(a.created_at).replace(' ', 'T')).getTime() : 0;
            const bCreated = b.created_at ? new Date(String(b.created_at).replace(' ', 'T')).getTime() : 0;
            if (aCreated !== bCreated) return aCreated - bCreated;

            // 7) Fallback por 'orden'
            return (a.orden || 0) - (b.orden || 0);
        }
        // Aplica los filtros de "Mostrar solo listas" y "Atenuar no listas"
        function applyReadinessFilters() {
            const readyOnlyEl = document.getElementById('readyOnlyToggle');
            const dimEl = document.getElementById('dimNotReadyToggle');
            const readyOnly = !!(readyOnlyEl && readyOnlyEl.checked);
            const dimNotReady = !!(dimEl && dimEl.checked);
            let processed = 0;
            document.querySelectorAll('.kanban-card').forEach(card => {
                const id = Number(card.dataset.id);
                const order = masterOrderList.find(o => Number(o.id) === id);
                if (!order) return;
                const ready = isOrderReadyForFilter(order);
                // Mostrar solo listas
                if (readyOnly) {
                    card.style.display = ready ? '' : 'none';
                } else {
                    card.style.display = '';
                }
                // Atenuar no listas
                if (!ready && dimNotReady) {
                    card.classList.add('dim-not-ready');
                } else {
                    card.classList.remove('dim-not-ready');
                }
                processed++;
            });
            console.debug('[Kanban] applyReadinessFilters -> procesadas:', processed, 'readyOnly:', readyOnly, 'dimNotReady:', dimNotReady);
        }
        
        document.getElementById('groupByCarToggle') && document.getElementById('groupByCarToggle').addEventListener('change', function() {
            distributeAndRender(true);
        });

        // --- Auto-asignación desde Pendientes hacia Máquinas ---
        function getProductionLineKeys() {
            // Columnas cuyo id empiece por 'line_'
            return Array.from(document.querySelectorAll('.kanban-column[id^="line_"]')).map(el => el.id);
        }
        function extractLineIdFromKey(key) {
            // 'line_12' -> 12 (number)
            const m = String(key).match(/^line_(\d+)$/);
            return m ? Number(m[1]) : null;
        }
        function getColumnLoadSecondsFromDOM(columnId) {
            // Suma de tiempo teórico de las tarjetas de la columna (en segundos)
            const col = document.getElementById(columnId);
            if (!col) return 0;
            const cards = col.querySelectorAll('.column-cards .kanban-card');
            let sum = 0;
            cards.forEach(card => {
                const id = Number(card.dataset.id);
                const order = masterOrderList.find(o => Number(o.id) === id);
                if (!order) return;
                // Usar tiempo teórico si existe; fallback a accumulated_time (segundos) si aplica
                const secTheo = parseTimeToSeconds(order.theoretical_time || '00:00:00');
                const secAcc = typeof order.accumulated_time === 'number' ? order.accumulated_time : 0;
                sum += (secTheo || secAcc || 0);
            });
            return sum;
        }
        function pickLeastLoadedLine(lineKeys) {
            let bestKey = null;
            let bestLoad = Number.POSITIVE_INFINITY;
            lineKeys.forEach(key => {
                const load = getColumnLoadSecondsFromDOM(key);
                if (load < bestLoad) {
                    bestLoad = load;
                    bestKey = key;
                }
            });
            return bestKey;
        }
        function applyAutoAssignAndSort() {
            // 1) Identificar candidatos en Pendientes Asignación
            // Aceptar tanto camelCase como snake_case por si el backend envía snake_case
            const prevUnsaved = !!hasUnsavedChanges;
            const beforeState = new Map(); // id -> {productionLineId, production_line_id, status}
            const isUnassigned = (o) => {
                const a = o.productionLineId;
                const b = o.production_line_id;
                return (!a && !b) || String(a || b || '').length === 0;
            };
            const pendingOrders = masterOrderList.filter(isUnassigned);
            if (!pendingOrders.length) {
                console.log('[Auto-orden] No hay órdenes en Pendientes para asignar.');
            }
            console.log('[Auto-orden] Candidatos pendientes:', pendingOrders.map(o => ({ id: o.id, orden: o.orden, prio: o.is_priority, delivery: o.delivery_date })));

            // 2) Asignar TODAS las órdenes en Pendientes (el sistema no genera tarjeta sin stock)
            const eligible = [...pendingOrders];

            // 3) Ordenar candidatos por compareOrders (mejores primero)
            eligible.sort(compareOrders);
            console.log('[Auto-orden] Orden de asignación (mejor primero):', eligible.map(o => o.id));

            // 4) Repartir por menor carga actual
            const lineKeys = getProductionLineKeys();
            if (!lineKeys.length) {
                console.warn('[Auto-orden] No hay columnas de máquinas disponibles.');
                if (window.Swal && Swal.fire) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin líneas de producción',
                        text: 'No hay columnas de máquinas disponibles para asignar.',
                    });
                }
                return;
            }
            console.log('[Auto-orden] Columnas de máquinas detectadas:', lineKeys);
            let assignedCount = 0;
            eligible.forEach(order => {
                if (!beforeState.has(order.id)) {
                    beforeState.set(order.id, {
                        productionLineId: order.productionLineId,
                        production_line_id: order.production_line_id,
                        status: order.status
                    });
                }
                const key = pickLeastLoadedLine(lineKeys);
                if (!key) return;
                const lineId = extractLineIdFromKey(key);
                if (lineId == null) return;
                // Actualizar modelo en memoria (normalizar ambos campos)
                order.productionLineId = lineId;
                order.production_line_id = lineId;
                // Si la orden está en progreso no la movemos; en pendientes no debería estar in_progress
                if (!order.status || order.status === 'pending' || order.status === 'queued') {
                    order.status = 'queued';
                }
                assignedCount++;
                console.log(`[Auto-orden] Asignada orden ${order.id} a línea ${lineId} (col ${key})`);
                // Incrementar carga virtual para balanceo siguiente
                const ghost = document.createElement('div');
                ghost.className = 'kanban-card ghost-temp';
                const col = document.getElementById(key)?.querySelector('.column-cards');
                if (col) col.appendChild(ghost);
            });
            // Limpiar ghosts creados para el conteo
            document.querySelectorAll('.kanban-card.ghost-temp').forEach(n => n.remove());

            console.log('[Auto-orden] Asignadas', assignedCount, 'órdenes desde Pendientes.');

            // Marcar cambios sin guardar para evitar que el auto-refresh revierta inmediatamente
            hasUnsavedChanges = hasUnsavedChanges || assignedCount > 0;
            if (hasUnsavedChanges) { console.log('[Auto-orden] Cambios marcados como pendientes de guardar'); }

            // 5) Re-renderizar con auto-sort aplicado si está activo
            distributeAndRender(true);

            // 6) Preguntar si guardar cambios al backend
            const doConfirm = async () => {
                try {
                    if (window.Swal && Swal.fire) {
                        const res = await Swal.fire({
                            title: '{{ __('¿Guardar cambios?') }}',
                            text: '{{ __('Se asignaron automáticamente órdenes a máquinas. ¿Quieres persistir el cambio?') }}',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: '{{ __('Sí, guardar') }}',
                            cancelButtonText: '{{ __('No') }}'
                        });
                        return res.isConfirmed;
                    }
                } catch(_) {}
                return window.confirm('{{ __('Se asignaron automáticamente órdenes a máquinas. ¿Guardar cambios?') }}');
            };
            doConfirm().then(ok => {
                if (ok) {
                    saveKanbanChanges();
                } else {
                    // Revertir cambios en memoria
                    beforeState.forEach((snap, id) => {
                        const o = masterOrderList.find(x => x.id === id);
                        if (o) {
                            o.productionLineId = snap.productionLineId;
                            o.production_line_id = snap.production_line_id;
                            o.status = snap.status;
                        }
                    });
                    hasUnsavedChanges = prevUnsaved;
                    console.log('[Auto-orden] Cancelado por el usuario: cambios revertidos');
                    distributeAndRender(false);
                }
            });
        }
        // Exponer globalmente para llamadas inline o desde otros scripts
        try { window.applyAutoAssignAndSort = applyAutoAssignAndSort; console.log('[Bind] applyAutoAssignAndSort expuesta en window'); } catch(_) {}

        // Primera fase: SOLO mover Pendientes a líneas, sin reordenar tarjetas existentes
        function applyAssignPendingOnly() {
            console.log('[Asignación] Fase 1: mover solo Pendientes a líneas (sin reordenar)');
            const prevUnsaved = !!hasUnsavedChanges;
            const beforeState = new Map(); // id -> {productionLineId, production_line_id, status}
            const isUnassigned = (o) => {
                const a = o.productionLineId;
                const b = o.production_line_id;
                return (!a && !b) || String(a || b || '').length === 0;
            };
            const pendingOrders = masterOrderList.filter(isUnassigned);
            console.log('[Asignación] Pendientes detectadas:', pendingOrders.map(o => o.id));
            const lineKeys = getProductionLineKeys();
            if (!lineKeys.length) {
                console.warn('[Asignación] No hay columnas de máquinas disponibles.');
                if (window.Swal && Swal.fire) {
                    Swal.fire({
                        icon: 'warning', title: 'Sin líneas de producción', text: 'No hay columnas de máquinas disponibles para asignar.'
                    });
                }
                return;
            }
            console.log('[Asignación] Columnas de máquinas detectadas:', lineKeys);
            let assignedCount = 0;
            pendingOrders.forEach(order => {
                if (!beforeState.has(order.id)) {
                    beforeState.set(order.id, {
                        productionLineId: order.productionLineId,
                        production_line_id: order.production_line_id,
                        status: order.status
                    });
                }
                const key = pickLeastLoadedLine(lineKeys);
                if (!key) return;
                const lineId = extractLineIdFromKey(key);
                if (lineId == null) return;
                order.productionLineId = lineId;
                order.production_line_id = lineId;
                if (!order.status || order.status === 'pending' || order.status === 'queued') {
                    order.status = 'queued';
                }
                assignedCount++;
                console.log(`[Asignación] Asignada orden ${order.id} a línea ${lineId}`);
            });
            console.log('[Asignación] Total asignadas desde Pendientes:', assignedCount);
            hasUnsavedChanges = hasUnsavedChanges || assignedCount > 0;
            if (hasUnsavedChanges) console.log('[Asignación] Cambios marcados como pendientes de guardar');
            // Re-render SIN ordenar para respetar el orden actual de tarjetas
            distributeAndRender(false);
            // Preguntar si guardar
            const doConfirm = async () => {
                try {
                    if (window.Swal && Swal.fire) {
                        const res = await Swal.fire({
                            title: '{{ __('¿Guardar cambios?') }}',
                            text: '{{ __('Se asignaron pedidos pendientes a líneas. ¿Quieres persistir el cambio?') }}',
                            icon: 'question', showCancelButton: true,
                            confirmButtonText: '{{ __('Sí, guardar') }}',
                            cancelButtonText: '{{ __('No') }}'
                        });
                        return res.isConfirmed;
                    }
                } catch(_) {}
                return window.confirm('{{ __('Se asignaron pedidos pendientes a líneas. ¿Guardar cambios?') }}');
            };
            doConfirm().then(ok => {
                if (ok) {
                    saveKanbanChanges();
                } else {
                    // Revertir cambios si el usuario no quiere guardar
                    beforeState.forEach((snap, id) => {
                        const o = masterOrderList.find(x => x.id === id);
                        if (o) {
                            o.productionLineId = snap.productionLineId;
                            o.production_line_id = snap.production_line_id;
                            o.status = snap.status;
                        }
                    });
                    hasUnsavedChanges = prevUnsaved;
                    console.log('[Asignación] Cancelado por el usuario: cambios revertidos');
                    distributeAndRender(false);
                }
            });
        }
        try { window.applyAssignPendingOnly = applyAssignPendingOnly; console.log('[Bind] applyAssignPendingOnly expuesta en window'); } catch(_) {}
        
        // Lee el orden visual actual del DOM (izq->der por columna y arriba->abajo por tarjeta)
        function getVisualOrderIds() {
            try {
                const board = document.querySelector('.kanban-board');
                if (!board) return [];
                const ids = [];
                const columns = board.querySelectorAll('.kanban-column');
                columns.forEach(col => {
                    const cards = col.querySelectorAll('.column-cards .kanban-card');
                    cards.forEach(card => {
                        const id = Number(card.dataset.id);
                        // Solo considerar tarjetas visibles para no reordenar las ocultas por filtros
                        const isVisible = card.style.display !== 'none';
                        if (isVisible && !Number.isNaN(id)) ids.push(id);
                    });
                });
                return ids;
            } catch (e) {
                console.warn('getVisualOrderIds error', e);
                return [];
            }
        }

        // Aplica el orden visual sobre masterOrderList y marca cambios si varía
        function applyVisualOrderToMasterList() {
            try {
                const ids = getVisualOrderIds();
                if (!ids.length) return false;
                // Comprobar si la subsecuencia visible ya coincide con el orden actual
                const currentVisibleIds = masterOrderList.map(o => Number(o.id)).filter(id => ids.includes(id));
                const isSameVisibleOrder = currentVisibleIds.length === ids.length && currentVisibleIds.every((id, i) => id === ids[i]);
                if (isSameVisibleOrder) return false;

                // Reordenar de forma estable: las visibles siguen "ids" y las no visibles mantienen su posición relativa
                const objById = new Map(masterOrderList.map(o => [Number(o.id), o]));
                const idSet = new Set(ids);
                const visibleObjs = ids.map(id => objById.get(id)).filter(Boolean);
                let v = 0;
                const newList = masterOrderList.map(o => {
                    return idSet.has(Number(o.id)) ? visibleObjs[v++] : o;
                });

                const changed = newList.length === masterOrderList.length && newList.some((o, i) => o !== masterOrderList[i]);
                if (changed) {
                    masterOrderList = newList;
                    hasUnsavedChanges = true;
                    console.log('[Orden] masterOrderList actualizado desde el orden visual (estable)');
                    scheduleUpdateUnsavedFlag();
                }
                return changed;
            } catch (e) {
                console.warn('applyVisualOrderToMasterList error', e);
                return false;
            }
        }

        // Guardar cambios manualmente desde el botón azul
        function handleSaveKanban() {
            try {
                const prevUnsaved = !!hasUnsavedChanges;
                // Solo sincronizar con el DOM si el auto-orden está activo Y hubo cambio (autoSortDirty)
                try { if (isAutoSortEnabled() && autoSortDirty) applyVisualOrderToMasterList(); } catch(_) {}
                if (!hasUnsavedChanges) {
                    // Permitir guardar aunque no detectemos cambios (por ejemplo, tras auto-orden)
                    const confirmNoChanges = async () => {
                        try {
                            if (window.Swal && Swal.fire) {
                                const res = await Swal.fire({
                                    title: '{{ __('¿Guardar ahora?') }}',
                                    text: '{{ __('No se detectan cambios pendientes, pero puedes forzar el guardado. ¿Deseas continuar?') }}',
                                    icon: 'question', showCancelButton: true,
                                    confirmButtonText: '{{ __('Sí, guardar') }}',
                                    cancelButtonText: '{{ __('No') }}'
                                });
                                if (res.isConfirmed) {
                                    console.log('[Guardar] Forzando guardado sin cambios detectados…');
                                    saveKanbanChanges();
                                } else {
                                    console.log('[Guardar] Cancelado por el usuario (sin cambios detectados)');
                                    hasUnsavedChanges = prevUnsaved;
                                }
                                return;
                            }
                        } catch(_) {}
                        if (window.confirm('{{ __('No se detectan cambios pendientes. ¿Guardar de todas formas?') }}')) {
                            console.log('[Guardar] Forzando guardado sin cambios detectados…');
                            saveKanbanChanges();
                        } else {
                            console.log('[Guardar] Cancelado por el usuario (sin cambios detectados)');
                            hasUnsavedChanges = prevUnsaved;
                        }
                    };
                    return void confirmNoChanges();
                }
                const confirmAndSave = async () => {
                    try {
                        if (window.Swal && Swal.fire) {
                            const res = await Swal.fire({
                                title: '{{ __('¿Guardar cambios?') }}',
                                text: '{{ __('Se aplicaron cambios en el tablero. ¿Quieres persistirlos ahora?') }}',
                                icon: 'question', showCancelButton: true,
                                confirmButtonText: '{{ __('Sí, guardar') }}',
                                cancelButtonText: '{{ __('No') }}'
                            });
                            if (res.isConfirmed) {
                                console.log('[Guardar] Guardando cambios del Kanban…');
                                saveKanbanChanges();
                            } else {
                                console.log('[Guardar] Cancelado por el usuario');
                                hasUnsavedChanges = prevUnsaved;
                            }
                            return;
                        }
                    } catch(_) {}
                    // Fallback sin Swal
                    if (window.confirm('{{ __('Hay cambios sin guardar. ¿Guardar ahora?') }}')) {
                        console.log('[Guardar] Guardando cambios del Kanban…');
                        saveKanbanChanges();
                    } else {
                        console.log('[Guardar] Cancelado por el usuario');
                        hasUnsavedChanges = prevUnsaved;
                    }
                };
                confirmAndSave();
            } catch (e) {
                console.error('[Guardar] Error al guardar:', e);
            }
        }
        try { window.handleSaveKanban = handleSaveKanban; console.log('[Bind] handleSaveKanban expuesta en window'); } catch(_) {}
        function restorePendingSearchState() {
            const el = document.querySelector('#pending_assignment .pending-search-input');
            if (el) {
                el.value = pendingSearchValue || '';
                // Conectar handler de entrada para actualizar valor y re-filtrar en vivo
                const onInput = (e) => {
                    pendingSearchValue = e.target.value || '';
                    // Re-render mínimo: solo redistribuir para aplicar filtro; sin perder foco
                    distributeAndRender(false);
                    // Volver a enfocar tras re-render
                    const el2 = document.querySelector('#pending_assignment .pending-search-input');
                    if (el2) {
                        el2.focus();
                        try { if (pendingSearchCaret != null) el2.setSelectionRange(pendingSearchCaret, pendingSearchCaret); } catch(_) {}
                    }
                };
                // Evitar múltiples listeners: el nodo se recrea en cada render, así que es seguro añadir
                el.addEventListener('input', onInput);
                el.addEventListener('focus', () => { pendingSearchFocused = true; });
                el.addEventListener('blur', () => { pendingSearchFocused = false; });
                if (pendingSearchFocused) {
                    el.focus();
                    try { if (pendingSearchCaret != null) el.setSelectionRange(pendingSearchCaret, pendingSearchCaret); } catch(_) {}
                    // Refuerzo de foco para evitar pérdida cuando el valor está vacío
                    try {
                        requestAnimationFrame(() => {
                            const el3 = document.querySelector('#pending_assignment .pending-search-input');
                            if (el3) {
                                el3.focus();
                                try { if (pendingSearchCaret != null) el3.setSelectionRange(pendingSearchCaret, pendingSearchCaret); } catch(_) {}
                            }
                        });
                        setTimeout(() => {
                            const el4 = document.querySelector('#pending_assignment .pending-search-input');
                            if (el4) {
                                el4.focus();
                                try { if (pendingSearchCaret != null) el4.setSelectionRange(pendingSearchCaret, pendingSearchCaret); } catch(_) {}
                            }
                        }, 0);
                    } catch(_) {}
                }
            }
        }
        
        const translations = {
            noOrdersToOrganize: "{{ __('No hay órdenes o líneas de producción para organizar.') }}",
            organizingWithAIError: "{{ __('Error al organizar con IA:') }}",
            organizingWithAISuccess: "{{ __('Órdenes reorganizadas con IA.') }}",
            urgentOrder: "{{ __('Pedido Urgente') }}",
            day: "{{ __('día') }}",
            days: "{{ __('días') }}",
            urgentDeliveryPrefix: "{{ __('Urgente: Entrega en') }}",
            progress: "{{ __('Progreso') }}",
            noCustomer: "{{ __('Sin Cliente') }}",
            noDescription: "{{ __('Sin descripción') }}",
            unassigned: "{{ __('Assigned') }}",
            saving: "{{ __('Guardando...') }}",
            changesSaved: "{{ __('Cambios guardados') }}",
            errorSaving: "{{ __('Error al guardar. Revise la consola para más detalles.') }}",
            unknownError: "{{ __('Error desconocido.') }}",
            confirmTitle: "{{ __('¿Estás seguro?') }}",
            confirmText: "{{ __('Tienes cambios sin guardar que se perderán.') }}",
            confirmButton: "{{ __('Sí, salir') }}",
            cancelButton: "{{ __('Cancelar') }}",
            fullscreenError: "{{ __('No se pudo activar la pantalla completa.') }}",
            cardCountTitle: "{{ __('Número de tarjetas') }}",
            totalTimeTitle: "{{ __('Tiempo total teórico') }}"
        };
        
        const columns = {
            'pending_assignment': { id: 'pending_assignment', name: `{{__('Pendientes Asignación')}}`, items: [], color: '#9ca3af', productionLineId: null, type: 'status' },
            ...productionLinesData.reduce((acc, line) => {
                acc[`line_${line.id}`] = { 
                    id: `line_${line.id}`, 
                    name: line.name, 
                    items: [], 
                    color: '#3b82f6', 
                    productionLineId: line.id, 
                    type: 'production',
                    token: line.token // Añadimos el token de la línea de producción
                };
                return acc;
            }, {}),
            'final_states': { id: 'final_states', name: `{{__('Estados Finales')}}`, items: [], color: '#6b7280', productionLineId: null, type: 'final_states',
                subStates: [
                    { id: 'completed', name: `{{__('Finalizados')}}`, color: '#10b981', items: [] },
                    { id: 'paused', name: `{{__('Incidencias')}}`, color: '#f59e0b', items: [] },
                    { id: 'cancelled', name: `{{__('Cancelados')}}`, color: '#6b7280', items: [] }
                ]
            }
        };

        
        
        function isOrderUrgent(order) {
            try {
                if (!order) return false;
                // No marcar como urgente si la orden ya está finalizada/cancelada
                if (['completed', 'cancelled'].includes(order.status)) return false;

                // Urgencia por prioridad explícita
                const isPriority = order.is_priority === true || order.is_priority === 1;

                // Urgencia por fecha de entrega próxima (<= 5 días)
                let dueSoon = false;
                if (order.delivery_date) {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const deliveryDate = new Date(order.delivery_date);
                    deliveryDate.setHours(0, 0, 0, 0);
                    if (!isNaN(deliveryDate)) {
                        const diffTime = deliveryDate - today;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        dueSoon = diffDays <= 5;
                    }
                }

                return isPriority || dueSoon;
            } catch(_) {
                return false;
            }
        }

        // Función de organización con IA eliminada

        // --- 2. LÓGICA PRINCIPAL DE RENDERIZADO ---
        
        function distributeAndRender(shouldSort = true, callback = null) {
            // Capturar estado del input de búsqueda de Pendientes antes de re-renderizar
            capturePendingSearchState();
            const searchTerm = searchInput.value.trim().toLowerCase();
            
            let ordersToDisplay = searchTerm ? masterOrderList.filter(order => {
                // Búsqueda en campos básicos
                const orderIdMatch = String(order.order_id || '').toLowerCase().includes(searchTerm);
                const descripMatch = String(order.json?.descrip || '').toLowerCase().includes(searchTerm);
                const customerMatch = String(order.customerId || '').toLowerCase().includes(searchTerm);
                const processesMatch = String(order.processes_to_do || '').toLowerCase().includes(searchTerm);
                
                // Búsqueda en descripciones de artículos
                const articlesMatch = order.articles?.some(article => 
                    String(article.description || '').toLowerCase().includes(searchTerm)
                );
                
                return orderIdMatch || descripMatch || customerMatch || processesMatch || articlesMatch;
            }) : [...masterOrderList];

            if (shouldSort) {
                // Ordenar solo por el campo orden, sin considerar prioridad
                ordersToDisplay.sort((a, b) => {
                    return (a.orden || 0) - (b.orden || 0);
                });
            }

            Object.values(columns).forEach(column => {
                column.items = [];
                if (column.subStates) {
                    column.subStates.forEach(sub => { sub.items = []; });
                }
            });

            const distCounters = { final_states: 0, pending_assignment: 0 };
            ordersToDisplay.forEach(order => {
                let targetColumnKey = null;
                // Normalizar id de línea: aceptar camelCase y snake_case
                const normalizedLineId = (order.productionLineId != null && order.productionLineId !== '')
                    ? order.productionLineId
                    : (order.production_line_id != null && order.production_line_id !== '' ? order.production_line_id : null);
                if (['completed', 'paused', 'cancelled'].includes(order.status)) {
                    targetColumnKey = 'final_states';
                } else if (normalizedLineId) {
                    targetColumnKey = `line_${normalizedLineId}`;
                } else {
                    targetColumnKey = 'pending_assignment';
                }

                if (columns[targetColumnKey]) {
                    if (targetColumnKey === 'final_states') {
                        const subState = columns.final_states.subStates.find(s => s.id === order.status);
                        if (subState) subState.items.push(order);
                        distCounters.final_states++;
                    } else {
                        columns[targetColumnKey].items.push(order);
                        if (targetColumnKey === 'pending_assignment') distCounters.pending_assignment++;
                    }
                }
                // Log de diagnóstico para cuando debería ir a línea pero cae en pendientes
                if (targetColumnKey === 'pending_assignment' && (order.productionLineId || order.production_line_id)) {
                    console.warn('[Distribución] Orden con lineId pero en pendientes:', {
                        id: order.id,
                        status: order.status,
                        productionLineId: order.productionLineId,
                        production_line_id: order.production_line_id
                    });
                }
            });
            try {
                const prodKeys = Object.keys(columns).filter(k => k.startsWith('line_'));
                const perLine = prodKeys.reduce((acc, k) => { acc[k] = (columns[k].items||[]).length; return acc; }, {});
                console.log('[Kanban] Distribución:', { final_states: distCounters.final_states, pending: distCounters.pending_assignment, perLine });
            } catch(_) {}
            
            // Aplicar filtro solo a la columna Pendientes, según pendingSearchValue
            if (pendingSearchValue && columns.pending_assignment) {
                const term = pendingSearchValue.trim().toLowerCase();
                const matches = (order) => {
                    const orderIdMatch = String(order.order_id || '').toLowerCase().includes(term);
                    const descripMatch = String(order.json?.descrip || '').toLowerCase().includes(term);
                    const customerMatch = String(order.customerId || '').toLowerCase().includes(term);
                    const processesMatch = String(order.processes_to_do || '').toLowerCase().includes(term);
                    const articlesMatch = Array.isArray(order.articles_descriptions) && order.articles_descriptions.some(d => String(d||'').toLowerCase().includes(term));
                    return orderIdMatch || descripMatch || customerMatch || processesMatch || articlesMatch;
                };
                columns.pending_assignment.items = columns.pending_assignment.items.filter(matches);
            }

            // Render y restauración del estado del input
            renderBoard();
            restorePendingSearchState();

            // Actualizar estadísticas de la columna de pendientes después de renderizar
            const pendingColumn = document.getElementById('pending_assignment');
            if (pendingColumn) {
                updateColumnStats(pendingColumn);
            }
            
            if (callback) callback();
        }

        function renderBoard() {
            kanbanBoard.innerHTML = '';
            const fragment = document.createDocumentFragment();

            Object.values(columns).forEach(column => {
                const columnElement = createColumnElement(column);
                
                let allItems = (column.type === 'final_states') 
                    ? column.subStates.flatMap(sub => sub.items || []) 
                    : (column.items || []);
                
                let totalCards = allItems.length;
                let totalSeconds = allItems.reduce((sum, order) => sum + parseTimeToSeconds(order.theoretical_time), 0);
                
                const cardCountBadge = columnElement.querySelector('.card-count-badge');
                const timeSumBadge = columnElement.querySelector('.time-sum-badge');
                if (cardCountBadge) cardCountBadge.textContent = totalCards;
                if (timeSumBadge) timeSumBadge.innerHTML = `<i class="far fa-clock"></i> ${formatSecondsToTime(totalSeconds)}`;
                
                const appendCards = (items, container) => {
                    if (items && container) {
                        items.forEach(order => container.appendChild(createCardElement(order)));
                    }
                };

                // Ordenación automática por columna si está activada
                const sortItemsForColumn = (col, items) => {
                    let list = Array.isArray(items) ? [...items] : [];
                    if (isAutoSortEnabled()) {
                        try {
                            list.sort(compareOrders);
                            if (col.type === 'production') {
                                const idx = list.findIndex(o => o.status === 'in_progress');
                                if (idx > 0) {
                                    const [inProg] = list.splice(idx, 1);
                                    list.unshift(inProg);
                                }
                            }
                        } catch(e) { console.debug('Autosort falló, usando orden original', e); }
                    }
                    if (isGroupByCarEnabled()) {
                        try { list = compactByCar(list, col.type); } catch(e) { console.debug('Agrupación por carro falló', e); }
                    }
                    return list;
                };

                if (column.type === 'final_states') {
                    column.subStates.forEach(subState => {
                        const subContainer = columnElement.querySelector(`.final-state-section[data-state="${subState.id}"] .column-cards`);
                        const items = sortItemsForColumn(column, subState.items);
                        appendCards(items, subContainer);
                    });
                } else {
                    const container = columnElement.querySelector('.column-cards');
                    const items = sortItemsForColumn(column, column.items);
                    appendCards(items, container);
                }
                
                // Actualizar los tiempos acumulados para esta columna
               // updateAccumulatedTimes(columnElement);
                fragment.appendChild(columnElement);
            });
            kanbanBoard.appendChild(fragment);
            // Aplicar filtros de readiness tras renderizar las tarjetas
            try { applyReadinessFilters(); } catch (_) {}
            // Si la agrupación está activa, sincronizar a masterOrderList para permitir guardar
            if (isGroupByCarEnabled()) {
                setTimeout(function() {
                    try {
                        if (applyVisualOrderToMasterList()) {
                            hasUnsavedChanges = true;
                            scheduleUpdateUnsavedFlag();
                        }
                    } catch(e) {}
                }, 0);
            }
        }

        // --- 3. FUNCIONES DE DRAG & DROP ---
        
        function getDragAfterElement(container, y) {
            console.log('🔍 getDragAfterElement - Y:', y);
            
            const draggableElements = [...container.querySelectorAll('.kanban-card:not(.dragging)')];
            console.log('🔍 Elementos disponibles:', draggableElements.length);
            
            draggableElements.forEach((el, i) => {
                const box = el.getBoundingClientRect();
                const offset = y - box.top - (box.height * 0.8);
                console.log(`Elemento ${i} (ID: ${el.dataset.id}): top=${box.top}, height=${box.height}, offset=${offset}`);
            });
            
            const result = draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                // Zona de detección más amplia - 80% de la altura del elemento
                const offset = y - box.top - (box.height * 0.8);
                if (offset < 0 && offset > closest.offset) {
                    console.log(`✅ Nuevo closest: ${child.dataset.id} con offset ${offset}`);
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY });
            
            console.log('🎯 Resultado final:', result.element ? result.element.dataset.id : 'ninguno');
            return result.element;
        }

        function throttle(callback, delay) {
            let timeoutId;
            let lastArgs;
            let lastThis;
            let lastCallTime = 0;

            function throttled() {
                lastArgs = arguments;
                lastThis = this;
                const now = Date.now();

                if (now - lastCallTime >= delay) {
                    lastCallTime = now;
                    callback.apply(lastThis, lastArgs);
                } else {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(() => {
                        lastCallTime = Date.now();
                        callback.apply(lastThis, lastArgs);
                    }, delay - (now - lastCallTime));
                }
            }

            throttled.cancel = () => {
                clearTimeout(timeoutId);
            };

            return throttled;
        }

        function handleThrottledDragOver(event) {
            const container = event.target.closest('.column-cards');
            if (!container) {
                const columnEl = event.target.closest('.kanban-column');
                if (columnEl) {
                    const cardsContainer = columnEl.querySelector('.column-cards');
                    if (cardsContainer) {
                        processDragOver(event, cardsContainer);
                    }
                }
                return;
            } else {
                processDragOver(event, container);
            }
        }

        function handleDragStart(event) {
            console.log('🚀 HANDLE DRAG START');
            draggedCard = event.target.closest('.kanban-card');
            if (!draggedCard) {
                console.log('❌ No se encontró kanban-card');
                return;
            }
            console.log('✅ Drag card encontrada:', draggedCard.dataset.id);
            setTimeout(() => { if (draggedCard) draggedCard.classList.add('dragging'); }, 0);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedCard.dataset.id);
        }

        function handleDragEnd(event) {
            console.log('🏁 HANDLE DRAG END');
            if (draggedCard) draggedCard.classList.remove('dragging');
            draggedCard = null;
            
            // Limpiar caché de posición
            cachedDropPosition = null;
            cachedTargetContainer = null;
            
            document.querySelectorAll('.placeholder').forEach(p => p.remove());
            resetDropZones();
        }

        function dragOver(event) {
            event.preventDefault(); // Esencial para permitir el evento 'drop'
        }

        const throttledProcessDragOver = throttle(processDragOver, 100);

        function processDragOver(event) {
            console.log('🔄 DRAG OVER - Target:', event.target.tagName, event.target.className);
            event.preventDefault();
            if (!draggedCard) {
                console.log('❌ DRAG OVER - No hay draggedCard');
                return;
            }
            
            // Buscar el contenedor de tarjetas de forma más tolerante
            let targetCardsContainer = event.target.closest('.column-cards');
            
            // Si no encontramos el contenedor directamente, buscar en la columna completa
            if (!targetCardsContainer) {
                const targetColumn = event.target.closest('.kanban-column, .final-state-section');
                if (targetColumn) {
                    targetCardsContainer = targetColumn.querySelector('.column-cards');
                    console.log('🔄 DRAG OVER - Usando columna completa');
                }
            }
            
            if (!targetCardsContainer) {
                console.log('❌ DRAG OVER - No se encontró contenedor');
                return;
            }
            
            console.log('✅ DRAG OVER - Contenedor encontrado');
            
            const columnTarget = targetCardsContainer.closest('.kanban-column, .final-state-section');
            if (columnTarget) {
                resetDropZones();
                columnTarget.classList.add('drag-over');
            }
            
            // Limpiar placeholders existentes
            document.querySelectorAll('.placeholder').forEach(p => p.remove());
            
            // Verificar si hay una tarjeta "en curso" en esta columna y si estamos arrastrando sobre ella
            const hasInProgressCard = targetCardsContainer.querySelector('.kanban-card[data-status="1"]');
            const draggingOverInProgress = event.target.closest('.kanban-card[data-status="1"]') !== null;
            
            let afterElement;
            
            // Si estamos arrastrando sobre una tarjeta EN CURSO o hay una EN CURSO en la columna
            // y la tarjeta arrastrada no es la misma tarjeta en curso
            if ((draggingOverInProgress || hasInProgressCard) && draggedCard && draggedCard.dataset.status !== '1') {
                console.log('🛡️ Protección activada: Arrastrando sobre/cerca de tarjeta EN CURSO');
                
                // Obtener todas las tarjetas en la columna
                const allCards = Array.from(targetCardsContainer.querySelectorAll('.kanban-card'));
                
                if (allCards.length > 0) {
                    // Forzar a que se coloque después de la última tarjeta (posición final + 1)
                    afterElement = allCards[allCards.length - 1];
                    console.log('📌 Forzando posición al FINAL ABSOLUTO (última + 1)');
                } else {
                    // Si no hay tarjetas, colocar al principio
                    afterElement = null;
                    console.log('📌 No hay tarjetas en la columna, colocando al principio');
                }
            } else {
                // Comportamiento normal - calcular posición basada en el cursor
                afterElement = getDragAfterElement(targetCardsContainer, event.clientY);
            }
            
            // 🎯 CACHEAR la posición detectada para usar en drop
            cachedDropPosition = {
                afterElement: afterElement,
                afterElementId: afterElement ? parseInt(afterElement.dataset.id) : null,
                clientY: event.clientY
            };
            cachedTargetContainer = targetCardsContainer;
            console.log('💾 CACHEADO - afterElement:', cachedDropPosition.afterElementId || 'ninguno');
            
            const placeholder = document.createElement('div');
            placeholder.className = 'placeholder';
            placeholder.innerHTML = '⬇️ Soltar aquí ⬇️';
            placeholder.style.height = `${Math.max(draggedCard.offsetHeight, 120)}px`;
            
            if (afterElement) {
                targetCardsContainer.insertBefore(placeholder, afterElement);
            } else {
                targetCardsContainer.appendChild(placeholder);
            }
        }

        function drop(event) {
            event.preventDefault();
            hasUnsavedChanges = true;
            
            console.log('🎯 DROP INICIADO');
            console.log('Event target:', event.target);
            console.log('Event target classes:', event.target.className);
            
            if (!draggedCard) {
                console.log('❌ FALLO: No hay draggedCard');
                return;
            }

            const cardId = parseInt(draggedCard.dataset.id);
            const orderObj = masterOrderList.find(o => o.id === cardId);
            
            console.log('Card ID:', cardId);
            console.log('Order encontrada:', !!orderObj);
            
            // Búsqueda SUPER tolerante del contenedor objetivo
            let targetCardsContainer = null;
            let targetColumn = null;
            
            // Método 1: Buscar contenedor de tarjetas directamente
            targetCardsContainer = event.target.closest('.column-cards');
            if (targetCardsContainer) {
                console.log('✅ Método 1: Encontrado contenedor directo');
            } else {
                console.log('❌ Método 1: No encontrado contenedor directo');
            }
            
            // Método 2: Si no funciona, buscar cualquier columna cercana
            if (!targetCardsContainer) {
                targetColumn = event.target.closest('.kanban-column, .final-state-section');
                console.log('Columna encontrada en método 2:', !!targetColumn);
                if (targetColumn) {
                    targetCardsContainer = targetColumn.querySelector('.column-cards');
                    if (targetCardsContainer) {
                        console.log('✅ Método 2: Encontrado via columna');
                    } else {
                        console.log('❌ Método 2: Columna encontrada pero sin .column-cards');
                    }
                } else {
                    console.log('❌ Método 2: No se encontró columna');
                }
            }
            
            // Método 3: Si aún no funciona, buscar en el elemento padre
            if (!targetCardsContainer) {
                console.log('🔍 Método 3: Buscando en elementos padre...');
                let element = event.target;
                let attempts = 0;
                while (element && element !== document.body && attempts < 10) {
                    attempts++;
                    console.log(`Intento ${attempts}:`, element.tagName, element.className);
                    const column = element.querySelector('.kanban-column, .final-state-section');
                    if (column) {
                        targetCardsContainer = column.querySelector('.column-cards');
                        if (targetCardsContainer) {
                            console.log('✅ Método 3: Encontrado via elemento padre');
                            break;
                        }
                    }
                    element = element.parentElement;
                }
                if (!targetCardsContainer) {
                    console.log('❌ Método 3: No encontrado después de', attempts, 'intentos');
                }
            }
            
            // Método 4: Como último recurso, usar la columna que tiene drag-over
            if (!targetCardsContainer) {
                console.log('🔍 Método 4: Buscando columna con drag-over...');
                const dragOverColumn = document.querySelector('.kanban-column.drag-over, .final-state-section.drag-over');
                console.log('Columna drag-over encontrada:', !!dragOverColumn);
                if (dragOverColumn) {
                    targetCardsContainer = dragOverColumn.querySelector('.column-cards');
                    if (targetCardsContainer) {
                        console.log('✅ Método 4: Encontrado via drag-over');
                    } else {
                        console.log('❌ Método 4: Columna drag-over sin .column-cards');
                    }
                } else {
                    console.log('❌ Método 4: No hay columnas con drag-over');
                }
            }
            
            document.querySelectorAll('.placeholder').forEach(p => p.remove());

            // Solo fallar si realmente no encontramos NADA
            if (!orderObj) {
                console.log('❌ DROP FALLIDO: No se encontró orderObj para cardId:', cardId);
                handleDragEnd();
                return;
            }
            
            if (!targetCardsContainer) {
                console.log('❌ DROP FALLIDO: No se encontró contenedor objetivo después de 4 métodos');
                console.log('Todas las columnas disponibles:');
                document.querySelectorAll('.kanban-column, .final-state-section').forEach((col, i) => {
                    console.log(`Columna ${i}:`, col.className, 'tiene .column-cards:', !!col.querySelector('.column-cards'));
                });
                handleDragEnd();
                return;
            }

            console.log('✅ DROP EXITOSO: Contenedor encontrado');
            const targetColumnEl = targetCardsContainer.closest('.kanban-column, .final-state-section');
            const targetIsFinalState = targetColumnEl.classList.contains('final-state-section');
            const columnData = columns[targetColumnEl.id];
            const targetIsProduction = columnData && columnData.type === 'production';
            
            if (targetIsFinalState) {
                orderObj.status = targetColumnEl.dataset.state;
                orderObj.productionLineId = null;
            } else if (targetIsProduction) {
                const columnItems = columns[targetColumnEl.id].items;
                const hasInProgress = columnItems.some(item => item.status === 'in_progress' && item.id !== cardId);
                orderObj.status = hasInProgress ? 'pending' : 'in_progress';
                orderObj.productionLineId = columnData.productionLineId;
            } else { 
                orderObj.status = 'pending';
                orderObj.productionLineId = null;
            }

            console.log('🎯 Usando posición cacheada en lugar de recalcular...');
            console.log('💾 Posición cacheada:', cachedDropPosition ? cachedDropPosition.afterElementId : 'ninguna');
            
            let afterElement = null;
            let afterElementId = null;
            
            // Verificar si hay una tarjeta "en curso" en la columna destino
            const hasInProgressCard = targetCardsContainer.querySelector('.kanban-card[data-status="1"]');
            
            // Si hay una tarjeta en curso y la tarjeta arrastrada no es la misma tarjeta en curso
            if (hasInProgressCard && draggedCard && draggedCard.dataset.status !== '1') {
                console.log('🛡️ DROP - Protección activada: Hay una tarjeta EN CURSO en esta columna');
                
                // Obtener todas las tarjetas en la columna
                const allCards = Array.from(targetCardsContainer.querySelectorAll('.kanban-card'));
                
                if (allCards.length > 0) {
                    // Forzar a que se coloque después de la última tarjeta (posición final + 1)
                    const lastCard = allCards[allCards.length - 1];
                    afterElementId = parseInt(lastCard.dataset.id);
                    console.log('📌 DROP - Forzando posición al FINAL ABSOLUTO (después de ID:', afterElementId, ')');
                } else {
                    // Si no hay tarjetas, colocar al principio
                    afterElementId = null;
                    console.log('📌 DROP - No hay tarjetas en la columna, colocando al principio');
                }
            } else if (cachedDropPosition && cachedDropPosition.afterElementId) {
                // Usar posición cacheada si está disponible y no hay protección activa
                afterElementId = cachedDropPosition.afterElementId;
                console.log('✅ Usando afterElement cacheado:', afterElementId);
            } else {
                console.log('⚠️ No hay posición cacheada, insertando al final');
            }
            
            // Eliminar de posición original
            const oldMasterIndex = masterOrderList.findIndex(o => o.id === cardId);
            if (oldMasterIndex > -1) masterOrderList.splice(oldMasterIndex, 1);
            
            // Verificar si la tarjeta arrastrada es "en curso"
            const isInProgressCard = draggedCard && draggedCard.dataset.status === '1';
            
            // CASO ESPECIAL: Si hay una tarjeta en curso y la tarjeta arrastrada NO es la tarjeta en curso
            if (hasInProgressCard && !isInProgressCard) {
                console.log('🚨 PROTECCIÓN ESPECIAL: Forzando posición al final absoluto');
                
                // 1. Identificar todas las tarjetas de esta línea de producción
                const lineId = orderObj.productionLineId;
                const cardsInSameLine = masterOrderList.filter(o => o.productionLineId === lineId);
                
                // 2. Encontrar la tarjeta en curso (debe estar primera)
                const inProgressCard = cardsInSameLine.find(o => o.status === 'in_progress');
                
                if (inProgressCard) {
                    console.log('📍 Tarjeta EN CURSO encontrada:', inProgressCard.id);
                    
                    // 3. Agregar la tarjeta arrastrada al final absoluto
                    masterOrderList.push(orderObj);
                    console.log('📌 INSERTADA AL FINAL ABSOLUTO');
                } else {
                    // Si por alguna razón no hay tarjeta en curso (no debería pasar), usar lógica normal
                    if (afterElementId) {
                        const newMasterIndex = masterOrderList.findIndex(o => o.id === afterElementId);
                        if (newMasterIndex > -1) {
                            masterOrderList.splice(newMasterIndex, 0, orderObj);
                        } else {
                            masterOrderList.push(orderObj);
                        }
                    } else {
                        masterOrderList.push(orderObj);
                    }
                }
            } else {
                // CASO NORMAL: Usar lógica estándar de posicionamiento
                if (afterElementId) {
                    // Insertar ANTES del afterElement
                    const newMasterIndex = masterOrderList.findIndex(o => o.id === afterElementId);
                    console.log('Insertando en índice:', newMasterIndex, 'antes de tarjeta:', afterElementId);
                    if (newMasterIndex > -1) {
                        masterOrderList.splice(newMasterIndex, 0, orderObj);
                    } else {
                        // Si no encuentra el afterElement en masterOrderList, agregar al final
                        console.log('No se encontró afterElement en masterOrderList, agregando al final');
                        masterOrderList.push(orderObj);
                    }
                } else {
                    // No hay afterElement, insertar al final
                    console.log('No hay afterElement, insertando al final');
                    masterOrderList.push(orderObj);
                }
            }
            
            // Lógica especial para columnas de producción
            if (targetIsProduction) {
                const targetItems = masterOrderList.filter(o => o.productionLineId === columnData.productionLineId);
                
                // 1. Manejar tarjeta EN CURSO (siempre al inicio)
                const inProgressItem = targetItems.find(o => o.status === 'in_progress');
                if (inProgressItem) {
                    console.log('🔄 Reordenando: Moviendo tarjeta EN CURSO al inicio de la columna');
                    
                    // Eliminar la tarjeta en curso de su posición actual
                    const itemIndex = masterOrderList.findIndex(o => o.id === inProgressItem.id);
                    if (itemIndex > -1) masterOrderList.splice(itemIndex, 1);

                    // Insertar al inicio del grupo
                    const firstIndexOfGroup = masterOrderList.findIndex(o => o.productionLineId === columnData.productionLineId);
                    if (firstIndexOfGroup > -1) {
                        masterOrderList.splice(firstIndexOfGroup, 0, inProgressItem);
                        console.log('✅ Tarjeta EN CURSO colocada al INICIO del grupo');
                    } else {
                        masterOrderList.push(inProgressItem);
                        console.log('⚠️ No se encontró el grupo, agregando tarjeta EN CURSO al final');
                    }
                    
                    // 2. Si la tarjeta que acabamos de arrastrar NO es EN CURSO, asegurarnos que esté al final
                    if (orderObj.id !== inProgressItem.id && orderObj.status !== 'in_progress') {
                        console.log('🛡️ PROTECCIÓN UNIFICADA: Verificando posición de tarjeta arrastrada');
                        
                        // Buscar la posición actual de la tarjeta arrastrada
                        const draggedIndex = masterOrderList.findIndex(o => o.id === orderObj.id);
                        
                        // Si está en una posición incorrecta (antes que la tarjeta EN CURSO), moverla al final
                        if (draggedIndex !== -1 && draggedIndex <= itemIndex) {
                            console.log('⚠️ Tarjeta arrastrada en posición incorrecta, moviendo al final');
                            
                            // Eliminar de su posición actual
                            masterOrderList.splice(draggedIndex, 1);
                            
                            // Agregar al final absoluto
                            masterOrderList.push(orderObj);
                            console.log('📌 Tarjeta reubicada al FINAL ABSOLUTO');
                        }
                    }
                }
            }

            distributeAndRender(false, () => {
                // Recalcular posiciones
                recalculatePositions();
                
                // Actualizar los tiempos acumulados en todas las columnas
                document.querySelectorAll('.kanban-column').forEach(column => {
                   // updateAccumulatedTimes(column);
                });
                
                // También actualizar en secciones de estados finales
                document.querySelectorAll('.final-state-section').forEach(section => {
                   // updateAccumulatedTimes(section);
                });
                
                // Autoguardado: guardar cambios automáticamente después de cada drop
                document.getElementById('saveChangesBtn').click();
            });
        }

        function resetDropZones() {
             document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
             // Pequeño delay antes de limpiar placeholders para dar más tiempo
             setTimeout(() => {
                 if (!draggedCard) {
                     document.querySelectorAll('.placeholder').forEach(p => p.remove());
                 }
             }, 100);
        }

        // --- 4. FUNCIONES PARA CREAR ELEMENTOS DEL DOM ---
        
        function createColumnElement(column) {
            const columnElement = document.createElement('div');
            columnElement.className = 'kanban-column';
            columnElement.id = column.id;
            
            // Obtener el estado de la línea si existe
            let lineStatusHtml = '';
            let headerStatusClass = '';
            
            // Solo aplicar a columnas que son líneas de producción (no a pendientes ni estados finales)
            if (column.productionLineId && productionLineStatuses[column.productionLineId]) {
                const lineStatus = productionLineStatuses[column.productionLineId];
                let statusIcon = '';
                let statusText = '';
                let statusClass = '';
                
                if (lineStatus.type === 'shift' && lineStatus.action === 'start' || 
                    lineStatus.type === 'stop' && lineStatus.action === 'end') {
                    // Línea arrancada
                    statusIcon = '<i class="fas fa-play-circle line-status-running"></i>';
                    statusText = 'En Marcha';
                    statusClass = 'line-status-running';
                    headerStatusClass = 'column-header-running';
                } else if (lineStatus.type === 'stop' && lineStatus.action === 'start') {
                    // Línea en pausa
                    statusIcon = '<i class="fas fa-pause-circle line-status-paused"></i>';
                    statusText = 'En Pausa';
                    statusClass = 'line-status-paused';
                    headerStatusClass = 'column-header-paused';
                } else if (lineStatus.type === 'shift' && lineStatus.action === 'end') {
                    // Línea parada
                    statusIcon = '<i class="fas fa-stop-circle line-status-stopped"></i>';
                    statusText = 'Parada';
                    statusClass = 'line-status-stopped';
                    headerStatusClass = 'column-header-stopped';
                }
                
                if (statusText) {
                    // Estado de línea
                    lineStatusHtml = `
                        <div class="line-status-indicator ${statusClass}">
                            ${statusIcon} <span>${statusText}</span>
                        </div>
                    `;
                    
                    // Añadir información del operario si está disponible
                    if (lineStatus.operator_name) {
                        lineStatusHtml += `
                            <div class="line-operator">
                                <i class="fas fa-user"></i> ${lineStatus.operator_name}
                            </div>
                        `;
                    }
                }
            }
            
            let headerStatsHtml = `
                <div class="column-header-stats">
                    <span class="card-count-badge" title="${translations.cardCountTitle}">0</span>
                    <span class="time-sum-badge ms-2" title="${translations.totalTimeTitle}"><i class="far fa-clock"></i> 00:00:00</span>
                    <span class="column-menu-toggle ms-2" title="Opciones" data-column-id="${column.id}" data-line-id="${column.productionLineId || ''}" data-line-name="${column.name}" data-line-token="${column.token || ''}" style="cursor: pointer;"><i class="fas fa-ellipsis-v"></i></span>
                </div>
            `;
            
            // Preparar campo de búsqueda específico para la columna Pendientes Asignación
            let searchFieldHtml = '';
            if (column.id === 'pending_assignment') {
                searchFieldHtml = `
                    <div class="column-search-container mt-2">
                        <div class="position-relative">
                            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-2 text-gray-400"></i>
                            <input type="text" class="form-control ps-4 pending-search-input" 
                                style="height: 38px;" placeholder="{{ __('Buscar en pendientes...') }}">
                        </div>
                    </div>
                `;
            }
            
            let innerHTML;
            if (column.type === 'final_states') {
                innerHTML = `<div class="column-header" style="border-left: 4px solid ${column.color};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="column-title">${column.name}</h3>
                                    ${headerStatsHtml}
                                </div>
                             </div>
                             <div class="final-states-container">
                                 ${column.subStates.map(subState => `
                                     <div class="final-state-section" data-state="${subState.id}" style="border-left-color: ${subState.color};">
                                         <div class="final-state-header"><span class="final-state-title" style="color: ${subState.color};">${subState.name}</span></div>
                                         <div class="column-cards"></div>
                                     </div>`).join('')}
                             </div>`;
                columnElement.innerHTML = innerHTML;
                const cardsContainer = columnElement.querySelector('.column-cards');

                // Listeners en el contenedor de tarjetas
                if (cardsContainer) {
                    cardsContainer.addEventListener('dragover', dragOver);
                    cardsContainer.addEventListener('dragover', throttledProcessDragOver);
                    cardsContainer.addEventListener('dragleave', resetDropZones);
                    cardsContainer.addEventListener('drop', drop);
                }

                // Listeners en toda la columna para un área de drop más grande
                columnElement.addEventListener('dragover', dragOver);
                columnElement.addEventListener('dragover', throttledProcessDragOver);
                columnElement.addEventListener('dragleave', resetDropZones);
                columnElement.addEventListener('drop', drop);
            } else if (column.productionLineId) {
                // Columnas de líneas de producción con información completa
                innerHTML = `<div class="column-header ${headerStatusClass}" style="border-left: 4px solid ${column.color};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="column-title">${column.name}</h3>
                                    ${headerStatsHtml}
                                </div>
                                <!-- Primera línea: estado de línea y operador (siempre presente) -->
                                <div class="header-line-1">
                                    <div class="line-status-indicator">
                                        <i class="fas fa-circle"></i>
                                        <span>Estado</span>
                                    </div>
                                    <div class="line-operator">
                                        <i class="fas fa-user"></i>
                                        <span></span>
                                    </div>
                                </div>
                                <!-- Segunda línea: estado de planificación (siempre presente) -->
                                <div class="header-line-2">
                                    <div class="line-schedule">
                                        <i class="fas fa-calendar"></i>
                                        <span></span>
                                    </div>
                                </div>
                             </div>
                             ${searchFieldHtml}
                             <div class="column-cards"></div>`;
            } else {
                // Columnas fijas como "Pendientes de asignar"
                innerHTML = `<div class="column-header" style="border-left: 4px solid ${column.color};">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="column-title">${column.name}</h3>
                                    ${headerStatsHtml}
                                </div>
                             </div>
                             ${searchFieldHtml}
                             <div class="column-cards"></div>`;
            }
            
            // Aplicar el HTML a la columna
            columnElement.innerHTML = innerHTML;
            const cardsContainer = columnElement.querySelector('.column-cards');

            // Listeners en el contenedor de tarjetas
            if (cardsContainer) {
                cardsContainer.addEventListener('dragover', dragOver);
                cardsContainer.addEventListener('dragover', throttledProcessDragOver);
                cardsContainer.addEventListener('dragleave', resetDropZones);
                cardsContainer.addEventListener('drop', drop);
            }

            // Listeners en toda la columna para un área de drop más grande
            columnElement.addEventListener('dragover', dragOver);
            columnElement.addEventListener('dragover', throttledProcessDragOver);
            columnElement.addEventListener('dragleave', resetDropZones);
            columnElement.addEventListener('drop', drop);
            // Inicializar header de línea de producción con estado actual para evitar parpadeos
            if (column.productionLineId) {
                try { renderColumnStatusIndicator(column, columnElement); } catch(_) {}
            }
            
            return columnElement;
        }

        function createCardElement(order) {
            const card = document.createElement('div');
            // Clase base para todas las tarjetas
            card.className = 'kanban-card collapsed';
            
            // Añadir clase para tarjetas prioritarias
            if (order.is_priority === true || order.is_priority === 1) {
                card.classList.add('priority-order');
            }
            card.dataset.id = order.id;
            card.draggable = true;
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);

            card.style.borderLeftColor = order.statusColor || '#6b7280';
            
            const createdAtFormatted = new Date(order.created_at).toLocaleDateString();
            const deliveryDateFormatted = order.delivery_date ? new Date(order.delivery_date).toLocaleDateString() : '';
            const fechaPedidoErpFormatted = order.fecha_pedido_erp ? new Date(order.fecha_pedido_erp).toLocaleDateString() : '';
            const processDescription = '{{ $process->description }}';

            let urgencyIconHtml = '';
            let stockIconHtml = '';
            let priorityIconHtml = '';
            
            // Triángulo rojo para órdenes urgentes
            if (isOrderUrgent(order)) {
                card.classList.add('urgent');
                const titleText = translations.urgentOrder;
                urgencyIconHtml = `<span class="ms-2" title="${titleText}"><i class="fas fa-exclamation-triangle text-danger"></i></span>`;
            }
            
            // Triángulo azul para órdenes sin stock
            if (order.has_stock === 0) {
                const stockTitleText = 'Sin stock de materiales';
                stockIconHtml = `<span class="ms-2" title="${stockTitleText}"><i class="fas fa-exclamation-triangle text-primary"></i></span>`;
            }
            
            // Triángulo amarillo para órdenes prioritarias
            if (order.is_priority === true || order.is_priority === 1) {
                const priorityTitleText = 'Orden prioritaria';
                priorityIconHtml = `<span class="ms-2" title="${priorityTitleText}"><i class="fas fa-exclamation-triangle text-warning"></i></span>`;
            }

            // Grupo eliminado - ya no es necesario
            
            const countProcesses = (processString) => {
                if (!processString || typeof processString !== 'string') return 0;
                return processString.split(',').filter(p => p.trim() !== '').length;
            };

            const processesDoneCount = countProcesses(order.processes_done);
            const processesToDoCount = countProcesses(order.processes_to_do);
            const totalProcesses = processesDoneCount + processesToDoCount;
            const progressPercentage = totalProcesses > 0 ? (processesDoneCount / totalProcesses) * 100 : 0;
            
            const progressHtml = `
                <div class="progress-container">
                    <div class="d-flex justify-content-between text-xs text-muted mb-1">
                        <span>${translations.progress}</span>
                        <span>${processesDoneCount} / ${totalProcesses}</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: ${progressPercentage}%;" role="progressbar"></div>
                    </div>
                </div>`;

            const doneProcessesList = order.processes_done ? order.processes_done.split(',').filter(p => p.trim() !== '') : [];
            const toDoProcessesList = order.processes_to_do ? order.processes_to_do.split(',').filter(p => p.trim() !== '') : [];

            const doneHtml = doneProcessesList.map(p => `<span class="process-tag process-tag-done">${p.trim()}</span>`).join('');
            const toDoHtml = toDoProcessesList.map(p => `<span class="process-tag process-tag-pending">${p.trim()}</span>`).join('');

            const processListHtml = `
                <div class="mt-2">
                    <div class="process-list">
                        ${doneHtml}${toDoHtml}
                    </div>
                </div>`;

            // Generar HTML para las descripciones de artículos
            let articlesHtml = '';
            if (order.articles_descriptions && order.articles_descriptions.length > 0) {
                const articlesList = order.articles_descriptions.map(desc => `<span class="badge bg-secondary me-1 mb-1">${desc}</span>`).join('');
                articlesHtml = `<div class="text-sm mb-2"><strong>Artículos:</strong><br>${articlesList}</div>`;
            }

            // Generar HTML para la descripción solo si existe y no es el texto por defecto
            let descripHtml = '';
            const descripText = order.json?.descrip || '';
            if (descripText && descripText !== translations.noDescription && descripText.trim() !== '') {
                descripHtml = `<div class="text-sm mb-2">${descripText}</div>`;
            }

            const statusBadgeHtml = `<span class="badge" style="background-color: ${order.statusColor || '#6b7280'}; color: white;">${(order.status || 'PENDING').replace(/_/g, ' ').toUpperCase()}</span>`;
            
            const latestAfter = getLatestAfterItem(order);
            const carIconHtml = latestAfter ? `<span class="ms-2" title="Carro ${latestAfter.barcoder_id || ''}"><i class="fas fa-dolly"></i></span>` : '';
            const barcodeBadgeHtml = (latestAfter && latestAfter.barcode) ? `<span class="badge bg-light text-dark border ms-2"><i class="fas fa-barcode me-1"></i>${latestAfter.barcode}</span>` : '';

            card.innerHTML = `
                <div class="kanban-card-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div class="me-2" style="flex-grow: 1;">
                        <div class="fw-bold text-sm d-flex align-items-center">#${order.order_id}${urgencyIconHtml}${stockIconHtml}${priorityIconHtml}${carIconHtml}${barcodeBadgeHtml}</div>
                        <div class="text-xs fw-bold text-muted mt-1">${order.customerId || translations.noCustomer}</div>
                        ${processDescription ? `<div class="text-xs text-muted mt-1">${processDescription}</div>` : ''}
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="text-xs text-muted"><i class="far fa-calendar-alt me-1" title="Fecha de creación tarjeta"></i>${createdAtFormatted}</div>
                            ${deliveryDateFormatted ? `<div class="text-xs text-danger fw-bold ms-2"><i class="fas fa-truck me-1" title="Fecha de entrega en instalación cliente"></i>${deliveryDateFormatted}</div>` : ''}
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="text-xs text-muted">
                                <i class="fas fa-calendar-check me-1" title="Fecha creación pedido en ERP"></i>${fechaPedidoErpFormatted ? fechaPedidoErpFormatted : 'Sin fecha ERP'}
                            </div>
                        </div>
                        ${order.ready_after_datetime ? `
                        ${(() => {
                            const ready = isReady(order.ready_after_datetime);
                            const target = new Date((typeof order.ready_after_datetime === 'string') ? order.ready_after_datetime.replace(' ', 'T') : order.ready_after_datetime);
                            const rel = isNaN(target) ? '' : formatRelativeEs(target.getTime() - Date.now());
                            const abs = formatDateTimeEs(order.ready_after_datetime);
                            if (!ready) {
                                return `
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <div class="text-xs text-warning" title="Disponible a partir de">
                                        <i class="fas fa-lock me-1"></i>Disponible en ${rel}${abs ? ` · ${abs}` : ''}
                                    </div>
                                </div>`;
                            } else {
                                return `
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <div class="text-xs text-success" title="Disponible desde">
                                        <i class="fas fa-unlock me-1"></i>Ready desde ${abs}
                                    </div>
                                </div>`;
                            }
                        })()}
                        ` : ''}
                        ${order.estimated_start_datetime || order.estimated_end_datetime ? `
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            ${order.estimated_start_datetime ? `
                            <div class="text-xs text-primary">
                                <i class="fas fa-hourglass-start me-1" title="Fecha estimada de inicio"></i>${new Date(order.estimated_start_datetime).toLocaleString('es-ES', {dateStyle: 'short', timeStyle: 'short'})}
                            </div>` : ''}
                            ${order.estimated_end_datetime ? `
                            <div class="text-xs text-success ms-2">
                                <i class="fas fa-hourglass-end me-1" title="Fecha estimada de fin"></i>${new Date(order.estimated_end_datetime).toLocaleString('es-ES', {dateStyle: 'short', timeStyle: 'short'})}
                            </div>` : ''}
                        </div>` : ''}
                    </div>
                    <div class="d-flex flex-column align-items-end">
                        <span class="card-menu" role="button" data-order-id="${order.id}"><i class="fas fa-ellipsis-h"></i></span>
                        <div class="mt-2">${statusBadgeHtml}</div>
                    </div>
                </div>
                <div class="kanban-card-body">
                    ${descripHtml}
                    ${articlesHtml}
                    ${progressHtml}
                    ${processListHtml}
                    <div class="d-flex justify-content-between align-items-center mt-3">
                         <div class="d-flex align-items-center flex-wrap">
                            <span class="d-flex align-items-center me-3"><i class="fas fa-box text-muted me-1"></i><span class="text-xs">${order.box || 0}</span></span>
                            <span class="d-flex align-items-center me-3"><i class="fas fa-cubes text-muted me-1"></i><span class="text-xs">${order.units || 0}</span></span>
                            <span class="d-flex align-items-center me-3"><i class="fas fa-pallet text-muted me-1" title="Número de Palets"></i><span class="text-xs">${order.number_of_pallets || 0}</span></span>
                             <div class="d-flex flex-column me-2">
                                <span class="d-flex align-items-center"><i class="far fa-clock text-muted me-1" title="Tiempo teórico"></i><span class="text-xs">${order.theoretical_time || 'N/A'} </span></span>
                             </div>
                             <div class="d-flex flex-column">
                                <span class="d-flex align-items-center accumulated-time-badge ${order.accumulated_time > 0 ? '' : 'd-none'}" title="Tiempo de ocupación máquina"> <i class="fas fa-hourglass-half text-muted me-1"></i><span class="text-xs">${formatSecondsToTime(order.accumulated_time || 0)}</span></span>
                             </div>
                         </div>
                    </div>

                </div>
`;
            return card;
        }
        
        function parseTimeToSeconds(timeStr = "00:00:00") {
            if (!timeStr || typeof timeStr !== 'string' || !timeStr.match(/^\d{2}:\d{2}:\d{2}$/)) {
                return 0;
            }
            const parts = timeStr.split(':').map(Number);
            return parts[0] * 3600 + parts[1] * 60 + parts[2];
        }

        function formatSecondsToTime(totalSeconds) {
            if (isNaN(totalSeconds) || totalSeconds < 0) {
                return "00:00:00";
            }
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = Math.floor(totalSeconds % 60);
            return [hours, minutes, seconds].map(v => v.toString().padStart(2, '0')).join(':');
        }
        // isOrderUrgent se define más arriba basándose en la fecha de entrega (<= 5 días)
        // Formatea fecha/hora con tolerancia a cadenas tipo 'YYYY-MM-DD HH:MM:SS'
        function formatDateTimeEs(dateInput) {
            if (!dateInput) return '';
            try {
                let d = new Date(dateInput);
                if (isNaN(d)) {
                    // Intento: reemplazar espacio por 'T' para habilitar parsing local
                    if (typeof dateInput === 'string') {
                        const fixed = dateInput.replace(' ', 'T');
                        d = new Date(fixed);
                    }
                }
                if (isNaN(d)) {
                    console.warn('[Kanban] Fecha inválida recibida:', dateInput);
                    return String(dateInput);
                }
                return d.toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
            } catch (e) {
                console.warn('[Kanban] Error formateando fecha:', dateInput, e);
                return String(dateInput);
            }
        }
        
        // Función para calcular el tiempo acumulado de las tarjetas por encima
        function updateAccumulatedTimes(columnElement) {
            const cards = columnElement.querySelectorAll('.column-cards > .kanban-card');
            let accumulatedTime = 0;
            
            // Recorremos las tarjetas de arriba a abajo
            cards.forEach((card) => {
                const orderId = parseInt(card.dataset.id);
                const order = masterOrderList.find(o => o.id === orderId);
                
                if (order) {
                    // Guardamos el tiempo acumulado hasta esta tarjeta
                    card.dataset.accumulatedTime = accumulatedTime;
                    
                    // Actualizamos el elemento que muestra el tiempo acumulado
                    const accTimeBadge = card.querySelector('.accumulated-time-badge');
                    if (accTimeBadge) {
                        accTimeBadge.innerHTML = `<i class="fas fa-hourglass-half text-muted me-1"></i><span class="text-xs">${formatSecondsToTime(accumulatedTime)}</span>`;
                        
                        // Solo mostramos el badge si hay tiempo acumulado
                        if (accumulatedTime > 0) {
                            accTimeBadge.classList.remove('d-none');
                        } else {
                            accTimeBadge.classList.add('d-none');
                        }
                    }
                    
                    // Sumamos el tiempo de esta tarjeta para la siguiente
                    accumulatedTime += parseTimeToSeconds(order.theoretical_time || '00:00:00');
                }
            });
            
            // También actualizar en secciones de estados finales
            const finalStateSections = columnElement.querySelectorAll('.final-state-section .column-cards');
            finalStateSections.forEach(section => {
                let sectionAccumulatedTime = 0;
                const sectionCards = section.querySelectorAll('.kanban-card');
                
                sectionCards.forEach((card) => {
                    const orderId = parseInt(card.dataset.id);
                    const order = masterOrderList.find(o => o.id === orderId);
                    
                    if (order) {
                        card.dataset.accumulatedTime = sectionAccumulatedTime;
                        
                        const accTimeBadge = card.querySelector('.accumulated-time-badge');
                        if (accTimeBadge) {
                            accTimeBadge.innerHTML = `<i class="fas fa-hourglass-half text-muted me-1"></i><span class="text-xs">${formatSecondsToTime(sectionAccumulatedTime)}</span>`;
                            
                            if (sectionAccumulatedTime > 0) {
                                accTimeBadge.classList.remove('d-none');
                            } else {
                                accTimeBadge.classList.add('d-none');
                            }
                        }
                        
                        sectionAccumulatedTime += parseTimeToSeconds(order.theoretical_time || '00:00:00');
                    }
                });
            });
        }

        // --- 5. OBTENER ESTADO DE LÍNEAS DE PRODUCCIÓN ---
        
        function fetchProductionLineStatuses() {
            // Almacenar el estado anterior para comparación
            const previousStatuses = JSON.parse(JSON.stringify(productionLineStatuses || {}));
            
            // Usar la URL base del sitio actual para evitar problemas con dominios
            const baseUrl = window.location.origin;
            // Incluir el token del cliente en la URL
            fetch(`${baseUrl}/api/production-lines/statuses/${customerId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                cache: 'no-store' // Evitar caché para obtener datos frescos
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.statuses) {
                        // Crear nuevo objeto de estados
                        const newStatuses = {};
                        
                        // Convertir el array a un objeto para fácil acceso
                        data.statuses.forEach(status => {
                            newStatuses[status.production_line_id] = {
                                type: status.type,
                                action: status.action,
                                operator_name: status.operator_name,
                                timestamp: status.created_at,
                                scheduled_status: status.scheduled_status || 'off_shift'
                            };
                        });
                        
                        // Detectar si hay cambios reales antes de actualizar
                        let hasChanges = false;
                        
                        // Comprobar si hay cambios en los estados
                        Object.keys(newStatuses).forEach(lineId => {
                            const newStatus = newStatuses[lineId];
                            const oldStatus = previousStatuses[lineId];
                            
                            if (!oldStatus || 
                                oldStatus.type !== newStatus.type ||
                                oldStatus.action !== newStatus.action ||
                                oldStatus.operator_name !== newStatus.operator_name ||
                                oldStatus.scheduled_status !== newStatus.scheduled_status) {
                                hasChanges = true;
                            }
                        });
                        
                        // Actualizar el objeto global solo si hay cambios
                        productionLineStatuses = newStatuses;
                        
                        // Siempre actualizar visualmente después de refrescar los datos del Kanban
                        // Esto garantiza que los headers siempre se muestren correctamente
                        if (hasChanges) {
                            console.log('📊 Actualizando estados de líneas (cambios detectados)');
                        } else {
                            console.log('📊 No hay cambios en los estados de líneas, pero actualizando headers igualmente');
                        }
                        
                        // Forzar actualización de headers siempre
                        updateColumnHeaderStatuses();
                        
                        // Actualizar estadísticas de la columna sin reconstruir el DOM
                        Object.values(columns).forEach(column => {
                            if (!column.productionLineId) return;
                            const columnElement = document.getElementById(column.id);
                            if (columnElement) {
                                updateColumnStats(columnElement, true);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('❌ Error al obtener estados de líneas:', error);
                });
        }
        
        function updateColumnHeaderStatuses() {
            // Proteger el estado del input de Pendientes durante actualización de headers
            capturePendingSearchState();
            Object.values(columns).forEach(column => {
                if (!column.productionLineId) return;
                
                const columnElement = document.getElementById(column.id);
                if (!columnElement) return;
                
                const headerElement = columnElement.querySelector('.column-header');
                if (!headerElement) return;
                
                // Agregar clase para transiciones suaves
                if (!headerElement.classList.contains('smooth-transition')) {
                    headerElement.classList.add('smooth-transition');
                }
                
                // Determinar la clase de estado que debería tener
                let newHeaderClass = '';
                if (column.productionLineId && productionLineStatuses[column.productionLineId]) {
                    const lineStatus = productionLineStatuses[column.productionLineId];
                    
                    if (lineStatus.type === 'shift' && lineStatus.action === 'start' || 
                        lineStatus.type === 'stop' && lineStatus.action === 'end') {
                        newHeaderClass = 'column-header-running';
                    } else if (lineStatus.type === 'stop' && lineStatus.action === 'start') {
                        newHeaderClass = 'column-header-paused';
                    } else if (lineStatus.type === 'shift' && lineStatus.action === 'end') {
                        newHeaderClass = 'column-header-stopped';
                    }
                }
                
                // Verificar si la clase actual es diferente a la nueva
                const hasRunning = headerElement.classList.contains('column-header-running');
                const hasPaused = headerElement.classList.contains('column-header-paused');
                const hasStopped = headerElement.classList.contains('column-header-stopped');
                
                // Solo actualizar las clases si hay cambios
                if ((newHeaderClass === 'column-header-running' && !hasRunning) ||
                    (newHeaderClass === 'column-header-paused' && !hasPaused) ||
                    (newHeaderClass === 'column-header-stopped' && !hasStopped)) {
                    
                    // Eliminar clases de estado anteriores
                    headerElement.classList.remove('column-header-running', 'column-header-paused', 'column-header-stopped');
                    
                    // Añadir la nueva clase
                    if (newHeaderClass) {
                        headerElement.classList.add(newHeaderClass);
                    }
                }
                
                // Actualizar el contenido del indicador de estado
                renderColumnStatusIndicator(column, columnElement);
                
                // Actualizar estadísticas de la columna sin reconstruir el DOM
                updateColumnStats(columnElement, true);
            });
            // Restaurar estado del input de Pendientes tras actualizar headers
            restorePendingSearchState();
        }
        
        function renderColumnStatusIndicator(column, columnElement) {
            if (!column.productionLineId) return;
            
            const lineStatus = productionLineStatuses[column.productionLineId];
            if (!lineStatus) {
                // Silenciar para evitar ruido en consola y micro-janks
                return;
            }
            
            const statusContainer = columnElement.querySelector('.column-header');
            if (!statusContainer) return;
            
            // Obtener los contenedores de la primera y segunda línea
            const headerLine1 = columnElement.querySelector('.header-line-1');
            const headerLine2 = columnElement.querySelector('.header-line-2');
            
            if (!headerLine1 || !headerLine2) {
                console.error('❌ No se encontraron los contenedores de líneas del header');
                return;
            }
            
            try {
                // 1. Actualizar el indicador de estado (running, paused, stopped)
                const statusIndicator = headerLine1.querySelector('.line-status-indicator');
                if (statusIndicator) {
                    let statusText = 'Parada'; // Valor por defecto
                    let statusClass = 'line-status-stopped';
                    let iconType = 'stop';
                    
                    if (lineStatus.type === 'shift' && lineStatus.action === 'start' || 
                        lineStatus.type === 'stop' && lineStatus.action === 'end') {
                        statusText = 'En Marcha';
                        statusClass = 'line-status-running';
                        iconType = 'play';
                    } else if (lineStatus.type === 'stop' && lineStatus.action === 'start') {
                        statusText = 'En Pausa';
                        statusClass = 'line-status-paused';
                        iconType = 'pause';
                    } else if (lineStatus.type === 'shift' && lineStatus.action === 'end') {
                        statusText = 'Parada';
                        statusClass = 'line-status-stopped';
                        iconType = 'stop';
                    }
                    
                    // Actualizar el icono sin cambiar la estructura DOM
                    const iconElement = statusIndicator.querySelector('i');
                    if (iconElement) {
                        // Solo actualizar la clase si es diferente para evitar reflow
                        const newIconClass = `fas fa-${iconType}-circle`;
                        if (iconElement.className !== newIconClass) {
                            iconElement.className = newIconClass;
                        }
                    }
                    
                    // Actualizar el texto
                    const textElement = statusIndicator.querySelector('span');
                    if (textElement) {
                        if (textElement.textContent !== statusText) {
                            textElement.textContent = statusText;
                        }
                    }
                    
                    // Actualizar la clase solo si cambió
                    const desiredClass = `line-status-indicator ${statusClass}`;
                    if (statusIndicator.className !== desiredClass) {
                        statusIndicator.className = desiredClass;
                    }
                }
                
                // 2. Actualizar el elemento del operario
                const operatorElement = headerLine1.querySelector('.line-operator');
                if (operatorElement) {
                    const textElement = operatorElement.querySelector('span');
                    const operatorName = lineStatus.operator_name || '';
                    
                    if (textElement && textElement.textContent !== operatorName) {
                        textElement.textContent = operatorName;
                    }
                }
                
                // 3. Actualizar el elemento de planificación
                const scheduleElement = headerLine2.querySelector('.line-schedule');
                if (scheduleElement) {
                    let scheduleIcon = 'fa-calendar-minus';
                    let scheduleText = 'Fuera de turno';
                    let scheduleClass = 'line-schedule-offshift';
                    
                    switch(lineStatus.scheduled_status) {
                        case 'scheduled':
                            scheduleIcon = 'fa-calendar-check';
                            scheduleText = 'Planificada';
                            scheduleClass = 'line-schedule-planned';
                            break;
                        case 'unscheduled':
                            scheduleIcon = 'fa-calendar-times';
                            scheduleText = 'No planificada';
                            scheduleClass = 'line-schedule-unplanned';
                            break;
                    }
                    
                    // Actualizar el icono
                    const iconElement = scheduleElement.querySelector('i');
                    if (iconElement) {
                        const desired = `fas ${scheduleIcon}`;
                        if (iconElement.className !== desired) {
                            iconElement.className = desired;
                        }
                    }
                    
                    // Actualizar el texto
                    const textElement = scheduleElement.querySelector('span');
                    if (textElement && textElement.textContent !== scheduleText) {
                        textElement.textContent = scheduleText;
                    }
                    
                    // Actualizar la clase
                    const desiredScheduleClass = `line-schedule ${scheduleClass}`;
                    if (scheduleElement.className !== desiredScheduleClass) {
                        scheduleElement.className = desiredScheduleClass;
                    }
                }
                
                // 4. Aplicar transiciones suaves para evitar parpadeo
                statusContainer.classList.add('smooth-updates');
            } catch (error) {
                console.error(`❌ Error al actualizar header de línea ${column.productionLineId}:`, error);
            }
        }
        
        
        // Manejar clics en el menú de tres puntos
        document.addEventListener('click', function(event) {
            const menuToggle = event.target.closest('.column-menu-toggle');
            if (menuToggle) {
                const columnId = menuToggle.dataset.columnId;
                const lineToken = menuToggle.dataset.lineToken;
                console.log('Menu toggle clicked:', menuToggle.dataset);
                console.log('Column ID:', columnId, 'Line Token:', lineToken);
                
                const column = columns[columnId];
                
                if (column) {
                    // Asegurarnos de que el token esté disponible en el objeto column
                    if (lineToken && (!column.token || column.token === '')) {
                        column.token = lineToken;
                        console.log('Token añadido al objeto column desde dataset:', lineToken);
                    }
                    
                    showColumnMenu(column);
                }
            }
        });
        
        function showColumnMenu(column) {
            // Solo mostrar opciones si es una línea de producción
            const hasProductionLine = column.productionLineId && column.productionLineId !== '';
            // Verificar si tiene token para mostrar el botón de vista en vivo
            const hasToken = column.token && column.token !== '';
            
            // Depurar los datos de la columna
            console.log('Datos de columna:', column);
            console.log('Tiene token:', hasToken, 'Token:', column.token);
            
            Swal.fire({
                title: `Opciones para ${column.name}`,
                showCloseButton: true,
                showConfirmButton: false,
                html: `
                    <div class="d-flex flex-column gap-2 my-4">
                        ${hasProductionLine ? `
                        <button id="planningBtn" class="btn btn-primary w-100">
                            <i class="fas fa-calendar-alt me-2"></i>Planificación
                        </button>
                        ` : ''}
                        ${hasToken ? `
                        <button id="liveViewBtn" class="btn btn-success w-100">
                            <i class="fas fa-tv me-2"></i>Maquina en Vivo
                        </button>
                        ` : ''}
                        ${hasToken ? `
                        <button id="liveLineBtn" class="btn btn-info w-100">
                            <i class="fas fa-industry me-2"></i>Línea en Vivo
                        </button>
                        ` : ''}
                        ${hasProductionLine ? `
                        <button id="processesBtn" class="btn btn-warning w-100">
                            <i class="fas fa-cogs me-2"></i>Procesos
                        </button>
                        ` : ''}
                    </div>`,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    
                    // Evento para el botón de planificación (solo si existe)
                    if (hasProductionLine) {
                        popup.querySelector('#planningBtn').addEventListener('click', () => {
                            // Cerrar el popup actual
                            Swal.close();
                            
                            // Actualizar el nombre de la línea en el modal
                            document.getElementById('lineNameDisplay').textContent = column.name || 'Línea ' + column.productionLineId;
                            
                            // Establecer el ID de la línea en el formulario
                            document.getElementById('productionLineId').value = column.productionLineId;
                            
                            // Cargar los datos de disponibilidad
                            loadAvailabilityData(column.productionLineId);
                            
                            // Abrir el modal Bootstrap
                            schedulerModal.show();
                        });
                    }
                    
                    // Evento para el botón de vista en vivo (solo si existe token)
                    if (hasToken) {
                        popup.querySelector('#liveViewBtn').addEventListener('click', () => {
                            // URL de la vista en vivo con el token
                            const liveViewUrl = `/live-production/machine.html?token=${column.token}`;
                            
                            // Abrir en una nueva pestaña
                            window.open(liveViewUrl, '_blank');
                            
                            // Cerrar el popup
                            Swal.close();
                        });
                        
                        // Evento para el botón de línea en vivo (solo si existe token)
                        popup.querySelector('#liveLineBtn').addEventListener('click', () => {
                            // URL externa de la línea en vivo con el token
                            const liveLineUrl = `/live-production/live.html?token=${column.token}`;
                            
                            // Abrir en una nueva pestaña
                            window.open(liveLineUrl, '_blank');
                            
                            // Cerrar el popup
                            Swal.close();
                        });
                    }
                    
                    // Evento para el botón de procesos (solo si existe línea de producción)
                    if (hasProductionLine) {
                        popup.querySelector('#processesBtn').addEventListener('click', () => {
                            // URL de procesos de la línea de producción
                            const processesUrl = `/productionlines/${column.productionLineId}/processes`;
                            
                            // Abrir en una nueva pestaña
                            window.open(processesUrl, '_blank');
                            
                            // Cerrar el popup
                            Swal.close();
                        });
                    }
                }
            });
        }
        
        // Obtener estados iniciales y configurar actualización periódica
        fetchProductionLineStatuses();
        setInterval(fetchProductionLineStatuses, 30000); // Actualizar cada 30 segundos
        
        // --- 6. GUARDADO DE DATOS Y OTROS EVENTOS ---
        
        function saveKanbanChanges() {
            // Evitar múltiples solicitudes simultáneas
            if (isRequestInProgress) {
                console.log('⚠️ Ya hay una solicitud en curso. Esperando...');
                return;
            }
            
            // Marcar que hay una solicitud en curso
            isRequestInProgress = true;
            
            // Mostrar loader visual en el tablero
            showKanbanLoader();
            
            const saveBtn = document.getElementById('saveChangesBtn');
            saveBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> ${translations.saving}`;
            saveBtn.disabled = true;

            const updatedOrders = [];
            const statusMap = { 'pending': 0, 'in_progress': 1, 'completed': 2, 'cancelled': 4, 'paused': 3 };
            
            masterOrderList.forEach((order, index) => {

                updatedOrders.push({
                    id: order.id,
                    production_line_id: order.productionLineId ? order.productionLineId : null,
                    orden: index,
                    status: statusMap[order.status] !== undefined ? statusMap[order.status] : 0
                    // Ya no enviamos accumulated_time, se calcula automáticamente con el comando artisan
                });
            });

            fetch('{{ route('production-orders.update-batch') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ orders: updatedOrders })
            })
            .then(async response => {
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(errorText);
                }
                return response.json();
            })
            .then(data => {
                // Guardado exitoso: actualizar firma y limpiar banderas
                lastSavedSignature = computeCurrentSignature();
                hasUnsavedChanges = false;
                autoSortDirty = false;
                // Desactivar el toggle de auto-orden tras guardar
                const autoToggle = document.getElementById('autoSortToggle');
                if (autoToggle) {
                    autoToggle.checked = false;
                }
                // Re-render sin ordenar adicional para reflejar el estado persistido
                try { distributeAndRender(false); } catch(_) {}
                showToast(data.message || translations.changesSaved, 'success');
            })
            .catch(error => {
                console.error("--- ERROR AL GUARDAR: RESPUESTA COMPLETA DEL SERVIDOR ---");
                console.log(error.message);
                showToast(translations.errorSaving, 'error');
            })
            .finally(() => {
                saveBtn.innerHTML = `<i class="fas fa-save me-1"></i> {{ __('Guardar') }}`;
                saveBtn.disabled = false;
                
                // Marcar que la solicitud ha terminado
                isRequestInProgress = false;
                
                // Ocultar el loader visual
                hideKanbanLoader();
                
                console.log('🔄 Solicitud completada');
            });
        }

        // Función para mostrar un loader visual sobre el tablero Kanban
        function showKanbanLoader() {
            // Verificar si ya existe un loader
            if (document.getElementById('kanban-loader')) return;
            
            // Crear el elemento del loader
            const loader = document.createElement('div');
            loader.id = 'kanban-loader';
            loader.innerHTML = `
                <div class="loader-content">
                    <div class="spinner"></div>
                    <div class="loader-text">${translations.saving || 'Guardando cambios...'}</div>
                </div>
            `;
            
            // Añadir el loader al contenedor del Kanban
            const kanbanContainer = document.getElementById('kanbanContainer');
            if (kanbanContainer) {
                kanbanContainer.appendChild(loader);
                console.log('💾 Loader visual mostrado');
            }
        }
        
        // Función para ocultar el loader visual
        function hideKanbanLoader() {
            const loader = document.getElementById('kanban-loader');
            if (loader) {
                // Agregar clase para animar la salida
                loader.classList.add('fade-out');
                
                // Eliminar el loader después de la animación
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                    console.log('💾 Loader visual ocultado');
                }, 300);
            }
        }
        
        function showToast(message, type = 'success') {
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            Toast.fire({ icon: type, title: message });
        }
                
        function showCardMenu(orderId) {
            const order = masterOrderList.find(o => o.id == orderId);
            if (!order) return;

            const originalOrderUrl = order.original_order_id ? `/customers/${customerId}/original-orders/${order.original_order_id}` : '#';
            const isOriginalOrderDisabled = !order.original_order_id;
            
            // Determinar si la orden es prioritaria
            const isPriority = order.is_priority === true || order.is_priority === 1;
            const priorityBtnText = isPriority ? '{{ __('Quitar prioridad') }}' : '{{ __('Marcar como prioritaria') }}';
            const priorityBtnClass = isPriority ? 'btn-secondary' : 'btn-warning';

            Swal.fire({
                title: `{{ __('Order') }} #${order.order_id}`,
                showCloseButton: true,
                showConfirmButton: false,
                html: `
                    <div class="d-flex flex-column gap-2 my-4">
                        <button id="togglePriorityBtn" class="btn ${priorityBtnClass} w-100">
                            <i class="fas ${isPriority ? 'fa-star' : 'fa-star'} me-2"></i>${priorityBtnText}
                        </button>
                        <button id="notesBtn" class="btn btn-primary w-100">
                            <i class="fas fa-sticky-note me-2"></i>{{ __('Anotaciones') }}
                        </button>
                        <button id="viewIncidentsBtn" class="btn btn-danger w-100">{{ __('View Incidents') }}</button>
                        <button id="viewOriginalOrderBtn" class="btn btn-info w-100" ${isOriginalOrderDisabled ? 'disabled' : ''}>
                            {{ __('View Original Order') }}
                        </button>
                    </div>`,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    
                    // Evento para marcar/desmarcar como prioritaria
                    popup.querySelector('#togglePriorityBtn').addEventListener('click', () => {
                        // Mostrar indicador de carga
                        const btn = popup.querySelector('#togglePriorityBtn');
                        const originalText = btn.innerHTML;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
                        btn.disabled = true;
                        
                        // Llamar al backend para actualizar el estado de prioridad
                        fetch('{{ route("production-orders.toggle-priority") }}', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ order_id: orderId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Actualizar el estado en la lista maestra
                                const orderIndex = masterOrderList.findIndex(o => o.id == orderId);
                                if (orderIndex !== -1) {
                                    masterOrderList[orderIndex].is_priority = data.is_priority;
                                    
                                    // Actualizar la tarjeta en el DOM
                                    const card = document.querySelector(`.kanban-card[data-id="${orderId}"]`);
                                    if (card) {
                                        if (data.is_priority) {
                                            card.classList.add('priority-order');
                                        } else {
                                            card.classList.remove('priority-order');
                                        }
                                    }
                                }
                                
                                // Cerrar el popup
                                Swal.close();
                                
                                // Mostrar mensaje de éxito
                                const message = data.is_priority ? 'Orden marcada como prioritaria' : 'Prioridad eliminada de la orden';
                                Swal.fire({
                                    title: 'Éxito',
                                    text: message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                // Restaurar el botón y mostrar error
                                btn.innerHTML = originalText;
                                btn.disabled = false;
                                
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message || 'No se pudo actualizar la prioridad',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                            
                            Swal.fire({
                                title: 'Error',
                                text: 'Ocurrió un error al procesar la solicitud',
                                icon: 'error'
                            });
                        });
                    });
                    
                    // Evento para el botón de Anotaciones
                    popup.querySelector('#notesBtn').addEventListener('click', () => {
                        Swal.close();
                        // Depurar el valor de note
                        console.log('Valor de note en la orden:', order.id, order.note);
                        // Mostrar modal para editar anotaciones
                        Swal.fire({
                            title: `Anotaciones - Orden #${order.order_id}`,
                            html: `
                                <div class="form-group">
                                    <textarea id="orderNotes" class="form-control" rows="5" placeholder="Escribe aquí tus anotaciones...">${order.note !== undefined && order.note !== null ? order.note : ''}</textarea>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Guardar',
                            cancelButtonText: 'Cancelar',
                            preConfirm: () => {
                                const noteText = document.getElementById('orderNotes').value;
                                return fetch('{{ route("production-orders.update-note") }}', {
                                    method: 'POST',
                                    headers: { 
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ order_id: orderId, note: noteText })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Actualizar el estado en la lista maestra
                                        const orderIndex = masterOrderList.findIndex(o => o.id == orderId);
                                        if (orderIndex !== -1) {
                                            masterOrderList[orderIndex].note = noteText;
                                        }
                                        return { success: true, message: 'Anotaciones guardadas correctamente' };
                                    } else {
                                        throw new Error(data.message || 'No se pudieron guardar las anotaciones');
                                    }
                                })
                                .catch(error => {
                                    Swal.showValidationMessage(`Error: ${error.message}`);
                                    return { success: false };
                                });
                            }
                        }).then((result) => {
                            if (result.isConfirmed && result.value.success) {
                                showToast('Anotaciones guardadas correctamente', 'success');
                            }
                        });
                    });
                    
                    popup.querySelector('#viewIncidentsBtn').addEventListener('click', () => {
                        window.location.href = `/customers/${customerId}/production-order-incidents`;
                        Swal.close();
                    });
                    
                    if (!isOriginalOrderDisabled) {
                        popup.querySelector('#viewOriginalOrderBtn').addEventListener('click', () => {
                            window.open(originalOrderUrl, '_blank');
                            Swal.close();
                        });
                    }
                }
            });
        }

                // Función para actualizar los contadores de tarjetas y el placeholder del campo de búsqueda
        function updateColumnStats(columnElement, preventReflow = false) {
            if (!columnElement) return;
            
            // Contar tarjetas visibles (no ocultas)
            const cards = columnElement.querySelectorAll('.kanban-card');
            const visibleCards = Array.from(cards).filter(card => card.style.display !== 'none');
            const visibleCount = visibleCards.length;
            
            // Actualizar el contador de tarjetas solo si ha cambiado
            const cardCountBadge = columnElement.querySelector('.card-count-badge');
            if (cardCountBadge && cardCountBadge.textContent !== String(visibleCount)) {
                cardCountBadge.textContent = visibleCount;
            }
            
            // Calcular tiempo total de las tarjetas visibles
            let totalSeconds = 0;
            visibleCards.forEach(card => {
                const orderId = card.dataset.id;
                const order = masterOrderList.find(o => o.id == orderId);
                if (order) {
                    totalSeconds += parseTimeToSeconds(order.theoretical_time);
                }
            });
            
            // Formatear el tiempo total
            const formattedTime = formatSecondsToTime(totalSeconds);
            
            // Actualizar el badge de tiempo total solo si ha cambiado
            const timeSumBadge = columnElement.querySelector('.time-sum-badge');
            if (timeSumBadge) {
                const newTimeHtml = `<i class="far fa-clock"></i> ${formattedTime}`;
                if (timeSumBadge.innerHTML !== newTimeHtml) {
                    // Actualizar solo el texto del tiempo sin recrear el icono
                    const timeTextNode = Array.from(timeSumBadge.childNodes).find(node => 
                        node.nodeType === Node.TEXT_NODE || 
                        (node.nodeType === Node.ELEMENT_NODE && node.tagName !== 'I'));
                    
                    if (timeTextNode) {
                        // Si existe un nodo de texto, actualizarlo
                        if (timeTextNode.nodeType === Node.TEXT_NODE) {
                            timeTextNode.nodeValue = ` ${formattedTime}`;
                        } else {
                            timeTextNode.textContent = formattedTime;
                        }
                    } else {
                        // Si no hay nodo de texto (primera carga), usar innerHTML
                        timeSumBadge.innerHTML = newTimeHtml;
                    }
                }
            }
            
            // Si es la columna de pendientes, actualizar también el placeholder del campo de búsqueda
            if (columnElement.id === 'pending_assignment') {
                const searchInput = columnElement.querySelector('.pending-search-input');
                if (searchInput) {
                    const totalCards = cards.length;
                    const newPlaceholder = visibleCount < totalCards
                        ? `Mostrando ${visibleCount} de ${totalCards} tarjetas...`
                        : `Buscar en ${totalCards} tarjetas...`;
                        
                    if (searchInput.placeholder !== newPlaceholder) {
                        searchInput.placeholder = newPlaceholder;
                    }
                }
            }
            
            // Aplicar clase para transiciones suaves al header si no la tiene
            if (preventReflow) {
                const headerElement = columnElement.querySelector('.column-header');
                if (headerElement && !headerElement.classList.contains('smooth-updates')) {
                    headerElement.classList.add('smooth-updates');
                }
            }
        }
        
        function toggleFullscreen() {
            const element = document.getElementById('kanbanContainer');
            if (!document.fullscreenElement) {
                element.requestFullscreen().catch(err => showToast(translations.fullscreenError, 'error'));
            } else {
                document.exitFullscreen();
            }
        }
        
        // --- 6. INICIALIZACIÓN Y EVENT LISTENERS ---
        
        // Función para refrescar los datos del Kanban sin recargar la página
        async function refreshKanbanData() {
            try {
                // Solo refrescar si no hay cambios pendientes
                if (hasUnsavedChanges) return;
                
                console.log('🔄 Actualizando datos del Kanban...');
                
                // Obtener datos actualizados del servidor
                const response = await fetch('{{ route("kanban.data") }}');
                
                if (!response.ok) {
                    throw new Error('Error al obtener datos actualizados');
                }
                
                const data = await response.json();
                
                // Actualizar masterOrderList con los nuevos datos
                // Preservar los elementos que estamos editando actualmente
                if (data.processOrders && Array.isArray(data.processOrders)) {
                    // Crear un mapa de órdenes actuales para referencia rápida
                    const currentOrdersMap = {};
                    
                    // Guardar el estado de expansión de las tarjetas actuales
                    const expandedCardIds = new Set();
                    document.querySelectorAll('.kanban-card:not(.collapsed)').forEach(card => {
                        expandedCardIds.add(parseInt(card.dataset.id));
                    });
                    
                    // Ya no necesitamos guardar el valor aquí, usamos la variable global lastPendingSearchValue
                    console.log('🔍 Usando valor de búsqueda global:', lastPendingSearchValue);
                    
                    // Guardar las posiciones de scroll de todas las columnas
                    const scrollPositions = {};
                    document.querySelectorAll('.kanban-column').forEach(column => {
                        const columnId = column.dataset.id || column.dataset.state || column.id;
                        if (columnId) {
                            const cardsContainer = column.querySelector('.column-cards');
                            if (cardsContainer) {
                                // Solo guardar la posición exacta de scroll
                                scrollPositions[columnId] = {
                                    scrollTop: cardsContainer.scrollTop,
                                    scrollLeft: cardsContainer.scrollLeft
                                };
                            }
                        }
                    });
                    
                    masterOrderList.forEach(order => {
                        currentOrdersMap[order.id] = order;
                    });
                    
                    // Reemplazar masterOrderList con los nuevos datos
                    masterOrderList = data.processOrders;
                    
                    // Restaurar el estado de las órdenes que estaban siendo editadas
                    if (draggedCard) {
                        const draggedId = parseInt(draggedCard.dataset.id);
                        const draggedOrder = masterOrderList.find(o => o.id === draggedId);
                        if (draggedOrder && currentOrdersMap[draggedId]) {
                            // Mantener el estado actual de la orden que se está arrastrando
                            Object.assign(draggedOrder, currentOrdersMap[draggedId]);
                        }
                    }
                    
                    // Renderizar el tablero con los datos actualizados
                    // También actualizar los estados de las líneas de producción
                    fetchProductionLineStatuses();
                    
                    distributeAndRender(true, () => {
                        // Restaurar el campo de búsqueda de pendientes
                        const pendingSearchInput = document.querySelector('.pending-search-input');
                        if (pendingSearchInput && lastPendingSearchValue) {
                            pendingSearchInput.value = lastPendingSearchValue;
                            // Si tenía el foco antes de la actualización, restaurarlo
                            if (wasPendingSearchFocused) {
                                pendingSearchInput.focus();
                            }
                            
                            // Aplicar el filtro de búsqueda inmediatamente
                            applyPendingSearch(lastPendingSearchValue);
                        }
                        
                        // Restaurar el estado de expansión de las tarjetas
                        if (expandedCardIds.size > 0) {
                            document.querySelectorAll('.kanban-card').forEach(card => {
                                const cardId = parseInt(card.dataset.id);
                                if (expandedCardIds.has(cardId)) {
                                    card.classList.remove('collapsed');
                                }
                            });
                        }
                        
                        // Restaurar el valor del campo de búsqueda en la columna de pendientes
                        const newPendingSearchInput = document.querySelector('.pending-search-input');
                        if (newPendingSearchInput) {
                            console.log('🔄 Restaurando valor de búsqueda global:', lastPendingSearchValue);
                            // Siempre restaurar el valor, incluso si está vacío
                            newPendingSearchInput.value = lastPendingSearchValue;
                            
                            // Aplicar el filtro nuevamente si hay un valor de búsqueda
                            const pendingColumn = document.getElementById('pending_assignment');
                            if (pendingColumn) {
                                const cards = pendingColumn.querySelectorAll('.kanban-card');
                                cards.forEach(card => {
                                    const orderId = card.dataset.id;
                                    const order = masterOrderList.find(o => o.id == orderId);
                                    if (order) {
                                        const searchValue = lastPendingSearchValue.toLowerCase();
                                        const orderIdMatch = order.order_id?.toString().toLowerCase().includes(searchValue);
                                        // Aceptar múltiples posibles campos de cliente
                                        const customerField = (order.customerId || order.customer || order.json?.customer || order.name || '').toString().toLowerCase();
                                        const customerMatch = customerField.includes(searchValue);
                                        // Aceptar descripción desde distintos orígenes
                                        const descripField = (order.descrip || order.json?.descrip || '').toString().toLowerCase();
                                        const descripMatch = descripField.includes(searchValue);
                                        const processesMatch = order.processes_to_do?.toLowerCase().includes(searchValue);
                                        const articlesMatch = order.articles?.some(article => 
                                            article.description?.toLowerCase().includes(searchValue));
                                        
                                        if (lastPendingSearchValue === '' || orderIdMatch || customerMatch || descripMatch || processesMatch || articlesMatch) {
                                            card.style.display = '';
                                        } else {
                                            card.style.display = 'none';
                                        }
                                    }
                                });
                                
                                // Actualizar contadores de la columna
                                updateColumnStats(pendingColumn);
                            }
                        }
                        
                        // Restaurar las posiciones de scroll de las columnas con un enfoque simple
                        setTimeout(() => {
                            document.querySelectorAll('.kanban-column').forEach(column => {
                                const columnId = column.dataset.id || column.dataset.state || column.id;
                                if (columnId && scrollPositions[columnId]) {
                                    const cardsContainer = column.querySelector('.column-cards');
                                    if (cardsContainer) {
                                        // Restaurar la posición exacta de scroll sin animaciones
                                        cardsContainer.scrollTop = scrollPositions[columnId].scrollTop;
                                        cardsContainer.scrollLeft = scrollPositions[columnId].scrollLeft;
                                    }
                                }
                            });
                        }, 150); // Aumentar el retraso para asegurar que el DOM se ha actualizado completamente
                    });
                    console.log('✅ Datos del Kanban actualizados correctamente');
                }
            } catch (error) {
                console.error('Error al actualizar datos del Kanban:', error);
                // No mostrar toast de error para no molestar al usuario con mensajes constantes
            }
        }
        
        document.getElementById('saveChangesBtn').addEventListener('click', saveKanbanChanges);
        document.getElementById('refreshBtn').addEventListener('click', refreshKanbanData);
        document.getElementById('fullscreenBtn').addEventListener('click', toggleFullscreen);
        // Event listener para botón de IA eliminado
        // Listeners para filtros de readiness
        document.getElementById('readyOnlyToggle')?.addEventListener('change', () => applyReadinessFilters());
        document.getElementById('dimNotReadyToggle')?.addEventListener('change', () => applyReadinessFilters());
        // Listener para autosort (toggle)
        document.getElementById('autoSortToggle')?.addEventListener('change', () => { autoSortDirty = true; distributeAndRender(true); });
        // Listener para botón azul Guardar
        const btnSave = document.getElementById('applyAutoSortBtn');
        if (btnSave) {
            btnSave.addEventListener('click', () => {
                console.log('[UI] Click en Guardar cambios');
                (window.handleSaveKanban || handleSaveKanban)?.();
            });
        }
        // Listener para botón mover pendientes
        const btnAssignPending = document.getElementById('assignPendingBtn');
        if (btnAssignPending) {
            btnAssignPending.addEventListener('click', () => {
                console.log('[UI] Click en Mover pendientes a líneas');
                (window.applyAssignPendingOnly || applyAssignPendingOnly)?.();
            });
        }
        // Fallback delegado por si el botón se re-renderiza
        document.addEventListener('click', (ev) => {
            const t = ev.target;
            if (t && (t.id === 'applyAutoSortBtn' || t.closest?.('#applyAutoSortBtn'))) {
                console.log('[UI] Delegado: Click en Guardar cambios');
                (window.handleSaveKanban || handleSaveKanban)?.();
            }
            if (t && (t.id === 'assignPendingBtn' || t.closest?.('#assignPendingBtn'))) {
                console.log('[UI] Delegado: Click en Mover pendientes a líneas');
                (window.applyAssignPendingOnly || applyAssignPendingOnly)?.();
            }
        });
        
        // Variables globales para almacenar el valor y estado del campo de búsqueda de pendientes
        let lastPendingSearchValue = '';
        let wasPendingSearchFocused = false;
        
        // Función para guardar el valor de búsqueda actual y el estado del foco
        function savePendingSearchValue() {
            const pendingSearchInput = document.querySelector('.pending-search-input');
            if (pendingSearchInput) {
                lastPendingSearchValue = pendingSearchInput.value;
                // Guardar si el campo tenía el foco
                wasPendingSearchFocused = (document.activeElement === pendingSearchInput);
                console.log('🔍 Valor de búsqueda guardado globalmente:', lastPendingSearchValue, 'Tenía foco:', wasPendingSearchFocused);
            }
        }
        
        // Actualización automática cada 10 segundos
        setInterval(() => {
            // No actualizar si hay una operación de drag & drop en curso
            if (draggedCard) {
                console.log('🔄 Actualización automática pausada: operación de drag & drop en curso');
                return;
            }
            
            savePendingSearchValue();
            refreshKanbanData();
            
            // Restaurar el foco si el campo lo tenía antes de la actualización
            setTimeout(() => {
                if (wasPendingSearchFocused) {
                    const pendingSearchInput = document.querySelector('.pending-search-input');
                    if (pendingSearchInput) {
                        pendingSearchInput.focus();
                    }
                }
            }, 100); // Pequeño retraso para asegurar que el DOM está actualizado
        }, 10000);
        
        searchInput.addEventListener('input', () => setTimeout(() => distributeAndRender(true), 300));
        
        // Función para aplicar el filtro de búsqueda en la columna de pendientes
        function applyPendingSearch(searchValue) {
            const pendingSearchValue = searchValue.toLowerCase().trim();
            const pendingColumn = document.getElementById('pending_assignment');
            if (pendingColumn) {
                const cards = pendingColumn.querySelectorAll('.kanban-card');
                cards.forEach(card => {
                    const orderId = card.dataset.id;
                    const order = masterOrderList.find(o => o.id == orderId);
                    if (order) {
                        const orderIdMatch = order.order_id?.toString().toLowerCase().includes(pendingSearchValue);
                        // Aceptar múltiples posibles campos de cliente
                        const customerField = (order.customerId || order.customer || order.json?.customer || order.name || '').toString().toLowerCase();
                        const customerMatch = customerField.includes(pendingSearchValue);
                        // Aceptar descripción desde distintos orígenes
                        const descripField = (order.descrip || order.json?.descrip || '').toString().toLowerCase();
                        const descripMatch = descripField.includes(pendingSearchValue);
                        const processesMatch = order.processes_to_do?.toLowerCase().includes(pendingSearchValue);
                        const articlesMatch = order.articles?.some(article => 
                            article.description?.toLowerCase().includes(pendingSearchValue));
                        
                        if (pendingSearchValue === '' || orderIdMatch || customerMatch || descripMatch || processesMatch || articlesMatch) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
                
                // Actualizar contadores de la columna
                updateColumnStats(pendingColumn);
            }
        }
        
        // Evento para el campo de búsqueda específico de pendientes
        document.addEventListener('input', function(event) {
            if (event.target.classList.contains('pending-search-input')) {
                // Actualizar la variable global inmediatamente
                lastPendingSearchValue = event.target.value;
                console.log('🔍 Valor de búsqueda actualizado por input:', lastPendingSearchValue);
                
                // Aplicar el filtro con un pequeño retraso para evitar demasiadas actualizaciones
                setTimeout(() => {
                    applyPendingSearch(lastPendingSearchValue);
                }, 300);
            }
        });
        
        kanbanBoard.addEventListener('click', function(event) {
            const menuButton = event.target.closest('.card-menu');
            if (menuButton) {
                event.stopPropagation();
                const orderId = menuButton.dataset.orderId;
                if(orderId) showCardMenu(orderId);
            }
        });
        
        document.getElementById('backToProcessesBtn').addEventListener('click', function(event) {
            if (hasUnsavedChanges) {
                event.preventDefault();
                Swal.fire({
                    title: translations.confirmTitle,
                    text: translations.confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: translations.confirmButton,
                    cancelButtonText: translations.cancelButton
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = this.href;
                    }
                });
            }
        });
        
        window.addEventListener('beforeunload', function (e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Listener global para detectar drops no capturados
        document.addEventListener('drop', function(event) {
            console.log('🌍 DROP GLOBAL DETECTADO - Target:', event.target.tagName, event.target.className);
            console.log('🌍 DROP GLOBAL - Tiene draggedCard:', !!draggedCard);
        }, true);
        
        // Listener global para dragover
        document.addEventListener('dragover', function(event) {
            // Solo log cada 10 eventos para no saturar
            if (Math.random() < 0.1) {
                console.log('🌍 DRAGOVER GLOBAL - Target:', event.target.tagName, event.target.className);
            }
        }, true);

        function recalculatePositions() {
            console.log('📊 RECALCULANDO POSICIONES basado en masterOrderList');
            
            // Primero, asignar posiciones basadas en el masterOrderList
            masterOrderList.forEach((order, index) => {
                order.orden = index;
                console.log(`📌 Orden ${order.id}: posición ${index}`);
            });
            
            // Luego, actualizar el DOM para reflejar el orden del masterOrderList
            const cards = kanbanBoard.querySelectorAll('.kanban-card');
            cards.forEach((card) => {
                const orderId = parseInt(card.dataset.id);
                const order = masterOrderList.find(o => o.id === orderId);
                if (order) {
                    // Actualizar atributo data-orden en el DOM
                    card.dataset.orden = order.orden;
                }
            });
            
            console.log('✅ Posiciones recalculadas correctamente');
        }

        distributeAndRender(true, () => {
            // Actualizar los tiempos acumulados en todas las columnas al inicializar
            document.querySelectorAll('.kanban-column').forEach(column => {
               // updateAccumulatedTimes(column);
            });
            
            // También actualizar en secciones de estados finales
            document.querySelectorAll('.final-state-section').forEach(section => {
               // updateAccumulatedTimes(section);
            });
            
            console.log('Kanban final inicializado con tiempos acumulados');
        });

        //ponemos un wait que reciva ms desde otra parte
        function wait(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // --- SCHEDULER FUNCTIONALITY ---
        
        // Bootstrap modal instance
        let schedulerModal;
        
        // Inicializar el modal Bootstrap inmediatamente
        schedulerModal = new bootstrap.Modal(document.getElementById('schedulerModal'));
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM cargado, inicializando eventos del scheduler');
            
            // Evento para abrir el modal del scheduler
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.open-scheduler-btn');
                if (btn) {
                    const lineId = btn.getAttribute('data-line-id');
                    const lineName = btn.getAttribute('data-line-name');
                    
                    if (!lineId) {
                        console.error('Error: No se encontró el ID de línea');
                        return;
                    }
                    
                    // Actualizar el nombre de la línea en el modal
                    document.getElementById('lineNameDisplay').textContent = lineName || 'Línea ' + lineId;
                    
                    // Establecer el ID de la línea en el formulario
                    document.getElementById('productionLineId').value = lineId;
                    
                    // Cargar los datos de disponibilidad
                    loadAvailabilityData(lineId);
                    
                    // Abrir el modal
                    schedulerModal.show();
                }
            });
        });
        
        // Evento para el botón de guardar del scheduler
        document.getElementById('saveScheduler').addEventListener('click', function(e) {
            // Prevenir cualquier comportamiento por defecto
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Botón guardar clickeado');
            
            const productionLineId = document.getElementById('productionLineId').value;
            if (!productionLineId) {
                console.error('Error: No se encontró el ID de línea');
                return;
            }
            
            // Recopilar datos del formulario
            const data = {
                production_line_id: productionLineId,
                customer_id: customerId, // Agregar el ID del cliente
                days: {}
            };
            
            // Procesar cada día y sus turnos seleccionados
            document.querySelectorAll('.day-row').forEach(dayRow => {
                const dayNum = dayRow.getAttribute('data-day');
                const dayActive = dayRow.querySelector('.day-active').checked;
                
                if (dayActive) {
                    // Obtener todos los turnos seleccionados para este día
                    const selectedShifts = Array.from(dayRow.querySelectorAll('.shift-checkbox:checked'))
                        .map(checkbox => checkbox.value);
                    
                    // Solo añadir días con turnos seleccionados
                    if (selectedShifts.length > 0) {
                        // Inicializar el array para este día si no existe
                        if (!data.days[dayNum]) {
                            data.days[dayNum] = [];
                        }
                        
                        // Añadir los turnos seleccionados al día
                        data.days[dayNum] = selectedShifts;
                    }
                }
            });
            
            console.log('Datos a enviar:', data);
            
            // Enviar los datos al servidor usando fetch directamente sin depender del formulario
            // Usar URL absoluta para evitar problemas con rutas relativas en producción
            const baseUrl = window.location.origin;
            // Modificamos la URL para incluir el ID de la línea en la ruta, siguiendo el mismo patrón que la carga
            const lineIdForUrl = data.production_line_id;
            fetch(`${baseUrl}/api/production-lines/${lineIdForUrl}/availability`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                // Cerrar el modal del planificador primero, antes de mostrar cualquier mensaje
                schedulerModal.hide();
                
                if (data.error) {
                    throw new Error(data.error);
                } else if (data.success) {
                    // Mostrar mensaje de éxito
                    Swal.fire({
                        title: 'Guardado correctamente',
                        text: data.message || 'La disponibilidad ha sido actualizada',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    // Mostrar mensaje de error
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ha ocurrido un error al guardar la disponibilidad',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                }
            })
            .catch(error => {
                console.error('Error al guardar la disponibilidad:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ha ocurrido un error al guardar la disponibilidad',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            });
        });
        
        // Función para cargar los datos de disponibilidad
        function loadAvailabilityData(lineId) {
            // Mostrar spinner de carga
            document.getElementById('schedulerDaysContainer').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando datos de disponibilidad...</p>
                </div>
            `;
            const baseUrl = window.location.origin;
            // Cargar los datos del servidor
            fetch(`${baseUrl}/api/production-lines/${lineId}/availability`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                
                // Procesar la nueva estructura JSON
                renderSchedulerDays(data.shifts, data.availability);
            })
            .catch(error => {
                console.error('Error al cargar los datos de disponibilidad:', error);
                document.getElementById('schedulerDaysContainer').innerHTML = `
                    <div class="alert alert-danger">
                        Error al cargar los datos de disponibilidad. Por favor, inténtelo de nuevo.
                    </div>
                `;
            });
        }
        
        // Función para renderizar los días y turnos en el scheduler
        function renderSchedulerDays(shifts, availability) {
            const days = {
                1: 'Lunes',
                2: 'Martes',
                3: 'Miércoles',
                4: 'Jueves',
                5: 'Viernes',
                6: 'Sábado',
                7: 'Domingo'
            };
            
            // Preparar la disponibilidad por día para fácil acceso
            const availabilityByDay = {};
            Object.keys(days).forEach(dayNum => {
                availabilityByDay[dayNum] = [];
            });
            
            // Organizar la disponibilidad por día
            if (availability && Array.isArray(availability)) {
                availability.forEach(item => {
                    if (availabilityByDay[item.day_of_week]) {
                        availabilityByDay[item.day_of_week].push(item.shift_list_id);
                    }
                });
            }
            
            // Generar el HTML para cada día
            let html = '';
            
            Object.entries(days).forEach(([dayNum, dayName]) => {
                const dayHasShifts = availabilityByDay[dayNum].length > 0;
                
                html += `
                    <div class="row mb-3 align-items-center day-row" data-day="${dayNum}">
                        <div class="col-3">
                            <div class="form-check">
                                <input class="form-check-input day-active" type="checkbox" id="day${dayNum}" 
                                       ${dayHasShifts ? 'checked' : ''}>
                                <label class="form-check-label" for="day${dayNum}">
                                    ${dayName}
                                </label>
                            </div>
                        </div>
                        <div class="col-9">
                            <div class="shifts-container ${dayHasShifts ? '' : 'disabled'}">
                `;
                
                if (shifts && shifts.length > 0) {
                    html += '<div class="d-flex flex-wrap gap-3">';
                    
                    shifts.forEach(shift => {
                        const isChecked = availabilityByDay[dayNum].includes(shift.id);
                        html += `
                            <div class="form-check shift-checkbox-wrapper">
                                <input class="form-check-input shift-checkbox" 
                                    type="checkbox" 
                                    id="shift${dayNum}_${shift.id}" 
                                    name="shifts[${dayNum}][]" 
                                    value="${shift.id}"
                                    ${isChecked ? 'checked' : ''}
                                    >
                                <label class="form-check-label" for="shift${dayNum}_${shift.id}">
                                    ${shift.start} - ${shift.end}
                                </label>
                            </div>
                        `;
                    });
                    
                    html += '</div>';
                } else {
                    html += '<div class="text-muted">No hay turnos definidos</div>';
                }
                
                html += `
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Actualizar el contenedor
            document.getElementById('schedulerDaysContainer').innerHTML = html;
            
            // Añadir eventos a los checkboxes de días
            document.querySelectorAll('.day-active').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const dayRow = this.closest('.day-row');
                    const shiftsContainer = dayRow.querySelector('.shifts-container');
                    
                    if (this.checked) {
                        shiftsContainer.classList.remove('disabled');
                    } else {
                        shiftsContainer.classList.add('disabled');
                        // Desmarcar todos los turnos
                        dayRow.querySelectorAll('.shift-checkbox').forEach(shiftCheckbox => {
                            shiftCheckbox.checked = false;
                        });
                    }
                });
            });
        }

    });
    </script>
@endpush
