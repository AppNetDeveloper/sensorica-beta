@extends('layouts.admin')

@section('title', __('Editar Cliente') . ' - ' . $customer->name)
@section('page-title', __('Editar Cliente'))

@section('breadcrumb')
<ul class="breadcrumb">
  <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
  <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
  <li class="breadcrumb-item"><a href="{{ route('customers.clients.index', $customer->id) }}">{{ __('Clientes') }}</a></li>
  <li class="breadcrumb-item">{{ __('Editar') }}</li>
</ul>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Editar Cliente') }} #{{ $client->id }}</h5>
    <a href="{{ route('customers.clients.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Back') }}</a>
  </div>
  <div class="card-body">
    <form action="{{ route('customers.clients.update', [$customer->id, $client->id]) }}" method="POST" class="row g-3">
      @csrf
      @method('PUT')
      <div class="col-md-6">
        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ $client->name }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Address') }}</label>
        <input type="text" name="address" class="form-control" value="{{ $client->address }}">
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Route') }}</label>
        <select name="route_name_id" class="form-select">
          <option value="">-- {{ __('Select') }} --</option>
          @foreach(($routeNames ?? []) as $r)
            <option value="{{ $r->id }}" {{ (int)$client->route_name_id === (int)$r->id ? 'selected' : '' }}>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Phone') }}</label>
        <input type="text" name="phone" class="form-control" value="{{ $client->phone }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Email') }}</label>
        <input type="email" name="email" class="form-control" value="{{ $client->email }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Tax ID') }}</label>
        <input type="text" name="tax_id" class="form-control" value="{{ $client->tax_id }}">
      </div>
      <div class="col-md-3 form-check mt-4 ms-2">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ $client->active ? 'checked' : '' }}>
        <label class="form-check-label" for="active">{{ __('Active') }}</label>
      </div>
      <div class="col-12">
        <button class="btn btn-primary" type="submit">{{ __('Save Changes') }}</button>
      </div>
    </form>
  </div>
</div>
@endsection
