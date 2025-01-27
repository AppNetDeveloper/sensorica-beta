<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatorPost; // Cambiado a OperatorPost
use App\Models\Operator;
use App\Models\RfidReading;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Operator Post API",
 *     version="1.0.0"
 * )
 */
class OperatorPostController extends Controller // Cambiado a OperatorPostController
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
            'client_id' => 'required|exists:operators,client_id', // Validar client_id en lugar de operator_id
            'rfid_reading_id' => 'required|exists:rfid_readings,id',
            'sensor_id' => 'nullable|exists:sensors,id',
            'modbus_id' => 'nullable|exists:modbuses,id',
        ]);

        // Obtener el operador correspondiente al client_id
        $operator = Operator::where('client_id', $validated['client_id'])->first();

        if (!$operator) {
            return response()->json(['error' => 'No se encontró un operador asociado al client_id.'], 404);
        }

        // Crear la relación utilizando el ID del operador encontrado
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
}
