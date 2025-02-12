<?php

namespace App\Http\Controllers;

use App\Models\RfidColor;
use Illuminate\Http\Request;

class RfidColorController extends Controller
{
    /**
     * Muestra la lista de colores RFID.
     */
    public function index(Request $request)
    {
        // Se asume que 'production_line_id' se pasa como parámetro de ruta.
        $production_line_id = $request->route('production_line_id');
        $colors = RfidColor::all();
        
        return view('color.index', compact('colors', 'production_line_id'));
    }

    /**
     * Muestra el formulario para crear un nuevo color RFID.
     */
    public function create(Request $request)
    {
        $production_line_id = $request->route('production_line_id');
        return view('color.create', compact('production_line_id'));
    }

    /**
     * Almacena un nuevo color RFID.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Se puede incluir el production_line_id si fuera necesario asignarlo al nuevo registro.
        RfidColor::create($request->only('name'));

        return redirect()->route('rfid.colors.index', ['production_line_id' => $request->input('production_line_id')])
                         ->with('success', __('Color RFID creado correctamente.'));
    }

    /**
     * Muestra el formulario para editar un color RFID existente.
     */
    public function edit($id)
    {
        $color = RfidColor::findOrFail($id);
        return view('color.edit', compact('color'));
    }

    /**
     * Actualiza el color RFID.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $color = RfidColor::findOrFail($id);
        $color->update($request->only('name'));

        return redirect()->route('rfid.colors.index')
                         ->with('success', __('Color RFID actualizado correctamente.'));
    }

    /**
     * Elimina un color RFID.
     */
    public function destroy($id)
    {
        $color = RfidColor::findOrFail($id);
        $color->delete();

        return redirect()->route('rfid.colors.index')
                         ->with('success', __('Color RFID eliminado correctamente.'));
    }
}
