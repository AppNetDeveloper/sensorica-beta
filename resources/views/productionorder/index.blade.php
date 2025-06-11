@extends('layouts.admin')

@section('title', __('Production Order Kanban Control'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Production Order Kanban Control') }}</li>
    </ul>
@endsection

@section('content')


    <!-- Barra de Filtros y Controles -->
    <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <div class="flex flex-nowrap items-center justify-between gap-3 w-full overflow-x-auto">
            <!-- Grupo Izquierdo: Filtros y Acciones -->
            <div class="flex items-center gap-3 flex-nowrap whitespace-nowrap">
                <select id="statusFilter" class="px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="0">{{ __('Pending') }}</option>
                    <option value="1">{{ __('In Progress') }}</option>
                    <option value="2">{{ __('Completed') }}</option>
                    <option value="3">{{ __('Incident') }}</option>
                </select>
                <button id="applyFilters" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md flex items-center gap-1">
                    <i class="fas fa-filter"></i>
                    <span>{{ __('Apply') }}</span>
                </button>
                <button id="refreshBtn" class="p-2 h-10 w-10 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg shadow-md hover:shadow-lg transition-all" title="{{ __('Refresh') }}">
                    <i class="fas fa-sync-alt text-blue-500"></i>
                </button>
                <button id="fullscreenBtn" class="p-2 h-10 w-10 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg shadow-md hover:shadow-lg transition-all" title="{{ __('Fullscreen') }}">
                    <i class="fas fa-expand-arrows-alt text-blue-500"></i>
                </button>
                <!-- Búsqueda - Ahora a la derecha -->
                <div class="relative min-w-[200px] ml-auto">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="{{ __('Search...') }}"
                           class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600">
                </div>
            </div>


        </div>
    </div>

    <!-- Contenedor del Kanban -->
    <div id="kanbanContainer" class="relative">
        <div class="kanban-board" role="list" aria-label="{{ __('Kanban Board') }}"></div>
        <div id="loadingOverlay" class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <p class="text-center">{{ __('Loading data...') }}</p>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        :root {
            /* Variables para modo claro */
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
            --primary-light: #dbeafe;
            --danger-color: #ef4444;
            --text-muted: #6b7280;
            --transition-speed: 0.2s;
        }

        body.dark {
            /* Variables para modo oscuro */
            --kanban-bg: #0f172a;
            --column-bg: #1e293b;
            --column-border: #334155;
            --header-bg: #334155;
            --header-text: #f1f5f9;
            --card-bg: #2d3748;
            --card-text: #e2e8f0;
            --card-hover-bg: #334155;
            --card-border: #4a5568;
            --card-shadow: 0 2px 4px rgba(0,0,0,0.2);
            --card-shadow-hover: 0 5px 15px rgba(0,0,0,0.3);
            --scrollbar-thumb: #475569;
            --primary-color: #60a5fa;
            --primary-light: #1e40af;
            --danger-color: #f87171;
            --text-muted: #94a3b8;
        }

        .kanban-board {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            overflow-x: auto;
            background-color: var(--kanban-bg);
            min-height: calc(100vh - 250px);
            scrollbar-width: thin;
            scrollbar-color: var(--scrollbar-thumb) transparent;
        }

        .kanban-board::-webkit-scrollbar { height: 10px; }
        .kanban-board::-webkit-scrollbar-track { background: transparent; }
        .kanban-board::-webkit-scrollbar-thumb {
            background-color: var(--scrollbar-thumb);
            border-radius: 10px;
            border: 2px solid var(--kanban-bg);
        }
        .kanban-board::-webkit-scrollbar-thumb:hover { background-color: var(--primary-color); }

        .column {
            flex: 1 0 340px;
            background-color: var(--column-bg);
            border-radius: 12px;
            min-width: 340px;
            max-width: 380px;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--column-border);
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            transition: all var(--transition-speed) ease;
            max-height: calc(100vh - 290px);
        }

        .column-header {
            padding: 0.75rem 1rem;
            position: sticky;
            top: 0;
            background-color: var(--column-bg);
            z-index: 10;
            border-bottom: 1px solid var(--column-border);
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .column-title {
            font-weight: 600;
            color: var(--header-text);
            margin: 0;
            font-size: 1rem;
        }

        .column-cards {
            padding: 0.75rem;
            overflow-y: auto;
            flex-grow: 1;
            scrollbar-width: thin;
            scrollbar-color: var(--scrollbar-thumb) transparent;
        }
        .column-cards::-webkit-scrollbar { width: 6px; }
        .column-cards::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb); border-radius: 6px; }

        .card {
            background-color: var(--card-bg);
            color: var(--card-text);
            border-radius: 10px;
            border: 1px solid var(--card-border);
            border-left: 5px solid; /* El color se asignará dinámicamente */
            box-shadow: var(--card-shadow);
            margin-bottom: 1rem;
            transition: all 0.25s ease-in-out;
            cursor: grab;
            animation: fadeIn 0.3s ease-out;
            overflow: hidden;
        }

        .card.dragging {
            opacity: 0.6;
            transform: rotate(3deg);
            box-shadow: var(--card-shadow-hover);
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-shadow-hover);
        }

        .card-header {
            padding: 0.75rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .card-body { 
            padding: 0 1.25rem 1.25rem 1.25rem;
        }

        .card-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--card-border);
            background-color: var(--column-bg);
            font-size: 0.875rem;
            color: var(--text-muted);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-menu {
            font-size: 1rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .card-menu:hover { color: var(--primary-color); }
        .assigned-avatars .avatar-img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid var(--card-bg);
            margin-left: -10px;
        }
        .assigned-avatars .avatar-img:first-child { margin-left: 0; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card.collapsed .card-body, .card.collapsed .card-footer { display: none; }

        #kanbanContainer:fullscreen {
            background-color: var(--kanban-bg);
            overflow: hidden; 
            padding-bottom: 20px;
        }

        #kanbanContainer:fullscreen .kanban-board {
            height: 100%;
            min-height: auto;
            overflow-x: hidden;
        }

        #kanbanContainer:fullscreen .column {
            height: 100%;
            max-height: none;
            flex-grow: 1; 
            flex-shrink: 1; 
            flex-basis: 0; 
            min-width: 280px; 
        }
    </style>
