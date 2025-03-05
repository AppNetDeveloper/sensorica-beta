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
    {{-- Kanban Board --}}
    <div class="kanban-board" role="list" aria-label="{{ __('Kanban Board') }}"></div>
    <div id="load-more-container" style="text-align: center; margin: 1.5rem 0;"></div>
@endsection

@push('style')
    <style>
        :root {
            /* Variables para modo claro (Kanban blanco) */
            --kanban-bg: #ffffff;
            --column-bg: #f8f9fa;
            --header-bg: #e9ecef;
            --header-text: #212529;
            --card-bg: #ffffff;
            --card-text: #212529;
            --card-hover-bg: #f8f9fa;
            --scrollbar-thumb: #ced4da;
            --primary-color: #3b82f6;   /* Azul para botones y barra de progreso */
            --danger-color: #ef4444;    /* Rojo para etiquetas, etc. */
        }

        /* Variables para modo oscuro */
        body.dark-mode {
            --kanban-bg: #1f2937;
            --column-bg: #374151;
            --header-bg: #4b5563;
            --header-text: #ffffff;
            --card-bg: #ffffff; /* Se mantiene blanco para resaltar el contenido */
            --card-text: #1f2937;
            --card-hover-bg: #f3f4f6;
            --scrollbar-thumb: #6b7280;
            --primary-color: #3b82f6;
            --danger-color: #ef4444;
        }

        /* Contenedor principal del tablero Kanban */
        .kanban-board {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem;
            overflow-x: auto;
            background-color: var(--kanban-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .kanban-board::-webkit-scrollbar {
            height: 8px;
        }
        .kanban-board::-webkit-scrollbar-thumb {
            background: var(--scrollbar-thumb);
            border-radius: 4px;
        }
        .kanban-board::-webkit-scrollbar-thumb:hover {
            background: var(--header-bg);
        }

        /* Columnas del Kanban */
        .column {
            flex: 1;
            background-color: var(--column-bg);
            padding: 1rem;
            border-radius: 8px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            animation: fadeIn 0.3s ease-in-out;
        }

        /* Cabecera de cada columna */
        .column-header {
            background-color: #f9fafb; /* Fondo claro */
            border-left: 4px solid var(--primary-color); /* Línea de color a la izquierda */
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .column-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0; /* Eliminamos margen por defecto */
        }
        .column-actions {
            display: flex;
            gap: 0.5rem;
        }
        .column-actions button {
            background-color: transparent;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            color: #374151;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .column-actions button:hover {
            background-color: #f3f4f6;
        }

        /* Tarjetas */
        .card {
            background-color: var(--card-bg);
            color: var(--card-text);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            position: relative;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: grab;
            animation: fadeIn 0.5s ease-in-out;
        }
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            background-color: var(--card-hover-bg);
        }
        .card-header,
        .card-body,
        .card-footer {
            padding: 1rem;
        }
        /* Encabezado de la tarjeta */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .card-header-title {
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
            line-height: 1.2;
        }
        .card-menu {
            font-size: 1.25rem;
            color: var(--scrollbar-thumb);
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .card-menu:hover {
            color: var(--header-bg);
        }
        /* Cuerpo de la tarjeta */
        .card-body p {
            margin: 0.25rem 0;
            font-size: 0.9rem;
        }
        /* Pie de la tarjeta (placeholder para assigned users) */
        .card-footer {
            border-top: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        .assigned-avatars {
            display: flex;
            align-items: center;
        }
        .assigned-avatars .avatar-img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid #fff;
            margin-right: -8px;
        }

        /* Efecto al arrastrar */
        .card.dragging {
            opacity: 0.7;
            transform: rotate(2deg);
        }

        /* Animación */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Estilo para tarjetas colapsadas */
        .card.collapsed {
            height: 80px; /* Altura reducida para mostrar solo parte del contenido */
            overflow: hidden;
            transition: height 0.3s ease;
        }
        .card.collapsed:hover {
            height: auto;
        }
    </style>
@endpush

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Variables globales para paginación
        let currentPage = 1;
        let lastPage = 1;

        // Objeto global para almacenar el JSON de cada orden
        const orderData = {};

        // Columnas, usando traducciones de Laravel con Blade
        const columns = {
            0: { id: 'pending',   name: "{{ __('To Do') }}" },
            1: { id: 'started',   name: "{{ __('In Progress') }}" },
            2: { id: 'completed', name: "{{ __('Done') }}" },
            3: { id: 'paused',    name: "{{ __('Paused') }}" },
            4: { id: 'cancelled', name: "{{ __('Cancelled') }}" },
            5: { id: 'issues',    name: "{{ __('Issues') }}" }
        };

        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        if (!token) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: "{{ __('Token not provided in URL.') }}",
            });
            throw new Error('Token required to load data.');
        }

        // Usamos un Map para seguir las tarjetas ya creadas
        const existingCards = new Map();

        // Función para cargar datos; recibe la página a cargar (por defecto 1)
        async function loadKanbanData(page = 1) {
            try {
                const response = await fetch(`/api/production-orders?token=${token}&page=${page}`);
                if (!response.ok) throw new Error("{{ __('Error fetching API data.') }}");
                const responseData = await response.json();
                currentPage = responseData.current_page;
                lastPage = responseData.last_page;
                // Si estamos en la página 1, reiniciamos el tablero; en caso contrario, se añaden nuevos pedidos
                await updateKanbanBoard(responseData.data, page > 1);
                updateLoadMoreButton();
                updateCardCollapse();
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ __('An error occurred while loading Kanban data.') }}",
                });
            }
        }

        // Función para actualizar (o inicializar) el tablero; "append" indica si se añaden pedidos sin limpiar los existentes.
        async function updateKanbanBoard(orders, append = false) {
            const kanbanBoard = document.querySelector('.kanban-board');

            if (!append) {
                // Reiniciar el tablero: vaciar tarjetas y columnas
                existingCards.clear();
                kanbanBoard.innerHTML = "";
                // Crear columnas con la cabecera
                Object.entries(columns).forEach(([status, column]) => {
                    const columnElement = document.createElement('div');
                    columnElement.classList.add('column');
                    columnElement.id = column.id;
                    columnElement.setAttribute('role', 'list'); // Para accesibilidad

                    // Cabecera
                    columnElement.innerHTML = `
                        <div class="column-header">
                            <h3 class="column-title">${column.name}</h3>
                            <div class="column-actions">
                                <button
                                    type="button"
                                    role="button"
                                    aria-label="{{ __('Add Card') }}"
                                    title="{{ __('Add Card') }}"
                                    onclick="addCard('${column.id}')"
                                >+</button>
                                <button
                                    type="button"
                                    role="button"
                                    aria-label="{{ __('Delete Column') }}"
                                    title="{{ __('Delete Column') }}"
                                    onclick="deleteColumn('${column.id}')"
                                >🗑</button>
                            </div>
                        </div>
                    `;

                    kanbanBoard.appendChild(columnElement);
                });
            }

            const updatedOrderIds = new Set();

            // Procesar y añadir cada orden
            for (const order of orders.sort((a, b) => a.orden - b.orden)) {
                const columnId = columns[order.status].id;
                const columnElement = document.getElementById(columnId);

                let cardElement = existingCards.get(order.id);
                if (!cardElement) {
                    cardElement = document.createElement('div');
                    cardElement.classList.add('card');
                    cardElement.draggable = true;
                    cardElement.setAttribute('data-id', order.id);
                    cardElement.setAttribute('role', 'listitem'); // Accesibilidad
                    // Eventos de arrastrar
                    cardElement.ondragstart = dragStart;
                    cardElement.ondragend = dragEnd;
                    columnElement.appendChild(cardElement);
                    existingCards.set(order.id, cardElement);
                } else if (cardElement.parentElement !== columnElement) {
                    columnElement.appendChild(cardElement);
                }

                // Convertir order_id a string para evitar errores y mostrarlo
                const orderIdString = String(order.order_id);
                // Formatear fechas
                const createdAtFormatted = new Date(order.created_at).toLocaleString();
                const updatedAtFormatted = new Date(order.updated_at).toLocaleString();
                // Calcular días transcurridos desde la creación
                const createdDate = new Date(order.created_at);
                const now = new Date();
                const diffTime = now - createdDate;
                const daysSince = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                // Almacenar el JSON (parsearlo si es string)
                const orderJson = typeof order.json === 'string' ? JSON.parse(order.json) : order.json;
                orderData[order.id] = orderJson;

                // Construir el contenido de la tarjeta
                cardElement.innerHTML = `
                    <!-- ENCABEZADO DE LA TARJETA -->
                    <div class="card-header">
                        <div>
                            <p class="card-header-title">{{ __('OrderId') }}</p>
                            <p class="card-title">${orderIdString}</p>
                        </div>
                        <span
                            class="card-menu"
                            role="button"
                            aria-label="{{ __('Show Card Menu') }}"
                            onclick="showCardMenu(${order.id})"
                        >⋮</span>
                    </div>
                    <!-- CUERPO DE LA TARJETA -->
                    <div class="card-body">
                        <p><strong>{{ __('Box') }}:</strong> ${order.box}</p>
                        <p><strong>{{ __('Units/Box') }}:</strong> ${order.units_box}</p>
                        <p><strong>{{ __('Units') }}:</strong> ${order.units}</p>
                        <p><strong>{{ __('Created') }}:</strong> ${createdAtFormatted}</p>
                        <p><strong>{{ __('Updated') }}:</strong> ${updatedAtFormatted}</p>
                        <p><strong>{{ __('Created afte') }}:</strong> ${daysSince} {{ __('days') }}</p>
                    </div>
                    <!-- PIE DE LA TARJETA -->
                    <div class="card-footer">
                        <div class="assigned-avatars">
                            <img
                                class="avatar-img"
                                src="https://i.pravatar.cc/100?img=1"
                                alt="{{ __('Assigned user') }}"
                                loading="lazy"
                            >
                            <img
                                class="avatar-img"
                                src="https://i.pravatar.cc/100?img=2"
                                alt="{{ __('Assigned user') }}"
                                loading="lazy"
                            >
                            <img
                                class="avatar-img"
                                src="https://i.pravatar.cc/100?img=3"
                                alt="{{ __('Assigned user') }}"
                                loading="lazy"
                            >
                            <div
                                style="width:24px;height:24px;border-radius:50%;background:#fff;color:#111;display:flex;align-items:center;justify-content:center;font-size:0.75rem;margin-right:-8px;border:1px solid #fff;"
                            >
                                +2
                            </div>
                        </div>
                    </div>
                `;

                updatedOrderIds.add(order.id);
            }

            if (!append) {
                // En modo no-append, se eliminan las tarjetas que no están en la data (para refrescar)
                existingCards.forEach((cardElement, orderId) => {
                    if (!updatedOrderIds.has(orderId)) {
                        cardElement.remove();
                        existingCards.delete(orderId);
                    }
                });
            }

            // Asignar eventos de dragover y drop a cada columna
            document.querySelectorAll('.column').forEach(column => {
                column.ondragover = dragOver;
                column.ondrop = drop;
            });
        }

        // Función para actualizar (o crear) el botón "Load More"
        function updateLoadMoreButton() {
            const loadMoreContainer = document.getElementById('load-more-container');
            loadMoreContainer.innerHTML = "";
            if (currentPage < lastPage) {
                const btn = document.createElement('button');
                btn.textContent = "{{ __('Load More') }}";
                btn.style.padding = "0.5rem 1rem";
                btn.style.fontSize = "1rem";
                btn.style.backgroundColor = "var(--primary-color)";
                btn.style.color = "#fff";
                btn.style.border = "none";
                btn.style.borderRadius = "0.25rem";
                btn.style.cursor = "pointer";
                btn.onclick = () => loadKanbanData(currentPage + 1);
                loadMoreContainer.appendChild(btn);
            }
        }

        // Función para colapsar todas las tarjetas
        function updateCardCollapse() {
            document.querySelectorAll('.card').forEach(card => {
                card.classList.add('collapsed');
            });
        }

        // Función para mostrar el menú de opciones de la tarjeta usando SweetAlert2
        function showCardMenu(orderId) {
            const orderJson = orderData[orderId];
            Swal.fire({
                title: "{{ __('Order Notice') }}",
                html: `
                    <button
                        id="verJson"
                        class="swal2-confirm swal2-styled"
                        style="margin:5px;"
                    >{{ __('Ver JSON') }}</button>
                    <button
                        id="cerrar"
                        class="swal2-cancel swal2-styled"
                        style="margin:5px;"
                    >{{ __('Cerrar') }}</button>
                    <button
                        id="borrar"
                        class="swal2-deny swal2-styled"
                        style="margin:5px;"
                    >{{ __('Borrar') }}</button>
                `,
                showConfirmButton: false,
                didOpen: () => {
                    const popup = Swal.getPopup();
                    const verJsonBtn = popup.querySelector('#verJson');
                    const cerrarBtn = popup.querySelector('#cerrar');
                    const borrarBtn = popup.querySelector('#borrar');

                    verJsonBtn.addEventListener('click', () => {
                        Swal.fire({
                            title: "{{ __('Order JSON') }}",
                            html: `<pre style="text-align:left;">${JSON.stringify(orderJson, null, 2)}</pre>`,
                            width: '600px'
                        });
                    });
                    cerrarBtn.addEventListener('click', () => {
                        Swal.close();
                    });
                    borrarBtn.addEventListener('click', () => {
                        Swal.fire('{{ __('Borrar clicked') }}');
                    });
                }
            });
        }

        /* DRAG & DROP LÓGICA */
        let draggedCard = null;

        function dragStart(event) {
            draggedCard = event.target;
            event.target.classList.add('dragging');
        }

        function dragEnd(event) {
            event.target.classList.remove('dragging');
        }

        function dragOver(event) {
            event.preventDefault();
        }

        async function drop(event) {
            const targetColumn = event.target.closest('.column');
            if (!draggedCard || !targetColumn) return;

            const orderId = draggedCard.getAttribute('data-id');
            const newStatusKey = Object.entries(columns).find(([key, col]) => col.id === targetColumn.id)[0];

            try {
                const response = await fetch(`/api/production-orders/${orderId}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: parseInt(newStatusKey) })
                });
                if (response.ok) {
                    await loadKanbanData(1); // Recargamos desde la página 1
                } else {
                    throw new Error("{{ __('Error updating order status.') }}");
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "{{ __('Failed to update order status.') }}",
                });
            }
            draggedCard = null;
        }

        // Funciones para botones en el encabezado de columna (ejemplo)
        function addCard(columnId) {
            Swal.fire('{{ __('Add Card for column:') }} ' + columnId);
        }
        function deleteColumn(columnId) {
            Swal.fire('{{ __('Delete Column:') }} ' + columnId);
        }

        // Cargar la primera página al iniciar
        loadKanbanData();
    </script>
@endpush
