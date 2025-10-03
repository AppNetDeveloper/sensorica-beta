<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedProcurement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:procurement {type=full : Tipo de seeding (full, vendors, assets, inventory)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed procurement and vendor data (vendors, assets, inventory)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');

        $this->info("🚀 Iniciando seeding de procurement: {$type}");
        $this->newLine();

        switch ($type) {
            case 'full':
                $this->seedFull();
                break;
            case 'vendors':
                $this->seedVendors();
                break;
            case 'assets':
                $this->seedAssets();
                break;
            case 'inventory':
                $this->seedInventory();
                break;
            default:
                $this->error("Tipo no válido. Usa: full, vendors, assets, inventory");
                return 1;
        }

        $this->newLine();
        $this->info("✅ Seeding de procurement completado exitosamente!");
        return 0;
    }

    private function seedFull()
    {
        $this->info("📦 Ejecutando seeding completo...");
        
        $this->call('db:seed', ['--class' => 'VendorProcurementSeeder']);
        $this->call('db:seed', ['--class' => 'AssetSeeder']);
        $this->call('db:seed', ['--class' => 'InventarioSeeder']);
        
        $this->info("✓ Seeding completo finalizado");
    }

    private function seedVendors()
    {
        $this->info("🏢 Ejecutando seeding de proveedores...");
        $this->call('db:seed', ['--class' => 'VendorProcurementSeeder']);
        $this->info("✓ Proveedores y compras creados");
    }

    private function seedAssets()
    {
        $this->info("🔧 Ejecutando seeding de activos...");
        $this->call('db:seed', ['--class' => 'AssetSeeder']);
        $this->info("✓ Activos básicos creados");
    }

    private function seedInventory()
    {
        $this->info("📋 Ejecutando seeding de inventario...");
        $this->call('db:seed', ['--class' => 'InventarioSeeder']);
        $this->info("✓ Inventario detallado creado");
    }
}
