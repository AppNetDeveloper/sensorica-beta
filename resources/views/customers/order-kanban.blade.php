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
<!-- Controles -->
    <!-- Barra de Filtros y Controles -->
    <div class="mb-3 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="d-flex flex-wrap items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap items-center gap-3">
                <a href="{{ route('customers.order-organizer', $customer) }}" class="btn btn-secondary me-2">
                    <i class="ti ti-arrow-left me-1"></i> {{ __('Back to Processes') }}
                </a>
                <div class="position-relative" style="max-width: 400px;">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="{{ __('Search by order ID or customer...') }}"
                           class="form-control ps-5" style="width: 100%;">
                </div>
            </div>
            <div class="d-flex items-center gap-2">
                <button id="saveChangesBtn" class="btn btn-primary" title="{{ __('Save Changes') }}">
                    <i class="fas fa-save me-1"></i> {{ __('Guardar') }}
                </button>
                <button id="refreshBtn" class="btn btn-light" title="{{ __('Refresh') }}">
                    <i class="fas fa-sync-alt text-primary"></i>
                </button>
                <button id="fullscreenBtn" class="btn btn-light" title="{{ __('Fullscreen') }}">
                    <i class="fas fa-expand-arrows-alt text-primary"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Contenedor del Kanban -->
    <div id="kanbanContainer" class="position-relative">
        <div class="kanban-board" role="list" aria-label="{{ __('Kanban Board') }}"></div>
    </div>
@endsection

