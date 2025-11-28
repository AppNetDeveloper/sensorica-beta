@extends('layouts.admin')

@section('title', 'Estadísticas de Líneas de Producción')

@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        /* ===== NUEVO DISEÑO MODERNO ===== */

        /* Container */
        .ls-container { padding: 0; }

        /* Header con gradiente */
        .ls-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 24px;
        }

        .ls-header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        .ls-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }

        .ls-subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
        }

        /* Filtros Card */
        .ls-filters-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .ls-filters-card .form-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .ls-filters-card .form-control,
        .ls-filters-card .form-select {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 10px 14px;
            transition: all 0.2s;
        }

        .ls-filters-card .form-control:focus,
        .ls-filters-card .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        /* Botones del header */
        .ls-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .ls-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .ls-btn-primary {
            background: white;
            color: #667eea;
        }
        .ls-btn-primary:hover {
            background: #f8fafc;
            color: #5a67d8;
        }

        .ls-btn-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .ls-btn-secondary:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        /* Stats Cards (KPIs) */
        .ls-stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .ls-stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }

        .ls-stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .ls-stats-success { background: rgba(34, 197, 94, 0.15); color: #22c55e; }
        .ls-stats-primary { background: rgba(102, 126, 234, 0.15); color: #667eea; }
        .ls-stats-warning { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .ls-stats-secondary { background: rgba(100, 116, 139, 0.15); color: #64748b; }
        .ls-stats-danger { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

        .ls-stats-info h4 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        .ls-stats-info span {
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Tabla Card */
        .ls-table-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .ls-table-header {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .ls-table-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ls-table-title i {
            color: #667eea;
        }

        /* Checkboxes modernos */
        .ls-check-group {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .ls-check-group .form-check {
            margin: 0;
        }

        .ls-check-group .form-check-input {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid #cbd5e1;
        }

        .ls-check-group .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .ls-check-group .form-check-label {
            font-size: 0.85rem;
            color: #64748b;
        }

        /* Toolbar botones */
        .ls-toolbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .ls-toolbar .btn {
            border-radius: 50px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .ls-ai-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        .ls-ai-btn:hover {
            opacity: 0.9;
            color: white;
        }

        /* DataTable estilos */
        .ls-table-body {
            padding: 0;
        }

        .dataTables_wrapper {
            padding: 20px !important;
        }

        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            margin: 0 !important;
        }

        table.dataTable thead th {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 14px 12px;
            border-bottom: 2px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        table.dataTable tbody td {
            padding: 14px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        table.dataTable tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .dataTables_filter input {
            border-radius: 50px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 8px 16px !important;
        }

        .dataTables_filter input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
        }

        .dataTables_paginate .paginate_button {
            border-radius: 8px !important;
            margin: 0 2px !important;
        }

        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            border: none !important;
            color: white !important;
        }

        /* Select2 personalizado */
        .select2-container--default .select2-selection--multiple {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            min-height: 44px;
            padding: 4px 8px;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #667eea;
            border: none;
            color: white;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.85rem;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 6px;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* ===== DataTable Badges y Elementos ===== */

        /* OEE Badge */
        .ls-oee-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .ls-oee-success { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
        .ls-oee-warning { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .ls-oee-danger { background: rgba(239, 68, 68, 0.15); color: #dc2626; }

        /* Status Badge */
        .ls-status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .ls-status-active { background: #dcfce7; color: #16a34a; }
        .ls-status-paused { background: #fef3c7; color: #d97706; }
        .ls-status-error { background: #fee2e2; color: #dc2626; }
        .ls-status-completed { background: #dbeafe; color: #2563eb; }
        .ls-status-progress { background: #e0f2fe; color: #0284c7; }
        .ls-status-pending { background: #f1f5f9; color: #64748b; }

        /* Time Badge */
        .ls-time-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
            font-family: 'Monaco', 'Consolas', monospace;
        }
        .ls-time-positive { background: #dcfce7; color: #16a34a; }
        .ls-time-negative { background: #fee2e2; color: #dc2626; }
        .ls-time-neutral { background: #f1f5f9; color: #64748b; }

        /* Action Button */
        .ls-action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .ls-action-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        /* Search Input */
        .ls-search-input {
            border-radius: 50px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 8px 16px !important;
            transition: all 0.2s;
        }
        .ls-search-input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15) !important;
        }

        /* Length Select */
        .ls-length-select {
            border-radius: 10px !important;
            border: 2px solid #e2e8f0 !important;
            padding: 6px 12px !important;
        }

        /* ===== DataTables Responsive ===== */

        /* Control column (expand icon) */
        td.dtr-control {
            position: relative;
            cursor: pointer;
        }
        td.dtr-control::before {
            content: '\f105';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            display: inline-block;
            width: 28px;
            height: 28px;
            line-height: 28px;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        tr.parent td.dtr-control::before {
            content: '\f107';
            background: #64748b;
        }
        td.dtr-control:hover::before {
            transform: scale(1.1);
        }

        /* Child row (expanded content) */
        .ls-child-row {
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            margin: 8px 0;
        }
        .ls-child-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .ls-child-item:last-child {
            border-bottom: none;
        }
        .ls-child-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.85rem;
        }
        .ls-child-value {
            color: #1e293b;
            font-weight: 500;
        }

        /* Pagination */
        .dataTables_paginate .paginate_button {
            border-radius: 10px !important;
            margin: 0 3px !important;
            padding: 8px 14px !important;
            border: none !important;
            background: #f1f5f9 !important;
            color: #64748b !important;
            font-weight: 600 !important;
            transition: all 0.2s !important;
        }
        .dataTables_paginate .paginate_button:hover:not(.disabled) {
            background: #e2e8f0 !important;
            color: #334155 !important;
        }
        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
        }
        .dataTables_paginate .paginate_button.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }

        /* Info text */
        .dataTables_info {
            color: #64748b;
            font-size: 0.9rem;
            padding-top: 12px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .ls-header { padding: 16px; }
            .ls-header-icon { width: 46px; height: 46px; font-size: 1.4rem; }
            .ls-title { font-size: 1.2rem; }
            .ls-stats-card { padding: 14px; }
            .ls-stats-icon { width: 42px; height: 42px; font-size: 1.2rem; }
            .ls-stats-info h4 { font-size: 1.2rem; }
            .ls-table-header { flex-direction: column; align-items: flex-start; }
            .ls-toolbar { width: 100%; justify-content: flex-start; margin-top: 12px; }
            .ls-check-group { flex-wrap: wrap; }

            /* DataTable mobile */
            table.dataTable thead { display: none; }
            table.dataTable tbody td {
                padding: 12px 8px !important;
                font-size: 0.9rem;
            }
            .ls-action-btn { width: 32px; height: 32px; }
            .ls-oee-badge, .ls-status-badge { font-size: 0.75rem; padding: 4px 8px; }
        }

        @media (max-width: 576px) {
            .ls-header { border-radius: 12px; padding: 14px; }
            .ls-filters-card { padding: 14px; border-radius: 12px; }
            .ls-table-card { border-radius: 12px; }
            .ls-stats-card { border-radius: 10px; }
            .row.g-3 > [class*="col-"] { padding-left: 6px; padding-right: 6px; }
        }

        /* ===== ESTILOS PARA MODAL DE RESULTADOS IA ===== */
        /* Contenido del resultado */
        .ai-result-content {
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            transition: font-size 0.2s ease;
            max-height: 65vh;
            overflow-y: auto;
            padding: 1rem;
            background: white;
            border-radius: 8px;
        }

        /* Tablas Markdown con estilos Bootstrap */
        .ai-result-content table {
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 6px;
            overflow: hidden;
        }

        .ai-result-content table thead th {
            background-color: #0d6efd;
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            border: none;
            text-align: left;
            white-space: nowrap;
        }

        .ai-result-content table tbody td {
            padding: 0.65rem 0.75rem;
            border-top: 1px solid #dee2e6;
            vertical-align: top;
        }

        .ai-result-content table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .ai-result-content table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.15s ease-in-out;
        }

        /* Encabezados */
        .ai-result-content h1, .ai-result-content h2, .ai-result-content h3,
        .ai-result-content h4, .ai-result-content h5, .ai-result-content h6 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #212529;
        }

        .ai-result-content h1 { font-size: 1.8rem; border-bottom: 2px solid #0d6efd; padding-bottom: 0.3rem; }
        .ai-result-content h2 { font-size: 1.5rem; color: #0d6efd; }
        .ai-result-content h3 { font-size: 1.3rem; color: #495057; }
        .ai-result-content h4 { font-size: 1.1rem; }
        .ai-result-content h5 { font-size: 1rem; }

        /* Listas */
        .ai-result-content ul, .ai-result-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .ai-result-content li {
            margin-bottom: 0.35rem;
        }

        /* Párrafos y separadores */
        .ai-result-content p {
            margin-bottom: 1rem;
        }

        .ai-result-content hr {
            margin: 1.5rem 0;
            border: none;
            border-top: 2px solid #e9ecef;
        }

        /* Código */
        .ai-result-content code {
            background-color: #f8f9fa;
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: #d63384;
        }

        .ai-result-content pre {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            border-left: 4px solid #0d6efd;
        }

        .ai-result-content pre code {
            background: none;
            padding: 0;
            color: #212529;
        }

        /* Blockquotes */
        .ai-result-content blockquote {
            padding: 0.5rem 1rem;
            margin: 1rem 0;
            border-left: 4px solid #0dcaf0;
            background-color: #f8f9fa;
            font-style: italic;
        }

        /* Enlaces */
        .ai-result-content a {
            color: #0d6efd;
            text-decoration: none;
        }

        .ai-result-content a:hover {
            text-decoration: underline;
        }

        /* Barra de progreso de scroll */
        .scroll-progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #0d6efd 0%, #0dcaf0 100%);
            width: 0%;
            transition: width 0.1s ease;
            z-index: 1050;
            border-radius: 0 2px 2px 0;
        }

        /* Botón volver arriba */
        #btnScrollTop {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 45px;
            height: 45px;
            border-radius: 50% !important;
            background: #0d6efd;
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1055;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #btnScrollTop.show {
            opacity: 1;
            visibility: visible;
        }

        #btnScrollTop:hover {
            background: #0b5ed7;
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4);
        }

        /* Controles de fuente */
        .font-controls .btn {
            font-family: monospace;
            font-weight: bold;
            min-width: 36px;
        }

        /* Modal en fullscreen personalizado */
        .modal-fullscreen-custom {
            max-width: 100% !important;
            width: 100% !important;
            height: 100vh;
            margin: 0 !important;
        }

        .modal-fullscreen-custom .modal-content {
            height: 100vh;
            border-radius: 0 !important;
        }

        .modal-fullscreen-custom .ai-result-content {
            max-height: calc(100vh - 200px);
        }

        /* Tabs personalizados */
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-radius: 6px 6px 0 0;
            color: #6c757d;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-tabs .nav-link:hover {
            border-color: #e9ecef #e9ecef #dee2e6;
            background-color: #f8f9fa;
            color: #495057;
        }

        .nav-tabs .nav-link.active {
            background-color: white;
            border-color: #dee2e6 #dee2e6 #fff;
            color: #0d6efd;
        }

        /* Toolbar de acciones */
        .ai-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .ai-toolbar .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        /* Toast personalizado */
        .copy-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #198754;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Metadatos del análisis */
        .ai-metadata {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .ai-metadata i {
            color: #0d6efd;
        }

        /* Responsive ajustes */
        @media (max-width: 768px) {
            .modal-dialog[style*="80%"] {
                max-width: 95% !important;
                width: 95% !important;
            }

            .ai-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .ai-toolbar .btn-group {
                width: 100%;
                justify-content: center;
            }

            .ai-result-content {
                font-size: 0.9rem;
            }

            .ai-result-content table {
                font-size: 0.85rem;
            }

            #btnScrollTop {
                bottom: 20px;
                right: 20px;
                width: 40px;
                height: 40px;
            }
        }

    </style>
@endpush

@section('content')
<div class="ls-container">
    {{-- Header Principal --}}
    <div class="ls-header">
        <div class="row align-items-center">
            <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                <div class="d-flex align-items-center">
                    <div class="ls-header-icon me-3">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h4 class="ls-title mb-1">Estadísticas de Producción</h4>
                        <p class="ls-subtitle mb-0">Análisis OEE y tiempos de líneas</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12">
                <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    <button type="button" class="ls-btn ls-btn-primary" id="fetchData">
                        <i class="fas fa-search"></i>
                        <span>Buscar</span>
                    </button>
                    <button type="button" class="ls-btn ls-btn-secondary" id="resetFilters">
                        <i class="fas fa-undo"></i>
                        <span>Restablecer</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtros Card --}}
    <div class="ls-filters-card">
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Líneas de Producción</label>
                <select id="modbusSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Empleado</label>
                <select id="operatorSelect" class="form-select select2-multiple" multiple style="width: 100%;">
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Fecha Inicio</label>
                <input type="datetime-local" class="form-control" id="startDate">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Fecha Fin</label>
                <input type="datetime-local" class="form-control" id="endDate">
            </div>
        </div>
    </div>

    {{-- KPIs Cards --}}
    <div class="row g-3 mb-4">
        {{-- OEE Promedio --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-success">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="avgOEE">0%</h4>
                    <span>Promedio OEE</span>
                </div>
            </div>
        </div>
        {{-- Total Duración --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-primary">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="totalDuration">00:00:00</h4>
                    <span>Total Duración</span>
                </div>
            </div>
        </div>
        {{-- Diferencia --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-warning">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="totalTheoretical" title="Suma neta: tiempo ganado menos tiempo de más">00:00:00</h4>
                    <span>Total Diferencia</span>
                </div>
            </div>
        </div>
        {{-- Preparación --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-secondary">
                    <i class="fas fa-hand-paper"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="totalPrepairTime">00:00:00</h4>
                    <span>Total Preparación</span>
                </div>
            </div>
        </div>
        {{-- Tiempo Lento --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-warning">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="totalSlowTime">00:00:00</h4>
                    <span>Tiempo Lento</span>
                </div>
            </div>
        </div>
        {{-- Paradas --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-danger">
                    <i class="fas fa-stop-circle"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="totalProductionStopsTime">00:00:00</h4>
                    <span>Paradas</span>
                </div>
            </div>
        </div>
        {{-- Falta Material --}}
        <div class="col-xl col-lg-3 col-md-4 col-sm-6">
            <div class="ls-stats-card">
                <div class="ls-stats-icon ls-stats-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="ls-stats-info">
                    <h4 id="totalDownTime">00:00:00</h4>
                    <span>Falta Material</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de Datos --}}
    <div class="ls-table-card">
        <div class="ls-table-header">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="ls-table-title">
                    <i class="fas fa-table"></i>
                    Registros de Producción
                </span>
                <div class="ls-check-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="hideZeroOEE">
                        <label class="form-check-label" for="hideZeroOEE">Ocultar 0% OEE</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="hide100OEE">
                        <label class="form-check-label" for="hide100OEE">Ocultar 100% OEE</label>
                    </div>
                </div>
            </div>
            <div class="ls-toolbar">
                        @php($aiUrl = config('services.ai.url'))
                        @php($aiToken = config('services.ai.token'))
                        @if(!empty($aiUrl) && !empty($aiToken))
                        <div class="btn-group btn-group-sm me-2" role="group">
                            <button type="button" class="btn btn-dark dropdown-toggle position-relative" data-bs-toggle="dropdown" aria-expanded="false" title="@lang('Análisis con IA')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; font-weight: 600;">
                                <i class="bi bi-stars me-1"></i>
                                <span class="d-none d-sm-inline">@lang('Análisis IA')</span>
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.65em;">PRO</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 380px; max-height: 600px; overflow-y: auto;">
                                <li><h6 class="dropdown-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: -0.5rem -0.5rem 0.5rem -0.5rem; padding: 0.75rem 1rem;">
                                    <i class="fas fa-brain me-2"></i>{{ __("Análisis Inteligente de OEE") }}
                                    <span class="badge bg-warning text-dark ms-2" style="font-size: 0.7em;">PRO</span>
                                </h6></li>

                                <!-- SECCIÓN 1: Análisis de OEE -->
                                <li><h6 class="dropdown-header text-primary"><i class="fas fa-chart-line me-1"></i> {{ __("OEE y Rendimiento") }}</h6></li>
                                <li><a class="dropdown-item" href="#" data-analysis="oee-general">
                                    <i class="fas fa-chart-line text-success me-2"></i>{{ __("Análisis General de OEE") }}
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="performance">
                                    <i class="fas fa-tachometer-alt text-primary me-2"></i>{{ __("Rendimiento por Línea") }}
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="availability-performance">
                                    <i class="fas fa-exchange-alt text-primary me-2"></i>{{ __("Disponibilidad vs Rendimiento") }}
                                </a></li>

                                <li><hr class="dropdown-divider"></li>

                                <!-- SECCIÓN 2: Paradas y Tiempo -->
                                <li><h6 class="dropdown-header text-danger"><i class="fas fa-pause-circle me-1"></i> {{ __("Paradas y Tiempo Improductivo") }}</h6></li>
                                <li><a class="dropdown-item" href="#" data-analysis="stops">
                                    <i class="fas fa-pause-circle text-danger me-2"></i>{{ __("Análisis de Paradas") }}
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="idle-time">
                                    <i class="fas fa-hourglass-half text-danger me-2"></i>{{ __("Consumo de Tiempo Improductivo") }}
                                </a></li>

                                <li><hr class="dropdown-divider"></li>

                                <!-- SECCIÓN 3: Operadores y Turnos -->
                                <li><h6 class="dropdown-header text-info"><i class="fas fa-users me-1"></i> {{ __("Operadores y Turnos") }}</h6></li>
                                <li><a class="dropdown-item" href="#" data-analysis="operators">
                                    <i class="fas fa-users text-info me-2"></i>{{ __("Eficiencia de Operadores") }}
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="shift-variations">
                                    <i class="fas fa-user-clock text-info me-2"></i>{{ __("Variaciones por Turno/Operador") }}
                                </a></li>
                                <li><a class="dropdown-item" href="#" data-analysis="shift-profitability">
                                    <i class="fas fa-money-bill-trend-up text-success me-2"></i>{{ __("Rentabilidad por Turno") }}
                                </a></li>

                                <li><hr class="dropdown-divider"></li>

                                <!-- SECCIÓN 4: Análisis Comparativos -->
                                <li><h6 class="dropdown-header text-warning"><i class="fas fa-balance-scale me-1"></i> {{ __("Análisis Comparativos") }}</h6></li>
                                <li><a class="dropdown-item" href="#" data-analysis="comparison">
                                    <i class="fas fa-balance-scale text-warning me-2"></i>{{ __("Comparativa Top/Bottom") }}
                                </a></li>

                                <li><hr class="dropdown-divider"></li>

                                <!-- SECCIÓN 5: Análisis Completo -->
                                <li><h6 class="dropdown-header text-dark"><i class="fas fa-layer-group me-1"></i> {{ __("Análisis Completo") }}</h6></li>
                                <li><a class="dropdown-item" href="#" data-analysis="full">
                                    <i class="fas fa-brain text-dark me-2"></i>{{ __("Análisis Total (CSV extendido)") }}
                                </a></li>
                            </ul>
                        </div>
                        @endif
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportExcel">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="exportPDF">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="printTable">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
        <div class="ls-table-body">
            <div class="table-responsive">
                <table id="controlWeightTable" class="table table-hover" style="width:100%">
                </table>
            </div>
        </div>
    </div>

    @include('productionlines.status-legend')

    @include('productionlines.time-legend')
</div>
    
    <!-- Modal para detalles de línea de producción -->
    <div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="max-width: 80%; width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel">Detalles de Línea de Producción</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Información General</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="40%">Línea</td>
                                                <td id="modal-line-name"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Orden</td>
                                                <td id="modal-order-id"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Caja</td>
                                                <td id="modal-box"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Unidades</td>
                                                <td id="modal-units"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">UPM Real</td>
                                                <td id="modal-upm-real"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">UPM Teórico</td>
                                                <td id="modal-upm-theoretical"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Estado</td>
                                                <td><span id="modal-status" class="badge"></span></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo de inicio</td>
                                                <td id="modal-created-at"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Última actualización</td>
                                                <td id="modal-updated-at"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Básculas</h6>
                                </div>
                                <div class="card-body p-0">
                                    <h6 class="text-primary p-2 mb-0 bg-light border-bottom">Báscula Final de Línea</h6>
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="50%">Nº en Turno</td>
                                                <td id="modal-weights-0-shift-number"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kg en Turno</td>
                                                <td id="modal-weights-0-shift-kg"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Nº en Orden</td>
                                                <td id="modal-weights-0-order-number"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kg en Orden</td>
                                                <td id="modal-weights-0-order-kg"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <h6 class="text-danger p-2 mb-0 bg-light border-bottom border-top">Básculas de Rechazo</h6>
                                    <div id="weights-rejection-container" class="p-2">
                                        <!-- Aquí se insertarán dinámicamente las básculas de rechazo -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">OEE</h6>
                                </div>
                                <div class="card-body p-0 text-center">
                                    <div style="height: 402px; padding: 15px; display: flex; justify-content: center; align-items: center;">
                                        <canvas id="oeeChart" style="max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Tiempos de Producción</h6>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped table-hover mb-0">
                                        <tbody>
                                            <tr>
                                                <td class="fw-bold" width="50%">Tiempo de producción</td>
                                                <td id="modal-on-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Ganado</td>
                                                <td id="modal-fast-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Lento</td>
                                                <td id="modal-slow-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo de Más</td>
                                                <td id="modal-out-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Parada Falta Material</td>
                                                <td id="modal-down-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Paradas No Justificadas</td>
                                                <td id="modal-production-stops-time"></td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Tiempo Preparación</td>
                                                <td id="modal-prepair-time"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
  {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <style>
        .dataTables_wrapper {
            overflow-x: auto;
        }
        
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        
        .select2-container .select2-selection--multiple {
            min-height: 38px;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        /* Ajustar color del texto en las opciones seleccionadas */
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #212529;
            font-weight: 500;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: #495057;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #212529;
            background-color: #dde2e6;
        }
    </style>
@endpush

@push('scripts')
  {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/dashboard-animations.css') }}" rel="stylesheet">
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="{{ asset('js/dashboard-animations.js') }}?v={{ time() }}"></script>

    <script>
        $(document).ready(function() {
            console.log('Document ready, checking for DashboardAnimations class...');
            // La clase se inicializa automáticamente en el archivo JS
        });
        // Limitar el rango máximo de fechas a 7 días
        function ensureMaxRange7Days() {
            const startVal = $('#startDate').val();
            const endVal = $('#endDate').val();
            if (!startVal || !endVal) return;
            const start = new Date(startVal);
            const end = new Date(endVal);
            const sevenDaysMs = 7 * 24 * 60 * 60 * 1000;
            if ((end - start) > sevenDaysMs) {
                const newStart = new Date(end.getTime() - sevenDaysMs);
                const fmt = (d) => d.toISOString().slice(0,16);
                $('#startDate').val(fmt(newStart));
            }
        }
    </script>

    <script>
        // IA: Configuración y utilidades
        const AI_URL = "{{ config('services.ai.url') }}";
        const AI_TOKEN = "{{ config('services.ai.token') }}";

        // Funciones auxiliares para normalización y CSV
        function cleanValue(value) {
            if (value === null || value === undefined) return '';
            let str = String(value).trim();
            if (str === '') return '';
            const needsQuoting = /[",\n\r]/.test(str);
            if (str.includes('"')) {
                str = str.replace(/"/g, '""');
            }
            return needsQuoting ? `"${str}"` : str;
        }

        function safeValue(value, fallback = '') {
            if (value === null || value === undefined) return fallback;
            const str = String(value).trim();
            if (!str || str === '-' || str === '--' || str.toLowerCase() === 'null' || str.toLowerCase() === 'undefined') {
                return fallback;
            }
            return str;
        }

        function normalizeDateTime(value) {
            const raw = safeValue(value, '');
            if (!raw || raw === '0000-00-00 00:00:00' || raw === '0000-00-00') return '';
            const trimmed = raw.trim();
            const isIso = /\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(trimmed);
            const isSql = /\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/.test(trimmed);
            if (isIso) {
                return trimmed.length === 16 ? `${trimmed}:00` : trimmed;
            }
            if (isSql) {
                const dt = new Date(trimmed.replace(' ', 'T'));
                if (!Number.isNaN(dt.getTime())) {
                    return dt.toISOString();
                }
            }
            const parsed = new Date(trimmed);
            if (!Number.isNaN(parsed.getTime())) {
                return parsed.toISOString();
            }
            return '';
        }

        function durationToSeconds(value) {
            const raw = safeValue(value, '');
            if (!raw) return 0;
            if (/^-?\d+$/.test(raw)) {
                return parseInt(raw, 10);
            }
            const match = raw.match(/(-?)(\d{1,3}):(\d{2}):(\d{2})/);
            if (!match) return 0;
            const sign = match[1] === '-' ? -1 : 1;
            const hours = parseInt(match[2], 10) || 0;
            const minutes = parseInt(match[3], 10) || 0;
            const seconds = parseInt(match[4], 10) || 0;
            return sign * (hours * 3600 + minutes * 60 + seconds);
        }

        // === Turnos: helpers y caché ===
        const shiftCacheByToken = {};
        const shiftCacheByLineId = {};

        function parseTimeToMinutes(timeStr) {
            if (!timeStr || typeof timeStr !== 'string') return null;
            const parts = timeStr.split(':').map(p => parseInt(p, 10));
            if (parts.length < 2 || parts.some(Number.isNaN)) return null;
            return parts[0] * 60 + parts[1];
        }

        function findShiftForDate(dateStr, shifts) {
            if (!dateStr || !Array.isArray(shifts) || shifts.length === 0) return null;
            const d = new Date(dateStr);
            if (Number.isNaN(d.getTime())) return null;
            const minutes = d.getHours() * 60 + d.getMinutes();
            for (const shift of shifts) {
                const startMin = parseTimeToMinutes(shift.start);
                const endMin = parseTimeToMinutes(shift.end);
                if (startMin === null || endMin === null) continue;
                if (startMin <= endMin) {
                    if (minutes >= startMin && minutes < endMin) return shift;
                } else {
                    if (minutes >= startMin || minutes < endMin) return shift; // cruza medianoche
                }
            }
            return null;
        }

        async function ensureShiftsLoadedForTokens(tokens) {
            try {
                const toLoad = (tokens || []).filter(t => t && !shiftCacheByToken[t]);
                if (toLoad.length === 0) return;
                await Promise.all(toLoad.map(async (t) => {
                    const resp = await fetch(`/api/shift-lists?token=${encodeURIComponent(t)}`);
                    if (!resp.ok) { shiftCacheByToken[t] = []; return; }
                    const arr = await resp.json();
                    const list = Array.isArray(arr) ? arr : [];
                    shiftCacheByToken[t] = list;
                    list.forEach(s => {
                        const lid = s && s.production_line_id;
                        if (!lid) return;
                        if (!Array.isArray(shiftCacheByLineId[lid])) shiftCacheByLineId[lid] = [];
                        shiftCacheByLineId[lid].push(s);
                    });
                }));
                // deduplicate por id
                Object.keys(shiftCacheByLineId).forEach(lid => {
                    const seen = {};
                    shiftCacheByLineId[lid] = shiftCacheByLineId[lid].filter(s => {
                        if (!s || seen[s.id]) return false;
                        seen[s.id] = true;
                        return true;
                    });
                });
            } catch (e) {
                console.warn('[Shifts] Error precargando turnos', e);
            }
        }

        function resolveShiftForRow(item) {
            if (!item) return null;
            const lid = item.production_line_id || (item.production_line && item.production_line.id);
            const shifts = lid ? (shiftCacheByLineId[lid] || []) : [];
            const when = item.created_at || item.start || item.updated_at || null;
            const found = findShiftForDate(when, shifts);
            if (!found) return null;
            return {
                id: found.id,
                name: found.name || `Turno ${found.start}-${found.end}`,
                start: found.start,
                end: found.end,
                active: found.active
            };
        }

        // Análisis General de OEE
        function collectOEEGeneralData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'OEE General' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                totalDuration: $('#totalDuration').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas para análisis
            let csv = 'ID,OEE_Pct,Duracion_Horas,UPM_Real,UPM_Teorico\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                // Validar datos mínimos
                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName) {
                    skipped++;
                    return;
                }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = 0;
                if (oeeRaw !== null && oeeRaw !== undefined) {
                    oeeNum = typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0;
                }
                // Normalizar OEE a porcentaje (0-100)
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durationSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                if (durationSec <= 0) {
                    skipped++;
                    return;
                }

                const upmReal = parseFloat(row.units_per_minute_real ?? row[5]) || 0;
                const upmTeo = parseFloat(row.units_per_minute_theoretical ?? row[6]) || 0;

                // Convertir a horas
                const duracionHoras = (durationSec / 3600).toFixed(2);

                csv += `${count + 1},${oeeNum.toFixed(2)},${duracionHoras},${upmReal.toFixed(2)},${upmTeo.toFixed(2)}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas para análisis`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'OEE General', note };
        }

        // Análisis de Paradas
        function collectStopsData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Paradas' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                totalStops: $('#totalProductionStopsTime').text() || '00:00:00',
                totalDownTime: $('#totalDownTime').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas (horas)
            let csv = 'ID,Paradas_Horas,Falta_Material_Horas,Preparacion_Horas,Total_Improductivo_Horas\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName) {
                    skipped++;
                    return;
                }

                const paradasSec = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSec = durationToSeconds(row.down_time ?? row[14]) || 0;
                const prepSec = durationToSeconds(row.prepair_time ?? row[11]) || 0;
                const totalImprod = paradasSec + faltaSec + prepSec;

                // Convertir a horas
                const paradasHoras = (paradasSec / 3600).toFixed(2);
                const faltaHoras = (faltaSec / 3600).toFixed(2);
                const prepHoras = (prepSec / 3600).toFixed(2);
                const totalHoras = (totalImprod / 3600).toFixed(2);

                csv += `${count + 1},${paradasHoras},${faltaHoras},${prepHoras},${totalHoras}\n`;
                count++;
            });

            let note = `${count} registros - tiempos en horas`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Paradas', note };
        }

        // Análisis de Rendimiento
        function collectPerformanceData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Rendimiento' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                slowTime: $('#totalSlowTime').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas
            let csv = 'ID,OEE_Pct,Tiempo_Lento_Horas,UPM_Real,UPM_Teorico,Eficiencia_Pct\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName) {
                    skipped++;
                    return;
                }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = 0;
                if (oeeRaw !== null && oeeRaw !== undefined) {
                    oeeNum = typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0;
                }
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const lentoSec = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const upmReal = parseFloat(row.units_per_minute_real ?? row[5]) || 0;
                const upmTeo = parseFloat(row.units_per_minute_theoretical ?? row[6]) || 0;

                // Eficiencia = UPM Real / UPM Teórico * 100
                const eficiencia = upmTeo > 0 ? ((upmReal / upmTeo) * 100).toFixed(2) : '0.00';
                const lentoHoras = (lentoSec / 3600).toFixed(2);

                csv += `${count + 1},${oeeNum.toFixed(2)},${lentoHoras},${upmReal.toFixed(2)},${upmTeo.toFixed(2)},${eficiencia}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas para análisis`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Rendimiento', note };
        }

        // Análisis de Operadores
        function collectOperatorsData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Operadores' };
            }

            const table = $('#controlWeightTable').DataTable();
            const selectedOps = $('#operatorSelect').select2('data').map(o => o.text).join(', ') || 'Todos';
            const metrics = {
                operators: selectedOps,
                avgOEE: $('#avgOEE').text() || '0%',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas
            let csv = 'ID,OEE_Pct,Duracion_Horas,Tiempo_Ganado_Horas,Tiempo_Perdido_Horas,Balance_Horas\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName) {
                    skipped++;
                    return;
                }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = 0;
                if (oeeRaw !== null && oeeRaw !== undefined) {
                    oeeNum = typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0;
                }
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                const ganadoSec = durationToSeconds(row.fast_time ?? row[16]) || 0;
                const perdidoSec = durationToSeconds(row.out_time ?? row[17]) || 0;
                const balanceSec = ganadoSec - perdidoSec;

                // Convertir a horas
                const durHoras = (durSec / 3600).toFixed(2);
                const ganadoHoras = (ganadoSec / 3600).toFixed(2);
                const perdidoHoras = (perdidoSec / 3600).toFixed(2);
                const balanceHoras = (balanceSec / 3600).toFixed(2);

                csv += `${count + 1},${oeeNum.toFixed(2)},${durHoras},${ganadoHoras},${perdidoHoras},${balanceHoras}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas (Balance = Ganado - Perdido)`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Operadores', note };
        }

        // Comparativa Top/Bottom
        function collectComparisonData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Comparativa' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas para análisis estadístico
            let csv = 'ID,Es_Top,OEE_Pct,Duracion_Horas,Preparacion_Horas,Lento_Horas,Paradas_Horas,Falta_Material_Horas,Total_Improductivo_Horas\n';
            const allRows = [];
            let skipped = 0;

            table.rows({search: 'applied'}).data().each(function(row) {
                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName || String(lineaName).trim() === '') { skipped++; return; }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                const prepSec = durationToSeconds(row.prepair_time ?? row[11]) || 0;
                const lentoSec = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const paradasSec = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSec = durationToSeconds(row.down_time ?? row[14]) || 0;

                if (durSec <= 0) { skipped++; return; }

                allRows.push({
                    oee: oeeNum,
                    durHoras: durSec / 3600,
                    prepHoras: prepSec / 3600,
                    lentoHoras: lentoSec / 3600,
                    paradasHoras: paradasSec / 3600,
                    faltaHoras: faltaSec / 3600,
                    totalImprod: (prepSec + lentoSec + paradasSec + faltaSec) / 3600
                });
            });

            // Ordenar por OEE descendente antes de extraer top/bottom
            allRows.sort((a, b) => b.oee - a.oee);

            // Top 10 (mejor OEE) y Bottom 10 (peor OEE)
            const top10 = allRows.slice(0, 10);
            const bottom10 = allRows.slice(-10);
            let count = 0;

            top10.forEach(r => {
                csv += `${count + 1},1,${r.oee.toFixed(2)},${r.durHoras.toFixed(2)},${r.prepHoras.toFixed(2)},${r.lentoHoras.toFixed(2)},${r.paradasHoras.toFixed(2)},${r.faltaHoras.toFixed(2)},${r.totalImprod.toFixed(2)}\n`;
                count++;
            });
            bottom10.forEach(r => {
                csv += `${count + 1},0,${r.oee.toFixed(2)},${r.durHoras.toFixed(2)},${r.prepHoras.toFixed(2)},${r.lentoHoras.toFixed(2)},${r.paradasHoras.toFixed(2)},${r.faltaHoras.toFixed(2)},${r.totalImprod.toFixed(2)}\n`;
                count++;
            });

            let note = `${count} registros (Top10 + Bottom10) - columnas numéricas`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Comparativa', note };
        }

        // Disponibilidad vs Rendimiento
        function collectAvailabilityPerformanceData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Disponibilidad vs Rendimiento' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas para análisis estadístico
            let csv = 'ID,OEE_Pct,Duracion_Horas,Disponible_Horas,Incidencias_Horas,Pct_Disponibilidad,Pct_Incidencias\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName || String(lineaName).trim() === '') { skipped++; return; }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                if (durSec <= 0) { skipped++; return; }

                const paradasSec = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSec = durationToSeconds(row.down_time ?? row[14]) || 0;
                const prepSec = durationToSeconds(row.prepair_time ?? row[11]) || 0;

                const disponibleSec = Math.max(durSec - paradasSec - faltaSec, 0);
                const incidenciasSec = paradasSec + faltaSec + prepSec;

                // Convertir a horas
                const durHoras = (durSec / 3600).toFixed(2);
                const dispHoras = (disponibleSec / 3600).toFixed(2);
                const incidHoras = (incidenciasSec / 3600).toFixed(2);

                // Calcular porcentajes
                const pctDisp = durSec > 0 ? ((disponibleSec / durSec) * 100).toFixed(2) : '0.00';
                const pctIncid = durSec > 0 ? ((incidenciasSec / durSec) * 100).toFixed(2) : '0.00';

                csv += `${count + 1},${oeeNum.toFixed(2)},${durHoras},${dispHoras},${incidHoras},${pctDisp},${pctIncid}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Disponibilidad vs Rendimiento', note };
        }

        // Variaciones por Turno/Operador
        function collectShiftVariationsData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Variaciones por Turno/Operador' };
            }

            const table = $('#controlWeightTable').DataTable();
            const selectedOps = $('#operatorSelect').select2('data').map(o => o.text).join(', ') || 'Todos';
            const metrics = {
                operators: selectedOps,
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas para análisis estadístico
            let csv = 'ID,Turno_ID,OEE_Pct,Duracion_Horas,Lento_Horas,Ganado_Horas,Num_Operadores,Eficiencia_Tiempo\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName || String(lineaName).trim() === '') { skipped++; return; }

                const resolvedShift = resolveShiftForRow(row);
                const turnoId = (row.shift_id ?? (resolvedShift && resolvedShift.id)) || 0;

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                if (durSec <= 0) { skipped++; return; }

                const lentoSec = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const ganadoSec = durationToSeconds(row.fast_time ?? row[16]) || 0;

                // Contar operadores
                let numOps = 0;
                if (Array.isArray(row.operator_names)) {
                    numOps = row.operator_names.length;
                } else if (row.operator_names) {
                    numOps = 1;
                }

                // Convertir a horas
                const durHoras = (durSec / 3600).toFixed(2);
                const lentoHoras = (lentoSec / 3600).toFixed(2);
                const ganadoHoras = (ganadoSec / 3600).toFixed(2);

                // Eficiencia de tiempo: (duración - lento) / duración * 100
                const eficiencia = durSec > 0 ? (((durSec - lentoSec) / durSec) * 100).toFixed(2) : '0.00';

                csv += `${count + 1},${turnoId},${oeeNum.toFixed(2)},${durHoras},${lentoHoras},${ganadoHoras},${numOps},${eficiencia}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Variaciones por Turno/Operador', note };
        }

        // Rentabilidad por Turno
        function collectShiftProfitabilityData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Rentabilidad por Turno' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            const groups = {};
            let skipped = 0;

            table.rows({search: 'applied'}).data().each(function(row) {
                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName || String(lineaName).trim() === '') { skipped++; return; }

                const resolvedShift = resolveShiftForRow(row);
                const shiftId = (row.shift_id ?? (resolvedShift && resolvedShift.id)) || 0;
                const lineId = row.production_line_id || 0;
                const key = `${lineId}|${shiftId}`;

                if (!groups[key]) {
                    groups[key] = {
                        shiftId: shiftId,
                        orders: 0,
                        oeeSum: 0, oeeCount: 0,
                        durSum: 0,
                        slowSum: 0, stopsSum: 0, downSum: 0, prepairSum: 0,
                        kgSum: 0, numSum: 0
                    };
                }
                const g = groups[key];
                g.orders++;

                const oeeRaw = row.oee ?? row[7];
                const oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                g.oeeSum += oeeNum > 1 ? oeeNum : (oeeNum * 100);
                g.oeeCount++;

                const dur = durationToSeconds(row.on_time ?? row[10]) || 0;
                const lento = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const stops = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const falta = durationToSeconds(row.down_time ?? row[14]) || 0;
                const prepair = durationToSeconds(row.prepair_time ?? row[11]) || 0;
                g.durSum += dur;
                g.slowSum += lento;
                g.stopsSum += stops;
                g.downSum += falta;
                g.prepairSum += prepair;

                const kg = parseFloat(row.weights_0_shiftKg) || 0;
                const num = parseInt(row.weights_0_shiftNumber) || 0;
                g.kgSum += kg;
                g.numSum += num;
            });

            // CSV con columnas numéricas para análisis estadístico
            let csv = 'ID,Turno_ID,Ordenes,OEE_Promedio_Pct,Duracion_Horas,Improductivo_Horas,Neto_Horas,Lento_Horas,Paradas_Horas,Falta_Material_Horas,Preparacion_Horas,Kg_Total,Cajas_Total,Pct_Productivo\n';
            let count = 0;
            const maxRows = 100;

            Object.values(groups).forEach(g => {
                if (count >= maxRows) return;
                if (g.durSum <= 0) return;

                const improdSec = g.slowSum + g.stopsSum + g.downSum + g.prepairSum;
                const netoSec = Math.max(g.durSum - improdSec, 0);
                const oeeAvg = g.oeeCount > 0 ? g.oeeSum / g.oeeCount : 0;

                // Convertir a horas
                const durHoras = (g.durSum / 3600).toFixed(2);
                const improdHoras = (improdSec / 3600).toFixed(2);
                const netoHoras = (netoSec / 3600).toFixed(2);
                const lentoHoras = (g.slowSum / 3600).toFixed(2);
                const paradasHoras = (g.stopsSum / 3600).toFixed(2);
                const faltaHoras = (g.downSum / 3600).toFixed(2);
                const prepHoras = (g.prepairSum / 3600).toFixed(2);

                // Porcentaje productivo
                const pctProductivo = g.durSum > 0 ? ((netoSec / g.durSum) * 100).toFixed(2) : '0.00';

                csv += `${count + 1},${g.shiftId},${g.orders},${oeeAvg.toFixed(2)},${durHoras},${improdHoras},${netoHoras},${lentoHoras},${paradasHoras},${faltaHoras},${prepHoras},${g.kgSum.toFixed(2)},${g.numSum},${pctProductivo}\n`;
                count++;
            });

            let note = `${count} grupos - columnas numéricas`;
            if (skipped > 0) note += ` (${skipped} filas omitidas)`;

            return { metrics, csv, type: 'Rentabilidad por Turno', note };
        }

        // Consumo de Tiempo Improductivo
        function collectIdleTimeData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Tiempo improductivo' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                totalSlowTime: $('#totalSlowTime').text() || '00:00:00',
                totalStopsTime: $('#totalProductionStopsTime').text() || '00:00:00',
                totalDownTime: $('#totalDownTime').text() || '00:00:00',
                dateRange: `${$('#startDate').val()} a ${$('#endDate').val()}`
            };

            // CSV con columnas numéricas para análisis estadístico
            let csv = 'ID,OEE_Pct,Lento_Horas,Paradas_Horas,Falta_Material_Horas,Neto_Horas,Total_Improductivo_Horas,Pct_Improductivo\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName || String(lineaName).trim() === '') { skipped++; return; }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                if (durSec <= 0) { skipped++; return; }

                const lentoSec = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const paradasSec = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSec = durationToSeconds(row.down_time ?? row[14]) || 0;
                const totalImprodSec = lentoSec + paradasSec + faltaSec;
                const netoSec = Math.max(durSec - totalImprodSec, 0);

                // Convertir a horas
                const lentoHoras = (lentoSec / 3600).toFixed(2);
                const paradasHoras = (paradasSec / 3600).toFixed(2);
                const faltaHoras = (faltaSec / 3600).toFixed(2);
                const netoHoras = (netoSec / 3600).toFixed(2);
                const improdHoras = (totalImprodSec / 3600).toFixed(2);

                // Porcentaje improductivo
                const pctImprod = durSec > 0 ? ((totalImprodSec / durSec) * 100).toFixed(2) : '0.00';

                csv += `${count + 1},${oeeNum.toFixed(2)},${lentoHoras},${paradasHoras},${faltaHoras},${netoHoras},${improdHoras},${pctImprod}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Tiempo improductivo', note };
        }

        // Análisis Total extendido
        function collectFullAnalysisData() {
            if (!$.fn.DataTable.isDataTable('#controlWeightTable')) {
                console.error('[AI] DataTable no inicializada');
                return { metrics: {}, csv: '', type: 'Análisis Total', note: 'Sin datos' };
            }

            const table = $('#controlWeightTable').DataTable();
            const metrics = {
                avgOEE: $('#avgOEE').text() || '0%',
                totalDuration: $('#totalDuration').text() || '00:00:00',
                totalDifference: $('#totalTheoretical').text() || '00:00:00',
                totalPrepTime: $('#totalPrepairTime').text() || '00:00:00',
                totalSlowTime: $('#totalSlowTime').text() || '00:00:00',
                totalStopsTime: $('#totalProductionStopsTime').text() || '00:00:00',
                totalDownTime: $('#totalDownTime').text() || '00:00:00'
            };

            // CSV con columnas numéricas para análisis estadístico
            let csv = 'ID,OEE_Pct,Duracion_Horas,Diferencia_Horas,Preparacion_Horas,Lento_Horas,Paradas_Horas,Falta_Material_Horas,Neto_Horas,Pct_Productivo,Num_Operadores\n';
            let count = 0;
            let skipped = 0;
            const maxRows = 100;

            table.rows({search: 'applied'}).data().each(function(row) {
                if (count >= maxRows) return false;

                const lineaName = row.production_line_name ?? row[1];
                if (!lineaName || String(lineaName).trim() === '') { skipped++; return; }

                const oeeRaw = row.oee ?? row[7];
                let oeeNum = (oeeRaw !== null && oeeRaw !== undefined)
                    ? (typeof oeeRaw === 'number' ? oeeRaw : parseFloat(String(oeeRaw).replace(',', '.')) || 0)
                    : 0;
                if (oeeNum > 0 && oeeNum <= 1) oeeNum = oeeNum * 100;

                const durSec = durationToSeconds(row.on_time ?? row[10]) || 0;
                if (durSec <= 0) { skipped++; return; }

                const fastSec = durationToSeconds(row.fast_time ?? row[16]) || 0;
                const outSec = durationToSeconds(row.out_time ?? row[17]) || 0;
                const diffSec = outSec - fastSec;

                const prepSec = durationToSeconds(row.prepair_time ?? row[11]) || 0;
                const lentoSec = durationToSeconds(row.slow_time ?? row[12]) || 0;
                const paradasSec = durationToSeconds(row.production_stops_time ?? row[13]) || 0;
                const faltaSec = durationToSeconds(row.down_time ?? row[14]) || 0;

                const totalImprodSec = prepSec + lentoSec + paradasSec + faltaSec;
                const netoSec = Math.max(durSec - totalImprodSec, 0);

                // Contar operadores
                let numOps = 0;
                if (Array.isArray(row.operator_names)) {
                    numOps = row.operator_names.length;
                } else if (row.operator_names) {
                    numOps = 1;
                }

                // Convertir a horas
                const durHoras = (durSec / 3600).toFixed(2);
                const diffHoras = (diffSec / 3600).toFixed(2);
                const prepHoras = (prepSec / 3600).toFixed(2);
                const lentoHoras = (lentoSec / 3600).toFixed(2);
                const paradasHoras = (paradasSec / 3600).toFixed(2);
                const faltaHoras = (faltaSec / 3600).toFixed(2);
                const netoHoras = (netoSec / 3600).toFixed(2);

                // Porcentaje productivo
                const pctProductivo = durSec > 0 ? ((netoSec / durSec) * 100).toFixed(2) : '0.00';

                csv += `${count + 1},${oeeNum.toFixed(2)},${durHoras},${diffHoras},${prepHoras},${lentoHoras},${paradasHoras},${faltaHoras},${netoHoras},${pctProductivo},${numOps}\n`;
                count++;
            });

            let note = `${count} registros - columnas numéricas`;
            if (skipped > 0) note += ` (${skipped} omitidos)`;

            return { metrics, csv, type: 'Análisis Total', note };
        }

        async function startAiTask(fullPrompt, userPromptForDisplay, agentType = 'supervisor') {
            try {
                console.log('[AI] Iniciando análisis:', userPromptForDisplay);
                console.log('[AI] Prompt length:', fullPrompt.length, 'caracteres');
                console.log('[AI] Agente seleccionado:', agentType);

                // Mostrar modal de procesamiento
                $('#aiProcessingTitle').text(userPromptForDisplay);
                $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>Enviando solicitud a IA...');
                const processingModal = new bootstrap.Modal(document.getElementById('aiProcessingModal'));
                processingModal.show();

                const fd = new FormData();
                fd.append('prompt', fullPrompt);
                fd.append('agent', agentType);

                const resp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks`, {
                    method: 'POST',
                    headers: { 'Authorization': `Bearer ${AI_TOKEN}` },
                    body: fd
                });
                if (!resp.ok) {
                    const t = await resp.text();
                    throw new Error(`AI create failed ${resp.status}: ${t}`);
                }
                const created = await resp.json();
                const taskId = (created && (created.id || created.task_id || created.taskId)) || created;
                if (!taskId) throw new Error('No task id');

                console.log('[AI] Tarea creada con ID:', taskId);
                console.log('[AI] Iniciando polling cada 5 segundos...');
                
                // Actualizar estado
                $('#aiProcessingStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... Esperando respuesta...');

                let done = false; let last; let pollCount = 0;
                while (!done) {
                    pollCount++;
                    console.log(`[AI] Polling #${pollCount} - Esperando 5 segundos...`);
                    $('#aiProcessingStatus').html(`<i class="fas fa-spinner fa-spin me-2"></i>IA procesando... (${pollCount * 5}s)`);
                    await new Promise(r => setTimeout(r, 5000));
                    
                    console.log(`[AI] Polling #${pollCount} - Verificando estado de la tarea...`);
                    const pollResp = await fetch(`${AI_URL.replace(/\/$/, '')}/api/ollama-tasks/${encodeURIComponent(taskId)}`, {
                        headers: { 'Authorization': `Bearer ${AI_TOKEN}` }
                    });
                    
                    if (pollResp.status === 404) {
                        console.log('[AI] Error: Tarea no encontrada (404)');
                        try { const nf = await pollResp.json(); alert(nf?.error || 'Task not found'); } catch {}
                        return;
                    }
                    if (!pollResp.ok) {
                        console.log('[AI] Error en polling:', pollResp.status, pollResp.statusText);
                        throw new Error(`poll failed: ${pollResp.status}`);
                    }
                    
                    last = await pollResp.json();
                    console.log(`[AI] Polling #${pollCount} - Respuesta recibida:`, last);
                    
                    const task = last && last.task ? last.task : null;
                    if (!task) {
                        console.log(`[AI] Polling #${pollCount} - No hay objeto task, continuando...`);
                        continue;
                    }
                    
                    console.log(`[AI] Polling #${pollCount} - Estado de la tarea:`, {
                        hasResponse: task.response != null,
                        hasError: task.error != null,
                        error: task.error
                    });
                    
                    if (task.response == null) {
                        if (task.error && /processing/i.test(task.error)) { 
                            console.log(`[AI] Polling #${pollCount} - Tarea aún procesando...`);
                            continue; 
                        }
                        if (task.error == null) { 
                            console.log(`[AI] Polling #${pollCount} - Sin respuesta ni error, continuando...`);
                            continue; 
                        }
                    }
                    if (task.error && !/processing/i.test(task.error)) { 
                        console.log('[AI] Error en la tarea:', task.error);
                        alert(task.error); 
                        return; 
                    }
                    if (task.response != null) { 
                        console.log('[AI] ¡Respuesta recibida! Finalizando polling...');
                        done = true; 
                    }
                }

                // Cerrar modal de procesamiento
                bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal')).hide();

                // Mostrar resultado
                $('#aiResultPrompt').text(userPromptForDisplay);
                const content = (last && last.task && last.task.response != null) ? last.task.response : last;

                let rawText;
                try {
                    rawText = typeof content === 'string' ? content : JSON.stringify(content, null, 2);
                } catch {
                    rawText = String(content);
                }

                // Establecer metadatos del análisis
                const now = new Date();
                const timestamp = now.toLocaleString('es-ES', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                $('#aiResultTimestamp').text(timestamp);

                // Calcular estadísticas del texto
                const wordCount = (rawText || '').trim().split(/\s+/).filter(w => w.length > 0).length;
                const lineCount = (rawText || '').split('\n').length;
                const charCount = (rawText || '').length;
                $('#aiResultStats').text(`${wordCount} palabras, ${lineCount} líneas, ${charCount} caracteres`);

                // Establecer texto plano
                $('#aiResultText').text(rawText || '');

                // Convertir Markdown a HTML con marked.js
                const htmlTarget = $('#aiResultHtml');
                if (window.marked && window.DOMPurify) {
                    try {
                        console.log('[AI] Parseando Markdown con marked.js...');

                        // Convertir Markdown a HTML
                        let htmlContent = marked.parse(rawText || '');
                        console.log('[AI] Markdown parseado correctamente');

                        // Agregar clases de Bootstrap a las tablas
                        htmlContent = htmlContent.replace(/<table>/g, '<table class="table table-striped table-bordered table-hover">');

                        // Sanitizar el HTML con DOMPurify
                        const sanitized = DOMPurify.sanitize(htmlContent, {
                            ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'style', 'src', 'alt', 'title', 'colspan', 'rowspan'],
                            ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                                          'ul', 'ol', 'li', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
                                          'a', 'code', 'pre', 'blockquote', 'hr', 'span', 'div']
                        });

                        htmlTarget.html(sanitized);
                        console.log('[AI] HTML sanitizado e inyectado en el DOM');
                    } catch (err) {
                        console.error('[AI] Error al parsear Markdown:', err);
                        htmlTarget.html('<p class="text-danger">Error al procesar el contenido Markdown.</p>');
                    }
                } else {
                    console.warn('[AI] marked.js o DOMPurify no disponible, mostrando texto plano');
                    htmlTarget.text(rawText || '');
                }

                // Mostrar la tab de "Vista Formateada" por defecto
                const renderedTabTrigger = document.getElementById('ai-tab-rendered');
                if (renderedTabTrigger && bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(renderedTabTrigger).show();
                }

                // Inicializar funcionalidades del modal (copiar, descargar, imprimir, etc.)
                initAIResultModalFeatures(rawText, userPromptForDisplay);

                // Mostrar modal
                const resultModal = new bootstrap.Modal(document.getElementById('aiResultModal'));
                resultModal.show();
            } catch (err) {
                console.error('[AI] Unexpected error:', err);
                // Cerrar modal de procesamiento si está abierto
                const procModal = bootstrap.Modal.getInstance(document.getElementById('aiProcessingModal'));
                if (procModal) procModal.hide();
                alert('{{ __('Error al procesar solicitud de IA') }}');
            }
        }

        /**
         * Inicializa las funcionalidades interactivas del modal de resultados IA
         * @param {string} rawText - Texto sin procesar del análisis
         * @param {string} analysisType - Tipo de análisis realizado
         */
        function initAIResultModalFeatures(rawText, analysisType) {
            console.log('[AI Modal] Inicializando funcionalidades interactivas...');

            // Estado de tamaño de fuente (100% por defecto)
            let currentFontSize = 100;

            // ===== 1. COPIAR AL PORTAPAPELES =====
            $('#btnCopyResult').off('click').on('click', function() {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(rawText).then(() => {
                        console.log('[AI Modal] Texto copiado al portapapeles');
                        showToast('✓ Copiado al portapapeles', 'success');
                    }).catch(err => {
                        console.error('[AI Modal] Error al copiar:', err);
                        showToast('✗ Error al copiar', 'danger');
                    });
                } else {
                    // Fallback para navegadores antiguos
                    const textarea = document.createElement('textarea');
                    textarea.value = rawText;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        showToast('✓ Copiado al portapapeles', 'success');
                    } catch (err) {
                        showToast('✗ Error al copiar', 'danger');
                    }
                    document.body.removeChild(textarea);
                }
            });

            // ===== 2. DESCARGAR ARCHIVO .MD =====
            $('#btnDownloadResult').off('click').on('click', function() {
                try {
                    const timestamp = new Date().toISOString().replace(/[:]/g, '-').split('.')[0];
                    const filename = `analisis-ia-${timestamp}.md`;

                    const blob = new Blob([rawText], { type: 'text/markdown;charset=utf-8' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);

                    console.log('[AI Modal] Archivo descargado:', filename);
                    showToast('✓ Archivo descargado', 'success');
                } catch (err) {
                    console.error('[AI Modal] Error al descargar:', err);
                    showToast('✗ Error al descargar', 'danger');
                }
            });

            // ===== 3. IMPRIMIR / PDF =====
            $('#btnPrintResult').off('click').on('click', function() {
                try {
                    const printWindow = window.open('', '_blank');
                    const htmlContent = $('#aiResultHtml').html();

                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <title>Análisis IA - ${analysisType}</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    line-height: 1.6;
                                    padding: 20px;
                                    max-width: 1200px;
                                    margin: 0 auto;
                                }
                                h1, h2, h3, h4, h5, h6 {
                                    margin-top: 1.5rem;
                                    margin-bottom: 0.75rem;
                                    color: #212529;
                                }
                                table {
                                    width: 100%;
                                    border-collapse: collapse;
                                    margin-bottom: 1.5rem;
                                    font-size: 0.9rem;
                                }
                                table thead th {
                                    background-color: #0d6efd;
                                    color: white;
                                    padding: 0.75rem;
                                    border: 1px solid #0d6efd;
                                    text-align: left;
                                }
                                table tbody td {
                                    padding: 0.65rem;
                                    border: 1px solid #dee2e6;
                                }
                                table tbody tr:nth-child(odd) {
                                    background-color: #f8f9fa;
                                }
                                pre {
                                    background-color: #f8f9fa;
                                    padding: 1rem;
                                    border-radius: 4px;
                                    overflow-x: auto;
                                }
                                code {
                                    background-color: #f8f9fa;
                                    padding: 0.2rem 0.4rem;
                                    border-radius: 3px;
                                    font-family: 'Courier New', monospace;
                                }
                                @media print {
                                    body { padding: 10px; }
                                    table { page-break-inside: auto; }
                                    tr { page-break-inside: avoid; page-break-after: auto; }
                                }
                            </style>
                        </head>
                        <body>
                            <h1>Análisis IA: ${analysisType}</h1>
                            <p><strong>Generado:</strong> ${new Date().toLocaleString('es-ES')}</p>
                            <hr>
                            ${htmlContent}
                        </body>
                        </html>
                    `);

                    printWindow.document.close();
                    printWindow.focus();

                    // Esperar a que se cargue el contenido antes de imprimir
                    setTimeout(() => {
                        printWindow.print();
                    }, 250);

                    console.log('[AI Modal] Ventana de impresión abierta');
                } catch (err) {
                    console.error('[AI Modal] Error al imprimir:', err);
                    showToast('✗ Error al imprimir', 'danger');
                }
            });

            // ===== 4. PANTALLA COMPLETA =====
            $('#btnFullscreen').off('click').on('click', function() {
                const dialog = $('#aiResultModalDialog');
                const icon = $(this).find('i');

                if (dialog.hasClass('modal-fullscreen-custom')) {
                    dialog.removeClass('modal-fullscreen-custom');
                    icon.removeClass('fa-compress').addClass('fa-expand');
                    $(this).attr('title', 'Pantalla completa');
                    console.log('[AI Modal] Saliendo de pantalla completa');
                } else {
                    dialog.addClass('modal-fullscreen-custom');
                    icon.removeClass('fa-expand').addClass('fa-compress');
                    $(this).attr('title', 'Salir de pantalla completa');
                    console.log('[AI Modal] Entrando en pantalla completa');
                }
            });

            // ===== 5. CONTROL DE TAMAÑO DE FUENTE =====
            function updateFontSize() {
                $('.ai-result-content').css('font-size', currentFontSize + '%');
                console.log('[AI Modal] Tamaño de fuente:', currentFontSize + '%');
            }

            $('#btnFontDecrease').off('click').on('click', function() {
                if (currentFontSize > 70) {
                    currentFontSize -= 10;
                    updateFontSize();
                    showToast(`Tamaño: ${currentFontSize}%`, 'info');
                }
            });

            $('#btnFontReset').off('click').on('click', function() {
                currentFontSize = 100;
                updateFontSize();
                showToast('Tamaño: 100% (normal)', 'info');
            });

            $('#btnFontIncrease').off('click').on('click', function() {
                if (currentFontSize < 150) {
                    currentFontSize += 10;
                    updateFontSize();
                    showToast(`Tamaño: ${currentFontSize}%`, 'info');
                }
            });

            // ===== 6. BARRA DE PROGRESO DE SCROLL Y BOTÓN "VOLVER ARRIBA" =====
            const scrollContainers = $('.ai-result-content, #aiResultText');
            const btnScrollTop = $('#btnScrollTop');

            scrollContainers.off('scroll').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight - $(this).outerHeight();
                const scrollPercent = scrollHeight > 0 ? (scrollTop / scrollHeight) * 100 : 0;

                $('#aiScrollProgress').css('width', scrollPercent + '%');

                // Mostrar/ocultar botón "Volver arriba"
                if (scrollTop > 300) {
                    btnScrollTop.addClass('show');
                } else {
                    btnScrollTop.removeClass('show');
                }
            });

            // Click en botón "Volver arriba"
            btnScrollTop.off('click').on('click', function() {
                scrollContainers.animate({ scrollTop: 0 }, 400);
                console.log('[AI Modal] Volviendo arriba');
            });

            // ===== 7. LIMPIEZA AL CERRAR EL MODAL =====
            $('#aiResultModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                console.log('[AI Modal] Modal cerrado, limpiando event handlers...');

                // Resetear tamaño de fuente
                currentFontSize = 100;
                $('.ai-result-content').css('font-size', '100%');

                // Quitar clase fullscreen si está activa
                $('#aiResultModalDialog').removeClass('modal-fullscreen-custom');
                $('#btnFullscreen').find('i').removeClass('fa-compress').addClass('fa-expand');

                // Ocultar botón "Volver arriba"
                btnScrollTop.removeClass('show');

                // Reset scroll progress
                $('#aiScrollProgress').css('width', '0%');

                // Limpiar event handlers
                $('#btnCopyResult, #btnDownloadResult, #btnPrintResult, #btnFullscreen').off('click');
                $('#btnFontDecrease, #btnFontReset, #btnFontIncrease').off('click');
                scrollContainers.off('scroll');
                btnScrollTop.off('click');
            });

            console.log('[AI Modal] Funcionalidades interactivas inicializadas correctamente');
        }

        /**
         * Muestra un toast de notificación temporal
         * @param {string} message - Mensaje a mostrar
         * @param {string} type - Tipo de toast: success, danger, info
         */
        function showToast(message, type = 'success') {
            const bgColor = type === 'success' ? '#198754' : type === 'danger' ? '#dc3545' : '#0dcaf0';
            const toast = $(`
                <div class="copy-toast" style="background: ${bgColor};">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `);

            $('body').append(toast);

            // Auto-remover después de 3 segundos
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        $(function(){
            // Prompts ultra-simplificados para agentes especialistas
            const analysisPrompts = {
                'oee-general': {
                    title: 'Análisis General de OEE',
                    prompt: `Analiza el rendimiento OEE de las líneas de producción.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- OEE_Pct: OEE en porcentaje (0-100)
- Duracion_Horas: Tiempo total en horas decimales
- UPM_Real: Unidades por minuto reales
- UPM_Teorico: Unidades por minuto teóricas

ANÁLISIS REQUERIDO:
1. Estadísticas de OEE_Pct: media | mediana | desviación estándar | mínimo | máximo
2. Distribución: % registros con OEE >85 (excelente) | 70-85 (bueno) | <70 (mejorar)
3. Top 5 con mejor OEE y Bottom 5 con peor OEE
4. Correlación entre Duracion_Horas y OEE_Pct
5. Eficiencia de velocidad: ratio UPM_Real/UPM_Teorico promedio
6. Tres recomendaciones con impacto cuantificado

Responde con tablas y números concretos.`
                },
                'stops': {
                    title: 'Análisis de Paradas',
                    prompt: `Analiza el tiempo improductivo por paradas en las líneas de producción.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- Paradas_Horas: Tiempo total de paradas en horas decimales
- Falta_Material_Horas: Tiempo por falta de material en horas
- Preparacion_Horas: Tiempo de preparación en horas
- Total_Improductivo_Horas: Suma total de tiempo improductivo

ANÁLISIS REQUERIDO:
1. Estadísticas de Paradas_Horas: media | mediana | máximo | suma total
2. Top 5 registros con mayor Total_Improductivo_Horas
3. Composición del tiempo improductivo: % Paradas | % Falta_Material | % Preparacion
4. Correlación entre Paradas_Horas y Falta_Material_Horas
5. Registros donde Falta_Material_Horas > 50% del Total_Improductivo_Horas
6. Tres acciones para reducir tiempo improductivo con impacto estimado

Responde con tablas y números concretos.`
                },
                'performance': {
                    title: 'Análisis de Rendimiento',
                    prompt: `Analiza el rendimiento y eficiencia de velocidad de las líneas.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- OEE_Pct: OEE en porcentaje (0-100)
- Tiempo_Lento_Horas: Tiempo operando bajo velocidad óptima en horas
- UPM_Real: Unidades por minuto reales
- UPM_Teorico: Unidades por minuto teóricas
- Eficiencia_Pct: Eficiencia de velocidad (UPM_Real/UPM_Teorico * 100)

ANÁLISIS REQUERIDO:
1. Estadísticas de Eficiencia_Pct: media | mediana | desviación estándar
2. Distribución: % registros con Eficiencia >90 (óptimo) | 80-90 (aceptable) | <80 (crítico)
3. Top 5 con mayor gap entre UPM_Teorico y UPM_Real
4. Correlación entre Tiempo_Lento_Horas y Eficiencia_Pct
5. Correlación entre OEE_Pct y Eficiencia_Pct
6. Potencial de mejora: si todos operaran a UPM_Teorico
7. Tres recomendaciones para mejorar velocidad

Responde con tablas y números concretos.`
                },
                'operators': {
                    title: 'Análisis de Operadores',
                    prompt: `Analiza el impacto de operadores en el rendimiento de producción.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- OEE_Pct: OEE en porcentaje (0-100)
- Duracion_Horas: Tiempo total en horas decimales
- Tiempo_Ganado_Horas: Tiempo ganado sobre estándar (positivo = adelanto)
- Tiempo_Perdido_Horas: Tiempo perdido vs estándar
- Balance_Horas: Tiempo_Ganado - Tiempo_Perdido (positivo = eficiente)

ANÁLISIS REQUERIDO:
1. Estadísticas de Balance_Horas: media | mediana | suma total
2. Distribución: % registros con Balance >0 (eficientes) | Balance <0 (ineficientes)
3. Top 5 con mejor Balance_Horas y Bottom 5 con peor Balance
4. Correlación entre Balance_Horas y OEE_Pct
5. Correlación entre Duracion_Horas y Balance_Horas
6. Registros donde Tiempo_Perdido_Horas > Tiempo_Ganado_Horas
7. Tres recomendaciones para mejorar productividad

Responde con tablas y números concretos.`
                },
                'comparison': {
                    title: 'Comparativa Alto/Bajo',
                    prompt: `Compara las 10 mejores líneas (TOP) vs las 10 peores (BOTTOM) por OEE.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- Es_Top: 1 = Top 10 mejores | 0 = Bottom 10 peores
- OEE_Pct: OEE en porcentaje (0-100)
- Duracion_Horas: Tiempo total en horas decimales
- Preparacion_Horas: Tiempo de preparación
- Lento_Horas: Tiempo operando lento
- Paradas_Horas: Tiempo de paradas
- Falta_Material_Horas: Tiempo por falta de material
- Total_Improductivo_Horas: Suma de tiempo improductivo

ANÁLISIS REQUERIDO:
1. Grupo TOP (Es_Top=1): media OEE_Pct | media Total_Improductivo_Horas
2. Grupo BOTTOM (Es_Top=0): media OEE_Pct | media Total_Improductivo_Horas
3. Gap entre grupos: diferencia en OEE_Pct y en horas improductivas
4. Factor diferenciador principal: ¿Paradas | Lento | Falta_Material | Preparacion?
5. Correlación entre Total_Improductivo_Horas y OEE_Pct
6. Tres acciones para mejorar grupo BOTTOM con impacto estimado

Responde con tabla comparativa TOP vs BOTTOM.`
                },
                'availability-performance': {
                    title: 'Disponibilidad vs Rendimiento',
                    prompt: `Analiza la relación entre disponibilidad operativa y rendimiento.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- OEE_Pct: OEE en porcentaje (0-100)
- Duracion_Horas: Tiempo total en horas decimales
- Disponible_Horas: Tiempo efectivamente disponible
- Incidencias_Horas: Tiempo perdido en incidencias
- Pct_Disponibilidad: % de tiempo disponible (Disponible/Duracion*100)
- Pct_Incidencias: % de tiempo en incidencias

ANÁLISIS REQUERIDO:
1. Estadísticas de Pct_Disponibilidad: media | mediana | mínimo | máximo
2. Clasificación: % registros con Pct_Disponibilidad >85 (alta) | 70-85 (media) | <70 (baja)
3. Correlación entre Pct_Disponibilidad y OEE_Pct
4. Correlación entre Incidencias_Horas y OEE_Pct
5. Top 5 con mayor Pct_Incidencias
6. Registros con alta disponibilidad pero bajo OEE (problema de rendimiento)
7. Tres acciones para mejorar disponibilidad con impacto estimado

Responde con tablas y números concretos.`
                },
                'shift-variations': {
                    title: 'Variaciones por Turno/Operador',
                    prompt: `Analiza las variaciones de rendimiento por turno y operadores.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- Turno_ID: Identificador del turno (número)
- OEE_Pct: OEE en porcentaje (0-100)
- Duracion_Horas: Tiempo total en horas decimales
- Lento_Horas: Tiempo operando lento
- Ganado_Horas: Tiempo ganado sobre estándar
- Num_Operadores: Cantidad de operadores asignados
- Eficiencia_Tiempo: % eficiencia ((Duracion-Lento)/Duracion*100)

ANÁLISIS REQUERIDO:
1. Estadísticas por Turno_ID: media OEE_Pct | media Eficiencia_Tiempo
2. Variabilidad entre turnos: desviación estándar de OEE_Pct por turno
3. Correlación entre Num_Operadores y OEE_Pct
4. Correlación entre Lento_Horas y Eficiencia_Tiempo
5. Top 5 registros con mejor Eficiencia_Tiempo
6. Registros donde Lento_Horas > 20% de Duracion_Horas
7. Tres acciones para reducir variabilidad entre turnos

Responde con tablas por turno y números concretos.`
                },
                'idle-time': {
                    title: 'Consumo de Tiempo Improductivo',
                    prompt: `Analiza la distribución del tiempo improductivo en las líneas.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- OEE_Pct: OEE en porcentaje (0-100)
- Lento_Horas: Tiempo operando lento
- Paradas_Horas: Tiempo de paradas
- Falta_Material_Horas: Tiempo por falta de material
- Neto_Horas: Tiempo neto productivo
- Total_Improductivo_Horas: Suma de tiempo improductivo
- Pct_Improductivo: % de tiempo improductivo sobre total

ANÁLISIS REQUERIDO:
1. Estadísticas de Total_Improductivo_Horas: media | suma total | máximo
2. Composición promedio: % Lento | % Paradas | % Falta_Material
3. Top 5 registros con mayor Pct_Improductivo
4. Clasificación por causa dominante: donde Lento | Paradas | Falta_Material > 50%
5. Correlación entre Total_Improductivo_Horas y OEE_Pct
6. Ratio Neto/Improductivo: mejor y peor caso
7. Tres acciones para recuperar horas productivas

Responde con tablas y números concretos.`
                },
                'shift-profitability': {
                    title: 'Rentabilidad por Turno',
                    prompt: `Analiza la rentabilidad y eficiencia por turno de producción.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial del grupo
- Turno_ID: Identificador del turno
- Ordenes: Cantidad de órdenes procesadas
- OEE_Promedio_Pct: OEE promedio del turno (0-100)
- Duracion_Horas: Tiempo total del turno en horas
- Improductivo_Horas: Tiempo improductivo total
- Neto_Horas: Tiempo neto productivo
- Lento_Horas | Paradas_Horas | Falta_Material_Horas | Preparacion_Horas: Desglose improductivo
- Kg_Total: Kilogramos producidos
- Cajas_Total: Cajas producidas
- Pct_Productivo: % de tiempo productivo

ANÁLISIS REQUERIDO:
1. Ranking por Turno_ID: media OEE_Promedio_Pct | media Pct_Productivo | suma Kg_Total
2. Productividad: Kg_Total / Neto_Horas por turno
3. Eficiencia: turno con mejor y peor Pct_Productivo
4. Composición improductivo por turno: % Lento | % Paradas | % Falta_Material | % Preparacion
5. Correlación entre Ordenes y OEE_Promedio_Pct
6. Gap de productividad en Kg/hora entre mejor y peor turno
7. Tres acciones para mejorar rentabilidad

Responde con tablas comparativas por turno.`
                },
                'full': {
                    title: 'Análisis Total (CSV extendido)',
                    prompt: `Genera un análisis ejecutivo integral de todas las líneas de producción.

COLUMNAS DEL CSV (todas numéricas):
- ID: Identificador secuencial
- OEE_Pct: OEE en porcentaje (0-100)
- Duracion_Horas: Tiempo total en horas
- Diferencia_Horas: Diferencia vs tiempo teórico
- Preparacion_Horas | Lento_Horas | Paradas_Horas | Falta_Material_Horas: Desglose improductivo
- Neto_Horas: Tiempo neto productivo
- Pct_Productivo: % de tiempo productivo
- Num_Operadores: Cantidad de operadores

ANÁLISIS REQUERIDO:
1. Resumen ejecutivo: OEE promedio | % tiempo productivo | principal problema detectado
2. Estadísticas de OEE_Pct: media | mediana | desviación estándar | percentil 90
3. Distribución OEE: % registros >85 | 70-85 | <70
4. Composición tiempo improductivo: % Preparacion | % Lento | % Paradas | % Falta_Material
5. Correlación entre Num_Operadores y OEE_Pct
6. Top 5 registros con mejor OEE y Bottom 5 con peor OEE
7. Tres riesgos críticos identificados con magnitud
8. Tres oportunidades de mejora con impacto estimado en puntos de OEE
9. Plan de acción: 3 acciones prioritarias

Responde con estructura de informe ejecutivo y números concretos.`
                }
            };

            // Variable global para guardar el prompt y título actual
            let currentPromptData = null;
            
            // Click en opciones del dropdown
            $('.dropdown-item[data-analysis]').on('click', function(e) {
                e.preventDefault();
                const analysisType = $(this).data('analysis');
                const config = analysisPrompts[analysisType];
                
                if (!config) return;
                
                // Recolectar datos según el tipo de análisis
                let data;
                switch(analysisType) {
                    case 'oee-general':
                        data = collectOEEGeneralData();
                        break;
                    case 'stops':
                        data = collectStopsData();
                        break;
                    case 'performance':
                        data = collectPerformanceData();
                        break;
                    case 'operators':
                        data = collectOperatorsData();
                        break;
                    case 'comparison':
                        data = collectComparisonData();
                        break;
                    case 'availability-performance':
                        data = collectAvailabilityPerformanceData();
                        break;
                    case 'shift-variations':
                        data = collectShiftVariationsData();
                        break;
                    case 'shift-profitability':
                        data = collectShiftProfitabilityData();
                        break;
                    case 'idle-time':
                        data = collectIdleTimeData();
                        break;
                    case 'full':
                        data = collectFullAnalysisData();
                        break;
                    default:
                        return;
                }
                
                // Verificar si hay datos
                if (!data.csv || data.csv.trim() === '' || data.csv.split('\n').length <= 1) {
                    alert('No hay datos disponibles para analizar. Por favor, ejecuta primero una búsqueda.');
                    return;
                }
                
                // Construir prompt final
                let finalPrompt = `${config.prompt}\n\n=== PERIODO ===\n${data.metrics.dateRange}\n\n`;
                
                // Añadir métricas específicas
                finalPrompt += '=== MÉTRICAS ===\n';
                Object.keys(data.metrics).forEach(key => {
                    if (key !== 'dateRange') {
                        finalPrompt += `${key}: ${data.metrics[key]}\n`;
                    }
                });
                
                finalPrompt += `\n=== DATOS (CSV) ===\n${data.csv}`;
                
                console.log(`[AI] Análisis: ${config.title}`);
                console.log(`[AI] Tamaño prompt: ${finalPrompt.length} caracteres`);
                
                // Guardar prompt y título para enviarlo después
                currentPromptData = {
                    prompt: finalPrompt,
                    title: config.title
                };
                
                // Mostrar modal de edición
                $('#aiEditModalTitle').text(config.title);
                $('#aiPromptEdit').val(finalPrompt);
                const editModal = new bootstrap.Modal(document.getElementById('aiPromptEditModal'));
                editModal.show();
            });
            
            // Click en botón de enviar después de editar
            $('#btn-ai-send-edited').on('click', function() {
                if (!currentPromptData) return;

                // Obtener el prompt editado
                const editedPrompt = $('#aiPromptEdit').val().trim();

                if (!editedPrompt) {
                    alert('El prompt no puede estar vacío');
                    return;
                }

                // Obtener el agente seleccionado
                const selectedAgent = $('input[name="aiAgentType"]:checked').val() || 'supervisor';

                // Deshabilitar botón y mostrar spinner
                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin me-1"></i>Enviando...');

                // Cerrar modal de edición
                bootstrap.Modal.getInstance(document.getElementById('aiPromptEditModal')).hide();

                // Enviar a IA con el agente seleccionado
                startAiTask(editedPrompt, currentPromptData.title, selectedAgent).finally(() => {
                    // Restaurar botón
                    $btn.prop('disabled', false);
                    $btn.html('<i class="fas fa-paper-plane me-1"></i>Enviar a IA');
                });
            });
        });
    </script>

    <!-- AI Prompt Edit Modal -->
    <div class="modal fade" id="aiPromptEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i><span id="aiEditModalTitle">Editar Prompt</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">@lang('Tipo de Agente IA'):</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check border rounded p-3 h-100" style="cursor: pointer;" onclick="$('#agentSupervisor').prop('checked', true);">
                                    <input class="form-check-input" type="radio" name="aiAgentType" id="agentSupervisor" value="supervisor" checked>
                                    <label class="form-check-label w-100" for="agentSupervisor" style="cursor: pointer;">
                                        <span class="fw-bold text-primary"><i class="fas fa-user-tie me-1"></i>Supervisor</span>
                                        <small class="text-muted d-block mt-1">Respuestas más descriptivas y elaboradas. Ideal para informes ejecutivos.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-3 h-100" style="cursor: pointer;" onclick="$('#agentDataAnalysis').prop('checked', true);">
                                    <input class="form-check-input" type="radio" name="aiAgentType" id="agentDataAnalysis" value="data_analysis">
                                    <label class="form-check-label w-100" for="agentDataAnalysis" style="cursor: pointer;">
                                        <span class="fw-bold text-success"><i class="fas fa-chart-line me-1"></i>Data Analysis</span>
                                        <small class="text-muted d-block mt-1">Respuestas técnicas y estrictas. Ideal para análisis estadístico.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <label class="form-label fw-bold">Prompt a enviar (puedes editarlo):</label>
                    <textarea class="form-control font-monospace" id="aiPromptEdit" rows="15" style="font-size: 0.9rem;"></textarea>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>
                        Este prompt incluye las instrucciones, métricas y datos CSV. Puedes modificarlo antes de enviar.
                    </small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancelar')</button>
                    <button type="button" class="btn btn-primary" id="btn-ai-send-edited">
                        <i class="fas fa-paper-plane me-1"></i>@lang('Enviar a IA')
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Processing Modal -->
    <div class="modal fade" id="aiProcessingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="fas fa-robot me-2"></i><span id="aiProcessingTitle">Procesando...</span></h5>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                    <p class="text-muted mb-0" id="aiProcessingStatus">
                        <i class="fas fa-spinner fa-spin me-2"></i>Procesando solicitud...
                    </p>
                    <small class="text-muted d-block mt-2">
                        Esto puede tardar varios segundos. Por favor, espere...
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Result Modal (Mejorado) -->
    <div class="modal fade" id="aiResultModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" id="aiResultModalDialog" style="max-width: 80%; width: 80%;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="flex-grow-1">
                        <h5 class="modal-title mb-1">@lang('Resultado IA')</h5>
                        <small class="text-muted ai-metadata">
                            <i class="fas fa-clock me-1"></i><span id="aiResultTimestamp"></span>
                            <span class="mx-2">|</span>
                            <i class="fas fa-align-left me-1"></i><span id="aiResultStats"></span>
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body position-relative">
                    <!-- Barra de progreso de scroll -->
                    <div class="scroll-progress-bar" id="aiScrollProgress"></div>

                    <!-- Tipo de análisis -->
                    <p class="text-muted mb-3"><strong>@lang('Tipo de Análisis'):</strong> <span id="aiResultPrompt"></span></p>

                    <!-- Barra de herramientas -->
                    <div class="ai-toolbar">
                        <!-- Control de tamaño de fuente -->
                        <div class="btn-group btn-group-sm font-controls" role="group" aria-label="Controles de fuente">
                            <button type="button" class="btn btn-outline-secondary" id="btnFontDecrease" title="Reducir tamaño">
                                <i class="fas fa-minus"></i> A-
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFontReset" title="Tamaño normal">
                                A
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFontIncrease" title="Aumentar tamaño">
                                <i class="fas fa-plus"></i> A+
                            </button>
                        </div>

                        <!-- Botones de acción -->
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones">
                            <button type="button" class="btn btn-outline-primary" id="btnCopyResult" title="Copiar al portapapeles">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <button type="button" class="btn btn-outline-success" id="btnDownloadResult" title="Descargar como archivo">
                                <i class="fas fa-download"></i> Descargar
                            </button>
                            <button type="button" class="btn btn-outline-info" id="btnPrintResult" title="Imprimir o guardar como PDF">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnFullscreen" title="Pantalla completa">
                                <i class="fas fa-expand"></i> Pantalla completa
                            </button>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" id="aiResultTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="ai-tab-rendered" data-bs-toggle="tab" data-bs-target="#aiResultRendered" type="button" role="tab" aria-controls="aiResultRendered" aria-selected="true">
                                <i class="fas fa-eye me-1"></i>Vista Formateada
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ai-tab-raw" data-bs-toggle="tab" data-bs-target="#aiResultRaw" type="button" role="tab" aria-controls="aiResultRaw" aria-selected="false">
                                <i class="fas fa-file-alt me-1"></i>Texto Plano
                            </button>
                        </li>
                    </ul>

                    <!-- Contenido de las tabs -->
                    <div class="tab-content" id="aiResultTabContent">
                        <!-- Tab: Vista Formateada (Markdown parseado) -->
                        <div class="tab-pane fade show active" id="aiResultRendered" role="tabpanel" aria-labelledby="ai-tab-rendered">
                            <div id="aiResultHtml" class="ai-result-content"></div>
                        </div>

                        <!-- Tab: Texto Plano -->
                        <div class="tab-pane fade" id="aiResultRaw" role="tabpanel" aria-labelledby="ai-tab-raw">
                            <pre id="aiResultText" class="bg-light p-3 rounded" style="white-space: pre-wrap; min-height: 200px; overflow: auto;"></pre>
                        </div>
                    </div>

                    <!-- Botón flotante "Volver arriba" -->
                    <button type="button" id="btnScrollTop" class="btn btn-primary" title="Volver arriba">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Librerías para parsing de Markdown y seguridad -->
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
    <script>
        // Configurar marked.js para mejor compatibilidad con Markdown
        if (window.marked) {
            marked.setOptions({
                breaks: true,        // Convertir saltos de línea en <br>
                gfm: true,          // GitHub Flavored Markdown
                headerIds: true,    // Generar IDs para encabezados
                mangle: false,      // No modificar emails
                sanitize: false     // No sanitizar (lo haremos con DOMPurify)
            });
        }
    </script>

    <script>
        const token = new URLSearchParams(window.location.search).get('token');
        console.log("Token obtenido:", token);

        // Función para formatear segundos a HH:MM:SS
        function formatTime(seconds) {
            if (seconds === null || seconds === undefined || isNaN(seconds) || seconds === 0) return '00:00:00';
            seconds = parseInt(seconds);
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;
            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        async function fetchProductionLines() {
            try {
                console.log("Intentando obtener líneas de producción...");
                const response = await fetch(`/api/production-lines/${token}`);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Líneas de producción recibidas:", data);

                const modbusSelect = $('#modbusSelect');
                modbusSelect.empty();
                
                // Ordenar las líneas de producción alfabéticamente por nombre
                data.sort((a, b) => a.name.localeCompare(b.name));
                
                data.forEach(line => {
                    modbusSelect.append(`<option value="${line.token}">${line.name}</option>`);
                });
                
                // Inicializar Select2 para líneas de producción
                modbusSelect.select2({
                    placeholder: "Seleccionar líneas",
                    allowClear: true
                });
                
                // Cargar operarios
                fetchOperators();
            } catch (error) {
                console.error("Error al cargar líneas de producción:", error);
            }
        }
        
        // Función para cargar los operarios disponibles
        async function fetchOperators() {
            try {
                console.log("Intentando obtener operarios con IDs internos...");
                const response = await fetch('/api/operators/internal');
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const operators = await response.json();
                console.log("Operarios con IDs internos recibidos:", operators);
                
                const operatorSelect = $('#operatorSelect');
                operatorSelect.empty();
                
                // Ordenar los operarios alfabéticamente por nombre
                if (Array.isArray(operators)) {
                    operators.sort((a, b) => a.name.localeCompare(b.name));
                    
                    operators.forEach(operator => {
                        operatorSelect.append(`<option value="${operator.id}">${operator.name}</option>`);
                    });
                } else {
                    console.error("El formato de datos de operarios no es válido:", operators);
                }
                
                // Inicializar Select2 para operarios
                operatorSelect.select2({
                    placeholder: "Seleccionar empleados",
                    allowClear: true
                });
            } catch (error) {
                console.error("Error al cargar operarios:", error);
            }
        }

        async function fetchOrderStats(lineTokens, startDate, endDate) {
            try {
                const tokensArray = Array.isArray(lineTokens) ? lineTokens : [lineTokens];
                const filteredTokens = tokensArray.filter(token => token && token.trim() !== '');
                const selectedOperators = $('#operatorSelect').val();
                
                // Determinar el modo de filtrado basado en la selección de líneas y operadores
                let filterMode = 'line_only'; // Por defecto, filtrar solo por línea
                
                if (selectedOperators && selectedOperators.length > 0) {
                    // Si hay operadores seleccionados, priorizar el filtrado por operador
                    filterMode = 'operator_only';
                }
                
                if (filteredTokens.length === 0) {
                    throw new Error('No hay tokens válidos seleccionados');
                }
                
                const tokenParam = filteredTokens.join(',');
                let url = `/api/order-stats-all?token=${tokenParam}&start_date=${startDate}&end_date=${endDate}`;
                
                // Añadir operadores seleccionados a la URL si hay alguno
                if (selectedOperators && selectedOperators.length > 0) {
                    const operatorParam = selectedOperators.join(',');
                    url += `&operators=${operatorParam}`;
                    
                    // Añadir el modo de filtrado a la URL
                    url += `&filter_mode=${filterMode}`;
                }
                
                // Añadir filtros de OEE si están activados
                const hideZeroOEE = $('#hideZeroOEE').is(':checked');
                const hide100OEE = $('#hide100OEE').is(':checked');
                
                if (hideZeroOEE) {
                    url += `&hide_zero_oee=1`;
                }
                if (hide100OEE) {
                    url += `&hide_100_oee=1`;
                }
                
                await ensureShiftsLoadedForTokens(filteredTokens);
                const fullUrl = window.location.origin + url;
                console.log("URL COMPLETA de la API:", fullUrl);
                console.log("==================================================");
                console.log("COPIABLE:", fullUrl);
                console.log("==================================================");

                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Datos de estadísticas recibidos (ya filtrados por backend):", data);

                // Procesar los datos para asegurar que tienen la estructura correcta
                const processedData = data.map(item => {
                    console.log('Procesando item:', item.id, 'down_time:', item.down_time, 'production_stops_time:', item.production_stops_time);
                    return {
                        id: item.id || '-',
                        production_line_name: item.production_line_name || '-',
                        production_line_id: item.production_line_id || null,
                        order_id: item.order_id || '-',
                        box: item.box || '-',
                        units: parseInt(item.units) || 0,
                        units_per_minute_real: parseFloat(item.units_per_minute_real) || 0,
                        units_per_minute_theoretical: parseFloat(item.units_per_minute_theoretical) || 0,
                        oee: parseFloat(item.oee) || 0,
                        status: item.status || 'unknown',
                        created_at: item.created_at || null,
                        updated_at: item.updated_at || null,
                        down_time: item.down_time !== undefined ? parseFloat(item.down_time) : 0,
                        production_stops_time: item.production_stops_time !== undefined ? parseFloat(item.production_stops_time) : 0,
                        on_time: item.on_time || null,
                        operator_names: item.operator_names || [],
                        fast_time: item.fast_time || null,
                        slow_time: item.slow_time || null,
                        out_time: item.out_time || null,
                        prepair_time: item.prepair_time || null,
                        // Báscula final de línea (main)
                        weights_0_shiftNumber: item.weights_0_shiftNumber ?? null,
                        weights_0_shiftKg: item.weights_0_shiftKg ?? null,
                        weights_0_orderNumber: item.weights_0_orderNumber ?? null,
                        weights_0_orderKg: item.weights_0_orderKg ?? null,
                        // Básculas de rechazo (1-3)
                        weights_1_shiftNumber: item.weights_1_shiftNumber ?? null,
                        weights_1_shiftKg: item.weights_1_shiftKg ?? null,
                        weights_1_orderNumber: item.weights_1_orderNumber ?? null,
                        weights_1_orderKg: item.weights_1_orderKg ?? null,
                        weights_2_shiftNumber: item.weights_2_shiftNumber ?? null,
                        weights_2_shiftKg: item.weights_2_shiftKg ?? null,
                        weights_2_orderNumber: item.weights_2_orderNumber ?? null,
                        weights_2_orderKg: item.weights_2_orderKg ?? null,
                        weights_3_shiftNumber: item.weights_3_shiftNumber ?? null,
                        weights_3_shiftKg: item.weights_3_shiftKg ?? null,
                        weights_3_orderNumber: item.weights_3_orderNumber ?? null,
                        weights_3_orderKg: item.weights_3_orderKg ?? null
                    }});
                
                // Actualizar los KPIs con los datos ya filtrados del backend
                updateKPIs(processedData);
                
                // Limpiar cualquier estado de carga previo
                $('#loadingIndicator').hide();
                $('#controlWeightTable').show();
                
                // Destruir la tabla existente de forma segura antes de reinicializar
                if ($.fn.DataTable.isDataTable('#controlWeightTable')) {
                    $('#controlWeightTable').DataTable().destroy();
                }
                // Limpiar el contenido HTML para evitar conflictos
                $('#controlWeightTable').empty();

                const table = $('#controlWeightTable').DataTable({
                    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                         "<'row'<'col-sm-12'tr>>" +
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                    buttons: [],
                    responsive: {
                        details: {
                            type: 'column',
                            target: 0,
                            renderer: function(api, rowIdx, columns) {
                                let data = '<div class="ls-child-row">';
                                columns.forEach(function(col, i) {
                                    if (col.hidden) {
                                        data += '<div class="ls-child-item">' +
                                            '<span class="ls-child-label">' + col.title + '</span>' +
                                            '<span class="ls-child-value">' + col.data + '</span>' +
                                            '</div>';
                                    }
                                });
                                data += '</div>';
                                return data;
                            }
                        }
                    },
                    data: processedData,
                    columns: [
                        { data: null, defaultContent: '', className: 'dtr-control', orderable: false, responsivePriority: 1 },
                        { data: 'production_line_name', title: 'Línea', className: 'text-truncate', responsivePriority: 1, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Línea: ${cellData}`);
                        }},
                        { data: 'order_id', title: 'Orden', className: 'text-truncate', responsivePriority: 2, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Orden: ${cellData}`);
                        }},
                        { data: 'operator_names', title: 'Empleados', className: 'text-truncate', responsivePriority: 5, render: function(data, type, row) {
                            if (!data || data.length === 0) return '<span class="text-muted">Sin asignar</span>';
                            const names = Array.isArray(data) ? data : [data];
                            const displayNames = names.slice(0, 2).join(', ');
                            const remaining = names.length > 2 ? ` +${names.length - 2} más` : '';
                            return `<span title="${names.join(', ')}">${displayNames}${remaining}</span>`;
                        }},
                        { data: 'oee', title: 'OEE', responsivePriority: 1, render: data => `${Math.round(data)}%`, createdCell: function(td, cellData, rowData) {
                            const color = cellData >= 80 ? 'success' : cellData >= 60 ? 'warning' : 'danger';
                            $(td).html(`<span class="ls-oee-badge ls-oee-${color}">${Math.round(cellData)}%</span>`);
                            $(td).attr('title', `OEE: ${Math.round(cellData)}%\nEstado: ${cellData >= 80 ? 'Excelente' : cellData >= 60 ? 'Aceptable' : 'Necesita mejora'}`);
                        }},
                        { data: 'status', title: 'Estado', responsivePriority: 2, render: data => {
                            const statusMap = {
                                'active': '<span class="ls-status-badge ls-status-active">Activo</span>',
                                'paused': '<span class="ls-status-badge ls-status-paused">Pausado</span>',
                                'error': '<span class="ls-status-badge ls-status-error">Incidencia</span>',
                                'completed': '<span class="ls-status-badge ls-status-completed">Completado</span>',
                                'in_progress': '<span class="ls-status-badge ls-status-progress">En Progreso</span>',
                                'pending': '<span class="ls-status-badge ls-status-pending">Planificada</span>'
                            };
                            return statusMap[data] || '<span class="ls-status-badge ls-status-pending">Desconocido</span>';
                        }, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Estado actual: ${cellData}`);
                        }},
                        { data: 'created_at', title: 'Iniciado', responsivePriority: 6, render: data => new Date(data).toLocaleString(), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Inicio: ${new Date(data).toLocaleString()}`);
                        }},
                        { data: 'updated_at', title: 'Últ. actualización', responsivePriority: 7, render: data => data ? new Date(data).toLocaleString() : '-', createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Última actualización: ${data ? new Date(data).toLocaleString() : '-'}`);
                        }},
                        { data: 'on_time', title: 'Duración', responsivePriority: 3, render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Duración: ${formatTime(cellData)}`);
                        }},
                        { data: null, title: 'Diferencia', responsivePriority: 4, render: function(data, type, row) {
                            if (row.fast_time && parseInt(row.fast_time) > 0) {
                                return '<span class="ls-time-badge ls-time-positive">+' + formatTime(row.fast_time) + '</span>';
                            } else if (row.out_time && parseInt(row.out_time) > 0) {
                                return '<span class="ls-time-badge ls-time-negative">-' + formatTime(row.out_time) + '</span>';
                            } else {
                                return '<span class="ls-time-badge ls-time-neutral">00:00:00</span>';
                            }
                        }, createdCell: function(td, cellData, rowData) {
                            if (rowData.fast_time && parseInt(rowData.fast_time) > 0) {
                                $(td).attr('title', `Tiempo ganado: ${formatTime(rowData.fast_time)}`);
                            } else if (rowData.out_time && parseInt(rowData.out_time) > 0) {
                                $(td).attr('title', `Tiempo de más: ${formatTime(rowData.out_time)}`);
                            }
                        }},
                        { data: 'prepair_time', title: 'Preparación', responsivePriority: 8, render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Tiempo de preparación: ${formatTime(cellData)}`);
                        }},
                        { data: 'slow_time', title: 'Lento', responsivePriority: 9, render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Tiempo en velocidad lenta: ${formatTime(cellData)}`);
                        }},
                        { data: 'down_time', title: 'Paradas', responsivePriority: 10, render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Paradas no justificadas: ${formatTime(cellData)}`);
                        }},
                        { data: 'production_stops_time', title: 'Falta material', responsivePriority: 11, render: data => formatTime(data), createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', `Parada falta material: ${formatTime(cellData)}`);
                        }},
                        { data: null, title: '', orderable: false, responsivePriority: 1, className: 'text-end', render: function(data, type, row) {
                            return `<button class="ls-action-btn" onclick="showDetailsModal(${JSON.stringify(row).replace(/"/g, '&quot;')})">
                                <i class="fas fa-eye"></i>
                            </button>`;
                        }, createdCell: function(td, cellData, rowData) {
                            $(td).attr('title', 'Ver detalles completos');
                        }}
                    ],
                    order: [[2, 'desc']],
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    searching: true,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                    },
                    drawCallback: function() {
                        // Estilizar los controles después de cada redibujado
                        $('.dataTables_filter input').addClass('ls-search-input');
                        $('.dataTables_length select').addClass('ls-length-select');
                    },
                    initComplete: function() {
                        $('.dataTables_filter input').addClass('ls-search-input');
                        $('.dataTables_length select').addClass('ls-length-select');
                    }
                });

                // Configurar event listeners para los checkboxes de filtrado OEE
                $('#hideZeroOEE, #hide100OEE').off('change').on('change', function() {
                    // Recargar datos cuando cambien los filtros de OEE
                    $('#fetchData').click();
                });

            } catch (error) {
                console.error("Error al cargar datos:", error);
            }
        }

        // Función para actualizar los KPIs
        function updateKPIs(data) {
            console.log('Actualizando KPIs con', data.length, 'registros filtrados');

            const hasNumericValue = (value) => value !== null && value !== undefined && !isNaN(value);

            let totalDurationSeconds = 0;
            let totalOEE = 0;
            let validOEECount = 0;
            let totalFastTime = 0;
            let totalOutTime = 0;
            let totalDownTime = 0;
            let totalProductionStopsTime = 0;
            let totalPrepairTime = 0;
            let totalSlowTime = 0;

            data.forEach(item => {
                if (hasNumericValue(item.on_time)) {
                    totalDurationSeconds += Number(item.on_time);
                }

                if (hasNumericValue(item.oee)) {
                    const oeeValue = parseFloat(item.oee);
                    totalOEE += oeeValue > 1 ? oeeValue : oeeValue * 100;
                    validOEECount++;
                }

                if (hasNumericValue(item.fast_time)) {
                    totalFastTime += Number(item.fast_time);
                }

                if (hasNumericValue(item.out_time)) {
                    totalOutTime += Number(item.out_time);
                }

                if (hasNumericValue(item.down_time)) {
                    totalDownTime += Number(item.down_time);
                }

                if (hasNumericValue(item.production_stops_time)) {
                    totalProductionStopsTime += Number(item.production_stops_time);
                }

                if (hasNumericValue(item.prepair_time)) {
                    totalPrepairTime += Number(item.prepair_time);
                }

                if (hasNumericValue(item.slow_time)) {
                    totalSlowTime += Number(item.slow_time);
                }
            });

            $('#totalDuration').text(formatTime(totalDurationSeconds));

            const avgOEE = validOEECount > 0 ? totalOEE / validOEECount : 0;
            console.log('Cálculo OEE:', {
                totalOEE: totalOEE,
                validOEECount: validOEECount,
                avgOEE: avgOEE,
                roundedAvgOEE: Math.round(avgOEE)
            });
            $('#avgOEE').text(`${Math.round(avgOEE)}%`);

            if (avgOEE >= 80) {
                $('#avgOEE').removeClass('text-danger text-warning').addClass('text-success');
            } else if (avgOEE >= 60) {
                $('#avgOEE').removeClass('text-danger text-success').addClass('text-warning');
            } else {
                $('#avgOEE').removeClass('text-success text-warning').addClass('text-danger');
            }

            if (totalFastTime >= totalOutTime) {
                $('#totalTheoretical').removeClass('text-danger').addClass('text-success');
            } else {
                $('#totalTheoretical').removeClass('text-success').addClass('text-danger');
            }

            const netTheoreticalTime = Math.abs(totalFastTime - totalOutTime);
            $('#totalTheoretical').text(formatTime(netTheoreticalTime));

            $('#totalPrepairTime').text(formatTime(totalPrepairTime));
            $('#totalSlowTime').text(formatTime(totalSlowTime));

            // Paradas => down_time, Falta Material => production_stops_time (requerimiento previo)
            $('#totalProductionStopsTime').text(formatTime(totalDownTime));
            $('#totalDownTime').text(formatTime(totalProductionStopsTime));
        }

        // Inicializar fechas por defecto
        function initializeDates() {
            const now = new Date();
            const oneWeekAgo = new Date();
            oneWeekAgo.setDate(now.getDate() - 7);
            
            // Formato YYYY-MM-DDThh:mm
            const formatDate = (date) => {
                return date.toISOString().slice(0, 16);
            };
            
            $('#startDate').val(formatDate(oneWeekAgo));
            $('#endDate').val(formatDate(now));
        }

        // Función para mostrar detalles en el modal
        function showDetailsModal(row) {
            console.log('Mostrando detalles de la fila:', row);
            console.log('OEE de la fila:', row.oee);
            
            // Actualizar datos generales en el modal
            $('#modal-line-name').text(row.production_line_name || '-');
            $('#modal-order-id').text(row.order_id || '-');
            $('#modal-box').text(row.box || '-');
            $('#modal-units').text(row.units ? row.units.toLocaleString() : '0');
            $('#modal-upm-real').text(row.units_per_minute_real ? parseFloat(row.units_per_minute_real).toFixed(2) : '0.00');
            $('#modal-upm-theoretical').text(row.units_per_minute_theoretical ? parseFloat(row.units_per_minute_theoretical).toFixed(2) : '0.00');
            
            // Actualizar tiempos de producción
            $('#modal-on-time').text(row.on_time !== null && row.on_time !== undefined ? formatTime(row.on_time) : '-');
            $('#modal-fast-time').text(row.fast_time !== null && row.fast_time !== undefined ? formatTime(row.fast_time) : '-');
            $('#modal-slow-time').text(row.slow_time !== null && row.slow_time !== undefined ? formatTime(row.slow_time) : '-');
            $('#modal-out-time').text(row.out_time !== null && row.out_time !== undefined ? formatTime(row.out_time) : '-');
            $('#modal-down-time').text(row.down_time !== null && row.down_time !== undefined ? formatTime(row.down_time) : '-');
            $('#modal-production-stops-time').text(row.production_stops_time !== null && row.production_stops_time !== undefined ? formatTime(row.production_stops_time) : '-');
            $('#modal-prepair-time').text(row.prepair_time !== null && row.prepair_time !== undefined ? formatTime(row.prepair_time) : '-');
            
            // Función auxiliar para verificar si un valor tiene datos reales
            const hasRealData = (value) => {
                return value !== null && value !== undefined && value !== '' && value !== '-' && value !== 0 && value !== '0';
            };
            
            // Verificar si hay datos en básculas
            const hasMainScaleData = (
                hasRealData(row.weights_0_shiftNumber) ||
                hasRealData(row.weights_0_shiftKg) ||
                hasRealData(row.weights_0_orderNumber) ||
                hasRealData(row.weights_0_orderKg)
            );
            
            // Variable para verificar si hay datos en básculas de rechazo
            let hasRejectionScaleData = false;
            
            // Actualizar datos de báscula final de línea (weights_0)
            $('#modal-weights-0-shift-number').text(row.weights_0_shiftNumber !== null && row.weights_0_shiftNumber !== undefined ? row.weights_0_shiftNumber : '-');
            $('#modal-weights-0-shift-kg').text(row.weights_0_shiftKg !== null && row.weights_0_shiftKg !== undefined ? row.weights_0_shiftKg : '-');
            $('#modal-weights-0-order-number').text(row.weights_0_orderNumber !== null && row.weights_0_orderNumber !== undefined ? row.weights_0_orderNumber : '-');
            $('#modal-weights-0-order-kg').text(row.weights_0_orderKg !== null && row.weights_0_orderKg !== undefined ? row.weights_0_orderKg : '-');
            
            // Actualizar básculas de rechazo (weights_1, weights_2, weights_3)
            const rejectionWeightsContainer = $('#weights-rejection-container');
            rejectionWeightsContainer.empty(); // Limpiar contenedor
            
            // Comprobar y mostrar básculas de rechazo (1-3)
            for (let i = 1; i <= 3; i++) {
                const shiftNumber = row[`weights_${i}_shiftNumber`];
                const shiftKg = row[`weights_${i}_shiftKg`];
                const orderNumber = row[`weights_${i}_orderNumber`];
                const orderKg = row[`weights_${i}_orderKg`];
                
                // Solo mostrar si hay al menos un valor real
                if (hasRealData(shiftNumber) || hasRealData(shiftKg) || hasRealData(orderNumber) || hasRealData(orderKg)) {
                    hasRejectionScaleData = true;
                    const weightHtml = `
                        <div class="mb-3">
                            <h6 class="text-secondary">Báscula ${i}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="fw-bold">Nº en Turno:</label>
                                    <span>${shiftNumber !== null && shiftNumber !== undefined ? shiftNumber : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Kg en Turno:</label>
                                    <span>${shiftKg !== null && shiftKg !== undefined ? shiftKg : '-'}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <label class="fw-bold">Nº en Orden:</label>
                                    <span>${orderNumber !== null && orderNumber !== undefined ? orderNumber : '-'}</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-bold">Kg en Orden:</label>
                                    <span>${orderKg !== null && orderKg !== undefined ? orderKg : '-'}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    rejectionWeightsContainer.append(weightHtml);
                }
            }
            
            // Si no hay básculas de rechazo, mostrar mensaje
            if (rejectionWeightsContainer.children().length === 0) {
                rejectionWeightsContainer.html('<p class="text-muted">No hay datos de básculas de rechazo</p>');
            }
            
            // Ocultar o mostrar la sección completa de básculas según si hay datos
            const scaleCard = $('.card:has(.card-header:contains("Básculas"))');
            if (!hasMainScaleData && !hasRejectionScaleData) {
                scaleCard.hide();
            } else {
                scaleCard.show();
            }
            
            // Actualizar estado
            const statusMap = {
                'active': { text: 'Activo', class: 'bg-success' },
                'paused': { text: 'Pausado', class: 'bg-warning' },
                'error': { text: 'Incidencia', class: 'bg-danger' },
                'completed': { text: 'Completado', class: 'bg-primary' },
                'in_progress': { text: 'En Progreso', class: 'bg-info' },
                'pending': { text: 'Planificada', class: 'bg-secondary' }
            };
            const status = statusMap[row.status] || { text: 'Iniciada Anterior', class: 'bg-secondary' };
            $('#modal-status').text(status.text).removeClass().addClass('badge ' + status.class);
            
            // Asegurar que el OEE se pase correctamente al gráfico
            const oeeData = {
                oee: row.oee,
                units_per_minute_real: row.units_per_minute_real,
                units_per_minute_theoretical: row.units_per_minute_theoretical,
                ...row
            };
            
            // Crear gráfica de OEE
            createOEEChart(oeeData);
            
            // Actualizar fechas
            $('#modal-created-at').text(row.created_at ? new Date(row.created_at).toLocaleString() : '-');
            $('#modal-updated-at').text(row.updated_at ? new Date(row.updated_at).toLocaleString() : '-');
            
            // Mostrar el modal usando jQuery (compatible con la versión de Bootstrap del sistema)
            $('#detailsModal').modal('show');
            
            // Configurar el botón de cierre manualmente
            $('.btn-close, .btn-secondary').on('click', function() {
                $('#detailsModal').modal('hide');
            });
            
            // Asegurarse de que el canvas exista y sea visible antes de crear la gráfica
            $('#detailsModal').on('shown.bs.modal', function() {
                console.log('Modal mostrado, creando gráfica...');
                createOEEChart(row);
            });
        }
        
        // Función para crear la gráfica de OEE
        function createOEEChart(row) {
            console.log('Intentando crear gráfica OEE...');
            
            // Verificar si el canvas existe
            const canvas = document.getElementById('oeeChart');
            if (!canvas) {
                console.error('No se encontró el elemento canvas para la gráfica');
                // Intentar crear el canvas si no existe
                const chartContainer = document.querySelector('.card-body');
                if (chartContainer) {
                    console.log('Recreando el canvas...');
                    const canvasElement = document.createElement('canvas');
                    canvasElement.id = 'oeeChart';
                    canvasElement.height = 200;
                    chartContainer.appendChild(canvasElement);
                    return setTimeout(() => createOEEChart(row), 100); // Intentar de nuevo
                }
                return;
            }
            
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.error('No se pudo obtener el contexto 2d del canvas');
                return;
            }
            
            console.log('Canvas encontrado, creando gráfica...');
            
            // Destruir gráfica anterior si existe
            if (window.oeeChartInstance) {
                window.oeeChartInstance.destroy();
            }
            
            // Calcular OEE como porcentaje
            console.log('Datos de OEE recibidos:', row.oee, typeof row.oee);
            
            // Usar el valor de OEE directamente desde la API sin cálculos
            let oeePercentage = 0;
            if (row.oee !== null && row.oee !== undefined && !isNaN(row.oee)) {
                oeePercentage = parseFloat(row.oee);
            } else {
                oeePercentage = 0;
            }
            
            console.log('OEE directo desde API:', oeePercentage);
            
            // Crear nueva gráfica
            window.oeeChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['OEE', 'Restante'],
                    datasets: [{
                        data: [oeePercentage, 100 - oeePercentage],
                        backgroundColor: [
                            oeePercentage >= 80 ? '#28a745' : (oeePercentage >= 60 ? '#ffc107' : '#dc3545'),
                            '#f0f2f5'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.label}: ${Math.round(context.raw)}%`;
                                }
                            }
                        }
                    },
                    elements: {
                        center: {
                            text: `${Math.round(oeePercentage)}%`,
                            color: '#000',
                            fontStyle: 'Arial',
                            sidePadding: 20,
                            minFontSize: 20,
                            lineHeight: 25
                        }
                    }
                }
            });
            
            // Añadir texto en el centro del gráfico usando el valor actual del gráfico
            Chart.register({
                id: 'doughnutCenterText',
                afterDraw: function(chart) {
                    if (chart.config.type === 'doughnut' && chart.data.datasets[0]) {
                        const width = chart.width;
                        const height = chart.height;
                        const ctx = chart.ctx;
                        
                        ctx.restore();
                        const fontSize = (height / 114).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.textBaseline = "middle";
                        
                        // Obtener el valor OEE del dataset actual
                        const oeeValue = chart.data.datasets[0].data[0] || 0;
                        const text = `${Math.round(oeeValue)}%`;
                        const textX = Math.round((width - ctx.measureText(text).width) / 2);
                        const textY = height / 2;
                        
                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }
            });
        }

        $(document).ready(() => {
            initializeDates();
            fetchProductionLines();
            setupDateFilters();

            // Botón de refrescar datos
            $('#refreshData').click(function() {
                const selectedLines = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const selectedOperators = $('#operatorSelect').val();
                console.log("Parámetros seleccionados (refresh):", { selectedLines, startDate, endDate, selectedOperators });
                
                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    ensureMaxRange7Days();
                    $('#loadingIndicator').show();
                    $('#controlWeightTable').hide();
                    $(this).find('i').addClass('fa-spin');
                    fetchOrderStats(selectedLines, startDate, endDate);
                    setTimeout(() => { $(this).find('i').removeClass('fa-spin'); }, 1200);
                } else {
                    alert('Por favor selecciona líneas y fechas válidas.');
                }
            });

            $('#fetchData').click(() => {
                const selectedLines = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                const selectedOperators = $('#operatorSelect').val();
                console.log("Parámetros seleccionados:", { selectedLines, startDate, endDate, selectedOperators });

                if (selectedLines && selectedLines.length > 0 && startDate && endDate) {
                    ensureMaxRange7Days();
                    // Mostrar indicador de carga
                    $('#loadingIndicator').show();
                    $('#controlWeightTable').hide();
                    
                    fetchOrderStats(selectedLines, startDate, endDate);
                } else {
                    alert('Por favor selecciona líneas y fechas válidas.');
                }
            });

            // Resetear filtros
            $('#resetFilters').click(() => {
                initializeDates();
                $('#modbusSelect').val([]).trigger('change');
            });

            // Configurar eventos para los botones de exportación
            $('#exportExcel').on('click', () => exportData('excel'));
            $('#exportPDF').on('click', () => exportData('pdf'));
            $('#printTable').on('click', () => exportData('print'));
        });

        // Función para configurar filtros de fecha
        function setupDateFilters() {
            // Configuración de Select2 para el select múltiple
            $('#modbusSelect').select2({
                placeholder: 'Selecciona líneas de producción...',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "No se encontraron resultados";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                }
            });
        }    

        // Función para exportar datos
        function exportData(type) {
            const table = $('#controlWeightTable').DataTable();
            if (!table) {
                alert('No hay datos para exportar');
                return;
            }

            // Mapeo de estados
            const statusMap = {
                'active': 'Activo',
                'in_progress': 'En Progreso',
                'completed': 'Completado',
                'paused': 'Pausado',
                'error': 'Incidencia',
                'pending': 'Planificada',
                'unknown': 'Desconocido'
            };

            // Función auxiliar para formatear tiempo
            function formatTimeExport(seconds) {
                if (!seconds || seconds === 0) return '00:00:00';
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = seconds % 60;
                return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            }

            // Función para obtener empleados como texto
            function getOperatorNames(data) {
                if (!data || data.length === 0) return 'Sin asignar';
                const names = Array.isArray(data) ? data : [data];
                return names.join(', ');
            }

            // Función para calcular diferencia duración teórica
            function getDiferenciaDuracion(row) {
                if (row.fast_time && parseInt(row.fast_time) > 0) {
                    return '+' + formatTimeExport(row.fast_time);
                } else if (row.out_time && parseInt(row.out_time) > 0) {
                    return '-' + formatTimeExport(row.out_time);
                }
                return '-';
            }

            switch (type) {
                case 'excel':
                    // Exportar a Excel usando SheetJS (XLSX)
                    const wb = XLSX.utils.book_new();
                    const wsData = [];

                    // Encabezados (sin la columna Acciones)
                    const headers = ['Línea', 'Orden', 'Empleados', 'OEE', 'Estado', 'Iniciado', 'Última actualización', 'Duración', 'Dif. Duración Teórica', 'Preparación', 'Lento', 'Paradas', 'Falta material'];
                    wsData.push(headers);

                    // Datos
                    table.rows().every(function() {
                        const rowData = this.data();
                        const row = [];

                        row.push(rowData.production_line_name || '-');
                        row.push(rowData.order_id || '-');
                        row.push(getOperatorNames(rowData.operator_names));
                        row.push(rowData.oee ? Math.round(rowData.oee) + '%' : '-');
                        row.push(statusMap[rowData.status] || statusMap['unknown']);
                        row.push(rowData.created_at ? new Date(rowData.created_at).toLocaleString() : '-');
                        row.push(rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-');
                        row.push(formatTimeExport(rowData.on_time));
                        row.push(getDiferenciaDuracion(rowData));
                        row.push(formatTimeExport(rowData.prepair_time));
                        row.push(formatTimeExport(rowData.slow_time));
                        row.push(formatTimeExport(rowData.down_time));
                        row.push(formatTimeExport(rowData.production_stops_time));

                        wsData.push(row);
                    });

                    const ws = XLSX.utils.aoa_to_sheet(wsData);
                    XLSX.utils.book_append_sheet(wb, ws, "Datos de Producción");

                    // Guardar archivo
                    XLSX.writeFile(wb, "Datos_Produccion_" + new Date().toLocaleDateString() + ".xlsx");
                    break;

                case 'pdf':
                    // Exportar a PDF usando jsPDF
                    const doc = new window.jspdf.jsPDF({ orientation: 'landscape' });

                    // Título del documento
                    doc.setFontSize(18);
                    doc.text('Datos de Producción', 14, 22);
                    doc.setFontSize(11);
                    doc.text('Fecha: ' + new Date().toLocaleString(), 14, 30);

                    // Encabezados para PDF (sin Acciones)
                    const pdfHeaders = ['Línea', 'Orden', 'Empleados', 'OEE', 'Estado', 'Iniciado', 'Actualizado', 'Duración', 'Dif. Teórica', 'Preparación', 'Lento', 'Paradas', 'Falta mat.'];

                    const pdfData = [];
                    table.rows().every(function() {
                        const rowData = this.data();
                        const row = [];

                        row.push(rowData.production_line_name || '-');
                        row.push(rowData.order_id || '-');
                        row.push(getOperatorNames(rowData.operator_names));
                        row.push(rowData.oee ? Math.round(rowData.oee) + '%' : '-');
                        row.push(statusMap[rowData.status] || statusMap['unknown']);
                        row.push(rowData.created_at ? new Date(rowData.created_at).toLocaleString() : '-');
                        row.push(rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-');
                        row.push(formatTimeExport(rowData.on_time));
                        row.push(getDiferenciaDuracion(rowData));
                        row.push(formatTimeExport(rowData.prepair_time));
                        row.push(formatTimeExport(rowData.slow_time));
                        row.push(formatTimeExport(rowData.down_time));
                        row.push(formatTimeExport(rowData.production_stops_time));

                        pdfData.push(row);
                    });

                    // Generar tabla en PDF
                    doc.autoTable({
                        head: [pdfHeaders],
                        body: pdfData,
                        startY: 40,
                        margin: { top: 40 },
                        styles: { overflow: 'linebreak', fontSize: 7 },
                        headStyles: { fillColor: [41, 128, 185], textColor: 255 },
                        alternateRowStyles: { fillColor: [245, 245, 245] }
                    });

                    // Guardar PDF
                    doc.save("Datos_Produccion_" + new Date().toLocaleDateString() + ".pdf");
                    break;

                case 'print':
                    // Imprimir manualmente
                    let printWindow = window.open('', '_blank');
                    let tableHtml = '<html><head><title>Datos de Producción</title>';
                    tableHtml += '<style>body{font-family:Arial,sans-serif;font-size:10px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ddd;padding:6px;text-align:left;}th{background-color:#f2f2f2;}</style>';
                    tableHtml += '</head><body>';
                    tableHtml += '<h1>Datos de Producción</h1>';
                    tableHtml += '<p>Fecha: ' + new Date().toLocaleString() + '</p>';
                    tableHtml += '<table>';

                    // Encabezados
                    tableHtml += '<thead><tr>';
                    tableHtml += '<th>Línea</th><th>Orden</th><th>Empleados</th><th>OEE</th><th>Estado</th><th>Iniciado</th><th>Actualizado</th><th>Duración</th><th>Dif. Teórica</th><th>Preparación</th><th>Lento</th><th>Paradas</th><th>Falta mat.</th>';
                    tableHtml += '</tr></thead>';

                    // Datos
                    tableHtml += '<tbody>';
                    table.rows().every(function() {
                        let rowData = this.data();
                        tableHtml += '<tr>';

                        tableHtml += '<td>' + (rowData.production_line_name || '-') + '</td>';
                        tableHtml += '<td>' + (rowData.order_id || '-') + '</td>';
                        tableHtml += '<td>' + getOperatorNames(rowData.operator_names) + '</td>';
                        tableHtml += '<td>' + (rowData.oee ? Math.round(rowData.oee) + '%' : '-') + '</td>';
                        tableHtml += '<td>' + (statusMap[rowData.status] || statusMap['unknown']) + '</td>';
                        tableHtml += '<td>' + (rowData.created_at ? new Date(rowData.created_at).toLocaleString() : '-') + '</td>';
                        tableHtml += '<td>' + (rowData.updated_at ? new Date(rowData.updated_at).toLocaleString() : '-') + '</td>';
                        tableHtml += '<td>' + formatTimeExport(rowData.on_time) + '</td>';
                        tableHtml += '<td>' + getDiferenciaDuracion(rowData) + '</td>';
                        tableHtml += '<td>' + formatTimeExport(rowData.prepair_time) + '</td>';
                        tableHtml += '<td>' + formatTimeExport(rowData.slow_time) + '</td>';
                        tableHtml += '<td>' + formatTimeExport(rowData.down_time) + '</td>';
                        tableHtml += '<td>' + formatTimeExport(rowData.production_stops_time) + '</td>';

                        tableHtml += '</tr>';
                    });
                    tableHtml += '</tbody></table>';
                    tableHtml += '</body></html>';

                    printWindow.document.write(tableHtml);
                    printWindow.document.close();
                    printWindow.focus();
                    setTimeout(function() {
                        printWindow.print();
                        printWindow.close();
                    }, 500);
                    break;
            }
        }

    </script>
@endpush