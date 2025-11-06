@extends('layouts.admin')

@section('title', __('Daily Routes') . ' - ' . $customer->name)
@section('page-title', __('Daily Routes'))

@push('styles')
<style>
  .route-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    margin-bottom: 24px;
  }

  .route-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
  }

  .route-card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px 24px;
    border-radius: 14px 14px 0 0;
    color: white;
  }

  .route-card-body {
    padding: 24px;
  }

  .clients-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
  }

  .vehicles-section {
    min-height: 100px;
  }

  .day-selector {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 24px;
  }

  .day-selector .btn-lg {
    min-width: 200px;
    font-weight: 600;
    padding: 12px 24px;
    transition: all 0.3s ease;
  }

  .day-selector .btn-lg:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .day-selector .btn-lg i {
    font-size: 1.2em;
    margin-right: 4px;
  }

  .kpi-card {
    cursor: pointer;
    overflow: hidden;
    position: relative;
  }

  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
  }

  .kpi-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
  }

  .kpi-card:hover::before {
    left: 100%;
  }

  .kpi-icon-wrapper {
    transition: all 0.3s ease;
  }

  .kpi-card:hover .kpi-icon-wrapper {
    transform: scale(1.1) rotate(5deg);
  }

  .empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
  }

  .empty-state i {
    font-size: 64px;
    opacity: 0.3;
    margin-bottom: 16px;
  }
