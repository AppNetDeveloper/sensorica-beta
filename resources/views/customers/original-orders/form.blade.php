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
                                                        <th class="text-center">@lang('Finished')</th>
                                                        <th class="text-right">@lang('Actions')</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="processes_list">
                                                    <script>
                                                        console.log('%c--- INICIO DEBUG RENDERIZADO DE PROCESOS ---', 'color: blue; font-weight: bold;');
                                                        console.log('Datos completos de la orden (incluye procesos):', @json($originalOrder));
                                                    </script>
                                                    @php
                                                        $processIdsToRender = old('processes', $originalOrder->processes->pluck('id')->all());
                                                    @endphp

                                                    @if (!empty($processIdsToRender))
                                                        @foreach ($processIdsToRender as $processId)
                                                            @php
                                                                $process = $processes->firstWhere('id', $processId);
                                                                if (!$process) continue;

                                                                $dbProcess = $originalOrder->processes->firstWhere('id', $processId);
                                                                $dbFinished = $dbProcess ? $dbProcess->pivot->finished : false;
                                                                $isFinished = old('processes_finished.' . $processId, $dbFinished);
                                                            @endphp
                                                            
                                                            <script>
                                                                // Script de depuración para este proceso
                                                                console.groupCollapsed('Debug Proceso: {{ addslashes($process->name) }} (ID: {{ $processId }})');
                                                                console.log('Valor de `finished` en BD (crudo):', @json($dbFinished));
                                                                console.log('Tipo de dato en BD:', '{{ gettype($dbFinished) }}');
                                                                console.log('Valor `isFinished` final (usado en @checked):', @json($isFinished));
                                                                console.log('Tipo de dato final:', '{{ gettype($isFinished) }}');
                                                                console.log('Objeto `pivot` completo:', @json($dbProcess->pivot ?? null));
                                                                console.groupEnd();
                                                            </script>

                                                            <tr data-process-id="{{ $process->id }}">
                                                                <td>{{ $process->code }}</td>
                                                                <td>{{ $process->name }}</td>
                                                                <td class="text-center">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox"
                                                                               class="custom-control-input"
                                                                               id="finished_{{ $process->id }}"
                                                                               name="processes_finished[{{ $process->id }}]"
                                                                               value="1"
                                                                               {{ $isFinished ? 'checked' : '' }}>
                                                                        <label class="custom-control-label" for="finished_{{ $process->id }}"></label>
                                                                    </div>
                                                                </td>
                                                                <td class="text-right process-actions">
                                                                    <button type="button" class="btn btn-sm btn-danger remove-process">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </td>
                                                                <input type="hidden" name="processes[]" value="{{ $process->id }}">
                                                            </tr>
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        <div id="no_processes" class="text-center p-3 {{ !empty($processIdsToRender) ? 'd-none' : '' }}">
                                            <p class="text-muted mb-0">@lang('No services added yet. Use the selector above to add services.')</p>
                                        </div>
                                        
                                        <!-- El contenedor de inputs ocultos ya no es necesario. -->
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
                                    <h5 class="modal-title" id="articlesModalLabel">@lang('Add Articles')</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" id="current_process_id">
                                    <input type="hidden" id="current_unique_id">
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <h6 id="process_name_display"></h6>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="article_code">@lang('Article Code')</label>
                                                <input type="text" class="form-control" id="article_code" placeholder="@lang('Enter article code')">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="article_description">@lang('Description')</label>
                                                <input type="text" class="form-control" id="article_description" placeholder="@lang('Enter description')">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="article_group">@lang('Group')</label>
                                                <input type="text" class="form-control" id="article_group" placeholder="@lang('Group')">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <button type="button" id="add_article_btn" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> @lang('Add Article')
                                            </button>
                                        </div>
                                    </div>
                                    
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
                                            <tbody id="articles_list">
                                                <!-- Articles will be added here dynamically -->
                                            </tbody>
                                        </table>
                                        <div id="no_articles" class="text-center p-3">
                                            <p class="text-muted mb-0">@lang('No articles added yet.')</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" id="close-articles-modal">@lang('Close')</button>
                                </div>
                            </div>
                        </div>
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
        // Función para actualizar la visibilidad del mensaje "No hay procesos"
        function updateNoProcessesMessage() {
            if ($('#processes_list tr').length === 0) {
                $('#no_processes').removeClass('d-none');
            } else {
                $('#no_processes').addClass('d-none');
            }
        }

        // Almacenar los procesos en una variable JS para un acceso más seguro
        const allProcesses = @json($processes->keyBy('id'));

        // 1. Añadir proceso a la tabla
        $('#add_process_btn').on('click', function() {
            const processId = $('#process_selector').val();
            if (!processId) {
                alert('Por favor, seleccione un proceso.');
                return;
            }

            // Evitar añadir duplicados
            if ($(`#processes_list tr[data-process-id="${processId}"]`).length > 0) {
                alert('Este proceso ya ha sido añadido.');
                return;
            }

            const process = allProcesses[processId];
            
            if (!process) {
                alert('Error: No se encontró el proceso seleccionado.');
                return;
            }

            const newRow = `
                <tr data-process-id="${process.id}">
                    <td>${process.code}</td>
                    <td>${process.name}</td>
                    <td class="text-center">
                        <div class="custom-control custom-switch">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="finished_${process.id}"
                                   name="processes_finished[${process.id}]"
                                   value="1">
                            <label class="custom-control-label" for="finished_${process.id}"></label>
                        </div>
                    </td>
                    <td class="text-right process-actions">
                        <button type="button" class="btn btn-sm btn-danger remove-process">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                    <input type="hidden" name="processes[]" value="${process.id}">
                </tr>
            `;

            $('#processes_list').append(newRow);
            updateNoProcessesMessage();
        });

        // 2. Eliminar proceso de la tabla
        $('#processes_list').on('click', '.remove-process', function() {
            $(this).closest('tr').remove();
            updateNoProcessesMessage();
        });

        // Inicializar el mensaje al cargar la página
        updateNoProcessesMessage();
    });
</script>
@endpush
