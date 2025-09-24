@extends('layouts.admin')

@section('title', __('Crear Nombre de Ruta') . ' - ' . $customer->name)
@section('page-title', __('Crear Nombre de Ruta'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.route-names.index', $customer->id) }}">{{ __('Nombres de Rutas') }}</a></li>
    <li class="breadcrumb-item">{{ __('Crear') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Nuevo Nombre de Ruta') }}</h5>
    <a href="{{ route('customers.route-names.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Back') }}</a>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.route-names.store', $customer->id) }}" method="POST" class="row g-3">
      @csrf
      <div class="col-md-6">
        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Note') }}</label>
        <input type="text" name="note" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label d-block mb-1">{{ __('Days of week') }}</label>
        <div class="d-flex flex-wrap gap-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_mon" value="1">
            <label class="form-check-label" for="day_mon">{{ __('Monday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_tue" value="2">
            <label class="form-check-label" for="day_tue">{{ __('Tuesday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_wed" value="4">
            <label class="form-check-label" for="day_wed">{{ __('Wednesday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_thu" value="8">
            <label class="form-check-label" for="day_thu">{{ __('Thursday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_fri" value="16">
            <label class="form-check-label" for="day_fri">{{ __('Friday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_sat" value="32">
            <label class="form-check-label" for="day_sat">{{ __('Saturday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_sun" value="64">
            <label class="form-check-label" for="day_sun">{{ __('Sunday') }}</label>
          </div>
        </div>
      </div>
      <div class="col-md-3 form-check mt-4 ms-2">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" checked>
        <label class="form-check-label" for="active">{{ __('Active') }}</label>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
