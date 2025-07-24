@extends('layouts.admin')
@section('title')
    {{ __(' Dashboard') }}
@endsection
@section('content')
    <!-- [ breadcrumb ] start -->
    <!-- [ breadcrumb ] end -->
    
    <!-- [ Main Content ] start -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">{{ __('Bienvenido a tu panel de control') }}</h4>
                <p class="text-muted mb-0">
                    {{ __('Gestiona y supervisa tus actividades desde aquí') }}
                </p>
            </div>
        </div>
    </div>
    
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
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center p-5">
                        <div class="avatar-lg mx-auto mb-4">
                            <i class="ti ti-user-check display-4 text-primary"></i>
                        </div>
                        <h4 class="mb-3">{{ __('¡Bienvenido a tu panel de control!') }}</h4>
                        <p class="text-muted mb-4">
                            {{ __('Actualmente no tienes asignados permisos específicos para ver los widgets del dashboard.') }}
                        </p>
                        <p class="text-muted">
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
        <div class="col-xl-3 col-md-12">
            <a href="users">
                <div class="card comp-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-b-0 text-muted">{{ __('Total Users') }}</h6>
                                <h3 class="m-b-5">{{ $user }}</h3>
                            </div>
                            <div class="col-auto">
                                <i class="ti ti-users bg-primary text-white d-block"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('manage-role')
        <div class="col-xl-3 col-md-12">
            <a href="roles">
                <div class="card comp-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-b-0 text-muted">{{ __('Total Role') }}</h6>
                                <h3 class="m-b-5">{{ $role }}</h3>
                            </div>
                            <div class="col-auto">
                                <i class="ti ti-key bg-info text-white d-block"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('manage-module')
        <div class="col-xl-3 col-md-12">
            <a href="modules">
                <div class="card comp-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-b-0 text-muted">{{ __('Total Module') }}</h6>
                                <h3 class="m-b-5">{{ $modual }}</h3>
                            </div>
                            <div class="col-auto">
                                <i class="ti ti-users bg-success text-white d-block"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
        @can('manage-langauge')
        <div class="col-xl-3 col-md-12">
            <a href="language">
                <div class="card comp-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-b-0 text-muted">{{ __('Total Languages') }}</h6>
                                <h3 class="m-b-5">{{ $languages }}</h3>
                            </div>
                            <div class="col-auto">
                                <i class="ti ti-world bg-danger text-white d-block"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        <!-- Widget de trabajadores si tiene permiso -->
        @can('workers-show')
        <div class="col-xl-3 col-md-12">
            <a href="{{ route('workers-admin.index') }}">
                <div class="card comp-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-b-0 text-muted">{{ __('Total Workers') }}</h6>
                                <h3 class="m-b-5">{{ $operatorsCount }}</h3>
                            </div>
                            <div class="col-auto">
                                <i class="fa-regular fa-user bg-warning text-white d-block"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        <!-- Widgets de turnos si tiene permiso -->
        @can('shift-show')
        <!-- Resumen de líneas de producción -->
        <div class="col-xl-3 col-md-12">
            <a href="{{ route('shift.index') }}">
                <div class="card comp-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h6 class="m-b-0 text-muted">{{ __('Production Lines') }}</h6>
                                <h3 class="m-b-5">{{ $productionLineStats['total'] }}</h3>
                            </div>
                            <div class="col-auto">
                                <i class="fa-regular fa-clock bg-info text-white d-block"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Estado de líneas activas -->
        <div class="col-xl-3 col-md-12">
            <div class="card comp-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-b-0 text-muted">{{ __('Active Lines') }}</h6>
                            <h3 class="m-b-5">{{ $productionLineStats['active'] }}</h3>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-play bg-success text-white d-block"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado de líneas en pausa o paradas -->
        <div class="col-xl-3 col-md-12">
            <div class="card comp-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-b-0 text-muted">{{ __('Paused/Stopped') }}</h6>
                            <h3 class="m-b-5">{{ $productionLineStats['paused'] + $productionLineStats['stopped'] }}</h3>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-pause bg-warning text-white d-block"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado de líneas con incidencias -->
        <div class="col-xl-3 col-md-12">
            <div class="card comp-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <h6 class="m-b-0 text-muted">{{ __('Incidents') }}</h6>
                            <h3 class="m-b-5">{{ $productionLineStats['incident'] }}</h3>
                        </div>
                        <div class="col-auto">
                            <i class="fa-solid fa-triangle-exclamation bg-danger text-white d-block"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla resumen de líneas de producción -->
        <div class="col-xl-12 col-md-12 mt-3">
            <div class="card">
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
                                        <td>{{ $line->name }}</td>
                                        <td>
                                            @if($line->lastShiftHistory)
                                                @php
                                                    $statusClass = '';
                                                    $statusText = '';
                                                    
                                                    // Verificar si es un turno activo o reanudado
                                                    if ($line->lastShiftHistory->action == 'start' || 
                                                        ($line->lastShiftHistory->type === 'stop' && $line->lastShiftHistory->action === 'end')) {
                                                        // Unificamos la visualización para turnos activos y reanudados
                                                        $statusClass = 'badge bg-success';
                                                        $statusText = __('Active');
                                                    }
                                                    // Resto de casos normales
                                                    else {
                                                        switch($line->lastShiftHistory->action) {
                                                            case 'pause':
                                                                $statusClass = 'badge bg-warning';
                                                                $statusText = __('Paused');
                                                                break;
                                                            case 'stop':
                                                                $statusClass = 'badge bg-secondary';
                                                                $statusText = __('Stopped');
                                                                break;
                                                            case 'incident':
                                                                $statusClass = 'badge bg-danger';
                                                                $statusText = __('Incident');
                                                                break;
                                                            default:
                                                                $statusClass = 'badge bg-light text-dark';
                                                                $statusText = __('Unknown');
                                                        }
                                                    }
                                                @endphp
                                                <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                            @else
                                                <span class="badge bg-light text-dark">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($line->lastShiftHistory)
                                                {{ \Carbon\Carbon::parse($line->lastShiftHistory->created_at)->format('d/m/Y H:i') }}
                                            @else
                                                {{ __('Never') }}
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">{{ __('No production lines found') }}</td>
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
        <div class="col-lg-12 ">
            @role('admin')
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <h4 class="card-title mb-0">{{ 'Users' }}</h4>
                        </div>

                        <div class="col-sm-7 d-none d-md-block">

                            <div class="btn-group btn-group-toggle float-end mr-3" role="group" data-toggle="buttons">
                                <label class="btn btn-light-primary active" for="option1" id="option1" >
                                    <input id="option1" type="radio" class="btn-ckeck" name="options" autocomplete="off" checked="">
                                    {{ __('Month') }}
                                </label>
                                <label class="btn btn-light-primary" for="option2" id="option2">
                                    <input id="option2" type="radio" class="btn-ckeck" name="options" autocomplete="off"> {{ __('Year') }}
                                </label>
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
@endsection
@push('style')
    {{--  @include('layouts.includes.datatable_css')  --}}
    {{--  <link href="{{ asset('css/custom.css') }}" rel="stylesheet">  --}}
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
