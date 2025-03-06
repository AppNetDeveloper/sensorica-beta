<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reference;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="GroupLevel",
 *     type="object",
 *     required={"id_group", "level", "uds", "total", "measure", "eanCode", "envase"},
 *     @OA\Property(property="id_group", type="string", example="refer1"),
 *     @OA\Property(property="level", type="integer", example=1),
 *     @OA\Property(property="uds", type="integer", example=12),
 *     @OA\Property(property="total", type="string", example="2 * 12 = 24"),
 *     @OA\Property(property="measure", type="string", example="g (g-force)"),
 *     @OA\Property(property="eanCode", type="string", example="123123123322"),
 *     @OA\Property(property="envase", type="string", example="SINENVASE")
 * )
 *
 * @OA\Schema(
 *     schema="Reference",
 *     type="object",
 *     required={"id", "customerId", "families", "eanCode", "rfidCode", "description", "value", "magnitude", "measure", "envase", "tolerancia_min", "tolerancia_max"},
 *     @OA\Property(property="id", type="string", example="REFER1"),
 *     @OA\Property(property="customerId", type="string", example="REFER1"),
 *     @OA\Property(property="families", type="string", example="FamiliaPrueba"),
 *     @OA\Property(property="eanCode", type="string", example="1234567890123"),
 *     @OA\Property(property="rfidCode", type="string", example="1234567890123"),
 *     @OA\Property(property="description", type="string", example="Descripción de la referencia"),
 *     @OA\Property(property="value", type="integer", example=123),
 *     @OA\Property(property="magnitude", type="string", example="Aceleración"),
 *     @OA\Property(property="measure", type="string", example="m/s2"),
 *     @OA\Property(property="envase", type="string", example="ENVASE1"),
 *     @OA\Property(property="tolerancia_min", type="number", format="float", example=2.00),
 *     @OA\Property(property="tolerancia_max", type="number", format="float", example=2.00),
 *     @OA\Property(
 *         property="groupLevels",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/GroupLevel")
 *     )
 * )
 */
class ReferenceController extends Controller
{
    /**
     * Obtiene la lista de referencias.
     *
     * @OA\Get(
     *     path="/api/reference-Topflow",
     *     summary="Obtiene la lista de referencias",
     *     tags={"Reference-Topflow"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de referencias",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Reference")
     *         )
     *     )
     * )
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $references = Reference::with('groupLevels')->get();
        return response()->json($references);
    }

    /**
     * Crea una nueva referencia.
     *
     * @OA\Post(
     *     path="/api/reference-Topflow",
     *     summary="Crea una nueva referencia",
     *     tags={"Reference-Topflow"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para crear la referencia",
     *         @OA\JsonContent(
     *             required={"id", "customerId", "families", "eanCode", "rfidCode", "description", "value", "magnitude", "measure", "envase", "tolerancia_min", "tolerancia_max", "groupLevel"},
     *             @OA\Property(property="id", type="string", example="REFER1"),
     *             @OA\Property(property="customerId", type="string", example="REFER1"),
     *             @OA\Property(property="families", type="string", example="FamiliaPrueba"),
     *             @OA\Property(property="eanCode", type="string", example="1234567890123"),
     *             @OA\Property(property="rfidCode", type="string", example="1234567890123"),
     *             @OA\Property(property="description", type="string", example="Descripción de la referencia"),
     *             @OA\Property(property="value", type="integer", example=123),
     *             @OA\Property(property="magnitude", type="string", example="Aceleración"),
     *             @OA\Property(property="measure", type="string", example="m/s2"),
     *             @OA\Property(property="envase", type="string", example="ENVASE1"),
     *             @OA\Property(property="tolerancia_min", type="number", format="float", example=2.00),
     *             @OA\Property(property="tolerancia_max", type="number", format="float", example=2.00),
     *             @OA\Property(
     *                 property="groupLevel",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"id_group", "level", "uds", "total", "measure", "eanCode", "envase"},
     *                     @OA\Property(property="id_group", type="string", example="refer1"),
     *                     @OA\Property(property="level", type="integer", example=1),
     *                     @OA\Property(property="uds", type="integer", example=12),
     *                     @OA\Property(property="total", type="string", example="2 * 12 = 24"),
     *                     @OA\Property(property="measure", type="string", example="g (g-force)"),
     *                     @OA\Property(property="eanCode", type="string", example="123123123322"),
     *                     @OA\Property(property="envase", type="string", example="SINENVASE")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Referencia creada exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/Reference")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados son inválidos.")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id'                     => 'required|string|unique:references,id',
            'customerId'             => 'required|string',
            'families'               => 'required|string',
            'eanCode'                => 'required|string',
            'rfidCode'               => 'required|string',
            'description'            => 'required|string',
            'value'                  => 'required|integer',
            'magnitude'              => 'required|string',
            'measure'                => 'required|string',
            'envase'                 => 'required|string',
            'tolerancia_min'         => 'required|numeric',
            'tolerancia_max'         => 'required|numeric',
            'groupLevel'             => 'required|array',
            'groupLevel.*.id_group'  => 'required|string',
            'groupLevel.*.level'     => 'required|integer',
            'groupLevel.*.uds'       => 'required|integer',
            'groupLevel.*.total'     => 'required|string',
            'groupLevel.*.measure'   => 'required|string',
            'groupLevel.*.eanCode'   => 'required|string',
            'groupLevel.*.envase'    => 'required|string',
        ]);

        $reference = Reference::create($validated);

        foreach ($validated['groupLevel'] as $group) {
            $reference->groupLevels()->create($group);
        }

        return response()->json($reference->load('groupLevels'), 201);
    }

    /**
     * Obtiene una referencia específica.
     *
     * @OA\Get(
     *     path="/api/reference-Topflow/{id}",
     *     summary="Obtiene una referencia específica",
     *     tags={"Reference-Topflow"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la referencia",
     *         @OA\Schema(type="string", example="REFER1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la referencia",
     *         @OA\JsonContent(ref="#/components/schemas/Reference")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Referencia no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Referencia no encontrada.")
     *         )
     *     )
     * )
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $reference = Reference::with('groupLevels')->findOrFail($id);
        return response()->json($reference);
    }
}
