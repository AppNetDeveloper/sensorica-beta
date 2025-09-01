@extends('layouts.admin')

@section('title', __('Crear causa de mantenimiento') . ' - ' . $customer->name)
@section('page-title', __('Crear causa de mantenimiento'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.maintenance-causes.index', $customer->id) }}">{{ __('Causas de mantenimiento') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Crear') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Nueva causa') }}</h5>
    <a href="{{ route('customers.maintenance-causes.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Volver') }}</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('customers.maintenance-causes.store', $customer->id) }}" class="row g-3">
      @csrf
      <div class="col-md-6">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" class="form-control" value="{{ old('code') }}">
      </div>
      <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
      </div>
      <div class="col-12">
        <label class="form-label">{{ __('Production lines (optional)') }}</label>
        <select name="production_line_ids[]" class="form-select" multiple size="6">
          @foreach($lines as $line)
            <option value="{{ $line->id }}" {{ collect(old('production_line_ids', []))->contains($line->id) ? 'selected' : '' }}>
              {{ $line->name }}
            </option>
          @endforeach
        </select>
        <small class="text-muted d-block mt-1">
          {{ __('Si no seleccionas ninguna línea, la causa será Global (aplicable a todas las líneas). Si seleccionas varias líneas, se crearán varias causas (una por línea).') }}
        </small>
      </div>
      <div class="col-md-3 form-check form-switch mt-3 ms-2">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
        <label class="form-check-label" for="active">{{ __('Active') }}</label>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <button type="button" class="btn btn-outline-secondary ms-2" onclick="history.back(); return false;">{{ __('Atrás') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
