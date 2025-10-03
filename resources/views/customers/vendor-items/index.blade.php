@extends('layouts.admin')

@section('title', __('Productos de compra') . ' - ' . $customer->name)
@section('page-title', __('Productos de compra'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-items.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Productos de compra') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Listado de productos de compra') }}</h5>
    <a href="{{ route('customers.vendor-items.create', $customer) }}" class="btn btn-sm btn-primary">
      <i class="ti ti-plus"></i> {{ __('Nuevo producto') }}
    </a>
  </div>
  <div class="card-body">
    @if($items->isEmpty())
      <div class="alert alert-info mb-0">{{ __('No hay productos registrados todavía.') }}</div>
    @else
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="itemsTable">
          <thead>
            <tr>
              <th>{{ __('Nombre') }}</th>
              <th>{{ __('SKU') }}</th>
              <th>{{ __('Proveedor preferente') }}</th>
              <th>{{ __('Precio unitario') }}</th>
              <th>{{ __('Lead time (días)') }}</th>
              <th>{{ __('Unidad') }}</th>
              <th class="text-end">{{ __('Acciones') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $item)
              <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->sku ?? '—' }}</td>
                <td>{{ optional($item->supplier)->name ?? '—' }}</td>
                <td>{{ $item->unit_price ? number_format($item->unit_price, 2) . ' ' . __('€') : '—' }}</td>
                <td>{{ $item->lead_time_days ?? '—' }}</td>
                <td>{{ $item->unit_of_measure ?? '—' }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.vendor-items.edit', [$customer, $item]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-edit"></i>
                  </a>
                  <form action="{{ route('customers.vendor-items.destroy', [$customer, $item]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Estás seguro de eliminar este producto?') }}');">
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
    $('#itemsTable').DataTable({
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"btn-toolbar"B><"flex-grow-1"f>>rtip',
      buttons: [
        {
          extend: 'excelHtml5',
          title: '{{ __('Productos de compra') }} - {{ $customer->name }}'
        },
        {
          extend: 'pdfHtml5',
          title: '{{ __('Productos de compra') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        },
        {
          extend: 'print',
          title: '{{ __('Productos de compra') }} - {{ $customer->name }}',
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
