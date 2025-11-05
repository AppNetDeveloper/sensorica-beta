@extends('layouts.admin')

@section('title', __('Order Organizer') . ' - ' . $customer->name)

@section('page-title', __('Order Organizer'))

@section('breadcrumb')
    <div class="mb-4">
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Order Organizer') }}</li>
    </ul>
    </div>
@endsection

@section('content')
<div class="card border-0 shadow-lg">
    <div class="card-header bg-gradient-primary text-white border-0 py-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="card-title mb-1">
                    <i class="fas fa-tasks me-2"></i>{{ __('Processes') }}
                </h4>
                <p class="card-text mb-0 opacity-75">
                    {{ $customer->name }} - {{ __('Organize your production processes') }}
                </p>
            </div>

            @can('kanban-filter-toggle')
                <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 px-3 py-2 rounded-pill">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               id="kanbanFilterToggle"
                               {{ $filterEnabled ? 'checked' : '' }}
                               style="width: 3rem; height: 1.5rem; cursor: pointer;">
                        <label class="form-check-label ms-2" for="kanbanFilterToggle" style="cursor: pointer; color: #000000 !important; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                            <i class="fas fa-filter me-1"></i>
                            <span id="filterStatusText" class="fw-semibold" style="color: #000000 !important; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                {{ $filterEnabled ? __('Filtro activado') : __('Filtro desactivado') }}
                            </span>
                        </label>
                    </div>
                    <small id="filterHelpText" style="color: #000000 !important; text-shadow: 0 1px 2px rgba(0,0,0,0.1); font-weight: 500;">
                        {{ $filterEnabled
                            ? __('Órdenes no listas ocultas en Kanban')
                            : __('Todas las órdenes visibles en Kanban')
                        }}
                    </small>
                </div>
            @endcan
        </div>
    </div>
    <div class="card-body p-4">
        @if($groupedProcesses->count() > 0)
            <div class="row g-4">
                @foreach($groupedProcesses as $processData)
                    @php
                        $process = $processData['process'];
                        $lines = $processData['lines'];
                    @endphp
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card h-100 border-0 shadow-md hover-lift process-card">
                            <div class="card-body d-flex flex-column p-4">
                                <!-- Icono y título del proceso -->
                                <div class="text-center mb-3">
                                    <div class="process-icon mb-3">
                                        <i class="fas fa-industry fa-2x"></i>
                                    </div>
                                    <h5 class="card-title mb-2 fw-semibold">{{ $process->description ?: 'Sin descripción' }}</h5>
                                </div>

                                <!-- Información de líneas -->
                                <div class="text-center mb-3">
                                    <span class="badge bg-light text-dark px-3 py-2">
                                        <i class="fas fa-cogs me-1"></i>
                                        {{ $lines->count() }} {{ trans_choice('maquina|maquinas', $lines->count()) }}
                                    </span>
                                </div>

                                @php
                                    $bgColor = $process->color ?? '#0d6efd';
                                    // Calcular si el color es claro u oscuro para ajustar el texto
                                    $hex = ltrim($bgColor, '#');
                                    $r = hexdec(substr($hex, 0, 2));
                                    $g = hexdec(substr($hex, 2, 2));
                                    $b = hexdec(substr($hex, 4, 2));
                                    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                    $textColor = $brightness > 155 ? '#000000' : '#ffffff';
                                @endphp

                                <!-- Botón de acción -->
                                <div class="mt-auto">
                                    <a href="{{ route('customers.order-kanban', ['customer' => $customer->id, 'process' => $process->id]) }}"
                                       class="btn w-100 py-3 process-button"
                                       style="background-color: {{ $bgColor }}; border-color: {{ $bgColor }}; color: {{ $textColor }};">
                                        <i class="ti ti-layout-kanban me-2"></i>
                                        <span class="fw-semibold">{{ __('Organize Orders') }}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="ti ti-info-circle me-3 fa-2x"></i>
                    <div>
                        <h5 class="alert-heading mb-1">{{ __('No production lines found') }}</h5>
                        <p class="mb-0">{{ __('No production lines found for this customer.') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('css')
<style>
/* Estilos mejorados para las tarjetas de procesos */
.process-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 12px;
    overflow: hidden;
}

.process-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

.process-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.process-button {
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.process-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.process-button:hover::before {
    left: 100%;
}

.process-button:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    filter: brightness(1.1);
}

/* Estilos para el toggle switch del filtro mejorados */
#kanbanFilterToggle {
    cursor: pointer;
    background-color: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.3);
}

#kanbanFilterToggle:checked {
    background-color: #28a745;
    border-color: #28a745;
    box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
}

