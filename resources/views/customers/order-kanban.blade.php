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
            <div class="position-relative" style="max-width: 400px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-gray-400"></i>
                <input type="text" id="searchInput" placeholder="{{ __('Search by order ID or customer...') }}"
                       class="form-control ps-5" style="width: 100%;">
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button id="autoOrganizeBtn" class="btn btn-light" title="{{ __('Organizar con IA') }}">
                ✨
            </button>
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
        .column-header { padding: 0.75rem 1rem; position: sticky; top: 0; background-color: var(--header-bg); z-index: 10; border-bottom: 1px solid var(--column-border); display: flex; align-items: center; justify-content: space-between; }
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
        .kanban-card.collapsed .kanban-card-body,
        .kanban-card.collapsed .kanban-card-footer,
        .kanban-card.collapsed .kanban-card-body .process-list,
        .kanban-card.collapsed .kanban-card-body .progress-container,
        .kanban-card.collapsed .kanban-card-body .d-flex.justify-content-between.align-items-center:not(:first-child) { 
            display: none; 
        }
        .kanban-card.dragging { opacity: 0; height: 0; padding: 0; margin: 0; border: none; overflow: hidden; }
        .kanban-card:hover { transform: translateY(-2px); box-shadow: var(--card-shadow-hover); }

        .kanban-card-header { padding: 0.75rem 1.25rem; display: flex; justify-content: space-between; align-items: flex-start; cursor: pointer; }
        .kanban-card-body { padding: 0 1.25rem 1.25rem 1.25rem; }
        .kanban-card-footer { padding: 0.75rem 1.25rem; border-top: 1px solid var(--card-border); background-color: var(--column-bg); font-size: 0.875rem; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center; }
        .card-menu { font-size: 1rem; color: var(--text-muted); cursor: pointer; }
        .group-badge { display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 50%; color: white; font-size: 0.75rem; font-weight: bold; margin-left: 8px; }
        .progress-container { margin-top: 0.75rem; }
        .progress { height: 8px; background-color: var(--progress-bg); border-radius: 4px; overflow: hidden; }
        .progress-bar { height: 100%; background-color: var(--progress-bar-bg); border-radius: 4px; transition: width 0.3s ease; }
        
        .process-list { display: flex; flex-wrap: wrap; gap: 0.25rem; }
        .process-tag { padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 500; }
        .process-tag-done { background-color: #10b981; color: white; }
        .process-tag-pending { background-color: var(--warning-color); color: white; }


        :fullscreen #kanbanContainer { height: 100vh; padding: 1rem; }
        :fullscreen .kanban-board { align-items: stretch; }
        :fullscreen .kanban-column { height: 100%; }
        .cursor-pointer { cursor: pointer; }
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
        let masterOrderList = @json($processOrders);
        const customerId = {{ $customer->id }};
        const productionLinesData = @json($productionLines);
        
        let hasUnsavedChanges = false;
        let draggedCard = null;
        let cachedDropPosition = null; // Cachear posición detectada durante dragOver
        let cachedTargetContainer = null; // Cachear contenedor objetivo
        
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
                acc[`line_${line.id}`] = { id: `line_${line.id}`, name: line.name, items: [], color: '#3b82f6', productionLineId: line.id, type: 'production' };
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

        // --- LÓGICA DE ORGANIZACIÓN AUTOMÁTICA CON IA (GEMINI) ---
        
        function isOrderUrgent(order) {
            if (!order.delivery_date || ['completed', 'cancelled'].includes(order.status)) {
                return false;
            }
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const deliveryDate = new Date(order.delivery_date);
            deliveryDate.setHours(0, 0, 0, 0);
            if (isNaN(deliveryDate)) return false;
            const diffTime = deliveryDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays <= 5;
        }

        async function autoOrganizeWithAI() {
            const apiKey = "AIzaSyDt4KWXISfHgcDCLX2IJIXXf2rY0NjxVvo";
            const autoOrganizeBtn = document.getElementById('autoOrganizeBtn');
            autoOrganizeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            autoOrganizeBtn.disabled = true;

            try {
                const workableOrders = masterOrderList
                    .filter(order => !['completed', 'paused', 'cancelled'].includes(order.status))
                    .map(order => ({
                        id: order.id,
                        delivery_date: order.delivery_date,
                        theoretical_time: parseFloat(order.theoretical_time) || 1,
                        is_urgent: isOrderUrgent(order)
                    }));
                
                const productionLines = Object.values(columns)
                    .filter(c => c.type === 'production')
                    .map(c => ({ id: c.productionLineId, name: c.name }));

                if (workableOrders.length === 0 || productionLines.length === 0) {
                    showToast(translations.noOrdersToOrganize, 'info');
                    return;
                }

                const prompt = `
                    Eres un experto en planificación de producción. Tu tarea es organizar las siguientes órdenes de producción de la manera más eficiente posible, asignándolas a las líneas de producción disponibles.
                    Reglas de Priorización y Balanceo:
                    1.  **Balanceo de Carga:** El objetivo principal es balancear la carga de trabajo total (suma de 'theoretical_time') entre todas las líneas de producción de la forma más equitativa posible.
                    2.  **Urgencia:** Las órdenes marcadas como 'is_urgent: true' tienen la máxima prioridad y deben ser procesadas antes que las no urgentes.
                    3.  **Fecha de Entrega:** Dentro de cada grupo (urgentes y no urgentes), las órdenes con la 'delivery_date' más cercana en el tiempo deben ir primero.
                    4.  **Tiempo Teórico:** Si todo lo demás es igual, las órdenes con mayor 'theoretical_time' van primero.
                    5.  **Se tiene que usar los id reales de production_line_id no se tienen que inventar estos id .
                    6.  **Se tiene que usar los id reales de order_id no se tienen que inventar estos id . Ademas no olvidar de ninguno
                    Líneas de Producción Disponibles: ${JSON.stringify(productionLines)}
                    Órdenes a Organizar: ${JSON.stringify(workableOrders)}
                    Tu respuesta DEBE ser únicamente un objeto JSON con una clave "assignments" que contenga un array. CADA ORDEN de la lista 'Órdenes a Organizar' debe tener su correspondiente entrada en el array "assignments". No dejes ninguna orden sin asignar. Cada elemento del array debe ser un objeto con "order_id" (número) y "assigned_line_id" (número). No incluyas explicaciones ni texto adicional.
                `;

                console.log("--- PROMPT ENVIADO A GEMINI ---", prompt);

                const payload = {
                    contents: [{ role: "user", parts: [{ text: prompt }] }],
                    generationConfig: {
                        responseMimeType: "application/json",
                        responseSchema: {
                            type: "OBJECT",
                            properties: {
                                "assignments": {
                                    type: "ARRAY",
                                    items: {
                                        type: "OBJECT",
                                        properties: {
                                            "order_id": { "type": "NUMBER" },
                                            "assigned_line_id": { "type": "NUMBER" }
                                        },
                                        required: ["order_id", "assigned_line_id"]
                                    }
                                }
                            },
                            required: ["assignments"]
                        }
                    }
                };

                const apiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=${apiKey}`;
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) {
                    const errorBody = await response.json();
                    throw new Error(`Error de la API de Gemini: ${errorBody.error?.message || response.statusText}`);
                }
                
                const result = await response.json();
                console.log("--- RESPUESTA RECIBIDA DE GEMINI (RAW) ---", result);

                 // Espera sin bloquear la página
                await wait(20000); // Espera 20 segundos

                const jsonText = result.candidates[0].content.parts[0].text;
                const parsedResult = JSON.parse(jsonText);
                
                if (!parsedResult.assignments || parsedResult.assignments.length < workableOrders.length) {
                    console.warn("La IA no ha asignado todas las órdenes. Las no asignadas quedarán en pendientes.");
                }

                hasUnsavedChanges = true;
                const assignedLineIds = new Set();
                
                masterOrderList.forEach(order => {
                    if (!['completed', 'paused', 'cancelled'].includes(order.status)) {
                        order.productionLineId = null;
                        order.status = 'pending';
                    }
                });

                parsedResult.assignments.forEach(assignment => {
                    const order = masterOrderList.find(o => o.id === assignment.order_id);
                    if (order) {
                        order.productionLineId = assignment.assigned_line_id;
                        if (!assignedLineIds.has(assignment.assigned_line_id)) {
                            order.status = 'in_progress';
                            assignedLineIds.add(assignment.assigned_line_id);
                        } else {
                            order.status = 'pending';
                        }
                    }
                });
                
                masterOrderList.forEach((order, index) => order.orden = index);
                
                distributeAndRender(false, () => recalculatePositions());
                showToast(translations.organizingWithAISuccess, 'success');

            } catch (error) {
                console.error('Error al organizar con IA:', error);
                showToast(`${translations.organizingWithAIError} ${error.message}`, 'error');
            } finally {
                autoOrganizeBtn.innerHTML = '✨';
                autoOrganizeBtn.disabled = false;
            }
        }

        // --- 2. LÓGICA PRINCIPAL DE RENDERIZADO ---

        function distributeAndRender(shouldSort = true, callback = null) {
            const searchTerm = searchInput.value.trim().toLowerCase();
            
            let ordersToDisplay = searchTerm ? masterOrderList.filter(order => {
                return (String(order.order_id || '').toLowerCase().includes(searchTerm) ||
                        String(order.json?.descrip || '').toLowerCase().includes(searchTerm) ||
                        String(order.customerId || '').toLowerCase().includes(searchTerm));
            }) : [...masterOrderList];

            if (shouldSort) {
                ordersToDisplay.sort((a, b) => (a.orden || 0) - (b.orden || 0));
            }

            Object.values(columns).forEach(column => {
                column.items = [];
                if (column.subStates) {
                    column.subStates.forEach(sub => { sub.items = []; });
                }
            });

            ordersToDisplay.forEach(order => {
                let targetColumnKey = null;
                if (['completed', 'paused', 'cancelled'].includes(order.status)) {
                    targetColumnKey = 'final_states';
                } else if (order.productionLineId) {
                    targetColumnKey = `line_${order.productionLineId}`;
                } else {
                    targetColumnKey = 'pending_assignment';
                }

                if (columns[targetColumnKey]) {
                    if (targetColumnKey === 'final_states') {
                        const subState = columns.final_states.subStates.find(s => s.id === order.status);
                        if (subState) subState.items.push(order);
                    } else {
                        columns[targetColumnKey].items.push(order);
                    }
                }
            });
            
            renderBoard();
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

                if (column.type === 'final_states') {
                    column.subStates.forEach(subState => {
                        const subContainer = columnElement.querySelector(`.final-state-section[data-state="${subState.id}"] .column-cards`);
                        appendCards(subState.items, subContainer);
                    });
                } else {
                    const container = columnElement.querySelector('.column-cards');
                    appendCards(column.items, container);
                }
                
                // Actualizar los tiempos acumulados para esta columna
                updateAccumulatedTimes(columnElement);
                fragment.appendChild(columnElement);
            });
            kanbanBoard.appendChild(fragment);
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
            
            // Crear y mostrar placeholder Y cachear posición
            const afterElement = getDragAfterElement(targetCardsContainer, event.clientY);
            
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
            
            // Usar posición cacheada si está disponible
            if (cachedDropPosition && cachedDropPosition.afterElementId) {
                afterElementId = cachedDropPosition.afterElementId;
                console.log('✅ Usando afterElement cacheado:', afterElementId);
            } else {
                console.log('⚠️ No hay posición cacheada, insertando al final');
            }
            
            // Eliminar de posición original
            const oldMasterIndex = masterOrderList.findIndex(o => o.id === cardId);
            if (oldMasterIndex > -1) masterOrderList.splice(oldMasterIndex, 1);
            
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
            
            // Lógica especial para columnas de producción
            if (targetIsProduction) {
                const targetItems = masterOrderList.filter(o => o.productionLineId === columnData.productionLineId);
                const inProgressItem = targetItems.find(o => o.status === 'in_progress');
                if (inProgressItem) {
                    const itemIndex = masterOrderList.findIndex(o => o.id === inProgressItem.id);
                    if (itemIndex > -1) masterOrderList.splice(itemIndex, 1);

                    const firstIndexOfGroup = masterOrderList.findIndex(o => o.productionLineId === columnData.productionLineId);
                    if (firstIndexOfGroup > -1) {
                        masterOrderList.splice(firstIndexOfGroup, 0, inProgressItem);
                    } else {
                         masterOrderList.push(inProgressItem);
                    }
                }
            }

            distributeAndRender(false, () => {
                // Recalcular posiciones
                recalculatePositions();
                
                // Actualizar los tiempos acumulados en todas las columnas
                document.querySelectorAll('.kanban-column').forEach(column => {
                    updateAccumulatedTimes(column);
                });
                
                // También actualizar en secciones de estados finales
                document.querySelectorAll('.final-state-section').forEach(section => {
                    updateAccumulatedTimes(section);
                });
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
            
            let headerStatsHtml = `
                <div class="column-header-stats">
                    <span class="card-count-badge" title="${translations.cardCountTitle}">0</span>
                    <span class="time-sum-badge" title="${translations.totalTimeTitle}"><i class="far fa-clock"></i> 00:00:00</span>
                </div>
            `;
            
            let innerHTML;
            if (column.type === 'final_states') {
                innerHTML = `<div class="column-header">
                                <h3 class="column-title">${column.name}</h3>
                                ${headerStatsHtml}
                             </div>
                             <div class="final-states-container">
                                 ${column.subStates.map(subState => `
                                     <div class="final-state-section" data-state="${subState.id}" style="border-left-color: ${subState.color};">
                                         <div class="final-state-header"><span class="final-state-title" style="color: ${subState.color};">${subState.name}</span></div>
                                         <div class="column-cards"></div>
                                     </div>`).join('')}
                             </div>`;
                columnElement.innerHTML = innerHTML;
                columnElement.querySelectorAll('.column-cards').forEach(el => {
                    el.addEventListener('dragover', dragOver);
                    el.addEventListener('dragleave', resetDropZones);
                    el.addEventListener('drop', drop);
                });
                columnElement.addEventListener('dragover', dragOver);
                columnElement.addEventListener('dragleave', resetDropZones);
                columnElement.addEventListener('drop', drop);
            } else {
                innerHTML = `<div class="column-header" style="border-left: 4px solid ${column.color};">
                                <h3 class="column-title">${column.name}</h3>
                                ${headerStatsHtml}
                             </div>
                             <div class="column-cards"></div>`;
                columnElement.innerHTML = innerHTML;
                columnElement.querySelector('.column-cards').addEventListener('dragover', dragOver);
                columnElement.querySelector('.column-cards').addEventListener('dragleave', resetDropZones);
                columnElement.querySelector('.column-cards').addEventListener('drop', drop);
                columnElement.addEventListener('dragover', dragOver);
                columnElement.addEventListener('dragleave', resetDropZones);
                columnElement.addEventListener('drop', drop);
            }
            return columnElement;
        }

        function createCardElement(order) {
            const card = document.createElement('div');
            card.className = 'kanban-card collapsed';
            card.dataset.id = order.id;
            card.draggable = true;
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);

            card.style.borderLeftColor = order.statusColor || '#6b7280';
            
            const createdAtFormatted = new Date(order.created_at).toLocaleDateString();
            const deliveryDateFormatted = order.delivery_date ? new Date(order.delivery_date).toLocaleDateString() : '';
            const processDescription = '{{ $process->description }}';

            let urgencyIconHtml = '';
            let stockIconHtml = '';
            
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

            const groupNumber = order.grupo_numero || 0;
            const groupColors = ['#6b7280', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'];
            const groupBadgeHtml = groupNumber > 0 ? `<span class="group-badge" style="background-color: ${groupColors[groupNumber-1] || '#6b7280'}" title="Grupo ${groupNumber}">${groupNumber}</span>` : '';
            
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

            card.innerHTML = `
                <div class="kanban-card-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div class="me-2" style="flex-grow: 1;">
                        <div class="fw-bold text-sm d-flex align-items-center">#${order.order_id}${urgencyIconHtml}${stockIconHtml} ${groupBadgeHtml}</div>
                        <div class="text-xs fw-bold text-muted mt-1">${order.customerId || translations.noCustomer}</div>
                        ${processDescription ? `<div class="text-xs text-muted mt-1">${processDescription}</div>` : ''}
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
                             <div class="d-flex flex-column me-2">
                                <span class="d-flex align-items-center"><i class="far fa-clock text-muted me-1" title="Tiempo teórico"></i><span class="text-xs">${order.theoretical_time || 'N/A'} </span></span>
                             </div>
                             <div class="d-flex flex-column">
                                <span class="d-flex align-items-center accumulated-time-badge d-none" title="Tiempo acumulado de tarjetas anteriores"> <i class="fas fa-hourglass-half text-muted me-1"></i><span class="text-xs">00:00:00</span></span>
                             </div>
                         </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="text-xs text-muted"><i class="far fa-calendar-alt me-1"></i>${createdAtFormatted}</div>
                        ${deliveryDateFormatted ? `<div class="text-xs text-danger fw-bold"><i class="fas fa-truck me-1"></i>${deliveryDateFormatted}</div>` : ''}
                    </div>
                </div>
                <div class="kanban-card-footer">
                    <span class="text-xs fw-medium">${translations.unassigned}</span>
                    <div class="assigned-avatars d-flex align-items-center"><img class="avatar-img" style="width:28px; height:28px; border-radius:50%;" src="https://i.pravatar.cc/40?img=1" alt="user"></div>
                </div>`;
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
            
            // También actualizamos para las tarjetas en estados finales si existen
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

        // --- 5. GUARDADO DE DATOS Y OTROS EVENTOS ---

        function saveKanbanChanges() {
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
                hasUnsavedChanges = false;
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
            });
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

            Swal.fire({
                title: `{{ __('Order') }} #${order.order_id}`,
                showCloseButton: true,
                showConfirmButton: false,
                html: `
                    <div class="d-flex flex-column gap-2 my-4">
                        <button id="viewIncidentsBtn" class="btn btn-danger w-100">{{ __('View Incidents') }}</button>
                        <button id="viewOriginalOrderBtn" class="btn btn-info w-100" ${isOriginalOrderDisabled ? 'disabled' : ''}>
                            {{ __('View Original Order') }}
                        </button>
                    </div>`,
                didOpen: () => {
                    const popup = Swal.getPopup();
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

        function toggleFullscreen() {
            const element = document.getElementById('kanbanContainer');
            if (!document.fullscreenElement) {
                element.requestFullscreen().catch(err => showToast(translations.fullscreenError, 'error'));
            } else {
                document.exitFullscreen();
            }
        }
        
        // --- 6. INICIALIZACIÓN Y EVENT LISTENERS ---
        
        document.getElementById('saveChangesBtn').addEventListener('click', saveKanbanChanges);
        document.getElementById('refreshBtn').addEventListener('click', () => window.location.reload());
        document.getElementById('fullscreenBtn').addEventListener('click', toggleFullscreen);
        document.getElementById('autoOrganizeBtn').addEventListener('click', autoOrganizeWithAI);
        
        searchInput.addEventListener('input', () => setTimeout(() => distributeAndRender(true), 300));
        
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
            const cards = kanbanBoard.querySelectorAll('.kanban-card');
            cards.forEach((card, index) => {
                const orderId = parseInt(card.dataset.id);
                const order = masterOrderList.find(o => o.id === orderId);
                if (order) {
                    order.orden = index;
                }
            });
        }

        distributeAndRender(true, () => {
            // Actualizar los tiempos acumulados en todas las columnas al inicializar
            document.querySelectorAll('.kanban-column').forEach(column => {
                updateAccumulatedTimes(column);
            });
            
            // También actualizar en secciones de estados finales
            document.querySelectorAll('.final-state-section').forEach(section => {
                updateAccumulatedTimes(section);
            });
            
            console.log('Kanban final inicializado con tiempos acumulados');
        });

        //ponemos un wait que reciva ms desde otra parte
        function wait(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

    });
    </script>
@endpush
