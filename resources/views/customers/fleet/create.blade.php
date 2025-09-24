@extends('layouts.admin')

@section('title', __('Add Vehicle') . ' - ' . $customer->name)
@section('page-title', __('Add Vehicle'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.fleet-vehicles.index', $customer->id) }}">{{ __('Fleet') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('New Vehicle') }}</h5>
    <a href="{{ route('customers.fleet-vehicles.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Back') }}</a>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.fleet-vehicles.store', $customer->id) }}" method="POST" class="row g-3">
      @csrf
      <div class="col-md-4">
        <label class="form-label">{{ __('Vehicle Type') }}</label>
        <select name="vehicle_type" class="form-select">
          <option value="">{{ __('Select type') }}</option>
          <option value="furgoneta">{{ __('Van') }}</option>
          <option value="camion">{{ __('Truck') }}</option>
          <option value="trailer">{{ __('Trailer') }}</option>
          <option value="otro">{{ __('Other') }}</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Plate') }}</label>
        <input type="text" name="plate" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Default Route') }}</label>
        <select name="default_route_name_id" class="form-select">
          <option value="">-- {{ __('Select') }} --</option>
          @foreach(($routeNames ?? []) as $r)
            <option value="{{ $r->id }}">{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Weight (kg)') }}</label>
        <input type="number" step="0.01" name="weight_kg" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Length (cm)') }}</label>
        <input type="number" step="0.01" name="length_cm" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Width (cm)') }}</label>
        <input type="number" step="0.01" name="width_cm" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Height (cm)') }}</label>
        <input type="number" step="0.01" name="height_cm" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Capacity (kg)') }}</label>
        <input type="number" step="0.01" name="capacity_kg" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Fuel Type') }}</label>
        <input type="text" name="fuel_type" class="form-control" placeholder="Diesel / Gasolina / Eléctrico">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('ITV Expiration') }}</label>
        <input type="date" name="itv_expires_at" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Insurance Expiration') }}</label>
        <input type="date" name="insurance_expires_at" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label">{{ __('Notes') }}</label>
        <textarea name="notes" class="form-control" rows="3"></textarea>
      </div>
      <div class="col-md-3 form-check mt-4 ms-2">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
        <label class="form-check-label" for="active">{{ __('Active') }}</label>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
