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
        ->paginate(50);
    
    
        return RfidErrorPointResource::collection($errorPoints);
    }
    

}