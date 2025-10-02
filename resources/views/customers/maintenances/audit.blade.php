@extends('layouts.admin')

@section('title', __('Historial de Auditoría') . ' - ' . __('Maintenance') . ' #' . $maintenance->id)
@section('page-title', __('Historial de Auditoría'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.maintenances.index', $customer->id) }}">{{ __('Maintenances') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Audit History') }} #{{ $maintenance->id }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">
      <i class="ti ti-history me-2"></i>{{ __('Historial de Auditoría') }} - {{ __('Maintenance') }} #{{ $maintenance->id }}
    </h5>
  </div>
  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-6">
        <strong>{{ __('Production Line') }}:</strong> {{ optional($maintenance->productionLine)->name }}
      </div>
      <div class="col-md-6">
        <strong>{{ __('Created') }}:</strong> {{ $maintenance->created_at->format('Y-m-d H:i') }}
      </div>
    </div>

    @if($logs->isEmpty())
      <div class="alert alert-info">
        <i class="ti ti-info-circle me-2"></i>{{ __('No hay registros de auditoría para este mantenimiento') }}
      </div>
    @else
      <div class="timeline">
        @foreach($logs as $log)
        <div class="timeline-item mb-4">
          <div class="d-flex">
            <div class="timeline-marker me-3">
              @if($log->action === 'created')
                <div class="badge bg-success rounded-circle p-2">
                  <i class="ti ti-plus"></i>
                </div>
              @elseif($log->action === 'started')
                <div class="badge bg-primary rounded-circle p-2">
                  <i class="ti ti-player-play"></i>
                </div>
              @elseif($log->action === 'updated')
                <div class="badge bg-info rounded-circle p-2">
                  <i class="ti ti-edit"></i>
                </div>
              @elseif($log->action === 'finished')
                <div class="badge bg-warning rounded-circle p-2">
                  <i class="ti ti-check"></i>
                </div>
              @elseif($log->action === 'deleted')
                <div class="badge bg-danger rounded-circle p-2">
                  <i class="ti ti-trash"></i>
                </div>
              @else
                <div class="badge bg-secondary rounded-circle p-2">
                  <i class="ti ti-dots"></i>
                </div>
              @endif
            </div>
            <div class="flex-grow-1">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">
                      {{ ucfirst($log->action) }}
                      @if($log->description)
                        <small class="text-muted">- {{ $log->description }}</small>
                      @endif
                    </h6>
                    <small class="text-muted">{{ $log->created_at->format('Y-m-d H:i:s') }}</small>
                  </div>
                  
                  <div class="mb-2">
                    <strong>{{ __('User') }}:</strong> {{ optional($log->user)->name ?? 'Sistema' }}
                    @if($log->ip_address)
                      <span class="text-muted ms-2">(IP: {{ $log->ip_address }})</span>
                    @endif
                  </div>

                  @if($log->old_values || $log->new_values)
                  <div class="mt-3">
                    <strong>{{ __('Cambios') }}:</strong>
                    <div class="table-responsive mt-2">
                      <table class="table table-sm table-bordered">
                        <thead>
                          <tr>
                            <th>{{ __('Campo') }}</th>
                            <th>{{ __('Valor Anterior') }}</th>
                            <th>{{ __('Valor Nuevo') }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          @php
                            $oldValues = $log->old_values ?? [];
                            $newValues = $log->new_values ?? [];
                            $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                          @endphp
                          @foreach($allKeys as $key)
                          <tr>
                            <td><code>{{ $key }}</code></td>
                            <td>
                              @if(isset($oldValues[$key]))
                                @if(is_bool($oldValues[$key]))
                                  <span class="badge bg-{{ $oldValues[$key] ? 'success' : 'secondary' }}">
                                    {{ $oldValues[$key] ? 'true' : 'false' }}
                                  </span>
                                @elseif(is_null($oldValues[$key]))
                                  <span class="text-muted">null</span>
                                @else
                                  {{ $oldValues[$key] }}
                                @endif
                              @else
                                <span class="text-muted">-</span>
                              @endif
                            </td>
                            <td>
                              @if(isset($newValues[$key]))
                                @if(is_bool($newValues[$key]))
                                  <span class="badge bg-{{ $newValues[$key] ? 'success' : 'secondary' }}">
                                    {{ $newValues[$key] ? 'true' : 'false' }}
                                  </span>
                                @elseif(is_null($newValues[$key]))
                                  <span class="text-muted">null</span>
                                @else
                                  {{ $newValues[$key] }}
                                @endif
                              @else
                                <span class="text-muted">-</span>
                              @endif
                            </td>
                          </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>

      <div class="mt-4">
        {{ $logs->links() }}
      </div>
    @endif

    <div class="mt-4">
      <a href="{{ route('customers.maintenances.index', $customer->id) }}" class="btn btn-secondary">
        <i class="ti ti-arrow-left me-1"></i>{{ __('Volver') }}
      </a>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .timeline-marker {
    min-width: 40px;
  }
  .timeline-item:not(:last-child) .timeline-marker::after {
    content: '';
    position: absolute;
    left: 19px;
    top: 40px;
    width: 2px;
    height: calc(100% + 16px);
    background: #e0e0e0;
  }
</style>
@endpush
