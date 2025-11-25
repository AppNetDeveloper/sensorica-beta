@extends('layouts.admin')

@section('title', __('Worker Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Worker Management') }}</li>
    </ul>
@endsection

@section('content')
<div class="workers-container">
    {{-- Header --}}
    <div class="workers-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-5">
                <div class="d-flex align-items-center">
                    <div class="workers-header-icon me-3">
                        <i class="ti ti-users"></i>
                    </div>
                    <div>
                        <h4 class="workers-title mb-1">{{ __('Worker Management') }}</h4>
                        <p class="workers-subtitle mb-0">{{ __('Manage factory workers and operators') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 mt-3 mt-lg-0 flex-wrap">
                    {{-- Buscador --}}
                    <div class="workers-search-box d-none d-md-block">
                        <i class="ti ti-search"></i>
                        <input type="text" id="searchWorkers" class="form-control" placeholder="{{ __('Search workers...') }}">
                    </div>
                    {{-- Botones de acción --}}
                    <button type="button" class="btn btn-light workers-btn-action" onclick="openAddWorkerModal()">
                        <i class="ti ti-user-plus me-1"></i> {{ __('Add') }}
                    </button>
                    <button type="button" class="btn btn-light workers-btn-action" onclick="$('#excelFileInput').click()">
                        <i class="ti ti-file-import me-1"></i> {{ __('Import') }}
                    </button>
                    <button type="button" class="btn btn-light workers-btn-action" onclick="exportToExcel()">
                        <i class="ti ti-file-export me-1"></i> {{ __('Export') }}
                    </button>
                    <button type="button" class="btn btn-light workers-btn-action workers-btn-monitor" onclick="openMonitor()">
                        <i class="ti ti-device-desktop me-1"></i> {{ __('Monitor') }}
                    </button>
                    <button type="button" class="btn btn-light workers-btn-action workers-btn-print" onclick="printList()">
                        <i class="ti ti-printer me-1"></i> {{ __('Print') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Input oculto para importar Excel --}}
    <input type="file" id="excelFileInput" accept=".xlsx" style="display: none;" />

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="workers-stats-card workers-stats-primary">
                <div class="workers-stats-icon">
                    <i class="ti ti-users"></i>
                </div>
                <div class="workers-stats-info">
                    <h3 id="statsTotalWorkers">-</h3>
                    <span>{{ __('Total Workers') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="workers-stats-card workers-stats-success">
                <div class="workers-stats-icon">
                    <i class="ti ti-user-check"></i>
                </div>
                <div class="workers-stats-info">
                    <h3 id="statsActiveWorkers">-</h3>
                    <span>{{ __('Active') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="workers-stats-card workers-stats-warning">
                <div class="workers-stats-icon">
                    <i class="ti ti-phone"></i>
                </div>
                <div class="workers-stats-info">
                    <h3 id="statsWithPhone">-</h3>
                    <span>{{ __('With Phone') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="workers-stats-card workers-stats-info">
                <div class="workers-stats-icon">
                    <i class="ti ti-mail"></i>
                </div>
                <div class="workers-stats-info">
                    <h3 id="statsWithEmail">-</h3>
                    <span>{{ __('With Email') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Buscador móvil --}}
    <div class="workers-search-mobile d-md-none mb-4">
        <div class="workers-search-box-mobile">
            <i class="ti ti-search"></i>
            <input type="text" id="searchWorkersMobile" class="form-control" placeholder="{{ __('Search workers...') }}">
        </div>
    </div>

    {{-- Info Card --}}
    <div class="workers-info-card mb-4">
        <div class="d-flex align-items-start">
            <div class="workers-info-icon me-3">
                <i class="ti ti-info-circle"></i>
            </div>
            <div class="workers-info-content">
                <strong>{{ __('Information') }}:</strong>
                <ul class="mb-0 mt-2 ps-3">
                    <li><strong>PIN</strong>: {{ __('Used only on the production line screen for quick login (clocking). Not a system access credential.') }}</li>
                    <li><strong>{{ __('Password') }}</strong>: {{ __('Used for system functions that require traditional authentication (when applicable).') }}</li>
                    <li>{{ __('You can reset the PIN via WhatsApp to the worker if they have a phone configured.') }}</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Workers Grid --}}
    <div class="row" id="workersGrid">
        {{-- Los workers se cargan por JavaScript --}}
    </div>

    {{-- Loading State --}}
    <div id="workersLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">{{ __('Loading') }}...</span>
        </div>
        <p class="mt-3 text-muted">{{ __('Loading workers') }}...</p>
    </div>

    {{-- Empty State --}}
    <div id="workersEmpty" class="row" style="display: none;">
        <div class="col-12">
            <div class="card workers-empty-state">
                <div class="card-body text-center py-5">
                    <div class="workers-empty-icon mb-4">
                        <i class="ti ti-users-minus"></i>
                    </div>
                    <h4>{{ __('No workers found') }}</h4>
                    <p class="text-muted mb-4">{{ __('Start by adding your first worker.') }}</p>
                    <button type="button" class="btn btn-primary" onclick="openAddWorkerModal()">
                        <i class="ti ti-user-plus me-1"></i> {{ __('Add Worker') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Añadir/Editar Worker --}}
<div class="modal fade" id="workerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header workers-modal-header">
                <h5 class="modal-title" id="workerModalTitle">
                    <i class="ti ti-user-plus me-2"></i>
                    {{ __('Add Worker') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="workerForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Worker Code') }} <span class="text-danger">*</span></label>
                            <div class="workers-input-group">
                                <div class="workers-input-icon"><i class="ti ti-id"></i></div>
                                <input type="number" id="workerId" class="form-control workers-input" placeholder="1001" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <div class="workers-input-group">
                                <div class="workers-input-icon"><i class="ti ti-user"></i></div>
                                <input type="text" id="workerName" class="form-control workers-input" placeholder="{{ __('Full name') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Email') }} <span class="text-muted">({{ __('optional') }})</span></label>
                            <div class="workers-input-group">
                                <div class="workers-input-icon"><i class="ti ti-mail"></i></div>
                                <input type="email" id="workerEmail" class="form-control workers-input" placeholder="email@domain.com">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Phone') }} <span class="text-muted">({{ __('optional') }})</span></label>
                            <div class="workers-input-group">
                                <div class="workers-input-icon"><i class="ti ti-phone"></i></div>
                                <input type="tel" id="workerPhone" class="form-control workers-input" placeholder="+34XXXXXXXXX">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">PIN <span class="text-muted">({{ __('optional') }}, 4-6 {{ __('digits') }})</span></label>
                            <div class="workers-input-group">
                                <div class="workers-input-icon"><i class="ti ti-key"></i></div>
                                <input type="text" id="workerPin" class="form-control workers-input" placeholder="PIN" maxlength="6" inputmode="numeric">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Password') }} <span class="text-muted">({{ __('optional') }})</span></label>
                            <div class="workers-input-group">
                                <div class="workers-input-icon"><i class="ti ti-lock"></i></div>
                                <input type="password" id="workerPassword" class="form-control workers-input" placeholder="{{ __('Password') }}">
                            </div>
                            <small class="text-muted" id="passwordHint" style="display: none;">{{ __('Leave blank to keep current password') }}</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" onclick="saveWorker()">
                    <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Acciones Worker --}}
<div class="modal fade" id="workerActionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="ti ti-settings me-2"></i>
                    {{ __('Worker Actions') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="text-center mb-4" id="workerActionsName">-</h6>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary workers-action-modal-btn" onclick="editWorkerFromModal()">
                        <i class="ti ti-edit me-2"></i> {{ __('Edit') }}
                    </button>
                    <button type="button" class="btn btn-outline-success workers-action-modal-btn" id="btnToggleActive" onclick="toggleWorkerActive()">
                        <i class="ti ti-user-check me-2"></i> {{ __('Enable/Disable') }}
                    </button>
                    <button type="button" class="btn btn-outline-info workers-action-modal-btn" onclick="resetPinWhatsApp()">
                        <i class="ti ti-key me-2"></i> {{ __('Reset PIN via WhatsApp') }}
                    </button>
                    <button type="button" class="btn btn-outline-warning workers-action-modal-btn" onclick="resetPasswordEmail()">
                        <i class="ti ti-mail me-2"></i> {{ __('Reset Password via Email') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary workers-action-modal-btn" onclick="resetPasswordWhatsApp()">
                        <i class="ti ti-brand-whatsapp me-2"></i> {{ __('Reset Password via WhatsApp') }}
                    </button>
                    <hr>
                    <button type="button" class="btn btn-outline-danger workers-action-modal-btn" onclick="deleteWorker()">
                        <i class="ti ti-trash me-2"></i> {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* Container */
.workers-container {
    padding: 0;
}

/* Header */
.workers-header {
    background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.workers-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.workers-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.workers-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Search Box */
.workers-search-box {
    position: relative;
}

.workers-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255,255,255,0.7);
    font-size: 1.1rem;
}

.workers-search-box input {
    padding-left: 42px;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    height: 42px;
    width: 200px;
    color: white;
    font-size: 0.9rem;
}

.workers-search-box input::placeholder {
    color: rgba(255,255,255,0.7);
}

.workers-search-box input:focus {
    background: rgba(255,255,255,0.25);
    border-color: rgba(255,255,255,0.5);
    box-shadow: none;
    color: white;
}

/* Mobile Search */
.workers-search-box-mobile {
    position: relative;
}

.workers-search-box-mobile i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #fb923c;
    font-size: 1.1rem;
}

.workers-search-box-mobile input {
    padding-left: 42px;
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    height: 46px;
    width: 100%;
    font-size: 0.95rem;
}

.workers-search-box-mobile input:focus {
    border-color: #fb923c;
    box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.15);
}

/* Action Buttons */
.workers-btn-action {
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    background: white;
    color: #fb923c;
    border: none;
}

.workers-btn-action:hover {
    background: #f8fafc;
    color: #f97316;
}

.workers-btn-monitor {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    color: white !important;
}

.workers-btn-monitor:hover {
    background: linear-gradient(135deg, #3a9fee 0%, #00d4e0 100%) !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.4);
}

.workers-btn-print {
    background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%) !important;
    color: white !important;
}

.workers-btn-print:hover {
    background: linear-gradient(135deg, #7c27c9 0%, #3d00b8 100%) !important;
    color: white !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(142, 45, 226, 0.4);
}

/* Stats Cards */
.workers-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.workers-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.workers-stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.workers-stats-primary .workers-stats-icon {
    background: rgba(251, 146, 60, 0.15);
    color: #fb923c;
}

.workers-stats-success .workers-stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.workers-stats-warning .workers-stats-icon {
    background: rgba(234, 179, 8, 0.15);
    color: #eab308;
}

.workers-stats-info .workers-stats-icon {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.workers-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.workers-stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* Info Card */
.workers-info-card {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 20px;
    border-left: 4px solid #f59e0b;
}

.workers-info-icon {
    width: 40px;
    height: 40px;
    background: rgba(245, 158, 11, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d97706;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.workers-info-content {
    color: #92400e;
    font-size: 0.9rem;
}

.workers-info-content strong {
    color: #78350f;
}

.workers-info-content li {
    margin-bottom: 4px;
}

/* Worker Cards */
.workers-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    cursor: pointer;
}

.workers-card:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    transform: translateY(-4px);
}

.workers-card.inactive {
    opacity: 0.6;
}

.worker-card-wrapper {
    animation: workersFadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes workersFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Card Header */
.workers-card-header {
    background: linear-gradient(135deg, #fdba74 0%, #fb923c 100%);
    padding: 20px;
    text-align: center;
    position: relative;
}

.workers-card-header.inactive {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
}

.workers-card-id {
    position: absolute;
    top: 10px;
    left: 10px;
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.workers-card-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

.workers-card-status.active {
    background: rgba(34, 197, 94, 0.9);
    color: white;
}

.workers-card-status.inactive {
    background: rgba(239, 68, 68, 0.9);
    color: white;
}

.workers-avatar {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 3px solid rgba(255,255,255,0.5);
}

.workers-avatar-initials {
    color: white;
    font-size: 1.4rem;
    font-weight: 700;
}

/* Card Body */
.workers-card-body {
    padding: 16px;
    text-align: center;
}

.workers-name {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.workers-contact-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #64748b;
    font-size: 0.8rem;
    margin-bottom: 4px;
}

.workers-contact-item i {
    color: #94a3b8;
    font-size: 0.9rem;
}

.workers-contact-item span {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Card Footer */
.workers-card-footer {
    padding: 12px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}

.workers-btn-actions {
    background: rgba(251, 146, 60, 0.1);
    color: #fb923c;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.workers-btn-actions:hover {
    background: #fb923c;
    color: white;
}

/* Empty State */
.workers-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
    border-radius: 16px;
}

.workers-empty-icon {
    width: 80px;
    height: 80px;
    background: #f1f5f9;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2.5rem;
    color: #94a3b8;
}

/* Modal Styles */
.workers-modal-header {
    background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
    color: white;
    border: none;
}

.workers-modal-header .modal-title {
    font-weight: 600;
}

.workers-input-group {
    position: relative;
}

.workers-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    z-index: 1;
}

.workers-input {
    padding-left: 42px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.workers-input:focus {
    border-color: #fb923c;
    box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.15);
}

.workers-action-modal-btn {
    text-align: left;
    padding: 12px 16px;
    border-radius: 10px;
}

#workerModal .modal-content,
#workerActionsModal .modal-content {
    border-radius: 16px;
    border: none;
}

/* Dark Mode */
[data-theme="dark"] .workers-card {
    background: #1e293b;
}

[data-theme="dark"] .workers-card-body {
    background: #1e293b;
}

[data-theme="dark"] .workers-name {
    color: #f1f5f9;
}

[data-theme="dark"] .workers-contact-item {
    color: #94a3b8;
}

[data-theme="dark"] .workers-card-footer {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .workers-stats-card {
    background: #1e293b;
}

[data-theme="dark"] .workers-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .workers-info-card {
    background: linear-gradient(135deg, #422006 0%, #78350f 100%);
    border-color: #d97706;
}

[data-theme="dark"] .workers-info-content {
    color: #fef3c7;
}

[data-theme="dark"] .workers-info-content strong {
    color: #fde68a;
}

[data-theme="dark"] .workers-empty-state {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .workers-input {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

/* Responsive */
@media (max-width: 991.98px) {
    .workers-header .row {
        gap: 16px;
    }
}

@media (max-width: 767.98px) {
    .workers-stats-card {
        margin-bottom: 12px;
    }

    .workers-btn-action {
        padding: 6px 12px;
        font-size: 0.8rem;
    }

    .workers-btn-action span {
        display: none;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
const workersApiUrl = '/api/workers';
let allWorkers = [];
let currentWorker = null;
let isEditMode = false;

// Cargar trabajadores al iniciar
document.addEventListener('DOMContentLoaded', function() {
    loadWorkers();

    // Buscador desktop
    document.getElementById('searchWorkers')?.addEventListener('keyup', function() {
        filterWorkers(this.value);
    });

    // Buscador mobile
    document.getElementById('searchWorkersMobile')?.addEventListener('keyup', function() {
        filterWorkers(this.value);
    });

    // Importar Excel
    document.getElementById('excelFileInput').addEventListener('change', handleExcelImport);
});

function loadWorkers() {
    document.getElementById('workersLoading').style.display = 'block';
    document.getElementById('workersGrid').innerHTML = '';
    document.getElementById('workersEmpty').style.display = 'none';

    fetch(`${workersApiUrl}/list-all`)
        .then(response => response.json())
        .then(data => {
            allWorkers = data.operators || [];
            updateStats();
            renderWorkers(allWorkers);
            document.getElementById('workersLoading').style.display = 'none';
        })
        .catch(error => {
            console.error('Error loading workers:', error);
            document.getElementById('workersLoading').style.display = 'none';
            Swal.fire('Error', 'No se pudieron cargar los trabajadores', 'error');
        });
}

function updateStats() {
    document.getElementById('statsTotalWorkers').textContent = allWorkers.length;
    document.getElementById('statsActiveWorkers').textContent = allWorkers.filter(w => w.active).length;
    document.getElementById('statsWithPhone').textContent = allWorkers.filter(w => w.phone).length;
    document.getElementById('statsWithEmail').textContent = allWorkers.filter(w => w.email).length;
}

function renderWorkers(workers) {
    const grid = document.getElementById('workersGrid');
    grid.innerHTML = '';

    if (workers.length === 0) {
        document.getElementById('workersEmpty').style.display = 'block';
        return;
    }

    document.getElementById('workersEmpty').style.display = 'none';

    workers.forEach((worker, index) => {
        const initials = worker.name ? worker.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??';
        const isActive = worker.active !== false && worker.active !== 0;

        const card = `
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 worker-card-wrapper"
                 data-name="${(worker.name || '').toLowerCase()}"
                 data-email="${(worker.email || '').toLowerCase()}"
                 data-id="${worker.id}"
                 style="animation-delay: ${index * 0.03}s">
                <div class="card workers-card h-100 ${!isActive ? 'inactive' : ''}" onclick="openWorkerActions(${worker.id})">
                    <div class="workers-card-header ${!isActive ? 'inactive' : ''}">
                        <div class="workers-card-id">#${worker.id}</div>
                        <div class="workers-card-status ${isActive ? 'active' : 'inactive'}">
                            ${isActive ? '{{ __("Active") }}' : '{{ __("Inactive") }}'}
                        </div>
                        <div class="workers-avatar">
                            <span class="workers-avatar-initials">${initials}</span>
                        </div>
                    </div>
                    <div class="workers-card-body">
                        <h5 class="workers-name" title="${worker.name || ''}">${worker.name || '-'}</h5>
                        <div class="workers-contact-item">
                            <i class="ti ti-mail"></i>
                            <span title="${worker.email || ''}">${worker.email || '{{ __("No email") }}'}</span>
                        </div>
                        <div class="workers-contact-item">
                            <i class="ti ti-phone"></i>
                            <span>${worker.phone || '{{ __("No phone") }}'}</span>
                        </div>
                    </div>
                    <div class="workers-card-footer">
                        <button type="button" class="workers-btn-actions" onclick="event.stopPropagation(); openWorkerActions(${worker.id})">
                            <i class="ti ti-settings me-1"></i> {{ __('Actions') }}
                        </button>
                    </div>
                </div>
            </div>
        `;
        grid.innerHTML += card;
    });
}

function filterWorkers(searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    const filtered = allWorkers.filter(w => {
        const name = (w.name || '').toLowerCase();
        const email = (w.email || '').toLowerCase();
        const id = String(w.id);
        return name.includes(searchTerm) || email.includes(searchTerm) || id.includes(searchTerm);
    });
    renderWorkers(filtered);
}

function openAddWorkerModal() {
    isEditMode = false;
    currentWorker = null;
    document.getElementById('workerModalTitle').innerHTML = '<i class="ti ti-user-plus me-2"></i>{{ __("Add Worker") }}';
    document.getElementById('workerId').value = '';
    document.getElementById('workerId').readOnly = false;
    document.getElementById('workerName').value = '';
    document.getElementById('workerEmail').value = '';
    document.getElementById('workerPhone').value = '';
    document.getElementById('workerPin').value = '';
    document.getElementById('workerPassword').value = '';
    document.getElementById('passwordHint').style.display = 'none';

    var modal = new bootstrap.Modal(document.getElementById('workerModal'));
    modal.show();
}

function openEditWorkerModal(worker) {
    isEditMode = true;
    currentWorker = worker;
    document.getElementById('workerModalTitle').innerHTML = '<i class="ti ti-edit me-2"></i>{{ __("Edit Worker") }}';
    document.getElementById('workerId').value = worker.id;
    document.getElementById('workerId').readOnly = true;
    document.getElementById('workerName').value = worker.name || '';
    document.getElementById('workerEmail').value = worker.email || '';
    document.getElementById('workerPhone').value = worker.phone || '';
    document.getElementById('workerPin').value = '';
    document.getElementById('workerPassword').value = '';
    document.getElementById('passwordHint').style.display = 'block';

    // Cerrar modal de acciones
    bootstrap.Modal.getInstance(document.getElementById('workerActionsModal'))?.hide();

    setTimeout(() => {
        var modal = new bootstrap.Modal(document.getElementById('workerModal'));
        modal.show();
    }, 300);
}

function saveWorker() {
    const id = document.getElementById('workerId').value;
    const name = document.getElementById('workerName').value;
    const email = document.getElementById('workerEmail').value || null;
    const phone = document.getElementById('workerPhone').value || null;
    const pin = document.getElementById('workerPin').value || null;
    const password = document.getElementById('workerPassword').value || null;

    if (!id || !name) {
        Swal.fire('Error', '{{ __("Worker Code and Name are required") }}', 'error');
        return;
    }

    if (pin && (pin.length < 4 || pin.length > 6 || /\D/.test(pin))) {
        Swal.fire('Error', '{{ __("PIN must be 4-6 numeric digits") }}', 'error');
        return;
    }

    const payload = { id: parseInt(id), name, email, phone };
    if (pin) payload.pin = pin;
    if (password) payload.password = password;

    fetch(`${workersApiUrl}/update-or-insert`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) throw new Error('Error saving worker');
        return response.json();
    })
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('workerModal'))?.hide();
        Swal.fire({
            icon: 'success',
            title: isEditMode ? '{{ __("Updated") }}!' : '{{ __("Added") }}!',
            text: isEditMode ? '{{ __("Worker updated successfully") }}' : '{{ __("Worker added successfully") }}',
            timer: 2000,
            showConfirmButton: false
        });
        loadWorkers();
    })
    .catch(error => {
        Swal.fire('Error', error.message, 'error');
    });
}

function openWorkerActions(workerId) {
    currentWorker = allWorkers.find(w => w.id === workerId);
    if (!currentWorker) return;

    document.getElementById('workerActionsName').textContent = currentWorker.name || '-';

    const isActive = currentWorker.active !== false && currentWorker.active !== 0;
    const btnToggle = document.getElementById('btnToggleActive');
    btnToggle.innerHTML = isActive
        ? '<i class="ti ti-user-minus me-2"></i>{{ __("Disable") }}'
        : '<i class="ti ti-user-check me-2"></i>{{ __("Enable") }}';

    var modal = new bootstrap.Modal(document.getElementById('workerActionsModal'));
    modal.show();
}

function editWorkerFromModal() {
    if (currentWorker) {
        openEditWorkerModal(currentWorker);
    }
}

function toggleWorkerActive() {
    if (!currentWorker) return;

    const isActive = currentWorker.active !== false && currentWorker.active !== 0;
    const actionText = isActive ? '{{ __("disable") }}' : '{{ __("enable") }}';

    Swal.fire({
        title: `{{ __("Are you sure you want to") }} ${actionText} {{ __("this worker") }}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '{{ __("Yes") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${workersApiUrl}/${currentWorker.id}/toggle-active`, { method: 'POST' })
            .then(() => {
                bootstrap.Modal.getInstance(document.getElementById('workerActionsModal'))?.hide();
                Swal.fire('{{ __("Success") }}', '', 'success');
                loadWorkers();
            })
            .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    });
}

function deleteWorker() {
    if (!currentWorker) return;

    Swal.fire({
        title: '{{ __("Are you sure?") }}',
        text: '{{ __("This action cannot be undone.") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: '{{ __("Yes, delete") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${workersApiUrl}/${currentWorker.id}`, { method: 'DELETE' })
            .then(() => {
                bootstrap.Modal.getInstance(document.getElementById('workerActionsModal'))?.hide();
                Swal.fire('{{ __("Deleted") }}!', '', 'success');
                loadWorkers();
            })
            .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    });
}

function resetPinWhatsApp() {
    if (!currentWorker) return;
    if (!currentWorker.phone) {
        Swal.fire('{{ __("Warning") }}', '{{ __("This worker has no phone assigned.") }}', 'warning');
        return;
    }

    Swal.fire({
        title: '{{ __("Reset PIN via WhatsApp") }}',
        text: `{{ __("A new PIN will be sent to") }}: ${currentWorker.phone}`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '{{ __("Confirm") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${workersApiUrl}/reset-pin-whatsapp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone: currentWorker.phone })
            })
            .then(() => {
                Swal.fire('{{ __("Success") }}', '{{ __("PIN reset and sent via WhatsApp") }}', 'success');
            })
            .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    });
}

function resetPasswordEmail() {
    if (!currentWorker) return;
    if (!currentWorker.email) {
        Swal.fire('{{ __("Warning") }}', '{{ __("This worker has no email assigned.") }}', 'warning');
        return;
    }

    Swal.fire({
        title: '{{ __("Reset Password via Email") }}',
        text: `{{ __("A new password will be sent to") }}: ${currentWorker.email}`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '{{ __("Confirm") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${workersApiUrl}/reset-password-email`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: currentWorker.email })
            })
            .then(() => {
                Swal.fire('{{ __("Success") }}', '{{ __("Password reset and sent via email") }}', 'success');
            })
            .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    });
}

function resetPasswordWhatsApp() {
    if (!currentWorker) return;
    if (!currentWorker.phone) {
        Swal.fire('{{ __("Warning") }}', '{{ __("This worker has no phone assigned.") }}', 'warning');
        return;
    }

    Swal.fire({
        title: '{{ __("Reset Password via WhatsApp") }}',
        text: `{{ __("A new password will be sent to") }}: ${currentWorker.phone}`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: '{{ __("Confirm") }}',
        cancelButtonText: '{{ __("Cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`${workersApiUrl}/reset-password-whatsapp`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ phone: currentWorker.phone })
            })
            .then(() => {
                Swal.fire('{{ __("Success") }}', '{{ __("Password reset and sent via WhatsApp") }}', 'success');
            })
            .catch(error => Swal.fire('Error', error.message, 'error'));
        }
    });
}

function exportToExcel() {
    if (allWorkers.length === 0) {
        Swal.fire('{{ __("Warning") }}', '{{ __("No workers to export") }}', 'warning');
        return;
    }

    const data = allWorkers.map(w => ({
        'Codigo Trabajador': w.id,
        'Nombre': w.name,
        'Email': w.email || '',
        'Teléfono': w.phone || '',
        'Activo': w.active ? 'Sí' : 'No'
    }));

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Trabajadores');
    XLSX.writeFile(wb, 'trabajadores_export.xlsx');
}

function openMonitor() {
    const url = '/workers/workers-live.html?shift=true&order=true&name=true&id=true&nameSize=1.2rem&numberSize=2rem&idSize=1rem&labelSize=1rem';
    window.open(url, '_blank');
}

function printList() {
    const url = '/workers/export.html';
    window.open(url, '_blank');
}

function handleExcelImport(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const sheet = workbook.Sheets[sheetName];
            let rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

            if (!rows || rows.length === 0) {
                Swal.fire('Error', '{{ __("The file is empty or has incorrect format.") }}', 'error');
                document.getElementById('excelFileInput').value = '';
                return;
            }

            // Ignorar encabezados
            rows = rows.slice(1);

            const validRows = rows
                .map(row => ({
                    id: parseInt(row[0]) || null,
                    name: row[1] ? String(row[1]).trim() : null,
                    email: row[2] ? String(row[2]).trim() : null,
                    phone: row[3] ? String(row[3]).trim() : null,
                    password: row[4] ? String(row[4]).trim() : null
                }))
                .filter(r => r.id && r.name);

            if (validRows.length === 0) {
                Swal.fire('Error', '{{ __("No valid rows found to process.") }}', 'error');
                document.getElementById('excelFileInput').value = '';
                return;
            }

            Swal.fire({
                title: '{{ __("Processing file") }}',
                text: `{{ __("Importing") }} ${validRows.length} {{ __("workers") }}...`,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const promises = validRows.map(row => {
                return fetch(`${workersApiUrl}/update-or-insert`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(row)
                });
            });

            Promise.all(promises)
                .then(() => {
                    Swal.fire('{{ __("Success") }}', '{{ __("File processed successfully.") }}', 'success');
                    loadWorkers();
                    document.getElementById('excelFileInput').value = '';
                })
                .catch(() => {
                    Swal.fire('Error', '{{ __("An error occurred while processing the file.") }}', 'error');
                    document.getElementById('excelFileInput').value = '';
                });

        } catch (error) {
            Swal.fire('Error', '{{ __("Error reading file.") }}', 'error');
            document.getElementById('excelFileInput').value = '';
        }
    };
    reader.readAsArrayBuffer(file);
}
</script>
@endpush
