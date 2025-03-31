<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Modbus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModbusController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/modbuses",
     *     summary="Get modbuses by customer token",
     *     tags={"Modbus"},
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="token", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getModbuses(Request $request)
    {
        $token = $request->query('token'); // Obtener el token de los parámetros de la consulta

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        // Busca el cliente usando el token
        $customer = Customer::where('token', $token)->first();

        if (!$customer) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        // Obtén los modbuses con model_name 'weight'
        $modbuses = Modbus::where('model_name', 'weight')->get(['name', 'token']);

        return response()->json($modbuses);
    }
       /**
     * @OA\Post(
     *     path="/api/modbus/send",
     *     summary="Send dosage value via MQTT",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="tolvaId", type="integer"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="value", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token not provided",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function sendDosage(Request $request)
    {
        // Obtener los datos del request
        $id = $request->input('id');
        $token = trim($request->input('token')); // Eliminar espacios en blanco y caracteres no visibles
        $inValue = $request->input('value'); // El valor ingresado
        $inValue = is_numeric($inValue) ? $inValue + 0 : 0;  // Convierte a número, si no es numérico, será 0
    
        // Validación básica
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }
    
        // Buscar el modbus utilizando el token
        $modbus = Modbus::where('token', $token)
                        ->where('id', $id)
                        ->first();
    
        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }
        
        
        // Modificar el tópico MQTT de peso a dosificación
        $topic = str_replace('peso', 'dosifica', $modbus->mqtt_topic_modbus);
    
        // Crear el JSON con el valor de dosificación
        $message = json_encode(['value' => $inValue]);
    
        // Publicar el mensaje MQTT y registrar en las tablas
        $this->publishMqttMessage($topic, $message);
    
        return response()->json(['message' => 'Dosage value sent successfully']);
    }
    

    public function setZero(Request $request)
    {
        // Obtener los datos del request
        $id = $request->input('id');
        $token = trim($request->input('token')); // Eliminar espacios en blanco y caracteres no visibles

        // Validación básica
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        // Buscar el modbus utilizando el token
        $modbus = Modbus::where('token', $token)
                        ->where('id', $id)
                        ->first();

        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }

        // Modificar el tópico MQTT de la tabla para usar 'zero'
        $topic = str_replace('peso', 'zero', $modbus->mqtt_topic_modbus);

        // Crear el JSON con el valor true para el comando zero
        $message = json_encode(['value' => true]);

        // Publicar el mensaje MQTT y registrar en las tablas
        $this->publishMqttMessage($topic, $message);

        return response()->json(['message' => 'Zero command sent successfully']);
    }


    // Función privada para enviar mensaje MQTT
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
