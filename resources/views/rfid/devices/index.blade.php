@extends('layouts.admin')

@section('title', __('RFID Dispositivos'))

{{-- Migas de pan (breadcrumb) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('RFID Dispositivos') }}</li>
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
                        {{ __('Dispositivos RFID') }} {{ $production_line_id }}
                    </h4>
                    <div>
                        <a href="{{ route('rfid.devices.create', ['production_line_id' => $production_line_id]) }}" 
                           class="btn btn-primary me-2">
                            {{ __('Añadir Nuevo Dispositivo RFID') }}
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                            {{ __('Importar Excel') }}
                        </button>
                    </div>
                </div>

                @if ($rfidDevices->isEmpty())
                    <div class="card-body">
                        <div class="alert alert-info">
                            {{ __('No hay dispositivos RFID asociados a esta línea de producción.') }}
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                            <table id="rfidDevicesTable" class="display table table-striped table-bordered" style="width:100%">
                                <thead>
                                    <tr>
                                        <!-- Estas columnas serán exportadas -->
                                        <th>{{ __('Nombre') }}</th>
                                        <th class="d-none">{{ __('RFID Lectura EPC') }}</th>
                                        <th class="d-none">{{ __('RFID Tipo') }}</th>
                                        <th class="d-none">{{ __('MQTT Topic 1') }}</th>
                                        <th class="d-none">{{ __('Función Modelo 0') }}</th>
                                        <th class="d-none">{{ __('Función Modelo 1') }}</th>
                                        <th class="d-none">{{ __('Invers Sensors') }}</th>
                                        <th class="d-none">{{ __('Tiempo óptimo de producción') }}</th>
                                        <th class="d-none">{{ __('Multiplicador velocidad reducida') }}</th>
                                        <th>{{ __('EPC') }}</th>
                                        <th>{{ __('TID') }}</th>
                                        <!-- Columna de acciones (no se exporta) -->
                                        <th>{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rfidDevices as $device)
                                        <tr>
                                            <td>{{ $device->name }}</td>
                                            <!-- Se muestra el EPC del registro relacionado de RfidReading -->
                                            <td class="d-none">{{ optional($device->rfidReading)->epc }}</td>
                                            <td class="d-none">{{ $device->rfid_type }}</td>
                                            <td class="d-none">{{ $device->mqtt_topic_1 }}</td>
                                            <td class="d-none">{{ $device->function_model_0 }}</td>
                                            <td class="d-none">{{ $device->function_model_1 }}</td>
                                            <td class="d-none">{{ $device->invers_sensors }}</td>
                                            <td class="d-none">{{ $device->optimal_production_time }}</td>
                                            <td class="d-none">{{ $device->reduced_speed_time_multiplier }}</td>
                                            <td>{{ $device->epc }}</td>
                                            <td>{{ $device->tid }}</td>
                                            <td>
                                                <a href="{{ route('rfid.devices.edit', $device->id) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    {{ __('Editar') }}
                                                </a>
                                                <form action="{{ route('rfid.devices.destroy', $device->id) }}"
                                                      method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-danger"
                                                            onclick="return confirm('{{ __('¿Estás seguro de eliminar este dispositivo RFID?') }}')">
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
          <form action="{{ route('rfid.devices.import', $production_line_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
              <h5 class="modal-title" id="importModalLabel">{{ __('Importar Dispositivos RFID') }}</h5>
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
            $('#rfidDevicesTable').DataTable({
                responsive: true,
                scrollX: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Exportar a Excel',
                        exportOptions: {
                            // Se exportan las columnas en el siguiente orden:
                            // 0: Nombre  
                            // 1: RFID Lectura EPC  
                            // 2: RFID Tipo  
                            // 3: MQTT Topic 1  
                            // 4: Función Modelo 0  
                            // 5: Función Modelo 1  
                            // 6: Invers Sensors  
                            // 7: Tiempo óptimo de producción  
                            // 8: Multiplicador velocidad reducida  
                            // 9: EPC  
                            // 10: TID  
                            columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
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
