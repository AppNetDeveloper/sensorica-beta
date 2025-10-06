<?php

namespace App\Observers;

use App\Models\BarcodeScan;
use App\Models\BarcodeScanAfter;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BarcodeScanObserver
{
    /**
     * Handle the BarcodeScan "created" event.
     *
     * @param  \App\Models\BarcodeScan  $barcodeScan
     * @return void
     */
    public function created(BarcodeScan $barcodeScan)
    {
        try {
            $productionOrder = null;

            try {
                $productionOrder = $barcodeScan->productionOrder()->with(['originalOrderProcess.process'])->first();
            } catch (\Throwable $inner) {
                Log::warning('BarcodeScanObserver: error loading production order for scan', [
                    'barcode_scan_id' => $barcodeScan->id,
                    'error' => $inner->getMessage(),
                ]);
            }

            if (!$productionOrder) {
                return;
            }

            if (empty($productionOrder->original_order_id) || $productionOrder->grupo_numero === null) {
                return;
            }

            $groupOrders = collect();

            try {
                $groupOrders = ProductionOrder::with(['originalOrderProcess.process'])
                    ->where('original_order_id', $productionOrder->original_order_id)
                    ->where('grupo_numero', $productionOrder->grupo_numero)
                    ->orderBy('id')
                    ->get();
            } catch (\Throwable $inner) {
                Log::warning('BarcodeScanObserver: error loading group orders', [
                    'barcode_scan_id' => $barcodeScan->id,
                    'production_order_id' => $productionOrder->id,
                    'error' => $inner->getMessage(),
                ]);
                return;
            }

            if ($groupOrders->count() <= 1) {
                return;
            }

            $sortedOrders = $groupOrders->sortBy(function ($order) {
                $sequence = optional(optional($order->originalOrderProcess)->process)->sequence;
                if ($sequence === null) {
                    return $order->orden ?? PHP_INT_MAX;
                }
                return $sequence;
            })->values();

            $currentIndex = $sortedOrders->search(function ($order) use ($productionOrder) {
                return (int) $order->id === (int) $productionOrder->id;
            });

            if ($currentIndex === false) {
                return;
            }

            $nextOrder = $sortedOrders->slice($currentIndex + 1)->first(function ($order) {
                return (int) $order->status === 0;
            });

            if (!$nextOrder) {
                return;
            }

            try {
                BarcodeScanAfter::updateOrCreate(
                    [
                        'barcode_scan_id' => $barcodeScan->id,
                        'production_order_id' => $nextOrder->id,
                    ],
                    [
                        'production_line_id' => $nextOrder->production_line_id,
                        'barcoder_id' => $nextOrder->barcoder_id,
                        'original_order_id' => $nextOrder->original_order_id,
                        'original_order_process_id' => $nextOrder->original_order_process_id,
                        'order_id' => $nextOrder->order_id,
                        'grupo_numero' => $nextOrder->grupo_numero,
                        'scanned_at' => $barcodeScan->scanned_at ?: Carbon::now('Europe/Madrid'),
                        'meta' => [
                            'trigger_scan_id' => $barcodeScan->id,
                            'trigger_order_id' => $productionOrder->id,
                            'trigger_status' => $productionOrder->status,
                        ],
                    ]
                );
            } catch (\Throwable $inner) {
                Log::warning('BarcodeScanObserver: error creating barcode_scans_after record', [
                    'barcode_scan_id' => $barcodeScan->id,
                    'next_production_order_id' => $nextOrder->id,
                    'error' => $inner->getMessage(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('BarcodeScanObserver: unexpected error during created handler', [
                'barcode_scan_id' => $barcodeScan->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the BarcodeScan "updated" event.
     *
     * @param  \App\Models\BarcodeScan  $barcodeScan
     * @return void
     */
    public function updated(BarcodeScan $barcodeScan)
    {
        //
    }

    /**
     * Handle the BarcodeScan "deleted" event.
     *
     * @param  \App\Models\BarcodeScan  $barcodeScan
     * @return void
     */
    public function deleted(BarcodeScan $barcodeScan)
    {
        //
    }

    /**
     * Handle the BarcodeScan "restored" event.
     *
     * @param  \App\Models\BarcodeScan  $barcodeScan
     * @return void
     */
    public function restored(BarcodeScan $barcodeScan)
    {
        //
    }

    /**
     * Handle the BarcodeScan "force deleted" event.
     *
     * @param  \App\Models\BarcodeScan  $barcodeScan
     * @return void
     */
    public function forceDeleted(BarcodeScan $barcodeScan)
    {
        //
    }
}
