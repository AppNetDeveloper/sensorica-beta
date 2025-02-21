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
        // Validar los datos recibidos
        $validated = $request->validate([
            'operator_id'      => 'required|exists:operators,id',
            'rfid_reading_id'  => 'nullable|exists:rfid_readings,id',
            'sensor_id'        => 'nullable|exists:sensors,id',
            'modbus_id'        => 'nullable|exists:modbuses,id',
        ]);
    
        // Actualizar registros existentes para el operador que tengan finish_at nulo
        OperatorPost::where('operator_id', $validated['operator_id'])
            ->whereNull('finish_at')
            ->update(['finish_at' => now()]);
    
        // Actualizar registros existentes para sensor (si se envía)
        if (!empty($validated['sensor_id'])) {
            OperatorPost::where('sensor_id', $validated['sensor_id'])
                ->whereNull('finish_at')
                ->update(['finish_at' => now()]);
        }
    
        // Actualizar registros existentes para modbus (si se envía)
        if (!empty($validated['modbus_id'])) {
            OperatorPost::where('modbus_id', $validated['modbus_id'])
                ->whereNull('finish_at')
                ->update(['finish_at' => now()]);
        }
    
        // Lógica especial para rfid_reading_id
        if (!empty($validated['rfid_reading_id'])) {
            // Buscar el registro de RFID para obtener su nombre
            $rfidRecord = RfidReading::find($validated['rfid_reading_id']);
            if ($rfidRecord) {
                $rfidName = $rfidRecord->name;
                // Obtener todos los IDs de RFID que tengan el mismo nombre
                $rfidIds = RfidReading::where('name', $rfidName)->pluck('id');
                
                // Actualizar los registros existentes en OperatorPost para estos RFID
                OperatorPost::whereIn('rfid_reading_id', $rfidIds)
                    ->whereNull('finish_at')
                    ->update(['finish_at' => now()]);
                
                // Crear un nuevo registro para cada RFID encontrado
                $createdRecords = [];
                foreach ($rfidIds as $rfidId) {
                    $data = $validated;
                    $data['rfid_reading_id'] = $rfidId;
                    $createdRecords[] = OperatorPost::create($data);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Relaciones creadas correctamente para RFID con el mismo nombre.',
                    'data'    => $createdRecords,
                ], 201);
            }
        }
    
        // Si no se envía rfid_reading_id o no aplica la lógica especial, se crea un único registro
        $relation = OperatorPost::create($validated);
    
        return response()->json([
            'success' => true,
            'message' => 'Relación creada correctamente.',
            'data'    => $relation,
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
        $relations = OperatorPost::with([
            'operator', 
            'rfidReading', 
            'sensor', 
            'modbus', 
            'productList' // Agregamos la relación aquí
        ])->get();
    
        return response()->json([
            'data' => $relations // DataTables espera una clave 'data'
        ]);
    }
    
}