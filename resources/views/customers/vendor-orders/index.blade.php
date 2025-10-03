@extends('layouts.admin')

@section('title', __('Pedidos a proveedor') . ' - ' . $customer->name)
@section('page-title', __('Pedidos a proveedor'))

@section('breadcrumb')
<div class="mb-4">
  <ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('customers.vendor-orders.index', $customer) }}">{{ $customer->name }}</a></li>
    <li class="breadcrumb-item active">{{ __('Pedidos a proveedor') }}</li>
  </ul>
</div>
@endsection

@section('content')
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Listado de pedidos a proveedor') }}</h5>
    <a href="{{ route('customers.vendor-orders.create', $customer) }}" class="btn btn-sm btn-primary">
      <i class="ti ti-plus"></i> {{ __('Nuevo pedido') }}
    </a>
  </div>
  <div class="card-body">
    @if($orders->isEmpty())
      <div class="alert alert-info mb-0">{{ __('No hay pedidos registrados todavía.') }}</div>
    @else
      <div class="table-responsive">
        <table class="table table-striped align-middle" id="vendorOrdersTable">
          <thead>
            <tr>
              <th>{{ __('Referencia') }}</th>
              <th>{{ __('Proveedor') }}</th>
              <th>{{ __('Estado') }}</th>
              <th>{{ __('Fecha solicitada') }}</th>
              <th>{{ __('Fecha esperada') }}</th>
              <th>{{ __('Importe total') }}</th>
              <th class="text-end">{{ __('Acciones') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach($orders as $order)
              <tr>
                <td>{{ $order->reference }}</td>
                <td>{{ optional($order->supplier)->name ?? '—' }}</td>
                <td><span class="badge bg-secondary text-uppercase">{{ __($order->status) }}</span></td>
                <td>{{ optional($order->requested_at)->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ optional($order->expected_at)->format('d/m/Y') ?? '—' }}</td>
                <td>{{ number_format($order->total_amount, 2) }} {{ $order->currency }}</td>
                <td class="text-end">
                  <a href="{{ route('customers.vendor-orders.show', [$customer, $order]) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
                  <a href="{{ route('customers.vendor-orders.edit', [$customer, $order]) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                  <form action="{{ route('customers.vendor-orders.destroy', [$customer, $order]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('¿Estás seguro de eliminar este pedido?') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
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
    $('#vendorOrdersTable').DataTable({
      responsive: true,
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"btn-toolbar"B><"flex-grow-1"f>>rtip',
      buttons: [
        {
          extend: 'excelHtml5',
          title: '{{ __('Pedidos a proveedor') }} - {{ $customer->name }}'
        },
        {
          extend: 'pdfHtml5',
          title: '{{ __('Pedidos a proveedor') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        },
        {
          extend: 'print',
          title: '{{ __('Pedidos a proveedor') }} - {{ $customer->name }}',
          exportOptions: {
            columns: ':not(:last-child)'
          }
        }
      ],
      order: [[3, 'desc']],
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
