<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function index()
    {
        // Retorna la vista con la lista de sensores
        return view('sensors.index');
    }
}
