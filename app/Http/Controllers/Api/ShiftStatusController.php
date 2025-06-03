<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use Illuminate\Http\JsonResponse;

class ShiftStatusController extends Controller
{
    public function getStatuses(): JsonResponse
    {
        $productionLines = ProductionLine::with('lastShiftHistory')->get();
        
        $statuses = $productionLines->map(function($line) {
            return [
                'line_id' => $line->id,
                'last_shift' => $line->lastShiftHistory ? [
                    'type' => $line->lastShiftHistory->type,
                    'action' => $line->lastShiftHistory->action
                ] : null
            ];
        });

        return response()->json($statuses);
    }
}
