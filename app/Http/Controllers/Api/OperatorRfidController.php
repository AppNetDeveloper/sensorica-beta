<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperatorRfid;
use App\Models\Operator;
use App\Models\RfidReading;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Operator RFID API",
 *     version="1.0.0"
 * )
 */
class OperatorRfidController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/operator-rfid",
     *     summary="List all operator-rfid relations",
     *     tags={"OperatorRfid"},
     *     @OA\Response(
     *         response=200,
     *         description="List of relations",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/OperatorRfid"))
     *     )
     * )
     */
    public function index()
    {
        $relations = OperatorRfid::with(['operator', 'rfidReading'])->get();
        return response()->json($relations, 200);
    }
    /**
     * @OA\Post(
     *     path="/api/operator-rfid",
     *     summary="Create a new operator-rfid relation",
     *     tags={"OperatorRfid"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "rfid_reading_id"},
     *             @OA\Property(property="client_id", type="integer", example=1),
     *             @OA\Property(property="rfid_reading_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Relation created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OperatorRfid")
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:operators,client_id', // Validar client_id en lugar de operator_id
            'rfid_reading_id' => 'required|exists:rfid_readings,id',
        ]);

        // Obtener el operador correspondiente al client_id
        $operator = Operator::where('client_id', $validated['client_id'])->first();

        if (!$operator) {
            return response()->json(['error' => 'No se encontró un operador asociado al client_id.'], 404);
        }

        // Crear la relación utilizando el ID del operador encontrado
        $relation = OperatorRfid::create([
            'operator_id' => $operator->id, // Usar el ID real del operador
            'rfid_reading_id' => $validated['rfid_reading_id'],
        ]);

        return response()->json($relation, 201);
    }


    /**
     * @OA\Get(
     *     path="/api/operator-rfid/{id}",
     *     summary="Get a specific operator-rfid relation",
     *     tags={"OperatorRfid"},
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
     *         @OA\JsonContent(ref="#/components/schemas/OperatorRfid")
     *     )
     * )
     */
    public function show($id)
    {
        $relation = OperatorRfid::with(['operator', 'rfidReading'])->find($id);

        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }

        return response()->json($relation, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/operator-rfid/{id}",
     *     summary="Update an operator-rfid relation",
     *     tags={"OperatorRfid"},
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
     *             @OA\Property(property="rfid_reading_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relation updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/OperatorRfid")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $relation = OperatorRfid::find($id);

        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }

        $validated = $request->validate([
            'operator_id' => 'sometimes|exists:operators,id',
            'rfid_reading_id' => 'sometimes|exists:rfid_readings,id',
        ]);

        $relation->update($validated);

        return response()->json($relation, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/operator-rfid/{id}",
     *     summary="Delete an operator-rfid relation",
     *     tags={"OperatorRfid"},
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
        $relation = OperatorRfid::find($id);

        if (!$relation) {
            return response()->json(['error' => 'Relation not found'], 404);
        }

        $relation->delete();

        return response()->json(['message' => 'Relation deleted successfully'], 200);
    }
}
