@extends('layouts.admin')
@section('title', __('Editar Cliente'))

@push('styles')
<style>
    .mapping-row {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
    }
    .mapping-actions {
        display: flex;
        align-items: center;
        height: 100%;
    }
    .transformations-container {
        margin-top: 10px;
    }
    .add-mapping-btn {
        margin-bottom: 20px;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el índice para nuevos mapeos
        let mappingIndex = {{ $customer->fieldMappings->count() }};
        const mappingsContainer = document.getElementById('mappings-container');
        const addMappingBtn = document.getElementById('add-mapping');
        
        // Función para generar un token seguro
        window.generateToken = function() {
            const tokenField = document.getElementById('token');
            const randomString = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            tokenField.value = randomString;
            return false;
        };
        
        // Función para actualizar los índices de los mapeos
        function updateMappingIndexes() {
            const rows = document.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                // Actualizar el atributo data-index
                row.setAttribute('data-index', index);
                
                // Actualizar los nombres de los campos del formulario
                const inputs = row.querySelectorAll('[name^="field_mappings["]');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/field_mappings\[\d+\]/g, `field_mappings[${index}]`);
                    input.setAttribute('name', newName);
                });
                
                // Actualizar los IDs de los checkboxes de transformaciones
                const checkboxes = row.querySelectorAll('input[type="checkbox"][id^="transformation_"]');
                checkboxes.forEach(checkbox => {
                    const id = checkbox.getAttribute('id');
                    const newId = id.replace(/transformation_\d+_/, `transformation_${index}_`);
                    checkbox.setAttribute('id', newId);
                    
                    // Actualizar el for del label asociado
                    const label = document.querySelector(`label[for="${id}"]`);
                    if (label) {
                        label.setAttribute('for', newId);
                    }
                });
            });
            
            // Actualizar el contador para nuevos mapeos
            mappingIndex = rows.length;
        }
        
        // Función para actualizar los botones de mover
        function updateMoveButtons() {
            const rows = mappingsContainer.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                const upBtn = row.querySelector('.move-up');
                const downBtn = row.querySelector('.move-down');
                
                if (upBtn) {
                    upBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
                }
                
                if (downBtn) {
                    downBtn.style.visibility = index === rows.length - 1 ? 'hidden' : 'visible';
                }
            });
            
            // Actualizar los índices después de mover
            updateMappingIndexes();
        }
        
        // Función para mostrar notificación
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `position-fixed bottom-0 end-0 m-3 p-3 bg-${type} text-white rounded shadow`;
            toast.style.zIndex = '1100';
            toast.style.transition = 'opacity 0.3s';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Forzar reflow para que se aplique la transición
            toast.offsetHeight;
            
            // Ocultar la notificación después de 3 segundos
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
        
        // Añadir nuevo mapeo
        addMappingBtn.addEventListener('click', function() {
            // Deshabilitar el botón para evitar múltiples clics
            const originalText = addMappingBtn.innerHTML;
            addMappingBtn.disabled = true;
            addMappingBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...';
            
            fetch(`{{ route('customers.field-mapping-row', $customer->id) }}?index=${mappingIndex}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Error al cargar la fila de mapeo');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    const div = document.createElement('div');
                    div.className = 'mapping-row mb-3 p-3 border rounded';
                    div.setAttribute('data-index', mappingIndex);
                    div.innerHTML = data.html;
                    
                    // Insertar antes del botón de añadir si existe, o al final
                    const addButtonContainer = document.querySelector('#add-mapping-container');
                    if (addButtonContainer && addButtonContainer.parentNode) {
                        addButtonContainer.parentNode.insertBefore(div, addButtonContainer);
                    } else {
                        mappingsContainer.appendChild(div);
                    }
                    
                    // Actualizar índices y botones
                    updateMappingIndexes();
                    updateMoveButtons();
                    
                    // Desplazar la vista al nuevo elemento con un pequeño retraso
                    setTimeout(() => {
                        div.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        // Resaltar temporalmente el nuevo elemento
                        div.style.transition = 'box-shadow 0.5s';
                        div.style.boxShadow = '0 0 0 2px var(--bs-primary)';
                        setTimeout(() => {
                            div.style.boxShadow = 'none';
                        }, 1000);
                    }, 100);
                    
                    // Mostrar notificación de éxito
                    showToast('Nuevo mapeo agregado correctamente', 'success');
                } else {
                    throw new Error(data.message || 'Error al crear el mapeo');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error: ' + error.message, 'danger');
            })
            .finally(() => {
                // Restaurar el botón
                addMappingBtn.disabled = false;
                addMappingBtn.innerHTML = originalText;
            });
        });
        
        // Delegación de eventos para los botones de eliminar y mover
        mappingsContainer.addEventListener('click', function(e) {
            const row = e.target.closest('.mapping-row');
            if (!row) return;
            
            // Eliminar mapeo
            if (e.target.closest('.remove-mapping')) {
                if (confirm('¿Estás seguro de que quieres eliminar este mapeo?')) {
                    // Agregar clase para animación
                    row.style.transition = 'opacity 0.3s';
                    row.style.opacity = '0';
                    
                    // Esperar a que termine la animación antes de eliminar
                    setTimeout(() => {
                        row.remove();
                        
                        // Actualizar la interfaz
                        updateDeleteButtons();
                        updateMoveButtons();
                        
                        // Mostrar notificación
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 m-3 p-3 bg-success text-white rounded shadow';
                        toast.style.zIndex = '1100';
                        toast.textContent = 'Mapeo eliminado correctamente';
                        document.body.appendChild(toast);
                        
                        // Ocultar la notificación después de 3 segundos
                        setTimeout(() => {
                            toast.style.transition = 'opacity 0.5s';
                            toast.style.opacity = '0';
                            setTimeout(() => toast.remove(), 500);
                        }, 3000);
                    }, 300);
                }
                return;
            }
            
            // Mover arriba
            if (e.target.closest('.move-up')) {
                const prev = row.previousElementSibling;
                if (prev && prev.classList.contains('mapping-row')) {
                    row.parentNode.insertBefore(row, prev);
                    updateMoveButtons();
                }
                return;
            }
            
            // Mover abajo
            if (e.target.closest('.move-down')) {
                const next = row.nextElementSibling;
                if (next && next.classList.contains('mapping-row')) {
                    row.parentNode.insertBefore(next, row);
                    updateMoveButtons();
                }
                return;
            }
        });
        
        // Actualizar visibilidad de botones de movimiento y eliminar
        function updateDeleteButtons() {
            const rows = document.querySelectorAll('.mapping-row');
            const noMappingsMessage = document.querySelector('.no-mappings-message');
            const hasMappings = rows.length > 0;
            
            // Mostrar u ocultar mensaje de "no hay mapeos"
            if (noMappingsMessage) {
                noMappingsMessage.style.display = hasMappings ? 'none' : 'block';
            }
            
            // Mostrar u ocultar el contenedor de mapeos
            const mappingsContainer = document.getElementById('mappings-container');
            if (mappingsContainer) {
                const mappingRows = mappingsContainer.querySelectorAll('.mapping-row');
                mappingsContainer.style.display = mappingRows.length > 0 ? 'block' : 'none';
            }
            
            // Actualizar índices después de eliminar
            updateMappingIndexes();
        }
        
        // Actualizar visibilidad de botones de movimiento
        function updateMoveButtons() {
            const rows = document.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                const upBtn = row.querySelector('.move-up');
                const downBtn = row.querySelector('.move-down');
                
                if (upBtn) upBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
                if (downBtn) downBtn.style.visibility = index === rows.length - 1 ? 'hidden' : 'visible';
                
                // Actualizar los nombres de los campos con el nuevo índice
                row.querySelectorAll('[name^="field_mappings["]').forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/field_mappings\[\d+\]/, `field_mappings[${index}]`);
                    input.setAttribute('name', newName);
                });
            });
            
            // Actualizar índices después de mover
            updateMappingIndexes();
        }
        
        // Inicializar botones de movimiento
        updateMoveButtons();
        
        // ========== SECCIÓN PARA MAPEOS DE PROCESOS ==========
        
        // Inicializar el índice para nuevos mapeos de procesos
        let processMappingIndex = {{ $customer->processFieldMappings->count() }};
        const processMappingsContainer = document.getElementById('process-mappings-container');
        const addProcessMappingBtn = document.getElementById('add-process-mapping');
        
        // Función para actualizar los índices de los mapeos de procesos
        function updateProcessMappingIndexes() {
            const rows = processMappingsContainer.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                // Actualizar el atributo data-index
                row.setAttribute('data-index', index);
                
                // Actualizar los nombres de los campos del formulario
                const inputs = row.querySelectorAll('[name^="process_field_mappings["]');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/process_field_mappings\[\d+\]/g, `process_field_mappings[${index}]`);
                    input.setAttribute('name', newName);
                });
                
                // Actualizar los IDs de los checkboxes de transformaciones
                const checkboxes = row.querySelectorAll('input[type="checkbox"][id^="process_transformation_"]');
                checkboxes.forEach(checkbox => {
                    const id = checkbox.getAttribute('id');
                    const newId = id.replace(/process_transformation_\d+_/, `process_transformation_${index}_`);
                    checkbox.setAttribute('id', newId);
                    
                    // Actualizar el for del label asociado
                    const label = document.querySelector(`label[for="${id}"]`);
                    if (label) {
                        label.setAttribute('for', newId);
                    }
                });
            });
            
            // Actualizar el contador para nuevos mapeos
            processMappingIndex = rows.length;
        }
        
        // Función para actualizar los botones de mover de procesos
        function updateProcessMoveButtons() {
            const rows = processMappingsContainer.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                const upBtn = row.querySelector('.move-up');
                const downBtn = row.querySelector('.move-down');
                
                if (upBtn) {
                    upBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
                }
                
                if (downBtn) {
                    downBtn.style.visibility = index === rows.length - 1 ? 'hidden' : 'visible';
                }
            });
            
            // Actualizar los índices después de mover
            updateProcessMappingIndexes();
        }
        
        // Añadir nuevo mapeo de proceso
        if (addProcessMappingBtn) {
            addProcessMappingBtn.addEventListener('click', function() {
                // Deshabilitar el botón para evitar múltiples clics
                const originalText = addProcessMappingBtn.innerHTML;
                addProcessMappingBtn.disabled = true;
                addProcessMappingBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cargando...';

                fetch(`{{ route('customers.field-mapping-row', $customer->id) }}?type=process&index=${processMappingIndex}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            const div = document.createElement('div');
                            div.className = 'mapping-row mb-3 p-3 border rounded';
                            div.setAttribute('data-index', processMappingIndex);
                            div.innerHTML = data.html;
                            
                            // Remover el mensaje de "no hay mapeos" si existe
                            const noMappingsAlert = processMappingsContainer.querySelector('.alert-info');
                            if (noMappingsAlert) {
                                noMappingsAlert.remove();
                            }
                            
                            processMappingsContainer.appendChild(div);
                            processMappingIndex++;
                            
                            // Actualizar botones de movimiento
                            updateProcessMoveButtons();
                            
                            // Animación de entrada
                            setTimeout(() => {
                                div.style.transition = 'box-shadow 0.3s';
                                div.style.boxShadow = '0 0 0 2px var(--bs-success)';
                                setTimeout(() => {
                                    div.style.boxShadow = 'none';
                                }, 1000);
                            }, 100);
                            
                            // Mostrar notificación de éxito
                            showToast('Nuevo mapeo de proceso agregado correctamente', 'success');
                        } else {
                            throw new Error(data.message || 'Error al crear el mapeo de proceso');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error: ' + error.message, 'danger');
                    })
                    .finally(() => {
                        // Restaurar el botón
                        addProcessMappingBtn.disabled = false;
                        addProcessMappingBtn.innerHTML = originalText;
                    });
            });
        }
        
        // Delegación de eventos para los botones de eliminar y mover de procesos
        if (processMappingsContainer) {
            processMappingsContainer.addEventListener('click', function(e) {
                const row = e.target.closest('.mapping-row');
                if (!row) return;
                
                // Eliminar mapeo de proceso
                if (e.target.closest('.remove-mapping')) {
                    if (confirm('¿Estás seguro de que quieres eliminar este mapeo de proceso?')) {
                        // Agregar clase para animación
                        row.style.transition = 'opacity 0.3s';
                        row.style.opacity = '0';
                        
                        setTimeout(() => {
                            row.remove();
                            updateProcessMoveButtons();
                            
                            // Si no quedan mapeos, mostrar el mensaje
                            if (processMappingsContainer.querySelectorAll('.mapping-row').length === 0) {
                                const alertDiv = document.createElement('div');
                                alertDiv.className = 'alert alert-info';
                                alertDiv.textContent = 'No hay mapeos de procesos definidos. Haz clic en "Añadir Mapeo de Proceso" para crear uno nuevo.';
                                processMappingsContainer.appendChild(alertDiv);
                            }
                            
                            // Mostrar notificación
                            showToast('Mapeo de proceso eliminado correctamente', 'success');
                        }, 300);
                    }
                    return;
                }
                
                // Mover hacia arriba
                if (e.target.closest('.move-up')) {
                    const prevRow = row.previousElementSibling;
                    if (prevRow && prevRow.classList.contains('mapping-row')) {
                        row.parentNode.insertBefore(row, prevRow);
                        updateProcessMoveButtons();
                        showToast('Mapeo movido hacia arriba', 'success');
                    }
                    return;
                }
                
                // Mover hacia abajo
                if (e.target.closest('.move-down')) {
                    const nextRow = row.nextElementSibling;
                    if (nextRow && nextRow.classList.contains('mapping-row')) {
                        row.parentNode.insertBefore(nextRow, row);
                        updateProcessMoveButtons();
                        showToast('Mapeo movido hacia abajo', 'success');
                    }
                    return;
                }
            });
            
            // Actualizar índices después de mover
            updateProcessMappingIndexes();
        }
        
        // Inicializar botones de movimiento de procesos
        if (processMappingsContainer) {
            updateProcessMoveButtons();
        }
        
        // ========== SECCIÓN PARA MAPEOS DE ARTÍCULOS ==========
        
        // Inicializar el índice para nuevos mapeos de artículos
        let articleMappingIndex = {{ $customer->articleFieldMappings->count() }};
        const articleMappingsContainer = document.getElementById('article-mappings-container');
        const addArticleMappingBtn = document.getElementById('add-article-mapping');
        
        // Función para actualizar los índices de los mapeos de artículos
        function updateArticleMappingIndexes() {
            const rows = articleMappingsContainer.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                // Actualizar el atributo data-index
                row.setAttribute('data-index', index);
                
                // Actualizar los nombres de los campos del formulario
                const inputs = row.querySelectorAll('[name^="article_field_mappings["]');
                inputs.forEach(input => {
                    const name = input.getAttribute('name');
                    const newName = name.replace(/article_field_mappings\[\d+\]/g, `article_field_mappings[${index}]`);
                    input.setAttribute('name', newName);
                });
                
                // Actualizar los IDs de los checkboxes de transformaciones
                const checkboxes = row.querySelectorAll('input[type="checkbox"][id^="article_transformation_"]');
                checkboxes.forEach(checkbox => {
                    const id = checkbox.getAttribute('id');
                    const newId = id.replace(/article_transformation_\d+_/, `article_transformation_${index}_`);
                    checkbox.setAttribute('id', newId);
                    
                    // Actualizar el for del label asociado
                    const label = document.querySelector(`label[for="${id}"]`);
                    if (label) {
                        label.setAttribute('for', newId);
                    }
                });
            });
            
            // Actualizar el contador para nuevos mapeos
            articleMappingIndex = rows.length;
        }
        
        // Función para actualizar los botones de mover de artículos
        function updateArticleMoveButtons() {
            const rows = articleMappingsContainer.querySelectorAll('.mapping-row');
            rows.forEach((row, index) => {
                const upBtn = row.querySelector('.move-up');
                const downBtn = row.querySelector('.move-down');
                
                if (upBtn) {
                    upBtn.style.visibility = index === 0 ? 'hidden' : 'visible';
                }
                
                if (downBtn) {
                    downBtn.style.visibility = index === rows.length - 1 ? 'hidden' : 'visible';
                }
            });
            
            // Actualizar los índices después de mover
            updateArticleMappingIndexes();
        }
        
        // Agregar nuevo mapeo de artículo
        if (addArticleMappingBtn) {
            addArticleMappingBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'mapping-row mb-3 p-3 border rounded';
                newRow.setAttribute('data-index', articleMappingIndex);
                
                newRow.innerHTML = `
                    <input type="hidden" name="article_field_mappings[${articleMappingIndex}][id]" value="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Campo en la API</label>
                            <input type="text" 
                                   name="article_field_mappings[${articleMappingIndex}][source_field]" 
                                   class="form-control source-field" 
                                   placeholder="ej: grupos[*].articulos[*].CodigoArticulo"
                                   required>
                            <small class="text-muted">Ruta al campo en el JSON de la API. Usa [*] para arrays.</small>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Campo en la base de datos</label>
                            <select name="article_field_mappings[${articleMappingIndex}][target_field]" class="form-select target-field" required>
                                <option value="">-- Seleccionar campo --</option>
                                @foreach($articleStandardFields as $value => $label)
                                    <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="article_field_mappings[${articleMappingIndex}][is_required]" value="0">
                                <input type="checkbox" 
                                       name="article_field_mappings[${articleMappingIndex}][is_required]" 
                                       class="form-check-input" 
                                       value="1"
                                       checked>
                                <label class="form-check-label">Requerido</label>
                            </div>
                            
                            <div class="ms-auto">
                                <button type="button" class="btn btn-sm btn-outline-secondary move-up">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary move-down">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-mapping">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="transformations-container mt-2">
                        <label class="form-label small">Transformaciones:</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($transformationOptions as $value => $label)
                                <div class="form-check form-check-inline">
                                    <input type="checkbox" 
                                           name="article_field_mappings[${articleMappingIndex}][transformations][]" 
                                           class="form-check-input" 
                                           value="{{ $value }}"
                                           id="article_transformation_${articleMappingIndex}_{{ $value }}">
                                    <label class="form-check-label small" for="article_transformation_${articleMappingIndex}_{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                `;
                
                articleMappingsContainer.appendChild(newRow);
                articleMappingIndex++;
                updateArticleMoveButtons();
                showToast('Nuevo mapeo de artículo agregado', 'success');
            });
        }
        
        // Manejar eventos de artículos (eliminar y mover)
        if (articleMappingsContainer) {
            articleMappingsContainer.addEventListener('click', function(e) {
                const row = e.target.closest('.mapping-row');
                if (!row) return;
                
                // Eliminar mapeo
                if (e.target.closest('.remove-mapping')) {
                    if (confirm('¿Estás seguro de que quieres eliminar este mapeo de artículo?')) {
                        row.remove();
                        updateArticleMoveButtons();
                        showToast('Mapeo de artículo eliminado', 'success');
                    }
                    return;
                }
                
                // Mover hacia arriba
                if (e.target.closest('.move-up')) {
                    const prevRow = row.previousElementSibling;
                    if (prevRow && prevRow.classList.contains('mapping-row')) {
                        row.parentNode.insertBefore(row, prevRow);
                        updateArticleMoveButtons();
                        showToast('Mapeo movido hacia arriba', 'success');
                    }
                    return;
                }
                
                // Mover hacia abajo
                if (e.target.closest('.move-down')) {
                    const nextRow = row.nextElementSibling;
                    if (nextRow && nextRow.classList.contains('mapping-row')) {
                        row.parentNode.insertBefore(nextRow, row);
                        updateArticleMoveButtons();
                        showToast('Mapeo movido hacia abajo', 'success');
                    }
                    return;
                }
            });
            
            // Actualizar índices después de mover
            updateArticleMappingIndexes();
        }
        
        // Inicializar botones de movimiento de artículos
        if (articleMappingsContainer) {
            updateArticleMoveButtons();
        }
    });
