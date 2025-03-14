<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HostList;
use App\Models\HostMonitor;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\ServerAlertMail;

class ServerMonitorController extends Controller
{
    /**
     * Registra un nuevo servidor en host_lists.
     *
     * @OA\Post(
     *     path="/api/register-server",
     *     summary="Registra un nuevo servidor",
     *     tags={"Server Registration"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"host","name"},
     *             @OA\Property(property="host", type="string", example="192.168.1.100"),
     *             @OA\Property(property="name", type="string", example="Servidor Xmart"),
     *             @OA\Property(property="emails", type="string", example="admin@example.com"),
     *             @OA\Property(property="phones", type="string", example="1234567890"),
     *             @OA\Property(property="telegrams", type="string", example="@xmart_server"),
     *             @OA\Property(property="token", type="string", example="opcional-token-personalizado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Servidor registrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Server registered successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function index(Request $request)
    {
        // Validar los datos recibidos
        $validated = $request->validate([
            'host' => 'required|string|unique:host_lists,host',
            'name' => 'required|string',
            'emails' => 'nullable|string',
            'phones' => 'nullable|string',
            'telegrams' => 'nullable|string',
            // Opcionalmente, se puede permitir que se envíe un token personalizado.
            'token' => 'nullable|string|unique:host_lists,token',
        ]);

        // Si no se envía token, generarlo de forma aleatoria
        $token = $validated['token'] ?? Str::random(60);

        // Crear el registro en host_lists
        $host = HostList::create([
            'host'     => $validated['host'],
            'name'     => $validated['name'],
            'token'    => $token,
            'emails'   => $validated['emails'] ?? null,
            'phones'   => $validated['phones'] ?? null,
            'telegrams'=> $validated['telegrams'] ?? null,
            // Si deseas asignar un usuario, podrías agregar 'user_id' aquí
            'user_id'  => $request->input('user_id'),
        ]);

        return response()->json([
            'message' => 'Server registered successfully.',
            'data'    => $host,
        ], 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'token' => 'required|exists:host_lists,token',
            'total_memory' => 'required|numeric',
            'memory_free' => 'required|numeric',
            'memory_used' => 'required|numeric',
            'memory_used_percent' => 'required|numeric',
            'disk' => 'required|numeric',
            'cpu' => 'required|numeric',
        ]);

        // Obtener el host mediante el token
        $host = HostList::where('token', $request->token)->first();

        // Llamar al método de limpieza de registros antiguos
        $this->deleteOldRecords($host);

        // Crear el registro en host_monitors
        $hostMonitor = HostMonitor::create([
            'id_host'             => $host->id,
            'total_memory'        => $request->total_memory,
            'memory_free'         => $request->memory_free,
            'memory_used'         => $request->memory_used,
            'memory_used_percent' => $request->memory_used_percent,
            'disk'                => $request->disk,
            'cpu'                 => $request->cpu,
        ]);

        // Verificar si alguna métrica excede el umbral (80%)
        if ($request->cpu > 80 || $request->memory_used_percent > 80 || $request->disk > 80) {

            // Consultar el registro anterior para este host (excluyendo el actual)
            $previousRecord = HostMonitor::where('id_host', $host->id)
                ->where('id', '<', $hostMonitor->id)
                ->orderBy('id', 'desc')
                ->first();

            // Si existe un registro anterior y también excede el umbral, no se envía la alerta
            $sendAlert = true;
            if ($previousRecord) {
                if ($previousRecord->cpu > 80 || $previousRecord->memory_used_percent > 80 || $previousRecord->disk > 80) {
                    $sendAlert = false;
                }
            }

            if ($sendAlert && $host->emails) {
                // Obtener los correos y separarlos por comas
                $emailList = array_map('trim', explode(',', $host->emails));

                // Preparar datos para el correo de alerta
                $alertData = [
                    'host' => $host->name,
                    'cpu' => $request->cpu,
                    'memory_used_percent' => $request->memory_used_percent,
                    'disk' => $request->disk,
                ];

                // Enviar correo usando el Mailable ServerAlertMail
                Mail::to($emailList)->send(new ServerAlertMail($alertData));
            }
        }

        return response()->json([
            'message' => 'Data stored successfully',
            'data'    => $hostMonitor,
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Eliminar registros antiguos de host_monitors.
     */
    private function deleteOldRecords(HostList $host)
    {
        $host->hostMonitors()
             ->where('created_at', '<', Carbon::now()->subDays(7))
             ->delete();
    }
}
