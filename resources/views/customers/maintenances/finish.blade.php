@extends('layouts.admin')

@section('page-title') {{ __('Finalizar mantenimiento') }} @endsection
@section('title') {{ __('Finalizar mantenimiento') }} @endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.maintenances.index', $customer->id) }}">{{ __('Maintenances') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Finalizar') }}</li>
@endsection

@section('content')
<div class="row">
  <div class="col-12 col-md-8 col-lg-6">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Confirmar finalización') }}</h5>
      </div>
      <div class="card-body">
        <p class="text-muted mb-3">
          {{ __('Se establecerá la fecha/hora de fin a ahora y el usuario será el actual.') }}
        </p>
        <div class="mb-3">
          <label class="form-label fw-bold">{{ __('Línea de producción') }}</label>
          <div>{{ optional($maintenance->productionLine)->name }}</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">{{ __('Inicio') }}</label>
          <div>{{ optional($maintenance->start_datetime)->format('Y-m-d H:i') }}</div>
        </div>
        @if($maintenance->end_datetime)
        <div class="mb-3">
          <label class="form-label fw-bold">{{ __('Fin actual') }}</label>
          <div>{{ optional($maintenance->end_datetime)->format('Y-m-d H:i') }}</div>
        </div>
        @endif
        <form method="POST" action="{{ route('customers.maintenances.finish.store', [$customer->id, $maintenance->id]) }}">
          @csrf
          <div class="mb-3">
            <label for="annotations" class="form-label">{{ __('Notas (opcional)') }}</label>
            <textarea id="annotations" name="annotations" class="form-control" rows="4">{{ old('annotations', $maintenance->annotations) }}</textarea>
            @error('annotations')
              <div class="text-danger small">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex gap-2">
            <a href="{{ route('customers.maintenances.index', $customer->id) }}" class="btn btn-secondary">{{ __('Cancelar') }}</a>
            <button type="submit" class="btn btn-warning">{{ __('Finalizar mantenimiento ahora') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
