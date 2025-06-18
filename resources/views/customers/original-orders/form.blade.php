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
                                {{-- Left column content remains the same --}}
                                <div class="form-group">
                                    <label for="order_id">@lang('Order ID') *</label>
                                    <input type="text" name="order_id" id="order_id" 
                                           class="form-control @error('order_id') is-invalid @enderror"
                                           value="{{ old('order_id', $originalOrder->order_id ?? '') }}" required>
                                    @error('order_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="client_number">@lang('Client Number')</label>
                                    <input type="text" name="client_number" id="client_number" 
                                           class="form-control @error('client_number') is-invalid @enderror"
                                           value="{{ old('client_number', $originalOrder->client_number ?? '') }}">
                                    @error('client_number')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="delivery_date">@lang('Delivery Date')</label>
                                    <input type="datetime-local" name="delivery_date" id="delivery_date" 
                                           class="form-control @error('delivery_date') is-invalid @enderror"
                                           value="{{ old('delivery_date', isset($originalOrder->delivery_date) ? $originalOrder->delivery_date->format('Y-m-d\TH:i') : '') }}">
                                    @error('delivery_date')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" name="in_stock" id="in_stock" 
                                               class="form-check-input @error('in_stock') is-invalid @enderror"
                                               value="1" 
                                               {{ old('in_stock', isset($originalOrder) && $originalOrder->in_stock ? 'checked' : '') }}>
                                        <label class="form-check-label" for="in_stock">@lang('In Stock')</label>
                                        <small class="form-text text-muted d-block">@lang('Check if the material is currently in stock')</small>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input type="checkbox" name="processed" id="processed" 
                                               class="form-check-input @error('processed') is-invalid @enderror"
                                               value="1" 
                                               {{ old('processed', isset($originalOrder) && $originalOrder->processed ? 'checked' : '') }}>
                                        <label class="form-check-label" for="processed">@lang('Mark as Processed')</label>
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
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
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
                                    {{-- * CORRECCIÓN CLAVE: Se aplica el estilo de scroll directamente a este div y se elimina el div .table-responsive --}}
                                    <div class="card-body p-0" style="overflow-x: auto;">
                                        <table class="table table-hover mb-0" id="processes_table">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>@lang('Code')</th>
                                                    <th>@lang('Name')</th>
                                                    <th class="text-center">@lang('Finished')</th>
                                                    <th class="text-right">@lang('Actions')</th>
                                                </tr>
                                            </thead>
                                            <tbody id="processes_list">
                                                @php
                                                    $processesToRender = old('processes', $isEdit ? $originalOrder->processes->mapWithKeys(function ($process) {
                                                        return [$process->pivot->id => $process->id];
                                                    }) : []);
                                                @endphp

                                                @if ($processesToRender && count($processesToRender) > 0)
                                                    @foreach ($processesToRender as $uniqueId => $processId)
                                                        @php
                                                            $process = $processes->firstWhere('id', $processId);
                                                            if (!$process) continue;
                                                            
                                                            $pivot = null;
                                                            if ($isEdit && isset($originalOrder->processes)) {
                                                                $pivot = $originalOrder->processes->firstWhere('pivot.id', $uniqueId);
                                                            }
                                                            
                                                            $isFinished = isset(old('finished')[$uniqueId]) || 
                                                                ($pivot && $pivot->pivot->finished);
                                                        @endphp
                                                        <tr data-unique-id="{{ $uniqueId }}" data-process-id="{{ $process->id }}">
                                                            <td>{{ $process->code }}</td>
                                                            <td>{{ $process->name }}</td>
                                                            <td class="text-center">
                                                                <div class="custom-control custom-switch">
                                                                    <input type="checkbox" class="custom-control-input" 
                                                                           id="finished_{{ $uniqueId }}" 
                                                                           name="finished[{{ $uniqueId }}]" 
                                                                           value="1" 
                                                                           {{ $isFinished ? 'checked' : '' }}>
                                                                    <label class="custom-control-label" for="finished_{{ $uniqueId }}"></label>
                                                                </div>
                                                            </td>
                                                            <td class="text-right process-actions">
                                                                <button type="button" class="btn btn-sm btn-info toggle-articles mr-1" data-process-id="{{ $uniqueId }}" title="@lang('Show/Hide Articles')">
                                                                    <i class="fas fa-boxes"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-danger remove-process"><i class="fas fa-times"></i></button>
                                                            </td>
                                                            <input type="hidden" name="processes[{{ $uniqueId }}]" value="{{ $process->id }}">
                                                        </tr>
                                                        {{-- Fila para los artículos del proceso --}}
                                                        <tr class="process-articles" data-process-row="{{ $uniqueId }}">
                                                            <td colspan="4" class="p-0 border-0">
                                                                <div class="articles-container" style="display: none;">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <h6 class="mb-0">@lang('Related Articles')</h6>
                                                                    </div>
                                                                    <div class="articles-list" data-process-id="{{ $uniqueId }}">
                                                                        @php
                                                                            $allArticles = is_string($articlesData) ? json_decode($articlesData, true) : ($articlesData ?? []);
                                                                            $processArticles = $allArticles[$uniqueId] ?? [];
                                                                        @endphp
                                                                        
                                                                        @forelse($processArticles as $article)
                                                                            <div class="article-item mb-2 p-2 bg-white rounded border">
                                                                                <strong>@lang('Code'):</strong> {{ $article['code'] ?? 'N/A' }} | 
                                                                                <strong>@lang('Description'):</strong> {{ $article['description'] ?? 'N/A' }} | 
                                                                                <strong>@lang('Group'):</strong> {{ $article['group'] ?? 'N/A' }}

                                                                                <input type="hidden" name="articles[{{ $uniqueId }}][{{ $loop->index }}][code]" value="{{ $article['code'] ?? '' }}">
                                                                                <input type="hidden" name="articles[{{ $uniqueId }}][{{ $loop->index }}][description]" value="{{ $article['description'] ?? '' }}">
                                                                                <input type="hidden" name="articles[{{ $uniqueId }}][{{ $loop->index }}][group]" value="{{ $article['group'] ?? '' }}">
                                                                            </div>
                                                                        @empty
                                                                            <p class="text-muted mb-0">@lang('No articles associated with this process.')</p>
                                                                        @endforelse
                                                                    </div>

                                                                    <div class="add-article-form mt-3 pt-3 border-top">
                                                                        <div class="row">
                                                                            <div class="col-md-3"><input type="text" class="form-control form-control-sm new-article-code" placeholder="@lang('Code')"></div>
                                                                            <div class="col-md-4"><input type="text" class="form-control form-control-sm new-article-description" placeholder="@lang('Description')"></div>
                                                                            <div class="col-md-3"><input type="text" class="form-control form-control-sm new-article-group" placeholder="@lang('Group')"></div>
                                                                            <div class="col-md-2"><button type="button" class="btn btn-success btn-sm btn-block add-article-btn">@lang('Add')</button></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                        <div id="no_processes" class="text-center p-3 {{ !empty($processesToRender) ? 'd-none' : '' }}">
                                            <p class="text-muted mb-0">@lang('No services added yet. Use the selector above to add services.')</p>
                                        </div>
                                    </div>
                                </div>

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
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* El estilo personalizado para .table-responsive ya no es necesario, lo eliminamos. */
    
    #processes_table tbody tr {
        cursor: pointer;
    }
    
    #processes_table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    #processes_table th,
    #processes_table td {
        vertical-align: middle !important;
    }
    
    .process-actions {
        white-space: nowrap;
    }
    
    .articles-container {
        padding: 15px;
        background-color: #f1f4f8;
        border-top: 1px solid #dee2e6;
    }
    
    @media (max-width: 1700px) {
        .card-body > .row > .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
            margin-bottom: 20px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
// El Javascript no necesita cambios, se mantiene igual.
$(document).ready(function() {
    
    const allProcesses = @json($processes->keyBy('id'));
    const articlesData = @json($articlesData ?? new stdClass());

    function updateNoProcessesMessage() {
        const hasProcesses = $('#processes_list tr[data-process-id]').length > 0;
        $('#no_processes').toggleClass('d-none', hasProcesses);
    }

    $('#add_process_btn').on('click', function() {
        const processId = $('#process_selector').val();
        if (!processId) return;
        const process = allProcesses[processId];
        if (!process) return;

        const uniqueId = `new_${Date.now()}`;
        const newRowHtml = `
            <tr data-unique-id="${uniqueId}" data-process-id="${process.id}">
                <td>${process.code}</td>
                <td>${process.name}</td>
                <td class="text-center">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="finished_${uniqueId}" name="finished[${uniqueId}]" value="1">
                        <label class="custom-control-label" for="finished_${uniqueId}"></label>
                    </div>
                </td>
                <td class="text-right process-actions">
                    <button type="button" class="btn btn-sm btn-info toggle-articles mr-1" data-process-id="${uniqueId}" title="@lang('Show/Hide Articles')">
                        <i class="fas fa-boxes"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger remove-process"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="processes[${uniqueId}]" value="${process.id}">
                </td>
            </tr>
            <tr class="process-articles" data-process-row="${uniqueId}">
                <td colspan="4" class="p-0 border-0">
                    <div class="articles-container" style="display: none;">
                         <h6 class="mb-2">@lang('Related Articles')</h6>
                         <div class="articles-list" data-process-id="${uniqueId}">
                            <p class="text-muted mb-0">@lang('No articles associated with this process.')</p>
                         </div>
                         <div class="add-article-form mt-3 pt-3 border-top">
                             <div class="row">
                                 <div class="col-md-3"><input type="text" class="form-control form-control-sm new-article-code" placeholder="@lang('Code')"></div>
                                 <div class="col-md-4"><input type="text" class="form-control form-control-sm new-article-description" placeholder="@lang('Description')"></div>
                                 <div class="col-md-3"><input type="text" class="form-control form-control-sm new-article-group" placeholder="@lang('Group')"></div>
                                 <div class="col-md-2"><button type="button" class="btn btn-success btn-sm btn-block add-article-btn">@lang('Add')</button></div>
                             </div>
                         </div>
                    </div>
                </td>
            </tr>`;
        $('#processes_list').append(newRowHtml);
        $('#process_selector').val('');
        updateNoProcessesMessage();
    });

    $('#processes_list').on('click', '.remove-process', function(e) {
        e.stopPropagation();
        const row = $(this).closest('tr');
        const processRow = row.next('.process-articles');
        row.remove();
        processRow.remove();
        updateNoProcessesMessage();
    });

    $(document).on('click', '.toggle-articles', function(e) {
        e.stopPropagation();
        const uniqueId = $(this).data('process-id');
        const articlesRow = $(`tr.process-articles[data-process-row="${uniqueId}"]`);
        $('.process-articles').not(articlesRow).find('.articles-container').slideUp();
        articlesRow.find('.articles-container').slideToggle();
    });
    
    $(document).on('click', '.add-article-btn', function(e) {
        e.stopPropagation();
        const container = $(this).closest('.articles-container');
        const articlesList = container.find('.articles-list');
        const processUniqueId = articlesList.data('process-id');
        const codeInput = container.find('.new-article-code');
        const descriptionInput = container.find('.new-article-description');
        const groupInput = container.find('.new-article-group');
        const code = codeInput.val().trim();
        const description = descriptionInput.val().trim();
        const group = groupInput.val().trim();
        if (code === '') {
            alert("@lang('The article code cannot be empty.')");
            return;
        }
        articlesList.find('.text-muted').remove();
        const articleIndex = Date.now();
        const newArticleHtml = `
            <div class="article-item mb-2 p-2 bg-white rounded border">
                <strong>@lang('Code'):</strong> ${code} | 
                <strong>@lang('Description'):</strong> ${description} | 
                <strong>@lang('Group'):</strong> ${group}
                <input type="hidden" name="articles[${processUniqueId}][${articleIndex}][code]" value="${escapeHTML(code)}">
                <input type="hidden" name="articles[${processUniqueId}][${articleIndex}][description]" value="${escapeHTML(description)}">
                <input type="hidden" name="articles[${processUniqueId}][${articleIndex}][group]" value="${escapeHTML(group)}">
            </div>`;
        articlesList.append(newArticleHtml);
        codeInput.val('');
        descriptionInput.val('');
        groupInput.val('');
    });
    
    function escapeHTML(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
    updateNoProcessesMessage();
});
</script>
@endpush
