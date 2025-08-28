@extends('layouts.admin')

@section('title', __('Maintenances') . ' - ' . $customer->name)
@section('page-title', __('Maintenances'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Maintenances') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Maintenances') }}</h5>
    @can('maintenance-create')
    <a href="{{ route('customers.maintenances.create', $customer->id) }}" class="btn btn-sm btn-primary">{{ __('Create') }}</a>
    @endcan
  </div>
  <div class="card-body">
    <form method="GET" action="{{ route('customers.maintenances.index', $customer->id) }}" class="row g-2 align-items-end mb-3">
      <div class="col-md-4">
        <label class="form-label">{{ __('Production Line') }}</label>
        <select name="production_line_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($lines as $line)
            <option value="{{ $line->id }}" {{ (string)($lineId ?? '') === (string)$line->id ? 'selected' : '' }}>{{ $line->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Operator') }}</label>
        <select name="operator_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($operators as $op)
            <option value="{{ $op->id }}" {{ (string)($operatorId ?? '') === (string)$op->id ? 'selected' : '' }}>{{ $op->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('User') }}</label>
        <select name="user_id" class="form-select">
          <option value="">{{ __('All') }}</option>
          @foreach($users as $u)
            <option value="{{ $u->id }}" {{ (string)($userId ?? '') === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Start from') }}</label>
        <input type="date" name="start_from" value="{{ $startFrom ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Start to') }}</label>
        <input type="date" name="start_to" value="{{ $startTo ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('End from') }}</label>
        <input type="date" name="end_from" value="{{ $endFrom ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('End to') }}</label>
        <input type="date" name="end_to" value="{{ $endTo ?? '' }}" class="form-control" />
      </div>
      <div class="col-md-2 d-flex">
        <button type="submit" class="btn btn-outline-primary w-100">{{ __('Filter') }}</button>
      </div>
    </form>

    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="maintenancesTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Production Line') }}</th>
            <th>{{ __('Start') }}</th>
            <th>{{ __('End') }}</th>
            <th>{{ __('Downtime') }}</th>
            <th>{{ __('Machine Stopped?') }}</th>
            <th>{{ __('Operator') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Annotations') }}</th>
            <th>{{ __('Operator Annotation') }}</th>
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
    const table = $('#maintenancesTable').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      order: [[2, 'desc']], // start_datetime desc
      columnDefs: [
        { targets: -1, responsivePriority: 1 }, // Actions column highest priority
        { targets: 0, responsivePriority: 100 }, // ID lowest priority
        { targets: [2,3], responsivePriority: 2 }, // Start/End
        { targets: [4], responsivePriority: 3 } // Downtime
      ],
      ajax: {
        url: "{{ route('customers.maintenances.index', $customer->id) }}",
        data: function(d) {
          const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
          d.production_line_id = form.production_line_id.value;
          d.operator_id = form.operator_id.value;
          d.user_id = form.user_id.value;
          d.start_from = form.start_from.value;
          d.start_to = form.start_to.value;
          d.end_from = form.end_from.value;
          d.end_to = form.end_to.value;
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        { data: 'production_line', name: 'production_line', orderable: false, searchable: true },
        { data: 'start_datetime', name: 'start_datetime' },
        { data: 'end_datetime', name: 'end_datetime' },
        { data: 'downtime_formatted', name: 'downtime_formatted', orderable: false, searchable: false },
        { data: 'production_line_stop_label', name: 'production_line_stop', orderable: false, searchable: false },
        { data: 'operator_name', name: 'operator_name', orderable: false, searchable: true },
        { data: 'user_name', name: 'user_name', orderable: false, searchable: true },
        { data: 'annotations_short', name: 'annotations', orderable: false, searchable: true },
        { data: 'operator_annotations_short', name: 'operator_annotations', orderable: false, searchable: true },
        { data: 'actions', name: 'actions', orderable: false, searchable: false }
      ],
      language: {
        url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}'
      }
    });

    // Filtros: recargar DataTable sin navegar
    const filterForm = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
    filterForm.addEventListener('submit', function(e){
      e.preventDefault();
      table.ajax.reload();
    });
  });
</script>
@endpush
