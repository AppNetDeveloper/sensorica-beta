@extends('layouts.admin')

@section('title', __('RFID Colores'))

{{-- Migas de pan (breadcrumb) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('RFID Colores') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Card principal --}}
            <div class="card border-0 shadow">
                {{-- Cabecera con título y botones --}}
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        {{ __('RFID Colores para la Línea de Producción') }} {{ $production_line_id }}
                    </h4>
                    <div class="d-flex">
                        <a href="{{ route('rfid.colors.create', ['production_line_id' => $production_line_id]) }}" 
                           class="btn btn-primary me-2">
                            {{ __('Añadir Nuevo Color RFID') }}
                        </a>
                    </div>
                </div>

                @if ($colors->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info">
                            {{ __('No hay colores RFID asociados a esta línea de producción.') }}
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                            <table id="rfidColorsTable" class="display table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('ID') }}</th>
                                        <th>{{ __('Nombre') }}</th>
                                        <th>{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($colors as $color)
                                        <tr>
                                            <td>{{ $color->id }}</td>
                                            <td>{{ $color->name }}</td>
                                            <td>
                                                <a href="{{ route('rfid.colors.edit', ['production_line_id' => $production_line_id, 'color' => $color->id]) }}"
                                                    class="btn btn-sm btn-primary">
                                                     {{ __('Editar') }}
                                                 </a>
                                                 <form action="{{ route('rfid.colors.destroy', ['production_line_id' => $production_line_id, 'color' => $color->id]) }}"
                                                       method="POST" style="display:inline;">
                                                     @csrf
                                                     @method('DELETE')
                                                     <button type="submit"
                                                             class="btn btn-sm btn-danger"
                                                             onclick="return confirm('{{ __('¿Estás seguro de eliminar este color RFID?') }}')">
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
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" />
@endpush

@push('scripts')
    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#rfidColorsTable').DataTable({
                responsive: true,
                scrollX: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
@endpush
