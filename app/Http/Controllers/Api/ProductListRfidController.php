<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductListRfid;
use App\Models\ProductList;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API de Relaciones entre Productos y RFID",
 *      description="APIs para gestionar relaciones entre ProductList y RfidReading",
 * )
 */
class ProductListRfidController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/product-list-rfids",
     *     summary="Listar todas las relaciones",
     *     tags={"ProductListRfid"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de relaciones",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/ProductListRfid")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $relations = ProductListRfid::with(['productList', 'rfidReading'])->get();
        return response()->json($relations, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/product-list-rfids",
     *     summary="Crear una nueva relación",
     *     tags={"ProductListRfid"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "rfid_reading_id"},
     *             @OA\Property(property="client_id", type="integer", example=23613092),
     *             @OA\Property(property="rfid_reading_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Relación creada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductListRfid")
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validar que el client_id y rfid_reading_id existan
        $validated = $request->validate([
            'client_id' => 'required|exists:product_lists,client_id', // Validar client_id
            'rfid_reading_id' => 'required|exists:rfid_readings,id', // Validar rfid_reading_id
        ]);

        // Obtener el id correspondiente al client_id
        $productList = ProductList::where('client_id', $validated['client_id'])->first();

        if (!$productList) {
            return response()->json(['error' => 'No se encontró un producto asociado al client_id.'], 404);
        }

        // Crear la relación utilizando el id obtenido
        $relation = ProductListRfid::create([
            'product_list_id' => $productList->id,
            'rfid_reading_id' => $validated['rfid_reading_id'],
        ]);

        return response()->json($relation, 201);
    }


    

    /**
     * @OA\Get(
     *     path="/api/product-list-rfids/{id}",
     *     summary="Obtener una relación específica",
     *     tags={"ProductListRfid"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la relación",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la relación",
     *         @OA\JsonContent(ref="#/components/schemas/ProductListRfid")
     *     )
     * )
     */
    public function show($id)
    {
        $relation = ProductListRfid::with(['productList', 'rfidReading'])->find($id);

        if (!$relation) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        return response()->json($relation, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/product-list-rfids/{id}",
     *     summary="Actualizar una relación existente",
     *     tags={"ProductListRfid"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="product_list_id", type="integer", example=1),
     *             @OA\Property(property="rfid_reading_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relación actualizada correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/ProductListRfid")
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $relation = ProductListRfid::find($id);

        if (!$relation) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        $validated = $request->validate([
            'product_list_id' => 'sometimes|exists:product_lists,id',
            'rfid_reading_id' => 'sometimes|exists:rfid_readings,id',
        ]);

        $relation->update($validated);

        return response()->json($relation, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/product-list-rfids/{id}",
     *     summary="Eliminar una relación",
     *     tags={"ProductListRfid"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la relación",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Relación eliminada correctamente"
     *     )
     * )
     */
    public function destroy($id)
    {
        $relation = ProductListRfid::find($id);

        if (!$relation) {
            return response()->json(['message' => 'Relación no encontrada'], 404);
        }

        $relation->delete();

        return response()->json(['message' => 'Relación eliminada correctamente'], 200);
    }
}
