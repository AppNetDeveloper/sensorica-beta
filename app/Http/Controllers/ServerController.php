<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ServerController extends Controller
{
    public function index()
    {
        return view('server.index');
    }
        /**
     * Muestra la vista de configuración de Upload Stats.
     *
     * @return \Illuminate\View\View
     */
    public function showUploadStatsConfig()
    {
        return view('server.uploadstats');
    }
}
