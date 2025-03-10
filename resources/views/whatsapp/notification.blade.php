@extends('layouts.admin')
@section('title', __('WhatsApp'))

@section('content')
    <div class="row">
        <div class="col-lg-12">



            {{-- Alertas de sesión (éxito/error) --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Tarjeta: Estado de Conexión --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5">{{ __('Estado de Conexión') }}</h2>
                    @if ($isLinked)
                        <p class="fw-bold text-success">{{ __('WhatsApp ya vinculado') }}</p>
                        <p class="text-secondary">{{ __('Tu aplicación ya está conectada con WhatsApp.') }}</p>
                    @elseif ($qrCode)
                        <p class="fw-bold text-danger">{{ __('Escanea el Código QR') }}</p>
                        <p class="text-secondary">
                            {{ __('Por favor, escanea el siguiente código QR con tu aplicación de WhatsApp para conectarte.') }}
                        </p>
                        <div class="text-center">
                            <img src="{{ $qrCode }}" alt="{{ __('Código QR de WhatsApp') }}" class="img-fluid mt-3">
                        </div>
                    @else
                        <p class="fw-bold text-danger">{{ __('Error') }}</p>
                        <p class="text-secondary">
                            {{ __('No se pudo obtener el estado de conexión. Intenta nuevamente más tarde.') }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Tarjeta: Desconectar WhatsApp --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5">{{ __('Desconectar WhatsApp') }}</h2>
                    <form action="{{ route('whatsapp.disconnect') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger">
                            {{ __('Desconectar') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Tarjeta: Actualizar Número de Notificación --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5">{{ __('Actualizar Número de Notificación') }}</h2>
                    <form action="{{ route('whatsapp.updatePhone') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="phone_number" class="form-label">{{ __('Número de Notificación') }}</label>
                            <input
                                type="text"
                                name="phone_number"
                                id="phone_number"
                                class="form-control"
                                value="{{ $phoneNumber }}"
                                placeholder="{{ __('Ingrese el número de notificación') }}"
                            >
                        </div>
                        <button type="submit" class="btn btn-outline-primary">
                            {{ __('Actualizar Número') }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Tarjeta: Enviar Mensaje de Prueba --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5">{{ __('Enviar Mensaje de Prueba') }}</h2>
                    <form action="{{ route('whatsapp.sendTestMessage') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="test_phone_number" class="form-label">{{ __('Número de Teléfono') }}</label>
                            <input
                                type="text"
                                name="test_phone_number"
                                id="test_phone_number"
                                class="form-control"
                                placeholder="{{ __('Ingrese el número de teléfono') }}"
                            >
                        </div>
                        <div class="mb-3">
                            <label for="test_message" class="form-label">{{ __('Mensaje') }}</label>
                            <input
                                type="text"
                                name="test_message"
                                id="test_message"
                                class="form-control"
                                placeholder="{{ __('Ingrese el mensaje de prueba') }}"
                            >
                        </div>
                        <button type="submit" class="btn btn-outline-success">
                            {{ __('Enviar Mensaje') }}
                        </button>
                    </form>
                </div>
            </div>

        </div>{{-- Fin col --}}
    </div>{{-- Fin row --}}
@endsection
