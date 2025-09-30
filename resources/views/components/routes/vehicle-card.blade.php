@props(['assignment', 'dayDate', 'clientVehicleAssignments', 'dayNames'])

@php
  $vehicleClients = collect($clientVehicleAssignments ?? [])->filter(function($clientAssignment) use ($assignment, $dayDate) {
      return $clientAssignment->fleet_vehicle_id == $assignment->fleetVehicle->id &&
             $clientAssignment->assignment_date->format('Y-m-d') == $dayDate;
  });
  $clientCount = $vehicleClients->count();
  $vehicleStatus = $clientCount > 3 ? 'vehicle-full' : ($clientCount > 0 ? 'vehicle-partial' : 'vehicle-empty');
  $vehicleType = strtolower($assignment->fleetVehicle->vehicle_type ?? 'default');
  $vehicleIcon = 'vehicle-icon-' . str_replace(' ', '-', $vehicleType);
  $dayIndex = \Carbon\Carbon::parse($dayDate)->dayOfWeek === 0 ? 6 : \Carbon\Carbon::parse($dayDate)->dayOfWeek - 1;
@endphp

@once
  @push('styles')
    <style>
      .vehicle-client-item { position: relative; }
      .vehicle-client-item .client-orders {
        opacity: 1;
        max-height: none;
        overflow: visible;
      }
      .vehicle-client-item .order-chip {
        display: inline-flex;
        align-items: center;
        background: rgba(13,110,253,0.12); /* bootstrap primary soft */
        color: #0d6efd;
        border: 1px solid rgba(13,110,253,0.25);
        border-radius: 6px;
        padding: 2px 6px;
        font-size: 11px;
        line-height: 1.1;
      }
      .vehicle-client-item .order-chip i { font-size: 12px; opacity: 0.7; }
    </style>
  @endpush
@endonce

