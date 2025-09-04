<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProductionOrder;
use App\Models\OriginalOrderProcess;
use App\Models\WorkCalendar;
use App\Models\LineAvailability;
use App\Models\ShiftList;
use App\Models\ShiftHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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
            
            // Obtener el tiempo de pausa configurado (en minutos)
            $breakTimeMinutes = Config::get('production.break_time_minutes', 30);
            $breakTimeSeconds = $breakTimeMinutes * 60;
            $this->info("Tiempo de pausa configurado: {$breakTimeMinutes} minutos ({$breakTimeSeconds} segundos)");
            
            // Obtener configuración para el cálculo de OEE histórico
            $oeeHistoryDays = Config::get('production.oee_history_days', 30);
            $oeeMinimumPercentage = Config::get('production.oee_minimum_percentage', 30);
            $this->info("Configuración OEE: Usando {$oeeHistoryDays} días de historial, mínimo {$oeeMinimumPercentage}%");
            
            // Caché para almacenar el OEE promedio por línea de producción
            $oeeAverageByLine = [];
            
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
                    
                    // Solo calculamos fechas estimadas para órdenes pendientes (status=0) con línea de producción asignada
                    if ($order->status === 0 && $order->production_line_id) {
                        $this->info("    * Calculando fechas estimadas para la orden ID: {$order->id}");
                        
                        // Obtener el OEE promedio para esta línea de producción
                        try {
                            if (!isset($oeeAverageByLine[$order->production_line_id])) {
                                $oeeAverageByLine[$order->production_line_id] = $this->getAverageOEE($order->production_line_id, $oeeHistoryDays, $oeeMinimumPercentage);
                            }
                            
                            $lineOEE = $oeeAverageByLine[$order->production_line_id];
                            $this->info("    * OEE promedio para la línea: {$lineOEE}%");
                        } catch (\Exception $e) {
                            // Si hay error al obtener OEE, usar el mínimo por defecto
                            $lineOEE = $oeeMinimumPercentage;
                            $this->warn("    * Error al obtener OEE para la línea {$order->production_line_id}. Usando valor mínimo: {$lineOEE}%");
                            Log::warning("Error al obtener OEE para la línea {$order->production_line_id}: {$e->getMessage()}");
                        }
                        
                        try {
                            // Paso 1: Obtener la línea de producción y su cliente asociado
                            try {
                                $productionLine = \App\Models\ProductionLine::find($order->production_line_id);
                                if (!$productionLine || !$productionLine->customer_id) {
                                    $this->warn("    * No se encontró la línea de producción o no tiene cliente asociado");
                                    // Si no hay línea o cliente, ponemos fechas en null y continuamos
                                    $order->estimated_start_datetime = null;
                                    $order->estimated_end_datetime = null;
                                    $order->save();
                                    continue;
                                }
                                $customerId = $productionLine->customer_id;
                                $this->info("    * Cliente ID asociado a la línea: {$customerId}");
                            } catch (\Exception $e) {
                                $this->error("    * Error al obtener la línea de producción: {$e->getMessage()}");
                                Log::error("Error al obtener la línea de producción ID {$order->production_line_id}: {$e->getMessage()}");
                                // Si hay error, ponemos fechas en null y continuamos
                                $order->estimated_start_datetime = null;
                                $order->estimated_end_datetime = null;
                                $order->save();
                                continue;
                            }
                            
                            // Paso 2: Obtener la disponibilidad de la línea (días y turnos)
                            try {
                                $lineAvailability = LineAvailability::where('production_line_id', $order->production_line_id)
                                    // No filtrar por 'active'; se considerará toda disponibilidad configurada
                                    ->get();
                                
                                if ($lineAvailability->isEmpty()) {
                                    $this->warn("    * La línea no tiene configuración de disponibilidad activa");
                                    // Si no hay disponibilidad, ponemos fechas en null y continuamos
                                    $order->estimated_start_datetime = null;
                                    $order->estimated_end_datetime = null;
                                    $order->save();
                                    continue;
                                }
                            } catch (\Exception $e) {
                                $this->error("    * Error al obtener la disponibilidad de la línea: {$e->getMessage()}");
                                Log::error("Error al obtener la disponibilidad de la línea ID {$order->production_line_id}: {$e->getMessage()}");
                                // Si hay error, ponemos fechas en null y continuamos
                                $order->estimated_start_datetime = null;
                                $order->estimated_end_datetime = null;
                                $order->save();
                                continue;
                            }
                            
                            // Paso 3: Obtener los turnos disponibles
                            try {
                                // Extraer los IDs de turnos únicos de la disponibilidad
                                $shiftIds = $lineAvailability->pluck('shift_list_id')->unique()->filter();
                                
                                if ($shiftIds->isEmpty()) {
                                    $this->warn("    * No se encontraron IDs de turnos en la configuración de disponibilidad");
                                    // Si no hay IDs de turnos, ponemos fechas en null y continuamos
                                    $order->estimated_start_datetime = null;
                                    $order->estimated_end_datetime = null;
                                    $order->save();
                                    continue;
                                }
                                
                                $shifts = ShiftList::whereIn('id', $shiftIds)->get();
                                
                                if ($shifts->isEmpty()) {
                                    $this->warn("    * No se encontraron turnos para la línea");
                                    // Si no hay turnos, ponemos fechas en null y continuamos
                                    $order->estimated_start_datetime = null;
                                    $order->estimated_end_datetime = null;
                                    $order->save();
                                    continue;
                                }
                            } catch (\Exception $e) {
                                $this->error("    * Error al obtener los turnos de la línea: {$e->getMessage()}");
                                Log::error("Error al obtener turnos para la línea ID {$order->production_line_id}: {$e->getMessage()}");
                                // Si hay error, ponemos fechas en null y continuamos
                                $order->estimated_start_datetime = null;
                                $order->estimated_end_datetime = null;
                                $order->save();
                                continue;
                            }
                            
                            // Paso 4: Calcular fecha estimada de inicio (ahora + tiempo acumulado)
                            // Usamos la fecha actual como punto de partida
                            $estimatedStartDate = $now->copy();
                            
                            // Si hay tiempo acumulado, lo añadimos
                            if ($order->accumulated_time > 0) {
                                try {
                                    // Aplicamos el factor OEE al tiempo acumulado
                                    $originalAccumulatedTime = $order->accumulated_time;
                                    $adjustedAccumulatedTime = $this->adjustTimeByOEE($originalAccumulatedTime, $lineOEE);
                                    $remainingSeconds = $adjustedAccumulatedTime;
                                    
                                    $this->info("    * Tiempo acumulado original: {$originalAccumulatedTime} segundos");
                                    $this->info("    * Tiempo acumulado ajustado por OEE ({$lineOEE}%): {$adjustedAccumulatedTime} segundos");
                                } catch (\Exception $e) {
                                    $this->error("    * Error al ajustar el tiempo acumulado: {$e->getMessage()}");
                                    Log::error("Error al ajustar tiempo acumulado para la orden ID {$order->id}: {$e->getMessage()}");
                                    // Si hay error, usamos el tiempo original sin ajustar
                                    $remainingSeconds = $order->accumulated_time;
                                    $this->warn("    * Usando tiempo acumulado original sin ajustar: {$remainingSeconds} segundos");
                                }
                                
                                // Iteramos día a día hasta distribuir todo el tiempo acumulado
                                while ($remainingSeconds > 0) {
                                    // Verificar si el día actual es laborable según el calendario
                                    $currentDate = $estimatedStartDate->format('Y-m-d');
                                    $isWorkingDay = true; // Por defecto asumimos que es día laborable
                                    
                                    // Verificar en el calendario del cliente
                                    $calendarDay = WorkCalendar::where('customer_id', $customerId)
                                        ->where('calendar_date', $currentDate)
                                        ->first();
                                    
                                    if ($calendarDay) {
                                        // Día laborable si is_working_day == true o type == 'workday'
                                        $isWorkingDay = ($calendarDay->is_working_day)
                                            || (strtolower((string)$calendarDay->type) === 'workday');
                                    }
                                    
                                    if (!$isWorkingDay) {
                                        $this->info("    * El día {$currentDate} no es laborable, avanzando al siguiente");
                                        $estimatedStartDate->addDay();
                                        continue;
                                    }
                                    
                                    // Verificar disponibilidad para el día de la semana actual (1=lunes, 7=domingo)
                                    $dayOfWeek = $estimatedStartDate->dayOfWeek ?: 7; // Carbon usa 0 para domingo, lo convertimos a 7
                                    
                                    $dayAvailability = $lineAvailability->where('day_of_week', $dayOfWeek);
                                    
                                    if ($dayAvailability->isEmpty()) {
                                        $this->info("    * No hay disponibilidad para el día {$dayOfWeek}, avanzando al siguiente");
                                        $estimatedStartDate->addDay();
                                        continue;
                                    }
                                    
                                    // Procesar cada turno disponible para este día
                                    $secondsProcessedToday = 0;
                                    
                                    foreach ($dayAvailability as $availability) {
                                        $shift = $shifts->firstWhere('id', $availability->shift_list_id);
                                        
                                        if (!$shift) continue;
                                        
                                        // Obtener horas de inicio y fin del turno
                                        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start, 'Europe/Madrid');
                                        $shiftEnd = Carbon::createFromFormat('H:i:s', $shift->end, 'Europe/Madrid');
                                        
                                        // Ajustar al día actual
                                        $shiftStart->setDate($estimatedStartDate->year, $estimatedStartDate->month, $estimatedStartDate->day);
                                        $shiftEnd->setDate($estimatedStartDate->year, $estimatedStartDate->month, $estimatedStartDate->day);
                                        
                                        // Si el turno termina antes de empezar (cruza medianoche), añadir un día a la hora de fin
                                        if ($shiftEnd <= $shiftStart) {
                                            $shiftEnd->addDay();
                                        }
                                        
                                        // Si estamos en el día actual y la hora actual es posterior al inicio del turno,
                                        // ajustar el tiempo disponible
                                        if ($estimatedStartDate->format('Y-m-d') === $now->format('Y-m-d') && $now > $shiftStart) {
                                            $shiftStart = $now->copy();
                                        }
                                        // Si tras ajustar, el turno ya no tiene ventana válida, continuar con el siguiente turno
                                        if ($shiftStart >= $shiftEnd) {
                                            continue;
                                        }
                                        
                                        // Calcular duración del turno en segundos tras los ajustes
                                        $shiftDurationSeconds = $shiftEnd->diffInSeconds($shiftStart);
                                        
                                        // Descontar el tiempo de pausa si el turno es lo suficientemente largo
                                        // Solo aplicamos la pausa si el turno dura más de 4 horas (14400 segundos)
                                        if ($shiftDurationSeconds >= 4 * 3600) {
                                            $shiftDurationSeconds -= $breakTimeSeconds;
                                            $this->info("        * Descontando {$breakTimeMinutes} minutos de pausa del turno");
                                        }
                                        
                                        // Determinar cuántos segundos podemos procesar en este turno
                                        $secondsToProcess = min($remainingSeconds, $shiftDurationSeconds);
                                        
                                        if ($secondsToProcess > 0) {
                                            $remainingSeconds -= $secondsToProcess;
                                            $secondsProcessedToday += $secondsToProcess;
                                            
                                            // Si hemos distribuido todo el tiempo, la fecha de inicio es el final de este turno
                                            if ($remainingSeconds <= 0) {
                                                $estimatedStartDate = $shiftStart->copy()->addSeconds($secondsToProcess);
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Si no se procesó tiempo hoy o aún queda tiempo, avanzar al día siguiente
                                    if ($secondsProcessedToday === 0 || $remainingSeconds > 0) {
                                        $estimatedStartDate->addDay()->startOfDay();
                                    }
                                }
                            }
                            
                            // Paso 5: Calcular fecha estimada de fin (fecha inicio + tiempo teórico)
                            $estimatedEndDate = $estimatedStartDate->copy();
                            
                            try {
                                // Aplicamos el factor OEE al tiempo teórico
                                $originalTheoreticalSeconds = $estimatedSeconds;
                                $adjustedTheoreticalSeconds = $this->adjustTimeByOEE($originalTheoreticalSeconds, $lineOEE);
                                $remainingTheoreticalSeconds = $adjustedTheoreticalSeconds;
                                
                                $this->info("    * Tiempo teórico original: {$originalTheoreticalSeconds} segundos");
                                $this->info("    * Tiempo teórico ajustado por OEE ({$lineOEE}%): {$adjustedTheoreticalSeconds} segundos");
                            } catch (\Exception $e) {
                                $this->error("    * Error al ajustar el tiempo teórico: {$e->getMessage()}");
                                Log::error("Error al ajustar tiempo teórico para la orden ID {$order->id}: {$e->getMessage()}");
                                // Si hay error, usamos el tiempo original sin ajustar
                                $remainingTheoreticalSeconds = $originalTheoreticalSeconds;
                                $this->warn("    * Usando tiempo teórico original sin ajustar: {$remainingTheoreticalSeconds} segundos");
                            }
                            
                            if ($remainingTheoreticalSeconds > 0) {
                                
                                // Iteramos día a día hasta distribuir todo el tiempo teórico
                                while ($remainingTheoreticalSeconds > 0) {
                                    // Verificar si el día actual es laborable según el calendario
                                    $currentDate = $estimatedEndDate->format('Y-m-d');
                                    $isWorkingDay = true; // Por defecto asumimos que es día laborable
                                    
                                    // Verificar en el calendario del cliente
                                    $calendarDay = WorkCalendar::where('customer_id', $customerId)
                                        ->where('calendar_date', $currentDate)
                                        ->first();
                                    
                                    if ($calendarDay) {
                                        // Día laborable si is_working_day == true o type == 'workday'
                                        $isWorkingDay = ($calendarDay->is_working_day)
                                            || (strtolower((string)$calendarDay->type) === 'workday');
                                    }
                                    
                                    if (!$isWorkingDay) {
                                        $estimatedEndDate->addDay();
                                        continue;
                                    }
                                    
                                    // Verificar disponibilidad para el día de la semana actual
                                    $dayOfWeek = $estimatedEndDate->dayOfWeek ?: 7; // Carbon usa 0 para domingo, lo convertimos a 7
                                    
                                    $dayAvailability = $lineAvailability->where('day_of_week', $dayOfWeek);
                                    
                                    if ($dayAvailability->isEmpty()) {
                                        $estimatedEndDate->addDay();
                                        continue;
                                    }
                                    
                                    // Procesar cada turno disponible para este día
                                    $secondsProcessedToday = 0;
                                    
                                    foreach ($dayAvailability as $availability) {
                                        $shift = $shifts->firstWhere('id', $availability->shift_list_id);
                                        
                                        if (!$shift) continue;
                                        
                                        // Obtener horas de inicio y fin del turno
                                        $shiftStart = Carbon::createFromFormat('H:i:s', $shift->start, 'Europe/Madrid');
                                        $shiftEnd = Carbon::createFromFormat('H:i:s', $shift->end, 'Europe/Madrid');
                                        
                                        // Ajustar al día actual
                                        $shiftStart->setDate($estimatedEndDate->year, $estimatedEndDate->month, $estimatedEndDate->day);
                                        $shiftEnd->setDate($estimatedEndDate->year, $estimatedEndDate->month, $estimatedEndDate->day);
                                        
                                        // Si el turno termina antes de empezar (cruza medianoche), añadir un día a la hora de fin
                                        if ($shiftEnd <= $shiftStart) {
                                            $shiftEnd->addDay();
                                        }
                                        
                                        // Si estamos en el día de inicio y la hora de inicio es posterior al inicio del turno,
                                        // ajustar el tiempo disponible
                                        if ($estimatedEndDate->format('Y-m-d') === $estimatedStartDate->format('Y-m-d') && 
                                            $estimatedStartDate > $shiftStart) {
                                            $shiftStart = $estimatedStartDate->copy();
                                        }
                                        // Si tras ajustar, el turno ya no tiene ventana válida, continuar con el siguiente turno
                                        if ($shiftStart >= $shiftEnd) {
                                            continue;
                                        }
                                        
                                        // Calcular duración del turno en segundos
                                        $shiftDurationSeconds = $shiftEnd->diffInSeconds($shiftStart);
                                        
                                        // Descontar el tiempo de pausa si el turno es lo suficientemente largo
                                        // Solo aplicamos la pausa si el turno dura más de 4 horas (14400 segundos)
                                        if ($shiftDurationSeconds >= 4 * 3600) {
                                            $shiftDurationSeconds -= $breakTimeSeconds;
                                            $this->info("        * Descontando {$breakTimeMinutes} minutos de pausa del turno");
                                        }
                                        
                                        // Determinar cuántos segundos podemos procesar en este turno
                                        $secondsToProcess = min($remainingTheoreticalSeconds, $shiftDurationSeconds);
                                        
                                        if ($secondsToProcess > 0) {
                                            $remainingTheoreticalSeconds -= $secondsToProcess;
                                            $secondsProcessedToday += $secondsToProcess;
                                            
                                            // Si hemos distribuido todo el tiempo, la fecha de fin es el final de este turno
                                            if ($remainingTheoreticalSeconds <= 0) {
                                                $estimatedEndDate = $shiftStart->copy()->addSeconds($secondsToProcess);
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Si no se procesó tiempo hoy o aún queda tiempo, avanzar al día siguiente
                                    if ($secondsProcessedToday === 0 || $remainingTheoreticalSeconds > 0) {
                                        $estimatedEndDate->addDay()->startOfDay();
                                    }
                                }
                            }
                            
                            // Paso 6: Guardar las fechas estimadas en la orden
                            $order->estimated_start_datetime = $estimatedStartDate;
                            $order->estimated_end_datetime = $estimatedEndDate;
                            $order->save();
                            
                            $this->info("    * Fechas estimadas calculadas: Inicio: {$estimatedStartDate->format('Y-m-d H:i:s')}, Fin: {$estimatedEndDate->format('Y-m-d H:i:s')}");
                            
                        } catch (\Exception $e) {
                            $this->error("    * Error al calcular fechas estimadas: {$e->getMessage()}");
                            // En caso de error, ponemos fechas en null
                            $order->estimated_start_datetime = null;
                            $order->estimated_end_datetime = null;
                            $order->save();
                        }
                    }
                }
            }
            
            $this->info("Se actualizaron los tiempos acumulados de {$updatedCount} órdenes.");
            
            // Tras calcular y guardar estimated_start/end para todas las órdenes activas,
            // encadenamos disponibilidad por grupo (original_order_id + grupo_numero)
            try {
                $this->updateGroupReadyAfter($activeOrders);
            } catch (\Throwable $e) {
                $this->warn('Error actualizando ready_after_datetime por grupo: ' . $e->getMessage());
                Log::warning('updateGroupReadyAfter error: ' . $e->getMessage());
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error al actualizar los tiempos acumulados: {$e->getMessage()}");
            Log::error("Error en UpdateAccumulatedTimes: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Encadenar ready_after_datetime por grupos de procesos dentro de cada OriginalOrder.
     * Para cada grupo (original_order_id + grupo_numero), ordena por sequence del Process
     * y establece ready_after_datetime del elemento i con el estimated_end_datetime del elemento i-1.
     * Si no hay estimated_end_datetime disponible, no establece valor.
     */
    protected function updateGroupReadyAfter($orders)
    {
        // Filtrar solo órdenes con referencias necesarias
        $orders = $orders->filter(function($o) {
            return !empty($o->original_order_id)
                && !empty($o->original_order_process_id)
                && isset($o->grupo_numero);
        });

        if ($orders->isEmpty()) {
            $this->info('No hay órdenes con grupo/proceso para encadenar ready_after_datetime.');
            return;
        }

        // Cargar procesos con su secuencia para ordenar correctamente
        $originalOrderProcessIds = $orders->pluck('original_order_process_id')->unique()->values();
        $processByOOP = OriginalOrderProcess::with('process')
            ->whereIn('id', $originalOrderProcessIds)
            ->get()
            ->keyBy('id');

        // Agrupar por original_order_id + grupo_numero
        $grouped = $orders->groupBy(function($o) {
            return $o->original_order_id . '|' . ($o->grupo_numero ?? '');
        });

        foreach ($grouped as $groupKey => $groupOrders) {
            // Ordenar las órdenes del grupo por sequence del Process
            $sorted = $groupOrders->sortBy(function($o) use ($processByOOP) {
                $oop = $processByOOP->get($o->original_order_process_id);
                return $oop && $oop->process ? ($oop->process->sequence ?? PHP_INT_MAX) : PHP_INT_MAX;
            })->values();

            // Encadenar: para i>0, ready_after_datetime = estimated_end_datetime de i-1
            for ($i = 0; $i < $sorted->count(); $i++) {
                /** @var ProductionOrder $current */
                $current = $sorted[$i];

                if ($i === 0) {
                    // El primero del grupo no depende de otro. Lo dejamos como está.
                    continue;
                }

                /** @var ProductionOrder $prev */
                $prev = $sorted[$i - 1];

                // Preferimos fecha real de fin si existe; si no, usamos la estimada
                $readyAfter = $prev->finished_at ?: $prev->estimated_end_datetime;

                // Si hay valor, persistirlo (aunque no exista cast en el modelo)
                if (!empty($readyAfter)) {
                    // Evitar escritura si no cambia, para reducir saves
                    if ($current->ready_after_datetime != $readyAfter) {
                        $current->ready_after_datetime = $readyAfter;
                        // Si no hay estimated_start_datetime, alinearlo con ready_after + margen de seguridad
                        if (empty($current->estimated_start_datetime)) {
                            try {
                                $safetyHours = (int) Config::get('production.ready_after_safety_hours', 6);
                                $current->estimated_start_datetime = Carbon::parse($readyAfter)->addHours($safetyHours);
                            } catch (\Throwable $e) {
                                // Fallback: si por alguna razón falla el parse, usar ready_after directamente
                                $current->estimated_start_datetime = $readyAfter;
                            }
                        }
                        $current->save();
                        $this->info("[ready_after] PO {$current->id} disponible desde {$readyAfter} (prev PO {$prev->id})");
                    }
                }
            }
        }
    }
    
    /**
     * Calcula el OEE promedio de una línea de producción basado en su historial reciente.
     *
     * @param int $productionLineId ID de la línea de producción
     * @param int $days Número de días para el historial
     * @param float $minimumPercentage Porcentaje mínimo de OEE a aplicar
     * @return float Porcentaje de OEE (0-100)
     */
    protected function getAverageOEE($productionLineId, $days, $minimumPercentage)
    {
        try {
            // Validar parámetros de entrada
            if (!$productionLineId || !is_numeric($productionLineId)) {
                Log::warning("ID de línea de producción inválido: {$productionLineId}");
                return $minimumPercentage;
            }
            
            $days = max(1, intval($days)); // Asegurar que days sea un entero positivo
            $minimumPercentage = max(1, min(100, floatval($minimumPercentage))); // Limitar entre 1% y 100%
            
            // Calcular la fecha límite para el historial
            $startDate = Carbon::now('Europe/Madrid')->subDays($days);
            
            // Obtener registros de OEE para la línea de producción
            $oeeHistory = ShiftHistory::where('production_line_id', $productionLineId)
                ->where('created_at', '>=', $startDate)
                ->where('oee', '>', 0) // Solo considerar registros con OEE válido
                ->select(DB::raw('AVG(oee * 100) as average_oee')) // Convertir a porcentaje
                ->first();
            
            // Si hay registros, usar el promedio; si no, usar el mínimo
            $averageOee = $oeeHistory && $oeeHistory->average_oee ? $oeeHistory->average_oee : $minimumPercentage;
            
            // Asegurar que no sea menor que el mínimo establecido
            $averageOee = max($averageOee, $minimumPercentage);
            
            // Redondear a 2 decimales
            return round($averageOee, 2);
        } catch (\Exception $e) {
            $this->error("Error al calcular OEE promedio: {$e->getMessage()}");
            Log::error("Error al calcular OEE promedio para línea ID {$productionLineId}: {$e->getMessage()}");
            // En caso de error, usar el mínimo
            return $minimumPercentage;
        }
    }
    
    /**
     * Ajusta un tiempo en segundos según el factor OEE.
     *
     * @param int $timeInSeconds Tiempo en segundos a ajustar
     * @param float $oeePercentage Porcentaje de OEE (0-100)
     * @return int Tiempo ajustado en segundos
     */
    protected function adjustTimeByOEE($timeInSeconds, $oeePercentage)
    {
        try {
            // Validar parámetros de entrada
            if (!is_numeric($timeInSeconds) || $timeInSeconds < 0) {
                Log::warning("Tiempo en segundos inválido: {$timeInSeconds}");
                return $timeInSeconds;
            }
            
            if (!is_numeric($oeePercentage) || $oeePercentage <= 0) {
                Log::warning("Porcentaje OEE inválido: {$oeePercentage}");
                return $timeInSeconds;
            }
            
            // Convertir a tipos adecuados
            $timeInSeconds = intval($timeInSeconds);
            $oeePercentage = floatval($oeePercentage);
            
            // Si el OEE es 100% o superior, no hay ajuste necesario
            if ($oeePercentage >= 100) {
                return $timeInSeconds;
            }
            
            // Convertir OEE a factor decimal (ej: 70% -> 0.7) y limitar a un mínimo de 0.01
            $oeeFactor = max(0.01, $oeePercentage / 100);
            
            // Ajustar el tiempo dividiendo por el factor OEE
            // Ejemplo: 1 hora con OEE de 50% -> 1h / 0.5 = 2h
            return ceil($timeInSeconds / $oeeFactor);
        } catch (\Exception $e) {
            Log::error("Error al ajustar tiempo por OEE: {$e->getMessage()}");
            // En caso de error, devolver el tiempo original sin ajustar
            return $timeInSeconds;
        }
    }
}
