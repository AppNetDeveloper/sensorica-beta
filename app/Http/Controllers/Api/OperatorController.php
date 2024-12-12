<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Operator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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
        $operators = Operator::all(['client_id as id', 'name', 'email', 'phone']);
        return response()->json($operators, 200);
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
        $newPassword = Str::random(8);
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
        $validated = $request->validate([
            'phone' => 'required|string'
        ]);

        $operator = Operator::where('phone', $validated['phone'])->first();
        if(!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        // Generar nueva contraseña aleatoria
        $newPassword = Str::random(8);
        $operator->password = Hash::make($newPassword);
        $operator->save();

        // Aquí en el futuro se llamará a la API de WhatsApp para enviar el mensaje
        // Por ahora, simplemente devolvemos un mensaje genérico
        return response()->json(['message' => 'Password reset successfully. (WhatsApp message would be sent here)'], 200);
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

        // Si de verdad quieres buscar por client_id
        $operator = Operator::where('client_id', $validated['operator_id'])->first();
        if (!$operator) {
            return response()->json(['error' => 'Operator not found'], 404);
        }

        if (Hash::check($validated['password'], $operator->password)) {
            return response()->json(['valid' => true], 200);
        } else {
            return response()->json(['valid' => false], 200);
        }
    }
}
