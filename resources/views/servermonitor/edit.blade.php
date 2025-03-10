@extends('layouts.admin')

@section('title')
    {{ __('Editar Servidor') }}
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <h4>{{ __('Editar Servidor') }}</h4>

        <!-- Mensajes de éxito o error -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <!-- Formulario de edición -->
        <form action="{{ route('hosts.update', $host->id) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="mb-3">
                <label for="host" class="form-label">{{ __('Host o IP') }}</label>
                <input type="text" name="host" id="host" class="form-control @error('host') is-invalid @enderror"
                       value="{{ old('host', $host->host) }}" required>
                @error('host')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Nombre') }}</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $host->name) }}" required>
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <!-- Campos opcionales de emails, phones, telegrams -->
            <div class="mb-3">
                <label for="emails" class="form-label">{{ __('Emails (separados por comas)') }}</label>
                <input type="text" name="emails" id="emails" class="form-control"
                       value="{{ old('emails', $host->emails) }}">
            </div>

            <div class="mb-3">
                <label for="phones" class="form-label">{{ __('Phones (separados por comas)') }}</label>
                <input type="text" name="phones" id="phones" class="form-control"
                       value="{{ old('phones', $host->phones) }}">
            </div>

            <div class="mb-3">
                <label for="telegrams" class="form-label">{{ __('Telegrams (separados por comas)') }}</label>
                <input type="text" name="telegrams" id="telegrams" class="form-control"
                       value="{{ old('telegrams', $host->telegrams) }}">
            </div>

            <button type="submit" class="btn btn-primary">
                {{ __('Guardar Cambios') }}
            </button>
            <a href="{{ route('servermonitor.index') }}" class="btn btn-secondary">
                {{ __('Cancelar') }}
            </a>
        </form>
    </div>
</div>
@endsection
