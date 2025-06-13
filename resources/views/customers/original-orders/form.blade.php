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
                                    <div class="select2-purple">
                                        <select name="processes[]" id="processes" 
                                                class="select2" multiple="multiple" 
                                                data-placeholder="@lang('Select processes')" 
                                                data-dropdown-css-class="select2-purple" 
                                                style="width: 100%;" required>
                                            @foreach($processes as $process)
                                                @php
                                                    $isOptionSelected = (is_array(old('processes')) && in_array($process->id, old('processes'))) 
                                                                        || (!old('processes') && $isEdit && in_array($process->id, $selectedProcesses));
                                                @endphp
                                                <option value="{{ $process->id }}" 
                                                        {{ $isOptionSelected ? 'selected' : '' }}>
                                                    {{ $process->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <small class="form-text text-muted">@lang('Select all processes that should be associated with this order.')</small>
                                </div>
                                
                                @if(isset($originalOrder) && $originalOrder->processes->isNotEmpty())
                                    <div class="form-group mt-4">
                                        <h5 class="border-bottom pb-2">@lang('Process Status')</h5>
                                        <p class="text-muted small">@lang('Mark processes as finished when they are completed.')</p>
                                        <div class="list-group mt-3">
                                            @foreach($originalOrder->processes as $process)
                                                @php
                                                    // Determina si el proceso está finalizado basándose en si 'finished_at' tiene un valor
                                                    $isFinished = !is_null($process->pivot->finished_at);
                                                @endphp
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span class="process-name">{{ $process->name }}</span>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input process-finished-toggle"
                                                               id="toggle_finished_{{ $process->id }}"
                                                               data-process-id="{{ $process->id }}"
                                                               {{ $isFinished ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="toggle_finished_{{ $process->id }}">
                                                            {{ $isFinished ? __('Finished') : __('Mark as Finished') }}
                                                        </label>
                                                    </div>
                                                </div>
                                                {{-- Asegúrate que el valor inicial del input oculto también refleje el estado correcto --}}
                                                <input type="hidden" name="processes_finished[{{ $process->id }}]" value="{{ $isFinished ? '1' : '0' }}" id="process_finished_{{ $process->id }}">
                                            @endforeach
                                        </div>
                                    </div>
                                    
                                    <div class="form-group mt-4">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="processed" name="processed" value="1"
                                                   {{ old('processed', $originalOrder->processed ?? false) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="processed">@lang('Mark as Processed')</label>
                                        </div>
                                    <small class="form-text text-muted">@lang('Indicates whether this order has been processed in the system.')</small>
                                </div>
                                @endif

                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info"></i> @lang('Note'):</h5>
                                    @lang('The order details should be in valid JSON format. You can include any additional information about the order in this field.')
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
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
<!-- Select2 from CDN -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
<style>
    .select2-container--default .select2-selection--multiple {
        min-height: 38px;
        padding: 5px 10px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #6f42c1;
        border-color: #6f42c1;
        color: white;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.7);
    }
</style>
@endpush

@push('scripts')
<!-- jQuery (make sure it's loaded before Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Select2 from CDN -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded');
        
        // Initialize Select2
        if ($.fn.select2) {
            console.log('Initializing Select2');
            $('.select2').select2({
                theme: 'bootstrap4',
                width: 'resolve'
            });
        } else {
            console.error('Select2 is not loaded');
        }

        // Handle process finished toggles
        function updateProcessFinishedToggle(checkbox) {
            const $checkbox = $(checkbox);
            const processId = $checkbox.data('process-id');
            const isFinished = $checkbox.is(':checked') ? '1' : '0';
            
            console.log('Updating process:', processId, 'finished:', isFinished);
            
            // Update the hidden input
            const $hiddenInput = $(`#process_finished_${processId}`);
            if ($hiddenInput.length) {
                $hiddenInput.val(isFinished);
                console.log('Updated hidden input:', $hiddenInput.attr('name'), '=', $hiddenInput.val());
            } else {
                console.error('Hidden input not found for process:', processId);
            }
            
            // Update the label
            const $label = $checkbox.siblings('label');
            if ($label.length) {
                $label.text(isFinished === '1' ? '{{ __("Finished") }}' : '{{ __("Mark as Finished") }}');
                console.log('Updated label text to:', $label.text());
            } else {
                console.error('Label not found for checkbox:', checkbox);
            }
        }

        // Initialize toggle states on page load
        console.log('Initializing toggle states');
        $('.process-finished-toggle').each(function() {
            updateProcessFinishedToggle(this);
        });

        // Handle toggle changes
        $(document).on('change', '.process-finished-toggle', function() {
            console.log('Toggle changed:', this);
            updateProcessFinishedToggle(this);
            
            // Force form submission to test if the value is being sent
            // $('form').submit();
        });

        // Format JSON on page load
        try {
            const textarea = document.getElementById('order_details');
            if (textarea && textarea.value) {
                const obj = JSON.parse(textarea.value);
                textarea.value = JSON.stringify(obj, null, 4);
            }
        } catch (e) {
            console.error('Invalid JSON in order details', e);
        }
        
        // Debug: Log all hidden inputs
        console.log('All hidden inputs:');
        $('input[type="hidden"][name^="processes_finished"]').each(function() {
            console.log($(this).attr('name'), '=', $(this).val());
        });
    });
</script>
@endpush
