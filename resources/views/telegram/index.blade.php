@extends('layouts.admin')
@section('title', __('Telegram Server'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Telegram') }}</li>
    </ul>
@endsection

@section('content')
<div class="telegram-container">
    {{-- Header --}}
    <div class="tg-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center">
                    <div class="tg-header-icon me-3">
                        <i class="ti ti-brand-telegram"></i>
                    </div>
                    <div>
                        <h4 class="tg-title mb-1">{{ __('Telegram Server') }}</h4>
                        <p class="tg-subtitle mb-0">{{ __('Configure Telegram integration for notifications') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex justify-content-lg-end mt-3 mt-lg-0">
                    <div class="tg-status-badge" id="statusBadge">
                        @if ($isConnected)
                            <span class="badge-connected">
                                <i class="ti ti-circle-check-filled"></i> {{ __('Connected') }}
                            </span>
                        @else
                            <span class="badge-disconnected">
                                <i class="ti ti-circle-x-filled"></i> {{ __('Disconnected') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas de sesión --}}
    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-check me-2"></i> {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-alert-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        {{-- Columna Izquierda - Estado de Conexión --}}
        <div class="col-lg-5 mb-4">
            <div class="card tg-card tg-connection-card h-100">
                <div class="card-body">
                    <div class="tg-card-header mb-4">
                        <div class="tg-card-icon tg-icon-primary">
                            <i class="ti ti-plug-connected"></i>
                        </div>
                        <h5 class="tg-card-title">{{ __('Connection Status') }}</h5>
                    </div>

                    <div id="connectionStatus" class="text-center">
                        @if ($isConnected)
                            {{-- Estado Conectado --}}
                            <div class="tg-connected-state">
                                <div class="tg-connected-icon mb-3">
                                    <i class="ti ti-circle-check"></i>
                                </div>
                                <h5 class="text-success mb-2">{{ __('Telegram Connected') }}</h5>
                                <p class="text-muted mb-4">{{ __('Your application is connected with Telegram.') }}</p>

                                <form action="{{ route('telegram.logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-disconnect">
                                        <i class="ti ti-logout me-2"></i> {{ __('Disconnect') }}
                                    </button>
                                </form>
                            </div>
                        @else
                            {{-- Estado No Conectado --}}
                            <div class="tg-login-state">
                                <div class="tg-login-icon mb-3">
                                    <i class="ti ti-login"></i>
                                </div>
                                <h5 class="text-warning mb-2">{{ __('Login to Telegram') }}</h5>
                                <p class="text-muted mb-4">{{ __('Connect your Telegram account to receive notifications.') }}</p>

                                @if (!session('phone'))
                                    {{-- Formulario para solicitar código --}}
                                    <form action="{{ route('telegram.requestCode') }}" method="POST" class="tg-login-form">
                                        @csrf
                                        <div class="tg-input-group mb-4">
                                            <div class="tg-input-icon">
                                                <i class="ti ti-phone"></i>
                                            </div>
                                            <input type="text" name="phone" id="phone"
                                                   class="form-control tg-input"
                                                   placeholder="{{ __('Phone number with country code') }}"
                                                   required>
                                        </div>
                                        <button type="submit" class="btn btn-primary tg-btn-login w-100">
                                            <i class="ti ti-send me-2"></i> {{ __('Request Code') }}
                                        </button>
                                    </form>
                                @else
                                    {{-- Formulario para verificar código --}}
                                    <div class="tg-code-sent mb-4">
                                        <div class="alert alert-info d-flex align-items-center mb-0">
                                            <i class="ti ti-info-circle me-2"></i>
                                            <div>
                                                <strong>{{ __('Code sent to:') }}</strong><br>
                                                <span class="text-primary">{{ session('phone') }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <form action="{{ route('telegram.verifyCode') }}" method="POST" class="tg-verify-form">
                                        @csrf
                                        <input type="hidden" name="phone" value="{{ session('phone') }}">

                                        <div class="tg-input-group mb-3">
                                            <div class="tg-input-icon">
                                                <i class="ti ti-key"></i>
                                            </div>
                                            <input type="text" name="code" id="code"
                                                   class="form-control tg-input"
                                                   placeholder="{{ __('Verification code') }}"
                                                   required>
                                        </div>

                                        <div class="tg-input-group mb-4">
                                            <div class="tg-input-icon">
                                                <i class="ti ti-lock"></i>
                                            </div>
                                            <input type="password" name="password" id="password"
                                                   class="form-control tg-input"
                                                   placeholder="{{ __('Password (if required)') }}">
                                        </div>

                                        <button type="submit" class="btn btn-success tg-btn-verify w-100 mb-3">
                                            <i class="ti ti-check me-2"></i> {{ __('Verify Code') }}
                                        </button>
                                    </form>

                                    <form action="{{ route('telegram.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                                            <i class="ti ti-arrow-back me-1"></i> {{ __('Cancel and start over') }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha - Configuraciones --}}
        <div class="col-lg-7">
            {{-- Destinatarios de Mantenimiento --}}
            <div class="card tg-card mb-4" style="animation-delay: 0.1s">
                <div class="card-body">
                    <div class="tg-card-header mb-4">
                        <div class="tg-card-icon tg-icon-orange">
                            <i class="ti ti-users"></i>
                        </div>
                        <div>
                            <h5 class="tg-card-title mb-0">{{ __('Maintenance Recipients') }}</h5>
                            <small class="text-muted">{{ __('Receive maintenance alerts via Telegram') }}</small>
                        </div>
                    </div>

                    <form action="{{ route('telegram.updateMaintenancePeers') }}" method="POST">
                        @csrf
                        <div class="tg-input-group mb-3">
                            <div class="tg-input-icon">
                                <i class="ti ti-at"></i>
                            </div>
                            <input type="text" name="maintenance_peers" id="maintenance_peers"
                                   class="form-control tg-input"
                                   value="{{ $maintenancePeers }}"
                                   placeholder="@username1, +34612345678">
                        </div>
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ __('Use @username or phone with country code. Separate with commas.') }}
                            </small>
                            <button type="submit" class="btn btn-primary tg-btn-save">
                                <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Información sobre formatos --}}
            <div class="card tg-card tg-info-card mb-4" style="animation-delay: 0.2s">
                <div class="card-body">
                    <div class="tg-card-header mb-3">
                        <div class="tg-card-icon tg-icon-blue">
                            <i class="ti ti-help-hexagon"></i>
                        </div>
                        <div>
                            <h5 class="tg-card-title mb-0">{{ __('Recipient Formats') }}</h5>
                            <small class="text-muted">{{ __('How to specify recipients') }}</small>
                        </div>
                    </div>

                    <div class="tg-formats-grid">
                        <div class="tg-format-item">
                            <div class="tg-format-icon">
                                <i class="ti ti-at"></i>
                            </div>
                            <div class="tg-format-info">
                                <strong>@username</strong>
                                <span class="text-muted">{{ __('Telegram username') }}</span>
                            </div>
                        </div>
                        <div class="tg-format-item">
                            <div class="tg-format-icon">
                                <i class="ti ti-phone"></i>
                            </div>
                            <div class="tg-format-info">
                                <strong>+34612345678</strong>
                                <span class="text-muted">{{ __('Phone with country code') }}</span>
                            </div>
                        </div>
                        <div class="tg-format-item">
                            <div class="tg-format-icon">
                                <i class="ti ti-hash"></i>
                            </div>
                            <div class="tg-format-info">
                                <strong>123456789</strong>
                                <span class="text-muted">{{ __('Telegram User ID') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Enviar Mensaje de Prueba --}}
            @if ($isConnected)
            <div class="card tg-card tg-test-card mb-4" style="animation-delay: 0.3s">
                <div class="card-body">
                    <div class="tg-card-header mb-4">
                        <div class="tg-card-icon tg-icon-green">
                            <i class="ti ti-send"></i>
                        </div>
                        <div>
                            <h5 class="tg-card-title mb-0">{{ __('Send Test Message') }}</h5>
                            <small class="text-muted">{{ __('Verify your connection works') }}</small>
                        </div>
                    </div>

                    <form action="{{ route('telegram.sendMessage') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label small text-muted">{{ __('Recipient') }}</label>
                                <div class="tg-input-group">
                                    <div class="tg-input-icon">
                                        <i class="ti ti-user"></i>
                                    </div>
                                    <input type="text" name="peer" id="peer"
                                           class="form-control tg-input"
                                           placeholder="@username"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-7 mb-3">
                                <label class="form-label small text-muted">{{ __('Message') }}</label>
                                <div class="tg-input-group">
                                    <div class="tg-input-icon">
                                        <i class="ti ti-message"></i>
                                    </div>
                                    <input type="text" name="message" id="message"
                                           class="form-control tg-input"
                                           placeholder="{{ __('Test message') }}"
                                           required>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success tg-btn-send">
                                <i class="ti ti-send me-2"></i> {{ __('Send Message') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="card tg-card tg-disabled-card mb-4" style="animation-delay: 0.3s">
                <div class="card-body text-center py-5">
                    <div class="tg-disabled-icon mb-3">
                        <i class="ti ti-send-off"></i>
                    </div>
                    <h5 class="text-muted mb-2">{{ __('Send Test Message') }}</h5>
                    <p class="text-muted small mb-0">{{ __('Connect to Telegram first to send test messages') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.telegram-container {
    padding: 0;
}

/* Header */
.tg-header {
    background: linear-gradient(135deg, #0088cc 0%, #229ED9 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.tg-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.tg-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.tg-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Status Badge */
.tg-status-badge .badge-connected,
.tg-status-badge .badge-disconnected {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
}

.tg-status-badge .badge-connected {
    background: rgba(255,255,255,0.95);
    color: #0088cc;
}

.tg-status-badge .badge-disconnected {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* Cards */
.tg-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    animation: tgFadeInUp 0.4s ease forwards;
    opacity: 0;
}

.tg-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

@keyframes tgFadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Card Header */
.tg-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
}

.tg-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

.tg-icon-primary {
    background: rgba(0, 136, 204, 0.15);
    color: #0088cc;
}

.tg-icon-orange {
    background: rgba(249, 115, 22, 0.15);
    color: #f97316;
}

.tg-icon-blue {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.tg-icon-green {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.tg-card-title {
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

/* Input Group */
.tg-input-group {
    position: relative;
}

.tg-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.1rem;
    z-index: 1;
}

.tg-input {
    padding-left: 44px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.tg-input:focus {
    border-color: #0088cc;
    box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.15);
}

/* Buttons */
.tg-btn-save,
.tg-btn-login,
.tg-btn-verify {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 500;
}

.tg-btn-send {
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 500;
}

/* Connection States */
.tg-connection-card {
    min-height: 400px;
}

.tg-connected-icon,
.tg-login-icon,
.tg-disabled-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto;
}

.tg-connected-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.tg-login-icon {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.tg-disabled-icon {
    background: rgba(148, 163, 184, 0.15);
    color: #94a3b8;
}

/* Disconnect Button */
.btn-disconnect {
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 500;
}

/* Code Sent Alert */
.tg-code-sent .alert {
    border-radius: 12px;
    border: none;
    background: rgba(59, 130, 246, 0.1);
    color: #1e40af;
}

/* Info Card */
.tg-info-card {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

/* Formats Grid */
.tg-formats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.tg-format-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.tg-format-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: rgba(0, 136, 204, 0.1);
    color: #0088cc;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.tg-format-info {
    display: flex;
    flex-direction: column;
}

.tg-format-info strong {
    font-size: 0.875rem;
    color: #1e293b;
}

.tg-format-info span {
    font-size: 0.75rem;
}

/* Test Card */
.tg-test-card {
    border: 2px dashed #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.tg-test-card:hover {
    border-color: #0088cc;
}

/* Disabled Card */
.tg-disabled-card {
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
}

/* Dark Mode */
[data-theme="dark"] .tg-card {
    background: #1e293b;
}

[data-theme="dark"] .tg-card-title {
    color: #f1f5f9;
}

[data-theme="dark"] .tg-input {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

[data-theme="dark"] .tg-input:focus {
    border-color: #0088cc;
    box-shadow: 0 0 0 3px rgba(0, 136, 204, 0.2);
}

[data-theme="dark"] .tg-info-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}

[data-theme="dark"] .tg-format-item {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .tg-format-info strong {
    color: #f1f5f9;
}

[data-theme="dark"] .tg-test-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-color: #334155;
}

[data-theme="dark"] .tg-test-card:hover {
    border-color: #0088cc;
}

[data-theme="dark"] .tg-disabled-card {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .tg-code-sent .alert {
    background: rgba(59, 130, 246, 0.15);
    color: #93c5fd;
}

/* Responsive */
@media (max-width: 991.98px) {
    .tg-connection-card {
        min-height: auto;
    }
}

@media (max-width: 575.98px) {
    .tg-formats-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Initial load animation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.tg-card').forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
    });
});
</script>
@endpush
