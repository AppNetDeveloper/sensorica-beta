@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Registrar recepción') . ' - ' . $vendorOrder->reference)
@section('page-title', __('Registrar recepción de pedido'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.index', $customer) }}">{{ __('Pedidos a proveedor') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.show', [$customer, $vendorOrder]) }}">{{ $vendorOrder->reference }}</a></li>
    <li class="breadcrumb-item active">{{ __('Registrar recepción') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Datos de recepción') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.vendor-orders.receipts.store', [$customer, $vendorOrder]) }}" method="POST">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label for="reference" class="form-label">{{ __('Referencia albarán') }}</label>
          <input type="text" name="reference" id="reference" class="form-control @error('reference') is-invalid @enderror" value="{{ old('reference') }}">
          @error('reference')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label for="received_at" class="form-label">{{ __('Fecha de recepción') }}</label>
          <input type="datetime-local" name="received_at" id="received_at" class="form-control @error('received_at') is-invalid @enderror" value="{{ old('received_at', now()->format('Y-m-d\TH:i')) }}">
          @error('received_at')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label for="notes" class="form-label">{{ __('Notas') }}</label>
          <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
          @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>

      <hr class="my-4">

      <h5 class="mb-3">{{ __('Líneas con cantidad pendiente') }}</h5>
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>{{ __('Producto') }}</th>
              <th>{{ __('Descripción') }}</th>
              <th class="text-end">{{ __('Cant. pedida') }}</th>
              <th class="text-end">{{ __('Cant. pendiente') }}</th>
              <th class="text-end" style="width: 160px;">{{ __('Cant. a recibir') }}</th>
              <th class="text-end" style="width: 160px;">{{ __('Coste unitario') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pendingLines as $line)
              @php
                $inputName = "lines[{$line->id}]";
                $createAssetChecked = old("lines.{$line->id}.create_asset") || $errors->has("lines.{$line->id}.asset_category_id") || $errors->has("lines.{$line->id}.article_code");
              @endphp
              <tr>
                <td>{{ optional($line->item)->name ?? '—' }}</td>
                <td>{{ $line->description }}</td>
                <td class="text-end">{{ number_format($line->quantity_ordered, 4) }}</td>
                <td class="text-end" data-pending="{{ $line->quantity_pending }}">{{ number_format($line->quantity_pending, 4) }}</td>
                <td>
                  <input type="number" name="{{ $inputName }}[quantity_received]" class="form-control form-control-sm quantity-input @error("lines.{$line->id}.quantity_received") is-invalid @enderror" min="0" max="{{ $line->quantity_pending }}" step="0.0001" value="{{ old("lines.{$line->id}.quantity_received", $line->quantity_pending) }}">
                  @error("lines.{$line->id}.quantity_received")
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </td>
                <td>
                  <input type="number" name="{{ $inputName }}[unit_cost]" class="form-control form-control-sm @error("lines.{$line->id}.unit_cost") is-invalid @enderror" min="0" step="0.0001" value="{{ old("lines.{$line->id}.unit_cost", $line->unit_price) }}">
                  @error("lines.{$line->id}.unit_cost")
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </td>
              </tr>
              <tr class="bg-light">
                <td colspan="6">
                  <div class="d-flex flex-column gap-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                      <div class="form-check form-switch">
                        <input class="form-check-input create-asset-toggle" type="checkbox" role="switch" id="create-asset-{{ $line->id }}" name="{{ $inputName }}[create_asset]" value="1" {{ $createAssetChecked ? 'checked' : '' }} data-target="#asset-fields-{{ $line->id }}">
                        <label class="form-check-label" for="create-asset-{{ $line->id }}">{{ __('Crear activo automáticamente') }}</label>
                      </div>
                      <small class="text-muted">{{ __('Se generará un activo con códigos únicos y vínculo a esta recepción.') }}</small>
                    </div>
                    <div id="asset-fields-{{ $line->id }}" class="asset-extra-fields" style="display: {{ $createAssetChecked ? 'block' : 'none' }};">
                      <div class="row g-3">
                        <div class="col-md-4">
                          <label class="form-label required" for="asset-category-{{ $line->id }}">{{ __('Categoría del activo') }}</label>
                          <select class="form-select @error("lines.{$line->id}.asset_category_id") is-invalid @enderror" name="{{ $inputName }}[asset_category_id]" id="asset-category-{{ $line->id }}">
                            <option value="">{{ __('Selecciona una categoría') }}</option>
                            @foreach($assetCategories as $categoryId => $categoryName)
                              <option value="{{ $categoryId }}" {{ (string)$categoryId === old("lines.{$line->id}.asset_category_id") ? 'selected' : '' }}>{{ $categoryName }}</option>
                            @endforeach
                          </select>
                          @error("lines.{$line->id}.asset_category_id")
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-md-4">
                          <label class="form-label" for="asset-location-{{ $line->id }}">{{ __('Ubicación inicial') }}</label>
                          <select class="form-select @error("lines.{$line->id}.asset_location_id") is-invalid @enderror" name="{{ $inputName }}[asset_location_id]" id="asset-location-{{ $line->id }}">
                            <option value="">{{ __('Selecciona una ubicación') }}</option>
                            @foreach($assetLocations as $locationId => $locationName)
                              <option value="{{ $locationId }}" {{ (string)$locationId === old("lines.{$line->id}.asset_location_id") ? 'selected' : '' }}>{{ $locationName }}</option>
                            @endforeach
                          </select>
                          @error("lines.{$line->id}.asset_location_id")
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-md-4">
                          <label class="form-label" for="asset-cost-center-{{ $line->id }}">{{ __('Centro de coste') }}</label>
                          <select class="form-select @error("lines.{$line->id}.asset_cost_center_id") is-invalid @enderror" name="{{ $inputName }}[asset_cost_center_id]" id="asset-cost-center-{{ $line->id }}">
                            <option value="">{{ __('Sin centro de coste') }}</option>
                            @foreach($assetCostCenters as $costCenterId => $costCenterName)
                              <option value="{{ $costCenterId }}" {{ (string)$costCenterId === old("lines.{$line->id}.asset_cost_center_id") ? 'selected' : '' }}>{{ $costCenterName }}</option>
                            @endforeach
                          </select>
                          @error("lines.{$line->id}.asset_cost_center_id")
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-md-4">
                          <label class="form-label" for="asset-article-{{ $line->id }}">{{ __('Código de artículo (opcional)') }}</label>
                          <input type="text" class="form-control @error("lines.{$line->id}.article_code") is-invalid @enderror" name="{{ $inputName }}[article_code]" id="asset-article-{{ $line->id }}" value="{{ old("lines.{$line->id}.article_code") }}" placeholder="{{ strtoupper(Str::slug($vendorOrder->reference ?: 'order', '-')) }}-{{ $line->id }}">
                          @error("lines.{$line->id}.article_code")
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                        <div class="col-md-8">
                          <label class="form-label" for="asset-description-{{ $line->id }}">{{ __('Descripción del activo') }}</label>
                          <input type="text" class="form-control @error("lines.{$line->id}.asset_description") is-invalid @enderror" name="{{ $inputName }}[asset_description]" id="asset-description-{{ $line->id }}" value="{{ old("lines.{$line->id}.asset_description", $line->description) }}">
                          @error("lines.{$line->id}.asset_description")
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
              <input type="hidden" name="{{ $inputName }}[vendor_order_line_id]" value="{{ $line->id }}">
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('customers.vendor-orders.show', [$customer, $vendorOrder]) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Guardar recepción') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.quantity-input').forEach(function (input) {
    input.addEventListener('input', function (event) {
      const max = parseFloat(event.target.getAttribute('max')) || 0;
      const val = parseFloat(event.target.value) || 0;
      if (val > max) {
        event.target.value = max.toFixed(4);
      }
      if (val < 0) {
        event.target.value = '0.0000';
      }
    });
  });

  document.querySelectorAll('.create-asset-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function (event) {
      const targetSelector = event.target.getAttribute('data-target');
      if (!targetSelector) return;
      const container = document.querySelector(targetSelector);
      if (!container) return;
      container.style.display = event.target.checked ? 'block' : 'none';
    });
  });
</script>
@endpush