<div class="vehicle-container mb-2 p-2 border rounded bg-primary text-white vehicle-drop-zone custom-tooltip {{ $vehicleStatus }}"
     data-vehicle-id="{{ $assignment->fleetVehicle->id }}"
     data-assignment-id="{{ $assignment->id }}"
     data-route-id="{{ $assignment->route_name_id }}"
     data-day-index="{{ $assignment->day_of_week }}"
     data-vehicle-type="{{ $vehicleType }}"
     style="min-height: 60px;">

  {{-- Tooltip --}}
  <div class="tooltip-content">
    <strong>{{ $assignment->fleetVehicle->plate }}</strong><br>
    {{ __('Type') }}: {{ $assignment->fleetVehicle->vehicle_type ?? __('Standard') }}<br>
    {{ __('Clients') }}: {{ $clientCount }}<br>
    {{ __('Route') }}: {{ $assignment->routeName->name ?? '' }}<br>
    {{ __('Day') }}: {{ $dayNames[$dayIndex] ?? '' }}
  </div>

  {{-- Card Header --}}
  <div class="d-flex justify-content-between align-items-start mb-1">
    <div class="flex-grow-1">
      <span class="fw-bold">
        <span class="{{ $vehicleIcon }}"></span>
        {{ $assignment->fleetVehicle->plate }}
        @if($clientCount > 0)
          <span class="badge bg-light text-dark ms-1" style="font-size: 0.7em;">{{ $clientCount }}</span>
        @endif
      </span>
      @if($assignment->driver)
        <div style="font-size: 0.75em; opacity: 0.9; margin-top: 2px;">
          <i class="ti ti-user"></i> {{ $assignment->driver->name }}
        </div>
      @else
        <div style="font-size: 0.75em; opacity: 0.7; margin-top: 2px;">
          <i class="ti ti-user"></i> {{ __('No driver') }}
        </div>
      @endif
    </div>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-sm p-0 text-white vehicle-assign-driver-btn"
              type="button"
              style="background: rgba(255,255,255,0.2); border: none; font-size: 13px; padding: 4px 6px !important; border-radius: 4px;"
              data-assignment-id="{{ $assignment->id }}"
              data-current-driver-id="{{ $assignment->user_id ?? '' }}"
              data-vehicle-plate="{{ $assignment->fleetVehicle->plate }}"
              title="{{ __('Assign/Change driver') }}">
        <i class="fas fa-user-edit"></i>
      </button>
      <button class="btn btn-sm p-0 text-white vehicle-copy-prev-week-btn"
              type="button"
              style="background: none; border: none; font-size: 12px;"
              data-assignment-id="{{ $assignment->id }}"
              data-route-id="{{ $assignment->route_name_id }}"
              data-vehicle-id="{{ $assignment->fleet_vehicle_id }}"
              data-day-index="{{ $assignment->assignment_date->dayOfWeekIso - 1 }}"
              title="{{ __('Copy from last week') }}">
        <i class="fas fa-copy"></i>
      </button>
      <button class="btn btn-sm p-0 text-white vehicle-print-btn"
              type="button"
              style="background: none; border: none; font-size: 12px;"
              data-assignment-id="{{ $assignment->id }}"
              data-vehicle-plate="{{ $assignment->fleetVehicle->plate }}"
              title="{{ __('Print route sheet') }}">
        <i class="fas fa-print"></i>
      </button>
      <button class="btn btn-sm p-0 text-white vehicle-excel-btn"
              type="button"
              style="background: none; border: none; font-size: 12px;"
              data-assignment-id="{{ $assignment->id }}"
              data-vehicle-plate="{{ $assignment->fleetVehicle->plate }}"
              title="{{ __('Export to Excel') }}">
        <i class="fas fa-file-excel"></i>
      </button>
      <button class="btn btn-sm p-0 text-white remove-vehicle-btn"
              style="background: none; border: none; font-size: 12px;"
              data-assignment-id="{{ $assignment->id }}"
              title="{{ __('Remove vehicle') }}">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>

  {{-- Client List --}}
  <div class="vehicle-clients-list" style="min-height: 30px;">
    @if($vehicleClients->count() > 0)
      @foreach($vehicleClients as $clientAssignment)
        @php
          $orderAssignments = $clientAssignment->orderAssignments ?? collect();
          $activeCount = $orderAssignments->where('active', true)->count();
          $totalCount = $orderAssignments->count();
        @endphp
        <div class="badge bg-light text-dark me-1 mb-1 d-flex flex-column vehicle-client-item"
             style="min-width: 140px; cursor: grab; padding: 8px;"
             draggable="true"
             data-client-name="{{ $clientAssignment->customerClient->name }}"
             data-client-assignment-id="{{ $clientAssignment->id }}"
             data-client-id="{{ $clientAssignment->customer_client_id }}">
          <div class="d-flex align-items-center justify-content-between w-100">
            <span class="drag-handle me-2 text-muted" title="{{ __('Drag to reorder') }}">⋮⋮</span>
            <span class="flex-grow-1">{{ $clientAssignment->customerClient->name }}</span>
            @if($totalCount > 0)
              <span class="badge {{ $activeCount === $totalCount ? 'bg-success' : ($activeCount > 0 ? 'bg-warning' : 'bg-secondary') }} ms-1" 
                    style="font-size: 0.65em;"
                    title="{{ __('Active orders') }}: {{ $activeCount }} / {{ $totalCount }}">
                {{ $activeCount }}/{{ $totalCount }}
              </span>
            @endif
            <button class="btn btn-sm p-0 text-danger client-remove-btn ms-1" style="background: none; border: none; font-size: 10px; line-height: 1;" data-client-assignment-id="{{ $clientAssignment->id }}" title="{{ __('Remove client from vehicle') }}">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="client-orders d-flex flex-wrap gap-1 mt-2">
            @if($orderAssignments->count() > 0)
              @foreach($orderAssignments as $orderAssignment)
                @php
                  $order = $orderAssignment->originalOrder;
                  $isActive = $orderAssignment->active;
                @endphp
                <span class="order-chip {{ $isActive ? 'order-active' : 'order-inactive' }}" 
                      draggable="true"
                      data-order-assignment-id="{{ $orderAssignment->id }}"
                      data-order-id="{{ $order->order_id }}"
                      data-active="{{ $isActive ? '1' : '0' }}"
                      data-sort-order="{{ $orderAssignment->sort_order }}"
                      style="cursor: move; {{ !$isActive ? 'opacity: 0.5; text-decoration: line-through;' : '' }}"
                      title="{{ __('Drag to reorder') }} | {{ $isActive ? __('Click to deactivate') : __('Click to activate') }}">
                  <span class="drag-handle" style="cursor: grab; margin-right: 4px;">⋮</span>
                  {{ $order->order_id }}
                  @if($order->delivery_date)
                    <small class="ms-1 text-muted">{{ $order->delivery_date->format('d/m') }}</small>
                  @elseif($order->estimated_delivery_date)
                    <small class="ms-1 text-muted">~{{ $order->estimated_delivery_date->format('d/m') }}</small>
                  @endif
                  <i class="ti ti-x ms-1" style="font-size: 10px;"></i>
                </span>
              @endforeach
            @else
              <span class="text-muted small">{{ __('No pending orders') }}</span>
            @endif
          </div>
        </div>
      @endforeach
    @else
      <small class="opacity-75">{{ __('Drop clients here') }}</small>
    @endif
  </div>
</div>
