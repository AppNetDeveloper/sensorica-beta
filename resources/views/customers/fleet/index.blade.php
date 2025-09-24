@extends('layouts.admin')

@section('title', __('Fleet') . ' - ' . $customer->name)
@section('page-title', __('Fleet'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Fleet') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Fleet') }}</h5>
    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
      <div class="btn-group btn-group-sm" role="group" aria-label="Crear">
        @can('fleet-create')
          <a href="{{ route('customers.fleet-vehicles.create', $customer->id) }}" class="btn btn-primary d-inline-flex align-items-center" title="{{ __('Add Vehicle') }}">
            <i class="ti ti-plus me-1"></i><span>{{ __('Create') }}</span>
          </a>
        @endcan
      </div>
      <div class="btn-group btn-group-sm ms-3" role="group" aria-label="Exportar">
        @can('fleet-view')
          <a href="{{ route('customers.fleet-vehicles.export', $customer->id) }}" class="btn btn-outline-secondary d-inline-flex align-items-center" title="{{ __('Export') }}">
            <i class="ti ti-download me-1"></i><span>{{ __('Export') }}</span>
          </a>
        @endcan
      </div>
      @can('fleet-create')
      <form action="{{ route('customers.fleet-vehicles.import', $customer->id) }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center ms-3" aria-label="Importar CSV">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" class="form-control form-control-sm w-auto me-2" required>
        <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center" title="{{ __('Import') }}">
          <i class="ti ti-upload me-1"></i><span>{{ __('Import') }}</span>
        </button>
      </form>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <form id="fleetFilters" class="row g-2 align-items-end mb-3">
      <div class="col-md-3">
        <label class="form-label">{{ __('ITV') }}</label>
        <select name="itv_filter" class="form-select">
          <option value="">{{ __('All') }}</option>
          <option value="expired">{{ __('Expired') }}</option>
          <option value="next30">{{ __('Next 30 days') }}</option>
          <option value="next60">{{ __('Next 60 days') }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Insurance') }}</label>
        <select name="insurance_filter" class="form-select">
          <option value="">{{ __('All') }}</option>
          <option value="expired">{{ __('Expired') }}</option>
          <option value="next30">{{ __('Next 30 days') }}</option>
          <option value="next60">{{ __('Next 60 days') }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Active Status') }}</label>
        <select name="active_filter" class="form-select">
          <option value="">{{ __('All') }}</option>
          <option value="active">{{ __('Only Active') }}</option>
          <option value="inactive">{{ __('Only Inactive') }}</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="button" id="btnApplyFilters" class="btn btn-outline-primary flex-fill">{{ __('Apply Filters') }}</button>
        <button type="button" id="btnClearFilters" class="btn btn-outline-secondary flex-fill">{{ __('Clear') }}</button>
      </div>
    </form>
    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="fleetTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Vehicle Type') }}</th>
            <th>{{ __('Plate') }}</th>
            <th>{{ __('Weight (kg)') }}</th>
            <th>{{ __('Length (cm)') }}</th>
            <th>{{ __('Width (cm)') }}</th>
            <th>{{ __('Height (cm)') }}</th>
            <th>{{ __('Capacity (kg)') }}</th>
            <th>{{ __('Fuel Type') }}</th>
            <th>{{ __('ITV Expiration') }}</th>
            <th>{{ __('Insurance Expiration') }}</th>
            <th>{{ __('Volume (m³)') }}</th>
            <th>{{ __('Notes') }}</th>
            <th>{{ __('Active') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script>
  $(function(){
    const $filters = document.getElementById('fleetFilters');
    const table = $('#fleetTable').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      ajax: {
        url: "{{ route('customers.fleet-vehicles.index', $customer->id) }}",
        data: function(d){
          d.itv_filter = $filters.itv_filter.value;
          d.insurance_filter = $filters.insurance_filter.value;
          d.active_filter = $filters.active_filter.value;
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'vehicle_type', name: 'vehicle_type' },
        { data: 'plate', name: 'plate' },
        { data: 'weight_kg', name: 'weight_kg' },
        { data: 'length_cm', name: 'length_cm' },
        { data: 'width_cm', name: 'width_cm' },
        { data: 'height_cm', name: 'height_cm' },
        { data: 'capacity_kg', name: 'capacity_kg' },
        { data: 'fuel_type', name: 'fuel_type' },
        { data: 'itv_expires_at', name: 'itv_expires_at' },
        { data: 'insurance_expires_at', name: 'insurance_expires_at' },
        { data: 'volume_m3', name: 'volume_m3', orderable: false, searchable: false },
        { data: 'notes', name: 'notes', orderable: false, searchable: true },
        { data: 'active', name: 'active', orderable: false, searchable: false },
        { data: 'actions', name: 'actions', orderable: false, searchable: false },
      ],
      order: [[0, 'desc']],
      language: { url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}' }
    });

    // Apply/Clear actions
    $('#btnApplyFilters').on('click', function(){ table.ajax.reload(); });
    $('#btnClearFilters').on('click', function(){
      $filters.itv_filter.value = '';
      $filters.insurance_filter.value = '';
      $filters.active_filter.value = '';
      table.ajax.reload();
    });
    // Also reload on change for quick filtering
    $('#fleetFilters select').on('change', function(){ table.ajax.reload(); });
  });
</script>
@endpush