</style>
@endpush

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Daily Routes') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Daily Routes') }}</h5>
    <div class="d-flex gap-2">
      <a href="{{ route('customers.routes.index', $customer->id) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-calendar-week"></i> {{ __('Weekly View') }}
      </a>
    </div>
  </div>
  <div class="card-body">
    @php
      $today = now()->format('Y-m-d');
      $tomorrow = now()->addDay()->format('Y-m-d');
      $dayAfterTomorrow = now()->addDays(2)->format('Y-m-d');

      $dayNames = [__('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday'), __('Sunday')];
      $dayName = $dayNames[$dayIndex];

      // Determinar qué día estamos viendo
      $isToday = $selectedDate->format('Y-m-d') === $today;
      $isTomorrow = $selectedDate->format('Y-m-d') === $tomorrow;
      $isDayAfterTomorrow = $selectedDate->format('Y-m-d') === $dayAfterTomorrow;

      // Calcular estadísticas
      $totalVehicles = collect($routeAssignments ?? [])->count();
      $totalClients = collect($clientVehicleAssignments ?? [])->count();
      $totalRoutes = collect($routeNames ?? [])->count();
      $activeRoutes = collect($routeAssignments ?? [])->pluck('route_name_id')->unique()->count();

      $totalOrdersInTrucks = collect($clientVehicleAssignments ?? [])->sum(function($assignment) {
          return $assignment->orderAssignments->where('active', true)->count();
      });
    @endphp

    <!-- Selector de Día -->
    <div class="day-selector">
      <div class="d-flex justify-content-center align-items-center mb-3 gap-3">
        <a href="{{ request()->fullUrlWithQuery(['date' => $today]) }}"
           class="btn {{ $isToday ? 'btn-primary' : 'btn-outline-primary' }} btn-lg">
          <i class="ti ti-calendar-today"></i> {{ __('Today') }}
        </a>
        <a href="{{ request()->fullUrlWithQuery(['date' => $tomorrow]) }}"
           class="btn {{ $isTomorrow ? 'btn-success' : 'btn-outline-success' }} btn-lg">
          <i class="ti ti-calendar-plus"></i> {{ __('Tomorrow') }}
        </a>
        <a href="{{ request()->fullUrlWithQuery(['date' => $dayAfterTomorrow]) }}"
           class="btn {{ $isDayAfterTomorrow ? 'btn-info' : 'btn-outline-info' }} btn-lg">
          <i class="ti ti-calendar-event"></i> {{ __('Day After Tomorrow') }}
        </a>
      </div>

      <div class="text-center mb-3">
        <h4 class="mb-0 fw-bold">{{ $dayName }}</h4>
        <div class="text-muted">{{ $selectedDate->format('d M, Y') }}</div>
        @if($isToday)
          <span class="badge bg-primary mt-1">{{ __('Today') }}</span>
        @elseif($isTomorrow)
          <span class="badge bg-success mt-1">{{ __('Tomorrow') }}</span>
        @elseif($isDayAfterTomorrow)
          <span class="badge bg-info mt-1">{{ __('Day After Tomorrow') }}</span>
        @endif
      </div>

      <!-- Estadísticas -->
      <div class="row g-3 mt-2">
        <div class="col">
          <div class="card h-100 border-0 shadow-sm kpi-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: all 0.3s ease;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-white">
                  <div class="small mb-1" style="opacity: 0.9;">{{ __('Vehicles Assigned') }}</div>
                  <div class="fs-3 fw-bold">{{ $totalVehicles }}</div>
                </div>
                <div class="kpi-icon-wrapper" style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                  <i class="ti ti-truck fs-1 text-white"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card h-100 border-0 shadow-sm kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); transition: all 0.3s ease;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-white">
                  <div class="small mb-1" style="opacity: 0.9;">{{ __('Clients Planned') }}</div>
                  <div class="fs-3 fw-bold">{{ $totalClients }}</div>
                </div>
                <div class="kpi-icon-wrapper" style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                  <i class="ti ti-users fs-1 text-white"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card h-100 border-0 shadow-sm kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); transition: all 0.3s ease;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-white">
                  <div class="small mb-1" style="opacity: 0.9;">{{ __('Active Routes') }}</div>
                  <div class="fs-3 fw-bold">{{ $activeRoutes }}/{{ $totalRoutes }}</div>
                </div>
                <div class="kpi-icon-wrapper" style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                  <i class="ti ti-route fs-1 text-white"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="card h-100 border-0 shadow-sm kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); transition: all 0.3s ease;">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-white">
                  <div class="small mb-1" style="opacity: 0.9;">{{ __('Orders in Trucks') }}</div>
                  <div class="fs-3 fw-bold">{{ $totalOrdersInTrucks }}</div>
                </div>
                <div class="kpi-icon-wrapper" style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                  <i class="ti ti-package fs-1 text-white"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filtros -->
    <div class="mb-4">
      <div class="d-flex gap-2 flex-wrap">
        <input type="search" id="clientSearch" placeholder="{{ __('Search client...') }}" class="form-control" style="max-width: 300px;">
        <input type="search" id="orderSearch" placeholder="{{ __('🔍 Search order number...') }}" class="form-control" style="max-width: 300px; border: 2px solid #0d6efd;">
        <select id="vehicleTypeFilter" class="form-select" style="max-width: 200px;">
          <option value="">{{ __('All vehicles') }}</option>
          <option value="furgoneta">🚐 {{ __('Van') }}</option>
          <option value="camion">🚛 {{ __('Truck') }}</option>
          <option value="moto">🏍️ {{ __('Motorcycle') }}</option>
        </select>
        <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
          <i class="fas fa-times"></i> {{ __('Clear filters') }}
        </button>
      </div>
    </div>

    <!-- Lista de Rutas -->
    <div id="routes-container">
      @php
        $clientsByRoute = collect($customerClients ?? [])->groupBy('route_name_id');
        $dayDate = $selectedDate->format('Y-m-d');

        // Calcular semana para compatibilidad con APIs existentes
        $monday = (clone $selectedDate)->startOfWeek(\Carbon\Carbon::MONDAY);
        $weekParam = $monday->format('Y-m-d');
      @endphp

      @forelse($routeNames as $route)
        <div class="route-card" data-route-id="{{ $route->id }}">
          <div class="route-card-header">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0 fw-bold">{{ $route->name }}</h5>
                <small class="opacity-90">{{ __('Route ID') }}: {{ $route->id }}</small>
              </div>
              <div class="d-flex gap-2 align-items-center">
                <button class="btn btn-outline-secondary btn-sm route-copy-prev-week-btn"
                        data-route-id="{{ $route->id }}"
                        data-day-index="{{ $dayIndex }}"
                        title="{{ __('Copy entire route from last week') }}">
                  <i class="fas fa-copy"></i> {{ __('Copy Last Week') }}
                </button>
                <button class="btn btn-outline-info btn-sm route-print-btn"
                        data-route-id="{{ $route->id }}"
                        data-route-name="{{ $route->name }}"
                        data-day-index="{{ $dayIndex }}"
                        data-day-date="{{ $dayDate }}"
                        title="{{ __('Print entire route') }}">
                  <i class="fas fa-print"></i> {{ __('Print') }}
                </button>
                <button class="btn btn-outline-success btn-sm route-excel-btn"
                        data-route-id="{{ $route->id }}"
                        data-route-name="{{ $route->name }}"
                        data-day-index="{{ $dayIndex }}"
                        data-day-date="{{ $dayDate }}"
                        title="{{ __('Export route to Excel') }}">
                  <i class="fas fa-file-excel"></i> {{ __('Excel') }}
                </button>
                <div class="vr" style="height: 30px;"></div>
                <button class="btn btn-success btn-add-vehicle-daily"
                        data-bs-toggle="modal"
                        data-bs-target="#vehicleModal"
                        data-route-id="{{ $route->id }}"
                        data-route-name="{{ $route->name }}"
                        data-day-index="{{ $dayIndex }}"
                        data-day-name="{{ $dayName }}"
                        style="font-weight: 600; padding: 8px 20px;">
                  <i class="ti ti-truck me-1" style="font-size: 1.2em;"></i> {{ __('Add Vehicle') }}
                </button>
              </div>
            </div>
          </div>

          <div class="route-card-body">
            <!-- Clientes disponibles -->
            @php
              $clients = ($clientsByRoute->get($route->id) ?? collect());
              $assignedClientIds = collect($clientVehicleAssignments ?? [])
                ->filter(function($a) use ($route, $dayDate) {
                  try {
                    $date = $a->assignment_date instanceof \Carbon\Carbon ? $a->assignment_date->format('Y-m-d') : (string)$a->assignment_date;
                  } catch (\Throwable $e) {
                    $date = (string)($a->assignment_date ?? '');
                  }
                  return ((int)$a->route_name_id === (int)$route->id) && ($date === $dayDate);
                })
                ->pluck('customer_client_id');
              $availableClients = $clients->whereNotIn('id', $assignedClientIds);
            @endphp

            @if($availableClients->count() > 0)
              <div class="clients-section">
                <h6 class="text-muted mb-3"><i class="ti ti-users"></i> {{ __('Available Clients') }} ({{ $availableClients->count() }})</h6>
                <div class="mb-2">
                  <input type="search" class="form-control form-control-sm clients-search" placeholder="{{ __('Search client...') }}">
                </div>
                <div class="clients-wrapper">
                  <div class="d-flex flex-wrap gap-2 clients-list">
                    @foreach($availableClients as $c)
                      @php
                        $pendingOrders = ($c->pendingDeliveries ?? collect());
                        $pendingOrdersCount = $pendingOrders->count();
                        $hasUnfinishedOrders = $pendingOrders->filter(function($order) {
                            return is_null($order->finished_at) && !is_null($order->delivery_date);
                        })->count() > 0;

                        $badgeClass = 'bg-light text-dark border';
                        $badgeCountClass = 'bg-primary';
                        if ($hasUnfinishedOrders) {
                            $badgeClass = 'bg-danger text-white border-danger';
                            $badgeCountClass = 'bg-white text-danger';
                        }
                      @endphp
                      <span class="badge {{ $badgeClass }} draggable-client"
                            draggable="true"
                            data-client-id="{{ $c->id }}"
                            data-client-name="{{ $c->name }}"
                            data-route-id="{{ $route->id }}"
                            data-day-index="{{ $dayIndex }}"
                            style="cursor: grab; padding: 8px 12px; font-size: 14px;"
                            title="{{ $hasUnfinishedOrders ? __('Has orders pending completion') : '' }}">
                        {{ $c->name }}
                        @if($pendingOrdersCount > 0)
                          <span class="badge {{ $badgeCountClass }} ms-1" style="font-size: 0.7em;">{{ $pendingOrdersCount }}</span>
                        @endif
                      </span>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif

            <!-- Vehículos asignados -->
            @php
              $assignedVehicles = collect($routeAssignments ?? [])->filter(function($assignment) use ($route, $dayDate) {
                  return $assignment->route_name_id == $route->id &&
                         $assignment->assignment_date->format('Y-m-d') == $dayDate;
              });
            @endphp

            <div class="vehicles-section">
              <h6 class="text-muted mb-3"><i class="ti ti-truck"></i> {{ __('Assigned Vehicles') }} ({{ $assignedVehicles->count() }})</h6>
              @if($assignedVehicles->count() > 0)
                <div class="assigned-vehicles">
                  @foreach($assignedVehicles as $assignment)
                    <x-routes.vehicle-card
                      :assignment="$assignment"
                      :dayDate="$dayDate"
                      :clientVehicleAssignments="$clientVehicleAssignments"
                      :dayNames="$dayNames"
                    />
                  @endforeach
                </div>
              @else
                <div class="empty-state">
                  <i class="ti ti-truck-off"></i>
                  <p class="text-muted mb-3">{{ __('No vehicles assigned to this route yet') }}</p>
                  <button class="btn btn-success btn-lg btn-add-vehicle-daily shadow-sm"
                          data-bs-toggle="modal"
                          data-bs-target="#vehicleModal"
                          data-route-id="{{ $route->id }}"
                          data-route-name="{{ $route->name }}"
                          data-day-index="{{ $dayIndex }}"
                          data-day-name="{{ $dayName }}"
                          style="font-weight: 600; padding: 12px 30px;">
                    <i class="ti ti-truck me-2" style="font-size: 1.3em;"></i> {{ __('Add First Vehicle') }}
                  </button>
                </div>
              @endif
            </div>
          </div>
        </div>
      @empty
        <div class="empty-state">
          <i class="ti ti-route-off"></i>
          <h5>{{ __('No active routes for this day') }}</h5>
          <p class="text-muted">{{ __('There are no routes configured for') }} {{ $dayName }}</p>
          <a href="{{ route('customers.route-names.index', $customer->id) }}" class="btn btn-primary">
            <i class="ti ti-plus"></i> {{ __('Configure routes') }}
          </a>
        </div>
      @endforelse
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
  <div id="routesToast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="routesToastBody">OK</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Confirm') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="confirmActionMessage">{{ __('Are you sure?') }}</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-danger" id="confirmActionYes">{{ __('Yes, continue') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para seleccionar vehículos -->
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Assign Vehicle') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div class="text-muted small" id="modalRouteInfo"></div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">{{ __('Select Vehicle') }}</label>
          <select id="vehicleSelect" class="form-select">
            <option value="">-- {{ __('Select a vehicle') }} --</option>
            @foreach(($fleetVehicles ?? []) as $v)
              <option value="{{ $v->id }}" data-plate="{{ $v->plate }}" data-type="{{ $v->vehicle_type }}">
                {{ $v->plate }}
                @if($v->vehicle_type) ({{ $v->vehicle_type }}) @endif
              </option>
            @endforeach
          </select>
          <div class="form-text">{{ __('Choose a vehicle from your fleet to assign to this route and day.') }}</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">{{ __('Assign Driver') }} <span class="text-muted">({{ __('Optional') }})</span></label>
          <select id="driverSelect" class="form-select">
            <option value="">-- {{ __('No driver assigned') }} --</option>
            @foreach(($availableDrivers ?? []) as $driver)
              <option value="{{ $driver->id }}">
                {{ $driver->name }}
                @if($driver->email) ({{ $driver->email }}) @endif
              </option>
            @endforeach
          </select>
          <div class="form-text">{{ __('Optionally assign a driver/transporter to this vehicle for this route.') }}</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-primary" id="assignVehicleBtn">{{ __('Assign') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para asignar/cambiar conductor -->
<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Assign/Change Driver') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <div class="text-muted small" id="modalDriverVehicleInfo"></div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">{{ __('Select Driver') }}</label>
          <select id="driverModalSelect" class="form-select">
            <option value="">-- {{ __('No driver assigned') }} --</option>
            @foreach(($availableDrivers ?? []) as $driver)
              <option value="{{ $driver->id }}">
                {{ $driver->name }}
                @if($driver->email) ({{ $driver->email }}) @endif
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-primary" id="saveDriverBtn">{{ __('Save') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal detalle de cliente -->
<div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" style="max-width: 80%;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <div>
          <h5 class="modal-title mb-0"><i class="ti ti-clipboard-text"></i> {{ __('Client details') }}</h5>
          <small class="d-block opacity-75" id="clientDetailsSubtitle"></small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-warning btn-sm" id="emailClientDetailsBtn" title="{{ __('Send by email') }}">
            <i class="ti ti-mail"></i> {{ __('Email') }}
          </button>
          <button type="button" class="btn btn-success btn-sm" id="downloadClientDetailsPdfBtn" title="{{ __('Download PDF') }}">
            <i class="ti ti-download"></i> {{ __('Download PDF') }}
          </button>
          <button type="button" class="btn btn-light btn-sm" id="printClientDetailsBtn" title="{{ __('Print') }}">
            <i class="ti ti-printer"></i> {{ __('Print') }}
          </button>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>
      <div class="modal-body">
        <div id="clientDetailsLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3 text-muted">{{ __('Loading client orders...') }}</p>
        </div>
        <div id="clientDetailsContent" class="d-none"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('style')
<link rel="stylesheet" href="{{ asset('css/routes.css') }}">
@endpush

@push('scripts')
<script>
// Reutilizar el mismo JavaScript de la vista semanal
// Variables globales necesarias para las APIs
window.customerId = {{ $customer->id }};
window.currentWeek = '{{ $weekParam }}'; // Lunes de la semana actual para compatibilidad
window.currentDayIndex = {{ $dayIndex }};
window.currentDate = '{{ $dayDate }}';

// Función de toast
function showToast(message, type = 'primary') {
  const toastEl = document.getElementById('routesToast');
  const toastBody = document.getElementById('routesToastBody');
  toastEl.classList.remove('bg-primary', 'bg-success', 'bg-danger', 'bg-warning');
  toastEl.classList.add('bg-' + type);
  toastBody.textContent = message;
  const toast = new bootstrap.Toast(toastEl);
  toast.show();
}

// Función para limpiar filtros
function clearFilters() {
  document.getElementById('clientSearch').value = '';
  document.getElementById('orderSearch').value = '';
  document.getElementById('vehicleTypeFilter').value = '';
  filterContent();
}

// Función de filtrado
function filterContent() {
  const clientSearch = document.getElementById('clientSearch').value.toLowerCase();
  const orderSearch = document.getElementById('orderSearch').value.toLowerCase();
  const vehicleType = document.getElementById('vehicleTypeFilter').value.toLowerCase();

  // Filtrar clientes
  document.querySelectorAll('.draggable-client').forEach(el => {
    const clientName = el.dataset.clientName.toLowerCase();
    const matches = clientName.includes(clientSearch);
    el.style.display = matches ? '' : 'none';
  });

  // Filtrar vehículos
  document.querySelectorAll('.vehicle-container').forEach(el => {
    const vType = el.dataset.vehicleType || '';
    const matchesType = !vehicleType || vType.includes(vehicleType);

    // Buscar en pedidos si hay orderSearch
    let matchesOrder = !orderSearch;
    if (orderSearch) {
      const orderChips = el.querySelectorAll('.order-chip');
      orderChips.forEach(chip => {
        const orderId = chip.dataset.orderId?.toLowerCase() || '';
        if (orderId.includes(orderSearch)) {
          matchesOrder = true;
        }
      });
    }

    el.style.display = (matchesType && matchesOrder) ? '' : 'none';
  });
}

// Event listeners para filtros
document.getElementById('clientSearch').addEventListener('input', filterContent);
document.getElementById('orderSearch').addEventListener('input', filterContent);
document.getElementById('vehicleTypeFilter').addEventListener('change', filterContent);

// Filtros locales por ruta
document.querySelectorAll('.clients-search').forEach(input => {
  input.addEventListener('input', function() {
    const searchValue = this.value.toLowerCase();
    const clientsList = this.closest('.clients-section').querySelector('.clients-list');
    clientsList.querySelectorAll('.draggable-client').forEach(el => {
      const clientName = el.dataset.clientName.toLowerCase();
      el.style.display = clientName.includes(searchValue) ? '' : 'none';
    });
  });
});

// Drag & Drop para clientes - MEJORADO
document.querySelectorAll('.draggable-client').forEach(client => {
  client.addEventListener('dragstart', function(e) {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', JSON.stringify({
      clientId: this.dataset.clientId,
      clientName: this.dataset.clientName,
      routeId: this.dataset.routeId,
      dayIndex: this.dataset.dayIndex
    }));

    // Agregar clases para animaciones profesionales
    this.classList.add('dragging');

    // Marcar todos los vehículos como valid-drop-target
    document.querySelectorAll('.vehicle-drop-zone').forEach(zone => {
      zone.classList.add('valid-drop-target');
    });
  });

  client.addEventListener('dragend', function() {
    // Limpiar todas las clases
    this.classList.remove('dragging');
    document.querySelectorAll('.vehicle-drop-zone').forEach(zone => {
      zone.classList.remove('valid-drop-target', 'drag-over');
    });
  });
});

