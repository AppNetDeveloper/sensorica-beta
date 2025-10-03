@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Pedido a proveedor') . ' - ' . $vendorOrder->reference)
@section('page-title', __('Pedido a proveedor'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.index', $customer) }}">{{ __('Pedidos a proveedor') }}</a></li>
    <li class="breadcrumb-item active">{{ $vendorOrder->reference }}</li>
  </ul>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const referenceInput = document.getElementById('filter-reference');
    const receiverSelect = document.getElementById('filter-receiver');
    const dateFromInput = document.getElementById('filter-date-from');
    const dateToInput = document.getElementById('filter-date-to');
    const resetButton = document.getElementById('reset-filters');
    const tableRows = document.querySelectorAll('#receipts-table tbody tr');

    function normalize(value) {
      return (value || '').toString().trim().toLowerCase();
    }

    function passesFilters(row) {
      const referenceFilter = normalize(referenceInput?.value);
      const receiverFilter = receiverSelect?.value || '';
      const dateFrom = dateFromInput?.value;
      const dateTo = dateToInput?.value;

      const rowReference = row.getAttribute('data-reference') || '';
      const rowReceiver = row.getAttribute('data-receiver') || '';
      const rowDate = row.getAttribute('data-date') || '';

      if (referenceFilter && !rowReference.includes(referenceFilter)) {
        return false;
      }

      if (receiverFilter && rowReceiver !== receiverFilter) {
        return false;
      }

      if (dateFrom && (!rowDate || rowDate < dateFrom)) {
        return false;
      }

      if (dateTo && (!rowDate || rowDate > dateTo)) {
        return false;
      }

      return true;
    }

    function applyFilters() {
      tableRows.forEach(function (row) {
        row.style.display = passesFilters(row) ? '' : 'none';
      });
    }

    referenceInput?.addEventListener('input', applyFilters);
    receiverSelect?.addEventListener('change', applyFilters);
    dateFromInput?.addEventListener('change', applyFilters);
    dateToInput?.addEventListener('change', applyFilters);

    resetButton?.addEventListener('click', function () {
      if (referenceInput) referenceInput.value = '';
      if (receiverSelect) receiverSelect.value = '';
      if (dateFromInput) dateFromInput.value = '';
      if (dateToInput) dateToInput.value = '';
      applyFilters();
    });

    applyFilters();
  });
</script>
@endpush

