@extends('layouts.admin')

@section('title', __('Editar categoría de activos') . ' - ' . $customer->name)
@section('page-title', __('Editar categoría de activos'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.asset-categories.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Editar categoría de activos') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Editar categoría') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.asset-categories.update', [$customer, $assetCategory]) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <label for="name" class="form-label">{{ __('Nombre') }} <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $assetCategory->name) }}" required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="parent_id" class="form-label">{{ __('Categoría padre') }}</label>
          <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
            <option value="">{{ __('Sin padre') }}</option>
            @foreach($parents as $id => $label)
              <option value="{{ $id }}" @selected(old('parent_id', $assetCategory->parent_id) == $id)>{{ $label }}</option>
            @endforeach
          </select>
          @error('parent_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="label_code" class="form-label">{{ __('Código etiqueta (QR/Barcode)') }} <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="text" name="label_code" id="label_code" class="form-control @error('label_code') is-invalid @enderror" value="{{ old('label_code', $assetCategory->label_code) }}" required>
            <button type="button" class="btn btn-outline-secondary" id="regenerateLabelCode">{{ __('Regenerar') }}</button>
          </div>
          @error('label_code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label for="rfid_epc" class="form-label">{{ __('EPC RFID de categoría') }}</label>
          <div class="input-group">
            <input type="text" name="rfid_epc" id="rfid_epc" class="form-control @error('rfid_epc') is-invalid @enderror" value="{{ old('rfid_epc', $assetCategory->rfid_epc) }}">
            <button type="button" class="btn btn-outline-secondary" id="regenerateEpc">{{ __('Regenerar') }}</button>
          </div>
          @error('rfid_epc')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label for="description" class="form-label">{{ __('Descripción') }}</label>
          <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $assetCategory->description) }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('customers.asset-categories.index', $customer) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Actualizar') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.getElementById('regenerateLabelCode').addEventListener('click', function(){
    const base = document.getElementById('name').value.trim();
    const code = base ? `CAT-${base.toUpperCase().replace(/[^A-Z0-9]+/g, '-')}-${Date.now()}` : `CAT-${Date.now()}`;
    document.getElementById('label_code').value = code;
  });

  document.getElementById('regenerateEpc').addEventListener('click', function(){
    const randomHex = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('').toUpperCase();
    document.getElementById('rfid_epc').value = `EPC-${randomHex}`;
  });
</script>
@endpush
