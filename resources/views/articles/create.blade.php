@extends('layouts.admin')

@section('title', __('New Article'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('article-families.index') }}">{{ __('Article Families') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('article-families.show', $articleFamily) }}">{{ $articleFamily->name }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('New Article') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">@lang('New Article')</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('article-families.articles.store', $articleFamily) }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">@lang('Name') <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
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
                        
                        <div class="mb-3">
                            <label for="article_family_id" class="form-label">@lang('Article Family') <span class="text-danger">*</span></label>
                            <select class="form-control @error('article_family_id') is-invalid @enderror" 
                                    id="article_family_id" name="article_family_id" required>
                                <option value="">{{ __('Select an article family') }}</option>
                                @foreach($articleFamilies as $family)
                                    <option value="{{ $family->id }}" {{ $family->id == $articleFamily->id ? 'selected' : '' }}>
                                        {{ $family->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('article_family_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <a href="{{ route('article-families.articles.index', $articleFamily) }}" class="btn btn-secondary me-2">@lang('Cancel')</a>
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