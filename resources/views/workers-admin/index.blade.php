@extends('layouts.admin')

{{-- Título de la página --}}
@section('title', 'Gestión de Trabajadores')

{{-- Migas de pan (breadcrumb) si las usas en tu layout --}}
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Gestión de Trabajadores') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3"><!-- Agregamos margen top de 1rem -->
        <div class="col-lg-12">
            {{-- Card principal sin borde y con sombra --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Gestión de Trabajadores</h4>
                </div>
                <div class="card-body">
                    {{-- Botón (oculto) para subir Excel --}}
                    <input type="file" id="excelFileInput" accept=".xlsx" style="display: none;" />

                    {{-- Contenedor con scroll responsivo de Bootstrap, 
                         más la clase personalizada "my-narrow-table" para reducir ancho --}}
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table id="workersTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Codigo Trabajador</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Contraseña</th>
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
    {{-- Extensión Responsive de DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css">

    <style>
        /* Espacio entre botones DataTables y la tabla */
        .dt-buttons {
            margin-bottom: 1rem;
        }

        /* Alineación de botones de acciones en la tabla */
        button {
            margin-right: 5px;
        }

        /* Clase para que la tabla se muestre más angosta y centrada */
        .my-narrow-table {
            max-width: 90%; /* Ajusta este valor según necesites (80%, 900px, etc.) */
            margin: 0 auto; /* Centra horizontalmente el contenido */
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

    {{-- XLSX para leer Excel --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

    <script>
        // Ajusta esta variable a tu ruta de API (p.ej. '/api/workers')
        const workersApiUrl = '/api/workers';

        $(document).ready(function () {
            const table = $('#workersTable').DataTable({
                ajax: {
                    url: `${workersApiUrl}/list-all`,
                    dataSrc: 'operators', // 🚀 Cambia aquí para usar solo "operators"
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
                    { data: 'phone', defaultContent: '' },
                    {
                        data: null,
                        defaultContent: '',
                        render: () => ''  // No mostramos contraseña
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
                                <button class="reset-email-btn btn btn-sm btn-warning"
                                        data-email="${data.email || ''}">
                                    Reset Pass Email
                                </button>
                                <button class="reset-whatsapp-btn btn btn-sm btn-success"
                                        data-phone="${data.phone || ''}">
                                    Reset Pass WhatsApp
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
                        text: '<i class="fas fa-user-plus"></i> Añadir Trabajador',
                        className: 'btn btn-success btn-lg',
                        style: 'font-size: 1.2rem; padding: 0.75rem 1.5rem;',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Trabajador',
                                html: `
                                    <input id="workerId" class="swal2-input" placeholder="Codigo Trabajador (Obligatorio)" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="workerName" class="swal2-input" placeholder="Nombre del Trabajador" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="workerEmail" class="swal2-input" placeholder="Email (Opcional)" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="workerPhone" class="swal2-input" placeholder="Teléfono (Opcional)" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="workerPassword" type="password" class="swal2-input" placeholder="Contraseña (Opcional)" style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                `,
                                width: '800px', // Aumenta el ancho
                                padding: '2em', // Aumenta el padding
                                confirmButtonText: 'Añadir',
                                showCancelButton: true,
                                preConfirm: () => {
                                    const id       = $('#workerId').val();
                                    const name     = $('#workerName').val();
                                    const email    = $('#workerEmail').val();
                                    const phone    = $('#workerPhone').val();
                                    const password = $('#workerPassword').val();

                                    if (!id || !name) {
                                        Swal.showValidationMessage('ID y Nombre son obligatorios.');
                                        return false;
                                    }
                                    return {
                                        id: parseInt(id),
                                        name,
                                        email: email || null,
                                        phone: phone || null,
                                        password: password || null
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const { id, name, email, phone, password } = result.value;
                                    $.ajax({
                                        url: `${workersApiUrl}/update-or-insert`,
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify({ id, name, email, phone, password }),
                                        success: function () {
                                            Swal.fire('Trabajador añadido o actualizado', '', 'success');
                                            table.ajax.reload();
                                        },
                                        error: function (xhr) {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error al Añadir',
                                                text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    },
                    {
                        text: 'Importar Excel',
                        className: 'btn btn-success',
                        action: function () {
                            $('#excelFileInput').click();
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Exportar a Excel',
                        className: 'btn btn-success',
                        title: null,
                        exportOptions: {
                            columns: [0, 1, 2, 3, 4],
                            format: {
                                body: function (data, row, column) {
                                    // En la columna 4 la contraseña está vacía
                                    if (column === 4) return '';
                                    return data;
                                }
                            }
                        }
                    },
                    {
                        text: 'Monitor',
                        className: 'btn btn-primary', // Estilo del botón
                        action: function () {
                            const url = '/workers/workers-live.html?shift=true&order=true&name=true&id=true&nameSize=1.2rem&numberSize=2rem&idSize=1rem&labelSize=1rem';
                            window.open(url, '_blank'); // Abre el enlace en una nueva pestaña
                        }
                    },
                    {
                        text: 'Imprimir Listado',
                        className: 'btn btn-primary', // Estilo del botón
                        action: function () {
                            const url = '/workers/export.html';
                            window.open(url, '_blank'); // Abre el enlace en una nueva pestaña
                        }
                    }
                ]
            });

            // ---- Eventos CRUD / Reset Pass / Excel ----
            
            // Editar trabajador
            $('#workersTable tbody').on('click', '.edit-btn', function () {
                const currentId    = $(this).data('id');
                const currentName  = $(this).data('name');
                const currentEmail = $(this).data('email');
                const currentPhone = $(this).data('phone');

                Swal.fire({
                    title: 'Editar Trabajador',
                    html: `
                        <input id="workerId" class="swal2-input" value="${currentId}" readonly>
                        <input id="workerName" class="swal2-input" value="${currentName}">
                        <input id="workerEmail" class="swal2-input" placeholder="Email (Opcional)" value="${currentEmail}">
                        <input id="workerPhone" class="swal2-input" placeholder="Teléfono (Opcional)" value="${currentPhone}">
                        <input id="workerPassword" type="password" class="swal2-input" placeholder="Nueva Contraseña (opcional)">
                        <small>Deje la contraseña en blanco para no cambiarla</small>
                    `,
                    confirmButtonText: 'Actualizar',
                    showCancelButton: true,
                    preConfirm: () => {
                        const id       = $('#workerId').val();
                        const name     = $('#workerName').val();
                        const email    = $('#workerEmail').val() || null;
                        const phone    = $('#workerPhone').val() || null;
                        const password = $('#workerPassword').val();

                        if (!id || !name) {
                            Swal.showValidationMessage('ID y Nombre son obligatorios.');
                            return false;
                        }
                        return {
                            id: parseInt(id),
                            name,
                            email,
                            phone,
                            password: password || null
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { id, name, email, phone, password } = result.value;
                        const payload = { id, name, email, phone };
                        if (password) payload.password = password;

                        $.ajax({
                            url: `${workersApiUrl}/update-or-insert`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(payload),
                            success: function () {
                                Swal.fire('Trabajador actualizado', '', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Actualizar',
                                    text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Eliminar trabajador
            $('#workersTable tbody').on('click', '.delete-btn', function () {
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
                            url: `${workersApiUrl}/${id}`,
                            method: 'DELETE',
                            success: function () {
                                Swal.fire('Trabajador eliminado', '', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Eliminar',
                                    text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Resetear contraseña por email
            $('#workersTable tbody').on('click', '.reset-email-btn', function () {
                const email = $(this).data('email');
                if (!email) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin email',
                        text: 'Este trabajador no tiene email asignado. Por favor, asígnalo primero.'
                    });
                    return;
                }
                Swal.fire({
                    title: 'Resetear Contraseña por Email',
                    text: `Se enviará una nueva contraseña al email: ${email}`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${workersApiUrl}/reset-password-email`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ email }),
                            success: function () {
                                Swal.fire('Contraseña reseteada y enviada por email', '', 'success');
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Resetear por Email',
                                    text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Resetear contraseña por WhatsApp
            $('#workersTable tbody').on('click', '.reset-whatsapp-btn', function () {
                const phone = $(this).data('phone');
                if (!phone) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sin teléfono',
                        text: 'Este trabajador no tiene teléfono asignado. Por favor, asígnalo primero.'
                    });
                    return;
                }
                Swal.fire({
                    title: 'Resetear Contraseña por WhatsApp',
                    text: `Se generará una nueva contraseña para el teléfono: ${phone}`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${workersApiUrl}/reset-password-whatsapp`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({ phone }),
                            success: function () {
                                Swal.fire('Contraseña reseteada, se enviará por WhatsApp', '', 'success');
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error al Resetear por WhatsApp',
                                    text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Importar Excel
            $('#excelFileInput').change(function (e) {
                const file = e.target.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = function (e) {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const sheetName = workbook.SheetNames[0];
                    const sheet = workbook.Sheets[sheetName];
                    let rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

                    if (!rows || rows.length === 0) {
                        Swal.fire('Error', 'El archivo está vacío o tiene un formato incorrecto.', 'error');
                        $('#excelFileInput').val('');
                        return;
                    }

                    console.log('Encabezados reales detectados:', rows[0]);
                    const expectedHeaders = ['ID Cliente', 'Nombre', 'Email', 'Teléfono', 'Contraseña'];
                    const actualHeaders = rows[0].map(h => String(h).trim());
                    const missingHeaders = expectedHeaders.filter(h => !actualHeaders.includes(h));
                    if (missingHeaders.length > 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: `El archivo no tiene los encabezados esperados. Faltan: ${missingHeaders.join(', ')}`,
                            footer: 'Asegúrate de que los encabezados estén correctos en el archivo.'
                        });
                        console.warn('Encabezados reales:', actualHeaders);
                        $('#excelFileInput').val('');
                        return;
                    }

                    // Ignoramos la primera fila de encabezados
                    rows = rows.slice(1);

                    const formattedRows = rows.map(row => ({
                        id: parseInt(row[0]) || null,
                        name: row[1] ? String(row[1]).trim() : null,
                        email: row[2] ? String(row[2]).trim() : null,
                        phone: row[3] ? String(row[3]).trim() : null,
                        password: row[4] ? String(row[4]).trim() : null
                    }));

                    const invalidRows = formattedRows.filter(r => !r.id || !r.name);
                    if (invalidRows.length > 0) {
                        console.warn('Filas inválidas:', invalidRows);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error en el archivo',
                            text: `Se ignoraron ${invalidRows.length} filas con datos incompletos.`,
                            footer: 'ID Cliente y Nombre son obligatorios.'
                        });
                    }

                    const validRows = formattedRows.filter(r => r.id && r.name);
                    if (validRows.length === 0) {
                        Swal.fire('Error', 'No se encontraron filas válidas para procesar.', 'error');
                        $('#excelFileInput').val('');
                        return;
                    }

                    Swal.fire({
                        title: 'Procesando archivo',
                        text: 'Esto puede tardar unos segundos.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const promises = validRows.map(row => {
                        return $.ajax({
                            url: `${workersApiUrl}/update-or-insert`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(row)
                        }).fail(xhr => {
                            console.error('Error en la fila:', row, 'Respuesta del servidor:', xhr.responseText);
                        });
                    });

                    Promise.all(promises)
                        .then(() => {
                            Swal.fire('Éxito', 'El archivo fue procesado correctamente.', 'success');
                            table.ajax.reload();
                            $('#excelFileInput').val('');
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Ocurrió un error al procesar el archivo.', 'error');
                            $('#excelFileInput').val('');
                        });
                };
                reader.readAsArrayBuffer(file);
            });
        });
    </script>
@endpush
