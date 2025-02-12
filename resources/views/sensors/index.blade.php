@extends('layouts.admin')

@section('title', __('Sensors'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => request()->route('id')]) }}">
                {{ __('Production Lines') }}
            </a>
        </li>
        <li class="breadcrumb-item">{{ __('Sensors') }}</li>
    </ul>
@endsection

@section('content')
<div class="row mt-3">
    <div class="col-lg-12">

        {{-- Tarjeta principal sin borde y con sombra --}}
        <div class="card border-0 shadow">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">{{ __('X-Smart Eco System') }}</h4>
                {{-- Si deseas un botón extra en la cabecera, podrías ponerlo aquí --}}
            </div>

            <div class="card-body">
                {{-- Estilo para las imágenes dentro de las cards --}}
                <style>
                    .img-card {
                        max-height: 180px;
                        object-fit: cover;
                    }
                </style>

                {{-- Grid de tarjetas --}}
                <div class="row row-cols-1 row-cols-md-3 row-cols-xl-4 g-4">
                    {{-- Card Barcoders --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/Designer.jpeg" class="card-img-top img-card" alt="Barcoders">
                            <div class="card-body">
                                <h5 class="card-title">Barcoders</h5>
                                <p class="card-text">Administrar listado de Lectores.</p>
                                <a href="{{ route('barcodes.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Barcoders --}}

                    {{-- Card Modbuses --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/basculas.webp" class="card-img-top img-card" alt="Modbuses">
                            <div class="card-body">
                                <h5 class="card-title">Básculas</h5>
                                <p class="card-text">Administrar listado de Básculas.</p>
                                <a href="{{ route('modbuses.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Modbuses --}}

                    {{-- Card Smart Sensors --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/sensors.webp" class="card-img-top img-card" alt="Smart Sensors">
                            <div class="card-body">
                                <h5 class="card-title">Sensores Recuento y Estado</h5>
                                <p class="card-text">Administrar listado de Sensorica.</p>
                                <a href="{{ route('smartsensors.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Smart Sensors --}}

                    {{-- Card RFID Reader --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/rfid.webp" class="card-img-top img-card" alt="RFID Reader">
                            <div class="card-body">
                                <h5 class="card-title">RFID Reader</h5>
                                <p class="card-text">Administrar listado de Antenas RFID.</p>
                                <a href="{{ route('rfid.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card RFID Reader --}}

                    {{-- Card Categorías RFID --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/rfid_categories.webp" class="card-img-top img-card" alt="RFID Categories">
                            <div class="card-body">
                                <h5 class="card-title">RFID Categorías</h5>
                                <p class="card-text">Grupos de EPC RFID (Para poder hacer un paquete!).</p>
                                <a href="{{ route('rfid.categories.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Categorías RFID --}}

                    {{-- Card Dispositivos RFID --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/rfid_devices.webp" class="card-img-top img-card" alt="RFID Devices">
                            <div class="card-body">
                                <h5 class="card-title">RFID Dispositivos</h5>
                                <p class="card-text">Cada EPC es una confection o dispozitivo identificado por RFID.</p>
                                <a href="{{ route('rfid.devices.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Dispositivos RFID --}}

                    {{-- Card Confecciones (Nuevo Recurso: RfidColor) --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/color.webp" class="card-img-top img-card" alt="Confecciones">
                            <div class="card-body">
                                <h5 class="card-title">Colores Confecciones</h5>
                                <p class="card-text">Administrar listado de Colores RFID.</p>
                                <a href="{{ route('rfid.colors.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Confecciones --}}

                    {{-- Card Monitor OEE --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/oee.webp" class="card-img-top img-card" alt="Monitor OEE">
                            <div class="card-body">
                                <h5 class="card-title">Monitor OEE</h5>
                                <p class="card-text">Administrar listado de Monitor OEE.</p>
                                <a href="{{ route('oee.index', ['production_line_id' => request()->route('id')]) }}"
                                   class="btn btn-primary">
                                   Ver más
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card Monitor OEE --}}

                    {{-- Card WhatsApp --}}
                    <div class="col">
                        <div class="card h-100">
                            <img src="/necesarios/whatsapp.webp" class="card-img-top img-card" alt="WhatsApp">
                            <div class="card-body">
                                <h5 class="card-title">WhatsApp</h5>
                                <p class="card-text">Enviar notificaciones a través de WhatsApp.</p>
                                <a href="{{ route('whatsapp.notifications') }}" class="btn btn-success">
                                    Enviar Notificación
                                </a>
                            </div>
                        </div>
                    </div>
                    {{-- Fin card WhatsApp --}}
                </div>{{-- row --}}
            </div>{{-- card-body --}}
        </div>{{-- card --}}
    </div>{{-- col-lg-12 --}}
</div>{{-- row --}}
@endsection
