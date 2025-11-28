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
<div class="pr-container">
    {{-- Header Principal --}}
    <div class="pr-header">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="pr-header-icon me-3">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div>
                        <h4 class="pr-title mb-1">@lang('Gestión de Procesos')</h4>
                        <p class="pr-subtitle mb-0">@lang('Manage production processes and settings')</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    @can('process-create')
                    <a href="{{ route('processes.create') }}" class="pr-btn pr-btn-primary">
                        <i class="fas fa-plus"></i>
                        <span>@lang('Nuevo Proceso')</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="pr-alert pr-alert-success">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="pr-alert pr-alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Bulk Actions --}}
    @can('process-edit')
    <div class="pr-bulk-actions">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <button type="button" class="pr-bulk-btn pr-bulk-edit" id="bulk-edit-btn" data-bs-toggle="modal" data-bs-target="#bulkEditModal" disabled>
                <i class="fas fa-layer-group"></i> @lang('Bulk edit selected')
            </button>
            <button type="button" class="pr-bulk-btn pr-bulk-delete" id="bulk-delete-btn" disabled data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                <i class="fas fa-trash"></i> @lang('Bulk delete selected')
            </button>
            <span class="pr-selected-count" id="selected-count">@lang('Selected processes: 0')</span>
        </div>
    </div>
    @endcan

    {{-- Tabla Card --}}
    <div class="pr-table-card">
        <div class="pr-table-header">
            <span class="pr-table-title">
                <i class="fas fa-list"></i>
                @lang('List of Processes')
            </span>
            <span class="pr-table-count">
                {{ $processes->count() }} @lang('processes')
            </span>
        </div>
        <div class="pr-table-body">
            <table id="processes-table" class="table" style="width:100%">
                <thead>
                    <tr>
                        @can('process-edit')
                            <th style="width: 50px;">
                                <input type="checkbox" id="select-all" class="form-check-input" title="@lang('Select all')">
                            </th>
                        @endcan
                        <th>#</th>
                        <th>@lang('Code')</th>
                        <th>@lang('Name')</th>
                        <th>@lang('Factor')</th>
                        <th>@lang('Sequence')</th>
                        <th>@lang('Color')</th>
                        <th>@lang('Kanban Pos.')</th>
                        <th>@lang('Description')</th>
                        <th>@lang('Actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($processes as $index => $process)
                        <tr>
                            @can('process-edit')
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input process-select" value="{{ $process->id }}" data-name="{{ $process->name }}">
                                </td>
                            @endcan
                            <td>{{ $index + 1 }}</td>
                            <td><span class="pr-code">{{ $process->code }}</span></td>
                            <td><span class="pr-name">{{ $process->name }}</span></td>
                            <td class="text-center">{{ number_format($process->factor_correccion, 2) }}</td>
                            <td class="text-center">{{ $process->sequence }}</td>
                            <td class="text-center">
                                @if($process->color)
                                    <span class="pr-color-badge" style="background-color: {{ $process->color }};" title="{{ $process->color }}"></span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $process->posicion_kanban ?? '-' }}</td>
                            <td><span class="pr-description">{{ $process->description ?? '-' }}</span></td>
                            <td>
                                <div class="pr-actions">
                                    @can('process-show')
                                    <a href="{{ route('processes.show', $process) }}" class="pr-action-btn pr-action-view" title="@lang('View')">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan

                                    @can('process-edit')
                                    <a href="{{ route('processes.edit', $process) }}" class="pr-action-btn pr-action-edit" title="@lang('Edit')">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan

                                    @can('process-delete')
                                    <form action="{{ route('processes.destroy', $process) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="pr-action-btn pr-action-delete" title="@lang('Delete')" onclick="return confirm('@lang('Are you sure you want to delete this process?')')">
                                            <i class="fas fa-trash"></i>
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
    </div>

    {{-- Info Card --}}
    <div class="pr-info-card">
        <div class="pr-info-title"><i class="fas fa-info-circle me-2"></i>@lang('Information')</div>
        <div class="pr-info-items">
            <div><strong>@lang('Color')</strong>: @lang('Used to visually identify processes in the Kanban board.')</div>
            <div><strong>@lang('Kanban Position')</strong>: @lang('Defines display order on the Kanban board.')</div>
            <div><strong>@lang('Correction Factor')</strong>: @lang('Multiplier adjustment for production times.')</div>
            <div><strong>@lang('Sequence')</strong>: @lang('Numeric order to organize processes in production.')</div>
        </div>
    </div>

    {{-- Modal Bulk Edit --}}
    @can('process-edit')
    <form id="bulk-edit-form" action="{{ route('processes.bulk-update') }}" method="POST">
        @csrf
        <input type="hidden" name="process_ids" id="process-ids-input">
        <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content pr-modal">
                    <div class="modal-header pr-modal-header">
                        <h5 class="modal-title"><i class="fas fa-layer-group me-2"></i>@lang('Bulk edit processes')</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">@lang('Update selected fields for all chosen processes. Empty fields will remain unchanged.')</p>
                        <div class="pr-modal-alert">
                            <strong>@lang('Selected processes'):</strong>
                            <span id="bulk-selected-names">-</span>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label">@lang('Color')</label>
                                <input type="color" class="form-control form-control-color" id="bulk-color" name="color">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">@lang('Kanban Position')</label>
                                <input type="number" min="1" class="form-control" id="bulk-posicion" name="posicion_kanban">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">@lang('Correction Factor')</label>
                                <input type="number" step="0.01" min="0.1" class="form-control" id="bulk-factor" name="factor_correccion">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">@lang('Sequence')</label>
                                <input type="number" min="1" class="form-control" id="bulk-sequence" name="sequence">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pr-modal-btn pr-modal-cancel" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>@lang('Cancel')
                        </button>
                        <button type="submit" class="pr-modal-btn pr-modal-confirm">
                            <i class="fas fa-save me-1"></i>@lang('Apply changes')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endcan

    {{-- Modal Bulk Delete --}}
    @can('process-delete')
    <form id="bulk-delete-form" action="{{ route('processes.bulk-delete') }}" method="POST">
        @csrf
        @method('DELETE')
        <input type="hidden" name="process_ids" id="bulk-delete-process-ids-input">
        <div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-labelledby="bulkDeleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content pr-modal">
                    <div class="modal-header pr-modal-header pr-modal-header-danger">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>@lang('Confirm Bulk Delete')</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="pr-modal-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            @lang('This action cannot be undone. All selected processes will be permanently deleted.')
                        </div>
                        <p class="mt-3">@lang('Are you sure you want to delete the following processes?')</p>
                        <div class="pr-modal-alert">
                            <strong>@lang('Selected processes'):</strong>
                            <span id="bulk-delete-selected-names">-</span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="pr-modal-btn pr-modal-cancel" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>@lang('Cancel')
                        </button>
                        <button type="submit" class="pr-modal-btn pr-modal-danger">
                            <i class="fas fa-trash me-1"></i>@lang('Delete Selected')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endcan
