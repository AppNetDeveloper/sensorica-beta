<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkerController extends Controller
{
    /**
     * Retorna la vista Blade (workers-admin.index) 
     * que se encargará de llamar a la API vía AJAX.
     */
    public function index()
    {
        return view('workers-admin.index');
    }
}
