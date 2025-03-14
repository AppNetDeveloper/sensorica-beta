@extends('layouts.admin')
@section('title', __('Telegram Server'))

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
            <div class="card-body text-center">
                <h3 class="h5 mb-3"><i class="bi bi-telegram"></i> {{ __('Estado de Conexión') }}</h3>
                @if ($isConnected)
                    <p class="fw-bold text-success"><i class="bi bi-check-circle-fill"></i> {{ __('Telegram ya vinculado') }}</p>
                    <p class="text-secondary">{{ __('Tu aplicación está conectada con Telegram.') }}</p>
                @else
                    <p class="fw-bold text-danger"><i class="bi bi-x-circle-fill"></i> {{ __('Iniciar sesión en Telegram') }}</p>

                    @if (!session('phone')) 
                        {{-- Solicitar Código --}}
                        <form action="{{ route('telegram.requestCode') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="phone" class="form-label">{{ __('Número de Teléfono') }}</label>
                                <input type="text" name="phone" id="phone" class="form-control" placeholder="{{ __('Ingrese su número de teléfono') }}" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> {{ __('Solicitar Código') }}
                            </button>
                        </form>
                    @else
                        {{-- Ingresar Código de Verificación --}}
                        <div class="alert alert-info">
                            <p><i class="bi bi-info-circle"></i> {{ __('Código enviado a:') }} <strong>{{ session('phone') }}</strong></p>
                        </div>
                        <form action="{{ route('telegram.verifyCode') }}" method="POST">
                            @csrf
                            <input type="hidden" name="phone" value="{{ session('phone') }}">
                            <div class="mb-3">
                                <label for="code" class="form-label">{{ __('Código de Verificación') }}</label>
                                <input type="text" name="code" id="code" class="form-control" placeholder="{{ __('Ingrese el código recibido') }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">{{ __('Contraseña (si es requerida)') }}</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="{{ __('Ingrese su contraseña (si aplica)') }}">
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> {{ __('Validar Código') }}
                            </button>
                        </form>
                        </br>
                        <form action="{{ route('telegram.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-circle"></i> {{ __('Cerrar Sesión') }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        {{-- Desconectar Telegram --}}
        @if ($isConnected)
        <div class="card shadow-lg mb-4">
            <div class="card-body text-center">
                <h3 class="h5 mb-3"><i class="bi bi-box-arrow-right"></i> {{ __('Desconectar Telegram') }}</h3>
                <form action="{{ route('telegram.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> {{ __('Cerrar Sesión') }}
                    </button>
                </form>
            </div>
        </div>
        @endif

        {{-- Enviar Mensaje de Prueba --}}
        @if ($isConnected)
        <div class="card shadow-lg mb-4">
            <div class="card-body">
                <h3 class="h5 mb-3 text-center"><i class="bi bi-chat-dots"></i> {{ __('Enviar Mensaje de Prueba') }}</h3>
                <form action="{{ route('telegram.sendMessage') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="peer" class="form-label">{{ __('Destino') }}</label>
                        <input type="text" name="peer" id="peer" class="form-control" placeholder="{{ __('Ingrese el ID del destinatario') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">{{ __('Mensaje') }}</label>
                        <input type="text" name="message" id="message" class="form-control" placeholder="{{ __('Ingrese el mensaje') }}" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-send"></i> {{ __('Enviar Mensaje') }}
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
