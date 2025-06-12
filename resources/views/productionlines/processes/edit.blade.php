@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Editar Ordre del Procés') }}: {{ $process->name }}</h1>
        <a href="{{ route('productionlines.processes.index', $productionLine->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('Tornar') }}
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>{{ __('Codi') }}:</strong> {{ $process->code }}</p>
                    <p><strong>{{ __('Nom') }}:</strong> {{ $process->name }}</p>
                    <p><strong>{{ __('Descripció') }}:</strong> {{ $process->description }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>{{ __('Línia de Producció') }}:</strong> {{ $productionLine->name }}</p>
                    <p><strong>{{ __('Client') }}:</strong> {{ $productionLine->customer->name ?? 'N/A' }}</p>
                </div>
            </div>

            <form action="{{ route('productionlines.processes.update', [$productionLine->id, $process->id]) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label for="order">{{ __('Nou Ordre') }} *</label>
                    <input type="number" 
                           name="order" 
                           id="order" 
                           class="form-control @error('order') is-invalid @enderror" 
                           value="{{ old('order', $pivot->order) }}" 
                           min="1" 
                           required>
                    @error('order')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">
                        {{ __('Actualitzeu l\'ordre per canviar la seqüència d\'aquest procés.') }}
                    </small>
                </div>

                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ __('Actualitzar Ordre') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
