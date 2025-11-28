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
<div class="ps-container">
    {{-- Header Principal --}}
    <div class="ps-header">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="ps-header-icon me-3">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <h4 class="ps-title mb-1">{{ $process->name }}</h4>
                        <p class="ps-subtitle mb-0">{{ __('Process Details') }} #{{ $process->id }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    @can('process-edit')
                    <a href="{{ route('processes.edit', $process) }}" class="ps-btn ps-btn-primary">
                        <i class="fas fa-edit"></i>
                        <span>{{ __('Edit') }}</span>
                    </a>
                    @endcan
                    <a href="{{ route('processes.index') }}" class="ps-btn ps-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>{{ __('Back') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="row g-4 mb-4">
        {{-- Basic Information --}}
        <div class="col-lg-6">
            <div class="ps-info-card">
                <div class="ps-info-header">
                    <i class="fas fa-info-circle"></i>
                    <span>{{ __('Basic Information') }}</span>
                </div>
                <div class="ps-info-body">
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Code') }}</span>
                        <span class="ps-code-badge">{{ $process->code }}</span>
                    </div>
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Name') }}</span>
                        <span class="ps-info-value">{{ $process->name }}</span>
                    </div>
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Correction Factor') }}</span>
                        <span class="ps-info-value">{{ number_format($process->factor_correccion, 2) }}</span>
                    </div>
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Sequence') }}</span>
                        <span class="ps-sequence-badge">{{ $process->sequence }}</span>
                    </div>
                    @if($process->posicion_kanban)
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Kanban Position') }}</span>
                        <span class="ps-info-value">{{ $process->posicion_kanban }}</span>
                    </div>
                    @endif
                    @if($process->color)
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Color') }}</span>
                        <div class="d-flex align-items-center gap-2">
                            <span class="ps-color-badge" style="background-color: {{ $process->color }};"></span>
                            <span class="ps-info-value">{{ $process->color }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Description & Timeline --}}
        <div class="col-lg-6">
            <div class="ps-info-card mb-4">
                <div class="ps-info-header">
                    <i class="fas fa-align-left"></i>
                    <span>{{ __('Description') }}</span>
                </div>
                <div class="ps-info-body">
                    <p class="ps-description">{{ $process->description ?? __('No description available.') }}</p>
                </div>
            </div>

            <div class="ps-info-card">
                <div class="ps-info-header">
                    <i class="fas fa-clock"></i>
                    <span>{{ __('Timeline') }}</span>
                </div>
                <div class="ps-info-body">
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Created') }}</span>
                        <span class="ps-info-value">{{ $process->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="ps-info-row">
                        <span class="ps-info-label">{{ __('Last Updated') }}</span>
                        <span class="ps-info-value">{{ $process->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Production Lines --}}
    <div class="ps-table-card">
        <div class="ps-table-header">
            <span class="ps-table-title">
                <i class="fas fa-industry"></i>
                {{ __('Associated Production Lines') }}
            </span>
            <span class="ps-table-count">
                {{ $process->productionLines->count() }} {{ __('lines') }}
            </span>
        </div>
        <div class="ps-table-body">
            @if($process->productionLines->isNotEmpty())
                <table class="ps-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Line Name') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Order') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($process->productionLines as $index => $line)
                            <tr>
                                <td><span class="ps-index-badge">{{ $index + 1 }}</span></td>
                                <td><span class="ps-line-name">{{ $line->name }}</span></td>
                                <td>{{ $line->customer->name ?? 'N/A' }}</td>
                                <td><span class="ps-order-badge">{{ $line->pivot->order }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="ps-empty-state">
                    <i class="fas fa-inbox"></i>
                    <h6>{{ __('No production lines associated') }}</h6>
                    <p>{{ __('This process is not currently assigned to any production line.') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Actions Footer --}}
    <div class="ps-actions-footer">
        <a href="{{ route('processes.index') }}" class="ps-footer-btn ps-footer-back">
            <i class="fas fa-arrow-left"></i>
            {{ __('Back to List') }}
        </a>
        <div class="d-flex gap-2">
            @can('process-edit')
            <a href="{{ route('processes.edit', $process) }}" class="ps-footer-btn ps-footer-edit">
                <i class="fas fa-edit"></i>
                {{ __('Edit') }}
            </a>
            @endcan
            @can('process-delete')
            <form action="{{ route('processes.destroy', $process) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="ps-footer-btn ps-footer-delete" onclick="return confirm('{{ __('Are you sure you want to delete this process?') }}')">
                    <i class="fas fa-trash"></i>
                    {{ __('Delete') }}
                </button>
            </form>
            @endcan
        </div>
    </div>
</div>
@endsection

@push('style')
    <style>
        /* ===== Process Show - Estilo Moderno ===== */
        .ps-container { padding: 0; }

        /* Header con gradiente */
        .ps-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 24px;
        }
        .ps-header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .ps-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }
        .ps-subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }

        /* Botones del header */
        .ps-btn {
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
        .ps-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .ps-btn-primary {
            background: white;
            color: #667eea;
        }
        .ps-btn-primary:hover {
            background: #f8fafc;
            color: #5a67d8;
        }
        .ps-btn-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .ps-btn-secondary:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        /* Info Cards */
        .ps-info-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .ps-info-header {
            padding: 16px 20px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: #1e293b;
        }
        .ps-info-header i {
            color: #667eea;
        }
        .ps-info-body {
            padding: 20px;
        }
        .ps-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .ps-info-row:last-child {
            border-bottom: none;
        }
        .ps-info-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .ps-info-value {
            font-weight: 500;
            color: #1e293b;
        }

        /* Badges */
        .ps-code-badge {
            font-family: monospace;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
        }
        .ps-sequence-badge {
            background: rgba(102, 126, 234, 0.15);
            color: #667eea;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
        }
        .ps-color-badge {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: 2px solid rgba(0,0,0,0.1);
        }

        /* Description */
        .ps-description {
            color: #64748b;
            line-height: 1.6;
            margin: 0;
        }

        /* Table Card */
        .ps-table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 24px;
        }
        .ps-table-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ps-table-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ps-table-title i {
            color: #667eea;
        }
        .ps-table-count {
            background: #f1f5f9;
            color: #64748b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .ps-table-body {
            padding: 0;
        }

        /* Table */
        .ps-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ps-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 14px 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }
        .ps-table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        .ps-table tbody tr:hover {
            background: #f8fafc;
        }
        .ps-table tbody tr:last-child td {
            border-bottom: none;
        }

        .ps-index-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .ps-line-name {
            font-weight: 600;
            color: #1e293b;
        }
        .ps-order-badge {
            background: rgba(34, 197, 94, 0.15);
            color: #16a34a;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Empty State */
        .ps-empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #64748b;
        }
        .ps-empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        .ps-empty-state h6 {
            color: #334155;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .ps-empty-state p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Actions Footer */
        .ps-actions-footer {
            background: white;
            border-radius: 16px;
            padding: 20px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ps-footer-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ps-footer-btn:hover {
            transform: translateY(-2px);
        }
        .ps-footer-back {
            background: #f1f5f9;
            color: #64748b;
        }
        .ps-footer-back:hover {
            background: #e2e8f0;
            color: #334155;
        }
        .ps-footer-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .ps-footer-edit:hover {
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .ps-footer-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }
        .ps-footer-delete:hover {
            background: #ef4444;
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ps-header { padding: 16px; border-radius: 12px; }
            .ps-header-icon { width: 46px; height: 46px; font-size: 1.4rem; }
            .ps-title { font-size: 1.2rem; }
            .ps-table-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .ps-actions-footer { flex-direction: column; gap: 16px; }
            .ps-actions-footer > * { width: 100%; text-align: center; justify-content: center; }
        }
        @media (max-width: 576px) {
            .ps-header { padding: 14px; }
            .ps-btn { padding: 10px 16px; font-size: 0.85rem; }
            .ps-info-row { flex-direction: column; align-items: flex-start; gap: 6px; }
        }
    </style>
@endpush
