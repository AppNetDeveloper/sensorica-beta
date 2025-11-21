@php
$logo = asset(Storage::url('uploads/logo/'));
$currentYear = date('Y');
@endphp

<footer class="modern-footer">
    <div class="footer-container">
        <div class="footer-content">
            <!-- Columna 1: Logo y descripción -->
            <div class="footer-column footer-brand">
                <img src="{{ $logo . 'light_logo.png' }}" class="footer-logo" alt="{{ config('app.name') }}">
                <p class="footer-description">
                    {{ __('Solución integral para la gestión y control de producción.') }}
                </p>
                <div class="footer-social">
                    <a href="#" class="social-link" title="Facebook">
                        <i class="ti ti-brand-facebook"></i>
                    </a>
                    <a href="#" class="social-link" title="Twitter">
                        <i class="ti ti-brand-twitter"></i>
                    </a>
                    <a href="#" class="social-link" title="LinkedIn">
                        <i class="ti ti-brand-linkedin"></i>
                    </a>
                    <a href="#" class="social-link" title="Instagram">
                        <i class="ti ti-brand-instagram"></i>
                    </a>
                </div>
            </div>

            <!-- Columna 2: Enlaces rápidos -->
            <div class="footer-column">
                <h6 class="footer-title">{{ __('Quick Links') }}</h6>
                <ul class="footer-links">
                    <li><a href="{{ url('/') }}"><i class="ti ti-home"></i> {{ __('Dashboard') }}</a></li>
                    @can('show-user')
                    <li><a href="{{ route('users.index') }}"><i class="ti ti-users"></i> {{ __('Users') }}</a></li>
                    @endcan
                    @can('manage-role')
                    <li><a href="{{ route('manage-role.index') }}"><i class="ti ti-shield-check"></i> {{ __('Roles') }}</a></li>
                    @endcan
                    @role('admin')
                    <li><a href="{{ route('settings.index') }}"><i class="ti ti-settings"></i> {{ __('Settings') }}</a></li>
                    @endrole
                </ul>
            </div>

            <!-- Columna 3: Soporte -->
            <div class="footer-column">
                <h6 class="footer-title">{{ __('Support') }}</h6>
                <ul class="footer-links">
                    <li><a href="#"><i class="ti ti-help"></i> {{ __('Help Center') }}</a></li>
                    <li><a href="#"><i class="ti ti-file-text"></i> {{ __('Documentation') }}</a></li>
                    <li><a href="#"><i class="ti ti-mail"></i> {{ __('Contact Us') }}</a></li>
                    <li><a href="#"><i class="ti ti-question-mark"></i> {{ __('FAQ') }}</a></li>
                </ul>
            </div>

            <!-- Columna 4: Información -->
            <div class="footer-column">
                <h6 class="footer-title">{{ __('Information') }}</h6>
                <ul class="footer-info">
                    <li>
                        <i class="ti ti-versions"></i>
                        <span>{{ __('Version') }}: <strong>2.0.0</strong></span>
                    </li>
                    <li>
                        <i class="ti ti-calendar"></i>
                        <span>{{ __('Updated') }}: <strong>{{ date('M Y') }}</strong></span>
                    </li>
                    <li>
                        <i class="ti ti-users"></i>
                        <span>{{ __('Users') }}: <strong>{{ \App\Models\User::count() }}</strong></span>
                    </li>
                    <li>
                        <i class="ti ti-server"></i>
                        <span>{{ __('Status') }}: <strong class="text-success">{{ __('Online') }}</strong></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p class="footer-copyright">
                    &copy; {{ $currentYear }} <strong>{{ config('app.name') }}</strong>. {{ __('All rights reserved.') }}
                </p>
                <div class="footer-bottom-links">
                    <a href="#">{{ __('Privacy Policy') }}</a>
                    <span class="separator">•</span>
                    <a href="#">{{ __('Terms of Service') }}</a>
                    <span class="separator">•</span>
                    <a href="#">{{ __('Cookies') }}</a>
                </div>
            </div>
        </div>
    </div>
</footer>
