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

          <div class="mb-3">
            <label for="cause_ids" class="form-label fw-bold">{{ __('Causas de mantenimiento') }}</label>
            <select id="cause_ids" name="cause_ids[]" class="form-select" multiple size="8">
              @foreach(($causes ?? []) as $cause)
                <option value="{{ $cause->id }}" {{ in_array($cause->id, old('cause_ids', $selectedCauseIds ?? [])) ? 'selected' : '' }}>
                  {{ $cause->name }}
                </option>
              @endforeach
            </select>
            @error('cause_ids')
              <div class="text-danger small">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="part_ids" class="form-label fw-bold">{{ __('Piezas usadas') }}</label>
            <select id="part_ids" name="part_ids[]" class="form-select" multiple size="8">
              @foreach(($parts ?? []) as $part)
                <option value="{{ $part->id }}" {{ in_array($part->id, old('part_ids', $selectedPartIds ?? [])) ? 'selected' : '' }}>
                  {{ $part->name }}
                </option>
              @endforeach
            </select>
            @error('part_ids')
              <div class="text-danger small">{{ $message }}</div>
            @enderror
          </div>

          @if($checklistTemplate && $checklistTemplate->items->count() > 0)
          <div class="mb-3">
            <label class="form-label fw-bold">
              <i class="ti ti-checklist me-1"></i>{{ __('Checklist de finalización') }}
              @if($checklistTemplate->description)
                <small class="text-muted d-block">{{ $checklistTemplate->description }}</small>
              @endif
            </label>
            <div class="card">
              <div class="card-body">
                @foreach($checklistTemplate->items as $item)
                <div class="form-check mb-2">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    name="checklist[{{ $item->id }}]" 
                    id="checklist_{{ $item->id }}" 
                    value="1"
                    {{ isset($existingResponses[$item->id]) && $existingResponses[$item->id] ? 'checked' : '' }}
                    {{ $item->required ? 'required' : '' }}
                  >
                  <label class="form-check-label" for="checklist_{{ $item->id }}">
                    {{ $item->description }}
                    @if($item->required)
                      <span class="badge bg-danger ms-1">{{ __('Obligatorio') }}</span>
                    @endif
                  </label>
                </div>
                @endforeach
              </div>
            </div>
            @error('checklist')
              <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
          </div>
          @endif

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
