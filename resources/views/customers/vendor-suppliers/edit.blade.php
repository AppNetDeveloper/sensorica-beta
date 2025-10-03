@extends('layouts.admin')

@section('title', __('Editar proveedor') . ' - ' . $customer->name)
@section('page-title', __('Editar proveedor'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-suppliers.index', $customer) }}">{{ __('Proveedores') }}</a></li>
    <li class="breadcrumb-item active">{{ $supplier->name }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Actualizar datos del proveedor') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.vendor-suppliers.update', [$customer, $supplier]) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">{{ __('Nombre') }}</label>
          <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-control @error('name') is-invalid @enderror" required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">{{ __('CIF/NIF') }}</label>
          <input type="text" name="tax_id" value="{{ old('tax_id', $supplier->tax_id) }}" class="form-control @error('tax_id') is-invalid @enderror">
          @error('tax_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">{{ __('Email') }}</label>
          <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-control @error('email') is-invalid @enderror">
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">{{ __('Teléfono') }}</label>
          <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control @error('phone') is-invalid @enderror">
          @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">{{ __('Persona de contacto') }}</label>
          <input type="text" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" class="form-control @error('contact_name') is-invalid @enderror">
          @error('contact_name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-6">
          <label class="form-label">{{ __('Condiciones de pago') }}</label>
          <input type="text" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}" class="form-control @error('payment_terms') is-invalid @enderror">
          @error('payment_terms')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
      <div class="mt-4 d-flex justify-content-between">
        <a href="{{ route('customers.vendor-suppliers.index', $customer) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Actualizar proveedor') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
