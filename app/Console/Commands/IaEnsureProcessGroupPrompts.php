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
Planifica la fábrica: organiza los pedidos del grupo "{{description}}" asignándolos a las líneas de producción, cumpliendo las reglas.

Datos de entrada (JSON):
{{json}}

Reglas obligatorias:
- Usa únicamente la información provista. No pidas datos adicionales.
- Cada orden ya incluye su tiempo teórico en segundos: unplanned_orders[].theoretical_time.
- Solo asigna una orden a una línea que tenga el código de proceso requerido (process_code debe existir en process_codes_present de la línea).
- Asigna todas las órdenes compatibles; si una orden no es compatible con ninguna línea, inclúyela en unassignable con una causa breve.
- No existe un límite máximo de tiempo ni de número de órdenes por línea. Asigna todas las órdenes posibles.
- Equilibra la carga de forma aproximada: intenta que la suma (lines[].planned_theoretical_time + órdenes asignadas) quede razonablemente similar entre líneas, sin buscar perfección ni imponer límites duros; pequeñas desviaciones son aceptables.
- No uses siempre la misma línea de producción: distribuye las órdenes entre todas las líneas válidas.
- Si varias líneas son válidas, elige la que deje el reparto más equilibrado en términos generales.

Salida requerida:
Responde ÚNICAMENTE con el JSON limpio, sin preguntas, sin presentaciones, sin comentarios ni explicaciones adicionales. No añadas claves adicionales; usa exactamente la estructura y claves indicadas. Solo el JSON:

{
  "assignments": [
    { "id": <production_order_id>, "production_line_id": <id_linea> }
  ],
  "unassignable": [
    { "id": <production_order_id>, "reason": "<motivo de no asignación>" }
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
