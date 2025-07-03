@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', __('Monitor OEE'))

{{-- Migas de pan (breadcrumb) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">
            <a href="{{ route('customers.index') }}">{{ __('Customers') }}</a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('productionlines.index', ['customer_id' => $customer_id]) }}">
                {{ __('Production Lines') }}
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{ route('sensors.index', ['id' => $production_line_id]) }}">
                {{ __('Sensors') }}
            </a>
        </li>
        <li class="breadcrumb-item">{{ __('Monitor OEE') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">

            {{-- Card principal --}}
            <div class="card border-0 shadow">
                {{-- Cabecera con título y botón para crear nuevo --}}
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ __('Monitor OEE') }}</h4>
                    <a href="{{ route('oee.create', ['production_line_id' => $production_line_id]) }}"
                       class="btn btn-primary">
                       {{ __('Añadir Nuevo Monitor OEE') }}
                    </a>
                </div>

                {{-- Si no hay monitores OEE, mostramos alerta informativa --}}
                @if ($monitorOees->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info">
                            {{ __('No hay monitores OEE disponibles para esta línea de producción.') }}
                        </div>
                    </div>
                @else
                    {{-- Tabla con DataTables --}}
                    <div class="card-body">
                        <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                            <table id="monitorOeeTable" class="display table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>ID Línea de Producción</th>
                                        <th>MQTT Topic</th>
                                        <th>MQTT Topic 2</th>
                                        <th>Sensor Activo</th>
                                        <th>Modbus Activo</th>
                                        <th>OEE Topic</th>
                                        <th>Hora de Inicio del Turno</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($monitorOees as $monitorOee)
                                        <tr>
                                            <td>{{ $monitorOee->id }}</td>
                                            <td>{{ $monitorOee->production_line_id }}</td>
                                            <td>{{ $monitorOee->mqtt_topic }}</td>
                                            <td>{{ $monitorOee->mqtt_topic2 }}</td>
                                            <td>{{ $monitorOee->sensor_active ? 'Activo' : 'Inactivo' }}</td>
                                            <td>{{ $monitorOee->modbus_active ? 'Activo' : 'Inactivo' }}</td>
                                            <td>{{ $monitorOee->topic_oee }}</td>
                                            <td>{{ $monitorOee->time_start_shift }}</td>
                                            <td>
                                                <a href="{{ route('oee.edit', ['oee' => $monitorOee->id, 'production_line_id' => $production_line_id]) }}"
                                                   class="btn btn-sm btn-primary">
                                                    {{ __('Editar') }}
                                                </a>
                                                <form action="{{ route('oee.destroy', ['oee' => $monitorOee->id, 'production_line_id' => $production_line_id]) }}"
                                                      method="POST"
                                                      style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('¿Estás seguro?')">
                                                        {{ __('Eliminar') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>{{-- table-responsive --}}
                    </div>{{-- card-body --}}
                @endif
            </div>{{-- card --}}
        </div>{{-- col-lg-12 --}}
    </div>{{-- row --}}
@endsection

@push('style')
    {{-- DataTables CSS (si no lo cargas globalmente en tu layout) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" />
@endpush

@push('scripts')
    {{-- DataTables JS (si no lo cargas globalmente en tu layout) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#monitorOeeTable').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
@endpush
