@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Relaciones Operador - Puesto')

{{-- Migas de pan (opcional) --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Worker Post') }}</li>
    </ul>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <table id="workerPostTable" class="table table-bordered table-striped" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Trabajador</th>
                        <th>RFID</th>
                        <th>Sensor</th>
                        <th>Bascula</th>
                        <th>Contador</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
@endsection

@push('style')
    <!-- Asegúrate de tener el token de CSRF en una metaetiqueta -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- CSS de DataTables + Botones --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
@endpush

@push('scripts')
    {{-- JS de jQuery, DataTables y extensiones --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Configurar Ajax para que incluya el token CSRF en cada petición
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    
        // Tomará https://imperio.boisolo.dev si está en .env (sin la barra final, si existiera)
        const baseUrl = "{{ rtrim(config('app.url'), '/') }}"; 
    
        // Ejemplo: https://imperio.boisolo.dev/worker-post/api
        const apiIndexUrl = `${baseUrl}/worker-post/api`;
    
        // Ejemplo: https://imperio.boisolo.dev/worker-post
        const storeUrl = `${baseUrl}/worker-post`;
    
        // Para update/delete con placeholders:
        let updateUrlTemplate = `${baseUrl}/worker-post/:id`;
        let deleteUrlTemplate = `${baseUrl}/worker-post/:id`;
    
        // Datos para llenar <select> en formularios
        const operators = @json($operators);
        const rfids    = @json($rfids);
        const sensors  = @json($sensors);
        const modbuses = @json($modbuses);
    
        /**
         * Deshabilita selects dependiendo de cuál se selecciona.
         * Si se selecciona 'rfid', deshabilita sensor y modbus, etc.
         */
        function validateSelection(selectedType) {
            if (selectedType === 'rfid') {
                $('#sensorId').prop('disabled', true);
                $('#modbusId').prop('disabled', true);
            } else if (selectedType === 'sensor') {
                $('#rfidId').prop('disabled', true);
                $('#modbusId').prop('disabled', true);
            } else if (selectedType === 'modbus') {
                $('#rfidId').prop('disabled', true);
                $('#sensorId').prop('disabled', true);
            }
        }
    
        function resetSelection() {
            $('#sensorId').prop('disabled', false);
            $('#rfidId').prop('disabled', false);
            $('#modbusId').prop('disabled', false);
        }
    
        $(document).ready(function () {
            // Inicializar DataTable
            const table = $('#workerPostTable').DataTable({
                // 'Bfrtip' permite mostrar los botones (B) encima de la tabla
                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Añadir Relación',
                        className: 'btn btn-primary',
                        action: function (e, dt, node, config) {
                            // Lógica para añadir relación
                            Swal.fire({
                                title: 'Añadir Relación',
                                width: '800px', // Aumenta el ancho
                                padding: '2em', // Aumenta el padding
                                html: ` 
                                    <select id="operatorId" class="swal2-input" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 7px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                        <option value="">Seleccione Trabajador</option>
                                        ${operators.map(op => `<option value="${op.id}">${op.name}</option>`).join('')}
                                    </select>
                                    <select id="rfidId" class="swal2-input" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 7px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                        <option value="">Seleccione Tarjeta Rfid</option>
                                        ${rfids.map(rfid => `<option value="${rfid.id}">${rfid.epc}</option>`).join('')}
                                    </select>
                                    <select id="sensorId" class="swal2-input" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 7px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                        <option value="">Seleciona Sensor Conteo</option>
                                        ${sensors.map(sensor => `<option value="${sensor.id}">${sensor.name}</option>`).join('')}
                                    </select>
                                    <select id="modbusId" class="swal2-input" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 7px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                        <option value="">Seleccione una Bascula</option>
                                        ${modbuses.map(modbus => `<option value="${modbus.id}">${modbus.name}</option>`).join('')}
                                    </select>
                                `,
                                confirmButtonText: 'Guardar',
                                preConfirm: () => {
                                    const data = {
                                        operator_id: $('#operatorId').val(),
                                        rfid_reading_id: $('#rfidId').val(),
                                        sensor_id: $('#sensorId').val(),
                                        modbus_id: $('#modbusId').val(),
                                    };
                                    // Validación mínima
                                    if (!data.operator_id && (!data.rfid_reading_id && !data.sensor_id && !data.modbus_id)) {
                                        Swal.showValidationMessage('Debe seleccionar un operador y al menos una ubicación.');
                                        return false;
                                    }
                                    // Hacer la petición POST
                                    return $.post(storeUrl, data)
                                        .done(response => {
                                            if (response.success) {
                                                Swal.fire('Guardado', response.message, 'success');
                                                table.ajax.reload(); // Recargar la tabla
                                            } else {
                                                Swal.fire('Error', response.message, 'error');
                                            }
                                        })
                                        .fail(xhr => {
                                            Swal.showValidationMessage(xhr.responseJSON?.message || 'Error');
                                        });
                                }
                            });
    
                            // Manejo de selects en tiempo real
                            $('#rfidId, #sensorId, #modbusId').change(function () {
                                resetSelection();
                                validateSelection($(this).attr('id').replace('Id', ''));
                            });
                        }
                    },
                    {
                        extend: 'excel',
                        text: 'Exportar a Excel',
                        className: 'btn btn-success'
                    }
                ],
                order: [[0, 'desc']],
                ajax: {
                    url: apiIndexUrl,
                    dataSrc: 'data', // Asegúrate de que coincida con la clave de la respuesta JSON
                    error: function (xhr) {
                        Swal.fire('Error', 'Error al cargar datos.', 'error');
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'operator.name', defaultContent: 'Sin asignar' },
                    { data: 'rfid_reading.epc', defaultContent: 'Sin asignar' },
                    { data: 'sensor.name', defaultContent: 'Sin asignar' },
                    { data: 'modbus.name', defaultContent: 'Sin asignar' },
                    { data: 'count', defaultContent: '0' },
                    { data: 'created_at' },
                    { data: 'updated_at', defaultContent: 'En curso' },
                    {
                        data: null,
                        render: function (data) {
                            // Aquí mezclas Blade y JS
                                return `@role('admin')
                                            <button data-id="${'${data.id}'}" class="btn-sm btn btn-warning">Editar</button>
                                            <button data-id="${'${data.id}'}" class="btn-sm btn btn-danger">Eliminar</button>
                                        @else
                                            <!-- Nada -->
                                        @endrole`;
                        }
                    }
                ],
                responsive: true,
                scrollX: true,
            });
    
            // Evento: Editar
            $('#workerPostTable').on('click', '.edit-btn', function () {
                const id = $(this).data('id');
                const rowData = table.row($(this).closest('tr')).data();
    
                // Construir la URL para update
                const updateUrl = updateUrlTemplate.replace(':id', id);
    
                Swal.fire({
                    title: 'Editar Relación',
                    html: `
                        <input type="hidden" id="relationId" value="${id}">
                        <label for="operatorId">Operador:</label>
                        <select id="operatorId" class="swal2-input">
                            <option value="">Seleccione un operador</option>
                            ${operators.map(op => `
                                <option value="${op.id}" ${(rowData.operator && op.id == rowData.operator.id) ? 'selected' : ''}>
                                    ${op.name}
                                </option>
                            `).join('')}
                        </select>
                        <label for="rfidId">RFID:</label>
                        <select id="rfidId" class="swal2-input">
                            <option value="">Seleccione un RFID</option>
                            ${rfids.map(rfid => `
                                <option value="${rfid.id}" ${(rowData.rfid_reading && rfid.id == rowData.rfid_reading.id) ? 'selected' : ''}>
                                    ${rfid.epc}
                                </option>
                            `).join('')}
                        </select>
                        <label for="sensorId">Sensor:</label>
                        <select id="sensorId" class="swal2-input">
                            <option value="">Seleccione un Sensor</option>
                            ${sensors.map(sensor => `
                                <option value="${sensor.id}" ${(rowData.sensor && sensor.id == rowData.sensor.id) ? 'selected' : ''}>
                                    ${sensor.name}
                                </option>
                            `).join('')}
                        </select>
                        <label for="modbusId">Modbus:</label>
                        <select id="modbusId" class="swal2-input">
                            <option value="">Seleccione un Modbus</option>
                            ${modbuses.map(modbus => `
                                <option value="${modbus.id}" ${(rowData.modbus && modbus.id == rowData.modbus.id) ? 'selected' : ''}>
                                    ${modbus.name}
                                </option>
                            `).join('')}
                        </select>
                    `,
                    confirmButtonText: 'Actualizar',
                    preConfirm: () => {
                        const data = {
                            operator_id: $('#operatorId').val(),
                            rfid_reading_id: $('#rfidId').val(),
                            sensor_id: $('#sensorId').val(),
                            modbus_id: $('#modbusId').val(),
                        };
                        // PUT a la URL con ID
                        return $.ajax({
                            url: updateUrl,
                            method: 'PUT',
                            data
                        }).done(response => {
                            if (response.success) {
                                Swal.fire('Actualizado', response.message, 'success');
                                table.ajax.reload(); // Recargar la tabla
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }).fail(xhr => {
                            Swal.showValidationMessage(xhr.responseJSON?.message || 'Error');
                        });
                    }
                });
    
                // Habilitar/Deshabilitar selects en tiempo real
                $('#rfidId, #sensorId, #modbusId').change(function () {
                    resetSelection();
                    validateSelection($(this).attr('id').replace('Id', ''));
                });
            });
    
            // Evento: Eliminar
            $('#workerPostTable').on('click', '.delete-btn', function () {
                const id = $(this).data('id');
                const deleteUrl = deleteUrlTemplate.replace(':id', id);
    
                Swal.fire({
                    title: 'Eliminar Relación',
                    text: '¿Estás seguro? Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: deleteUrl,
                            method: 'DELETE'
                        }).done(response => {
                            if (response.success) {
                                Swal.fire('Eliminado', response.message, 'success');
                                table.ajax.reload(); // Recargar la tabla
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }).fail(() => {
                            Swal.fire('Error', 'No se pudo eliminar.', 'error');
                        });
                    }
                });
            });
        });
    </script>
@endpush
