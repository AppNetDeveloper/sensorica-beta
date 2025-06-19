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
                <select id="statusFilter" class="form-select" style="min-width: 150px;">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="0">{{ __('To Do') }}</option>
                    <option value="1">{{ __('In Progress') }}</option>
                    <option value="2">{{ __('Completed') }}</option>
                    <option value="3">{{ __('Incident') }}</option>
                    <option value="4">{{ __('Paused') }}</option>
                    <option value="5">{{ __('Cancelled') }}</option>
                </select>
                <div class="position-relative">
                    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="{{ __('Search...') }}"
                           class="form-control ps-5" style="min-width: 250px;">
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
        
        .kanban-card { background-color: var(--card-bg); color: var(--card-text); border-radius: 10px; border: 1px solid var(--card-border); border-left: 5px solid; box-shadow: var(--card-shadow); margin-bottom: 1rem; cursor: grab; overflow: hidden; }
        .kanban-card.dragging { opacity: 0.6; transform: rotate(3deg); }
        .kanban-card:hover { transform: translateY(-4px); box-shadow: var(--card-shadow-hover); }

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
        const statusFilter = document.getElementById('statusFilter');

        function generateDummyData() {
            masterOrderList = [
                { id: 1, order_id: 'OP-001', status: 0, box: 10, units: 100, created_at: '2025-06-20T10:00:00Z', json: { refer: { descrip: 'Producto A - Modelo Premium', customerId: 'Cliente Alpha' } } },
                { id: 2, order_id: 'OP-002', status: 1, box: 5, units: 50, created_at: '2025-06-20T11:00:00Z', json: { refer: { descrip: 'Producto B - Edición Limitada', customerId: 'Cliente Beta' } } },
                { id: 3, order_id: 'OP-003', status: 2, box: 20, units: 200, created_at: '2025-06-19T09:00:00Z', json: { refer: { descrip: 'Componente C - Estándar', customerId: 'Cliente Gamma' } } },
                { id: 4, order_id: 'OP-004', status: 3, box: 1, units: 10, created_at: '2025-06-18T15:00:00Z', json: { refer: { descrip: 'Producto D - Prototipo', customerId: 'Cliente Delta' } } },
                { id: 5, order_id: 'OP-005', status: 4, box: 8, units: 80, created_at: '2025-06-17T12:00:00Z', json: { refer: { descrip: 'Producto E - Básico', customerId: 'Cliente Epsilon' } } },
                { id: 6, order_id: 'OP-006', status: 0, box: 12, units: 120, created_at: '2025-06-21T08:00:00Z', json: { refer: { descrip: 'Producto F - Industrial', customerId: 'Cliente Zeta' } } },
                { id: 7, order_id: 'OP-007', status: 1, box: 15, units: 150, created_at: '2025-06-21T09:30:00Z', json: { refer: { descrip: 'Accesorio G - Kit Completo', customerId: 'Cliente Eta' } } },
            ];
            applyAndRenderFilters();
        }
        
        function applyAndRenderFilters() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const statusValue = statusFilter.value;

            const filteredOrders = masterOrderList.filter(order => {
                const searchMatch = !searchTerm || 
                                    (order.order_id && String(order.order_id).toLowerCase().includes(searchTerm)) || 
                                    (order.json?.refer?.descrip && order.json.refer.descrip.toLowerCase().includes(searchTerm)) ||
                                    (order.json?.refer?.customerId && order.json.refer.customerId.toLowerCase().includes(searchTerm));

                const statusMatch = !statusValue || String(order.status) === statusValue;
                return searchMatch && statusMatch;
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

        function handleDragStart(event) { draggedCard = event.target; setTimeout(() => event.target.classList.add('dragging'), 0); }
        function handleDragEnd(event) { event.target.classList.remove('dragging'); draggedCard = null; }
        function dragOver(event) {
            event.preventDefault();
            const targetColumn = event.target.closest('.kanban-column, .final-state-section');
            if (targetColumn) {
                targetColumn.classList.add('drag-over');
                
                // Resaltar el área de soltado objetivo
                const dropZone = targetColumn.classList.contains('final-state-section') ? 
                    targetColumn.querySelector('.column-cards') : 
                    targetColumn.querySelector('.column-cards');
                
                if (dropZone) {
                    dropZone.style.backgroundColor = 'var(--card-hover-bg)';
                }
            }
        }

        function drop(event) {
            event.preventDefault();
            
            // Encontrar el contenedor de destino (columna o sección de estado final)
            let targetContainer = event.target.closest('.kanban-column, .final-state-section');
            if (!targetContainer) return;
            if (!draggedCard) return;

            // Remover clases de arrastre
            document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
            document.querySelectorAll('.column-cards').forEach(el => {
                el.style.backgroundColor = '';
            });

            // Obtener información del contenedor de destino
            let targetColumnEl, targetState;
            
            if (targetContainer.classList.contains('kanban-column')) {
                // Es una columna normal
                targetColumnEl = targetContainer;
                targetState = null;
            } else {
                // Es una sección de estado final
                targetState = targetContainer.dataset.state;
                targetColumnEl = targetContainer.closest('.kanban-column');
            }
            
            if (!targetColumnEl) return;

            const targetColumnId = targetColumnEl.id;
            const targetColumn = columns[targetColumnId];
            if (!targetColumn) return;

            // Remover de la columna anterior
            const previousContainer = draggedCard.closest('.kanban-column, .final-state-section');
            const previousColumnId = previousContainer?.closest('.kanban-column')?.id;
            const previousColumn = previousColumnId ? columns[previousColumnId] : null;
            
            if (previousColumn) {
                previousColumn.items = previousColumn.items.filter(item => item.id !== draggedCard.dataset.id);
            }

            // Agregar a la nueva columna/sección
            const order = masterOrderList.find(o => o.id.toString() === draggedCard.dataset.id);
            if (order) {
                // Inicializar el array de items si no existe
                targetColumn.items = targetColumn.items || [];
                
                // Actualizar el estado del pedido si es una sección de estado final
                if (targetState) {
                    order.status = targetState;
                    order.statusColor = {
                        'completed': '#10b981',
                        'incidents': '#ef4444',
                        'cancelled': '#6b7280'
                    }[targetState] || '#6b7280';
                }
                
                // Agregar a la columna si no existe ya
                if (!targetColumn.items.some(item => item.id === order.id)) {
                    targetColumn.items.push(order);
                }
                
                // Actualizar el ID de la línea de producción (si corresponde)
                const productionLineId = targetColumn.productionLineId || '';
                draggedCard.dataset.productionLineId = productionLineId;
                
                // Actualizar el pedido en la lista maestra
                order.productionLineId = productionLineId;
                
                // Mover la tarjeta al contenedor correcto
                let cardsContainer;
                if (targetState) {
                    // Buscar el contenedor de la sección de estado específica
                    cardsContainer = targetColumnEl.querySelector(`.final-state-section[data-state="${targetState}"] .column-cards`);
                } else {
                    // Usar el contenedor de la columna principal
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
                    
                    cardsContainer.appendChild(draggedCard);
                }
                
                // Mostrar notificación
                const fromColumn = previousColumn ? previousColumn.name : 'Desconocido';
                const toColumn = targetState ? 
                    targetColumn.subStates?.find(s => s.id === targetState)?.name || targetColumn.name : 
                    targetColumn.name;
                
                showToast(`Orden movida de <strong>${fromColumn}</strong> a <strong>${toColumn}</strong>`);
                
                console.log(`Orden ${order.order_id} movida de ${fromColumn} a ${toColumn}`, {
                    estado: targetState,
                    lineaProduccion: productionLineId
                });
            }
            
            draggedCard = null;
        }
        
        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            `;
            document.body.appendChild(toast);
            
            // Inicializar toast de Bootstrap
            const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
            bsToast.show();
            
            // Eliminar el toast después de que se oculte
            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
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
        statusFilter.addEventListener('change', applyAndRenderFilters);
        searchInput.addEventListener('input', () => setTimeout(applyAndRenderFilters, 300));
    });
    </script>
@endpush
