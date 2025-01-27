<?php

namespace App\Http\Controllers;

use App\Models\OperatorPost;
use App\Models\Operator;
use App\Models\RfidReading;
use App\Models\Sensor;
use App\Models\Modbus;
use Illuminate\Http\Request;

class OperatorPostController extends Controller
{
    /**
     * Muestra la lista de relaciones.
     */
    public function index()
    {
        // Estos datos se envían para poblar los <select> del formulario
        $operators = Operator::all();
        $rfids = RfidReading::all();
        $sensors = Sensor::all();
        $modbuses = Modbus::all();
    
        // La vista blade se encarga de renderizar la tabla y los formularios modales
        return view('worker-post.index', compact('operators', 'rfids', 'sensors', 'modbuses'));
    }

    /**
     * Almacena una nueva relación en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'rfid_reading_id' => 'nullable|exists:rfid_readings,id',
            'sensor_id' => 'nullable|exists:sensors,id',
            'modbus_id' => 'nullable|exists:modbuses,id',
        ]);

        $relation = OperatorPost::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Relación creada correctamente.',
            'data' => $relation
        ], 201);
    }

    /**
     * Actualiza una relación existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'rfid_reading_id' => 'nullable|exists:rfid_readings,id',
            'sensor_id' => 'nullable|exists:sensors,id',
            'modbus_id' => 'nullable|exists:modbuses,id',
        ]);

        $relation = OperatorPost::findOrFail($id);
        $relation->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Relación actualizada correctamente.',
            'data' => $relation
        ]);
    }

    /**
     * Elimina una relación de la base de datos.
     */
    public function destroy($id)
    {
        $relation = OperatorPost::findOrFail($id);
        $relation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Relación eliminada correctamente.'
        ]);
    }
    
    /**
     * Devuelve las relaciones en formato JSON para DataTables.
     */
    public function apiIndex()
    {
        $relations = OperatorPost::with(['operator', 'rfidReading', 'sensor', 'modbus'])->get();
        return response()->json([
            'data' => $relations // DataTables espera una clave 'data'
        ]);
    }
}