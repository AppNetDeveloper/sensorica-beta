<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RfidPostController extends Controller
{
    /**
     * Muestra la vista principal de RFID/Post.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Si deseas enviar datos a la vista, podrías obtenerlos aquí.
        // Ejemplo: $posts = Post::all();
        // return view('rfid.post.index', compact('posts'));

        return view('rfid.post.index');
    }
}