// Drop zones en vehículos - MEJORADO
document.querySelectorAll('.vehicle-drop-zone').forEach(zone => {
  zone.addEventListener('dragover', function(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    // Agregar clase drag-over para animación
    this.classList.add('drag-over');
  });

  zone.addEventListener('dragleave', function(e) {
    // Solo remover si realmente salimos del elemento
    if (e.target === this) {
      this.classList.remove('drag-over');
    }
  });

  zone.addEventListener('drop', function(e) {
    e.preventDefault();

    // Limpiar todas las clases visuales
    this.classList.remove('drag-over', 'valid-drop-target');
    document.querySelectorAll('.vehicle-drop-zone').forEach(z => {
      z.classList.remove('valid-drop-target', 'drag-over');
    });

    const data = JSON.parse(e.dataTransfer.getData('text/plain'));
    const vehicleId = this.dataset.vehicleId;
    const routeId = this.dataset.routeId;
    const dayIndex = this.dataset.dayIndex;

    // Llamar API para asignar cliente a vehículo
    fetch(`/customers/${customerId}/routes/assign-client-vehicle`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        customer_client_id: data.clientId,
        fleet_vehicle_id: vehicleId,
        route_name_id: routeId,
        day_index: currentDayIndex,
        week: currentWeek
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(data.message || 'Error', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error assigning client', 'danger');
    });
  });
});

