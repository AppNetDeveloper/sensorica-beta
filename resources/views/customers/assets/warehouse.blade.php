@extends('layouts.admin')

@section('title', __('Inventario de activos') . ' - ' . $customer->name)
@section('page-title', __('Inventario de activos'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.assets.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Inventario de activos') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div>
      <h5 class="mb-0">{{ __('Visión de almacén') }}</h5>
      <small class="text-muted">{{ __('Analiza el estado general del inventario de activos y detecta alertas de reposición.') }}</small>
    </div>
    <div class="d-flex flex-column flex-md-row gap-2">
      <a href="{{ route('customers.assets.index', $customer) }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left"></i> {{ __('Volver al listado de activos') }}
      </a>
      <a href="{{ route('customers.assets.create', $customer) }}" class="btn btn-primary">
        <i class="ti ti-plus"></i> {{ __('Registrar nuevo activo') }}
      </a>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 text-white kpi-card" style="background: linear-gradient(135deg,#0ea5e9 0%,#0369a1 100%);">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <p class="text-white-50 mb-1">{{ __('Activos totales') }}</p>
                <h3 class="mb-0">{{ $totals['total'] }}</h3>
              </div>
              <i class="ti ti-packages kpi-icon"></i>
            </div>
            <p class="small mb-0 mt-2 opacity-75">{{ __('Inventario completo del cliente') }}</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="text-muted mb-1">{{ __('Activos disponibles') }}</p>
                <h3 class="text-success mb-0">{{ $totals['available'] }}</h3>
              </div>
              <span class="badge bg-light text-success">{{ $totals['available_percent'] }}%</span>
            </div>
            <div class="progress" style="height:6px;">
              <div class="progress-bar bg-success" style="width: {{ $totals['available_percent'] }}%;"></div>
            </div>
            <small class="text-muted d-block mt-2">{{ __('Activos listos para usar o en reserva') }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="text-muted mb-1">{{ __('Activos con incidencias') }}</p>
                <h3 class="text-warning mb-0">{{ $totals['issues'] }}</h3>
              </div>
              <span class="badge bg-light text-warning">{{ $totals['issues_percent'] }}%</span>
            </div>
            <div class="progress" style="height:6px;">
              <div class="progress-bar bg-warning" style="width: {{ $totals['issues_percent'] }}%;"></div>
            </div>
            <small class="text-muted d-block mt-2">{{ __('Incluye consumidos, dañados o con problemas de calidad') }}</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <p class="text-muted mb-1">{{ __('Activos sin localizar') }}</p>
                <h3 class="text-danger mb-0">{{ $totals['missing'] }}</h3>
              </div>
              <span class="badge bg-light text-danger">{{ $totals['missing_percent'] }}%</span>
            </div>
            <div class="progress" style="height:6px;">
              <div class="progress-bar bg-danger" style="width: {{ $totals['missing_percent'] }}%;"></div>
            </div>
            <small class="text-muted d-block mt-2">{{ __('Requiere investigación inmediata') }}</small>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-header bg-white">
            <h6 class="mb-0">{{ __('Activos por categoría') }}</h6>
          </div>
          <div class="card-body">
            @if($byCategory->isEmpty())
              <p class="text-muted mb-0">{{ __('No hay categorías registradas para este cliente.') }}</p>
            @else
              <div class="table-responsive">
                <table class="table table-sm align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>{{ __('Categoría') }}</th>
                      <th class="text-end">{{ __('Disponibles') }}</th>
                      <th class="text-end">{{ __('Mantenimiento') }}</th>
                      <th class="text-end">{{ __('Consumidos') }}</th>
                      <th class="text-end">{{ __('Dañados') }}</th>
                      <th class="text-end">{{ __('Problemas calidad') }}</th>
                      <th class="text-end">{{ __('Sin localizar') }}</th>
                      <th class="text-end">{{ __('Total') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($byCategory as $item)
                      <tr>
                        <td>{{ $item['name'] }}</td>
                        <td class="text-end">{{ $item['counts']['available'] }}</td>
                        <td class="text-end">{{ $item['counts']['maintenance'] }}</td>
                        <td class="text-end">{{ $item['counts']['consumed'] }}</td>
                        <td class="text-end">{{ $item['counts']['damaged'] }}</td>
                        <td class="text-end">{{ $item['counts']['quality_issue'] }}</td>
                        <td class="text-end">{{ $item['counts']['lost'] }}</td>
                        <td class="text-end fw-semibold">{{ $item['counts']['total'] }}</td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-header bg-white">
            <h6 class="mb-0">{{ __('Desglose por estado') }}</h6>
          </div>
          <div class="card-body">
            @if($totals['total'] === 0)
              <p class="text-muted mb-0">{{ __('No hay activos registrados.') }}</p>
            @else
              <ul class="list-group list-group-flush">
                @foreach($statuses as $status)
                  <li class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-capitalize">{{ __($status['key']) }}</span>
                      <span class="fw-semibold">{{ $status['count'] }}</span>
                    </div>
                    <div class="progress" style="height:4px;">
                      <div class="progress-bar" role="progressbar" style="width: {{ $status['percent'] }}%;"></div>
                    </div>
                    <small class="text-muted">{{ $status['percent'] }}% {{ __('del total') }}</small>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        </div>
      </div>
    </div>

    <div class="card mt-4 border-0 shadow-sm">
      <div class="card-header bg-white">
        <h6 class="mb-0">{{ __('Activos por ubicación') }}</h6>
      </div>
      <div class="card-body">
        @if($byLocation->isEmpty())
          <p class="text-muted mb-0">{{ __('No hay ubicaciones registradas para este cliente.') }}</p>
        @else
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Ubicación') }}</th>
                  <th class="text-end">{{ __('Disponibles') }}</th>
                  <th class="text-end">{{ __('Mantenimiento') }}</th>
                  <th class="text-end">{{ __('Consumidos') }}</th>
                  <th class="text-end">{{ __('Problemas calidad') }}</th>
                  <th class="text-end">{{ __('Sin localizar') }}</th>
                  <th class="text-end">{{ __('Total') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($byLocation as $item)
                  <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="text-end">{{ $item['counts']['available'] }}</td>
                    <td class="text-end">{{ $item['counts']['maintenance'] }}</td>
                    <td class="text-end">{{ $item['counts']['consumed'] }}</td>
                    <td class="text-end">{{ $item['counts']['quality_issue'] }}</td>
                    <td class="text-end">{{ $item['counts']['lost'] }}</td>
                    <td class="text-end fw-semibold">{{ $item['counts']['total'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .kpi-card .kpi-icon {
    font-size: 2.5rem;
    opacity: 0.35;
  }
</style>
@endpush
