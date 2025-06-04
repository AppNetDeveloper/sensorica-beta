@php
use App\Facades\UtilityFacades;
$logo = asset(Storage::url('uploads/logo/'));
$company_favicon = UtilityFacades::getValByName('company_favicon');
$settings = UtilityFacades::settings();
if(isset($settings['color']))
{
    $primary_color = $settings['color'];
    if ($primary_color!="") {
        $color = $primary_color;
    } else {
        $color = 'theme-1';
    }
}
else{
    $color = 'theme-1';
}

if(isset($settings['dark_mode']))
{
    $dark_mode = $settings['dark_mode'];
    if ($dark_mode!="") {
        $dark_mode = $dark_mode;
    } else {
        $dark_mode = "";
    }
}
else{
    $dark_mode = "";
}





@endphp
<!DOCTYPE html>
<html dir="{{ env('SITE_RTL') == 'on' ? 'rtl' : '' }}" lan="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ $logo . (isset($company_favicon) && !empty($company_favicon) ? $company_favicon : 'favicon.png') }}" type="image" sizes="16x16">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{--  <link href="{{ asset('css/app.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/free.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet" /> --}}

    <!-- font css -->
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">
    {{--  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">  --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26vBsMLFYH4kQ67/bbV8XaCQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">
   
    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">

    {{-- Notification --}}
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/notifier.css') }}">

{{--  {{ dd($settings['dark_mode']) }}  --}}
    @if (env('SITE_RTL') == 'on')
    <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}">
    @else
        @if ($dark_mode == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-dark.css') }}">
        @else
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">
        @endif
    @endif

        <link href="{{ asset('css/toastr.min.css') }}" rel="stylesheet">
        @yield('css')
        <link href="{{ asset('vendor/css/custom.css') }}" rel="stylesheet">
        <link href="{{ asset('css/bootstrap-datetimepicker.css') }}" rel="stylesheet">
    {{--  <link href="{{ asset('css/style.css') }}" rel="stylesheet">  --}}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    @stack('style')
    @laravelPWA
    
    <!-- Estilos para el botón de instalación PWA -->
    <style>
        #installPWA {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        #installPWA:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }
        #installPWA i {
            font-size: 1.2em;
        }
    </style>
</head>

<body class="{{ $color }}">
    <!-- [ Pre-loader ] start -->
    <div class="loader-bg">
        <div class="loader-track">
            <div class="loader-fill"></div>
        </div>
    </div>
    <!-- [ Pre-loader ] End -->
    <!-- [ Mobile header ] start -->
    <div class="dash-mob-header dash-header">
        <div class="pcm-logo">
            <img src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}" alt="" class="logo logo-lg" style="max-height: 40px;" />
        </div>
        <div class="pcm-toolbar">
            <a href="#!" class="dash-head-link" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
                <span class="sr-only">Toggle menu</span>
            </a>
        </div>
    </div>
    <!-- [ Mobile header ] End -->

    <!-- [ navigation menu ] start -->
    {{--  @include('layouts.sidebar')  --}}
    @include('partial.nav-builder')

    <!-- [ navigation menu ] end -->
    <!-- [ Header ] start -->
    {{--  @include('include.header')  --}}
    @include('partial.header')

    <!-- Botón flotante de instalación PWA -->
    <button id="installPWA" aria-label="Instalar aplicación">
        <i class="ti ti-download"></i>
        <span>Instalar App</span>
    </button>

    <!-- Script para el manejo de la instalación PWA -->
    <script>
        // Variable para almacenar el evento beforeinstallprompt
        let deferredPrompt;
        const installButton = document.getElementById('installPWA');
        
        // Solo mostrar el botón si el navegador soporta PWA
        if (window.matchMedia('(display-mode: browser)').matches) {
            // Mostrar el botón solo si no está ya instalado
            window.addEventListener('beforeinstallprompt', (e) => {
                // Prevenir que Chrome 67 y anteriores muestren automáticamente el prompt
                e.preventDefault();
                // Guardar el evento para que se pueda activar más tarde
                deferredPrompt = e;
                // Mostrar el botón
                if (installButton) {
                    installButton.style.display = 'flex';
                }
                
                // Ocultar el botón después de 10 segundos
                setTimeout(() => {
                    if (installButton) {
                        installButton.style.display = 'none';
                    }
                }, 10000);
            });
            
            // Evento de clic en el botón de instalación
            if (installButton) {
                installButton.addEventListener('click', async () => {
                    if (!deferredPrompt) return;
                    
                    // Mostrar el prompt de instalación
                    deferredPrompt.prompt();
                    
                    // Esperar a que el usuario responda al prompt
                    const { outcome } = await deferredPrompt.userChoice;
                    
                    // Limpiar el evento guardado ya que no se puede volver a usar
                    deferredPrompt = null;
                    
                    // Ocultar el botón independientemente del resultado
                    if (installButton) {
                        installButton.style.display = 'none';
                    }
                    
                    // Opcional: rastrear el resultado de la instalación
                    console.log(`User response to the install prompt: ${outcome}`);
                });
            }
            
            // Ocultar el botón cuando la aplicación se instala correctamente
            window.addEventListener('appinstalled', () => {
                console.log('PWA fue instalado');
                if (installButton) {
                    installButton.style.display = 'none';
                }
                deferredPrompt = null;
            });
            
            // Verificar si la aplicación ya está instalada
            window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
                if (e.matches) {
                    // La aplicación está instalada
                    if (installButton) {
                        installButton.style.display = 'none';
                    }
                }
            });
            
            // Verificar el modo de visualización actual
            if (window.matchMedia('(display-mode: standalone)').matches) {
                // La aplicación ya está instalada
                if (installButton) {
                    installButton.style.display = 'none';
                }
            }
        }
    </script>
