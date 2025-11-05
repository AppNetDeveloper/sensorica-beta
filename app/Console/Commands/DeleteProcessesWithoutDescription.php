<?php

namespace App\Console\Commands;

use App\Models\Process;
use Illuminate\Console\Command;

class DeleteProcessesWithoutDescription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'processes:delete-without-description';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all processes that do not have a description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔍 Buscando procesos sin descripción...');

        // Buscar procesos sin descripción (NULL o vacío)
        $processesWithoutDescription = Process::whereNull('description')
            ->orWhere('description', '')
            ->get();

        $count = $processesWithoutDescription->count();

        if ($count === 0) {
            $this->info('✅ No se encontraron procesos sin descripción.');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Se encontraron {$count} proceso(s) sin descripción:");
        $this->newLine();

        // Mostrar tabla con los procesos que se van a eliminar
        $tableData = $processesWithoutDescription->map(function ($process) {
            return [
                'ID' => $process->id,
                'Código' => $process->code,
                'Nombre' => $process->name,
                'Secuencia' => $process->sequence,
                'Descripción' => $process->description ?? '(vacío)',
            ];
        })->toArray();

        $this->table(['ID', 'Código', 'Nombre', 'Secuencia', 'Descripción'], $tableData);
        $this->newLine();

        // Confirmar antes de eliminar
        if ($this->confirm('¿Estás seguro de que deseas eliminar estos procesos?', false)) {
            $this->info('🗑️  Eliminando procesos...');

            $deletedCount = 0;
            foreach ($processesWithoutDescription as $process) {
                $process->delete();
                $deletedCount++;
                $this->line("   ✓ Eliminado: {$process->code} - {$process->name}");
            }

            $this->newLine();
            $this->info("✅ Se eliminaron {$deletedCount} proceso(s) sin descripción.");

            return Command::SUCCESS;
        } else {
            $this->warn('❌ Operación cancelada. No se eliminó ningún proceso.');
            return Command::FAILURE;
        }
    }
}
