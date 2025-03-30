<?php

namespace App\Http\Controllers;

use App\Models\ShiftList;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use App\Models\ShiftHistory;

class ShiftManagementController extends Controller
{
    /**
     * Muestra la vista principal (lista de turnos).
     */
    public function index()
    {
        // Trae las líneas con su último historial
        $productionLines = ProductionLine::with('lastShiftHistory')->get();
        return view('shift.index', compact('productionLines'));
    }
    

    /**
     * Retorna datos en formato JSON para DataTables.
     */
    public function getShiftsData(Request $request)
    {
        $query = ShiftList::with('productionLine');
    
        // Si se envía un id de línea de producción, aplicar el filtro
        if ($request->filled('production_line')) {
            $query->where('production_line_id', $request->production_line);
        }
    
        $shiftLists = $query->get();
    
        return response()->json([
            'data' => $shiftLists,
        ]);
    }

    /**
     * Muestra el historial (último registro) de turnos para una línea de producción.
     */
    public function showShiftHistory($productionLineId)
    {
        // Obtén el último registro para la línea de producción
        $lastRecord = ShiftHistory::where('production_line_id', $productionLineId)
            ->orderBy('id', 'desc')
            ->first();

        return view('shift.history', compact('lastRecord', 'productionLineId'));
    }

    /**
     * Crea un nuevo turno (soporta AJAX y normal).
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_line_id' => 'required|integer',
            'start' => 'required|date_format:H:i',
            'end'   => 'required|date_format:H:i',
        ]);

        ShiftList::create($request->all());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift created successfully.'
            ]);
        }

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
