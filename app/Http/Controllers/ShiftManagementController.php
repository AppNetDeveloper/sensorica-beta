<?php

namespace App\Http\Controllers;

use App\Models\ShiftList;
use App\Models\ProductionLine;
use Illuminate\Http\Request;

class ShiftManagementController extends Controller
{
    /**
     * Muestra la vista principal (lista de turnos).
     */
    public function index()
    {
        $productionLines = ProductionLine::all();
        return view('shift.index', compact('productionLines'));
    }

    /**
     * Retorna datos en formato JSON para DataTables.
     */
    public function getShiftsData()
    {
        // Carga con la relación
        $shiftLists = ShiftList::with('productionLine')->get();

        // DataTables usa por defecto la clave 'data'
        return response()->json([
            'data' => $shiftLists,
        ]);
    }

    /**
     * Crea un nuevo turno (soporta AJAX y normal).
     */
    public function store(Request $request)
    {
        // Ajustar 'H:i' vs 'H:i:s' según tu input <time>
        $request->validate([
            'production_line_id' => 'required|integer',
            'start' => 'required|date_format:H:i',
            'end'   => 'required|date_format:H:i',
        ]);

        ShiftList::create($request->all());

        // Si la petición es AJAX, responder con JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift created successfully.'
            ]);
        }

        // Si no, redirigir
        return redirect()->route('shift.index')->with('success', 'Shift created successfully.');
    }

    /**
     * Actualiza un turno existente (soporta AJAX y normal).
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'production_line_id' => 'required|integer',
            'start' => 'required|date_format:H:i',
            'end'   => 'required|date_format:H:i',
        ]);

        $shift = ShiftList::findOrFail($id);
        $shift->update($request->all());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift updated successfully.'
            ]);
        }

        return redirect()->route('shift.index')->with('success', 'Shift updated successfully.');
    }

    /**
     * Elimina un turno existente (soporta AJAX y normal).
     */
    public function destroy(Request $request, $id)
    {
        $shift = ShiftList::findOrFail($id);
        $shift->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift deleted successfully.'
            ]);
        }

        return redirect()->route('shift.index')->with('success', 'Shift deleted successfully.');
    }
}
