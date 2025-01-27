<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductionOrderController extends Controller
{
    public function index()
    {
        return view('productionorder.index'); // Cargar el Blade de producción
    }
}
