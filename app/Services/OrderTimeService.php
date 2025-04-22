<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\OrderStat;
use App\Models\OrderMac;
use App\Models\ShiftHistory;
use Illuminate\Support\Facades\Log;
use App\Models\ShiftList; // Asegúrate de que la ruta del modelo sea la correcta


class OrderTimeService
{
    public function getTimeOrder($productionLineId)
    {
        $orderStats = OrderStat::where('production_line_id', $productionLineId)
        ->orderBy('created_at', 'desc')
        ->first();

        if (!$orderStats) {
            // Manejar el caso en que no se encuentre la orden
            return [
                'timeOnSeconds' => 0,
                'timeOnFormatted' => '00:00:00'
            ];
        }
        
            $timeOnSeconds = $orderStats->on_time;            
            $timeOnFormatted = gmdate('H:i:s', $timeOnSeconds);

        return [
            'timeOnSeconds' => $timeOnSeconds,
            'timeOnFormatted' => $timeOnFormatted
        ];
    }
}
