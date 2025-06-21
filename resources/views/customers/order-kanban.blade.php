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
            <a href="{{ route('customers.order-organizer', $customer) }}" class="btn btn-secondary me-2">
                <i class="ti ti-arrow-left me-1"></i> {{ __('Back to Processes') }}
            </a>
            <div class="position-relative" style="max-width: 400px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-gray-400"></i>
                <input type="text" id="searchInput" placeholder="{{ __('Search by order ID or customer...') }}"
                       class="form-control ps-5" style="width: 100%;">
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button id="saveChangesBtn" class="btn btn-primary" title="{{ __('Save Changes') }}">
                <i class="fas fa-save me-1"></i> {{ __('Guardar') }}
            </button>
            <button id="refreshBtn" class="btn btn-info" title="{{ __('Refresh Data') }}">
                <i class="fas fa-sync-alt"></i>
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
    {{-- Los estilos CSS son correctos y se mantienen sin cambios --}}
    <style>
        :root {
            --kanban-bg: #f9fafb; --column-bg: #f3f4f6; --column-border: #e5e7eb;
            --header-bg: #ffffff; --header-text: #374151; --card-bg: #ffffff;
            --card-text: #1f2937; --card-hover-bg: #f9fafb; --card-border: #e5e7eb;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.06); --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.1);
            --scrollbar-thumb: #d1d5db; --primary-color: #3b82f6; --danger-color: #ef4444; --text-muted: #6b7280;
        }

        body.dark {
            --kanban-bg: #0f172a; --column-bg: #1e293b; --column-border: #334155;
            --header-bg: #334155; --header-text: #f1f5f9; --card-bg: #2d3748;
            --card-text: #e2e8f0; --card-hover-bg: #334155; --card-border: #4a5568;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.2); --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.3);
            --scrollbar-thumb: #475569; --primary-color: #60a5fa; --danger-color: #f87171; --text-muted: #94a3b8;
        }

        #kanbanContainer {
            display: flex; flex-direction: column;
            height: calc(100vh - 220px); /* Ajustado para más espacio */
            overflow: hidden;
        }

        .kanban-board {
            display: flex; gap: 1rem; padding: 1rem;
            overflow-x: auto; overflow-y: hidden; background-color: var(--kanban-bg);
            flex: 1; min-height: 0; align-items: stretch; /* Cambio a stretch */
        }
        
        .kanban-board::-webkit-scrollbar { height: 10px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 10px; border: 2px solid var(--kanban-bg); }

        .kanban-column {
            flex: 0 0 340px; background-color: var(--column-bg); border-radius: 12px;
            min-width: 340px; display: flex; flex-direction: column;
            border: 1px solid var(--column-border); box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            max-height: 100%; overflow: hidden;
        }
        
        .column-header {
            padding: 0.75rem 1rem; position: sticky; top: 0;
            background-color: var(--header-bg); z-index: 10;
            border-bottom: 1px solid var(--column-border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .column-title { font-weight: 600; color: var(--header-text); margin: 0; font-size: 1rem; }

        .column-cards {
            padding: 0.75rem; overflow-y: auto; flex-grow: 1;
            display: flex; flex-direction: column; gap: 8px;
            min-height: 100px;
        }

        .column-cards::-webkit-scrollbar { width: 6px; }
        .column-cards::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 3px; }

        /* Estructura de Estados Finales */
        .final-states-container { display: flex; flex-direction: column; flex-grow: 1; overflow-y: auto; padding: 0.5rem; gap: 1rem; }
        .final-state-section {
            background-color: rgba(0, 0, 0, 0.02); border-radius: 8px;
            border: 1px dashed var(--column-border);
            display: flex; flex-direction: column; flex: 1;
            min-height: 150px; overflow: hidden; transition: all 0.2s ease;
        }
        .final-state-section.drag-over {
             border-color: var(--primary-color);
             box-shadow: 0 0 0 2px var(--primary-color);
             transform: translateY(-2px);
        }
        .final-state-header { padding: 10px 12px; border-bottom: 1px solid var(--card-border); background-color: var(--header-bg); }
        .final-state-title { font-weight: 600; font-size: 0.9rem; color: var(--header-text); }

        .kanban-card {
            background-color: var(--card-bg); color: var(--card-text); border-radius: 10px;
            border: 1px solid var(--card-border); border-left: 5px solid;
            box-shadow: var(--card-shadow); flex-shrink: 0; /* Evita que se encoja */
            overflow: hidden; width: 100%;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); cursor: grab;
        }
        .kanban-card.collapsed .kanban-card-body, .kanban-card.collapsed .kanban-card-footer { display: none; }
        .kanban-card.dragging {
            opacity: 0.5; transform: rotate(2deg) scale(1.02);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15); z-index: 1000;
        }
        .kanban-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }

        .kanban-card-header { padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: flex-start; cursor: pointer; }
        .kanban-card-body { padding: 0 1.25rem 1.25rem 1.25rem; }
        .kanban-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid var(--card-border); background-color: var(--column-bg); font-size: 0.875rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; }
        .card-menu { font-size: 1rem; color: var(--text-muted); cursor: pointer; }

        /* Pantalla Completa */
        :fullscreen #kanbanContainer { height: 100vh; padding: 1rem; }
        :fullscreen .kanban-board { align-items: stretch; }
        :fullscreen .kanban-column { height: 100%; }

    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- 1. CONFIGURACIÓN INICIAL Y VARIABLES GLOBALES ---
        
        const kanbanBoard = document.querySelector('.kanban-board');
        const searchInput = document.getElementById('searchInput');

        // La fuente de la verdad para todas las órdenes.
        let masterOrderList = @json($processOrders ?? []);
        
        // Estructura que define las columnas. Se llenará dinámicamente.
        const columns = {
            'pending_assignment': {
                id: 'pending_assignment', name: 'Pendientes Asignación', items: [],
                color: '#9ca3af', productionLineId: null, type: 'status'
            },
            ...(@json($productionLines ?? [])).reduce((acc, line) => ({
                ...acc,
                [`line_${line.id}`]: {
                    id: `line_${line.id}`, name: line.name, items: [],
                    color: '#3b82f6', productionLineId: line.id, type: 'production'
                }
            }), {}),
            'final_states': {
                id: 'final_states', name: 'Estados Finales', items: [],
                color: '#6b7280', productionLineId: null, type: 'final_states',
                subStates: [
                    { id: 'completed', name: 'Finalizados', color: '#10b981', items: [] },
                    { id: 'incidents', name: 'Incidencias', color: '#ef4444', items: [] },
                    { id: 'cancelled', name: 'Cancelados', color: '#6b7280', items: [] }
                ]
            }
        };

        let draggedCard = null;

        // --- 2. LÓGICA PRINCIPAL DE RENDERIZADO ---

        /**
         * Orquesta todo el proceso de renderizado.
         * 1. Limpia las columnas.
         * 2. Distribuye las órdenes de `masterOrderList` en las columnas.
         * 3. Llama a `renderBoard` para dibujar el DOM.
         */
        function distributeAndRender() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            
            // Filtra la lista maestra si hay un término de búsqueda
            const ordersToDisplay = searchTerm ? masterOrderList.filter(order => {
                const orderId = String(order.order_id || '').toLowerCase();
                const desc = String(order.json?.refer?.descrip || '').toLowerCase();
                const customer = String(order.json?.refer?.customerId || '').toLowerCase();
                return orderId.includes(searchTerm) || desc.includes(searchTerm) || customer.includes(searchTerm);
            }) : masterOrderList;

            // Limpiar SIEMPRE los `items` de las columnas para evitar duplicados.
            Object.values(columns).forEach(column => {
                column.items = [];
                if (column.subStates) {
                    column.subStates.forEach(sub => { sub.items = []; });
                }
            });

            // Distribuir las órdenes (filtradas o no) en la estructura de `columns`.
            ordersToDisplay.forEach(order => {
                let targetColumnKey = null;
                // Asignación a producción_line_id para compatibilidad
                if(order.productionLineId) order.production_line_id = order.productionLineId;

                if (['completed', 'incidents', 'cancelled'].includes(order.status)) {
                    targetColumnKey = 'final_states';
                } else if (order.production_line_id) {
                    const lineKey = `line_${order.production_line_id}`;
                    if (columns[lineKey]) {
                        targetColumnKey = lineKey;
                    } else {
                         targetColumnKey = 'pending_assignment'; // Fallback si la línea no existe
                    }
                } else {
                    targetColumnKey = 'pending_assignment';
                }

                if (targetColumnKey) {
                    if (targetColumnKey === 'final_states') {
                        const subState = columns.final_states.subStates.find(s => s.id === order.status);
                        if (subState) subState.items.push(order);
                    } else {
                        columns[targetColumnKey].items.push(order);
                    }
                }
            });
            
            renderBoard();
        }

        /**
         * Dibuja el DOM basado en el estado actual del objeto `columns`.
         * Esta función es "tonta": solo lee datos, no los modifica.
         */
        function renderBoard() {
            kanbanBoard.innerHTML = ''; // Limpiar el DOM
            const fragment = document.createDocumentFragment();

            Object.values(columns).forEach(column => {
                const columnElement = createColumnElement(column); // Crea la estructura de la columna
                fragment.appendChild(columnElement);

                // Poblar la columna con sus tarjetas
                const appendCards = (items, container) => {
                    if (items && container) {
                        items.forEach(order => {
                            const cardElement = createCardElement(order);
                            container.appendChild(cardElement);
                        });
                    }
                };

                if (column.type === 'final_states') {
                    column.subStates.forEach(subState => {
                        const subContainer = columnElement.querySelector(`.final-state-section[data-state="${subState.id}"] .column-cards`);
                        appendCards(subState.items, subContainer);
                    });
                } else {
                    const container = columnElement.querySelector('.column-cards');
                    appendCards(column.items, container);
                }
            });

            kanbanBoard.appendChild(fragment);
        }

        // --- 3. FUNCIONES DE DRAG & DROP ---

        function handleDragStart(event) {
            draggedCard = event.target.closest('.kanban-card');
            if (!draggedCard) return;

            // Añadir feedback visual
            setTimeout(() => {
                if (draggedCard) draggedCard.classList.add('dragging');
            }, 0);

            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', draggedCard.dataset.id);
        }

        function handleDragEnd() {
            if (draggedCard) {
                draggedCard.classList.remove('dragging');
            }
            draggedCard = null;
            resetDropZones();
        }

        function dragOver(event) {
            event.preventDefault();
            const target = event.target.closest('.kanban-column, .final-state-section');
            if (target) {
                resetDropZones(); // Limpia el resaltado anterior
                target.classList.add('drag-over');
            }
        }
        
        function resetDropZones() {
             document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
        }

        function drop(event) {
            event.preventDefault();
            if (!draggedCard) return;

            // 1. Identificar la orden y el destino
            const cardId = parseInt(draggedCard.dataset.id);
            const orderObj = masterOrderList.find(o => o.id === cardId);
            const targetContainer = event.target.closest('.kanban-column, .final-state-section');
            
            if (!orderObj || !targetContainer) {
                resetDropZones();
                return;
            }
            
            // 2. Determinar los nuevos datos
            let newStatus = orderObj.status;
            let newProductionLineId = orderObj.production_line_id;

            if (targetContainer.classList.contains('final-state-section')) {
                const stateId = targetContainer.dataset.state;
                if (['completed', 'incidents', 'cancelled'].includes(stateId)) {
                    newStatus = stateId;
                    newProductionLineId = null;
                }
            } else if (targetContainer.classList.contains('kanban-column')) {
                const columnData = columns[targetContainer.id];
                if (columnData.type === 'production') {
                    newStatus = 'in_progress';
                    newProductionLineId = columnData.productionLineId;
                } else if (columnData.id === 'pending_assignment') {
                    newStatus = 'pending';
                    newProductionLineId = null;
                }
            }
            
            // 3. Actualizar la fuente de la verdad (`masterOrderList`)
            orderObj.status = newStatus;
            orderObj.production_line_id = newProductionLineId;
            orderObj.productionLineId = newProductionLineId; // Sincronizar
            
            console.log(`Orden ${orderObj.id} movida a:`, { status: newStatus, line_id: newProductionLineId });

            // 4. Disparar un re-renderizado completo del tablero
            distributeAndRender();
        }

        // --- 4. FUNCIONES PARA CREAR ELEMENTOS DEL DOM ---

        function createColumnElement(column) {
            const columnElement = document.createElement('div');
            columnElement.className = 'kanban-column';
            columnElement.id = column.id;
            columnElement.addEventListener('dragover', dragOver);
            columnElement.addEventListener('dragleave', resetDropZones);
            columnElement.addEventListener('drop', drop);

            let innerHTML;
            if (column.type === 'final_states') {
                innerHTML = `
                    <div class="column-header">
                        <h3 class="column-title">${column.name}</h3>
                    </div>
                    <div class="final-states-container">
                        ${column.subStates.map(subState => `
                            <div class="final-state-section" data-state="${subState.id}" style="border-left-color: ${subState.color};">
                                <div class="final-state-header">
                                    <span class="final-state-title" style="color: ${subState.color};">${subState.name}</span>
                                </div>
                                <div class="column-cards"></div>
                            </div>
                        `).join('')}
                    </div>`;
            } else {
                innerHTML = `
                    <div class="column-header" style="border-left: 4px solid ${column.color};">
                        <h3 class="column-title">${column.name}</h3>
                    </div>
                    <div class="column-cards"></div>`;
            }
            columnElement.innerHTML = innerHTML;
            return columnElement;
        }

        function createCardElement(order) {
            const card = document.createElement('div');
            card.className = 'kanban-card collapsed';
            card.dataset.id = order.id;
            card.draggable = true;

            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);

            const statusInfo = columns.final_states.subStates.find(s => s.id === order.status) || 
                               columns[order.production_line_id ? `line_${order.production_line_id}` : 'pending_assignment'];
            
            card.style.borderLeftColor = statusInfo?.color || '#6b7280';
            
            const createdAtFormatted = new Date(order.created_at).toLocaleDateString();
            const deliveryDateFormatted = order.delivery_date ? new Date(order.delivery_date).toLocaleDateString() : '';
            const processDescription = '{{ $process->description }}';

            card.innerHTML = `
                <div class="kanban-card-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div>
                        <div class="fw-bold text-sm">#${order.order_id}</div>
                        ${processDescription ? `<div class="text-xs text-muted mt-1">${processDescription}</div>` : ''}
                    </div>
                    <span class="card-menu" role="button" onclick="event.stopPropagation(); showCardMenu(${order.id})"><i class="fas fa-ellipsis-h"></i></span>
                </div>
                <div class="kanban-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-xs text-muted">${order.json?.refer?.customerId || 'N/A'}</span>
                        <span class="badge" style="background-color: ${card.style.borderLeftColor}; color: white;">${(order.status || 'PENDING').replace('_', ' ').toUpperCase()}</span>
                    </div>
                    <div class="text-sm mb-2">${order.json?.refer?.descrip || 'Sin descripción'}</div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                         <div class="d-flex align-items-center">
                             <i class="fas fa-box text-muted me-1"></i><span class="text-xs">${order.box || 0} cajas</span>
                             <i class="fas fa-cubes text-muted ms-2 me-1"></i><span class="text-xs">${order.units || 0} uds</span>
                         </div>
                         <div class="text-xs text-muted"><i class="far fa-calendar-alt me-1"></i>${createdAtFormatted}</div>
                    </div>
                    ${deliveryDateFormatted ? `
                    <div class="d-flex justify-content-end align-items-center">
                        <div class="text-xs" style="color: #e67e22 !important;"><i class="fas fa-truck me-1"></i>${deliveryDateFormatted}</div>
                    </div>` : ''}
                </div>
                <div class="kanban-card-footer">
                    <span class="text-xs fw-medium">{{__("Assigned")}}</span>
                    <div class="assigned-avatars d-flex align-items-center">
                        <!-- Avatares de ejemplo -->
                        <img class="avatar-img" style="width:28px; height:28px; border-radius:50%;" src="https://i.pravatar.cc/40?img=1" alt="user">
                    </div>
                </div>`;
            return card;
        }

        // --- 5. GUARDADO DE DATOS Y OTROS EVENTOS ---

        function saveKanbanChanges() {
            const saveBtn = document.getElementById('saveChangesBtn');
            const originalContent = saveBtn.innerHTML;
            saveBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> {{ __("Guardando...") }}`;
            saveBtn.disabled = true;

            const updatedOrders = [];
            const statusMap = { 'pending': 0, 'in_progress': 1, 'completed': 2, 'cancelled': 4, 'incidents': 5 };

            // La fuente de la verdad ahora es `masterOrderList`, pero `columns` refleja el orden.
            Object.values(columns).forEach(column => {
                const processItems = (items, productionLineId, statusStr) => {
                    (items || []).forEach((order, index) => {
                        updatedOrders.push({
                            id: order.id,
                            production_line_id: productionLineId,
                            orden: index,
                            status: statusMap[statusStr]
                        });
                    });
                };
                
                if (column.type === 'production') {
                    processItems(column.items, column.productionLineId, 'in_progress');
                } else if (column.id === 'pending_assignment') {
                    processItems(column.items, null, 'pending');
                } else if (column.type === 'final_states') {
                    column.subStates.forEach(subState => {
                        processItems(subState.items, null, subState.id);
                    });
                }
            });

            console.log('Datos a enviar:', updatedOrders);

            fetch('{{ route('production-orders.update-batch') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ orders: updatedOrders })
            })
            .then(response => response.json().then(data => ({ ok: response.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) throw data;
                showToast(data.message || 'Cambios guardados correctamente', 'success');
            })
            .catch(error => {
                console.error('Error al guardar:', error);
                const errorMessage = error.message || 'Ocurrió un error desconocido.';
                showToast(`Error al guardar: ${errorMessage}`, 'error');
            })
            .finally(() => {
                saveBtn.innerHTML = originalContent;
                saveBtn.disabled = false;
            });
        }

        function showToast(message, type = 'success') {
            const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true });
            Toast.fire({ icon: type, title: message });
        }

        function showCardMenu(orderId) {
            const order = masterOrderList.find(o => o.id == orderId);
            Swal.fire({
                title: `{{ __('Order') }} #${order.order_id}`,
                showCloseButton: true, showConfirmButton: false,
                html: `<div class="d-flex flex-column gap-2 my-4">
                           <button id="viewJsonBtn" class="btn btn-primary w-100">{{ __('View Data') }}</button>
                       </div>`,
                didOpen: () => {
                    Swal.getPopup().querySelector('#viewJsonBtn').addEventListener('click', () => {
                        Swal.fire({ title: "{{ __('Order Data') }}", html: `<pre class="text-start text-sm p-4 bg-light rounded" style="max-height: 400px; overflow-y: auto;">${JSON.stringify(order, null, 2)}</pre>` });
                    });
                }
            });
        }

        function toggleFullscreen() {
            const element = document.getElementById('kanbanContainer');
            if (!document.fullscreenElement) {
                element.requestFullscreen().catch(err => console.error(err));
            } else {
                document.exitFullscreen();
            }
        }
        
        // --- 6. INICIALIZACIÓN ---
        
        document.getElementById('saveChangesBtn').addEventListener('click', saveKanbanChanges);
        document.getElementById('refreshBtn').addEventListener('click', () => {
            // Aquí podrías recargar los datos desde el servidor en un futuro
            showToast('Datos refrescados (demo)', 'info');
            distributeAndRender();
        });
        document.getElementById('fullscreenBtn').addEventListener('click', toggleFullscreen);
        searchInput.addEventListener('input', () => setTimeout(distributeAndRender, 300));
        
        // Renderizar el tablero por primera vez al cargar la página
        distributeAndRender();
        console.log('Kanban inicializado.');
        console.log('Órdenes cargadas:', masterOrderList);
        console.log('Estructura de columnas:', columns);
    });
    </script>
@endpush

