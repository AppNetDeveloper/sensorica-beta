@extends('layouts.admin')

@section('title', __('New Process'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('processes.index') }}">{{ __('Processes') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('New Process') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">@lang('New Process')</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('processes.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="code" class="form-label">@lang('Code') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                   id="code" name="code" value="{{ old('code') }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">@lang('Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="sequence" class="form-label">@lang('Sequence') <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control @error('sequence') is-invalid @enderror" 
                                   id="sequence" name="sequence" value="{{ old('sequence') }}" required>
                            <small class="form-text text-muted">@lang('Number that defines the order of this process in the production sequence.')</small>
                            @error('sequence')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="factor_correccion" class="form-label">@lang('Correction Factor') <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control @error('factor_correccion') is-invalid @enderror" 
                                   id="factor_correccion" name="factor_correccion" value="{{ old('factor_correccion', '1.00') }}" required>
                            <small class="form-text text-muted">@lang('Factor used to calculate the time for this process (time = quantity * factor)')</small>
                            @error('factor_correccion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="color" class="form-label">@lang('Color') <span class="text-danger">*</span></label>
                            <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror" 
                                   id="color" name="color" value="{{ old('color', '#6c757d') }}" required>
                            <small class="form-text text-muted">@lang('Color to represent this process in the Kanban')</small>
                            @error('color')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="posicion_kanban" class="form-label">@lang('Kanban Position') <span class="text-danger">*</span></label>
                            <input type="number" min="1" class="form-control @error('posicion_kanban') is-invalid @enderror" 
                                   id="posicion_kanban" name="posicion_kanban" value="{{ old('posicion_kanban') }}" required>
                            <small class="form-text text-muted">@lang('Position where this process will be displayed in the Kanban (leave empty to not show)')</small>
                            @error('posicion_kanban')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">@lang('Description')</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('processes.index') }}" class="btn btn-secondary me-2">@lang('Cancel')</a>
                            <button type="submit" class="btn btn-primary">@lang('Save')</button>
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
