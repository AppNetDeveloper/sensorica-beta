@extends('layouts.admin')

@section('title', __('Crear Nuevo Barcode'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productionlines.index', ['customer_id' => $customer_id]) }}">{{ __('Production Lines') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('barcodes.index', ['production_line_id' => $production_line_id]) }}">{{ __('Barcodes') }}</a></li>
        <li class="breadcrumb-item">{{ __('Crear Nuevo Barcode') }}</li>
    </ul>
@endsection

@php
    // Importar la clase Str
    use Illuminate\Support\Str;
    
    // Generar token aleatorio único para MQTT topic
    $randomToken = Str::random(20);
    $mqttTopic = "c/barcoder/{$randomToken}";
    
    // Generar ID de máquina aleatorio
    $machineId = 'M' . rand(1000, 9999) . Str::random(5);
@endphp

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ __('Crear Nuevo Barcode') }}</h4>
                    <form action="{{ route('barcodes.store', ['production_line_id' => $production_line_id]) }}" method="POST">
                        @csrf

                        <input type="hidden" name="production_line_id" value="{{ $production_line_id }}">

                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="mqtt_topic_barcodes">{{ __('MQTT Topic Barcodes') }}</label>
                            <input type="text" class="form-control" id="mqtt_topic_barcodes" name="mqtt_topic_barcodes" value="{{ $mqttTopic }}" required>
                            <small class="form-text text-muted">Generado automáticamente en formato c/barcoder/{token}</small>
                        </div>

                        <div class="form-group">
                            <label for="machine_id">{{ __('Machine ID') }}</label>
                            <input type="text" class="form-control" id="machine_id" name="machine_id" value="{{ $machineId }}">
                            <small class="form-text text-muted">Generado automáticamente</small>
                        </div>

                        <div class="form-group">
                            <label for="ope_id">{{ __('OPE ID') }}</label>
                            <input type="text" class="form-control" id="ope_id" name="ope_id" value="Envasado">
                            <small class="form-text text-muted">Valor predeterminado</small>
                        </div>

                        <div class="form-group">
                            <label for="order_notice">{{ __('Order Notice') }}</label>
                            <textarea class="form-control" id="order_notice" name="order_notice">{}</textarea>
                            <small class="form-text text-muted">Valor predeterminado</small>
                        </div>

                        <div class="form-group">
                            <label for="last_barcode">{{ __('Last Barcode') }}</label>
                            <input type="text" class="form-control" id="last_barcode" name="last_barcode" value="FINALIZADO">
                            <small class="form-text text-muted">Valor predeterminado</small>
                        </div>

                        <div class="form-group">
                            <label for="ip_zerotier">{{ __('IP Zerotier') }}</label>
                            <input type="text" class="form-control" id="ip_zerotier" name="ip_zerotier">
                        </div>

                        <div class="form-group">
                            <label for="user_ssh">{{ __('User SSH') }}</label>
                            <input type="text" class="form-control" id="user_ssh" name="user_ssh">
                        </div>

                        <div class="form-group">
                            <label for="port_ssh">{{ __('Port SSH') }}</label>
                            <input type="text" class="form-control" id="port_ssh" name="port_ssh">
                        </div>

                        <div class="form-group">
                            <label for="user_ssh_password">{{ __('User SSH Password') }}</label>
                            <input type="password" class="form-control" id="user_ssh_password" name="user_ssh_password">
                        </div>

                        <div class="form-group">
                            <label for="ip_barcoder">{{ __('IP Barcoder') }}</label>
                            <input type="text" class="form-control" id="ip_barcoder" name="ip_barcoder">
                        </div>

                        <div class="form-group">
                            <label for="port_barcoder">{{ __('Port Barcoder') }}</label>
                            <input type="text" class="form-control" id="port_barcoder" name="port_barcoder">
                        </div>

                        <div class="form-group">
                            <label for="conexion_type">{{ __('Conexion Type') }}</label>
                            <input type="text" class="form-control" id="conexion_type" name="conexion_type" value="1">
                            <small class="form-text text-muted">Valor predeterminado</small>
                        </div>

                        <div class="form-group">
                            <label for="iniciar_model">{{ __('Iniciar Model') }}</label>
                            <input type="text" class="form-control" id="iniciar_model" name="iniciar_model" value="INICIAR">
                        </div>

                        <div class="form-group">
                            <label for="sended">{{ __('Sended') }}</label>
                            <input type="number" class="form-control" id="sended" name="sended" value="0">
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('barcodes.index', ['production_line_id' => $production_line_id]) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
