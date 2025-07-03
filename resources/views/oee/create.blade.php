@extends('layouts.admin')
@section('title', 'Crear Monitor OEE')

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
        <li class="breadcrumb-item">{{ __('Crear') }}</li>
    </ul>
@endsection

@section('content')
<div class="container">
    <h1>Crear Nuevo Monitor OEE</h1>
    <form action="{{ route('oee.store', ['production_line_id' => $production_line_id]) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="production_line_id">Línea de Producción</label>
            <input type="text" class="form-control" value="{{ $productionLine->name }}" readonly>
            <input type="hidden" name="production_line_id" value="{{ $productionLine->id }}">
        </div>
<!--vamos a anadir a los 3 input mqtt_topic, mqtt_topic2 y topic_oee un valor de topico unico generado por la linea de production y el id de la linea de production
pero sin dejar espacios entre el nombre de la linea y el id de la linea de production  ponemos nombre/id pero el nmbre cambiamos los espacios por- -->
        <div class="form-group">
            <label for="mqtt_topic">MQTT Topic</label>
            <input type="text" name="mqtt_topic" class="form-control" required value="{{ str_replace(' ', '-', $productionLine->name) }}/{{ $productionLine->id }}/mqtt">
        </div>

        <div class="form-group">
            <label for="mqtt_topic2">MQTT Topic 2 (Opcional)</label>
            <input type="text" name="mqtt_topic2" class="form-control" value="{{ str_replace(' ', '-', $productionLine->name) }}/{{ $productionLine->id }}/mqtt2">
        </div>

        <div class="form-group">
            <label for="topic_oee">Topic OEE (Opcional)</label>
            <input type="text" name="topic_oee" class="form-control" value="{{ str_replace(' ', '-', $productionLine->name) }}/{{ $productionLine->id }}/oee">
        </div>

        <div class="form-group">
            <label for="sensor_active">Sensor Activo</label>
            <select name="sensor_active" class="form-control" required>
                <option value="1">Sí</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="modbus_active">Modbus Activo</label>
            <select name="modbus_active" class="form-control" required>
                <option value="1">Sí</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="time_start_shift">Hora de Inicio del Turno (Opcional)</label>
            <input type="datetime-local" name="time_start_shift" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">Crear Monitor OEE</button>
    </form>
</div>
@endsection
