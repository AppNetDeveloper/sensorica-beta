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

            <div class="card border-0 shadow">
                <div class="card-header d-flex justify-content-between align-items-center">

                </div>
                <div class="card-body">
                    <table id="shiftTable" class="table table-striped table-bordered w-100">
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
    {{-- Botones de DataTables (para exportar Excel, etc.) --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
@endpush

@push('scripts')
    {{-- jQuery debe cargarse antes que DataTables --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- DataTables core JS --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    {{-- Extensión de botones para DataTables (ej. export to Excel) --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    {{-- SweetAlert2 (alertas) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Para incluir el token CSRF en todas las peticiones AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Ajusta baseUrl en caso de que tengas subcarpetas o dominio
        const baseUrl = "{{ rtrim(config('app.url'), '/') }}";
        const apiIndexUrl = `${baseUrl}/shift-lists/api`; // GET para DataTables
        const storeUrl    = `${baseUrl}/shift-lists`;     // POST crear
        const updateUrlTemplate = `${baseUrl}/shift-lists/:id`; // PUT update
        const deleteUrlTemplate = `${baseUrl}/shift-lists/:id`; // DELETE

        $(document).ready(function() {
            // Inicializar DataTable
            const table = $('#shiftTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: '{{ __("Add Shift") }}',
                        className: 'btn btn-primary',
                        action: function() {
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
                ajax: {
                    url: apiIndexUrl,
                    dataSrc: 'data',
                    error: function() {
                        Swal.fire('Error', '{{ __("Error loading data") }}', 'error');
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'production_line.name', defaultContent: '{{ __("No line") }}' },
                    { data: 'start' },
                    { data: 'end' },
                    {
                        data: null,
                        render: function(data) {
                            // Botón "Editar"
                            const editBtn = `
                                <button 
                                    class="btn btn-sm btn-primary edit-shift"
                                    data-id="${data.id}"
                                    data-production-line-id="${data.production_line_id}"
                                    data-start="${data.start}"
                                    data-end="${data.end}"
                                >
                                    {{ __("Edit") }}
                                </button>
                            `;
                            // Form para "Borrar"
                            const deleteForm = `
                                <form 
                                    action="${deleteUrlTemplate.replace(':id', data.id)}" 
                                    method="POST" 
                                    style="display:inline;"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        class="btn btn-sm btn-danger" 
                                        onclick="return confirm('{{ __('Are you sure?') }}')"
                                    >
                                        {{ __("Delete") }}
                                    </button>
                                </form>
                            `;
                            return editBtn + ' ' + deleteForm;
                        }
                    }
                ],
                responsive: true,
                scrollX: true,
            });

            // Crear turno (submit del formulario "Crear")
            $('#createShiftForm').on('submit', function(e) {
                e.preventDefault();
                const formData = $(this).serialize();
                $.post(storeUrl, formData)
                    .done(response => {
                        if (response.success) {
                            Swal.fire('{{ __("Saved") }}', response.message, 'success');
                            table.ajax.reload(null, false); // Recarga sin resetear paginación
                            $('#createShiftModal').modal('hide');
                        } else {
                            Swal.fire('{{ __("Error") }}', response.message, 'error');
                        }
                    })
                    .fail(xhr => {
                        Swal.fire('{{ __("Error") }}', xhr.responseJSON?.message || 'Error', 'error');
                    });
            });

            // Abrir modal "Editar" con datos
            $('#shiftTable').on('click', '.edit-shift', function() {
                const id = $(this).data('id');
                const productionLineId = $(this).data('production-line-id');
                const start = $(this).data('start');
                const end = $(this).data('end');

                // Rellenar campos en modal
                $('#editShiftId').val(id);
                $('#editProductionLineId').val(productionLineId);
                // Si llega "HH:MM:SS", recortamos a "HH:MM"
                $('#editStartTime').val(start.slice(0,5));
                $('#editEndTime').val(end.slice(0,5));

                $('#editShiftModal').modal('show');
            });

            // Guardar cambios (submit del formulario "Editar")
            $('#editShiftForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#editShiftId').val();
                const url = updateUrlTemplate.replace(':id', id);
                const formData = $(this).serialize();

                $.ajax({
                    url: url,
                    method: 'PUT',
                    data: formData
                })
                .done(response => {
                    if (response.success) {
                        Swal.fire('{{ __("Updated") }}', response.message, 'success');
                        table.ajax.reload(null, false);
                        $('#editShiftModal').modal('hide');
                    } else {
                        Swal.fire('{{ __("Error") }}', response.message, 'error');
                    }
                })
                .fail(xhr => {
                    Swal.fire('{{ __("Error") }}', xhr.responseJSON?.message || 'Error', 'error');
                });
            });
        });
    </script>
@endpush
