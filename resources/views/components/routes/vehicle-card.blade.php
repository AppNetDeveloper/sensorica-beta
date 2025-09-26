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
    <span class="fw-bold">
      <span class="{{ $vehicleIcon }}"></span>
      {{ $assignment->fleetVehicle->plate }}
      @if($clientCount > 0)
        <span class="badge bg-light text-dark ms-1" style="font-size: 0.7em;">{{ $clientCount }}</span>
      @endif
    </span>
    <div class="d-flex align-items-center gap-1">
      <button class="btn btn-sm p-0 text-white vehicle-print-btn"
              type="button"
              style="background: none; border: none; font-size: 12px;"
              data-assignment-id="{{ $assignment->id }}"
              data-vehicle-plate="{{ $assignment->fleetVehicle->plate }}"
              title="{{ __('Print route sheet') }}">
        <i class="fas fa-print"></i>
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
        <div class="badge bg-light text-dark me-1 mb-1 d-flex flex-column vehicle-client-item"
             style="min-width: 140px; cursor: grab; padding: 8px;"
             draggable="true"
             data-client-name="{{ $clientAssignment->customerClient->name }}"
             data-client-assignment-id="{{ $clientAssignment->id }}">
          <div class="d-flex align-items-center justify-content-between w-100">
            <span class="drag-handle me-2 text-muted" title="{{ __('Drag to reorder') }}">⋮⋮</span>
            <span class="flex-grow-1">{{ $clientAssignment->customerClient->name }}</span>
            <button class="btn btn-sm p-0 text-danger client-remove-btn" style="background: none; border: none; font-size: 10px; line-height: 1;" data-client-assignment-id="{{ $clientAssignment->id }}" title="{{ __('Remove client from vehicle') }}">
              <i class="fas fa-times"></i>
            </button>
          </div>
          <div class="client-orders d-flex flex-wrap gap-1 mt-2">
            <span class="order-chip">pedido-test1 <i class="ti ti-x ms-1"></i></span>
            <span class="order-chip">pedido-test2 <i class="ti ti-x ms-1"></i></span>
          </div>
        </div>
      @endforeach
    @else
      <small class="opacity-75">{{ __('Drop clients here') }}</small>
    @endif
  </div>
</div>
