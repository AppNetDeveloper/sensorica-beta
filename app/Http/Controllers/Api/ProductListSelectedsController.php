<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductListSelecteds;
use App\Models\ProductList;
use App\Models\Modbus;
use App\Models\Sensor;
use App\Models\RfidReading;
use Carbon\Carbon;

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
                $query->with('rfidColor'); // Cargar relación con rfid_color
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
        // Validar parámetros. rfid_reading_id es opcional.
        $validated = $request->validate([
            'client_id'                => 'required|exists:product_lists,client_id',
            'rfid_reading_id'          => 'nullable|exists:rfid_readings,id',
            'modify_all'               => 'nullable|boolean',
            'modify_line'              => 'nullable|boolean',
            'modbus_id'                => 'nullable|exists:modbuses,id',
            'sensor_id'                => 'nullable|exists:sensors,id',
            'modify_all_modbus_line'   => 'nullable|boolean',
            'modify_all_sensor_line'   => 'nullable|boolean',
        ]);

        // Requerir que al menos se seleccione uno: RFID, Báscula o Sensor.
        if (empty($validated['rfid_reading_id']) && empty($validated['modbus_id']) && empty($validated['sensor_id'])) {
            return response()->json(['error' => 'Debe seleccionar al menos RFID, Báscula o Sensor.'], 422);
        }

        // Buscar el producto asociado.
        $productList = ProductList::where('client_id', $validated['client_id'])->first();
        if (!$productList) {
            return response()->json(['error' => 'No se encontró un producto asociado al client_id.'], 404);
        }

        // Cerrar asignaciones abiertas globalmente para cada categoría.
        if (!empty($validated['rfid_reading_id'])) {
            ProductListSelecteds::where('rfid_reading_id', $validated['rfid_reading_id'])
                ->whereNull('finish_at')
                ->update(['finish_at' => Carbon::now()]);
        }
        if (!empty($validated['modbus_id'])) {
            ProductListSelecteds::where('modbus_id', $validated['modbus_id'])
                ->whereNull('finish_at')
                ->update(['finish_at' => Carbon::now()]);
        }
        if (!empty($validated['sensor_id'])) {
            ProductListSelecteds::where('sensor_id', $validated['sensor_id'])
                ->whereNull('finish_at')
                ->update(['finish_at' => Carbon::now()]);
        }

        // Procesar asignaciones.
        if (!empty($validated['rfid_reading_id'])) {
            // Caso "con RFID": Buscar el RFID.
            $rfidReading = RfidReading::with('rfidColor')->find($validated['rfid_reading_id']);
            if (!$rfidReading) {
                return response()->json(['error' => 'RFID Reading no encontrado.'], 404);
            }

            if (!empty($validated['modify_all']) && $validated['modify_all'] == true) {
                // Replicar asignación para todos los RFID que cumplan el filtro.
                if (!empty($validated['modify_line']) && $validated['modify_line'] == true) {
                    // Filtrar por production_line_id y rfid_color_id.
                    $rfidReadings = RfidReading::where('production_line_id', $rfidReading->production_line_id)
                        ->where('rfid_color_id', $rfidReading->rfid_color_id)
                        ->get();
                } else {
                    // Filtrar solo por rfid_color_id.
                    $rfidReadings = RfidReading::where('rfid_color_id', $rfidReading->rfid_color_id)
                        ->get();
                }
                foreach ($rfidReadings as $rfid) {
                    // Cerrar asignaciones abiertas para este RFID (globalmente)
                    ProductListSelecteds::where('rfid_reading_id', $rfid->id)
                        ->whereNull('finish_at')
                        ->update(['finish_at' => Carbon::now()]);

                    // Crear nueva asignación para este RFID.
                    ProductListSelecteds::create([
                        'product_list_id' => $productList->id,
                        'rfid_reading_id' => $rfid->id,
                        'modbus_id'       => $validated['modbus_id'] ?? null,
                        'sensor_id'       => $validated['sensor_id'] ?? null,
                    ]);
                }
                $message = ['message' => 'Asignaciones creadas para todos los RFID con el mismo color' .
                    (!empty($validated['modify_line']) && $validated['modify_line'] == true
                        ? ' y en la misma línea de producción.' : '.')];
            } else {
                // Crear una única asignación con el RFID seleccionado.
                $relation = ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'rfid_reading_id' => $validated['rfid_reading_id'],
                    'modbus_id'       => $validated['modbus_id'] ?? null,
                    'sensor_id'       => $validated['sensor_id'] ?? null,
                ]);
                $message = $relation;
            }
        } elseif (!empty($validated['modbus_id']) && empty($validated['rfid_reading_id'])) {
            // Caso "sin RFID": Para Básculas.
            if (!empty($validated['modify_all_modbus_line']) && $validated['modify_all_modbus_line'] == true) {
                // Buscar la báscula para obtener su production_line_id.
                $modbus = \App\Models\Modbus::find($validated['modbus_id']);
                if (!$modbus) {
                    return response()->json(['error' => 'Báscula no encontrada.'], 404);
                }
                // Filtrar todas las básculas con el mismo production_line_id.
                $modbusesSameLine = \App\Models\Modbus::where('production_line_id', $modbus->production_line_id)->get();
                foreach ($modbusesSameLine as $mb) {
                    // Cerrar asignaciones abiertas para esta báscula.
                    ProductListSelecteds::where('modbus_id', $mb->id)
                        ->whereNull('finish_at')
                        ->update(['finish_at' => Carbon::now()]);
                    // Crear nueva asignación.
                    ProductListSelecteds::create([
                        'product_list_id' => $productList->id,
                        'modbus_id'       => $mb->id,
                        'sensor_id'       => $validated['sensor_id'] ?? null,
                    ]);
                }
                $message = ['message' => 'Asignaciones creadas para todas las básculas de la misma línea.'];
            } else {
                // Crear asignación única para la báscula seleccionada.
                $relation = ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'modbus_id'       => $validated['modbus_id'],
                    'sensor_id'       => $validated['sensor_id'] ?? null,
                ]);
                $message = $relation;
            }
        } elseif (!empty($validated['sensor_id']) && empty($validated['rfid_reading_id']) && empty($validated['modbus_id'])) {
            // Caso "sin RFID": Para Sensores.
            if (!empty($validated['modify_all_sensor_line']) && $validated['modify_all_sensor_line'] == true) {
                // Buscar el sensor para obtener su production_line_id.
                $sensor = \App\Models\Sensor::find($validated['sensor_id']);
                if (!$sensor) {
                    return response()->json(['error' => 'Sensor no encontrado.'], 404);
                }
                // Filtrar todos los sensores con el mismo production_line_id.
                $sensorsSameLine = \App\Models\Sensor::where('production_line_id', $sensor->production_line_id)->get();
                foreach ($sensorsSameLine as $sn) {
                    // Cerrar asignaciones abiertas para este sensor.
                    ProductListSelecteds::where('sensor_id', $sn->id)
                        ->whereNull('finish_at')
                        ->update(['finish_at' => Carbon::now()]);
                    // Crear nueva asignación.
                    ProductListSelecteds::create([
                        'product_list_id' => $productList->id,
                        'sensor_id'       => $sn->id,
                    ]);
                }
                $message = ['message' => 'Asignaciones creadas para todos los sensores de la misma línea.'];
            } else {
                // Crear asignación única para el sensor seleccionado.
                $relation = ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'sensor_id'       => $validated['sensor_id'],
                ]);
                $message = $relation;
            }
        } else {
            // Caso por defecto: Si se envían combinaciones (ej. sensor y báscula sin RFID)
            $relation = ProductListSelecteds::create([
                'product_list_id' => $productList->id,
                'modbus_id'       => $validated['modbus_id'] ?? null,
                'sensor_id'       => $validated['sensor_id'] ?? null,
            ]);
            $message = $relation;
        }

        return response()->json($message, 201);
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
