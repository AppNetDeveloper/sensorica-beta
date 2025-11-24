@php
// Verifica si el usuario está autenticado
if (!Auth::check()) {
    // Si el usuario no está autenticado, redirige a la página de login
    echo redirect()->route('login')->send();
    exit; // Detiene la ejecución del script después de redirigir
}

// Si está autenticado, continúa ejecutando el resto del código
$users = Auth::user();
$currantLang = $users->currentLanguage();
$logo = asset(Storage::url('uploads/logo/'));
$settings = Utility::settings();
@endphp

<nav class="dash-sidebar light-sidebar transprent-bg">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('home') }}" class="b-brand">
                <!-- ======== Cambiar el logo aquí ============ -->
                @if (isset($settings['dark_mode']))
                    @if ($settings['dark_mode'] == 'on')
                        <img class="c-sidebar-brand-full pt-2 mt-1 mb-1 navbar-brand-img"
                             src="{{ $logo . (!empty($company_logo) ? $company_logo : 'light_logo.png') }}"
                             alt="Logo"
                             style="height: 80px !important; width: auto !important; max-width: 100% !important; object-fit: contain !important;">
                    @else
                        <img class="c-sidebar-brand-full pt-2 mt-1 mb-1 navbar-brand-img"
                             src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                             alt="Logo"
                             style="height: 80px !important; width: auto !important; max-width: 100% !important; object-fit: contain !important;">
                    @endif
                @else
                    <img class="c-sidebar-brand-full pt-2 mt-1 mb-1 navbar-brand-img"
                         src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                         alt="Logo"
                         style="height: 80px !important; width: auto !important; max-width: 100% !important; object-fit: contain !important;">
                @endif
            </a>
        </div>

        <div class="navbar-content active dash-trigger ps ps--active-y">
            <ul class="dash-navbar" style="display: block;">
                <li class="dash-item dash-hasmenu {{ request()->is('/') ? 'active' : '' }}">
                    <a class="dash-link" href="{{ url('/') }}">
                        <span class="dash-micon"><i class="ti ti-home"></i></span>
                        <span class="dash-mtext custom-weight">{{ __('Dashboard') }}</span>
                    </a>
                </li>

                {{-- Enlace para transportistas --}}
                @can('deliveries-view')
                    <li class="dash-item dash-hasmenu {{ request()->is('my-deliveries*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('deliveries.my-deliveries') }}" title="{{ __('My Deliveries') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-truck-delivery"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('My Deliveries') }}</span>
                        </a>
                    </li>
                @endcan

                @role('admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('settings*') ? 'active' : '' }}">
                        <a class="dash-link" title="{{ __('Settings') }}" href="{{ route('settings.index') }}">
                            <span class="dash-micon"><i class="ti ti-settings"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Settings') }}</span>
                        </a>
                    </li>
                @endrole
                @role('admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('ia_prompts*') ? 'active' : '' }}">
                        <a class="dash-link" title="{{ __('IA') }}" href="{{ route('ia_prompts.index') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="fas fa-wand-magic-sparkles"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('IA') }}</span>
                        </a>
                    </li>
                @endrole
                
                @can('manage-langauge')
                    <li class="dash-item dash-hasmenu {{ request()->is('index') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('index') }}">
                            <span class="dash-micon"><i class="ti ti-world"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Language') }}</span>
                        </a>
                    </li>
                @endcan
                

                {{-- Separador: Sistema --}}
                <li class="dash-item">
                    <div class="dash-category-header">
                        <span class="dash-category-text">{{ __('System') }}</span>
                    </div>
                </li>

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('server-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('server.index') }}" title="{{ __('Server') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-server"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Server') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('servermonitor show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('servermonitor.index') }}" title="{{ __('Server Monitor') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-activity"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Server Monitor') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('whatsapp show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('whatsapp.notifications') }}" title="{{ __('Whatsapp Server') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-brand-whatsapp"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Whatsapp Server') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('telegram show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('telegram.index') }}" title="{{ __('Telegram Server') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-brand-telegram"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Telegram Server') }}</span>
                        </a>
                    </li>
                @endif



                {{-- Separador: Gestión de Usuarios --}}
                <li class="dash-item">
                    <div class="dash-category-header">
                        <span class="dash-category-text">{{ __('User Management') }}</span>
                    </div>
                </li>

                <!-- Gestión de usuarios, roles y permisos según permisos del usuario -->
                @can('show-user')
                    <li class="dash-item dash-hasmenu {{ request()->is('users*') ? 'active' : '' }}">
                        <a class="dash-link" title="{{ __('Users') }}" href="{{ route('users.index') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-users"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Users') }}</span>
                        </a>
                    </li>
                @endcan

                @can('manage-role')
                    <li class="dash-item dash-hasmenu {{ request()->is('roles*') ? 'active' : '' }}">
                        <a class="dash-link" title="{{ __('Roles') }}" href="{{ route('manage-role.index') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-shield-check"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Roles') }}</span>
                        </a>
                    </li>
                @endcan

                @can('manage-permission')
                    <li class="dash-item dash-hasmenu {{ request()->is('permission*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('manage-permission.index') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="{{ __('Permissions') }}">
                            <span class="dash-micon"><i class="ti ti-lock"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Permissions') }}</span>
                        </a>
                    </li>
                @endcan


                {{-- Separador: Gestión de Producción --}}
                <li class="dash-item">
                    <div class="dash-category-header">
                        <span class="dash-category-text">{{ __('Production') }}</span>
                    </div>
                </li>

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('customer-show') || auth()->user()->can('productionline-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('customers.index') }}" title="{{ __('Customers') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-building"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Customers') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('workers-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('workers-admin.index') }}" title="{{ __('Workers') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-user-check"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Workers') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('product-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('confections*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('confections.index') }}" title="{{ __('Confections') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-lemon"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Confections') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('process-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('processes*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('processes.index') }}" title="{{ __('Processes') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-settings-automation"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Processes') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('article-family-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('article-families*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('article-families.index') }}" title="{{ __('Article Families') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-folders"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Article Families') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('shift-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('shift.index') }}" title="{{ __('Shift') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-clock"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Shift') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('worker-post-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('worker-post.index') }}" title="{{ __('Worker Post') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-briefcase"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Worker Post') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('rfid-post-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('rfid.post.index') }}" title="{{ __('Asignar Confeccion') }}" data-bs-toggle="tooltip" data-bs-placement="right">
                            <span class="dash-micon"><i class="ti ti-tag"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Asignar Confeccion') }}</span>
                        </a>
                    </li>
                @endif
                @include('layouts.menu')
            </ul>

            <!-- Botón minimizador del sidebar con icono moderno -->
            <div id="sidebar-minimizer" class="dash-sidebar-minimizer" aria-label="Minimizar sidebar">
                <i class="ti ti-chevrons-left sidebar-toggle-icon"></i>
            </div>
            
            <!-- Script inline para manejar el clic en el botón minimizador y generar tooltips automáticamente -->
            <script>
                // Eliminar cualquier event listener previo para evitar duplicados
                document.removeEventListener('click', window.sidebarClickHandler);
                
                // Función para alternar el estado del sidebar y guardarlo en localStorage
                function toggleSidebar(event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    // Toggle de la clase en el body
                    document.body.classList.toggle('dash-minimenu');
                    
                    // Guardar el estado en localStorage
                    if (document.body.classList.contains('dash-minimenu')) {
                        localStorage.setItem('dashSidebarState', 'minimized');
                    } else {
                        localStorage.setItem('dashSidebarState', 'expanded');
                    }
                    
                    // Cambiar el icono según el estado
                    updateMinimizeIcon();
                }
                
                // Función para actualizar el icono según el estado
                function updateMinimizeIcon() {
                    const icon = document.querySelector('#sidebar-minimizer .sidebar-toggle-icon');
                    if (icon) {
                        if (document.body.classList.contains('dash-minimenu')) {
                            icon.className = 'ti ti-chevrons-right sidebar-toggle-icon';
                        } else {
                            icon.className = 'ti ti-chevrons-left sidebar-toggle-icon';
                        }
                    }
                }
                
                // Asignar el event listener directamente al botón
                const minimizer = document.getElementById('sidebar-minimizer');
                if (minimizer) {
                    // Eliminar cualquier event listener previo
                    minimizer.removeEventListener('click', toggleSidebar);
                    // Añadir el nuevo event listener
                    minimizer.addEventListener('click', toggleSidebar);
                }
                
                // Generar tooltips automáticamente para todos los enlaces del menú
                document.querySelectorAll('.dash-sidebar .dash-link').forEach(function(link) {
                    // Si no tiene ya un atributo title, añadirlo basado en el texto del menú
                    if (!link.hasAttribute('title')) {
                        const menuText = link.querySelector('.dash-mtext');
                        if (menuText) {
                            link.setAttribute('title', menuText.textContent.trim());
                        }
                    }
                });
                
                // Cargar el estado guardado inmediatamente
                (function() {
                    const savedState = localStorage.getItem('dashSidebarState');
                    if (savedState === 'minimized') {
                        document.body.classList.add('dash-minimenu');
                        updateMinimizeIcon();
                    }
                })();
            </script>
        </div>
    </div>
</nav>
