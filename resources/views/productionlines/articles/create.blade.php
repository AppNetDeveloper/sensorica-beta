@extends('layouts.admin')

@section('title', __('Afegir Article a la Línia de Producció'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => $productionLine->customer_id]) }}">{{ __('Línies de Producció') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.articles.index', $productionLine->id) }}">{{ __('Articles') }}: {{ $productionLine->name }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Afegir Article') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">{{ __('Afegir Article a la Línia de Producció') }}: {{ $productionLine->name }}</h5>
                        <a href="{{ route('productionlines.articles.index', $productionLine->id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left"></i> {{ __('Tornar') }}
                        </a>
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

                    <form action="{{ route('productionlines.articles.store', $productionLine->id) }}" method="POST" id="article-form">
                        @csrf

                        <!-- Selector de familias de artículos -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="article_family_id" class="form-label">{{ __('Seleccionar Família de Artículos') }}:</label>
                                <select name="article_family_id" id="article_family_id" class="form-select @error('article_family_id') is-invalid @enderror">
                                    <option value="">{{ __('Seleccionar familia (opcional)') }}</option>
                                    @foreach($articleFamilies as $family)
                                        <option value="{{ $family->id }}" {{ old('article_family_id') == $family->id ? 'selected' : '' }}>
                                            {{ $family->name }} ({{ $family->articles->count() }} {{ __('articles') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('article_family_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('Selecciona una familia para asociar todos sus artículos, o déjalo vacío para seleccionar artículos individuales.') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label for="order" class="form-label">{{ __('Ordre') }} <span class="text-danger">*</span></label>
                                <input type="number" min="1" class="form-control @error('order') is-invalid @enderror"
                                       id="order" name="order" value="{{ old('order') }}" required>
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="form-text text-muted">{{ __('El ordre determinarà la seqüència en què es mostraran els articles.') }}</small>
                            </div>
                        </div>

                        <!-- Lista de artículos disponibles -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">{{ __('Articles Disponibles') }}</h6>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="select-all-articles">
                                                <label class="form-check-label" for="select-all-articles">
                                                    {{ __('Seleccionar Todos') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($availableArticles->count() > 0)
                                            <div class="row" id="articles-container">
                                                @foreach($availableArticles as $article)
                                                    <div class="col-md-6 col-lg-4 mb-3 article-item" data-family="{{ $article->article_family_id }}">
                                                        <div class="card h-100 article-card">
                                                            <div class="card-body">
                                                                <div class="form-check">
                                                                    <input type="checkbox"
                                                                           class="form-check-input article-checkbox"
                                                                           name="article_id"
                                                                           value="{{ $article->id }}"
                                                                           id="article_{{ $article->id }}"
                                                                           {{ old('article_id') == $article->id ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="article_{{ $article->id }}">
                                                                        <strong>{{ $article->name }}</strong>
                                                                    </label>
                                                                </div>
                                                                <div class="mt-2">
                                                                    <small class="text-muted">
                                                                        {{ __('Codi') }}: {{ $article->name }}<br>
                                                                        {{ __('Família') }}: {{ $article->articleFamily->name ?? 'N/A' }}<br>
                                                                        @if($article->description)
                                                                            {{ __('Descripció') }}: {{ Str::limit($article->description, 50) }}
                                                                        @endif
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center py-4">
                                                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">{{ __('No hi ha articles disponibles per associar a aquesta línia de producció.') }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                        @error('article_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('productionlines.articles.index', $productionLine->id) }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-times"></i> {{ __('Cancel·lar') }}
                                    </a>
                                    @can('productionline-article-create')
                                    <button type="submit" class="btn btn-primary" id="submit-btn">
                                        <i class="fas fa-save"></i> {{ __('Guardar') }}
                                    </button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .article-card {
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }

    .article-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .form-check-input:checked + .form-check-label {
        font-weight: bold;
        color: #0d6efd;
    }

    .article-item.hidden {
        display: none !important;
    }

    .card-header {
        border-bottom: 1px solid #dee2e6;
    }

    .form-select:focus,
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }

    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .btn-primary:hover {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }

    .breadcrumb {
        background-color: #f8f9fa;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
    }

    .alert {
        border-radius: 0.375rem;
        border: none;
    }

    .alert-success {
        background-color: #d1edff;
        color: #0c63e4;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
    }

    @media (max-width: 768px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1rem;
        }

        .col-md-6 {
            margin-bottom: 1rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const familySelect = document.getElementById('article_family_id');
    const articlesContainer = document.getElementById('articles-container');
    const articleItems = document.querySelectorAll('.article-item');
    const articleCheckboxes = document.querySelectorAll('.article-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-articles');
    const submitBtn = document.getElementById('submit-btn');
    const articleForm = document.getElementById('article-form');

    // Función para filtrar artículos por familia
    function filterArticlesByFamily(familyId) {
        if (!familyId) {
            // Mostrar todos los artículos
            articleItems.forEach(item => {
                item.classList.remove('hidden');
            });
        } else {
            // Mostrar solo artículos de la familia seleccionada
            articleItems.forEach(item => {
                const articleFamilyId = item.dataset.family;
                if (articleFamilyId === familyId) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                    // Desmarcar checkboxes de artículos ocultos
                    const checkbox = item.querySelector('.article-checkbox');
                    checkbox.checked = false;
                }
            });
        }
        updateSelectAllState();
        updateSubmitButton();
    }

    // Función para actualizar el estado del checkbox "Seleccionar todos"
    function updateSelectAllState() {
        const visibleCheckboxes = Array.from(articleCheckboxes).filter(checkbox => {
            return !checkbox.closest('.article-item').classList.contains('hidden');
        });

        const checkedVisible = visibleCheckboxes.filter(checkbox => checkbox.checked);

        if (visibleCheckboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedVisible.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedVisible.length === visibleCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    // Función para actualizar el botón de submit
    function updateSubmitButton() {
        const checkedArticles = document.querySelectorAll('.article-checkbox:checked');
        const orderValue = document.getElementById('order').value;

        if (checkedArticles.length === 0 || !orderValue) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> {{ __("Guardar") }}';
        } else {
            submitBtn.disabled = false;
            const articleText = checkedArticles.length === 1 ? '{{ __("Article") }}' : '{{ __("Articles") }}';
            submitBtn.innerHTML = '<i class="fas fa-save"></i> {{ __("Guardar") }} (' + checkedArticles.length + ' ' + articleText + ')';
        }
    }

    // Event listener para el selector de familias
    familySelect.addEventListener('change', function() {
        const selectedFamilyId = this.value;
        filterArticlesByFamily(selectedFamilyId);

        // Si se selecciona una familia, desmarcar el checkbox individual si estaba marcado
        if (selectedFamilyId) {
            articleCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
        updateSelectAllState();
        updateSubmitButton();
    });

    // Event listener para checkboxes individuales
    articleCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Si se marca un artículo individual, deseleccionar la familia
            if (this.checked) {
                familySelect.value = '';
                filterArticlesByFamily('');
            }
            updateSelectAllState();
            updateSubmitButton();
        });
    });

    // Event listener para "Seleccionar todos"
    selectAllCheckbox.addEventListener('change', function() {
        const visibleCheckboxes = Array.from(articleCheckboxes).filter(checkbox => {
            return !checkbox.closest('.article-item').classList.contains('hidden');
        });

        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });

        // Si se seleccionan todos, deseleccionar la familia
        if (this.checked) {
            familySelect.value = '';
        }

        updateSubmitButton();
    });

    // Event listener para el campo de orden
    document.getElementById('order').addEventListener('input', function() {
        updateSubmitButton();
    });

    // Validación del formulario
    articleForm.addEventListener('submit', function(e) {
        const checkedArticles = document.querySelectorAll('.article-checkbox:checked');
        const orderValue = document.getElementById('order').value;
        const familyValue = familySelect.value;

        if (checkedArticles.length === 0 && !familyValue) {
            e.preventDefault();
            alert('{{ __("Debe seleccionar al menos un artículo o una familia de artículos.") }}');
            return false;
        }

        if (!orderValue) {
            e.preventDefault();
            alert('{{ __("El campo orden es obligatorio.") }}');
            document.getElementById('order').focus();
            return false;
        }

        // Mostrar indicador de carga
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> {{ __("Guardando...") }}';
    });

    // Inicialización
    updateSelectAllState();
    updateSubmitButton();
});
</script>
@endpush