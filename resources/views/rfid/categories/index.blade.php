@extends('layouts.admin')

@section('title', __('RFID Categorías'))

{{-- Migas de pan (breadcrumb) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('RFID Categorías') }}</li>
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
                        {{ __('Categorías RFID para la Línea de Producción') }} {{ $production_line_id }}
                    </h4>
                    <div class="d-flex">
                        <a href="{{ route('rfid.categories.create', ['production_line_id' => $production_line_id]) }}" 
                           class="btn btn-primary me-2">
                            {{ __('Añadir Nueva Categoría RFID') }}
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                            {{ __('Importar Excel') }}
                        </button>
                    </div>
                </div>

                @if ($categories->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info">
                            {{ __('No hay categorías RFID asociadas a esta línea de producción.') }}
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                            <table id="rfidCategoriesTable" class="display table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>{{ __('ID') }}</th>
                                        <th>{{ __('Nombre') }}</th>
                                        <th>{{ __('EPC') }}</th>
                                        <th>{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td>{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->epc }}</td>
                                            <td>
                                                <a href="{{ route('rfid.categories.edit', $category->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    {{ __('Editar') }}
                                                </a>
                                                <form action="{{ route('rfid.categories.destroy', $category->id) }}"
                                                      method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('{{ __('¿Estás seguro de eliminar esta categoría RFID?') }}')">
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

    {{-- Modal para Importar Excel --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <form action="{{ route('rfid.categories.import', $production_line_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="importModalLabel">{{ __('Importar Categorías RFID') }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Cerrar') }}"></button>
            </div>
            <div class="modal-body">
              <div class="mb-3">
                <label for="excel_file" class="form-label">{{ __('Selecciona el archivo Excel') }}</label>
                <input type="file" name="excel_file" id="excel_file" class="form-control" required>
                @error('excel_file')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cerrar') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('Importar') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

@endsection

@push('style')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" />
    {{-- DataTables Buttons CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
@endpush

@push('scripts')
    {{-- DataTables JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    {{-- DataTables Buttons JS y dependencias --}}
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    {{-- JSZip para exportar a Excel --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.0/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#rfidCategoriesTable').DataTable({
                responsive: true,
                scrollX: true,
                // Definimos el layout de la tabla para incluir el botón de exportación
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Exportar a Excel',
                        exportOptions: {
                            // Se exportan únicamente las columnas 0, 1 y 2 (omitiendo la columna de Acciones)
                            columns: [0, 1, 2]
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });
        });
    </script>
@endpush
