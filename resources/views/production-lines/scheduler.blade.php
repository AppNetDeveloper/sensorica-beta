{{-- Planificador de disponibilidad de líneas de producción --}}
<div class="scheduler-container">
    <div class="mb-4">
        <h5>Planificación de disponibilidad para: <strong>{{ $productionLine->name }}</strong></h5>
        <p class="text-muted">Configure los turnos disponibles para cada día de la semana</p>
    </div>

    <form id="schedulerForm" data-production-line-id="{{ $productionLine->id }}">
        @csrf
        <div class="scheduler-grid">
            <div class="row mb-3 fw-bold">
                <div class="col-3">Día</div>
                <div class="col-9">Turnos disponibles</div>
            </div>

            @php
                $days = [
                    1 => 'Lunes',
                    2 => 'Martes',
                    3 => 'Miércoles',
                    4 => 'Jueves',
                    5 => 'Viernes',
                    6 => 'Sábado',
                    7 => 'Domingo'
                ];
            @endphp

            @foreach($days as $dayNum => $dayName)
                <div class="row mb-3 align-items-center day-row" data-day="{{ $dayNum }}">
                    <div class="col-3">
                        <div class="form-check">
                            <input class="form-check-input day-active" type="checkbox" id="day{{ $dayNum }}" 
                                   {{ $availabilityByDay[$dayNum]->count() > 0 ? 'checked' : '' }}>
                            <label class="form-check-label" for="day{{ $dayNum }}">
                                {{ $dayName }}
                            </label>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="shifts-container {{ $availabilityByDay[$dayNum]->count() > 0 ? '' : 'disabled' }}">
                            @if($shifts->count() > 0)
                                <div class="d-flex flex-wrap gap-3">
                                    @foreach($shifts as $shift)
                                        <div class="form-check shift-checkbox-wrapper">
                                            <input class="form-check-input shift-checkbox" 
                                                type="checkbox" 
                                                id="shift{{ $dayNum }}_{{ $shift->id }}" 
                                                name="shifts[{{ $dayNum }}][]" 
                                                value="{{ $shift->id }}"
                                                {{ $availabilityByDay[$dayNum]->contains('shift_list_id', $shift->id) ? 'checked' : '' }}
                                                >
                                            <label class="form-check-label" for="shift{{ $dayNum }}_{{ $shift->id }}">
                                                {{ $shift->start }} - {{ $shift->end }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-muted">No hay turnos definidos</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-secondary me-2" id="cancelScheduler">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="saveScheduler">Guardar</button>
        </div>
    </form>
</div>

<style>
    .scheduler-container {
        max-width: 100%;
        padding: 0.5rem;
    }
    .shifts-container.disabled {
        opacity: 0.6;
        pointer-events: none;
    }
    .day-row {
        padding: 0.8rem 0;
        border-bottom: 1px solid #eee;
    }
    .day-row:last-child {
        border-bottom: none;
    }
    .form-check {
        margin-bottom: 0.5rem;
        background-color: #f8f9fa;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        border: 1px solid #e9ecef;
    }
    .form-check:hover {
        background-color: #e9ecef;
    }
    .scheduler-grid {
        margin-bottom: 1rem;
    }
    .scheduler-swal-popup {
        max-width: 800px;
        width: 100% !important;
    }
    
    /* Estilos para checkboxes personalizados */
    .custom-checkbox {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        color: #495057;
        text-align: left;
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
        width: 100%;
        display: flex;
        align-items: center;
    }
    
    .custom-checkbox:hover {
        background-color: #e9ecef;
    }
    
    .custom-checkbox.active {
        background-color: #cfe2ff;
        border-color: #9ec5fe;
        color: #084298;
    }
    
    .check-icon {
        visibility: hidden;
        color: #084298;
    }
    
    .custom-checkbox.active .check-icon {
        visibility: visible;
    }
    
    .custom-checkbox-container {
        min-width: 150px;
    }
</style>

<script>
    console.log('SCRIPT INICIADO');
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado - Inicializando planificador');
        
        // Añadir un evento de clic directo a cada checkbox de turno
        document.querySelectorAll('.shift-checkbox').forEach(checkbox => {
            console.log('Inicializando checkbox de turno:', checkbox.id);
            
            // Añadir evento de clic directamente al checkbox
            checkbox.addEventListener('click', function(e) {
                console.log('Clic en checkbox de turno:', this.id, 'Estado actual:', this.checked);
                // El evento se maneja automáticamente por el navegador, no necesitamos hacer nada más
            });
            
            // También añadir evento de clic al contenedor para mejorar UX
            const wrapper = checkbox.closest('.shift-checkbox-wrapper');
            if (wrapper) {
                wrapper.addEventListener('click', function(e) {
                    // Si el clic fue directamente en el checkbox, no hacemos nada
                    if (e.target === checkbox) return;
                    
                    // Si el clic fue en el wrapper o la etiqueta, cambiamos el estado del checkbox
                    console.log('Clic en wrapper de checkbox:', checkbox.id);
                    checkbox.checked = !checkbox.checked;
                    
                    // Disparar el evento change manualmente
                    const event = new Event('change', { bubbles: true });
                    checkbox.dispatchEvent(event);
                });
            }
        });
        
        // Manejar la activación/desactivación de días
        document.querySelectorAll('.day-active').forEach(checkbox => {
            console.log('Inicializando checkbox de día:', checkbox.id);
            
            // Inicializar el estado de los turnos según el estado inicial del día
            const dayRow = checkbox.closest('.day-row');
            const shiftsContainer = dayRow.querySelector('.shifts-container');
            const shiftCheckboxes = dayRow.querySelectorAll('.shift-checkbox');
            
            if (!checkbox.checked) {
                console.log('Día inicialmente desactivado:', dayRow.dataset.day);
                shiftsContainer.classList.add('disabled');
                shiftCheckboxes.forEach(cb => {
                    cb.disabled = true;
                });
            } else {
                console.log('Día inicialmente activado:', dayRow.dataset.day);
                shiftsContainer.classList.remove('disabled');
                shiftCheckboxes.forEach(cb => {
                    cb.disabled = false;
                });
            }
            
            // Manejar el evento de cambio
            checkbox.addEventListener('change', function() {
                console.log('Cambio en checkbox de día:', this.id, 'Nuevo estado:', this.checked);
                
                const dayRow = this.closest('.day-row');
                const shiftsContainer = dayRow.querySelector('.shifts-container');
                const shiftCheckboxes = dayRow.querySelectorAll('.shift-checkbox');
                
                if (this.checked) {
                    console.log('Activando turnos para el día:', dayRow.dataset.day);
                    shiftsContainer.classList.remove('disabled');
                    shiftCheckboxes.forEach(cb => cb.disabled = false);
                } else {
                    console.log('Desactivando turnos para el día:', dayRow.dataset.day);
                    shiftsContainer.classList.add('disabled');
                    shiftCheckboxes.forEach(cb => {
                        cb.checked = false;
                        cb.disabled = true;
                    });
                }
            });
        });

        // Manejar el envío del formulario
        document.getElementById('schedulerForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productionLineId = this.dataset.productionLineId;
            const formData = new FormData(this);
            
            // Convertir FormData a objeto para enviar como JSON
            const data = {
                production_line_id: productionLineId,
                days: {}
            };
            
            document.querySelectorAll('.day-row').forEach(row => {
                const dayNum = row.dataset.day;
                const isDayActive = row.querySelector('.day-active').checked;
                
                if (isDayActive) {
                    data.days[dayNum] = [];
                    row.querySelectorAll('.custom-checkbox.active').forEach(btn => {
                        const shiftId = btn.dataset.shiftId;
                        data.days[dayNum].push(parseInt(shiftId));
                    });
                }
            });
            
            // Mostrar indicador de carga
            const saveBtn = document.getElementById('saveScheduler');
            const originalBtnText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...';
            
            // Obtener token CSRF de manera segura
            let csrfToken = '';
            const metaTag = document.querySelector('meta[name="csrf-token"]');
            if (metaTag) {
                csrfToken = metaTag.getAttribute('content');
            }
            
            // Enviar datos al servidor
            fetch('/api/production-lines/availability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // Restaurar el botón a su estado original
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
                
                if (data.success) {
                    Swal.close();
                    Swal.fire({
                        title: 'Éxito',
                        text: 'Planificación guardada correctamente',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        // Actualizar la interfaz del Kanban si es necesario
                        if (typeof updateColumnStats === 'function') {
                            updateColumnStats();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'Ha ocurrido un error al guardar la planificación',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                // Restaurar el botón a su estado original en caso de error
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalBtnText;
                
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Ha ocurrido un error al guardar la planificación: ' + error.message,
                    icon: 'error'
                });
            });
        });

        // Botón cancelar
        document.getElementById('cancelScheduler').addEventListener('click', function() {
            Swal.close();
        });
    });
console.log('FIN DEL SCRIPT');
</script>
