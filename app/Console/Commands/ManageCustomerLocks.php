<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CustomerLockManager;
use Illuminate\Console\Command;

class ManageCustomerLocks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locks:manage {action}
                            {--customer-id= : ID específico del cliente}
                            {--force : Forzar la acción sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestionar bloqueos de clientes (list, cleanup, release, status)';

    private CustomerLockManager $lockManager;

    public function __construct(CustomerLockManager $lockManager)
    {
        parent::__construct();
        $this->lockManager = $lockManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listLocks();
            case 'cleanup':
                return $this->cleanupLocks();
            case 'release':
                return $this->releaseLock();
            case 'status':
                return $this->showStatus();
            case 'reset':
                return $this->resetAllLocks();
            default:
                $this->error("❌ Acción desconocida: {$action}");
                $this->info('📋 Acciones disponibles:');
                $this->line('  list     - Listar todos los bloqueos activos');
                $this->line('  status   - Mostrar estado detallado de bloqueos');
                $this->line('  cleanup  - Limpiar bloqueos expirados');
                $this->line('  release  - Liberar bloqueo de un cliente específico');
                $this->line('  reset    - Eliminar todos los bloqueos (requiere --force)');
                return 1;
        }
    }

    private function listLocks(): int
    {
        $this->info('🔒 === Bloqueos Activos por Cliente ===');
        $this->newLine();

        $locksStatus = $this->lockManager->getAllLocksStatus();

        if (empty($locksStatus)) {
            $this->info('✅ No hay bloqueos activos.');
            return 0;
        }

        foreach ($locksStatus as $lockInfo) {
            $customer = $lockInfo['customer'];
            $info = $lockInfo['lock_info'];

            $status = $info['is_active'] ? '🔒 Activo' : '⚠️ Expirado';
            $ageInfo = "{$info['age_minutes']} min / {$info['timeout_minutes']} min";

            $this->line("📋 Cliente: {$customer['name']} (ID: {$customer['customer_id']})");
            $this->line("   Estado: {$status}");
            $this->line("   Edad: {$ageInfo}");

            if ($info['tolerance_used'] > 0) {
                $this->line("   🎲 Tolerancia: +{$info['tolerance_used']} min");
            }

            $this->line("   Creado: {$info['created_at']}");
            $this->line("   Archivo: {$info['file_path']}");
            $this->newLine();
        }

        $this->info("📊 Total de bloqueos: " . count($locksStatus));
        return 0;
    }

    private function showStatus(): int
    {
        $this->info('📊 === Estado Detallado de Sistema de Bloqueos ===');
        $this->newLine();

        // Obtener todos los clientes
        $customers = Customer::all();
        $totalCustomers = $customers->count();
        $activeLocks = 0;
        $expiredLocks = 0;
        $noLocks = 0;

        $locksStatus = $this->lockManager->getAllLocksStatus();
        $lockedCustomerIds = array_column($locksStatus, 'customer_id');

        $this->table(
            ['Cliente', 'ID', 'Estado del Bloqueo', 'Configuración'],
            $customers->map(function ($customer) use ($locksStatus, &$activeLocks, &$expiredLocks, &$noLocks) {
                $lockInfo = collect($locksStatus)->firstWhere('customer_id', $customer->id);

                if (!$lockInfo) {
                    $noLocks++;
                    return [
                        'name' => $customer->name,
                        'id' => $customer->id,
                        'status' => '✅ Sin bloqueo',
                        'config' => $customer->getLockTimeout() . "min / " . ($customer->getLockTimeoutTolerance() * 100) . "%"
                    ];
                }

                $info = $lockInfo['lock_info'];
                if ($info['is_active']) {
                    $activeLocks++;
                    $status = "🔒 Activo ({$info['age_minutes']}min)";
                } else {
                    $expiredLocks++;
                    $status = "⚠️ Expirado ({$info['age_minutes']}min)";
                }

                return [
                    'name' => $customer->name,
                    'id' => $customer->id,
                    'status' => $status,
                    'config' => $customer->getLockTimeout() . "min / " . ($customer->getLockTimeoutTolerance() * 100) . "%"
                ];
            })
        );

        $this->newLine();
        $this->info('📈 Resumen:');
        $this->line("   Total clientes: {$totalCustomers}");
        $this->line("   Bloqueos activos: {$activeLocks}");
        $this->line("   Bloqueos expirados: {$expiredLocks}");
        $this->line("   Sin bloqueo: {$noLocks}");

        return 0;
    }

    private function cleanupLocks(): int
    {
        $this->info('🧹 Limpiando bloqueos expirados...');

        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que quieres limpiar los bloqueos expirados?')) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        $cleaned = $this->lockManager->cleanupExpiredLocks();

        if ($cleaned > 0) {
            $this->info("✅ Se eliminaron {$cleaned} bloqueos expirados.");
        } else {
            $this->info('ℹ️ No se encontraron bloqueos expirados.');
        }

        return 0;
    }

    private function releaseLock(): int
    {
        $customerId = $this->option('customer-id');

        if (!$customerId) {
            $this->error('❌ Debes especificar --customer-id para liberar un bloqueo específico.');
            $this->info('💡 Ejemplo: php artisan locks:manage release --customer-id=123');
            return 1;
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            $this->error("❌ Cliente con ID {$customerId} no encontrado.");
            return 1;
        }

        $lockInfo = $this->lockManager->getLockInfo($customer);

        if (!$lockInfo) {
            $this->info("ℹ️ No existe bloqueo para el cliente {$customer->name} (ID: {$customerId})");
            return 0;
        }

        $this->info("📋 Información del bloqueo:");
        $this->line("   Cliente: {$customer->name} (ID: {$customerId})");
        $this->line("   Edad: {$lockInfo['age_minutes']} minutos");
        $this->line("   Activo: " . ($lockInfo['is_active'] ? 'Sí' : 'No'));
        $this->line("   Archivo: {$lockInfo['file_path']}");

        if (!$this->option('force')) {
            if (!$this->confirm("¿Estás seguro de que quieres liberar este bloqueo?")) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        if ($this->lockManager->releaseLock($customer)) {
            $this->info("✅ Bloqueo liberado para el cliente {$customer->name}");
        } else {
            $this->error("❌ No se pudo liberar el bloqueo para el cliente {$customer->name}");
            return 1;
        }

        return 0;
    }

    private function resetAllLocks(): int
    {
        $this->warn('⚠️ Esta operación eliminará TODOS los bloqueos de clientes.');

        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar TODOS los bloqueos? Esta acción es irreversible.', false)) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }

            if (!$this->confirm('Por favor confirma nuevamente: ¿Eliminar todos los bloqueos?', false)) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        $locksStatus = $this->lockManager->getAllLocksStatus();
        $totalLocks = count($locksStatus);

        if ($totalLocks === 0) {
            $this->info('ℹ️ No hay bloqueos para eliminar.');
            return 0;
        }

        $this->info("🔄 Eliminando {$totalLocks} bloqueos...");

        $successCount = 0;
        foreach ($locksStatus as $lockInfo) {
            $customer = Customer::find($lockInfo['customer_id']);
            if ($customer && $this->lockManager->releaseLock($customer)) {
                $successCount++;
                $this->line("   ✅ Bloqueo eliminado: {$customer->name}");
            }
        }

        $this->info("✅ Se eliminaron {$successCount} de {$totalLocks} bloqueos.");
        return 0;
    }
}
