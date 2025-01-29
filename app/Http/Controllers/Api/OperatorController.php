<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
//anadir log
use Illuminate\Support\Facades\Log;
use App\Models\Scada;
use App\Models\ScadaOperatorLog;

class OperatorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/workers/update-or-insert",
     *     summary="Update or insert a single worker",
     *     tags={"Workers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="Client ID"),
     *             @OA\Property(property="name", type="string", description="Worker name"),
     *             @OA\Property(property="password", type="string", description="Worker password (optional)"),
     *             @OA\Property(property="email", type="string", description="Worker email (optional)"),
     *             @OA\Property(property="phone", type="string", description="Worker phone (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Worker updated or inserted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function updateOrInsertSingle(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',
            'password' => 'nullable|string',
            'email' => 'nullable|string|email',
            'phone' => 'nullable|string'
        ]);

        $operator = Operator::where('client_id', $validated['id'])->first();

        $dataToUpdate = [
            'name' => $validated['name']
        ];

        if(isset($validated['password']) && $validated['password']) {
            $dataToUpdate['password'] = Hash::make($validated['password']);
        }

        if(isset($validated['email'])) {
            $dataToUpdate['email'] = $validated['email'];
        }

        if(isset($validated['phone'])) {
            $dataToUpdate['phone'] = $validated['phone'];
        }

        if ($operator) {
            $operator->update($dataToUpdate);
        } else {
            $dataToUpdate['client_id'] = $validated['id'];
            Operator::create($dataToUpdate);
        }

        return response()->json(['message' => 'Operator updated or inserted successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/workers/replace-all",
     *     summary="Replace all Workers",
     *     tags={"Workers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="Client ID"),
     *                 @OA\Property(property="name", type="string", description="Workers name"),
     *                 @OA\Property(property="password", type="string", description="Worker password (optional)"),
     *                 @OA\Property(property="email", type="string", description="Worker email (optional)"),
     *                 @OA\Property(property="phone", type="string", description="Worker phone (optional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="All workers replaced successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function replaceAll(Request $request)
    {
        $validated = $request->validate([
            '*.id' => 'required|integer',
            '*.name' => 'required|string',
            '*.password' => 'nullable|string',
            '*.email' => 'nullable|string|email',
            '*.phone' => 'nullable|string',
        ]);

        // Delete all current records
        Operator::truncate();

        // Insert the new list
        foreach ($validated as $item) {
            $data = [
                'client_id' => $item['id'],
                'name' => $item['name'],
                'email' => $item['email'] ?? null,
                'phone' => $item['phone'] ?? null
            ];

            if(isset($item['password']) && $item['password']) {
                $data['password'] = Hash::make($item['password']);
            }

            Operator::create($data);
        }

        return response()->json(['message' => 'All operators replaced successfully'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/workers/list-all",
     *     summary="Get all workers",
     *     tags={"Workers"},
     *     @OA\Response(
     *         response=200,
     *         description="Returns all operators",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="Client ID"),
     *                 @OA\Property(property="name", type="string", description="Operator Name"),
     *                 @OA\Property(property="email", type="string", description="Operator Email"),
     *                 @OA\Property(property="phone", type="string", description="Operator Phone")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function listAll(Request $request)
    {
        // Obtener todos los operadores
        $operators = Operator::all(['client_id as id', 'name', 'email', 'phone', 'count_shift', 'count_order'])->toArray();
    
        // Obtener todos los colores RFID
        $rfidColors = \App\Models\RfidColor::all(['id', 'name']);
    
        // Fusionar los datos, manteniendo los operadores como el JSON original y agregando rfid_colors al final
        return response()->json(array_merge($operators, [['rfid_colors' => $rfidColors]]), 200);
    }
    /**
     * @OA\Get(
     *     path="/api/workers/{id}",
     *     summary="Get a single worker by ID",
     *     tags={"Workers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Client ID of the worker",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Worker found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Worker not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $operator = Operator::where('client_id', $id)->first();
        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        return response()->json([
            'id' => $operator->client_id,
            'name' => $operator->name,
            'email' => $operator->email,
            'phone' => $operator->phone
            // Nota: No retornamos password por seguridad
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/workers/{id}",
     *     summary="Delete a single worker by ID",
     *     tags={"Workers"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Client ID of the worker to delete",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Worker deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Worker not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $operator = Operator::where('client_id', $id)->first();

        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        $operator->delete();

        return response()->json(['message' => 'Operator deleted successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/workers/reset-password-email",
     *     summary="Reset worker password by email",
     *     tags={"Workers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", description="Operator's email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successfully and email sent",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Operator not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function resetPasswordByEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email'
        ]);

        $operator = Operator::where('email', $validated['email'])->first();
        if(!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        // Generar nueva contraseña aleatoria
        $newPassword = substr(str_shuffle(str_repeat('0123456789', 8)), 0, 8);
        $operator->password = Hash::make($newPassword);
        $operator->save();

        // Enviar email con la nueva contraseña
        // Usando el Mail Facade (configurando MAIL_* en .env)
        try {
            Mail::raw("Su nueva contraseña es: $newPassword", function($msg) use ($operator) {
                $msg->to($operator->email)->subject('Reseteo de contraseña');
            });
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error sending email: '.$e->getMessage()], 500);
        }

        return response()->json(['message' => 'Password reset and email sent successfully'], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/workers/reset-password-whatsapp",
     *     summary="Reset worker password by whatsapp",
     *     tags={"Workers"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="phone", type="string", description="Operator's phone")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset and would send whatsapp message",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Operator not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function resetPasswordByWhatsapp(Request $request)
    {
        Log::info('Request Data: ' . json_encode($request->all()));
        // Validar la solicitud
        $request->merge(['phone' => (string) $request->phone]);
        $validated = $request->validate([
            'phone' => 'required|string'
        ]);
        // Buscar al operador por teléfono
        $operator = Operator::where('phone', $validated['phone'])->first();

        if (!$operator) {
            Log::error('Operator not found with phone: ' . $validated['phone']);
            return response()->json(['error' => 'Operator not found'], 404);
        }else{
            Log::info('Operator found with phone: ' . $validated['phone']);
        }

        // Generar una nueva contraseña aleatoria
        $newPassword = substr(str_shuffle(str_repeat('0123456789', 8)), 0, 8);
        Log::info('Nueva contrasena: ' . $newPassword);
        // Hashear y guardar la nueva contraseña
        $operator->password = Hash::make($newPassword);
        $operator->save();
        Log::info('Contraseña guardada en db: ' . $newPassword);

        // Preparar el mensaje de WhatsApp
        $phoneNumber = $operator->phone;
        $message = "Tu contraseña se ha reseteado correctamente. Nueva contraseña: $newPassword";
        Log::info('Generated WhatsApp message: ' . $message);

        // URL de la API de WhatsApp
        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . "/api/send-message";
        Log::info('WhatsApp API URL: ' . $apiUrl);

        $requestData = [
            'jid' => $phoneNumber . '@s.whatsapp.net',
            'message' => $message,
        ];

        Log::info('Request Data to WhatsApp API: ' . json_encode($requestData));

        try {
            // Realizar la llamada a la API de WhatsApp
            $response = Http::post($apiUrl, $requestData);

            Log::info('WhatsApp API Response Status: ' . $response->status());
            Log::info('WhatsApp API Response Body: ' . $response->body());

            if ($response->successful()) {
                return response()->json([
                    'message' => 'Password reset successfully and sent via WhatsApp.'
                ], 200);
            }

            return response()->json([
                'error' => 'Failed to send WhatsApp message. Please try again.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Exception when calling WhatsApp API: ' . $e->getMessage());
            Log::error('Full exception details: ' . $e);
            return response()->json([
                'error' => 'Error connecting to the WhatsApp API. Please try again later.'
            ], 500);
        }
    }
    /**
 * @OA\Post(
 *     path="/api/workers/verify-password",
 *     summary="Verify the password of a worker",
 *     tags={"Workers"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="operator_id", type="integer", description="Client ID of the worker"),
 *             @OA\Property(property="password", type="string", description="Password to verify")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Returns valid: true if password correct, false otherwise",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="valid", type="boolean")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Worker not found"
 *     )
 * )
 */
public function verifyPassword(Request $request)
{
    $validated = $request->validate([
        'operator_id' => 'required|integer',
        'password' => 'required|string'
    ]);

    // Buscar al operador por client_id
    $operator = Operator::where('client_id', $validated['operator_id'])->first();
    if (!$operator) {
        return response()->json(['error' => 'Operator not found'], 404);
    }

    // Si la contraseña recibida es 'nullo' y el campo password en la DB es NULL
    if ($validated['password'] === 'nullo' && is_null($operator->password)) {
        return response()->json(['valid' => true], 200);
    }

    // Si la contraseña no es 'nullo', verificar normalmente usando Hash
    if (!is_null($operator->password) && Hash::check($validated['password'], $operator->password)) {
        return response()->json(['valid' => true], 200);
    }

    // En cualquier otro caso, la contraseña no es válida
    return response()->json(['valid' => false], 200);
}

    public function logScadaAccess(Request $request)
    {
        Log::info('Inicio del registro del login realizado por SCADA.');
        Log::info('Datos recibidos en la solicitud:', $request->all());

        // Mapear 'tokenscada' a 'token' si existe
        if ($request->has('tokenscada')) {
            $request->merge(['token' => $request->input('tokenscada')]);
        }

        // Validar los datos de entrada
        try {
            $validated = $request->validate([
                'operator_id' => 'required|integer', // Esto es realmente el client_id del operador
                'token' => 'required|string', // Token de SCADA
            ]);
            Log::info('Datos validados correctamente.', $validated);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Error de validación:', $e->errors());
            return response()->json(['error' => 'Validation error', 'details' => $e->errors()], 422);
        }

        // Buscar el operador por client_id
        $operator = Operator::where('client_id', $validated['operator_id'])->first();
        if (!$operator) {
            Log::error('Operador no encontrado con client_id: ' . $validated['operator_id']);
            return response()->json(['error' => 'Operator not found'], 404);
        }
        Log::info('Operador encontrado:', ['id' => $operator->id, 'client_id' => $operator->client_id, 'name' => $operator->name]);

        // Buscar SCADA por token
        $scada = Scada::where('token', $validated['token'])->first();
        if (!$scada) {
            Log::error('SCADA no encontrado con token: ' . $validated['token']);
            return response()->json(['error' => 'SCADA not found'], 404);
        }
        Log::info('SCADA encontrado:', ['id' => $scada->id, 'name' => $scada->name]);

        // Registrar en la tabla ScadaOperatorLog
        try {
            ScadaOperatorLog::create([
                'operator_id' => $operator->id, // Usar el ID del operador encontrado
                'scada_id' => $scada->id,
            ]);
            Log::info('Login registrado exitosamente.', ['operator_id' => $operator->id, 'scada_id' => $scada->id]);
        } catch (\Exception $e) {
            Log::error('Error al registrar el login:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Error logging access', 'details' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Login registrado exitosamente'], 200);
    }
    public function getLoginsByScadaToken(Request $request)
    {
        Log::info('Inicio de búsqueda de logins por token de SCADA.');

        // Validar el token recibido
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        Log::info('Token recibido:', ['token' => $validated['token']]);

        // Buscar SCADA por token
        $scada = Scada::where('token', $validated['token'])->first();

        if (!$scada) {
            Log::error('SCADA no encontrado con token: ' . $validated['token']);
            return response()->json(['error' => 'SCADA not found'], 404);
        }

        Log::info('SCADA encontrado:', ['id' => $scada->id, 'name' => $scada->name]);

        // Buscar los registros en ScadaOperatorLog por scada_id
        $logs = ScadaOperatorLog::with('operator') // Cargar la relación con operadores
            ->where('scada_id', $scada->id)
            ->get();

        if ($logs->isEmpty()) {
            Log::info('No se encontraron registros de logins para SCADA con id: ' . $scada->id);
            return response()->json(['message' => 'No login records found for this SCADA'], 200);
        }

        Log::info('Registros de logins encontrados:', ['count' => $logs->count()]);

        // Devolver los registros encontrados
        return response()->json([
            'scada' => [
                'id' => $scada->id,
                'name' => $scada->name,
            ],
            'logs' => $logs->map(function ($log) {
                return [
                    'operator_id' => $log->operator_id,
                    'operator_name' => $log->operator->name,
                    'logged_at' => $log->created_at,
                ];
            }),
        ], 200);
    }
}
