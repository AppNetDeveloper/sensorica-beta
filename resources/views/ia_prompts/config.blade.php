{{-- resources/views/ia_prompts/config.blade.php --}}

@extends('layouts.admin')

@section('title')
    {{ __('Configuración de AI') }}
@endsection

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('ia_prompts.index') }}">{{ __('Prompts de IA') }}</a></li>
        <li class="breadcrumb-item">{{ __('Configuración') }}</li>
    </ul>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">{{ __('Configuración de AI') }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('ai_config.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="ai_url" class="form-label">{{ __('URL de AI') }}</label>
                        <input type="url" class="form-control" id="ai_url" name="ai_url" value="{{ old('ai_url', $aiUrl) }}" required>
                        <div class="form-text">{{ __('Introduce la URL completa de la API de AI.') }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="ai_token" class="form-label">{{ __('Token de AI') }}</label>
                        <input type="text" class="form-control" id="ai_token" name="ai_token" value="{{ old('ai_token', $aiToken) }}" required>
                        <div class="form-text">{{ __('Introduce el token de autenticación para la API de AI.') }}</div>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('Actualizar Configuración') }}</button>
                    <a href="{{ route('ia_prompts.index') }}" class="btn btn-secondary">{{ __('Cancelar') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection