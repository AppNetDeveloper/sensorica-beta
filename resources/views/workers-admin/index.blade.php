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
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div> {{-- .table-responsive --}}

                    {{-- Leyenda de ayuda sobre PIN y Contraseña --}}
                    <div class="alert alert-info mt-3" role="alert">
                        <strong>Información:</strong>
                        <div>- <strong>PIN</strong>: se usa únicamente en la <em>pantalla de línea de producción</em> para un mini login rápido (fichaje). No es una credencial de acceso al sistema.</div>
                        <div>- <strong>Contraseña</strong>: se usa para funciones del sistema que requieran autenticación tradicional (cuando aplique). No afecta al fichaje de la línea.</div>
                        <div>- Puedes <strong>resetear el PIN por WhatsApp</strong> al trabajador si tiene teléfono configurado.</div>
                    </div>
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

        #workersTable {
            margin: 0;
            border: none;
        }

        #workersTable thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        #workersTable thead th {
            border: none;
            padding: 1.2rem;
            font-weight: 700;
            color: #495057;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dee2e6;
        }

        #workersTable tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f3f4;
        }

        #workersTable tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }

        #workersTable tbody td {
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

        .btn-toggle {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-reset-email {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }

        .btn-reset-whatsapp {
            background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
            color: white;
        }

        .btn-reset-pin {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #333;
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

        .btn-add-worker,
        .dt-button.btn-add-worker,
        .buttons-html5.btn-add-worker {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3) !important;
        }

        .btn-add-worker:hover,
        .dt-button.btn-add-worker:hover,
        .buttons-html5.btn-add-worker:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4) !important;
        }

        .btn-import-excel,
        .dt-button.btn-import-excel,
        .buttons-html5.btn-import-excel {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3) !important;
        }

        .btn-import-excel:hover,
        .dt-button.btn-import-excel:hover,
        .buttons-html5.btn-import-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4) !important;
        }

        .btn-export-excel,
        .dt-button.btn-export-excel,
        .buttons-html5.btn-export-excel {
            background: linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%) !important;
            box-shadow: 0 4px 15px rgba(252, 74, 26, 0.3) !important;
        }

        .btn-export-excel:hover,
        .dt-button.btn-export-excel:hover,
        .buttons-html5.btn-export-excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(252, 74, 26, 0.4) !important;
        }

        .btn-monitor,
        .dt-button.btn-monitor,
        .buttons-html5.btn-monitor {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3) !important;
        }

        .btn-monitor:hover,
        .dt-button.btn-monitor:hover,
        .buttons-html5.btn-monitor:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4) !important;
        }

        .btn-print,
        .dt-button.btn-print,
        .buttons-html5.btn-print {
            background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%) !important;
            box-shadow: 0 4px 15px rgba(142, 45, 226, 0.3) !important;
        }

        .btn-print:hover,
        .dt-button.btn-print:hover,
        .buttons-html5.btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(142, 45, 226, 0.4) !important;
        }

        /* Alerta de información */
        .alert-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.1);
            border-left: 5px solid #2196f3;
        }

        .alert-info strong {
            color: #1565c0;
            font-weight: 700;
        }

        /* SweetAlert worker modal mejorado */
        .worker-modal .swal2-popup {
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.98);
        }

        .worker-modal .swal2-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .worker-modal .swal2-html-container{
            margin: 0 !important;
            padding: 1.5rem;
        }

        .worker-modal .wm-grid{
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 15px 20px;
            align-items: center;
            width: 100%;
        }

        .worker-modal label{
            font-weight: 700;
            color: #667eea;
            margin: 0;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .worker-modal .swal2-input{
            width: 100% !important;
            margin: 0 !important;
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

        .worker-modal .swal2-input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
            background: white !important;
        }

        .worker-modal .swal2-confirm {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .worker-modal .swal2-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .worker-modal .swal2-cancel {
            background: #6c757d;
            border: none;
            border-radius: 12px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .worker-modal small {
            grid-column: 2 / span 1;
            color: #6c757d;
            font-style: italic;
            font-size: 0.9rem;
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
            .worker-modal .wm-grid{
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .worker-modal small{
                grid-column: auto;
            }

            .worker-modal .swal2-popup {
                width: 95% !important;
                margin: 1rem !important;
            }
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
        #workersTable tbody tr {
            position: relative;
        }

        #workersTable tbody tr::after {
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

        #workersTable tbody tr:hover::after {
            transform: scaleX(1);
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
                        render: function (data) {
                            return `
                                <div class="d-flex flex-wrap gap-1">
                                    <button class="action-btn btn-edit"
                                            data-id="${data.id}"
                                            data-name="${data.name}"
                                            data-email="${data.email || ''}"
                                            data-phone="${data.phone || ''}"
                                            title="Editar trabajador">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button class="action-btn btn-delete"
                                            data-id="${data.id}"
                                            title="Eliminar trabajador">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                    <button class="action-btn btn-toggle"
                                            data-id="${data.id}"
                                            data-active="${data.active}"
                                            title="${data.active ? 'Deshabilitar trabajador' : 'Habilitar trabajador'}">
                                        <i class="fas ${data.active ? 'fa-user-slash' : 'fa-user-check'}"></i> ${data.active ? 'Deshab.' : 'Habilitar'}
                                    </button>
                                    <button class="action-btn btn-reset-email"
                                            data-email="${data.email || ''}"
                                            title="Resetear contraseña por email">
                                        <i class="fas fa-envelope"></i> Email
                                    </button>
                                    <button class="action-btn btn-reset-whatsapp"
                                            data-phone="${data.phone || ''}"
                                            title="Resetear contraseña por WhatsApp">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </button>
                                    <button class="action-btn btn-reset-pin"
                                            data-phone="${data.phone || ''}"
                                            data-id="${data.id}"
                                            title="Resetear PIN por WhatsApp">
                                        <i class="fas fa-key"></i> PIN
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
                        text: '<i class="fas fa-user-plus"></i> Añadir Trabajador',
                        className: 'btn-add-worker',
                        action: function () {
                            Swal.fire({
                                title: 'Añadir Trabajador',
                                html: `
                                    <div class="wm-grid">
                                      <label>Código Trabajador <span class="text-danger">*</span></label>
                                      <input id="workerId" class="swal2-input" placeholder="Ej: 1001" autocomplete="off" inputmode="numeric">
                                      <label>Nombre <span class="text-danger">*</span></label>
                                      <input id="workerName" class="swal2-input" placeholder="Nombre y apellidos" autocomplete="off">
                                      <label>Email (opcional)</label>
                                      <input id="workerEmail" class="swal2-input" placeholder="email@dominio.com" autocomplete="off">
                                      <label>Teléfono (opcional)</label>
                                      <input id="workerPhone" class="swal2-input" placeholder="+34XXXXXXXXX" autocomplete="off" inputmode="tel">
                                      <label>PIN (opcional, 4–6 dígitos)</label>
                                      <input id="workerPin" class="swal2-input" placeholder="PIN para fichaje en línea" autocomplete="new-password" inputmode="numeric" maxlength="10">
                                      <label>Contraseña (opcional)</label>
                                      <input id="workerPassword" type="password" class="swal2-input" placeholder="Credencial del sistema (si aplica)" autocomplete="new-password">
                                    </div>
                                `,
                                width: '800px', // Aumenta el ancho
                                padding: '2em', // Aumenta el padding
                                confirmButtonText: 'Añadir',
                                showCancelButton: true,
                                allowOutsideClick: false,
                                allowEnterKey: true,
                                customClass: { popup: 'worker-modal' },
                                didOpen: () => { setTimeout(() => document.getElementById('workerId')?.focus(), 50); },
                                preConfirm: () => {
                                    const id       = $('#workerId').val();
                                    const name     = $('#workerName').val();
                                    const email    = $('#workerEmail').val();
                                    const phone    = $('#workerPhone').val();
                                    const password = $('#workerPassword').val();
                                    const pin      = $('#workerPin').val();

                                    if (!id || !name) {
                                        Swal.showValidationMessage('Código y Nombre son obligatorios.');
                                        return false;
                                    }
                                    if (pin && (pin.length < 4 || pin.length > 6 || /\D/.test(pin))) {
                                        Swal.showValidationMessage('El PIN debe tener 4–6 dígitos numéricos.');
                                        return false;
                                    }
                                    return {
                                        id: parseInt(id),
                                        name,
                                        email: email || null,
                                        phone: phone || null,
                                        password: password || null,
                                        pin: pin || null
                                    };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    const { id, name, email, phone, password, pin } = result.value;
                                    $.ajax({
                                        url: `${workersApiUrl}/update-or-insert`,
                                        method: 'POST',
                                        contentType: 'application/json',
                                        data: JSON.stringify({ id, name, email, phone, password, pin }),
                                        success: function () {
                                            Swal.fire({
                                                icon: 'success',
                                                title: '¡Excelente!',
                                                text: 'Trabajador añadido o actualizado correctamente',
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
                        text: '<i class="fas fa-desktop"></i> Monitor',
                        className: 'btn-monitor',
                        action: function () {
                            const url = '/workers/workers-live.html?shift=true&order=true&name=true&id=true&nameSize=1.2rem&numberSize=2rem&idSize=1rem&labelSize=1rem';
                            window.open(url, '_blank');
                        }
                    },
                    {
                        text: '<i class="fas fa-print"></i> Imprimir Listado',
                        className: 'btn-print',
                        action: function () {
                            const url = '/workers/export.html';
                            window.open(url, '_blank');
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
            $('#workersTable').on('draw.dt', function() {
                $('#workersTable tbody tr').each(function(index) {
                    $(this).css({
                        'opacity': 0,
                        'transform': 'translateY(20px)'
                    }).delay(index * 50).animate({
                        'opacity': 1,
                        'transform': 'translateY(0)'
                    }, 300);
                });
            });

            // ---- Eventos CRUD / Reset Pass / Excel ----
            
            // Editar trabajador
            $('#workersTable tbody').on('click', '.btn-edit', function () {
                const currentId    = $(this).data('id');
                const currentName  = $(this).data('name');
                const currentEmail = $(this).data('email');
                const currentPhone = $(this).data('phone');

                Swal.fire({
                    title: 'Editar Trabajador',
                    html: `
                        <div class="wm-grid">
                          <label>Código Trabajador</label>
                          <input id="workerId" class="swal2-input" value="${currentId}" readonly>
                          <label>Nombre</label>
                          <input id="workerName" class="swal2-input" value="${currentName}">
                          <label>Email (opcional)</label>
                          <input id="workerEmail" class="swal2-input" placeholder="Email (Opcional)" value="${currentEmail}">
                          <label>Teléfono (opcional)</label>
                          <input id="workerPhone" class="swal2-input" placeholder="Teléfono (Opcional)" value="${currentPhone}">
                          <label>PIN (opcional, 4–6 dígitos)</label>
                          <input id="workerPinEdit" class="swal2-input" placeholder="PIN (Opcional, 4-6 dígitos)" autocomplete="new-password" inputmode="numeric" maxlength="10">
                          <label>Nueva Contraseña (opcional)</label>
                          <input id="workerPassword" type="password" class="swal2-input" placeholder="Nueva Contraseña (opcional)" autocomplete="new-password">
                          <small>Deje la contraseña en blanco para no cambiarla</small>
                        </div>
                    `,
                    confirmButtonText: 'Actualizar',
                    showCancelButton: true,
                    allowOutsideClick: false,
                    allowEnterKey: true,
                    customClass: { popup: 'worker-modal' },
                    didOpen: () => { setTimeout(() => document.getElementById('workerName')?.focus(), 50); },
                    preConfirm: () => {
                        const id       = $('#workerId').val();
                        const name     = $('#workerName').val();
                        const email    = $('#workerEmail').val() || null;
                        const phone    = $('#workerPhone').val() || null;
                        const password = $('#workerPassword').val();
                        const pin      = $('#workerPinEdit').val();

                        if (!id || !name) {
                            Swal.showValidationMessage('ID y Nombre son obligatorios.');
                            return false;
                        }
                        if (pin && (pin.length < 4 || pin.length > 6 || /\D/.test(pin))) {
                            Swal.showValidationMessage('El PIN debe tener 4–6 dígitos numéricos.');
                            return false;
                        }
                        return {
                            id: parseInt(id),
                            name,
                            email,
                            phone,
                            password: password || null,
                            pin: pin || null
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { id, name, email, phone, password, pin } = result.value;
                        const payload = { id, name, email, phone };
                        if (password) payload.password = password;
                        if (pin) payload.pin = pin;

                        $.ajax({
                            url: `${workersApiUrl}/update-or-insert`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(payload),
                            success: function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Actualizado!',
                                    text: 'Trabajador actualizado correctamente',
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
            $('#workersTable tbody').on('click', '.btn-delete', function () {
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
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: 'Trabajador eliminado correctamente',
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

            // Toggle estado activo del trabajador
            $('#workersTable tbody').on('click', '.btn-toggle', function () {
                const id = $(this).data('id');
                const currentActive = $(this).data('active');
                const actionText = currentActive ? 'deshabilitar' : 'habilitar';
                
                Swal.fire({
                    title: `¿Estás seguro de ${actionText} este trabajador?`,
                    text: currentActive ? 'El trabajador no podrá acceder al sistema.' : 'El trabajador podrá acceder al sistema.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: `Sí, ${actionText}`,
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${workersApiUrl}/${id}/toggle-active`,
                            method: 'POST',
                            success: function () {
                                const newActiveText = currentActive ? 'habilitado' : 'deshabilitado';
                                Swal.fire(`Trabajador ${newActiveText}`, '', 'success');
                                table.ajax.reload();
                            },
                            error: function (xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: `Error al ${actionText}`,
                                    text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}`
                                });
                            }
                        });
                    }
                });
            });

            // Resetear contraseña por email
            $('#workersTable tbody').on('click', '.btn-reset-email', function () {
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
            $('#workersTable tbody').on('click', '.btn-reset-whatsapp', function () {
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

            // Resetear PIN por WhatsApp (handler fuera del bloque de error y junto al resto de handlers)
            $('#workersTable tbody').on('click', '.btn-reset-pin', function () {
                const phone = $(this).data('phone');
                const operatorId = $(this).data('id');
                if (!phone && !operatorId) {
                    Swal.fire({ icon: 'warning', title: 'Datos insuficientes', text: 'No hay teléfono ni ID de operador para enviar el PIN.' });
                    return;
                }
                Swal.fire({
                    title: 'Resetear PIN por WhatsApp',
                    text: phone ? `Se generará un nuevo PIN para el teléfono: ${phone}` : `Se generará un nuevo PIN para el operador ID: ${operatorId}`,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `${workersApiUrl}/reset-pin-whatsapp`,
                            method: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify(phone ? { phone } : { operator_id: operatorId }),
                            success: function () {
                                Swal.fire('PIN reseteado, se enviará por WhatsApp', '', 'success');
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: 'Error al Resetear PIN', text: `Status: ${xhr.status}. Mensaje: ${xhr.responseJSON?.error || 'Error desconocido'}` });
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
                    const expectedHeaders = ['Codigo Trabajador', 'Nombre', 'Email', 'Teléfono', 'Contraseña'];
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
                            footer: 'Codigo Trabajador y Nombre son obligatorios.'
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
