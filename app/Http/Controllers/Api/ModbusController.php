<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Modbus;
use Illuminate\Http\Request;
use App\Models\MqttSendServer1;
use App\Models\MqttSendServer2;
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
        //vamos a poner hora y fecha en este log
        Log::info( date('Y-m-d H:i:s') . ' Message sent to MQTT topic: ' . $topic . ' with value: ' . $inValue); // Guardar en el log

            // Preparar el segundo tópico MQTT y mensaje
        $secondTopic = $modbus->mqtt_topic . '1/dosage';
        $secondMessage = json_encode([
            'value' => $inValue,
            'time' => date('Y-m-d H:i:s')
        ]);

        // Publicar el segundo mensaje MQTT
        $this->publishMqttMessage($secondTopic, $secondMessage);

        // Log para el segundo mensaje
        Log::info(date('Y-m-d H:i:s') . ' Second message sent to MQTT topic: ' . $secondTopic . ' with value: ' . $inValue . ' and time: ' . date('Y-m-d H:i:s'));

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
            // Insertar en las tablas mqtt_send_server1 y mqtt_send_server2
            MqttSendServer2::createRecord($topic, $message);
            MqttSendServer1::createRecord($topic, $message);

            // Registro en logs
            Log::info("Stored message in both mqtt_send_server1 and mqtt_send_server2 tables. Topic: " . $topic);

        } catch (\Exception $e) {
            Log::error("Error storing message in databases: " . $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/modbus/tara",
     *     summary="Set tara value for a Modbus",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="value", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tara value set successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     ),
     *     @OA\Response(response=400, description="Token not provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     ),
     *     @OA\Response(response=404, description="Invalid token or Modbus not found",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     )
     * )
     */
    public function setTara(Request $request)
    {
        $token = trim($request->input('token'));
        $id = $request->input('id');
        $value = $request->input('value');
        $value = is_numeric($value) ? $value + 0 : 0;

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        $modbus = Modbus::where('token', $token)->where('id', $id)->first();
        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }

        $modbus->tara = $value;
        $modbus->save();

        return response()->json(['message' => 'Tara value set successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/modbus/tara/reset",
     *     summary="Reset tara value for a Modbus to zero",
     *     tags={"Modbus"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tara reset successfully",
     *         @OA\JsonContent(@OA\Property(property="message", type="string"))
     *     ),
     *     @OA\Response(response=400, description="Token not provided",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     ),
     *     @OA\Response(response=404, description="Invalid token or Modbus not found",
     *         @OA\JsonContent(@OA\Property(property="error", type="string"))
     *     )
     * )
     */
    public function resetTara(Request $request)
    {
        $token = trim($request->input('token'));
        $id = $request->input('id');

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 400);
        }

        $modbus = Modbus::where('token', $token)->where('id', $id)->first();
        if (!$modbus) {
            return response()->json(['error' => 'Invalid token or Modbus not found'], 404);
        }

        $modbus->tara = 0;
        $modbus->save();

        return response()->json(['message' => 'Tara reset successfully']);
    }
}
