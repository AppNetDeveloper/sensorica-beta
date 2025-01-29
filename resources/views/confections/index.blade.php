@extends('layouts.admin')

@section('title', 'Gestión de Confecciones')

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('home') }}">{{ __('Dashboard') }}</a>
        </li>
        <li class="breadcrumb-item">{{ __('Gestión de Confecciones') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row mt-3"><!-- Margen top de 1rem, igual que en “Trabajadores” -->
        <div class="col-lg-12">
            {{-- Card principal sin borde y con sombra (mismo estilo) --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    
                </div>
                <div class="card-body">
                    {{-- Botón (oculto) para subir Excel --}}
                    <input type="file" id="excelFileInput" accept=".xlsx" style="display: none;" />

                    {{-- Contenedor con scroll responsivo de Bootstrap,
                         mismo estilo "max-width: 98%; margin: 0 auto;" --}}
                    <div class="table-responsive" style="max-width: 98%; margin: 0 auto;">
                        <table id="productsTable" class="display table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Codigo Confection</th>
                                    <th>Nombre</th>
                                    <th>Tiempo Óptimo</th>
                                    <th>Kg por Caja</th>
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

    {{-- CSRF para AJAX (evitar error 419) --}}
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
    </script>

    <script>
        const productsApiUrl = '/api/product-lists';

        $(document).ready(function () {
            const table = $('#productsTable').DataTable({
                ajax: {
                    url: `${productsApiUrl}/list-all`,
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
                    { data: 'optimal_production_time', defaultContent: '' },
                    { data: 'box_kg', defaultContent: '' },
                    {
                        data: null,
                        render: function (data) {
                            return `
                                <button class="edit-btn btn btn-sm btn-secondary"
                                    data-id="${data.id}"
                                    data-name="${data.name}"
                                    data-optimal="${data.optimal_production_time || ''}"
                                    data-box="${data.box_kg || ''}">
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
                        text: 'Añadir Confección',
                        className: 'btn btn-success',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Producto',
                                html: `
                                    <input id="productId" class="swal2-input" placeholder="Codigo Confection"style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="productName" class="swal2-input" placeholder="Nombre del Producto"style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="productOptimalTime" class="swal2-input" placeholder="Tiempo Óptimo (opcional)"style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                    <input id="productBoxKg" class="swal2-input" placeholder="Kg por Caja (opcional)"style="
                                        width: 550px; 
                                        background: transparent; 
                                        color: black; 
                                        text-shadow: 1px 1px 2px white; 
                                        border: 1px solid #ccc;
                                        padding: 0.5em;
                                        border-radius: 4px;
                                    ">
                                `,
                                confirmButtonText: 'Añadir',
                                showCancelButton: true,
                                width: '800px', // Aumenta el ancho
                                padding: '2em',  // Aumenta el espacio interno
                                preConfirm: () => {
                                    const id      = $('#productId').val();
                                    const name    = $('#productName').val();
                                    const optTime = $('#productOptimalTime').val();
                                    const boxKg   = $('#productBoxKg').val();

                                    if (!id || !name) {
                                        Swal.showValidationMessage('ID y Nombre son obligatorios.');
                                        return false;
                                    }
                                    return {
                                        id: parseInt(id),
                                        name,
                                        optimal_production_time: optTime ? parseInt(optTime) : null,
                                        box_kg: boxKg ? parseFloat(boxKg) : null
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: `${productsApiUrl}/update-or-insert`,
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify(result.value),
                                        success: function () {
                                            Swal.fire('Éxito', 'Producto añadido o actualizado.', 'success');
                                            table.ajax.reload();
                                        },
                                        error: function (xhr) {
                                            Swal.fire('Error', `No se pudo añadir. ${xhr.responseJSON?.error || ''}`, 'error');
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
                            columns: [0,1,2,3]
                        }
                    }
                ]
            });

            // Evento para Editar
            $('#productsTable tbody').on('click', '.edit-btn', function () {
                const currentId   = $(this).data('id');
                const currentName = $(this).data('name');
                const currentOpt  = $(this).data('optimal');
                const currentBox  = $(this).data('box');

                Swal.fire({
                    title: 'Editar Producto',
                    width: '600px', // Aumenta el ancho
                    padding: '2em',  // Aumenta el espacio interno
                    html: `
                        <input id="productId" class="swal2-input" value="${currentId}" readonly>
                        <input id="productName" class="swal2-input" value="${currentName}">
                        <input id="productOptimalTime" class="swal2-input" value="${currentOpt || ''}">
                        <input id="productBoxKg" class="swal2-input" value="${currentBox || ''}">
                    `,
                    confirmButtonText: 'Actualizar',
                    showCancelButton: true,
                    preConfirm: () => {
                        const id      = $('#productId').val();
                        const name    = $('#productName').val();
                        const optTime = $('#productOptimalTime').val();
                        const boxKg   = $('#productBoxKg').val();

                        if (!id || !name) {
                            Swal.showValidationMessage('ID y Nombre son obligatorios.');
                            return false;
                        }
                        return {
                            id: parseInt(id),
                            name,
                            optimal_production_time: optTime ? parseInt(optTime) : null,
                            box_kg: boxKg ? parseFloat(boxKg) : null
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${productsApiUrl}/update-or-insert`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(result.value),
                            success: function () {
                                Swal.fire('Éxito', 'Producto actualizado.', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire('Error', `No se pudo actualizar. ${xhr.responseJSON?.error || ''}`, 'error');
                            }
                        });
                    }
                });
            });

            // Evento para Eliminar
            $('#productsTable tbody').on('click', '.delete-btn', function () {
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
                            url: `${productsApiUrl}/${id}`,
                            method: 'DELETE',
                            success: function () {
                                Swal.fire('Éxito', 'Producto eliminado.', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire('Error', `No se pudo eliminar. ${xhr.responseJSON?.error || ''}`, 'error');
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
                        return;
                    }

                    console.log('Encabezados reales detectados:', rows[0]);
                    const expectedHeaders = ['ID Cliente', 'Nombre', 'Tiempo Óptimo', 'Kg por Caja'];
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
                        return;
                    }

                    // Ignoramos la primera fila (encabezados)
                    rows = rows.slice(1);

                    const formattedRows = rows.map(row => ({
                        id: parseInt(row[0]) || null,
                        name: row[1] ? String(row[1]).trim() : null,
                        optimal_production_time: row[2] ? parseInt(row[2]) : null,
                        box_kg: row[3] ? parseFloat(row[3]) : null
                    }));

                    const invalidRows = formattedRows.filter(r => !r.id || !r.name);
                    if (invalidRows.length > 0) {
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
                            url: `${productsApiUrl}/update-or-insert`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(row),
                        }).fail(xhr => {
                            console.error('Error en la fila:', row, 'Respuesta del servidor:', xhr.responseText);
                        });
                    });

                    Promise.all(promises)
                        .then(() => {
                            Swal.fire('Éxito', 'El archivo fue procesado correctamente.', 'success');
                            table.ajax.reload();
                        })
                        .catch(() => {
                            Swal.fire('Error', 'Ocurrió un error al procesar el archivo.', 'error');
                        });
                };
                reader.readAsArrayBuffer(file);
                $(this).val('');
            });
        });
    </script>
@endpush
