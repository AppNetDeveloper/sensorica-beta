@extends('layouts.admin')

@section('title', __('Process Details'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('processes.index') }}">{{ __('Processes') }}</a>
        </li>
        <li class="breadcrumb-item">{{ $process->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-10 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-white mb-0">{{ __('Process Details') }}: {{ $process->name }}</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('processes.edit', $process) }}" class="btn-modern btn-primary">
                                <i class="fas fa-edit me-1"></i> {{ __('Edit') }}
                            </a>
                            <a href="{{ route('processes.index') }}" class="btn-modern btn-secondary">
                                <i class="fas fa-list me-1"></i> {{ __('Back to Processes') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Información Principal en Tarjetas Modernas -->
                    <div class="row mb-5">
                        <div class="col-md-6 mb-4">
                            <div class="info-card-modern">
                                <div class="info-card-header">
                                    <i class="fas fa-info-circle"></i>
                                    <h6 class="mb-0">{{ __('Basic Information') }}</h6>
                                </div>
                                <div class="info-card-body">
                                    <div class="info-item">
                                        <span class="info-label">{{ __('ID') }}:</span>
                                        <span class="info-value">{{ $process->id }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Code') }}:</span>
                                        <span class="info-value">{{ $process->code }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Name') }}:</span>
                                        <span class="info-value">{{ $process->name }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Correction Factor') }}:</span>
                                        <span class="info-value">{{ number_format($process->factor_correccion, 2) }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Sequence') }}:</span>
                                        <span class="info-value">{{ $process->sequence }}</span>
                                    </div>
                                    @if($process->posicion_kanban)
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Kanban Position') }}:</span>
                                        <span class="info-value">{{ $process->posicion_kanban }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="info-card-modern">
                                <div class="info-card-header">
                                    <i class="fas fa-align-left"></i>
                                    <h6 class="mb-0">{{ __('Description') }}</h6>
                                </div>
                                <div class="info-card-body">
                                    <p class="description-text">
                                        {{ $process->description ?? __('No description available.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="info-card-modern mt-3">
                                <div class="info-card-header">
                                    <i class="fas fa-clock"></i>
                                    <h6 class="mb-0">{{ __('Timeline Information') }}</h6>
                                </div>
                                <div class="info-card-body">
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Created') }}:</span>
                                        <span class="info-value">{{ $process->created_at->format('d/m/Y H:i:s') }}</span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Last Updated') }}:</span>
                                        <span class="info-value">{{ $process->updated_at->format('d/m/Y H:i:s') }}</span>
                                    </div>
                                    @if($process->color)
                                    <div class="info-item">
                                        <span class="info-label">{{ __('Color') }}:</span>
                                        <span class="color-indicator" style="background-color: {{ $process->color }};"></span>
                                        <span class="info-value">{{ $process->color }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Líneas de Producción Asociadas -->
                    <div class="mt-4">
                        <h5 class="section-title mb-4">
                            <i class="fas fa-industry me-2"></i>
                            {{ __('Associated Production Lines') }}
                        </h5>
                        @if($process->productionLines->isNotEmpty())
                            <div class="table-responsive-modern">
                                <table class="table-modern">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag me-1"></i> #</th>
                                            <th><i class="fas fa-tag me-1"></i> {{ __('Line Name') }}</th>
                                            <th><i class="fas fa-user me-1"></i> {{ __('Customer') }}</th>
                                            <th><i class="fas fa-sort me-1"></i> {{ __('Order in this Line') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($process->productionLines as $index => $line)
                                            <tr class="table-row-hover">
                                                <td><span class="badge-modern">{{ $index + 1 }}</span></td>
                                                <td>{{ $line->name }}</td>
                                                <td>{{ $line->customer->name ?? 'N/A' }}</td>
                                                <td><span class="sequence-badge">{{ $line->pivot->order }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-modern">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <h6>{{ __('No production lines associated with this process.') }}</h6>
                                <p class="text-muted">{{ __('This process is not currently assigned to any production line.') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Botones de Acción -->
                    <div class="d-flex justify-content-between mt-5 pt-4 border-top">
                        <a href="{{ route('processes.index') }}" class="btn-modern btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to List') }}
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('processes.edit', $process) }}" class="btn-modern btn-primary">
                                <i class="fas fa-edit me-1"></i> {{ __('Edit') }}
                            </a>
                            <form action="{{ route('processes.destroy', $process) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-modern btn-danger"
                                        onclick="return confirm('{{ __('Are you sure you want to delete this process?') }}')">
                                    <i class="fas fa-trash me-1"></i> {{ __('Delete') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    {{-- Font Awesome para iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Estilos modernos para la página */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Card principal con glassmorfismo */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }

        .card-title {
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Breadcrumb moderno */
        .breadcrumb {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: #764ba2;
            transform: translateX(3px);
        }

        .breadcrumb-item.active {
            color: #6c757d;
            font-weight: 600;
        }

        /* Tarjetas de información modernas */
        .info-card-modern {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(31, 38, 135, 0.1);
            transition: all 0.3s ease;
            animation: fadeInScale 0.6s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .info-card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(31, 38, 135, 0.2);
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid rgba(102, 126, 234, 0.2);
        }

        .info-card-header i {
            color: #667eea;
            font-size: 1.2rem;
        }

        .info-card-header h6 {
            color: #495057;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            margin: 0;
        }

        .info-card-body {
            color: #495057;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item:hover {
            background: rgba(102, 126, 234, 0.05);
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            margin-left: -0.5rem;
            margin-right: -0.5rem;
            border-radius: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-weight: 500;
            color: #495057;
            font-size: 0.95rem;
        }

        .description-text {
            color: #495057;
            line-height: 1.6;
            font-size: 0.95rem;
            margin: 0;
        }

        .color-indicator {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 0.5rem;
            vertical-align: middle;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Sección de título */
        .section-title {
            color: #495057;
            font-weight: 700;
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            padding-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }

        /* Tabla moderna */
        .table-responsive-modern {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(31, 38, 135, 0.1);
            overflow: hidden;
        }

        .table-modern {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-modern thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
            position: relative;
        }

        .table-modern thead th:first-child {
            border-top-left-radius: 12px;
        }

        .table-modern thead th:last-child {
            border-top-right-radius: 12px;
        }

        .table-modern tbody tr {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .table-row-hover:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.01);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15);
        }

        .table-modern tbody td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            color: #495057;
            font-weight: 500;
        }

        .badge-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .sequence-badge {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
        }

        .sequence-badge:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: scale(1.05);
        }

        /* Estado vacío moderno */
        .empty-state-modern {
            text-align: center;
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(31, 38, 135, 0.1);
            color: #6c757d;
        }

        .empty-state-modern i {
            color: rgba(102, 126, 234, 0.3);
            opacity: 0.5;
        }

        .empty-state-modern h6 {
            color: #495057;
            font-weight: 600;
            margin: 1rem 0 0.5rem 0;
        }

        /* Botones modernos */
        .btn-modern {
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-modern:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-modern.btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-modern.btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-modern.btn-secondary {
            background: #6c757d;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }

        .btn-modern.btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .btn-modern.btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-modern.btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }

        /* Efectos adicionales */
        .gap-2 {
            gap: 0.5rem;
        }

        .pt-4 {
            padding-top: 2rem;
        }

        .border-top {
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }

            .btn-modern {
                padding: 0.6rem 1rem;
                font-size: 0.85rem;
            }

            .info-card-modern {
                margin-bottom: 1rem;
            }

            .table-responsive-modern {
                padding: 1rem;
            }

            .section-title {
                font-size: 1.1rem;
            }
        }

        /* Animaciones de entrada */
        .info-card-modern:nth-child(1) {
            animation-delay: 0.1s;
        }

        .info-card-modern:nth-child(2) {
            animation-delay: 0.2s;
        }

        .table-responsive-modern {
            animation-delay: 0.3s;
        }

        .empty-state-modern {
            animation-delay: 0.3s;
        }
    </style>
@endpush
