@extends('layouts.admin')

@section('title', __('Listado de Rutas') . ' - ' . $customer->name)
@section('page-title', __('Listado de Rutas'))

@push('styles')
<style>
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
  
  @keyframes pulse {
    0%, 100% {
      transform: scale(1);
    }
    50% {
      transform: scale(1.05);
    }
  }
  
  .kpi-card:active {
    animation: pulse 0.3s ease;
  }
</style>
@endpush

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Rutas') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Rutas') }}</h5>
  </div>
  <div class="card-body">
    @php
      $weekParam = request()->get('week'); // expected format YYYY-MM-DD (monday)
      $monday = $weekParam ? \Carbon\Carbon::parse($weekParam)->startOfWeek(\Carbon\Carbon::MONDAY) : now()->startOfWeek(\Carbon\Carbon::MONDAY);
      $days = [];
      for ($i = 0; $i < 7; $i++) {
        $days[] = (clone $monday)->addDays($i);
      }
      $prevWeek = (clone $monday)->subWeek()->format('Y-m-d');
      $nextWeek = (clone $monday)->addWeek()->format('Y-m-d');
      $dayNames = [__('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday'), __('Sunday')];
    @endphp

    <div class="page-header-container mb-4">
      <!-- Columna Izquierda: Stats y Filtros -->
      <div class="header-left-column">
        <!-- Estadísticas en tiempo real -->
        @php
          $totalVehicles = collect($routeAssignments ?? [])->count();
          $totalClients = collect($clientVehicleAssignments ?? [])->count();
          $totalRoutes = collect($routeNames ?? [])->count();
          $activeRoutes = collect($routeAssignments ?? [])->pluck('route_name_id')->unique()->count();
          
          // Calcular pedidos en camiones
          $totalOrdersInTrucks = collect($clientVehicleAssignments ?? [])->sum(function($assignment) {
              return $assignment->orderAssignments->where('active', true)->count();
          });
        @endphp
        
        <div>
          <h6 class="text-muted mb-2">{{ __('Statistics') }}</h6>
          <div id="routes-summary" class="row g-3">
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
                    <div class="fs-3 fw-bold" id="orders-in-trucks-count">{{ $totalOrdersInTrucks }}</div>
                  </div>
                  <div class="kpi-icon-wrapper" style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                    <i class="ti ti-package fs-1 text-white"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col">
            <div class="card h-100 border-0 shadow-sm kpi-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); transition: all 0.3s ease;">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div class="text-white">
                    <div class="small mb-1" style="opacity: 0.9;">{{ __('Week') }}</div>
                    <div class="fs-3 fw-bold">{{ now()->format('W') }}</div>
                  </div>
                  <div class="kpi-icon-wrapper" style="background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px;">
                    <i class="ti ti-calendar-week fs-1 text-white"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        </div>

        <div>
          <h6 class="text-muted mb-2">{{ __('Filters') }}</h6>
          <div class="filters-bar">
          <input type="search" id="clientSearch" placeholder="{{ __('Search client...') }}" class="form-control">
          <input type="search" id="orderSearch" placeholder="{{ __('🔍 Search order number...') }}" class="form-control" style="border: 2px solid #0d6efd;">
          <select id="vehicleTypeFilter" class="form-select">
            <option value="">{{ __('All vehicles') }}</option>
            <option value="furgoneta">🚐 {{ __('Van') }}</option>
            <option value="camion">🚛 {{ __('Truck') }}</option>
            <option value="moto">🏍️ {{ __('Motorcycle') }}</option>
          </select>
          <select id="routeStatusFilter" class="form-select">
            <option value="">{{ __('All routes') }}</option>
            <option value="with-vehicles">{{ __('Routes with vehicles') }}</option>
            <option value="without-vehicles">{{ __('Routes without vehicles') }}</option>
          </select>
          <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
            <i class="fas fa-times"></i> {{ __('Clear filters') }}
          </button>
        </div>
      </div>

      <!-- Columna Derecha: Navegación -->
      <div class="header-right-column">
        <div class="d-flex justify-content-end align-items-center gap-2">
          <a href="{{ request()->fullUrlWithQuery(['week' => $prevWeek]) }}" class="btn btn-sm btn-outline-secondary">&laquo; {{ __('Previous') }}</a>
          <span class="fw-semibold px-3 text-nowrap">{{ $monday->format('d M') }} &ndash; {{ (clone $monday)->addDays(6)->format('d M, Y') }}</span>
          <a href="{{ request()->fullUrlWithQuery(['week' => $nextWeek]) }}" class="btn btn-sm btn-outline-secondary">{{ __('Next') }} &raquo;</a>
          <a href="{{ request()->url() }}" class="btn btn-sm btn-secondary">{{ __('Today') }}</a>
        </div>
      </div>
    </div>

    <div id="routes-table-wrapper" class="table-responsive mt-4">
      <table class="table table-bordered table-hover">
        <thead class="table-light">
          <tr>
            <th style="width: 200px;">{{ __('Route') }}</th>
            @foreach($days as $idx => $day)
              <th class="text-center" style="min-width: 150px;">
                <div class="fw-semibold">{{ $dayNames[$idx] }}</div>
                <div class="text-muted small">{{ $day->format('d M') }}</div>
              </th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @php($clientsByRoute = collect($customerClients ?? [])->groupBy('route_name_id'))
          @forelse(($routeNames ?? []) as $r)
            <tr>
              <td class="align-top">
                <div class="fw-semibold text-primary mb-1">{{ $r->name }}</div>
                @php($maskAll = (int)($r->days_mask ?? 0))
                <div class="d-flex flex-wrap gap-1">
                  @php($labels=[__('L'),__('M'),__('X'),__('J'),__('V'),__('S'),__('D')])
                  @for($i=0;$i<7;$i++)
                    <span class="badge rounded-pill {{ ($maskAll & (1<<$i))? 'bg-primary' : 'bg-light text-muted' }}" style="font-size: 0.7em;">{{ $labels[$i] }}</span>
                  @endfor
                </div>
              </td>
              @for($i=0; $i<7; $i++)
                <x-routes.day-cell 
                  :route="$r"
                  :dayIndex="$i"
                  :days="$days"
                  :dayNames="$dayNames"
                  :clientsByRoute="$clientsByRoute"
                  :routeAssignments="$routeAssignments"
                  :clientVehicleAssignments="$clientVehicleAssignments"
                  :fleetVehicles="$fleetVehicles"
                />
              @endfor
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-4">
                {{ __('No routes created yet') }}
                <div class="small mt-1">
                  <a href="{{ route('customers.route-names.index', $customer->id) }}" class="text-decoration-none">{{ __('Create your first route') }}</a>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
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
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <div>
          <h5 class="modal-title mb-0"><i class="ti ti-clipboard-text"></i> {{ __('Client details') }}</h5>
          <small class="d-block opacity-75" id="clientDetailsSubtitle"></small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-light btn-sm" id="printClientDetailsBtn">
            <i class="ti ti-printer"></i> {{ __('Print') }}
          </button>
          <button type="button" class="btn btn-light btn-sm" id="exportClientDetailsBtn">
            <i class="ti ti-file-download"></i> {{ __('Export PDF') }}
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
<style>
  .table th {
    position: sticky;
    top: 0;
    background: #f8f9fa !important;
    z-index: 10;
  }
  .table td {
    vertical-align: top;
    min-height: 80px;
  }
  .badge {
    font-size: 0.75em;
  }
  .client-detail-card {
    border: 1px solid #dee2e6;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    margin-bottom: 1rem;
    overflow: hidden;
  }
  .client-detail-card .card-header {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: #fff;
  }
  .client-process-item {
    border-left: 4px solid #0d6efd;
    padding-left: 12px;
    margin-bottom: 12px;
  }
  .client-process-item .badge {
    font-size: 0.7rem;
  }
  .client-articles-list {
    border-radius: 8px;
    background: #f8fafc;
    padding: 10px;
    margin-top: 8px;
  }
  .client-article-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid rgba(148, 163, 184, 0.35);
    font-size: 0.85rem;
  }
  .client-article-row:last-child {
    border-bottom: none;
  }
  .client-article-row code {
    background: rgba(71, 85, 105, 0.1);
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 0.75rem;
  }
  .client-article-stock {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" integrity="sha512-0NRoNbVc5hVW0Qwd4uKZgdKzac8AjtKoa6HgMHqmpYyqn1nVbWcv16O3Qe9n3VWeItPxX2VINeodIZ6T2fCk7w==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaLb2xY1jBJOCa/MEGLjm+rXhN3kLBXjg5eQof8I4eAbOe+tfLOcAfeUeawuO/7dBDEuDfSUdifYEsaJ2P0hA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toast helper function (global)
  const toastEl = document.getElementById('routesToast');
  const toastBody = document.getElementById('routesToastBody');
  window.showToast = function(msg, variant='primary', delay=2000){
    if (!toastEl || !toastBody) {
      console.warn('Toast elements not found, falling back to alert:', msg);
      alert(msg);
      return;
    }
    toastEl.className = `toast align-items-center text-white bg-${variant} border-0`;
    toastBody.textContent = msg;
    const t = new bootstrap.Toast(toastEl, { delay });
    t.show();
  }
  
  // Sistema de auto-refresh después de cambios (global)
  let refreshTimeout = null;
  let isDragging = false;
  
  window.scheduleRefresh = function(delay = 2000) {
    if (refreshTimeout) clearTimeout(refreshTimeout);
    refreshTimeout = setTimeout(() => {
      // Verificar si hay modals abiertos antes de refrescar
      const openModals = document.querySelectorAll('.modal.show');
      if (openModals.length > 0) {
        console.log('Modal abierto detectado, posponiendo refresh...');
        // Reprogramar el refresh para más tarde
        window.scheduleRefresh(3000);
        return;
      }
      
      // Verificar si se está arrastrando algo
      if (isDragging) {
        console.log('Drag en progreso detectado, posponiendo refresh...');
        // Reprogramar el refresh para más tarde
        window.scheduleRefresh(2000);
        return;
      }
      
      console.log('Auto-refreshing page after changes...');
      window.location.reload();
    }, delay);
  }
  
  window.cancelScheduledRefresh = function() {
    if (refreshTimeout) {
      clearTimeout(refreshTimeout);
      refreshTimeout = null;
    }
  }
  
  // Listeners para manejar modals y refresh
  document.addEventListener('show.bs.modal', function(e) {
    console.log('Modal abierto, cancelando refresh programado');
    window.cancelScheduledRefresh();
  });
  
  document.addEventListener('hidden.bs.modal', function(e) {
    console.log('Modal cerrado, reprogramando refresh si es necesario');
    // Solo reprogramar si había cambios pendientes (esto se puede mejorar con una flag)
    // Por ahora, reprogramamos con un delay más largo para dar tiempo al usuario
    if (document.querySelector('.toast.show')) {
      // Si hay un toast visible, significa que hubo una acción reciente
      window.scheduleRefresh(2000);
    }
  });

  // Doble click para ver detalles de cliente
  const printClientDetailsBtn = document.getElementById('printClientDetailsBtn');
  const exportClientDetailsBtn = document.getElementById('exportClientDetailsBtn');

  function setClientDetailsActionsEnabled(enabled) {
    [printClientDetailsBtn, exportClientDetailsBtn].forEach(btn => {
      if (!btn) return;
      btn.disabled = !enabled;
      btn.classList.toggle('disabled', !enabled);
    });
  }

  setClientDetailsActionsEnabled(false);

  document.addEventListener('dblclick', function(e) {
    const clientTag = e.target.closest('.draggable-client, .vehicle-client-item');
    if (!clientTag) return;

    const clientId = clientTag.dataset.clientId;
    const clientName = clientTag.dataset.clientName || clientTag.querySelector('.flex-grow-1')?.textContent?.trim();
    if (!clientId) {
      console.warn('Client ID not found on element for details modal');
      return;
    }

    openClientDetailsModal(clientId, clientName);
  });

  function openClientDetailsModal(clientId, clientName) {
    const modalEl = document.getElementById('clientDetailsModal');
    const loadingEl = document.getElementById('clientDetailsLoading');
    const contentEl = document.getElementById('clientDetailsContent');

    loadingEl.classList.remove('d-none');
    contentEl.classList.add('d-none');
    contentEl.innerHTML = '';

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    const detailsUrl = '{{ route("customers.routes.client-details", [$customer->id, ':clientId']) }}'.replace(':clientId', clientId);

    fetch(detailsUrl, {
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
      })
      .then(data => {
        if (!data.success) throw new Error(data.message || 'Unknown error');

        renderClientDetails(data, contentEl, clientName);
        loadingEl.classList.add('d-none');
        contentEl.classList.remove('d-none');
        const subtitle = [];
        if (data.client?.address) subtitle.push(data.client.address);
        if (data.orders?.length) subtitle.push(`{{ __('Orders') }}: ${data.orders.length}`);
        document.getElementById('clientDetailsSubtitle').textContent = subtitle.join(' · ');
        setClientDetailsActionsEnabled(true);
      })
      .catch(error => {
        console.error('Error fetching client details:', error);
        loadingEl.innerHTML = `<div class="alert alert-danger">{{ __('Error loading details') }}: ${error.message}</div>`;
        setClientDetailsActionsEnabled(false);
      });
  }

  function renderClientDetails(data, container, fallbackName) {
    const client = data.client || {};
    const orders = data.orders || [];

    const headerHtml = `
      <div class="mb-4">
        <h4 class="mb-1">${client.name || fallbackName || '{{ __('Client') }}'}</h4>
        <div class="text-muted small">
          ${client.address ? `<div><i class="ti ti-map-pin"></i> ${client.address}</div>` : ''}
          ${client.phone ? `<div><i class="ti ti-phone"></i> ${client.phone}</div>` : ''}
        </div>
      </div>
    `;

    if (orders.length === 0) {
      container.innerHTML = `${headerHtml}<div class="alert alert-info">{{ __('No pending orders for this client') }}</div>`;
      return;
    }

    const ordersHtml = orders.map(order => {
      const processesHtml = (order.processes || []).map(process => {
        const articlesHtml = (process.articles || []).map(article => `
          <div class="client-article-row">
            <div>
              <code>${article.codigo_articulo || '-'}</code>
              <div class="text-muted">${article.descripcion_articulo || ''}</div>
            </div>
            <div class="text-end">
              <div class="client-article-stock ${article.in_stock ? 'text-success' : 'text-danger'}">
                <i class="ti ${article.in_stock ? 'ti-check' : 'ti-alert-triangle'}"></i>
                ${article.in_stock ? '{{ __('In stock') }}' : '{{ __('No stock') }}'}
              </div>
              <div class="text-muted small">${article.grupo_articulo || ''}</div>
            </div>
          </div>
        `).join('');

        return `
          <div class="client-process-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <strong>#${process.grupo_numero || '-'} · ${process.name || '{{ __('Process') }}'}</strong>
                <div class="text-muted small">{{ __('Time') }}: ${process.time || '—'} · {{ __('Boxes') }}: ${process.box ?? '0'} · {{ __('Units/Box') }}: ${process.units_box ?? '0'} · {{ __('Pallets') }}: ${process.number_of_pallets ?? '0'}</div>
              </div>
              <span class="badge ${process.in_stock ? 'bg-success' : 'bg-danger'}">${process.in_stock ? '{{ __('Stock ready') }}' : '{{ __('Awaiting stock') }}'}</span>
            </div>
            ${articlesHtml ? `<div class="client-articles-list">${articlesHtml}</div>` : `<div class="text-muted small">{{ __('No articles linked to this process') }}</div>`}
          </div>
        `;
      }).join('');

      // Determinar badge y estado según si está finalizado o no
      let badgeClass = 'bg-success';
      let badgeText = '{{ __('Ready') }}';
      
      if (!order.is_finished) {
        // Pedido NO finalizado
        badgeClass = order.is_overdue ? 'bg-danger' : 'bg-warning text-dark';
        badgeText = '{{ __('Pending completion') }}';
      } else if (!order.in_stock) {
        // Pedido finalizado pero sin stock completo
        badgeClass = 'bg-warning text-dark';
        badgeText = '{{ __('Pending stock') }}';
      }

      return `
        <div class="client-detail-card ${!order.is_finished ? 'border-warning' : ''}">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold">{{ __('Order') }} #${order.order_id}</div>
              <div class="small">${order.delivery_date ? `{{ __('Delivery') }}: ${order.delivery_date}` : order.estimated_delivery_date ? `{{ __('Estimated') }}: ${order.estimated_delivery_date}` : '{{ __('No delivery date') }}'}</div>
            </div>
            <div>
              <span class="badge ${badgeClass}">
                ${badgeText}
              </span>
            </div>
          </div>
          <div class="card-body">
            ${processesHtml || `<div class="text-muted small">{{ __('No processes associated to this order') }}</div>`}
          </div>
        </div>
      `;
    }).join('');

    container.innerHTML = headerHtml + ordersHtml;
  }

  function printClientDetails() {
    const contentEl = document.getElementById('clientDetailsContent');
    if (!contentEl || contentEl.classList.contains('d-none')) return;

    const printWindow = window.open('', '_blank', 'width=900,height=800');
    const styles = Array.from(document.styleSheets)
      .map(sheet => {
        try {
          if (sheet.href) {
            return `<link rel="stylesheet" href="${sheet.href}">`;
          }
        } catch (err) {
          return '';
        }
        return '';
      })
      .join('\n');

    printWindow.document.write(`<!DOCTYPE html><html><head><title>{{ __('Client details') }}</title>${styles}<style>body{font-family: Arial, sans-serif; padding:20px;} .client-detail-card{page-break-inside:avoid;}</style></head><body>${contentEl.innerHTML}</body></html>`);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
  }

  async function exportClientDetailsPdf() {
    const contentEl = document.getElementById('clientDetailsContent');
    if (!contentEl || contentEl.classList.contains('d-none')) return;
    if (!window.jspdf || !window.jspdf.jsPDF || typeof html2canvas === 'undefined') {
      window.showToast('{{ __('PDF library not loaded') }}', 'danger', 3000);
      return;
    }

    const { jsPDF } = window.jspdf;
    const canvas = await html2canvas(contentEl, { scale: 2, useCORS: true, windowWidth: contentEl.scrollWidth });
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF('p', 'pt', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const imgWidth = pageWidth - 40;
    const imgHeight = (canvas.height * imgWidth) / canvas.width;
    let position = 20;

    if (imgHeight < pageHeight - 40) {
      pdf.addImage(imgData, 'PNG', 20, position, imgWidth, imgHeight);
    } else {
      let remainingHeight = imgHeight;
      let y = 20;
      const canvasPageHeight = ((pageHeight - 40) * canvas.width) / imgWidth;
      while (remainingHeight > 0) {
        const canvasElement = document.createElement('canvas');
        canvasElement.width = canvas.width;
        canvasElement.height = Math.min(canvasPageHeight, canvas.height - (canvas.height - remainingHeight * canvas.width / imgWidth));
        const ctx = canvasElement.getContext('2d');
        ctx.drawImage(canvas, 0, canvas.height - remainingHeight * canvas.width / imgWidth, canvas.width, canvasElement.height, 0, 0, canvas.width, canvasElement.height);
        const pageData = canvasElement.toDataURL('image/png');
        if (y !== 20) {
          pdf.addPage();
          y = 20;
        }
        const pageImgHeight = (canvasElement.height * imgWidth) / canvasElement.width;
        pdf.addImage(pageData, 'PNG', 20, y, imgWidth, pageImgHeight);
        remainingHeight -= pageImgHeight;
        if (remainingHeight > 0) {
          pdf.addPage();
        }
      }
    }

    const modalEl = document.getElementById('clientDetailsModal');
    const clientName = modalEl.dataset.clientName || 'client';
    const clientId = modalEl.dataset.clientId || 'details';
    pdf.save(`client-${clientId}-${clientName.replace(/\s+/g, '_')}.pdf`);
  }

  printClientDetailsBtn?.addEventListener('click', printClientDetails);
  exportClientDetailsBtn?.addEventListener('click', () => {
    exportClientDetailsPdf().catch(err => {
      console.error('PDF export error:', err);
      window.showToast('{{ __('Error generating PDF') }}', 'danger', 3000);
    });
  });
  
  // Listeners para detectar drag & drop
  document.addEventListener('dragstart', function(e) {
    if (e.target.closest('.draggable-client, .vehicle-client-item')) {
      isDragging = true;
      console.log('Drag iniciado, pausando auto-refresh');
      window.cancelScheduledRefresh();
    }
  });
  
  document.addEventListener('dragend', function(e) {
    if (e.target.closest('.draggable-client, .vehicle-client-item')) {
      isDragging = false;
      console.log('Drag finalizado, auto-refresh disponible');
      // No reprogramar automáticamente aquí, ya que el dragend específico lo hará si es necesario
    }
  });
  
  // También detectar cuando se cancela el drag (escape, click fuera, etc.)
  document.addEventListener('dragover', function(e) {
    // Mantener el estado de dragging mientras se mueve
  });
  
  document.addEventListener('drop', function(e) {
    // El drop se maneja en los listeners específicos
  });
  
  let currentRouteId = null;
  let currentRouteName = null;
  let currentDayIndex = null;
  let currentDayName = null;

  // Manejar click en botón "+"
  document.querySelectorAll('[data-bs-target="#vehicleModal"]').forEach(btn => {
    btn.addEventListener('click', function() {
      currentRouteId = this.dataset.routeId;
      currentRouteName = this.dataset.routeName;
      currentDayIndex = this.dataset.dayIndex;
      currentDayName = this.dataset.dayName;
      
      document.getElementById('modalRouteInfo').textContent = 
        `{{ __('Route') }}: ${currentRouteName} | {{ __('Day') }}: ${currentDayName}`;
      
      // Reset select
      document.getElementById('vehicleSelect').value = '';
    });
  });

  // Manejar asignación de vehículo
  document.getElementById('assignVehicleBtn').addEventListener('click', function() {
    const vehicleSelect = document.getElementById('vehicleSelect');
    const driverSelect = document.getElementById('driverSelect');
    const vehicleId = vehicleSelect.value;
    const driverId = driverSelect.value || null;
    
    if (!vehicleId) {
      showToast('{{ __('Please select a vehicle') }}','warning');
      return;
    }

    const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
    const plate = selectedOption.dataset.plate;
    const type = selectedOption.dataset.type;
    
    // Llamada AJAX para guardar la asignación
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const currentWeek = new URLSearchParams(window.location.search).get('week') || '{{ now()->startOfWeek()->format("Y-m-d") }}';
    
    fetch('{{ route("customers.routes.assign-vehicle", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        route_name_id: currentRouteId,
        fleet_vehicle_id: vehicleId,
        user_id: driverId,
        day_index: parseInt(currentDayIndex),
        week: currentWeek
      })
    })
    .then(response => {
      console.log('Response status:', response.status);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('Response data:', data);
      if (data.success) {
        showToast('{{ __('Vehicle assigned successfully') }}','success');
        
        // Programar refresh automático después de asignar vehículo
        window.scheduleRefresh(3000);
        
        // Actualizar la celda dinámicamente en lugar de recargar
        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
        const plate = selectedOption.dataset.plate;
        const type = selectedOption.dataset.type;
        
        // Encontrar la celda correspondiente y añadir el vehículo
        const buttons = document.querySelectorAll(`[data-route-id="${currentRouteId}"][data-day-index="${currentDayIndex}"]`);
        if (buttons.length > 0) {
          const cell = buttons[0].closest('td');
          
          // Buscar si ya existe una sección de vehículos asignados específica
          let vehicleSection = cell.querySelector('.assigned-vehicles');
          if (!vehicleSection) {
            // Crear nueva sección de vehículos asignados
            vehicleSection = document.createElement('div');
            vehicleSection.className = 'mt-2 assigned-vehicles';
            vehicleSection.innerHTML = '<small class="text-muted">{{ __('Assigned') }}:</small>';
            cell.appendChild(vehicleSection);
          }
          
          // Crear el contenedor completo del vehículo con zona de drop
          const vehicleContainer = document.createElement('div');
          vehicleContainer.className = 'vehicle-container mb-2 p-2 border rounded bg-primary text-white vehicle-drop-zone';
          vehicleContainer.setAttribute('data-vehicle-id', vehicleId);
          vehicleContainer.setAttribute('data-assignment-id', 'new'); // Se actualizará en el próximo refresh
          vehicleContainer.setAttribute('data-route-id', currentRouteId);
          vehicleContainer.setAttribute('data-day-index', currentDayIndex);
          vehicleContainer.style.minHeight = '60px';
          
          vehicleContainer.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-1">
              <span class="fw-bold">
                <i class="fas fa-truck me-1"></i>${plate}${type ? ` <small>(${type})</small>` : ''}
              </span>
              <div class="d-flex align-items-center gap-1">
                <button class="btn btn-sm p-0 text-white vehicle-print-btn"
                        type="button"
                        style="background: none; border: none; font-size: 12px;"
                        data-assignment-id="new"
                        data-vehicle-plate="${plate}"
                        title="{{ __('Print route sheet') }}">
                  <i class="fas fa-print"></i>
                </button>
                <button class="btn btn-sm p-0 text-white remove-vehicle-btn" 
                        style="background: none; border: none; font-size: 12px;"
                        data-assignment-id="new"
                        title="{{ __('Remove vehicle') }}">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <div class="vehicle-clients-list" style="min-height: 30px;">
              <small class="opacity-75">{{ __('Drop clients here') }}</small>
            </div>
          `;
          
          vehicleSection.appendChild(vehicleContainer);
        }
      } else {
        showToast('{{ __('Error assigning vehicle') }}: ' + (data.message || 'Unknown error'),'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error details:', error);
      showToast('{{ __('Error assigning vehicle') }}: ' + error.message, 'danger', 3000);
    });
    
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('vehicleModal'));
    modal.hide();
  });

  // Drag & Drop functionality
  let draggedClient = null;

  // Drag start - cuando se empieza a arrastrar un cliente
  document.addEventListener('dragstart', function(e) {
    if (e.target.classList.contains('draggable-client')) {
      draggedClient = {
        element: e.target,
        clientId: e.target.dataset.clientId,
        clientName: e.target.dataset.clientName,
        routeId: e.target.dataset.routeId,
        dayIndex: e.target.dataset.dayIndex
      };
      e.target.style.opacity = '0.5';
      console.log('Dragging client:', draggedClient);
    }
  });

  // Drag end - cuando se termina de arrastrar
  document.addEventListener('dragend', function(e) {
    if (e.target.classList.contains('draggable-client')) {
      e.target.style.opacity = '1';
      draggedClient = null;
    }
  });

  // Drag over - permitir drop en las zonas de vehículos
  document.addEventListener('dragover', function(e) {
    if (e.target.closest('.vehicle-drop-zone') && draggedClient) {
      e.preventDefault();
      const dropZone = e.target.closest('.vehicle-drop-zone');
      dropZone.classList.add('drag-over');
    }
  });

  // Drag leave - quitar highlight cuando se sale de la zona
  document.addEventListener('dragleave', function(e) {
    if (e.target.closest('.vehicle-drop-zone')) {
      const dropZone = e.target.closest('.vehicle-drop-zone');
      dropZone.classList.remove('drag-over');
    }
  });

  // Drop - cuando se suelta el cliente en un vehículo
  document.addEventListener('drop', function(e) {
    const dropZone = e.target.closest('.vehicle-drop-zone');
    if (dropZone && draggedClient) {
      e.preventDefault();
      dropZone.classList.remove('drag-over');
      
      const vehicleId = dropZone.dataset.vehicleId;
      const routeId = dropZone.dataset.routeId;
      const dayIndex = dropZone.dataset.dayIndex;
      
      console.log('Dropping client in vehicle:', {
        client: draggedClient,
        vehicle: vehicleId,
        route: routeId,
        day: dayIndex
      });

      // Llamada AJAX para asignar cliente al vehículo
      assignClientToVehicle(draggedClient.clientId, vehicleId, routeId, dayIndex, draggedClient.clientName);
    }
  });

  // Función para asignar cliente a vehículo
  function assignClientToVehicle(clientId, vehicleId, routeId, dayIndex, clientName) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const currentWeek = new URLSearchParams(window.location.search).get('week') || '{{ now()->startOfWeek()->format("Y-m-d") }}';
    
    fetch('{{ route("customers.routes.assign-client-vehicle", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        customer_client_id: clientId,
        fleet_vehicle_id: vehicleId,
        route_name_id: routeId,
        day_index: parseInt(dayIndex),
        week: currentWeek
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Mover el cliente visualmente al vehículo
        const vehicleContainer = document.querySelector(`[data-vehicle-id="${vehicleId}"][data-day-index="${dayIndex}"]`);
        const clientsList = vehicleContainer.querySelector('.vehicle-clients-list');
        
        // Remover el placeholder si existe
        const placeholder = clientsList.querySelector('.opacity-75');
        if (placeholder) placeholder.remove();
        
        // Crear el badge del cliente en el vehículo con pedidos debajo del nombre
        const clientBadge = document.createElement('div');
        clientBadge.className = 'badge bg-light text-dark me-1 mb-1 d-flex flex-column vehicle-client-item';
        clientBadge.style.minWidth = '140px';
        clientBadge.style.padding = '8px';
        clientBadge.setAttribute('draggable', 'true');
        clientBadge.setAttribute('data-client-name', clientName);
        clientBadge.setAttribute('data-client-assignment-id', 'new');
        clientBadge.setAttribute('data-client-id', clientId);
        clientBadge.innerHTML = `
          <div class="d-flex align-items-center justify-content-between w-100">
            <span class="drag-handle me-2 text-muted" title="{{ __('Drag to reorder') }}">⋮⋮</span>
            <span class="flex-grow-1">${clientName}</span>
            <button class="btn btn-sm p-0 text-danger client-remove-btn" 
                    style="background: none; border: none; font-size: 10px; line-height: 1;"
                    data-client-assignment-id="new"
                    data-client-name="${clientName}"
                    title="{{ __('Remove client from vehicle') }}">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="client-orders d-flex flex-wrap gap-1 mt-2">
            <span class="order-chip">pedido-test1 <i class="ti ti-x ms-1"></i></span>
            <span class="order-chip">pedido-test2 <i class="ti ti-x ms-1"></i></span>
          </div>
        `;
        clientsList.appendChild(clientBadge);
        
        // Remover el cliente de la lista disponible del mismo día/ruta (robusto)
        try {
          const sourceBtn = document.querySelector(`[data-route-id="${routeId}"][data-day-index="${dayIndex}"]`);
          const sourceCell = sourceBtn ? sourceBtn.closest('td') : null;
          if (sourceCell) {
            sourceCell.querySelectorAll(`.clients-list .draggable-client[data-client-id="${clientId}"]`).forEach(el => {
              const li = el.closest('li'); if (li) li.remove();
            });
          }
        } catch (e) { console.warn('Cleanup available list failed:', e); }

        // Fallback: si tenemos referencia directa, eliminarla también
        if (draggedClient && draggedClient.element) {
          const li = draggedClient.element.closest('li');
          if (li && li.isConnected) li.remove();
        }
        
        console.log('Client assigned successfully');
        
        // Programar refresh automático después de asignar cliente por drag & drop
        window.scheduleRefresh(2000);
      } else {
        window.showToast('{{ __('Error assigning client to vehicle') }}: ' + (data.message || 'Unknown error'), 'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error assigning client:', error);
      window.showToast('{{ __('Error assigning client to vehicle') }}: ' + error.message, 'danger', 3000);
    });
  }

  // Variables globales para modal de conductor
  let currentAssignmentId = null;

  // Guardar conductor
  document.getElementById('saveDriverBtn').addEventListener('click', function() {
    const driverId = document.getElementById('driverModalSelect').value || null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('{{ route("customers.routes.assign-vehicle", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        assignment_id: currentAssignmentId,
        user_id: driverId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        window.showToast('{{ __('Driver updated successfully') }}', 'success', 2000);
        const modal = bootstrap.Modal.getInstance(document.getElementById('driverModal'));
        modal.hide();
        setTimeout(() => window.location.reload(), 1000);
      } else {
        window.showToast('{{ __('Error') }}: ' + (data.message || 'Unknown error'), 'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error updating driver:', error);
      window.showToast('{{ __('Error updating driver') }}', 'danger', 3000);
    });
  });

  // Manejar botones de clientes dentro de vehículos
  document.addEventListener('click', function(e) {
    // Botón de asignar/cambiar conductor
    if (e.target.closest('.vehicle-assign-driver-btn')) {
      const btn = e.target.closest('.vehicle-assign-driver-btn');
      currentAssignmentId = btn.dataset.assignmentId;
      const currentDriverId = btn.dataset.currentDriverId;
      const vehiclePlate = btn.dataset.vehiclePlate;
      
      // Actualizar info del modal
      document.getElementById('modalDriverVehicleInfo').textContent = 
        `{{ __('Vehicle') }}: ${vehiclePlate}`;
      
      // Seleccionar conductor actual si existe
      document.getElementById('driverModalSelect').value = currentDriverId || '';
      
      // Mostrar modal
      const modal = new bootstrap.Modal(document.getElementById('driverModal'));
      modal.show();
      return;
    }

    // Botón de copiar RUTA COMPLETA de semana anterior
    if (e.target.closest('.route-copy-prev-week-btn')) {
      const btn = e.target.closest('.route-copy-prev-week-btn');
      const routeId = btn.dataset.routeId;
      const dayIndex = btn.dataset.dayIndex;
      const currentWeek = new URLSearchParams(window.location.search).get('week') || '{{ now()->startOfWeek()->format("Y-m-d") }}';
      
      if (confirm('{{ __('Copy ENTIRE route (all vehicles) from last week? Current orders will be assigned.') }}')) {
        copyEntireRouteFromPreviousWeek(routeId, dayIndex, currentWeek);
      }
      return;
    }

    // Botón de imprimir RUTA COMPLETA
    if (e.target.closest('.route-print-btn')) {
      const btn = e.target.closest('.route-print-btn');
      const routeId = btn.dataset.routeId;
      const routeName = btn.dataset.routeName;
      const dayDate = btn.dataset.dayDate;
      
      console.log('Opening print for entire route:', { routeId, routeName, dayDate });
      
      const printUrl = '{{ route("customers.routes.print-entire-route", $customer->id) }}?route_name_id=' + routeId + '&day_date=' + dayDate;
      window.open(printUrl, '_blank', 'width=900,height=800');
      
      window.showToast(`{{ __('Opening route sheet for') }} ${routeName}...`, 'info', 2000);
      return;
    }

    // Botón de Excel RUTA COMPLETA
    if (e.target.closest('.route-excel-btn')) {
      const btn = e.target.closest('.route-excel-btn');
      const routeId = btn.dataset.routeId;
      const routeName = btn.dataset.routeName;
      const dayDate = btn.dataset.dayDate;
      
      console.log('Exporting entire route to Excel:', { routeId, routeName, dayDate });
      
      const excelUrl = '{{ route("customers.routes.export-entire-route-excel", $customer->id) }}?route_name_id=' + routeId + '&day_date=' + dayDate;
      window.location.href = excelUrl;
      
      window.showToast(`{{ __('Downloading Excel for') }} ${routeName}...`, 'success', 2000);
      return;
    }

    // Botón de copiar de semana anterior (vehículo individual)
    if (e.target.closest('.vehicle-copy-prev-week-btn')) {
      const btn = e.target.closest('.vehicle-copy-prev-week-btn');
      const routeId = btn.dataset.routeId;
      const vehicleId = btn.dataset.vehicleId;
      const dayIndex = btn.dataset.dayIndex;
      const currentWeek = new URLSearchParams(window.location.search).get('week') || '{{ now()->startOfWeek()->format("Y-m-d") }}';
      
      if (confirm('{{ __('Copy clients from this route last week? Current orders will be assigned.') }}')) {
        copyFromPreviousWeek(routeId, vehicleId, dayIndex, currentWeek);
      }
      return;
    }

    // Botón de Excel
    if (e.target.closest('.vehicle-excel-btn')) {
      const btn = e.target.closest('.vehicle-excel-btn');
      const assignmentId = btn.dataset.assignmentId;
      const plate = btn.dataset.vehiclePlate || '{{ __('Vehicle') }}';
      
      if (!assignmentId || assignmentId === 'new') {
        window.showToast('{{ __('Please save the vehicle assignment first') }}', 'warning', 3000);
        return;
      }
      
      console.log('Exporting to Excel for vehicle:', { assignmentId, plate });
      
      // Descargar Excel
      const excelUrl = '{{ route("customers.routes.export-excel", $customer->id) }}?assignment_id=' + assignmentId;
      window.location.href = excelUrl;
      
      window.showToast(`{{ __('Downloading Excel for') }} ${plate}...`, 'success', 2000);
      return;
    }

    // Botón de impresión del vehículo
    if (e.target.closest('.vehicle-print-btn')) {
      const btn = e.target.closest('.vehicle-print-btn');
      const assignmentId = btn.dataset.assignmentId;
      const plate = btn.dataset.vehiclePlate || '{{ __('Vehicle') }}';
      
      if (!assignmentId || assignmentId === 'new') {
        window.showToast('{{ __('Please save the vehicle assignment first') }}', 'warning', 3000);
        return;
      }
      
      console.log('Opening print sheet for vehicle:', { assignmentId, plate });
      
      // Abrir ventana de impresión
      const printUrl = '{{ route("customers.routes.print-sheet", $customer->id) }}?assignment_id=' + assignmentId;
      window.open(printUrl, '_blank', 'width=900,height=800');
      
      window.showToast(`{{ __('Opening route sheet for') }} ${plate}...`, 'info', 2000);
      return;
    }

    // Click en pedido (order-chip) para toggle active/inactive
    if (e.target.closest('.order-chip')) {
      const chip = e.target.closest('.order-chip');
      const orderAssignmentId = chip.dataset.orderAssignmentId;
      const orderId = chip.dataset.orderId;
      const currentActive = chip.dataset.active === '1';
      
      console.log('Toggle order:', orderId, 'Assignment ID:', orderAssignmentId, 'Current active:', currentActive);
      
      toggleOrderActive(orderAssignmentId, orderId, chip);
      return;
    }
    
    // Botón "×" para remover cliente del vehículo
    if (e.target.closest('.client-remove-btn')) {
      const btn = e.target.closest('.client-remove-btn');
      let clientName = btn?.dataset?.clientName || '';
      const assignmentId = btn?.dataset?.clientAssignmentId;
      if(!clientName){
        // Fallback: leer el texto del badge
        const badge = btn.closest('.badge');
        clientName = badge ? (badge.querySelector('span.flex-grow-1')?.textContent || '').trim() : '';
      }

      const modalEl = document.getElementById('confirmActionModal');
      document.getElementById('confirmActionMessage').textContent = `${"{{ __('Are you sure you want to remove') }}"} ${clientName} ${"{{ __('from this vehicle?') }}"}`;
      const modal = new bootstrap.Modal(modalEl);
      const confirmBtn = document.getElementById('confirmActionYes');
      
      // Limpiar listeners previos
      const newConfirmBtn = confirmBtn.cloneNode(true);
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
      
      newConfirmBtn.addEventListener('click', function() {
        console.log('Remove client from vehicle:', clientName, 'Assignment ID:', assignmentId);
        if (assignmentId === 'new') {
          btn.closest('.badge')?.remove();
          window.showToast(`${clientName} {{ __('removed from vehicle') }}`,'success');
        } else {
          removeClientFromVehicle(assignmentId, clientName);
        }
        modal.hide();
      });
      modal.show();
      return;
    }
  });

  // Función para copiar RUTA COMPLETA de semana anterior
  function copyEntireRouteFromPreviousWeek(routeId, dayIndex, currentWeek) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    window.showToast('{{ __('Copying entire route from previous week') }}...', 'info', 2000);
    
    fetch('{{ route("customers.routes.copy-entire-route-previous-week", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        route_name_id: routeId,
        day_index: parseInt(dayIndex),
        week: currentWeek
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        window.showToast(data.message, 'success', 3000);
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        window.showToast('{{ __('Error') }}: ' + (data.message || 'Unknown error'), 'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error copying entire route:', error);
      window.showToast('{{ __('Error copying route') }}: ' + error.message, 'danger', 3000);
    });
  }

  // Función para copiar de semana anterior (vehículo individual)
  function copyFromPreviousWeek(routeId, vehicleId, dayIndex, currentWeek) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    window.showToast('{{ __('Copying from previous week') }}...', 'info', 2000);
    
    fetch('{{ route("customers.routes.copy-previous-week", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        route_name_id: routeId,
        fleet_vehicle_id: vehicleId,
        day_index: parseInt(dayIndex),
        week: currentWeek
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        window.showToast(data.message, 'success', 3000);
        // Recargar página para mostrar los cambios
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        window.showToast('{{ __('Error') }}: ' + (data.message || 'Unknown error'), 'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error copying from previous week:', error);
      window.showToast('{{ __('Error copying from previous week') }}: ' + error.message, 'danger', 3000);
    });
  }

  // Función para actualizar contador de pedidos activos
  function updateOrderCounter(chipElement) {
    const clientItem = chipElement.closest('.vehicle-client-item');
    if (!clientItem) return;
    
    const orderChips = clientItem.querySelectorAll('.order-chip');
    const activeChips = Array.from(orderChips).filter(chip => chip.dataset.active === '1');
    const totalChips = orderChips.length;
    const activeCount = activeChips.length;
    
    const counterBadge = clientItem.querySelector('.badge.bg-success, .badge.bg-warning, .badge.bg-secondary');
    if (counterBadge && totalChips > 0) {
      counterBadge.textContent = `${activeCount}/${totalChips}`;
      counterBadge.title = `{{ __('Active orders') }}: ${activeCount} / ${totalChips}`;
      
      // Cambiar color según estado
      counterBadge.classList.remove('bg-success', 'bg-warning', 'bg-secondary');
      if (activeCount === totalChips) {
        counterBadge.classList.add('bg-success');
      } else if (activeCount > 0) {
        counterBadge.classList.add('bg-warning');
      } else {
        counterBadge.classList.add('bg-secondary');
      }
    }
  }

  // Función para toggle active/inactive de un pedido
  function toggleOrderActive(orderAssignmentId, orderId, chipElement) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('{{ route("customers.routes.toggle-order-active", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        order_assignment_id: orderAssignmentId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Actualizar estado visual del chip
        const newActive = data.active;
        chipElement.dataset.active = newActive ? '1' : '0';
        
        if (newActive) {
          chipElement.classList.remove('order-inactive');
          chipElement.classList.add('order-active');
          chipElement.style.opacity = '1';
          chipElement.style.textDecoration = 'none';
          chipElement.title = '{{ __('Click to deactivate') }}';
        } else {
          chipElement.classList.remove('order-active');
          chipElement.classList.add('order-inactive');
          chipElement.style.opacity = '0.5';
          chipElement.style.textDecoration = 'line-through';
          chipElement.title = '{{ __('Click to activate') }}';
        }
        
        // Actualizar contador de pedidos activos
        updateOrderCounter(chipElement);
        
        window.showToast(data.message, 'success', 2000);
      } else {
        window.showToast('{{ __('Error toggling order status') }}: ' + (data.message || 'Unknown error'), 'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error toggling order:', error);
      window.showToast('{{ __('Error toggling order status') }}: ' + error.message, 'danger', 3000);
    });
  }

  // Función para remover cliente del vehículo
  function removeClientFromVehicle(assignmentId, clientName) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('{{ route("customers.routes.remove-client-vehicle", $customer->id) }}', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        assignment_id: assignmentId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remover el badge del cliente
        const clientBadge = document.querySelector(`[data-client-assignment-id="${assignmentId}"]`)?.closest('.badge');
        if (clientBadge) {
          clientBadge.remove();
        }

        // Reponer cliente en la lista disponible del mismo día/ruta
        try {
          const routeId = data.route_name_id || data.route_id;
          const dayIndex = data.day_index;
          const clientId = data.client_id || data.customer_client_id;
          
          console.log('Trying to re-add client:', { clientName, clientId, routeId, dayIndex });
          
          // Buscar la celda del día/ruta correspondiente (múltiples estrategias)
          let dayCell = null;
          let clientsList = null;
          
          // Estrategia 1: Buscar por botón + con data-attrs
          const plusBtn = document.querySelector(`[data-route-id="${routeId}"][data-day-index="${dayIndex}"]`);
          if (plusBtn) {
            dayCell = plusBtn.closest('td');
            clientsList = dayCell ? dayCell.querySelector('.clients-list') : null;
          }
          
          // Estrategia 2: Si no funciona, buscar por estructura de tabla
          if (!clientsList) {
            const allCells = document.querySelectorAll('td');
            for (const cell of allCells) {
              const btn = cell.querySelector(`[data-route-id="${routeId}"][data-day-index="${dayIndex}"]`);
              if (btn) {
                dayCell = cell;
                clientsList = cell.querySelector('.clients-list');
                break;
              }
            }
          }
          
          // Estrategia 3: Si aún no funciona, buscar dentro de .clients-wrapper
          if (!clientsList && dayCell) {
            const wrapper = dayCell.querySelector('.clients-wrapper');
            if (wrapper) {
              clientsList = wrapper.querySelector('.clients-list') || wrapper.querySelector('ul');
            }
          }
          
          // Estrategia 4: Crear la lista si no existe pero tenemos la celda
          if (!clientsList && dayCell) {
            console.log('Creating clients list structure for day cell');
            // Buscar si existe el wrapper de clientes
            let wrapper = dayCell.querySelector('.clients-wrapper');
            if (!wrapper) {
              // Crear la estructura completa si no existe
              const clientsSection = document.createElement('div');
              clientsSection.innerHTML = `
                <div class="clients-wrapper">
                  <ul class="list-unstyled mb-2 small clients-list"></ul>
                </div>
              `;
              // Insertar después del header pero antes de los vehículos asignados
              const header = dayCell.querySelector('.d-flex.justify-content-between');
              if (header) {
                header.insertAdjacentElement('afterend', clientsSection);
                clientsList = clientsSection.querySelector('.clients-list');
              }
            } else {
              // El wrapper existe, crear solo la lista
              clientsList = document.createElement('ul');
              clientsList.className = 'list-unstyled mb-2 small clients-list';
              wrapper.appendChild(clientsList);
            }
          }
          
          console.log('Found elements:', { 
            plusBtn: !!plusBtn, 
            dayCell: !!dayCell, 
            clientsList: !!clientsList, 
            routeId, 
            dayIndex,
            dayCellHTML: dayCell ? dayCell.innerHTML.substring(0, 200) + '...' : 'null'
          });
          
          if (clientsList && clientId && clientName) {
            // Verificar que el cliente no esté ya en la lista
            const existingClient = clientsList.querySelector(`[data-client-id="${clientId}"]`);
            if (!existingClient) {
              const li = document.createElement('li');
              li.className = 'mb-1';
              li.innerHTML = `
                <span class="badge bg-light text-dark border draggable-client"
                      draggable="true"
                      data-client-id="${clientId}"
                      data-client-name="${clientName}"
                      data-route-id="${routeId}"
                      data-day-index="${dayIndex}"
                      style="cursor: grab;">
                  ${clientName}
                </span>`;
              clientsList.appendChild(li);
              console.log('Client re-added to available list successfully');
            } else {
              console.log('Client already exists in available list');
            }
          } else {
            console.warn('Missing data to re-add client:', { clientsList: !!clientsList, clientId, clientName });
          }
        } catch (e) { 
          console.error('Could not re-add client to available list:', e); 
        }

        window.showToast(`${clientName || '{{ __('Client') }}'} {{ __('removed from vehicle successfully') }}`,'success');
        
        // Programar refresh automático después de eliminar cliente
        window.scheduleRefresh(2000);
      } else {
        window.showToast('{{ __('Error removing client from vehicle') }}: ' + (data.message || 'Unknown error'),'danger', 3000);
      }
    })
    .catch(error => {
      console.error('Error removing client:', error);
      window.showToast('{{ __('Error removing client from vehicle') }}: ' + error.message,'danger', 3000);
    });
  }

  // Manejar eliminación de vehículos
  document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-vehicle-btn')) {
      const btn = e.target.closest('.remove-vehicle-btn');
      const assignmentId = btn.dataset.assignmentId;
      
      // Confirm remove vehicle via modal
      const modalEl = document.getElementById('confirmActionModal');
      document.getElementById('confirmActionMessage').textContent = `{{ __('Are you sure you want to remove this vehicle assignment?') }}`;
      const modal = new bootstrap.Modal(modalEl);
      const confirmBtn = document.getElementById('confirmActionYes');
      
      // Limpiar listeners previos
      const newConfirmBtn = confirmBtn.cloneNode(true);
      confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
      
      newConfirmBtn.addEventListener('click', function() {
        performRemoveVehicle(btn);
        modal.hide();
      });
      modal.show();
      return;
    }
  });

  function performRemoveVehicle(btn){
      const assignmentId = btn.dataset.assignmentId;
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      fetch('{{ route("customers.routes.remove-vehicle", $customer->id) }}', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          assignment_id: assignmentId
        })
      })
      .then(response => {
        console.log('Remove response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('Remove response data:', data);
        if (data.success) {
          // Eliminar el badge dinámicamente
          const badgeElement = btn.closest('.badge');
          if (badgeElement) {
            badgeElement.remove();
          }
          
          // Si no quedan más vehículos asignados, eliminar toda la sección
          const assignedSection = btn.closest('.mt-2');
          if (assignedSection && assignedSection.querySelectorAll('.badge').length === 0) {
            assignedSection.remove();
          }
          
          window.showToast('{{ __('Vehicle removed successfully') }}','success');
          
          // Programar refresh automático después de eliminar vehículo
          window.scheduleRefresh(2000);
        } else {
          window.showToast('{{ __('Error removing vehicle') }}: ' + (data.message || 'Unknown error'),'danger', 3000);
        }
      })
      .catch(error => {
        console.error('Remove error details:', error);
        window.showToast('{{ __('Error removing vehicle') }}: ' + error.message,'danger', 3000);
      });
  }

  // Funcionalidad de filtros
  const clientSearch = document.getElementById('clientSearch');
  const orderSearch = document.getElementById('orderSearch');
  const vehicleTypeFilter = document.getElementById('vehicleTypeFilter');
  const routeStatusFilter = document.getElementById('routeStatusFilter');

  function applyFilters() {
    const searchTerm = clientSearch.value.toLowerCase();
    const orderSearchTerm = orderSearch ? orderSearch.value.toLowerCase() : '';
    const vehicleType = vehicleTypeFilter.value;
    const routeStatus = routeStatusFilter.value;

    // Limpiar highlights previos
    document.querySelectorAll('.order-chip').forEach(chip => {
      chip.style.outline = '';
      chip.style.backgroundColor = '';
    });

    // Búsqueda de pedidos
    if (orderSearchTerm) {
      let foundOrders = 0;
      document.querySelectorAll('.order-chip').forEach(chip => {
        const orderId = chip.dataset.orderId || chip.textContent.trim().split(' ')[0];
        if (orderId.toLowerCase().includes(orderSearchTerm)) {
          chip.style.outline = '3px solid #0d6efd';
          chip.style.backgroundColor = '#e7f1ff';
          chip.scrollIntoView({ behavior: 'smooth', block: 'center' });
          foundOrders++;
        }
      });
      
      if (foundOrders === 0) {
        window.showToast('{{ __('Order not found') }}: ' + orderSearchTerm, 'warning', 2000);
      } else {
        window.showToast(`{{ __('Found') }} ${foundOrders} {{ __('order(s)') }}`, 'success', 2000);
      }
      return; // Si busca pedido, no aplicar otros filtros
    }

    // Filtrar clientes
    document.querySelectorAll('.draggable-client, [data-client-name]').forEach(client => {
      const clientName = client.dataset.clientName || client.textContent;
      const matchesSearch = clientName.toLowerCase().includes(searchTerm);
      
      if (matchesSearch) {
        client.style.display = '';
        // Highlight del término buscado
        if (searchTerm) {
          client.style.backgroundColor = '#fff3cd';
        } else {
          client.style.backgroundColor = '';
        }
      } else {
        client.style.display = 'none';
      }
    });

    // Filtrar vehículos por tipo
    document.querySelectorAll('.vehicle-container').forEach(vehicle => {
      const vType = vehicle.dataset.vehicleType || 'default';
      const matchesType = !vehicleType || vType.includes(vehicleType);
      
      if (matchesType) {
        vehicle.style.display = '';
      } else {
        vehicle.style.display = 'none';
      }
    });

    // Filtrar rutas por estado
    document.querySelectorAll('tbody tr').forEach(row => {
      const hasVehicles = row.querySelectorAll('.vehicle-container').length > 0;
      let showRow = true;

      if (routeStatus === 'with-vehicles' && !hasVehicles) {
        showRow = false;
      } else if (routeStatus === 'without-vehicles' && hasVehicles) {
        showRow = false;
      }

      row.style.display = showRow ? '' : 'none';
    });

    // Actualizar estadísticas
    updateStats();
  }

  // updateStats function moved to first script to avoid scope issues

  // Event listeners para filtros (solo si los elementos existen)
  if (clientSearch) {
    clientSearch.addEventListener('input', applyFilters);
  }
  if (orderSearch) {
    orderSearch.addEventListener('input', applyFilters);
  }
  if (vehicleTypeFilter) {
    vehicleTypeFilter.addEventListener('change', applyFilters);
  }
  if (routeStatusFilter) {
    routeStatusFilter.addEventListener('change', applyFilters);
  }

  // Función global para limpiar filtros
  window.clearFilters = function() {
    if (clientSearch) clientSearch.value = '';
    if (orderSearch) orderSearch.value = '';
    if (vehicleTypeFilter) vehicleTypeFilter.value = '';
    if (routeStatusFilter) routeStatusFilter.value = '';
    applyFilters();
  };

  // Mejorar la creación dinámica de vehículos con iconos
  function getVehicleIcon(type) {
    const icons = {
      'furgoneta': '🚐',
      'camion': '🚛', 
      'moto': '🏍️',
      'default': '🚚'
    };
    return icons[type] || icons.default;
  }

  // Función simple de actualización de estadísticas
  function updateStats() {
    try {
      const visibleVehicles = document.querySelectorAll('.vehicle-container:not([style*="display: none"])').length;
      const visibleClients = document.querySelectorAll('[data-client-name]:not([style*="display: none"])').length;
      
      // Solo actualizar si los elementos de estadísticas existen
      const vehicleStatEl = document.querySelector('.stat-card .number');
      const clientStatEl = document.querySelector('.stat-card:nth-child(2) .number');
      
      if (vehicleStatEl) {
        vehicleStatEl.textContent = visibleVehicles;
      }
      if (clientStatEl) {
        clientStatEl.textContent = visibleClients;
      }
      
      console.log('Stats updated:', { vehicles: visibleVehicles, clients: visibleClients });
    } catch (error) {
      console.warn('Error updating stats:', error);
    }
  }

  // ===== DRAG & DROP DE PEDIDOS =====
  let draggedOrder = null;
  let draggedOrderContainer = null;

  document.addEventListener('dragstart', function(e) {
    const orderChip = e.target.closest('.order-chip');
    if (orderChip && orderChip.classList.contains('order-chip')) {
      // Solo permitir drag si es el handle
      if (!e.target.closest('.drag-handle') && !orderChip.draggable) {
        e.preventDefault();
        return;
      }
      
      draggedOrder = orderChip;
      draggedOrderContainer = orderChip.closest('.client-orders');
      orderChip.style.opacity = '0.4';
      console.log('Dragging order:', orderChip.dataset.orderId);
    }
  });

  document.addEventListener('dragend', function(e) {
    const orderChip = e.target.closest('.order-chip');
    if (orderChip) {
      orderChip.style.opacity = '';
      
      // Guardar nuevo orden
      if (draggedOrderContainer) {
        saveOrdersOrder(draggedOrderContainer);
      }
      
      draggedOrder = null;
      draggedOrderContainer = null;
    }
  });

  document.addEventListener('dragover', function(e) {
    if (!draggedOrder) return;
    
    const clientOrders = e.target.closest('.client-orders');
    if (!clientOrders || clientOrders !== draggedOrderContainer) return;
    
    e.preventDefault();
    
    const afterElement = getDragAfterElementOrder(clientOrders, e.clientY);
    if (afterElement == null) {
      clientOrders.appendChild(draggedOrder);
    } else {
      clientOrders.insertBefore(draggedOrder, afterElement);
    }
  });

  function getDragAfterElementOrder(container, y) {
    const draggableElements = [...container.querySelectorAll('.order-chip:not([style*="opacity: 0.4"])')];
    
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      
      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  function saveOrdersOrder(clientOrdersContainer) {
    const clientItem = clientOrdersContainer.closest('.vehicle-client-item');
    if (!clientItem) return;
    
    const clientAssignmentId = clientItem.dataset.clientAssignmentId;
    const orderChips = clientOrdersContainer.querySelectorAll('.order-chip');
    const orderedIds = Array.from(orderChips).map(chip => parseInt(chip.dataset.orderAssignmentId));
    
    console.log('Saving new order:', orderedIds);
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch('{{ route("customers.routes.reorder-orders", $customer->id) }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        client_assignment_id: clientAssignmentId,
        ordered_ids: orderedIds
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log('Orders reordered successfully');
        // No mostrar toast para no molestar al usuario
      } else {
        window.showToast('{{ __('Error reordering') }}: ' + (data.message || 'Unknown error'), 'warning', 2000);
      }
    })
    .catch(error => {
      console.error('Error reordering orders:', error);
    });
  }

  // Actualizar estadísticas cada 30 segundos
  setInterval(updateStats, 30000);
});
</script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  let draggingItem = null;
  let originCtx = null; // { vehicleId, routeId, dayIndex }

  function isNumericId(v){ return /^\d+$/.test(String(v || '')); }

  // Start dragging a client inside a vehicle list
  document.addEventListener('dragstart', function(e){
    const item = e.target.closest('.vehicle-client-item');
    if(!item) return;
    draggingItem = item;
    item.classList.add('dragging');
    const originContainer = item.closest('.vehicle-container');
    originCtx = originContainer ? {
      vehicleId: originContainer.dataset.vehicleId,
      routeId: originContainer.dataset.routeId,
      dayIndex: originContainer.dataset.dayIndex
    } : null;
  });

  // End drag
  document.addEventListener('dragend', function(e){
    const item = e.target.closest('.vehicle-client-item');
    if(!item) return;
    item.classList.remove('dragging');
    const list = item.closest('.vehicle-clients-list');
    if (!list) return;

    // Persist order
    const vehicleContainer = list.closest('.vehicle-container');
    const vehicleId = vehicleContainer?.dataset.vehicleId;
    const routeId = vehicleContainer?.dataset.routeId;
    const dayIndex = vehicleContainer?.dataset.dayIndex;
    const currentWeek = new URLSearchParams(window.location.search).get('week') || '{{ now()->startOfWeek()->format("Y-m-d") }}';
    const ids = Array.from(list.querySelectorAll('.vehicle-client-item'))
      .map(el => el.getAttribute('data-client-assignment-id'))
      .filter(isNumericId);

    if(ids.length === 0) return; // nothing to persist

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const doReorder = () => fetch('{{ route("customers.routes.reorder-clients", $customer->id) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
          route_name_id: routeId,
          fleet_vehicle_id: vehicleId,
          day_index: parseInt(dayIndex),
          week: currentWeek,
          ordered_assignment_ids: ids
        })
      }).then(r => r.json()).then(data => {
        if(!data.success){
          console.warn('Reorder not saved:', data);
        }
      }).catch(err => console.error('Reorder error:', err));

    // If moved to a different vehicle/day/route, first move the assignment to new container
    const movedAcross = originCtx && (originCtx.vehicleId !== vehicleId || originCtx.routeId !== routeId || String(originCtx.dayIndex) !== String(dayIndex));
    if (movedAcross) {
      const assignmentId = item.getAttribute('data-client-assignment-id');
      fetch('{{ route("customers.routes.move-client", $customer->id) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
          assignment_id: parseInt(assignmentId),
          route_name_id: routeId,
          fleet_vehicle_id: vehicleId,
          day_index: parseInt(dayIndex),
          week: currentWeek
        })
      }).then(r => r.json()).then(data => {
        if(!data.success){
          console.warn('Move not saved:', data);
          return;
        }
        // After moving, reorder to persist precise order
        return doReorder().then(() => {
          // Programar refresh automático después de mover entre vehículos
          window.scheduleRefresh(1500);
        });
      }).catch(err => console.error('Move error:', err));
    } else {
      // Same container: only reorder
      doReorder().then(() => {
        // Programar refresh automático después de reordenar
        window.scheduleRefresh(1500);
      });
    }
  });

  // Allow sorting by hovering over the list
  document.addEventListener('dragover', function(e){
    const list = e.target.closest('.vehicle-clients-list');
    if (!list || !draggingItem) return;
    e.preventDefault();
    list.classList.add('drag-over');
    const afterElement = getDragAfterElement(list, e.clientY);
    if (afterElement == null) {
      list.appendChild(draggingItem);
    } else {
      list.insertBefore(draggingItem, afterElement);
    }
  });

  document.addEventListener('dragleave', function(e){
    const list = e.target.closest('.vehicle-clients-list');
    if (!list) return;
    list.classList.remove('drag-over');
  });

  function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.vehicle-client-item:not(.dragging)')];
    return draggableElements.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }
});
</script>
@endpush

@push('scripts')
<script>
// Local search per day-cell: filters only the clients list inside the same cell
document.addEventListener('input', function(e){
  const search = e.target.closest('.clients-search');
  if (!search) return;
  const q = (search.value || '').toLowerCase();
  const cell = search.closest('td');
  if (!cell) return;
  cell.querySelectorAll('.clients-list .draggable-client').forEach(span => {
    const name = (span.getAttribute('data-client-name') || span.textContent || '').toLowerCase();
    const li = span.closest('li');
    if (!li) return;
    li.style.display = name.includes(q) ? '' : 'none';
  });
});
</script>
@endpush
