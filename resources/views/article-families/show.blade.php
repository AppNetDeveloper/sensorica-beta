@extends('layouts.admin')

@section('title', 'Detalles de la Familia de Artículos')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('article-families.index') }}">{{ __('Familias de Artículos') }}</a>
        </li>
        <li class="breadcrumb-item">{{ $articleFamily->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalles de la Familia de Artículos</h5>
                        <div class="btn-group">
                            <a href="{{ route('article-families.edit', $articleFamily) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="{{ route('article-families.index') }}" class="btn btn-secondary btn-sm">
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
                                    <td>{{ $articleFamily->id }}</td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td>{{ $articleFamily->name }}</td>
                                </tr>
                                <tr>
                                    <th>Descripción:</th>
                                    <td>{{ $articleFamily->description ?? 'No hay descripción disponible.' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Información Adicional</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Fecha de Creación:</h6>
                                    <p class="card-text">{{ $articleFamily->created_at->format('d/m/Y H:i:s') }}</p>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">Última Actualización:</h6>
                                    <p class="card-text">{{ $articleFamily->updated_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5 class="mb-3">Artículos Asociados</h5>
                        @if($articleFamily->articles->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre del Artículo</th>
                                            <th>Descripción</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($articleFamily->articles as $index => $article)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $article->name }}</td>
                                                <td>{{ $article->description ?? 'N/A' }}</td>
                                                <td>
                                                    @can('article-show')
                                                    <a href="{{ route('article-families.articles.show', [$articleFamily, $article]) }}" class="btn btn-sm btn-info" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @endcan
                                                    @can('article-edit')
                                                    <a href="{{ route('article-families.articles.edit', [$articleFamily, $article]) }}" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                No hay artículos asociados a esta familia.
                            </div>
                        @endif
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('article-families.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('article-families.edit', $articleFamily) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            @can('article-create')
                            <a href="{{ route('article-families.articles.create', $articleFamily) }}" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i> Agregar Artículo
                            </a>
                            @endcan
                            @can('article-family-delete')
                            <form action="{{ route('article-families.destroy', $articleFamily) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('¿Está seguro de eliminar esta familia de artículos?')">
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