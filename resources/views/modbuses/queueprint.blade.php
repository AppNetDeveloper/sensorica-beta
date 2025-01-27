@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Listado de Queue Print')

{{-- Contenido principal --}}
@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Card principal --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Listado de Queue Print</h4>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    {{-- Filtros: Usado y Info --}}
                    <div class="d-flex align-items-center mb-4">
                        <div class="form-group mr-3">
                            <label for="usedSelect" class="mr-2">Filtrar por Usado:</label>
                            <select id="usedSelect" class="form-control" style="width: 200px;">
                                <option value="all">Todos</option>
                                <option value="0">No usados</option>
                                <option value="1">Usados</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="infoSelect" class="mr-2">Info:</label>
                            <select id="infoSelect" class="form-control" style="width: 200px;">
                                <option value="all">All</option>
                                <option value="lite">Lite</option>
                            </select>
                        </div>
                    </div>

                    {{-- Tabla de datos --}}
                    <div class="table-responsive" style="padding: 15px;">
                        <table id="queuePrintTable" class="display nowrap table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Modbus ID</th>
                                    <th>Valor</th>
                                    <th>Usado</th>
                                    <th>URL de Retorno</th>
                                    <th>Token de Retorno</th>
                                    <th>Fecha de Creación</th>
                                    <th>Fecha de Actualización</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se insertarán aquí mediante JavaScript -->
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <style>
        .dt-buttons {
            margin-bottom: 1rem;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background-color: #28a745;
            color: #fff !important;
            border-radius: 5px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #218838;
        }
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 5px;
        }
        th {
            position: relative;
            padding-right: 20px;
        }
        th.sorting:after,
        th.sorting_asc:after,
        th.sorting_desc:after {
            content: '';
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            border: 4px solid transparent;
        }
        th.sorting:after {
            border-top: 4px solid #ccc;
        }
        th.sorting_asc:after {
            border-bottom: 4px solid #000;
        }
        th.sorting_desc:after {
            border-top: 4px solid #000;
        }
        .table-responsive {
            border-radius: 8px;
            background-color: transparent; /* Fondo transparente */
        }
    </style>
@endpush

@push('scripts')
    {{-- Librerías necesarias --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function () {
            const token = new URLSearchParams(window.location.search).get('token');
            let used = 'all'; 
            let info = 'all'; 
            let table;

            async function fetchQueuePrintData() {
                const apiUrl = `/api/queue-print-list?token=${token}&used=${used}`;
                const response = await fetch(apiUrl);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                return await response.json();
            }

            function initializeTable(data) {
                table = $('#queuePrintTable').DataTable({
                    data: data,
                    columns: [
                        { data: 'id', orderable: true },
                        { data: 'modbus_id' },
                        { data: 'value' },
                        { data: 'used', render: data => data ? 'Sí' : 'No' },
                        { data: 'url_back' },
                        { data: 'token_back' },
                        { data: 'created_at' },
                        { data: 'updated_at' }
                    ],
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'excelHtml5', text: 'Exportar a Excel', className: 'btn btn-success' },
                        { extend: 'print', text: 'Imprimir', className: 'btn btn-success' }
                    ],
                    scrollX: true,
                    order: [[0, 'desc']],
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    searching: true,
                    autoWidth: false
                });
            }

            async function loadTableData() {
                try {
                    const data = await fetchQueuePrintData();
                    if (table) {
                        table.clear().rows.add(data).draw();
                    } else {
                        initializeTable(data);
                    }

                    if (info === 'lite') {
                        table.column(1).visible(false);
                        table.column(4).visible(false);
                        table.column(5).visible(false);
                        table.column(6).visible(false);
                        table.column(7).visible(false);
                    } else {
                        table.columns().visible(true);
                    }
                    table.columns.adjust();
                } catch (error) {
                    console.error("Error al cargar datos:", error);
                }
            }

            $('#usedSelect').change(function () {
                used = $(this).val();
                loadTableData();
            });

            $('#infoSelect').change(function () {
                info = $(this).val();
                loadTableData();
            });

            loadTableData();
        });
    </script>
@endpush
