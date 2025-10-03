@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Recepción') . ' - ' . ($receipt->reference ?? $receipt->id))
@section('page-title', __('Detalle de recepción'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.index', $customer) }}">{{ __('Pedidos a proveedor') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.show', [$customer, $vendorOrder]) }}">{{ $vendorOrder->reference }}</a></li>
    <li class="breadcrumb-item active">{{ __('Recepción') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Resumen de recepción') }}</h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Referencia') }}</dt>
          <dd class="col-7">{{ $receipt->reference ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Fecha recepción') }}</dt>
          <dd class="col-7">{{ optional($receipt->received_at)->format('d/m/Y H:i') ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Recibido por') }}</dt>
          <dd class="col-7">{{ optional($receipt->receiver)->name ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Total líneas') }}</dt>
          <dd class="col-7">{{ $receipt->lines->sum('quantity_received') }}</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Líneas recibidas') }}</h5>
      </div>
      <div class="card-body">
        @if($receipt->lines->isEmpty())
          <div class="alert alert-info mb-0">{{ __('No se registraron líneas en esta recepción.') }}</div>
        @else
          <div class="table-responsive">
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>{{ __('Producto') }}</th>
                  <th>{{ __('Descripción') }}</th>
                  <th class="text-end">{{ __('Cantidad recibida') }}</th>
                  <th class="text-end">{{ __('Coste unitario') }}</th>
                  <th class="text-end">{{ __('Subtotal') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($receipt->lines as $line)
                  @php
                    $orderLine = $line->vendorOrderLine;
                    $quantity = (float)$line->quantity_received;
                    $unitCost = (float)$line->unit_cost;
                    $subtotal = $quantity * $unitCost;
                  @endphp
                  <tr>
                    <td>{{ optional(optional($orderLine)->item)->name ?? '—' }}</td>
                    <td>{{ optional($orderLine)->description ?? '—' }}</td>
                    <td class="text-end">{{ number_format($quantity, 4) }}</td>
                    <td class="text-end">{{ number_format($unitCost, 4) }} {{ $vendorOrder->currency }}</td>
                    <td class="text-end">{{ number_format($subtotal, 2) }} {{ $vendorOrder->currency }}</td>
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

@if(!empty($receipt->notes))
  <div class="card mt-3">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Notas') }}</h5>
    </div>
    <div class="card-body">
      <p class="mb-0">{!! nl2br(e($receipt->notes)) !!}</p>
    </div>
  </div>
@endif
@endsection
