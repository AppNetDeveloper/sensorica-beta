@extends('layouts.admin')

@section('title', __('Editar centro de coste') . ' - ' . $customer->name)
@section('page-title', __('Editar centro de coste'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.asset-cost-centers.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Editar centro de coste') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Editar centro de coste') }}</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.asset-cost-centers.update', [$customer, $assetCostCenter]) }}" method="POST">
      @csrf
      @method('PUT')
      <div class="row g-3">
        <div class="col-md-4">
          <label for="code" class="form-label">{{ __('Código') }} <span class="text-danger">*</span></label>
          <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $assetCostCenter->code) }}" required>
          @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-8">
          <label for="name" class="form-label">{{ __('Nombre') }} <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $assetCostCenter->name) }}" required>
          @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12">
          <label for="description" class="form-label">{{ __('Descripción') }}</label>
          <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', $assetCostCenter->description) }}</textarea>
          @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('customers.asset-cost-centers.index', $customer) }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Actualizar') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
