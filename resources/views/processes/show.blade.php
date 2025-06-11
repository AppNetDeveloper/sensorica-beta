@extends('layouts.admin')

@section('title', 'Detalles del Proceso')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('processes.index') }}">{{ __('Procesos') }}</a>
        </li>
        <li class="breadcrumb-item">{{ $process->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalles del Proceso</h5>
                        <div class="btn-group">
                            <a href="{{ route('processes.edit', $process) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <a href="{{ route('processes.index') }}" class="btn btn-secondary btn-sm">
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
                                    <td>{{ $process->id }}</td>
                                </tr>
                                <tr>
                                    <th>Código:</th>
                                    <td>{{ $process->code }}</td>
                                </tr>
                                <tr>
                                    <th>Nombre:</th>
                                    <td>{{ $process->name }}</td>
                                </tr>
                                <tr>
                                    <th>Orden de Secuencia:</th>
                                    <td>{{ $process->sequence }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-3">Información Adicional</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Descripción:</h6>
                                    <p class="card-text">{{ $process->description ?? 'No hay descripción disponible.' }}</p>
                                </div>
                            </div>
                            
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">Fecha de Creación:</h6>
                                    <p class="card-text">{{ $process->created_at->format('d/m/Y H:i:s') }}</p>
                                    
                                    <h6 class="card-subtitle mb-2 text-muted">Última Actualización:</h6>
                                    <p class="card-text">{{ $process->updated_at->format('d/m/Y H:i:s') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5 class="mb-3">Líneas de Producción Asociadas</h5>
                        @if($process->productionLines->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Nombre de la Línea</th>
                                            <th>Cliente</th>
                                            <th>Orden en esta Línea</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($process->productionLines as $index => $line)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $line->name }}</td>
                                                <td>{{ $line->customer->name ?? 'N/A' }}</td>
                                                <td>{{ $line->pivot->order }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                No hay líneas de producción asociadas a este proceso.
                            </div>
                        @endif
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('processes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
                        </a>
                        <div class="btn-group">
                            <a href="{{ route('processes.edit', $process) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                            <form action="{{ route('processes.destroy', $process) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" 
                                        onclick="return confirm('¿Está seguro de eliminar este proceso?')">
                                    <i class="fas fa-trash me-1"></i> Eliminar
                                </button>
                            </form>
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
