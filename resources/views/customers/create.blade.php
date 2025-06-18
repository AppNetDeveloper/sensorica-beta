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
