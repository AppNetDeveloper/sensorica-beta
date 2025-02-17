<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScanPostController extends Controller
{
    public function index()
    {
        // Por el momento retornamos la vista vacía
        return view('scan-post.index');
    }
}