</body>

<!-- [ Main Content ] start -->
<div class="dash-container">
    <div class="dash-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">

                        <div class="page-header-title">
                            <h4 class="m-b-10">@yield('title')</h4>
                        </div>
                        @yield('breadcrumb')

                    </div>
                </div>
            </div>
        </div>
{{--  {{ dd(Session::all() )}}  --}}
        @yield('content')
    </div>
</div>
<!-- [ Main Content ] end -->

{{--  <footer class="dash-footer">
    <div class="footer-wrapper">
        <span class="text-muted">
            <img src="{{ $logo . 'dark_logo.png' }}" class="navbar-brand-img main-logo" alt="logo">
        </span>
        <div class="ms-auto">Powered by&nbsp;
             &copy; {{ date('Y') }} <a href="#" class="fw-bold ms-1"
                target="_blank">{{ config('app.name') }}
        </div>
    </div>
</footer>  --}}

<footer class="dash-footer">
    <div class="footer-wrapper">
        <span class="text-muted">
            Powered by&nbsp;
            &copy; {{ date('Y') }} <a href="#" class="fw-bold ms-1"
                target="_blank">{{ config('app.name') }}
            {{--  <img src="{{ $logo . 'dark_logo.png' }}" class="main-logo" alt="logo">  --}}

        </span>
        {{--  <div class="ms-auto ff">Powered by&nbsp;
            &copy; {{ date('Y') }} <a href="#" class="fw-bold ms-1"
                target="_blank">{{ config('app.name') }}
        </div>  --}}

        <div class="py-1">
            <ul class="list-inline m-0">
                <li class="list-inline-item">
                    <a class="link-secondary" href="javascript:"></a>
                </li>
                <li class="list-inline-item">
                    <a class="link-secondary" href="javascript:"> </a>
                </li>
                <li class="list-inline-item">
                    <a class="link-secondary" href="javascript:"></a>
                </li>
                <li class="list-inline-item">
                    <a class="link-secondary" href="javascript:"></a>
                </li>
            </ul>
        </div>
    </div>
</footer>

