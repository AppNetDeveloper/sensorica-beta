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
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Processes') }}</h5>
            
            @can('kanban-filter-toggle')
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" 
                               id="kanbanFilterToggle" 
                               {{ $filterEnabled ? 'checked' : '' }}
                               style="width: 3rem; height: 1.5rem; cursor: pointer;">
                        <label class="form-check-label ms-2" for="kanbanFilterToggle" style="cursor: pointer;">
                            <i class="fas fa-filter me-1"></i>
                            <span id="filterStatusText">
                                {{ $filterEnabled ? __('Filtro activado') : __('Filtro desactivado') }}
                            </span>
                        </label>
                    </div>
                    <small class="text-muted" id="filterHelpText">
                        {{ $filterEnabled 
                            ? __('Órdenes no listas ocultas en Kanban') 
                            : __('Todas las órdenes visibles en Kanban') 
                        }}
                    </small>
                </div>
            @endcan
        </div>
    </div>
    <div class="card-body">
        @if($groupedProcesses->count() > 0)
            <div class="row">
                @foreach($groupedProcesses as $processData)
                    @php
                        $process = $processData['process'];
                        $lines = $processData['lines'];
                    @endphp
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-center mb-2">{{ $process->description ?: 'Sin descripción' }}</h5>

                                <div class="text-center text-muted small mb-2">
                                    {{ $lines->count() }} {{ trans_choice('maquina|maquinas', $lines->count()) }}
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
                                <a href="{{ route('customers.order-kanban', ['customer' => $customer->id, 'process' => $process->id]) }}" 
                                   class="btn w-100 mt-auto"
                                   style="background-color: {{ $bgColor }}; border-color: {{ $bgColor }}; color: {{ $textColor }};">
                                    <i class="ti ti-layout-kanban me-2"></i> {{ __('Organize Orders') }}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info mb-0">
                <i class="ti ti-info-circle me-2"></i> {{ __('No production lines found for this customer.') }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('css')
<style>
/* Estilos para las tarjetas de procesos */
.card-body a.btn:hover {
    filter: brightness(0.9);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    transition: all 0.2s ease;
}

.card-body a.btn {
    transition: all 0.2s ease;
    font-weight: 500;
}

/* Estilos para el toggle switch del filtro */
#kanbanFilterToggle {
    cursor: pointer;
}

#kanbanFilterToggle:checked {
    background-color: #28a745;
    border-color: #28a745;
}

#kanbanFilterToggle:not(:checked) {
    background-color: #6c757d;
    border-color: #6c757d;
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
}

#filterHelpText {
    font-size: 0.875rem;
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
                    // Actualizar textos según el nuevo estado
                    if (data.value) {
                        statusText.textContent = '{{ __("Filtro activado") }}';
                        helpText.textContent = '{{ __("Órdenes no listas ocultas en Kanban") }}';
                    } else {
                        statusText.textContent = '{{ __("Filtro desactivado") }}';
                        helpText.textContent = '{{ __("Todas las órdenes visibles en Kanban") }}';
                    }
                    
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
