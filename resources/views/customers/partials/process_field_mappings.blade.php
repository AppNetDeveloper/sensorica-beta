<div class="mapping-row mb-3 p-3 border rounded" data-index="{{ $index }}">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="process_field_mappings_{{ $index }}_source_field" class="form-label">{{ __('Campo de Origen (API)') }}</label>
                <input type="text" 
                       name="process_field_mappings[{{ $index }}][source_field]" 
                       id="process_field_mappings_{{ $index }}_source_field"
                       class="form-control" 
                       value="{{ old('process_field_mappings.' . $index . '.source_field', $mapping->source_field ?? '') }}"
                       placeholder="ej: ProcessCode, ProcessName">
                <small class="form-text text-muted">{{ __('Nombre del campo en la respuesta de la API') }}</small>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="form-group">
                <label for="process_field_mappings_{{ $index }}_target_field" class="form-label">{{ __('Campo de Destino (BD)') }}</label>
                <select name="process_field_mappings[{{ $index }}][target_field]" 
                        id="process_field_mappings_{{ $index }}_target_field"
                        class="form-control">
                    <option value="">{{ __('Seleccionar campo...') }}</option>
                    @foreach($processStandardFields as $field => $label)
                        <option value="{{ $field }}" 
                                {{ old('process_field_mappings.' . $index . '.target_field', $mapping->target_field ?? '') == $field ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">{{ __('Campo en la tabla original_order_processes') }}</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                <label class="form-label">{{ __('Transformaciones') }}</label>
                <div class="transformations-container">
                    @foreach($transformationOptions as $transformation => $label)
                        <div class="form-check form-check-inline">
                            <input type="checkbox" 
                                   name="process_field_mappings[{{ $index }}][transformations][]" 
                                   id="process_transformation_{{ $index }}_{{ $transformation }}"
                                   class="form-check-input" 
                                   value="{{ $transformation }}"
                                   {{ in_array($transformation, old('process_field_mappings.' . $index . '.transformations', $mapping->transformations ?? [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="process_transformation_{{ $index }}_{{ $transformation }}">
                                {{ $label }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
            
            <div class="form-check mt-2">
                <input type="checkbox" 
                       name="process_field_mappings[{{ $index }}][is_required]" 
                       id="process_field_mappings_{{ $index }}_is_required"
                       class="form-check-input" 
                       value="1"
                       {{ old('process_field_mappings.' . $index . '.is_required', $mapping->is_required ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="process_field_mappings_{{ $index }}_is_required">
                    {{ __('Campo requerido') }}
                </label>
            </div>
        </div>
        
        <div class="col-md-1">
            <div class="mapping-actions d-flex flex-column align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary move-up mb-1" 
                        {{ $isFirst ?? false ? 'style=visibility:hidden' : '' }}>
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-down mb-1"
                        {{ $isLast ?? false ? 'style=visibility:hidden' : '' }}>
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-mapping">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Campo oculto para el ID del mapeo existente -->
    @if(isset($mapping) && $mapping->id)
        <input type="hidden" name="process_field_mappings[{{ $index }}][id]" value="{{ $mapping->id }}">
    @endif
</div>