// Modal para agregar vehículo
document.querySelectorAll('.btn-add-vehicle, .btn-add-vehicle-daily').forEach(btn => {
  btn.addEventListener('click', function() {
    const routeId = this.dataset.routeId;
    const routeName = this.dataset.routeName;
    const dayIndex = this.dataset.dayIndex;
    const dayName = this.dataset.dayName;

    document.getElementById('modalRouteInfo').textContent = `${routeName} - ${dayName}`;
    document.getElementById('vehicleSelect').dataset.routeId = routeId;
    document.getElementById('vehicleSelect').dataset.dayIndex = dayIndex;
  });
});

// Asignar vehículo
document.getElementById('assignVehicleBtn').addEventListener('click', function() {
  const vehicleSelect = document.getElementById('vehicleSelect');
  const driverSelect = document.getElementById('driverSelect');
  const vehicleId = vehicleSelect.value;
  const driverId = driverSelect.value;
  const routeId = vehicleSelect.dataset.routeId;
  const dayIndex = vehicleSelect.dataset.dayIndex;

  if (!vehicleId) {
    showToast('Please select a vehicle', 'warning');
    return;
  }

  fetch(`/customers/${customerId}/routes/assign-vehicle`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      route_name_id: routeId,
      fleet_vehicle_id: vehicleId,
      user_id: driverId || null,
      day_index: currentDayIndex,
      week: currentWeek
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast(data.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('vehicleModal')).hide();
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message || 'Error', 'danger');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error assigning vehicle', 'danger');
  });
});

