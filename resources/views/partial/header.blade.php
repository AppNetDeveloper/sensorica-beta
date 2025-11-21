@php
use App\Facades\UtilityFacades;
$users = \Auth::user();
$currantLang = $users->currentLanguage();
$languages = UtilityFacades::languages();
$profile = asset(Storage::url('uploads/avatar/'));

// Mapeo de idiomas a códigos de bandera (ISO 3166-1 alpha-2)
$languageFlags = [
    'en' => 'gb',
    'es' => 'es',
    'fr' => 'fr',
    'de' => 'de',
    'it' => 'it',
    'pt' => 'pt',
    'ar' => 'sa',
    'zh' => 'cn',
    'ja' => 'jp',
    'ko' => 'kr',
];
@endphp

<header class="dash-header transprent-bg modern-header">
    <div class="header-wrapper">
        <div class="ms-auto ml-auto">
            <ul class="list-unstyled header-items">
                <!-- Notificaciones -->
                <li class="dropdown dash-h-item header-notifications">
                    <a class="dash-head-link dropdown-toggle arrow-none position-relative"
                       data-bs-toggle="dropdown"
                       href="#"
                       role="button"
                       aria-haspopup="false"
                       aria-expanded="false"
                       title="{{ __('Notifications') }}"
                       data-bs-toggle-second="tooltip"
                       data-bs-placement="bottom">
                        <i class="ti ti-bell"></i>
                        <span class="notification-badge">3</span>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end notification-dropdown">
                        <div class="dropdown-header">
                            <h6 class="mb-0">{{ __('Notifications') }}</h6>
                            <span class="badge bg-primary rounded-pill">3 {{ __('new') }}</span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="notification-list">
                            <!-- Ejemplo de notificaciones -->
                            <a href="#" class="dropdown-item notification-item unread">
                                <div class="notification-icon bg-primary">
                                    <i class="ti ti-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <h6>{{ __('New order assigned') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('2 minutes ago') }}</p>
                                </div>
                            </a>
                            <a href="#" class="dropdown-item notification-item unread">
                                <div class="notification-icon bg-success">
                                    <i class="ti ti-check"></i>
                                </div>
                                <div class="notification-content">
                                    <h6>{{ __('Process completed') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('15 minutes ago') }}</p>
                                </div>
                            </a>
                            <a href="#" class="dropdown-item notification-item unread">
                                <div class="notification-icon bg-warning">
                                    <i class="ti ti-alert-triangle"></i>
                                </div>
                                <div class="notification-content">
                                    <h6>{{ __('Attention required') }}</h6>
                                    <p class="text-muted small mb-0">{{ __('1 hour ago') }}</p>
                                </div>
                            </a>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item text-center text-primary view-all">
                            {{ __('View all notifications') }}
                        </a>
                    </div>
                </li>

                <!-- Selector de idioma con banderas -->
                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none"
                       data-bs-toggle="dropdown"
                       href="#"
                       role="button"
                       aria-haspopup="false"
                       aria-expanded="false"
                       title="{{ __('Language') }}"
                       data-bs-toggle-second="tooltip"
                       data-bs-placement="bottom">
                        <span class="flag-icon flag-icon-{{ $languageFlags[$currantLang] ?? 'gb' }}"></span>
                        <span class="drp-text hide-mob">{{ Str::upper($currantLang) }}</span>
                        <i class="ti ti-chevron-down drp-arrow"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end language-dropdown">
                        @foreach ($languages as $language)
                        <a class="dropdown-item @if ($language == $currantLang) active @endif"
                            href="{{ route('change.language', $language) }}">
                            <span class="flag-icon flag-icon-{{ $languageFlags[$language] ?? 'gb' }}"></span>
                            <span class="ms-2">{{ Str::upper($language) }}</span>
                            @if ($language == $currantLang)
                                <i class="ti ti-check ms-auto"></i>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </li>

                <!-- Perfil de usuario -->
                <li class="dropdown dash-h-item user-dropdown">
                    <a class="dash-head-link custom-header dropdown-toggle arrow-none"
                       data-bs-toggle="dropdown"
                       href="#"
                       role="button"
                       aria-haspopup="false"
                       aria-expanded="false">
                        <img class="user-avatar rounded-circle"
                             width="45"
                             height="45"
                             src="{{ !empty(Auth::user()->avatar) ? $profile . '/' . Auth::user()->avatar : $profile . '/avatar.png' }}"
                             alt="{{ Auth::user()->name }}">
                        <span class="user-info hide-mob">
                            <span class="user-name">{{ Auth::user()->name }}</span>
                            <span class="user-role">{{ Auth::user()->getRoleNames()->first() ?? 'User' }}</span>
                        </span>
                        <i class="ti ti-chevron-down drp-arrow"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end user-menu-dropdown">
                        <div class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <img class="rounded-circle me-2"
                                     width="45"
                                     height="45"
                                     src="{{ !empty(Auth::user()->avatar) ? $profile . '/' . Auth::user()->avatar : $profile . '/avatar.png' }}"
                                     alt="{{ Auth::user()->name }}">
                                <div>
                                    <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                                    <small class="text-muted">{{ Auth::user()->email }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('profile') }}" class="dropdown-item">
                            <i class="ti ti-user me-2"></i>
                            <span>{{ __('Profile') }}</span>
                        </a>
                        @role('admin')
                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                            <i class="ti ti-settings me-2"></i>
                            <span>{{ __('Settings') }}</span>
                        </a>
                        @endrole
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                            class="dropdown-item text-danger">
                            <i class="ti ti-power me-2"></i> {{ __('Logout') }}
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</header>
