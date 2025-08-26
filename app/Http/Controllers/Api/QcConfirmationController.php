<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\QcConfirmation;
use App\Models\ProductionOrder;

class QcConfirmationController extends Controller
{
    /**
     * Store a QC confirmation sent from the Kanban UI.
     * Expected payload: { token: string, production_order_id?: int, id?: int (legacy), operator_id?: int, notes?: string }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required','string'],
            'production_order_id' => ['nullable','integer'],
            'id' => ['nullable'], // legacy field name for production_order_id
            'operator_id' => ['nullable','integer'],
            'notes' => ['nullable','string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $token = (string) $request->input('token');
        $orderId = $request->input('production_order_id') ?: $request->input('id');
        $operatorId = $request->input('operator_id');
        $notes = $request->input('notes');

        if (!$orderId || !is_numeric($orderId)) {
            return response()->json([
                'success' => false,
                'message' => 'Falta production_order_id (o id legacy) válido',
            ], 422);
        }

        // Resolver production_line_id por token (seguridad)
        $productionLineId = DB::table('production_lines')->where('token', $token)->value('id');
        if (!$productionLineId) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido. No se encontró la línea de producción',
            ], 422);
        }

        // Resolver original_order_id desde la ProductionOrder
        $po = ProductionOrder::find($orderId);
        if (!$po) {
            return response()->json([
                'success' => false,
                'message' => 'ProductionOrder no encontrado',
            ], 404);
        }

        $qc = QcConfirmation::create([
            'production_line_id' => (int)$productionLineId,
            'production_order_id' => (int)$orderId,
            'original_order_id' => $po->original_order_id,
            'operator_id' => $operatorId ?: null,
            'notes' => $notes,
            'confirmed_at' => now(),
        ]);

        Log::info('[QC CONFIRMATION] Confirmación registrada', [
            'qc_confirmation_id' => $qc->id,
            'production_line_id' => $productionLineId,
            'production_order_id' => (int)$orderId,
            'operator_id' => $operatorId,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Confirmación de QC registrada',
            'id' => $qc->id,
        ]);
    }
}
