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
        $production_line_id = $request->route('production_line_id');
        
        // Mostrar los colores ordenados (últimos creados primero)
        $colors = RfidColor::orderBy('created_at', 'desc')->get();

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

        // Crear el color
        RfidColor::create([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('rfid.colors.index', ['production_line_id' => $request->route('production_line_id')])
            ->with('success', __('Color RFID creado correctamente.'));
    }

    /**
     * Muestra el formulario para editar un color RFID existente.
     */
    public function edit(Request $request, $production_line_id, $rfidColor)
    {
        // Obtener el modelo manualmente
        $color = RfidColor::findOrFail($rfidColor);
    
        return view('color.edit', [
            'color' => $color,
            'production_line_id' => $production_line_id
        ]);
    }
    

    /**
     * Actualiza el color RFID.
     */
    public function update(Request $request, $production_line_id, RfidColor $rfidColor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Actualizar el color
        $rfidColor->update([
            'name' => $request->name,
        ]);

        return redirect()
            ->route('rfid.colors.index', ['production_line_id' => $production_line_id])
            ->with('success', __('Color RFID actualizado correctamente.'));
    }

    /**
     * Elimina un color RFID.
     */
    public function destroy(Request $request, $production_line_id, RfidColor $rfidColor)
    {
        // Eliminar el color
        $rfidColor->delete();

        return redirect()
            ->route('rfid.colors.index', ['production_line_id' => $production_line_id])
            ->with('success', __('Color RFID eliminado correctamente.'));
    }
}
