<?php

namespace App\Observers;

use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class ProductionOrderObserver
{
    /**
     * Handle the ProductionOrder "created" event.
     *
     * @param  \App\Models\ProductionOrder  $productionOrder
     * @return void
     */
    public function created(ProductionOrder $productionOrder)
    {
        //$this->updateAccumulatedTimes($productionOrder);
    }

    /**
     * Handle the ProductionOrder "updated" event.
     *
     * @param  \App\Models\ProductionOrder  $productionOrder
     * @return void
     */
    public function updated(ProductionOrder $productionOrder)
    {
        // Solo actualizamos los tiempos si ha cambiado el status o la línea de producción
        if ($productionOrder->isDirty('status') || $productionOrder->isDirty('production_line_id') || $productionOrder->isDirty('orden')) {
            //$this->updateAccumulatedTimes($productionOrder);
        }

        // Notificación por WhatsApp si el status no es 0, 1 o 2
        try {
            if ($productionOrder->isDirty('status')) {
                $status = (int) $productionOrder->status;
                if (!in_array($status, [0, 1, 2], true)) {
                    $phones = array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_PHONE_ORDEN_INCIDENCIA', ''))));
                    if (!empty($phones)) {
                        $line = $productionOrder->productionLine()->with('customer')->first();
                        $customerName = $line?->customer?->name ?? '-';
                        $lineLabel = $line?->name ?? ('ID ' . ($productionOrder->production_line_id ?? '-'));

                        $message = sprintf(
                            "ALERTA ORDEN (tarjeta pasada a incidencias):\nCentro de producción: %s\nLínea: %s\nOrderID: %s\nStatus: %s\nFecha: %s",
                            (string) $customerName,
                            (string) $lineLabel,
                            (string) ($productionOrder->order_id ?? $productionOrder->id),
                            (string) $status,
                            now()->format('Y-m-d H:i')
                        );

                        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/send-message';
                        foreach ($phones as $phone) {
                            Http::withoutVerifying()->get($apiUrl, [
                                'jid' => $phone . '@s.whatsapp.net',
                                'message' => $message,
                            ]);
                        }
                    }
                }

                // Aviso: finalizada (2) pero sin pasar por en curso (1)
                if ($status === 2 && (int) $productionOrder->getOriginal('status') !== 1) {
                    $phones = array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_PHONE_ORDEN_INCIDENCIA', ''))));
                    if (!empty($phones)) {
                        $line = $productionOrder->productionLine()->with('customer')->first();
                        $customerName = $line?->customer?->name ?? '-';
                        $lineLabel = $line?->name ?? ('ID ' . ($productionOrder->production_line_id ?? '-'));

                        $message = sprintf(
                            "ALERTA ORDEN (finalizada sin iniciarse):\nCentro de producción: %s\nLínea: %s\nOrderID: %s\nStatus: %s\nFecha: %s",
                            (string) $customerName,
                            (string) $lineLabel,
                            (string) ($productionOrder->order_id ?? $productionOrder->id),
                            (string) $status,
                            now()->format('Y-m-d H:i')
                        );

                        $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/send-message';
                        foreach ($phones as $phone) {
                            Http::withoutVerifying()->get($apiUrl, [
                                'jid' => $phone . '@s.whatsapp.net',
                                'message' => $message,
                            ]);
                        }
                    }
                }

                // Aviso: pasó de EN CURSO (1) a FINALIZADA (2) en menos de N segundos (configurable)
                if ($status === 2 && (int) $productionOrder->getOriginal('status') === 1) {
                    try {
                        $prevUpdatedAt = $productionOrder->getOriginal('updated_at');
                        if ($prevUpdatedAt) {
                            $seconds = now()->diffInSeconds(Carbon::parse($prevUpdatedAt));
                            $threshold = (int) env('ORDER_MIN_ACTIVE_SECONDS', 60);
                            if ($seconds < $threshold) {
                                $phones = array_filter(array_map('trim', explode(',', (string) env('WHATSAPP_PHONE_ORDEN_INCIDENCIA', ''))));
                                if (!empty($phones)) {
                                    $line = $productionOrder->productionLine()->with('customer')->first();
                                    $customerName = $line?->customer?->name ?? '-';
                                    $lineLabel = $line?->name ?? ('ID ' . ($productionOrder->production_line_id ?? '-'));

                                    $message = sprintf(
                                        "ALERTA ORDEN (posible incidencia - menos de %d s en curso):\nCentro de producción: %s\nLínea: %s\nOrderID: %s\nStatus: %s\nTiempo en curso: %ss\nFecha: %s",
                                        (int) $threshold,
                                        (string) $customerName,
                                        (string) $lineLabel,
                                        (string) ($productionOrder->order_id ?? $productionOrder->id),
                                        (string) $status,
                                        (string) $seconds,
                                        now()->format('Y-m-d H:i')
                                    );

                                    $apiUrl = rtrim(env('LOCAL_SERVER'), '/') . '/api/send-message';
                                    foreach ($phones as $phone) {
                                        Http::withoutVerifying()->get($apiUrl, [
                                            'jid' => $phone . '@s.whatsapp.net',
                                            'message' => $message,
                                        ]);
                                    }
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::error('Error enviando alerta WhatsApp de posible incidencia (menos de 1 min)', [
                            'order_id' => $productionOrder->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('Error enviando alerta WhatsApp de incidente de orden', [
                'order_id' => $productionOrder->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ProductionOrder "deleted" event.
     *
     * @param  \App\Models\ProductionOrder  $productionOrder
     * @return void
     */
    public function deleted(ProductionOrder $productionOrder)
    {
        //$this->updateAccumulatedTimes($productionOrder);
    }

    /**
     * Actualiza los tiempos acumulados ejecutando el comando artisan
     *
     * @param  \App\Models\ProductionOrder  $productionOrder
     * @return void
     */
    private function updateAccumulatedTimes(ProductionOrder $productionOrder)
    {
        try {
            // Registrar el evento que desencadenó la actualización
            Log::info("Actualizando tiempos acumulados debido a cambios en la orden ID: {$productionOrder->id}");
            
            // Obtener la línea de producción afectada
            $lineId = $productionOrder->production_line_id;
            
            // Ejecutar el comando de actualización de tiempos acumulados
            // Si hay una línea de producción, actualizar solo esa línea para mayor eficiencia
            if ($lineId) {
                Log::info("Actualizando solo la línea de producción ID: {$lineId}");
                Artisan::call('production:update-accumulated-times', ['line_id' => $lineId]);
            } else {
                // Si no hay línea de producción (por ejemplo, si se eliminó), actualizar todas
                Artisan::call('production:update-accumulated-times');
            }
            
            // Registrar el resultado
            $output = Artisan::output();
            Log::info("Resultado de la actualización de tiempos: " . substr($output, 0, 200) . "...");
        } catch (\Exception $e) {
            Log::error("Error al actualizar tiempos acumulados desde el observer: {$e->getMessage()}");
        }
    }
}
