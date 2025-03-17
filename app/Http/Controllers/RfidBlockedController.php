<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RfidBlocked;

class RfidBlockedController extends Controller
{
    /**
     * Elimina todos los registros de la tabla rfid_blocked.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroyAll()
    {
        // Elimina todos los registros de la tabla
        RfidBlocked::truncate();

        // Redirige de vuelta a la misma página con un mensaje de éxito
        return redirect()->back()->with('success', 'Todos los registros han sido eliminados.');
    }
}
