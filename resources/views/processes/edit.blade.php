@extends('layouts.admin')

@section('title', __('Edit Process'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('processes.index') }}">{{ __('Processes') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Edit') }}: {{ $process->name }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h5 class="card-title text-white mb-0">@lang('Edit Process'): {{ $process->name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('processes.update', $process) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="code" class="form-label">@lang('Code') <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                           id="code" name="code" value="{{ old('code', $process->code) }}" required>
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="name" class="form-label">@lang('Name') <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $process->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="sequence" class="form-label">@lang('Sequence') <span class="text-danger">*</span></label>
                                    <input type="number" min="1" class="form-control @error('sequence') is-invalid @enderror"
                                           id="sequence" name="sequence" value="{{ old('sequence', $process->sequence) }}" required>
                                    <small class="form-text text-muted">@lang('Number that defines the order of this process in the production sequence.')</small>
                                    @error('sequence')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="factor_correccion" class="form-label">@lang('Correction Factor') <span class="text-danger">*</span></label>
                                    @php
                                        $factorValue = old('factor_correccion', $process->factor_correccion);
                                        $factorValue = is_numeric($factorValue) ? number_format((float)$factorValue, 2, '.', '') : $factorValue;
                                    @endphp
                                    <input type="text" class="form-control @error('factor_correccion') is-invalid @enderror"
                                           id="factor_correccion" name="factor_correccion"
                                           value="{{ $factorValue }}" required
                                           inputmode="decimal" pattern="^\d+(\.\d{1,2})?$"
                                           title="Por favor usa punto como separador decimal (ejemplo: 1.50)">
                                    <small class="form-text text-muted">@lang('Factor used to calculate the time for this process (time = quantity * factor)')</small>
                                    @error('factor_correccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="posicion_kanban" class="form-label">@lang('Kanban Position')</label>
                                    <input type="number" min="1" class="form-control @error('posicion_kanban') is-invalid @enderror"
                                           id="posicion_kanban" name="posicion_kanban" value="{{ old('posicion_kanban', $process->posicion_kanban) }}">
                                    <small class="form-text text-muted">@lang('Position where this process will be displayed in the Kanban (leave empty to not show)')</small>
                                    @error('posicion_kanban')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="color" class="form-label">@lang('Color')</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                                               id="color" name="color" value="{{ old('color', $process->color ?? '#6c757d') }}"
                                               style="width: 80px; height: 50px;">
                                        <div>
                                            <small class="form-text text-muted">@lang('Color to represent this process in the Kanban')</small>
                                            <div id="color-preview" class="mt-1" style="width: 100px; height: 30px; border-radius: 8px; background: {{ old('color', $process->color ?? '#6c757d') }};"></div>
                                        </div>
                                    </div>
                                    @error('color')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">@lang('Description')</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4">{{ old('description', $process->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between mt-4 gap-2">
                            <a href="{{ route('processes.index') }}" class="btn-cancel-modal">
                                <i class="fas fa-times me-1"></i>@lang('Cancel')
                            </a>
                            <div class="d-flex gap-2">
                                <a href="{{ route('processes.show', $process) }}" class="btn btn-info text-white">
                                    <i class="fas fa-eye me-1"></i> @lang('View')
                                </a>
                                <button type="submit" class="btn-confirm-modal">
                                    <i class="fas fa-save me-1"></i>@lang('Save')
                                </button>
                            </div>
                        </div>
                    </form>
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

        /* Formularios mejorados */
        .form-label {
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
            background: white;
        }

        .form-control::placeholder {
            color: #6c757d;
            font-style: italic;
        }

        /* Color picker mejorado */
        .form-control-color {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-control-color:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        #color-preview {
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        #color-preview:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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

        /* Validación de formularios */
        .is-invalid {
            border-color: #dc3545;
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .form-text {
            font-size: 0.8rem;
            font-style: italic;
            color: #6c757d;
        }

        /* Efectos adicionales */
        .mb-4 {
            margin-bottom: 1.5rem;
        }

        .gap-2 {
            gap: 0.5rem;
        }

        .gap-3 {
            gap: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }

            .btn-confirm-modal, .btn-cancel-modal {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Scripts adicionales si son necesarios
        document.addEventListener('DOMContentLoaded', function() {
            // Preview del color
            const colorInput = document.getElementById('color');
            const colorPreview = document.getElementById('color-preview');

            if (colorInput && colorPreview) {
                colorInput.addEventListener('input', function() {
                    colorPreview.style.background = this.value;
                });
            }
        });
    </script>
@endpush
