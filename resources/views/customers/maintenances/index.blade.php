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
    <div class="d-flex gap-2">
      <a href="{{ route('customers.maintenance-causes.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">
        {{ __('Causas de mantenimiento') }}
      </a>
      <a href="{{ route('customers.maintenance-parts.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">
        {{ __('Piezas de mantenimiento') }}
      </a>
      @can('maintenance-create')
      <a href="{{ route('customers.maintenances.create', $customer->id) }}" class="btn btn-sm btn-primary">{{ __('Create') }}</a>
      @endcan
    </div>
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

    <!-- Summary cards -->
    <div class="row g-3 mb-3" id="maint-summary">
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">{{ __('Stopped before Start') }}</div>
                <div class="fs-4 fw-bold" id="sum_stopped">00:00:00</div>
              </div>
              <i class="ti ti-player-pause fs-2 text-warning"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">{{ __('Downtime') }}</div>
                <div class="fs-4 fw-bold" id="sum_downtime">00:00:00</div>
              </div>
              <i class="ti ti-clock fs-2 text-danger"></i>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="text-muted small">{{ __('Total Time') }}</div>
                <div class="fs-4 fw-bold" id="sum_total">00:00:00</div>
              </div>
              <i class="ti ti-sum fs-2 text-primary"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="maintenancesTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Production Line') }}</th>
            <th>{{ __('Created') }}</th>
            <th>{{ __('Start') }}</th>
            <th>{{ __('End') }}</th>
            <th>{{ __('Stopped before Start') }}</th>
            <th>{{ __('Downtime') }}</th>
            <th>{{ __('Total Time') }}</th>
            <th>{{ __('Causes') }}</th>
            <th>{{ __('Parts') }}</th>
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
      order: [[2, 'desc']], // created_at desc
      columnDefs: [
        { targets: -1, responsivePriority: 1 }, // Actions column highest priority
        { targets: 0, responsivePriority: 100 }, // ID lowest priority
        { targets: [2,3,4], responsivePriority: 2 }, // Created/Start/End
        { targets: [5,6,7], responsivePriority: 3 } // Stopped/Downtime/Total
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
        { data: 'created_at', name: 'created_at' },
        { data: 'start_datetime', name: 'start_datetime' },
        { data: 'end_datetime', name: 'end_datetime' },
        { data: 'stopped_formatted', name: 'stopped_formatted', orderable: false, searchable: false },
        { data: 'downtime_formatted', name: 'downtime_formatted', orderable: false, searchable: false },
        { data: 'total_time_formatted', name: 'total_time_formatted', orderable: false, searchable: false },
        { data: 'causes_list', name: 'causes_list', orderable: false, searchable: true },
        { data: 'parts_list', name: 'parts_list', orderable: false, searchable: true },
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

    // Load totals
    function loadTotals() {
      const form = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
      const params = new URLSearchParams({
        totals: 1,
        production_line_id: form.production_line_id.value || '',
        operator_id: form.operator_id.value || '',
        user_id: form.user_id.value || '',
        start_from: form.start_from.value || '',
        start_to: form.start_to.value || '',
        end_from: form.end_from.value || '',
        end_to: form.end_to.value || ''
      });
      fetch("{{ route('customers.maintenances.index', $customer->id) }}?" + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }})
        .then(r => r.json())
        .then(data => {
          document.getElementById('sum_stopped').textContent = data.stopped_before_start || '00:00:00';
          document.getElementById('sum_downtime').textContent = data.downtime || '00:00:00';
          document.getElementById('sum_total').textContent = data.total_time || '00:00:00';
        })
        .catch(() => {
          document.getElementById('sum_stopped').textContent = '—';
          document.getElementById('sum_downtime').textContent = '—';
          document.getElementById('sum_total').textContent = '—';
        });
    }

    loadTotals();

    // Filtros: recargar DataTable sin navegar
    const filterForm = document.querySelector('form[action="{{ route('customers.maintenances.index', $customer->id) }}"]');
    filterForm.addEventListener('submit', function(e){
      e.preventDefault();
      table.ajax.reload();
      loadTotals();
    });
  });
</script>
@endpush
