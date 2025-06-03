@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', __('Shift Lists'))

{{-- Migas de pan (opcional) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item active">{{ __('Shift Lists') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Mensajes de éxito de redirect --}}
            @if (session('success'))
                <div class="alert alert-success mb-2">{{ session('success') }}</div>
            @endif

            {{-- Listado de Production Lines con botones de acción --}}
            <div class="mb-4">
                <div class="row">
                    @foreach($productionLines as $line)
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">{{ $line->name }}</h5>
                                        <span class="badge bg-primary">ID #{{ $line->id }}</span>
                                    </div>
                                    <div id="status-badge-{{ $line->id }}" class="mt-1 text-center">
                                        <!-- Estado se actualizará aquí dinámicamente -->
                                    </div>
                                </div>
                                <div class="card-body text-center">
                                    @php
                                        // Se asume que $line->lastShiftHistory fue eager loaded en el controlador.
                                        $last = $line->lastShiftHistory;
                                    @endphp

                                    <div class="btn-group" role="group" aria-label="Shift Actions">
                                        @if($last)
                                            @if($last->type === 'shift' && $last->action === 'start')
                                                {{-- Turno iniciado: mostrar Pausar y Finalizar Turno --}}
                                                <button type="button" class="btn btn-outline-warning" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="inicio_pausa"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('Pause') }}">
                                                    <i class="fa fa-pause" style="font-size: 2rem;"  aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="final_trabajo"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('End Shift') }}">
                                                    <i class="fa fa-stop" style="font-size: 2rem;" aria-hidden="true"></i>
                                                </button>
                                            @elseif($last->type === 'stop' && $last->action === 'start')
                                                {{-- Pausa iniciada: mostrar sólo Finalizar pausa --}}
                                                <button type="button" class="btn-outline-warning btn btn-outline-warning" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="final_pausa"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('Reanudar') }}">
                                                    <i class="fa fa-play" style="font-size: 2rem;" aria-hidden="true"></i>
                                                </button>
                                            @elseif($last->type === 'stop' && $last->action === 'end')
                                                {{-- Pausa finalizada: mostrar Inicio pausa y Finalizar Turno --}}
                                                <button type="button" class="btn-outline-warning" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="inicio_pausa"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('Pause') }}">
                                                    <i class="fa fa-pause" style="font-size: 2rem;" aria-hidden="true"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="final_trabajo"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('End Shift') }}">
                                                    <i class="fa fa-stop" style="font-size: 2rem;" aria-hidden="true"></i>
                                                </button>
                                            @elseif($last->type === 'shift' && $last->action === 'end')
                                                {{-- Turno finalizado: mostrar sólo Iniciar Turno --}}
                                                <button type="button" class="btn btn-outline-success" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="inicio_trabajo"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('Start Shift') }}">
                                                    <i class="fa fa-play" style="font-size: 2rem;" aria-hidden="true"></i>
                                                </button>
                                            @else
                                                {{-- Caso por defecto: Iniciar Turno (si no hay historial o estado desconocido) --}}
                                                <button type="button" class="btn btn-outline-success" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                        data-action="inicio_trabajo"
                                                        data-line-id="{{ $line->id }}"
                                                        title="{{ __('Start Shift') }}">
                                                    <i class="fa fa-play" style="font-size: 2rem;" aria-hidden="true"></i>
                                                </button>
                                            @endif
                                        @else
                                            {{-- Sin historial: opción para iniciar el turno --}}
                                            <button type="button" class="btn btn-outline-success" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                                    data-action="inicio_trabajo"
                                                    data-line-id="{{ $line->id }}"
                                                    title="{{ __('Start Shift') }}">
                                                <i class="fa fa-play" style="font-size: 2rem;" aria-hidden="true"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Filtro para DataTable --}}
            <div class="mb-3">
                <label for="productionLineFilter">{{ __('Filter by Production Line') }}</label>
                <select id="productionLineFilter" class="form-select">
                    <option value="">{{ __('All') }}</option>
                    @foreach ($productionLines as $line)
                        <option value="{{ $line->id }}">{{ $line->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tabla de Turnos (DataTable) --}}
            <div class="card border-0 shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    {{-- Aquí podrías agregar otros controles o títulos --}}
                </div>
                <div class="card-body">
                    {{-- >>> Añadida clase 'dt-responsive' <<< --}}
                    <table id="shiftTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Production Line') }}</th>
                                <th>{{ __('Start') }}</th>
                                <th>{{ __('End') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Historial de Turnos -->
            <div class="card border-0 shadow mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">{{ __('Historial de Turnos') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 row">
                        <div class="col-md-3">
                            <label for="historyProductionLineFilter" class="form-label">{{ __('Filtrar por Línea') }}</label>
                            <select id="historyProductionLineFilter" class="form-select">
                                <option value="">{{ __('Todas las líneas') }}</option>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="historyTypeFilter" class="form-label">{{ __('Tipo') }}</label>
                            <select id="historyTypeFilter" class="form-select">
                                <option value="">{{ __('Todos') }}</option>
                                <option value="shift">{{ __('Turno') }}</option>
                                <option value="stop">{{ __('Pausa') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="historyActionFilter" class="form-label">{{ __('Acción') }}</label>
                            <select id="historyActionFilter" class="form-select">
                                <option value="">{{ __('Todas') }}</option>
                                <option value="start">{{ __('Inicio') }}</option>
                                <option value="end">{{ __('Fin') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="resetHistoryFilters" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-undo me-1"></i> {{ __('Restablecer') }}
                            </button>
                        </div>
                    </div>
                    <table id="shiftHistoryTable" class="table table-striped table-bordered dt-responsive nowrap w-100">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Línea de Producción') }}</th>
                                <th>{{ __('Tipo') }}</th>
                                <th>{{ __('Acción') }}</th>
                                <th>{{ __('Usuario') }}</th>
                                <th>{{ __('Fecha/Hora') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para Crear turno --}}
    <div class="modal fade" id="createShiftModal" tabindex="-1" aria-labelledby="createShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createShiftForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createShiftModalLabel">{{ __('Create Shift') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="createProductionLineId" class="form-label">{{ __('Production Line') }}</label>
                            <select class="form-select" name="production_line_id" id="createProductionLineId" required>
                                <option value="" disabled selected>-- {{ __('Select') }} --</option>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="createStartTime" class="form-label">{{ __('Start Time') }}</label>
                            <input type="time" class="form-control" name="start" id="createStartTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="createEndTime" class="form-label">{{ __('End Time') }}</label>
                            <input type="time" class="form-control" name="end" id="createEndTime" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal para Editar turno --}}
    <div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editShiftForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editShiftId" name="id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editShiftModalLabel">{{ __('Edit Shift') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editProductionLineId" class="form-label">{{ __('Production Line') }}</label>
                            <select class="form-select" id="editProductionLineId" name="production_line_id" required>
                                @foreach ($productionLines as $line)
                                    <option value="{{ $line->id }}">{{ $line->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editStartTime" class="form-label">{{ __('Start Time') }}</label>
                            <input type="time" class="form-control" id="editStartTime" name="start" required>
                        </div>
                        <div class="mb-3">
                            <label for="editEndTime" class="form-label">{{ __('End Time') }}</label>
                            <input type="time" class="form-control" id="editEndTime" name="end" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('style')
    {{-- Token CSRF para peticiones AJAX --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    {{-- DataTables Responsive CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    {{-- >>> DataTables Responsive Bootstrap 5 CSS (para icono '+' y estilo) <<< --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    {{-- Botones de DataTables (para exportar Excel, etc.) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    
@endpush

@push('scripts')
    {{-- jQuery debe cargarse antes que DataTables --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- DataTables core JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    {{-- DataTables Responsive JS --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    {{-- >>> DataTables Responsive Bootstrap 5 JS (para icono '+' y funcionalidad) <<< --}}
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    {{-- Extensión de botones para DataTables (ej. export to Excel) --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    {{-- SweetAlert2 (alertas) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Configuración global AJAX con CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // URLs originales
        const baseUrl = window.location.origin;
        const apiIndexUrl = `${baseUrl}/shift-lists/api`;
        const storeUrl    = `${baseUrl}/shift-lists`;
        const updateUrlTemplate = `${baseUrl}/shift-lists/:id`;
        const deleteUrlTemplate = `${baseUrl}/shift-lists/:id`;

        $(document).ready(function() {
            // Inicializar DataTable con filtro de Production Line
            const table = $('#shiftTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: '{{ __("Add Shift") }}',
                        className: 'btn btn-primary',
                        action: function() {
                             // Resetear formulario antes de mostrar
                            $('#createShiftForm')[0].reset();
                            $('#createShiftModal').modal('show');
                        }
                    },
                    {
                        extend: 'excel',
                        text: '{{ __("Export to Excel") }}',
                        className: 'btn btn-success'
                    }
                ],
                order: [[0, 'desc']],
                processing: true, // Indicador de carga
                // serverSide: false, // Asumiendo carga client-side desde la API
                ajax: {
                    url: apiIndexUrl,
                    dataSrc: 'data', // Asumiendo que la API devuelve { data: [...] }
                    data: function(d) {
                        d.production_line = $('#productionLineFilter').val();
                    },
                    error: function(xhr, status, error) { // Manejo de errores mejorado
                        console.error("Error loading DataTable data:", status, error, xhr);
                        Swal.fire('Error', '{{ __("Error loading data. Check console for details.") }}', 'error');
                    }
                },
                columns: [
                    { data: 'id', name: 'id' }, // 'name' útil para server-side
                    { data: 'production_line.name', name: 'production_line.name', defaultContent: '{{ __("No line") }}' },
                    { data: 'start', name: 'start' },
                    { data: 'end', name: 'end' },
                    {
                        data: null,
                        name: 'actions', // Nombre para columna de acciones
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) { // Usar 'row' para datos completos
                            const editBtn = `
                                <button
                                    class="btn btn-sm btn-primary edit-shift"
                                    title="{{ __('Edit') }}"
                                    data-id="${row.id}"
                                    data-production-line-id="${row.production_line_id}"
                                    data-start="${row.start}"
                                    data-end="${row.end}"
                                >
                                    <i class="fa fa-edit"></i> {{-- Icono Editar --}}
                                </button>
                            `;
                            // Se mantiene el formulario de borrado original
                            const deleteForm = `
                                <form
                                    action="${deleteUrlTemplate.replace(':id', row.id)}"
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('{{ __('Are you sure?') }}');" {{-- Confirmación simple --}}
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-danger"
                                        title="{{ __('Delete') }}"
                                    >
                                        <i class="fa fa-trash"></i> {{-- Icono Borrar --}}
                                    </button>
                                </form>
                            `;
                            return editBtn + ' ' + deleteForm;
                        }
                    }
                ],
                responsive: true, // Habilitar responsividad (clave)
                // scrollX: true, // Puedes descomentar si prefieres scroll horizontal además del colapso
                language: { // Traducciones (opcional)
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }
            });

            // Recargar tabla al cambiar filtro
            $('#productionLineFilter').on('change', function() {
                table.ajax.reload();
            });

            // --- MANEJO MODALES (Crear/Editar) ---

            // Enviar formulario de creación
            $('#createShiftForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post(storeUrl, formData)
                    .done(response => {
                        if (response.success) {
                            Swal.fire('{{ __("Saved") }}', response.message, 'success');
                            table.ajax.reload(null, false); // Recargar sin perder paginación
                            $('#createShiftModal').modal('hide');
                        } else {
                            // Mostrar errores si vienen en la respuesta
                            const errorMsg = response.errors ? Object.values(response.errors).join('<br>') : response.message;
                            Swal.fire('{{ __("Error") }}', errorMsg || '{{ __("Could not save.") }}', 'error');
                        }
                    })
                    .fail(xhr => {
                        console.error("Create Error:", xhr);
                        Swal.fire('{{ __("Error") }}', xhr.responseJSON?.message || '{{ __("Server error.") }}', 'error');
                    });
            });

            // Cargar datos en modal de edición
            // Se usa delegación en tbody por si la tabla se redibuja
            $('#shiftTable tbody').on('click', '.edit-shift', function() {
                const button = $(this);
                $('#editShiftId').val(button.data('id'));
                $('#editProductionLineId').val(button.data('production-line-id'));
                // Asegurar formato HH:MM para input type="time"
                $('#editStartTime').val(button.data('start') ? button.data('start').substring(0, 5) : '');
                $('#editEndTime').val(button.data('end') ? button.data('end').substring(0, 5) : '');

                $('#editShiftModal').modal('show');
            });

            // Enviar formulario de edición
            $('#editShiftForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#editShiftId').val();
                const url = updateUrlTemplate.replace(':id', id);
                const formData = $(this).serialize(); // Incluye _method=PUT

                $.ajax({
                    url: url,
                    method: 'POST', // Usar POST para que Laravel reconozca _method=PUT
                    data: formData
                })
                .done(response => {
                    if (response.success) {
                        Swal.fire('{{ __("Updated") }}', response.message, 'success');
                        table.ajax.reload(null, false);
                        $('#editShiftModal').modal('hide');
                    } else {
                        const errorMsg = response.errors ? Object.values(response.errors).join('<br>') : response.message;
                        Swal.fire('{{ __("Error") }}', errorMsg || '{{ __("Could not update.") }}', 'error');
                    }
                })
                .fail(xhr => {
                    console.error("Update Error:", xhr);
                    Swal.fire('{{ __("Error") }}', xhr.responseJSON?.message || '{{ __("Server error.") }}', 'error');
                });
            });

            // --- MANEJO BOTONES PLAY/PAUSE/STOP ---
            // Delegación de eventos en el body para los botones de las tarjetas
            $(document).on('click', 'button[data-action]', function(e) { // Delegar desde un elemento estático superior
                const button = $(this);
                // Evitar que se active si está dentro del modal o tabla
                if (button.closest('.modal').length > 0 || button.closest('#shiftTable').length > 0) {
                    return;
                }

                const action = button.data('action');
                const lineId = button.data('line-id');

                // Deshabilitar botón para evitar doble clic
                button.prop('disabled', true).addClass('opacity-50'); // Estilo visual de deshabilitado

                console.log("Enviando evento:", { production_line_id: lineId, event: action });

                $.ajax({
                    url: "/shift-event", // Ruta original
                    method: "POST",
                    data: {
                        production_line_id: lineId,
                        event: action
                        // CSRF token ya está en $.ajaxSetup
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: response.message || '{{ __("Action sent successfully") }}',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Recargar página para actualizar estado de botones
                        });
                    },
                    error: function(xhr) {
                        console.error("Shift Event Error:", xhr);
                        const err = xhr.responseJSON?.message || '{{ __("Error sending action.") }}';
                        Swal.fire('Error', err, 'error');
                        // Rehabilitar botón si falla
                        button.prop('disabled', false).removeClass('opacity-50');
                    }
                    // 'complete' no es necesario si se rehabilita en error y se recarga en success
                });
            });

        });

        // Función para actualizar los botones según el estado
        function updateShiftButtons(statuses) {
            statuses.forEach(status => {
                const lineId = status.line_id;
                const lastShift = status.last_shift;
                const buttonGroup = $(`button[data-line-id="${lineId}"]`).closest('.btn-group');
                
                if (!buttonGroup.length) return; // Si no existe el grupo de botones, salir
                
                // Guardar el estado actual para comparar
                const currentState = buttonGroup.html();
                let newState = '';
                
                if (!lastShift) {
                    // Sin historial: mostrar solo Iniciar Turno
                    newState = `
                        <button type="button" class="btn btn-outline-success" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="inicio_trabajo"
                                data-line-id="${lineId}"
                                title="{{ __('Start Shift') }}">
                            <i class="fa fa-play" style="font-size: 2rem;"></i>
                        </button>
                    `;
                } else if (lastShift.type === 'shift' && lastShift.action === 'start') {
                    // Turno iniciado: mostrar Pausar y Finalizar Turno
                    newState = `
                        <button type="button" class="btn btn-outline-warning" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="inicio_pausa"
                                data-line-id="${lineId}"
                                title="{{ __('Pause') }}">
                            <i class="fa fa-pause" style="font-size: 2rem;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="final_trabajo"
                                data-line-id="${lineId}"
                                title="{{ __('End Shift') }}">
                            <i class="fa fa-stop" style="font-size: 2rem;"></i>
                        </button>
                    `;
                } else if (lastShift.type === 'stop' && lastShift.action === 'start') {
                    // Pausa iniciada: mostrar solo Reanudar
                    newState = `
                        <button type="button" class="btn btn-outline-warning" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="final_pausa"
                                data-line-id="${lineId}"
                                title="{{ __('Resume') }}">
                            <i class="fa fa-play" style="font-size: 2rem;"></i>
                        </button>
                    `;
                } else if (lastShift.type === 'stop' && lastShift.action === 'end') {
                    // Pausa finalizada: mostrar Inicio pausa y Finalizar Turno
                    newState = `
                        <button type="button" class="btn btn-outline-warning" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="inicio_pausa"
                                data-line-id="${lineId}"
                                title="{{ __('Pause') }}">
                            <i class="fa fa-pause" style="font-size: 2rem;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="final_trabajo"
                                data-line-id="${lineId}"
                                title="{{ __('End Shift') }}">
                            <i class="fa fa-stop" style="font-size: 2rem;"></i>
                        </button>
                    `;
                } else if (lastShift.type === 'shift' && lastShift.action === 'end') {
                    // Turno finalizado: mostrar solo Iniciar Turno
                    newState = `
                        <button type="button" class="btn btn-outline-success" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="inicio_trabajo"
                                data-line-id="${lineId}"
                                title="{{ __('Start Shift') }}">
                            <i class="fa fa-play" style="font-size: 2rem;"></i>
                        </button>
                    `;
                } else {
                    // Estado desconocido: mostrar solo Iniciar Turno
                    newState = `
                        <button type="button" class="btn btn-outline-success" style="padding: 1rem 2rem; font-size: 1.25rem;"
                                data-action="inicio_trabajo"
                                data-line-id="${lineId}"
                                title="{{ __('Start Shift') }}">
                            <i class="fa fa-play" style="font-size: 2rem;"></i>
                        </button>
                    `;
                }
                
                // Actualizar el estado en el encabezado de la tarjeta
                const statusBadge = $(`#status-badge-${lineId}`);
                if (statusBadge.length) {
                    if (!lastShift) {
                        statusBadge.html('<span class="badge bg-secondary"><i class="fas fa-power-off me-1"></i> {{ __("shift.shift_stopped") }}</span>');
                    } else if (lastShift.type === 'shift' && lastShift.action === 'start') {
                        statusBadge.html('<span class="badge bg-success"><i class="fas fa-play-circle me-1"></i> {{ __("shift.shift_in_progress") }}</span>');
                    } else if (lastShift.type === 'stop' && lastShift.action === 'start') {
                        statusBadge.html('<span class="badge bg-warning text-dark"><i class="fas fa-pause-circle me-1"></i> {{ __("shift.shift_paused") }}</span>');
                    } else if (lastShift.type === 'stop' && lastShift.action === 'end') {
                        statusBadge.html('<span class="badge bg-info text-white"><i class="fas fa-redo me-1"></i> {{ __("shift.shift_resumed") }}</span>');
                    } else if (lastShift.type === 'shift' && lastShift.action === 'end') {
                        statusBadge.html('<span class="badge bg-danger"><i class="fas fa-stop-circle me-1"></i> {{ __("shift.shift_ended") }}</span>');
                    } else {
                        statusBadge.html('<span class="badge bg-secondary"><i class="fas fa-question-circle me-1"></i> {{ __("shift.status_unknown") }}</span>');
                    }
                }
                
                // Solo actualizar los botones si ha cambiado el estado
                if (currentState !== newState) {
                    buttonGroup.html(newState);
                }
            });
        }


        // Función para actualizar los estados
        function updateShiftStatuses() {
            $.get(`${baseUrl}/api/shift/statuses`)
                .done(function(response) {
                    updateShiftButtons(response);
                })
                .fail(function(error) {
                    console.error('Error al actualizar estados:', error);
                });
        }

        // Función para mostrar mensajes de error
        function showErrorMessage(message) {
            const table = $('#shiftHistoryTable');
            table.find('tbody').html(`
                <tr>
                    <td colspan="6" class="text-center">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${message}
                        </div>
                    </td>
                </tr>
            `);
        }
        
        // Función para inicializar la tabla de historial
        function initializeHistoryTable() {
            console.log('Inicializando DataTable...');
            
            // Primero, verifiquemos que la tabla existe
            if ($.fn.DataTable.isDataTable('#shiftHistoryTable')) {
                $('#shiftHistoryTable').DataTable().destroy();
            }
            
            // Inicializar DataTable
            const table = $('#shiftHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                
                // Configuración de AJAX
                ajax: {
                    url: `${baseUrl}/api/shift-history`,
                    type: 'GET',
                    data: function(d) {
                        // Agregar los parámetros de búsqueda
                        d.production_line_id = $('#historyProductionLineFilter').val() || '';
                        d.type = $('#historyTypeFilter').val() || '';
                        d.action = $('#historyActionFilter').val() || '';
                        
                        // Log the request data for debugging
                        console.log('Enviando parámetros al servidor:', JSON.stringify({
                            draw: d.draw,
                            start: d.start,
                            length: d.length,
                            search: d.search,
                            order: d.order,
                            production_line_id: d.production_line_id,
                            type: d.type,
                            action: d.action
                        }, null, 2));
                        
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('Datos recibidos del servidor:', JSON.stringify({
                            draw: json.draw,
                            recordsTotal: json.recordsTotal,
                            recordsFiltered: json.recordsFiltered,
                            data: json.data ? json.data.length : 0
                        }, null, 2));
                        
                        if (!json.data || json.data.length === 0) {
                            console.log('No se encontraron registros en la respuesta');
                            return [];
                        }
                        
                        // Mapear los datos para asegurar que tengan el formato correcto
                        return json.data.map(item => ({
                            id: item.id,
                            production_line: item.production_line || null,
                            type: item.type,
                            action: item.action,
                            operator: item.operator || null,
                            created_at: item.created_at
                        }));
                    },
                    error: function(xhr, error, thrown) {
                        console.error('Error en la petición AJAX:', {
                            status: xhr.status,
                            statusText: xhr.statusText,
                            response: xhr.responseText,
                            error: error,
                            thrown: thrown
                        });
                        
                        showErrorMessage('Error al cargar el historial. Por favor, inténtalo de nuevo.');
                        return [];
                    }
                },
                
                // Configuración de columnas
                columns: [
                    { 
                        data: 'id',
                        name: 'id',
                        className: 'text-center',
                        width: '5%'
                    },
                    { 
                        data: 'production_line',
                        name: 'production_line.name',
                        className: 'text-center',
                        render: function(data, type, row) {
                            return data ? data.name : 'N/A';
                        }
                    },
                    { 
                        data: 'type',
                        name: 'type',
                        className: 'text-center',
                        render: function(data) {
                            const types = {
                                'shift': '{{ __("Turno") }}',
                                'stop': '{{ __("Pausa") }}'
                            };
                            return `<span class="badge bg-info">${types[data] || data}</span>`;
                        }
                    },
                    { 
                        data: 'action',
                        name: 'action',
                        className: 'text-center',
                        render: function(data) {
                            const actions = {
                                'start': '{{ __("Inicio") }}',
                                'end': '{{ __("Fin") }}'
                            };
                            const badgeClass = data === 'start' ? 'bg-success' : 'bg-danger';
                            return `<span class="badge ${badgeClass}">${actions[data] || data}</span>`;
                        }
                    },
                    { 
                        data: 'operator',
                        name: 'operator.name',
                        className: 'text-center',
                        render: function(data) {
                            return data ? data.name : 'Sistema';
                        }
                    },
                    { 
                        data: 'created_at',
                        name: 'created_at',
                        className: 'text-center',
                        render: function(data) {
                            if (!data) return '';
                            const date = new Date(data);
                            return date.toLocaleString('es-ES', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            });
                        }
                    }
                ],
                
                // Configuración de paginación
                paging: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                displayStart: 0,
                autoWidth: false,
                
                // Configuración de búsqueda
                searching: true,
                
                // Configuración de ordenación
                order: [[0, 'desc']],
                
                // Configuración de idioma
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json',
                    emptyTable: 'No hay datos disponibles en la tabla',
                    zeroRecords: 'No se encontraron registros que coincidan con la búsqueda',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'No hay registros disponibles',
                    infoFiltered: '(filtrado de _MAX_ registros en total)'
                },
                
                // Botones de exportación
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                
                // Callbacks
                drawCallback: function() {
                    const api = this.api();
                    const recordsDisplay = api.page.info().recordsDisplay;
                    console.log('Tabla dibujada. Mostrando ' + recordsDisplay + ' registros');
                    
                    if (recordsDisplay === 0) {
                        // Mostrar mensaje personalizado cuando no hay datos
                        const emptyMsg = 'No se encontraron registros';
                        $('.dataTables_empty').html(emptyMsg);
                    }
                    
                    // Forzar el redibujado de los botones de DataTables
                    api.columns.adjust().responsive.recalc();
                },
                error: function(xhr, error, thrown) {
                    console.error('Error en DataTable:', { xhr, error, thrown });
                    showErrorMessage('Error al cargar los datos. Por favor, recarga la página.');
                },
                initComplete: function() {
                    console.log('DataTable inicializada correctamente');
                }
            });
        }
        
        // Variable global para la tabla
        let historyTable;
        
        // Inicializar todo cuando el documento esté listo
        $(document).ready(function() {
            try {
                // Inicializar la tabla de historial
                historyTable = initializeHistoryTable();
                
                // Configurar eventos de filtrado
                $('#historyProductionLineFilter, #historyTypeFilter, #historyActionFilter').on('change', function() {
                    console.log('Filtro cambiado, recargando tabla...');
                    if (historyTable) {
                        historyTable.ajax.reload();
                    }
                });
                
                // Restablecer filtros
                $('#resetHistoryFilters').on('click', function() {
                    $('#historyProductionLineFilter, #historyTypeFilter, #historyActionFilter').val('');
                    if (historyTable) {
                        historyTable.ajax.reload();
                    }
                });
                
                // Actualizar automáticamente la tabla cada 30 segundos
                setInterval(function() {
                    try {
                        const table = $('#shiftHistoryTable').DataTable();
                        if (table) {
                            console.log('Actualizando tabla automáticamente...');
                            table.ajax.reload(null, false);
                        } else {
                            console.log('No se pudo obtener la instancia de DataTable');
                        }
                    } catch (e) {
                        console.log('Error al actualizar la tabla:', e.message);
                    }
                }, 30000);
                
                // Actualizar estados de los turnos
                updateShiftStatuses();
                
                // Actualizar estados cada 5 segundos
                setInterval(updateShiftStatuses, 5000);
                
            } catch (e) {
                console.error('Error al inicializar la tabla:', e);
            }
        });
    </script>
@endpush
