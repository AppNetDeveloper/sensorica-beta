@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Editar activo') . ' - ' . $customer->name)
@section('page-title', __('Editar activo'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.assets.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Editar activo') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Editar activo') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.assets.update', [$customer, $asset]) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <label for="asset_category_id" class="form-label">{{ __('Categoría') }} <span class="text-danger">*</span></label>
          <select name="asset_category_id" id="asset_category_id" class="form-select @error('asset_category_id') is-invalid @enderror" required>
            <option value="">{{ __('Seleccionar categoría') }}</option>
            @foreach($categories as $id => $label)
              <option value="{{ $id }}" {{ (string)old('asset_category_id', $asset->asset_category_id) === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('asset_category_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="asset_cost_center_id" class="form-label">{{ __('Centro de coste') }}</label>
          <select name="asset_cost_center_id" id="asset_cost_center_id" class="form-select @error('asset_cost_center_id') is-invalid @enderror">
            <option value="">{{ __('Sin centro de coste') }}</option>
            @foreach($costCenters as $id => $label)
              <option value="{{ $id }}" {{ (string)old('asset_cost_center_id', $asset->asset_cost_center_id) === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('asset_cost_center_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="asset_location_id" class="form-label">{{ __('Ubicación') }}</label>
          <select name="asset_location_id" id="asset_location_id" class="form-select @error('asset_location_id') is-invalid @enderror">
            <option value="">{{ __('Sin ubicación') }}</option>
            @foreach($locations as $id => $label)
              <option value="{{ $id }}" {{ (string)old('asset_location_id', $asset->asset_location_id) === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('asset_location_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="vendor_supplier_id" class="form-label">{{ __('Proveedor') }}</label>
          <select name="vendor_supplier_id" id="vendor_supplier_id" class="form-select @error('vendor_supplier_id') is-invalid @enderror">
            <option value="">{{ __('Sin proveedor asociado') }}</option>
            @foreach($suppliers as $id => $label)
              <option value="{{ $id }}" {{ (string)old('vendor_supplier_id', $asset->vendor_supplier_id) === (string)$id ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('vendor_supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="article_code" class="form-label">{{ __('Código de artículo') }} <span class="text-danger">*</span></label>
          <input type="text" name="article_code" id="article_code" class="form-control @error('article_code') is-invalid @enderror" value="{{ old('article_code', $asset->article_code) }}" required>
          @error('article_code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="label_code" class="form-label">{{ __('Código etiqueta (QR/Barcode)') }} <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="text" name="label_code" id="label_code" class="form-control @error('label_code') is-invalid @enderror" value="{{ old('label_code', $asset->label_code) }}" required>
            <button type="button" class="btn btn-outline-secondary" id="regenerateLabelCode">{{ __('Regenerar') }}</button>
          </div>
          @error('label_code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label for="description" class="form-label">{{ __('Descripción') }} <span class="text-danger">*</span></label>
          <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" required>{{ old('description', $asset->description) }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label for="status" class="form-label">{{ __('Estado') }} <span class="text-danger">*</span></label>
          <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach($statuses as $statusOption)
              <option value="{{ $statusOption }}" {{ old('status', $asset->status) === $statusOption ? 'selected' : '' }}>{{ __($statusOption) }}</option>
            @endforeach
          </select>
          @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label for="acquired_at" class="form-label">{{ __('Fecha de adquisición') }}</label>
          <input type="date" name="acquired_at" id="acquired_at" class="form-control @error('acquired_at') is-invalid @enderror" value="{{ old('acquired_at', optional($asset->acquired_at)->format('Y-m-d')) }}">
          @error('acquired_at')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label d-block">{{ __('Etiqueta RFID') }}</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="has_rfid_tag" name="has_rfid_tag" value="1" {{ old('has_rfid_tag', $asset->has_rfid_tag) ? 'checked' : '' }}>
            <label class="form-check-label" for="has_rfid_tag">{{ __('Este activo tiene etiqueta RFID') }}</label>
          </div>
        </div>
        <div class="col-md-6" id="rfid_tid_wrapper">
          <label for="rfid_tid" class="form-label">{{ __('RFID TID (único)') }}</label>
          <div class="input-group">
            <input type="text" name="rfid_tid" id="rfid_tid" class="form-control @error('rfid_tid') is-invalid @enderror" value="{{ old('rfid_tid', $asset->rfid_tid) }}">
            <button type="button" class="btn btn-outline-secondary" id="regenerateTid">{{ __('Regenerar') }}</button>
          </div>
          @error('rfid_tid')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6" id="rfid_epc_wrapper">
          <label for="rfid_epc" class="form-label">{{ __('RFID EPC (categoría)') }}</label>
          <div class="input-group">
            <input type="text" name="rfid_epc" id="rfid_epc" class="form-control @error('rfid_epc') is-invalid @enderror" value="{{ old('rfid_epc', $asset->rfid_epc) }}">
            <button type="button" class="btn btn-outline-secondary" id="regenerateEpc">{{ __('Regenerar') }}</button>
          </div>
          @error('rfid_epc')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label for="attributes" class="form-label">{{ __('Atributos adicionales (JSON)') }}</label>
          <textarea name="attributes" id="attributes" rows="3" class="form-control @error('attributes') is-invalid @enderror" placeholder='{"color":"Rojo","grosor":"16mm"}'>{{ old('attributes', $asset->attributes ? json_encode($asset->attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
          @error('attributes')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('customers.assets.index', $customer) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Actualizar activo') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const hasRfidToggle = document.getElementById('has_rfid_tag');
  const tidWrapper = document.getElementById('rfid_tid_wrapper');
  const epcWrapper = document.getElementById('rfid_epc_wrapper');

  function toggleRfidFields() {
    const visible = hasRfidToggle.checked;
    tidWrapper.classList.toggle('d-none', !visible);
    epcWrapper.classList.toggle('d-none', !visible);
  }

  hasRfidToggle.addEventListener('change', toggleRfidFields);
  toggleRfidFields();

  document.getElementById('regenerateLabelCode').addEventListener('click', function(){
    const categorySelect = document.getElementById('asset_category_id');
    const selectedText = categorySelect.options[categorySelect.selectedIndex]?.text || 'CAT';
    const slug = selectedText.toUpperCase().replace(/[^A-Z0-9]+/g, '-');
    document.getElementById('label_code').value = `${slug}-${Date.now()}`;
  });

  document.getElementById('regenerateTid').addEventListener('click', function(){
    const randomHex = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
    document.getElementById('rfid_tid').value = `TID-${randomHex}`;
  });

  document.getElementById('regenerateEpc').addEventListener('click', function(){
    const randomHex = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
    document.getElementById('rfid_epc').value = `EPC-${randomHex}`;
  });
</script>
@endpush
