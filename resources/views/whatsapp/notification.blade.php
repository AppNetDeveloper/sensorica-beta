@extends('layouts.admin')
@section('title', __('WhatsApp'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('WhatsApp Notifications') }}</li>
    </ul>
@endsection

@section('content')
<div class="whatsapp-container">
    {{-- Header --}}
    <div class="wa-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center">
                    <div class="wa-header-icon me-3">
                        <i class="ti ti-brand-whatsapp"></i>
                    </div>
                    <div>
                        <h4 class="wa-title mb-1">{{ __('WhatsApp Notifications') }}</h4>
                        <p class="wa-subtitle mb-0">{{ __('Configure WhatsApp integration for notifications') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="d-flex justify-content-lg-end mt-3 mt-lg-0">
                    <div class="wa-status-badge" id="statusBadge">
                        @if ($isLinked)
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
            <div class="card wa-card wa-connection-card h-100">
                <div class="card-body">
                    <div class="wa-card-header mb-4">
                        <div class="wa-card-icon wa-icon-primary">
                            <i class="ti ti-qrcode"></i>
                        </div>
                        <h5 class="wa-card-title">{{ __('Connection Status') }}</h5>
                    </div>

                    <div id="connectionStatus" class="text-center">
                        @if ($isLinked)
                            <div class="wa-connected-state">
                                <div class="wa-connected-icon mb-3">
                                    <i class="ti ti-circle-check"></i>
                                </div>
                                <h5 class="text-success mb-2">{{ __('WhatsApp Connected') }}</h5>
                                <p class="text-muted mb-4">{{ __('Your application is connected with WhatsApp.') }}</p>

                                <form action="{{ route('whatsapp.disconnect') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-disconnect">
                                        <i class="ti ti-unlink me-2"></i> {{ __('Disconnect') }}
                                    </button>
                                </form>
                            </div>
                        @elseif ($qrCode)
                            <div class="wa-qr-state">
                                <div class="wa-qr-icon mb-3">
                                    <i class="ti ti-scan"></i>
                                </div>
                                <h5 class="text-warning mb-2">{{ __('Scan QR Code') }}</h5>
                                <p class="text-muted mb-4">{{ __('Scan the QR code with your WhatsApp app to connect.') }}</p>

                                <div class="wa-qr-container">
                                    <img src="{{ $qrCode }}" alt="{{ __('WhatsApp QR Code') }}" class="wa-qr-image">
                                    <div class="wa-qr-overlay">
                                        <div class="wa-qr-spinner"></div>
                                    </div>
                                </div>

                                <p class="text-muted mt-3 small">
                                    <i class="ti ti-refresh me-1"></i> {{ __('QR code refreshes automatically') }}
                                </p>
                            </div>
                        @else
                            <div class="wa-error-state">
                                <div class="wa-error-icon mb-3">
                                    <i class="ti ti-wifi-off"></i>
                                </div>
                                <h5 class="text-danger mb-2">{{ __('Connection Error') }}</h5>
                                <p class="text-muted mb-4">{{ __('Could not get connection status. Please try again later.') }}</p>

                                <button onclick="refreshConnectionState()" class="btn btn-outline-primary">
                                    <i class="ti ti-refresh me-2"></i> {{ __('Retry') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha - Configuraciones --}}
        <div class="col-lg-7">
            {{-- Teléfonos de Mantenimiento --}}
            <div class="card wa-card mb-4" style="animation-delay: 0.1s">
                <div class="card-body">
                    <div class="wa-card-header mb-4">
                        <div class="wa-card-icon wa-icon-orange">
                            <i class="ti ti-tool"></i>
                        </div>
                        <div>
                            <h5 class="wa-card-title mb-0">{{ __('Maintenance Phones') }}</h5>
                            <small class="text-muted">{{ __('Receive maintenance alerts') }}</small>
                        </div>
                    </div>

                    <form action="{{ route('whatsapp.updateMaintenancePhones') }}" method="POST">
                        @csrf
                        <div class="wa-input-group mb-3">
                            <div class="wa-input-icon">
                                <i class="ti ti-device-mobile"></i>
                            </div>
                            <input type="text" name="maintenance_phones" id="maintenance_phones"
                                   class="form-control wa-input"
                                   value="{{ $phoneNumberMaintenance }}"
                                   placeholder="34611111111, 34622222222">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i> {{ __('Separate multiple numbers with commas') }}
                            </small>
                            <button type="submit" class="btn btn-primary wa-btn-save">
                                <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Teléfonos de Incidencias --}}
            <div class="card wa-card mb-4" style="animation-delay: 0.2s">
                <div class="card-body">
                    <div class="wa-card-header mb-4">
                        <div class="wa-card-icon wa-icon-red">
                            <i class="ti ti-alert-octagon"></i>
                        </div>
                        <div>
                            <h5 class="wa-card-title mb-0">{{ __('Incident Phones') }}</h5>
                            <small class="text-muted">{{ __('Receive order incident alerts') }}</small>
                        </div>
                    </div>

                    <form action="{{ route('whatsapp.updateIncidentPhones') }}" method="POST">
                        @csrf
                        <div class="wa-input-group mb-3">
                            <div class="wa-input-icon">
                                <i class="ti ti-device-mobile"></i>
                            </div>
                            <input type="text" name="incident_phones" id="incident_phones"
                                   class="form-control wa-input"
                                   value="{{ $phoneNumberIncident }}"
                                   placeholder="34611111111, 34622222222">
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i> {{ __('Separate multiple numbers with commas') }}
                            </small>
                            <button type="submit" class="btn btn-primary wa-btn-save">
                                <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Número de Notificación General --}}
            <div class="card wa-card mb-4" style="animation-delay: 0.3s">
                <div class="card-body">
                    <div class="wa-card-header mb-4">
                        <div class="wa-card-icon wa-icon-blue">
                            <i class="ti ti-bell-ringing"></i>
                        </div>
                        <div>
                            <h5 class="wa-card-title mb-0">{{ __('Notification Number') }}</h5>
                            <small class="text-muted">{{ __('General notification recipient') }}</small>
                        </div>
                    </div>

                    <form action="{{ route('whatsapp.updatePhone') }}" method="POST">
                        @csrf
                        <div class="wa-input-group mb-3">
                            <div class="wa-input-icon">
                                <i class="ti ti-phone"></i>
                            </div>
                            <input type="text" name="phone_number" id="phone_number"
                                   class="form-control wa-input"
                                   value="{{ $phoneNumber }}"
                                   placeholder="{{ __('Enter notification number') }}">
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary wa-btn-save">
                                <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Enviar Mensaje de Prueba --}}
            <div class="card wa-card wa-test-card mb-4" style="animation-delay: 0.4s">
                <div class="card-body">
                    <div class="wa-card-header mb-4">
                        <div class="wa-card-icon wa-icon-green">
                            <i class="ti ti-send"></i>
                        </div>
                        <div>
                            <h5 class="wa-card-title mb-0">{{ __('Send Test Message') }}</h5>
                            <small class="text-muted">{{ __('Verify your connection works') }}</small>
                        </div>
                    </div>

                    <form action="{{ route('whatsapp.sendTestMessage') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label small text-muted">{{ __('Phone Number') }}</label>
                                <div class="wa-input-group">
                                    <div class="wa-input-icon">
                                        <i class="ti ti-phone"></i>
                                    </div>
                                    <input type="text" name="test_phone_number" id="test_phone_number"
                                           class="form-control wa-input"
                                           placeholder="34612345678">
                                </div>
                            </div>
                            <div class="col-md-7 mb-3">
                                <label class="form-label small text-muted">{{ __('Message') }}</label>
                                <div class="wa-input-group">
                                    <div class="wa-input-icon">
                                        <i class="ti ti-message"></i>
                                    </div>
                                    <input type="text" name="test_message" id="test_message"
                                           class="form-control wa-input"
                                           placeholder="{{ __('Test message') }}">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success wa-btn-send">
                                <i class="ti ti-send me-2"></i> {{ __('Send Message') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.whatsapp-container {
    padding: 0;
}

/* Header */
.wa-header {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.wa-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.wa-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.wa-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Status Badge */
.wa-status-badge .badge-connected,
.wa-status-badge .badge-disconnected {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge-connected {
    background: rgba(255,255,255,0.95);
    color: #128C7E;
}

.badge-disconnected {
    background: rgba(255,255,255,0.2);
    color: white;
}

/* Cards */
.wa-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    animation: waFadeInUp 0.4s ease forwards;
    opacity: 0;
}

.wa-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

@keyframes waFadeInUp {
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
.wa-card-header {
    display: flex;
    align-items: center;
    gap: 14px;
}

.wa-card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
}

.wa-icon-primary {
    background: rgba(37, 211, 102, 0.15);
    color: #25D366;
}

.wa-icon-orange {
    background: rgba(249, 115, 22, 0.15);
    color: #f97316;
}

.wa-icon-red {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.wa-icon-blue {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.wa-icon-green {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.wa-card-title {
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

/* Input Group */
.wa-input-group {
    position: relative;
}

.wa-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.1rem;
    z-index: 1;
}

.wa-input {
    padding-left: 44px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.wa-input:focus {
    border-color: #25D366;
    box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.15);
}

/* Buttons */
.wa-btn-save {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 500;
}

.wa-btn-send {
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 500;
}

/* Connection States */
.wa-connection-card {
    min-height: 400px;
}

.wa-connected-icon,
.wa-qr-icon,
.wa-error-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin: 0 auto;
}

.wa-connected-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.wa-qr-icon {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.wa-error-icon {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

/* QR Code Container */
.wa-qr-container {
    position: relative;
    display: inline-block;
    padding: 16px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border: 2px solid #e2e8f0;
}

.wa-qr-image {
    max-width: 220px;
    border-radius: 8px;
    display: block;
}

.wa-qr-overlay {
    display: none;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    border-radius: 14px;
    align-items: center;
    justify-content: center;
}

.wa-qr-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e2e8f0;
    border-top-color: #25D366;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Disconnect Button */
.btn-disconnect {
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 500;
}

/* Test Card */
.wa-test-card {
    border: 2px dashed #e2e8f0;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.wa-test-card:hover {
    border-color: #25D366;
}

/* Dark Mode */
[data-theme="dark"] .wa-card {
    background: #1e293b;
}

[data-theme="dark"] .wa-card-title {
    color: #f1f5f9;
}

[data-theme="dark"] .wa-input {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

[data-theme="dark"] .wa-input:focus {
    border-color: #25D366;
    box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2);
}

[data-theme="dark"] .wa-qr-container {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .wa-test-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-color: #334155;
}

[data-theme="dark"] .wa-test-card:hover {
    border-color: #25D366;
}

/* Responsive */
@media (max-width: 991.98px) {
    .wa-connection-card {
        min-height: auto;
    }
}
</style>
@endpush

@push('scripts')
<script>
function refreshConnectionState() {
    const container = document.getElementById('connectionStatus');
    const statusBadge = document.getElementById('statusBadge');

    // Show loading
    const qrOverlay = container.querySelector('.wa-qr-overlay');
    if (qrOverlay) {
        qrOverlay.style.display = 'flex';
    }

    fetch('{{ route("whatsapp.status") }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        let html = '';
        let badgeHtml = '';

        if (data.isLinked) {
            badgeHtml = `<span class="badge-connected">
                <i class="ti ti-circle-check-filled"></i> {{ __("Connected") }}
            </span>`;

            html = `<div class="wa-connected-state">
                <div class="wa-connected-icon mb-3">
                    <i class="ti ti-circle-check"></i>
                </div>
                <h5 class="text-success mb-2">{{ __("WhatsApp Connected") }}</h5>
                <p class="text-muted mb-4">{{ __("Your application is connected with WhatsApp.") }}</p>

                <form action="{{ route('whatsapp.disconnect') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-disconnect">
                        <i class="ti ti-unlink me-2"></i> {{ __("Disconnect") }}
                    </button>
                </form>
            </div>`;
        } else if (data.qrCode) {
            badgeHtml = `<span class="badge-disconnected">
                <i class="ti ti-circle-x-filled"></i> {{ __("Disconnected") }}
            </span>`;

            html = `<div class="wa-qr-state">
                <div class="wa-qr-icon mb-3">
                    <i class="ti ti-scan"></i>
                </div>
                <h5 class="text-warning mb-2">{{ __("Scan QR Code") }}</h5>
                <p class="text-muted mb-4">{{ __("Scan the QR code with your WhatsApp app to connect.") }}</p>

                <div class="wa-qr-container">
                    <img src="${data.qrCode}" alt="{{ __("WhatsApp QR Code") }}" class="wa-qr-image">
                    <div class="wa-qr-overlay">
                        <div class="wa-qr-spinner"></div>
                    </div>
                </div>

                <p class="text-muted mt-3 small">
                    <i class="ti ti-refresh me-1"></i> {{ __("QR code refreshes automatically") }}
                </p>
            </div>`;
        } else {
            badgeHtml = `<span class="badge-disconnected">
                <i class="ti ti-circle-x-filled"></i> {{ __("Disconnected") }}
            </span>`;

            html = `<div class="wa-error-state">
                <div class="wa-error-icon mb-3">
                    <i class="ti ti-wifi-off"></i>
                </div>
                <h5 class="text-danger mb-2">{{ __("Connection Error") }}</h5>
                <p class="text-muted mb-4">{{ __("Could not get connection status. Please try again later.") }}</p>

                <button onclick="refreshConnectionState()" class="btn btn-outline-primary">
                    <i class="ti ti-refresh me-2"></i> {{ __("Retry") }}
                </button>
            </div>`;
        }

        container.innerHTML = html;
        statusBadge.innerHTML = badgeHtml;
    })
    .catch(error => {
        console.error('Error refreshing connection state:', error);
        if (qrOverlay) {
            qrOverlay.style.display = 'none';
        }
    });
}

// Auto-refresh every 15 seconds
setInterval(refreshConnectionState, 15000);

// Initial load animation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.wa-card').forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
    });
});
</script>
@endpush