</div>
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        /* ===== Processes - Estilo Moderno ===== */
        .pr-container { padding: 0; }

        /* Header con gradiente */
        .pr-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 24px;
        }
        .pr-header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .pr-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }
        .pr-subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }

        /* Botones del header */
        .pr-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .pr-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .pr-btn-primary {
            background: white;
            color: #667eea;
        }
        .pr-btn-primary:hover {
            background: #f8fafc;
            color: #5a67d8;
        }

        /* Alertas */
        .pr-alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .pr-alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .pr-alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Bulk Actions */
        .pr-bulk-actions {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .pr-bulk-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pr-bulk-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pr-bulk-edit {
            background: rgba(102, 126, 234, 0.15);
            color: #667eea;
        }
        .pr-bulk-edit:hover:not(:disabled) {
            background: #667eea;
            color: white;
        }
        .pr-bulk-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .pr-bulk-delete:hover:not(:disabled) {
            background: #ef4444;
            color: white;
        }
        .pr-selected-count {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Tabla Card */
        .pr-table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .pr-table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pr-table-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pr-table-title i { color: #667eea; }
        .pr-table-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .pr-table-body { padding: 0; }

        /* DataTable estilos */
        .dataTables_wrapper { padding: 20px !important; }

        #processes-table {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 0 !important;
        }
        #processes-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 14px 12px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #processes-table tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        #processes-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Celdas especiales */
        .pr-code {
            font-family: monospace;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.85rem;
        }
        .pr-name {
            font-weight: 600;
            color: #1e293b;
        }
        .pr-description {
            color: #64748b;
            font-size: 0.85rem;
        }
        .pr-color-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 2px solid rgba(0,0,0,0.1);
        }

        /* Botones de acción */
        .pr-actions {
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .pr-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.8rem;
        }
        .pr-action-btn:hover {
            transform: translateY(-2px);
        }
        .pr-action-view {
            background: rgba(14, 165, 233, 0.15);
            color: #0ea5e9;
        }
        .pr-action-view:hover {
            background: #0ea5e9;
            color: white;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        }
        .pr-action-edit {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }
        .pr-action-edit:hover {
            background: #f59e0b;
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        .pr-action-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .pr-action-delete:hover {
            background: #ef4444;
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* Info Card */
        .pr-info-card {
            background: #eff6ff;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #3b82f6;
        }
        .pr-info-title {
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 12px;
        }
        .pr-info-items {
            display: flex;
            flex-direction: column;
            gap: 8px;
            color: #1e3a5f;
            font-size: 0.9rem;
        }

        /* Modal */
        .pr-modal {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }
        .pr-modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 20px;
        }
        .pr-modal-header-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .pr-modal-header .modal-title {
            color: white;
            font-weight: 700;
        }
        .pr-modal-alert {
            background: #eff6ff;
            border-radius: 10px;
            padding: 14px;
            color: #1e40af;
        }
        .pr-modal-warning {
            background: #fef3c7;
            border-radius: 10px;
            padding: 14px;
            color: #92400e;
        }
        .pr-modal-btn {
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pr-modal-cancel {
            background: #f1f5f9;
            color: #64748b;
        }
        .pr-modal-cancel:hover {
            background: #e2e8f0;
        }
        .pr-modal-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .pr-modal-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .pr-modal-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .pr-modal-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        /* DataTables controles */
        .dataTables_filter input {
            border-radius: 50px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 8px 16px !important;
        }
        .dataTables_filter input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
            outline: none;
        }
        .dataTables_length select {
            border-radius: 10px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 6px 12px !important;
        }
        .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            margin: 0 2px !important;
            padding: 8px 14px !important;
            border: none !important;
            background: #f1f5f9 !important;
            color: #64748b !important;
            font-weight: 600 !important;
        }
        .dataTables_paginate .paginate_button:hover:not(.disabled) {
            background: #e2e8f0 !important;
            color: #334155 !important;
        }
        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }
        .dataTables_info {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .pr-header { padding: 16px; border-radius: 12px; }
            .pr-header-icon { width: 46px; height: 46px; font-size: 1.4rem; }
            .pr-title { font-size: 1.2rem; }
            .pr-table-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .pr-action-btn { width: 28px; height: 28px; font-size: 0.75rem; }
            .pr-actions { gap: 4px; }
            .pr-bulk-actions { flex-direction: column; }
        }
        @media (max-width: 576px) {
            .pr-header { padding: 14px; }
            .pr-btn { padding: 10px 16px; font-size: 0.85rem; }
            #processes-table thead th { font-size: 0.65rem; padding: 10px 6px; }
            #processes-table tbody td { padding: 10px 6px; font-size: 0.8rem; }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            const canEdit = @json($canEditProcesses);

            // Limpiar modales residuales
            $('#bulkEditModal, #bulkDeleteModal').removeClass('show').css('display', 'none');
            if ($('.modal.show').length === 0) {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({'padding-right': '', 'overflow': ''});
            }

            const columnDefs = [];
            const defaultOrder = [0, 'asc'];

            if (canEdit) {
                columnDefs.push(
                    { orderable: false, targets: [0, 6, 8, 9], searchable: false, className: 'text-center' },
                    { orderable: true, targets: [1, 2, 3, 4, 5, 7], className: 'text-center' }
                );
                defaultOrder[0] = 1;
            } else {
                columnDefs.push(
                    { orderable: false, targets: [0, 5, 7, 8], searchable: false, className: 'text-center' },
                    { orderable: true, targets: [1, 2, 3, 4, 6], className: 'text-center' }
                );
            }

            const table = $('#processes-table').DataTable({
                responsive: true,
                dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('No entries to show') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    paginate: {
                        next: '<i class="fas fa-chevron-right"></i>',
                        previous: '<i class="fas fa-chevron-left"></i>'
                    },
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}"
                },
                order: [defaultOrder],
                columnDefs: columnDefs,
                pageLength: 10
            });

            if (!canEdit) return;

            const selectAll = $('#select-all');
            const bulkEditBtn = $('#bulk-edit-btn');
            const bulkDeleteBtn = $('#bulk-delete-btn');
            const selectedCount = $('#selected-count');
            const selectedNamesSpan = $('#bulk-selected-names');
            const bulkDeleteSelectedNamesSpan = $('#bulk-delete-selected-names');
            const processIdsContainer = $('#process-ids-input');
            const bulkDeleteProcessIdsContainer = $('#bulk-delete-process-ids-input');
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

            $(document).on('change', '.process-select', updateSelectionState);

            selectAll.on('change', function() {
                $('.process-select').prop('checked', $(this).is(':checked'));
                updateSelectionState();
            });

            document.getElementById('bulkEditModal').addEventListener('hidden.bs.modal', function() {
                $('#bulk-color, #bulk-posicion, #bulk-factor, #bulk-sequence').val('');
            });

            bulkForm.on('submit', function(event) {
                const ids = JSON.parse(processIdsContainer.val() || '[]');
                if (!ids.length) { event.preventDefault(); return; }
                bulkForm.find('input[name="process_ids[]"]').remove();
                ids.forEach(id => bulkForm.append('<input type="hidden" name="process_ids[]" value="' + id + '">'));
                bootstrap.Modal.getInstance(document.getElementById('bulkEditModal'))?.hide();
                setTimeout(() => { $('.modal-backdrop').remove(); $('body').removeClass('modal-open').css('padding-right', ''); }, 300);
            });

            bulkDeleteForm.on('submit', function(event) {
                const ids = JSON.parse(bulkDeleteProcessIdsContainer.val() || '[]');
                if (!ids.length) { event.preventDefault(); return; }
                bulkDeleteForm.find('input[name="process_ids[]"]').remove();
                ids.forEach(id => bulkDeleteForm.append('<input type="hidden" name="process_ids[]" value="' + id + '">'));
                bootstrap.Modal.getInstance(document.getElementById('bulkDeleteModal'))?.hide();
                setTimeout(() => { $('.modal-backdrop').remove(); $('body').removeClass('modal-open').css('padding-right', ''); }, 300);
            });
        });
    </script>
@endpush