<!-- Form Modal -->
<div class="modal fade" role="dialog" id="common_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <div class="body">

            </div>
        </div>
    </div>
</div>


<!-- Form Modal Ends -->

    {{--  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>  --}}

    <script src="{{ asset('assets/js/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/dash.js') }}"></script>



    <script src="{{ asset('assets/js/plugins/choices.min.js') }}"></script>


    <!-- Apex Chart -->
    <script src="{{ asset('assets/js/plugins/apexcharts.min.js') }}"></script>

      <script>
        function removeClassByPrefix(node, prefix) {
          for (let i = 0; i < node.classList.length; i++) {
            let value = node.classList[i];
            if (value.startsWith(prefix)) {
              node.classList.remove(value);
            }
          }
        }
      </script>

{{-- Notiffication --}}

<script src="{{ asset('js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/js/plugins/notifier.js') }}"></script>
<script src="{{ asset('js/coreui.bundle.min.js') }}"></script>
<script src="{{ asset('js/coreui-utils.js') }}"></script>
{{--  <script src="{{ asset('js/select2.min.js') }}"></script>  --}}
<script src="{{ asset('js/moment.min.js') }}"></script>
<script src="{{ asset('js/bootstrap-datetimepicker.min.js') }}"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
    // Initialize toastr with RTL support
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    
    // Set RTL if needed
    @if(env('SITE_RTL') == 'on')
        toastr.options.positionClass = "toast-top-left";
    @endif
