<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatorPost; // Modelo actualizado
use App\Models\Operator;
use App\Models\RfidReading;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;   // 👈 Asegúrate de que esta línea existe
use Illuminate\Support\Facades\DB;          // ← transacciones

/**
 * @OA\Info(
 *     title="Operator Post API",
 *     version="1.0.0"
 * )
 */
class OperatorPostController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/operator-post",
     *     summary="List all operator-post relations",
     *     tags={"OperatorPost"},
     *     @OA\Response(
     *         response=200,
     *         description="List of relations",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/OperatorPost"))
     *     )
     * )
     */
    public function index()
    {
        $relations = OperatorPost::with(['operator', 'rfidReading', 'sensor', 'modbus'])->get();
        return response()->json($relations, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/operator-post",
     *     summary="Create a new operator-post relation",
     *     tags={"OperatorPost"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "rfid_reading_id"},
     *             @OA\Property(property="client_id", type="integer", example=1),
     *             @OA\Property(property="rfid_reading_id", type="integer", example=2),
     *             @OA\Property(property="sensor_id", type="integer", example=3),
     *             @OA\Property(property="modbus_id", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Relation created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OperatorPost")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:operators,client_id',
            'rfid_reading_id' => 'required|exists:rfid_readings,id',
            'sensor_id' => 'nullable|exists:sensors,id',
            'modbus_id' => 'nullable|exists:modbuses,id',
        ]);

        $operator = Operator::where('client_id', $validated['client_id'])->first();

        if (!$operator) {
            return response()->json(['error' => 'No se encontró un operador asociado al client_id.'], 404);
        }

        $relation = OperatorPost::create([
            'operator_id' => $operator->id,
            'rfid_reading_id' => $validated['rfid_reading_id'],
            'sensor_id' => $validated['sensor_id'] ?? null,
            'modbus_id' => $validated['modbus_id'] ?? null,
        ]);

        return response()->json($relation, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/operator-post/{id}",
     *     summary="Get a specific operator-post relation",
     *     tags={"OperatorPost"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Relation ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relation details",
     *         @OA\JsonContent(ref="#/components/schemas/OperatorPost")
     *     )
     * )
     */
    public function show($id)
    {
        $relation = OperatorPost::with(['operator', 'rfidReading', 'sensor', 'modbus'])->find($id);

        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }

        return response()->json($relation, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/operator-post/{id}",
     *     summary="Update an operator-post relation",
     *     tags={"OperatorPost"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Relation ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="operator_id", type="integer", example=1),
     *             @OA\Property(property="rfid_reading_id", type="integer", example=2),
     *             @OA\Property(property="sensor_id", type="integer", example=3),
     *             @OA\Property(property="modbus_id", type="integer", example=4)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relation updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OperatorPost")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $relation = OperatorPost::find($id);

        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }

        $validated = $request->validate([
            'operator_id' => 'sometimes|exists:operators,id',
            'rfid_reading_id' => 'sometimes|exists:rfid_readings,id',
            'sensor_id' => 'nullable|exists:sensors,id',
            'modbus_id' => 'nullable|exists:modbuses,id',
        ]);

        $relation->update($validated);

        return response()->json($relation, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/operator-post/{id}",
     *     summary="Delete an operator-post relation",
     *     tags={"OperatorPost"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Relation ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relation deleted successfully",
     *         @OA\JsonContent(type="object", @OA\Property(property="message", type="string"))
     *     )
     * )
     */
    public function destroy($id)
    {
        $relation = OperatorPost::find($id);

        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }

        $relation->delete();

        return response()->json(['message' => 'Relation deleted successfully'], 200);
    }
        /**  🔐 Token fijo para todos los clientes */
    private const API_TOKEN = '915afe8b7fabc2ca8d759761b0fe159f88bf0f064be7e4cd6a1f99021eff4e3b';


    /**
     * PUT/POST: /api/operator-post/update-count
     *
     * 1. Comprueba el token Bearer.
     * 2. Valida id y count entrante.
     * 3. Calcula la diferencia con el valor actual.
     * 4. Actualiza:
     *      - operator_post.count      (nuevo valor)
     *      - operators.count_shift    (±diferencia)
     *      - operators.count_order    (±diferencia)
     * 5. Respuesta JSON con los nuevos valores.
     */
    public function updateCount(Request $request): JsonResponse
    {
        /* ─── 1) Autenticación por token ───────────────────────── */
        if ($request->bearerToken() !== self::API_TOKEN) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        /* ─── 2) Validación ────────────────────────────────────── */
        $validated = $request->validate([
            'id'    => 'required|integer|exists:operator_post,id',
            'count' => 'required|integer|min:0',
        ]);

        /* ─── 3-4) Operaciones atómicas en transacción ─────────── */
        $result = DB::transaction(function () use ($validated) {

            /* 3.a) Registro operator_post */
            $operatorPost = OperatorPost::lockForUpdate()->findOrFail($validated['id']); // lock evita race-condition
            $oldCount     = $operatorPost->count;
            $newCount     = $validated['count'];
            $difference   = $newCount - $oldCount;   // puede ser negativo, positivo o 0

            /* 3.b) Actualizar operator_post si hay cambio */
            if ($difference !== 0) {
                $operatorPost->count = $newCount;
                $operatorPost->save();
            }

            /* 3.c) Actualizar tabla operators (solo si existe el registro) */
            $operator = Operator::lockForUpdate()->find($operatorPost->operator_id);

            if ($operator && $difference !== 0) {
                $operator->count_shift += $difference;
                $operator->count_order += $difference;
                $operator->save();
            }

            return [
                'operatorPost' => $operatorPost,
                'operator'     => $operator,
                'difference'   => $difference,
                'oldCount'     => $oldCount,
                'newCount'     => $newCount,
            ];
        });

        /* ─── 5) Respuesta ─────────────────────────────────────── */
        return response()->json([
            'status'     => 'success',
            'message'    => 'Count actualizado correctamente.',
            'difference' => $result['difference'],                    // positivo = incremento
            'data'       => [
                'operator_post' => [
                    'id'           => $result['operatorPost']->id,
                    'count_old'    => $result['oldCount'],
                    'count_new'    => $result['newCount'],
                ],
                'operator' => $result['operator'] ? [
                    'id'            => $result['operator']->id,
                    'count_shift'   => $result['operator']->count_shift,
                    'count_order'   => $result['operator']->count_order,
                ] : null,
            ],
        ], 200);
    }
}
