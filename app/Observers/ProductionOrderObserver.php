<?php

namespace App\Observers;

use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

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
        $this->updateAccumulatedTimes($productionOrder);
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
            $this->updateAccumulatedTimes($productionOrder);
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
        $this->updateAccumulatedTimes($productionOrder);
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
