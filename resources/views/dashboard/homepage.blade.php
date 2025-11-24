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
        @can('manage-user')
        <div class="kpi-card-col mb-4 animate-card">
            <a href="users" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-primary">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Total Users') }}</h6>
                                <h3>{{ $user }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('manage-role')
        <div class="kpi-card-col mb-4 animate-card">
            <a href="roles" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-info">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Total Role') }}</h6>
                                <h3>{{ $role }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-shield-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('manage-module')
        <div class="kpi-card-col mb-4 animate-card">
            <a href="modules" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-success">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Total Module') }}</h6>
                                <h3>{{ $modual }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('manage-langauge')
        <div class="kpi-card-col mb-4 animate-card">
            <a href="language" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-danger">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Total Languages') }}</h6>
                                <h3>{{ $languages }}</h3>
                            </div>
                            <div class="stat-icon-wrapper bg-white bg-opacity-25">
                                <i class="ti ti-world"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        <!-- Widget de trabajadores si tiene permiso -->
        @can('workers-show')
        <div class="kpi-card-col mb-4 animate-card">
            <a href="{{ route('workers-admin.index') }}" class="text-decoration-none">
                <div class="card modern-stat-card stat-card-warning">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Total Workers') }}</h6>
                                <h3>{{ $operatorsCount }}</h3>
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

        <!-- Widget de Order Organizer (Grupos y Máquinas) - permiso productionline-kanban -->
        @can('productionline-kanban')
        @if(isset($orderOrganizerStats) && $orderOrganizerStats)
        <div class="kpi-card-col mb-4 animate-card">
            <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#selectCustomerModal">
                <div class="card modern-stat-card stat-card-indigo">
                    <div class="stat-card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="stat-content">
                                <h6>{{ __('Order Organizer') }}</h6>
                                <h3>{{ $orderOrganizerStats['groups'] }} <small style="font-size: 0.5em;">{{ __('groups') }}</small></h3>
                                <small class="text-white-50">{{ $orderOrganizerStats['machines'] }} {{ __('machines') }}</small>
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
        </div>

        <!-- Estado de líneas en pausa o paradas -->
        <div class="kpi-card-col mb-4 animate-card">
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
        </div>

        <!-- Tabla resumen de líneas de producción -->
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

        <!-- project-ticket end -->

        {{-- <div class="row"> --}}
        <div class="col-lg-12 mb-4 animate-card">
            @role('admin')
            <div class="card chart-card">
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-sm-5">
                            <h4 class="card-title mb-0">
                                <i class="ti ti-chart-line me-2"></i>
                                {{ __('Users Growth') }}
                            </h4>
                        </div>

                        <div class="col-sm-7 d-none d-md-block">
                            <div class="btn-group float-end" role="group">
                                <button type="button" class="btn btn-chart-toggle active" id="option1">
                                    <i class="ti ti-calendar-month me-1"></i>
                                    {{ __('Month') }}
                                </button>
                                <button type="button" class="btn btn-chart-toggle" id="option2">
                                    <i class="ti ti-calendar-stats me-1"></i>
                                    {{ __('Year') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="c-chart-wrapper chartbtn">
                        <canvas class="chart" id="main-chart" height="300"></canvas>
                    </div>
                </div>
            </div>
            @endrole
        </div>

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
    </style>
@endpush


@section('javascript')
@role('admin')
    <script src="{{ asset('js/Chart.min.js') }}"></script>
    <script src="{{ asset('js/coreui-chartjs.bundle.js') }}"></script>
    <script src="{{ asset('js/main.js') }}" defer></script>
    <script>
        $(document).on("click", "#option2", function() {
            getChartData('year');
        });

        $(document).on("click", "#option1", function() {
            getChartData('month');
        });
        $(document).ready(function() {
            getChartData('month');
        })

        function getChartData(type) {

            $.ajax({
                url: "{{ route('get.chart.data') }}",
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
@endrole
@endsection
