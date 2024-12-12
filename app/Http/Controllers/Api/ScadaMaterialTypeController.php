<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scada;
use App\Models\ScadaMaterialType;
use Illuminate\Http\Request;

class ScadaMaterialTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/scada-material/{token}",
     *     summary="Obtener los materiales de un SCADA por su token",
     *     tags={"Scada Materials"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token único del SCADA",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="abc123"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de materiales asociados al SCADA",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Material 1")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="SCADA o materiales no encontrados",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Scada or materials not found")
     *         )
     *     )
     * )
     */

    public function getScadaMaterialByToken($token)
    {
        // Buscar el registro en Scada por el token
        $scada = Scada::where('token', $token)->first();

        if (!$scada) {
            return response()->json(['error' => 'Scada not found'], 404);
        }

        // Obtener los ScadaMaterialType relacionados con el Scada
        $materials = ScadaMaterialType::where('scada_id', $scada->id)->get();

        if ($materials->isEmpty()) {
            return response()->json(['error' => 'No materials found'], 404);
        }

        // Crear un array de respuesta con los nombres de los materiales
        $materialNames = $materials->map(function ($material) {
            return ['id' => $material->id,
            'name' => $material->name
        ];
        });

        // Retornar el resultado en JSON
        return response()->json($materialNames, 200);
    }

        /**
     * @OA\Get(
     *     path="/api/scada/{token}/material-types",
     *     summary="Listar materiales de SCADA",
     *     tags={"Scada Material Types"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de SCADA",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Listado exitoso"),
     *     @OA\Response(response=404, description="SCADA no encontrado")
     * )
     */
    public function index($token)
    {
        // Buscar el registro en Scada por el token
        $scada = Scada::where('token', $token)->first();

        if (!$scada) {
            return response()->json(['error' => 'Scada not found'], 404);
        }

        // Obtener los ScadaMaterialType relacionados con el Scada
        $materials = ScadaMaterialType::where('scada_id', $scada->id)->get();

        if ($materials->isEmpty()) {
            return response()->json(['error' => 'No materials found'], 404);
        }

        // Crear un array de respuesta con los nombres de los materiales
        $materialNames = $materials->map(function ($material) {
            return [
                'id' => $material->id,
                'name' => $material->name,
                'client_id' => $material->client_id,
                'service_type' => $material->service_type,
                'density' => $material->density // Añadimos más detalles si es necesario
            ];
        });

        // Retornar el resultado en JSON
        return response()->json($materialNames, 200);
    }


    /**
     * @OA\Post(
     *     path="/api/scada/{token}/material-types",
     *     summary="Crear material en SCADA",
     *     tags={"Scada Material Types"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de SCADA",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "density"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="density", type="string"),
     *             @OA\Property(property="service_type", type="integer"),
     *             @OA\Property(property="client_id", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Creado exitosamente"),
     *     @OA\Response(response=404, description="SCADA no encontrado")
     * )
     */
    public function store(Request $request, $token)
    {
        try {
            $scada = Scada::where('token', $token)->firstOrFail();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'density' => 'required|string|max:255',
                'client_id' => 'required|string|max:255',
                'service_type' => 'required|integer|in:0,1',
            ]);

            $materialType = $scada->materialTypes()->create($validated);

            return response()->json($materialType, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/scada/{token}/material-types/{id}",
     *     summary="Obtener un material específico",
     *     tags={"Scada Material Types"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de SCADA",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del material",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Material encontrado"),
     *     @OA\Response(response=404, description="SCADA o material no encontrado")
     * )
     */
    public function show($token, $id)
    {
        $scada = Scada::where('token', $token)->firstOrFail();
        $materialType = $scada->materialTypes()->findOrFail($id);

        return response()->json($materialType, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/scada/{token}/material-types/{id}",
     *     summary="Actualizar un material",
     *     tags={"Scada Material Types"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de SCADA",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del material",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="density", type="string"),
     *             @OA\Property(property="service_type", type="integer"),
     *             @OA\Property(property="client_id", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Actualizado exitosamente"),
     *     @OA\Response(response=404, description="SCADA o material no encontrado")
     * )
     */
    public function update(Request $request, $token, $id)
    {
        try {
            $scada = Scada::where('token', $token)->firstOrFail();
            $materialType = $scada->materialTypes()->findOrFail($id);

            $validated = $request->validate([
                'name' => 'string|max:255',
                'density' => 'string|max:255',
                'client_id' => 'string|max:255',
                'service_type' => 'integer|in:0,1',
            ]);

            $materialType->update($validated);

            return response()->json($materialType, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/scada/{token}/material-types/{id}",
     *     summary="Eliminar un material",
     *     tags={"Scada Material Types"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         description="Token de SCADA",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del material",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Eliminado exitosamente"),
     *     @OA\Response(response=404, description="SCADA o material no encontrado")
     * )
     */
    public function destroy($token, $id)
    {
        $scada = Scada::where('token', $token)->firstOrFail();
        $materialType = $scada->materialTypes()->findOrFail($id);

        $materialType->delete();

        return response()->json(null, 204);
    }
}
