@php
    $mappings = $mappings ?? [];
    $articleStandardFields = $articleStandardFields ?? [];
    $index = $index ?? 0;
    $mapping = $mapping ?? null;
    $isFirst = $isFirst ?? false;
    $isLast = $isLast ?? false;
    
    // Si no hay mapeo, creamos uno vacío
    if (!$mapping) {
        $mapping = new \App\Models\ArticleFieldMapping([
            'source_field' => '',
            'target_field' => '',
            'transformations' => [],
            'is_required' => true
        ]);
    }
    
    // Opciones de transformaciones disponibles
    $transformationOptions = [
        'trim' => 'Eliminar espacios',
        'uppercase' => 'Convertir a mayúsculas',
        'lowercase' => 'Convertir a minúsculas',
        'number' => 'Convertir a número',
        'date' => 'Formatear como fecha',
        'to_boolean' => 'Convertir a booleano (1/0)',
    ];
@endphp

<div class="mapping-row mb-3 p-3 border rounded" data-index="{{ $index }}">
    <input type="hidden" name="article_field_mappings[{{ $index }}][id]" value="{{ $mapping->id ?? '' }}">
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Campo en la API</label>
            <input type="text" 
                   name="article_field_mappings[{{ $index }}][source_field]" 
                   class="form-control source-field" 
                   value="{{ old("article_field_mappings.{$index}.source_field", $mapping->source_field) }}" 
                   placeholder="ej: grupos[*].articulos[*].CodigoArticulo"
                   required>
            <small class="text-muted">Ruta al campo en el JSON de la API. Usa [*] para arrays.</small>
        </div>
        
        <div class="col-md-5">
            <label class="form-label">Campo en la base de datos</label>
            <select name="article_field_mappings[{{ $index }}][target_field]" class="form-select target-field" required>
                <option value="">-- Seleccionar campo --</option>
                @foreach($articleStandardFields as $value => $label)
                    <option value="{{ $value }}" {{ old("article_field_mappings.{$index}.target_field", $mapping->target_field) == $value ? 'selected' : '' }}>
                        {{ $label }} ({{ $value }})
                    </option>
                @endforeach
            </select>
        </div>
        
        <div class="col-md-2 d-flex align-items-end">
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="article_field_mappings[{{ $index }}][is_required]" value="0">
                <input type="checkbox" 
                       name="article_field_mappings[{{ $index }}][is_required]" 
                       class="form-check-input" 
                       value="1"
                       {{ old("article_field_mappings.{$index}.is_required", $mapping->is_required) ? 'checked' : '' }}>
                <label class="form-check-label">Requerido</label>
            </div>
            
            <div class="ms-auto">
                @if(!$isFirst)
                    <button type="button" class="btn btn-sm btn-outline-secondary move-up">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                @endif
                
                @if(!$isLast)
                    <button type="button" class="btn btn-sm btn-outline-secondary move-down">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                @endif
                
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
                           name="article_field_mappings[{{ $index }}][transformations][]" 
                           class="form-check-input" 
                           value="{{ $value }}"
                           id="article_transformation_{{ $index }}_{{ $value }}"
                           {{ in_array($value, old("article_field_mappings.{$index}.transformations", $mapping->transformations ?? [])) ? 'checked' : '' }}>
                    <label class="form-check-label small" for="article_transformation_{{ $index }}_{{ $value }}">
                        {{ $label }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>
