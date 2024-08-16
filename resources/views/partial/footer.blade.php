@php
$logo = asset(Storage::url('uploads/logo/'));
@endphp

<footer class="c-footer">
    <img src="{{ $logo . 'light_logo.png' }}" class="navbar-brand-img main-logo mt-5" alt="logo">
    <div> &copy; {{ date('Y') }} {{ config('app.name') }}.</div>
</footer>
