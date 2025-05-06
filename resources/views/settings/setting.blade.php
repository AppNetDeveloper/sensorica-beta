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
                            <a href="settings#useradd-3" class="list-group-item list-group-item-action useradd-3">
                                {{ __('Email') }} <div class="float-end"></div>
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
                                {{ Form::label('timezone', __('Timezone'), ['class' => 'form-label text-dark']) }}
                                <select name="timezone" class="form-control" data-trigger id="choices-single-default">
                                    <option value="">{{ __('Select Timezone') }}</option>
                                    @foreach ($timezones as $k => $timezone)
                                        <option value="{{ $k }}" {{ env('TIMEZONE') == $k ? 'selected' : '' }}>
                                            {{ $timezone }}
                                        </option>
                                    @endforeach
                                </select>
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
        $(".useradd-2, .useradd-3").removeClass("active");
    });
    $(document).on("click", ".useradd-2", function(){
        $(".useradd-2").addClass("active");
        $(".useradd-1, .useradd-3").removeClass("active");
    });
    $(document).on("click", ".useradd-3", function(){
        $(".useradd-3").addClass("active");
        $(".useradd-1, .useradd-2").removeClass("active");
    });
</script>
@endpush
