@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Modbuses')

{{-- Migas de pan (breadcrumb) si las usas en tu layout --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Gestión de Modbuses') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">

            {{-- Card principal --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ __('Gestión de Modbuses') }}</h4>
                    <a href="{{ route('modbuses.create', ['production_line_id' => $production_line_id]) }}"
                       class="btn btn-primary">
                        {{ __('Añadir Nuevo Modbus') }}
                    </a>
                </div>

                <div class="card-body">
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table id="modbusesTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Modelo</th>
                                    <th>Último Valor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($modbuses as $modbus)
                                    <tr>
                                        <td>{{ $modbus->id }}</td>
                                        <td>{{ $modbus->name }}</td>
                                        <td>{{ $modbus->model_name }}</td>
                                        <td>{{ $modbus->last_value }}</td>
                                        <td>
                                            <a href="{{ route('modbuses.edit', $modbus->id) }}"
                                               class="btn btn-sm btn-primary">
                                                {{ __('Editar') }}
                                            </a>
                                            <form action="{{ route('modbuses.destroy', ['production_line_id' => $production_line_id, 'modbus' => $modbus->id]) }}"
                                                  method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('¿Estás seguro?')">
                                                    {{ __('Eliminar') }}
                                                </button>
                                            </form>
                                            <a href="/live-weight/live.html?token={{ $modbus->token }}"
                                               class="btn btn-sm btn-primary">
                                                {{ __('Live View') }}
                                            </a>
                                            <a href="/modbuses/queue-print?token={{ $modbus->token }}"
                                               class="btn btn-sm btn-primary">
                                                {{ __('Api Call List') }}
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
    {{-- Si YA tienes DataTables CSS en tu layout, no hace falta repetirlo --}}
    {{-- Aquí solo a modo de ejemplo (CDN) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" />
@endpush

@push('scripts')
    {{-- Si YA tienes DataTables JS en tu layout, no hace falta repetirlo --}}
    {{-- Aquí solo a modo de ejemplo (CDN) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#modbusesTable').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
                // Agrega más configuraciones si lo deseas (buttons, dom, etc.)
            });
        });
    </script>
@endpush

