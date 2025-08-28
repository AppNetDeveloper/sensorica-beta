@extends('layouts.admin')

@section('title', __('Piezas de mantenimiento') . ' - ' . $customer->name)
@section('page-title', __('Piezas de mantenimiento'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Piezas de mantenimiento') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Piezas de mantenimiento') }}</h5>
    @can('maintenance-create')
    <a href="{{ route('customers.maintenance-parts.create', $customer->id) }}" class="btn btn-sm btn-primary">{{ __('Crear pieza') }}</a>
    @endcan
  </div>
  <div class="card-body">
    <div class="table-responsive" style="max-width: 100%; margin: 0 auto;">
      <table class="table table-striped align-middle" id="partsTable">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Code') }}</th>
            <th>{{ __('Active') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($parts as $part)
          <tr>
            <td>{{ $part->id }}</td>
            <td>{{ $part->name }}</td>
            <td>{{ $part->code }}</td>
            <td>
              @if($part->active)
                <span class="badge bg-success">{{ __('Active') }}</span>
              @else
                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
              @endif
            </td>
            <td class="text-nowrap">
              <a href="{{ route('customers.maintenance-parts.edit', [$customer->id, $part->id]) }}" class="btn btn-sm btn-outline-primary me-1">
                {{ __('Edit') }}
              </a>
              <form action="{{ route('customers.maintenance-parts.destroy', [$customer->id, $part->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
              </form>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center text-muted">{{ __('No records found') }}</td>
          </tr>
          @endforelse
        </tbody>
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
    $('#partsTable').DataTable({
      responsive: true,
      language: { url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}' }
    });
  });
</script>
@endpush
