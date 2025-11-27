<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ServerController extends Controller
{
    /**
     * Directorio donde se guardan los backups locales
     */
    protected string $backupPath = 'backups';

    public function index()
    {
        return view('server.index');
    }

    /**
     * Crear un backup de la base de datos
     */
    public function createBackup(Request $request)
    {
        try {
            // Crear directorio si no existe
            $backupDir = storage_path('app/' . $this->backupPath);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generar nombre de archivo con timestamp
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;

            // Obtener configuración de la base de datos
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port', 3306);
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            // Ejecutar mysqldump (stderr a /dev/null para evitar warnings en el archivo)
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s %s 2>/dev/null > %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                // Limpiar archivo si hubo error
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                return response()->json([
                    'success' => false,
                    'message' => __('Error creating backup'),
                    'error' => implode("\n", $output)
                ], 500);
            }

            // Obtener tamaño del archivo
            $size = filesize($filepath);

            return response()->json([
                'success' => true,
                'message' => __('Backup created successfully'),
                'backup' => [
                    'filename' => $filename,
                    'size' => $this->formatBytes($size),
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error creating backup'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los backups disponibles
     */
    public function listBackups()
    {
        try {
            $backupDir = storage_path('app/' . $this->backupPath);
            $backups = [];

            if (is_dir($backupDir)) {
                $files = glob($backupDir . '/*.sql');

                foreach ($files as $file) {
                    $filename = basename($file);
                    $backups[] = [
                        'filename' => $filename,
                        'size' => $this->formatBytes(filesize($file)),
                        'size_bytes' => filesize($file),
                        'created_at' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }

                // Ordenar por fecha de creación (más reciente primero)
                usort($backups, function ($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
            }

            return response()->json([
                'success' => true,
                'backups' => $backups
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error listing backups'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar un backup específico
     */
    public function downloadBackup(string $filename): BinaryFileResponse
    {
        // Validar nombre de archivo (solo caracteres seguros)
        if (!preg_match('/^backup_[\d\-_]+\.sql$/', $filename)) {
            abort(400, __('Invalid filename'));
        }

        $filepath = storage_path('app/' . $this->backupPath . '/' . $filename);

        if (!file_exists($filepath)) {
            abort(404, __('Backup not found'));
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/sql'
        ]);
    }

    /**
     * Eliminar un backup específico
     */
    public function deleteBackup(string $filename)
    {
        try {
            // Validar nombre de archivo (solo caracteres seguros)
            if (!preg_match('/^backup_[\d\-_]+\.sql$/', $filename)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid filename')
                ], 400);
            }

            $filepath = storage_path('app/' . $this->backupPath . '/' . $filename);

            if (!file_exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Backup not found')
                ], 404);
            }

            unlink($filepath);

            return response()->json([
                'success' => true,
                'message' => __('Backup deleted successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error deleting backup'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir un backup existente
     */
    public function uploadBackup(Request $request)
    {
        try {
            $request->validate([
                'backup_file' => 'required|file|max:512000' // Max 500MB
            ]);

            $file = $request->file('backup_file');
            $originalName = $file->getClientOriginalName();

            // Validar extensión
            if ($file->getClientOriginalExtension() !== 'sql') {
                return response()->json([
                    'success' => false,
                    'message' => __('Only .sql files are allowed')
                ], 400);
            }

            // Crear directorio si no existe
            $backupDir = storage_path('app/' . $this->backupPath);
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generar nombre seguro con prefijo uploaded_
            $filename = 'uploaded_' . date('Y-m-d_H-i-s') . '.sql';
            $file->move($backupDir, $filename);

            return response()->json([
                'success' => true,
                'message' => __('Backup uploaded successfully'),
                'backup' => [
                    'filename' => $filename,
                    'original_name' => $originalName,
                    'size' => $this->formatBytes(filesize($backupDir . '/' . $filename)),
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error uploading backup'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar un backup específico
     */
    public function restoreBackup(string $filename)
    {
        try {
            // Validar nombre de archivo (backup_ o uploaded_)
            if (!preg_match('/^(backup|uploaded)_[\d\-_]+\.sql$/', $filename)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid filename')
                ], 400);
            }

            $filepath = storage_path('app/' . $this->backupPath . '/' . $filename);

            if (!file_exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Backup not found')
                ], 404);
            }

            // Obtener configuración de la base de datos
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port', 3306);
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');

            // Ejecutar mysql para restaurar
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s --password=%s %s < %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('Error restoring backup'),
                    'error' => implode("\n", $output)
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => __('Backup restored successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Error restoring backup'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatear bytes a unidad legible
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
