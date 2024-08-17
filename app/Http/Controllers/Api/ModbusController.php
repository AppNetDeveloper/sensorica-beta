<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Modbus;
use Illuminate\Http\Request;

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
}
