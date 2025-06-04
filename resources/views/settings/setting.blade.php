@extends('layouts.admin')
@section('title', __('Settings'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Settings') }}</li>
    </ul>
@endsection

@php
    use App\Facades\UtilityFacades;
    $settings = UtilityFacades::settings();
    $languages = UtilityFacades::languages();
    $lang = UtilityFacades::getValByName('default_language');
    $timezones = config('timezones');
    $logo = asset(Storage::url('uploads/logo/'));
    // Definir color primario
    $color = isset($settings['color']) && $settings['color'] != "" ? $settings['color'] : 'theme-1';
    // Definir modo oscuro
    $dark_mode = isset($settings['dark_mode']) && $settings['dark_mode'] != "" ? $settings['dark_mode'] : "";
    // Si $mail_config no está definido, se asigna un arreglo con los valores actuales
    $mail_config = $mail_config ?? [
        'mail_driver'      => config('mail.default'), // o config('mail.mailer') si lo prefieres
        'mail_host'        => config('mail.mailers.smtp.host'),
        'mail_port'        => config('mail.mailers.smtp.port'),
        'mail_username'    => config('mail.mailers.smtp.username'),
        'mail_password'    => config('mail.mailers.smtp.password'),
        'mail_encryption'  => config('mail.mailers.smtp.encryption') === null ? 'null' : config('mail.mailers.smtp.encryption'),
        'mail_from_address'=> config('mail.from.address'),
        'mail_from_name'   => config('mail.from.name'),
    ];

@endphp

@section('content')
    <!-- [ Main Content ] start -->
    <div class="row">
        <!-- [ sample-page ] start -->
        <div class="col-sm-12">
            <div class="row">
                {{-- Menú lateral --}}
                <div class="col-xl-3">
                    <div class="card sticky-top">
                        <div class="list-group list-group-flush" id="useradd-sidenav">
                            <a href="settings#useradd-1" class="list-group-item list-group-item-action useradd-1 active">
                                {{ __('App Setting') }} <div class="float-end"></div>
                            </a>
                            <a href="settings#useradd-2" class="list-group-item list-group-item-action useradd-2">
                                {{ __('General') }} <div class="float-end"></div>
                            </a>
                            <a href="settings#useradd-3-5" class="list-group-item list-group-item-action useradd-3-5">
                                {{ __('Lector RFID') }} <div class="float-end"></div>
                            </a>
                            <a href="settings#useradd-3-6" class="list-group-item list-group-item-action useradd-3-6">
                                {{ __('Redis') }} <div class="float-end"></div>
                            </a>
                            <a href="#useradd-3-8" class="list-group-item list-group-item-action useradd-3-8">
                                {{ __('Base de Datos Réplica') }} <div class="float-end"></div>
                            </a>
                            <a href="#useradd-3-7" class="list-group-item list-group-item-action useradd-3-7">
                                {{ __('Upload Stats Settings') }} <div class="float-end"></div>
                            </a>
                            <a href="settings#useradd-3" class="list-group-item list-group-item-action useradd-3">
                                {{ __('Email') }} <div class="float-end"></div>
                            </a>
                            <a href="settings#useradd-4" class="list-group-item list-group-item-action useradd-4">
                                {{ __('Finish Shift Email Settings') }} <div class="float-end"></div>
                            </a>
                        </div>
                    </div>
                </div>
                {{-- Contenido principal --}}
                <div class="col-xl-9">
                    {{-- Sección: App Settings --}}
                    <div id="useradd-1" class="card mb-4">
                        {{ Form::open(['route' => ['settings.logo'], 'method' => 'POST', 'enctype' => 'multipart/form-data']) }}
                        <div class="card-header">
                            <h5>{{ __('App Settings') }}</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ __('App Name') }} {{ __('& App Logo') }}</p>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        {{ Form::label('name', __('Application Name'), ['class' => 'form-label']) }}
                                        <input type="text" name="app_name" class="form-control" value="{{ config('app.name') }}">
                                    </div>
                                </div>
                                <div class="col-lg-12 mt-3">
                                    <div class="form-group">
                                        {{ Form::label('dark_logo', __('Dark Logo'), ['class' => 'form-label']) }}
                                        <div class="bg-light text-center">
                                            {!! Form::image($logo . '/dark_logo.png', null, ['class' => 'img-responsive my-2 text-center logo_img']) !!}
                                        </div>
                                        {!! Form::file('dark_logo', ['class' => 'form-control', 'accept' => 'image/png']) !!}
                                    </div>
                                </div>
                                <div class="col-lg-12 mt-3">
                                    <div class="form-group">
                                        {{ Form::label('light_logo', __('Light Logo'), ['class' => 'form-label']) }}
                                        <div class="bg-dark text-center">
                                            {!! Form::image($logo . '/light_logo.png', null, ['class' => 'img-responsive my-2 text-center logo_img']) !!}
                                        </div>
                                        {!! Form::file('light_logo', ['class' => 'form-control', 'accept' => 'image/png']) !!}
                                    </div>
                                </div>
                                <div class="col-lg-12 mt-3">
                                    <div class="form-group">
                                        {{ Form::label('favicon', __('Favicon'), ['class' => 'form-label']) }}
                                        <div class="bg-light text-center">
                                            {!! Form::image($logo . '/favicon.png', null, ['class' => 'img-responsive my-2 text-center logo_img']) !!}
                                        </div>
                                        {!! Form::file('favicon', ['class' => 'form-control', 'accept' => 'image/png']) !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-whitesmoke mb-5">
                            <span class="float-end">
                                <button class="btn btn-primary" type="submit" id="save-btn">{{ __('Save Changes') }}</button>
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary me-1">{{ __('Cancel') }}</a>
                            </span>
                        </div>
                        {{ Form::close() }}
                    </div>
                    {{-- Sección: General Setting --}}
                    <div id="useradd-2" class="card mb-4">
                        {{ Form::open(['route' => 'settings.datetime', 'method' => 'post']) }}
                        <div class="card-header">
                            <h5>{{ __('General Setting') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group row">
                                <div class="col-md-8">
                                    <strong class="d-block">{{ __('Two Factor Authentication') }}</strong>
                                    {{ @$settings['authentication'] != 'deactivate' ? __('Activate') : __('Deactivate') }}
                                    {{ __('Two factor authentication for application.') }}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-switch mt-2 float-end custom-switch-v1">
                                        <input type="checkbox" name="authentication" class="form-check-input input-primary" {{ @$settings['authentication'] != 'deactivate' ? 'checked' : '' }}>
                                    </label>
                                </div>
                                @if (!extension_loaded('imagick'))
                                    <small>
                                        {{ __('Note: for 2FA your server must have Imagick.') }}
                                        <a href="https://www.php.net/manual/en/book.imagick.php" target="_new">{{ __('Imagick Document') }}</a>
                                    </small>
                                @endif
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <strong class="d-block">{{ __('RTL Setting') }}</strong>
                                    {{ env('SITE_RTL') == 'on' ? __('Deactivate') : __('Activate') }}
                                    {{ __('RTL setting for application.') }}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-switch mt-2 float-end custom-switch-v1">
                                        <input type="checkbox" name="SITE_RTL" id="site_rtl" class="form-check-input input-primary" {{ env('SITE_RTL') == 'on' ? 'checked="checked"' : '' }}>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <strong class="d-block">{{ __('Dark Layout') }}</strong>
                                    {{ @$settings['dark_mode'] == 'on' ? __('Deactivate') : __('Activate') }}
                                    {{ __('Dark Layout for application.') }}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-switch mt-2 float-end custom-switch-v1">
                                        <input type="checkbox" name="dark_mode" id="cust-darklayout" class="form-check-input input-primary" @if(@$settings['dark_mode'] == 'on') checked @endif>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <strong class="d-block">{{ __('Primary color settings') }}</strong>
                                </div>
                                <div class="col-md-4">
                                    <div class="theme-color themes-color float-end">
                                        <a href="settings#!" class="{{ $color == 'theme-1' ? 'active_color' : '' }}" data-value="theme-1" onclick="check_theme('theme-1')"></a>
                                        <input type="radio" class="theme_color" name="color" value="theme-1" style="display: none;">
                                        <a href="settings#!" class="{{ $color == 'theme-2' ? 'active_color' : '' }}" data-value="theme-2" onclick="check_theme('theme-2')"></a>
                                        <input type="radio" class="theme_color" name="color" value="theme-2" style="display: none;">
                                        <a href="settings#!" class="{{ $color == 'theme-3' ? 'active_color' : '' }}" data-value="theme-3" onclick="check_theme('theme-3')"></a>
                                        <input type="radio" class="theme_color" name="color" value="theme-3" style="display: none;">
                                        <a href="settings#!" class="{{ $color == 'theme-4' ? 'active_color' : '' }}" data-value="theme-4" onclick="check_theme('theme-4')"></a>
                                        <input type="radio" class="theme_color" name="color" value="theme-4" style="display: none;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                {{ Form::label('app_url', __('APP URL'), ['class' => 'form-label text-dark']) }}
                                {{ Form::text('app_url', env('APP_URL'), ['class' => 'form-control', 'placeholder' => 'https://example.com']) }}
                                <small class="form-text text-muted">{{ __('The base URL of your application') }}</small>
                            </div>

                            <div class="form-group mt-3">
                                {{ Form::label('asset_url', __('Asset URL'), ['class' => 'form-label text-dark']) }}
                                {{ Form::text('asset_url', env('ASSET_URL', ''), ['class' => 'form-control', 'placeholder' => 'https://example.com']) }}
                                <small class="form-text text-muted">{{ __('The base URL for assets (leave empty to use APP_URL)') }}</small>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('Database Configuration') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('db_connection', __('Connection'), ['class' => 'form-label']) }}
                                                {{ Form::select('db_connection', ['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlsrv' => 'SQL Server'], env('DB_CONNECTION', 'mysql'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('db_host', __('Host'), ['class' => 'form-label']) }}
                                                {{ Form::text('db_host', env('DB_HOST', '127.0.0.1'), ['class' => 'form-control', 'placeholder' => '127.0.0.1']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('db_port', __('Port'), ['class' => 'form-label']) }}
                                                {{ Form::number('db_port', env('DB_PORT', '3306'), ['class' => 'form-control', 'placeholder' => '3306']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('db_database', __('Database Name'), ['class' => 'form-label']) }}
                                                {{ Form::text('db_database', env('DB_DATABASE', ''), ['class' => 'form-control', 'placeholder' => 'database_name']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('db_username', __('Username'), ['class' => 'form-label']) }}
                                                {{ Form::text('db_username', env('DB_USERNAME', ''), ['class' => 'form-control', 'placeholder' => 'db_user']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('db_password', __('Password'), ['class' => 'form-label']) }}
                                                <div class="input-group">
                                                    {{ Form::password('db_password', ['class' => 'form-control', 'placeholder' => '••••••••', 'value' => env('DB_PASSWORD', '')]) }}
                                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        {{ __('Warning: Changing these settings may break your application if not configured correctly.') }}
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                {{ Form::label('timezone', __('Timezone'), ['class' => 'form-label text-dark']) }}
                                <select name="timezone" class="form-control" data-trigger id="choices-single-default">
                                    <option value="">{{ __('Select Timezone') }}</option>
                                    @foreach ($timezones as $k => $timezone)
                                        <option value="{{ $k }}" {{ env('APP_TIMEZONE') == $k ? 'selected' : '' }}>{{ $timezone }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- MQTT Configuration -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('MQTT Configuration') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('mqtt_server', __('MQTT Server'), ['class' => 'form-label']) }}
                                                {{ Form::text('mqtt_server', env('MQTT_SERVER'), ['class' => 'form-control', 'placeholder' => 'mqtt.example.com']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('mqtt_port', __('MQTT Port'), ['class' => 'form-label']) }}
                                                {{ Form::number('mqtt_port', env('MQTT_PORT', '1883'), ['class' => 'form-control', 'placeholder' => '1883']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('mqtt_sensorica_server', __('Sensorica Server'), ['class' => 'form-label']) }}
                                                {{ Form::text('mqtt_sensorica_server', env('MQTT_SENSORICA_SERVER'), ['class' => 'form-control', 'placeholder' => '127.0.0.1']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('mqtt_sensorica_port', __('Sensorica Port'), ['class' => 'form-label']) }}
                                                {{ Form::number('mqtt_sensorica_port', env('MQTT_SENSORICA_PORT', '1883'), ['class' => 'form-control', 'placeholder' => '1883']) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('mqtt_sensorica_server_backup', __('Sensorica Backup Server'), ['class' => 'form-label']) }}
                                                {{ Form::text('mqtt_sensorica_server_backup', env('MQTT_SENSORICA_SERVER_BACKUP'), ['class' => 'form-control', 'placeholder' => 'backup.example.com']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('mqtt_sensorica_port_backup', __('Sensorica Backup Port'), ['class' => 'form-label']) }}
                                                {{ Form::number('mqtt_sensorica_port_backup', env('MQTT_SENSORICA_PORT_BACKUP', '1883'), ['class' => 'form-control', 'placeholder' => '1883']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Backup Configuration -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('Backup Configuration') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('backup_archive_password', __('Backup Password'), ['class' => 'form-label']) }}
                                                {{ Form::password('backup_archive_password', ['class' => 'form-control', 'value' => env('BACKUP_ARCHIVE_PASSWORD', '')]) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('backup_archive_encryption', __('Backup Encryption'), ['class' => 'form-label']) }}
                                                {{ Form::select('backup_archive_encryption', ['' => 'None', 'aes-256-cbc' => 'AES-256-CBC'], env('BACKUP_ARCHIVE_ENCRYPTION', ''), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- SFTP Configuration -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('SFTP Configuration') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('sftp_host', __('SFTP Host'), ['class' => 'form-label']) }}
                                                {{ Form::text('sftp_host', env('SFTP_HOST'), ['class' => 'form-control', 'placeholder' => 'sftp.example.com']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('sftp_username', __('SFTP Username'), ['class' => 'form-label']) }}
                                                {{ Form::text('sftp_username', env('SFTP_USERNAME'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('sftp_port', __('SFTP Port'), ['class' => 'form-label']) }}
                                                {{ Form::number('sftp_port', env('SFTP_PORT', '22'), ['class' => 'form-control', 'placeholder' => '22']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('sftp_password', __('SFTP Password'), ['class' => 'form-label']) }}
                                                <div class="input-group">
                                                    <input type="password" name="sftp_password" class="form-control" value="{{ env('SFTP_PASSWORD', '') }}" autocomplete="off">
                                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group mt-3">
                                        {{ Form::label('sftp_root', __('SFTP Root Path'), ['class' => 'form-label']) }}
                                        {{ Form::text('sftp_root', env('SFTP_ROOT', '/var/www/ftp/'), ['class' => 'form-control', 'placeholder' => '/path/to/root']) }}
                                    </div>
                                </div>
                            </div>

                            <!-- System Settings -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('System Settings') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('shift_time', __('Shift Time'), ['class' => 'form-label']) }}
                                                {{ Form::time('shift_time', env('SHIFT_TIME', '08:00:00'), ['class' => 'form-control']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('production_min_time', __('Min Production Time (sec)'), ['class' => 'form-label']) }}
                                                {{ Form::number('production_min_time', env('PRODUCTION_MIN_TIME', '3'), ['class' => 'form-control', 'min' => '1']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('production_max_time', __('Max Production Time (sec)'), ['class' => 'form-label']) }}
                                                {{ Form::number('production_max_time', env('PRODUCTION_MAX_TIME', '5'), ['class' => 'form-control', 'min' => '1']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('clear_db_day', __('Clear DB After (days)'), ['class' => 'form-label']) }}
                                                {{ Form::number('clear_db_day', env('CLEAR_DB_DAY', '40'), ['class' => 'form-control', 'min' => '1']) }}
                                            </div>
                                            <div class="form-group mt-3">
                                                {{ Form::label('production_min_time_weight', __('Min Production Weight (kg)'), ['class' => 'form-label']) }}
                                                {{ Form::number('production_min_time_weight', env('PRODUCTION_MIN_TIME_WEIGHT', '30'), ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- External API Settings -->
                            <div id="external-api-settings" class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('External API Settings') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <div class="form-check form-switch">
                                                    @php
                                                        $useCurl = env('USE_CURL', 'true');
                                                        $isChecked = ($useCurl === 'true' || $useCurl === true);
                                                    @endphp
                                                    {{ Form::checkbox('use_curl', '1', $isChecked, ['class' => 'form-check-input', 'id' => 'use_curl']) }}
                                                    {{ Form::label('use_curl', __('Use cURL for External Requests'), ['class' => 'form-check-label']) }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {{ Form::label('external_api_queue_type', __('Request Type'), ['class' => 'form-label']) }}
                                                {{ Form::select('external_api_queue_type', ['get' => 'GET', 'post' => 'POST', 'put' => 'PUT', 'delete' => 'DELETE'], env('EXTERNAL_API_QUEUE_TYPE', 'put'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {{ Form::label('external_api_queue_model', __('Data Model'), ['class' => 'form-label']) }}
                                                {{ Form::text('external_api_queue_model', env('EXTERNAL_API_QUEUE_MODEL', 'dataToSend3'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RFID Settings -->
                            <div id="rfid-settings" class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('RFID Settings') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        @php
                                            $rfidAutoAdd = env('RFID_AUTO_ADD', 'true');
                                            $isRfidChecked = ($rfidAutoAdd === 'true' || $rfidAutoAdd === true);
                                        @endphp
                                        {{ Form::checkbox('rfid_auto_add', '1', $isRfidChecked, ['class' => 'form-check-input', 'id' => 'rfid_auto_add']) }}
                                        {{ Form::label('rfid_auto_add', __('Auto Add RFID Tags'), ['class' => 'form-check-label']) }}
                                    </div>
                                    <small class="text-muted">{{ __('Automatically add new RFID tags to the system when scanned') }}</small>
                            <!-- Local Server Settings -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('Local Server Settings') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('local_server', __('Local Server URL'), ['class' => 'form-label']) }}
                                                {{ Form::url('local_server', env('LOCAL_SERVER', 'http://127.0.0.1/'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('token_system', __('System Token'), ['class' => 'form-label']) }}
                                                <div class="input-group">
                                                    {{ Form::text('token_system', env('TOKEN_SYSTEM'), ['class' => 'form-control', 'id' => 'token_system']) }}
                                                    <button class="btn btn-outline-secondary" type="button" id="copyTokenBtn">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('tcp_server', __('TCP Server'), ['class' => 'form-label']) }}
                                                {{ Form::text('tcp_server', env('TCP_SERVER', 'localhost'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('tcp_port', __('TCP Port'), ['class' => 'form-label']) }}
                                                {{ Form::number('tcp_port', env('TCP_PORT', '8000'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Production Settings -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('Production Settings') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {{ Form::label('shift_time', __('Shift Start Time'), ['class' => 'form-label']) }}
                                                {{ Form::time('shift_time', env('SHIFT_TIME', '08:00:00'), ['class' => 'form-control']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {{ Form::label('production_min_time', __('Min Production Time (min)'), ['class' => 'form-label']) }}
                                                {{ Form::number('production_min_time', env('PRODUCTION_MIN_TIME', '3'), ['class' => 'form-control', 'min' => '1']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {{ Form::label('production_max_time', __('Max Production Time (min)'), ['class' => 'form-label']) }}
                                                {{ Form::number('production_max_time', env('PRODUCTION_MAX_TIME', '5'), ['class' => 'form-control', 'min' => '1']) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('clear_db_day', __('Clear Old Data (days)'), ['class' => 'form-label']) }}
                                                <div class="input-group">
                                                    {{ Form::number('clear_db_day', env('CLEAR_DB_DAY', '40'), ['class' => 'form-control', 'min' => '1']) }}
                                                    <span class="input-group-text">{{ __('days') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                {{ Form::label('production_min_time_weight', __('Min Production Weight (kg)'), ['class' => 'form-label']) }}
                                                <div class="input-group">
                                                    {{ Form::number('production_min_time_weight', env('PRODUCTION_MIN_TIME_WEIGHT', '30'), ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                                    <span class="input-group-text">kg</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- WhatsApp Configuration -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">{{ __('WhatsApp Configuration') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                {{ Form::label('whatsapp_link', __('WhatsApp Server URL'), ['class' => 'form-label']) }}
                                                {{ Form::url('whatsapp_link', env('WHATSAPP_LINK'), ['class' => 'form-control', 'placeholder' => 'http://127.0.0.1:3005']) }}
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {{ Form::label('whatsapp_phone_not', __('Notification Phone'), ['class' => 'form-label']) }}
                                                {{ Form::text('whatsapp_phone_not', env('WHATSAPP_PHONE_NOT'), ['class' => 'form-control', 'placeholder' => '34619929305']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-3">
                                {{ Form::label('site_date_format', __('Date Format'), ['class' => 'form-label text-dark']) }}
                                <select name="site_date_format" class="form-control" data-trigger id="choices-single-default">
                                    <option value="M j, Y" @if(@$settings['site_date_format'] == 'M j, Y') selected="selected" @endif>Jan 1, 2015</option>
                                    <option value="d-m-Y" @if(@$settings['site_date_format'] == 'd-m-Y') selected="selected" @endif>d-m-y</option>
                                    <option value="m-d-Y" @if(@$settings['site_date_format'] == 'm-d-Y') selected="selected" @endif>m-d-y</option>
                                    <option value="Y-m-d" @if(@$settings['site_date_format'] == 'Y-m-d') selected="selected" @endif>y-m-d</option>
                                </select>
                            </div>
                            <div class="form-group mt-3">
                                {{ Form::label('default_language', __('Default Language'), ['class' => 'form-label']) }}
                                <div class="changeLanguage">
                                    <select name="default_language" id="choices-single-default" class="form-control" data-trigger>
                                        @foreach ($languages as $language)
                                            <option value="{{ $language }}" {{ $lang == $language ? 'selected' : '' }}>{{ Str::upper($language) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-whitesmoke mb-5">
                            <span class="float-end">
                                <button class="btn btn-primary" type="submit" id="save-btn">{{ __('Save Changes') }}</button>
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary me-1">{{ __('Cancel') }}</a>
                            </span>
                        </div>
                        {{ Form::close() }}
                    </div>
                    {{-- Sección: Configuración Lector RFID --}}
                    <div id="useradd-3-5" class="card mb-4">
                        {{ Form::open(['route' => 'settings.rfid', 'method' => 'post']) }}
                        <div class="card-header">
                            <h5>{{ __('Configuración Lector RFID') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('rfid_reader_ip', __('IP del Lector RFID'), ['class' => 'form-label']) }}
                                        {{ Form::text('rfid_reader_ip', $rfid_config['rfid_reader_ip'] ?? '', ['class' => 'form-control', 'placeholder' => __('Ej: 192.168.1.100')]) }}
                                        <small class="form-text text-muted">
                                            {{ __('Dirección IP del lector RFID en la red local') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('rfid_reader_port', __('Puerto del Lector RFID'), ['class' => 'form-label']) }}
                                        {{ Form::number('rfid_reader_port', $rfid_config['rfid_reader_port'] ?? '1080', ['class' => 'form-control', 'placeholder' => __('Ej: 1080'), 'min' => '1', 'max' => '65535']) }}
                                        <small class="form-text text-muted">
                                            {{ __('Puerto TCP del servicio del lector RFID (por defecto: 1080)') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('rfid_monitor_url', __('URL del Monitor de Antena RFID'), ['class' => 'form-label']) }}
                                        {{ Form::url('rfid_monitor_url', $rfid_config['rfid_monitor_url'] ?? '', ['class' => 'form-control', 'placeholder' => __('Ej: http://192.168.1.100:3000/')]) }}
                                        <small class="form-text text-muted">
                                            {{ __('URL completa del monitor de antena RFID (opcional)') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-whitesmoke">
                            <span class="float-end">
                                <button class="btn btn-primary" type="submit" id="save-rfid-btn">{{ __('Guardar Configuración RFID') }}</button>
                            </span>
                        </div>
                        {{ Form::close() }}
                    </div>
                    {{-- Sección: Configuración Redis --}}
                    <div id="useradd-3-6" class="card mb-4">
                        {{ Form::open(['route' => 'settings.redis', 'method' => 'post']) }}
                        <div class="card-header">
                            <h5>{{ __('Configuración de Redis') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('redis_host', __('Redis Host'), ['class' => 'form-label']) }}
                                        {{ Form::text('redis_host', env('REDIS_HOST', '127.0.0.1'), ['class' => 'form-control', 'placeholder' => __('Ej: 127.0.0.1')]) }}
                                        <small class="form-text text-muted">
                                            {{ __('Dirección del servidor Redis') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('redis_port', __('Redis Port'), ['class' => 'form-label']) }}
                                        {{ Form::number('redis_port', env('REDIS_PORT', '6379'), ['class' => 'form-control', 'placeholder' => __('Ej: 6379'), 'min' => '1', 'max' => '65535']) }}
                                        <small class="form-text text-muted">
                                            {{ __('Puerto del servidor Redis (por defecto: 6379)') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('redis_password', __('Contraseña de Redis'), ['class' => 'form-label']) }}
                                        {{ Form::password('redis_password', ['class' => 'form-control', 'placeholder' => __('Dejar en blanco si no hay contraseña')]) }}
                                        <small class="form-text text-muted">
                                            {{ __('Contraseña de autenticación de Redis (opcional)') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row mt-3">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        {{ Form::label('redis_prefix', __('Prefijo de Redis'), ['class' => 'form-label']) }}
                                        {{ Form::text('redis_prefix', env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'), ['class' => 'form-control', 'placeholder' => __('Ej: mi_app_')]) }}
                                        <small class="form-text text-muted">
                                            {{ __('Prefijo para las claves de Redis (por defecto: nombre de la aplicación)') }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-whitesmoke">
                            <span class="float-end">
                                <button class="btn btn-primary" type="submit" id="save-redis-btn">{{ __('Guardar Configuración Redis') }}</button>
                            </span>
                        </div>
                        {{ Form::close() }}
                    </div>

                    {{-- Sección: Upload Stats Settings --}}
                    <div id="useradd-3-7" class="card mb-4">
                        {{ Form::open(['route' => 'settings.upload-stats', 'method' => 'post']) }}
                        <div class="card-header">
                            <h5>{{ __('Upload Stats Settings') }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('mysql_server', __('MySQL Server'), ['class' => 'form-label']) }}
                                        {{ Form::text('mysql_server', env('MYSQL_SERVER'), ['class' => 'form-control', 'placeholder' => 'localhost']) }}
                                    </div>
                                    <div class="form-group mt-3">
                                        {{ Form::label('mysql_port', __('MySQL Port'), ['class' => 'form-label']) }}
                                        {{ Form::text('mysql_port', env('MYSQL_PORT', '3306'), ['class' => 'form-control']) }}
                                    </div>
                                    <div class="form-group mt-3">
                                        {{ Form::label('mysql_db', __('Database Name'), ['class' => 'form-label']) }}
                                        {{ Form::text('mysql_db', env('MYSQL_DB'), ['class' => 'form-control']) }}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        {{ Form::label('mysql_table_line', __('Lines Table'), ['class' => 'form-label']) }}
                                        {{ Form::text('mysql_table_line', env('MYSQL_TABLE_LINE'), ['class' => 'form-control']) }}
                                    </div>
                                    <div class="form-group mt-3">
                                        {{ Form::label('mysql_table_sensor', __('Sensors Table'), ['class' => 'form-label']) }}
                                        {{ Form::text('mysql_table_sensor', env('MYSQL_TABLE_SENSOR'), ['class' => 'form-control']) }}
                                    </div>
                                    <div class="form-group mt-3">
                                        {{ Form::label('mysql_user', __('Database User'), ['class' => 'form-label']) }}
                                        {{ Form::text('mysql_user', env('MYSQL_USER'), ['class' => 'form-control']) }}
                                    </div>
                                    <div class="form-group mt-3">
                                        {{ Form::label('mysql_password', __('Database Password'), ['class' => 'form-label']) }}
                                        <div class="input-group">
                                            {{ Form::password('mysql_password', [
                                                'class' => 'form-control', 
                                                'value' => env('MYSQL_PASSWORD'), 
                                                'id' => 'mysql_password',
                                                'autocomplete' => 'off',
                                                'data-original-value' => env('MYSQL_PASSWORD')
                                            ]) }}
                                            <button class="btn btn-outline-secondary toggle-password" type="button" data-target="mysql_password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 d-flex justify-content-between">
                                    <div>
                                        <button type="button" id="check-connection" class="btn btn-info">
                                            <i class="fas fa-plug"></i> {{ __('Check Connection') }}
                                        </button>
                                        <button type="button" id="verify-sync-db" class="btn btn-warning ms-2">
                                            <i class="fas fa-sync"></i> {{ __('Verify & Sync Database') }}
                                        </button>
                                    </div>
                                </div>
                                <div id="connection-status" class="col-12 mt-3 text-center"></div>
                            </div>
                        </div>
                        <div class="card-footer bg-whitesmoke">
                            <span class="float-end">
                                <button class="btn btn-primary" type="submit" id="save-upload-stats-btn">{{ __('Save Upload Stats Settings') }}</button>
                            </span>
                        </div>
                        {{ Form::close() }}
                    </div>

                    {{-- Sección: Configuración de Base de Datos Réplica --}}
                    <div id="useradd-3-8" class="card mb-4">
                        <form id="replicaDbForm">
                            @csrf
                            <div class="card-header">
                                <h5>{{ __('Configuración de Base de Datos Réplica') }}</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="replica_db_host" class="form-label">{{ __('Host') }}</label>
                                            <input type="text" class="form-control" id="replica_db_host" name="replica_db_host" value="{{ env('REPLICA_DB_HOST', '') }}" placeholder="Ej: localhost">
                                            <small class="form-text text-muted">
                                                {{ __('Dirección del servidor de la base de datos de réplica') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row mt-3">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="replica_db_port" class="form-label">{{ __('Puerto') }}</label>
                                            <input type="number" class="form-control" id="replica_db_port" name="replica_db_port" value="{{ env('REPLICA_DB_PORT', '3306') }}" min="1" max="65535" placeholder="3306">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="replica_db_database" class="form-label">{{ __('Base de Datos') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="replica_db_database" name="replica_db_database" value="{{ env('REPLICA_DB_DATABASE', '') }}" placeholder="Ej: mi_base_de_datos">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="replica_db_username" class="form-label">{{ __('Usuario') }}</label>
                                            <input type="text" class="form-control" id="replica_db_username" name="replica_db_username" value="{{ env('REPLICA_DB_USERNAME', '') }}" placeholder="Usuario de la base de datos">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="replica_db_password" class="form-label">{{ __('Contraseña') }}</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="replica_db_password" name="replica_db_password" value="{{ env('REPLICA_DB_PASSWORD', '') }}" placeholder="Contraseña">
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-whitesmoke">
                                <div class="float-start">
                                    <button type="button" class="btn btn-info" id="test-connection-btn">
                                        <i class="fas fa-plug"></i> {{ __('Probar Conexión') }}
                                    </button>
                                    <button type="button" class="btn btn-success d-none" id="create-db-btn">
                                        <i class="fas fa-database"></i> {{ __('Crear Base de Datos' ) }}
                                    </button>
                                </div>
                                <div class="float-end">
                                    <button type="submit" class="btn btn-primary" id="save-replica-db-btn">
                                        <i class="fas fa-save"></i> {{ __('Guardar Configuración') }}
                                    </button>
                                </div>
                                <div class="clearfix"></div>
                            </div>
                        </form>
                    </div>
                    

                    
                    {{-- Sección: Email Settings --}}
                    <div id="useradd-3" class="card">
                        <div class="card-header">
                            <h5>{{ __('Email Settings') }}</h5>
                        </div>
                        {{ Form::open(['route' => 'settings.emails', 'method' => 'post']) }}
                        <div class="card-body">
                            <div class="row mt-3 container-fluid">
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_driver', __('Controlador de correo'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_driver', old('mail_driver', $mail_config['mail_driver'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail Driver')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_host', __('Host de correo'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_host', old('mail_host', $mail_config['mail_host'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail Host')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_port', __('Mail Port'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_port', old('mail_port', $mail_config['mail_port'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail Port')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_username', __('Mail Username'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_username', old('mail_username', $mail_config['mail_username'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail Username')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_password', __('Mail Password'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_password', old('mail_password', $mail_config['mail_password'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail Password')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_encryption', __('Mail Encryption'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_encryption', old('mail_encryption', $mail_config['mail_encryption'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail Encryption')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_from_address', __('Mail From Address'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_from_address', old('mail_from_address', $mail_config['mail_from_address'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail From Address')]) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('mail_from_name', __('Mail From Name'), ['class' => 'form-label']) }}
                                        {{ Form::text('mail_from_name', old('mail_from_name', $mail_config['mail_from_name'] ?? ''), ['class' => 'form-control', 'placeholder' => __('Enter Mail From Name')]) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <span class="float-end">
                                <button class="btn btn-primary float-end mb-3" type="submit" id="save-btn">{{ __('Save Changes') }}</button>
                                <a class="btn btn-info d-inline send_mail float-end m-auto me-1 fs-6" href="javascript:void(0);" id="test-mail" data-action="test-mail">
                                    {{ __('Send Test Mail') }}
                                </a>
                                <a href="{{ route('settings.index') }}" class="btn btn-secondary float-end me-1">{{ __('Cancel') }}</a>
                            </span>
                        </div>
                        {{ Form::close() }}
                    </div>
                    {{-- Sección: Finish Shift Email Settings --}}
                    <div id="useradd-4" class="card mb-4">
                        <div class="card-header">
                            <h5>{{ __('Finish Shift Email Settings') }}</h5>
                        </div>
                        {{ Form::open(['route' => 'settings.finishshiftemails', 'method' => 'post']) }}
                        <div class="card-body container-fluid">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('EMAIL_FINISH_SHIFT_LISTWORKERS', __('Worker Emails (comma separated)'), ['class' => 'form-label']) }}
                                        {{ Form::text(
                                            'EMAIL_FINISH_SHIFT_LISTWORKERS',
                                            old('EMAIL_FINISH_SHIFT_LISTWORKERS', env('EMAIL_FINISH_SHIFT_LISTWORKERS', '')),
                                            ['class' => 'form-control', 'placeholder' => 'uno@ej.com, dos@ej.com']
                                        ) }}
                                    </div>
                                </div>
                                <div class="col-sm-12 mb-3">
                                    <div class="form-group">
                                        {{ Form::label('EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED', __('Assignment Emails (comma separated)'), ['class' => 'form-label']) }}
                                        {{ Form::text(
                                            'EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED',
                                            old('EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED', env('EMAIL_FINISH_SHIFT_LISTCONFECCIONSIGNED', '')),
                                            ['class' => 'form-control', 'placeholder' => 'admin@ej.com, soporte@ej.com']
                                        ) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-whitesmoke">
                            <button class="btn btn-primary" type="submit">
                                {{ __('Save Finish Shift Emails') }}
                            </button>
                            <a href="{{ route('settings.index') }}" class="btn btn-secondary">
                                {{ __('Cancel') }}
                            </a>

                            {{-- ← Aquí el botón de test --}}
                            <a href="{{ route('settings.testFinishShifts') }}" class="btn btn-info ms-2"onclick="return confirm('{{ __('Are you sure you want to send test emails?') }}');">
                                    {{ __('Test Finish Shift Emails') }}
                            </a>
                        </div>
                        
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
            <!-- [ sample-page ] end -->
        </div>
        <!-- [ Main Content ] end -->
    </div>
@endsection

@push('scripts')
<script>
    // Toggle para mostrar/ocultar contraseña
    $(document).on('click', '.toggle-password', function() {
        const button = $(this);
        const input = button.closest('.input-group').find('input');
        const icon = button.find('i');
        
        if (input.attr('type') === 'password') {
            // Save current value and change to text
            input.attr('data-original-value', input.val());
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            // Change back to password and restore value
            input.attr('type', 'password');
            input.val(input.attr('data-original-value') || '');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Probar conexión a la base de datos
    $('#test-connection-btn').on('click', function() {
        const $btn = $(this);
        const $form = $('#replicaDbForm');
        const $createDbBtn = $('#create-db-btn');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Probando...');
        
        // Get form data and map to expected field names
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            host: $('#replica_db_host').val(),
            port: $('#replica_db_port').val(),
            database: $('#replica_db_database').val(),
            username: $('#replica_db_username').val(),
            password: $('#replica_db_password').val()
        };
        
        $.ajax({
            url: '/settings/test-replica-db-connection',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    
                    // Mostrar/ocultar botón de crear base de datos
                    if (response.database_exists) {
                        $createDbBtn.addClass('d-none');
                    } else {
                        $createDbBtn.removeClass('d-none');
                    }
                } else {
                    toastr.error(response.message);
                    $createDbBtn.addClass('d-none');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                toastr.error(response.message || 'Error al probar la conexión');
                $createDbBtn.addClass('d-none');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-plug"></i> {{ __("Probar Conexión") }}');
            }
        });
    });

    // Crear base de datos
    $('#create-db-btn').on('click', function() {
        if (!confirm('¿Está seguro de que desea crear la base de datos? Esta operación no se puede deshacer.')) {
            return;
        }
        
        const $btn = $(this);
        const $form = $('#replicaDbForm');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
        
        // Get form data and map to expected field names
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            host: $('#replica_db_host').val(),
            port: $('#replica_db_port').val(),
            database: $('#replica_db_database').val(),
            username: $('#replica_db_username').val(),
            password: $('#replica_db_password').val()
        };
        
        $.ajax({
            url: '/settings/create-replica-database',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $btn.addClass('d-none');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                toastr.error(response.message || 'Error al crear la base de datos');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-database"></i> {{ __("Crear Base de Datos") }}');
            }
        });
    });

    // Guardar configuración
    $('#replicaDbForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $btn = $('#save-replica-db-btn');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        
        // Get form data with correct field names for validation
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            replica_db_host: $('#replica_db_host').val(),
            replica_db_port: $('#replica_db_port').val(),
            replica_db_database: $('#replica_db_database').val(),
            replica_db_username: $('#replica_db_username').val(),
            replica_db_password: $('#replica_db_password').val()
        };
        
        $.ajax({
            url: '/settings/replica-db',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Ocultar el botón de crear base de datos hasta que se vuelva a probar la conexión
                    $('#create-db-btn').addClass('d-none');
                } else {
                    toastr.error(response.message || 'Error al guardar la configuración');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                toastr.error(response.message || 'Error al guardar la configuración');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save"></i> {{ __("Guardar Configuración") }}');
            }
        });
    });

    function check_theme(color_val) {
        $('.theme-color').prop('checked', false);
        $('input[value="' + color_val + '"]').prop('checked', true);
    }

    $(document).on('click', "input[name$='settingtype']", function() {
        var test = $(this).val();
        if (test == 's3') {
            $("#s3").fadeIn(500).removeClass('d-none');
        } else {
            $("#s3").fadeOut(500);
        }
    });

    $('body').on('click', '.send_mail', function() {
        var action = $(this).data('action');
        var modal = $('#common_modal');
        $.get(action, function(response) {
            modal.find('.modal-title').html('{{ __("Test Mail") }}');
            modal.find('.body').html(response);
            modal.modal('show');
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        var genericExamples = document.querySelectorAll('[data-trigger]');
        for (let i = 0; i < genericExamples.length; ++i) {
            var element = genericExamples[i];
            new Choices(element, {
                placeholderValue: 'This is a placeholder set in the config',
                searchPlaceholderValue: 'Select Option'
            });
        }
    });

    $(document).on("click", ".useradd-1", function(){
        $(".useradd-1").addClass("active");
        $(".useradd-2, .useradd-3, .useradd-4").removeClass("active");
    });
    $(document).on("click", ".useradd-2", function(){
        $(".useradd-2").addClass("active");
        $(".useradd-1, .useradd-3, .useradd-3-5, .useradd-4").removeClass("active");
    });
    $(document).on("click", ".useradd-3-5", function(){
        $(".useradd-3-5").addClass("active");
        $(".useradd-1, .useradd-2, .useradd-3, .useradd-4").removeClass("active");
    });
    $(document).on("click", ".useradd-3", function(){
        $(".useradd-3").addClass("active");
        $(".useradd-1, .useradd-2, .useradd-3-5, .useradd-4").removeClass("active");
    });
    $(document).on("click", ".useradd-4", function(){
        $(".useradd-4").addClass("active");
        $(".useradd-1, .useradd-2, .useradd-3, .useradd-3-5").removeClass("active");
    });
    // Upload Stats - Check Database Connection
    $('#check-connection').on('click', function() {
        const $btn = $(this);
        const $status = $('#connection-status');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Checking...") }}');
        $status.removeClass('text-success text-danger').html('');
        
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            mysql_server: $('input[name="mysql_server"]').val(),
            mysql_port: $('input[name="mysql_port"]').val(),
            mysql_db: $('input[name="mysql_db"]').val(),
            mysql_user: $('input[name="mysql_user"]').val(),
            mysql_password: $('input[name="mysql_password"]').val(),
            mysql_table_line: $('input[name="mysql_table_line"]').val(),
            mysql_table_sensor: $('input[name="mysql_table_sensor"]').val()
        };
        
        $.ajax({
            url: '/api/check-db-connection',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    $status.html('<i class="fas fa-check-circle"></i> ' + response.message).addClass('text-success');
                } else {
                    $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Connection failed')).addClass('text-danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Error checking connection')).addClass('text-danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-plug"></i> {{ __("Check Connection") }}');
            }
        });
    });
    
    // Upload Stats - Verify & Sync Database
    $('#verify-sync-db').on('click', function() {
        if (!confirm('{{ __("Are you sure you want to verify and sync the database? This may take some time.") }}')) {
            return;
        }
        
        const $btn = $(this);
        const $status = $('#connection-status');
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __("Processing...") }}');
        $status.removeClass('text-success text-danger').html('');
        
        const formData = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            mysql_server: $('input[name="mysql_server"]').val(),
            mysql_port: $('input[name="mysql_port"]').val(),
            mysql_db: $('input[name="mysql_db"]').val(),
            mysql_user: $('input[name="mysql_user"]').val(),
            mysql_password: $('input[name="mysql_password"]').val(),
            mysql_table_line: $('input[name="mysql_table_line"]').val(),
            mysql_table_sensor: $('input[name="mysql_table_sensor"]').val()
        };
        
        $.ajax({
            url: '/api/verify-and-sync-database',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.status === 'success') {
                    $status.html('<i class="fas fa-check-circle"></i> ' + response.message).addClass('text-success');
                } else {
                    $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Verification failed')).addClass('text-danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON || {};
                $status.html('<i class="fas fa-times-circle"></i> ' + (response.message || 'Error during verification')).addClass('text-danger');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-sync"></i> {{ __("Verify & Sync Database") }}');
            }
        });
    });
    
    // Save Upload Stats Settings
    $('form').on('submit', function(e) {
        // Make sure password field has the current value before submission
        const $form = $(this);
        const $passwordInput = $form.find('input[type="password"][name="mysql_password"]');
        
        // If password field is visible (not toggled), update its value from the input
        if ($passwordInput.length && $passwordInput.attr('type') === 'password') {
            $passwordInput.val($passwordInput.attr('data-original-value') || '');
        }
        
        // The settings will be saved to the .env file by the controller
    });
    
    // Initialize password field with value from data-original-value if it exists
    $(document).ready(function() {
        const $passwordInput = $('input[type="password"][name="mysql_password"]');
        if ($passwordInput.length && $passwordInput.data('original-value')) {
            $passwordInput.attr('data-original-value', $passwordInput.data('original-value'));
        }

        // Handle menu highlighting on page load
        const hash = window.location.hash;
        if (hash) {
            $('.list-group-item').removeClass('active');
            $(`a[href="${hash}"]`).addClass('active');
        }

        // Handle menu highlighting on click
        $('.list-group-item').on('click', function(e) {
            e.preventDefault(); // Prevent default anchor behavior
            const target = $(this).attr('href');
            
            // Update active state
            $('.list-group-item').removeClass('active');
            $(this).addClass('active');
            
            // Smooth scroll to target
            if (target !== '#') {
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 20
                }, 500);
                
                // Update URL without page reload
                if (history.pushState) {
                    history.pushState(null, null, target);
                } else {
                    window.location.hash = target;
                }
            }
        });

        // Highlight menu item based on scroll position
        $(window).on('scroll', function() {
            const scrollPosition = $(this).scrollTop();
            
            $('.card').each(function() {
                const currentSection = $(this);
                const sectionTop = currentSection.offset().top - 100;
                const sectionHeight = currentSection.outerHeight();
                const sectionId = '#' + currentSection.attr('id');
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    $('.list-group-item').removeClass('active');
                    $(`a[href="${sectionId}"]`).addClass('active');
                }
            });
        }).scroll(); // Trigger scroll event on page load
    });
</script>
@endpush