#kanbanFilterToggle:not(:checked) {
    background-color: #6c757d;
    border-color: #6c757d;
    box-shadow: 0 0 10px rgba(108, 117, 125, 0.3);
}

#kanbanFilterToggle:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.form-check-label {
    user-select: none;
}

#filterStatusText {
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    color: #000000 !important;
}

.form-check-label {
    color: #000000 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

#filterHelpText {
    font-size: 0.875rem;
    color: #000000 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    font-weight: 500;
}

/* Asegurar que los elementos del filtro siempre sean negros */
.bg-white.bg-opacity-10 .form-check-label,
.bg-white.bg-opacity-10 #filterStatusText,
.bg-white.bg-opacity-10 #filterHelpText {
    color: #000000 !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Animación de entrada para las tarjetas */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.process-card {
    animation: fadeInUp 0.5s ease-out;
}

.process-card:nth-child(1) { animation-delay: 0.1s; }
.process-card:nth-child(2) { animation-delay: 0.2s; }
.process-card:nth-child(3) { animation-delay: 0.3s; }
.process-card:nth-child(4) { animation-delay: 0.4s; }
.process-card:nth-child(5) { animation-delay: 0.5s; }
.process-card:nth-child(6) { animation-delay: 0.6s; }

/* Efectos de sombra adicionales */
.process-card {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.process-card:hover {
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

/* Sombra para el contenedor principal */
.card.border-0.shadow-lg {
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

/* Sombra para botones de proceso */
.process-button {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.process-button:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

/* Sombra para iconos de proceso */
.process-icon {
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

/* Sombra para badges */
.badge {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Efecto de sombra para el header */
.card-header {
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

/* Estilos para el tablero Kanban */
.kanban-board {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 1rem 0;
    min-height: 70vh;
}

.kanban-column {
    flex: 0 0 300px;
    background: #f8f9fa;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    max-height: 80vh;
}

.kanban-column-header {
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.2);
}

.kanban-column-cards {
    padding: 1rem;
    overflow-y: auto;
    flex-grow: 1;
    min-height: 100px;
    transition: background-color 0.2s;
}

/* Estilos para las tarjetas */
.kanban-card {
    background: white;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: grab;
    transition: transform 0.2s, box-shadow 0.2s;
}

.kanban-card:active {
    cursor: grabbing;
}

.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Efectos de arrastre */
.kanban-card.dragging {
    opacity: 0.5;
    transform: rotate(3deg);
}

.kanban-column.drag-over .kanban-column-cards {
    background-color: rgba(0,0,0,0.05);
    border: 2px dashed #0d6efd;
    border-radius: 4px;
}

/* Scrollbar personalizada */
.kanban-column-cards::-webkit-scrollbar {
    width: 6px;
}

.kanban-column-cards::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.kanban-column-cards::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

.kanban-column-cards::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive */
@media (max-width: 768px) {
    .kanban-column {
        flex: 0 0 280px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle switch para el filtro de Kanban
    const kanbanFilterToggle = document.getElementById('kanbanFilterToggle');
    
    if (kanbanFilterToggle) {
        kanbanFilterToggle.addEventListener('change', async function() {
            const isChecked = this.checked;
            const statusText = document.getElementById('filterStatusText');
            const helpText = document.getElementById('filterHelpText');
            
            // Deshabilitar el switch durante la petición
            this.disabled = true;
            
            try {
                const response = await fetch('{{ route("customers.kanban-filter-toggle", $customer) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar textos según el nuevo estado manteniendo estilos
                    if (data.value) {
                        statusText.textContent = '{{ __("Filtro activado") }}';
                        helpText.textContent = '{{ __("Órdenes no listas ocultas en Kanban") }}';
                    } else {
                        statusText.textContent = '{{ __("Filtro desactivado") }}';
                        helpText.textContent = '{{ __("Todas las órdenes visibles en Kanban") }}';
                    }

                    // Forzar estilos negros después de actualizar
                    statusText.style.color = '#000000 !important';
                    statusText.style.textShadow = '0 1px 2px rgba(0,0,0,0.1)';
                    helpText.style.color = '#000000 !important';
                    helpText.style.textShadow = '0 1px 2px rgba(0,0,0,0.1)';
                    statusText.parentElement.style.color = '#000000 !important';
                    statusText.parentElement.style.textShadow = '0 1px 2px rgba(0,0,0,0.1)';
                    
                    // Mostrar notificación de éxito
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Configuración actualizada") }}',
                            text: data.message,
                            timer: 3000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        alert(data.message);
                    }
                } else {
                    // Error en la respuesta
                    this.checked = !isChecked; // Revertir el estado
                    alert(data.message || '{{ __("Error al cambiar la configuración") }}');
                }
            } catch (error) {
                console.error('Error toggling filter:', error);
                this.checked = !isChecked; // Revertir el estado
                alert('{{ __("Error de conexión al servidor") }}');
            } finally {
                // Rehabilitar el switch
                this.disabled = false;
            }
        });
    }
    

    // Variables globales
    let draggedItem = null;
    const kanbanBoard = document.querySelector('.kanban-board');
    const kanbanColumns = document.querySelectorAll('.kanban-column');
    const backButton = document.getElementById('backToProcesses');
    const saveButton = document.getElementById('saveKanban');

    // Inicializar el tablero
    function initKanban() {
        // Hacer las columnas droppables
        kanbanColumns.forEach(column => {
            const cardsContainer = column.querySelector('.kanban-column-cards');
            
            // Eventos para las columnas
            cardsContainer.addEventListener('dragover', handleDragOver);
            cardsContainer.addEventListener('dragenter', handleDragEnter);
            cardsContainer.addEventListener('dragleave', handleDragLeave);
            cardsContainer.addEventListener('drop', handleDrop);
            
            // Actualizar contador
            updateColumnCounter(column);
        });
        
        // Agregar tarjetas de ejemplo (esto se puede eliminar cuando se conecte con datos reales)
        addExampleCards();
    }
    
    // Función para agregar tarjetas de ejemplo
    function addExampleCards() {
        const pendingCards = document.getElementById('pending-cards');
        if (pendingCards && pendingCards.children.length === 0) {
            const orders = [
                { id: 1, name: 'Pedido #001', client: 'Cliente A' },
                { id: 2, name: 'Pedido #002', client: 'Cliente B' },
                { id: 3, name: 'Pedido #003', client: 'Cliente C' }
            ];
            
            orders.forEach(order => {
                const card = createCardElement(order);
                pendingCards.appendChild(card);
            });
            
            // Actualizar contadores
            kanbanColumns.forEach(updateColumnCounter);
        }
    }
    
    // Crear elemento de tarjeta
    function createCardElement(order) {
        const card = document.createElement('div');
        card.className = 'kanban-card';
        card.draggable = true;
        card.dataset.orderId = order.id;
        
        card.innerHTML = `
            <strong>${order.name}</strong><br>
            <small class="text-muted">${order.client}</small>
        `;
        
        // Eventos de arrastre
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
        
        return card;
    }
    
    // Manejadores de eventos de arrastre
    function handleDragStart(e) {
        draggedItem = this;
        this.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.innerHTML);
    }
    
    function handleDragEnd() {
        this.classList.remove('dragging');
        draggedItem = null;
    }
    
    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }
    
    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }
    
    function handleDragLeave() {
        this.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        
        if (draggedItem) {
            this.appendChild(draggedItem);
            updateColumnCounter(this.closest('.kanban-column'));
        }
    }
    
    // Actualizar contador de tarjetas en la columna
    function updateColumnCounter(column) {
        const counter = column.querySelector('.kanban-column-header .badge');
        if (counter) {
            const cardsCount = column.querySelectorAll('.kanban-card').length;
            counter.textContent = cardsCount;
        }
    }
    
    // Función para mostrar/ocultar el tablero Kanban
    window.selectLines = function(lineIds, lineNames) {
        document.getElementById('kanbanBoardContainer').classList.remove('d-none');
        document.getElementById('processesList').classList.add('d-none');
        backButton.classList.remove('d-none');
        
        // Aquí podrías cargar los pedidos reales usando lineIds y lineNames
        // Por ahora usamos los datos de ejemplo
        initKanban();
    };
    
    // Botón para volver a la lista de procesos
    if (backButton) {
        backButton.addEventListener('click', function() {
            document.getElementById('kanbanBoardContainer').classList.add('d-none');
            document.getElementById('processesList').classList.remove('d-none');
            this.classList.add('d-none');
        });
    }
    
    // Botón para guardar los cambios
    if (saveButton) {
        saveButton.addEventListener('click', function() {
            const kanbanData = {};
            
            // Recopilar el estado actual del tablero
            document.querySelectorAll('.kanban-column').forEach(column => {
                const columnId = column.dataset.columnId;
                const cards = [];
                
                column.querySelectorAll('.kanban-card').forEach((card, index) => {
                    cards.push({
                        id: card.dataset.orderId,
                        position: index + 1
                    });
                });
                
                kanbanData[columnId] = cards;
            });
            
            // Aquí podrías enviar los datos al servidor
            console.log('Datos del Kanban a guardar:', kanbanData);
            
            // Mostrar notificación
            alert('¡Cambios guardados correctamente!');
        });
    }
});
</script>
@endpush
