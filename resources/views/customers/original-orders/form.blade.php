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
                                                        <th width="100">@lang('Finished')</th>
                                                        <th width="80"></th>
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
                                                                
                                                                // Buscar el pivote por ID único si existe
                                                                $pivot = null;
                                                                if (isset($originalOrder->processes)) {
                                                                    $pivot = $originalOrder->processes->first(function($p) use ($uniqueId) {
                                                                        return $p->pivot->id == $uniqueId;
                                                                    });
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
                                                                    <div class="articles-container" style="display: none; padding: 10px; background-color: #f8f9fa; border-top: 1px solid #dee2e6;">
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
                                                                                </div>
                                                                            @empty
                                                                                <p class="text-muted mb-0">@lang('No articles associated with this process.')</p>
                                                                            @endforelse
                                                                        </div>
                                                                    </div>
                                                                </td>
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
                    
                    <!-- Container for hidden inputs that will be submitted with the form -->
                    <div id="articles_hidden_inputs_container" style="display: none;">
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
    .articles-container {
        /* display: none; is inline */
        padding: 10px;
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        /* transition: all 0.3s ease-in-out; */ /* Comentado para probar conflicto con jQuery slideToggle */
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    
    const allProcesses = @json($processes->keyBy('id'));
    
    // Mostrar en consola los datos de artículos recibidos del backend
    console.log('Datos de artículos recibidos del backend:', @json($articlesData));

    function updateNoProcessesMessage() {
        $('#no_processes').toggleClass('d-none', $('#processes_list tr').length > 0);
    }

    function escapeHTML(str) {
        if (!str) return '';
        return str.toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
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
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="finished_${uniqueId}" name="finished[${uniqueId}]" value="1">
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

    // Toggle para mostrar/ocultar artículos de un proceso
    $(document).on('click', '.toggle-articles', function() {
        console.log('Botón de artículos clickeado.');
        const processId = $(this).data('process-id');
        console.log('ID de proceso (uniqueId):', processId);

        const selector = `tr[data-process-row="${processId}"] .articles-container`;
        console.log('Buscando contenedor con el selector:', selector);

        const $articlesContainer = $(selector);
        console.log('Contenedor de artículos encontrado:', $articlesContainer);
        console.log('Número de contenedores encontrados:', $articlesContainer.length);

        if ($articlesContainer.length === 0) {
            console.error('¡Error! No se encontró el contenedor de artículos. Revisa el selector y el DOM.');
            return;
        }
        
        // Cerrar otros contenedores de artículos abiertos
        $('.articles-container').not($articlesContainer).slideUp(200);
        
        // Alternar el contenedor actual
        console.log('Ejecutando slideToggle...');
        $articlesContainer.slideToggle(200);
        console.log('slideToggle ejecutado.');
    });

    updateNoProcessesMessage();
});
</script>
@endpush
