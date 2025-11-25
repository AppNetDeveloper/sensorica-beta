@extends('layouts.admin')

@section('title', __('Order Organizer') . ' - ' . $customer->name)

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Production Centers') }}</a></li>
        <li class="breadcrumb-item">{{ $customer->name }}</li>
        <li class="breadcrumb-item">{{ __('Order Organizer') }}</li>
    </ul>
@endsection

@section('content')
<div class="order-organizer-container">
    {{-- Header --}}
    <div class="oo-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="d-flex align-items-center">
                    <div class="oo-header-icon me-3">
                        <i class="ti ti-layout-kanban"></i>
                    </div>
                    <div>
                        <h4 class="oo-title mb-1">{{ __('Order Organizer') }}</h4>
                        <p class="oo-subtitle mb-0">
                            <i class="ti ti-building-factory me-1"></i>{{ $customer->name }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-lg-end gap-3 mt-3 mt-lg-0">
                    {{-- Toggle de filtro --}}
                    @can('kanban-filter-toggle')
                    <div class="oo-filter-toggle">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="kanbanFilterToggle"
                                   {{ $filterEnabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="kanbanFilterToggle">
                                <span class="filter-label">{{ __('Ready Filter') }}</span>
                                <span class="filter-status {{ $filterEnabled ? 'active' : '' }}" id="filterStatusBadge">
                                    {{ $filterEnabled ? __('ON') : __('OFF') }}
                                </span>
                            </label>
                        </div>
                    </div>
                    @endcan

                    {{-- Botón volver --}}
                    <a href="{{ route('customers.index') }}" class="btn btn-light">
                        <i class="ti ti-arrow-left me-1"></i> {{ __('Back') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="oo-stats-card oo-stats-primary">
                <div class="oo-stats-icon">
                    <i class="ti ti-box"></i>
                </div>
                <div class="oo-stats-info">
                    <h3>{{ $groupedProcesses->count() }}</h3>
                    <span>{{ __('Process Groups') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="oo-stats-card oo-stats-success">
                <div class="oo-stats-icon">
                    <i class="ti ti-building-factory"></i>
                </div>
                <div class="oo-stats-info">
                    <h3>{{ $totalLines }}</h3>
                    <span>{{ __('Production Lines') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="oo-stats-card oo-stats-info">
                <div class="oo-stats-icon">
                    <i class="ti ti-filter"></i>
                </div>
                <div class="oo-stats-info">
                    <h3 id="filterStatusText">{{ $filterEnabled ? __('Active') : __('Inactive') }}</h3>
                    <span>{{ __('Ready Filter') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Search Box --}}
    @if($groupedProcesses->count() > 3)
    <div class="oo-search-container mb-4">
        <div class="oo-search-box">
            <i class="ti ti-search"></i>
            <input type="text" id="searchProcesses" class="form-control" placeholder="{{ __('Search processes...') }}">
        </div>
    </div>
    @endif

    {{-- Process Cards Grid --}}
    @if($groupedProcesses->count() > 0)
    <div class="row" id="processesGrid">
        @foreach($groupedProcesses as $index => $processData)
            @php
                $process = $processData['process'];
                $lines = $processData['lines'];
                $bgColor = $process->color ?? '#6366f1';
                $hex = ltrim($bgColor, '#');
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                $textColor = $brightness > 155 ? '#1e293b' : '#ffffff';
                $lightBg = "rgba({$r}, {$g}, {$b}, 0.1)";
            @endphp
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4 process-card-wrapper"
                 data-name="{{ strtolower($process->description ?? '') }}"
                 style="animation-delay: {{ (int)$loop->index * 0.05 }}s">
                <div class="card oo-process-card h-100">
                    {{-- Card Header con color del proceso --}}
                    <div class="oo-process-header" style="background: {{ $bgColor }};">
                        <div class="oo-process-icon" style="background: rgba(255,255,255,0.2);">
                            <i class="ti ti-tools" style="color: {{ $textColor }}; font-size: 1.8rem;"></i>
                        </div>
                        <h5 class="oo-process-name" style="color: {{ $textColor }};">
                            {{ $process->description ?: __('No description') }}
                        </h5>
                    </div>

                    {{-- Stats del proceso --}}
                    <div class="oo-process-stats">
                        <div class="oo-process-stat">
                            <div class="oo-stat-icon" style="background: {{ $lightBg }};">
                                <i class="ti ti-settings-automation" style="color: {{ $bgColor }}; font-size: 1rem;"></i>
                            </div>
                            <div class="oo-stat-data">
                                <span class="oo-stat-value">{{ $lines->count() }}</span>
                                <span class="oo-stat-label">{{ __('Machines') }}</span>
                            </div>
                        </div>
                        <div class="oo-process-stat">
                            <div class="oo-stat-icon bg-success-light">
                                <i class="ti ti-player-play text-success" style="font-size: 1rem;"></i>
                            </div>
                            <div class="oo-stat-data">
                                @php
                                    $activeCount = $lines->filter(function($line) {
                                        if ($line->lastShiftHistory) {
                                            $action = strtolower(trim($line->lastShiftHistory->action ?? ''));
                                            $type = strtolower(trim($line->lastShiftHistory->type ?? ''));
                                            return $action == 'start' || ($type === 'stop' && $action === 'end');
                                        }
                                        return false;
                                    })->count();
                                @endphp
                                <span class="oo-stat-value">{{ $activeCount }}</span>
                                <span class="oo-stat-label">{{ __('Active') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Lista de máquinas --}}
                    <div class="oo-process-body">
                        <div class="oo-machines-label">
                            <i class="ti ti-list me-1"></i> {{ __('Machines') }}
                        </div>
                        <div class="oo-machines-list">
                            @foreach($lines->take(4) as $line)
                            <div class="oo-machine-item">
                                <span class="oo-machine-bullet" style="background: {{ $bgColor }};"></span>
                                <span>{{ $line->name }}</span>
                            </div>
                            @endforeach
                            {{-- Espacios vacíos para mantener altura consistente --}}
                            @for($i = $lines->count(); $i < 4; $i++)
                            <div class="oo-machine-item oo-machine-empty">
                                <span class="oo-machine-bullet" style="background: transparent;"></span>
                                <span>&nbsp;</span>
                            </div>
                            @endfor
                            @if($lines->count() > 4)
                            <div class="oo-machine-more">
                                <span class="badge bg-light text-secondary">+{{ $lines->count() - 4 }} {{ __('more') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Botón de acción --}}
                    <div class="oo-process-footer">
                        <a href="{{ route('customers.order-kanban', ['customer' => $customer->id, 'process' => $process->id]) }}"
                           class="oo-btn-kanban"
                           style="background: {{ $bgColor }}; color: {{ $textColor }};">
                            <i class="ti ti-layout-kanban me-2"></i>
                            {{ __('Open Kanban') }}
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @else
    {{-- Empty State --}}
    <div class="row">
        <div class="col-12">
            <div class="card oo-empty-state">
                <div class="card-body text-center py-5">
                    <div class="oo-empty-icon mb-4">
                        <i class="ti ti-layout-kanban-off"></i>
                    </div>
                    <h4>{{ __('No production lines found') }}</h4>
                    <p class="text-muted mb-4">{{ __('No production lines found for this customer.') }}</p>
                    <a href="{{ route('productionlines.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> {{ __('Add Production Line') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('style')
<style>
/* Container */
.order-organizer-container {
    padding: 0;
}

/* Header */
.oo-header {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.oo-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.oo-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.oo-subtitle {
    color: rgba(255,255,255,0.8);
    font-size: 0.95rem;
}

/* Filter Toggle */
.oo-filter-toggle {
    background: rgba(255,255,255,0.15);
    padding: 10px 16px;
    border-radius: 50px;
    backdrop-filter: blur(10px);
}

.oo-filter-toggle .form-check-input {
    width: 44px;
    height: 24px;
    cursor: pointer;
    background-color: rgba(255,255,255,0.3);
    border: none;
}

.oo-filter-toggle .form-check-input:checked {
    background-color: #22c55e;
}

.oo-filter-toggle .form-check-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: white;
    margin-left: 8px;
}

.filter-label {
    font-weight: 500;
}

.filter-status {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    background: rgba(255,255,255,0.2);
}

.filter-status.active {
    background: #22c55e;
}

/* Stats Cards */
.oo-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.oo-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.oo-stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.oo-stats-primary .oo-stats-icon {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.oo-stats-success .oo-stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.oo-stats-info .oo-stats-icon {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
}

.oo-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.oo-stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* Search Box */
.oo-search-container {
    max-width: 400px;
}

.oo-search-box {
    position: relative;
}

.oo-search-box i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #0ea5e9;
    font-size: 1.1rem;
}

.oo-search-box input {
    padding-left: 48px;
    border-radius: 50px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.oo-search-box input:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14,165,233,0.1);
}

/* Process Card */
.oo-process-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.oo-process-card:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

/* Para que todas las cards tengan la misma altura */
.process-card-wrapper .card.h-100 {
    min-height: 420px;
}

/* Process Header */
.oo-process-header {
    padding: 24px 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.oo-process-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
}

.oo-process-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 12px;
    position: relative;
    z-index: 1;
}

.oo-process-name {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    position: relative;
    z-index: 1;
}

/* Process Stats */
.oo-process-stats {
    display: flex;
    padding: 16px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    gap: 12px;
}

.oo-process-stat {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.oo-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.oo-stat-data {
    display: flex;
    flex-direction: column;
}

.oo-stat-value {
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
    color: #1e293b;
}

.oo-stat-label {
    font-size: 0.65rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Background colors */
.bg-success-light { background: rgba(34,197,94,0.15); }

/* Process Body - Machines List */
.oo-process-body {
    padding: 16px 20px;
    flex: 1;
}

.oo-machines-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.oo-machines-list {
    height: 100px;
    overflow: hidden;
}

.oo-machine-empty {
    visibility: hidden;
}

.oo-machine-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    font-size: 0.85rem;
    color: #475569;
}

.oo-machine-bullet {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.oo-machine-more {
    margin-top: 8px;
}

/* Process Footer */
.oo-process-footer {
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    margin-top: auto;
}

.oo-btn-kanban {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.oo-btn-kanban::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.oo-btn-kanban:hover::before {
    left: 100%;
}

.oo-btn-kanban:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    filter: brightness(1.1);
}

/* Empty State */
.oo-empty-state {
    border: 2px dashed #e2e8f0;
    background: #f8fafc;
    border-radius: 16px;
}

.oo-empty-icon {
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

/* Animations */
.process-card-wrapper {
    animation: ooFadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes ooFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 991.98px) {
    .oo-header .row {
        gap: 16px;
    }
}

@media (max-width: 767.98px) {
    .oo-process-stats {
        flex-direction: column;
    }
    .oo-stats-card {
        margin-bottom: 12px;
    }
    .oo-filter-toggle {
        width: 100%;
        justify-content: center;
    }
}

/* Dark mode */
[data-theme="dark"] .oo-process-card {
    background: #1e293b;
}

[data-theme="dark"] .oo-process-stats {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .oo-process-stat {
    background: #1e293b;
}

[data-theme="dark"] .oo-stat-value {
    color: #f1f5f9;
}

[data-theme="dark"] .oo-process-body {
    background: #1e293b;
}

[data-theme="dark"] .oo-machine-item {
    color: #cbd5e1;
}

[data-theme="dark"] .oo-process-footer {
    background: #0f172a;
    border-color: #334155;
}

[data-theme="dark"] .oo-stats-card {
    background: #1e293b;
}

[data-theme="dark"] .oo-stats-info h3 {
    color: #f1f5f9;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Buscador de procesos
    $('#searchProcesses').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();

        $('.process-card-wrapper').each(function() {
            var name = $(this).data('name');
            if (name.indexOf(searchTerm) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Toggle del filtro
    const kanbanFilterToggle = document.getElementById('kanbanFilterToggle');

    if (kanbanFilterToggle) {
        kanbanFilterToggle.addEventListener('change', async function() {
            const isChecked = this.checked;
            const statusBadge = document.getElementById('filterStatusBadge');
            const statusText = document.getElementById('filterStatusText');

            this.disabled = true;

            try {
                const response = await fetch('{{ route("customers.kanban-filter-toggle", $customer) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (data.value) {
                        statusBadge.textContent = '{{ __("ON") }}';
                        statusBadge.classList.add('active');
                        statusText.textContent = '{{ __("Active") }}';
                    } else {
                        statusBadge.textContent = '{{ __("OFF") }}';
                        statusBadge.classList.remove('active');
                        statusText.textContent = '{{ __("Inactive") }}';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Configuration updated") }}',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                } else {
                    this.checked = !isChecked;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || '{{ __("Error changing configuration") }}'
                    });
                }
            } catch (error) {
                console.error('Error toggling filter:', error);
                this.checked = !isChecked;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ __("Connection error") }}'
                });
            } finally {
                this.disabled = false;
            }
        });
    }
});
</script>
@endpush
