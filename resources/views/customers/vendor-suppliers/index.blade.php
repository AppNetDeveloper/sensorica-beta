@extends('layouts.admin')

@section('title', __('Proveedores') . ' - ' . $customer->name)
@section('page-title', __('Proveedores'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-suppliers.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Proveedores') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Listado de proveedores') }}</h5>
    <a href="{{ route('customers.vendor-suppliers.create', $customer) }}" class="btn btn-sm btn-primary">
      <i class="ti ti-plus"></i> {{ __('Nuevo proveedor') }}
    </a>
  </div>
  <div class="card-body">
    @if($suppliers->isEmpty())
      <div class="alert alert-info mb-0">{{ __('No hay proveedores registrados todavía.') }}</div>
    @else
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="suppliersTable">
          <thead>
            <tr>
              <th>{{ __('Nombre') }}</th>
              <th>{{ __('CIF/NIF') }}</th>
              <th>{{ __('Email') }}</th>
              <th>{{ __('Teléfono') }}</th>
              <th>{{ __('Persona de contacto') }}</th>
              <th>{{ __('Condiciones de pago') }}</th>
              <th class="text-end">{{ __('Acciones') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($suppliers as $supplier)
              <tr>
                <td>{{ $supplier->name }}</td>
                <td>{{ $supplier->tax_id ?? '—' }}</td>
                <td>{{ $supplier->email ?? '—' }}</td>
                <td>{{ $supplier->phone ?? '—' }}</td>
                <td>{{ $supplier->contact_name ?? '—' }}</td>
                <td>{{ $supplier->payment_terms ?? '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.vendor-suppliers.edit', [$customer, $supplier]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-edit"></i>
                  </a>
                  <form action="{{ route('customers.vendor-suppliers.destroy', [$customer, $supplier]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Estás seguro de eliminar este proveedor?') }}');">
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
    $('#suppliersTable').DataTable({
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"btn-toolbar"B><"flex-grow-1"f>>rtip',
      buttons: [
        {
          extend: 'excelHtml5',
          title: '{{ __('Proveedores') }} - {{ $customer->name }}'
        },
        {
          extend: 'pdfHtml5',
          title: '{{ __('Proveedores') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        },
        {
          extend: 'print',
          title: '{{ __('Proveedores') }} - {{ $customer->name }}',
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
