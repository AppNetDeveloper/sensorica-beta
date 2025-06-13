@extends('layouts.admin')

@section('title', __('Original Orders'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Original Orders') }}</li>
    </ul>
@endsection

@section('content')
<div class="container-fluid px-0">
    <div class="row mt-3 mx-0">
        <div class="col-12 px-0">
            <div class="card border-0 shadow" style="width: 100%;">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@lang('Original Orders') - {{ $customer->name }}</h5>
                        @can('original-order-create')
                        <a href="{{ route('customers.original-orders.create', $customer->id) }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus"></i> @lang('New Order')
                        </a>
                        @endcan
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
                    
                    <div class="table-responsive" style="width: 100%; margin: 0 auto;">
                        <table id="original-orders-table" class="table table-striped table-hover" style="width: 100%;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-uppercase">@lang('ORDER ID')</th>
                                    <th class="text-uppercase">@lang('CLIENT NUMBER')</th>
                                    <th class="text-uppercase">@lang('PROCESSED')</th>
                                    <th class="text-uppercase">@lang('FINISHED AT')</th>
                                    <th class="text-uppercase">@lang('CREATED AT')</th>
                                    <th class="text-uppercase">@lang('ACTIONS')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($originalOrders as $index => $order)
                                    @if($order)
                                     <tr>
                                         <td>{{ $index + 1 }}</td>
                                         <td>{{ $order->order_id }}</td>
                                         <td>{{ $order->client_number }}</td>
                                         <td>
                                             @if($order->processed)
                                                 <span class="badge bg-success">@lang('Yes')</span>
                                             @else
                                                 <span class="badge bg-warning">@lang('No')</span>
                                             @endif
                                         </td>
                                         <td>
                                             @if($order->finished_at)
                                                 <span class="badge bg-success">{{ $order->finished_at->format('Y-m-d H:i') }}</span>
                                             @else
                                                 <span class="badge bg-info">@lang('Pending')</span>
                                             @endif
                                         </td>
                                         <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                         <td>
                                             <div class="btn-group" role="group">
                                                 <a href="{{ route('customers.original-orders.show', [$customer->id, $order->id]) }}" 
                                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="@lang('View')">
                                                     <i class="fas fa-eye"></i>
                                                 </a>
                                                 @can('original-order-edit')
                                                 <a href="{{ route('customers.original-orders.edit', [$customer->id, $order->id]) }}" 
                                                    class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="@lang('Edit')">
                                                     <i class="fas fa-edit"></i>
                                                 </a>
                                                 @endcan
                                                 @can('original-order-delete')
                                                 <form action="{{ route('customers.original-orders.destroy', [$customer->id, $order->id]) }}" 
                                                       method="POST" style="display: inline-block;">
                                                     @csrf
                                                     @method('DELETE')
                                                     <button type="submit" class="btn btn-sm btn-danger" 
                                                             data-bs-toggle="tooltip" title="@lang('Delete')" 
                                                             onclick="return confirm('@lang('Are you sure you want to delete this order?')')">
                                                         <i class="fas fa-trash"></i>
                                                     </button>
                                                 </form>
                                                 @endcan
                                             </div>
                                         </td>
                                     </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        #original-orders-table_wrapper .dt-buttons {
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
        #original-orders-table_wrapper {
            width: 100%;
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
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            const table = $('#original-orders-table').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    search: "{{ __('Search:') }}",
                    lengthMenu: "{{ __('Show _MENU_ entries') }}",
                    info: "{{ __('Showing _START_ to _END_ of _TOTAL_ entries') }}",
                    infoEmpty: "{{ __('Showing 0 to 0 of 0 entries') }}",
                    infoFiltered: "{{ __('(filtered from _MAX_ total entries)') }}",
                    paginate: {
                        first: "{{ __('First') }}",
                        last: "{{ __('Last') }}",
                        next: "{{ __('Next') }}",
                        previous: "{{ __('Previous') }}"
                    },
                    emptyTable: "{{ __('No data available in table') }}",
                    zeroRecords: "{{ __('No matching records found') }}"
                },
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: true, targets: [0, 1, 2, 3, 4, 5] }, // Incluye la nueva columna 'Finished At'
                    { orderable: false, targets: [6], searchable: false } // 'Actions' ahora es el target 6
                ],
                dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
                buttons: [
                    {
                        extend: 'pageLength',
                        className: 'btn btn-secondary'
                    }
                ],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "{{ __('All') }}"]],
                pageLength: 10
            });
            table.on('draw', function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            });
        });
    </script>
@endpush
