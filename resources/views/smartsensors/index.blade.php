@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', __('Smart Sensors'))

{{-- Migas de pan (breadcrumb) si las usas --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Smart Sensors') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">

            {{-- Card principal --}}
            <div class="card border-0 shadow">
                {{-- Cabecera: título y botón "Añadir Sensor" --}}
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ __('Smart Sensors') }}</h4>
                    <a href="{{ route('smartsensors.create', ['production_line_id' => $production_line_id]) }}" 
                       class="btn btn-primary">
                        {{ __('Añadir Nuevo Sensor') }}
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        {{-- Se cambia 'table' con clase 'data-table' por un ID #sensorsTable para DataTables --}}
                        <table id="sensorsTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo de Sensor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sensors as $sensor)
                                    <tr>
                                        <td>{{ $sensor->id }}</td>
                                        <td>{{ $sensor->name }}</td>
                                        <td>{{ $sensor->sensor_type }}</td>
                                        <td>
                                            <a href="{{ route('smartsensors.edit', $sensor->id) }}"
                                               class="btn btn-sm btn-primary">
                                                {{ __('Editar') }}
                                            </a>
                                            <form action="{{ route('smartsensors.destroy', $sensor->id) }}"
                                                  method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('¿Estás seguro?')">
                                                    {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                            <a href="{{ route('smartsensors.live-view', $sensor->id) }}" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-chart-line me-1"></i>{{ __('Live View') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>{{-- table-responsive --}}
                </div>{{-- card-body --}}
            </div>{{-- card --}}
        </div>{{-- col-lg-12 --}}
    </div>{{-- row --}}
@endsection

@push('style')
    {{-- DataTables CSS (ejemplo con CDN; omite si ya lo tienes en tu layout) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" />
@endpush

@push('scripts')
    {{-- DataTables JS (ejemplo con CDN; omite si ya lo tienes en tu layout) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Inicialización de DataTables al cargar la vista
        $(document).ready(function() {
            $('#sensorsTable').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });

        /**
         * Muestra un SweetAlert con nombres personalizados y se actualiza cada segundo
         */
        async function showLiveSensor(token) {
            let intervalId;

            // Mapeo de nombres personalizados
            const fieldNames = {
                orderId: "ID de Orden",
                quantity: "Cantidad Total",
                uds: "Unidades por Pedido",
                optimal_production_time: "Tiempo Óptimo de Producción (s)",
                reduced_speed_time_multiplier: "Multiplicador de Tiempo Reducido",
                count_total: "Total General",
                count_total_0: "Conteo Total (Estado 0)",
                count_total_1: "Conteo Total (Estado 1)",
                count_shift_0: "Conteo por Turno (Estado 0)",
                count_shift_1: "Conteo por Turno (Estado 1)",
                count_order_0: "Conteo por Pedido (Estado 0)",
                count_order_1: "Conteo por Pedido (Estado 1)",
                count_week_0: "Conteo por Semana (Estado 0)",
                count_week_1: "Conteo por Semana (Estado 1)",
                downtime_count: "Tiempo de Inactividad (s)"
            };

            const fetchData = async () => {
                try {
                    const response = await fetch(`/api/sensors/${token}`);
                    if (!response.ok) {
                        throw new Error('Error al obtener datos del sensor.');
                    }
                    const data = await response.json();

                    // Generar contenido con nombres personalizados
                    const dataHtml = Object.entries(data)
                        .filter(([key]) => fieldNames[key])
                        .map(([key, value]) => `<p><strong>${fieldNames[key]}:</strong> ${value}</p>`)
                        .join('');

                    Swal.update({
                        title: `Live View - Sensor: ${data.name || 'Desconocido'}`,
                        html: dataHtml,
                    });
                } catch (error) {
                    console.error(error);
                    clearInterval(intervalId);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los datos del sensor.',
                    });
                }
            };

            Swal.fire({
                title: 'Cargando...',
                html: '<p>Obteniendo datos en tiempo real...</p>',
                didOpen: () => {
                    Swal.showLoading();
                    fetchData();
                },
                willClose: () => {
                    clearInterval(intervalId);
                },
            });

            // Actualizar cada segundo
            intervalId = setInterval(fetchData, 1000);
        }
    </script>
@endpush
