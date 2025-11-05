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
    {{-- Font Awesome para iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

    <style>
        /* Estilos modernos para la página */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Card principal con glassmorfismo */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: none;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, -20px) rotate(180deg); }
        }

        .card-title {
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Breadcrumb moderno */
        .breadcrumb {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .breadcrumb-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .breadcrumb-item a:hover {
            color: #764ba2;
            transform: translateX(3px);
        }

        .breadcrumb-item.active {
            color: #6c757d;
            font-weight: 600;
        }

        /* Tabla moderna */
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            background: white;
            margin: 2rem auto;
        }

        #rolesTable {
            margin: 0;
            border: none;
        }

        #rolesTable thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        #rolesTable thead th {
            border: none;
            padding: 1.2rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        #rolesTable tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
            position: relative;
        }

        #rolesTable tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        #rolesTable tbody tr::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        #rolesTable tbody tr:hover::after {
            transform: scaleX(1);
        }

        #rolesTable tbody td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        /* Botones de acción mejorados */
        .action-btn {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0.2rem;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .action-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .edit-btn {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .edit-btn:hover {
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }

        .permissions-btn {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .permissions-btn:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.4);
        }

        .delete-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .delete-btn:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        /* Botones de DataTables */
        .dt-buttons {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 0.5rem;
        }

        .dt-button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .dt-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .dt-button span {
            margin-right: 0.5rem;
        }

        button {
            margin-right: 5px;
        }

        /* SweetAlert2 personalizado */
        .swal2-input {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
        }

        .swal2-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .swal2-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }

            .action-btn {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }

            .dt-button {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
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
                                <button class="action-btn edit-btn"
                                    data-id="${data.id}"
                                    data-name="${data.name}">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="action-btn permissions-btn"
                                    data-id="${data.id}"
                                    data-name="${data.name}">
                                    <i class="fas fa-key"></i> Permisos
                                </button>
                                <button class="action-btn delete-btn"
                                    data-id="${data.id}">
                                    <i class="fas fa-trash"></i> Eliminar
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
                        text: '<i class="fas fa-plus"></i> Añadir Rol',
                        className: 'dt-button',
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
                        text: '<i class="fas fa-file-excel"></i> Exportar a Excel',
                        className: 'dt-button',
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