</script>
{{--  <script src="{{ asset('js/custom.js') }}"></script>  --}}
    <script>
        var toster_pos = "{{ env('SITE_RTL') == 'on' ? 'left' : 'right' }}";
    </script>
    <script>
        function delete_record(id) {
            event.preventDefault();
            if (confirm('Are You Sure?')) {
                document.getElementById(id).submit();
            }
        }
    </script>

    @include('layouts.includes.alerts')
    @yield('javascript')

    @stack('scripts')


    <style>
        /* --- Danger --- */
        .btn-outline-danger { color: #DC3545 !important; border-color: #DC3545 !important; background-color: transparent !important; }
        .btn-outline-danger:hover, .btn-outline-danger:focus, .btn-outline-danger:active, .btn-outline-danger.active, .btn-check:active + .btn-outline-danger, .btn-check:checked + .btn-outline-danger { color: #fff !important; background-color: #DC3545 !important; border-color: #DC3545 !important; box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.5) !important; }
        .btn-outline-danger:disabled, .btn-outline-danger.disabled { color: #DC3545 !important; border-color: #DC3545 !important; background-color: transparent !important; }

        /* --- Warning --- */
        .btn-outline-warning { color: #ffc107 !important; border-color: #ffc107 !important; background-color: transparent !important; }
        .btn-outline-warning:hover, .btn-outline-warning:focus, .btn-outline-warning:active, .btn-outline-warning.active, .btn-check:active + .btn-outline-warning, .btn-check:checked + .btn-outline-warning { color: #000 !important; /* Texto negro en hover */ background-color: #ffc107 !important; border-color: #ffc107 !important; box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.5) !important; }
        .btn-outline-warning:disabled, .btn-outline-warning.disabled { color: #ffc107 !important; border-color: #ffc107 !important; background-color: transparent !important; }

        /* --- Success --- */
        .btn-outline-success { color: #198754 !important; border-color: #198754 !important; background-color: transparent !important; }
        .btn-outline-success:hover, .btn-outline-success:focus, .btn-outline-success:active, .btn-outline-success.active, .btn-check:active + .btn-outline-success, .btn-check:checked + .btn-outline-success { color: #fff !important; background-color: #198754 !important; border-color: #198754 !important; box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.5) !important; }
        .btn-outline-success:disabled, .btn-outline-success.disabled { color: #198754 !important; border-color: #198754 !important; background-color: transparent !important; }

        /* --- Info --- */
        .btn-outline-info { color: #0dcaf0 !important; border-color: #0dcaf0 !important; background-color: transparent !important; }
        .btn-outline-info:hover, .btn-outline-info:focus, .btn-outline-info:active, .btn-outline-info.active, .btn-check:active + .btn-outline-info, .btn-check:checked + .btn-outline-info { color: #000 !important; /* Texto negro en hover (BS5) */ background-color: #0dcaf0 !important; border-color: #0dcaf0 !important; box-shadow: 0 0 0 0.25rem rgba(13, 202, 240, 0.5) !important; }
        .btn-outline-info:disabled, .btn-outline-info.disabled { color: #0dcaf0 !important; border-color: #0dcaf0 !important; background-color: transparent !important; }

        /* --- Primary --- */
        .btn-outline-primary { color: #0d6efd !important; border-color: #0d6efd !important; background-color: transparent !important; }
        .btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active, .btn-outline-primary.active, .btn-check:active + .btn-outline-primary, .btn-check:checked + .btn-outline-primary { color: #fff !important; background-color: #0d6efd !important; border-color: #0d6efd !important; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.5) !important; }
        .btn-outline-primary:disabled, .btn-outline-primary.disabled { color: #0d6efd !important; border-color: #0d6efd !important; background-color: transparent !important; }

        /* --- Secondary --- */
        .btn-outline-secondary { color: #6c757d !important; border-color: #6c757d !important; background-color: transparent !important; }
        .btn-outline-secondary:hover, .btn-outline-secondary:focus, .btn-outline-secondary:active, .btn-outline-secondary.active, .btn-check:active + .btn-outline-secondary, .btn-check:checked + .btn-outline-secondary { color: #fff !important; background-color: #6c757d !important; border-color: #6c757d !important; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.5) !important; }
        .btn-outline-secondary:disabled, .btn-outline-secondary.disabled { color: #6c757d !important; border-color: #6c757d !important; background-color: transparent !important; }

        /* --- Light --- */
        /* Nota: El botón 'light' está pensado para usarse sobre fondos oscuros */
        .btn-outline-light {
            color: #f8f9fa !important; /* Color texto/borde: Casi blanco */
            border-color: #f8f9fa !important;
            background-color: transparent !important;
        }
        .btn-outline-light:hover,
        .btn-outline-light:focus,
        .btn-outline-light:active,
        .btn-outline-light.active,
        .btn-check:active + .btn-outline-light,
        .btn-check:checked + .btn-outline-light {
            color: #000 !important; /* Texto negro en hover/activo para contraste */
            background-color: #f8f9fa !important; /* Fondo casi blanco */
            border-color: #f8f9fa !important;
            box-shadow: 0 0 0 0.25rem rgba(248, 249, 250, 0.5) !important; /* Sombra clara */
        }
        /* Opcional deshabilitado */
        .btn-outline-light:disabled,
        .btn-outline-light.disabled {
            color: #f8f9fa !important;
            border-color: #f8f9fa !important;
            background-color: transparent !important;
        }

        /* --- Dark --- */
        .btn-outline-dark {
            color: #212529 !important; /* Color texto/borde: Casi negro */
            border-color: #212529 !important;
            background-color: transparent !important;
        }
        .btn-outline-dark:hover,
        .btn-outline-dark:focus,
        .btn-outline-dark:active,
        .btn-outline-dark.active,
        .btn-check:active + .btn-outline-dark,
        .btn-check:checked + .btn-outline-dark {
            color: #fff !important; /* Texto blanco en hover/activo */
            background-color: #212529 !important; /* Fondo casi negro */
            border-color: #212529 !important;
            box-shadow: 0 0 0 0.25rem rgba(33, 37, 41, 0.5) !important; /* Sombra oscura */
        }
        /* Opcional deshabilitado */
        .btn-outline-dark:disabled,
        .btn-outline-dark.disabled {
            color: #212529 !important;
            border-color: #212529 !important;
            background-color: transparent !important;
        }

    </style>

</body>

</html>
