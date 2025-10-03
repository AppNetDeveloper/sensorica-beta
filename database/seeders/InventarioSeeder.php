<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetCostCenter;
use App\Models\AssetLocation;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class InventarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear categorías adicionales específicas de inventario
        $inventoryCategories = [
            [
                'customer_id' => 1,
                'name' => 'Materias Primas',
                'description' => 'Materiales básicos para producción',
                'metadata' => [
                    'color' => '#E74C3C',
                    'tipo' => 'materia_prima'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Productos Semielaborados',
                'description' => 'Productos en proceso de fabricación',
                'metadata' => [
                    'color' => '#F39C12',
                    'tipo' => 'semielaborado'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Productos Terminados',
                'description' => 'Productos listos para venta',
                'metadata' => [
                    'color' => '#27AE60',
                    'tipo' => 'terminado'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Envases y Embalajes',
                'description' => 'Materiales para empaquetado',
                'metadata' => [
                    'color' => '#8E44AD',
                    'tipo' => 'envase'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Repuestos y Consumibles',
                'description' => 'Piezas de recambio y materiales consumibles',
                'metadata' => [
                    'color' => '#3498DB',
                    'tipo' => 'repuesto'
                ]
            ],
        ];

        foreach ($inventoryCategories as $categoryData) {
            AssetCategory::firstOrCreate(
                ['name' => $categoryData['name'], 'customer_id' => $categoryData['customer_id']],
                $categoryData
            );
        }

        // Crear centros de costo específicos para inventario
        $inventoryCostCenters = [
            [
                'customer_id' => 1,
                'code' => 'INV-MP-001',
                'name' => 'Inventario Materias Primas',
                'description' => 'Control de stock de materias primas',
                'metadata' => [
                    'tipo' => 'materia_prima',
                    'prioridad' => 'alta'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'INV-PT-001',
                'name' => 'Inventario Productos Terminados',
                'description' => 'Control de productos listos para venta',
                'metadata' => [
                    'tipo' => 'producto_terminado',
                    'prioridad' => 'media'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'INV-CONS-001',
                'name' => 'Inventario Consumibles',
                'description' => 'Control de materiales consumibles',
                'metadata' => [
                    'tipo' => 'consumible',
                    'prioridad' => 'media'
                ]
            ],
        ];

        foreach ($inventoryCostCenters as $costCenterData) {
            AssetCostCenter::firstOrCreate(
                ['name' => $costCenterData['name'], 'customer_id' => $costCenterData['customer_id']],
                $costCenterData
            );
        }

        // Crear ubicaciones específicas para inventario
        $inventoryLocations = [
            [
                'customer_id' => 1,
                'code' => 'ALM-MP-A',
                'name' => 'Almacén Materias Primas A',
                'description' => 'Sección A - Materias primas',
                'metadata' => [
                    'tipo' => 'materia_prima',
                    'zona' => 'A',
                    'seguridad' => 'estandar'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'ALM-MP-B',
                'name' => 'Almacén Materias Primas B',
                'description' => 'Sección B - Materias primas peligrosas',
                'metadata' => [
                    'tipo' => 'materia_prima_peligrosa',
                    'zona' => 'B',
                    'seguridad' => 'alta'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'ALM-PT',
                'name' => 'Almacén Productos Terminados',
                'description' => 'Almacén de productos listos para expedición',
                'metadata' => [
                    'tipo' => 'producto_terminado',
                    'zona' => 'C',
                    'seguridad' => 'media'
                ]
            ],
            [
                'customer_id' => 1,
                'code' => 'ALM-CONS',
                'name' => 'Almacén Consumibles',
                'description' => 'Almacén de repuestos y consumibles',
                'metadata' => [
                    'tipo' => 'consumible',
                    'zona' => 'D',
                    'seguridad' => 'estandar'
                ]
            ],
        ];

        foreach ($inventoryLocations as $locationData) {
            AssetLocation::firstOrCreate(
                ['name' => $locationData['name'], 'customer_id' => $locationData['customer_id']],
                $locationData
            );
        }

        // Crear activos de inventario detallados
        $inventoryAssets = [
            // MATERIAS PRIMAS
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Materias Primas')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Materias Primas')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Materias Primas A')->where('customer_id', 1)->first()->id,
                'article_code' => 'MP-ALU-001',
                'label_code' => 'LBL-MP-001',
                'description' => 'Aluminio en lingotes 99.7% pureza',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_MP_001',
                'rfid_epc' => 'EPC_MP_001',
                'acquired_at' => now()->subDays(15),
                'attributes' => [
                    'tipo_material' => 'Aluminio',
                    'pureza' => '99.7%',
                    'forma' => 'Lingotes',
                    'peso_unitario' => '25kg',
                    'unidades_por_paquete' => 20,
                    'peso_total_paquete' => '500kg',
                    'normativa' => 'UNE-EN 573-3',
                    'lote_produccion' => 'ALU-2024-07-001',
                    'fecha_caducidad' => now()->addYears(2)->format('Y-m-d')
                ],
                'metadata' => [
                    'proveedor' => 'Aluminios del Mediterráneo S.A.',
                    'precio_unitario' => 2.85,
                    'precio_total' => 14250.00,
                    'cantidad_disponible' => 5000,
                    'cantidad_minima' => 1000,
                    'cantidad_maxima' => 10000,
                    'fecha_ultima_compra' => now()->subDays(15)->format('Y-m-d'),
                    'proxima_compra_estimada' => now()->addDays(45)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Materias Primas')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Materias Primas')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Materias Primas A')->where('customer_id', 1)->first()->id,
                'article_code' => 'MP-PVC-001',
                'label_code' => 'LBL-MP-002',
                'description' => 'PVC granulado blanco RAL 9010',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_MP_002',
                'rfid_epc' => 'EPC_MP_002',
                'acquired_at' => now()->subDays(10),
                'attributes' => [
                    'tipo_material' => 'PVC',
                    'color' => 'Blanco RAL 9010',
                    'forma' => 'Granulado',
                    'densidad' => '1.38 g/cm³',
                    'indice_fluidez' => '1.2 g/10min',
                    'bolsas_por_palet' => 40,
                    'peso_bolsa' => '25kg',
                    'peso_palet' => '1000kg',
                    'normativa' => 'UNE-EN 15343',
                    'lote_produccion' => 'PVC-2024-07-045'
                ],
                'metadata' => [
                    'proveedor' => 'Plásticos Valencianos S.L.',
                    'precio_unitario' => 1.65,
                    'precio_total' => 8250.00,
                    'cantidad_disponible' => 5000,
                    'cantidad_minima' => 2000,
                    'cantidad_maxima' => 8000,
                    'fecha_ultima_compra' => now()->subDays(10)->format('Y-m-d'),
                    'proxima_compra_estimada' => now()->addDays(30)->format('Y-m-d')
                ]
            ],
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Materias Primas')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Materias Primas')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Materias Primas B')->where('customer_id', 1)->first()->id,
                'article_code' => 'MP-ACE-001',
                'label_code' => 'LBL-MP-003',
                'description' => 'Aceite lubricante industrial ISO VG 68',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subDays(20),
                'attributes' => [
                    'tipo_producto' => 'Aceite lubricante',
                    'grado_viscosidad' => 'ISO VG 68',
                    'envase' => 'Bidón',
                    'capacidad' => '20 litros',
                    'unidades_por_caja' => 4,
                    'peso_unitario' => '18.5kg',
                    'clasificacion_peligroso' => 'No peligroso',
                    'lote_fabricacion' => 'ACE-2024-06-128',
                    'fecha_caducidad' => now()->addYears(3)->format('Y-m-d')
                ],
                'metadata' => [
                    'proveedor' => 'Lubricantes Industriales S.A.',
                    'precio_unitario' => 85.00,
                    'precio_total' => 3400.00,
                    'cantidad_disponible' => 40,
                    'cantidad_minima' => 10,
                    'cantidad_maxima' => 60,
                    'fecha_ultima_compra' => now()->subDays(20)->format('Y-m-d'),
                    'proxima_compra_estimada' => now()->addDays(60)->format('Y-m-d')
                ]
            ],

            // PRODUCTOS SEMIELABORADOS
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Productos Semielaborados')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Productos Terminados')->where('customer_id', 1)->first()->id,
                'article_code' => 'SEMI-PER-001',
                'label_code' => 'LBL-SEMI-001',
                'description' => 'Perfil de aluminio extrusionado 40x40mm',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_SEMI_001',
                'rfid_epc' => 'EPC_SEMI_001',
                'acquired_at' => now()->subDays(5),
                'attributes' => [
                    'tipo_producto' => 'Perfil extrusionado',
                    'material' => 'Aluminio 6063',
                    'dimensiones' => '40x40mm',
                    'longitud' => '6 metros',
                    'espesor_pared' => '2mm',
                    'peso_unitario' => '3.2kg',
                    'unidades_por_paquete' => 10,
                    'peso_total_paquete' => '32kg',
                    'acabado_superficie' => 'Anodizado natural',
                    'lote_produccion' => 'PER-2024-07-089',
                    'estado_produccion' => 'Corte y taladrado completado'
                ],
                'metadata' => [
                    'proceso_fabricacion' => 'Extrusión + Corte + Taladrado',
                    'tiempo_produccion' => '45 minutos/unidad',
                    'costo_produccion' => 12.50,
                    'cantidad_disponible' => 150,
                    'cantidad_minima' => 50,
                    'cantidad_maxima' => 300,
                    'fecha_produccion' => now()->subDays(5)->format('Y-m-d'),
                    'fecha_disponibilidad' => now()->format('Y-m-d')
                ]
            ],

            // PRODUCTOS TERMINADOS
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Productos Terminados')->where('customer_id', 1)->first()->id,
                'article_code' => 'PT-VENT-001',
                'label_code' => 'LBL-PT-001',
                'description' => 'Ventana corredera aluminio 1200x1000mm',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_PT_001',
                'rfid_epc' => 'EPC_PT_001',
                'acquired_at' => now()->subDays(3),
                'attributes' => [
                    'tipo_producto' => 'Ventana corredera',
                    'material_marco' => 'Aluminio anodizado',
                    'material_vidrio' => 'Vidrio doble 4+12+4',
                    'dimensiones' => '1200x1000mm',
                    'peso_unitario' => '28kg',
                    'color' => 'Blanco RAL 9010',
                    'accesorios' => ['Manilla', 'Rodamientos', 'Junta perimetral'],
                    'lote_produccion' => 'VENT-2024-07-156',
                    'certificacion' => 'UNE-EN 14351-1',
                    'garantia' => '10 años'
                ],
                'metadata' => [
                    'precio_venta' => 285.00,
                    'margen_beneficio' => 35,
                    'cantidad_disponible' => 25,
                    'cantidad_minima' => 10,
                    'cantidad_maxima' => 50,
                    'fecha_produccion' => now()->subDays(7)->format('Y-m-d'),
                    'fecha_disponibilidad_venta' => now()->subDays(3)->format('Y-m-d'),
                    'cliente_objetivo' => 'Constructoras y particulares',
                    'canal_venta' => 'Catálogo + Presupuesto'
                ]
            ],

            // ENVASES Y EMBALAJES
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Envases y Embalajes')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Consumibles')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Consumibles')->where('customer_id', 1)->first()->id,
                'article_code' => 'ENV-CAJ-001',
                'label_code' => 'LBL-ENV-001',
                'description' => 'Caja cartón reforzada 600x400x300mm',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subDays(12),
                'attributes' => [
                    'tipo_envase' => 'Caja de cartón',
                    'dimensiones' => '600x400x300mm',
                    'material' => 'Cartón ondulado doble',
                    'gramaje' => '400g/m²',
                    'capacidad_peso' => '25kg',
                    'unidades_por_paquete' => 25,
                    'paletizado' => 'Sí - 100 unidades/palet',
                    'color_impresion' => 'Logo empresa + especificaciones',
                    'lote_produccion' => 'CAJ-2024-07-234',
                    'normativa' => 'UNE-EN 13193'
                ],
                'metadata' => [
                    'proveedor' => 'Envases Industriales S.L.',
                    'precio_unitario' => 2.15,
                    'precio_total' => 5375.00,
                    'cantidad_disponible' => 2500,
                    'cantidad_minima' => 500,
                    'cantidad_maxima' => 5000,
                    'fecha_ultima_compra' => now()->subDays(12)->format('Y-m-d'),
                    'proxima_compra_estimada' => now()->addDays(25)->format('Y-m-d'),
                    'uso_principal' => 'Embalaje productos terminados'
                ]
            ],

            // REPUESTOS Y CONSUMIBLES
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Repuestos y Consumibles')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Consumibles')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Consumibles')->where('customer_id', 1)->first()->id,
                'article_code' => 'REP-ROD-001',
                'label_code' => 'LBL-REP-001',
                'description' => 'Rodamiento axial 6205-2RS',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subDays(8),
                'attributes' => [
                    'tipo_repuesto' => 'Rodamiento axial',
                    'modelo' => '6205-2RS',
                    'dimensiones' => 'Ø25xØ52x15mm',
                    'material' => 'Acero cromado',
                    'velocidad_maxima' => '14000 rpm',
                    'carga_dinamica' => '14000N',
                    'carga_estatica' => '7900N',
                    'precisión' => 'ABEC-3',
                    'sellado' => 'Doble labio RS',
                    'lote_produccion' => 'ROD-2024-06-567',
                    'vida_util_estimada' => '5000 horas'
                ],
                'metadata' => [
                    'proveedor' => 'Rodamientos Europeos S.A.',
                    'precio_unitario' => 8.50,
                    'precio_total' => 425.00,
                    'cantidad_disponible' => 50,
                    'cantidad_minima' => 20,
                    'cantidad_maxima' => 100,
                    'fecha_ultima_compra' => now()->subDays(8)->format('Y-m-d'),
                    'proxima_compra_estimada' => now()->addDays(90)->format('Y-m-d'),
                    'maquina_asociada' => 'Torno CNC Haas ST-10',
                    'frecuencia_uso' => 'Mensual'
                ]
            ],

            // Más productos terminados
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Productos Terminados')->where('customer_id', 1)->first()->id,
                'article_code' => 'PT-PUER-001',
                'label_code' => 'LBL-PT-002',
                'description' => 'Puerta abatible aluminio 900x2100mm',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_PT_002',
                'rfid_epc' => 'EPC_PT_002',
                'acquired_at' => now()->subDays(7),
                'attributes' => [
                    'tipo_producto' => 'Puerta abatible',
                    'material_marco' => 'Aluminio térmico',
                    'material_hoja' => 'Panel sándwich 40mm',
                    'dimensiones' => '900x2100mm',
                    'peso_unitario' => '45kg',
                    'color' => 'Gris antracita RAL 7016',
                    'accesorios' => ['Bisagras regulables', 'Cerradura 3 puntos', 'Manilla ergonómica'],
                    'aislamiento_termico' => 'Uw = 1.2 W/m²K',
                    'aislamiento_acustico' => '35dB',
                    'lote_produccion' => 'PUER-2024-07-089',
                    'certificacion' => 'UNE-EN 14351-1',
                    'garantia' => '10 años'
                ],
                'metadata' => [
                    'precio_venta' => 485.00,
                    'margen_beneficio' => 38,
                    'cantidad_disponible' => 12,
                    'cantidad_minima' => 5,
                    'cantidad_maxima' => 25,
                    'fecha_produccion' => now()->subDays(10)->format('Y-m-d'),
                    'fecha_disponibilidad_venta' => now()->subDays(7)->format('Y-m-d'),
                    'cliente_objetivo' => 'Empresas constructoras',
                    'canal_venta' => 'Proyectos + Catálogo'
                ]
            ],

            // Consumibles críticos
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Repuestos y Consumibles')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Consumibles')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Consumibles')->where('customer_id', 1)->first()->id,
                'article_code' => 'CONS-FIL-001',
                'label_code' => 'LBL-CONS-001',
                'description' => 'Filamento PLA blanco 1.75mm 1kg',
                'status' => 'active',
                'has_rfid_tag' => false,
                'acquired_at' => now()->subDays(5),
                'attributes' => [
                    'tipo_consumible' => 'Filamento impresión 3D',
                    'material' => 'PLA',
                    'color' => 'Blanco puro',
                    'diametro' => '1.75mm',
                    'peso_neto' => '1000g',
                    'longitud_aproximada' => '330 metros',
                    'tolerancia_diametro' => '±0.05mm',
                    'temperatura_impresion' => '190-220°C',
                    'temperatura_cama' => '50-60°C',
                    'velocidad_impresion' => '40-60mm/s',
                    'lote_produccion' => 'FIL-2024-07-445'
                ],
                'metadata' => [
                    'proveedor' => '3D Print Solutions',
                    'precio_unitario' => 22.50,
                    'precio_total' => 1125.00,
                    'cantidad_disponible' => 50,
                    'cantidad_minima' => 20,
                    'cantidad_maxima' => 100,
                    'fecha_ultima_compra' => now()->subDays(5)->format('Y-m-d'),
                    'proxima_compra_estimada' => now()->addDays(15)->format('Y-m-d'),
                    'maquina_asociada' => 'Impresora 3D Ultimaker S5',
                    'consumo_medio_mensual' => '8kg',
                    'dias_autonomia' => 6
                ]
            ],

            // Producto terminado premium
            [
                'customer_id' => 1,
                'asset_category_id' => AssetCategory::where('name', 'Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_cost_center_id' => AssetCostCenter::where('name', 'Inventario Productos Terminados')->where('customer_id', 1)->first()->id,
                'asset_location_id' => AssetLocation::where('name', 'Almacén Productos Terminados')->where('customer_id', 1)->first()->id,
                'article_code' => 'PT-FACH-001',
                'label_code' => 'LBL-PT-003',
                'description' => 'Fachada ventilada panel composite 3000x1500mm',
                'status' => 'active',
                'has_rfid_tag' => true,
                'rfid_tid' => 'TID_PT_003',
                'rfid_epc' => 'EPC_PT_003',
                'acquired_at' => now()->subDays(12),
                'attributes' => [
                    'tipo_producto' => 'Fachada ventilada',
                    'material' => 'Panel composite aluminio',
                    'dimensiones' => '3000x1500mm',
                    'espesor' => '4mm',
                    'peso_unitario' => '18kg',
                    'color' => 'Gris plata metalizado',
                    'acabado' => 'Brillo alto',
                    'resistencia_fuego' => 'B-s1,d0',
                    'resistencia_impacto' => 'Clase 3',
                    'lote_produccion' => 'FACH-2024-06-234',
                    'certificacion' => 'UNE-EN 13501-1',
                    'garantia' => '15 años'
                ],
                'metadata' => [
                    'precio_venta' => 1250.00,
                    'margen_beneficio' => 42,
                    'cantidad_disponible' => 8,
                    'cantidad_minima' => 3,
                    'cantidad_maxima' => 15,
                    'fecha_produccion' => now()->subDays(15)->format('Y-m-d'),
                    'fecha_disponibilidad_venta' => now()->subDays(12)->format('Y-m-d'),
                    'cliente_objetivo' => 'Arquitectos y constructoras premium',
                    'canal_venta' => 'Proyectos especiales',
                    'tiempo_instalacion' => '2-3 días',
                    'requiere_especialista' => true
                ]
            ]
        ];

        foreach ($inventoryAssets as $assetData) {
            Asset::create($assetData);
        }

        $this->command->info('✓ InventarioSeeder ejecutado correctamente');
        $this->command->info('✓ Creadas categorías específicas de inventario: ' . count($inventoryCategories));
        $this->command->info('✓ Creados centros de costo de inventario: ' . count($inventoryCostCenters));
        $this->command->info('✓ Creadas ubicaciones de inventario: ' . count($inventoryLocations));
        $this->command->info('✓ Creados activos de inventario: ' . count($inventoryAssets));
        $this->command->info('✓ Datos simulados de inventario realista para demostración');
    }
}
