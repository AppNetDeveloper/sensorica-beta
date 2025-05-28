@extends('layouts.admin') {{-- O el layout principal que estés usando --}}

@section('title')
    {{ __('Editar Prompt de IA') }}: {{ $iaPrompt->name }}
@endsection

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        {{-- Nombre de ruta sin 'admin.' --}}
        <li class="breadcrumb-item"><a href="{{ route('ia_prompts.index') }}">{{ __('Prompts de IA') }}</a></li>
        <li class="breadcrumb-item">{{ __('Editar Prompt') }}</li>
    </ul>
@endsection

@push('style')
    <link rel="stylesheet" href="[https://unpkg.com/@tabler/icons-webfont@latest/tabler-icons.min.css](https://unpkg.com/@tabler/icons-webfont@latest/tabler-icons.min.css)">
    <style>
        .form-control-plaintext { padding-top: .375rem; padding-bottom: .375rem; margin-bottom: 0; line-height: 1.5; background-color: transparent; border: solid transparent; border-width: 1px 0; color: #6c757d; }
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Editando Prompt') }}: <em>{{ $iaPrompt->name }}</em></h3>
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

                {{-- Nombre de ruta sin 'admin.' --}}
                <form action="{{ route('ia_prompts.update', $iaPrompt->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="key" class="form-label">{{ __('Clave (Key)') }}</label>
                        <input type="text" id="key" name="key" class="form-control-plaintext" value="{{ $iaPrompt->key }}" readonly>
                        <small class="form-text text-muted">{{__('La clave no se puede modificar.')}}</small>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">{{ __('Nombre Descriptivo') }} <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $iaPrompt->name) }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">{{ __('Contenido del Prompt') }} <span class="text-danger">*</span></label>
                        <textarea id="content" name="content" class="form-control @error('content') is-invalid @enderror" rows="15" required>{{ old('content', $iaPrompt->content) }}</textarea>
                        @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                         <small class="form-text text-muted">Puedes usar placeholders como <code>{{ "{{ placeholderName "}}}}</code> que serán reemplazados dinámicamente.</small>
                    </div>

                    <div class="mb-3">
                        <label for="model_name" class="form-label">{{ __('Nombre del Modelo IA (Opcional)') }}</label>
                        <input type="text" id="model_name" name="model_name" class="form-control @error('model_name') is-invalid @enderror" value="{{ old('model_name', $iaPrompt->model_name) }}">
                        @error('model_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" id="is_active" name="is_active" class="form-check-input" value="1" {{ old('is_active', $iaPrompt->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">{{ __('Activo') }}</label>
                        @error('is_active') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> {{ __('Actualizar Prompt') }}</button>
                        {{-- Nombre de ruta sin 'admin.' --}}
                        <a href="{{ route('ia_prompts.index') }}" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> {{ __('Cancelar y Volver al Listado') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
