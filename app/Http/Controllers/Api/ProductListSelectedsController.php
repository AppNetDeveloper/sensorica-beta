<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductListSelecteds;
use App\Models\ProductList;
use App\Models\Modbus;
use App\Models\Sensor;
use App\Models\RfidReading;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API de Relaciones Productos y RFID (product_list_selecteds)",
 *      description="APIs para gestionar la tabla product_list_selecteds, relacionando ProductList, RfidReading, Modbus y Sensor."
 * )
 * 
 * @OA\Tag(
 *     name="ProductListSelecteds",
 *     description="Operaciones sobre las relaciones en la tabla product_list_selecteds"
 * )
 */
class ProductListSelectedsController extends Controller
{
    /**
     * Mostrar lista completa de relaciones (CRUD principal).
     * GET /api/product-list-selecteds
     */
    public function index()
    {
        $relations = ProductListSelecteds::with([
            'productList',
            'rfidReading' => function ($query) {
                $query->with('rfidColor'); // Añade esto para cargar la relación con rfid_color
            },
            'modbus',
            'sensor'
        ])->get();
        
        return response()->json($relations, 200);
    }

    /**
     * Crear una relación.
     * POST /api/product-list-selecteds
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'       => 'required|exists:product_lists,client_id',
            'rfid_reading_id' => 'required|exists:rfid_readings,id',
            'modify_all'      => 'nullable|boolean',
            'modbus_id'       => 'nullable|exists:modbuses,id',
            'sensor_id'       => 'nullable|exists:sensors,id',
        ]);
    
        $productList = ProductList::where('client_id', $validated['client_id'])->first();
        if (!$productList) {
            return response()->json(['error' => 'No se encontró un producto asociado al client_id.'], 404);
        }
    
        $rfidReading = RfidReading::with('rfidColor')->find($validated['rfid_reading_id']);
        if (!$rfidReading) {
            return response()->json(['error' => 'RFID Reading no encontrado.'], 404);
        }
    
        if ($validated['modify_all']) {
            $rfidReadingsSameColor = RfidReading::where('rfid_color_id', $rfidReading->rfid_color_id)->get();
            
            foreach ($rfidReadingsSameColor as $rfid) {
                ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'rfid_reading_id' => $rfid->id,
                    'modbus_id'       => $validated['modbus_id'] ?? null,
                    'sensor_id'       => $validated['sensor_id'] ?? null,
                    'modify_all'      => true, // Pasar esta información al modelo para que maneje la lógica de finalización
                ]);
            }
        } else {
            $relation = ProductListSelecteds::create([
                'product_list_id' => $productList->id,
                'rfid_reading_id' => $validated['rfid_reading_id'],
                'modbus_id'       => $validated['modbus_id'] ?? null,
                'sensor_id'       => $validated['sensor_id'] ?? null,
                'modify_all'      => false, // Pasar esta información al modelo para usar la lógica estándar
            ]);
        }
    
        return response()->json($validated['modify_all'] ? ['message' => 'Relaciones creadas para todas las tarjetas del mismo color'] : $relation, 201);
    }

    /**
     * Ver una relación por ID.
     * GET /api/product-list-selecteds/{id}
     */
    public function show($id)
    {
        $relation = ProductListSelecteds::with([
            'productList',
            'rfidReading',
            'modbus',
            'sensor'
        ])->find($id);
        
        if (!$relation) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }
        return response()->json($relation, 200);
    }

    /**
     * Actualizar una relación por ID.
     * PUT /api/product-list-selecteds/{id}
     */
    public function update(Request $request, $id)
    {
        $relation = ProductListSelecteds::find($id);
        if (!$relation) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        $validated = $request->validate([
            'product_list_id' => 'sometimes|exists:product_lists,id',
            'rfid_reading_id' => 'sometimes|exists:rfid_readings,id',
            'modbus_id'       => 'nullable|exists:modbuses,id',
            'sensor_id'       => 'nullable|exists:sensors,id',
        ]);

        $relation->update($validated);
        return response()->json($relation, 200);
    }

    /**
     * Eliminar una relación por ID.
     * DELETE /api/product-list-selecteds/{id}
     */
    public function destroy($id)
    {
        $relation = ProductListSelecteds::find($id);
        if (!$relation) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        $relation->delete();
        return response()->json(['message' => 'Relación eliminada correctamente'], 200);
    }

    /**
     * Listar todos los Modbuses (sin ID).
     * GET /api/product-list-selecteds/modbuses
     */
    public function listModbuses()
    {
        $modbuses = Modbus::select('id','name')->get();
        return response()->json($modbuses, 200);
    }

    /**
     * Listar todos los Sensors (sin ID).
     * GET /api/product-list-selecteds/sensors
     */
    public function listSensors()
    {
        $sensors = Sensor::select('id','name')->get();
        return response()->json($sensors, 200);
    }
}