<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config; // Para leer la configuración de BD
use Illuminate\Support\Facades\Log;     // Para escribir logs de errores
use Symfony\Component\Process\Process; // Para ejecutar comandos de consola
use Symfony\Component\Process\Exception\ProcessFailedException; // Para capturar errores del proceso

class ReplicateDatabaseNightly extends Command
{
    /**
     * The name and signature of the console command.
     * El nombre con el que llamarás al comando: php artisan db:replicate-nightly
     * @var string
     */
    protected $signature = 'db:replicate-nightly';

    /**
     * The console command description.
     * Descripción que aparecerá cuando ejecutes php artisan list
     * @var string
     */
    protected $description = 'Dumps the primary database (boisol) and restores it to the secondary database (sol).';

    /**
     * Execute the console command.
     * Aquí va la lógica principal del comando.
     * @return int 0 si éxito, 1 (u otro > 0) si falla
     */
    public function handle()
    {
        $this->info('>>> Iniciando proceso de copia nocturna de base de datos (boisol -> sol)...');

        // --- Configuración ---
        $dumpFile = storage_path('app/db_dump_boisol_' . date('YmdHis') . '.sql'); // Archivo temporal para el dump

        // --- Obtener detalles de la Base de Datos Origen (boisol) ---
        // Asume que la conexión por defecto de Laravel apunta a 'boisol' según tu .env
        $sourceConnectionName = Config::get('database.default');
        $sourceConfig = Config::get('database.connections.' . $sourceConnectionName);

        if (!$sourceConfig) {
            $this->error("Error: No se encontró la configuración para la conexión de base de datos por defecto: {$sourceConnectionName}");
            return 1;
        }
        // Asegúrate de que la base de datos origen es 'boisol' según la configuración leída
        if (strtolower($sourceConfig['database']) !== 'boisol') {
             $this->warn("Advertencia: La conexión por defecto '{$sourceConnectionName}' apunta a la BD '{$sourceConfig['database']}' en lugar de 'boisol'. Continuando de todas formas...");
             // Podrías parar aquí si quieres ser estricto:
             // $this->error("Error: La conexión por defecto '{$sourceConnectionName}' no apunta a la BD 'boisol'. Revisa tu configuración.");
             // return 1;
        }
        $sourceDbName = $sourceConfig['database']; // Nombre real leído (debería ser boisol)
        $sourceDbUser = $sourceConfig['username'];
        $sourceDbPassword = $sourceConfig['password'];
        $sourceDbHost = $sourceConfig['host'];
        $sourceDbPort = $sourceConfig['port'];


        // --- Obtener detalles de la Base de Datos Destino (sol) ---
        // Lee directamente de las variables .env que definimos
        $targetDbName = env('REPLICA_DB_DATABASE'); // Debería ser 'sol'
        $targetDbUser = env('REPLICA_DB_USERNAME');
        $targetDbPassword = env('REPLICA_DB_PASSWORD');
        $targetDbHost = env('REPLICA_DB_HOST');
        $targetDbPort = env('REPLICA_DB_PORT', 3306); // Puerto por defecto si no está en .env

        // Validar que tenemos los datos necesarios para el destino
        if (!$targetDbName || !$targetDbUser || !$targetDbPassword || !$targetDbHost) {
             $this->error("Error: Faltan variables de entorno para la base de datos destino (REPLICA_DB_DATABASE, REPLICA_DB_USERNAME, REPLICA_DB_PASSWORD, REPLICA_DB_HOST).");
             return 1;
        }
         // Asegúrate de que la base de datos destino es 'sol'
        if (strtolower($targetDbName) !== 'sol') {
             $this->warn("Advertencia: La variable REPLICA_DB_DATABASE es '{$targetDbName}' en lugar de 'sol'. Se restaurará en '{$targetDbName}'.");
             // Podrías parar aquí si quieres ser estricto:
             // $this->error("Error: La variable REPLICA_DB_DATABASE no es 'sol'. Revisa tu .env.");
             // return 1;
        }


        // --- 1. Crear Dump de la Base de Datos Origen (boisol) ---
        $this->info("Paso 1: Creando volcado de la base de datos origen '{$sourceDbName}'...");

        // NOTA IMPORTANTE SOBRE SEGURIDAD:
        // Pasar la contraseña directamente con --password es inseguro porque puede aparecer
        // en la lista de procesos del sistema. Es MUCHO MÁS SEGURO configurar un archivo
        // de opciones .my.cnf para el usuario que ejecuta este script.
        // Si usas .my.cnf, puedes omitir --user y --password aquí.
        $dumpCommand = sprintf(
            'mariadb-dump --host=%s --port=%s --user=%s --password=%s --single-transaction --skip-lock-tables --routines --events %s > %s',
            escapeshellarg($sourceDbHost),
            escapeshellarg($sourceDbPort),
            escapeshellarg($sourceDbUser),
            escapeshellarg($sourceDbPassword), // ¡INSEGURO! Considerar .my.cnf
            escapeshellarg($sourceDbName),      // Base de datos específica a dumpear (boisol)
            escapeshellarg($dumpFile)           // Archivo de salida
        );

        $processDump = Process::fromShellCommandline($dumpCommand);
        $processDump->setTimeout(3600); // Timeout de 1 hora para el dump (ajusta si es necesario)

        try {
            $processDump->mustRun();
            $this->info("-> Volcado de '{$sourceDbName}' creado con éxito en: " . $dumpFile);
        } catch (ProcessFailedException $exception) {
            Log::error("Fallo al crear el dump de '{$sourceDbName}': " . $exception->getMessage());
            $this->error("ERROR: Fallo al crear el dump de la base de datos origen.");
            // Intenta borrar el archivo parcial si existe
            if (file_exists($dumpFile)) {
                 @unlink($dumpFile);
            }
            return 1; // Termina con error
        }

        // --- 2. Restaurar Dump en la Base de Datos Destino (sol) ---
        $this->info("Paso 2: Restaurando volcado en la base de datos destino '{$targetDbName}' en {$targetDbHost}...");

        // ASUNCIÓN IMPORTANTE: Se asume que la base de datos destino ('sol') ya existe
        // en el servidor destino. Este script no la crea.

        // Mismo comentario de seguridad sobre la contraseña que para mariadb-dump
        $restoreCommand = sprintf(
            'mariadb --host=%s --port=%s --user=%s --password=%s %s < %s',
            escapeshellarg($targetDbHost),
            escapeshellarg($targetDbPort),
            escapeshellarg($targetDbUser),
            escapeshellarg($targetDbPassword), // ¡INSEGURO! Considerar .my.cnf
            escapeshellarg($targetDbName),      // Base de datos específica donde restaurar (sol)
            escapeshellarg($dumpFile)           // Archivo de entrada
        );

        $processRestore = Process::fromShellCommandline($restoreCommand);
        $processRestore->setTimeout(7200); // Timeout de 2 horas para restaurar (ajusta si es necesario)

        try {
            $processRestore->mustRun();
            $this->info("-> Volcado restaurado con éxito en la base de datos '{$targetDbName}'.");
        } catch (ProcessFailedException $exception) {
            Log::error("Fallo al restaurar el dump en '{$targetDbName}': " . $exception->getMessage());
            $this->error("ERROR: Fallo al restaurar el dump en la base de datos destino.");
            // No borres el dump aquí, puede ser útil para investigar el fallo manualmente
            return 1; // Termina con error
        } finally {
            // --- 3. Limpieza ---
            // Este bloque 'finally' se ejecuta siempre, haya habido éxito o error en la restauración
            // (siempre que el dump se haya creado con éxito antes).
            if (file_exists($dumpFile)) {
                 $this->info("Paso 3: Limpiando archivo de volcado temporal...");
                 if (@unlink($dumpFile)) {
                    $this->info("-> Archivo temporal '{$dumpFile}' eliminado.");
                 } else {
                    Log::warning("No se pudo eliminar el archivo de volcado temporal: {$dumpFile}");
                    $this->warn("Advertencia: No se pudo eliminar el archivo de volcado temporal: {$dumpFile}");
                 }
            }
        }

        $this->info('>>> Proceso de copia nocturna de base de datos finalizado con éxito.');
        return 0; // Termina con éxito
    }
}