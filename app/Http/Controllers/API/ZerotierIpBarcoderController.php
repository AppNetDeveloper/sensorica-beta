<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Barcode;




class ZerotierIpBarcoderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/ip-zerotier",
     *     summary="Update the ZeroTier IP address for a barcode",
     *     tags={"Barcode"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="ipZerotier", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ipZerotier updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Token and ipZerotier are required",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function ipZerotier(Request $request)
    {
        // Obtener los valores de token y ipZerotier desde la solicitud
        $token = $request->input('token');
        $ipZerotier = $request->input('ipZerotier');

        // Validar que ambos valores están presentes
        if (empty($token) || empty($ipZerotier)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token and ipZerotier are required.'
            ], 400);
        }

        // Buscar el registro en la tabla 'barcodes' con el token proporcionado
        $barcode = Barcode::where('token', $token)->first();

        // Si no se encuentra el registro, devolver un error
        if (!$barcode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token.'
            ], 404);
        }

        // Actualizar el campo ip_zerotier con el valor proporcionado
        $barcode->ip_zerotier = $ipZerotier;
        $barcode->save();

        // Registrar la acción en los logs
        Log::info('Updated ip_zerotier for token.', [
            'token' => $token,
            'ip_zerotier' => $ipZerotier
        ]);

        // Devolver una respuesta de éxito
        return response()->json([
            'status' => 'success',
            'message' => 'ipZerotier updated successfully.'
        ]);
    }
}
