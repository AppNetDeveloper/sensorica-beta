@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Estadísticas de Modbus')

{{-- Contenido principal --}}
@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Card principal --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Estadísticas de Modbus</h4>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                    {{-- Filtros --}}
                    <div class="d-flex align-items-center mb-4">
                        <div class="form-group mr-3">
                            <label for="modbusSelect" class="mr-2">Modbus:</label>
                            <select id="modbusSelect" class="form-control" style="width: 200px;">
                                <!-- Opciones dinámicas -->
                            </select>
                        </div>
                        <div class="form-group mr-3">
                            <label for="startDate" class="mr-2">Fecha de Inicio:</label>
                            <input type="datetime-local" id="startDate" class="form-control" style="width: 200px;">
                        </div>
                        <div class="form-group">
                            <label for="endDate" class="mr-2">Fecha de Fin:</label>
                            <input type="datetime-local" id="endDate" class="form-control" style="width: 200px;">
                        </div>
                        <div class="form-group">
                            <button id="fetchData" class="btn btn-success" style="margin-left: 10px;">Filtrar</button>
                        </div>
                    </div>

                    {{-- Tabla de datos --}}
                    <div class="table-responsive" style="padding: 15px;">
                        <table id="controlWeightTable" class="display nowrap table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Último Peso</th>
                                    <th>Última Dimensión</th>
                                    <th>Último Número de Cajas</th>
                                    <th>Último Código de Barras</th>
                                    <th>Último Código de Barras Final</th>
                                    <th>Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Datos dinámicos -->
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
        .table-responsive {
            border-radius: 8px;
            background-color: transparent; /* Fondo transparente */
        }
    </style>
@endpush

@push('scripts')
    {{-- DataTables JS --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

    <script>
        const token = new URLSearchParams(window.location.search).get('token');

        // Cargar Modbus
        async function fetchModbuses() {
            try {
                const response = await fetch(`/api/modbuses?token=${token}`);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();

                const modbusSelect = $('#modbusSelect');
                modbusSelect.empty();
                data.forEach(modbus => {
                    modbusSelect.append(`<option value="${modbus.token}">${modbus.name}</option>`);
                });
            } catch (error) {
                console.error("Error al cargar modbuses:", error);
            }
        }

        // Cargar datos en la tabla
        async function fetchControlWeights(modbusToken, startDate, endDate) {
            try {
                const url = `/api/control-weights/${modbusToken}/all?token=${token}&start_date=${startDate}&end_date=${endDate}`;
                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();

                if ($.fn.DataTable.isDataTable('#controlWeightTable')) {
                    $('#controlWeightTable').DataTable().clear().destroy();
                }

                $('#controlWeightTable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'excelHtml5', text: 'Exportar a Excel', className: 'btn btn-success' }
                    ],
                    data: data,
                    columns: [
                        { data: 'last_control_weight', defaultContent: 'N/A' },
                        { data: 'last_dimension', defaultContent: 'N/A' },
                        { data: 'last_box_number', defaultContent: 'N/A' },
                        { data: 'last_barcoder', defaultContent: 'N/A' },
                        { data: 'last_final_barcoder', defaultContent: 'N/A' },
                        { data: 'created_at', defaultContent: 'N/A' }
                    ],
                    order: [[5, 'desc']],
                    paging: true,
                    pageLength: 10,
                    lengthChange: true,
                    searching: true
                });
            } catch (error) {
                console.error("Error al cargar datos:", error);
            }
        }

        $(document).ready(() => {
            fetchModbuses();

            $('#fetchData').click(() => {
                const modbusToken = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                if (modbusToken && startDate && endDate) {
                    fetchControlWeights(modbusToken, startDate, endDate);
                } else {
                    alert("Por favor, completa todos los campos.");
                }
            });
        });
    </script>
@endpush
