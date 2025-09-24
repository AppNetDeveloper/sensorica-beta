@extends('layouts.admin')

@section('title', __('Crear Cliente') . ' - ' . $customer->name)
@section('page-title', __('Crear Cliente'))

@section('breadcrumb')
<ul class="breadcrumb">
  <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
  <li class="breadcrumb-item"><a href="{{ route('customers.clients.index', $customer->id) }}">{{ __('Clientes') }}</a></li>
  <li class="breadcrumb-item">{{ __('Crear') }}</li>
</ul>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Nuevo Cliente') }}</h5>
    <a href="{{ route('customers.clients.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Back') }}</a>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.clients.store', $customer->id) }}" method="POST" class="row g-3">
      @csrf
      <div class="col-md-6">
        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Address') }}</label>
        <input type="text" name="address" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Route') }}</label>
        <select name="route_name_id" class="form-select">
          <option value="">-- {{ __('Select') }} --</option>
          @foreach(($routeNames ?? []) as $r)
            <option value="{{ $r->id }}">{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Phone') }}</label>
        <input type="text" name="phone" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Email') }}</label>
        <input type="email" name="email" class="form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Tax ID') }}</label>
        <input type="text" name="tax_id" class="form-control">
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
