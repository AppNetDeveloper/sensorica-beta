@extends('layouts.admin')

@section('title', __('Historial de Callbacks') . ' - ' . $customer->name)
@section('page-title', __('Historial de Callbacks'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item">{{ $customer->name }} - {{ __('Historial de Callbacks') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Historial de Callbacks') }}</h5>
    <div class="text-muted small">
      {{ __('Mostrando') }} {{ $callbacks->firstItem() ?: 0 }}–{{ $callbacks->lastItem() ?: 0 }} {{ __('de') }} {{ $callbacks->total() }}
    </div>
  </div>
  <div class="card-body p-3 p-md-4">
    <div class="container-fluid">
      <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle mb-0" id="callbacksTable" style="width:100%">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Fecha') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Intentos') }}</th>
            <th>{{ __('URL') }}</th>
            <th>{{ __('Mensaje de error') }}</th>
            <th class="text-end">{{ __('Acciones') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($callbacks as $cb)
            <tr>
              <td>{{ $cb->id }}</td>
              <td>{{ optional($cb->created_at)->format('Y-m-d H:i') }}</td>
              <td>
                @php
                  $badges = [0 => 'secondary', 1 => 'success', 2 => 'danger'];
                  $labels = [0 => __('Pendiente'), 1 => __('Enviado'), 2 => __('Error')];
                @endphp
                <span class="badge bg-{{ $badges[$cb->status] ?? 'secondary' }}">{{ $labels[$cb->status] ?? $cb->status }}</span>
              </td>
              <td>{{ $cb->attempts ?? 0 }}</td>
              <td><code class="small">{{ \Illuminate\Support\Str::limit($cb->callback_url, 60) }}</code></td>
              <td class="small">{{ \Illuminate\Support\Str::limit($cb->error_message, 90) }}</td>
              <td class="text-end">
                <div class="d-flex justify-content-end flex-wrap gap-2">
                  @can('callbacks.update')
                    <a href="{{ route('customers.callbacks.edit', [$customer->id, $cb->id]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Editar') }}">
                      <i class="ti ti-edit"></i>
                    </a>
                  @endcan
                  @can('callbacks.force')
                    <form action="{{ route('customers.callbacks.force', [$customer->id, $cb->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Forzar reintento?') }}')">
                      @csrf
                      <button class="btn btn-sm btn-outline-dark" type="submit" title="{{ __('Forzar reintento') }}">
                        <i class="ti ti-refresh"></i>
                      </button>
                    </form>
                  @endcan
                  @can('callbacks.delete')
                    <form action="{{ route('customers.callbacks.destroy', [$customer->id, $cb->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Eliminar callback?') }}')">
                      @csrf
                      @method('DELETE')
                      <button class="btn btn-sm btn-outline-danger" type="submit" title="{{ __('Eliminar') }}">
                        <i class="ti ti-trash"></i>
                      </button>
                    </form>
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">{{ __('No hay callbacks registrados') }}</td>
            </tr>
          @endforelse
        </tbody>
        </table>
      </div>
    </div>
    <div class="mt-3 d-none" id="laravelPaginator">
      {{ $callbacks->links() }}
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
    // Initialize DataTables client-side
    const dt = $('#callbacksTable').DataTable({
      responsive: true,
      order: [[0, 'desc']],
      pageLength: 25,
      language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
    // Hide Laravel paginator when DataTables is active
    $('#laravelPaginator').addClass('d-none');
  });
  </script>
@endpush
