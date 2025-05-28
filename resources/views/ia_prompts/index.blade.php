{{-- resources/views/ia_prompts/index.blade.php --}}

@extends('layouts.admin') {{-- O el layout principal que estés usando --}}

@section('title')
    {{ __('Prompts de IA') }}
@endsection

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Prompts de IA') }}</li>
    </ul>
@endsection

@push('style')
    <link rel="stylesheet" href="https://unpkg.com/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        .prompt-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
        .prompt-name { font-size: 1.1rem; font-weight: 600; }
        .prompt-key { font-size: 0.8rem; color: #6c757d; display: block; }
        .status-badge { padding: 0.25em 0.6em; font-size: 0.75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.25rem; }
        .status-badge.bg-success { background-color: #28a745; color: white; }
        .status-badge.bg-danger { background-color: #dc3545; color: white; }
        .prompt-details dt { font-weight: bold; color: #555; margin-top: 0.5rem; }
        .prompt-details dd { margin-left: 0; color: #333; word-break: break-word; white-space: pre-wrap; background-color: #f8f9fa; padding: 8px; border-radius: 4px; max-height: 150px; overflow-y: auto; font-family: monospace; font-size: 0.9em; }
        .card-actions { margin-top: auto; padding-top: 1rem; }
        .card.h-100 { display: flex; flex-direction: column; }
        .card-body { display: flex; flex-direction: column; flex-grow: 1; }
    </style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-12">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ __('Listado de Prompts de IA') }}</h3>
            </div>
            <div class="card-body">
                {{-- Se usa $prompts (plural) para la colección --}}
                @if($prompts->isEmpty())
                    <p class="text-muted">{{ __('No hay prompts configurados.') }}</p>
                @else
                    <div class="row">
                        {{-- El bucle define $prompt (singular) para cada iteración --}}
                        @foreach($prompts as $prompt)
                            <div class="col-xl-6 col-md-12 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="card-body">
                                        <div class="prompt-card-header">
                                            <div>
                                                {{-- Se usa $prompt (singular) para acceder a las propiedades --}}
                                                <h5 class="prompt-name mb-0">{{ $prompt->name }}</h5>
                                                <small class="prompt-key">(Clave: {{ $prompt->key }})</small>
                                            </div>
                                            <div>
                                                @if ($prompt->is_active)
                                                    <span class="status-badge bg-success">{{ __('Activo') }}</span>
                                                @else
                                                    <span class="status-badge bg-danger">{{ __('Inactivo') }}</span>
                                                @endif
                                            </div>
                                        </div>

                                        <dl class="prompt-details">
                                            <dt>{{ __('Modelo IA') }}:</dt>
                                            <dd>{{ $prompt->model_name ?? __('No especificado') }}</dd>

                                            <dt>{{ __('Contenido') }}:</dt>
                                            <dd>{{ Str::limit($prompt->content, 300) }}</dd>

                                            <dt>{{ __('Última Actualización') }}:</dt>
                                            <dd>{{ $prompt->updated_at->format('d/m/Y H:i:s') }}</dd>
                                        </dl>

                                        <div class="card-actions text-end mt-auto">
                                            {{-- Asegúrate que el nombre de la ruta aquí sea 'ia_prompts.edit' si eliminaste el prefijo 'admin.' de tus rutas --}}
                                            <a href="{{ route('ia_prompts.edit', $prompt->id) }}"
                                               class="btn btn-sm btn-outline-primary" title="{{ __('Editar') }}">
                                               <i class="ti ti-pencil"></i> {{ __('Editar') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
    <script>
        // ...
    </script>
@endsection
