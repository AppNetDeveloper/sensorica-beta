<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Process;
use App\Models\IaPrompt;

class IaEnsureProcessGroupPrompts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan ia:ensure-process-group-prompts
     */
    protected $signature = 'ia:ensure-process-group-prompts {--model_name=} {--activate=1} {--update=0}';

    /**
     * The console command description.
     */
    protected $description = 'Crea (si no existen) plantillas de ia-prompts para cada description única de procesos (CNC, COR, etc.).';

    public function handle(): int
    {
        $modelName = $this->option('model_name') ?: null;
        $activate = (string)$this->option('activate') !== '0';
        $forceUpdate = (string)$this->option('update') !== '0';

        // Traer descripciones únicas no nulas
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

        $created = 0; $skipped = 0;
        foreach ($descriptions as $desc) {
            $slug = Str::slug($desc, '_');
            $key = 'process_group.' . $slug;
            $name = 'Grupo de Proceso: ' . $desc;

            // Plantilla base editable
            $content = <<<TPL
 Debes asignar órdenes no planificadas del grupo "{{description}}" a las líneas de producción más adecuadas.

Datos de entrada (JSON):
{{json}}

Reglas obligatorias:
- No asignes una orden a una línea que no tenga el código de proceso requerido por esa orden (process_code debe existir en process_codes_present de la línea).
- Evita saturar una sola línea. Reparte la carga intentando equilibrar las horas planificadas entre líneas, considerando:
  - Carga actual planificada de cada línea en segundos: lines[].planned_theoretical_time
  - Tiempo teórico de cada orden en segundos: unplanned_orders[].theoretical_time
- Si varias líneas son válidas, elige la que deje el reparto de carga lo más equilibrado posible.
- Si ninguna línea cumple (por ausencia de process_code), marca esa orden como no asignable con una causa.

Salida requerida:
Responde ÚNICAMENTE con el JSON limpio, sin presentaciones, comentarios ni explicaciones adicionales. Solo el JSON:

{
  "assignments": [
    { "id": <production_order_id>, "production_line_id": <id_linea> },
    ...
  ],
  "unassignable": [
    { "id": <production_order_id>, "reason": "<motivo de no asignación>" },
    ...
  ]
}
TPL;

            $existing = IaPrompt::where('key', $key)->first();
            if ($existing) {
                if ($forceUpdate) {
                    $existing->update([
                        'name' => $name,
                        'content' => $content,
                        'model_name' => $modelName,
                        'is_active' => $activate,
                    ]);
                    $this->info("[upd] Actualizado prompt existente: {$key}");
                } else {
                    $this->line("[skip] {$key} ya existe. Use --update=1 para sobrescribir.");
                    $skipped++;
                }
                continue;
            }

            IaPrompt::create([
                'key' => $key,
                'name' => $name,
                'content' => $content,
                'model_name' => $modelName,
                'is_active' => $activate,
            ]);
            $this->info("[ok] Creado prompt: {$key}");
            $created++;
        }

        $this->info("Finalizado. Creados={$created}, existentes={$skipped}");
        return self::SUCCESS;
    }
}
