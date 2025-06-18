<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-boxes"></i> Mapeos de Campos de Artículos
            <small class="text-muted">- Configuración para mapear campos JSON a artículos</small>
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Campos disponibles para artículos:</strong>
            <ul class="mb-0 mt-2">
                <li><code>codigo_articulo</code> - Código del artículo</li>
                <li><code>descripcion_articulo</code> - Descripción del artículo</li>
                <li><code>grupo_articulo</code> - Grupo del artículo</li>
            </ul>
        </div>

        <div id="article-mappings-container">
            @if($customer->articleFieldMappings && $customer->articleFieldMappings->count() > 0)
                @foreach($customer->articleFieldMappings as $index => $mapping)
                    <div class="article-mapping-row border rounded p-3 mb-3" data-index="{{ $index }}">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Campo Origen (JSON)</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="article_mappings[{{ $index }}][source_field]" 
                                       value="{{ $mapping->source_field }}"
                                       placeholder="ej: grupos[*].articulos[*].CodigoArticulo">
                                <small class="text-muted">Usa [*] para arrays dinámicos</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Campo Destino</label>
                                <select class="form-control" name="article_mappings[{{ $index }}][target_field]">
                                    <option value="">Seleccionar campo...</option>
                                    <option value="codigo_articulo" {{ $mapping->target_field == 'codigo_articulo' ? 'selected' : '' }}>
                                        Código del Artículo
                                    </option>
                                    <option value="descripcion_articulo" {{ $mapping->target_field == 'descripcion_articulo' ? 'selected' : '' }}>
                                        Descripción del Artículo
                                    </option>
                                    <option value="grupo_articulo" {{ $mapping->target_field == 'grupo_articulo' ? 'selected' : '' }}>
                                        Grupo del Artículo
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Transformación (JSON)</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="article_mappings[{{ $index }}][transformation]" 
                                       value="{{ $mapping->transformation ? json_encode($mapping->transformation) : '' }}"
                                       placeholder='{"format": "uppercase"}'>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="button" class="btn btn-danger btn-sm d-block remove-article-mapping">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <button type="button" id="add-article-mapping" class="btn btn-success">
            <i class="fas fa-plus"></i> Agregar Mapeo de Artículo
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let articleMappingIndex = {{ $customer->articleFieldMappings ? $customer->articleFieldMappings->count() : 0 }};

    // Agregar nuevo mapeo de artículo
    document.getElementById('add-article-mapping').addEventListener('click', function() {
        const container = document.getElementById('article-mappings-container');
        const newRow = document.createElement('div');
        newRow.className = 'article-mapping-row border rounded p-3 mb-3';
        newRow.setAttribute('data-index', articleMappingIndex);
        
        newRow.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Campo Origen (JSON)</label>
                    <input type="text" 
                           class="form-control" 
                           name="article_mappings[${articleMappingIndex}][source_field]" 
                           placeholder="ej: grupos[*].articulos[*].CodigoArticulo">
                    <small class="text-muted">Usa [*] para arrays dinámicos</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Campo Destino</label>
                    <select class="form-control" name="article_mappings[${articleMappingIndex}][target_field]">
                        <option value="">Seleccionar campo...</option>
                        <option value="codigo_articulo">Código del Artículo</option>
                        <option value="descripcion_articulo">Descripción del Artículo</option>
                        <option value="grupo_articulo">Grupo del Artículo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transformación (JSON)</label>
                    <input type="text" 
                           class="form-control" 
                           name="article_mappings[${articleMappingIndex}][transformation]" 
                           placeholder='{"format": "uppercase"}'>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block remove-article-mapping">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(newRow);
        articleMappingIndex++;
    });

    // Eliminar mapeo de artículo
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-article-mapping') || 
            e.target.closest('.remove-article-mapping')) {
            const row = e.target.closest('.article-mapping-row');
            if (row) {
                row.remove();
            }
        }
    });
});
</script>
