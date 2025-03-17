<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetUserLocale
{
    public function handle($request, Closure $next)
    {
        // Si el usuario está logueado y tiene la columna 'lang':
        if (Auth::check() && Auth::user()->lang) {
            // Cambiamos el idioma de la aplicación
            App::setLocale(Auth::user()->lang);
        }

        return $next($request);
    }
}
