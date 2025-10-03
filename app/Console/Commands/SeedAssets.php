<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:assets {type? : Tipo de seeder (simple, full, inventario, custom-fields)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecutar seeders para crear activos de prueba';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'simple';

        switch ($type) {
            case 'simple':
                $this->call('db:seed', ['--class' => 'SimpleAssetSeeder']);
                $this->info('✓ Seeder simple ejecutado: Activos básicos creados');
                break;

            case 'full':
                $this->call('db:seed', ['--class' => 'AssetSeeder']);
                $this->info('✓ Seeder completo ejecutado: Categorías, centros de costo, ubicaciones y activos creados');
                break;

            case 'inventario':
                $this->call('db:seed', ['--class' => 'InventarioSeeder']);
                $this->info('✓ Seeder de inventario ejecutado: Activos de inventario detallados creados');
                break;

            default:
                $this->error('Tipo de seeder no válido. Usa: simple, full, inventario, o custom-fields');
                return 1;
        }

        $this->info('Para ver los activos creados, ve a: /customers/{id}/assets');
        $this->info('También puedes ver el inventario en: /customers/{id}/assets/inventory');

        return 0;
    }
}
