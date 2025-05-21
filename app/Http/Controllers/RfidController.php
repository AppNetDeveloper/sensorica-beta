<?php

namespace App\Http\Controllers;

use App\Models\RfidAnt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // Asegúrate de importar el cliente HTTP de Laravel
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class RfidController extends Controller
{
    /**
     * Muestra la lista de antenas RFID asociadas a la línea de producción.
     */
    public function index($production_line_id)
    {
        $rfidAnts = RfidAnt::where('production_line_id', $production_line_id)->get();

        return view('rfid.index', [
            'production_line_id' => $production_line_id,
            'rfidAnts' => $rfidAnts,
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva antena RFID.
     */
    public function create($production_line_id)
    {
        return view('rfid.form', [
            'rfidAnt' => new RfidAnt(),
            'production_line_id' => $production_line_id,
        ]);
    }

    /**
     * Almacena una nueva antena RFID en la base de datos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'mqtt_topic' => 'required|string|max:255',
            'token' => 'required|string|unique:rfid_ants,token|max:255',
            'production_line_id' => 'required|exists:production_lines,id',
        ]);

        RfidAnt::create($request->all());

        return redirect()->route('rfid.index', $request->production_line_id)
            ->with('status', __('Antena RFID creada exitosamente.'));
    }

    /**
     * Muestra el formulario para editar una antena RFID existente.
     */
    public function edit($id)
    {
        $rfidAnt = RfidAnt::findOrFail($id);

        return view('rfid.form', [
            'rfidAnt' => $rfidAnt,
            'production_line_id' => $rfidAnt->production_line_id,
        ]);
    }

    /**
     * Actualiza una antena RFID existente en la base de datos.
     */
    public function update(Request $request, $id)
    {
        $rfidAnt = RfidAnt::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'mqtt_topic' => 'required|string|max:255',
            'token' => 'required|string|max:255|unique:rfid_ants,token,' . $rfidAnt->id,
        ]);

        $rfidAnt->update($request->all());

        return redirect()->route('rfid.index', $rfidAnt->production_line_id)
            ->with('status', __('Antena RFID actualizada exitosamente.'));
    }

    /**
     * Elimina una antena RFID de la base de datos.
     */
    public function destroy($id)
    {
        $rfidAnt = RfidAnt::findOrFail($id);
        $productionLineId = $rfidAnt->production_line_id;

        $rfidAnt->delete();

        return redirect()->route('rfid.index', $productionLineId)
            ->with('status', __('Antena RFID eliminada exitosamente.'));
    }
    
    
    /**
     * Muestra el visualizador MQTT (basado en WebSocket, como lo tenías).
     */
    public function showMqttVisualizer(): View
    {
        // MODIFICADO: Añadir la variable $gatewayDataUrl
        $gatewayDataUrl = "/rfid-mqtt/api/gateway-data"; // Se define la URL para la API
        return view('rfid.visualizer', compact('gatewayDataUrl')); // Se pasa la variable a la vista
    }

    // --- NUEVOS MÉTODOS ---

    /**
     * Muestra la vista Blade para el visualizador AJAX de mensajes del gateway.
     */
    public function showAjaxVisualizer(): View
    {
        // Pasa la URL de la API de Laravel que obtendrá los datos del gateway Node.js
        $gatewayDataUrl = "/rfid-mqtt/api/gateway-data";
        return view('rfid.ajax_visualizer', compact('gatewayDataUrl'));
    }

    /**
     * Obtiene los mensajes del gateway Node.js y los devuelve como JSON.
     * Esta ruta será llamada por AJAX desde la vista ajax_visualizer.blade.php.
     */
    public function getGatewayMessages(Request $request): JsonResponse
    {
        // Obtener la URL base del servidor Node.js desde el .env
        // Asegúrate de que NODE_GATEWAY_URL esté definido en tu .env
        // Ejemplo: NODE_GATEWAY_URL=http://localhost:4003
        $nodeGatewayUrl = rtrim(env('NODE_GATEWAY_URL', 'http://localhost:4003'), '/');
        $apiUrl = $nodeGatewayUrl . '/api/gateway-messages';

        try {
            // Realizar la petición GET al servidor Node.js
            // Si tu servidor Node.js usa HTTPS con un certificado autofirmado y tienes problemas,
            // podrías necesitar ->withoutVerifying() para desarrollo, ¡pero cuidado en producción!
            // Ejemplo: $response = Http::withoutVerifying()->get($apiUrl);
            $response = Http::get($apiUrl);

            if ($response->successful()) {
                // Devolver los datos como JSON si la petición fue exitosa
                return response()->json($response->json());
            } else {
                // Devolver un error si la petición falló
                \Log::error("Error fetching data from Node.js gateway. Status: " . $response->status() . " Body: " . $response->body());
                return response()->json([
                    'error' => 'No se pudieron obtener los datos del gateway Node.js.',
                    'status_code' => $response->status(),
                    'details' => $response->body() // Puede ser útil para depurar
                ], $response->status());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Manejar errores de conexión (ej. el servidor Node.js no está corriendo)
            \Log::error("ConnectionException while fetching data from Node.js gateway: " . $e->getMessage());
            return response()->json([
                'error' => 'No se pudo conectar al gateway Node.js.',
                'message' => $e->getMessage()
            ], 503); // Service Unavailable
        } catch (\Exception $e) {
            // Manejar otros errores inesperados
            \Log::error("Unexpected exception while fetching data from Node.js gateway: " . $e->getMessage());
            return response()->json([
                'error' => 'Ocurrió un error inesperado.',
                'message' => $e->getMessage()
            ], 500); // Internal Server Error
        }
    }
}
