@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Categorías de activos') . ' - ' . $customer->name)
@section('page-title', __('Categorías de activos'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.asset-categories.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Categorías de activos') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
  <div class="card-header bg-transparent">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ __('Listado de categorías') }}</h5>
      <a href="{{ route('customers.asset-categories.create', $customer) }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus"></i> {{ __('Nueva categoría') }}
      </a>
    </div>
  </div>
  <div class="card-body">
    @if($categories->isEmpty())
      <div class="alert alert-info mb-0">{{ __('No hay categorías registradas todavía.') }}</div>
    @else
      <div class="table-responsive" style="width: 100%; margin: 0 auto;">
        <table class="table table-striped align-middle" id="categoriesTable" style="width: 100%;">
          <thead>
            <tr>
              <th>{{ __('Categoría') }}</th>
              <th>{{ __('Padre') }}</th>
              <th>{{ __('Código etiqueta') }}</th>
              <th>{{ __('EPC categoría') }}</th>
              <th>{{ __('Descripción') }}</th>
              <th class="text-end">{{ __('Acciones') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($categories as $category)
              <tr>
                <td>{{ $category->name }}</td>
                <td>{{ optional($category->parent)->name ?? '—' }}</td>
                <td><code>{{ $category->label_code }}</code></td>
                <td>
                  @if($category->rfid_epc)
                    <code>{{ $category->rfid_epc }}</code>
                  @else
                    —
                  @endif
                </td>
                <td>{{ $category->description ? Str::limit($category->description, 80) : '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.asset-categories.edit', [$customer, $category]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-edit"></i>
                  </a>
                  <form action="{{ route('customers.asset-categories.destroy', [$customer, $category]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Eliminar esta categoría? Todos los activos asociados quedarán sin categoría.') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="ti ti-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css"/>
<style>
    .table th, .table td {
        vertical-align: middle;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
    #categoriesTable_wrapper .dt-buttons {
        margin-bottom: 10px;
    }
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 10px;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    .card-body {
        padding: 1.25rem;
    }
    #categoriesTable_wrapper {
        width: 100%;
    }
    .dataTables_paginate {
        float: right !important;
        width: 100%;
        text-align: right !important;
    }
    .dataTables_info {
        padding-top: 8px;
        margin-bottom: 10px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
  $(function(){
    $('#categoriesTable').DataTable({
      responsive: true,
      dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>><"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7 text-end"p>>',
      buttons: [
        {
          extend: 'excelHtml5',
          title: '{{ __('Categorías de activos') }} - {{ $customer->name }}'
        },
        {
          extend: 'pdfHtml5',
          title: '{{ __('Categorías de activos') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        },
        {
          extend: 'print',
          title: '{{ __('Categorías de activos') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        }
      ],
      language: {
        url: '{{ asset('assets/vendor/datatables/i18n/es_es.json') }}'
      },
      columnDefs: [
        { targets: -1, orderable: false, searchable: false }
      ]
    });
  });
</script>
@endpush
