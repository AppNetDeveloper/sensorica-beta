@extends('layouts.admin')

@section('title', 'Production Order Kanban Control')

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
    <div class="kanban-board"></div>
@endsection

@push('style')
    <style>
        /* Contenedor principal del tablero Kanban */
        .kanban-board {
            display: flex;
            gap: 20px;
            padding: 20px;
            overflow-x: auto;
            background-color: #1c1e21; /* Fondo oscuro similar al layout */
            border-radius: 8px;
        }

        /* Columnas del Kanban */
        .column {
            flex: 1;
            background-color: #2a2d31; /* Fondo oscuro que combina con el tema */
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-width: 250px;
        }

        .column h3 {
            background-color: #3a3d42; /* Fondo más oscuro para el encabezado */
            color: #ffffff; /* Texto blanco */
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Tarjetas */
        .card {
            background-color: #ffffff; /* Fondo blanco para contrastar */
            color: #333333; /* Texto oscuro */
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            font-size: 0.9rem;
            font-weight: 500;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-3px); /* Efecto de elevación */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            background-color: #f9f9f9; /* Fondo ligeramente más claro */
        }

        .card-menu {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.2rem;
            color: #6c757d;
            cursor: pointer;
        }

        .card-menu:hover {
            color: #343a40; /* Más oscuro al pasar el ratón */
        }

        /* Efecto al arrastrar */
        .card.dragging {
            opacity: 0.6;
            transform: rotate(2deg);
        }

        /* Barra de scroll oculta para el tablero */
        .kanban-board::-webkit-scrollbar {
            height: 6px;
        }

        .kanban-board::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 4px;
        }

        .kanban-board::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Ajustes de texto */
        .card div {
            margin-bottom: 8px;
        }

        /* Animaciones */
        .column {
            animation: fadeIn 0.3s ease-in-out;
        }

        .card {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const columns = {
            0: { id: 'pending', name: 'Pending' },
            1: { id: 'started', name: 'Started' },
            2: { id: 'completed', name: 'Completed' },
            3: { id: 'paused', name: 'Paused' },
            4: { id: 'cancelled', name: 'Cancelled' },
            5: { id: 'issues', name: 'Issues' }
        };

        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');

        if (!token) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Token not provided in URL.',
            });
            throw new Error('Token required to load data.');
        }

        const existingCards = new Map();

        async function loadKanbanData() {
            try {
                const response = await fetch(`/api/production-orders?token=${token}`);
                if (!response.ok) throw new Error('Error fetching API data.');

                const data = await response.json();
                await updateKanbanBoard(data.data);
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while loading Kanban data.',
                });
            }
        }

        async function updateKanbanBoard(orders) {
            const kanbanBoard = document.querySelector('.kanban-board');

            Object.entries(columns).forEach(([status, column]) => {
                let columnElement = document.getElementById(column.id);
                if (!columnElement) {
                    columnElement = document.createElement('div');
                    columnElement.classList.add('column');
                    columnElement.id = column.id;
                    columnElement.innerHTML = `<h3>${column.name}</h3>`;
                    kanbanBoard.appendChild(columnElement);
                }
            });

            const updatedOrderIds = new Set();

            for (const order of orders.sort((a, b) => a.orden - b.orden)) {
                const columnId = columns[order.status].id;
                const columnElement = document.getElementById(columnId);

                let cardElement = existingCards.get(order.id);

                if (!cardElement) {
                    cardElement = document.createElement('div');
                    cardElement.classList.add('card');
                    cardElement.draggable = true;
                    cardElement.setAttribute('data-id', order.id);
                    cardElement.setAttribute('data-order-id', order.order_id);
                    columnElement.appendChild(cardElement);

                    cardElement.ondragstart = dragStart;
                    cardElement.ondragend = dragEnd;

                    existingCards.set(order.id, cardElement);
                } else {
                    if (cardElement.parentElement.id !== columnId) {
                        columnElement.appendChild(cardElement);
                    }
                }

                cardElement.innerHTML = `
                    <span class="card-menu" onclick="showCardMenu(${order.id})">⋮</span>
                    <div>Order ID: ${order.order_id}</div>
                    <div>Box: ${order.box}</div>
                    <div>Units/Box: ${order.units_box}</div>
                    <div>Order: ${order.orden}</div>
                `;

                updatedOrderIds.add(order.id);
            }

            existingCards.forEach((cardElement, orderId) => {
                if (!updatedOrderIds.has(orderId)) {
                    cardElement.remove();
                    existingCards.delete(orderId);
                }
            });

            document.querySelectorAll('.column').forEach(column => {
                column.ondragover = dragOver;
                column.ondrop = drop;
            });
        }

        async function showCardMenu(orderId) {
            try {
                const response = await fetch(`/api/production-orders/${orderId}`);
                const order = await response.json();

                Swal.fire({
                    title: 'Order Details',
                    html: `
                        <p><strong>Order ID:</strong> ${order.order_id}</p>
                        <p><strong>Box:</strong> ${order.box}</p>
                        <p><strong>Units/Box:</strong> ${order.units_box}</p>
                        <p><strong>Order:</strong> ${order.orden}</p>
                        <p><strong>Status:</strong> ${columns[order.status].name}</p>
                    `,
                    icon: 'info',
                    showCancelButton: false,
                    confirmButtonText: 'Close'
                });
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not load order details.',
                });
            }
        }

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
                    await loadKanbanData();
                } else {
                    throw new Error('Error updating order status.');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update order status.',
                });
            }

            draggedCard = null;
        }

        loadKanbanData();
    </script>
@endpush
