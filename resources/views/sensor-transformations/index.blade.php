@extends('layouts.admin')
@section('title', 'Transformación de Sensores')

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
        <li class="breadcrumb-item">{{ __('Transformación de Sensores') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">

            {{-- Card principal --}}
            <div class="card border-0 shadow">
                {{-- Cabecera con título y botón para crear nuevo --}}
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ __('Transformación de Sensores') }}</h4>
                    <a href="{{ route('sensor-transformations.create', ['production_line_id' => $production_line_id]) }}"
                       class="btn btn-primary">
                       {{ __('Añadir Nueva Transformación') }}
                    </a>
                </div>

                {{-- Si no hay transformaciones, mostramos alerta informativa --}}
                @if ($sensorTransformations->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info">
                            {{ __('No hay transformaciones de sensores disponibles para esta línea de producción.') }}
                        </div>
                    </div>
                @else
                    {{-- Tabla con DataTables --}}
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif
                        <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                            <table id="sensorTransformationsTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Nombre') }}</th>
                                    <th>{{ __('Valor Mínimo') }}</th>
                                    <th>{{ __('Valor Intermedio') }}</th>
                                    <th>{{ __('Valor Máximo') }}</th>
                                    <th>{{ __('Valores de Salida') }}</th>
                                    <th>{{ __('Tópico Entrada') }}</th>
                                    <th>{{ __('Tópico Salida') }}</th>
                                    <th>{{ __('Estado') }}</th>
                                    <th>{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sensorTransformations as $transformation)
                                    <tr>
                                        <td>{{ $transformation->id }}</td>
                                        <td>{{ $transformation->name ?? 'Sin nombre' }}</td>
                                        <td>{{ $transformation->min_value ?? 'N/A' }}</td>
                                        <td>{{ $transformation->mid_value ?? 'N/A' }}</td>
                                        <td>{{ $transformation->max_value ?? 'N/A' }}</td>
                                        <td>
                                            <small><strong>≤ Mín:</strong> {{ $transformation->below_min_value_output ?? '0' }}</small><br>
                                            <small><strong>Mín-Med:</strong> {{ $transformation->min_to_mid_value_output ?? '1' }}</small><br>
                                            <small><strong>Med-Máx:</strong> {{ $transformation->mid_to_max_value_output ?? '2' }}</small><br>
                                            <small><strong>> Máx:</strong> {{ $transformation->above_max_value_output ?? '3' }}</small>
                                        </td>
                                        <td>{{ $transformation->input_topic }}</td>
                                        <td>{{ $transformation->output_topic }}</td>
                                        <td>
                                            @if($transformation->active)
                                                <span class="badge badge-success">{{ __('Activo') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ __('Inactivo') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('sensor-transformations.edit', ['sensor_transformation' => $transformation->id, 'production_line_id' => $production_line_id]) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> {{ __('Editar') }}
                                                </a>
                                                <form action="{{ route('sensor-transformations.destroy', ['sensor_transformation' => $transformation->id, 'production_line_id' => $production_line_id]) }}"
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
            $('#sensorTransformationsTable').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
@endpush
