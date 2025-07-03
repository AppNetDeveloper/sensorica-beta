@extends('layouts.admin')
@section('title', 'Crear Transformación de Sensor')

{{-- Migas de pan (breadcrumb) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => $customer_id]) }}">
                {{ __('Production Lines') }}
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('sensors.index', ['id' => $production_line_id]) }}">
                {{ __('Sensors') }}
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('sensor-transformations.index', ['production_line_id' => $production_line_id]) }}">
                {{ __('Transformación de Sensores') }}
            </a>
        </li>
        <li class="breadcrumb-item">{{ __('Crear') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow">
            <div class="card-header border-0">
                <h4 class="card-title mb-0">{{ __('Crear Transformación de Sensor') }}</h4>
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

                    <form action="{{ route('sensor-transformations.store') }}" method="POST">
                        @csrf
                        
                        <!-- Campo oculto para production_line_id -->
                        <input type="hidden" name="production_line_id" value="{{ $production_line_id }}">
                        
                        <!-- Línea de Producción (solo lectura) -->
                        <div class="form-group">
                            <label for="production_line">{{ __('Línea de Producción') }}</label>
                            <input type="text" class="form-control" id="production_line" value="{{ $productionLine->name }}" readonly>
                        </div>

                        <!-- Nombre -->
                        <div class="form-group">
                            <label for="name">{{ __('Nombre') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Nombre de la transformación">
                        </div>

                        <!-- Descripción -->
                        <div class="form-group">
                            <label for="description">{{ __('Descripción') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Descripción de la transformación">{{ old('description') }}</textarea>
                        </div>

                        <!-- Valor Mínimo -->
                        <div class="form-group">
                            <label for="min_value">{{ __('Valor Mínimo') }}</label>
                            <input type="number" step="0.01" class="form-control" id="min_value" name="min_value" value="{{ old('min_value') }}" placeholder="Valor mínimo">
                        </div>

                        <!-- Valor Intermedio -->
                        <div class="form-group">
                            <label for="mid_value">{{ __('Valor Intermedio') }}</label>
                            <input type="number" step="0.01" class="form-control" id="mid_value" name="mid_value" value="{{ old('mid_value') }}" placeholder="Valor intermedio">
                        </div>

                        <!-- Valor Máximo -->
                        <div class="form-group">
                            <label for="max_value">{{ __('Valor Máximo') }}</label>
                            <input type="number" step="0.01" class="form-control" id="max_value" name="max_value" value="{{ old('max_value') }}" placeholder="Valor máximo">
                        </div>

                        <hr>
                        <h5>{{ __('Valores de Salida Personalizados') }}</h5>
                        <p class="text-muted">{{ __('Define los valores que se enviarán para cada rango de transformación') }}</p>

                        <!-- Valor de Salida para Menor o Igual al Mínimo -->
                        <div class="form-group">
                            <label for="below_min_value_output">{{ __('Valor de Salida si ≤ Mínimo') }}</label>
                            <input type="text" class="form-control" id="below_min_value_output" name="below_min_value_output" value="{{ old('below_min_value_output', '0') }}" placeholder="Valor a enviar cuando es menor o igual al mínimo">
                            <small class="form-text text-muted">{{ __('Valor que se enviará cuando el valor del sensor sea menor o igual al valor mínimo') }}</small>
                        </div>

                        <!-- Valor de Salida para Entre Mínimo y Medio -->
                        <div class="form-group">
                            <label for="min_to_mid_value_output">{{ __('Valor de Salida si > Mínimo y ≤ Medio') }}</label>
                            <input type="text" class="form-control" id="min_to_mid_value_output" name="min_to_mid_value_output" value="{{ old('min_to_mid_value_output', '1') }}" placeholder="Valor a enviar cuando está entre mínimo y medio">
                            <small class="form-text text-muted">{{ __('Valor que se enviará cuando el valor del sensor esté entre el valor mínimo y el valor medio') }}</small>
                        </div>

                        <!-- Valor de Salida para Entre Medio y Máximo -->
                        <div class="form-group">
                            <label for="mid_to_max_value_output">{{ __('Valor de Salida si > Medio y ≤ Máximo') }}</label>
                            <input type="text" class="form-control" id="mid_to_max_value_output" name="mid_to_max_value_output" value="{{ old('mid_to_max_value_output', '2') }}" placeholder="Valor a enviar cuando está entre medio y máximo">
                            <small class="form-text text-muted">{{ __('Valor que se enviará cuando el valor del sensor esté entre el valor medio y el valor máximo') }}</small>
                        </div>

                        <!-- Valor de Salida para Mayor que Máximo -->
                        <div class="form-group">
                            <label for="above_max_value_output">{{ __('Valor de Salida si > Máximo') }}</label>
                            <input type="text" class="form-control" id="above_max_value_output" name="above_max_value_output" value="{{ old('above_max_value_output', '3') }}" placeholder="Valor a enviar cuando es mayor que el máximo">
                            <small class="form-text text-muted">{{ __('Valor que se enviará cuando el valor del sensor sea mayor que el valor máximo') }}</small>
                        </div>

                        <hr>

                        <!-- Tópico de Entrada -->
                        <div class="form-group">
                            <label for="input_topic">{{ __('Tópico de Entrada') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="input_topic" name="input_topic" value="{{ old('input_topic') }}" placeholder="Ejemplo: sensor/temperatura/raw" required>
                            <small class="form-text text-muted">{{ __('Tópico MQTT de entrada para recibir datos del sensor') }}</small>
                        </div>

                        <!-- Tópico de Salida -->
                        <div class="form-group">
                            <label for="output_topic">{{ __('Tópico de Salida') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="output_topic" name="output_topic" value="{{ old('output_topic') }}" placeholder="Ejemplo: sensor/temperatura/transformed" required>
                            <small class="form-text text-muted">{{ __('Tópico MQTT de salida para enviar datos transformados') }}</small>
                        </div>

                        <!-- Estado Activo -->
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="active" name="active" value="1" {{ old('active') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="active">{{ __('Activo') }}</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('Guardar') }}
                            </button>
                            <a href="{{ route('sensor-transformations.index', ['production_line_id' => $production_line_id]) }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('Cancelar') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
