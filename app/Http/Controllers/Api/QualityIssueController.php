<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\QualityIssue;
use Illuminate\Support\Facades\DB;

class QualityIssueController extends Controller
{
    /**
     * Store a quality issue log sent from the Kanban UI.
     * Expected payload: { token: string, id: int|string, texto: string }
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required','string'],
            'texto' => ['required','string'],
            'operator_id' => ['nullable','integer'],
            // production_line_id no se acepta desde el cliente por seguridad; se resuelve por token
            'production_order_id' => ['nullable','integer'],
            'original_order_id' => ['nullable','integer'],
            'id' => ['nullable'], // compat: legacy field name
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $token = (string) $request->input('token');
        // Compat: aceptar production_order_id o legacy id
        $orderId = $request->input('production_order_id');
        if (!$orderId) {
            $orderId = $request->input('id');
        }
        $texto = (string) $request->input('texto');
        $operatorId = $request->input('operator_id');
        $originalOrderId = $request->input('original_order_id');
        // Seguridad: SIEMPRE resolver production_line_id desde el token
        $productionLineId = null;
        if (!empty($token)) {
            $productionLineId = DB::table('production_lines')
                ->where('token', $token)
                ->value('id');
        }

        if (!$orderId || !is_numeric($orderId)) {
            return response()->json([
                'success' => false,
                'message' => 'Falta production_order_id (o id legacy) válido',
            ], 422);
        }

        if (!$productionLineId) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar production_line_id (envíalo o provee un token válido)'
            ], 422);
        }

        // Resolver original_order_id desde la ProductionOrder si no viene en la petición
        $resolvedOriginalOrderId = $originalOrderId;
        if (!$resolvedOriginalOrderId) {
            $poForOrigin = \App\Models\ProductionOrder::find($orderId);
            $resolvedOriginalOrderId = $poForOrigin->original_order_id ?? null;
        }

        // Guardar en base de datos
        $issue = QualityIssue::create([
            'production_line_id' => (int)$productionLineId,
            'production_order_id' => (int)$orderId,
            'operator_id' => $operatorId ?: null,
            'original_order_id' => $resolvedOriginalOrderId,
            'texto' => $texto,
        ]);

        // Duplicación de OriginalOrder y procesos del grupo hasta la secuencia reportada (incluida)
        $qcOriginalOrderId = null;
        $duplicatedCount = 0;
        try {
            DB::transaction(function () use (&$qcOriginalOrderId, &$duplicatedCount, $orderId) {
                // 1) Obtener ProductionOrder asociado
                $po = \App\Models\ProductionOrder::find($orderId);
                if (!$po) {
                    throw new \RuntimeException('ProductionOrder no encontrado');
                }

                $origOrderId = $po->original_order_id;
                $origProcId = $po->original_order_process_id;
                $grupoNumero = $po->grupo_numero;

                if (!$origOrderId || !$origProcId || is_null($grupoNumero)) {
                    throw new \RuntimeException('Faltan referencias: original_order_id/original_order_process_id/grupo_numero');
                }

                // 2) Proceso original reportado y su secuencia
                $reportedOop = \App\Models\OriginalOrderProcess::find($origProcId);
                if (!$reportedOop) {
                    throw new \RuntimeException('OriginalOrderProcess reportado no encontrado');
                }
                $reportedProcess = \App\Models\Process::find($reportedOop->process_id);
                if (!$reportedProcess) {
                    throw new \RuntimeException('Process del proceso reportado no encontrado');
                }
                $seqThreshold = (int) $reportedProcess->sequence;

                // 3) Seleccionar procesos del mismo grupo con sequence <= umbral
                $eligibleProcessIds = \App\Models\Process::where('sequence', '<=', $seqThreshold)->pluck('id');
                $oopToCopy = \App\Models\OriginalOrderProcess::where('original_order_id', $origOrderId)
                    ->where('grupo_numero', $reportedOop->grupo_numero)
                    ->whereIn('process_id', $eligibleProcessIds)
                    ->get();

                if ($oopToCopy->isEmpty()) {
                    throw new \RuntimeException('No hay procesos elegibles para duplicar');
                }

                // 4) Duplicar OriginalOrder con sufijo -QC en order_id (ERP)
                $orig = \App\Models\OriginalOrder::find($origOrderId);
                if (!$orig) {
                    throw new \RuntimeException('OriginalOrder no encontrado');
                }

                $baseErpOrder = (string) $orig->order_id;
                $candidate = $baseErpOrder . '-QC';
                $i = 2;
                while (\App\Models\OriginalOrder::where('order_id', $candidate)->exists()) {
                    $candidate = $baseErpOrder . "-QC-{$i}";
                    $i++;
                }

                $newOriginal = new \App\Models\OriginalOrder([
                    'order_id' => $candidate,
                    'customer_id' => $orig->customer_id,
                    'client_number' => $orig->client_number,
                    'order_details' => $orig->order_details,
                    'processed' => false,
                    'finished_at' => null,
                    // Urgente: la entrega del duplicado QC es hoy
                    'delivery_date' => now(),
                    'in_stock' => $orig->in_stock,
                    'fecha_pedido_erp' => $orig->fecha_pedido_erp,
                ]);
                $newOriginal->save();
                $qcOriginalOrderId = $newOriginal->id;

                // 5) Duplicar procesos elegibles al nuevo OriginalOrder
                $duplicatedCount = 0;
                foreach ($oopToCopy as $op) {
                    \App\Models\OriginalOrderProcess::create([
                        'original_order_id' => $qcOriginalOrderId,
                        'process_id' => $op->process_id,
                        'time' => $op->time,
                        'box' => $op->box,
                        'units_box' => $op->units_box,
                        'number_of_pallets' => $op->number_of_pallets,
                        'created' => false, // Arrancan sin crear
                        'finished' => false, // No finalizados
                        'finished_at' => null,
                        'grupo_numero' => $op->grupo_numero,
                        'in_stock' => $op->in_stock,
                    ]);
                    $duplicatedCount++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('[QUALITY CONTROL API] Error duplicando orden QC', [
                'production_order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }

        // Log de depuración por ahora
        Log::info('[QUALITY CONTROL API] Problemas de calidad recibidos', [
            'production_line_token' => $token,
            'production_line_id' => $productionLineId,
            'production_order_id' => (int)$orderId,
            'legacy_order_id_field_present' => $request->has('id'),
            'texto' => $texto,
            'operator_id' => $operatorId,
            'original_order_id' => $resolvedOriginalOrderId,
            'quality_issue_id' => $issue->id ?? null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        // Si se generó una orden QC, persistir referencia en la incidencia
        if ($qcOriginalOrderId) {
            $issue->original_order_id_qc = $qcOriginalOrderId;
            $issue->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Registro de calidad recibido',
            'id' => $issue->id ?? null,
            'operator_id' => $operatorId,
            'production_line_id' => $productionLineId,
            'production_order_id' => (int)$orderId,
            'original_order_id' => $originalOrderId ? (int)$originalOrderId : null,
            'original_order_id_qc' => $qcOriginalOrderId,
            'duplicated_count' => $duplicatedCount,
        ]);
    }
}
