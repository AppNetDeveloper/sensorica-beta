@extends('layouts.admin')

@section('title', __('Editar pieza de mantenimiento') . ' - ' . $customer->name)
@section('page-title', __('Editar pieza de mantenimiento'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.maintenance-parts.index', $customer->id) }}">{{ __('Piezas de mantenimiento') }}</a></li>
    <li class="breadcrumb-item active">{{ __('Editar') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Editar pieza') }}</h5>
    <a href="{{ route('customers.maintenance-parts.index', $customer->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('Volver') }}</a>
  </div>
  <div class="card-body">
    <form method="POST" action="{{ route('customers.maintenance-parts.update', [$customer->id, $part->id]) }}" class="row g-3">
      @csrf
      @method('PUT')
      <div class="col-md-6">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $part->name) }}" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $part->code) }}">
      </div>
      <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="description" rows="3" class="form-control">{{ old('description', $part->description) }}</textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">{{ __('Production line') }}</label>
        <select name="production_line_id" class="form-select">
          <option value="" {{ old('production_line_id', $part->production_line_id) ? '' : 'selected' }}>{{ __('Global (todas las líneas)') }}</option>
          @foreach($lines as $line)
            <option value="{{ $line->id }}" {{ (string)old('production_line_id', $part->production_line_id) === (string)$line->id ? 'selected' : '' }}>
              {{ $line->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 form-check form-switch mt-3 ms-2">
        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', $part->active) ? 'checked' : '' }}>
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
