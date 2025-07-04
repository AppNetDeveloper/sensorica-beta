@extends('layouts.admin')
@section('title', __('Create Sensor'))
@section('content')
<div class="container">
    <h1>{{ __('Create New Sensor') }}</h1>
    <form action="{{ route('smartsensors.store', ['production_line_id' => $production_line_id]) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="name">{{ __('Sensor Name') }}</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="barcoder_id">{{ __('Barcoder') }}</label>
            <select name="barcoder_id" class="form-control" required>
                @foreach ($barcoders as $barcoder)
                    <option value="{{ $barcoder->id }}">{{ $barcoder->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="sensor_type">{{ __('Sensor Type') }}</label>
            <select name="sensor_type" class="form-control" required>
                <option value="0">{{ __('Counting') }}</option>
                <option value="1">{{ __('Consumables Type Nets Paper etc') }}</option>
                <option value="2">{{ __('Consumables Type Stickers Ink') }}</option>
                <option value="3">{{ __('Raw Material Type Tipping Palot Deposit') }}</option>
                <option value="4">{{ __('Process Breakdown') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="optimal_production_time">{{ __('Optimal Production Time') }}</label>
            <input type="number" name="optimal_production_time" class="form-control" min="0">
        </div>

        <div class="form-group">
            <label for="reduced_speed_time_multiplier">{{ __('Reduced Speed Time Multiplier') }}</label>
            <input type="number" name="reduced_speed_time_multiplier" class="form-control" min="0">
        </div>

        <div class="form-group">
            <label for="min_correction_percentage">{{ __('Min Correction Percentage') }}</label>
            <input type="text" class="form-control" id="min_correction_percentage" name="min_correction_percentage" value="20">
        </div>

        <div class="form-group">
            <label for="max_correction_percentage">{{ __('Max Correction Percentage') }}</label>
            <input type="text" class="form-control" id="max_correction_percentage" name="max_correction_percentage" value="98">
        </div>

        <div class="form-group">
            <label for="json_api">{{ __('JSON API') }}</label>
            <textarea name="json_api" class="form-control" rows="3"></textarea>
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
            <input type="text" name="mqtt_topic_sensor" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="mqtt_topic_1">{{ __('MQTT Topic Send (Example: c/cliente/pli/PLI03/sta/PLI03STA01/mac/PLI03STA01MAC03)') }}</label>
            <input type="text" name="mqtt_topic_1" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="function_model_0">{{ __('Function Model 0') }}</label>
            <select name="function_model_0" class="form-control" required>
                <option value="sendMqttValue0">{{ __('sendMqttValue0') }}</option>
                <option value="none">{{ __('none') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="function_model_1">{{ __('Function Model 1') }}</label>
            <select name="function_model_1" class="form-control" required>
                <option value="sendMqttValue1">{{ __('sendMqttValue1') }}</option>
                <option value="none">{{ __('none') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="count_total">{{ __('Total Counter') }}</label>
            <input type="number" name="count_total" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_0">{{ __('Total Counter 0') }}</label>
            <input type="number" name="count_total_0" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_1">{{ __('Total Counter 1') }}</label>
            <input type="number" name="count_total_1" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_0">{{ __('Shift Counter 0') }}</label>
            <input type="number" name="count_shift_0" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_1">{{ __('Shift Counter 1') }}</label>
            <input type="number" name="count_shift_1" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_0">{{ __('Order Counter 0') }}</label>
            <input type="number" name="count_order_0" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_1">{{ __('Order Counter 1') }}</label>
            <input type="number" name="count_order_1" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="invers_sensors">{{ __('Inverse Sensor') }}</label>
            <select name="invers_sensors" class="form-control">
                <option value="0">{{ __('No') }}</option>
                <option value="1">{{ __('Yes') }}</option>
            </select>
        </div>

        <div class="form-group">
            <label for="downtime_count">{{ __('Downtime Count') }}</label>
            <input type="number" name="downtime_count" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="unic_code_order">{{ __('Unique Order Code') }}</label>
            <input type="text" name="unic_code_order" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">{{ __('Create Sensor') }}</button>
    </form>
</div>
@endsection