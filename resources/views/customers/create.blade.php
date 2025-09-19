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
        // Función para generar un token seguro
        window.generateToken = function() {
            const tokenField = document.getElementById('token');
            const randomString = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
            tokenField.value = randomString;
            return false;
        };
        
        // Función para mostrar notificación
        function showToast(message, type = 'success') {
            const existingToast = document.querySelector('.toast-message');
            if (existingToast) {
                existingToast.remove();
            }
            
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} toast-message position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        // ========== SECCIÓN PARA MAPEOS DE ÓRDENES ==========
        
        let mappingIndex = 0;
        const mappingsContainer = document.getElementById('mappings-container');
        const addMappingBtn = document.getElementById('add-mapping');
        
        // Agregar nuevo mapeo de orden
        if (addMappingBtn) {
            addMappingBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'mapping-row mb-3 p-3 border rounded';
                newRow.setAttribute('data-index', mappingIndex);
                
                newRow.innerHTML = `
                    <input type="hidden" name="field_mappings[${mappingIndex}][id]" value="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Campo en la API</label>
                            <input type="text" 
                                   name="field_mappings[${mappingIndex}][source_field]" 
                                   class="form-control source-field" 
                                   placeholder="ej: order_id, client_number"
                                   required>
                            <small class="text-muted">Ruta al campo en el JSON de la API. Usa [*] para arrays.</small>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Campo en la base de datos</label>
                            <select name="field_mappings[${mappingIndex}][target_field]" class="form-select target-field" required>
                                <option value="">-- Seleccionar campo --</option>
                                @foreach($standardFields as $value => $label)
                                    <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="field_mappings[${mappingIndex}][is_required]" value="0">
                                <input type="checkbox" 
                                       name="field_mappings[${mappingIndex}][is_required]" 
                                       class="form-check-input" 
                                       value="1"
                                       checked>
                                <label class="form-check-label">Requerido</label>
                            </div>
                            
                            <div class="ms-auto">
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
                                           name="field_mappings[${mappingIndex}][transformations][]" 
                                           class="form-check-input" 
                                           value="{{ $value }}"
                                           id="transformation_${mappingIndex}_{{ $value }}">
                                    <label class="form-check-label small" for="transformation_${mappingIndex}_{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                `;
                
                mappingsContainer.appendChild(newRow);
                mappingIndex++;
                
                // Ocultar mensaje de "No hay mapeos"
                const noMappingsAlert = mappingsContainer.querySelector('.alert-info');
                if (noMappingsAlert) {
                    noMappingsAlert.style.display = 'none';
                }
                
                showToast('Nuevo mapeo agregado', 'success');
            });
        }
        
        // Manejar eventos de órdenes (eliminar)
        if (mappingsContainer) {
            mappingsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-mapping')) {
                    const row = e.target.closest('.mapping-row');
                    if (row && confirm('¿Estás seguro de que quieres eliminar este mapeo?')) {
                        row.remove();
                        
                        // Mostrar mensaje si no hay mapeos
                        const remainingRows = mappingsContainer.querySelectorAll('.mapping-row');
                        if (remainingRows.length === 0) {
                            const noMappingsAlert = mappingsContainer.querySelector('.alert-info');
                            if (noMappingsAlert) {
                                noMappingsAlert.style.display = 'block';
                            }
                        }
                        
                        showToast('Mapeo eliminado', 'success');
                    }
                }
            });
        }
        
        // ========== SECCIÓN PARA MAPEOS DE PROCESOS ==========
        
        let processMappingIndex = 0;
        const processMappingsContainer = document.getElementById('process-mappings-container');
        const addProcessMappingBtn = document.getElementById('add-process-mapping');
        
        // Agregar nuevo mapeo de proceso
        if (addProcessMappingBtn) {
            addProcessMappingBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'mapping-row mb-3 p-3 border rounded';
                newRow.setAttribute('data-index', processMappingIndex);
                
                newRow.innerHTML = `
                    <input type="hidden" name="process_field_mappings[${processMappingIndex}][id]" value="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Campo en la API</label>
                            <input type="text" 
                                   name="process_field_mappings[${processMappingIndex}][source_field]" 
                                   class="form-control source-field" 
                                   placeholder="ej: grupos[*].servicios[*].CodigoArticulo"
                                   required>
                            <small class="text-muted">Ruta al campo en el JSON de la API. Usa [*] para arrays.</small>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Campo en la base de datos</label>
                            <select name="process_field_mappings[${processMappingIndex}][target_field]" class="form-select target-field" required>
                                <option value="">-- Seleccionar campo --</option>
                                @foreach($processStandardFields as $value => $label)
                                    <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="process_field_mappings[${processMappingIndex}][is_required]" value="0">
                                <input type="checkbox" 
                                       name="process_field_mappings[${processMappingIndex}][is_required]" 
                                       class="form-check-input" 
                                       value="1"
                                       checked>
                                <label class="form-check-label">Requerido</label>
                            </div>
                            
                            <div class="ms-auto">
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
                                           name="process_field_mappings[${processMappingIndex}][transformations][]" 
                                           class="form-check-input" 
                                           value="{{ $value }}"
                                           id="process_transformation_${processMappingIndex}_{{ $value }}">
                                    <label class="form-check-label small" for="process_transformation_${processMappingIndex}_{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                `;
                
                processMappingsContainer.appendChild(newRow);
                processMappingIndex++;
                
                // Ocultar mensaje de "No hay mapeos"
                const noMappingsAlert = processMappingsContainer.querySelector('.alert-info');
                if (noMappingsAlert) {
                    noMappingsAlert.style.display = 'none';
                }
                
                showToast('Nuevo mapeo de proceso agregado', 'success');
            });
        }
        
        // Manejar eventos de procesos (eliminar)
        if (processMappingsContainer) {
            processMappingsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-mapping')) {
                    const row = e.target.closest('.mapping-row');
                    if (row && confirm('¿Estás seguro de que quieres eliminar este mapeo de proceso?')) {
                        row.remove();
                        
                        // Mostrar mensaje si no hay mapeos
                        const remainingRows = processMappingsContainer.querySelectorAll('.mapping-row');
                        if (remainingRows.length === 0) {
                            const noMappingsAlert = processMappingsContainer.querySelector('.alert-info');
                            if (noMappingsAlert) {
                                noMappingsAlert.style.display = 'block';
                            }
                        }
                        
                        showToast('Mapeo de proceso eliminado', 'success');
                    }
                }
            });
        }
        
        // ========== SECCIÓN PARA MAPEOS DE ARTÍCULOS ==========
        
        let articleMappingIndex = 0;
        const articleMappingsContainer = document.getElementById('article-mappings-container');
        const addArticleMappingBtn = document.getElementById('add-article-mapping');
        
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
                
                // Ocultar mensaje de "No hay mapeos"
                const noMappingsAlert = articleMappingsContainer.querySelector('.alert-info');
                if (noMappingsAlert) {
                    noMappingsAlert.style.display = 'none';
                }
                
                showToast('Nuevo mapeo de artículo agregado', 'success');
            });
        }
        
        // Manejar eventos de artículos (eliminar)
        if (articleMappingsContainer) {
            articleMappingsContainer.addEventListener('click', function(e) {
                if (e.target.closest('.remove-mapping')) {
                    const row = e.target.closest('.mapping-row');
                    if (row && confirm('¿Estás seguro de que quieres eliminar este mapeo de artículo?')) {
                        row.remove();
                        
                        // Mostrar mensaje si no hay mapeos
                        const remainingRows = articleMappingsContainer.querySelectorAll('.mapping-row');
                        if (remainingRows.length === 0) {
                            const noMappingsAlert = articleMappingsContainer.querySelector('.alert-info');
                            if (noMappingsAlert) {
                                noMappingsAlert.style.display = 'block';
                            }
                        }
                        
                        showToast('Mapeo de artículo eliminado', 'success');
                    }
                }
            });
        }
        
        // ========== SECCIÓN PARA CALLBACK CONFIGURATION ==========
        
        // Manejar el toggle del callback
        const callbackCheckbox = document.getElementById('callback_finish_process');
        const callbackUrlContainer = document.getElementById('callback-url-container');
        const callbackMappingsSection = document.getElementById('callback-mappings-section');
        
        if (callbackCheckbox) {
            callbackCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                
                if (callbackUrlContainer) {
                    callbackUrlContainer.style.display = isChecked ? 'block' : 'none';
                }
                
                if (callbackMappingsSection) {
                    callbackMappingsSection.style.display = isChecked ? 'block' : 'none';
                }
                
                // Si se desactiva, limpiar la URL
                if (!isChecked) {
                    const callbackUrlInput = document.getElementById('callback_url');
                    if (callbackUrlInput) {
                        callbackUrlInput.value = '';
                    }
                }
            });
        }
        
        // ========== SECCIÓN PARA MAPEOS DE CALLBACK ==========
        
        // Inicializar el índice para nuevos mapeos de callback
        let callbackMappingIndex = 0;
        const callbackMappingsContainer = document.getElementById('callback-mappings-container');
        const addCallbackMappingBtn = document.getElementById('add-callback-mapping');
        
        // Agregar nuevo mapeo de callback
        if (addCallbackMappingBtn) {
            addCallbackMappingBtn.addEventListener('click', function() {
                const newRow = document.createElement('div');
                newRow.className = 'mapping-row mb-3 p-3 border rounded';
                newRow.setAttribute('data-index', callbackMappingIndex);
                
                newRow.innerHTML = `
                    <input type="hidden" name="callback_field_mappings[${callbackMappingIndex}][id]" value="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Campo de ProductionOrder</label>
                            <select name="callback_field_mappings[${callbackMappingIndex}][source_field]" class="form-select source-field" required>
                                <option value="">-- Seleccionar campo --</option>
                                @foreach($callbackStandardFields as $value => $label)
                                    <option value="{{ $value }}">{{ $label }} ({{ $value }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Campo de la tabla production_orders que se enviará.</small>
                        </div>
                        
                        <div class="col-md-5">
                            <label class="form-label">Nombre en el JSON del Callback</label>
                            <input type="text" 
                                   name="callback_field_mappings[${callbackMappingIndex}][target_field]" 
                                   class="form-control target-field" 
                                   placeholder="ej: order_id, production_line, status"
                                   required>
                            <small class="text-muted">Nombre que tendrá este campo en el JSON enviado al ERP.</small>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="callback_field_mappings[${callbackMappingIndex}][is_required]" value="0">
                                <input type="checkbox" 
                                       name="callback_field_mappings[${callbackMappingIndex}][is_required]" 
                                       class="form-check-input" 
                                       value="1"
                                       checked>
                                <label class="form-check-label">Requerido</label>
                            </div>
                            
                            <div class="ms-auto">
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
                                           name="callback_field_mappings[${callbackMappingIndex}][transformations][]" 
                                           class="form-check-input" 
                                           value="{{ $value }}"
                                           id="callback_transformation_${callbackMappingIndex}_{{ $value }}">
                                    <label class="form-check-label small" for="callback_transformation_${callbackMappingIndex}_{{ $value }}">
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                `;
                
                // Remover el mensaje de "no hay mapeos" si existe
                const noMappingsAlert = callbackMappingsContainer.querySelector('.alert-info');
                if (noMappingsAlert) {
                    noMappingsAlert.remove();
                }
                
                callbackMappingsContainer.appendChild(newRow);
                callbackMappingIndex++;
                showToast('Nuevo mapeo de callback agregado', 'success');
            });
        }
        
        // Manejar eventos de callback (eliminar)
        if (callbackMappingsContainer) {
            callbackMappingsContainer.addEventListener('click', function(e) {
                const row = e.target.closest('.mapping-row');
                if (!row) return;
                
                // Eliminar mapeo
                if (e.target.closest('.remove-mapping')) {
                    if (confirm('¿Estás seguro de que quieres eliminar este mapeo de callback?')) {
                        row.remove();
                        
                        // Si no quedan mapeos, mostrar el mensaje
                        if (callbackMappingsContainer.querySelectorAll('.mapping-row').length === 0) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-info';
                            alertDiv.textContent = 'No hay mapeos de callback definidos. Haz clic en "Añadir Mapeo de Callback" para crear uno nuevo.';
                            callbackMappingsContainer.appendChild(alertDiv);
                        }
                        
                        showToast('Mapeo de callback eliminado', 'success');
                    }
                    return;
                }
            });
        }
        
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
                    
                    <!-- Sección de Configuración de Callback -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('Configuración de Callback de Finalización') }}</h6>
                            <small class="text-muted">{{ __('Configuración para notificar al ERP cuando una orden se finaliza') }}</small>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input type="hidden" name="callback_finish_process" value="0">
                                <input type="checkbox" 
                                       name="callback_finish_process" 
                                       class="form-check-input" 
                                       id="callback_finish_process"
                                       value="1"
                                       {{ old('callback_finish_process') ? 'checked' : '' }}>
                                <label class="form-check-label" for="callback_finish_process">
                                    {{ __('Activar callback de finalización') }}
                                </label>
                                <small class="form-text text-muted d-block">
                                    {{ __('Cuando se active, se enviará una notificación HTTP al ERP cada vez que una orden se finalice') }}
                                </small>
                            </div>
                            
                            <div class="form-group mb-3" id="callback-url-container" style="{{ old('callback_finish_process') ? '' : 'display: none;' }}">
                                <label for="callback_url" class="form-label">{{ __('URL del Callback') }}</label>
                                <input type="url" 
                                       name="callback_url" 
                                       id="callback_url" 
                                       class="form-control" 
                                       value="{{ old('callback_url') }}" 
                                       placeholder="https://ejemplo.com/api/production-finished">
                                <small class="form-text text-muted">
                                    {{ __('URL donde se enviará la notificación cuando una orden se finalice') }}
                                </small>
                                @error('callback_url')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Mapeo de Campos para Callback -->
                    <div class="card mt-4" id="callback-mappings-section" style="{{ old('callback_finish_process') ? '' : 'display: none;' }}">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('Mapeo de Campos para Callback') }}</h6>
                                <small class="text-muted">
                                    {{ __('Define qué campos de production_orders se envían en el callback y cómo se nombran') }}
                                </small>
                            </div>
                            <button type="button" id="add-callback-mapping" class="btn btn-sm btn-warning">
                                <i class="fas fa-plus me-1"></i> {{ __('Añadir Mapeo de Callback') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Define cómo se mapean los campos de production_orders al JSON que se enviará al callback del ERP.
                            </p>
                            
                            <div id="callback-mappings-container">
                                <div class="alert alert-info">
                                    No hay mapeos de callback definidos. Haz clic en "Añadir Mapeo de Callback" para crear uno nuevo.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección de Mapeo de Campos para Órdenes -->
                    <div class="card mt-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">{{ __('Mapeo de Campos de Órdenes') }}</h6>
                                <small class="text-muted">
                                    {{ __('Define cómo se mapean los campos de la API a los campos de la base de datos') }}
                                </small>
                            </div>
                            <button type="button" id="add-mapping" class="btn btn-sm btn-success">
                                <i class="fas fa-plus me-1"></i> {{ __('Añadir Mapeo') }}
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">
                                Define cómo se mapean los campos de la API a los campos de la base de datos.
                            </p>
                            
                            <div id="mappings-container">
                                <div class="alert alert-info">
                                    No hay mapeos definidos. Haz clic en "Añadir Mapeo" para crear uno nuevo.
                                </div>
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
                                Define cómo se mapean los campos de procesos de la API a los campos de la base de datos.
                            </p>
                            
                            <div id="process-mappings-container">
                                <div class="alert alert-info">
                                    No hay mapeos de procesos definidos. Haz clic en "Añadir Mapeo de Proceso" para crear uno nuevo.
                                </div>
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
                                <div class="alert alert-info">
                                    No hay mapeos de artículos definidos. Haz clic en "Añadir Mapeo de Artículo" para crear uno nuevo.
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