// Remover vehículo
document.querySelectorAll('.remove-vehicle-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    if (!confirm('{{ __("Are you sure you want to remove this vehicle?") }}')) {
      return;
    }

    const assignmentId = this.dataset.assignmentId;

    fetch(`/customers/${customerId}/routes/remove-vehicle`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        assignment_id: assignmentId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(data.message || 'Error', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error removing vehicle', 'danger');
    });
  });
});

// Remover cliente de vehículo
document.querySelectorAll('.client-remove-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    e.preventDefault();

    if (!confirm('{{ __("Remove this client from the vehicle?") }}')) {
      return;
    }

    const assignmentId = this.dataset.clientAssignmentId;

    fetch(`/customers/${customerId}/routes/remove-client-vehicle`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        assignment_id: assignmentId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast(data.message || 'Error', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error removing client', 'danger');
    });
  });
});

// Copiar de semana anterior
document.querySelectorAll('.route-copy-prev-week-btn, .vehicle-copy-prev-week-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const routeId = this.dataset.routeId;
    const vehicleId = this.dataset.vehicleId;
    const dayIndex = this.dataset.dayIndex;

    if (vehicleId) {
      // Copiar vehículo específico
      fetch(`/customers/${customerId}/routes/copy-from-previous-week`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          route_name_id: routeId,
          fleet_vehicle_id: vehicleId,
          day_index: currentDayIndex,
          week: currentWeek
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          showToast(data.message || 'Error', 'danger');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error copying from previous week', 'danger');
      });
    } else {
      // Copiar ruta completa
      fetch(`/customers/${customerId}/routes/copy-entire-route-from-previous-week`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          route_name_id: routeId,
          day_index: currentDayIndex,
          week: currentWeek
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(data.message, 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          showToast(data.message || 'Error', 'danger');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('Error copying route', 'danger');
      });
    }
  });
});

