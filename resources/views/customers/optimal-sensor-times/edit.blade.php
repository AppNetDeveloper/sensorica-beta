@extends('layouts.admin')

@section('title', __('Edit Optimal Time'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.optimal-sensor-times.index', $customer->id) }}">{{ $customer->name }} - {{ __('Optimal Times') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-8 offset-lg-2">
        <div class="card border-0 shadow">
            <div class="card-header border-0">
                <h4 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>{{ __('Edit Optimal Time') }}
                </h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('customers.optimal-sensor-times.update', [$customer->id, $optimalSensorTime->id]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('Sensor') }}</label>
                            <input type="text" class="form-control" value="{{ $optimalSensorTime->sensor ? $optimalSensorTime->sensor->name : '-' }}" readonly>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('Production Line') }}</label>
                            <input type="text" class="form-control" value="{{ $optimalSensorTime->productionLine ? $optimalSensorTime->productionLine->name : '-' }}" readonly>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('Product') }}</label>
                            <input type="text" class="form-control" value="{{ $optimalSensorTime->productList ? $optimalSensorTime->productList->name : $optimalSensorTime->model_product }}" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Sensor Type') }}</label>
                            <div class="form-control-plaintext">
                                @switch($optimalSensorTime->sensor_type)
                                    @case(0)
                                        <span class="badge bg-primary">{{ __('Sensor de conteo') }}</span>
                                        @break
                                    @case(1)
                                        <span class="badge bg-info">{{ __('Sensor materia prima') }}</span>
                                        @break
                                    @case(2)
                                        <span class="badge bg-warning">Raw</span>
                                        @break
                                    @case(3)
                                        <span class="badge bg-danger">{{ __('Incidente') }}</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">N/A</span>
                                @endswitch
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Optimal Time') }} ({{ __('seconds') }}) <span class="text-danger">*</span></label>
                            <input type="number" 
                                   name="optimal_time" 
                                   class="form-control @error('optimal_time') is-invalid @enderror" 
                                   value="{{ old('optimal_time', $optimalSensorTime->optimal_time) }}" 
                                   step="0.01" 
                                   min="0.01"
                                   required>
                            @error('optimal_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr class="my-4">
                    <h5 class="mb-3"><i class="fas fa-microchip me-2"></i>{{ __('Sensor Configuration') }}</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Sensor Optimal Time') }} ({{ __('seconds') }})</label>
                            <input type="number" 
                                   name="sensor_optimal_production_time" 
                                   class="form-control @error('sensor_optimal_production_time') is-invalid @enderror" 
                                   value="{{ old('sensor_optimal_production_time', $optimalSensorTime->sensor ? $optimalSensorTime->sensor->optimal_production_time : 0) }}" 
                                   step="0.01" 
                                   min="0">
                            <small class="form-text text-muted">{{ __('Current optimal production time configured in the sensor') }}</small>
                            @error('sensor_optimal_production_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Reduced Speed Multiplier') }}</label>
                            <input type="number" 
                                   name="sensor_reduced_speed_multiplier" 
                                   class="form-control @error('sensor_reduced_speed_multiplier') is-invalid @enderror" 
                                   value="{{ old('sensor_reduced_speed_multiplier', $optimalSensorTime->sensor ? $optimalSensorTime->sensor->reduced_speed_time_multiplier : 2) }}" 
                                   step="0.1" 
                                   min="1"
                                   max="10">
                            <small class="form-text text-muted">{{ __('Multiplier for stop time calculation (x2, x3, x4...)') }}</small>
                            @error('sensor_reduced_speed_multiplier')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Min Correction Percentage') }} (%)</label>
                            <input type="number" 
                                   name="min_correction_percentage" 
                                   class="form-control @error('min_correction_percentage') is-invalid @enderror" 
                                   value="{{ old('min_correction_percentage', $optimalSensorTime->sensor ? $optimalSensorTime->sensor->min_correction_percentage : 20) }}" 
                                   step="0.01" 
                                   min="0"
                                   max="100">
                            <small class="form-text text-muted">{{ __('Minimum threshold to start updating optimal time when it increases (e.g., 20%)') }}</small>
                            @error('min_correction_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Max Correction Percentage') }} (%)</label>
                            <input type="number" 
                                   name="max_correction_percentage" 
                                   class="form-control @error('max_correction_percentage') is-invalid @enderror" 
                                   value="{{ old('max_correction_percentage', $optimalSensorTime->sensor ? $optimalSensorTime->sensor->max_correction_percentage : 98) }}" 
                                   step="0.01" 
                                   min="0"
                                   max="100">
                            <small class="form-text text-muted">{{ __('Maximum limit factor when updating increased time (e.g., 98% to cap at 98% of calculated value)') }}</small>
                            @error('max_correction_percentage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>{{ __('Stop Time Calculation') }}:</strong> 
                                {{ __('Sensor Optimal Time') }} × {{ __('Multiplier') }} = {{ __('Stop Time') }}
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="alert alert-warning">
                                <i class="fas fa-percentage me-2"></i>
                                <strong>{{ __('Correction Percentages Logic') }}:</strong><br>
                                {{ __('Updates optimal time only if new calculation is lower OR if higher and exceeds Min Correction % threshold, applying Max Correction % as limit.') }}
                            </div>
                        </div>

                        <div class="col-md-12">
                            <h6 class="mb-3"><i class="fas fa-cog me-2"></i>{{ __('Automatic Calculation Configuration') }}</h6>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="auto_optimal_time_enabled" 
                                               name="auto_optimal_time_enabled" 
                                               value="1"
                                               {{ ($optimalSensorTime->sensor && $optimalSensorTime->sensor->auto_optimal_time_enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="auto_optimal_time_enabled">
                                            {{ __('Enable Automatic Optimal Time Calculation') }}
                                        </label>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        {{ __('Enable automatic calculation and storage in optimal_sensor_times and product_lists') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="auto_update_sensor_optimal_time" 
                                               name="auto_update_sensor_optimal_time" 
                                               value="1"
                                               {{ ($optimalSensorTime->sensor && $optimalSensorTime->sensor->auto_update_sensor_optimal_time) ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold" for="auto_update_sensor_optimal_time">
                                            {{ __('Allow Auto-Update of Sensor Optimal Time') }}
                                        </label>
                                    </div>
                                    <small class="form-text text-muted d-block mt-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        {{ __('Allow the automatic calculation to update the optimal_production_time field in sensors table') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('customers.optimal-sensor-times.index', $customer->id) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> {{ __('Update') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
