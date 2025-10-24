@extends('layouts.admin')

@section('title', __('Articles de la Línia de Producció'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => $productionLine->customer_id]) }}">{{ __('Línies de Producció') }}</a>
        </li>
        <li class="breadcrumb-item">{{ $productionLine->name }} - {{ __('Articles') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ __('Articles de la Línia de Producció') }}: {{ $productionLine->name }}</h5>
                        <div>
                            @can('productionline-article-create')
                            <a href="{{ route('productionlines.articles.create', $productionLine->id) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-plus"></i> {{ __('Afegir Article') }}
                            </a>
                            @endcan
                            <a href="{{ route('productionlines.index', ['customer_id' => $productionLine->customer_id]) }}" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> {{ __('Tornar') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Panel de filtros y estadísticas mejorado -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-filter me-2"></i>{{ __('Filtres i Estadístiques') }}
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary fs-6 me-3">
                                                <i class="fas fa-box me-1"></i>{{ __('Total articles') }}: {{ $articles->count() }}
                                            </span>
                                            @if($articleFamilies->count() > 0)
                                                <span class="badge bg-info fs-6">
                                                    <i class="fas fa-folder me-1"></i>{{ __('Famílies') }}: {{ $articleFamilies->count() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <label for="article_family_id" class="form-label">
                                                <i class="fas fa-folder-open me-1"></i>{{ __('Filtrar per Família d\'Articles') }}
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="fas fa-search"></i>
                                                </span>
                                                <select name="article_family_id" id="article_family_id" class="form-select" onchange="this.form.submit()">
                                                    <option value="">{{ __('Totes les Famílies') }}</option>
                                                    @foreach($articleFamilies as $family)
                                                        <option value="{{ $family->id }}" {{ request('article_family_id') == $family->id ? 'selected' : '' }}>
                                                            {{ $family->name }} ({{ $family->articles->count() }} {{ __('articles') }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if(request('article_family_id'))
                                                    <a href="{{ route('productionlines.articles.index', $productionLine->id) }}" class="btn btn-outline-secondary" title="{{ __('Netejar Filtre') }}">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            @if(request('article_family_id'))
                                                <?php
                                                    $selectedFamily = $articleFamilies->find(request('article_family_id'));
                                                ?>
                                                @if($selectedFamily)
                                                    <small class="text-muted mt-1 d-block">
                                                        <i class="fas fa-info-circle me-1"></i>{{ __('Filtrant per') }}: <strong>{{ $selectedFamily->name }}</strong>
                                                    </small>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <div class="d-flex justify-content-end align-items-center gap-2">
                                                @can('productionline-article-delete')
                                                    <button type="button" class="btn btn-danger" onclick="bulkDelete()" id="bulk-delete-btn" {{ $articles->count() == 0 ? 'disabled' : '' }}>
                                                        <i class="fas fa-trash me-1"></i>{{ __('Eliminar Seleccionats') }}
                                                    </button>
                                                @endcan
                                                @can('productionline-article-create')
                                                    <a href="{{ route('productionlines.articles.create', $productionLine->id) }}" class="btn btn-success">
                                                        <i class="fas fa-plus me-1"></i>{{ __('Afegir Article') }}
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <form id="articles-form" method="POST">
                            @csrf
                            <table id="articles-table" class="display table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">
                                            <div class="form-check d-flex justify-content-center align-items-center">
                                                <input type="checkbox" id="select-all" class="form-check-input">
                                            </div>
                                        </th>
                                        <th width="8%" class="text-center">{{ __('Ordre') }}</th>
                                        <th width="15%">{{ __('Codi Família') }}</th>
                                        <th width="20%">{{ __('Nom Família') }}</th>
                                        <th width="15%">{{ __('Codi Article') }}</th>
                                        <th width="25%">{{ __('Nom Article') }}</th>
                                        <th width="12%" class="text-center">{{ __('Accions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($articles as $article)
                                        <tr>
                                            <td class="text-center">
                                                @can('productionline-article-delete')
                                                <input type="checkbox" name="selected_articles[]" value="{{ $article->id }}" class="form-check-input article-checkbox">
                                                @endcan
                                            </td>
                                            <td class="text-center">{{ $article->pivot->order }}</td>
                                            <td>{{ $article->articleFamily->name ?? 'N/A' }}</td>
                                            <td>{{ $article->articleFamily->description ?? '' }}</td>
                                            <td>{{ $article->name }}</td>
                                            <td>{{ $article->description ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    @can('productionline-article-edit')
                                                    <a href="{{ route('productionlines.articles.edit', [$productionLine->id, $article->id]) }}"
                                                        class="btn btn-sm btn-warning mx-1"
                                                        title="{{ __('Editar ordre') }}">
                                                         <i class="fas fa-edit"></i>
                                                    </a>
                                                    @endcan

                                                    @can('productionline-article-delete')
                                                    <button type="button"
                                                            class="btn btn-sm btn-danger mx-1"
                                                            title="{{ __('Eliminar associació') }}"
                                                            onclick="deleteArticle({{ $article->id }}, '{{ $article->name }}')">
                                                         <i class="fas fa-trash"></i>
                                                    </button>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-center" colspan="7">{{ __('No s\'han trobat articles associats.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">{{ __('Confirmar Eliminació') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('Esteu segur que voleu eliminar l\'associació amb l\'article') }}: <strong id="article-name"></strong>?</p>
                <p class="text-muted">{{ __('Aquesta acció no es pot desfer.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel·lar') }}</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('Eliminar') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/searchpanes/2.2.0/css/searchPanes.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/select/1.7.0/css/select.bootstrap5.min.css">

    <style>
        /* Panel de filtros mejorado */
        .card.border {
            border: 1px solid #e3e6f0 !important;
            border-radius: 0.75rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .card-header.bg-light {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Badges mejorados */
        .badge {
            font-size: 0.75rem;
            padding: 0.5em 0.75em;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
        }

        .badge.bg-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%) !important;
        }

        /* Input group mejorado */
        .input-group-text {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #ced4da;
            color: #6c757d;
        }

        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Botones mejorados */
        .btn-outline-secondary {
            border: 1px solid #6c757d;
            color: #6c757d;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #6c757d 0%, #5c636a 100%);
            border-color: #5c636a;
            color: white;
        }

        /* Tabla mejorada */
        .table th, .table td {
            vertical-align: middle;
            padding: 0.875rem 0.75rem;
        }

        .table-bordered {
            border: 1px solid #e3e6f0;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #e3e6f0;
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

        #articles-table_wrapper {
            width: 100%;
        }

        /* Encabezados de tabla más destacados */
        .table thead th {
            font-weight: 600;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            color: #495057;
        }

        /* Checkbox del header mejorado */
        .table thead th:first-child {
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        }

        /* Checkbox styling mejorado */
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-check-input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        /* Botones de acción mejorados */
        .btn-group .btn {
            margin: 0 2px;
            border-radius: 0.375rem !important;
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            border: none;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #fd7e14 0%, #e86804 100%);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-1px);
        }

        /* Espacio entre botones DataTables y la tabla */
        .dt-buttons {
            margin-bottom: 1rem;
        }

        .dt-buttons .btn {
            border-radius: 0.5rem !important;
            font-weight: 500;
            text-transform: none;
            padding: 0.5rem 1rem;
        }

        /* Información de filtro activo */
        .text-muted {
            color: #6c757d !important;
        }

        /* Responsive adjustments mejorados */
        @media (max-width: 768px) {
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }

            .text-end {
                text-align: start !important;
            }

            .card-body {
                padding: 1rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 0.4em 0.6em;
            }
        }

        /* Animaciones suaves */
        .btn, .form-select, .form-check-input {
            transition: all 0.2s ease-in-out;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        /* Loading state mejorado */
        .table-responsive {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- DataTables Core --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    {{-- DataTables Extensions --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>

    {{-- DataTables Responsive --}}
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>

    {{-- DataTables SearchPanes --}}
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/dataTables.searchPanes.min.js"></script>
    <script src="https://cdn.datatables.net/searchpanes/2.2.0/js/searchPanes.bootstrap5.min.js"></script>

    {{-- DataTables Select --}}
    <script src="https://cdn.datatables.net/select/1.7.0/js/dataTables.select.min.js"></script>

    <script>
        $(document).ready(function() {
            // Configuración simplificada de DataTables
            const tableConfig = {
                responsive: true,
                scrollX: true,
                searching: true,
                paging: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('All') }}"]],
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('No records available') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}",
                    loadingRecords: "{{ __('Loading...') }}",
                    processing: "{{ __('Processing...') }}",
                    searchPlaceholder: "{{ __('Search...') }}",
                    paginate: {
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: "{{ __('Next') }}",
                        previous: "{{ __('Previous') }}"
                    }
                },
                columnDefs: [
                    {
                        width: '5%',
                        targets: 0,
                        orderable: false,
                        className: 'text-center',
                        searchable: false
                    },
                    {
                        width: '8%',
                        targets: 1,
                        className: 'text-center',
                        orderable: true
                    },
                    {
                        width: '15%',
                        targets: 2,
                        orderable: true
                    },
                    {
                        width: '20%',
                        targets: 3,
                        orderable: true
                    },
                    {
                        width: '15%',
                        targets: 4,
                        orderable: true
                    },
                    {
                        width: '25%',
                        targets: 5,
                        orderable: true
                    },
                    {
                        width: '12%',
                        targets: 6,
                        orderable: false,
                        className: 'text-center',
                        searchable: false
                    }
                ],
                order: [[1, 'asc']], // Ordenar por la columna de orden por defecto
                dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex"B><"d-flex"f>>rt<"d-flex justify-content-between"<"d-flex"li><"d-flex"p>>',
                buttons: [
                    {
                        extend: 'pageLength',
                        className: 'btn btn-secondary btn-sm',
                        text: '<i class="fas fa-list"></i> {{ __("Show") }}'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5]
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> {{ __("Print") }}',
                        className: 'btn btn-info btn-sm',
                        exportOptions: {
                            columns: [1, 2, 3, 4, 5]
                        }
                    }
                ],
                initComplete: function() {
                    console.log('DataTables inicializado correctamente');

                    // Recrear el checkbox "Seleccionar todo" en el header después de que DataTables lo reemplace
                    const headerCheckbox = $('<div class="form-check d-flex justify-content-center align-items-center"><input type="checkbox" id="select-all" class="form-check-input"></div>');
                    this.api().column(0).header().innerHTML = headerCheckbox.html();

                    // Reasignar el evento del checkbox después de recrearlo
                    $('#select-all').off('change').on('change', function() {
                        const isChecked = $(this).is(':checked');
                        $('.article-checkbox').prop('checked', isChecked);
                        updateBulkDeleteButton();
                    });

                    // Actualizar el botón de eliminación masiva después de la inicialización
                    updateBulkDeleteButton();
                }
            };

            // Inicializar DataTables con configuración simplificada
            const table = $('#articles-table').DataTable(tableConfig);

            // Asegurarse de que la tabla es responsiva
            new $.fn.dataTable.Responsive(table);

            // Manejar el checkbox "Seleccionar todo"
            $('#select-all').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.article-checkbox').prop('checked', isChecked);
                updateBulkDeleteButton();
            });

            // Manejar checkboxes individuales
            $(document).on('change', '.article-checkbox', function() {
                const totalCheckboxes = $('.article-checkbox').length;
                const checkedCheckboxes = $('.article-checkbox:checked').length;
                $('#select-all').prop('checked', checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0);
                updateBulkDeleteButton();
            });

            // Función para actualizar el estado del botón de eliminación masiva
            function updateBulkDeleteButton() {
                const selectedCount = $('.article-checkbox:checked').length;
                const bulkDeleteBtn = $('#bulk-delete-btn');

                if (selectedCount > 0) {
                    bulkDeleteBtn.prop('disabled', false).html('<i class="fas fa-trash me-1"></i>{{ __("Eliminar Seleccionats") }} (' + selectedCount + ')');
                } else {
                    bulkDeleteBtn.prop('disabled', true).html('<i class="fas fa-trash me-1"></i>{{ __("Eliminar Seleccionats") }}');
                }
            }

            // Hacer la función global para que se pueda llamar desde el HTML
            window.updateBulkDeleteButton = updateBulkDeleteButton;
        });

        // Función para eliminar un artículo individual
        function deleteArticle(articleId, articleName) {
            document.getElementById('article-name').textContent = articleName;
            document.getElementById('delete-form').action = '{{ route("productionlines.articles.destroy", [$productionLine->id, ""]) }}/' + articleId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Función para eliminación masiva
        function bulkDelete() {
            const selectedArticles = $('.article-checkbox:checked');

            if (selectedArticles.length === 0) {
                alert('{{ __("No has seleccionado ningún artículo") }}');
                return;
            }

            if (confirm('{{ __("¿Estás seguro de que quieres eliminar los artículos seleccionados?") }}')) {
                // Crear un formulario temporal para enviar los IDs
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("productionlines.articles.bulk-delete", $productionLine->id) }}';

                // Agregar CSRF token
                const csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                // Agregar method DELETE
                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                // Agregar los IDs de artículos seleccionados
                selectedArticles.each(function() {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'article_ids[]';
                    input.value = $(this).val();
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
@endpush