</script>
@endpush

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Clientes') }}</a></li>
        <li class="breadcrumb-item">{{ __('Editar Cliente') }}</li>
    </ul>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">{{ __('Editar Información del Cliente') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('customers.update', $customer->id) }}" method="POST" id="customer-form">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">{{ __('Nombre del Cliente') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $customer->name) }}" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="token_zerotier" class="form-label">{{ __('Token ZeroTier') }} <span class="text-danger">*</span></label>
                                <input type="text" name="token_zerotier" id="token_zerotier" class="form-control" value="{{ old('token_zerotier', $customer->token_zerotier) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="token" class="form-label">{{ __('Token') }} <span class="text-danger">*</span></label>
                                <input type="text" name="token" id="token" class="form-control" value="{{ old('token', $customer->token) }}" required>
                                <small class="form-text text-muted">{{ __('Token de autenticación para las APIs') }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('Configuración de Pedidos') }}</h6>
                            <small class="text-muted">{{ __('URLs para la sincronización de pedidos') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3">
                                <label for="order_listing_url" class="form-label">{{ __('URL de Listado de Pedidos') }}</label>
                                <input type="url" name="order_listing_url" id="order_listing_url" class="form-control" 
                                    value="{{ old('order_listing_url', $customer->order_listing_url) }}" 
                                    placeholder="https://ejemplo.com/api/orders">
                                <small class="form-text text-muted">{{ __('URL para obtener el listado de pedidos') }}</small>
                                @error('order_listing_url')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="order_detail_url" class="form-label">{{ __('URL de Detalle de Pedido') }}</label>
                                <input type="url" name="order_detail_url" id="order_detail_url" class="form-control" 
                                    value="{{ old('order_detail_url', $customer->order_detail_url) }}" 
                                    placeholder="https://ejemplo.com/api/orders/{order_id}">
                                <small class="form-text text-muted">{{ __('URL para obtener el detalle de un pedido. Usar {order_id} como marcador') }}</small>
                                @error('order_detail_url')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Mapeo de Campos -->
                    <div class="card mt-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('Mapeo de Campos URL de Listado de Pedidos') }}</h6>
                                <small class="text-muted">
                                    {{ __('Define cómo se mapean los campos de la API a la base de datos con URL de Listado de Pedidos') }}
                                </small>
                            </div>
                            <button type="button" id="add-mapping" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> {{ __('Añadir Mapeo') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Define cómo se mapean los campos de la API a los campos de la base de datos.
                            </p>
                            
                            <div id="mappings-container">
                                @if($customer->fieldMappings->count() > 0)
                                    @foreach($customer->fieldMappings as $index => $mapping)
                                        @include('customers.partials.field_mappings', [
                                            'index' => $index,
                                            'mapping' => $mapping,
                                            'standardFields' => $standardFields,
                                            'transformationOptions' => $transformationOptions,
                                            'isFirst' => $loop->first,
                                            'isLast' => $loop->last
                                        ])
                                    @endforeach
                                @else
                                    <div class="alert alert-info">
                                        No hay mapeos definidos. Haz clic en "Añadir Mapeo" para crear uno nuevo.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Mapeo de Campos para Procesos -->
                    <div class="card mt-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('Mapeo de Campos URL de Detalle de Pedido') }}</h6>
                                <small class="text-muted">
                                    {{ __('Define cómo se mapean los campos de la API de detalle a la tabla original_order_processes') }}
                                </small>
                            </div>
                            <button type="button" id="add-process-mapping" class="btn btn-sm btn-success">
                                <i class="fas fa-plus me-1"></i> {{ __('Añadir Mapeo de Proceso') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Define cómo se mapean los campos de la API de detalle de pedido a los campos de la tabla original_order_processes.
                            </p>
                            
                            <div id="process-mappings-container">
                                @if($customer->processFieldMappings->count() > 0)
                                    @foreach($customer->processFieldMappings as $index => $mapping)
                                        @include('customers.partials.process_field_mappings', [
                                            'index' => $index,
                                            'mapping' => $mapping,
                                            'processStandardFields' => $processStandardFields,
                                            'transformationOptions' => $transformationOptions,
                                            'isFirst' => $loop->first,
                                            'isLast' => $loop->last
                                        ])
                                    @endforeach
                                @else
                                    <div class="alert alert-info">
                                        No hay mapeos de procesos definidos. Haz clic en "Añadir Mapeo de Proceso" para crear uno nuevo.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Mapeo de Campos para Artículos -->
                    <div class="card mt-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('Mapeo de Campos de Artículos') }}</h6>
                                <small class="text-muted">
                                    {{ __('Define cómo se mapean los campos de artículos de la API a la tabla original_order_articles') }}
                                </small>
                            </div>
                            <button type="button" id="add-article-mapping" class="btn btn-sm btn-success">
                                <i class="fas fa-plus me-1"></i> {{ __('Añadir Mapeo de Artículo') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Define cómo se mapean los campos de artículos de la API a los campos de la base de datos.
                            </p>
                            
                            <div id="article-mappings-container">
                                @if($customer->articleFieldMappings->count() > 0)
                                    @foreach($customer->articleFieldMappings as $index => $mapping)
                                        @include('customers.partials.article_field_mappings', [
                                            'index' => $index,
                                            'mapping' => $mapping,
                                            'articleStandardFields' => $articleStandardFields,
                                            'transformationOptions' => $transformationOptions,
                                            'isFirst' => $loop->first,
                                            'isLast' => $loop->last
                                        ])
                                    @endforeach
                                @else
                                    <div class="alert alert-info">
                                        No hay mapeos de artículos definidos. Haz clic en "Añadir Mapeo de Artículo" para crear uno nuevo.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Cancelar') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> {{ __('Guardar Cambios') }}
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
@endsection
