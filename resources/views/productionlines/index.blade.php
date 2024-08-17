@extends('layouts.admin')
@section('title', __('Production Lines'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item">{{ __('Production Lines') }}</li>
    </ul>
@endsection
@section('content')
    <div class="row">
    <div class="mb-3">
                        <a href="{{ route('productionlines.create', ['customer_id' => $customer_id]) }}" class="btn btn-primary">Añadir Nueva Línea</a>

                    </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive py-5 pb-4 dropdown_2">
                        <div class="container-fluid">
                            <table class="table table-bordered data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Token</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    @include('layouts.includes.datatable_css')
@endpush

@push('scripts')
    @include('layouts.includes.datatable_js')
    <script type="text/javascript">
        $(function () {
            var customer_id = "{{ $customer_id }}";
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "/customers/" + customer_id + "/productionlinesjson",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'token', name: 'token'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'updated_at', name: 'updated_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                "columnDefs": [
                    { "targets": [0], "visible": true, "searchable": true, "sortable": true },
                    { "targets": [1], "visible": true, "searchable": true, "sortable": true },
                    { "targets": [5], "render": function (data, type, full, meta) {
                        return data;
                    }},
                ],
            });
        });
    </script>
@endpush
