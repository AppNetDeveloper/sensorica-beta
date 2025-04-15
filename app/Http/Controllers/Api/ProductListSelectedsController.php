<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductListSelecteds;
use App\Models\ProductList;
use App\Models\Modbus;
use App\Models\Sensor;
use App\Models\RfidReading;
use App\Models\OperatorPost;
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

        // --- INICIO: Añadir información del operario ---
        foreach ($relations as $relation) {
            $operatorName = 'Sin asignar'; // Valor por defecto

            // Verificar si hay una lectura RFID asociada
            if ($relation->rfidReading && $relation->rfidReading->id) {
                // Buscar la asignación de operario activa para este RFID
                $activeOperatorPost = OperatorPost::where('rfid_reading_id', $relation->rfidReading->id)
                                                  ->whereNull('finish_at') // Clave: buscar la asignación activa
                                                  ->with('operator') // Cargar la relación con el modelo Operator
                                                  ->first();

                // Si se encontró una asignación activa y el operario existe
                if ($activeOperatorPost && $activeOperatorPost->operator) {
                    $operatorName = $activeOperatorPost->operator->name;
                }
            }

            // Añadir el nombre del operario al objeto de la relación
            $relation->operator_name = $operatorName;
        }
        // --- FIN: Añadir información del operario ---

        return response()->json($relations, 200);
    }

    /**
     * Crear una relación.
     * POST /api/product-list-selecteds
     */
    public function store(Request $request)
    {
        // Validamos esperando arrays para los campos multiselect.
        $validated = $request->validate([
            'client_id'         => 'required|exists:product_lists,client_id',
            'rfid_reading_ids'  => 'nullable|array',
            'rfid_reading_ids.*'=> 'exists:rfid_readings,id',
            'modbus_ids'        => 'nullable|array',
            'modbus_ids.*'      => 'exists:modbuses,id',
            'sensor_ids'        => 'nullable|array',
            'sensor_ids.*'      => 'exists:sensors,id',
        ]);
    
        // Requerir que se seleccione al menos uno de los tres.
        if (empty($validated['rfid_reading_ids']) && empty($validated['modbus_ids']) && empty($validated['sensor_ids'])) {
            return response()->json(['error' => 'Debe seleccionar al menos RFID, Báscula o Sensor.'], 422);
        }
    
        // Buscar el producto asociado al client_id.
        $productList = ProductList::where('client_id', $validated['client_id'])->first();
        if (!$productList) {
            return response()->json(['error' => 'No se encontró un producto asociado al client_id.'], 404);
        }
    
        // Si se seleccionaron RFID, creamos una asignación por cada RFID.
        if (!empty($validated['rfid_reading_ids'])) {
            foreach ($validated['rfid_reading_ids'] as $rfidId) {
                // Cerrar asignaciones abiertas para este RFID.
                ProductListSelecteds::where('rfid_reading_id', $rfidId)
                    ->whereNull('finish_at')
                    ->update(['finish_at' => \Carbon\Carbon::now()]);
                
                // Crear nueva asignación para este RFID.
                $productListSelected = ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'rfid_reading_id' => $rfidId,
                    // Si existen modbus o sensor, usamos el primer valor seleccionado (ajustable según necesidades).
                    'modbus_id'       => !empty($validated['modbus_ids']) ? $validated['modbus_ids'][0] : null,
                    'sensor_id'       => !empty($validated['sensor_ids']) ? $validated['sensor_ids'][0] : null,
                ]);
    
                // NUEVA LÓGICA: Buscar en operator_post por rfid_reading_id con finish_at null.
                $operatorPost = OperatorPost::where('rfid_reading_id', $rfidId)
                                ->whereNull('finish_at')
                                ->first();


    
                if ($operatorPost) {
                    // Actualizar finish_at de la entrada encontrada.
                    $operatorPost->update(['finish_at' => \Carbon\Carbon::now()]);
                    
                    // Duplicar la entrada: copiamos los datos, eliminamos el id para crear un nuevo registro,
                    // dejamos finish_at en null y reiniciamos count a 0.
                    $newData = $operatorPost->toArray();
                    unset($newData['id']); // Aseguramos que se genere un nuevo ID.
                    $newData['finish_at'] = null;
                    $newData['count'] = 0;
                    //anadimos $newData['product_list_selected_id']=  donde se guarda el id de la nueva asignación para este RFID. de la esta create $productListSelected
                    $newData['product_list_selected_id'] = $productListSelected->id;          
                    $newData['product_list_id']= $productList->id;
                    
                    // Opcional: Si necesitas relacionar el nuevo registro con la asignación creada, podrías
                    // asignar 'product_list_selected_id' => $productListSelected->id en $newData.
                    OperatorPost::create($newData);
                }
            }
            $message = ['message' => 'Asignaciones creadas para los RFID seleccionados.'];
        } elseif (!empty($validated['modbus_ids'])) {
            // Si no se seleccionó RFID pero sí básculas.
            foreach ($validated['modbus_ids'] as $modbusId) {
                ProductListSelecteds::where('modbus_id', $modbusId)
                    ->whereNull('finish_at')
                    ->update(['finish_at' => \Carbon\Carbon::now()]);
                ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'modbus_id'       => $modbusId,
                    'sensor_id'       => !empty($validated['sensor_ids']) ? $validated['sensor_ids'][0] : null,
                ]);
            }
            $message = ['message' => 'Asignaciones creadas para las básculas seleccionadas.'];
        } elseif (!empty($validated['sensor_ids'])) {
            // Si solo se seleccionaron sensores.
            foreach ($validated['sensor_ids'] as $sensorId) {
                ProductListSelecteds::where('sensor_id', $sensorId)
                    ->whereNull('finish_at')
                    ->update(['finish_at' => \Carbon\Carbon::now()]);
                ProductListSelecteds::create([
                    'product_list_id' => $productList->id,
                    'sensor_id'       => $sensorId,
                ]);
            }
            $message = ['message' => 'Asignaciones creadas para los sensores seleccionados.'];
        } else {
            // Caso por defecto (si se envían combinaciones no contempladas).
            $relation = ProductListSelecteds::create([
                'product_list_id' => $productList->id,
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
