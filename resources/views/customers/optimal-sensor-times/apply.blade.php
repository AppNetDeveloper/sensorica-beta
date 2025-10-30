@extends('layouts.admin')

@section('title', __('Apply to Sensor'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.optimal-sensor-times.index', $customer->id) }}">{{ $customer->name }} - {{ __('Optimal Times') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Apply to Sensor') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-8 offset-lg-2">
        <div class="card border-0 shadow">
            <div class="card-header border-0 bg-success text-white">
                <h4 class="card-title mb-0">
                    <i class="fas fa-arrow-right me-2"></i>{{ __('Apply Optimal Time to Sensor') }}
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

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>{{ __('This action will:') }}</strong>
                    <ul class="mb-0 mt-2">
                        <li>{{ __('Apply the optimal time') }} <strong>{{ number_format($optimalSensorTime->optimal_time, 2) }} {{ __('seconds') }}</strong> {{ __('to the sensor') }}</li>
                        <li>{{ __('Configure automatic calculation settings for the sensor') }}</li>
                    </ul>
                </div>

                <form action="{{ route('customers.optimal-sensor-times.apply.store', [$customer->id, $optimalSensorTime->id]) }}" method="POST">
                    @csrf

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">{{ __('Sensor') }}</label>
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-microchip me-2 text-primary"></i>
                                {{ $optimalSensorTime->sensor ? $optimalSensorTime->sensor->name : '-' }}
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">{{ __('Production Line') }}</label>
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-sitemap me-2 text-secondary"></i>
                                {{ $optimalSensorTime->productionLine ? $optimalSensorTime->productionLine->name : '-' }}
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">{{ __('Product') }}</label>
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-box me-2 text-info"></i>
                                {{ $optimalSensorTime->productList ? $optimalSensorTime->productList->name : $optimalSensorTime->model_product }}
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">{{ __('Optimal Time to Apply') }}</label>
                            <div class="p-3 bg-success bg-opacity-10 rounded border border-success">
                                <i class="fas fa-clock me-2 text-success"></i>
                                <strong class="text-success fs-5">{{ number_format($optimalSensorTime->optimal_time, 2) }} {{ __('seconds') }}</strong>
                            </div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">{{ __('Current Sensor Configuration') }}</label>
                            <div class="p-3 bg-light rounded">
                                @if($optimalSensorTime->sensor)
                                    <div class="mb-2">
                                        <strong>{{ __('Current optimal time') }}:</strong> 
                                        {{ number_format($optimalSensorTime->sensor->optimal_production_time ?? 0, 2) }} {{ __('seconds') }}
                                    </div>
                                    <div class="mb-2">
                                        <strong>{{ __('Auto calculation') }}:</strong> 
                                        @if($optimalSensorTime->sensor->auto_optimal_time_enabled)
                                            <span class="badge bg-success">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <strong>{{ __('Auto update') }}:</strong> 
                                        @if($optimalSensorTime->sensor->auto_update_sensor_optimal_time)
                                            <span class="badge bg-success">{{ __('Enabled') }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ __('Disabled') }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-muted">{{ __('Sensor not found') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="col-md-12">
                            <h5 class="mb-3"><i class="fas fa-cog me-2"></i>{{ __('Automatic Calculation Configuration') }}</h5>
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
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check"></i> {{ __('Apply to Sensor') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
