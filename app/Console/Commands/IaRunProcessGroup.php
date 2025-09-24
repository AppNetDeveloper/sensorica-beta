<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Process;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\IaPrompt;
use App\Models\IaPromptExecution;

class IaRunProcessGroup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan ia:run-process-group "CNC" --customer=1
     * Usage: php artisan ia:run-process-group --all --customer=1 --delay=10
     */
    protected $signature = 'ia:run-process-group {description? : Process description group (e.g. CNC, COR). Omitir para procesar todos} {--customer=} {--all : Procesar todos los grupos de descripción} {--delay=10 : Segundos de pausa entre grupos cuando se procesan todos} {--limit=50 : Máximo número de órdenes no planificadas por ejecución}';

    /**
     * The console command description.
     */
    protected $description = 'Genera ejecuciones de IA para un grupo de procesos (por description) o todos los grupos si no se especifica descripción.';

    public function handle(): int
    {
        $description = $this->argument('description');
        $customerId = $this->option('customer');
        $processAll = $this->option('all') || empty($description);
        $delay = max(0, (int)$this->option('delay'));
        $limit = max(1, (int)$this->option('limit'));

        if ($processAll) {
            return $this->processAllGroups($customerId, $delay, $limit);
        }

        $description = trim($description);
        return $this->processSingleGroup($description, $customerId, $limit);
    }

    /**
     * Procesa todos los grupos de descripción únicos con pausa entre cada uno.
     */
    private function processAllGroups(?string $customerId, int $delay, int $limit): int
    {
        // Obtener todas las descripciones únicas
        $descriptions = Process::query()
            ->whereNotNull('description')
            ->distinct()
            ->pluck('description')
            ->filter(fn($d) => trim((string)$d) !== '')
            ->values();

        if ($descriptions->isEmpty()) {
            $this->warn('No se encontraron descripciones en la tabla processes.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$descriptions->count()} grupos de procesos con {$delay}s de pausa entre cada uno...");
        
        $processed = 0;
        $errors = 0;

        foreach ($descriptions as $desc) {
            $this->info("--- Procesando grupo: {$desc} ---");
            
            try {
                $result = $this->processSingleGroup($desc, $customerId, $limit);
                if ($result === self::SUCCESS) {
                    $processed++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                $this->error("Error procesando grupo '{$desc}': " . $e->getMessage());
                $errors++;
            }

            // Pausa entre grupos (excepto en el último)
            if ($delay > 0 && $desc !== $descriptions->last()) {
                $this->info("Pausando {$delay} segundos...");
                sleep($delay);
            }
        }

        $this->info("Finalizado. Procesados: {$processed}, Errores: {$errors}");
        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Procesa un grupo específico de descripción.
     */
    private function processSingleGroup(string $description, ?string $customerId, int $limit = 50): int
    {
        // Clave del prompt (plantilla editable en ia_prompts)
        $slug = Str::slug($description, '_');
        $promptKey = 'process_group.' . $slug;
        $category = 'process_group';
        $subcategory = $description;

        $this->info("Construyendo variables para description='{$description}'");

        // 1) Processes del grupo: ids y codes
        $processes = Process::query()
            ->whereNotNull('description')
            ->where('description', $description)
            ->get(['id', 'code', 'name']);

        if ($processes->isEmpty()) {
            $this->warn('No se encontraron procesos con esa description.');
        }

        $processIds = $processes->pluck('id')->all();
        $processCodes = $processes->pluck('code')->values()->all();

        // 2) Production lines que tienen alguno de esos procesos
        $linesQuery = ProductionLine::query()
            ->with(['processes' => function ($q) use ($processIds) {
                $q->whereIn('processes.id', $processIds);
            }])
            ->whereHas('processes', function ($q) use ($processIds) {
                $q->whereIn('processes.id', $processIds);
            });

        if (!empty($customerId)) {
            $linesQuery->where('customer_id', (int)$customerId);
        }

        $lines = $linesQuery->get(['id', 'name', 'customer_id']);

        // Cargar carga planificada por línea (status = 0 => planificado pero no iniciado)
        $lineIds = $lines->pluck('id')->all();
        $plannedByLine = [];
        if (!empty($lineIds)) {
            $plannedByLine = DB::table('production_orders')
                ->select('production_line_id', DB::raw('COALESCE(SUM(theoretical_time), 0) as total_theoretical_time'))
                ->whereIn('production_line_id', $lineIds)
                ->where('status', 0)
                ->groupBy('production_line_id')
                ->pluck('total_theoretical_time', 'production_line_id')
                ->toArray();
        }

        $linesPayload = $lines->map(function ($line) use ($processCodes, $plannedByLine) {
            $presentCodes = collect($line->processes)->pluck('code')->values()->all();
            $presentCodes = array_values(array_intersect($processCodes, $presentCodes));
            $plannedSeconds = (int)($plannedByLine[$line->id] ?? 0);
            return [
                'line_id' => $line->id,
                // Mantenemos line_name y customer_id en el comando pero no los enviamos a la IA
                // 'line_name' => $line->name,
                // 'customer_id' => $line->customer_id,
                'process_codes_present' => $presentCodes,
                'planned_theoretical_time' => $plannedSeconds, // segundos ya planificados (status=0)
            ];
        })->values()->all();

        // 3) Production orders no planificadas del grupo (production_line_id IS NULL)
        // Limitar número de órdenes para evitar exceso de tokens
        $unplannedOrders = ProductionOrder::query()
            ->whereNull('production_line_id')
            ->where('process_category', $description)
            ->where('status', 0) // Solo órdenes pendientes de iniciar
            ->orderBy('id') // Orden consistente para procesamiento por lotes
            ->limit($limit)
            ->get(['id', 'order_id', 'process_category', 'original_order_process_id', 'theoretical_time', 'production_line_id']);

        $totalUnplanned = ProductionOrder::query()
            ->whereNull('production_line_id')
            ->where('process_category', $description)
            ->where('status', 0) // Solo contar las que realmente necesitan planificación
            ->count();

        if ($totalUnplanned > $limit) {
            $this->warn("Limitando a {$limit} órdenes de {$totalUnplanned} totales para evitar exceso de tokens.");
        }

        // Mapa original_order_process_id -> process_code en una sola consulta
        $oopIds = $unplannedOrders->pluck('original_order_process_id')->filter()->unique()->values()->all();
        $codeByOopId = [];
        if (!empty($oopIds)) {
            $rows = DB::table('original_order_processes as oop')
                ->join('processes as p', 'p.id', '=', 'oop.process_id')
                ->whereIn('oop.id', $oopIds)
                ->get(['oop.id as oop_id', 'p.code as process_code']);
            foreach ($rows as $r) {
                $codeByOopId[(int)$r->oop_id] = $r->process_code;
            }
        }

        $unplannedPayload = $unplannedOrders->map(function ($po) use ($codeByOopId) {
            $code = $po->original_order_process_id ? ($codeByOopId[(int)$po->original_order_process_id] ?? null) : null;
            return [
                'id' => $po->id,
                // Campos que mantenemos en el comando pero no enviamos a la IA para reducir tokens
                // 'production_order_id' => $po->id,
                // 'order_id' => $po->order_id,
                // 'process_category' => $po->process_category,
                // 'production_line_id' => $po->production_line_id, // Siempre null para no planificadas
                'process_code' => $code,
                'theoretical_time' => (int)($po->theoretical_time ?? 0),
            ];
        })->values()->all();

        // 4) Construir variables_json
        $variables = [
            'description' => $description,
            'codes' => $processCodes,
            'lines' => $linesPayload,
            'unplanned_orders' => $unplannedPayload,
        ];

        // 5) Cargar plantilla del prompt
        $prompt = IaPrompt::where('key', $promptKey)->where('is_active', true)->first();
        $template = $prompt?->content ?? "Analiza y planifica el grupo: {{description}}\n\nDatos:\n{{json}}";

        // 6) Render simplificado: reemplazar {{description}} y adjuntar JSON legible
        $rendered = str_replace('{{description}}', (string)$description, $template);
        $rendered = str_replace('{{json}}', json_encode($variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $rendered);

        // 7) Crear IaPromptExecution en estado queued (sin llamar a IA aún)
        $execution = IaPromptExecution::create([
            'customer_id' => $customerId ? (int)$customerId : null,
            'prompt_key' => $promptKey,
            'category' => $category,
            'subcategory' => $subcategory,
            'model_name' => $prompt?->model_name,
            'ai_provider' => null,
            'ai_url_used' => null,
            'variables_json' => $variables,
            'prompt_text' => $rendered,
            'response_json' => null,
            'response_text' => null,
            'tasker_id' => null,
            'status' => 'queued',
            'error_message' => null,
            'http_status' => null,
            'retry_count' => 0,
            'max_retries' => 10,
            'last_polled_at' => null,
            'next_poll_at' => null,
            'started_at' => now(),
            'finished_at' => null,
            'created_by' => auth()->id() ?? null,
        ]);

        $this->info('Ejecución creada ID='.$execution->id.' con status='.$execution->status.' y prompt_key='.$execution->prompt_key);
        return self::SUCCESS;
    }
}
