@extends('layouts.admin')

@section('title', __('Nombres de Rutas') . ' - ' . $customer->name)
@section('page-title', __('Nombres de Rutas'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Nombres de Rutas') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Nombres de Rutas') }}</h5>
    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
      <div class="btn-group btn-group-sm" role="group" aria-label="Crear">
        @can('route-names-create')
          <a href="{{ route('customers.route-names.create', $customer->id) }}" class="btn btn-primary d-inline-flex align-items-center">
            <i class="ti ti-plus me-1"></i><span>{{ __('Create') }}</span>
          </a>
        @endcan
      </div>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="routeNamesTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Note') }}</th>
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
  const table = $('#routeNamesTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: "{{ route('customers.route-names.index', $customer->id) }}",
    },
    columns: [
      { data: 'id', name: 'id' },
      { data: 'name', name: 'name' },
      { data: 'note', name: 'note' },
      { data: 'active', name: 'active', orderable: false, searchable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    order: [[0,'desc']],
    language: { url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}' }
  });
});
</script>
@endpush
