@extends('layouts.admin')

@section('title', 'Detalles del Artículo')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('article-families.index') }}">{{ __('Familias de Artículos') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('article-families.show', $articleFamily) }}">{{ $articleFamily->name }}</a>
        </li>
        <li class="breadcrumb-item">{{ $article->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalles del Artículo</h5>
                        <div class="btn-group">
                            <a href="{{ route('article-families.articles.edit', [$articleFamily, $article]) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="{{ route('article-families.articles.index', $articleFamily) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-list me-1"></i> Volver
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="mb-3">Información Básica</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="w-50">ID:</th>
                                    <td>{{ $article->id }}</td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td>{{ $article->name }}</td>
                                </tr>
                                <tr>
                                    <th>Descripción:</th>
                                    <td>{{ $article->description ?? 'No hay descripción disponible.' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Información de la Familia</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Familia de Artículos:</h6>
                                    <p class="card-text">
                                        <a href="{{ route('article-families.show', $articleFamily) }}" class="text-decoration-none">
                                            {{ $articleFamily->name }}
                                        </a>
                                    </p>
                                    @if($articleFamily->description)
                                        <h6 class="card-subtitle mb-2 text-muted">Descripción de la Familia:</h6>
                                        <p class="card-text">{{ $articleFamily->description }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Fecha de Creación:</h6>
                                    <p class="card-text">{{ $article->created_at->format('d/m/Y H:i:s') }}</p>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">Última Actualización:</h6>
                                    <p class="card-text">{{ $article->updated_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('article-families.articles.index', $articleFamily) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('article-families.articles.edit', [$articleFamily, $article]) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            @can('article-delete')
                            <form action="{{ route('article-families.articles.destroy', [$articleFamily, $article]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('¿Está seguro de eliminar este artículo?')">
                                    <i class="fas fa-trash me-1"></i> Eliminar
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .card {
            margin-bottom: 1.5rem;
        }
        .card-header h5 {
            font-weight: 600;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
@endpush