@endpush

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Font Awesome para iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- VARIABLES GLOBALES ---
        let masterOrderList = [];
        let refreshInterval;
        const REFRESH_INTERVAL_MS = 30000;
        let isLoading = false;
        const orderData = {};
        const existingCards = new Map();
        let draggedCard = null;

        const columns = {
            0: { id: 'pending',   name: "{{ __('To Do') }}",       color: '#f59e0b' },
            1: { id: 'started',   name: "{{ __('In Progress') }}", color: '#3b82f6' },
            2: { id: 'completed', name: "{{ __('Completed') }}",   color: '#10b981' },
            3: { id: 'incident',  name: "{{ __('Incident') }}",    color: '#ef4444' },
            4: { id: 'paused',    name: "{{ __('Paused') }}",      color: '#a855f7' },
            5: { id: 'cancelled', name: "{{ __('Cancelled') }}",   color: '#64748b' }
        };

        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        const kanbanBoard = document.querySelector('.kanban-board');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');

        if (!token) {
            Swal.fire({ icon: 'error', title: 'Error', text: "{{ __('Token not provided in URL.') }}" });
            return;
        }

        // --- LÓGICA DE DATOS Y RENDERIZADO ---

        async function loadAllPages(isAutoRefresh = false) {
            if (isLoading) return;
            isLoading = true;
            
            // Guardar posición del scroll y tarjetas abiertas en auto-refresh
            let scrollLeft = 0;
            const openCardIds = new Set();
            if (isAutoRefresh) {
                if(kanbanBoard) scrollLeft = kanbanBoard.scrollLeft;
                document.querySelectorAll('.card:not(.collapsed)').forEach(card => {
                    if (card.dataset.id) openCardIds.add(parseInt(card.dataset.id, 10));
                });
            } else {
                 toggleLoading(true);
            }
            
            if (refreshInterval) clearInterval(refreshInterval);

            try {
                let currentPage = 1;
                let lastPage = 1;
                let newMasterList = [];

                do {
                    const url = buildApiUrl(currentPage);
                    const response = await fetch(url);
                    if (!response.ok) throw new Error("{{ __('Error fetching page ') }}" + currentPage);

                    const responseData = await response.json();
                    lastPage = responseData.last_page || 1;
                    const newOrders = responseData.data || [];
                    newMasterList.push(...newOrders);
                    
                    currentPage++;
                    
                    if(currentPage <= lastPage) {
                         await new Promise(resolve => setTimeout(resolve, 400));
                    }

                } while (currentPage <= lastPage);
                
                masterOrderList = newMasterList;
                applyAndRenderFilters(isAutoRefresh, openCardIds, scrollLeft);
                
                if (!isAutoRefresh) {
                    showNotification("{{ __('All data loaded successfully') }}", 'success');
                }

            } catch (error) {
                console.error('Error loading Kanban data:', error);
                if (!isAutoRefresh) showNotification(error.message, 'error');
            } finally {
                isLoading = false;
                if (!isAutoRefresh) toggleLoading(false);
                startAutoRefresh();
            }
        }
        
        function applyAndRenderFilters(isAutoRefresh = false, openCardIds = new Set(), scrollLeft = 0) {
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

            renderBoard(filteredOrders, isAutoRefresh, openCardIds);

            // Restaurar posición del scroll si es un auto-refresh
            if (isAutoRefresh && kanbanBoard) {
                kanbanBoard.scrollLeft = scrollLeft;
            }
        }

        function renderBoard(ordersToRender, isAutoRefresh = false, openCardIds = new Set()) {
            initializeBoard();
            
            ordersToRender.forEach(order => {
                const columnInfo = columns[order.status];
                if (!columnInfo) return;

                const columnCardsContainer = document.querySelector(`#${columnInfo.id} .column-cards`);
                if (!columnCardsContainer) return;
                
                order.json = typeof order.json === 'string' ? JSON.parse(order.json) : (order.json || {});
                orderData[order.id] = order.json;
                
                const cardElement = createCardElement(order);
                
                existingCards.set(order.id, cardElement);
                columnCardsContainer.appendChild(cardElement);

                if ((isAutoRefresh && openCardIds.has(order.id)) || !cardElement.classList.contains('collapsed')) {
                    cardElement.classList.remove('collapsed');
                }
            });
        }

        function initializeBoard() {
            kanbanBoard.innerHTML = '';
            existingCards.clear();
            const fragment = document.createDocumentFragment();

            Object.values(columns).forEach(column => {
                const columnElement = document.createElement('div');
                columnElement.className = 'column';
                columnElement.id = column.id;
                
                columnElement.innerHTML = `
                    <div class="column-header">
                        <h3 class="column-title" style="color: ${column.color};">${column.name}</h3>
                    </div>
                    <div class="column-cards"></div>
                `;
                columnElement.ondragover = dragOver;
                columnElement.ondrop = drop;
                fragment.appendChild(columnElement);
            });
            kanbanBoard.appendChild(fragment);
        }
        
        function createCardElement(order) {
            const columnInfo = columns[order.status];
            const card = document.createElement('div');
            card.className = 'card collapsed';
            card.dataset.id = order.id;
            card.draggable = true;
            card.style.borderLeftColor = columnInfo.color;
            card.innerHTML = createCardHTML(order);
            
            card.ondragstart = dragStart;
            card.ondragend = dragEnd;
            return card;
        }

        function createCardHTML(order) {
            const orderJson = order.json;
            const createdAtFormatted = new Date(order.created_at).toLocaleString(undefined, { day: '2-digit', month: '2-digit', year: 'numeric' });
            const productName = orderJson?.refer?.descrip || '{{__("No product name")}}';
            const customerName = orderJson?.refer?.customerId || 'N/A';
            
            return `
                <div class="card-header" onclick="this.parentElement.classList.toggle('collapsed')">
                    <div class="font-bold text-sm text-gray-700 dark:text-gray-200">#${order.order_id}</div>
                    <span class="card-menu" role="button" aria-label="{{ __('Show Card Menu') }}" onclick="event.stopPropagation(); showCardMenu('${order.id}')">
                        <i class="fas fa-ellipsis-h"></i>
                    </span>
                </div>
                <div class="card-body">
                    <p class="font-semibold text-base mb-3 truncate" title="${productName}">${productName}</p>
                    
                    <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-300 mb-3">
                        <span class="flex items-center gap-2" title="{{__('Boxes')}}"><i class="fas fa-box-open text-gray-400"></i> ${order.box || 0}</span>
                        <span class="flex items-center gap-2" title="{{__('Units')}}"><i class="fas fa-dolly text-gray-400"></i> ${order.units || 0}</span>
                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-3 pt-2 border-t border-gray-200 dark:border-gray-700 space-y-1">
                        <p><strong>{{__("Customer")}}:</strong> ${customerName}</p>
                        <p><strong>{{__("Created")}}:</strong> ${createdAtFormatted}</p>
                    </div>
                </div>
                <div class="card-footer">
                     <span class="text-xs font-medium">{{__("Assigned")}}</span>
                    <div class="assigned-avatars flex items-center">
                        <img class="avatar-img" src="https://i.pravatar.cc/40?img=1" alt="user" loading="lazy" title="User 1">
                        <img class="avatar-img" src="https://i.pravatar.cc/40?img=2" alt="user" loading="lazy" title="User 2">
                        <img class="avatar-img" src="https://i.pravatar.cc/40?img=3" alt="user" loading="lazy" title="User 3">
                    </div>
                </div>
            `;
        }

        function dragStart(event) {
            draggedCard = event.target;
            setTimeout(() => event.target.classList.add('dragging'), 0);
        }

        function dragEnd(event) {
            event.target.classList.remove('dragging');
            draggedCard = null;
        }

        function dragOver(event) {
            event.preventDefault();
        }

        async function drop(event) {
            event.preventDefault();
            const targetColumnEl = event.target.closest('.column');
            if (!draggedCard || !targetColumnEl) return;

            const orderId = draggedCard.dataset.id;
            const newStatusEntry = Object.entries(columns).find(([, col]) => col.id === targetColumnEl.id);
            if (!newStatusEntry) return;

            const newStatusKey = newStatusEntry[0];
            const originalParent = draggedCard.parentElement;

            targetColumnEl.querySelector('.column-cards').appendChild(draggedCard);
            draggedCard.style.borderLeftColor = columns[newStatusKey].color;

            try {
                const response = await fetch(`/api/production-orders/${orderId}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({
                        status: parseInt(newStatusKey),
                        token: token
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: "{{ __('Error updating order status.') }}" }));
                    throw new Error(errorData.message);
                }
                
                const orderInMasterList = masterOrderList.find(o => o.id == orderId);
                if (orderInMasterList) {
                    orderInMasterList.status = newStatusKey;
                }

                showNotification("{{ __('Order updated successfully') }}", 'success');

            } catch (error) {
                console.error('Error updating status:', error);
                showNotification(error.message, 'error');
                originalParent.appendChild(draggedCard);
                const originalStatus = Object.entries(columns).find(([, col]) => col.id === originalParent.parentElement.id);
                if(originalStatus) {
                    draggedCard.style.borderLeftColor = columns[originalStatus[0]].color;
                }
            } finally {
                draggedCard = null;
            }
        }

        window.showCardMenu = function(orderId) {
            const orderJson = orderData[orderId];
            if (!orderJson) {
                showNotification("{{ __('Order data not found.') }}", "error");
                return;
            }
            Swal.fire({
                title: `{{ __('Order') }} #${orderJson.orderId || orderId}`,
                showCloseButton: true,
                showConfirmButton: false,
                html: `
                    <div class="flex flex-col gap-2 my-4">
                        <button id="viewJsonBtn" class="swal2-styled w-full">{{ __('View JSON') }}</button>
                        <button id="exportDbBtn" class="swal2-styled w-full">{{ __('Export to External DB') }}</button>
                        <button id="deleteBtn" class="swal2-styled swal2-deny w-full">{{ __('Delete') }}</button>
                    </div>
                `,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    popup.querySelector('#viewJsonBtn').addEventListener('click', () => {
                        Swal.fire({
                            title: "{{ __('Order JSON') }}",
                            html: `<pre class="text-left text-sm p-4 bg-gray-100 dark:bg-gray-800 rounded-lg max-h-96 overflow-auto">${JSON.stringify(orderJson, null, 2)}</pre>`,
                            width: '80%',
                        });
                    });
                    popup.querySelector('#exportDbBtn').addEventListener('click', () => handleExport(orderId));
                    popup.querySelector('#deleteBtn').addEventListener('click', () => Swal.fire('{{ __("Delete clicked") }}'));
                }
            });
        }
        
        async function handleExport(orderId) {
            Swal.fire({
                title: "{{ __('Processing...') }}",
                text: "{{ __('Please wait while exporting the order.') }}",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch('/api/transfer-external-db', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ orderId: orderId, externalSend: true })
                });

                if (!response.ok) {
                   const errorData = await response.json().catch(() => ({ message: `Error ${response.status}` }));
                   throw new Error(errorData.message);
                }
                
                const data = await response.json();
                Swal.fire('{{ __("Success") }}', '{{ __("Transfer completed successfully") }}', 'success');
                console.log("API Response:", data);

            } catch (error) {
                console.error("Transfer Error:", error);
                Swal.fire('{{ __("Error") }}', `{{ __("An error occurred during the transfer:") }} ${error.message}`, 'error');
            }
        }
        
        function buildApiUrl(page = 1) {
            const params = new URLSearchParams({ token: token, page: page });
            return `/api/production-orders?${params.toString()}`;
        }

        function toggleLoading(show) {
            if (loadingOverlay) {
                loadingOverlay.style.display = show ? 'flex' : 'none';
            }
        }

        function showNotification(message, type = 'success') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            Toast.fire({ icon: type, title: message });
        }

        function startAutoRefresh() {
            if (refreshInterval) clearInterval(refreshInterval);
            refreshInterval = setInterval(() => loadAllPages(true), REFRESH_INTERVAL_MS);
        }

        function toggleFullscreen() {
            const container = document.getElementById('kanbanContainer');
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => showNotification(`Error: ${err.message}`, 'error'));
            } else {
                document.exitFullscreen();
            }
        }
        
        // --- INICIALIZACIÓN Y EVENTOS ---
        
        initializeBoard();
        loadAllPages();

        document.getElementById('refreshBtn').addEventListener('click', () => loadAllPages());
        document.getElementById('fullscreenBtn').addEventListener('click', toggleFullscreen);
        
        document.getElementById('applyFilters').addEventListener('click', applyAndRenderFilters);
        statusFilter.addEventListener('change', applyAndRenderFilters);

        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(applyAndRenderFilters, 500);
        });

        document.addEventListener('fullscreenchange', () => {
            const btn = document.getElementById('fullscreenBtn');
            const icon = btn.querySelector('i');
            if (document.fullscreenElement) {
                icon.classList.replace('fa-expand-arrows-alt', 'fa-compress-arrows-alt');
                btn.title = "{{ __('Exit fullscreen (Esc)') }}";
            } else {
                icon.classList.replace('fa-compress-arrows-alt', 'fa-expand-arrows-alt');
                btn.title = "{{ __('Fullscreen (F11)') }}";
            }
        });

        window.addEventListener('beforeunload', () => {
            if (refreshInterval) clearInterval(refreshInterval);
        });
    });
    </script>
@endpush
