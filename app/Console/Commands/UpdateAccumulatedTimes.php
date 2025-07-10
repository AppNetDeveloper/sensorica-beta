<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionOrder;
use App\Models\OriginalOrderProcess;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateAccumulatedTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:update-accumulated-times {line_id? : ID de la línea de producción a actualizar (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza los tiempos acumulados de las órdenes de producción activas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando actualización de tiempos acumulados...');
        
        try {
            // Verificar si se especificó una línea de producción específica
            $lineId = $this->argument('line_id');
            
            // Construir la consulta base
            $query = ProductionOrder::where(function($query) {
                $query->where('status', 1)
                      ->orWhere(function($q) {
                          $q->where('status', 0)
                            ->whereNotNull('production_line_id');
                      });
            });
            
            // Si se especificó una línea, filtrar por ella
            if ($lineId) {
                $this->info("Actualizando solo la línea de producción ID: {$lineId}");
                $query->where('production_line_id', $lineId);
            }
            
            // Obtener las órdenes ordenadas
            $activeOrders = $query->orderBy('production_line_id')
                                 ->orderBy('orden')
                                 ->get();
            
            $this->info("Se encontraron {$activeOrders->count()} órdenes activas para actualizar.");
            
            // Usar la zona horaria de Madrid como se ha configurado en el resto de la aplicación
            $now = Carbon::now('Europe/Madrid');
            $updatedCount = 0;
            
            // Identificar las órdenes en fabricación (status=1) por línea de producción
            // para poder sumar su tiempo teórico al acumulado de las órdenes en espera
            $inProgressOrdersByLine = [];
            foreach ($activeOrders as $order) {
                if ($order->status === 1 && $order->production_line_id) {
                    $inProgressOrdersByLine[$order->production_line_id] = $order;
                }
            }
            
            // Procesar las órdenes por línea de producción
            $currentLineId = null;
            $accumulatedSeconds = 0;
            
            foreach ($activeOrders as $order) {
                // Si cambiamos de línea de producción, reiniciamos el acumulado
                if ($currentLineId !== $order->production_line_id) {
                    $currentLineId = $order->production_line_id;
                    $accumulatedSeconds = 0;
                    $this->info("Procesando línea de producción ID: {$currentLineId}");
                    
                    // Si hay una orden en fabricación para esta línea, sumamos su tiempo teórico al acumulado
                    if (isset($inProgressOrdersByLine[$currentLineId])) {
                        $inProgressOrder = $inProgressOrdersByLine[$currentLineId];
                        $theoreticalTime = $inProgressOrder->theoretical_time ?? 0;
                        
                        if ($theoreticalTime > 0) {
                            // El tiempo teórico ya está en segundos, lo usamos directamente
                            $accumulatedSeconds += $theoreticalTime;
                            $this->info("  * Sumando tiempo teórico de la orden en fabricación ID: {$inProgressOrder->id}: {$theoreticalTime} segundos");
                        } else {
                            // Si no tiene tiempo teórico, buscamos en el proceso original
                            $originalProcess = OriginalOrderProcess::find($inProgressOrder->original_order_process_id);
                            if ($originalProcess && $originalProcess->time) {
                                // El tiempo del proceso original está en minutos, convertir a segundos
                                $theoreticalSeconds = $originalProcess->time * 60;
                                $accumulatedSeconds += $theoreticalSeconds;
                                $this->info("  * Sumando tiempo del proceso original para orden en fabricación ID: {$inProgressOrder->id}: {$originalProcess->time} minutos ({$theoreticalSeconds} segundos)");
                            }
                        }
                    }
                }
                
                // Si la orden está en fabricación (status=1), reseteamos su tiempo acumulado
                if ($order->status === 1) {
                    $this->info("  - Reseteando tiempo acumulado para orden en fabricación ID: {$order->id}");
                    $order->accumulated_time = 0; // Guardamos como 0 segundos
                    $order->save();
                    $updatedCount++;
                    continue; // Pasamos a la siguiente orden
                }
                
                // Para órdenes asignadas a máquina (status=0), calculamos el tiempo acumulado
                if ($order->status === 0 && $order->production_line_id) {
                    $this->info("  - Procesando orden asignada ID: {$order->id}, Orden: {$order->orden}");
                    
                    // Guardar el tiempo acumulado en segundos directamente
                    $this->info("    * Tiempo acumulado asignado: {$accumulatedSeconds} segundos");
                    
                    // Actualizar el tiempo acumulado en la orden
                    $order->accumulated_time = $accumulatedSeconds;
                    $order->save();
                    $updatedCount++;
                    
                    // Incrementamos el acumulado para la siguiente orden
                    // Obtenemos el tiempo estimado del proceso original asociado a esta orden de producción
                    $estimatedSeconds = 0;
                    
                    // Obtenemos el tiempo teórico de la orden de producción
                    $estimatedSeconds = $order->theoretical_time ?? 0;
                    
                    if ($estimatedSeconds > 0) {
                        $this->info("    * Usando tiempo teórico de la orden: {$estimatedSeconds} segundos");
                    } else {
                        // Si no tiene tiempo teórico, buscamos en el proceso original
                        $originalProcess = OriginalOrderProcess::find($order->original_order_process_id);
                        if ($originalProcess && $originalProcess->time) {
                            $estimatedSeconds = $originalProcess->time * 60; // Convertir minutos a segundos
                            $this->info("    * Usando tiempo estimado del proceso original: {$originalProcess->time} minutos ({$estimatedSeconds} segundos)");
                        } else {
                            // Valor predeterminado de 30 minutos si no hay tiempo estimado
                            $estimatedSeconds = 30 * 60;
                            $this->info("    * No se encontró tiempo estimado, usando valor predeterminado: 1800 segundos (30 minutos)");
                        }
                    }
                    
                    $accumulatedSeconds += $estimatedSeconds;
                    $this->info("    * Incrementando acumulado en {$estimatedSeconds} segundos para la siguiente orden");
                }
            }
            
            $this->info("Se actualizaron los tiempos acumulados de {$updatedCount} órdenes.");
            Log::info("Comando production:update-accumulated-times ejecutado. Se actualizaron {$updatedCount} órdenes.");
            
            // Registrar la hora de la última ejecución
            $now = Carbon::now('Europe/Madrid');
            Log::info("Hora de finalización del comando: {$now->format('Y-m-d H:i:s')}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error al actualizar los tiempos acumulados: {$e->getMessage()}");
            Log::error("Error en production:update-accumulated-times: {$e->getMessage()}");
            return 1;
        }
    }
}
