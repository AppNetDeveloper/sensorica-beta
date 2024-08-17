@extends('layouts.admin')
@section('title', __('Customers'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Customers') }}
        </li>
    </ul>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive py-5 pb-4 dropdown_2">
                        <div class="container-fluid">
                            <table class="table table-bordered data-table">
                                <!-- Encabezados de las columnas aquí -->
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Token ZeroTier</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <!-- Cuerpo de la tabla se llenará automáticamente -->
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
    $('.data-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "/customers/getCustomers", // Ruta Laravel para obtener datos
        columns: [
            {data: 'id', name: 'id'},
            {data: 'name', name: 'name'},
            {data: 'token_zerotier', name: 'token_zerotier'},
            {data: 'created_at', name: 'created_at'},
            {data: 'updated_at', name: 'updated_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false},
        ],
        "columnDefs": [
            { "targets": [0], "visible": true, "searchable": true, "sortable": true },
            { "targets": [1], "visible": true, "searchable": true, "sortable": true },
            // Asegura que la columna 'action' sea tratada como HTML
            { "targets": [5], "render": function (data, type, full, meta) {
                return data;
            }},
        ],
    });
});


    </script>
@endpush