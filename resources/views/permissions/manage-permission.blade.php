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

        #permissionsTable {
            margin: 0;
            border: none;
        }

        #permissionsTable thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        #permissionsTable thead th {
            border: none;
            padding: 1.2rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        #permissionsTable tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
            position: relative;
        }

        #permissionsTable tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        #permissionsTable tbody tr::after {
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

        #permissionsTable tbody tr:hover::after {
            transform: scaleX(1);
        }

        #permissionsTable tbody td {
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
