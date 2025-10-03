<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetCostCenter;
use App\Models\AssetLocation;
use App\Models\Customer;
use Illuminate\Database\Seeder;

use Illuminate\Support\Str;

class AssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías de activos si no existen
        $categories = [
            [
                'customer_id' => 1,
                'name' => 'Herramientas',
                'slug' => Str::slug('Herramientas'),
                'label_code' => 'CAT-HERR-001',
                'description' => 'Herramientas manuales y eléctricas',
                'metadata' => [
                    'color' => '#FF6B6B',
                    'tipo' => 'herramienta'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Maquinaria',
                'slug' => Str::slug('Maquinaria'),
                'label_code' => 'CAT-MAQUI-001',
                'description' => 'Maquinaria pesada y equipos industriales',
                'metadata' => [
                    'color' => '#4ECDC4',
                    'tipo' => 'maquinaria'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Mobiliario',
                'slug' => Str::slug('Mobiliario'),
                'label_code' => 'CAT-MOBIL-001',
                'description' => 'Muebles y equipamiento de oficina',
                'metadata' => [
                    'color' => '#45B7D1',
                    'tipo' => 'mobiliario'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Electrónica',
                'slug' => Str::slug('Electrónica'),
                'label_code' => 'CAT-ELECT-001',
                'description' => 'Equipos electrónicos y de computación',
                'metadata' => [
                    'color' => '#96CEB4',
                    'tipo' => 'electronica'
                ]
            ],
        ];

        foreach ($categories as $categoryData) {
            AssetCategory::firstOrCreate(
                ['name' => $categoryData['name'], 'customer_id' => $categoryData['customer_id']],
                $categoryData
            );
        }

        // Crear centros de costo si no existen
        $costCenters = [
            [
                'customer_id' => 1,
                'code' => 'PROD-001',
                'name' => 'Producción',
                'description' => 'Centro de costo para producción',
                'metadata' => [
                    'tipo' => 'produccion',
                    'prioridad' => 'alta'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'MANT-001',
                'name' => 'Mantenimiento',
                'description' => 'Centro de costo para mantenimiento',
                'metadata' => [
                    'tipo' => 'mantenimiento',
                    'prioridad' => 'media'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'OFIC-001',
                'name' => 'Oficina',
                'description' => 'Centro de costo administrativo',
                'metadata' => [
                    'tipo' => 'oficina',
                    'prioridad' => 'baja'
                ]
            ],
        ];

        foreach ($costCenters as $costCenterData) {
            AssetCostCenter::firstOrCreate(
                ['name' => $costCenterData['name'], 'customer_id' => $costCenterData['customer_id']],
                $costCenterData
            );
        }

        // Crear ubicaciones si no existen
        $locations = [
            [
                'customer_id' => 1,
                'code' => 'ALM-PRINC',
                'name' => 'Almacén Principal',
                'description' => 'Almacén principal de la empresa',
                'metadata' => [
                    'tipo' => 'almacen_principal',
                    'seguridad' => 'alta',
                    'capacidad' => 1000
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'TALLER-MEC',
                'name' => 'Taller Mecánico',
                'description' => 'Taller de mantenimiento y reparaciones',
                'metadata' => [
                    'tipo' => 'taller',
                    'seguridad' => 'media',
                    'capacidad' => 200
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'OFIC-CENTRAL',
                'name' => 'Oficina Central',
                'description' => 'Ubicación de oficinas administrativas',
                'metadata' => [
                    'tipo' => 'oficina',
                    'seguridad' => 'estandar',
                    'capacidad' => 50
                ]
            ],
        ];

        foreach ($locations as $locationData) {
            AssetLocation::firstOrCreate(
                ['name' => $locationData['name'], 'customer_id' => $locationData['customer_id']],
                $locationData
            );
        }

        // Crear activos de ejemplo
        $assets = [
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Herramientas')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Producción')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Principal')->where('customer_id', 1)->first()->id,
                'article_code' => 'TAL-001',
                'label_code' => 'LBL-001',
                'description' => 'Taladro eléctrico profesional Bosch',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID123456789',
                'rfid_epc' => 'EPC987654321',
                'acquired_at' => now()->subMonths(6),
                'attributes' => [
                    'marca' => 'Bosch',
                    'modelo' => 'GBH 2-26',
                    'potencia' => '800W',
                    'voltaje' => '220V',
                    'peso' => '2.7kg',
                    'accesorios' => ['maletín', 'juego de brocas', 'manual']
                ],
                'metadata' => [
                    'proveedor' => 'Ferretería Central',
                    'precio_compra' => 185.50,
                    'garantia_meses' => 24,
                    'fecha_ultimo_mantenimiento' => now()->subMonths(2)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Maquinaria')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Producción')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Taller Mecánico')->where('customer_id', 1)->first()->id,
                'article_code' => 'TOR-001',
                'label_code' => 'LBL-002',
                'description' => 'Torno CNC industrial',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID987654321',
                'rfid_epc' => 'EPC123456789',
                'acquired_at' => now()->subYears(2),
                'attributes' => [
                    'marca' => 'Haas',
                    'modelo' => 'ST-10',
                    'capacidad' => '254mm chuck',
                    'potencia' => '7.5kW',
                    'dimensiones' => '2.1m x 1.5m x 1.8m',
                    'peso' => '1800kg',
                    'control' => 'CNC Siemens'
                ],
                'metadata' => [
                    'proveedor' => 'Maquinaria Industrial S.A.',
                    'precio_compra' => 45000.00,
                    'garantia_meses' => 36,
                    'fecha_ultimo_mantenimiento' => now()->subWeeks(3)->format('Y-m-d'),
                    'horas_uso_total' => 2847
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Electrónica')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Oficina')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Oficina Central')->where('customer_id', 1)->first()->id,
                'article_code' => 'LAP-001',
                'label_code' => 'LBL-003',
                'description' => 'Laptop Dell Latitude para oficina',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subMonths(8),
                'attributes' => [
                    'marca' => 'Dell',
                    'modelo' => 'Latitude 7420',
                    'procesador' => 'Intel i7-1165G7',
                    'ram' => '16GB',
                    'almacenamiento' => '512GB SSD',
                    'pantalla' => '14" Full HD',
                    'sistema_operativo' => 'Windows 11 Pro'
                ],
                'metadata' => [
                    'usuario_asignado' => 'María González',
                    'departamento' => 'Administración',
                    'precio_compra' => 1200.00,
                    'garantia_meses' => 36,
                    'fecha_proximo_mantenimiento' => now()->addMonths(4)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Mobiliario')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Oficina')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Oficina Central')->where('customer_id', 1)->first()->id,
                'article_code' => 'SILL-001',
                'label_code' => 'LBL-004',
                'description' => 'Silla ergonómica para oficina',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subMonths(3),
                'attributes' => [
                    'marca' => 'Steelcase',
                    'modelo' => 'Series 1',
                    'tipo' => 'Ergonómica',
                    'material' => 'Malla y aluminio',
                    'color' => 'Negro',
                    'capacidad_peso' => '150kg',
                    'ajuste_altura' => 'Sí',
                    'respaldo_ajustable' => 'Sí'
                ],
                'metadata' => [
                    'usuario_asignado' => 'Carlos Ruiz',
                    'departamento' => 'Contabilidad',
                    'precio_compra' => 285.00,
                    'garantia_meses' => 60,
                    'fecha_compra' => now()->subMonths(3)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Herramientas')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Mantenimiento')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Taller Mecánico')->where('customer_id', 1)->first()->id,
                'article_code' => 'MULT-001',
                'label_code' => 'LBL-005',
                'description' => 'Multímetro digital profesional',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID555666777',
                'rfid_epc' => 'EPC888999000',
                'acquired_at' => now()->subMonths(12),
                'attributes' => [
                    'marca' => 'Fluke',
                    'modelo' => '87V',
                    'tipo' => 'Multímetro digital TRMS',
                    'categoria' => 'CAT III 1000V, CAT IV 600V',
                    'funciones' => ['voltaje', 'corriente', 'resistencia', 'capacidad', 'frecuencia', 'temperatura'],
                    'precision' => '±0.05%',
                    'pantalla' => 'LCD retroiluminada 6000 cuentas'
                ],
                'metadata' => [
                    'proveedor' => 'Instrumentos Eléctricos S.L.',
                    'precio_compra' => 425.00,
                    'garantia_meses' => 36,
                    'fecha_ultima_calibracion' => now()->subMonths(6)->format('Y-m-d'),
                    'proxima_calibracion' => now()->addMonths(6)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Electrónica')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Producción')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Principal')->where('customer_id', 1)->first()->id,
                'article_code' => 'IMP-001',
                'label_code' => 'LBL-006',
                'description' => 'Impresora 3D industrial',
                'status' => 'maintenance',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID111222333',
                'rfid_epc' => 'EPC444555666',
                'acquired_at' => now()->subYears(1),
                'attributes' => [
                    'marca' => 'Ultimaker',
                    'modelo' => 'S5',
                    'tecnologia' => 'FDM',
                    'volumen_impresion' => '330 x 240 x 300 mm',
                    'materiales' => ['PLA', 'ABS', 'PETG', 'TPU', 'Nylon'],
                    'conectividad' => 'USB, Ethernet, WiFi',
                    'software' => 'Ultimaker Cura'
                ],
                'metadata' => [
                    'proveedor' => '3D Solutions',
                    'precio_compra' => 8500.00,
                    'garantia_meses' => 24,
                    'fecha_ultimo_mantenimiento' => now()->subDays(15)->format('Y-m-d'),
                    'estado_mantenimiento' => 'En revisión técnica',
                    'horas_uso_total' => 1247,
                    'tecnico_responsable' => 'Antonio López'
                ]
            ]
        ];

        foreach ($assets as $assetData) {
            Asset::create($assetData);
        }

        $this->command->info('✓ Asset seeder ejecutado correctamente');
        $this->command->info('✓ Creadas categorías: ' . count($categories));
        $this->command->info('✓ Creados centros de costo: ' . count($costCenters));
        $this->command->info('✓ Creadas ubicaciones: ' . count($locations));
        $this->command->info('✓ Creados activos: ' . count($assets));
    }
}
