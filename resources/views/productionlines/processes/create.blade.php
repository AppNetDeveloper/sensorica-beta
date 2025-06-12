@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Afegir Procés a la Línia') }}: {{ $productionLine->name }}</h1>
        <a href="{{ route('productionlines.processes.index', $productionLine->id) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> {{ __('Tornar') }}
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('productionlines.processes.store', $productionLine->id) }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="process_id">{{ __('Procés') }} *</label>
                    <select name="process_id" id="process_id" class="form-control @error('process_id') is-invalid @enderror" required>
                        <option value="">{{ __('Seleccioneu un procés') }}</option>
                        @foreach($availableProcesses as $process)
                            <option value="{{ $process->id }}" {{ old('process_id') == $process->id ? 'selected' : '' }}>
                                {{ $process->name }} ({{ $process->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('process_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="order">{{ __('Ordre') }} *</label>
                    <input type="number" 
                           name="order" 
                           id="order" 
                           class="form-control @error('order') is-invalid @enderror" 
                           value="{{ old('order', 1) }}" 
                           min="1" 
                           required>
                    @error('order')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                    <small class="form-text text-muted">
                        {{ __('L\'ordre determinarà la seqüència en què es mostraran els processos.') }}
                    </small>
                </div>

                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> {{ __('Desar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
