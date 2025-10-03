<?php

namespace Database\Seeders;

use App\Models\Asset;
use Illuminate\Database\Seeder;

class SimpleAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear algunos activos básicos de ejemplo
        $assets = [
            [
                'customer_id' => 1,
                'asset_category_id' => 1, // Asumiendo que existe la categoría con ID 1
                'asset_cost_center_id' => 1, // Asumiendo que existe el centro de costo con ID 1
                'asset_location_id' => 1, // Asumiendo que existe la ubicación con ID 1
                'article_code' => 'TEST-001',
                'label_code' => 'TEST-LBL-001',
                'description' => 'Activo de prueba básico',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subMonths(1),
                'attributes' => [
                    'campo_personalizado_1' => 'Valor de prueba 1',
                    'campo_personalizado_2' => 'Valor de prueba 2',
                    'categoria' => 'Electrónica',
                    'estado' => 'Nuevo'
                ],
                'metadata' => [
                    'precio' => 100.50,
                    'proveedor' => 'Proveedor de prueba',
                    'fecha_compra' => now()->subMonths(1)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => 1,
                'asset_cost_center_id' => 1,
                'asset_location_id' => 1,
                'article_code' => 'TEST-002',
                'label_code' => 'TEST-LBL-002',
                'description' => 'Segundo activo de prueba',
                'status' => 'inactive',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_TEST_001',
                'rfid_epc' => 'EPC_TEST_001',
                'acquired_at' => now()->subMonths(2),
                'attributes' => [
                    'tipo' => 'Mecánico',
                    'peso' => '15kg',
                    'dimensiones' => '50x30x20cm',
                    'material' => 'Acero inoxidable'
                ],
                'metadata' => [
                    'mantenimiento_requerido' => true,
                    'ultimo_mantenimiento' => now()->subMonths(1)->format('Y-m-d'),
                    'proximo_mantenimiento' => now()->addMonths(2)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => 1,
                'asset_cost_center_id' => 1,
                'asset_location_id' => 1,
                'article_code' => 'TEST-003',
                'label_code' => 'TEST-LBL-003',
                'description' => 'Tercer activo de prueba con RFID',
                'status' => 'maintenance',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_TEST_002',
                'rfid_epc' => 'EPC_TEST_002',
                'acquired_at' => now()->subMonths(6),
                'attributes' => [
                    'estado_actual' => 'En mantenimiento',
                    'tecnico_responsable' => 'Juan Pérez',
                    'problema_reportado' => 'No enciende correctamente',
                    'fecha_inicio_mantenimiento' => now()->subDays(5)->format('Y-m-d')
                ],
                'metadata' => [
                    'prioridad' => 'Alta',
                    'tiempo_estimado_reparacion' => '3 días',
                    'costo_estimado' => 150.00
                ]
            ]
        ];

        foreach ($assets as $assetData) {
            Asset::create($assetData);
        }

        $this->command->info('✓ Simple Asset seeder ejecutado correctamente');
        $this->command->info('✓ Creados ' . count($assets) . ' activos básicos de prueba');
        $this->command->info('✓ Incluyen diferentes estados: active, inactive, maintenance');
        $this->command->info('✓ Algunos tienen RFID, otros no');
        $this->command->info('✓ Campos personalizados en attributes y metadata');
    }
}
