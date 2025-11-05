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
    <div class="row mt-3">
        <div class="col-lg-12">
            {{-- Card principal con glassmorfismo --}}
            <div class="card border-0 shadow">
                <div class="card-header border-0">
                    <h4 class="card-title">Gestión de Confecciones</h4>
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
    {{-- Font Awesome para iconos --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
            margin: 2rem auto;
            background: white;
        }

        #productsTable {
            margin: 0;
            border: none;
        }

        #productsTable thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        #productsTable thead th {
            border: none;
            padding: 1.2rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        #productsTable tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
        }

        #productsTable tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        #productsTable tbody td {
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

        .btn-edit {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .btn-edit:hover {
            color: #212529;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-delete:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        /* Botones DataTables */
        .dt-buttons {
            margin: 2rem 0;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .dt-button,
        .buttons-html5,
        .dt-button:active,
        .dt-button:focus,
        .buttons-html5:active,
        .buttons-html5:focus {
            color: white !important;
            border: none !important;
            border-radius: 12px;
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
            text-decoration: none !important;
            background: none !important;
            box-shadow: none !important;
        }

        .dt-button::before,
        .buttons-html5::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .dt-button:hover::before,
        .buttons-html5:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-add-confection {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
        }

        .btn-add-confection:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }

        .btn-import-excel {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3) !important;
        }

        .btn-import-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4) !important;
        }

        .btn-export-excel {
            background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%) !important;
            box-shadow: 0 4px 15px rgba(252, 74, 26, 0.3) !important;
        }

        .btn-export-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(252, 74, 26, 0.4) !important;
        }

        /* SweetAlert modales mejorados */
        .swal2-popup {
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }

        .swal2-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .swal2-input {
            width: 100% !important;
            margin: 0.5rem 0 !important;
            background: #f8f9fa !important;
            border: 2px solid #e9ecef !important;
            color: #495057 !important;
            box-shadow: none !important;
            height: 2.8rem;
            padding: 0.75rem 1rem !important;
            border-radius: 10px !important;
            transition: all 0.3s ease !important;
            font-size: 1rem;
        }

        .swal2-input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
            background: white !important;
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

        .swal2-cancel {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        /* Loading spinner personalizado */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Animaciones para los toast notifications */
        .animated-toast {
            animation: slideInRight 0.3s ease-out;
        }

        @keyframes slideInRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .animated-toast.swal2-show {
            animation: slideInRight 0.3s ease-out;
        }

        .animated-toast.swal2-hide {
            animation: slideOutRight 0.3s ease-out;
        }

        @keyframes slideOutRight {
            0% {
                transform: translateX(0);
                opacity: 1;
            }
            100% {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        /* Mejorar efecto hover en todas las filas */
        #productsTable tbody tr {
            position: relative;
        }

        #productsTable tbody tr::after {
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

        #productsTable tbody tr:hover::after {
            transform: scaleX(1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }

            .dt-buttons {
                justify-content: flex-start;
                overflow-x: auto;
                padding: 0 1rem;
            }

            .action-btn {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }
        }

        @media (max-width: 640px){
            .swal2-popup {
                width: 95% !important;
                margin: 1rem !important;
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
                                <div class="d-flex flex-wrap gap-1">
                                    <button class="action-btn btn-edit"
                                            data-id="${data.id}"
                                            data-name="${data.name}"
                                            data-optimal="${data.optimal_production_time || ''}"
                                            data-box="${data.box_kg || ''}"
                                            title="Editar confección">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="action-btn btn-delete"
                                            data-id="${data.id}"
                                            title="Eliminar confección">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                responsive: true,
                scrollX: true,

                dom: 'Bfrtip',
                buttons: [
                    {
                        text: '<i class="fas fa-plus-circle"></i> Añadir Confección',
                        className: 'btn-add-confection',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Confección',
                                html: `
                                    <div class="form-group">
                                        <input id="productId" class="swal2-input" placeholder="Código Confección" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <input id="productName" class="swal2-input" placeholder="Nombre del Producto" autocomplete="off">
                                    </div>
                                    <div class="form-group">
                                        <input id="productOptimalTime" class="swal2-input" placeholder="Tiempo Óptimo (opcional)" type="number">
                                    </div>
                                    <div class="form-group">
                                        <input id="productBoxKg" class="swal2-input" placeholder="Kg por Caja (opcional)" type="number" step="0.01">
                                    </div>
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
                                            Swal.fire({
                                                icon: 'success',
                                                title: '¡Excelente!',
                                                text: 'Confección añadida correctamente',
                                                showConfirmButton: false,
                                                timer: 2000,
                                                backdrop: `rgba(102, 126, 234, 0.1)`,
                                                customClass: {
                                                    popup: 'animated-toast'
                                                }
                                            });
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
                        text: '<i class="fas fa-file-import"></i> Importar Excel',
                        className: 'btn-import-excel',
                        action: function () {
                            $('#excelFileInput').click();
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-export"></i> Exportar Excel',
                        className: 'btn-export-excel',
                        title: null,
                        exportOptions: {
                            columns: [0,1,2,3]
                        }
                    }
                ]
            });

            // ---- Mejoras visuales y de experiencia de usuario ----

            // Añadir efecto de carga personalizado
            $(document).ajaxStart(function() {
                if (!$('.loading-overlay').length) {
                    $('body').append('<div class="loading-overlay"><div class="loading-spinner"></div></div>');
                }
            });

            $(document).ajaxStop(function() {
                $('.loading-overlay').fadeOut(300, function() {
                    $(this).remove();
                });
            });

            // Animación de entrada para las filas de la tabla
            $('#productsTable').on('draw.dt', function() {
                $('#productsTable tbody tr').each(function(index) {
                    $(this).css({
                        'opacity': 0,
                        'transform': 'translateY(20px)'
                    }).delay(index * 50).animate({
                        'opacity': 1,
                        'transform': 'translateY(0)'
                    }, 300);
                });
            });

            // Evento para Editar
            $('#productsTable tbody').on('click', '.btn-edit', function () {
                const currentId   = $(this).data('id');
                const currentName = $(this).data('name');
                const currentOpt  = $(this).data('optimal');
                const currentBox  = $(this).data('box');

                Swal.fire({
                    title: 'Editar Confección',
                    html: `
                        <div class="form-group">
                            <input id="productId" class="swal2-input" value="${currentId}" readonly>
                        </div>
                        <div class="form-group">
                            <input id="productName" class="swal2-input" value="${currentName}">
                        </div>
                        <div class="form-group">
                            <input id="productOptimalTime" class="swal2-input" value="${currentOpt || ''}" type="number">
                        </div>
                        <div class="form-group">
                            <input id="productBoxKg" class="swal2-input" value="${currentBox || ''}" type="number" step="0.01">
                        </div>
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
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Actualizado!',
                                    text: 'Confección actualizada correctamente',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    backdrop: `rgba(102, 126, 234, 0.1)`,
                                    customClass: {
                                        popup: 'animated-toast'
                                    }
                                });
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
            $('#productsTable tbody').on('click', '.btn-delete', function () {
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
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: 'Confección eliminada correctamente',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    backdrop: `rgba(102, 126, 234, 0.1)`,
                                    customClass: {
                                        popup: 'animated-toast'
                                    }
                                });
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
