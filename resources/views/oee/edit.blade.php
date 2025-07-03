@extends('layouts.admin')
@section('title', 'Editar Monitor OEE')

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
            <a href="{{ route('oee.index', ['production_line_id' => $production_line_id]) }}">
                {{ __('Monitor OEE') }}
            </a>
        </li>
        <li class="breadcrumb-item">{{ __('Editar') }}</li>
    </ul>
@endsection

@section('content')
<div class="container">
    <h1>Editar Monitor OEE</h1>
    <form action="{{ route('oee.update', ['oee' => $monitorOee->id, 'production_line_id' => $production_line_id]) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="production_line_id">Línea de Producción</label>
            <select name="production_line_id" class="form-control" required>
                @foreach ($productionLines as $productionLine)
                    <option value="{{ $productionLine->id }}" {{ $monitorOee->production_line_id == $productionLine->id ? 'selected' : '' }}>{{ $productionLine->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="mqtt_topic">MQTT Topic</label>
            <input type="text" name="mqtt_topic" class="form-control" value="{{ $monitorOee->mqtt_topic }}" required>
        </div>

        <div class="form-group">
            <label for="mqtt_topic2">MQTT Topic 2 (Opcional)</label>
            <input type="text" name="mqtt_topic2" class="form-control" value="{{ $monitorOee->mqtt_topic2 }}">
        </div>

        <div class="form-group">
            <label for="topic_oee">Topic OEE (Opcional)</label>
            <input type="text" name="topic_oee" class="form-control" value="{{ $monitorOee->topic_oee }}">
        </div>

        <div class="form-group">
            <label for="sensor_active">Sensor Activo</label>
            <select name="sensor_active" class="form-control" required>
                <option value="1" {{ $monitorOee->sensor_active ? 'selected' : '' }}>Sí</option>
                <option value="0" {{ !$monitorOee->sensor_active ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="modbus_active">Modbus Activo</label>
            <select name="modbus_active" class="form-control" required>
                <option value="1" {{ $monitorOee->modbus_active ? 'selected' : '' }}>Sí</option>
                <option value="0" {{ !$monitorOee->modbus_active ? 'selected' : '' }}>No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="time_start_shift">Hora de Inicio del Turno (Opcional)</label>
            <input type="datetime-local" name="time_start_shift" class="form-control" value="{{ $monitorOee->time_start_shift }}">
        </div>

        <button type="submit" class="btn btn-success">Actualizar Monitor OEE</button>
    </form>
</div>
@endsection
