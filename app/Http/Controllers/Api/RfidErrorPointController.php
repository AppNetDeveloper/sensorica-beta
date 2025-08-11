<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RfidErrorPointResource;
use App\Models\RfidErrorPoint;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class RfidErrorPointController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/rfid-error-points",
     *     summary="Listar puntos de error RFID por fecha",
     *     description="Devuelve una lista paginada de puntos de error RFID del día indicado. Si no se envía fecha, usa el día actual (Europe/Madrid).",
     *     tags={"RFID"},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Fecha a consultar (YYYY-MM-DD). Si falta, se asume hoy.",
     *         @OA\Schema(type="string", example="2025-08-11")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado obtenido correctamente (paginado)",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer", example=101),
     *                     @OA\Property(property="production_line_id", type="integer", example=7),
     *                     @OA\Property(property="rfid_detail_id", type="integer", example=55),
     *                     @OA\Property(property="rfid_reading_id", type="integer", example=88),
     *                     @OA\Property(property="message", type="string", example="La tarjeta no es permitida todavia por no pasar el punto en este turno."),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Formato de fecha inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="date", type="array",
     *                     @OA\Items(type="string", example="Formato de fecha inválido. Usa YYYY-MM-DD.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    /**
     * GET /api/rfid-error-points?date=YYYY-MM-DD
     *
     * • Si «date» no llega → asume hoy (zona Europe/Madrid).  
     * • Acepta cualquier formato que Carbon reconozca (14‑05‑2025, 14/05/2025, etc.).  
     * • Devuelve los registros cuyo created_at esté dentro de ese día.
     */
    public function byDate(Request $request)
    {
        $tz      = config('app.timezone', 'Europe/Madrid');
        $rawDate = $request->query('date');
    
        try {
            $date = $rawDate ? Carbon::parse($rawDate, $tz) : now($tz);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'date' => 'Formato de fecha inválido. Usa YYYY-MM-DD.',
            ]);
        }
    
        [$start, $end] = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
    
        $errorPoints = RfidErrorPoint::with([
            'productionLine',
            'productList',
            'operator',
            'operatorPost',
            'rfidDetail',
            'rfidReading.rfidColor',   //  ←  aquí
        ])
        ->whereBetween('created_at', [$start, $end])
        ->orderBy('created_at', 'desc')
        ->paginate(5050);
    
    
        return RfidErrorPointResource::collection($errorPoints);
    }
    

}