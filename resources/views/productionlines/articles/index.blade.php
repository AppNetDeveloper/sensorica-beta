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

                    <!-- Filtro por familia de artículos -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="GET" action="{{ route('productionlines.articles.index', $productionLine->id) }}" class="d-flex align-items-center">
                                <label for="article_family_id" class="form-label me-2 mb-0">{{ __('Filtrar per Família') }}:</label>
                                <select name="article_family_id" id="article_family_id" class="form-select form-select-sm me-2" style="width: auto;" onchange="this.form.submit()">
                                    <option value="">{{ __('Totes les Famílies') }}</option>
                                    @foreach($articleFamilies as $family)
                                        <option value="{{ $family->id }}" {{ request('article_family_id') == $family->id ? 'selected' : '' }}>
                                            {{ $family->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(request('article_family_id'))
                                    <a href="{{ route('productionlines.articles.index', $productionLine->id) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-times"></i> {{ __('Netejar Filtre') }}
                                    </a>
                                @endif
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            @if($articles->count() > 0)
                                <div class="d-flex justify-content-end align-items-center">
                                    <span class="me-2">{{ __('Total articles') }}: {{ $articles->count() }}</span>
                                    @can('productionline-article-delete')
                                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()" {{ $articles->count() == 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash"></i> {{ __('Eliminar Seleccionats') }}
                                    </button>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <form id="articles-form" method="POST">
                            @csrf
                            <table id="articles-table" class="display table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th width="5%" class="text-center">
                                            <input type="checkbox" id="select-all" class="form-check-input">
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
        .table th, .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }

        /* Hacer la tabla más ancha y con bordes más visibles */
        .table-bordered {
            border: 1px solid #dee2e6;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #dee2e6;
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
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        /* Mejorar el espaciado entre botones */
        .btn-group .btn {
            margin: 0 3px;
        }

        /* Espacio entre botones DataTables y la tabla */
        .dt-buttons {
            margin-bottom: 1rem;
        }

        /* Checkbox styling */
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        /* Filtro styling */
        .form-select-sm {
            font-size: 0.875rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }

            .text-end {
                text-align: start !important;
            }
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
            // Verificar si hay filas en la tabla
            const hasRows = $('#articles-table tbody tr').length > 1; // -1 porque hay una fila vacía

            // Configuración base de DataTables
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
                ]
            };

            // Inicializar DataTables
            let table;

            if (hasRows) {
                table = $('#articles-table').DataTable(tableConfig);
                console.log('DataTables inicializado con datos existentes');
            } else {
                table = $('#articles-table').DataTable({
                    ...tableConfig,
                    data: [],
                    columns: [
                        { title: '<input type="checkbox" id="select-all" class="form-check-input">', className: 'text-center', orderable: false },
                        { title: '{{ __("Ordre") }}', className: 'text-center' },
                        { title: '{{ __("Codi Família") }}' },
                        { title: '{{ __("Nom Família") }}' },
                        { title: '{{ __("Codi Article") }}' },
                        { title: '{{ __("Nom Article") }}' },
                        { title: '{{ __("Accions") }}', className: 'text-center' }
                    ]
                });
                console.log('DataTables inicializado con datos vacíos');
            }

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
                const allChecked = $('.article-checkbox:checked').length === $('.article-checkbox').length;
                const anyChecked = $('.article-checkbox:checked').length > 0;
                $('#select-all').prop('checked', allChecked);
                updateBulkDeleteButton();
            });

            // Función para actualizar el estado del botón de eliminación masiva
            function updateBulkDeleteButton() {
                const selectedCount = $('.article-checkbox:checked').length;
                const bulkDeleteBtn = $('button[onclick="bulkDelete()"]');

                if (selectedCount > 0) {
                    bulkDeleteBtn.prop('disabled', false).html('<i class="fas fa-trash"></i> {{ __("Eliminar Seleccionats") }} (' + selectedCount + ')');
                } else {
                    bulkDeleteBtn.prop('disabled', true).html('<i class="fas fa-trash"></i> {{ __("Eliminar Seleccionats") }}');
                }
            }
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