<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\Maintenance;
use App\Models\Operator;
use Illuminate\Support\Carbon;

class MaintenanceApiController extends Controller
{
    // POST /api/maintenances/{token}
    // Body: operator_id (required), annotation (nullable)
    public function storeByToken(Request $request, string $token)
    {
        $line = ProductionLine::where('token', $token)->first();
        if (!$line) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        $validated = $request->validate([
            'operator_id' => 'required|exists:operators,id',
            'annotation' => 'nullable|string',
            'anotacion' => 'nullable|string',
        ]);

        // If there's already an active maintenance, return conflict
        $active = Maintenance::where('production_line_id', $line->id)
            ->where(function($q) {
                $q->whereNull('end_datetime')
                  ->orWhere('end_datetime', '0000-00-00 00:00:00');
            })
            ->latest('start_datetime')
            ->first();
        if ($active) {
            return response()->json([
                'message' => 'Active maintenance already exists',
                'maintenance_id' => $active->id,
                'in_maintenance' => true,
            ], 409);
        }

        $now = Carbon::now();
        $maintenance = Maintenance::create([
            'customer_id' => $line->customer_id,
            'production_line_id' => $line->id,
            'start_datetime' => $now,
            'end_datetime' => null,
            'annotations' => null,
            'operator_id' => $validated['operator_id'],
            'user_id' => null,
            'operator_annotations' => $validated['annotation'] ?? $validated['anotacion'] ?? null,
        ]);

        return response()->json([
            'message' => 'Maintenance created',
            'maintenance_id' => $maintenance->id,
            'in_maintenance' => true,
            'start_datetime' => $maintenance->start_datetime,
        ], 201);
    }

    // GET /api/maintenances/status/{token}
    public function getStatusByToken(string $token)
    {
        $line = ProductionLine::where('token', $token)->first();
        if (!$line) {
            return response()->json(['error' => 'Invalid token'], 404);
        }

        $active = Maintenance::where('production_line_id', $line->id)
            ->where(function($q) {
                $q->whereNull('end_datetime')
                  ->orWhere('end_datetime', '0000-00-00 00:00:00');
            })
            ->latest('start_datetime')
            ->first();

        if ($active) {
            return response()->json([
                'in_maintenance' => true,
                'maintenance_id' => $active->id,
                'start_datetime' => optional($active->start_datetime)->toDateTimeString(),
                'operator_id' => $active->operator_id,
                'operator_annotations' => $active->operator_annotations,
            ]);
        }

        return response()->json([
            'in_maintenance' => false,
        ]);
    }
}
