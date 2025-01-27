@extends('layouts.admin')

@section('title', 'Gestión de Permisos')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Gestión de Permisos') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3"><!-- Margen superior de 1rem, igual a "Trabajadores" -->
        <div class="col-lg-12">
            {{-- Card principal sin borde y con sombra --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Gestión de Permisos</h4>
                </div>
                <div class="card-body">
                    {{-- Input (oculto) para Excel, si lo usas --}}
                    <input type="file" id="excelFileInput" accept=".xlsx" style="display: none;" />

                    {{-- Contenedor responsivo con la misma estética --}}
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table id="permissionsTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre del Permiso</th>
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

    {{-- XLSX (opcional) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    {{-- CSRF para peticiones POST/DELETE (evitar error 419) --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
    </script>

    <script>
        // Ajusta estas rutas a tu configuración en web.php:
        // Ejemplo:
        // Route::get('manage-permission/list-all', [PermissionManageController::class, 'listAll'])->name('manage-permission.listAll');
        // Route::post('manage-permission/store-or-update', [PermissionManageController::class, 'storeOrUpdate'])->name('manage-permission.storeOrUpdate');
        // Route::delete('manage-permission/delete/{id}', [PermissionManageController::class, 'delete'])->name('manage-permission.delete');

        // 1) URL para listar permisos
        const listAllUrl = 'manage-permission/list-all';
        // 2) URL para crear/actualizar
        const storeOrUpdateUrl = 'manage-permission/store-or-update';
        // 3) Para eliminar, se construye con /manage-permission/delete/{id}

        $(document).ready(function () {
            const table = $('#permissionsTable').DataTable({
                ajax: {
                    url: listAllUrl,
                    dataSrc: '',
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al cargar Permisos',
                            text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                        });
                    }
                },
                columns: [
                    { data: 'id' },
                    { data: 'name' },
                    {
                        data: null,
                        render: function (data) {
                            return `
                                <button class="edit-btn btn btn-sm btn-secondary"
                                    data-id="${data.id}" 
                                    data-name="${data.name}">
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
                        text: 'Añadir Permiso',
                        className: 'btn btn-success',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Permiso',
                                html: `
                                    <input id="permId" class="swal2-input" placeholder="ID (opcional)">
                                    <input id="permName" class="swal2-input" placeholder="Nombre del Permiso">
                                `,
                                confirmButtonText: 'Guardar',
                                showCancelButton: true,
                                preConfirm: () => {
                                    const id   = $('#permId').val() || null;
                                    const name = $('#permName').val();
                                    if (!name) {
                                        Swal.showValidationMessage('El nombre del permiso es obligatorio.');
                                        return false;
                                    }
                                    return { id, name };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: storeOrUpdateUrl,
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify(result.value),
                                        success: function () {
                                            Swal.fire('Éxito', 'Permiso guardado correctamente', 'success');
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
                            columns: [0,1] // ID y nombre
                        }
                    }
                ]
            });

            // Editar
            $('#permissionsTable tbody').on('click', '.edit-btn', function() {
                const currentId   = $(this).data('id');
                const currentName = $(this).data('name');

                Swal.fire({
                    title: 'Editar Permiso',
                    html: `
                        <input id="permId" class="swal2-input" value="${currentId}" readonly>
                        <input id="permName" class="swal2-input" value="${currentName}">
                    `,
                    confirmButtonText: 'Actualizar',
                    showCancelButton: true,
                    preConfirm: () => {
                        const id   = $('#permId').val();
                        const name = $('#permName').val();
                        if (!id || !name) {
                            Swal.showValidationMessage('ID y nombre son obligatorios.');
                            return false;
                        }
                        return { id, name };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: storeOrUpdateUrl,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(result.value),
                            success: function () {
                                Swal.fire('Éxito', 'Permiso actualizado', 'success');
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

            // Eliminar
            $('#permissionsTable tbody').on('click', '.delete-btn', function() {
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
                            // DELETE manage-permission/delete/{id}
                            url: "{{ url('manage-permission/delete') }}/" + id,
                            method: 'DELETE',
                            success: function() {
                                Swal.fire('Permiso eliminado', '', 'success');
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
