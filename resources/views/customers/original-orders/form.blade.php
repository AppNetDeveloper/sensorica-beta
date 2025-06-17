@php
    $isEdit = isset($originalOrder) && $originalOrder->exists;
    $route = $isEdit 
        ? route('customers.original-orders.update', [$customer->id, $originalOrder->id])
        : route('customers.original-orders.store', $customer->id);
    $method = $isEdit ? 'PUT' : 'POST';
    $title = $isEdit ? __('Edit Order') : __('Create New Order');
    $selectedProcesses = $isEdit ? $selectedProcesses : [];
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $title }} - {{ $customer->name }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('customers.original-orders.index', $customer->id) }}" 
                           class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                <form action="{{ $route }}" method="POST">
                    @csrf
                    @method($method)
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="order_id">@lang('Order ID') *</label>
                                    <input type="text" name="order_id" id="order_id" 
                                           class="form-control @error('order_id') is-invalid @enderror"
                                           value="{{ old('order_id', $originalOrder->order_id ?? '') }}" required>
                                    @error('order_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="client_number">@lang('Client Number')</label>
                                    <input type="text" name="client_number" id="client_number" 
                                           class="form-control @error('client_number') is-invalid @enderror"
                                           value="{{ old('client_number', $originalOrder->client_number ?? '') }}">
                                    @error('client_number')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="delivery_date">@lang('Delivery Date')</label>
                                    <input type="datetime-local" name="delivery_date" id="delivery_date" 
                                           class="form-control @error('delivery_date') is-invalid @enderror"
                                           value="{{ old('delivery_date', isset($originalOrder->delivery_date) ? $originalOrder->delivery_date->format('Y-m-d\TH:i') : '') }}">
                                    @error('delivery_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" name="in_stock" id="in_stock" 
                                               class="form-check-input @error('in_stock') is-invalid @enderror"
                                               value="1" 
                                               {{ old('in_stock', isset($originalOrder) && $originalOrder->in_stock ? 'checked' : '') }}>
                                        <label class="form-check-label" for="in_stock">@lang('In Stock')</label>
                                        @error('in_stock')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <small class="form-text text-muted d-block">@lang('Check if the material is currently in stock')</small>
                                    </div>
                                    
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="processed" id="processed" 
                                               class="form-check-input @error('processed') is-invalid @enderror"
                                               value="1" 
                                               {{ old('processed', isset($originalOrder) && $originalOrder->processed ? 'checked' : '') }}>
                                        <label class="form-check-label" for="processed">@lang('Mark as Processed')</label>
                                        @error('processed')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <small class="form-text text-muted d-block">@lang('Indicates if this order has been processed in the system')</small>
                                        
                                        @if(isset($originalOrder) && $originalOrder->finished_at)
                                            <div class="mt-2">
                                                <strong>@lang('Processed on'):</strong> 
                                                <span class="text-info">{{ $originalOrder->finished_at->format('d/m/Y H:i:s') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="order_details">@lang('Order Details (JSON)') *</label>
                                    <textarea name="order_details" id="order_details" 
                                               class="form-control @error('order_details') is-invalid @enderror" 
                                               rows="8" required>{{ old('order_details', $originalOrder->order_details ?? '{
    "product": "",
    "quantity": 0,
    "due_date": ""
}') }}</textarea>
                                    @error('order_details')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">@lang('Enter valid JSON format with product details, quantities, and dates.')</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Select Processes') *</label>
                                    @error('processes')
                                        <div class="alert alert-danger">{{ $message }}</div>
                                    @enderror
                                    <div class="mb-3" style="max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; padding: .375rem .75rem; border-radius: .25rem;">
                                        <div class="input-group">
                                            <select id="process_selector" class="form-control">
                                                <option value="">@lang('Select a service...')</option>
                                                @if(isset($processes) && $processes->isNotEmpty())
                                                    @foreach($processes as $process)
                                                        <option value="{{ $process->id }}" 
                                                                data-name="{{ $process->name }}" 
                                                                data-code="{{ $process->code }}">
                                                            {{ $process->name }} ({{ $process->code }})
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div class="input-group-append">
                                                <button type="button" id="add_process_btn" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> @lang('Add')
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Lista de procesos añadidos -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">@lang('Added Services')</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0" id="processes_table">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>@lang('Code')</th>
                                                        <th>@lang('Name')</th>
                                                        <th class="text-center">@lang('Articles')</th>
                                                        <th class="text-center">@lang('Finished')</th>
                                                        <th class="text-right">@lang('Actions')</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="processes_list">
                                                    @php
                                                        // Si hay datos antiguos (error de validación), `old('processes')` será un array de [uniqueId => processId].
                                                        // Si no, creamos un array similar a partir de la relación de la base de datos.
                                                        $processesToRender = old('processes', $originalOrder->processes->mapWithKeys(function ($process) {
                                                            return [$process->pivot->id => $process->id];
                                                        }));
                                                    @endphp

                                                    @if ($processesToRender && count($processesToRender) > 0)
                                                        @foreach ($processesToRender as $uniqueId => $processId)
                                                            @php
                                                                $process = $processes->firstWhere('id', $processId);
                                                                if (!$process) continue; // Protección contra procesos que ya no existen
                                                                $isFinished = isset(old('processes_finished')[$uniqueId]) || 
                                                                    (isset($originalOrder->processes) && 
                                                                    $originalOrder->processes->contains('id', $processId) && 
                                                                    $originalOrder->processes->firstWhere('id', $processId)->pivot->finished);
                                                            @endphp
                                                            <tr data-unique-id="{{ $uniqueId }}" data-process-id="{{ $process->id }}">
                                                                <td>{{ $process->code }}</td>
                                                                <td>{{ $process->name }}</td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-sm btn-info add-articles-btn" 
                                                                            data-toggle="modal" 
                                                                            data-target="#articlesModal" 
                                                                            data-unique-id="{{ $uniqueId }}" 
                                                                            data-process-name="{{ $process->name }}">
                                                                        <i class="fas fa-plus"></i>
                                                                    </button>
                                                                </td>
                                                                <td class="text-center">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox" class="custom-control-input" 
                                                                               id="finished_{{ $uniqueId }}" 
                                                                               name="processes_finished[{{ $uniqueId }}]" 
                                                                               value="1" 
                                                                               {{ $isFinished ? 'checked' : '' }}>
                                                                        <label class="custom-control-label" for="finished_{{ $uniqueId }}"></label>
                                                                    </div>
                                                                </td>
                                                                <td class="text-right process-actions">
                                                                    <button type="button" class="btn btn-sm btn-danger remove-process"><i class="fas fa-times"></i></button>
                                                                </td>
                                                                <input type="hidden" name="processes[{{ $uniqueId }}]" value="{{ $process->id }}">
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div id="no_processes" class="text-center p-3 {{ !empty($processIdsToRender) ? 'd-none' : '' }}">
                                            <p class="text-muted mb-0">@lang('No services added yet. Use the selector above to add services.')</p>
                                        </div>
                                        
                                        <!-- Contenedor para los inputs ocultos de los artículos -->
                                        <div id="articles_hidden_inputs_container" style="display: none;"></div>
                                    </div>
                                </div>
                                
                                <!-- The Process Status section has been merged with the Added Services section above -->

                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> @lang('Note'):</h5>
                                    @lang('The order details should be in valid JSON format. You can include any additional information about the order in this field.')
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" id="save_changes_btn">
                            <i class="fas fa-save"></i> {{ $isEdit ? __('Update') : __('Create') }} @lang('Order')
                        </button>
                        <a href="{{ route('customers.original-orders.index', $customer->id) }}" 
                           class="btn btn-default">@lang('Cancel')</a>
                    </div>
                    
                    <!-- Modal para agregar artículos -->
                    <div class="modal fade" id="articlesModal" tabindex="-1" role="dialog" aria-labelledby="articlesModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="articlesModalLabel">@lang('Add Articles for') <span id="process_name_display" class="font-weight-bold"></span></h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <!-- Hidden fields to track context -->
                                    <input type="hidden" id="current_unique_id">

                                    <!-- Form to add a new article -->
                                    <div class="row mb-3">
                                        <div class="col-md-4"><input type="text" class="form-control" id="article_code" placeholder="@lang('Article Code')"></div>
                                        <div class="col-md-5"><input type="text" class="form-control" id="article_description" placeholder="@lang('Description')"></div>
                                        <div class="col-md-2"><input type="text" class="form-control" id="article_group" placeholder="@lang('Group')"></div>
                                        <div class="col-md-1">
                                            <button type="button" id="add_article_to_table_btn" class="btn btn-primary btn-block">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Table of added articles -->
                                    <div class="table-responsive mt-3">
                                        <table class="table table-bordered table-hover" id="articles_table">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>@lang('Code')</th>
                                                    <th>@lang('Description')</th>
                                                    <th>@lang('Group')</th>
                                                    <th width="80">@lang('Actions')</th>
                                                </tr>
                                            </thead>
                                            <tbody id="articles_list_in_modal"></tbody>
                                        </table>
                                        <div id="no_articles_in_modal" class="text-center p-3">
                                            <p class="text-muted mb-0">@lang('No articles added yet.')</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('Close')</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Container for hidden inputs that will be submitted with the form -->
                    <div id="articles_hidden_inputs_container">
                        <!-- Los artículos se cargarán dinámicamente con JavaScript -->
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    #processes_table tbody tr {
        cursor: pointer;
    }
    #processes_table tbody tr:hover {
        background-color: #f8f9fa;
    }
    .process-actions {
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    
    const allProcesses = @json($processes->keyBy('id'));
    let articlesData = {};
    @if(isset($articlesData))
        try {
            // Asegurarse de que articlesData sea un objeto JavaScript válido
            articlesData = JSON.parse(JSON.stringify(@json($articlesData ?? [])));
            console.log('Articles data loaded:', articlesData);
        } catch (e) {
            console.error('Error parsing articlesData:', e);
            articlesData = {};
        }
    @else
        articlesData = {};
    @endif

    function updateNoProcessesMessage() {
        $('#no_processes').toggleClass('d-none', $('#processes_list tr').length > 0);
    }

    function updateNoArticlesMessage() {
        $('#no_articles_in_modal').toggleClass('d-none', $('#articles_list_in_modal tr').length > 0);
    }

    function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>'"/]/g, tag => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;',
            "'": '&#39;', '"': '&quot;', '/': '&#x2F;'
        }[tag] || tag));
    }

    // --- PROCESS MANAGEMENT ---
    $('#add_process_btn').on('click', function() {
        const processId = $('#process_selector').val();
        if (!processId) return;
        const process = allProcesses[processId];
        if (!process) return;

        // Para procesos nuevos, usamos un ID temporal pero NO permitimos añadir artículos
        // hasta que se guarde el proceso en la base de datos
        const uniqueId = `new_${processId}`;
        const newRow = `
            <tr data-unique-id="${uniqueId}" data-process-id="${process.id}">
                <td>${process.code}</td>
                <td>${process.name}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-info add-articles-btn" 
                           data-toggle="modal" 
                           data-target="#articlesModal" 
                           data-unique-id="${uniqueId}" 
                           data-process-name="${process.name}" 
                           disabled title="@lang('Save the order first to add articles')">
                        <i class="fas fa-plus"></i>
                    </button>
                </td>
                <td class="text-center">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="finished_${uniqueId}" name="processes_finished[${uniqueId}]" value="1">
                        <label class="custom-control-label" for="finished_${uniqueId}"></label>
                    </div>
                </td>
                <td class="text-right process-actions">
                    <button type="button" class="btn btn-sm btn-danger remove-process"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="processes[${uniqueId}]" value="${process.id}">
                </td>
            </tr>`;
        $('#processes_list').append(newRow);
        updateNoProcessesMessage();
    });

    $('#processes_list').on('click', '.remove-process', function() {
        const uniqueId = $(this).closest('tr').data('unique-id');
        // Eliminar todos los artículos asociados a este proceso
        $(`#articles_hidden_inputs_container .article-group[data-parent-unique-id="${uniqueId}"]`).remove();
        $(this).closest('tr').remove();
        updateNoProcessesMessage();
    });

    // --- ARTICLE MODAL MANAGEMENT ---
    $('#articlesModal').on('show.bs.modal', function(event) {
        console.log('Modal opening');
        const button = $(event.relatedTarget);
        const uniqueId = button.data('unique-id');
        console.log('Process unique ID:', uniqueId);
        
        if (!uniqueId) {
            console.error('No unique ID found on button:', button);
            alert('Error: No se pudo identificar el proceso. Por favor, inténtalo de nuevo.');
            return;
        }
        
        // Asegurarse de que el ID único se establezca correctamente
        $('#current_unique_id').val(uniqueId);
        console.log('Set current_unique_id to:', $('#current_unique_id').val());
        
        // Guardar el ID único también como un atributo data para mayor seguridad
        $('#articlesModal').data('current-unique-id', uniqueId);
        
        $('#process_name_display').text(button.data('process-name') || 'Proceso');
        
        $('#articles_list_in_modal').empty();
        $('#article_code, #article_description, #article_group').val('');

        console.log('Looking for existing articles for process:', uniqueId);
        const existingArticles = $(`#articles_hidden_inputs_container .article-group[data-parent-unique-id="${uniqueId}"]`);
        console.log('Found existing articles:', existingArticles.length);
        
        existingArticles.each(function() {
            const articleUniqueId = $(this).data('article-unique-id');
            const code = $(this).find('input[name*="[code]"]').val();
            const description = $(this).find('input[name*="[description]"]').val();
            const group = $(this).find('input[name*="[group]"]').val();
            console.log('Loading existing article:', {articleUniqueId, code, description, group});
            addArticleRowToModal(articleUniqueId, code, description, group);
        });
        updateNoArticlesMessage();
    });

    $('#add_article_to_table_btn').on('click', function() {
        console.log('Add article button clicked');
        
        // Obtener el ID único del proceso de múltiples fuentes para mayor robustez
        let parentUniqueId = $('#current_unique_id').val();
        
        // Si no está en el campo oculto, intentar obtenerlo del atributo data del modal
        if (!parentUniqueId) {
            parentUniqueId = $('#articlesModal').data('current-unique-id');
            console.log('Got parentUniqueId from modal data attribute:', parentUniqueId);
        }
        
        // Si aún no lo tenemos, intentar obtenerlo del botón que abrió el modal
        if (!parentUniqueId) {
            const modalButton = $('.add-articles-btn[data-target="#articlesModal"]').filter(':visible').first();
            if (modalButton.length) {
                parentUniqueId = modalButton.data('unique-id');
                console.log('Got parentUniqueId from visible button:', parentUniqueId);
            }
        }
        
        const code = $('#article_code').val();
        console.log('Final Parent ID:', parentUniqueId, 'Code:', code);
        
        if (!code) {
            alert('Por favor, ingrese un código de artículo.');
            return;
        }
        
        if (!parentUniqueId) {
            console.error('No se pudo determinar el ID del proceso');
            alert('Error: No se pudo identificar el proceso. Por favor, cierre el modal e inténtelo de nuevo.');
            return;
        }
        
        const articleUniqueId = `art_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        const description = $('#article_description').val();
        const group = $('#article_group').val();
        
        console.log('Adding article:', {articleUniqueId, code, description, group, parentUniqueId});
        addArticleRowToModal(articleUniqueId, code, description, group);
        addArticleHiddenInputs(parentUniqueId, articleUniqueId, code, description, group);
        $('#article_code, #article_description, #article_group').val('');
        $('#article_code').focus();
    });
    
    $('#articles_list_in_modal').on('click', '.remove-article-from-modal', function() {
        const articleUniqueId = $(this).closest('tr').data('article-unique-id');
        $(`.article-group[data-article-unique-id="${articleUniqueId}"]`).remove();
        $(this).closest('tr').remove();
        updateNoArticlesMessage();
    });

    function addArticleRowToModal(articleUniqueId, code, description, group) {
        const newRow = `
            <tr data-article-unique-id="${articleUniqueId}">
                <td>${escapeHTML(code)}</td>
                <td>${escapeHTML(description)}</td>
                <td>${escapeHTML(group)}</td>
                <td><button type="button" class="btn btn-xs btn-danger remove-article-from-modal"><i class="fas fa-times"></i></button></td>
            </tr>`;
        $('#articles_list_in_modal').append(newRow);
        updateNoArticlesMessage();
    }

    function addArticleHiddenInputs(parentUniqueId, articleUniqueId, code, description, group) {
        const inputs = `
            <div class="article-group" data-parent-unique-id="${parentUniqueId}" data-article-unique-id="${articleUniqueId}">
                <input type="hidden" name="articles[${parentUniqueId}][${articleUniqueId}][code]" value="${escapeHTML(code)}">
                <input type="hidden" name="articles[${parentUniqueId}][${articleUniqueId}][description]" value="${escapeHTML(description)}">
                <input type="hidden" name="articles[${parentUniqueId}][${articleUniqueId}][group]" value="${escapeHTML(group)}">
            </div>`;
        $('#articles_hidden_inputs_container').append(inputs);
    }
    
    updateNoProcessesMessage();
    
    // Cargar los artículos iniciales desde los datos JSON
    if (articlesData && typeof articlesData === 'object' && !Array.isArray(articlesData)) {
        // Solo procesar las claves que contienen arrays de artículos
        Object.entries(articlesData).forEach(function([pivotId, articles]) {
            // Verificar que pivotId sea numérico y articles sea un array
            if (!isNaN(pivotId) && Array.isArray(articles)) {
                articles.forEach(function(article) {
                    if (article && typeof article === 'object' && article.id) {
                        const articleUniqueId = `db_${article.id}`;
                        const code = article.code || '';
                        const description = article.description || '';
                        const group = article.group || '';
                        addArticleHiddenInputs(pivotId, articleUniqueId, code, description, group);
                    }
                });
            }
        });
    }
});
</script>
@endpush
