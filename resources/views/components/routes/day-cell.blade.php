@props(['route', 'dayIndex', 'days', 'dayNames', 'clientsByRoute', 'routeAssignments', 'clientVehicleAssignments', 'fleetVehicles'])

@php
  $dayMask = 1 << $dayIndex;
  $isRouteActive = ((int)($route->days_mask ?? 0)) & $dayMask;
  $dayDate = $days[$dayIndex]->format('Y-m-d');
@endphp

<td class="align-top position-relative">
  @if($isRouteActive)
    {{-- Botones de acción de ruta --}}
    <div class="d-flex justify-content-end align-items-center mb-2 gap-1">
      <button class="btn btn-sm btn-outline-secondary route-copy-prev-week-btn"
              data-route-id="{{ $route->id }}"
              data-day-index="{{ $dayIndex }}"
              title="{{ __('Copy entire route from last week') }}">
        <i class="fas fa-copy"></i>
      </button>
      <button class="btn btn-sm btn-outline-info route-print-btn"
              data-route-id="{{ $route->id }}"
              data-route-name="{{ $route->name }}"
              data-day-index="{{ $dayIndex }}"
              data-day-date="{{ $dayDate }}"
              title="{{ __('Print entire route') }}">
        <i class="fas fa-print"></i>
      </button>
      <button class="btn btn-sm btn-outline-success route-excel-btn"
              data-route-id="{{ $route->id }}"
              data-route-name="{{ $route->name }}"
              data-day-index="{{ $dayIndex }}"
              data-day-date="{{ $dayDate }}"
              title="{{ __('Export route to Excel') }}">
        <i class="fas fa-file-excel"></i>
      </button>
      <button class="btn btn-outline-primary btn-add-vehicle"
              data-bs-toggle="modal"
              data-bs-target="#vehicleModal"
              data-route-id="{{ $route->id }}"
              data-route-name="{{ $route->name }}"
              data-day-index="{{ $dayIndex }}"
              data-day-name="{{ $dayNames[$dayIndex] }}"
              title="{{ __('Add vehicle') }}"
              aria-label="{{ __('Add vehicle') }}">
        <i class="ti ti-truck"></i>
        <i class="ti ti-plus plus-badge"></i>
      </button>
    </div>

    {{-- Búsqueda de clientes --}}
    <div class="mb-2">
      <div class="d-flex align-items-center gap-2">
        <small class="text-muted">{{ __('Clients') }}:</small>
        <input type="search" class="form-control form-control-sm clients-search" placeholder="{{ __('Search client...') }}">
      </div>
    </div>

    {{-- Lista de clientes disponibles --}}
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
      <div class="clients-wrapper">
      <ul class="list-unstyled mb-2 small clients-list">
        @foreach($availableClients as $c)
          @php
            $pendingOrders = ($c->pendingDeliveries ?? collect());
            $pendingOrdersCount = $pendingOrders->count();
            // Verificar si tiene pedidos no finalizados (problemáticos)
            $hasUnfinishedOrders = $pendingOrders->filter(function($order) {
                return is_null($order->finished_at) && !is_null($order->delivery_date);
            })->count() > 0;
            
            // Determinar color del badge según estado
            $badgeClass = 'bg-light text-dark border';
            $badgeCountClass = 'bg-primary';
            if ($hasUnfinishedOrders) {
                $badgeClass = 'bg-danger text-white border-danger';
                $badgeCountClass = 'bg-white text-danger';
            }
          @endphp
          <li class="mb-1">
            <span class="badge {{ $badgeClass }} draggable-client"
                  draggable="true"
                  data-client-id="{{ $c->id }}"
                  data-client-name="{{ $c->name }}"
                  data-route-id="{{ $route->id }}"
                  data-day-index="{{ $dayIndex }}"
                  style="cursor: grab;"
                  title="{{ $hasUnfinishedOrders ? __('Has orders pending completion') : '' }}">
              {{ $c->name }}
              @if($pendingOrdersCount > 0)
                <span class="badge {{ $badgeCountClass }} ms-1" style="font-size: 0.7em;">{{ $pendingOrdersCount }}</span>
              @endif
            </span>
          </li>
        @endforeach
      </ul>
      </div>
    @else
      <div class="text-muted small mb-2">{{ __('No available clients') }}</div>
    @endif

    {{-- Vehículos asignados --}}
    @php
      $assignedVehicles = collect($routeAssignments ?? [])->filter(function($assignment) use ($route, $dayDate) {
          return $assignment->route_name_id == $route->id &&
                 $assignment->assignment_date->format('Y-m-d') == $dayDate;
      });
      $defaultVehicles = collect($fleetVehicles ?? [])->where('default_route_name_id', $route->id);
    @endphp

    @if($assignedVehicles->count() > 0)
      <div class="mt-2 assigned-vehicles">
        <small class="text-muted">{{ __('Assigned') }}:</small>
        @foreach($assignedVehicles as $assignment)
          <x-routes.vehicle-card :assignment="$assignment" :dayDate="$dayDate" :clientVehicleAssignments="$clientVehicleAssignments" :dayNames="$dayNames" />
        @endforeach
      </div>
    @elseif($defaultVehicles->count() > 0)
      <div class="mt-2 default-vehicles">
        <small class="text-muted">{{ __('Default') }}:</small>
        @foreach($defaultVehicles as $v)
          <div class="badge bg-success text-white mt-1 d-block text-start">
            <i class="fas fa-truck me-1"></i>{{ $v->plate }}
            @if($v->vehicle_type)
              <small>({{ $v->vehicle_type }})</small>
            @endif
          </div>
        @endforeach
      </div>
    @endif

  @else
    <span class="text-muted small opacity-50">—</span>
  @endif
</td>