// Asignar/cambiar conductor
document.querySelectorAll('.vehicle-assign-driver-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.stopPropagation();

    const assignmentId = this.dataset.assignmentId;
    const currentDriverId = this.dataset.currentDriverId;
    const vehiclePlate = this.dataset.vehiclePlate;

    document.getElementById('modalDriverVehicleInfo').textContent = `Vehicle: ${vehiclePlate}`;
    document.getElementById('driverModalSelect').value = currentDriverId || '';
    document.getElementById('saveDriverBtn').dataset.assignmentId = assignmentId;

    const modal = new bootstrap.Modal(document.getElementById('driverModal'));
    modal.show();
  });
});

document.getElementById('saveDriverBtn').addEventListener('click', function() {
  const assignmentId = this.dataset.assignmentId;
  const driverId = document.getElementById('driverModalSelect').value;

  fetch(`/customers/${customerId}/routes/assign-vehicle`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      assignment_id: assignmentId,
      user_id: driverId || null
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast(data.message, 'success');
      bootstrap.Modal.getInstance(document.getElementById('driverModal')).hide();
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast(data.message || 'Error', 'danger');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error updating driver', 'danger');
  });
});

// Imprimir ruta
document.querySelectorAll('.route-print-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const routeId = this.dataset.routeId;
    const dayDate = this.dataset.dayDate;

    const url = `/customers/${customerId}/routes/print-entire-route?route_name_id=${routeId}&day_date=${dayDate}`;
    window.open(url, '_blank');
  });
});

