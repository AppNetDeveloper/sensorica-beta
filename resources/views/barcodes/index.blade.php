@extends('layouts.admin')

@section('title', __('Barcodes'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => request()->route('production_line_id')]) }}">
                {{ __('Production Lines') }}
            </a>
        </li>
        <li class="breadcrumb-item">{{ __('Barcodes') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Tarjeta principal --}}
            <div class="card border-0 shadow">
                {{-- Cabecera: título y botones --}}
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ __('Barcodes') }}</h4>
                    <div>
                        <a href="{{ route('barcodes.create', ['production_line_id' => $production_line_id]) }}"
                           class="btn btn-primary me-2">
                            {{ __('Añadir Nuevo Barcode') }}
                        </a>
                        <a href="{{ route('logs.view') }}" class="btn btn-secondary">
                            {{ __('Ver Logs') }}
                        </a>
                    </div>
                </div>

                {{-- Cuerpo: tabla DataTables --}}
                <div class="card-body">
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table class="table table-bordered data-table" id="barcodes-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Token</th>
                                    <th>Machine ID</th>
                                    <th>OPE ID</th>
                                    <th>Last Barcode</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- DataTables llena este tbody vía Ajax --}}
                            </tbody>
                        </table>
                    </div>{{-- table-responsive --}}
                </div>{{-- card-body --}}
            </div>{{-- card --}}
        </div>{{-- col-lg-12 --}}
    </div>{{-- row --}}
@endsection

@push('style')
    @include('layouts.includes.datatable_css')
    <style>
        /* Ajuste del scroll horizontal y compatibilidad móvil */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* para iOS */
            white-space: nowrap;
        }
    </style>
@endpush

@push('scripts')
    @include('layouts.includes.datatable_js')

    <script type="text/javascript">
        $(function () {
            var production_line_id = "{{ $production_line_id }}";
            $('#barcodes-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "/productionlines/" + production_line_id + "/barcodesjson",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'token', name: 'token'},
                    {data: 'machine_id', name: 'machine_id'},
                    {data: 'ope_id', name: 'ope_id'},
                    {data: 'last_barcode', name: 'last_barcode'},
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                scrollX: true,
                autoWidth: false,
                columnDefs: [
                    { targets: [0,1], visible: true, searchable: true, sortable: true },
                    {
                        targets: [5],
                        render: function (data) {
                            // Personaliza la columna 'last_barcode' si lo deseas
                            return data;
                        }
                    },
                ],
                // Opciones de scroll
                scrollY: '400px',
                scrollCollapse: true
            });
        });
    </script>
@endpush
