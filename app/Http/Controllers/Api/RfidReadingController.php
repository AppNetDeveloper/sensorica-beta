<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RfidReading;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API de RFID Readings",
 *      description="Gestión de lecturas RFID asociadas",
 *      @OA\Contact(
 *          email="soporte@tuempresa.com"
 *      )
 * )
 */
class RfidReadingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/rfid-readings",
     *     summary="Listar todas las lecturas RFID",
     *     tags={"RFID Readings"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de lecturas RFID",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", description="ID de la lectura RFID"),
     *                 @OA\Property(property="epc", type="string", description="Código EPC único"),
     *                 @OA\Property(property="name", type="string", description="Nombre del RFID"),
     *                 @OA\Property(property="production_line_id", type="integer", nullable=true, description="ID de la línea de producción")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $rfidReadings = RfidReading::with('rfidColor')->get();
        return response()->json($rfidReadings, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/rfid-readings",
     *     summary="Crear una nueva lectura RFID",
     *     tags={"RFID Readings"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"epc"},
     *             @OA\Property(property="epc", type="string", description="Código EPC único"),
     *             @OA\Property(property="name", type="string", nullable=true, description="Nombre del RFID"),
     *             @OA\Property(property="production_line_id", type="integer", nullable=true, description="ID de la línea de producción")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="RFID creado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RfidReading")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Mensaje de error")
     *         )
     *     )
     * )
     */ 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'epc' => 'required|string|unique:rfid_readings',
            'name' => 'nullable|string|max:255',
            'production_line_id' => 'nullable|integer|exists:production_lines,id',
        ]);

        $rfidReading = RfidReading::create($validated);

        return response()->json($rfidReading, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/rfid-readings/{id}",
     *     summary="Obtener una lectura RFID específica",
     *     tags={"RFID Readings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la lectura RFID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la lectura RFID",
     *         @OA\JsonContent(ref="#/components/schemas/RfidReading")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="RFID no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $rfidReading = RfidReading::find($id);

        if (!$rfidReading) {
            return response()->json(['error' => 'RFID Reading not found'], 404);
        }

        return response()->json($rfidReading, 200);
    }

    /**
     * @OA\Put(
     *     path="/api/rfid-readings/{id}",
     *     summary="Actualizar una lectura RFID existente",
     *     tags={"RFID Readings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la lectura RFID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="epc", type="string", description="Código EPC único"),
     *             @OA\Property(property="name", type="string", nullable=true, description="Nombre del RFID"),
     *             @OA\Property(property="production_line_id", type="integer", nullable=true, description="ID de la línea de producción")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RFID actualizado correctamente",
     *         @OA\JsonContent(ref="#/components/schemas/RfidReading")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="RFID no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $rfidReading = RfidReading::find($id);

        if (!$rfidReading) {
            return response()->json(['error' => 'RFID Reading not found'], 404);
        }

        $validated = $request->validate([
            'epc' => 'sometimes|required|string|unique:rfid_readings,epc,' . $id,
            'name' => 'nullable|string|max:255',
            'production_line_id' => 'nullable|integer|exists:production_lines,id',
        ]);

        $rfidReading->update($validated);

        return response()->json($rfidReading, 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/rfid-readings/{id}",
     *     summary="Eliminar una lectura RFID",
     *     tags={"RFID Readings"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la lectura RFID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RFID eliminado correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="RFID no encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $rfidReading = RfidReading::find($id);

        if (!$rfidReading) {
            return response()->json(['error' => 'RFID Reading not found'], 404);
        }

        $rfidReading->delete();

        return response()->json(['message' => 'RFID Reading deleted successfully'], 200);
    }
}
