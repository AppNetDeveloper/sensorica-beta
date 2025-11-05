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
    <div class="row mt-3">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-white mb-0">@lang('Article Family Details'): {{ $articleFamily->name }}</h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('article-families.edit', $articleFamily) }}" class="btn-confirm-modal btn-sm">
                                <i class="fas fa-edit me-1"></i> @lang('Edit')
                            </a>
                            <a href="{{ route('article-families.index') }}" class="btn-cancel-modal btn-sm">
                                <i class="fas fa-list me-1"></i> @lang('Back')
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h5 class="info-card-title">@lang('Basic Information')</h5>
                                <div class="info-item">
                                    <span class="info-label">@lang('ID'):</span>
                                    <span class="info-value">{{ $articleFamily->id }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">@lang('Name'):</span>
                                    <span class="info-value">{{ $articleFamily->name }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">@lang('Description'):</span>
                                    <span class="info-value">{{ $articleFamily->description ?? __('No description available.') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <h5 class="info-card-title">@lang('Additional Information')</h5>
                                <div class="info-item">
                                    <span class="info-label">@lang('Created at'):</span>
                                    <span class="info-value">{{ $articleFamily->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">@lang('Updated at'):</span>
                                    <span class="info-value">{{ $articleFamily->updated_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5 class="section-title">@lang('Associated Articles')</h5>
                        @if($articleFamily->articles->isNotEmpty())
                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>@lang('Article Name')</th>
                                            <th>@lang('Description')</th>
                                            <th>@lang('Actions')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($articleFamily->articles as $index => $article)
                                            <tr>
                                                <td><span class="badge-primary">{{ $index + 1 }}</span></td>
                                                <td class="article-name">{{ $article->name }}</td>
                                                <td class="article-description">{{ $article->description ?? 'N/A' }}</td>
                                                <td>
                                                    <div class="action-buttons">
                                                        @can('article-show')
                                                        <a href="{{ route('article-families.articles.show', [$articleFamily, $article]) }}" class="btn-action btn-view" title="@lang('View')">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @endcan
                                                        @can('article-edit')
                                                        <a href="{{ route('article-families.articles.edit', [$articleFamily, $article]) }}" class="btn-action btn-edit" title="@lang('Edit')">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state">
                                <i class="fas fa-box-open empty-icon"></i>
                                <p class="empty-message">@lang('No articles associated with this family.')</p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4 gap-2">
                        <a href="{{ route('article-families.index') }}" class="btn-cancel-modal">
                            <i class="fas fa-arrow-left me-1"></i> @lang('Back to List')
                        </a>
                        <div class="d-flex gap-2">
                            <a href="{{ route('article-families.edit', $articleFamily) }}" class="btn-confirm-modal">
                                <i class="fas fa-edit me-1"></i> @lang('Edit')
                            </a>
                            @can('article-create')
                            <a href="{{ route('article-families.articles.create', $articleFamily) }}" class="btn-success-custom">
                                <i class="fas fa-plus me-1"></i> @lang('Add Article')
                            </a>
                            @endcan
                            @can('article-family-delete')
                            <form action="{{ route('article-families.destroy', $articleFamily) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-custom"
                                        onclick="return confirm('@lang('Are you sure you want to delete this article family?')')">
                                    <i class="fas fa-trash me-1"></i> @lang('Delete')
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

        /* Tarjetas de información */
        .info-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .info-card-title {
            font-weight: 700;
            color: #495057;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 0.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .info-value {
            font-weight: 500;
            color: #495057;
            text-align: right;
            max-width: 60%;
        }

        /* Sección de artículos */
        .section-title {
            font-weight: 700;
            color: #495057;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 0.5rem;
            display: inline-block;
        }

        /* Tabla moderna */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .modern-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .modern-table th {
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
            border: none;
        }

        .modern-table tbody tr {
            transition: all 0.3s ease;
        }

        .modern-table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .modern-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        .badge-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .article-name {
            font-weight: 600;
            color: #495057;
        }

        .article-description {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-view {
            background: #17a2b8;
            color: white;
        }

        .btn-view:hover {
            background: #138496;
            transform: translateY(-2px);
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: #212529;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
            color: #212529;
        }

        /* Estados vacíos */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            border: 2px dashed #dee2e6;
        }

        .empty-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .empty-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin: 0;
        }

        /* Botones del formulario */
        .btn-confirm-modal {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-confirm-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-cancel-modal {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }

        /* Efectos adicionales */
        .gap-2 {
            gap: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }

            .btn-confirm-modal, .btn-cancel-modal, .btn-success-custom, .btn-danger-custom {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-value {
                text-align: left;
                max-width: 100%;
                margin-top: 0.25rem;
            }

            .modern-table {
                font-size: 0.85rem;
            }

            .modern-table th, .modern-table td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>
@endpush