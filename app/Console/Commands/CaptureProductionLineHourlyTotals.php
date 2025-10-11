<?php

namespace App\Console\Commands;

use App\Models\ProductionLine;
use App\Models\ProductionLineHourlyTotal;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaptureProductionLineHourlyTotals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:capture-line-hourly-totals {--force : Recalcula aunque exista un registro para la hora actual} {--once : Ejecuta solo una captura y finaliza}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Captura el total de theoretical_time por línea de producción para la hora actual';

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

            $this->line('Pausa de 60 minutos antes de la próxima captura...');
            sleep(3600);
        } while (true);

        return self::SUCCESS;
    }

    /**
     * Ejecuta la captura para una hora determinada.
     */
    protected function captureForHour(Carbon $capturedAt, bool $shouldOverwrite): bool
    {
        $this->info('Capturando totales para la hora: ' . $capturedAt->toDateTimeString());

        $lines = ProductionLine::query()
            ->with(['processes' => function ($query) {
                $query->orderBy('production_line_process.order');
            }])
            ->get();

        if ($lines->isEmpty()) {
            $this->warn('No se encontraron líneas de producción.');
            return true;
        }

        DB::beginTransaction();
        try {
            foreach ($lines as $line) {
                $firstProcess = $line->processes->first();

                if (!$firstProcess) {
                    $message = "La línea {$line->id} no tiene procesos configurados.";
                    Log::warning($message);
                    $this->warn($message);
                    continue;
                }

                $existing = ProductionLineHourlyTotal::query()
                    ->where('production_line_id', $line->id)
                    ->where('captured_at', $capturedAt)
                    ->first();

                if ($existing && !$shouldOverwrite) {
                    $this->line("Registro ya existente para la línea {$line->id} a las {$capturedAt->toTimeString()}, usar --force para sobrescribir.");
                    continue;
                }

                $totalTime = (int) round(ProductionOrder::query()
                    ->where('production_line_id', $line->id)
                    ->whereIn('status', [0, 1])
                    ->sum('theoretical_time'));

                ProductionLineHourlyTotal::updateOrCreate(
                    [
                        'production_line_id' => $line->id,
                        'captured_at' => $capturedAt,
                    ],
                    [
                        'process_id' => $firstProcess->id,
                        'total_time' => $totalTime,
                    ]
                );

                $this->info("Línea {$line->id} | Proceso {$firstProcess->id} | Total: {$totalTime} segundos");
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            Log::error('Error capturando totales horarios de líneas de producción', [
                'error' => $throwable->getMessage(),
            ]);
            $this->error('Ocurrió un error: ' . $throwable->getMessage());
            return false;
        }

        $this->info('Captura finalizada.');
        return true;
    }
}
