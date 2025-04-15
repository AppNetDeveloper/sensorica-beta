<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\Barcode;

class ShiftEventController extends Controller
{
    /**
     * Método público para recibir vía AJAX el evento de cambio de estado (por botón)
     * utilizando el token de la producción en lugar de production_line_id y recibiendo
     * también el campo operator_id. Se publica el mensaje MQTT con la siguiente estructura:
     *
     * Los posibles eventos son:
     * - "inicio_trabajo"  → {type: "shift", action: "start"}
     * - "final_trabajo"   → {type: "shift", action: "end"}
     * - "inicio_pausa"    → {type: "stop", action: "start"}
     * - "final_pausa"     → {type: "stop", action: "end"}
     *
     * Además se incluyen:
     * - "description": "Manual"
     * - "operator_id": valor recibido en la petición
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function publishShiftEvent(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'production_line_token' => 'required|string',
            'event' => 'required|string|in:inicio_trabajo,final_trabajo,inicio_pausa,final_pausa',
            'operator_id' => 'required|integer'
        ]);

        $productionLineToken = $request->production_line_token;
        $event = $request->event;
        $operatorId = $request->operator_id;
        $shiftListId=$request->shift_id;

        // Buscar la línea de producción utilizando el token recibido.
        $productionLine = ProductionLine::where('token', $productionLineToken)->first();
        if (!$productionLine) {
            return response()->json([
                'success' => false,
                'message' => 'Production Line not found'
            ], 404);
        }
        
        // Armar la estructura del mensaje en función del evento recibido.
        $data = [];
        switch ($event) {
            case 'inicio_trabajo':
                $data['type'] = 'shift';
                $data['action'] = 'start';
                break;
            case 'final_trabajo':
                $data['type'] = 'shift';
                $data['action'] = 'end';
                break;
            case 'inicio_pausa':
                $data['type'] = 'stop';
                $data['action'] = 'start';
                break;
            case 'final_pausa':
                $data['type'] = 'stop';
                $data['action'] = 'end';
                break;
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid event'
                ], 422);
        }
        
        // Agregar los campos adicionales al mensaje
        $data['description'] = 'Manual';
        $data['operator_id'] = $operatorId;
        $data['shift_list_id'] = $shiftListId;

        // Obtener el registro de Barcode asociado a la línea de producción
        $barcode = Barcode::where('production_line_id', $productionLine->id)->first();
        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode not found'
            ], 404);
        }

        // Definir el tópico MQTT y codificar el mensaje en formato JSON
        $mqttTopic = $barcode->mqtt_topic_barcodes . '/timeline_event';
        $jsonMessage = json_encode($data);

        // Publicar el mensaje MQTT
        // Se asume la implementación del método publishMqttMessage() para la publicación
        $this->publishMqttMessage($mqttTopic, $jsonMessage);

        return response()->json([
            'success' => true,
            'message' => 'Cambio en turno registrado con éxito'
        ]);
    }

    /**
     * Método para la publicación del mensaje MQTT.
     * En este método se debe implementar la lógica para la conexión y envío del mensaje 
     * a través de la librería MQTT que se esté utilizando.
     *
     * @param string $topic
     * @param string $message
     */
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Preparar los datos, agregando fecha y hora
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];

            $jsonData = json_encode($data);

            // Sanitizar el topic para evitar crear subcarpetas
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generar un identificador único en milisegundos
            $uniqueId = round(microtime(true) * 1000);

            // Guardar en servidor 1
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            file_put_contents($fileName1, $jsonData . PHP_EOL);
            \Log::info("Message stored in file (server1): {$fileName1}");

            // Guardar en servidor 2
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
            \Log::info("Message stored in file (server2): {$fileName2}");
        } catch (\Exception $e) {
            \Log::error("Error storing message in file: " . $e->getMessage());
        }
    }
}
