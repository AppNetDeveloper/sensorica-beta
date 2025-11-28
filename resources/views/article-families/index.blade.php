@extends('layouts.admin')

@section('title', __('Article Families Management'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Article Families Management') }}</li>
    </ul>
@endsection

@section('content')
<div class="af-container">
    {{-- Header Principal --}}
    <div class="af-header">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="af-header-icon me-3">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h4 class="af-title mb-1">@lang('Article Families')</h4>
                        <p class="af-subtitle mb-0">@lang('Manage article families and categories')</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    @can('article-family-create')
                    <a href="{{ route('article-families.create') }}" class="af-btn af-btn-primary">
                        <i class="fas fa-plus"></i>
                        <span>@lang('New Article Family')</span>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if(session('success'))
        <div class="af-alert af-alert-success">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="af-alert af-alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        </div>
    @endif

    {{-- Tabla Card --}}
    <div class="af-table-card">
        <div class="af-table-header">
            <span class="af-table-title">
                <i class="fas fa-list"></i>
                @lang('List of Article Families')
            </span>
            <span class="af-table-count">
                {{ $articleFamilies->count() }} @lang('families')
            </span>
        </div>
        <div class="af-table-body">
            <table id="article-families-table" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>@lang('Name')</th>
                        <th>@lang('Description')</th>
                        <th>@lang('Created At')</th>
                        <th>@lang('Actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articleFamilies as $index => $articleFamily)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="af-family-name">{{ $articleFamily->name }}</span>
                            </td>
                            <td>
                                <span class="af-description">{{ $articleFamily->description ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="af-date">{{ $articleFamily->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td>
                                <div class="af-actions">
                                    @can('article-family-show')
                                    <a href="{{ route('article-families.show', $articleFamily) }}" class="af-action-btn af-action-view" title="@lang('View')">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endcan

                                    @can('article-family-edit')
                                    <a href="{{ route('article-families.edit', $articleFamily) }}" class="af-action-btn af-action-edit" title="@lang('Edit')">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan

                                    @can('article-show')
                                    <a href="{{ route('article-families.articles.index', $articleFamily) }}" class="af-action-btn af-action-articles" title="@lang('View Articles')">
                                        <i class="fas fa-boxes"></i>
                                    </a>
                                    @endcan

                                    @can('article-family-delete')
                                    <form action="{{ route('article-families.destroy', $articleFamily) }}" method="POST" style="display: inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="af-action-btn af-action-delete" title="@lang('Delete')" onclick="return confirm('@lang('Are you sure you want to delete this article family?')')">
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
</div>
@endsection

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <style>
        /* ===== Article Families - Estilo Moderno ===== */
        .af-container { padding: 0; }

        /* Header con gradiente */
        .af-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 24px;
        }
        .af-header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .af-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }
        .af-subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }

        /* Botones del header */
        .af-btn {
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
        .af-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .af-btn-primary {
            background: white;
            color: #667eea;
        }
        .af-btn-primary:hover {
            background: #f8fafc;
            color: #5a67d8;
        }

        /* Alertas */
        .af-alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .af-alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }
        .af-alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        /* Tabla Card */
        .af-table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .af-table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .af-table-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .af-table-title i { color: #667eea; }
        .af-table-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .af-table-body { padding: 0; }

        /* DataTable estilos */
        .dataTables_wrapper { padding: 20px !important; }

        #article-families-table {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 0 !important;
        }
        #article-families-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        #article-families-table tbody td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        #article-families-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Celdas especiales */
        .af-family-name {
            font-weight: 600;
            color: #1e293b;
        }
        .af-description {
            color: #64748b;
            font-size: 0.9rem;
        }
        .af-date {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Botones de acción */
        .af-actions {
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .af-action-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .af-action-btn:hover {
            transform: translateY(-2px);
        }
        .af-action-view {
            background: rgba(14, 165, 233, 0.15);
            color: #0ea5e9;
        }
        .af-action-view:hover {
            background: #0ea5e9;
            color: white;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        }
        .af-action-edit {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }
        .af-action-edit:hover {
            background: #f59e0b;
            color: white;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        .af-action-articles {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }
        .af-action-articles:hover {
            background: #22c55e;
            color: white;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        .af-action-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .af-action-delete:hover {
            background: #ef4444;
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }

        /* DataTables controles */
        .dataTables_filter input {
            border-radius: 50px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 8px 16px !important;
            transition: all 0.2s;
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
            .af-header { padding: 16px; border-radius: 12px; }
            .af-header-icon { width: 46px; height: 46px; font-size: 1.4rem; }
            .af-title { font-size: 1.2rem; }
            .af-table-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .af-action-btn { width: 30px; height: 30px; font-size: 0.8rem; }
            .af-actions { gap: 4px; }
        }
        @media (max-width: 576px) {
            .af-header { padding: 14px; }
            .af-btn { padding: 10px 16px; font-size: 0.85rem; }
            #article-families-table thead th { font-size: 0.7rem; padding: 10px 8px; }
            #article-families-table tbody td { padding: 12px 8px; font-size: 0.85rem; }
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
            $('#article-families-table').DataTable({
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
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: '<i class="fas fa-chevron-right"></i>',
                        previous: '<i class="fas fa-chevron-left"></i>'
                    },
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}"
                },
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: [4], className: 'text-center' }
                ],
                pageLength: 10
            });
        });
    </script>
@endpush
