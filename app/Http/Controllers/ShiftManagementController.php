<?php

namespace App\Http\Controllers;

use App\Models\ShiftList;
use App\Models\ProductionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Barcode;
use App\Models\ShiftHistory;



use Monolog\Handler\StreamHandler;

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

        /**
     * Método público para recibir vía AJAX el evento de cambio de estado (por botón)
     * y publicar el mensaje MQTT.
     *
     * Los posibles eventos son:
     * - "inicio_trabajo"  → {type: "shift", action: "start"}
     * - "final_trabajo"   → {type: "shift", action: "end"}
     * - "inicio_comida"   → {type: "stop", action: "start"}
     * - "final_comida"    → {type: "stop", action: "end"}
     *
     * En todos los casos se incluye "description": "Manual".
     */
    public function publishShiftEvent(Request $request)
    {
        $request->validate([
            'production_line_id' => 'required|integer',
            'event' => 'required|string|in:inicio_trabajo,final_trabajo,inicio_comida,final_comida'
        ]);

        $productionLineId = $request->production_line_id;
        $event = $request->event;

        $data = [];
        switch($event) {
            case 'inicio_trabajo':
                $data['type'] = 'shift';
                $data['action'] = 'start';
                break;
            case 'final_trabajo':
                $data['type'] = 'shift';
                $data['action'] = 'end';
                break;
            case 'inicio_comida':
                $data['type'] = 'stop';
                $data['action'] = 'start';
                break;
            case 'final_comida':
                $data['type'] = 'stop';
                $data['action'] = 'end';
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid event'], 422);
        }
        $data['description'] = 'Manual';

        // Obtener el registro de Barcode para la línea de producción
        $barcode = Barcode::where('production_line_id', $productionLineId)->first();
        if (!$barcode) {
            return response()->json(['success' => false, 'message' => 'Barcode not found'], 404);
        }

        $mqttTopic = $barcode->mqtt_topic_barcodes . '/timeline_event';
        $jsonMessage = json_encode($data);

        $this->publishMqttMessage($mqttTopic, $jsonMessage);

        return response()->json(['success' => true, 'message' => 'MQTT message published']);
    }

    /**
     * Función privada para publicar un mensaje MQTT (simulada guardando el JSON en archivos).
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
