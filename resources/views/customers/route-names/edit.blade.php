@extends('layouts.admin')

@section('title', __('Editar Nombre de Ruta') . ' - ' . $customer->name)
@section('page-title', __('Editar Nombre de Ruta'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.route-names.index', $customer->id) }}">{{ __('Nombres de Rutas') }}</a></li>
    <li class="breadcrumb-item">{{ __('Editar') }} #{{ $routeName->id }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Editar Nombre de Ruta') }} #{{ $routeName->id }}</h5>
    <a href="{{ route('customers.route-names.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Back') }}</a>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.route-names.update', [$customer->id, $routeName->id]) }}" method="POST" class="row g-3">
      @csrf
      @method('PUT')
      <div class="col-md-6">
        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ $routeName->name }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Note') }}</label>
        <input type="text" name="note" class="form-control" value="{{ $routeName->note }}">
      </div>
      <div class="col-12">
        <label class="form-label d-block mb-1">{{ __('Days of week') }}</label>
        @php($mask = (int)($routeName->days_mask ?? 0))
        <div class="d-flex flex-wrap gap-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_mon" value="1" {{ ($mask & 1) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_mon">{{ __('Monday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_tue" value="2" {{ ($mask & 2) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_tue">{{ __('Tuesday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_wed" value="4" {{ ($mask & 4) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_wed">{{ __('Wednesday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_thu" value="8" {{ ($mask & 8) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_thu">{{ __('Thursday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_fri" value="16" {{ ($mask & 16) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_fri">{{ __('Friday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_sat" value="32" {{ ($mask & 32) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_sat">{{ __('Saturday') }}</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="days[]" id="day_sun" value="64" {{ ($mask & 64) ? 'checked' : '' }}>
            <label class="form-check-label" for="day_sun">{{ __('Sunday') }}</label>
          </div>
        </div>
      </div>
      <div class="col-md-3 form-check mt-4 ms-2">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ $routeName->active ? 'checked' : '' }}>
        <label class="form-check-label" for="active">{{ __('Active') }}</label>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">{{ __('Save Changes') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
