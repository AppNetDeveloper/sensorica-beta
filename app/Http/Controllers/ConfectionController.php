<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConfectionController extends Controller
{
    public function index()
    {
        // Retorna la vista con la tabla y la lógica DataTables
        return view('confections.index');
    }
}
