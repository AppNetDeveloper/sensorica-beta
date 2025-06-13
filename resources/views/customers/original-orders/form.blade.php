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
                                    
                                    @if(isset($originalOrder) && $originalOrder->processes->isNotEmpty())
                                        <div class="mt-3">
                                            <label>@lang('Process Status')</label>
                                            <div class="list-group">
                                                @foreach($originalOrder->processes as $process)
                                                    @php
                                                        $pivot = $process->pivot;
                                                        $isFinished = $pivot->finished ?? false;
                                                    @endphp
                                                    <div class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                                            <span class="process-name me-3">{{ $process->name }}</span>
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox" class="custom-control-input process-finished-toggle" 
                                                                       id="toggle_finished_{{ $process->id }}" 
                                                                       data-process-id="{{ $process->id }}"
                                                                       {{ $isFinished ? 'checked' : '' }}>
                                                                <label class="custom-control-label" for="toggle_finished_{{ $process->id }}">
                                                                    {{ $isFinished ? __('Finished') : __('Pending') }}
                                                                </label>
                                                            </div>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" 
                                               id="processed" name="processed" value="1"
                                               {{ old('processed', $originalOrder->processed ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="processed">@lang('Mark as Processed')</label>
                                    </div>
                                    <small class="form-text text-muted">@lang('Indicates whether this order has been processed in the system.')</small>
                                </div>

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
<!-- Select2 -->
<link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
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
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>
<script>
    $(function () {
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: 'resolve'
        });

        // Handle process finished toggles
        $(document).on('change', '.process-finished-toggle', function() {
            const processId = $(this).data('process-id');
            const isFinished = $(this).is(':checked') ? '1' : '0';
            
            // Update the hidden input
            $(`#process_finished_${processId}`).val(isFinished);
            
            // Update the label
            $(this).next('label').text(
                isFinished === '1' ? '{{ __("Finished") }}' : '{{ __("Mark as Finished") }}'
            );
        });

        // Format JSON on page load
        try {
            const textarea = document.getElementById('order_details');
            const obj = JSON.parse(textarea.value);
            textarea.value = JSON.stringify(obj, null, 4);
        } catch (e) {
            console.error('Invalid JSON in order details', e);
        }
    });
</script>
@endpush
