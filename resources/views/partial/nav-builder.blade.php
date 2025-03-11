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
                        <img class="c-sidebar-brand-full pt-3 mt-2 mb-1"
                             src="{{ $logo . (!empty($company_logo) ? $company_logo : 'light_logo.png') }}"
                             height="66" class="navbar-brand-img">
                    @else
                        <img class="c-sidebar-brand-full pt-3 mt-2 mb-1"
                             src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                             height="66" class="navbar-brand-img">
                    @endif
                @else
                    <img class="c-sidebar-brand-full pt-3 mt-2 mb-1"
                         src="{{ $logo . (!empty($company_logo) ? $company_logo : 'dark_logo.png') }}"
                         height="66" class="navbar-brand-img">
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

                @role('admin')
                    <li class="dash-item dash-hasmenu {{ request()->is('settings*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('settings.index') }}">
                            <span class="dash-micon"><i class="ti ti-settings"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Settings') }}</span>
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
                

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('server-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('server.index') }}">
                            <span class="dash-micon"><i class="fa-solid fa-server"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Server') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('servermonitor show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('servermonitor.index') }}">
                            <span class="dash-micon"><i class="bi bi-cpu"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Server Monitor') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('whatsapp show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('whatsapp.notifications') }}">
                            <span class="dash-micon"><i class="bi bi-whatsapp"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Whatsapp Server') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('db-upload-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('server.uploadstats') }}">
                            <span class="dash-micon"><i class="fa-regular fa-circle-up"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Settings Stats Upload') }}</span>
                        </a>
                    </li>
                @endif
                
                <!-- Gestión de usuarios, roles y permisos según permisos del usuario -->
                @can('manage-user')
                    <li class="dash-item dash-hasmenu {{ request()->is('users*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('users.index') }}">
                            <span class="dash-micon"><i class="ti ti-user"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Users') }}</span>
                        </a>
                    </li>
                @endcan
                
                @can('manage-role')
                    <li class="dash-item dash-hasmenu {{ request()->is('roles*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('manage-role.index') }}">
                            <span class="dash-micon"><i class="ti ti-briefcase"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Roles') }}</span>
                        </a>
                    </li>
                @endcan
                
                @can('manage-permission')
                    <li class="dash-item dash-hasmenu {{ request()->is('permission*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('manage-permission.index') }}">
                            <span class="dash-micon"><i class="ti ti-lock"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Permissions') }}</span>
                        </a>
                    </li>
                @endcan


                @if (auth()->user()->hasRole('admin') || auth()->user()->can('customer-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('customers.index') }}">
                            <span class="dash-micon"><i class="fa-regular fa-building"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Customers') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('workers-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('workers-admin.index') }}">
                            <span class="dash-micon"><i class="fa-regular fa-user"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Workers') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('product-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('confections.index') }}">
                            <span class="dash-micon"><i class="fa-regular fa-lemon"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Confections') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('shift-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('shift.index') }}">
                            <span class="dash-micon"><i class="fa-regular fa-clock"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Shift') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('worker-post-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('worker-post.index') }}">
                            <span class="dash-micon"><i class="fa-regular fa-address-book"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Worker Post') }}</span>
                        </a>
                    </li>
                @endif

                @if (auth()->user()->hasRole('admin') || auth()->user()->can('rfid-post-show'))
                    <li class="dash-item dash-hasmenu {{ request()->is('home*') ? 'active' : '' }}">
                        <a class="dash-link" href="{{ route('rfid.post.index') }}">
                            <span class="dash-micon"><i class="fa-regular fa-id-card"></i></span>
                            <span class="dash-mtext custom-weight">{{ __('Asignar Confeccion') }}</span>
                        </a>
                    </li>
                @endif
                <li class="dash-item">
                    <div class="btn btn-custom d-block text-center" style="cursor: default;">
                        <!-- Contenido que desees, por ejemplo, un texto o icono -->
                       
                    </div>
                </li>
                    
                @include('layouts.menu')
            </ul>
        </div>
    </div>
</nav>
