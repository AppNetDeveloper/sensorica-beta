@extends('layouts.admin')
@section('title')
    {{ config('app.name') }}
@endsection

@push('style')
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
@endpush

@section('content')
    <!-- Verificar si el usuario tiene al menos un permiso para ver los widgets -->
    @php
        $hasAnyPermission = auth()->user()->can('manage-user') || 
                           auth()->user()->can('manage-role') || 
                           auth()->user()->can('manage-module') || 
                           auth()->user()->can('manage-langauge');
    @endphp
    
    @if(!$hasAnyPermission)
        <!-- Mostrar tarjeta de bienvenida si no tiene permisos para ver los widgets -->
        <div class="row">
            <div class="col-12 animate-card">
                <div class="card welcome-card">
                    <div class="card-body text-center p-5">
                        <div class="welcome-icon">
                            <i class="ti ti-user-check"></i>
                        </div>
                        <h4 class="mb-3">{{ __('¡Bienvenido a tu panel de control!') }}</h4>
                        <p class="text-muted mb-4">
                            {{ __('Actualmente no tienes asignados permisos específicos para ver los widgets del dashboard.') }}
                        </p>
                        <p class="text-muted mb-0">
                            {{ __('Por favor, contacta con el administrador del sistema si necesitas acceso a funcionalidades adicionales.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Widgets normales para usuarios con permisos -->
        <div class="row">
        <!-- [ sample-page ] start -->
        <!-- analytic card start -->

        <!-- Widget de Order Organizer (Grupos y Máquinas) - permiso productionline-kanban -->
        @can('productionline-kanban')
        @if(isset($orderOrganizerStats) && $orderOrganizerStats)
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerModal">
                <div class="card modern-stat-card stat-card-indigo" data-kpi="orderOrganizer">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Order Organizer') }} <span class="kpi-trend"></span></h6>
                                <h3><span class="kpi-value" data-field="groups">{{ $orderOrganizerStats['groups'] }}</span> <small style="font-size: 0.5em;">{{ __('groups') }}</small></h3>
                                <small class="text-white-50"><span class="kpi-machines">{{ $orderOrganizerStats['machines'] }}</span> {{ __('machines') }}</small>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-layout-kanban"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @endcan

        <!-- Widget de Original Orders (Pedidos) - permiso productionline-orders -->
        @can('productionline-orders')
        @if(isset($originalOrdersStats) && $originalOrdersStats)
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerOrdersModal">
                <div class="card modern-stat-card stat-card-teal" data-kpi="pendingOrders">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Pending Orders') }} <span class="kpi-trend"></span></h6>
                                <h3><span class="kpi-value" data-field="total">{{ $originalOrdersStats['total'] }}</span> <small style="font-size: 0.5em;">{{ __('pending') }}</small></h3>
                                <small class="text-white-50">
                                    <span class="kpi-in-progress">{{ $originalOrdersStats['in_progress'] }}</span> {{ __('in progress') }}
                                </small>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-clipboard-list"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @endcan

        <!-- Widget de Mantenimiento - permiso maintenance-show -->
        @can('maintenance-show')
        @if(isset($maintenanceStats) && $maintenanceStats)
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerMaintenanceModal">
                <div class="card modern-stat-card stat-card-primary" data-kpi="maintenance">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Maintenance') }} <span class="kpi-trend"></span></h6>
                                <h3 class="kpi-value">{{ $maintenanceStats['pending'] }}</h3>
                                <small class="text-white-50">{{ __('last 7 days') }}</small>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-tool"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @endcan

        <!-- Widget de trabajadores si tiene permiso -->
        @can('workers-show')
        <div class="kpi-card-col mb-4 animate-card">
            <a href="{{ route('workers-admin.index') }}" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-warning" data-kpi="workers">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Total Workers') }} <span class="kpi-trend"></span></h6>
                                <h3 class="kpi-value">{{ $operatorsCount }}</h3>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-user-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        <!-- Widgets de Incidencias y Control de Calidad - permiso productionline-incidents -->
        @can('productionline-incidents')
        @if(isset($incidentsStats) && $incidentsStats)
        <!-- QC Confirmations (últimas 24h) -->
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerQcModal">
                <div class="card modern-stat-card stat-card-info" data-kpi="qcConfirmations">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('QC Confirmations') }} <span class="kpi-trend"></span></h6>
                                <h3 class="kpi-value">{{ $incidentsStats['qc_confirmations'] }}</h3>
                                <small class="text-white-50">{{ __('last 24 hours') }}</small>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-clipboard-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Production Order Incidents (en curso) -->
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerProductionIncidentsModal">
                <div class="card modern-stat-card stat-card-danger" data-kpi="productionIncidents">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Production Incidents') }} <span class="kpi-trend"></span></h6>
                                <h3 class="kpi-value">{{ $incidentsStats['production_incidents'] }}</h3>
                                <small class="text-white-50">{{ __('active') }}</small>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-alert-triangle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Quality Issues -->
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerQualityIssuesModal">
                <div class="card modern-stat-card stat-card-warning" data-kpi="qualityIssues">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Quality Issues') }} <span class="kpi-trend"></span></h6>
                                <h3 class="kpi-value">{{ $incidentsStats['quality_issues'] }}</h3>
                                <small class="text-white-50">{{ __('last 24 hours') }}</small>
                                <div class="kpi-sparkline mt-2"></div>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-flask"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endif
        @endcan

        <!-- Widgets de turnos si tiene permiso -->
        @can('shift-show')
        <!-- Resumen de líneas de producción -->
        <div class="kpi-card-col mb-4 animate-card">
            <a href="{{ route('shift.index') }}" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-purple">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Production Lines') }}</h6>
                                <h3>{{ $productionLineStats['total'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Estado de líneas activas -->
        <div class="kpi-card-col mb-4 animate-card">
            <a href="{{ route('shift.index') }}" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-success">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Active Lines') }}</h6>
                                <h3>{{ $productionLineStats['active'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-player-play"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Estado de líneas en pausa o paradas -->
        <div class="kpi-card-col mb-4 animate-card">
            <a href="{{ route('shift.index') }}" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-warning">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Paused/Stopped Lines') }}</h6>
                                <h3>{{ $productionLineStats['paused'] + $productionLineStats['stopped'] }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-player-pause"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        <!-- Gráfica de Pedidos Finalizados - después de todos los KPIs -->
        @can('productionline-orders')
        <div class="col-12 mb-4 animate-card">
            <div class="card chart-card">
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-sm-5">
                            <h4 class="card-title mb-0">
                                <i class="ti ti-chart-bar me-2"></i>
                                {{ __('Completed Orders') }}
                            </h4>
                        </div>

                        <div class="col-sm-7 d-none d-md-block">
                            <div class="btn-group float-end" role="group">
                                <button type="button" class="btn btn-chart-toggle active" id="option1">
                                    <i class="ti ti-calendar-week me-1"></i>
                                    {{ __('Last 7 days') }}
                                </button>
                                <button type="button" class="btn btn-chart-toggle" id="option2">
                                    <i class="ti ti-calendar-month me-1"></i>
                                    {{ __('Last 30 days') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="c-chart-wrapper chartbtn">
                        <canvas class="chart" id="main-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <!-- Tabla resumen de líneas de producción -->
        @can('shift-show')
        <div class="col-xl-12 col-md-12 mb-4 animate-card">
            <div class="card modern-table-card">
                <div class="card-header">
                    <h5>{{ __('Production Lines Status') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Line') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Last Update') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productionLines as $line)
                                    <tr>
                                        <td><strong>{{ $line->name }}</strong></td>
                                        <td>
                                            @if($line->lastShiftHistory)
                                                @php
                                                    $badgeClass = 'modern-badge badge-inactive';
                                                    $badgeIcon = 'ti ti-point';
                                                    $statusText = __('Unknown');

                                                    // Debug: ver qué valores tiene
                                                    $action = strtolower(trim($line->lastShiftHistory->action ?? ''));
                                                    $type = strtolower(trim($line->lastShiftHistory->type ?? ''));

                                                    // Verificar si es un turno activo o reanudado
                                                    if ($action == 'start' || ($type === 'stop' && $action === 'end')) {
                                                        $badgeClass = 'modern-badge badge-active';
                                                        $badgeIcon = 'ti ti-player-play';
                                                        $statusText = __('Active');
                                                    }
                                                    // Pausa
                                                    elseif ($action == 'pause') {
                                                        $badgeClass = 'modern-badge badge-paused';
                                                        $badgeIcon = 'ti ti-player-pause';
                                                        $statusText = __('Paused');
                                                    }
                                                    // Stop/Parada
                                                    elseif ($action == 'stop' || $action == 'end') {
                                                        $badgeClass = 'modern-badge badge-stopped';
                                                        $badgeIcon = 'ti ti-player-stop';
                                                        $statusText = __('Stopped');
                                                    }
                                                    // Incidencia
                                                    elseif ($action == 'incident' || $type == 'incident') {
                                                        $badgeClass = 'modern-badge badge-incident';
                                                        $badgeIcon = 'ti ti-alert-triangle';
                                                        $statusText = __('Incident');
                                                    }
                                                    // Si aún es unknown, mostrar el valor real para debug
                                                    else {
                                                        $statusText = __('Unknown') . ' (' . $action . ')';
                                                    }
                                                @endphp
                                                <span class="{{ $badgeClass }}">
                                                    <i class="{{ $badgeIcon }}"></i>
                                                    {{ $statusText }}
                                                </span>
                                            @else
                                                <span class="modern-badge badge-inactive">
                                                    <i class="ti ti-point"></i>
                                                    {{ __('Inactive') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($line->lastShiftHistory)
                                                <span class="text-muted">
                                                    <i class="ti ti-clock me-1"></i>
                                                    {{ \Carbon\Carbon::parse($line->lastShiftHistory->created_at)->format('d/m/Y H:i') }}
                                                </span>
                                            @else
                                                <span class="text-muted">{{ __('Never') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-4">
                                            <i class="ti ti-chart-line display-4 text-muted mb-2 d-block"></i>
                                            <p class="text-muted mb-0">{{ __('No production lines found') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endcan

    </div>
    <!-- [ Main Content ] end -->
        </div> <!-- Cierre del row de widgets -->
    @endif
    <!-- [ Main Content ] end -->

    <!-- Modal para seleccionar Centro de Producción -->
    @can('productionline-kanban')
    @if(isset($customersForKanban) && $customersForKanban && $customersForKanban->count() > 0)
    <div class="modal fade" id="selectCustomerModal" tabindex="-1" aria-labelledby="selectCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="selectCustomerModalLabel">
                        <i class="ti ti-building me-2"></i>{{ __('Select Production Center') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($customersForKanban as $customer)
                        <a href="{{ route('customers.order-organizer', $customer->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="ti ti-building-factory me-2 text-primary"></i>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                            <span class="badge bg-primary rounded-pill">
                                {{ $customer->productionLines->filter(fn($l) => $l->processes->isNotEmpty())->count() }} {{ __('machines') }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endcan

    <!-- Modal para seleccionar Centro de Producción (Original Orders) -->
    @can('productionline-orders')
    @if(isset($customersForOrders) && $customersForOrders && $customersForOrders->count() > 0)
    <div class="modal fade" id="selectCustomerOrdersModal" tabindex="-1" aria-labelledby="selectCustomerOrdersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-teal text-white" style="background: linear-gradient(135deg, #20c997 0%, #0ca678 100%);">
                    <h5 class="modal-title" id="selectCustomerOrdersModalLabel">
                        <i class="ti ti-clipboard-list me-2"></i>{{ __('Select Production Center') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($customersForOrders as $customer)
                        <a href="{{ route('customers.original-orders.index', $customer->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="ti ti-building-factory me-2 text-teal"></i>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                            <span class="badge bg-teal rounded-pill" style="background-color: #20c997 !important;">
                                {{ __('Orders') }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endcan

    <!-- Modal para seleccionar Centro de Producción (QC Confirmations) -->
    @can('productionline-incidents')
    @if(isset($customersForIncidents) && $customersForIncidents && $customersForIncidents->count() > 0)
    <div class="modal fade" id="selectCustomerQcModal" tabindex="-1" aria-labelledby="selectCustomerQcModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="selectCustomerQcModalLabel">
                        <i class="ti ti-clipboard-check me-2"></i>{{ __('Select Production Center') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($customersForIncidents as $customer)
                        <a href="{{ route('customers.qc-confirmations.index', $customer->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="ti ti-building-factory me-2 text-info"></i>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                            <span class="badge bg-info rounded-pill">{{ __('QC') }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar Centro de Producción (Production Incidents) -->
    <div class="modal fade" id="selectCustomerProductionIncidentsModal" tabindex="-1" aria-labelledby="selectCustomerProductionIncidentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="selectCustomerProductionIncidentsModalLabel">
                        <i class="ti ti-alert-triangle me-2"></i>{{ __('Select Production Center') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($customersForIncidents as $customer)
                        <a href="{{ route('customers.production-order-incidents.index', $customer->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="ti ti-building-factory me-2 text-danger"></i>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                            <span class="badge bg-danger rounded-pill">{{ __('Incidents') }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para seleccionar Centro de Producción (Quality Issues) -->
    <div class="modal fade" id="selectCustomerQualityIssuesModal" tabindex="-1" aria-labelledby="selectCustomerQualityIssuesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="selectCustomerQualityIssuesModalLabel">
                        <i class="ti ti-flask me-2"></i>{{ __('Select Production Center') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($customersForIncidents as $customer)
                        <a href="{{ route('customers.quality-incidents.index', $customer->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="ti ti-building-factory me-2 text-warning"></i>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill">{{ __('Quality') }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endcan

    <!-- Modal para seleccionar Centro de Producción (Maintenance) -->
    @can('maintenance-show')
    @if(isset($customersForMaintenance) && $customersForMaintenance && $customersForMaintenance->count() > 0)
    <div class="modal fade" id="selectCustomerMaintenanceModal" tabindex="-1" aria-labelledby="selectCustomerMaintenanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="selectCustomerMaintenanceModalLabel">
                        <i class="ti ti-tool me-2"></i>{{ __('Select Production Center') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($customersForMaintenance as $customer)
                        <a href="{{ route('customers.maintenances.index', $customer->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="ti ti-building-factory me-2 text-primary"></i>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ __('Maintenance') }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endcan
@endsection
@push('style')
    {{--  @include('layouts.includes.datatable_css')  --}}
    {{--  <link href="{{ asset('css/custom.css') }}" rel="stylesheet">  --}}
    <style>
        /* KPIs adaptivos: 5 por línea en pantallas muy grandes */
        @media (min-width: 1400px) {
            .kpi-card-col {
                flex: 0 0 20%;
                max-width: 20%;
            }
        }
        @media (min-width: 1200px) and (max-width: 1399.98px) {
            .kpi-card-col {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }
        @media (min-width: 992px) and (max-width: 1199.98px) {
            .kpi-card-col {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }
        @media (min-width: 768px) and (max-width: 991.98px) {
            .kpi-card-col {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        @media (max-width: 767.98px) {
            .kpi-card-col {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /* Efecto de parpadeo cuando el KPI cambia */
        @keyframes kpiBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .kpi-blink {
            animation: kpiBlink 0.3s ease-in-out 3;
        }

        /* Indicadores de tendencia */
        .kpi-trend {
            font-size: 0.75rem;
            margin-left: 5px;
        }
        .kpi-trend.trend-up {
            color: #28a745;
        }
        .kpi-trend.trend-down {
            color: #dc3545;
        }
        .kpi-trend.trend-same {
            color: rgba(255,255,255,0.6);
        }

        /* Efecto de alerta pulsante para incidencias activas */
        @keyframes alertPulseRed {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(220, 53, 69, 0);
            }
        }
        @keyframes alertPulseOrange {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 165, 0, 0.7);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(255, 165, 0, 0);
            }
        }
        @keyframes alertPulseYellow {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(255, 193, 7, 0);
            }
        }
        @keyframes alertPulseCyan {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(23, 162, 184, 0.7);
            }
            50% {
                box-shadow: 0 0 0 15px rgba(23, 162, 184, 0);
            }
        }
        .kpi-alert-active {
            animation: alertPulseRed 2s infinite;
        }
        /* Alerta naranja para mantenimiento */
        [data-kpi="maintenance"].kpi-alert-active {
            animation: alertPulseOrange 2s infinite;
        }
        /* Alerta cyan para QC confirmaciones (0 es malo) */
        [data-kpi="qcConfirmations"].kpi-alert-active {
            animation: alertPulseCyan 2s infinite;
        }
        /* Alerta amarilla para incidencias de calidad */
        [data-kpi="qualityIssues"].kpi-alert-active {
            animation: alertPulseYellow 2s infinite;
        }

        /* Mini sparklines */
        .kpi-sparkline {
            display: flex;
            align-items: flex-end;
            gap: 2px;
            height: 25px;
            min-width: 60px;
        }
        .kpi-sparkline .spark-bar {
            flex: 1;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 2px;
            min-width: 5px;
            max-width: 10px;
            transition: height 0.3s ease;
        }
        .kpi-sparkline .spark-bar:last-child {
            background: rgba(255, 255, 255, 0.9);
        }
    </style>
@endpush


@section('javascript')
@can('productionline-orders')
    <script src="{{ asset('js/Chart.min.js') }}"></script>
    <script src="{{ asset('js/coreui-chartjs.bundle.js') }}"></script>
    <script src="{{ asset('js/main.js') }}?v={{ time() }}" defer></script>
    <script>
        $(document).on("click", "#option2", function() {
            $('#option1').removeClass('active');
            $(this).addClass('active');
            getChartData('month');
        });

        $(document).on("click", "#option1", function() {
            $('#option2').removeClass('active');
            $(this).addClass('active');
            getChartData('week');
        });
        $(document).ready(function() {
            getChartData('week');
        })

        function getChartData(type) {
            $.ajax({
                url: "{{ route('get.production.chart.data') }}",
                type: 'POST',
                data: {
                    type: type,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },

                success: function(result) {
                    mainChart.data.labels = result.lable;
                    mainChart.data.datasets[0].data = result.value;
                    mainChart.update()
                },
                error: function(data) {
                    console.log(data.responseJSON);
                }
            });
        }
    </script>
@endcan

{{-- Auto-refresh de KPIs --}}
<script>
    // Almacenar valores anteriores para detectar cambios
    var previousKpiValues = {};

    // Intervalo de refresco en milisegundos (2 minutos = 120000)
    var KPI_REFRESH_INTERVAL = 120000;

    $(document).ready(function() {
        // Inicializar sparklines con datos actuales
        initializeSparklines();

        // Cargar datos iniciales de KPIs
        loadKpiData();

        // Configurar auto-refresh
        setInterval(loadKpiData, KPI_REFRESH_INTERVAL);
    });

    function loadKpiData() {
        $.ajax({
            url: "{{ route('get.kpi.data') }}",
            type: 'GET',
            success: function(data) {
                updateKpis(data);
            },
            error: function(err) {
                console.log('Error loading KPI data:', err);
            }
        });
    }

    function updateKpis(data) {
        // Maintenance (con alerta si hay mantenimientos sin cerrar)
        if (data.maintenance) {
            updateKpiCardWithAlert('maintenance', data.maintenance.value, data.maintenance);
        }

        // Workers
        if (data.workers) {
            updateKpiCard('workers', data.workers.value, data.workers);
        }

        // Order Organizer (tiene grupos y máquinas)
        if (data.orderOrganizer) {
            var $card = $('[data-kpi="orderOrganizer"]');
            var oldGroups = previousKpiValues['orderOrganizer_groups'];
            var newGroups = data.orderOrganizer.groups;

            if (oldGroups !== undefined && oldGroups !== newGroups) {
                triggerBlink($card);
            }

            $card.find('.kpi-value[data-field="groups"]').text(newGroups);
            $card.find('.kpi-machines').text(data.orderOrganizer.machines);
            updateTrend($card, data.orderOrganizer.trend);
            updateSparkline($card, data.orderOrganizer.sparkline);

            previousKpiValues['orderOrganizer_groups'] = newGroups;
        }

        // Pending Orders (tiene total, inProgress, notStarted)
        if (data.pendingOrders) {
            var $card = $('[data-kpi="pendingOrders"]');
            var oldTotal = previousKpiValues['pendingOrders_total'];
            var newTotal = data.pendingOrders.total;

            if (oldTotal !== undefined && oldTotal !== newTotal) {
                triggerBlink($card);
            }

            $card.find('.kpi-value[data-field="total"]').text(newTotal);
            $card.find('.kpi-in-progress').text(data.pendingOrders.inProgress);
            $card.find('.kpi-not-started').text(data.pendingOrders.notStarted);
            updateTrend($card, data.pendingOrders.trend);
            updateSparkline($card, data.pendingOrders.sparkline);

            previousKpiValues['pendingOrders_total'] = newTotal;
        }

        // QC Confirmations (con alerta si hay 0 confirmaciones hoy - mal)
        if (data.qcConfirmations) {
            updateKpiCardWithAlert('qcConfirmations', data.qcConfirmations.value, data.qcConfirmations);
        }

        // Production Incidents (con alerta visual)
        if (data.productionIncidents) {
            updateKpiCardWithAlert('productionIncidents', data.productionIncidents.value, data.productionIncidents);
        }

        // Quality Issues (con alerta si hay incidencias hoy)
        if (data.qualityIssues) {
            updateKpiCardWithAlert('qualityIssues', data.qualityIssues.value, data.qualityIssues);
        }

        // Production Lines stats
        if (data.productionLines) {
            // Este KPI no tiene data-kpi, pero podríamos añadirlo después si se requiere
        }
    }

    function updateKpiCard(kpiName, newValue, kpiData) {
        var $card = $('[data-kpi="' + kpiName + '"]');
        if ($card.length === 0) return;

        var oldValue = previousKpiValues[kpiName];

        // Detectar cambio y aplicar efecto parpadeo
        if (oldValue !== undefined && oldValue !== newValue) {
            triggerBlink($card);
        }

        // Actualizar valor
        $card.find('.kpi-value').text(newValue);

        // Actualizar tendencia si existe
        if (kpiData && kpiData.trend) {
            updateTrend($card, kpiData.trend);
        }

        // Actualizar sparkline si existe
        if (kpiData && kpiData.sparkline) {
            updateSparkline($card, kpiData.sparkline);
        }

        // Guardar valor para próxima comparación
        previousKpiValues[kpiName] = newValue;
    }

    // Versión con soporte de alerta pulsante
    function updateKpiCardWithAlert(kpiName, newValue, kpiData) {
        var $card = $('[data-kpi="' + kpiName + '"]');
        if ($card.length === 0) return;

        var oldValue = previousKpiValues[kpiName];

        // Detectar cambio y aplicar efecto parpadeo
        if (oldValue !== undefined && oldValue !== newValue) {
            triggerBlink($card);
        }

        // Actualizar valor
        $card.find('.kpi-value').text(newValue);

        // Actualizar tendencia si existe
        if (kpiData && kpiData.trend) {
            updateTrend($card, kpiData.trend);
        }

        // Actualizar sparkline si existe
        if (kpiData && kpiData.sparkline) {
            updateSparkline($card, kpiData.sparkline);
        }

        // Aplicar o quitar efecto de alerta pulsante
        if (kpiData && kpiData.isAlert) {
            $card.addClass('kpi-alert-active');
        } else {
            $card.removeClass('kpi-alert-active');
        }

        // Guardar valor para próxima comparación
        previousKpiValues[kpiName] = newValue;
    }

    function triggerBlink($card) {
        $card.removeClass('kpi-blink');
        // Forzar reflow para reiniciar animación
        void $card[0].offsetWidth;
        $card.addClass('kpi-blink');

        // Remover clase después de la animación (0.3s * 3 = 0.9s)
        setTimeout(function() {
            $card.removeClass('kpi-blink');
        }, 1000);
    }

    function updateTrend($card, trend) {
        var $trend = $card.find('.kpi-trend');
        $trend.removeClass('trend-up trend-down trend-same');

        if (trend === 'up') {
            $trend.addClass('trend-up').html('<i class="ti ti-arrow-up"></i>');
        } else if (trend === 'down') {
            $trend.addClass('trend-down').html('<i class="ti ti-arrow-down"></i>');
        } else {
            $trend.addClass('trend-same').html('<i class="ti ti-minus"></i>');
        }
    }

    function updateSparkline($card, sparklineData) {
        if (!sparklineData || sparklineData.length === 0) return;

        var $sparkline = $card.find('.kpi-sparkline');
        if ($sparkline.length === 0) return;

        var maxVal = Math.max.apply(null, sparklineData);
        if (maxVal === 0) maxVal = 1; // Evitar división por cero

        var html = '';
        sparklineData.forEach(function(val) {
            var height = Math.max(3, (val / maxVal) * 100); // Mínimo 3% de altura
            html += '<div class="spark-bar" style="height: ' + height + '%;" title="' + val + '"></div>';
        });

        $sparkline.html(html);
    }

    function initializeSparklines() {
        // Inicializar sparklines vacías con barras placeholder
        $('.kpi-sparkline').each(function() {
            var html = '';
            for (var i = 0; i < 7; i++) {
                html += '<div class="spark-bar" style="height: 20%;"></div>';
            }
            $(this).html(html);
        });
    }
</script>
@endsection
