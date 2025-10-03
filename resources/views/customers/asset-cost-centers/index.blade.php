@extends('layouts.admin')

@php use Illuminate\Support\Str; @endphp

@section('title', __('Centros de coste') . ' - ' . $customer->name)
@section('page-title', __('Centros de coste'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.asset-cost-centers.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Centros de coste') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Listado de centros de coste') }}</h5>
    <a href="{{ route('customers.asset-cost-centers.create', $customer) }}" class="btn btn-sm btn-primary">
      <i class="ti ti-plus"></i> {{ __('Nuevo centro de coste') }}
    </a>
  </div>
  <div class="card-body">
    @if($costCenters->isEmpty())
      <div class="alert alert-info mb-0">{{ __('No hay centros de coste registrados todavía.') }}</div>
    @else
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="costCentersTable">
          <thead>
            <tr>
              <th>{{ __('Código') }}</th>
              <th>{{ __('Nombre') }}</th>
              <th>{{ __('Descripción') }}</th>
              <th class="text-end">{{ __('Acciones') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($costCenters as $costCenter)
              <tr>
                <td>{{ $costCenter->code }}</td>
                <td>{{ $costCenter->name }}</td>
                <td>{{ $costCenter->description ? e(Str::limit($costCenter->description, 80)) : '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.asset-cost-centers.edit', [$customer, $costCenter]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-edit"></i>
                  </a>
                  <form action="{{ route('customers.asset-cost-centers.destroy', [$customer, $costCenter]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Eliminar este centro de coste?') }}');">
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
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css"/>
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
    $('#costCentersTable').DataTable({
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"btn-toolbar"B><"flex-grow-1"f>>rtip',
      buttons: [
        {
          extend: 'excelHtml5',
          title: '{{ __('Centros de coste') }} - {{ $customer->name }}'
        },
        {
          extend: 'pdfHtml5',
          title: '{{ __('Centros de coste') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        },
        {
          extend: 'print',
          title: '{{ __('Centros de coste') }} - {{ $customer->name }}',
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
