@extends('layouts.admin')
@section('title', __('Smart Sensors'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <a href="{{ route('smartsensors.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary mb-3">Añadir Nuevo Sensor</a>
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered data-table">
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
                                        <a href="{{ route('smartsensors.edit', $sensor->id) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form action="{{ route('smartsensors.destroy', $sensor->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</button>
                                        </form>
                                        <button class="btn btn-sm btn-primary" onclick="showLiveSensor('{{ $sensor->token }}')">Live View</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        /**
         * Muestra un SweetAlert con nombres personalizados y se actualiza cada segundo
         */
        async function showLiveSensor(token) {
            let intervalId; // Variable para manejar el intervalo de actualización

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

                    // Generar contenido HTML dinámicamente con nombres personalizados
                    const dataHtml = Object.entries(data)
                        .filter(([key]) => fieldNames[key]) // Filtrar solo los campos con nombres definidos
                        .map(([key, value]) => `<p><strong>${fieldNames[key]}:</strong> ${value}</p>`)
                        .join('');

                    // Actualizar contenido del SweetAlert
                    Swal.update({
                        title: `Live View - Sensor: ${data.name || 'Desconocido'}`,
                        html: dataHtml,
                    });
                } catch (error) {
                    console.error(error);
                    clearInterval(intervalId); // Detener el intervalo si ocurre un error
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudieron cargar los datos del sensor.',
                    });
                }
            };

            // Mostrar SweetAlert inicial
            Swal.fire({
                title: 'Cargando...',
                html: '<p>Obteniendo datos en tiempo real...</p>',
                didOpen: () => {
                    Swal.showLoading(); // Mostrar indicador de carga
                    fetchData(); // Realizar la primera llamada a la API
                },
                willClose: () => {
                    clearInterval(intervalId); // Limpiar el intervalo al cerrar el modal
                },
            });

            // Actualizar datos cada segundo
            intervalId = setInterval(fetchData, 1000);
        }
    </script>
@endpush