// Exportar a Excel
document.querySelectorAll('.route-excel-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const routeId = this.dataset.routeId;
    const dayDate = this.dataset.dayDate;

    const url = `/customers/${customerId}/routes/export-entire-route-to-excel?route_name_id=${routeId}&day_date=${dayDate}`;
    window.location.href = url;
  });
});

// Imprimir vehículo específico
document.querySelectorAll('.vehicle-print-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    const assignmentId = this.dataset.assignmentId;
    const url = `/customers/${customerId}/routes/print-route-sheet?assignment_id=${assignmentId}`;
    window.open(url, '_blank');
  });
});

// Exportar vehículo a Excel
document.querySelectorAll('.vehicle-excel-btn').forEach(btn => {
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    const assignmentId = this.dataset.assignmentId;
    const url = `/customers/${customerId}/routes/export-to-excel?assignment_id=${assignmentId}`;
    window.location.href = url;
  });
});

// Toggle orden activa/inactiva
document.querySelectorAll('.order-chip').forEach(chip => {
  chip.addEventListener('click', function(e) {
    if (e.target.classList.contains('ti-x')) {
      return; // Dejar que el handler del botón X lo maneje
    }

    const orderAssignmentId = this.dataset.orderAssignmentId;
    const isActive = this.dataset.active === '1';

    fetch(`/customers/${customerId}/routes/toggle-order-active`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({
        order_assignment_id: orderAssignmentId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast(data.message, 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.message || 'Error', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error toggling order', 'danger');
    });
  });
});

// Ver detalles de cliente
let currentClientId = null;
let currentClientName = '';

document.querySelectorAll('.vehicle-client-item').forEach(item => {
  const clientName = item.dataset.clientName;
  const clientId = item.dataset.clientId;

  // Doble clic para ver detalles
  item.addEventListener('dblclick', function() {
    loadClientDetails(clientId, clientName);
  });
});

// Función para cargar detalles del cliente
function loadClientDetails(clientId, clientName) {
  currentClientId = clientId;
  currentClientName = clientName;

  const modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
  modal.show();

  document.getElementById('clientDetailsSubtitle').textContent = clientName;
  document.getElementById('clientDetailsLoading').classList.remove('d-none');
  document.getElementById('clientDetailsContent').classList.add('d-none');

  fetch(`/customers/${customerId}/routes/client-details/${clientId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        renderClientDetails(data);
      } else {
        showToast('Error loading client details', 'danger');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      showToast('Error loading client details', 'danger');
    })
    .finally(() => {
      document.getElementById('clientDetailsLoading').classList.add('d-none');
    });
}

// Función para renderizar detalles del cliente
function renderClientDetails(data) {
  const content = document.getElementById('clientDetailsContent');
  const client = data.client;
  const orders = data.orders;

  let html = `
    <div class="mb-4">
      <h6 class="fw-bold mb-2">${client.name}</h6>
      ${client.address ? `<p class="mb-1"><i class="ti ti-map-pin"></i> ${client.address}</p>` : ''}
      ${client.phone ? `<p class="mb-1"><i class="ti ti-phone"></i> ${client.phone}</p>` : ''}
    </div>
  `;

  if (orders.length === 0) {
    html += `<div class="alert alert-info"><i class="ti ti-info-circle"></i> ${window.translations?.no_pending_orders || 'No pending orders'}</div>`;
  } else {
    orders.forEach(order => {
      const statusClass = order.is_finished ? 'bg-success' : (order.is_overdue ? 'bg-danger' : 'bg-warning');
      const statusText = order.is_finished ? 'Finished' : (order.is_overdue ? 'Overdue' : 'Pending');

      html += `
        <div class="card mb-3 border-${order.is_overdue ? 'danger' : (order.is_finished ? 'success' : 'warning')}">
          <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><i class="ti ti-file-text"></i> Order: ${order.order_id}</h6>
              <span class="badge ${statusClass}">${statusText}</span>
            </div>
          </div>
          <div class="card-body">
            <div class="row mb-2">
              <div class="col-md-6">
                <small class="text-muted">Delivery Date:</small>
                <div>${order.delivery_date || order.estimated_delivery_date || 'N/A'}</div>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Finished At:</small>
                <div>${order.finished_at || 'Not finished'}</div>
              </div>
            </div>
            ${order.processes.length > 0 ? `
              <h6 class="mt-3 mb-2">Processes:</h6>
              ${order.processes.map(process => `
                <div class="mb-2 p-2 bg-light rounded">
                  <div class="fw-bold">${process.name || 'Process'} (Group: ${process.grupo_numero})</div>
                  <div class="small">
                    <span class="me-2">📦 Boxes: ${process.box || 0}</span>
                    <span class="me-2">📊 Units/Box: ${process.units_box || 0}</span>
                    <span class="me-2">🚛 Pallets: ${process.number_of_pallets || 0}</span>
                  </div>
                  ${process.articles.length > 0 ? `
                    <div class="mt-2">
                      <small class="text-muted">Articles:</small>
                      <ul class="list-unstyled mb-0 ms-3">
                        ${process.articles.map(article => `
                          <li class="small">
                            <code>${article.codigo_articulo}</code> - ${article.descripcion_articulo || 'N/A'}
                            ${article.grupo_articulo ? `<span class="badge bg-secondary ms-1">${article.grupo_articulo}</span>` : ''}
                          </li>
                        `).join('')}
                      </ul>
                    </div>
                  ` : ''}
                </div>
              `).join('')}
            ` : ''}
          </div>
        </div>
      `;
    });
  }

  content.innerHTML = html;
  content.classList.remove('d-none');
}

// Imprimir detalles del cliente
document.getElementById('printClientDetailsBtn').addEventListener('click', function() {
  if (!currentClientId) return;

  const printContent = document.getElementById('clientDetailsContent').innerHTML;
  const printWindow = window.open('', '_blank');

  printWindow.document.write(
    '<html>' +
      '<head>' +
        '<title>Client Details - ' + currentClientName + '</title>' +
        '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">' +
        '<style>' +
          'body { padding: 20px; }' +
          '@media print { .no-print { display: none; } }' +
        '</style>' +
      '</head>' +
      '<body>' +
        '<h2>Client Details: ' + currentClientName + '</h2>' +
        '<p class="text-muted">Generated on: ' + new Date().toLocaleString() + '</p>' +
        '<hr>' +
        printContent +
        '<scr' + 'ipt>' +
          'window.onload = function() { window.print(); }' +
        '</scr' + 'ipt>' +
      '</body>' +
    '</html>'
  );

  printWindow.document.close();
});

// Descargar PDF
document.getElementById('downloadClientDetailsPdfBtn').addEventListener('click', function() {
  if (!currentClientId) {
    showToast('Please load client details first', 'warning');
    return;
  }

  showToast('Generating PDF...', 'info');

  // Llamar a un endpoint que genere el PDF
  const url = `/customers/${customerId}/routes/client-details/${currentClientId}/pdf?date=${currentDate}`;

  fetch(url, {
    method: 'GET',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
  })
  .then(response => {
    if (!response.ok) throw new Error('Error generating PDF');
    return response.blob();
  })
  .then(blob => {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `client-details-${currentClientName}-${currentDate}.pdf`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    showToast('PDF downloaded successfully', 'success');
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error generating PDF. Using print instead.', 'warning');
    // Fallback to print
    document.getElementById('printClientDetailsBtn').click();
  });
});

// Enviar por email
document.getElementById('emailClientDetailsBtn').addEventListener('click', function() {
  if (!currentClientId) {
    showToast('Please load client details first', 'warning');
    return;
  }

  // Mostrar modal para introducir email
  const email = prompt('Enter email address to send client details:');

  if (!email) {
    return;
  }

  // Validar email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showToast('Invalid email address', 'danger');
    return;
  }

  showToast('Sending email...', 'info');

  fetch(`/customers/${customerId}/routes/client-details/${currentClientId}/email`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
      email: email,
      date: currentDate,
      client_name: currentClientName
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast(`Email sent successfully to ${email}`, 'success');
    } else {
      showToast(data.message || 'Error sending email', 'danger');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error sending email', 'danger');
  });
});
</script>
@endpush
