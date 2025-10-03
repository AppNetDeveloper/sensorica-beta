@extends('layouts.admin')

@section('title', __('Editar producto de compra') . ' - ' . $customer->name)
@section('page-title', __('Editar producto de compra'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-items.index', $customer) }}">{{ __('Productos de compra') }}</a></li>
    <li class="breadcrumb-item active">{{ $item->name }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Actualizar datos del producto') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.vendor-items.update', [$customer, $item]) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">{{ __('Nombre') }}</label>
          <input type="text" name="name" value="{{ old('name', $item->name) }}" class="form-control @error('name') is-invalid @enderror" required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('SKU') }}</label>
          <input type="text" name="sku" value="{{ old('sku', $item->sku) }}" class="form-control @error('sku') is-invalid @enderror">
          @error('sku')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('Proveedor preferente') }}</label>
          <select name="vendor_supplier_id" class="form-select @error('vendor_supplier_id') is-invalid @enderror">
            <option value="">{{ __('Sin asignar') }}</option>
            @foreach($suppliers as $id => $name)
              <option value="{{ $id }}" {{ old('vendor_supplier_id', $item->vendor_supplier_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
          </select>
          @error('vendor_supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Unidad de medida') }}</label>
          <input type="text" name="unit_of_measure" value="{{ old('unit_of_measure', $item->unit_of_measure) }}" class="form-control @error('unit_of_measure') is-invalid @enderror">
          @error('unit_of_measure')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Precio unitario (€)') }}</label>
          <input type="number" step="0.0001" name="unit_price" value="{{ old('unit_price', $item->unit_price) }}" class="form-control @error('unit_price') is-invalid @enderror">
          @error('unit_price')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Lead time (días)') }}</label>
          <input type="number" name="lead_time_days" min="0" value="{{ old('lead_time_days', $item->lead_time_days) }}" class="form-control @error('lead_time_days') is-invalid @enderror">
          @error('lead_time_days')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label class="form-label">{{ __('Descripción') }}</label>
          <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $item->description) }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('customers.vendor-items.index', $customer) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Actualizar producto') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
