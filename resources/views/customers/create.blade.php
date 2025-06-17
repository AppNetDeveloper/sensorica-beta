@extends('layouts.admin')
@section('title', __('Agregar Nuevo Cliente'))

@push('styles')
<style>
    .mapping-row {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e9ecef;
        position: relative;
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
    .move-up, .move-down {
        cursor: pointer;
    }
    .remove-mapping {
        cursor: pointer;
        color: #dc3545;
    }
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
</style>
@endpush

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Clientes') }}</a></li>
        <li class="breadcrumb-item">{{ __('Nuevo Cliente') }}</li>
    </ul>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el índice para nuevos mapeos
        let mappingIndex = 0;
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
            
            // Mostrar/ocultar mensaje de "No hay mapeos"
            const noMappingsAlert = document.querySelector('#mappings-container > .alert');
            if (noMappingsAlert) {
                noMappingsAlert.style.display = rows.length === 0 ? 'block' : 'none';
            }
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
            // Si ya existe un toast, lo eliminamos
            const existingToast = document.querySelector('.toast-message');
            if (existingToast) {
                existingToast.remove();
            }
            
            const toast = document.createElement('div');
            toast.className = `toast-message position-fixed bottom-0 end-0 m-3 p-3 bg-${type} text-white rounded shadow`;
            toast.style.zIndex = '1100';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            // Eliminar la notificación después de 3 segundos
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // Agregar un nuevo mapeo
        addMappingBtn.addEventListener('click', function() {
            // Deshabilitar el botón para evitar múltiples clics
            const originalText = addMappingBtn.innerHTML;
            addMappingBtn.disabled = true;
            addMappingBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Cargando...';
            
            // Hacer una petición para obtener el HTML de la fila
            fetch(`/customers/0/field-mapping-row?index=${mappingIndex}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Crear un elemento temporal para el nuevo mapeo
                    const temp = document.createElement('div');
                    temp.innerHTML = data.html.trim();
                    const newMapping = temp.firstChild;
                    
                    // Ocultar el mensaje de "No hay mapeos" si existe
                    const noMappingsAlert = document.querySelector('#mappings-container > .alert');
                    if (noMappingsAlert) {
                        noMappingsAlert.style.display = 'none';
                    }
                    
                    // Agregar el nuevo mapeo al contenedor
                    mappingsContainer.appendChild(newMapping);
                    
                    // Actualizar índices y botones
                    updateMoveButtons();
                    
                    // Mostrar notificación
                    showToast('Nuevo mapeo añadido');
                } else {
                    throw new Error(data.message || 'Error al cargar el formulario de mapeo');
                }
            })
            .catch(error => {
                console.error('Error al cargar el formulario de mapeo:', error);
                showToast('Error al cargar el formulario de mapeo', 'danger');
            })
            .finally(() => {
                // Restaurar el botón
                addMappingBtn.disabled = false;
                addMappingBtn.innerHTML = originalText;
            });
        });
        
        // Manejar eventos de clic en el contenedor de mapeos
        mappingsContainer.addEventListener('click', function(e) {
            const row = e.target.closest('.mapping-row');
            if (!row) return;
            
            // Eliminar mapeo
            if (e.target.closest('.remove-mapping')) {
                if (confirm('¿Estás seguro de que deseas eliminar este mapeo?')) {
                    row.remove();
                    updateMoveButtons();
                    showToast('Mapeo eliminado');
                }
                return;
            }
            
            // Mover mapeo hacia arriba
            if (e.target.closest('.move-up')) {
                const prevRow = row.previousElementSibling;
                if (prevRow) {
                    row.parentNode.insertBefore(row, prevRow);
                    updateMoveButtons();
                }
                return;
            }
            
            // Mover mapeo hacia abajo
            if (e.target.closest('.move-down')) {
                const nextRow = row.nextElementSibling;
                if (nextRow) {
                    row.parentNode.insertBefore(nextRow, row);
                    updateMoveButtons();
                }
                return;
            }
        });
        
        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Generar token al cargar la página si no hay valor
        const tokenField = document.getElementById('token');
        if (tokenField && !tokenField.value) {
            generateToken();
        }
    });
</script>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
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
                <h5 class="card-title">{{ __('Información del Cliente') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('customers.store') }}" method="POST" id="customer-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="name" class="form-label">{{ __('Nombre del Cliente') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="token_zerotier" class="form-label">{{ __('Token ZeroTier') }} <span class="text-danger">*</span></label>
                                <input type="text" name="token_zerotier" id="token_zerotier" class="form-control" value="{{ old('token_zerotier') }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="token" class="form-label">{{ __('Token de API') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="token" id="token" class="form-control" value="{{ old('token', bin2hex(random_bytes(16))) }}" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateToken()">
                                        <i class="fas fa-sync-alt"></i> {{ __('Generar') }}
                                    </button>
                                </div>
                                <small class="form-text text-muted">{{ __('Token de autenticación para las APIs. Haz clic en Generar para crear uno automáticamente.') }}</small>
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
                                       placeholder="https://ejemplo.com/api/orders" value="{{ old('order_listing_url') }}">
                                <small class="form-text text-muted">{{ __('URL para obtener el listado de pedidos') }}</small>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="order_detail_url" class="form-label">{{ __('URL de Detalle de Pedido') }}</label>
                                <input type="url" name="order_detail_url" id="order_detail_url" class="form-control" 
                                       placeholder="https://ejemplo.com/api/orders/{order_id}" value="{{ old('order_detail_url') }}">
                                <small class="form-text text-muted">{{ __('URL para obtener el detalle de un pedido. Usar {order_id} como marcador') }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('Mapeo de Campos') }}</h6>
                                <small class="text-muted">
                                    Define cómo se mapean los campos de la API a los campos de la base de datos.
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" id="add-mapping">
                                <i class="fas fa-plus me-1"></i> {{ __('Añadir Mapeo') }}
                            </button>
                        </div>
                        
                        <div class="card-body">
                            <p class="text-muted small">
                                Define cómo se mapean los campos de la API a los campos de la base de datos.
                            </p>
                            
                            <div id="mappings-container">
                                <!-- Las filas de mapeo se agregarán aquí dinámicamente -->
                                <div class="alert alert-info">
                                    No hay mapeos definidos. Haz clic en "Añadir Mapeo" para crear uno nuevo.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Cancelar') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> {{ __('Guardar Cliente') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
