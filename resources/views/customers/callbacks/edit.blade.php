@extends('layouts.admin')

@section('title', __('Editar Callback') . ' #'.$callback->id . ' - ' . $customer->name)
@section('page-title', __('Editar Callback'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.callbacks.index', $customer->id) }}">{{ __('Historial de Callbacks') }}</a></li>
    <li class="breadcrumb-item">#{{ $callback->id }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Editar Callback') }} #{{ $callback->id }}</h5>
    <a href="{{ route('customers.callbacks.index', $customer->id) }}" class="btn btn-sm btn-secondary">&larr; {{ __('Volver') }}</a>
  </div>
  <div class="card-body">
    @if ($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('customers.callbacks.update', [$customer->id, $callback->id]) }}">
      @csrf
      @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">{{ __('Callback URL') }}</label>
          <input type="url" name="callback_url" class="form-control" value="{{ old('callback_url', $callback->callback_url) }}" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('Status') }}</label>
          <select name="status" class="form-select">
            <option value="0" {{ (string)old('status', $callback->status) === '0' ? 'selected' : '' }}>{{ __('Pendiente') }}</option>
            <option value="1" {{ (string)old('status', $callback->status) === '1' ? 'selected' : '' }}>{{ __('Enviado') }}</option>
            <option value="2" {{ (string)old('status', $callback->status) === '2' ? 'selected' : '' }}>{{ __('Error') }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('Intentos') }}</label>
          <input type="number" class="form-control" value="{{ $callback->attempts ?? 0 }}" disabled />
        </div>
        <div class="col-12">
          <label class="form-label">{{ __('Payload (JSON)') }}</label>
          <textarea name="payload" rows="10" class="form-control" placeholder="{ }">{{ old('payload', $callback->payload ? json_encode($callback->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) : '') }}</textarea>
          <div class="form-text">{{ __('Si se deja vacío, se mantiene el payload actual.') }}</div>
        </div>
        <div class="col-12">
          <label class="form-label">{{ __('Último error') }}</label>
          <textarea rows="3" class="form-control" disabled>{{ $callback->error_message }}</textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Último intento') }}</label>
          <input type="text" class="form-control" value="{{ optional($callback->last_attempt_at)->format('Y-m-d H:i:s') }}" disabled />
        </div>
        <div class="col-md-4">
          <label class="form-label">{{ __('Enviado con éxito') }}</label>
          <input type="text" class="form-control" value="{{ optional($callback->success_at)->format('Y-m-d H:i:s') }}" disabled />
        </div>
        <div class="col-md-4 d-flex align-items-end justify-content-end">
          <button type="submit" class="btn btn-primary">{{ __('Guardar cambios') }}</button>
        </div>
      </div>
    </form>

    <hr/>
    @can('callbacks.force')
    <form method="POST" action="{{ route('customers.callbacks.force', [$customer->id, $callback->id]) }}" onsubmit="return confirm('{{ __('¿Forzar reintento ahora?') }}')">
      @csrf
      <button class="btn btn-dark">{{ __('Forzar reintento') }}</button>
    </form>
    @endcan

    @can('callbacks.delete')
    <form class="mt-2" method="POST" action="{{ route('customers.callbacks.destroy', [$customer->id, $callback->id]) }}" onsubmit="return confirm('{{ __('¿Eliminar callback? Esta acción no se puede deshacer.') }}')">
      @csrf
      @method('DELETE')
      <button class="btn btn-outline-danger">{{ __('Eliminar') }}</button>
    </form>
    @endcan
  </div>
</div>
@endsection
