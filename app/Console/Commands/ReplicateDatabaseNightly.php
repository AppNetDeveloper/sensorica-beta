<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ReplicateDatabaseNightly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:replicate-nightly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dumps the primary database and replaces the secondary, detecting automatically between mysql/mariadb tools.';

    /**
     * Finds the first available executable from a list of candidates.
     *
     * @param array $commands
     * @return string|null
     */
    private function findExecutable(array $commands): ?string
    {
        foreach ($commands as $command) {
            // 'command -v' is a portable way to check if a command exists in the system's PATH.
            $process = Process::fromShellCommandline("command -v " . escapeshellarg($command));
            $process->run();
            if ($process->isSuccessful()) {
                return $command;
            }
        }
        return null;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('>>> Iniciando proceso de copia nocturna de base de datos');

        // --- NEW: Detect available database tools ---
        $this->info("Detectando herramientas de base de datos...");
        $dumpExecutable = $this->findExecutable(['mysqldump', 'mariadb-dump']);
        $clientExecutable = $this->findExecutable(['mysql', 'mariadb']);

        if (!$dumpExecutable) {
            $this->error("ERROR: No se encontró 'mysqldump' ni 'mariadb-dump'. Por favor, instale uno de los dos.");
            return 1;
        }

        if (!$clientExecutable) {
            $this->error("ERROR: No se encontró el cliente 'mysql' ni 'mariadb'. Por favor, instale uno de los dos.");
            return 1;
        }
        $this->info("-> Herramientas detectadas: '{$dumpExecutable}' y '{$clientExecutable}'.");

        // --- 0. Directorio temporal y retención de 7 días ---
        $tempDir = storage_path('app/backup-temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        foreach (glob("{$tempDir}/*.sql") as $oldFile) {
            if (filemtime($oldFile) < strtotime('-7 days')) {
                @unlink($oldFile);
                $this->info("-> Eliminado dump antiguo: " . basename($oldFile));
            }
        }

        // --- 1. Variables y paths ---
        $timestamp = date('YmdHis');
        $dumpFile  = "{$tempDir}/db_dump_boisol_{$timestamp}.sql";

        // --- 2. Configuración Origen ---
        $sourceConn   = Config::get('database.default');
        $sourceConfig = Config::get("database.connections.{$sourceConn}");
        if (!$sourceConfig) {
            $this->error("No se encontró la conexión de BD '{$sourceConn}'.");
            return 1;
        }
        $srcDb   = $sourceConfig['database'];
        $srcUser = $sourceConfig['username'];
        $srcPass = $sourceConfig['password'];
        $srcHost = $sourceConfig['host'];
        $srcPort = $sourceConfig['port'];

        // --- 3. Configuración Destino ---
        $tgtDb   = env('REPLICA_DB_DATABASE');
        $tgtUser = env('REPLICA_DB_USERNAME');
        $tgtPass = env('REPLICA_DB_PASSWORD');
        $tgtHost = env('REPLICA_DB_HOST');
        $tgtPort = env('REPLICA_DB_PORT', 3306);

        if (!$tgtDb || !$tgtUser || !$tgtPass || !$tgtHost) {
            $this->error("Faltan vars de entorno REPLICA_DB_* para BD destino.");
            return 1;
        }

        // --- 4. Dump con DROP TABLE (usando el ejecutable detectado) ---
        $this->info("Paso 1: Creando volcado de '{$srcDb}' en '{$dumpFile}'...");
        $dumpCmd = sprintf(
            '%s --skip-tz-utc --host=%s --port=%s --user=%s --password=%s ' .
            '--single-transaction --skip-lock-tables --routines --events ' .
            '--add-drop-table %s > %s',
            $dumpExecutable, // CAMBIO: Variable dinámica
            escapeshellarg($srcHost),
            escapeshellarg($srcPort),
            escapeshellarg($srcUser),
            escapeshellarg($srcPass),
            escapeshellarg($srcDb),
            escapeshellarg($dumpFile)
        );

        $procDump = Process::fromShellCommandline($dumpCmd);
        $procDump->setTimeout(3600);

        try {
            $procDump->mustRun();
            $this->info("-> Dump creado con éxito: {$dumpFile}");
        } catch (ProcessFailedException $e) {
            Log::error("Error al crear dump de '{$srcDb}': " . $e->getMessage());
            $this->error("ERROR: Falló la creación del volcado. El archivo quedará retenido en '{$tempDir}' durante 7 días para diagnóstico.");
            return 1;
        }

        // --- 5. Recrear BD destino (DROP + CREATE) corregido ---
        $this->info("Paso 2: Limpiando y recreando base de datos destino '{$tgtDb}'...");
        $sql = sprintf(
            "DROP DATABASE IF EXISTS `%s`; CREATE DATABASE `%s`;",
            $tgtDb,
            $tgtDb
        );
        $recreateCmd = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s -e %s',
            $clientExecutable, // CAMBIO: Variable dinámica
            escapeshellarg($tgtHost),
            escapeshellarg($tgtPort),
            escapeshellarg($tgtUser),
            escapeshellarg($tgtPass),
            escapeshellarg($sql)
        );

        $procRecreate = Process::fromShellCommandline($recreateCmd);
        $procRecreate->setTimeout(300);

        try {
            $procRecreate->mustRun();
            $this->info("-> Base de datos destino '{$tgtDb}' recreada correctamente.");
        } catch (ProcessFailedException $e) {
            Log::error("Error recrear BD destino '{$tgtDb}': " . $e->getMessage());
            $this->error("ERROR al limpiar la BD destino. Detalle:");
            $this->error($procRecreate->getErrorOutput());
            $this->error($procRecreate->getOutput());
            return 1;
        }

        // --- 6. Restaurar Dump en Destino ---
        $this->info("Paso 3: Restaurando volcado en '{$tgtDb}'...");
        $restoreCmd = sprintf(
            '%s --host=%s --port=%s --user=%s --password=%s %s < %s',
            $clientExecutable, // CAMBIO: Variable dinámica
            escapeshellarg($tgtHost),
            escapeshellarg($tgtPort),
            escapeshellarg($tgtUser),
            escapeshellarg($tgtPass),
            escapeshellarg($tgtDb),
            escapeshellarg($dumpFile)
        );

        $procRestore = Process::fromShellCommandline($restoreCmd);
        $procRestore->setTimeout(7200);

        try {
            $procRestore->mustRun();
            $this->info("-> Dump restaurado con éxito en '{$tgtDb}'.");
        } catch (ProcessFailedException $e) {
            Log::error("Error restaurar en '{$tgtDb}': " . $e->getMessage());
            $this->error("ERROR: Falló la restauración en la BD destino. El dump permanecerá hasta 7 días para diagnóstico.");
            return 1;
        }

        // --- 7. Limpieza del dump tras éxito completo ---
        if (file_exists($dumpFile)) {
            @unlink($dumpFile);
            $this->info("-> Dump temporal eliminado: {$dumpFile}");
        }

        $this->info('>>> Proceso completado con éxito.');
        return 0;
    }
}
