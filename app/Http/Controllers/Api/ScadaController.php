<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scada;
use App\Models\Modbus;
use App\Models\ScadaList;
use Illuminate\Http\Request;
use App\Models\ScadaOrder;
use App\Models\ScadaMaterialType;
//anadir Log
use Illuminate\Support\Facades\Log;
use Exception;

class ScadaController extends Controller
{
    public function getModbusesByScadaToken($token)
    {
        // Buscar el registro en scada por el token
        $scada = Scada::where('token', $token)->first();

        if (!$scada) {
            return response()->json(['error' => 'Scada not found'], 404);
        }

        // Obtener el production_line_id de scada
        $productionLineId = $scada->production_line_id;

                // Buscar el scada_order con las condiciones dadas
        $scadaOrder = ScadaOrder::where('scada_id', $scada->id)
        ->where('status', 1)
        ->orderBy('orden', 'asc')
        ->first();

        // Si no se cumple la condición de status = 1, buscar con status = 0
        if (!$scadaOrder) {
            $scadaOrder = ScadaOrder::where('scada_id', $scada->id)
                ->where('status', 0)
                ->orderBy('orden', 'asc')
                ->first();
        }

        // Obtener el scada_name y scada_order de scada_order o valores predeterminados
        $scadaName = $scada->name;
        $scadaOrderValue =  $scadaOrder->order_id ?? 'N/A';
        $scadaOrderUpdateDateTime = $scadaOrder->updated_at ?? '0000-00-00 00:00:00';
        $scadaOrderId =  $scadaOrder->id ?? 'N/A';

        // Buscar en modbuses donde production_line_id coincida y model_name sea 'weight'
        $modbuses = Modbus::where('production_line_id', $productionLineId)
                          ->where('model_name', 'weight')
                          ->get();

        if ($modbuses->isEmpty()) {
            return response()->json(['error' => 'No modbus lines found'], 404);
        }

        // Crear un array de respuesta con los datos relevantes de cada línea
        $modbusData = $modbuses->map(function ($modbus) {
            // Buscar en scada_list por el modbus_id actual
            $scadaList = ScadaList::where('modbus_id', $modbus->id)->first();

            // Si encontramos el registro en scada_list, extraemos fillinglevels, material_type, m3 y density
            if ($scadaList) {
                $materialType = $scadaList->materialType;
                $materialTypeName = $materialType ? $materialType->name : 'N/A';
                $density = $materialType ? $materialType->density : 'N/A';

                return [
                    'id' => $modbus->id,
                    'name' => $modbus->name,
                    'mqtt_topic_modbus' => $modbus->mqtt_topic_modbus,
                    'dimension' => $modbus->dimension,
                    'token' => $modbus->token,
                    'max_kg' => $modbus->max_kg,
                    'last_kg' => $modbus->last_kg,
                    'rec_box' => $modbus->rec_box,
                    'last_value' => $modbus->last_value,
                    'fillinglevels' => $scadaList->fillinglevels,  // Añadir fillinglevels
                    'material_type' => $materialTypeName,           // Añadir nombre del material
                    'density' => $density,                          // Añadir density del material
                    'm3' => $scadaList->m3,                          // Añadir m3
                    'tara' => $modbus->tara, //tara de la mosbus
                ];
            }

            // Si no se encuentra en scada_list, devolver valores por defecto
            return [
                'id' => $modbus->id,
                'name' => $modbus->name,
                'mqtt_topic_modbus' => $modbus->mqtt_topic_modbus,
                'dimension' => $modbus->dimension,
                'token' => $modbus->token,
                'max_kg' => $modbus->max_kg,
                'last_kg' => $modbus->last_kg,
                'rec_box' => $modbus->rec_box,
                'last_value' => $modbus->last_value,
                'fillinglevels' => 'N/A',
                'material_type' => 'N/A',
                'density' => 'N/A',
                'm3' => 'N/A',
                'tara'=> '0',
            ];
        });

        // Incluir el name de la línea scada en el JSON
        $response = [
            'scada_name' => $scadaName,
            'scada_order' => $scadaOrderValue,
            'scada_order_id' => $scadaOrderId,
            'scada_order_update_time' => $scadaOrderUpdateDateTime,
            'modbus_lines' => $modbusData
        ];

        return response()->json($response, 200);
    }

    /**
     * Actualiza el material asociado a una línea de Modbus en ScadaList.
     *
     * @param Request $request
     * @param int $modbusId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateMaterialForModbus(Request $request, $modbusId)
    {
        // Validar los datos entrantes
        $validatedData = $request->validate([
            'material_type_id' => 'required|exists:scada_material_type,id',  // Asegúrate de que el id del material existe
        ]);

        // Buscar la línea en scada_list usando el modbus_id
        $scadaList = ScadaList::where('modbus_id', $modbusId)->first();

        if (!$scadaList) {
            return response()->json(['error' => 'ScadaList not found for the given Modbus ID'], 404);
        }

        // Actualizar el material_type_id
        $scadaList->material_type_id = $validatedData['material_type_id'];
        $scadaList->save();  // Guardar los cambios en la base de datos

        try {
            // Obtener el nombre del material desde scada_material_type
            $material = ScadaMaterialType::find($validatedData['material_type_id']);
            if (!$material) {
                return response()->json(['error' => 'Material not found'], 404);
            }
        } catch (Exception $e) {
            return response()->json(['error' => 'Error retrieving material: ' . $e->getMessage()], 500);
        }

        // Preparar el tópico MQTT y mensaje
        $topic = $scadaList->modbus->mqtt_topic . '1/material';
        $message = json_encode([
            'value' => $material->name,
            'time' => date('Y-m-d H:i:s')
        ]);

        // Publicar el mensaje MQTT
        $this->publishMqttMessage($topic, $message);

        // Log para el mensaje MQTT
        Log::info(date('Y-m-d H:i:s') . ' Message sent to MQTT topic: ' . $topic . ' with value: ' . $material->name . ' and time: ' . date('Y-m-d H:i:s'));

        // Devolver una respuesta de éxito
        return response()->json(['message' => 'Material updated successfully'], 200);
    }
    private function publishMqttMessage($topic, $message)
    {

        try {
            // Preparar los datos a almacenar, agregando la fecha y hora
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];
        
            // Convertir a JSON
            $jsonData = json_encode($data);
        
            // Sanitizar el topic para evitar creación de subcarpetas
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generar un identificador único (por ejemplo, usando microtime)
            $uniqueId = round(microtime(true) * 1000); // milisegundos
        
            // Guardar en servidor 1
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            file_put_contents($fileName1, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server1): {$fileName1}");
        
            // Guardar en servidor 2
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            //Log::info("Mensaje almacenado en archivo (server2): {$fileName2}");
        } catch (\Exception $e) {
            Log::error("Error storing message in file: " . $e->getMessage());
        }
    }

}
