<div class="mapping-row mb-3 p-3 border rounded" data-index="{{ $index }}">
    <input type="hidden" name="callback_field_mappings[{{ $index }}][id]" value="{{ $mapping->id ?? '' }}">
    
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Campo de ProductionOrder</label>
            <select name="callback_field_mappings[{{ $index }}][source_field]" class="form-select source-field" required>
                <option value="">-- Seleccionar campo --</option>
                @foreach($callbackStandardFields as $value => $label)
                    <option value="{{ $value }}" {{ (old("callback_field_mappings.{$index}.source_field", $mapping->source_field ?? '') == $value) ? 'selected' : '' }}>
                        {{ $label }} ({{ $value }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Campo de la tabla production_orders que se enviará.</small>
        </div>
        
        <div class="col-md-5">
            <label class="form-label">Nombre en el JSON del Callback</label>
            <input type="text" 
                   name="callback_field_mappings[{{ $index }}][target_field]" 
                   class="form-control target-field" 
                   value="{{ old("callback_field_mappings.{$index}.target_field", $mapping->target_field ?? '') }}"
                   placeholder="ej: order_id, production_line, status"
                   required>
            <small class="text-muted">Nombre que tendrá este campo en el JSON enviado al ERP.</small>
        </div>
        
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="callback_field_mappings[{{ $index }}][is_required]" value="0">
                <input type="checkbox" 
                       name="callback_field_mappings[{{ $index }}][is_required]" 
                       class="form-check-input" 
                       value="1"
                       {{ old("callback_field_mappings.{$index}.is_required", $mapping->is_required ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Requerido</label>
            </div>
            
            <div class="ms-auto">
                <button type="button" class="btn btn-sm btn-outline-secondary move-up" {{ ($isFirst ?? false) ? 'style=visibility:hidden' : '' }}>
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-down" {{ ($isLast ?? false) ? 'style=visibility:hidden' : '' }}>
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
                           name="callback_field_mappings[{{ $index }}][transformations][]" 
                           class="form-check-input" 
                           value="{{ $value }}"
                           id="callback_transformation_{{ $index }}_{{ $value }}"
                           {{ (is_array(old("callback_field_mappings.{$index}.transformations", $mapping->transformations ?? [])) && in_array($value, old("callback_field_mappings.{$index}.transformations", $mapping->transformations ?? []))) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="callback_transformation_{{ $index }}_{{ $value }}">
                        {{ $label }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>
