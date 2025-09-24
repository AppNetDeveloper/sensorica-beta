@extends('layouts.admin')

@section('title', __('Clientes del Cliente') . ' - ' . $customer->name)
@section('page-title', __('Clientes del Cliente'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Clientes') }}</li>
  </ul>
  </div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Clientes') }}</h5>
    <div class="btn-toolbar" role="toolbar" aria-label="Toolbar">
      <div class="btn-group btn-group-sm me-3" role="group" aria-label="Acciones principales">
        @can('customer-clients-create')
        <a href="{{ route('customers.clients.create', $customer->id) }}" class="btn btn-primary d-inline-flex align-items-center">
          <i class="ti ti-plus me-1"></i><span>{{ __('Create') }}</span>
        </a>
        @endcan
        @can('customer-clients-view')
        <a href="{{ route('customers.clients.export', $customer->id) }}" class="btn btn-outline-secondary d-inline-flex align-items-center ms-2">
          <i class="ti ti-download me-1"></i><span>{{ __('Export') }}</span>
        </a>
        @endcan
      </div>
      @can('customer-clients-create')
      <form action="{{ route('customers.clients.import', $customer->id) }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2" aria-label="Importar CSV">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" class="form-control form-control-sm w-auto" required>
        <button type="submit" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center">
          <i class="ti ti-upload me-1"></i><span>{{ __('Import') }}</span>
        </button>
      </form>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="clientsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Address') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Tax ID') }}</th>
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
  const table = $('#clientsTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    ajax: {
      url: "{{ route('customers.clients.index', $customer->id) }}",
    },
    columns: [
      { data: 'id', name: 'id' },
      { data: 'name', name: 'name' },
      { data: 'address', name: 'address' },
      { data: 'phone', name: 'phone' },
      { data: 'email', name: 'email' },
      { data: 'tax_id', name: 'tax_id' },
      { data: 'active', name: 'active', orderable: false, searchable: false },
      { data: 'actions', name: 'actions', orderable: false, searchable: false },
    ],
    order: [[0,'desc']],
    language: { url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}' }
  });
});
</script>
@endpush
