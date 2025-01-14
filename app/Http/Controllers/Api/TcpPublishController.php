<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Env;
use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="API TCP Message Publisher",
 *     version="1.0.0"
 * )
 */
class TcpPublishController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/publish-message",
     *     summary="Publica un mensaje en el servidor TCP",
     *     tags={"TCP Operations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="'model': 'api_queue_print','token': 'bbc3d2fd39027b9fsdsdsd', 'value':'1', 'url_back': 'tcp', 'token_back': 'TU-BARCODER-O-LO-QUE-NECESITAS_IDENTIFICADOR'")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje enviado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="string", example="Mensaje enviado correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Hubo un error al enviar el mensaje"
     *     )
     * )
     */
    public function publishMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255', // Ajusta las validaciones según tus necesidades
        ]);

        $host = env('TCP_SERVER', '127.0.0.1');
        $port = env('TCP_PORT', 8000);

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            Log::error("Error al crear el socket: " . socket_strerror(socket_last_error()));
            return response()->json(['error' => 'Error al crear la conexión'], 500);
        }

        $result = socket_connect($socket, $host, $port);
        if ($result === false) {
            Log::error("Error al conectar al servidor: " . socket_strerror(socket_last_error($socket)));
            socket_close($socket);
            return response()->json(['error' => 'No se pudo conectar al servidor'], 500);
        }

        $message = $request->input('message') . "\n"; // Añadimos un salto de línea para indicar el fin del mensaje
        $bytesSent = socket_write($socket, $message, strlen($message));

        if ($bytesSent === false) {
            Log::error("Error al enviar el mensaje: " . socket_strerror(socket_last_error($socket)));
            socket_close($socket);
            return response()->json(['error' => 'Error al enviar el mensaje'], 500);
        }

        socket_close($socket);

        return response()->json(['success' => 'Mensaje enviado correctamente'], 200);
    }
}