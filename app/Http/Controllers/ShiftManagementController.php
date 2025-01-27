<?php

namespace App\Http\Controllers;

use App\Models\ShiftList;
use Illuminate\Http\Request;
use App\Models\ProductionLine;

class ShiftManagementController extends Controller
{
    /**
     * Muestra la lista de turnos.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $shiftLists = ShiftList::with('productionLine')->get(); // Obtiene turnos con sus líneas de producción
        $productionLines = ProductionLine::all(); // Obtiene todas las líneas de producción
    
        return view('shift.index', compact('shiftLists', 'productionLines'));
    }
    

    /**
     * Crea un nuevo turno.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_line_id' => 'required|integer',
            'start' => 'required|date_format:H:i:s',
            'end' => 'required|date_format:H:i:s',
        ]);

        ShiftList::create($request->all());

        return redirect()->route('shift.index')->with('success', 'Shift created successfully.');
    }

    /**
     * Actualiza un turno existente.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'production_line_id' => 'required|integer',
            'start' => 'required|date_format:H:i:s',
            'end' => 'required|date_format:H:i:s',
        ]);

        $shift = ShiftList::findOrFail($id);
        $shift->update($request->all());

        return redirect()->route('shift.index')->with('success', 'Shift updated successfully.');
    }

    /**
     * Elimina un turno existente.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $shift = ShiftList::findOrFail($id);
        $shift->delete();

        return redirect()->route('shift.index')->with('success', 'Shift deleted successfully.');
    }
}
