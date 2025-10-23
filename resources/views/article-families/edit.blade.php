@extends('layouts.admin')

@section('title', __('Edit Article Family'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('article-families.index') }}">{{ __('Article Families') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Edit') }}: {{ $articleFamily->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">@lang('Edit Article Family'): {{ $articleFamily->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('article-families.update', $articleFamily) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">@lang('Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $articleFamily->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">@lang('Description')</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description', $articleFamily->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('article-families.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> @lang('Cancel')
                            </a>
                            <div>
                                <a href="{{ route('article-families.show', $articleFamily) }}" class="btn btn-info text-white me-2">
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