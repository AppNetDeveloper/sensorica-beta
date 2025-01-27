@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Estadísticas de Líneas de Producción')

{{-- Contenido principal --}}
@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
           {{-- Card principal --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Estadísticas de Líneas de Producción</h4>
                </div>
                <div class="card-body" style="padding: 1.5rem;">
                   {{-- Filtros --}}
                    <div class="d-flex align-items-center mb-4">
                        <div class="form-group mr-3">
                            <label for="modbusSelect" class="mr-2">Línea de Producción:</label>
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
                    <div class="table-responsive" style="padding: 15px; overflow-x: auto;">
                        <table id="controlWeightTable" class="display nowrap table table-striped table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Linea ID</th>
                                    <th>Orden ID</th>
                                    <th>Cajas</th>
                                    <th>UDS Caja</th>
                                    <th>Unidades</th>
                                    <th>UPM Real</th>
                                    <th>UPM Teórico</th>
                                    <th>Seg/U Real</th>
                                    <th>Seg/U Teórico</th>
                                    <th>Unid Hechas Real</th>
                                    <th>Unid Hechas Teórico</th>
                                    <th>Paradas Sensores</th>
                                    <th>Tiempo Paradas Sensores</th>
                                    <th>Tiempo Paradas Prod</th>
                                    <th>Unid Hechas</th>
                                    <th>Unid Pendientes</th>
                                    <th>Unid Atrasadas</th>
                                    <th>Tiempo Lento</th>
                                    <th>Tiempo Rápido</th>
                                    <th>Tiempo Fuera</th>
                                    <th>Fin Teórico</th>
                                    <th>Fin Real</th>
                                    <th>OEE</th>
                                    <th>Nombre Línea</th>
                                    <th>Creado</th>
                                    <th>Actualizado</th>
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
        .dataTables_wrapper {
            overflow-x: auto;
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
        console.log("Token obtenido:", token);

        async function fetchProductionLines() {
            try {
                console.log("Intentando obtener líneas de producción...");
                const response = await fetch(`/api/production-lines/${token}`);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Líneas de producción recibidas:", data);

                const modbusSelect = $('#modbusSelect');
                modbusSelect.empty();
                data.forEach(line => {
                    modbusSelect.append(`<option value="${line.token}">${line.name}</option>`);
                });
            } catch (error) {
                console.error("Error al cargar líneas de producción:", error);
            }
        }

        async function fetchOrderStats(lineToken, startDate, endDate) {
            try {
                const url = `/api/order-stats-all?token=${lineToken}&start_date=${startDate}&end_date=${endDate}`;
                console.log("URL de datos de estadísticas:", url);

                const response = await fetch(url);
                if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
                const data = await response.json();
                console.log("Datos de estadísticas recibidos:", data);

                const table = $('#controlWeightTable').DataTable({
                    dom: 'Bfrtip',
                    buttons: [
                        { extend: 'excelHtml5', text: 'Exportar a Excel', className: 'btn btn-success' }
                    ],
                    scrollX: true, // Asegúrate de que esto esté configurado
                    scrollY: 400,
                    data: data,
                    destroy: true, // Añade esto para permitir la reinicialización
                    columns: [
                        { data: 'id' },
                        { data: 'production_line_id' },
                        { data: 'order_id' },
                        { data: 'box' },
                        { data: 'units_box' },
                        { data: 'units' },
                        { data: 'units_per_minute_real' },
                        { data: 'units_per_minute_theoretical' },
                        { data: 'seconds_per_unit_real' },
                        { data: 'seconds_per_unit_theoretical' },
                        { data: 'units_made_real' },
                        { data: 'units_made_theoretical' },
                        { data: 'sensor_stops_count' },
                        { data: 'sensor_stops_time' },
                        { data: 'production_stops_time' },
                        { data: 'units_made' },
                        { data: 'units_pending' },
                        { data: 'units_delayed' },
                        { data: 'slow_time' },
                        { data: 'fast_time' },
                        { data: 'out_time' },
                        { data: 'theoretical_end_time' },
                        { data: 'real_end_time' },
                        { data: 'oee' },
                        { data: 'production_line_name' },
                        { data: 'created_at' },
                        { data: 'updated_at' }
                    ],
                    order: [[25, 'desc']], // Ordenar por 'created_at' en orden descendente
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
            fetchProductionLines();

            $('#fetchData').click(() => {
                const lineToken = $('#modbusSelect').val();
                const startDate = $('#startDate').val();
                const endDate = $('#endDate').val();
                console.log("Parámetros seleccionados:", { lineToken, startDate, endDate });

                if (lineToken && startDate && endDate) {
                    fetchOrderStats(lineToken, startDate, endDate);
                } else {
                    alert("Por favor, completa todos los campos.");
                }
            });
        });
    </script>
@endpush