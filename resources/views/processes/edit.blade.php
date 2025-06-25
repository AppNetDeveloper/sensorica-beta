@extends('layouts.admin')

@section('title', __('Edit Process'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('processes.index') }}">{{ __('Processes') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Edit') }}: {{ $process->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">@lang('Edit Process'): {{ $process->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('processes.update', $process) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="code" class="form-label">@lang('Code') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                   id="code" name="code" value="{{ old('code', $process->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">@lang('Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $process->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="sequence" class="form-label">@lang('Sequence') <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control @error('sequence') is-invalid @enderror" 
                                   id="sequence" name="sequence" value="{{ old('sequence', $process->sequence) }}" required>
                            <small class="form-text text-muted">@lang('Number that defines the order of this process in the production sequence.')</small>
                            <small class="form-text text-muted">Número que define el orden de este proceso en la secuencia de producción.</small>
                            @error('sequence')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="factor_correccion" class="form-label">@lang('Correction Factor') <span class="text-danger">*</span></label>
                            @php
                                $factorValue = old('factor_correccion', $process->factor_correccion);
                                $factorValue = is_numeric($factorValue) ? number_format((float)$factorValue, 2, '.', '') : $factorValue;
                            @endphp
                            <input type="text" class="form-control @error('factor_correccion') is-invalid @enderror" 
                                   id="factor_correccion" name="factor_correccion" 
                                   value="{{ $factorValue }}" required 
                                   inputmode="decimal" pattern="^\d+(\.\d{1,2})?$" 
                                   title="Por favor usa punto como separador decimal (ejemplo: 1.50)">
                            <small class="form-text text-muted">@lang('Factor used to calculate the time for this process (time = quantity * factor)')</small>
                            @error('factor_correccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">@lang('Description')</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                     id="description" name="description" rows="3">{{ old('description', $process->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('processes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> @lang('Cancel')
                            </a>
                            <div>
                                <a href="{{ route('processes.show', $process) }}" class="btn btn-info text-white me-2">
                                    <i class="fas fa-eye me-1"></i> @lang('View')
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> @lang('Save')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Scripts adicionales si son necesarios
        document.addEventListener('DOMContentLoaded', function() {
            // Inicialización de componentes si es necesario
        });
    </script>
@endpush
