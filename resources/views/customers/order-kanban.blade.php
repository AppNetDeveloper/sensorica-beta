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
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">{{ __('Order Kanban') }}</h5>
    </div>
    <div class="card-body d-flex justify-content-between">
        <a href="{{ route('customers.order-organizer', $customer) }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left me-1"></i> {{ __('Back to Processes') }}
        </a>
    </div>
</div>


    <!-- Barra de Filtros y Controles -->
    <div class="mb-3 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="d-flex flex-wrap items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap items-center gap-3">
            <div class="position-relative flex-grow-1" style="max-width: 400px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-gray-400"></i>
                <input type="text" id="searchInput" placeholder="{{ __('Search by order ID or customer...') }}"
                       class="form-control ps-5" style="width: 100%;">
            </div>
            </div>
            <div class="d-flex items-center gap-3">
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

        .kanban-board { display: flex; gap: 1rem; padding: 1rem; overflow-x: auto; background-color: var(--kanban-bg); min-height: calc(100vh - 350px); }
        .kanban-board::-webkit-scrollbar { height: 10px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 10px; border: 2px solid var(--kanban-bg); }

        .kanban-column { flex: 0 0 340px; background-color: var(--column-bg); border-radius: 12px; min-width: 340px; display: flex; flex-direction: column; border: 1px solid var(--column-border); box-shadow: 0 1px 4px rgba(0,0,0,0.05); max-height: calc(100vh - 390px); }
        .column-header { padding: 0.75rem 1rem; position: sticky; top: 0; background-color: var(--column-bg); z-index: 10; border-bottom: 1px solid var(--column-border); display: flex; align-items: center; justify-content: space-between; }
        .column-title { font-weight: 600; color: var(--header-text); margin: 0; font-size: 1rem; }
        .column-cards { padding: 0.75rem; overflow-y: auto; flex-grow: 1; }
        
        .kanban-card { 
            background-color: var(--card-bg); 
            color: var(--card-text); 
            border-radius: 10px; 
            border: 1px solid var(--card-border); 
            border-left: 5px solid; 
            box-shadow: var(--card-shadow); 
            margin-bottom: 1rem; 
            cursor: grab; 
            overflow: hidden;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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

        .kanban-card-header { padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .kanban-card-body { padding: 0 1.25rem 1.25rem 1.25rem; }
        .kanban-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid var(--card-border); background-color: var(--column-bg); font-size: 0.875rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; }
        
        .card-menu { font-size: 1rem; color: var(--text-muted); cursor: pointer; }
        .card-menu:hover { color: var(--primary-color); }
        .assigned-avatars .avatar-img { width: 28px; height: 28px; border-radius: 50%; border: 2px solid var(--card-bg); margin-left: -10px; }
        .assigned-avatars .avatar-img:first-child { margin-left: 0; }
        .kanban-card.collapsed .kanban-card-body, .kanban-card.collapsed .kanban-card-footer { display: none; }
        
        /* Estilos para la columna de estados finales */
        .final-states-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            overflow-y: auto;
            padding: 8px;
            gap: 12px;
        }
        
        .final-state-section {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 150px;
            border: 1px solid var(--card-border);
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
        
        // Log production lines to console
        console.log('Líneas de producción para el proceso {{ $process->name }} (ID: {{ $process->id }})');
        console.log('Número de líneas:', productionLines.length);
        productionLines.forEach((line, index) => {
            console.log(`${index + 1}. ${line.name} (ID: ${line.id})`);
        });
        
        let masterOrderList = []; // Empezará vacía y se llenará con datos ficticios.
        let draggedCard = null;

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
            // Limpiar columnas existentes
            Object.keys(columns).forEach(key => {
                if (columns[key].items) {
                    columns[key].items = [];
                }
            });

            // Generar datos de ejemplo
            masterOrderList = [
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
                },
            ];

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
            
            // Mostrar notificación
            showToast('Datos de ejemplo cargados correctamente', {
                icon: 'check-circle',
                iconColor: '#10b981'
            });
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
            initializeBoardColumns();
            
            // Asignar todas las órdenes a la columna 'Pendientes Asignación' por defecto
            ordersToRender.forEach(order => {
                const pendingColumn = columns['pending_assignment'];
                if (!pendingColumn) return;

                const columnCardsContainer = document.querySelector(`#${pendingColumn.id} .column-cards`);
                if (columnCardsContainer) {
                    const cardElement = createCardElement(order);
                    columnCardsContainer.appendChild(cardElement);
                    
                    // Asignar la orden a la columna en el modelo de datos
                    if (!pendingColumn.items) pendingColumn.items = [];
                    pendingColumn.items.push(order);
                }
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
            return `
                <div class="kanban-card-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div class="fw-bold text-sm text-gray-700 dark:text-gray-200">#${order.order_id}</div>
                    <span class="card-menu" role="button" onclick="event.stopPropagation(); showCardMenu('${order.id}')"><i class="fas fa-ellipsis-h"></i></span>
                </div>
                <div class="kanban-card-body">
                    <p class="fw-semibold text-base mb-3" title="${order.json.refer.descrip}">${order.json.refer.descrip}</p>
                    <div class="d-flex justify-content-between align-items-center text-sm text-gray-600 dark:text-gray-300 mb-3">
                        <span class="d-flex align-items-center gap-2" title="{{__('Boxes')}}"><i class="fas fa-box-open text-gray-400"></i> ${order.box || 0}</span>
                        <span class="d-flex align-items-center gap-2" title="{{__('Units')}}"><i class="fas fa-dolly text-gray-400"></i> ${order.units || 0}</span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-3 pt-2 border-top">
                        <p><strong>{{__("Customer")}}:</strong> ${order.json.refer.customerId}</p>
                        <p><strong>{{__("Created")}}:</strong> ${createdAtFormatted}</p>
                    </div>
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
                draggedCard.style.visibility = 'visible';
                draggedCard.style.position = '';
                draggedCard.style.top = '';
                draggedCard.style.left = '';
                draggedCard.style.transform = draggedCard.originalTransform || '';
                draggedCard.style.width = '';
                draggedCard.style.height = '';
                draggedCard.style.zIndex = '';
                
                // Forzar un reflow para asegurar que los estilos se apliquen
                void draggedCard.offsetHeight;
            }
            
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
            event.preventDefault();
            if (!draggedCard) return;
            
            // Encontrar el contenedor de destino
            let targetContainer = event.target.closest('.kanban-column, .final-state-section, .kanban-card, .column-cards');
            if (!targetContainer) {
                resetDropZones();
                return;
            }
            
            // Determinar el destino final (columna y estado)
            let targetColumnEl, targetState, targetStateName = '', targetCardsContainer;
            
            if (targetContainer.classList.contains('kanban-card')) {
                // Si se suelta sobre otra tarjeta, obtener su columna
                targetColumnEl = targetContainer.closest('.kanban-column');
                targetCardsContainer = targetColumnEl?.querySelector('.column-cards');
                targetState = null;
            } else if (targetContainer.classList.contains('final-state-section')) {
                // Si es una sección de estado final
                targetState = targetContainer.dataset.state;
                targetColumnEl = targetContainer.closest('.kanban-column');
                targetCardsContainer = targetContainer.querySelector('.column-cards');
                targetStateName = targetContainer.querySelector('.final-state-title')?.textContent || '';
            } else if (targetContainer.classList.contains('column-cards')) {
                // Si se suelta directamente en el área de tarjetas
                targetColumnEl = targetContainer.closest('.kanban-column');
                targetCardsContainer = targetContainer;
                
                // Si es una sección de estado final, obtener el estado
                const finalStateSection = targetContainer.closest('.final-state-section');
                if (finalStateSection) {
                    targetState = finalStateSection.dataset.state;
                    targetStateName = finalStateSection.querySelector('.final-state-title')?.textContent || '';
                } else {
                    targetState = null;
                }
            } else {
                // Si es una columna normal, obtener su contenedor de tarjetas
                targetColumnEl = targetContainer;
                targetCardsContainer = targetColumnEl.querySelector('.column-cards');
                targetState = null;
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
            const order = masterOrderList.find(o => o.id === draggedCardId);
            if (!order) {
                console.error('Orden no encontrada:', draggedCardId);
                resetDropZones();
                return;
            }
            
            // Remover de la columna anterior
            if (previousColumn) {
                previousColumn.items = previousColumn.items.filter(item => item.id !== order.id);
            }
            
            // Actualizar el estado del pedido según el destino
            if (targetState) {
                // Si es un estado final (completado, incidencia, cancelado)
                order.status = targetState;
                order.statusColor = {
                    'completed': '#10b981',
                    'incidents': '#ef4444',
                    'cancelled': '#6b7280',
                    'pending': '#6b7280',
                    'in_progress': '#3b82f6'
                }[targetState] || '#3b82f6';
                
                // Si es un estado final, quitar de la línea de producción
                if (['completed', 'incidents', 'cancelled'].includes(targetState)) {
                    order.productionLineId = null;
                }
            } else if (targetColumnEl) {
                // Si es una columna de producción
                const targetColumnId = targetColumnEl.id;
                const targetColumn = columns[targetColumnId];
                
                if (targetColumn && targetColumn.type === 'production') {
                    order.productionLineId = targetColumn.productionLineId;
                    order.status = 'in_progress';
                    order.statusColor = '#3b82f6';
                } else if (targetColumnId === 'pending_assignment') {
                    // Si se mueve a pendientes
                    order.productionLineId = null;
                    order.status = 'pending';
                    order.statusColor = '#6b7280';
                }
            }
            
            // Aplicar efecto visual al cambiar de estado
            if (draggedCard) {
                draggedCard.style.transition = 'all 0.3s ease';
                draggedCard.style.transform = 'scale(1.02)';
                draggedCard.style.boxShadow = '0 10px 20px rgba(0,0,0,0.15)';
                
                // Actualizar el color del borde según el estado
                if (order.statusColor) {
                    draggedCard.style.borderLeftColor = order.statusColor;
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
            if (!targetColumn.items.some(item => item.id === order.id)) {
                targetColumn.items.push(order);
            }
            
            // Actualizar el ID de la línea de producción en la tarjeta
            if (draggedCard) {
                draggedCard.dataset.productionLineId = order.productionLineId || '';
                
                // Actualizar los estilos de la tarjeta
                if (order.statusColor) {
                    draggedCard.style.borderLeftColor = order.statusColor;
                }
                
                // Actualizar el estado en el atributo de datos
                draggedCard.dataset.status = order.status;
            }
            
            // Mover la tarjeta al contenedor correcto con animación
            let cardsContainer = null;
            
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
            
            if (cardsContainer) {
                // Actualizar el estilo de la tarjeta según el estado
                if (targetState) {
                    const color = {
                        'completed': '#10b981',
                        'incidents': '#ef4444',
                        'cancelled': '#6b7280'
                    }[targetState] || '#6b7280';
                    
                    draggedCard.style.borderLeftColor = color;
                }
                
                // Añadir clase temporal para la animación
                draggedCard.classList.add('card-dropped');
                
                // Insertar la tarjeta en el nuevo contenedor
                cardsContainer.appendChild(draggedCard);
                
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
                lineaProduccion: productionLineId
            });
            
            // Limpiar estilos y restablecer
            resetDropZones();
            draggedCard = null;
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
            if (!document.fullscreenElement) {
                document.getElementById('kanbanContainer').requestFullscreen().catch(err => alert(`Error: ${err.message}`));
            } else {
                document.exitFullscreen();
            }
        }
        
        // --- INICIALIZACIÓN Y EVENTOS ---
        
        generateDummyData(); // Cargar datos ficticios al inicio
        document.getElementById('refreshBtn').addEventListener('click', generateDummyData);
        document.getElementById('fullscreenBtn').addEventListener('click', toggleFullscreen);
        if (searchInput) {
            searchInput.addEventListener('input', () => setTimeout(applyAndRenderFilters, 300));
        }
    });
    </script>
@endpush
