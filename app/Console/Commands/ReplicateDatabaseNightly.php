<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ReplicateDatabaseNightly extends Command
{
    protected $signature = 'db:replicate-nightly';
    protected $description = 'Dumps the primary database (boisol) and fully replaces the secondary database (sol), reteniendo dumps fallidos 7 días.';

    public function handle()
    {
        $this->info('>>> Iniciando proceso de copia nocturna de base de datos');

        // --- 0. Directorio temporal y retención de 7 días ---
        $tempDir = storage_path('app/backup-temp');
        if (! is_dir($tempDir)) {
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
        if (! $sourceConfig) {
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

        if (! $tgtDb || ! $tgtUser || ! $tgtPass || ! $tgtHost) {
            $this->error("Faltan vars de entorno REPLICA_DB_* para BD destino.");
            return 1;
        }

        // --- 4. Dump con DROP TABLE ---
        $this->info("Paso 1: Creando volcado de '{$srcDb}' en '{$dumpFile}'...");
        $dumpCmd = sprintf(
            'mariadb-dump --skip-tz-utc --host=%s --port=%s --user=%s --password=%s '.
            '--single-transaction --skip-lock-tables --routines --events '.
            '--add-drop-table %s > %s',
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
            'mariadb --host=%s --port=%s --user=%s --password=%s -e %s',
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
            // Registra el mensaje de excepción
            Log::error("Error recrear BD destino '{$tgtDb}': " . $e->getMessage());
            // Muestra en consola el detalle de STDERR y STDOUT
            $this->error("ERROR al limpiar la BD destino. Detalle:");
            $this->error($procRecreate->getErrorOutput());
            $this->error($procRecreate->getOutput());
            return 1;
        }
        

        // --- 6. Restaurar Dump en Destino ---
        $this->info("Paso 3: Restaurando volcado en '{$tgtDb}'...");
        $restoreCmd = sprintf(
            'mariadb --host=%s --port=%s --user=%s --password=%s %s < %s',
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
