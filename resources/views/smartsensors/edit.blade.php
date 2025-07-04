@extends('layouts.admin')
@section('title', __('Edit Sensor'))
@section('content')
<div class="container">
    <h1>{{ __('Edit Sensor') }}</h1>
    <form action="{{ route('smartsensors.update', $sensor->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">{{ __('Sensor Name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ $sensor->name }}" required>
        </div>

        <div class="form-group">
            <label for="barcoder_id">{{ __('Barcoder') }}</label>
            <select name="barcoder_id" class="form-control" required>
                @foreach ($barcoders as $barcoder)
                    <option value="{{ $barcoder->id }}" {{ $sensor->barcoder_id == $barcoder->id ? 'selected' : '' }}>{{ $barcoder->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="sensor_type">{{ __('Sensor Type') }}</label>
            <select name="sensor_type" class="form-control" required>
                <option value="0" {{ $sensor->sensor_type == 0 ? 'selected' : '' }}>{{ __('Counting') }}</option>
                <option value="1" {{ $sensor->sensor_type == 1 ? 'selected' : '' }}>{{ __('Consumables Type Nets') }}</option>
                <option value="2" {{ $sensor->sensor_type == 2 ? 'selected' : '' }}>{{ __('Other Consumables Type Stickers Ink') }}</option>
                <option value="3" {{ $sensor->sensor_type == 3 ? 'selected' : '' }}>{{ __('Raw Material Type Tipping Palot') }}</option>
                <option value="4" {{ $sensor->sensor_type == 4 ? 'selected' : '' }}>{{ __('Process Breakdown') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="optimal_production_time">{{ __('Optimal Production Time') }}</label>
            <input type="number" name="optimal_production_time" class="form-control" value="{{ $sensor->optimal_production_time }}">
        </div>

        <div class="form-group">
            <label for="reduced_speed_time_multiplier">{{ __('Reduced Speed Time Multiplier') }}</label>
            <input type="number" name="reduced_speed_time_multiplier" class="form-control" value="{{ $sensor->reduced_speed_time_multiplier }}">
        </div>

        <div class="form-group">
            <label for="min_correction_percentage">{{ __('Min Correction Percentage') }}</label>
            <input type="text" class="form-control" id="min_correction_percentage" name="min_correction_percentage" value="{{ $sensor->min_correction_percentage }}">
        </div>

        <div class="form-group">
            <label for="max_correction_percentage">{{ __('Max Correction Percentage') }}</label>
            <input type="text" class="form-control" id="max_correction_percentage" name="max_correction_percentage" value="{{ $sensor->max_correction_percentage }}">
        </div>

        <div class="form-group">
            <label for="json_api">{{ __('JSON API') }}</label>
            <textarea name="json_api" class="form-control" rows="3">{{ $sensor->json_api }}</textarea>
            <small class="form-text text-muted">
                {{ __('Ejemplos de configuración:') }}<br>
                <strong>value</strong> - {{ __('Extrae el campo "value" (por defecto)') }}<br>
                <strong>medida</strong> - {{ __('Extrae el campo "medida" del JSON') }}<br>
                <strong>sensorDatas[0].value</strong> - {{ __('Extrae el campo "value" del primer elemento del array "sensorDatas"') }}<br>
                <strong>sensorDatas[?(@.flag=="REG137")].value</strong> - {{ __('Extrae el campo "value" del elemento con flag="REG137"') }}<br>
                <strong>datos.temperatura</strong> - {{ __('Extrae el campo anidado "temperatura" dentro del objeto "datos"') }}
            </small>
        </div>

        <div class="form-group">
            <label for="mqtt_topic_sensor">{{ __('MQTT Topic Sensor') }}</label>
            <input type="text" name="mqtt_topic_sensor" class="form-control" value="{{ $sensor->mqtt_topic_sensor }}" required>
        </div>

        <div class="form-group">
            <label for="mqtt_topic_1">{{ __('MQTT Topic Send') }}</label>
            <input type="text" name="mqtt_topic_1" class="form-control" value="{{ $sensor->mqtt_topic_1 }}" required>
        </div>

        <div class="form-group">
            <label for="function_model_0">{{ __('Function Model 0') }}</label>
            <select name="function_model_0" class="form-control" required>
                <option value="sendMqttValue0" {{ $sensor->function_model_0 == 'sendMqttValue0' ? 'selected' : '' }}>{{ __('sendMqttValue0') }}</option>
                <option value="none" {{ $sensor->function_model_0 == 'none' ? 'selected' : '' }}>{{ __('none') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="function_model_1">{{ __('Function Model 1') }}</label>
            <select name="function_model_1" class="form-control" required>
                <option value="sendMqttValue1" {{ $sensor->function_model_1 == 'sendMqttValue1' ? 'selected' : '' }}>{{ __('sendMqttValue1') }}</option>
                <option value="none" {{ $sensor->function_model_1 == 'none' ? 'selected' : '' }}>{{ __('none') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="count_total">{{ __('Total Counter') }}</label>
            <input type="number" name="count_total" class="form-control" value="{{ $sensor->count_total }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_0">{{ __('Total Counter 0') }}</label>
            <input type="number" name="count_total_0" class="form-control" value="{{ $sensor->count_total_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_1">{{ __('Total Counter 1') }}</label>
            <input type="number" name="count_total_1" class="form-control" value="{{ $sensor->count_total_1 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_0">{{ __('Shift Counter 0') }}</label>
            <input type="number" name="count_shift_0" class="form-control" value="{{ $sensor->count_shift_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_1">{{ __('Shift Counter 1') }}</label>
            <input type="number" name="count_shift_1" class="form-control" value="{{ $sensor->count_shift_1 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_0">{{ __('Order Counter 0') }}</label>
            <input type="number" name="count_order_0" class="form-control" value="{{ $sensor->count_order_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_1">{{ __('Order Counter 1') }}</label>
            <input type="number" name="count_order_1" class="form-control" value="{{ $sensor->count_order_1 }}" min="0">
        </div>

        <div class="form-group">
            <label for="invers_sensors">{{ __('Inverse Sensor') }}</label>
            <select name="invers_sensors" class="form-control">
                <option value="0" {{ $sensor->invers_sensors == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                <option value="1" {{ $sensor->invers_sensors == 1 ? 'selected' : '' }}>{{ __('Yes') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="downtime_count">{{ __('Downtime Count') }}</label>
            <input type="number" name="downtime_count" class="form-control" value="{{ $sensor->downtime_count }}" min="0">
        </div>

        <div class="form-group">
            <label for="unic_code_order">{{ __('Unique Order Code') }}</label>
            <input type="text" name="unic_code_order" class="form-control" value="{{ $sensor->unic_code_order }}">
        </div>

        <div class="form-group">
            <label for="shift_type">{{ __('Shift Type') }}</label>
            <input type="text" name="shift_type" class="form-control" value="{{ $sensor->shift_type }}">
        </div>

        <div class="form-group">
            <label for="productName">{{ __('Product Name') }}</label>
            <input type="text" name="productName" class="form-control" value="{{ $sensor->productName }}">
        </div>

        <div class="form-group">
            <label for="count_week_0">{{ __('Week Counter 0') }}</label>
            <input type="number" name="count_week_0" class="form-control" value="{{ $sensor->count_week_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_week_1">{{ __('Week Counter 1') }}</label>
            <input type="number" name="count_week_1" class="form-control" value="{{ $sensor->count_week_1 }}" min="0">
        </div>

        <button type="submit" class="btn btn-success">{{ __('Update Sensor') }}</button>
    </form>
</div>
@endsection