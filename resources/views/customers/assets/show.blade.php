@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Detalle de activo') . ' - ' . $asset->label_code)
@section('page-title', __('Detalle de activo'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.assets.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ $asset->label_code }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="row g-3">
  <div class="col-12 col-xl-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Identificación') }}</h5>
        <a href="{{ route('customers.assets.print-label', [$customer, $asset]) }}" target="_blank" class="btn btn-sm btn-outline-success">
          <i class="ti ti-printer"></i> {{ __('Imprimir etiqueta') }}
        </a>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Etiqueta (QR/Barcode)') }}</dt>
          <dd class="col-7"><code>{{ $asset->label_code }}</code></dd>

          <dt class="col-5 text-muted">{{ __('Código artículo') }}</dt>
          <dd class="col-7"><code>{{ $asset->article_code }}</code></dd>

          <dt class="col-5 text-muted">{{ __('Descripción') }}</dt>
          <dd class="col-7">{{ $asset->description }}</dd>

          <dt class="col-5 text-muted">{{ __('Estado') }}</dt>
          <dd class="col-7">
            <span class="badge bg-{{ $asset->status === 'active' ? 'success' : ($asset->status === 'maintenance' ? 'warning' : ($asset->status === 'retired' ? 'secondary' : 'info')) }} text-uppercase">
              {{ __($asset->status) }}
            </span>
          </dd>

          <dt class="col-5 text-muted">{{ __('Fecha de adquisición') }}</dt>
          <dd class="col-7">{{ optional($asset->acquired_at)->format('d/m/Y') ?? '—' }}</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Clasificación y logística') }}</h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Categoría') }}</dt>
          <dd class="col-7">{{ optional($asset->category)->name ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Centro de coste') }}</dt>
          <dd class="col-7">{{ optional($asset->costCenter)->name ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Ubicación') }}</dt>
          <dd class="col-7">{{ optional($asset->location)->name ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Proveedor asociado') }}</dt>
          <dd class="col-7">{{ optional($asset->supplier)->name ?? '—' }}</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-12 col-xl-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Identificación RFID') }}</h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Etiqueta RFID') }}</dt>
          <dd class="col-7">{{ $asset->has_rfid_tag ? __('Sí') : __('No') }}</dd>

          <dt class="col-5 text-muted">{{ __('RFID EPC (categoría)') }}</dt>
          <dd class="col-7">{{ $asset->rfid_epc ? Str::limit($asset->rfid_epc, 40) : '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('RFID TID (activo)') }}</dt>
          <dd class="col-7">{{ $asset->rfid_tid ? Str::limit($asset->rfid_tid, 40) : '—' }}</dd>
        </dl>
      </div>
    </div>
  </div>
</div>

@if(!empty($asset->attributes))
  <div class="card mt-3">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Atributos adicionales') }}</h5>
    </div>
    <div class="card-body">
      <pre class="mb-0 bg-light p-3 rounded">{{ json_encode($asset->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
  </div>
@endif

@if($asset->events->isNotEmpty())
  <div class="card mt-3">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Historial de eventos') }}</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>{{ __('Fecha') }}</th>
              <th>{{ __('Tipo') }}</th>
              <th>{{ __('Título') }}</th>
              <th>{{ __('Usuario') }}</th>
              <th>{{ __('Detalles') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($asset->events as $event)
              <tr>
                <td>{{ optional($event->event_at)->format('d/m/Y H:i') ?? $event->created_at->format('d/m/Y H:i') }}</td>
                <td><span class="badge bg-secondary text-uppercase">{{ __($event->type) }}</span></td>
                <td>{{ $event->title }}</td>
                <td>{{ optional($event->user)->name ?? '—' }}</td>
                <td>
                  @if($event->description)
                    <div>{{ $event->description }}</div>
                  @endif
                  @if($event->payload)
                    <pre class="small bg-light p-2 rounded mt-2 mb-0">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endif
@endsection
