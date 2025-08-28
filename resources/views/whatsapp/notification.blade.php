@extends('layouts.admin')
@section('title', __('WhatsApp'))

@section('content')
<div class="row">
    <div class="col-lg-12">

        {{-- Alertas de sesión --}}
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Estado de Conexión --}}
        <div class="card shadow-lg mb-4">
            <div class="card-body text-center" id="connectionStatus">
                <h3 class="h5 mb-3"><i class="bi bi-whatsapp"></i> {{ __('Estado de Conexión') }}</h3>
                @if ($isLinked)
                    <p class="fw-bold text-success"><i class="bi bi-check-circle-fill"></i> {{ __('WhatsApp ya vinculado') }}</p>
                    <p class="text-secondary">{{ __('Tu aplicación está conectada con WhatsApp.') }}</p>
                @elseif ($qrCode)
                    <p class="fw-bold text-danger"><i class="bi bi-qr-code"></i> {{ __('Escanea el Código QR') }}</p>
                    <p class="text-secondary">
                        {{ __('Escanea el código QR con tu aplicación de WhatsApp para conectarte.') }}
                    </p>
                    <div class="text-center">
                        <img src="{{ $qrCode }}" alt="{{ __('Código QR de WhatsApp') }}" class="img-fluid mt-3 border rounded shadow-sm" style="max-width: 300px;">
                    </div>
                @else
                    <p class="fw-bold text-danger"><i class="bi bi-x-circle-fill"></i> {{ __('Error de Conexión') }}</p>
                    <p class="text-secondary">
                        {{ __('No se pudo obtener el estado de conexión. Intenta nuevamente más tarde.') }}
                    </p>
                @endif
            </div>
        </div>

        {{-- Desconectar WhatsApp --}}
        <div class="card shadow-lg mb-4">
            <div class="card-body text-center">
                <h3 class="h5 mb-3"><i class="bi bi-box-arrow-right"></i> {{ __('Desconectar WhatsApp') }}</h3>
                <form action="{{ route('whatsapp.disconnect') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> {{ __('Desconectar') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Teléfonos de Mantenimiento --}}
        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3 text-center"><i class="bi bi-tools"></i> {{ __('Teléfonos de Mantenimiento') }}</h3>
                <form action="{{ route('whatsapp.updateMaintenancePhones') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="maintenance_phones" class="form-label">{{ __('Números (separados por coma)') }}</label>
                        <input type="text" name="maintenance_phones" id="maintenance_phones" class="form-control" value="{{ $phoneNumberMaintenance }}" placeholder="346XXXXXXXX, 346YYYYYYYY">
                        <div class="form-text">{{ __('Ejemplo: 34611111111,34622222222') }}</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save"></i> {{ __('Guardar teléfonos') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Actualizar Número de Notificación --}}
        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3 text-center"><i class="bi bi-telephone"></i> {{ __('Actualizar Número de Notificación') }}</h3>
                <form action="{{ route('whatsapp.updatePhone') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">{{ __('Número de Notificación') }}</label>
                        <input type="text" name="phone_number" id="phone_number" class="form-control" value="{{ $phoneNumber }}" placeholder="{{ __('Ingrese el número de notificación') }}">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-arrow-repeat"></i> {{ __('Actualizar Número') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Enviar Mensaje de Prueba --}}
        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3 text-center"><i class="bi bi-chat-dots"></i> {{ __('Enviar Mensaje de Prueba') }}</h3>
                <form action="{{ route('whatsapp.sendTestMessage') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="test_phone_number" class="form-label">{{ __('Número de Teléfono') }}</label>
                        <input type="text" name="test_phone_number" id="test_phone_number" class="form-control" placeholder="{{ __('Ingrese el número de teléfono') }}">
                    </div>
                    <div class="mb-3">
                        <label for="test_message" class="form-label">{{ __('Mensaje') }}</label>
                        <input type="text" name="test_message" id="test_message" class="form-control" placeholder="{{ __('Ingrese el mensaje de prueba') }}">
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-send"></i> {{ __('Enviar Mensaje') }}
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

{{-- Script para refrescar el estado de conexión cada 15 segundos --}}
<script>
    function refreshConnectionState() {
        fetch('{{ route("whatsapp.status") }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            let html = `<h3 class="h5 mb-3"><i class="bi bi-whatsapp"></i> {{ __("Estado de Conexión") }}</h3>`;
            if (data.isLinked) {
                html += `<p class="fw-bold text-success"><i class="bi bi-check-circle-fill"></i> {{ __("WhatsApp ya vinculado") }}</p>
                         <p class="text-secondary">{{ __("Tu aplicación está conectada con WhatsApp.") }}</p>`;
            } else if (data.qrCode) {
                html += `<p class="fw-bold text-danger"><i class="bi bi-qr-code"></i> {{ __("Escanea el Código QR") }}</p>
                         <p class="text-secondary">{{ __("Escanea el código QR con tu aplicación de WhatsApp para conectarte.") }}</p>
                         <div class="text-center">
                             <img src="${data.qrCode}" alt="{{ __("Código QR de WhatsApp") }}" class="img-fluid mt-3 border rounded shadow-sm" style="max-width: 300px;">
                         </div>`;
            } else {
                html += `<p class="fw-bold text-danger"><i class="bi bi-x-circle-fill"></i> {{ __("Error de Conexión") }}</p>
                         <p class="text-secondary">{{ __("No se pudo obtener el estado de conexión. Intenta nuevamente más tarde.") }}</p>`;
            }
            document.getElementById('connectionStatus').innerHTML = html;
        })
        .catch(error => console.error('Error al refrescar el estado de conexión:', error));
    }

    setInterval(refreshConnectionState, 15000);
    refreshConnectionState();
</script>
@endsection
