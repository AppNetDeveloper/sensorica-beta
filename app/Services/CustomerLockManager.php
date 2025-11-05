<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CustomerLockManager
{
    private string $locksDirectory;

    public function __construct()
    {
        $this->locksDirectory = storage_path('app/locks/customers');
        $this->ensureLocksDirectory();
    }

    private function ensureLocksDirectory(): void
    {
        if (!File::exists($this->locksDirectory)) {
            File::makeDirectory($this->locksDirectory, 0755, true);
        }
    }

    private function getLockFilePath(Customer $customer): string
    {
        return $this->locksDirectory . "/customer_{$customer->id}_orders_check.lock";
    }

    /**
     * Calcula el timeout real con tolerancia para evitar sincronización
     */
    private function calculateTimeoutWithTolerance(Customer $customer): int
    {
        $baseTimeout = $customer->lock_timeout; // minutos
        $tolerance = $customer->lock_timeout_tolerance ?? 0.10; // 10% por defecto

        // Aplicar tolerancia aleatoria para evitar sincronización
        // Reducir el tiempo en lugar de aumentarlo para evitar bloqueos demasiado largos
        $randomTolerance = (mt_rand() / mt_getrandmax()) * $tolerance;
        $timeoutWithMargin = $baseTimeout * (1 - $randomTolerance);

        return (int)ceil($timeoutWithMargin);
    }

    /**
     * Adquiere un bloqueo para el cliente específico
     */
    public function acquireLock(Customer $customer): bool
    {
        $lockFile = $this->getLockFilePath($customer);

        if (File::exists($lockFile)) {
            $lockTime = (int)File::get($lockFile);
            $lockAgeMinutes = (time() - $lockTime) / 60;

            // Calcular timeout con tolerancia para este cliente
            $timeoutMinutes = $this->calculateTimeoutWithTolerance($customer);

            Log::info("Verificando bloqueo para cliente {$customer->name}", [
                'customer_id' => $customer->id,
                'lock_age_minutes' => round($lockAgeMinutes, 2),
                'timeout_minutes' => $timeoutMinutes,
                'lock_file' => $lockFile
            ]);

            if ($lockAgeMinutes < $timeoutMinutes) {
                Log::info("Bloqueo activo para cliente {$customer->name}", [
                    'age' => round($lockAgeMinutes, 2) . ' minutos',
                    'timeout' => $timeoutMinutes . ' minutos'
                ]);
                return false; // Bloqueo activo
            }

            // Eliminar bloqueo antiguo
            Log::info("Eliminando bloqueo antiguo para cliente {$customer->name}", [
                'age' => round($lockAgeMinutes, 2) . ' minutos',
                'timeout' => $timeoutMinutes . ' minutos'
            ]);

            if (!File::delete($lockFile)) {
                Log::error("No se pudo eliminar el archivo de bloqueo antiguo", [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'lock_file' => $lockFile
                ]);
                return false;
            }
        }

        // Crear nuevo bloqueo
        $result = File::put($lockFile, time()) !== false;

        if ($result) {
            Log::info("Bloqueo adquirido para cliente {$customer->name}", [
                'customer_id' => $customer->id,
                'lock_file' => $lockFile,
                'timeout_minutes' => $this->calculateTimeoutWithTolerance($customer)
            ]);
        }

        return $result;
    }

    /**
     * Libera el bloqueo del cliente
     */
    public function releaseLock(Customer $customer): bool
    {
        $lockFile = $this->getLockFilePath($customer);
        $result = File::delete($lockFile);

        if ($result) {
            Log::info("Bloqueo liberado para cliente {$customer->name}", [
                'customer_id' => $customer->id,
                'lock_file' => $lockFile
            ]);
        }

        return $result;
    }

    /**
     * Verifica si un cliente está bloqueado
     */
    public function isLocked(Customer $customer): bool
    {
        $lockInfo = $this->getLockInfo($customer);

        if (!$lockInfo) {
            return false;
        }

        $timeoutMinutes = $this->calculateTimeoutWithTolerance($customer);
        return $lockInfo['age_minutes'] < $timeoutMinutes;
    }

    /**
     * Obtiene información del bloqueo de un cliente
     */
    public function getLockInfo(Customer $customer): ?array
    {
        $lockFile = $this->getLockFilePath($customer);

        if (!File::exists($lockFile)) {
            return null;
        }

        $lockTime = (int)File::get($lockFile);
        $ageMinutes = (time() - $lockTime) / 60;
        $timeoutMinutes = $this->calculateTimeoutWithTolerance($customer);

        return [
            'file_path' => $lockFile,
            'lock_time' => $lockTime,
            'age_minutes' => round($ageMinutes, 2),
            'timeout_minutes' => $timeoutMinutes,
            'created_at' => date('Y-m-d H:i:s', $lockTime),
            'is_active' => $ageMinutes < $timeoutMinutes,
            'tolerance_used' => round($timeoutMinutes - $customer->lock_timeout, 2) // Será negativo ahora
        ];
    }

    /**
     * Limpia bloqueos expirados para todos los clientes
     */
    public function cleanupExpiredLocks(): int
    {
        $cleaned = 0;

        if (!File::exists($this->locksDirectory)) {
            return $cleaned;
        }

        $files = File::glob($this->locksDirectory . '/*.lock');

        foreach ($files as $file) {
            if (!preg_match('/customer_(\d+)_orders_check\.lock$/', $file, $matches)) {
                continue;
            }

            $customerId = $matches[1];
            $customer = Customer::find($customerId);

            if (!$customer) {
                // Si el cliente no existe, eliminar el archivo
                File::delete($file);
                $cleaned++;
                Log::info("Eliminando bloqueo de cliente inexistente", [
                    'customer_id' => $customerId,
                    'lock_file' => $file
                ]);
                continue;
            }

            $lockInfo = $this->getLockInfo($customer);

            if ($lockInfo && !$lockInfo['is_active']) {
                File::delete($file);
                $cleaned++;
                Log::info("Eliminando bloqueo expirado", [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'age' => $lockInfo['age_minutes'] . ' minutos',
                    'timeout' => $lockInfo['timeout_minutes'] . ' minutos'
                ]);
            }
        }

        return $cleaned;
    }

    /**
     * Obtiene estado de todos los bloqueos activos
     */
    public function getAllLocksStatus(): array
    {
        $status = [];

        if (!File::exists($this->locksDirectory)) {
            return $status;
        }

        $files = File::glob($this->locksDirectory . '/*.lock');

        foreach ($files as $file) {
            if (!preg_match('/customer_(\d+)_orders_check\.lock$/', $file, $matches)) {
                continue;
            }

            $customerId = $matches[1];
            $customer = Customer::find($customerId);

            if ($customer) {
                $lockInfo = $this->getLockInfo($customer);
                if ($lockInfo) {
                    $status[] = [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'lock_info' => $lockInfo
                    ];
                }
            }
        }

        return $status;
    }

    /**
     * Fuerza la liberación de un bloqueo específico
     */
    public function forceReleaseLock(Customer $customer): bool
    {
        Log::warning("Forzando liberación de bloqueo para cliente {$customer->name}", [
            'customer_id' => $customer->id
        ]);

        return $this->releaseLock($customer);
    }
}