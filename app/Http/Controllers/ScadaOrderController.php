<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScadaOrderController extends Controller
{
    public function index()
    {
        return view('scadaorder.index'); // Ruta correcta para el nuevo Blade
    }
}
