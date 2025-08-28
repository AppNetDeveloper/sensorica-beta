<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductionLine;
use App\Models\Maintenance;
use App\Models\Operator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

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
            'production_line_stop' => 'nullable|in:0,1,true,false',
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
            'production_line_stop' => isset($validated['production_line_stop']) ? (bool)filter_var($validated['production_line_stop'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : false,
        ]);

        // WhatsApp notification on maintenance creation (API)
        try {
            $phones = array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_PHONE_MANTENIMIENTO', ''))));
            if (!empty($phones)) {
                $operator = Operator::find($validated['operator_id']);
                $stopped = !empty($maintenance->production_line_stop);
                $message = sprintf(
                    "Mantenimiento creado (API):\nCliente: %s\nLínea: %s\nInicio: %s\nOperario: %s\nParo de línea: %s",
                    (string) $line->customer_id,
                    $line->name ?? ('ID '.$line->id),
                    Carbon::parse($maintenance->start_datetime)->format('Y-m-d H:i'),
                    $operator->name ?? '-',
                    $stopped ? 'Sí' : 'No'
                );
                $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/send-message';
                foreach ($phones as $phone) {
                    Http::withoutVerifying()->get($apiUrl, [
                        'jid' => $phone . '@s.whatsapp.net',
                        'message' => $message,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }

        // Telegram notification on maintenance creation (API)
        try {
            $peers = array_filter(array_map('trim', explode(',', (string) env('TELEGRAM_MANTENIMIENTO_PEERS', ''))));
            if (!empty($peers)) {
                $operator = Operator::find($validated['operator_id']);
                $stopped = !empty($maintenance->production_line_stop);
                $message = sprintf(
                    'Mantenimiento creado (API):\nCliente: %s\nLínea: %s\nInicio: %s\nOperario: %s\nParo de línea: %s',
                    (string) $line->customer_id,
                    $line->name ?? ('ID '.$line->id),
                    Carbon::parse($maintenance->start_datetime)->format('Y-m-d H:i'),
                    $operator->name ?? '-',
                    $stopped ? 'Sí' : 'No'
                );
                $baseUrl = 'http://localhost:3006';
                foreach ($peers as $peer) {
                    $peer = trim($peer);
                    $finalPeer = (str_starts_with($peer, '+') || str_starts_with($peer, '@')) ? $peer : ('+' . $peer);
                    $url = sprintf('%s/send-message/1/%s/%s', $baseUrl, rawurlencode($finalPeer), rawurlencode($message));
                    Http::post($url);
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }

        return response()->json([
            'message' => 'Maintenance created',
            'maintenance_id' => $maintenance->id,
            'in_maintenance' => true,
            'start_datetime' => $maintenance->start_datetime,
            'production_line_stop' => (bool)$maintenance->production_line_stop,
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
            $now = Carbon::now();
            $accumulated = (int)($active->accumulated_maintenance_seconds ?? 0);
            $live = 0;
            if (empty($active->end_datetime)) {
                $live = $active->start_datetime ? Carbon::parse($active->start_datetime)->diffInSeconds($now) : 0;
            }
            $elapsedSeconds = $accumulated + $live;

            return response()->json([
                'in_maintenance' => true,
                'maintenance_id' => $active->id,
                'start_datetime' => optional($active->start_datetime)->toDateTimeString(),
                'operator_id' => $active->operator_id,
                'operator_annotations' => $active->operator_annotations,
                'production_line_stop' => (bool)$active->production_line_stop,
                'elapsed_seconds' => $elapsedSeconds,
                'accumulated_seconds' => $accumulated,
            ]);
        }

        return response()->json([
            'in_maintenance' => false,
        ]);
    }
}