@push('style')
    <style>
        :root {
            --kanban-bg: #f9fafb;
            --column-bg: #f3f4f6;
            --column-border: #e5e7eb;
            --header-bg: #ffffff;
            --header-text: #374151;
            --card-bg: #ffffff;
            --card-text: #1f2937;
            --card-hover-bg: #f9fafb;
            --card-border: #e5e7eb;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.06);
            --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.1);
            --scrollbar-thumb: #d1d5db;
            --primary-color: #3b82f6;
            --danger-color: #ef4444;
            --text-muted: #6b7280;
        }

        body.dark {
            --kanban-bg: #0f172a; --column-bg: #1e293b; --column-border: #334155;
            --header-bg: #334155; --header-text: #f1f5f9; --card-bg: #2d3748;
            --card-text: #e2e8f0; --card-hover-bg: #334155; --card-border: #4a5568;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.2); --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.3);
            --scrollbar-thumb: #475569; --primary-color: #60a5fa; --danger-color: #f87171; --text-muted: #94a3b8;
        }

        /* Contenedor principal del tablero */
        #kanbanContainer {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px); /* Ajustar según tu header */
            overflow: hidden;
        }

        .kanban-board { 
            display: flex; 
            gap: 1rem; 
            padding: 1rem; 
            overflow-x: auto; 
            overflow-y: hidden;
            background-color: var(--kanban-bg);
            flex: 1;
            min-height: 0; /* Importante para que funcione el scroll */
            align-items: flex-start;
        }
        
        /* Estilos para pantalla completa */
        :fullscreen #kanbanContainer {
            width: 100%;
            height: 100vh;
            background-color: var(--kanban-bg);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        :fullscreen .kanban-board {
            flex: 1;
            min-height: 0; /* Permite que el contenedor se encoja si es necesario */
            padding: 1rem 1rem 0.5rem;
            align-items: flex-start;
        }
        
        :fullscreen .kanban-column {
            height: 100%;
            max-height: 100% !important;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        :fullscreen .kanban-column .column-cards {
            max-height: none !important;
            flex: 1;
            overflow-y: auto;
        }
        .kanban-board::-webkit-scrollbar { height: 10px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 10px; border: 2px solid var(--kanban-bg); }

        .kanban-column { 
            flex: 0 0 340px; 
            background-color: var(--column-bg); 
            border-radius: 12px; 
            min-width: 340px; 
            display: flex; 
            flex-direction: column; 
            border: 1px solid var(--column-border); 
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            height: 100%;
            max-height: 100%;
            overflow: hidden;
        }
        
        /* Ajustar altura en pantalla completa */
        :fullscreen .kanban-column {
            max-height: calc(100vh - 100px) !important;
            height: 100%;
        }
        
        /* Contenedor de tarjetas con scroll */
        .kanban-column .column-cards {
            flex: 1 1 auto;
            overflow-y: auto;
            min-height: 100px;
            max-height: 100%;
            padding: 0.5rem;
            padding-right: 4px;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px; /* Espaciado consistente entre tarjetas */
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }
        
        /* Estilos para la barra de desplazamiento */
        .kanban-column .column-cards::-webkit-scrollbar {
            width: 6px;
        }
        
        .kanban-column .column-cards::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .kanban-column .column-cards::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .kanban-column .column-cards::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Contenedor de tarjetas en secciones de estado final */
        .final-state-section .column-cards {
            max-height: calc(100vh - 250px) !important;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 8px;
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }
        
        .final-state-section .column-cards::-webkit-scrollbar {
            width: 6px;
        }
        
        .final-state-section .column-cards::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .final-state-section .column-cards::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        /* Cabecera de columna fija */
        .kanban-column .column-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--column-bg);
        }
        
        /* Estilos para el scrollbar en las columnas */
        .kanban-column .column-cards::-webkit-scrollbar {
            width: 6px;
        }
        
        .kanban-column .column-cards::-webkit-scrollbar-thumb {
            background-color: var(--scrollbar-thumb);
            border-radius: 3px;
        }
        .column-header { padding: 0.75rem 1rem; position: sticky; top: 0; background-color: var(--column-bg); z-index: 10; border-bottom: 1px solid var(--column-border); display: flex; align-items: center; justify-content: space-between; }
        .column-title { font-weight: 600; color: var(--header-text); margin: 0; font-size: 1rem; }
        .column-cards { 
            padding: 0.75rem; 
            overflow-y: auto; 
            flex-grow: 1; 
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 100px;
        }
        
        .kanban-card { 
            background-color: var(--card-bg); 
            color: var(--card-text); 
            border-radius: 10px; 
            border: 1px solid var(--card-border); 
            border-left: 5px solid; 
            box-shadow: var(--card-shadow); 
            margin-bottom: 0.5rem;
            flex: 0 0 auto;
            min-height: 50px; /* Altura reducida cuando está colapsada */
            max-height: 400px; /* Altura máxima cuando está expandida */
            overflow: hidden;
            box-sizing: border-box;
            width: 100%;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            cursor: pointer;
            position: relative;
        }
        .kanban-card.dragging { 
            opacity: 0.8; 
            transform: rotate(2deg) scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15), 0 6px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: none;
        }
        .kanban-card:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 7px 14px rgba(0,0,0,0.1), 0 3px 6px rgba(0,0,0,0.08);
        }
        .kanban-card:active { 
            cursor: grabbing;
            transform: rotate(1deg) scale(0.99);
        }
        .kanban-card.drag-over {
            border: 2px dashed var(--primary-color);
            background-color: rgba(59, 130, 246, 0.05);
        }
        
        /* Efecto al soltar una tarjeta */
        .kanban-card.card-dropped {
            animation: cardDropped 0.3s ease-out;
        }
        
        @keyframes cardDropped {
            0% { transform: scale(0.95); opacity: 0.8; }
            70% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(1); }
        }
        
        /* Efecto de confirmación */
        .drop-confirmation {
            position: absolute;
            pointer-events: none;
            z-index: 1000;
        }
        
        /* Mejoras en el toast */
        .toast-notification {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .toast-notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .toast-notification i {
            font-size: 1.5rem;
        }
        
        /* Mejoras en las columnas durante el arrastre */
        .kanban-column.drag-over {
            box-shadow: inset 0 0 0 2px var(--primary-color);
            transform: translateY(-2px);
            transition: all 0.2s ease;
        }
        
        /* Efecto de elevación al pasar sobre una tarjeta */
        .kanban-card:hover {
            z-index: 5;
        }

        .kanban-card-header { 
            padding: 0.75rem 1.25rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            cursor: pointer; 
            overflow: visible;
            white-space: normal;
            word-break: break-word;
        }
        .kanban-card-header .fw-bold {
            font-size: 0.875rem;
            color: var(--card-text);
            margin-bottom: 0.25rem;
            white-space: normal;
            overflow: visible;
            text-overflow: clip;
            word-break: break-word;
        }
        .kanban-card-body { padding: 0 1.25rem 1.25rem 1.25rem; }
        .kanban-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid var(--card-border); background-color: var(--column-bg); font-size: 0.875rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; }
        
        .card-menu { font-size: 1rem; color: var(--text-muted); cursor: pointer; }
        .card-menu:hover { color: var(--primary-color); }
        .assigned-avatars .avatar-img { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--card-bg); margin-left: -10px; }
        .assigned-avatars .avatar-img:first-child { margin-left: 0; }
        .kanban-card:not(.collapsed) {
            min-height: 150px; /* Altura cuando está expandida */
        }
        
        .kanban-card.collapsed .kanban-card-body, 
        .kanban-card.collapsed .kanban-card-footer { 
            display: none; 
        }
        
        .kanban-card.collapsed {
            min-height: 50px; /* Altura cuando está colapsada */
        }
        
        /* Estilos para la columna de estados finales */
        .final-states-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            gap: 12px;
        }
        
        /* Secciones de estado final */
        .final-state-section {
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px dashed var(--column-border);
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 150px;
            overflow: hidden;
        }
        
        .final-state-section .column-cards {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 60px;
            max-height: calc(100vh - 200px); /* Altura máxima para forzar el scroll */
        }
        
        /* Asegurar que el contenedor de tarjetas use el espacio disponible */
        .final-state-section {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 150px;
            max-height: 100%;
            overflow: hidden;
        }
        
        /* Asegurar que las secciones de estado final en pantalla completa */
        :fullscreen .final-state-section {
            flex: 1;
        }
        
        .final-state-header {
            padding: 10px 12px;
            background-color: var(--header-bg);
            border-bottom: 1px solid var(--card-border);
        }
        
        .final-state-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--header-text);
        }
        
        .final-state-section .column-cards {
            padding: 8px;
            flex: 1;
            min-height: 100px;
            background-color: var(--column-bg);
            transition: background-color 0.2s;
        }
        
        .final-state-section.dragover .column-cards {
            background-color: var(--card-hover-bg);
        }
        
        .kanban-card {
            transform: none !important;
            will-change: transform, box-shadow;
        }
        
        .drag-clone {
            transform: rotate(1deg) !important;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2) !important;
            transition: transform 0.1s ease, box-shadow 0.1s ease !important;
            will-change: transform, box-shadow;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize production lines from PHP
        const productionLines = @json($productionLines ?? []);
        
        // Log process information to console
        console.log('Proceso actual:');
        console.log('- ID:', {{ $process->id }});
        console.log('- Nombre:', '{{ $process->name }}');
        console.log('- Descripción:', '{{ $process->description }}');
        
        // Log production lines to console
        console.log('\nLíneas de producción para el proceso {{ $process->name }} (ID: {{ $process->id }})');
        console.log('Número de líneas:', productionLines.length);
        productionLines.forEach((line, index) => {
            console.log(`${index + 1}. ${line.name} (ID: ${line.id})`);
        });
        
        let masterOrderList = []; // Empezará vacía y se llenará con datos ficticios.
        let draggedCard = null;
        let placeholder = null;
        let sourceColumnId = null; // Variable para guardar la columna de origen
        let dragSuccessful = false; // Variable para rastrear si el drop fue exitoso
        
        // Agregar órdenes del proceso desde el backend si existen
        @if(isset($processOrders) && count($processOrders) > 0)
            const processOrders = @json($processOrders);
            
            // Agregar las órdenes del proceso a la lista maestra
            if (processOrders && processOrders.length > 0) {
                masterOrderList = [...processOrders];
                console.log('Órdenes del proceso cargadas:', processOrders);
            }
        @endif

        // Columnas principales
        const columns = {
            // Columna de pendientes
            'pending_assignment': {
                id: 'pending_assignment',
                name: 'Pendientes Asignación',
                element: null,
                items: [],
                color: '#9ca3af', // Gris para pendientes
                productionLineId: null,
                type: 'status'
            },
            // Columnas de líneas de producción
            ...productionLines.reduce((acc, line) => ({
                ...acc,
                [`line_${line.id}`]: {
                    id: `line_${line.id}`,
                    name: line.name,
                    element: null,
                    items: [],
                    color: '#3b82f6', // Azul para líneas de producción
                    productionLineId: line.id,
                    type: 'production'
                }
            }), {}),
            // Columna de estados finales
            'final_states': {
                id: 'final_states',
                name: 'Estados Finales',
                element: null,
                items: [],
                color: '#6b7280', // Gris más oscuro para la columna de estados
                productionLineId: null,
                type: 'final_states',
                subStates: [
                    { id: 'completed', name: 'Finalizados', color: '#10b981' }, // Verde
                    { id: 'incidents', name: 'Incidencias', color: '#ef4444' }, // Rojo
                    { id: 'cancelled', name: 'Cancelados', color: '#6b7280' }  // Gris oscuro
                ]
            }
        };

        // Si no hay líneas de producción, agregar columna por defecto
        if (productionLines.length === 0) {
            columns['no_line'] = {
                id: 'no_line',
                name: 'Sin Línea',
                element: null,
                items: [],
                color: '#64748b',
                productionLineId: null,
                type: 'production'
            };
        }

        const kanbanBoard = document.querySelector('.kanban-board');
        const searchInput = document.getElementById('searchInput');

        function generateDummyData() {
            // No generar datos de ejemplo, solo limpiar columnas
            if (!masterOrderList || masterOrderList.length === 0) {
                console.log('No hay órdenes para mostrar');
                // Limpiar columnas existentes
                Object.keys(columns).forEach(key => {
                    if (columns[key]?.items) {
                        columns[key].items = [];
                    }
                });
                return; // Salir temprano si no hay datos

                // Generar datos de ejemplo
                const dummyOrders = [
                    { 
                        id: 1, 
                        order_id: 'OP-001', 
                        status: 'pending', 
                        box: 10, 
                        units: 100, 
                        created_at: '2025-06-20T10:00:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Producto A - Modelo Premium', 
                                customerId: 'Cliente Alpha' 
                            } 
                        },
                        statusColor: '#6b7280'
                    },
                        { 
                        id: 2, 
                        order_id: 'OP-002', 
                        status: 'in_progress', 
                        productionLineId: 1,
                        box: 5, 
                        units: 50, 
                        created_at: '2025-06-20T11:00:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Producto B - Edición Limitada', 
                                customerId: 'Cliente Beta' 
                            } 
                        },
                        statusColor: '#3b82f6'
                    },
                    { 
                        id: 3, 
                        order_id: 'OP-003', 
                        status: 'completed', 
                        productionLineId: 2,
                        box: 20, 
                        units: 200, 
                        created_at: '2025-06-19T09:00:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Componente C - Estándar', 
                                customerId: 'Cliente Gamma' 
                            } 
                        },
                        statusColor: '#10b981'
                    },
                    { 
                        id: 4, 
                        order_id: 'OP-004', 
                        status: 'incidents', 
                        box: 1, 
                        units: 10, 
                        created_at: '2025-06-18T15:00:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Producto D - Prototipo', 
                                customerId: 'Cliente Delta' 
                            } 
                        },
                        statusColor: '#ef4444'
                    },
                    { 
                        id: 5, 
                        order_id: 'OP-005', 
                        status: 'cancelled', 
                        box: 8, 
                        units: 80, 
                        created_at: '2025-06-17T12:00:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Producto E - Básico', 
                                customerId: 'Cliente Epsilon' 
                            } 
                        },
                        statusColor: '#6b7280'
                    },
                    { 
                        id: 6, 
                        order_id: 'OP-006', 
                        status: 'pending', 
                        box: 12, 
                        units: 120, 
                        created_at: '2025-06-21T08:00:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Producto F - Industrial', 
                                customerId: 'Cliente Zeta' 
                            } 
                        },
                        statusColor: '#6b7280'
                    },
                    { 
                        id: 7, 
                        order_id: 'OP-007', 
                        status: 'in_progress', 
                        productionLineId: 1,
                        box: 15, 
                        units: 150, 
                        created_at: '2025-06-21T09:30:00Z', 
                        json: { 
                            refer: { 
                                descrip: 'Accesorio G - Kit Completo', 
                                customerId: 'Cliente Eta' 
                            } 
                        },
                        statusColor: '#3b82f6'
                    }
                ];


                // Asignar las órdenes de ejemplo a la lista maestra
                masterOrderList = [...dummyOrders];
                console.log('Datos de ejemplo generados:', dummyOrders);
            }

            // Limpiar columnas existentes
            Object.keys(columns).forEach(key => {
                if (columns[key].items) {
                    columns[key].items = [];
                }
            });

            // Asignar órdenes a las columnas correspondientes
            masterOrderList.forEach(order => {
                let targetColumn = 'pending_assignment';
                
                // Determinar la columna de destino basada en el estado
                if (order.status === 'completed' || order.status === 'incidents' || order.status === 'cancelled') {
                    targetColumn = 'final_states';
                } else if (order.productionLineId) {
                    // Buscar la columna de la línea de producción
                    const lineColumn = Object.entries(columns).find(([key, col]) => 
                        col.productionLineId === order.productionLineId
                    );
                    if (lineColumn) {
                        targetColumn = lineColumn[0];
                    }
                }
                
                // Asegurarse de que la columna tenga un array de items
                if (!columns[targetColumn].items) {
                    columns[targetColumn].items = [];
                }
                
                // Agregar la orden a la columna correspondiente
                columns[targetColumn].items.push(order);
            });

            // Renderizar el tablero
            renderBoard(masterOrderList);
            
            // Mostrar notificación solo si se cargaron datos de ejemplo
            if (masterOrderList.length > 0 && 
                masterOrderList[0] && 
                masterOrderList[0].order_id && 
                typeof masterOrderList[0].order_id === 'string' && 
                masterOrderList[0].order_id.startsWith('OP-')) {
                showToast('Datos de ejemplo cargados correctamente', {
                    icon: 'check-circle',
                    iconColor: '#10b981'
                });
            }
        }
        
        function applyAndRenderFilters() {
            const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
            
            // Si no hay término de búsqueda, mostrar todas las órdenes
            if (!searchTerm) {
                // Reconstruir el tablero con todas las órdenes en sus columnas correspondientes
                Object.values(columns).forEach(column => {
                    if (column.items) column.items = [];
                });
                
                masterOrderList.forEach(order => {
                    let targetColumn = 'pending_assignment';
                    
                    // Determinar la columna de destino basada en el estado
                    if (order.status === 'completed' || order.status === 'incidents' || order.status === 'cancelled') {
                        targetColumn = 'final_states';
                    } else if (order.productionLineId) {
                        // Buscar la columna de la línea de producción
                        const lineColumn = Object.entries(columns).find(([key, col]) => 
                            col.productionLineId === order.productionLineId
                        );
                        if (lineColumn) {
                            targetColumn = lineColumn[0];
                        }
                    }
                    
                    // Asegurarse de que la columna tenga un array de items
                    if (!columns[targetColumn].items) {
                        columns[targetColumn].items = [];
                    }
                    
                    // Agregar la orden a la columna correspondiente
                    columns[targetColumn].items.push(order);
                });
                
                renderBoard(masterOrderList);
                return;
            }

            // Filtrar órdenes por el término de búsqueda
            const filteredOrders = masterOrderList.filter(order => {
                // Buscar coincidencias en varios campos
                return (
                    (order.order_id && String(order.order_id).toLowerCase().includes(searchTerm)) || 
                    (order.json?.refer?.descrip && order.json.refer.descrip.toLowerCase().includes(searchTerm)) ||
                    (order.json?.refer?.customerId && order.json.refer.customerId.toLowerCase().includes(searchTerm)) ||
                    (order.status && String(order.status).toLowerCase().includes(searchTerm))
                );
            });
            renderBoard(filteredOrders);
        }

        function renderBoard(ordersToRender) {
            console.log('Renderizando tablero Kanban...');
            
            // Inicializar las columnas del tablero aunque no haya órdenes
            initializeBoardColumns();
            
            // Resetear variables de arrastre
            draggedCard = null;
            dragClone = null;
            dragSuccessful = false;
            
            // IMPORTANTE: Limpiar completamente todas las tarjetas del tablero
            // para evitar duplicados al renderizar
            document.querySelectorAll('.kanban-column .column-cards').forEach(container => {
                container.innerHTML = '';
            });
            
            document.querySelectorAll('.final-state-section .column-cards').forEach(container => {
                container.innerHTML = '';
            });
            
            // Si no hay órdenes para renderizar, mostrar el tablero vacío pero construido
            if (!ordersToRender || ordersToRender.length === 0) {
                console.log('No hay órdenes para mostrar, pero el tablero se ha construido');
                return;
            }
            
            // Distribuir las órdenes en las columnas correspondientes según las reglas especificadas
            ordersToRender.forEach(order => {
                let targetColumnId = 'pending_assignment'; // Por defecto, va a pendientes
                let subStateId = null; // Para estados finales
                
                // Regla 1: Estados finales van a la columna 'final_states'
                if (order.status === 'completed' || order.status === 'incidents' || order.status === 'cancelled') {
                    targetColumnId = 'final_states';
                    
                    // Determinar la subsección correcta dentro de estados finales
                    if (order.status === 'completed') subStateId = 'completed';
                    else if (order.status === 'incidents') subStateId = 'incidents';
                    else if (order.status === 'cancelled') subStateId = 'cancelled';
                }
                // Regla 2: Si tiene production_line_id, va a la columna de esa línea
                else if (order.productionLineId || order.production_line_id) {
                    // Usar cualquiera de los dos campos que esté disponible
                    const lineId = order.productionLineId || order.production_line_id;
                    
                    // Asegurar que ambos campos estén sincronizados
                    order.productionLineId = lineId;
                    order.production_line_id = lineId;
                    
                    console.log(`Inicializando orden ${order.id} con production_line_id: ${lineId}`);
                    
                    // Buscar la columna de la línea de producción
                    const lineColumn = Object.entries(columns).find(([key, col]) => 
                        col.productionLineId === lineId
                    );
                    if (lineColumn) {
                        targetColumnId = lineColumn[0];
                    }
                }
                // Regla 3: Si no tiene production_line_id y no es estado final, va a pendientes asignación
                // (ya está asignado por defecto)
                
                const targetColumn = columns[targetColumnId];
                if (!targetColumn) return;
                
                // Renderizar la tarjeta en la columna correspondiente
                if (targetColumnId === 'final_states' && subStateId) {
                    // Para estados finales, necesitamos encontrar la subsección correcta
                    const subStateContainer = document.querySelector(`#${targetColumnId} .column-cards[data-state="${subStateId}"]`);
                    if (subStateContainer) {
                        const cardElement = createCardElement(order);
                        subStateContainer.appendChild(cardElement);
                    }
                } else {
                    // Para columnas normales (pendientes y líneas de producción)
                    const columnCardsContainer = document.querySelector(`#${targetColumnId} .column-cards`);
                    if (columnCardsContainer) {
                        const cardElement = createCardElement(order);
                        columnCardsContainer.appendChild(cardElement);
                    }
                }
                
                // Asignar la orden a la columna en el modelo de datos
                if (!targetColumn.items) targetColumn.items = [];
                targetColumn.items.push(order);
                
                // Registrar en consola para depuración
                console.log(`Orden ${order.order_id} (ID: ${order.id}) asignada a columna ${targetColumnId}${subStateId ? ' (subsección: ' + subStateId + ')' : ''}`, {
                    status: order.status,
                    status_code: order.status_code,
                    productionLineId: order.productionLineId
                });
            });
        }

        function initializeBoardColumns() {
            kanbanBoard.innerHTML = '';
            const fragment = document.createDocumentFragment();
            
            Object.values(columns).forEach(column => {
                const columnElement = document.createElement('div');
                columnElement.dataset.productionLineId = column.productionLineId || '';
                columnElement.className = 'kanban-column';
                columnElement.id = column.id;
                
                // Si es la columna de estados finales, crear una estructura diferente
                if (column.type === 'final_states') {
                    // Crear el encabezado de la columna
                    columnElement.innerHTML = `
                        <div class="column-header">
                            <h3 class="column-title" style="color: ${column.color};">${column.name}</h3>
                        </div>
                        <div class="final-states-container">
                            ${column.subStates.map(subState => `
                                <div class="final-state-section" 
                                     data-state="${subState.id}" 
                                     style="border-left: 4px solid ${subState.color};">
                                    <div class="final-state-header">
                                        <span class="final-state-title" style="color: ${subState.color};">
                                            ${subState.name}
                                        </span>
                                    </div>
                                    <div class="column-cards" data-state="${subState.id}"></div>
                                </div>
                            `).join('')}
                        </div>`;
                } else {
                    // Columnas normales (pendientes y líneas de producción)
                    columnElement.innerHTML = `
                        <div class="column-header">
                            <h3 class="column-title" style="color: ${column.color};">${column.name}</h3>
                        </div>
                        <div class="column-cards"></div>`;
                }
                
                columnElement.ondragover = dragOver;
                columnElement.ondrop = drop;
                fragment.appendChild(columnElement);
            });
            
            kanbanBoard.appendChild(fragment);
            
            // Guardar referencia a los elementos de las columnas
            Object.values(columns).forEach(column => {
                column.element = document.getElementById(column.id);
            });
        }
        
        function createCardElement(order) {
            // Find which column this order is in
            let columnInfo = null;
            for (const [key, col] of Object.entries(columns)) {
                if (col.items && col.items.some(item => item.id === order.id)) {
                    columnInfo = col;
                    break;
                }
            }
            
            const card = document.createElement('div');
            card.className = 'kanban-card collapsed';
            card.dataset.id = order.id;
            
            // Establecer valores por defecto
            const defaultColor = '#3b82f6'; // Color azul por defecto
            
            // Configurar propiedades basadas en columnInfo si existe
            if (columnInfo) {
                card.dataset.productionLineId = columnInfo.productionLineId || '';
                card.style.borderLeftColor = columnInfo.color || defaultColor;
            } else {
                card.dataset.productionLineId = '';
                card.style.borderLeftColor = defaultColor;
            }
            
            // Configurar eventos de arrastrar
            card.draggable = true;
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
            
            card.innerHTML = createCardHTML(order);
            return card;
        }

        function createCardHTML(order) {
            const createdAtFormatted = new Date(order.created_at).toLocaleDateString();
            // Formatear fecha de entrega si existe
            const deliveryDateFormatted = order.delivery_date ? new Date(order.delivery_date).toLocaleDateString() : null;
            // Obtener la descripción del proceso desde la variable global o del objeto order
            const processDescription = '{{ $process->description }}';
            
            return `
                <div class="kanban-card-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold text-sm text-gray-700 dark:text-gray-200">#${order.order_id}</div>
                            ${processDescription ? `<div class="text-xs text-muted mt-1">${processDescription}</div>` : ''}
                        </div>
                        <span class="card-menu" role="button" onclick="event.stopPropagation(); showCardMenu('${order.id}')">
                            <i class="fas fa-ellipsis-h"></i>
                        </span>
                    </div>
                </div>
                <div class="kanban-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs text-muted">${order.json?.refer?.customerId || 'N/A'}</span>
                        <span class="badge" style="background-color: ${order.statusColor || '#6b7280'}">
                            ${order.status?.replace('_', ' ').toUpperCase() || 'PENDING'}
                        </span>
                    </div>
                    <div class="text-sm mb-2">${order.json?.refer?.descrip || 'Sin descripción'}</div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-box text-muted me-1"></i>
                            <span class="text-xs">${order.box || 0} cajas</span>
                            <i class="fas fa-cubes text-muted ms-2 me-1"></i>
                            <span class="text-xs">${order.units || 0} uds</span>
                        </div>
                        <div class="text-xs text-muted">
                            <i class="far fa-calendar-alt me-1"></i>${createdAtFormatted}
                        </div>
                    </div>
                    ${deliveryDateFormatted ? `
                    <div class="d-flex justify-content-end align-items-center">
                        <div class="text-xs text-muted" style="color: #e67e22 !important;">
                            <i class="fas fa-truck me-1"></i>${deliveryDateFormatted}
                        </div>
                    </div>` : ''}
                </div>
                <div class="kanban-card-footer">
                     <span class="text-xs fw-medium">{{__("Assigned")}}</span>
                    <div class="assigned-avatars d-flex align-items-center">
                        <img class="avatar-img" src="https://i.pravatar.cc/40?img=1" alt="user" title="User 1">
                        <img class="avatar-img" src="https://i.pravatar.cc/40?img=2" alt="user" title="User 2">
                    </div>
                </div>`;
        }

        function handleDragStart(event) {
            draggedCard = event.target.closest('.kanban-card');
            if (!draggedCard) return;

            // Guardar la columna de origen
            const sourceColumn = draggedCard.closest('.kanban-column');
            if (sourceColumn) {
                sourceColumnId = sourceColumn.id;
                console.log(`Iniciando arrastre desde la columna: ${sourceColumnId}`);
            } else {
                sourceColumnId = null;
                console.warn('No se pudo determinar la columna de origen.');
            }
            
            // Guardar la transformación original para restaurarla después
            draggedCard.originalTransform = draggedCard.style.transform;
            
            // Agregar clase de arrastre con retraso para mejor feedback
            setTimeout(() => {
                if (!draggedCard) return;
                
                draggedCard.classList.add('dragging');
                
                // Crear clon para el efecto de arrastre
                const rect = draggedCard.getBoundingClientRect();
                dragClone = draggedCard.cloneNode(true);
                dragClone.classList.add('drag-clone');
                dragClone.style.width = `${rect.width}px`;
                dragClone.style.height = `${rect.height}px`;
                dragClone.style.position = 'fixed';
                dragClone.style.top = `${rect.top}px`;
                dragClone.style.left = `${rect.left}px`;
                dragClone.style.opacity = '0.8';
                dragClone.style.zIndex = '1000';
                dragClone.style.pointerEvents = 'none';
                dragClone.style.transform = 'none'; // Asegurar que el clon no tenga transformación
                document.body.appendChild(dragClone);
                
                // Ocultar la tarjeta original temporalmente pero mantener su espacio
                draggedCard.style.visibility = 'hidden';
                draggedCard.style.position = 'absolute';
                draggedCard.style.top = '0';
                draggedCard.style.left = '0';
            }, 10);
            
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedCard.dataset.id);
            
            // Agregar clase al cuerpo para indicar que se está arrastrando
            document.body.classList.add('dragging-active');
        }
        
        function handleDragEnd(event) {
            if (!draggedCard) return;
            
            // Restaurar estilos
            draggedCard.classList.remove('dragging');
            
            // Eliminar el clon si existe
            if (dragClone && dragClone.parentNode) {
                dragClone.parentNode.removeChild(dragClone);
                dragClone = null;
            }
            
            // Restaurar la tarjeta original
            if (draggedCard) {
                // Restaurar completamente todos los estilos para evitar problemas visuales
                draggedCard.style.visibility = 'visible';
                draggedCard.style.position = '';
                draggedCard.style.top = '';
                draggedCard.style.left = '';
                draggedCard.style.transform = draggedCard.originalTransform || '';
                draggedCard.style.width = '';
                draggedCard.style.height = '';
                draggedCard.style.opacity = '';
                draggedCard.style.zIndex = '';
                
                // Asegurarse de que la tarjeta sea visible y tenga el tamaño correcto
                setTimeout(() => {
                    if (draggedCard) {
                        draggedCard.style.display = '';
                        draggedCard.style.visibility = 'visible';
                    }
                }, 50);
                draggedCard.style.zIndex = '';
                
                // Forzar un reflow para asegurar que los estilos se apliquen
                void draggedCard.offsetHeight;
            }
            
            // Resetear el indicador de éxito para el próximo arrastre
            dragSuccessful = false;
            
            // Limpiar referencias
            draggedCard = null;
            
            // Quitar clase del cuerpo
            document.body.classList.remove('dragging-active');
            
            // Resetear zonas de drop
            resetDropZones();
        }
        
        function dragOver(event) {
            event.preventDefault();
            if (!draggedCard) return;
            
            // Encontrar el contenedor objetivo más cercano
            const targetContainer = event.target.closest('.kanban-column, .final-state-section, .kanban-card');
            if (!targetContainer) return;
            
            // Determinar el área de destino
            let dropZone;
            let targetColumn;
            
            if (targetContainer.classList.contains('kanban-card')) {
                // Si el objetivo es una tarjeta, obtener su columna
                targetColumn = targetContainer.closest('.kanban-column');
                dropZone = targetColumn?.querySelector('.column-cards');
            } else if (targetContainer.classList.contains('final-state-section')) {
                // Si es una sección de estado final
                targetColumn = targetContainer.closest('.kanban-column');
                dropZone = targetContainer.querySelector('.column-cards');
                
                // Resaltar la sección de estado
                document.querySelectorAll('.final-state-section').forEach(section => {
                    section.style.transform = section === targetContainer ? 'scale(1.01)' : '';
                    section.style.boxShadow = section === targetContainer ? '0 4px 12px rgba(0,0,0,0.1)' : '';
                    section.style.transition = 'all 0.2s ease';
                });
            } else {
                // Si es una columna normal
                targetColumn = targetContainer;
                dropZone = targetContainer.querySelector('.column-cards');
            }
            
            // Resaltar la columna/área de destino
            if (targetColumn) {
                // Quitar resaltado de todas las columnas
                document.querySelectorAll('.kanban-column').forEach(col => {
                    col.style.boxShadow = '';
                    col.style.transform = '';
                });
                
                // Resaltar la columna objetivo
                if (!targetContainer.classList.contains('kanban-card')) {
                    targetColumn.style.boxShadow = 'inset 0 0 0 2px var(--primary-color)';
                    targetColumn.style.transform = 'translateY(-2px)';
                    targetColumn.style.transition = 'all 0.2s ease';
                }
            }
            
            // Resaltar el área de soltado
            if (dropZone) {
                dropZone.style.background = 'rgba(59, 130, 246, 0.05)';
                dropZone.style.borderRadius = '8px';
                dropZone.style.border = '2px dashed var(--primary-color)';
                dropZone.style.transition = 'all 0.2s ease';
            }
        }

        function drop(event) {
            let targetColumnEl = null; // Declaración única al inicio
            event.preventDefault();
            if (!draggedCard) return;
            
            console.log('=== INICIO DROP EVENT ===');
            console.log('Tarjeta arrastrada ID:', draggedCard.dataset.id);
            
            // Limpiar cualquier estilo residual del drag
            draggedCard.style.position = '';
            draggedCard.style.top = '';
            draggedCard.style.left = '';
            draggedCard.style.visibility = 'visible';
            
            // Encontrar el contenedor de destino
            let targetContainer = event.target.closest('.kanban-column, .final-state-section, .kanban-card, .column-cards');
            if (!targetContainer) {
                console.log('No se encontró contenedor destino');
                resetDropZones();
                return;
            }
            
            console.log('Target container tipo:', targetContainer.classList.toString());
            console.log('Target container ID:', targetContainer.id || 'Sin ID');
            
            // Si el objetivo es una tarjeta o un contenedor de tarjetas, manejar el reordenamiento
            if ((targetContainer.classList.contains('kanban-card') && targetContainer !== draggedCard) || 
                targetContainer.classList.contains('column-cards')) {
                
                // Determinar el contenedor de tarjetas correcto
                let cardsContainer;
                let targetCard = null;
                
                if (targetContainer.classList.contains('kanban-card')) {
                    // Si el objetivo es una tarjeta
                    targetCard = targetContainer;
                    cardsContainer = targetContainer.closest('.column-cards');
                    console.log('Destino es una tarjeta, contenedor:', cardsContainer?.id || 'Sin ID');
                } else {
                    // Si el objetivo es el contenedor de tarjetas
                    cardsContainer = targetContainer;
                    targetColumnEl = cardsContainer.closest('.kanban-column'); // FIX: Get parent column
                    console.log('Destino es un contenedor de tarjetas, columna padre:', targetColumnEl?.id || 'Sin ID');
                }
                
                // Verificar que estamos en una columna válida
                if (!cardsContainer) {
                    console.log('No se encontró contenedor de tarjetas válido');
                    return;
                }
                
                // Si soltamos directamente en el contenedor (no en una tarjeta)
                if (!targetCard) {
                    // Añadir al final del contenedor
                    cardsContainer.appendChild(draggedCard);
                } else {
                    // Determinar si insertar antes o después de la tarjeta objetivo
                    const targetRect = targetCard.getBoundingClientRect();
                    const targetMiddleY = targetRect.top + (targetRect.height / 2);
                    const mouseY = event.clientY;
                    
                    // Eliminar la tarjeta de su posición actual primero
                    if (draggedCard.parentNode) {
                        draggedCard.parentNode.removeChild(draggedCard);
                    }
                    
                    if (mouseY < targetMiddleY) {
                        // Insertar antes del objetivo
                        cardsContainer.insertBefore(draggedCard, targetCard);
                    } else {
                        // Insertar después del objetivo
                        cardsContainer.insertBefore(draggedCard, targetCard.nextSibling);
                    }
                }
                
                // Actualizar el modelo de datos para reflejar el nuevo orden
                updateColumnDataOrder(cardsContainer);
                
                // Marcar como exitoso y salir
                dragSuccessful = true;
                resetDropZones();
                return;
            }
            
            // Función para actualizar el orden de las tarjetas en el modelo de datos
            function updateColumnDataOrder(container) {
                // Encontrar la columna a la que pertenece este contenedor
                const columnElement = container.closest('.kanban-column');
                if (!columnElement) return;
                
                const columnId = columnElement.dataset.columnId;
                if (!columnId || !columns[columnId]) return;
                
                // Obtener todas las tarjetas en el orden actual del DOM
                const cardElements = Array.from(container.querySelectorAll('.kanban-card'));
                
                // Actualizar el orden en el modelo de datos
                columns[columnId].items = cardElements.map(cardEl => {
                    const cardId = parseInt(cardEl.dataset.id);
                    return masterOrderList.find(order => order.id === cardId);
                }).filter(Boolean); // Eliminar posibles undefined
            }
            
            // Obtener el ID de la tarjeta arrastrada
            const cardId = parseInt(draggedCard.dataset.id);
            
            // Verificar si ya existe una tarjeta con el mismo ID en el destino
            // para evitar duplicados
            const existingCards = document.querySelectorAll(`.kanban-card[data-id="${cardId}"]`);
            if (existingCards.length > 1) {
                console.warn(`Se encontraron ${existingCards.length} instancias de la tarjeta ${cardId}`);
                // Eliminar todas las instancias excepto la que estamos arrastrando
                existingCards.forEach(card => {
                    if (card !== draggedCard && card.parentNode) {
                        card.parentNode.removeChild(card);
                    }
                });
            }
            
            // Si el objetivo es el área de tarjetas, subir al contenedor padre
            if (targetContainer.classList.contains('column-cards')) {
                targetContainer = targetContainer.closest('.kanban-column, .final-state-section') || targetContainer;
            }
            
            // Determinar el destino final (columna y estado)
            let targetState, targetStateName = '', targetCardsContainer;
            
            if (targetContainer.classList.contains('kanban-card')) {
                // Si se suelta sobre otra tarjeta, obtener su columna
                targetColumnEl = targetContainer.closest('.kanban-column');
                targetCardsContainer = targetColumnEl?.querySelector('.column-cards');
                targetState = null;
                
                // Verificar si estamos en una sección de estado final
                const finalStateSection = targetContainer.closest('.final-state-section');
                if (finalStateSection) {
                    targetState = finalStateSection.dataset.state;
                    targetStateName = finalStateSection.querySelector('.final-state-title')?.textContent || '';
                }
            } else if (targetContainer.classList.contains('final-state-section')) {
                // Si es una sección de estado final
                targetState = targetContainer.dataset.state;
                targetColumnEl = targetContainer.closest('.kanban-column');
                targetCardsContainer = targetContainer.querySelector('.column-cards');
                targetStateName = targetContainer.querySelector('.final-state-title')?.textContent || '';
            } else if (targetContainer.classList.contains('kanban-column')) {
                // Si es una columna normal, obtener su contenedor de tarjetas
                targetColumnEl = targetContainer;
                targetCardsContainer = targetContainer.querySelector('.column-cards');
                targetState = null;
                
                // Verificar si hay una sección de estado final dentro de la columna
                if (targetColumnEl.id === 'final_states') {
                    // Si es la columna de estados finales, buscar la sección más cercana al punto de soltado
                    const finalStateSections = Array.from(targetColumnEl.querySelectorAll('.final-state-section'));
                    const dropY = event.clientY;
                    
                    // Encontrar la sección más cercana al punto de soltado
                    const closestSection = finalStateSections.reduce((closest, section) => {
                        const rect = section.getBoundingClientRect();
                        const distance = Math.abs(rect.top + (rect.height / 2) - dropY);
                        
                        if (distance < closest.distance) {
                            return { section, distance };
                        }
                        return closest;
                    }, { section: null, distance: Infinity }).section;
                    
                    if (closestSection) {
                        targetState = closestSection.dataset.state;
                        targetCardsContainer = closestSection.querySelector('.column-cards');
                        targetStateName = closestSection.querySelector('.final-state-title')?.textContent || '';
                    }
                }
            }
            
            if (!targetColumnEl) {
                resetDropZones();
                return;
            }
            
            const targetColumnId = targetColumnEl.id;
            const targetColumn = columns[targetColumnId];
            if (!targetColumn) {
                resetDropZones();
                return;
            }
            
            // Obtener información de la columna de origen
            const previousContainer = draggedCard.closest('.kanban-column, .final-state-section');
            const previousColumnId = previousContainer?.closest('.kanban-column')?.id;
            const previousColumn = previousColumnId ? columns[previousColumnId] : null;
            
            // Si se suelta en la misma ubicación, no hacer nada
            if (previousContainer === targetContainer) {
                resetDropZones();
                return;
            }
            
            // Obtener el ID de la tarjeta arrastrada
            const draggedCardId = parseInt(draggedCard.dataset.id);
            
            // Encontrar la orden en la lista maestra
            let orderObj = masterOrderList.find(o => o.id === draggedCardId);
            if (!orderObj) {
                console.error('Orden no encontrada:', draggedCardId);
                resetDropZones();
                return;
            }
            
            // Remover de la columna anterior
            if (previousColumn) {
                previousColumn.items = previousColumn.items.filter(item => item.id !== orderObj.id);
                
                // Asegurarse de que la tarjeta se elimine físicamente de su contenedor anterior
                if (draggedCard && draggedCard.parentNode) {
                    // Guardar una referencia al nodo antes de eliminarlo
                    const originalCard = draggedCard;
                    draggedCard.parentNode.removeChild(draggedCard);
                    draggedCard = originalCard;
                }
            }
            
            // Actualizar el modelo de datos
            const orderId = draggedCard.dataset.id;
            // Verificar que estamos trabajando con la misma orden
            if (orderObj.id != orderId) {
                console.warn('ID de orden no coincide, actualizando referencia');
                const updatedOrder = masterOrderList.find(o => o.id == orderId);
                if (!updatedOrder) {
                    console.error('No se encontró la orden en masterOrderList:', orderId);
                    return;
                }
                // Actualizar referencia a la orden
                orderObj = updatedOrder;
            }
            
            console.log('Orden antes de actualizar:', {
                id: orderObj.id,
                productionLineId: orderObj.productionLineId,
                production_line_id: orderObj.production_line_id,
                status: orderObj.status
            });
            
            // Encontrar la columna de destino
            targetColumnEl = targetContainer.closest('.kanban-column');
            let targetStateEl = targetContainer.closest('.final-state-section');
            
            console.log('Destino encontrado:', {
                targetColumnEl: targetColumnEl?.id || 'No encontrado',
                targetStateEl: targetStateEl?.dataset?.state || 'No encontrado'
            });
            
            // Actualizar el estado de la orden según la columna de destino
            targetState = null;
            if (targetStateEl) {
                // Si es un estado final
                targetState = targetStateEl.dataset.state;
                orderObj.status = targetState;
                orderObj.statusColor = {
                    'completed': '#10b981',
                    'cancelled': '#ef4444',
                    'incidents': '#f59e0b',
                    'pending': '#6b7280',
                    'in_progress': '#3b82f6'
                }[targetState] || '#3b82f6';
                
                // Si es un estado final, quitar de la línea de producción
                if (['completed', 'incidents', 'cancelled'].includes(targetState)) {
                    orderObj.productionLineId = null;
                    orderObj.production_line_id = null; // Asegurar que ambos campos se actualicen
                }
            } else if (targetColumnEl) {
                // Si es una columna de producción
                const targetColumnId = targetColumnEl.id;
                const targetColumn = columns[targetColumnId];
                
                if (targetColumn && targetColumn.type === 'production') {
                    // Actualizar tanto productionLineId como production_line_id para asegurar consistencia
                    orderObj.productionLineId = targetColumn.productionLineId;
                    orderObj.production_line_id = targetColumn.productionLineId; // Añadido para asegurar que se envíe correctamente
                    orderObj.status = 'in_progress';
                    orderObj.statusColor = '#3b82f6';
                    console.log(`Orden ${orderObj.id} asignada a línea de producción ${targetColumn.productionLineId}`);
                } else if (targetColumnId === 'pending_assignment') {
                    // Si se mueve a pendientes
                    orderObj.productionLineId = null;
                    orderObj.production_line_id = null; // Añadido para asegurar que se envíe correctamente
                    orderObj.status = 'pending';
                    orderObj.statusColor = '#6b7280';
                    console.log(`Orden ${orderObj.id} movida a pendientes de asignación`);
                }
            }
            
            // Aplicar efecto visual al cambiar de estado
            if (draggedCard) {
                draggedCard.style.transition = 'all 0.3s ease';
                draggedCard.style.transform = 'scale(1.02)';
                draggedCard.style.boxShadow = '0 10px 20px rgba(0,0,0,0.15)';
                
                // Actualizar el color del borde según el estado
                if (orderObj.statusColor) {
                    draggedCard.style.borderLeftColor = orderObj.statusColor;
                }
                
                // Restaurar la transición después de la animación
                setTimeout(() => {
                    if (draggedCard) {
                        draggedCard.style.transition = 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)';
                        draggedCard.style.transform = '';
                        draggedCard.style.boxShadow = '';
                    }
                }, 300);
            }
            
            // Inicializar el array de items si no existe
            targetColumn.items = targetColumn.items || [];
            
            // Agregar a la columna si no existe ya
            if (!targetColumn.items.some(item => item.id === orderObj.id)) {
                targetColumn.items.push(orderObj);
            }
            
            // Actualizar el ID de la línea de producción en la tarjeta y en el objeto de datos maestro
            if (draggedCard) {
                draggedCard.dataset.productionLineId = orderObj.productionLineId || '';
                
                // Actualizar el objeto en masterOrderList para mantener la sincronización
                const masterOrderIndex = masterOrderList.findIndex(o => o.id === orderObj.id);
                if (masterOrderIndex !== -1) {
                    masterOrderList[masterOrderIndex].productionLineId = orderObj.productionLineId;
                    masterOrderList[masterOrderIndex].production_line_id = orderObj.productionLineId;
                    console.log(`Actualizado masterOrderList[${masterOrderIndex}].production_line_id = ${orderObj.productionLineId}`);
                }
                
                // Actualizar los estilos de la tarjeta
                if (orderObj.statusColor) {
                    draggedCard.style.borderLeftColor = orderObj.statusColor;
                }
                
                // Actualizar el estado en el atributo de datos
                draggedCard.dataset.status = orderObj.status;
            }
            
            // Mover la tarjeta al contenedor correcto con animación
            let cardsContainer = null;
            
            // Verificar que tenemos una columna de destino válida
            if (targetColumnEl) {
                if (targetState) {
                    // Buscar el contenedor de la sección de estado específica
                    const stateSection = targetColumnEl.querySelector(`.final-state-section[data-state="${targetState}"]`);
                    if (stateSection) {
                        cardsContainer = stateSection.querySelector('.column-cards');
                    }
                } else {
                    // Usar el contenedor de la columna principal
                    cardsContainer = targetColumnEl.querySelector('.column-cards');
                }
                
                // Si no se encontró un contenedor, usar el predeterminado
                if (!cardsContainer) {
                    console.warn('No se encontró el contenedor de destino, usando contenedor predeterminado');
                    cardsContainer = targetColumnEl.querySelector('.column-cards');
                }
            } else {
                console.error('No se encontró una columna de destino válida');
            }
            
            if (cardsContainer) {
                // IMPORTANTE: Verificar si ya existe una tarjeta con el mismo ID en el destino
                // y eliminarla para evitar duplicados
                const existingCard = cardsContainer.querySelector(`.kanban-card[data-id="${orderObj.id}"]`);
                if (existingCard && existingCard !== draggedCard) {
                    console.log(`Eliminando tarjeta duplicada con ID ${orderObj.id} del contenedor destino`);
                    cardsContainer.removeChild(existingCard);
                }
                
                // Actualizar el estilo de la tarjeta según el estado
                if (targetState) {
                    const color = {
                        'completed': '#10b981',
                        'incidents': '#ef4444',
                        'cancelled': '#6b7280'
                    }[targetState] || '#6b7280';
                    
                    draggedCard.style.borderLeftColor = color;
                }
                
                // Importante: Restaurar los estilos de posición antes de mover la tarjeta
                // para que se posicione correctamente en el nuevo contenedor
                draggedCard.style.position = '';
                draggedCard.style.top = '';
                draggedCard.style.left = '';
                draggedCard.style.visibility = 'visible';
                
                // Añadir clase temporal para la animación
                draggedCard.classList.add('card-dropped');
                
                // Insertar la tarjeta en el nuevo contenedor
                cardsContainer.appendChild(draggedCard);
                
                // Marcar el arrastre como exitoso
                dragSuccessful = true;
                
                // Mostrar efecto de confirmación
                const confirmEl = document.createElement('div');
                confirmEl.className = 'drop-confirmation';
                confirmEl.innerHTML = '<i class="fas fa-check-circle"></i>';
                confirmEl.style.position = 'absolute';
                confirmEl.style.top = '50%';
                confirmEl.style.left = '50%';
                confirmEl.style.transform = 'translate(-50%, -50%) scale(0.5)';
                confirmEl.style.opacity = '0';
                confirmEl.style.transition = 'all 0.3s ease';
                confirmEl.style.fontSize = '48px';
                confirmEl.style.color = targetState ? 
                    (targetState === 'completed' ? '#10b981' : 
                     targetState === 'incidents' ? '#ef4444' : '#6b7280') : 
                    '#3b82f6';
                
                cardsContainer.appendChild(confirmEl);
                
                // Animar el efecto de confirmación
                requestAnimationFrame(() => {
                    confirmEl.style.transform = 'translate(-50%, -50%) scale(1.5)';
                    confirmEl.style.opacity = '0.9';
                    
                    setTimeout(() => {
                        confirmEl.style.transform = 'translate(-50%, -50%) scale(1)';
                        confirmEl.style.opacity = '0';
                        
                        setTimeout(() => {
                            confirmEl.remove();
                        }, 300);
                    }, 200);
                });
            }
            
            // Mostrar notificación
            const fromColumn = previousColumn ? previousColumn.name : 'Sin asignar';
            const toColumn = targetState ? 
                targetColumn.subStates?.find(s => s.id === targetState)?.name || targetColumn.name : 
                targetColumn.name;
            
            showToast(`<i class="fas fa-arrow-right me-2"></i> Orden movida de <strong>${fromColumn}</strong> a <strong>${toColumn}</strong>`, {
                icon: 'check-circle',
                iconColor: targetState ? 
                    (targetState === 'completed' ? '#10b981' : 
                     targetState === 'incidents' ? '#ef4444' : '#6b7280') : 
                    '#3b82f6'
            });
            
            console.log(`Orden ${order.order_id} movida de ${fromColumn} a ${toColumn}`, {
                estado: targetState,
                lineaProduccion: order.productionLineId
            });
            
            // Eliminar la tarjeta de la columna de origen en la estructura de datos
            if (sourceColumnId && sourceColumnId !== targetColumnEl?.id) {
                const sourceCol = columns[sourceColumnId];
                if (sourceCol && sourceCol.items) {
                    const itemIndex = sourceCol.items.findIndex(item => item.id == orderObj.id);
                    if (itemIndex > -1) {
                        sourceCol.items.splice(itemIndex, 1);
                        console.log(`Orden ${orderObj.id} eliminada de la columna de origen: ${sourceColumnId}`);
                    }
                } else if (columns['final_states'] && columns['final_states'].subStates) {
                    // Manejar si la tarjeta viene de un sub-estado
                    Object.values(columns['final_states'].subStates).forEach(subState => {
                        const itemIndex = subState.items.findIndex(item => item.id == orderObj.id);
                        if (itemIndex > -1) {
                            subState.items.splice(itemIndex, 1);
                            console.log(`Orden ${orderObj.id} eliminada del sub-estado de origen: ${subState.id}`);
                        }
                    });
                }
            }

            // Restablecer las zonas de drop y la tarjeta arrastrada
            resetDropZones();
            draggedCard = null;
            sourceColumnId = null; // Limpiar la columna de origen
        }

        function resetDropZones() {
            // Limpiar estilos de todas las columnas
            document.querySelectorAll('.kanban-column, .final-state-section, .column-cards').forEach(el => {
                el.style.boxShadow = '';
                el.style.transform = '';
                el.style.background = '';
                el.style.border = '';
                el.style.borderRadius = '';
                el.style.transition = '';
            });
            
            // Remover clases de arrastre
            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        }
        
        function showToast(message, options = {}) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            
            // Configuración por defecto
            const defaultOptions = {
                icon: 'check-circle',
                iconColor: '#3b82f6',
                duration: 3000
            };
            
            // Combinar opciones con valores por defecto
            const config = { ...defaultOptions, ...options };
            
            // Crear el contenido del toast
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${config.icon} me-2" style="color: ${config.iconColor}"></i>
                    <div>${message}</div>
                </div>
            `;
            
            // Agregar al documento
            document.body.appendChild(toast);
            
            // Forzar el reflow para que la animación funcione
            void toast.offsetWidth;
            
            // Mostrar con animación
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Configurar el tiempo de visualización
            setTimeout(() => {
                toast.classList.remove('show');
                // Eliminar después de la animación
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            }, config.duration);
            
            // Cerrar al hacer clic
            toast.addEventListener('click', () => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.remove();
                    }
                }, 300);
            });
        }

        function showCardMenu(orderId) {
            const order = masterOrderList.find(o => o.id == orderId);
            Swal.fire({
                title: `{{ __('Order') }} #${order.order_id}`,
                showCloseButton: true, showConfirmButton: false,
                html: `
                    <div class="d-flex flex-column gap-2 my-4">
                        <button id="viewJsonBtn" class="btn btn-primary w-100">{{ __('View Data') }}</button>
                        <button id="exportDbBtn" class="btn btn-secondary w-100">{{ __('Export (Demo)') }}</button>
                        <button id="deleteBtn" class="btn btn-danger w-100">{{ __('Delete (Demo)') }}</button>
                    </div>`,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    popup.querySelector('#viewJsonBtn').addEventListener('click', () => Swal.fire({ title: "{{ __('Order Data') }}", html: `<pre class="text-start text-sm p-4 bg-light rounded">${JSON.stringify(order, null, 2)}</pre>` }));
                    popup.querySelector('#exportDbBtn').addEventListener('click', () => Swal.fire('{{ __("Action unavailable") }}', '{{ __("This is a demo version without database connection.") }}', 'info'));
                    popup.querySelector('#deleteBtn').addEventListener('click', () => Swal.fire('{{ __("Action unavailable") }}', '{{ __("This is a demo version without database connection.") }}', 'info'));
                }
            });
        }
        
        function showNotification(message, type = 'success') {
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            Toast.fire({ icon: type, title: message });
        }

        function toggleFullscreen() {
            const element = document.getElementById('kanbanContainer');
            
            // Función para manejar el cambio de pantalla completa
            function onFullscreenChange() {
                document.body.classList.toggle('fullscreen-mode', !!document.fullscreenElement);
            }
            
            // Agregar el listener para cambios en el estado de pantalla completa
            document.addEventListener('fullscreenchange', onFullscreenChange);
            
            // Alternar pantalla completa
            if (!document.fullscreenElement) {
                if (element.requestFullscreen) {
                    element.requestFullscreen().catch(err => {
                        console.error('Error al intentar pantalla completa:', err);
                        showToast('No se pudo activar el modo pantalla completa', { icon: 'exclamation-triangle', iconColor: '#ef4444' });
                    });
                } else if (element.webkitRequestFullscreen) { // Safari
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) { // IE11
                    element.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { // Safari
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) { // IE11
                    document.msExitFullscreen();
                }
                document.body.classList.remove('fullscreen-mode');
            }
        }
        
        // --- GUARDAR CAMBIOS DEL KANBAN ---
        
        function saveKanbanChanges() {
            // Mostrar indicador de carga
            const saveBtn = document.getElementById('saveChangesBtn');
            const originalContent = saveBtn.innerHTML;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __("Guardando...") }}';
            saveBtn.disabled = true;
            
            // Recopilar todas las órdenes que han sido movidas a columnas de producción
            const updatedOrders = [];
            
            console.log('=== INICIO GUARDADO DE KANBAN ===');
            console.log('Columnas disponibles:', Object.keys(columns));
            
            // Primero, asegurarse de que todas las órdenes en masterOrderList tengan production_line_id actualizado
            masterOrderList.forEach(order => {
                // Buscar en qué columna está esta orden
                let foundInColumn = null;
                Object.keys(columns).forEach(columnId => {
                    const column = columns[columnId];
                    const orderInColumn = column.items.find(item => item.id === order.id);
                    if (orderInColumn) {
                        foundInColumn = column;
                    }
                });
                
                // Actualizar production_line_id según la columna donde está
                if (foundInColumn) {
                    if (foundInColumn.type === 'production' && foundInColumn.productionLineId) {
                        // Si está en una columna de producción, actualizar el ID
                        order.productionLineId = foundInColumn.productionLineId;
                        order.production_line_id = foundInColumn.productionLineId;
                        console.log(`Sincronizado orden ${order.id} con production_line_id=${order.production_line_id} (columna ${foundInColumn.name})`);
                    } else if (foundInColumn.id === 'pending_assignment' || foundInColumn.id === 'final_states') {
                        // Si está en pendientes o estados finales, establecer como null
                        order.productionLineId = null;
                        order.production_line_id = null;
                        console.log(`Sincronizado orden ${order.id} con production_line_id=null (columna ${foundInColumn.id})`);
                    }
                }
            });
            
            const processedOrderIds = new Set();

            // Status mapping from string to integer
            const statusMap = {
                'pending': 0,
                'in_progress': 1,
                'completed': 2,
                'cancelled': 4,
                'incidents': 5
            };

            Object.keys(columns).forEach(columnId => {
                const column = columns[columnId];
                console.log(`Procesando columna para guardado: ${column.name || columnId}`);

                const processItems = (items, productionLineId, statusStr) => {
                    (items || []).forEach((order, index) => {
                        if (processedOrderIds.has(order.id)) {
                            console.warn(`Orden duplicada encontrada y omitida: ID ${order.id}`);
                            return; // Skip duplicate
                        }
                        
                        const finalStatus = statusMap[statusStr] !== undefined ? statusMap[statusStr] : 0;

                        updatedOrders.push({
                            id: order.id,
                            production_line_id: productionLineId,
                            orden: index,
                            status: finalStatus
                        });
                        processedOrderIds.add(order.id);
                    });
                };

                if (column.id === 'final_states') {
                    Object.values(column.subStates).forEach(subState => {
                        processItems(subState.items, null, subState.id);
                    });
                } else if (column.type === 'production') {
                    processItems(column.items, column.productionLineId, 'in_progress');
                } else if (column.id === 'pending_assignment') {
                    processItems(column.items, null, 'pending');
                }
            });
            
            // Verificar que los datos a enviar sean correctos
            console.log('Órdenes a actualizar:', updatedOrders);
            
            // Verificar si hay alguna orden con production_line_id incorrecto
            updatedOrders.forEach(order => {
                // Si la orden está en una columna de producción pero tiene production_line_id null
                const orderInMaster = masterOrderList.find(o => o.id == order.id);
                if (orderInMaster && orderInMaster.productionLineId && order.production_line_id === null) {
                    console.warn(`Corrigiendo orden ${order.id} con production_line_id null pero productionLineId=${orderInMaster.productionLineId}`);
                    order.production_line_id = orderInMaster.productionLineId;
                }
            });
            
            // Enviar los datos al servidor mediante AJAX
            fetch('{{ route('production-orders.update-batch') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ orders: updatedOrders })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al guardar los cambios');
                }
                return response.json();
            })
            .then(data => {
                // Mostrar mensaje de éxito
                showToast(`<i class="fas fa-check-circle me-2"></i> ${data.message || 'Cambios guardados correctamente'}`, {
                    icon: 'check-circle',
                    iconColor: '#10b981'
                });
                
                // Restaurar el botón
                saveBtn.innerHTML = originalContent;
                saveBtn.disabled = false;
                
                // Recargar la página después de un breve retraso para que el usuario vea el mensaje de éxito
                setTimeout(() => {
                    // Mostrar indicador de recarga
                    const refreshBtn = document.getElementById('refreshBtn');
                    if (refreshBtn) {
                        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-primary"></i>';
                    }
                    
                    // Recargar la página completa
                    window.location.reload();
                }, 1000); // Retraso de 1 segundo para que el usuario vea el mensaje de éxito
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Mostrar mensaje de error
                showToast(`<i class="fas fa-exclamation-triangle me-2"></i> Error al guardar los cambios: ${error.message}`, {
                    icon: 'exclamation-triangle',
                    iconColor: '#ef4444'
                });
                
                // Restaurar el botón
                saveBtn.innerHTML = originalContent;
                saveBtn.disabled = false;
            });
        }
        
        // --- INICIALIZACIÓN Y EVENTOS ---
        
        generateDummyData(); // Cargar datos ficticios al inicio
        document.getElementById('saveChangesBtn').addEventListener('click', saveKanbanChanges);
        document.getElementById('refreshBtn').addEventListener('click', generateDummyData);
        document.getElementById('fullscreenBtn').addEventListener('click', toggleFullscreen);
        if (searchInput) {
            searchInput.addEventListener('input', () => setTimeout(applyAndRenderFilters, 300));
        }
    });
    </script>
@endpush
