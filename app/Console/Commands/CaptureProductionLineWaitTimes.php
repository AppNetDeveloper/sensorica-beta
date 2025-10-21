<?php

namespace App\Console\Commands;

use App\Models\ProductionLine;
use App\Models\ProductionLineWaitTimeHistory;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaptureProductionLineWaitTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:capture-line-wait-times {--force : Recalcula aunque exista un registro para la hora actual} {--once : Ejecuta solo una captura y finaliza}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Captura WT y WTM (tiempo medio y mediano de espera) por línea de producción cada hora';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $shouldOverwrite = (bool) $this->option('force');
        $runOnce = (bool) $this->option('once');

        do {
            $capturedAt = Carbon::now()->startOfHour();

            $success = $this->captureForHour($capturedAt, $shouldOverwrite);

            if (!$success) {
                return self::FAILURE;
            }

            if ($runOnce) {
                break;
            }

            $this->line('⏰ Pausa de 60 minutos antes de la próxima captura de WT/WTM...');
            sleep(3600); // 1 hora
        } while (true);

        return self::SUCCESS;
    }

    /**
     * Ejecuta la captura para una hora determinada.
     */
    protected function captureForHour(Carbon $capturedAt, bool $shouldOverwrite): bool
    {
        $this->info('📊 Capturando WT/WTM para la hora: ' . $capturedAt->toDateTimeString());

        $lines = ProductionLine::query()->get();

        if ($lines->isEmpty()) {
            $this->warn('⚠️ No se encontraron líneas de producción.');
            return true;
        }

        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $existing = ProductionLineWaitTimeHistory::query()
                    ->where('production_line_id', $line->id)
                    ->where('captured_at', $capturedAt)
                    ->first();

                if ($existing && !$shouldOverwrite) {
                    $this->line("ℹ️ Registro ya existente para la línea {$line->id} ({$line->name}) a las {$capturedAt->toTimeString()}, usar --force para sobrescribir.");
                    continue;
                }

                // Obtener órdenes pendientes o en progreso de esta línea
                $orders = ProductionOrder::query()
                    ->where('production_line_id', $line->id)
                    ->whereIn('status', [0, 1]) // 0: Pendiente, 1: En progreso
                    ->whereNotNull('estimated_start_datetime')
                    ->get(['id', 'estimated_start_datetime']);

                if ($orders->isEmpty()) {
                    $this->line("ℹ️ Línea {$line->id} ({$line->name}) - Sin órdenes con estimated_start_datetime");
                    
                    // Guardar registro con valores null
                    ProductionLineWaitTimeHistory::updateOrCreate(
                        [
                            'production_line_id' => $line->id,
                            'captured_at' => $capturedAt,
                        ],
                        [
                            'order_count' => 0,
                            'wait_time_mean' => null,
                            'wait_time_median' => null,
                            'wait_time_min' => null,
                            'wait_time_max' => null,
                        ]
                    );
                    continue;
                }

                // Calcular wait times en minutos
                $now = Carbon::now();
                $waitMinutes = [];

                foreach ($orders as $order) {
                    try {
                        $estimatedStart = Carbon::parse($order->estimated_start_datetime);
                        $diffMinutes = $now->diffInMinutes($estimatedStart, false);
                        // Valor positivo = esperando (ya pasó la hora de inicio)
                        // Valor negativo = aún no llegó la hora
                        $waitMinutes[] = -$diffMinutes; // Invertimos para que positivo = espera
                    } catch (\Exception $e) {
                        Log::warning("Error parseando estimated_start_datetime para orden {$order->id}: {$e->getMessage()}");
                        continue;
                    }
                }

                if (empty($waitMinutes)) {
                    $this->warn("⚠️ Línea {$line->id} ({$line->name}) - No se pudieron calcular wait times");
                    continue;
                }

                // Calcular estadísticas
                $orderCount = count($waitMinutes);
                $mean = array_sum($waitMinutes) / $orderCount;
                $min = min($waitMinutes);
                $max = max($waitMinutes);

                // Calcular mediana
                sort($waitMinutes);
                $mid = floor($orderCount / 2);
                $median = ($orderCount % 2 === 0)
                    ? ($waitMinutes[$mid - 1] + $waitMinutes[$mid]) / 2
                    : $waitMinutes[$mid];

                ProductionLineWaitTimeHistory::updateOrCreate(
                    [
                        'production_line_id' => $line->id,
                        'captured_at' => $capturedAt,
                    ],
                    [
                        'order_count' => $orderCount,
                        'wait_time_mean' => round($mean, 2),
                        'wait_time_median' => round($median, 2),
                        'wait_time_min' => round($min, 2),
                        'wait_time_max' => round($max, 2),
                    ]
                );

                $this->info("✅ Línea {$line->id} ({$line->name}) | Órdenes: {$orderCount} | WT: " . round($mean, 2) . "m | WTM: " . round($median, 2) . "m");
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('❌ Error capturando wait times de líneas de producción', [
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);
            $this->error('❌ Ocurrió un error: ' . $throwable->getMessage());
            return false;
        }

        $this->info('✅ Captura de WT/WTM finalizada.');
        return true;
    }
}
