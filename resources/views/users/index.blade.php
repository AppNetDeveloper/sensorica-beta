@extends('layouts.admin')

@section('title', 'Gestión de Usuarios')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Gestión de Usuarios') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3"><!-- Margen top de 1rem -->
        <div class="col-lg-12">
            {{-- Card principal sin borde y con sombra --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Gestión de Usuarios</h4>
                </div>
                <div class="card-body">
                    {{-- Botón (oculto) para subir Excel, si lo usas --}}
                    <input type="file" id="excelFileInput" accept=".xlsx" style="display: none;" />

                    {{-- Contenedor con scroll responsivo de Bootstrap --}}
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table id="usersTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>   {{-- Quita esta columna si tu tabla no tiene "phone" --}}
                                    <th>Contraseña</th>{{-- No la mostramos realmente, pero dejamos la col. --}}
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div> 
                </div>
            </div>
        </div>
    </div>
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

    {{-- SweetAlert2 para alertas --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- XLSX para leer Excel (opcional) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    {{-- Configuración general de AJAX para incluir CSRF Token (evitar error 419) --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
    </script>

    <script>
        // Ajusta estos endpoints según tus rutas
        // 1) GET /users/list-all/json  (retorna JSON con usuarios)
        const listAllUrl       = 'users/list-all/json';

        // 2) POST /users/store-or-update/ajax  (crear/actualizar usuario)
        const storeOrUpdateUrl = 'users/store-or-update/ajax';

        // 3) DELETE /users/delete/ajax/{id} (eliminar usuario)

        $(document).ready(function () {
            // Inicializamos DataTables
            const table = $('#usersTable').DataTable({
                ajax: {
                    url: listAllUrl,
                    dataSrc: '',
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar datos',
                            text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                        });
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    { data: 'email', defaultContent: '' },
                    { data: 'phone', defaultContent: '' }, // Quita si no usas phone
                    {
                        data: null,
                        defaultContent: '',
                        render: () => ''  // No mostrar password
                    },
                    {
                        data: null,
                        render: function (data) {
                            return `
                                <button class="edit-btn btn btn-sm btn-secondary"
                                        data-id="${data.id}"
                                        data-name="${data.name}"
                                        data-email="${data.email || ''}"
                                        data-phone="${data.phone || ''}">
                                    Editar
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
                        text: 'Añadir Usuario',
                        className: 'btn btn-success',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Usuario',
                                html: `
                                    <input id="userId" class="swal2-input" placeholder="ID Usuario" readonly>
                                    <input id="userName" class="swal2-input" placeholder="Nombre">
                                    <input id="userEmail" class="swal2-input" placeholder="Email">
                                    <input id="userPhone" class="swal2-input" placeholder="Teléfono (opcional)">
                                    <input id="userPassword" type="password" class="swal2-input" placeholder="Contraseña">
                                `,
                                confirmButtonText: 'Guardar',
                                showCancelButton: true,
                                preConfirm: () => {
                                    const id       = $('#userId').val() || null;
                                    const name     = $('#userName').val();
                                    const email    = $('#userEmail').val();
                                    const phone    = $('#userPhone').val();
                                    const password = $('#userPassword').val();

                                    if (!name || !email || !password) {
                                        Swal.showValidationMessage('Nombre, Email y Contraseña son obligatorios.');
                                        return false;
                                    }
                                    return {
                                        id: (id ? parseInt(id) : null),
                                        name,
                                        email,
                                        phone: phone || null,
                                        password: password || null,
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const payload = result.value;
                                    $.ajax({
                                        url: storeOrUpdateUrl,
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify(payload),
                                        success: function () {
                                            Swal.fire('Usuario guardado correctamente', '', 'success');
                                            table.ajax.reload();
                                        },
                                        error: function (xhr) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error al guardar',
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
                            columns: [0,1,2,3] // Quita el index 4 (Contraseña) y 5 (Acciones)
                        }
                    }
                ]
            });

            // Editar
            $('#usersTable tbody').on('click', '.edit-btn', function () {
                const currentId    = $(this).data('id');
                const currentName  = $(this).data('name');
                const currentEmail = $(this).data('email');
                const currentPhone = $(this).data('phone');

                Swal.fire({
                    title: 'Editar Usuario',
                    html: `
                        <input id="userId" class="swal2-input" value="${currentId}" readonly>
                        <input id="userName" class="swal2-input" value="${currentName}">
                        <input id="userEmail" class="swal2-input" placeholder="Email" value="${currentEmail}">
                        <input id="userPhone" class="swal2-input" placeholder="Teléfono" value="${currentPhone}">
                        <input id="userPassword" type="password" class="swal2-input" placeholder="Nueva Contraseña (opcional)">
                    `,
                    confirmButtonText: 'Actualizar',
                    showCancelButton: true,
                    preConfirm: () => {
                        const id       = $('#userId').val();
                        const name     = $('#userName').val();
                        const email    = $('#userEmail').val();
                        const phone    = $('#userPhone').val();
                        const password = $('#userPassword').val();

                        if (!id || !name || !email) {
                            Swal.showValidationMessage('ID, Nombre y Email son obligatorios.');
                            return false;
                        }
                        return { 
                            id: parseInt(id),
                            name,
                            email,
                            phone: phone || null,
                            password: password || null
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const payload = result.value;
                        $.ajax({
                            url: storeOrUpdateUrl,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(payload),
                            success: function () {
                                Swal.fire('Usuario actualizado', '', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al actualizar',
                                    text: `Status: ${xhr.status}. ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Eliminar
            $('#usersTable tbody').on('click', '.delete-btn', function () {
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
                            url: '/users/delete/ajax/' + id,  // Ajusta a tu ruta real
                            method: 'DELETE',
                            success: function () {
                                Swal.fire('Usuario eliminado', '', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al eliminar',
                                    text: `Status: ${xhr.status}. ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Si necesitas importar Excel, reset pass, etc.,
            // copia la lógica de tu snippet anterior y ajusta los endpoints
            // (storeOrUpdateUrl, listAllUrl, etc.)
        });
    </script>
@endpush
