@extends('layouts.admin')

@section('title', __('Shift Lists'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Shift Lists') }}</li>
    </ul>
@endsection

@section('content')
<div class="shifts-container">
    {{-- Header Principal --}}
    <div class="shifts-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="d-flex align-items-center">
                    <div class="shifts-header-icon me-3">
                        <i class="ti ti-calendar-time"></i>
                    </div>
                    <div>
                        <h4 class="shifts-title mb-1">{{ __('Shift Lists') }}</h4>
                        <p class="shifts-subtitle mb-0">{{ __('Manage production line shifts and schedules') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 mt-3 mt-lg-0 flex-wrap">
                    <button type="button" class="btn btn-light shifts-btn-action" id="btnAddShift">
                        <i class="ti ti-plus me-1"></i> {{ __('Add Shift') }}
                    </button>
                    <button type="button" class="btn btn-light shifts-btn-action shifts-btn-export" id="btnExportExcel">
                        <i class="ti ti-file-spreadsheet me-1"></i> {{ __('Export') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="ti ti-check me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="shifts-stats-card shifts-stats-primary">
                <div class="shifts-stats-icon">
                    <i class="ti ti-building-factory"></i>
                </div>
                <div class="shifts-stats-info">
                    <h3>{{ $productionLines->count() }}</h3>
                    <span>{{ __('Production Lines') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="shifts-stats-card shifts-stats-success">
                <div class="shifts-stats-icon">
                    <i class="ti ti-player-play"></i>
                </div>
                <div class="shifts-stats-info">
                    <h3 id="statsActiveShifts">
                        {{ $productionLines->filter(function($line) {
                            $last = $line->lastShiftHistory;
                            return $last && $last->type === 'shift' && $last->action === 'start';
                        })->count() }}
                    </h3>
                    <span>{{ __('Active Shifts') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
            <div class="shifts-stats-card shifts-stats-warning">
                <div class="shifts-stats-icon">
                    <i class="ti ti-player-pause"></i>
                </div>
                <div class="shifts-stats-info">
                    <h3 id="statsPausedShifts">
                        {{ $productionLines->filter(function($line) {
                            $last = $line->lastShiftHistory;
                            return $last && $last->type === 'stop' && $last->action === 'start';
                        })->count() }}
                    </h3>
                    <span>{{ __('Paused') }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="shifts-stats-card shifts-stats-info">
                <div class="shifts-stats-icon">
                    <i class="ti ti-settings-automation"></i>
                </div>
                <div class="shifts-stats-info">
                    <h3 id="statsAutoShifts">-</h3>
                    <span>{{ __('Automated') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid de Production Lines --}}
    <div class="shifts-section-title mb-3">
        <i class="ti ti-layout-grid me-2"></i>{{ __('Production Lines Control') }}
    </div>
    <div class="row mb-4" id="productionLinesGrid">
        @foreach($productionLines as $line)
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4 line-card-wrapper" data-line-id="{{ $line->id }}">
            <div class="card shifts-line-card h-100">
                @php
                    $last = $line->lastShiftHistory;
                    $statusClass = 'stopped';
                    $statusText = __('Stopped');
                    $statusIcon = 'ti-player-stop';

                    if ($last) {
                        if ($last->type === 'shift' && $last->action === 'start') {
                            $statusClass = 'active';
                            $statusText = __('Active');
                            $statusIcon = 'ti-player-play';
                        } elseif ($last->type === 'stop' && $last->action === 'start') {
                            $statusClass = 'paused';
                            $statusText = __('Paused');
                            $statusIcon = 'ti-player-pause';
                        } elseif ($last->type === 'stop' && $last->action === 'end') {
                            $statusClass = 'active';
                            $statusText = __('Active');
                            $statusIcon = 'ti-player-play';
                        } elseif ($last->type === 'shift' && $last->action === 'end') {
                            $statusClass = 'stopped';
                            $statusText = __('Stopped');
                            $statusIcon = 'ti-player-stop';
                        }
                    }
                @endphp
                <div class="shifts-line-header {{ $statusClass }}">
                    <div class="shifts-line-status" id="status-badge-{{ $line->id }}">
                        <span class="status-indicator {{ $statusClass }}"></span>
                        <span class="status-text">{{ $statusText }}</span>
                    </div>
                    <h5 class="shifts-line-name">{{ $line->name }}</h5>
                </div>
                <div class="shifts-line-body">
                    <div class="shifts-line-actions" id="actions-{{ $line->id }}">
                        @if($last)
                            @if($last->type === 'shift' && $last->action === 'start')
                                {{-- Turno iniciado: Pausar y Finalizar --}}
                                <button type="button" class="shifts-action-btn shifts-btn-pause"
                                        data-action="inicio_pausa"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('Pause') }}">
                                    <i class="ti ti-player-pause"></i>
                                </button>
                                <button type="button" class="shifts-action-btn shifts-btn-stop"
                                        data-action="final_trabajo"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('End Shift') }}">
                                    <i class="ti ti-player-stop"></i>
                                </button>
                            @elseif($last->type === 'stop' && $last->action === 'start')
                                {{-- Pausa iniciada: Reanudar --}}
                                <button type="button" class="shifts-action-btn shifts-btn-resume"
                                        data-action="final_pausa"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('Resume') }}">
                                    <i class="ti ti-player-play"></i>
                                </button>
                            @elseif($last->type === 'stop' && $last->action === 'end')
                                {{-- Pausa finalizada: Pausar y Finalizar --}}
                                <button type="button" class="shifts-action-btn shifts-btn-pause"
                                        data-action="inicio_pausa"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('Pause') }}">
                                    <i class="ti ti-player-pause"></i>
                                </button>
                                <button type="button" class="shifts-action-btn shifts-btn-stop"
                                        data-action="final_trabajo"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('End Shift') }}">
                                    <i class="ti ti-player-stop"></i>
                                </button>
                            @elseif($last->type === 'shift' && $last->action === 'end')
                                {{-- Turno finalizado: Iniciar --}}
                                <button type="button" class="shifts-action-btn shifts-btn-start"
                                        data-action="inicio_trabajo"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('Start Shift') }}">
                                    <i class="ti ti-player-play"></i>
                                </button>
                            @else
                                {{-- Caso por defecto --}}
                                <button type="button" class="shifts-action-btn shifts-btn-start"
                                        data-action="inicio_trabajo"
                                        data-line-id="{{ $line->id }}"
                                        title="{{ __('Start Shift') }}">
                                    <i class="ti ti-player-play"></i>
                                </button>
                            @endif
                        @else
                            {{-- Sin historial --}}
                            <button type="button" class="shifts-action-btn shifts-btn-start"
                                    data-action="inicio_trabajo"
                                    data-line-id="{{ $line->id }}"
                                    title="{{ __('Start Shift') }}">
                                <i class="ti ti-player-play"></i>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Tabla de Turnos Programados --}}
    <div class="shifts-section-title mb-3">
        <i class="ti ti-calendar-time me-2"></i>{{ __('Scheduled Shifts') }}
    </div>
    <div class="card shifts-table-card mb-4">
        <div class="card-body">
            {{-- Filtro --}}
            <div class="row pb-3 mb-4 border-bottom">
                <div class="col-md-4">
                    <label for="productionLineFilter" class="form-label">{{ __('Filter by Production Line') }}</label>
                    <select id="productionLineFilter" class="form-select shifts-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($productionLines as $line)
                            <option value="{{ $line->id }}">{{ $line->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="shiftTable" class="table shifts-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Production Line') }}</th>
                            <th>{{ __('Start') }}</th>
                            <th>{{ __('End') }}</th>
                            <th>{{ __('Automation') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabla de Historial --}}
    <div class="shifts-section-title mb-3">
        <i class="ti ti-history me-2"></i>{{ __('Shift History') }}
    </div>
    <div class="card shifts-table-card">
        <div class="card-body">
            {{-- Filtros del historial --}}
            <div class="row g-3 mb-3">
                <div class="col-lg-3 col-md-6">
                    <label for="historyProductionLineFilter" class="form-label">{{ __('Production Line') }}</label>
                    <select id="historyProductionLineFilter" class="form-select shifts-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($productionLines as $line)
                            <option value="{{ $line->id }}">{{ $line->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="historyTypeFilter" class="form-label">{{ __('Type') }}</label>
                    <select id="historyTypeFilter" class="form-select shifts-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="shift">{{ __('Shift') }}</option>
                        <option value="stop">{{ __('Pause') }}</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label for="historyActionFilter" class="form-label">{{ __('Action') }}</label>
                    <select id="historyActionFilter" class="form-select shifts-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="start">{{ __('Start') }}</option>
                        <option value="end">{{ __('End') }}</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label for="historyOperatorFilter" class="form-label">{{ __('User') }}</label>
                    <select id="historyOperatorFilter" class="form-select shifts-select">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($operators as $operator)
                            <option value="{{ $operator->id }}">{{ $operator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 col-md-12">
                    <label class="form-label">&nbsp;</label>
                    <button id="resetHistoryFilters" class="btn btn-outline-secondary w-100">
                        <i class="ti ti-refresh me-1"></i> {{ __('Reset') }}
                    </button>
                </div>
            </div>
            {{-- Buscador --}}
            <div class="row pb-3 mb-4 border-bottom">
                <div class="col-12">
                    <div class="shifts-search-box">
                        <i class="ti ti-search"></i>
                        <input type="text" id="historySearchInput" class="form-control" placeholder="{{ __('Search...') }}">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table id="shiftHistoryTable" class="table shifts-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('ID') }}</th>
                            <th>{{ __('Production Line') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Date/Time') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Crear Turno --}}
<div class="modal fade" id="createShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shifts-modal">
            <div class="modal-header shifts-modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-plus me-2"></i>{{ __('Add Shift') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createShiftForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="createProductionLineId" class="form-label">{{ __('Production Line') }} <span class="text-danger">*</span></label>
                        <div class="shifts-input-group">
                            <div class="shifts-input-icon"><i class="ti ti-building-factory"></i></div>
                            <select class="form-select shifts-input" name="production_line_id" id="createProductionLineId" required>
                                <option value="" disabled selected>-- {{ __('Select') }} --</option>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createStartTime" class="form-label">{{ __('Start Time') }} <span class="text-danger">*</span></label>
                            <div class="shifts-input-group">
                                <div class="shifts-input-icon"><i class="ti ti-clock"></i></div>
                                <input type="time" class="form-control shifts-input" name="start" id="createStartTime" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createEndTime" class="form-label">{{ __('End Time') }} <span class="text-danger">*</span></label>
                            <div class="shifts-input-group">
                                <div class="shifts-input-icon"><i class="ti ti-clock-off"></i></div>
                                <input type="time" class="form-control shifts-input" name="end" id="createEndTime" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="form-check form-switch shifts-switch">
                            <input type="checkbox" class="form-check-input" id="createActive" name="active" value="1">
                            <label class="form-check-label" for="createActive">
                                <strong>{{ __('Automation') }}</strong>
                            </label>
                        </div>
                        <small class="text-muted">{{ __('When enabled, the system will automatically start and end the shift at the configured times.') }}</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> {{ __('Save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Editar Turno --}}
<div class="modal fade" id="editShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shifts-modal">
            <div class="modal-header shifts-modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-edit me-2"></i>{{ __('Edit Shift') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editShiftForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editShiftId" name="id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="editProductionLineId" class="form-label">{{ __('Production Line') }} <span class="text-danger">*</span></label>
                        <div class="shifts-input-group">
                            <div class="shifts-input-icon"><i class="ti ti-building-factory"></i></div>
                            <select class="form-select shifts-input" id="editProductionLineId" name="production_line_id" required>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editStartTime" class="form-label">{{ __('Start Time') }} <span class="text-danger">*</span></label>
                            <div class="shifts-input-group">
                                <div class="shifts-input-icon"><i class="ti ti-clock"></i></div>
                                <input type="time" class="form-control shifts-input" id="editStartTime" name="start" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEndTime" class="form-label">{{ __('End Time') }} <span class="text-danger">*</span></label>
                            <div class="shifts-input-group">
                                <div class="shifts-input-icon"><i class="ti ti-clock-off"></i></div>
                                <input type="time" class="form-control shifts-input" id="editEndTime" name="end" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <input type="hidden" name="active" value="0">
                        <div class="form-check form-switch shifts-switch">
                            <input type="checkbox" class="form-check-input" id="editActive" name="active" value="1">
                            <label class="form-check-label" for="editActive">
                                <strong>{{ __('Automation') }}</strong>
                            </label>
                        </div>
                        <small class="text-muted">{{ __('When enabled, the system will automatically start and end the shift at the configured times.') }}</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> {{ __('Update') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal de Carga --}}
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shifts-loading-modal">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-white" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                </div>
                <h5 class="mt-3 text-white mb-0">{{ __('Processing, please wait...') }}</h5>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<style>
/* Container */
.shifts-container {
    padding: 0;
}

/* Header */
.shifts-header {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    border-radius: 16px;
    padding: 24px;
    color: white;
}

.shifts-header-icon {
    width: 56px;
    height: 56px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
}

.shifts-title {
    color: white;
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.shifts-subtitle {
    color: rgba(255,255,255,0.85);
    font-size: 0.95rem;
}

/* Action Buttons */
.shifts-btn-action {
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
    background: white;
    color: #0ea5e9;
    border: none;
    transition: all 0.3s ease;
}

.shifts-btn-action:hover {
    background: #f8fafc;
    color: #0284c7;
    transform: translateY(-2px);
}

.shifts-btn-export {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    color: white !important;
}

.shifts-btn-export:hover {
    color: white !important;
    box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);
}

/* Stats Cards */
.shifts-stats-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.shifts-stats-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.shifts-stats-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.shifts-stats-primary .shifts-stats-icon {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.shifts-stats-success .shifts-stats-icon {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.shifts-stats-warning .shifts-stats-icon {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.shifts-stats-info .shifts-stats-icon {
    background: rgba(99, 102, 241, 0.15);
    color: #6366f1;
}

.shifts-stats-info h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: #1e293b;
}

.shifts-stats-info span {
    color: #64748b;
    font-size: 0.875rem;
}

/* Section Title */
.shifts-section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
}

.shifts-section-title i {
    color: #0ea5e9;
}

/* Production Line Cards */
.shifts-line-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
}

.shifts-line-card:hover {
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    transform: translateY(-4px);
}

.line-card-wrapper {
    animation: shiftsFadeInUp 0.4s ease forwards;
    opacity: 0;
}

@keyframes shiftsFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.line-card-wrapper:nth-child(1) { animation-delay: 0.05s; }
.line-card-wrapper:nth-child(2) { animation-delay: 0.1s; }
.line-card-wrapper:nth-child(3) { animation-delay: 0.15s; }
.line-card-wrapper:nth-child(4) { animation-delay: 0.2s; }
.line-card-wrapper:nth-child(5) { animation-delay: 0.25s; }
.line-card-wrapper:nth-child(6) { animation-delay: 0.3s; }
.line-card-wrapper:nth-child(7) { animation-delay: 0.35s; }
.line-card-wrapper:nth-child(8) { animation-delay: 0.4s; }

/* Line Header */
.shifts-line-header {
    padding: 20px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.shifts-line-header.active {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.shifts-line-header.paused {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.shifts-line-header.stopped {
    background: linear-gradient(135deg, #64748b 0%, #475569 100%);
}

.shifts-line-name {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0;
}

.shifts-line-status {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-bottom: 8px;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: white;
}

.status-indicator.active {
    animation: pulse-green 2s infinite;
}

.status-indicator.paused {
    animation: pulse-yellow 2s infinite;
}

@keyframes pulse-green {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); }
    50% { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
}

@keyframes pulse-yellow {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.7); }
    50% { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
}

.status-text {
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Line Body */
.shifts-line-body {
    padding: 20px;
    background: white;
}

.shifts-line-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
}

/* Action Buttons in Cards */
.shifts-action-btn {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.shifts-action-btn:hover {
    transform: scale(1.1);
}

.shifts-btn-start {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.shifts-btn-start:hover {
    box-shadow: 0 8px 25px rgba(34, 197, 94, 0.4);
}

.shifts-btn-pause {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.shifts-btn-pause:hover {
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
}

.shifts-btn-stop {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.shifts-btn-stop:hover {
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
}

.shifts-btn-resume {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
}

.shifts-btn-resume:hover {
    box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
}

/* Table Card */
.shifts-table-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow: hidden;
}

/* Search Box */
.shifts-search-box {
    position: relative;
}

.shifts-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1.1rem;
}

.shifts-search-box input {
    padding-left: 42px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    height: 46px;
    width: 100%;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.shifts-search-box input:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

/* Select */
.shifts-select {
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    padding: 10px 14px;
    transition: all 0.3s ease;
}

.shifts-select:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

/* Table Styles */
.shifts-table {
    border-collapse: separate;
    border-spacing: 0;
}

.shifts-table thead th {
    background: #f8fafc;
    border: none;
    padding: 16px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.shifts-table tbody td {
    padding: 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.shifts-table tbody tr {
    transition: all 0.3s ease;
}

.shifts-table tbody tr:hover {
    background: #f8fafc;
}

/* Badges */
.shifts-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.shifts-badge-auto {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
}

.shifts-badge-manual {
    background: #e2e8f0;
    color: #64748b;
}

.shifts-badge-shift {
    background: rgba(14, 165, 233, 0.15);
    color: #0ea5e9;
}

.shifts-badge-stop {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
}

.shifts-badge-start {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}

.shifts-badge-end {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

/* Table Action Buttons */
.shifts-table-btn {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.shifts-table-btn-edit {
    background: rgba(59, 130, 246, 0.15);
    color: #3b82f6;
}

.shifts-table-btn-edit:hover {
    background: #3b82f6;
    color: white;
}

.shifts-table-btn-delete {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.shifts-table-btn-delete:hover {
    background: #ef4444;
    color: white;
}

/* Modal Styles */
.shifts-modal {
    border-radius: 16px;
    border: none;
    overflow: hidden;
}

.shifts-modal-header {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    color: white;
    border: none;
    padding: 20px 24px;
}

.shifts-modal-header .modal-title {
    font-weight: 700;
}

/* Input Group */
.shifts-input-group {
    position: relative;
}

.shifts-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 1rem;
    z-index: 1;
}

.shifts-input {
    padding-left: 42px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    height: 46px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.shifts-input:focus {
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

/* Switch */
.shifts-switch .form-check-input {
    width: 48px;
    height: 24px;
    cursor: pointer;
}

.shifts-switch .form-check-input:checked {
    background-color: #0ea5e9;
    border-color: #0ea5e9;
}

/* Loading Modal */
.shifts-loading-modal {
    background: rgba(0, 0, 0, 0.8);
    border: none;
    border-radius: 16px;
}

/* DataTables Overrides */
.dataTables_wrapper .dt-buttons {
    margin-bottom: 16px;
}

.dataTables_wrapper .dt-buttons .btn {
    border-radius: 10px;
    padding: 8px 16px;
    font-weight: 600;
    font-size: 0.85rem;
}

.dataTables_info, .dataTables_paginate {
    margin-top: 16px;
}

.dataTables_paginate .paginate_button {
    border-radius: 8px !important;
    margin: 0 2px;
}

.dataTables_paginate .paginate_button.current {
    background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%) !important;
    border-color: transparent !important;
    color: white !important;
}

/* Dark Mode */
[data-theme="dark"] .shifts-line-card,
[data-theme="dark"] .shifts-stats-card,
[data-theme="dark"] .shifts-table-card {
    background: #1e293b;
}

[data-theme="dark"] .shifts-line-body {
    background: #1e293b;
}

[data-theme="dark"] .shifts-section-title {
    color: #f1f5f9;
}

[data-theme="dark"] .shifts-stats-info h3 {
    color: #f1f5f9;
}

[data-theme="dark"] .shifts-table thead th {
    background: #0f172a;
    color: #94a3b8;
}

[data-theme="dark"] .shifts-table tbody td {
    border-color: #334155;
}

[data-theme="dark"] .shifts-table tbody tr:hover {
    background: #334155;
}

[data-theme="dark"] .shifts-input,
[data-theme="dark"] .shifts-select {
    background: #0f172a;
    border-color: #334155;
    color: #f1f5f9;
}

/* Responsive */
@media (max-width: 991.98px) {
    .shifts-header .row {
        gap: 16px;
    }
}

@media (max-width: 767.98px) {
    .shifts-action-btn {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }

    .shifts-stats-card {
        margin-bottom: 12px;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Configuración global AJAX con CSRF
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// URLs
const baseUrl = window.location.origin;
const apiIndexUrl = `${baseUrl}/shift-lists/api`;
const storeUrl = `${baseUrl}/shift-lists`;
const updateUrlTemplate = `${baseUrl}/shift-lists/:id`;
const deleteUrlTemplate = `${baseUrl}/shift-lists/:id`;

// Variables globales
let shiftTable;
let historyTable;
let loadingModal = null;

$(document).ready(function() {
    // Botón añadir turno
    $('#btnAddShift').on('click', function() {
        $('#createShiftForm')[0].reset();
        $('#createShiftModal').modal('show');
    });

    // Botón exportar Excel
    $('#btnExportExcel').on('click', function() {
        if (shiftTable) {
            shiftTable.button('.buttons-excel').trigger();
        }
    });

    // Inicializar DataTable de turnos
    shiftTable = $('#shiftTable').DataTable({
        dom: '<"d-none"B>rtip',
        buttons: [
            {
                extend: 'excel',
                text: '{{ __("Export to Excel") }}',
                className: 'buttons-excel'
            }
        ],
        order: [[1, 'asc']],
        processing: true,
        ajax: {
            url: apiIndexUrl,
            dataSrc: 'data',
            data: function(d) {
                d.production_line = $('#productionLineFilter').val();
            },
            error: function(xhr, status, error) {
                console.error("Error loading DataTable data:", status, error, xhr);
                Swal.fire('Error', '{{ __("Error loading data.") }}', 'error');
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'production_line.name', name: 'production_line.name', defaultContent: '{{ __("No line") }}' },
            { data: 'start', name: 'start' },
            { data: 'end', name: 'end' },
            {
                data: 'active',
                name: 'active',
                orderable: true,
                searchable: false,
                render: function(data, type, row) {
                    const isActive = Number(row.active) === 1;
                    if (type === 'display') {
                        return isActive
                            ? '<span class="shifts-badge shifts-badge-auto"><i class="ti ti-robot me-1"></i>{{ __("Auto") }}</span>'
                            : '<span class="shifts-badge shifts-badge-manual"><i class="ti ti-hand-stop me-1"></i>{{ __("Manual") }}</span>';
                    }
                    return isActive ? 'Auto' : 'Manual';
                }
            },
            {
                data: null,
                name: 'actions',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <button class="shifts-table-btn shifts-table-btn-edit edit-shift me-1"
                            title="{{ __('Edit') }}"
                            data-id="${row.id}"
                            data-production-line-id="${row.production_line_id}"
                            data-start="${row.start}"
                            data-end="${row.end}"
                            data-active="${row.active ?? 0}">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button class="shifts-table-btn shifts-table-btn-delete delete-shift"
                            title="{{ __('Delete') }}"
                            data-id="${row.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    `;
                }
            }
        ],
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() == "es" ? "es-ES" : "en-GB" }}.json'
        },
        drawCallback: function() {
            // Actualizar estadística de turnos automáticos
            const data = this.api().data();
            let autoCount = 0;
            data.each(function(row) {
                if (Number(row.active) === 1) autoCount++;
            });
            $('#statsAutoShifts').text(autoCount);
        }
    });

    // Filtro de línea de producción
    $('#productionLineFilter').on('change', function() {
        shiftTable.ajax.reload();
    });

    // Editar turno
    $('#shiftTable tbody').on('click', '.edit-shift', function() {
        const button = $(this);
        $('#editShiftId').val(button.data('id'));
        $('#editProductionLineId').val(button.data('production-line-id'));
        $('#editStartTime').val(button.data('start') ? button.data('start').substring(0, 5) : '');
        $('#editEndTime').val(button.data('end') ? button.data('end').substring(0, 5) : '');
        $('#editActive').prop('checked', Number(button.data('active')) === 1);
        $('#editShiftModal').modal('show');
    });

    // Eliminar turno
    $('#shiftTable tbody').on('click', '.delete-shift', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: '{{ __("Are you sure?") }}',
            text: '{{ __("You will not be able to recover this shift!") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: '{{ __("Yes, delete it!") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoadingModal();

                $.ajax({
                    url: deleteUrlTemplate.replace(':id', id),
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        hideLoadingModal();
                        if (response.success) {
                            Swal.fire('{{ __("Deleted!") }}', '{{ __("The shift has been deleted.") }}', 'success');
                            shiftTable.ajax.reload();
                        } else {
                            Swal.fire('{{ __("Error") }}', response.message || '{{ __("Could not delete the shift.") }}', 'error');
                        }
                    },
                    error: function(xhr) {
                        hideLoadingModal();
                        Swal.fire('{{ __("Error") }}', xhr.responseJSON?.message || '{{ __("Server error.") }}', 'error');
                    }
                });
            }
        });
    });

    // Crear turno
    $('#createShiftForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        formData.append('production_line_id', $('#createProductionLineId').val());
        formData.append('start', $('#createStartTime').val());
        formData.append('end', $('#createEndTime').val());
        formData.append('active', $('#createActive').is(':checked') ? 1 : 0);

        showLoadingModal();

        fetch(storeUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) return response.json().then(err => Promise.reject(err));
            return response.json();
        })
        .then(data => {
            hideLoadingModal();
            if (data.success) {
                $('#createShiftModal').modal('hide');
                $('#createShiftForm')[0].reset();
                Swal.fire('{{ __("Success") }}', '{{ __("Shift created successfully") }}', 'success');
                shiftTable.ajax.reload();
                updateShiftStatuses();
            } else {
                throw new Error(data.message || '{{ __("Error creating shift") }}');
            }
        })
        .catch(error => {
            hideLoadingModal();
            Swal.fire('{{ __("Error") }}', error.message || '{{ __("Server error.") }}', 'error');
        });
    });

    // Actualizar turno
    $('#editShiftForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#editShiftId').val();
        const url = updateUrlTemplate.replace(':id', id);

        showLoadingModal();

        $.ajax({
            url: url,
            method: 'POST',
            data: $(this).serialize()
        })
        .done(response => {
            hideLoadingModal();
            if (response.success) {
                Swal.fire('{{ __("Updated") }}', response.message, 'success');
                shiftTable.ajax.reload(null, false);
                $('#editShiftModal').modal('hide');
            } else {
                Swal.fire('{{ __("Error") }}', response.message || '{{ __("Could not update.") }}', 'error');
            }
        })
        .fail(xhr => {
            hideLoadingModal();
            Swal.fire('{{ __("Error") }}', xhr.responseJSON?.message || '{{ __("Server error.") }}', 'error');
        });
    });

    // Botones de acción de líneas de producción
    $(document).on('click', 'button[data-action]', function(e) {
        const button = $(this);
        if (button.closest('.modal').length > 0 || button.closest('#shiftTable').length > 0) return;

        const action = button.data('action');
        const lineId = button.data('line-id');

        button.prop('disabled', true).css('opacity', '0.5');

        $.ajax({
            url: "/shift-event",
            method: "POST",
            data: {
                production_line_id: lineId,
                event: action
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: response.message || '{{ __("Action sent successfully") }}',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const err = xhr.responseJSON?.message || '{{ __("Error sending action.") }}';
                Swal.fire('Error', err, 'error');
                button.prop('disabled', false).css('opacity', '1');
            }
        });
    });

    // Inicializar tabla de historial
    initializeHistoryTable();

    // Actualizar estados
    updateShiftStatuses();
    setInterval(updateShiftStatuses, 5000);
});

// Funciones auxiliares
function showLoadingModal() {
    const modalElement = document.getElementById('loadingModal');
    if (modalElement) {
        if (loadingModal) {
            try { loadingModal.hide(); } catch(e) {}
            loadingModal.dispose();
        }
        loadingModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
        loadingModal.show();
    }
}

function hideLoadingModal() {
    if (loadingModal) {
        try {
            loadingModal.hide();
            setTimeout(() => {
                if (loadingModal) {
                    loadingModal.dispose();
                    loadingModal = null;
                }
            }, 300);
        } catch (e) {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            loadingModal = null;
        }
    }
}

function updateShiftStatuses() {
    $.get(`${baseUrl}/api/shift/statuses`)
        .done(function(response) {
            updateShiftButtons(response);
        })
        .fail(function(error) {
            console.error('Error updating statuses:', error);
        });
}

function updateShiftButtons(statuses) {
    let activeCount = 0;
    let pausedCount = 0;

    statuses.forEach(status => {
        const lineId = status.line_id;
        const lastShift = status.last_shift;
        const header = $(`.line-card-wrapper[data-line-id="${lineId}"] .shifts-line-header`);
        const statusBadge = $(`#status-badge-${lineId}`);
        const actionsContainer = $(`#actions-${lineId}`);

        if (!header.length) return;

        let statusClass = 'stopped';
        let statusText = '{{ __("Stopped") }}';
        let buttons = '';

        if (!lastShift) {
            buttons = `
                <button type="button" class="shifts-action-btn shifts-btn-start"
                        data-action="inicio_trabajo" data-line-id="${lineId}"
                        title="{{ __('Start Shift') }}">
                    <i class="ti ti-player-play"></i>
                </button>`;
        } else if (lastShift.type === 'shift' && lastShift.action === 'start') {
            statusClass = 'active';
            statusText = '{{ __("Active") }}';
            activeCount++;
            buttons = `
                <button type="button" class="shifts-action-btn shifts-btn-pause"
                        data-action="inicio_pausa" data-line-id="${lineId}"
                        title="{{ __('Pause') }}">
                    <i class="ti ti-player-pause"></i>
                </button>
                <button type="button" class="shifts-action-btn shifts-btn-stop"
                        data-action="final_trabajo" data-line-id="${lineId}"
                        title="{{ __('End Shift') }}">
                    <i class="ti ti-player-stop"></i>
                </button>`;
        } else if (lastShift.type === 'stop' && lastShift.action === 'start') {
            statusClass = 'paused';
            statusText = '{{ __("Paused") }}';
            pausedCount++;
            buttons = `
                <button type="button" class="shifts-action-btn shifts-btn-resume"
                        data-action="final_pausa" data-line-id="${lineId}"
                        title="{{ __('Resume') }}">
                    <i class="ti ti-player-play"></i>
                </button>`;
        } else if (lastShift.type === 'stop' && lastShift.action === 'end') {
            statusClass = 'active';
            statusText = '{{ __("Active") }}';
            activeCount++;
            buttons = `
                <button type="button" class="shifts-action-btn shifts-btn-pause"
                        data-action="inicio_pausa" data-line-id="${lineId}"
                        title="{{ __('Pause') }}">
                    <i class="ti ti-player-pause"></i>
                </button>
                <button type="button" class="shifts-action-btn shifts-btn-stop"
                        data-action="final_trabajo" data-line-id="${lineId}"
                        title="{{ __('End Shift') }}">
                    <i class="ti ti-player-stop"></i>
                </button>`;
        } else if (lastShift.type === 'shift' && lastShift.action === 'end') {
            buttons = `
                <button type="button" class="shifts-action-btn shifts-btn-start"
                        data-action="inicio_trabajo" data-line-id="${lineId}"
                        title="{{ __('Start Shift') }}">
                    <i class="ti ti-player-play"></i>
                </button>`;
        } else {
            buttons = `
                <button type="button" class="shifts-action-btn shifts-btn-start"
                        data-action="inicio_trabajo" data-line-id="${lineId}"
                        title="{{ __('Start Shift') }}">
                    <i class="ti ti-player-play"></i>
                </button>`;
        }

        header.removeClass('active paused stopped').addClass(statusClass);
        statusBadge.html(`
            <span class="status-indicator ${statusClass}"></span>
            <span class="status-text">${statusText}</span>
        `);
        actionsContainer.html(buttons);
    });

    $('#statsActiveShifts').text(activeCount);
    $('#statsPausedShifts').text(pausedCount);
}

function initializeHistoryTable() {
    if ($.fn.DataTable.isDataTable('#shiftHistoryTable')) {
        $('#shiftHistoryTable').DataTable().destroy();
    }

    historyTable = $('#shiftHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: `${baseUrl}/api/shift-history`,
            type: 'GET',
            data: function(d) {
                d.production_line_id = $('#historyProductionLineFilter').val() || '';
                d.type = $('#historyTypeFilter').val() || '';
                d.action = $('#historyActionFilter').val() || '';
                d.operator_id = $('#historyOperatorFilter').val() || '';
                if ($('#historySearchInput').val()) {
                    d.search = { value: $('#historySearchInput').val(), regex: false };
                }
                return d;
            },
            dataSrc: function(json) {
                if (!json.data || json.data.length === 0) return [];
                return json.data.map(item => ({
                    id: item.id,
                    production_line: item.production_line || null,
                    type: item.type,
                    action: item.action,
                    operator: item.operator || null,
                    created_at: item.created_at
                }));
            },
            error: function(xhr, error, thrown) {
                console.error('Error loading history:', error);
            }
        },
        columns: [
            { data: 'id', name: 'id', className: 'text-center' },
            {
                data: 'production_line', name: 'production_line.name',
                render: function(data) { return data ? data.name : 'N/A'; }
            },
            {
                data: 'type', name: 'type', className: 'text-center',
                render: function(data) {
                    const types = { 'shift': '{{ __("Shift") }}', 'stop': '{{ __("Pause") }}' };
                    const badgeClass = data === 'shift' ? 'shifts-badge-shift' : 'shifts-badge-stop';
                    return `<span class="shifts-badge ${badgeClass}">${types[data] || data}</span>`;
                }
            },
            {
                data: 'action', name: 'action', className: 'text-center',
                render: function(data) {
                    const actions = { 'start': '{{ __("Start") }}', 'end': '{{ __("End") }}' };
                    const badgeClass = data === 'start' ? 'shifts-badge-start' : 'shifts-badge-end';
                    return `<span class="shifts-badge ${badgeClass}">${actions[data] || data}</span>`;
                }
            },
            {
                data: 'operator', name: 'operator.name',
                render: function(data) { return data ? data.name : '{{ __("System") }}'; }
            },
            {
                data: 'created_at', name: 'created_at', className: 'text-center',
                render: function(data) {
                    if (!data) return '';
                    const date = new Date(data);
                    return date.toLocaleString('{{ app()->getLocale() }}', {
                        day: '2-digit', month: '2-digit', year: 'numeric',
                        hour: '2-digit', minute: '2-digit'
                    });
                }
            }
        ],
        paging: true,
        pageLength: 10,
        searching: false,
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/{{ app()->getLocale() == "es" ? "es-ES" : "en-GB" }}.json'
        }
    });

    // Eventos de filtrado
    $('#historyProductionLineFilter, #historyTypeFilter, #historyActionFilter, #historyOperatorFilter').on('change', function() {
        historyTable.ajax.reload();
    });

    let searchTimeout;
    $('#historySearchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            historyTable.ajax.reload();
        }, 500);
    });

    $('#resetHistoryFilters').on('click', function() {
        $('#historyProductionLineFilter, #historyTypeFilter, #historyActionFilter, #historyOperatorFilter').val('');
        $('#historySearchInput').val('');
        historyTable.ajax.reload();
    });

    // Auto-refresh cada 30 segundos
    setInterval(function() {
        if (historyTable) {
            historyTable.ajax.reload(null, false);
        }
    }, 30000);
}
</script>
@endpush
