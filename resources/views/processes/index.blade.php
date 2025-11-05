@extends('layouts.admin')

@php
    $canEditProcesses = auth()->user()?->can('process-edit') ?? false;
@endphp

@section('title', __('Process Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Process Management') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">@lang('Gestión de Procesos')</h4>
                        @can('process-create')
                        <a href="{{ route('processes.create') }}" class="btn-add-process">
                            <i class="fas fa-plus"></i> @lang('Nuevo Proceso')
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                        </div>
                    @endif

                    @can('process-edit')
                        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <button type="button" class="btn-bulk-edit" id="bulk-edit-btn" data-bs-toggle="modal" data-bs-target="#bulkEditModal" disabled>
                                    <i class="fas fa-layer-group me-1"></i> @lang('Bulk edit selected')
                                </button>
                                <button type="button" class="btn-bulk-delete" id="bulk-delete-btn" disabled data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                                    <i class="fas fa-trash me-1"></i> @lang('Bulk delete selected')
                                </button>
                                <span class="text-muted small" id="selected-count">@lang('Selected processes: 0')</span>
                            </div>
                            <small class="text-muted">@lang('Select one or more processes to edit color, Kanban position, factor or sequence in bulk, or delete multiple processes at once.')</small>
                        </div>
                    @endcan
                    
                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="processes-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    @can('process-edit')
                                        <th class="text-center" style="width: 50px;">
                                            <input type="checkbox" id="select-all" class="form-check-input" title="@lang('Select all')">
                                        </th>
                                    @endcan
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('CÓDIGO')</th>
                                    <th class="text-uppercase">@lang('NOMBRE')</th>
                                    <th class="text-uppercase">@lang('FACTOR')</th>
                                    <th class="text-uppercase">@lang('SECUENCIA')</th>
                                    <th class="text-uppercase">@lang('COLOR')</th>
                                    <th class="text-uppercase">@lang('POS. KANBAN')</th>
                                    <th class="text-uppercase">@lang('DESCRIPCIÓN')</th>
                                    <th class="text-uppercase">@lang('ACCIONES')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($processes as $index => $process)
                                    <tr>
                                        @can('process-edit')
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input process-select" value="{{ $process->id }}"
                                                    data-name="{{ $process->name }}">
                                            </td>
                                        @endcan
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $process->code }}</td>
                                        <td>{{ $process->name }}</td>
                                        <td class="text-center">{{ number_format($process->factor_correccion, 2) }}</td>
                                        <td>{{ $process->sequence }}</td>
                                        <td class="text-center">
                                            @if($process->color)
                                                <span style="display: inline-block; width: 30px; height: 30px; background-color: {{ $process->color }}; border: 1px solid #ddd; border-radius: 4px;" title="{{ $process->color }}"></span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $process->posicion_kanban ?? '-' }}</td>
                                        <td>{{ $process->description ?? 'N/A' }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @can('process-show')
                                                <a href="{{ route('processes.show', $process) }}" class="action-btn btn-view" title="@lang('View')">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                @endcan

                                                @can('process-edit')
                                                <a href="{{ route('processes.edit', $process) }}" class="action-btn btn-edit" title="@lang('Edit')">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                @endcan

                                                @can('process-delete')
                                                <form action="{{ route('processes.destroy', $process) }}" method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="action-btn btn-delete" title="@lang('Delete')" onclick="return confirm('@lang('Are you sure you want to delete this process?')')">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @can('process-edit')
                        <form id="bulk-edit-form" action="{{ route('processes.bulk-update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="process_ids" id="process-ids-input">

                            <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title" id="bulkEditModalLabel"><i class="fas fa-layer-group me-2"></i>@lang('Bulk edit processes')</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-3 text-muted">
                                                @lang('You can update the selected fields for all chosen processes. Any field left empty will remain unchanged.')
                                            </p>
                                            <div class="alert alert-info" role="alert">
                                                <strong>@lang('Selected processes'):</strong>
                                                <span id="bulk-selected-names" class="ms-1 text-dark fw-semibold">-</span>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label for="bulk-color" class="form-label">@lang('Color')</label>
                                                    <input type="color" class="form-control form-control-color" id="bulk-color" name="color">
                                                    <small class="text-muted">@lang('Set a color for all selected processes. Leave empty to keep current colors.')</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="bulk-posicion" class="form-label">@lang('Kanban Position')</label>
                                                    <input type="number" min="1" class="form-control" id="bulk-posicion" name="posicion_kanban">
                                                    <small class="text-muted">@lang('Set a position for the Kanban board. Leave empty to keep current positions.')</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="bulk-factor" class="form-label">@lang('Correction Factor')</label>
                                                    <input type="number" step="0.01" min="0.1" class="form-control" id="bulk-factor" name="factor_correccion">
                                                    <small class="text-muted">@lang('Example: 60.00. Leave empty to avoid changing the factor.')</small>
                                                </div>

                                                <div class="col-md-6">
                                                    <label for="bulk-sequence" class="form-label">@lang('Sequence')</label>
                                                    <input type="number" min="1" class="form-control" id="bulk-sequence" name="sequence">
                                                    <small class="text-muted">@lang('Defines the process order. Leave empty to keep the current value.')</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer d-flex justify-content-between">
                                            <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>@lang('Cancel')
                                            </button>
                                            <button type="submit" class="btn-confirm-modal">
                                                <i class="fas fa-save me-1"></i>@lang('Apply changes')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endcan

                    @can('process-delete')
                        <form id="bulk-delete-form" action="{{ route('processes.bulk-delete') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="process_ids" id="bulk-delete-process-ids-input">

                            <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title" id="bulkDeleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>@lang('Confirm Bulk Delete')</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="alert alert-warning" role="alert">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>@lang('Warning:'):</strong> @lang('This action cannot be undone. All selected processes will be permanently deleted.')
                                            </div>

                                            <p class="mb-3">
                                                @lang('Are you sure you want to delete the following processes?')
                                            </p>

                                            <div class="alert alert-info" role="alert">
                                                <strong>@lang('Selected processes'):</strong>
                                                <span id="bulk-delete-selected-names" class="ms-1 text-dark fw-semibold">-</span>
                                            </div>

                                            <div class="alert alert-danger" role="alert">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>@lang('Important:'):</strong> @lang('Make sure these processes are not being used in active production lines or orders before deleting.')
                                            </div>
                                        </div>
                                        <div class="modal-footer d-flex justify-content-between">
                                            <button type="button" class="btn-cancel-modal" data-bs-dismiss="modal">
                                                <i class="fas fa-times me-1"></i>@lang('Cancel')
                                            </button>
                                            <button type="submit" class="btn-confirm-modal btn-danger">
                                                <i class="fas fa-trash me-1"></i>@lang('Delete Selected Processes')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endcan

                    <!-- Leyenda de ayuda sobre la gestión de procesos -->
                    <div class="alert alert-info" role="alert">
                        <strong>Información:</strong>
                        <div>- <strong>Color</strong>: se utiliza para identificar visualmente los procesos en el Kanban board.</div>
                        <div>- <strong>Posición Kanban</strong>: define el orden de visualización en el tablero Kanban.</div>
                        <div>- <strong>Factor de Corrección</strong>: ajuste multiplicador para tiempos de producción.</div>
                        <div>- <strong>Secuencia</strong>: orden numérico para organizar los procesos en la producción.</div>
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

        /* Botón añadir proceso */
        .btn-add-process {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-add-process::before {
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

        .btn-add-process:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-add-process:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Tabla moderna */
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin: 2rem auto;
            background: white;
        }

        #processes-table {
            margin: 0;
            border: none;
        }

        #processes-table thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        #processes-table thead th {
            border: none;
            padding: 1.2rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        #processes-table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
        }

        #processes-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        #processes-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        /* Contenedor de la tabla para consistencia con otras páginas */
        #processes-table_wrapper .dataTables_wrapper {
            padding: 1rem;
        }

        /* Botones de acción mejorados */
        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0.2rem;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-edit:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-view {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .btn-view:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        }

        .btn-edit {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .btn-edit:hover {
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-delete:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        /* Botones de bulk edit */
        .btn-bulk-edit {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }

        .btn-bulk-edit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.4);
            color: white;
        }

        .btn-bulk-edit:disabled {
            background: #6c757d;
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Botones de bulk delete */
        .btn-bulk-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
        }

        .btn-bulk-delete:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
            color: white;
        }

        .btn-bulk-delete:disabled {
            background: #6c757d;
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            border: none !important;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a02622 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4) !important;
            color: white !important;
        }

        /* Alerts modernos */
        .alert {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 5px solid;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            border-left-color: #28a745;
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border-left-color: #dc3545;
            color: #721c24;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border-left-color: #17a2b8;
            color: #0c5460;
        }

        /* Modal mejorado - Estilos específicos para los modales de esta página */
        .modal-backdrop {
            z-index: 1040 !important;
        }

        #bulkEditModal,
        #bulkDeleteModal {
            z-index: 1050 !important;
        }

        #bulkEditModal .modal-dialog,
        #bulkDeleteModal .modal-dialog {
            z-index: 1055 !important;
            position: relative;
        }

        #bulkEditModal .modal-content,
        #bulkDeleteModal .modal-content {
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
            border: none;
            position: relative;
            z-index: 1060 !important;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            color: white;
            font-weight: 700;
        }

        .btn-close-white {
            filter: brightness(0) invert(1);
        }

        /* DataTables estilos personalizados */
        .dataTables_wrapper {
            padding: 0;
        }

        .dataTables_length, .dataTables_filter {
            margin-bottom: 1rem;
        }

        .dataTables_length label, .dataTables_filter label {
            font-weight: 600;
            color: #495057;
        }

        .dataTables_length select, .dataTables_filter input {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        .dataTables_length select:focus, .dataTables_filter input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .dataTables_info {
            color: #6c757d;
            font-weight: 500;
        }

        .dataTables_paginate .paginate_button {
            border-radius: 8px;
            margin: 0 2px;
            transition: all 0.3s ease;
        }

        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white !important;
            transform: translateY(-1px);
        }

        /* Formularios del modal */
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-label {
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        /* Botones del modal */
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
        }

        .btn-cancel-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4);
            color: white;
        }

        /* Loading spinner personalizado */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animaciones para los toast notifications */
        .animated-toast {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .animated-toast.swal2-show {
            animation: slideInRight 0.3s ease-out;
        }

        .animated-toast.swal2-hide {
            animation: slideOutRight 0.3s ease-out;
        }

        @keyframes slideOutRight {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Mejorar efecto hover en todas las filas */
        #processes-table tbody tr {
            position: relative;
        }

        #processes-table tbody tr::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        #processes-table tbody tr:hover::after {
            transform: scaleX(1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }

            .action-btn {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
                margin: 0.1rem;
            }

            .btn-add-process {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }
        }

        .container-fluid.px-0 {
            width: 100%;
            max-width: 100%;
        }
        .row.mx-0 {
            margin-left: 0;
            margin-right: 0;
            width: 100%;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>

    {{-- SweetAlert2 para alertas --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const canEdit = @json($canEditProcesses);

            // ---- Limpieza de modales atascados (solo modales de esta página) ----
            // Limpiar cualquier modal o backdrop residual de los modales de esta página
            $('#bulkEditModal, #bulkDeleteModal').removeClass('show').css('display', 'none');

            // Solo eliminar backdrops si no hay otros modales activos en la página
            if ($('.modal.show').length === 0) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('body').css('padding-right', '');
                $('body').css('overflow', '');
            }

            // Forzar que los modales siempre estén por encima del backdrop
            $('#bulkEditModal, #bulkDeleteModal').on('show.bs.modal', function (e) {
                const $modal = $(this);

                // Esperar a que el backdrop se cree
                setTimeout(() => {
                    const $backdrop = $('.modal-backdrop').last();

                    // Mover el backdrop ANTES del modal en el DOM para que quede detrás visualmente
                    if ($backdrop.length) {
                        $backdrop.insertBefore($modal);
                    }

                    // Asegurar z-index correcto
                    $backdrop.css('z-index', '1040');
                    $modal.css('z-index', '1050');
                }, 50);
            });

            // También manejar cuando el modal se muestra completamente
            $('#bulkEditModal, #bulkDeleteModal').on('shown.bs.modal', function (e) {
                const $modal = $(this);
                const $backdrop = $('.modal-backdrop').last();

                // Doble verificación
                if ($backdrop.length && $backdrop.index() > $modal.index()) {
                    $backdrop.insertBefore($modal);
                }
            });

            // ---- Mejoras visuales y de experiencia de usuario ----

            // Añadir efecto de carga personalizado
            $(document).ajaxStart(function() {
                if (!$('.loading-overlay').length) {
                    $('body').append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
                }
            });

            $(document).ajaxStop(function() {
                $('.loading-overlay').fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Animación de entrada para las filas de la tabla
            $('#processes-table').on('draw.dt', function() {
                $('#processes-table tbody tr').each(function(index) {
                    $(this).css({
                        'opacity': 0,
                        'transform': 'translateY(20px)'
                    }).delay(index * 50).animate({
                        'opacity': 1,
                        'transform': 'translateY(0)'
                    }, 300);
                });
            });

            const columnDefs = [];
            const defaultOrder = [0, 'asc'];

            if (canEdit) {
                columnDefs.push(
                    {
                        orderable: false,
                        targets: [0, 6, 8, 9],
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        orderable: true,
                        targets: [1, 2, 3, 4, 5, 7],
                        className: 'text-center'
                    }
                );
                defaultOrder[0] = 1; // Ordenar por la columna de índice cuando hay checkbox
            } else {
                columnDefs.push(
                    {
                        orderable: false,
                        targets: [0, 5, 7, 8],
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        orderable: true,
                        targets: [1, 2, 3, 4, 6],
                        className: 'text-center'
                    }
                );
            }

            const table = $('#processes-table').DataTable({
                responsive: {
                    details: false // Deshabilitar detalles responsivos para evitar duplicación
                },
                scrollX: false, // Desactivar scrollX para evitar duplicación de encabezados
                pagingType: 'simple_numbers',
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('No entries to show') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    paginate: {
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: '»',
                        previous: '«'
                    },
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}",
                    infoPostFix: ""
                },
                dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 d-flex justify-content-end'p>>",
                autoWidth: false, // Evitar cálculo automático de ancho
                order: [defaultOrder],
                columnDefs: columnDefs,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('All') }}"]],
                pageLength: 10,
                initComplete: function() {
                    // Mejora el aspecto de la tabla después de inicializar
                    $('#processes-table_wrapper').addClass('pb-3');
                    $('#processes-table_length label').addClass('font-weight-normal');
                    $('#processes-table_filter label').addClass('font-weight-normal');
                    $('#processes-table_paginate').addClass('mt-3');

                    // Añade íconos a los botones de ordenación
                    setTimeout(function() {
                        // Limpiar cualquier ícono existente primero
                        $('.sorting i, .sorting_asc i, .sorting_desc i').remove();

                        // Añadir nuevos íconos
                        $('.sorting').append(' <i class="fas fa-sort text-muted"></i>');
                        $('.sorting_asc').append(' <i class="fas fa-sort-up"></i>');
                        $('.sorting_desc').append(' <i class="fas fa-sort-down"></i>');
                    }, 100);
                }
            });

            // Actualizar la tabla si hay un mensaje de éxito o error
            @if(session('success') || session('error'))
                table.draw(false);
            @endif

            if (!canEdit) {
                return;
            }

            const selectAll = $('#select-all');
            const bulkEditBtn = $('#bulk-edit-btn');
            const bulkDeleteBtn = $('#bulk-delete-btn');
            const selectedCount = $('#selected-count');
            const selectedNamesSpan = $('#bulk-selected-names');
            const bulkDeleteSelectedNamesSpan = $('#bulk-delete-selected-names');
            const processIdsContainer = $('#process-ids-input');
            const bulkDeleteProcessIdsContainer = $('#bulk-delete-process-ids-input');
            const modalElement = document.getElementById('bulkEditModal');
            const bulkDeleteModalElement = document.getElementById('bulkDeleteModal');
            const bulkForm = $('#bulk-edit-form');
            const bulkDeleteForm = $('#bulk-delete-form');

            function updateSelectionState() {
                const checkedBoxes = $('.process-select:checked');
                const totalBoxes = $('.process-select');
                const ids = checkedBoxes.map(function() { return $(this).val(); }).get();
                const names = checkedBoxes.map(function() { return $(this).data('name'); }).get();

                selectAll.prop('checked', checkedBoxes.length && checkedBoxes.length === totalBoxes.length);
                bulkEditBtn.prop('disabled', checkedBoxes.length === 0);
                bulkDeleteBtn.prop('disabled', checkedBoxes.length === 0);

                selectedCount.text("{{ __('Selected processes:') }} " + checkedBoxes.length);
                selectedNamesSpan.text(names.length ? names.join(', ') : '-');
                bulkDeleteSelectedNamesSpan.text(names.length ? names.join(', ') : '-');

                processIdsContainer.val(JSON.stringify(ids));
                bulkDeleteProcessIdsContainer.val(JSON.stringify(ids));
            }

            // Manejo de selección de filas
            $(document).on('change', '.process-select', updateSelectionState);

            selectAll.on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.process-select').prop('checked', isChecked);
                updateSelectionState();
            });

            // Limpiar inputs al cerrar el modal
            modalElement.addEventListener('hidden.bs.modal', function () {
                $('#bulk-color').val('');
                $('#bulk-posicion').val('');
                $('#bulk-factor').val('');
                $('#bulk-sequence').val('');
            });

            // Antes de enviar, transformar el valor JSON a campos ocultos tipo array
            bulkForm.on('submit', function(event) {
                const ids = JSON.parse(processIdsContainer.val() || '[]');

                if (!ids.length) {
                    event.preventDefault();
                    bulkEditBtn.prop('disabled', true);
                    return;
                }

                // Limpiar inputs anteriores
                bulkForm.find('input[name="process_ids[]"]').remove();

                ids.forEach(function(id) {
                    bulkForm.append('<input type="hidden" name="process_ids[]" value="' + id + '">');
                });

                // Cerrar el modal después de enviar
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }

                // Forzar limpieza del backdrop por si queda residuo
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }, 300);
            });

            // Manejo del formulario de bulk delete
            bulkDeleteForm.on('submit', function(event) {
                const ids = JSON.parse(bulkDeleteProcessIdsContainer.val() || '[]');

                if (!ids.length) {
                    event.preventDefault();
                    bulkDeleteBtn.prop('disabled', true);
                    return;
                }

                // Limpiar inputs anteriores
                bulkDeleteForm.find('input[name="process_ids[]"]').remove();

                ids.forEach(function(id) {
                    bulkDeleteForm.append('<input type="hidden" name="process_ids[]" value="' + id + '">');
                });

                // Cerrar el modal después de enviar
                const modal = bootstrap.Modal.getInstance(bulkDeleteModalElement);
                if (modal) {
                    modal.hide();
                }

                // Forzar limpieza del backdrop por si queda residuo
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                }, 300);
            });
        });
    </script>
@endpush
