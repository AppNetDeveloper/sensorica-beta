@extends('layouts.admin')
@section('title', __('Edit Calendar Day') . ' - ' . $customer->name)
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item">{{ $customer->name }}</li>
        <li class="breadcrumb-item"><a href="{{ route('customers.work-calendars.index', $customer->id) }}">{{ __('Work Calendar') }}</a></li>
        <li class="breadcrumb-item">{{ __('Edit Calendar Day') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ __('Edit Calendar Day') }} - {{ \Carbon\Carbon::parse($calendar->calendar_date)->format('d/m/Y') }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.work-calendars.update', ['customer' => $customer->id, 'calendar' => $calendar->id]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="calendar_date" class="form-label">{{ __('Date') }} <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('calendar_date') is-invalid @enderror" 
                                       id="calendar_date" name="calendar_date" 
                                       value="{{ old('calendar_date', $calendar->calendar_date) }}" required>
                                @error('calendar_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6">
                                <label for="type" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" 
                                        id="type" name="type" required>
                                    <option value="" disabled>{{ __('Select type') }}</option>
                                    <option value="holiday" {{ old('type', $calendar->type) == 'holiday' ? 'selected' : '' }}>{{ __('Holiday') }}</option>
                                    <option value="maintenance" {{ old('type', $calendar->type) == 'maintenance' ? 'selected' : '' }}>{{ __('Maintenance') }}</option>
                                    <option value="vacation" {{ old('type', $calendar->type) == 'vacation' ? 'selected' : '' }}>{{ __('Vacation') }}</option>
                                    <option value="workday" {{ old('type', $calendar->type) == 'workday' ? 'selected' : '' }}>{{ __('Working Day') }}</option>
                                    <option value="weekend" {{ old('type', $calendar->type) == 'weekend' ? 'selected' : '' }}>{{ __('Weekend') }}</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" 
                                   value="{{ old('name', $calendar->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input @error('is_working_day') is-invalid @enderror" 
                                       type="checkbox" 
                                       id="is_working_day" 
                                       name="is_working_day" 
                                       value="1" 
                                       {{ old('is_working_day', $calendar->is_working_day) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_working_day">{{ __('Is Working Day?') }}</label>
                            </div>
                            @error('is_working_day')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('Toggle on if this is a working day, off if it is a non-working day') }}</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" 
                                      rows="3">{{ old('description', $calendar->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customers.work-calendars.index', $customer->id) }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> {{ __('Back') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> {{ __('Update') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function() {
        // Actualizar automáticamente el estado de is_working_day según el tipo seleccionado
        $('#type').change(function() {
            var type = $(this).val();
            var workingDayTypes = ['workday'];
            var isWorkingDay = workingDayTypes.includes(type);
            
            $('#is_working_day').prop('checked', isWorkingDay);
        });
    });
</script>
@endpush
