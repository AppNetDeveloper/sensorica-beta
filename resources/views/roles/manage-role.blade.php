@extends('layouts.admin')

@section('title', 'Gestión de Roles')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Gestión de Roles') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3"><!-- margen top de 1rem -->
        <div class="col-lg-12">
            {{-- Card principal sin borde y con sombra --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Gestión de Roles</h4>
                </div>
                <div class="card-body">
                    {{-- Input (oculto) para subir Excel, si llegas a usarlo --}}
                    <input type="file" id="excelFileInput" accept=".xlsx" style="display: none;" />

                    {{-- Tabla responsiva con el mismo estilo que “Trabajadores” --}}
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table id="rolesTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Rol</th>
                                    <th>Permisos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div> {{-- .table-responsive --}}
                </div> {{-- .card-body --}}
            </div> {{-- .card --}}
        </div> {{-- .col --}}
    </div> {{-- .row --}}
@endsection

@push('style')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

    <style>
        .dt-buttons {
            margin-bottom: 1rem;
        }
        button {
            margin-right: 5px;
        }
    </style>
@endpush

@push('scripts')
    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    {{-- DataTables núcleo --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    {{-- Extensiones DataTables: Buttons, JSZip, etc. --}}
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    {{-- Extensión Responsive de DataTables --}}
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- XLSX para leer Excel (opcional) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    {{-- Protección CSRF (evitar error 419 en peticiones POST/DELETE) --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
    </script>

    <script>
        // URLs definidas para las operaciones CRUD
        const listAllUrl = 'manage-role/list-all'; 
        const storeOrUpdateUrl = 'manage-role/store-or-update';

        $(document).ready(function () {
            const table = $('#rolesTable').DataTable({
                ajax: {
                    url: listAllUrl, // GET manage-role/list-all
                    dataSrc: '',
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar Roles',
                            text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                        });
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { 
                        data: 'permissions', 
                        defaultContent: '', 
                        render: function (data) {
                            if (Array.isArray(data)) {
                                // Extraer el nombre de cada permiso
                                return data.map(function(permission) {
                                    return permission.name || '';
                                }).join(', ');
                            }
                            return '';
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            return `
                                <button class="edit-btn btn btn-sm btn-secondary" 
                                    data-id="${data.id}" 
                                    data-name="${data.name}">
                                    Editar
                                </button>
                                <button class="permissions-btn btn btn-sm btn-info" 
                                    data-id="${data.id}" 
                                    data-name="${data.name}">
                                    Permisos
                                </button>
                                <button class="delete-btn btn btn-sm btn-danger" 
                                    data-id="${data.id}">
                                    Eliminar
                                </button>
                            `;
                        }
                    }
                ],
                responsive: true,
                scrollX: true,

                dom: 'Bfrtip',
                buttons: [
                    {
                        text: 'Añadir Rol',
                        className: 'btn btn-success',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Rol',
                                html: `
                                    <input id="roleId" class="swal2-input" placeholder="ID (opcional)">
                                    <input id="roleName" class="swal2-input" placeholder="Nombre del Rol">
                                `,
                                confirmButtonText: 'Guardar',
                                showCancelButton: true,
                                preConfirm: () => {
                                    const id   = $('#roleId').val() || null;
                                    const name = $('#roleName').val();
                                    if (!name) {
                                        Swal.showValidationMessage('El nombre del rol es obligatorio.');
                                        return false;
                                    }
                                    return { id, name };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: storeOrUpdateUrl, // POST manage-role/store-or-update
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify(result.value),
                                        success: function () {
                                            Swal.fire('Éxito', 'Rol guardado correctamente', 'success');
                                            table.ajax.reload();
                                        },
                                        error: function (xhr) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error al Guardar',
                                                text: `Status: ${xhr.status}. ${xhr.responseJSON?.error || 'Error desconocido'}`
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Exportar a Excel',
                        className: 'btn btn-success',
                        title: null,
                        exportOptions: {
                            columns: [0,1] // Exporta ID y Nombre
                        }
                    }
                ]
            });

            // Editar rol
            $('#rolesTable tbody').on('click', '.edit-btn', function() {
                const currentId   = $(this).data('id');
                const currentName = $(this).data('name');

                Swal.fire({
                    title: 'Editar Rol',
                    html: `
                        <input id="roleId" class="swal2-input" value="${currentId}" readonly>
                        <input id="roleName" class="swal2-input" value="${currentName}">
                    `,
                    confirmButtonText: 'Actualizar',
                    showCancelButton: true,
                    preConfirm: () => {
                        const id   = $('#roleId').val();
                        const name = $('#roleName').val();
                        if (!id || !name) {
                            Swal.showValidationMessage('ID y nombre son obligatorios.');
                            return false;
                        }
                        return { id, name };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: storeOrUpdateUrl, // POST manage-role/store-or-update
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(result.value),
                            success: function () {
                                Swal.fire('Éxito', 'Rol actualizado', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Actualizar',
                                    text: `Status: ${xhr.status}. ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });
            // Gestionar permisos del rol
            $('#rolesTable tbody').on('click', '.permissions-btn', function() {
                const roleId = $(this).data('id');
                const roleName = $(this).data('name');

                // Solicitar todos los permisos y los roles con permisos
                $.when(
                    $.ajax({
                        url: 'manage-permission/list-all',
                        method: 'GET'
                    }),
                    $.ajax({
                        url: listAllUrl,
                        method: 'GET'
                    })
                ).done(function(allPermissionsData, rolesData) {
                    const allPermissions = allPermissionsData[0];
                    const roles = rolesData[0];
                    const currentRole = roles.find(r => r.id === roleId);
                    const currentPermissions = currentRole && currentRole.permissions ? currentRole.permissions : [];

                    // Transformar a array de nombres
                    const currentPermissionsNames = Array.isArray(currentPermissions)
                        ? currentPermissions.map(p => p.name ? p.name : p)
                        : [];

                    let permissionsHtml = '';
                    allPermissions.forEach(permission => {
                        const checked = currentPermissionsNames.includes(permission.name) ? 'checked' : '';
                        permissionsHtml += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="${permission.name}" id="perm_${permission.id}" ${checked}>
                                <label class="form-check-label" for="perm_${permission.id}">
                                    ${permission.name}
                                </label>
                            </div>
                        `;
                    });

                    Swal.fire({
                        title: `Administrar Permisos para: ${roleName}`,
                        html: `
                            <div style="max-height:400px; overflow-y:auto;">
                                <form id="permissionsForm">
                                    ${permissionsHtml}
                                </form>
                            </div>
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Guardar',
                        preConfirm: () => {
                            const selectedPermissions = [];
                            $('#permissionsForm input:checked').each(function() {
                                selectedPermissions.push($(this).val());
                            });
                            return selectedPermissions;
                        }
                    }).then(result => {
                        if(result.isConfirmed) {
                            $.ajax({
                                url: `manage-role/update-permissions/${roleId}`, 
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({ permissions: result.value }),
                                success: function() {
                                    Swal.fire('Éxito', 'Permisos actualizados correctamente', 'success');
                                    table.ajax.reload();
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error al actualizar permisos',
                                        text: `Status: ${xhr.status}. ${xhr.responseJSON?.error || 'Error desconocido'}`
                                    });
                                }
                            });
                        }
                    });

                }).fail(function() {
                    Swal.fire('Error', 'No se pudieron cargar los permisos.', 'error');
                });
            });


            // Eliminar rol
            $('#rolesTable tbody').on('click', '.delete-btn', function() {
                const id = $(this).data('id');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: 'Esta acción no se puede deshacer.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "manage-role/delete/" + id,
                            method: 'DELETE',
                            success: function() {
                                Swal.fire('Rol eliminado', '', 'success');
                                table.ajax.reload();
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Eliminar',
                                    text: `Status: ${xhr.status}. ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