@section('content')
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Resumen del pedido') }}</h5>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Referencia') }}</dt>
          <dd class="col-7 fw-semibold">{{ $vendorOrder->reference }}</dd>

          <dt class="col-5 text-muted">{{ __('Proveedor') }}</dt>
          <dd class="col-7">{{ optional($vendorOrder->supplier)->name ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Estado') }}</dt>
          <dd class="col-7"><span class="badge bg-secondary text-uppercase">{{ __($vendorOrder->status) }}</span></dd>

          <dt class="col-5 text-muted">{{ __('Moneda') }}</dt>
          <dd class="col-7">{{ $vendorOrder->currency }}</dd>

          <dt class="col-5 text-muted">{{ __('Importe total') }}</dt>
          <dd class="col-7 fw-semibold">{{ number_format($vendorOrder->total_amount, 2) }} {{ $vendorOrder->currency }}</dd>

          <dt class="col-5 text-muted">{{ __('Fecha solicitada') }}</dt>
          <dd class="col-7">{{ optional($vendorOrder->requested_at)->format('d/m/Y H:i') ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Fecha esperada') }}</dt>
          <dd class="col-7">{{ optional($vendorOrder->expected_at)->format('d/m/Y') ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Creado por') }}</dt>
          <dd class="col-7">{{ optional($vendorOrder->requester)->name ?? '—' }}</dd>

          <dt class="col-5 text-muted">{{ __('Aprobado por') }}</dt>
          <dd class="col-7">{{ optional($vendorOrder->approver)->name ?? '—' }}</dd>
        </dl>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card h-100">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="orderTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="lines-tab" data-bs-toggle="tab" data-bs-target="#lines" type="button" role="tab" aria-controls="lines" aria-selected="true">{{ __('Líneas del pedido') }}</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="receipts-tab" data-bs-toggle="tab" data-bs-target="#receipts" type="button" role="tab" aria-controls="receipts" aria-selected="false">{{ __('Recepciones') }}</button>
          </li>
        </ul>
      </div>
      <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="lines" role="tabpanel" aria-labelledby="lines-tab">
          <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('customers.vendor-orders.edit', [$customer, $vendorOrder]) }}" class="btn btn-sm btn-outline-secondary">
              <i class="ti ti-edit"></i> {{ __('Editar pedido') }}
            </a>
          </div>
          @if($vendorOrder->lines->isEmpty())
            <div class="alert alert-info mb-0">{{ __('No hay líneas registradas.') }}</div>
          @else
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-light">
                  <tr>
                    <th>{{ __('Producto catalogado') }}</th>
                    <th>{{ __('Descripción') }}</th>
                    <th class="text-end">{{ __('Cantidad pedida') }}</th>
                    <th class="text-end">{{ __('Cantidad pendiente') }}</th>
                    <th class="text-end">{{ __('Precio unitario') }}</th>
                    <th class="text-end">{{ __('IVA (%)') }}</th>
                    <th class="text-end">{{ __('Subtotal') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($vendorOrder->lines as $line)
                    @php
                      $lineTotal = ($line->quantity_ordered ?? 0) * ($line->unit_price ?? 0);
                      $lineTotal *= 1 + (($line->tax_rate ?? 0) / 100);
                    @endphp
                    <tr>
                      <td>{{ optional($line->item)->name ?? '—' }}</td>
                      <td>{{ $line->description }}</td>
                      <td class="text-end">{{ number_format($line->quantity_ordered, 4) }}</td>
                      <td class="text-end">{{ number_format($line->quantity_pending, 4) }}</td>
                      <td class="text-end">{{ number_format($line->unit_price ?? 0, 4) }}</td>
                      <td class="text-end">{{ number_format($line->tax_rate ?? 0, 2) }}</td>
                      <td class="text-end">{{ number_format($lineTotal, 2) }} {{ $vendorOrder->currency }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
        <div class="tab-pane fade" id="receipts" role="tabpanel" aria-labelledby="receipts-tab">
          @php
            $totalOrdered = $vendorOrder->lines->sum('quantity_ordered');
            $totalReceived = $vendorOrder->lines->sum(fn ($line) => $line->receiptLines->sum('quantity_received'));
            $totalPending = $vendorOrder->lines->sum(fn ($line) => $line->quantity_pending);
            $progress = $totalOrdered > 0 ? ($totalReceived / max($totalOrdered, 0.0001)) * 100 : 0;
            $receivers = $vendorOrder->assetReceipts->pluck('receiver')->filter()->unique('id');
          @endphp

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">{{ __('Total pedido') }}</p>
                  <h4 class="mb-0">{{ number_format($totalOrdered, 2) }}</h4>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">{{ __('Cantidad recibida') }}</p>
                  <h4 class="mb-0 text-success">{{ number_format($totalReceived, 2) }}</h4>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">{{ __('Cantidad pendiente') }}</p>
                  <h4 class="mb-0 text-warning">{{ number_format($totalPending, 2) }}</h4>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">{{ __('Avance de recepción') }}</p>
                  <h4 class="mb-2">{{ number_format($progress, 1) }}%</h4>
                  <div class="progress" style="height: 6px;">
                    <div class="progress-bar" role="progressbar" style="width: {{ min(100, $progress) }}%" aria-valuenow="{{ min(100, $progress) }}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card border mb-3">
            <div class="card-body">
              <form id="receipts-filter-form" class="row gy-2 gx-3 align-items-end">
                <div class="col-md-4">
                  <label for="filter-reference" class="form-label">{{ __('Buscar por referencia o notas') }}</label>
                  <input type="text" id="filter-reference" class="form-control" placeholder="{{ __('Referencia, notas...') }}">
                </div>
                <div class="col-md-3">
                  <label for="filter-receiver" class="form-label">{{ __('Recibido por') }}</label>
                  <select id="filter-receiver" class="form-select">
                    <option value="">{{ __('Todos') }}</option>
                    @foreach($receivers as $receiver)
                      <option value="{{ $receiver->id }}">{{ $receiver->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <label for="filter-date-from" class="form-label">{{ __('Desde') }}</label>
                  <input type="date" id="filter-date-from" class="form-control">
                </div>
                <div class="col-md-2">
                  <label for="filter-date-to" class="form-label">{{ __('Hasta') }}</label>
                  <input type="date" id="filter-date-to" class="form-control">
                </div>
                <div class="col-md-1 d-grid">
                  <button type="button" id="reset-filters" class="btn btn-outline-secondary">{{ __('Limpiar') }}</button>
                </div>
              </form>
            </div>
          </div>

          <div class="d-flex justify-content-end mb-3">
            @can('asset-receipts-create')
              <a href="{{ route('customers.vendor-orders.receipts.create', [$customer, $vendorOrder]) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i> {{ __('Registrar recepción') }}
              </a>
            @endcan
          </div>
          @if($vendorOrder->assetReceipts->isEmpty())
            <div class="alert alert-info mb-0">{{ __('No hay recepciones registradas para este pedido.') }}</div>
          @else
            <div class="table-responsive">
              <p class="text-muted small mb-2">{{ __('Cada recepción muestra el total de unidades registradas y el usuario responsable.') }}</p>
              <table class="table table-striped align-middle" id="receipts-table">
                <thead class="table-light">
                  <tr>
                    <th>{{ __('Referencia') }}</th>
                    <th>{{ __('Fecha recepción') }}</th>
                    <th>{{ __('Recibido por') }}</th>
                    <th>{{ __('Notas') }}</th>
                    <th class="text-end">{{ __('Total líneas') }}</th>
                    <th class="text-end">{{ __('Activos generados') }}</th>
                    <th class="text-end">{{ __('Acciones') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($vendorOrder->assetReceipts as $receipt)
                    @php
                      $lineTotal = $receipt->lines->sum('quantity_received');
                      $assetsCount = $receipt->lines->whereNotNull('asset_id')->count();
                      $dateValue = optional($receipt->received_at)->format('Y-m-d');
                    @endphp
                    <tr data-reference="{{ Str::lower($receipt->reference . ' ' . $receipt->notes) }}" data-receiver="{{ optional($receipt->receiver)->id }}" data-date="{{ $dateValue }}">
                      <td>{{ $receipt->reference ?? '—' }}</td>
                      <td>{{ optional($receipt->received_at)->format('d/m/Y H:i') ?? '—' }}</td>
                      <td>{{ optional($receipt->receiver)->name ?? '—' }}</td>
                      <td>{{ Str::limit($receipt->notes, 40) ?? '—' }}</td>
                      <td class="text-end">{{ number_format($lineTotal, 4) }}</td>
                      <td class="text-end">
                        @if($assetsCount > 0)
                          <span class="badge bg-success">{{ $assetsCount }}</span>
                        @else
                          <span class="text-muted">0</span>
                        @endif
                      </td>
                      <td class="text-end">
                        <a href="{{ route('customers.vendor-orders.receipts.show', [$customer, $vendorOrder, $receipt]) }}" class="btn btn-sm btn-outline-primary">
                          <i class="ti ti-eye"></i>
                        </a>
                      </td>
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
</div>

@if(!empty($vendorOrder->notes))
  <div class="card mt-3">
    <div class="card-header">
      <h5 class="mb-0">{{ __('Notas') }}</h5>
    </div>
    <div class="card-body">
      <p class="mb-0">{!! nl2br(e($vendorOrder->notes)) !!}</p>
    </div>
  </div>
@endif
@endsection
