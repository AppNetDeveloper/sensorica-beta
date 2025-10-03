<?php

namespace Database\Seeders;

use App\Models\VendorSupplier;
use App\Models\VendorItem;
use App\Models\VendorOrder;
use App\Models\VendorOrderLine;
use App\Models\User;
use Illuminate\Database\Seeder;

class VendorProcurementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear proveedores
        $suppliers = [
            [
                'customer_id' => 1,
                'name' => 'Aluminios del Mediterráneo S.A.',
                'tax_id' => 'A12345678',
                'email' => 'ventas@alumediterraneo.com',
                'phone' => '+34 965 123 456',
                'contact_name' => 'Carlos Martínez',
                'payment_terms' => '30 días',
                'metadata' => [
                    'direccion' => 'Polígono Industrial Las Salinas, Calle 15, 03006 Alicante',
                    'sector' => 'Metalurgia',
                    'certificaciones' => ['ISO 9001', 'ISO 14001'],
                    'descuento_volumen' => 5,
                    'moneda_preferida' => 'EUR'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Plásticos Valencianos S.L.',
                'tax_id' => 'B87654321',
                'email' => 'compras@plasticosvalencianos.es',
                'phone' => '+34 963 789 012',
                'contact_name' => 'Ana García',
                'payment_terms' => '45 días',
                'metadata' => [
                    'direccion' => 'Avda. de la Industria 45, 46940 Manises, Valencia',
                    'sector' => 'Plásticos',
                    'certificaciones' => ['ISO 9001', 'REACH'],
                    'descuento_volumen' => 3,
                    'moneda_preferida' => 'EUR'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Ferretería Industrial Central',
                'tax_id' => 'B11223344',
                'email' => 'pedidos@ferreteriacentral.com',
                'phone' => '+34 912 345 678',
                'contact_name' => 'Miguel Rodríguez',
                'payment_terms' => '15 días',
                'metadata' => [
                    'direccion' => 'Calle Mayor 123, 28013 Madrid',
                    'sector' => 'Ferretería y herramientas',
                    'certificaciones' => ['Distribuidor oficial Bosch'],
                    'descuento_volumen' => 8,
                    'moneda_preferida' => 'EUR'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Lubricantes Industriales S.A.',
                'tax_id' => 'A55667788',
                'email' => 'comercial@lubriindustriales.com',
                'phone' => '+34 944 567 890',
                'contact_name' => 'Laura Fernández',
                'payment_terms' => '60 días',
                'metadata' => [
                    'direccion' => 'Polígono Industrial Asua, 48930 Getxo, Vizcaya',
                    'sector' => 'Lubricantes y químicos',
                    'certificaciones' => ['ISO 9001', 'ISO 45001', 'OHSAS 18001'],
                    'descuento_volumen' => 4,
                    'moneda_preferida' => 'EUR'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Envases Industriales S.L.',
                'tax_id' => 'B99887766',
                'email' => 'info@envasesindustriales.es',
                'phone' => '+34 976 234 567',
                'contact_name' => 'Roberto Sánchez',
                'payment_terms' => '30 días',
                'metadata' => [
                    'direccion' => 'Carretera Nacional II, Km 315, 50012 Zaragoza',
                    'sector' => 'Envases y embalajes',
                    'certificaciones' => ['FSC', 'PEFC'],
                    'descuento_volumen' => 6,
                    'moneda_preferida' => 'EUR'
                ]
            ],
            [
                'customer_id' => 1,
                'name' => 'Rodamientos Europeos S.A.',
                'tax_id' => 'A33445566',
                'email' => 'ventas@rodamientoseuropeos.com',
                'phone' => '+34 934 678 901',
                'contact_name' => 'Elena Jiménez',
                'payment_terms' => '30 días',
                'metadata' => [
                    'direccion' => 'Polígono Industrial Can Salvatella, 08210 Barberà del Vallès, Barcelona',
                    'sector' => 'Rodamientos y componentes mecánicos',
                    'certificaciones' => ['ISO 9001', 'TS 16949'],
                    'descuento_volumen' => 7,
                    'moneda_preferida' => 'EUR'
                ]
            ]
        ];

        foreach ($suppliers as $supplierData) {
            VendorSupplier::firstOrCreate(
                ['name' => $supplierData['name'], 'customer_id' => $supplierData['customer_id']],
                $supplierData
            );
        }

        // Crear items de proveedores
        $vendorItems = [
            // Aluminios del Mediterráneo
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Aluminios del Mediterráneo S.A.')->first()->id,
                'sku' => 'ALU-LING-25KG',
                'name' => 'Lingote de Aluminio 99.7%',
                'description' => 'Lingote de aluminio puro 99.7% de 25kg',
                'unit_of_measure' => 'kg',
                'unit_price' => 2.85,
                'lead_time_days' => 15,
                'metadata' => [
                    'pureza' => '99.7%',
                    'peso_unitario' => '25kg',
                    'normativa' => 'UNE-EN 573-3',
                    'stock_minimo' => 1000,
                    'categoria' => 'Materia prima'
                ]
            ],
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Aluminios del Mediterráneo S.A.')->first()->id,
                'sku' => 'ALU-PERF-40X40',
                'name' => 'Perfil Aluminio 40x40mm',
                'description' => 'Perfil de aluminio extrusionado 40x40mm x 6m',
                'unit_of_measure' => 'metro',
                'unit_price' => 12.50,
                'lead_time_days' => 10,
                'metadata' => [
                    'aleacion' => '6063',
                    'longitud' => '6 metros',
                    'espesor' => '2mm',
                    'acabado' => 'Anodizado natural',
                    'categoria' => 'Perfil'
                ]
            ],

            // Plásticos Valencianos
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Plásticos Valencianos S.L.')->first()->id,
                'sku' => 'PVC-GRAN-25KG',
                'name' => 'PVC Granulado Blanco',
                'description' => 'PVC granulado blanco RAL 9010, bolsa 25kg',
                'unit_of_measure' => 'kg',
                'unit_price' => 1.65,
                'lead_time_days' => 7,
                'metadata' => [
                    'color' => 'Blanco RAL 9010',
                    'densidad' => '1.38 g/cm³',
                    'indice_fluidez' => '1.2 g/10min',
                    'normativa' => 'UNE-EN 15343',
                    'categoria' => 'Materia prima'
                ]
            ],

            // Ferretería Industrial Central
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Ferretería Industrial Central')->first()->id,
                'sku' => 'BOSCH-GBH-2-26',
                'name' => 'Taladro Bosch GBH 2-26',
                'description' => 'Taladro percutor profesional Bosch GBH 2-26, 800W',
                'unit_of_measure' => 'unidad',
                'unit_price' => 185.50,
                'lead_time_days' => 3,
                'metadata' => [
                    'marca' => 'Bosch',
                    'potencia' => '800W',
                    'voltaje' => '220V',
                    'peso' => '2.7kg',
                    'garantia' => '24 meses',
                    'categoria' => 'Herramienta'
                ]
            ],
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Ferretería Industrial Central')->first()->id,
                'sku' => 'FLUKE-87V',
                'name' => 'Multímetro Fluke 87V',
                'description' => 'Multímetro digital TRMS Fluke 87V',
                'unit_of_measure' => 'unidad',
                'unit_price' => 425.00,
                'lead_time_days' => 5,
                'metadata' => [
                    'marca' => 'Fluke',
                    'tipo' => 'Multímetro digital TRMS',
                    'categoria_seguridad' => 'CAT III 1000V, CAT IV 600V',
                    'precision' => '±0.05%',
                    'garantia' => '36 meses',
                    'categoria' => 'Instrumento'
                ]
            ],

            // Lubricantes Industriales
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Lubricantes Industriales S.A.')->first()->id,
                'sku' => 'LUB-ISO-VG68-20L',
                'name' => 'Aceite Lubricante ISO VG 68',
                'description' => 'Aceite lubricante industrial ISO VG 68, bidón 20L',
                'unit_of_measure' => 'litro',
                'unit_price' => 4.25,
                'lead_time_days' => 12,
                'metadata' => [
                    'grado_viscosidad' => 'ISO VG 68',
                    'envase' => 'Bidón 20L',
                    'peso_especifico' => '0.925 kg/L',
                    'temperatura_trabajo' => '-20°C a +120°C',
                    'categoria' => 'Lubricante'
                ]
            ],

            // Envases Industriales
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Envases Industriales S.L.')->first()->id,
                'sku' => 'CAJ-CART-600X400',
                'name' => 'Caja Cartón 600x400x300mm',
                'description' => 'Caja de cartón reforzada 600x400x300mm',
                'unit_of_measure' => 'unidad',
                'unit_price' => 2.15,
                'lead_time_days' => 8,
                'metadata' => [
                    'dimensiones' => '600x400x300mm',
                    'material' => 'Cartón ondulado doble',
                    'gramaje' => '400g/m²',
                    'capacidad_peso' => '25kg',
                    'categoria' => 'Embalaje'
                ]
            ],

            // Rodamientos Europeos
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Rodamientos Europeos S.A.')->first()->id,
                'sku' => 'ROD-6205-2RS',
                'name' => 'Rodamiento 6205-2RS',
                'description' => 'Rodamiento rígido de bolas 6205-2RS',
                'unit_of_measure' => 'unidad',
                'unit_price' => 8.50,
                'lead_time_days' => 14,
                'metadata' => [
                    'dimensiones' => 'Ø25xØ52x15mm',
                    'material' => 'Acero cromado',
                    'velocidad_maxima' => '14000 rpm',
                    'precision' => 'ABEC-3',
                    'sellado' => 'Doble labio RS',
                    'categoria' => 'Rodamiento'
                ]
            ]
        ];

        foreach ($vendorItems as $itemData) {
            VendorItem::firstOrCreate(
                ['sku' => $itemData['sku'], 'customer_id' => $itemData['customer_id']],
                $itemData
            );
        }

        // Obtener usuarios para las órdenes
        $adminUser = User::where('email', 'admin@sensorica.es')->first();
        $requesterId = $adminUser ? $adminUser->id : 1;

        // Crear órdenes de compra
        $vendorOrders = [
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Aluminios del Mediterráneo S.A.')->first()->id,
                'requested_by' => $requesterId,
                'approved_by' => $requesterId,
                'reference' => 'PO-2024-001',
                'status' => 'approved',
                'currency' => 'EUR',
                'total_amount' => 14250.00,
                'requested_at' => now()->subDays(15),
                'expected_at' => now()->addDays(5),
                'notes' => 'Pedido urgente para producción Q4',
                'metadata' => [
                    'prioridad' => 'alta',
                    'proyecto' => 'Expansión línea producción',
                    'centro_coste' => 'PROD-001'
                ]
            ],
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Plásticos Valencianos S.L.')->first()->id,
                'requested_by' => $requesterId,
                'approved_by' => $requesterId,
                'reference' => 'PO-2024-002',
                'status' => 'pending',
                'currency' => 'EUR',
                'total_amount' => 8250.00,
                'requested_at' => now()->subDays(10),
                'expected_at' => now()->addDays(7),
                'notes' => 'Reposición stock PVC',
                'metadata' => [
                    'prioridad' => 'media',
                    'proyecto' => 'Stock regular',
                    'centro_coste' => 'INV-MP-001'
                ]
            ],
            [
                'customer_id' => 1,
                'vendor_supplier_id' => VendorSupplier::where('name', 'Ferretería Industrial Central')->first()->id,
                'requested_by' => $requesterId,
                'reference' => 'PO-2024-003',
                'status' => 'received',
                'currency' => 'EUR',
                'total_amount' => 1035.50,
                'requested_at' => now()->subDays(20),
                'expected_at' => now()->subDays(5),
                'notes' => 'Herramientas para nuevo taller',
                'metadata' => [
                    'prioridad' => 'media',
                    'proyecto' => 'Equipamiento taller',
                    'centro_coste' => 'MANT-001'
                ]
            ]
        ];

        foreach ($vendorOrders as $orderData) {
            VendorOrder::firstOrCreate(
                ['reference' => $orderData['reference'], 'customer_id' => $orderData['customer_id']],
                $orderData
            );
        }

        // Crear líneas de órdenes
        $orderLines = [
            // Líneas para PO-2024-001 (Aluminios)
            [
                'vendor_order_id' => VendorOrder::where('reference', 'PO-2024-001')->first()->id,
                'vendor_item_id' => VendorItem::where('sku', 'ALU-LING-25KG')->first()->id,
                'description' => 'Lingotes de aluminio para producción Q4',
                'quantity_ordered' => 5000.0000,
                'quantity_received' => 0.0000,
                'unit_price' => 2.8500,
                'tax_rate' => 21.00,
                'status' => 'pending',
                'metadata' => [
                    'lote_requerido' => 'ALU-2024-Q4-001',
                    'fecha_necesaria' => now()->addDays(5)->format('Y-m-d')
                ]
            ],

            // Líneas para PO-2024-002 (Plásticos)
            [
                'vendor_order_id' => VendorOrder::where('reference', 'PO-2024-002')->first()->id,
                'vendor_item_id' => VendorItem::where('sku', 'PVC-GRAN-25KG')->first()->id,
                'description' => 'PVC granulado blanco para reposición',
                'quantity_ordered' => 5000.0000,
                'quantity_received' => 0.0000,
                'unit_price' => 1.6500,
                'tax_rate' => 21.00,
                'status' => 'pending',
                'metadata' => [
                    'almacen_destino' => 'ALM-MP-A',
                    'fecha_necesaria' => now()->addDays(7)->format('Y-m-d')
                ]
            ],

            // Líneas para PO-2024-003 (Ferretería)
            [
                'vendor_order_id' => VendorOrder::where('reference', 'PO-2024-003')->first()->id,
                'vendor_item_id' => VendorItem::where('sku', 'BOSCH-GBH-2-26')->first()->id,
                'description' => 'Taladro profesional para taller',
                'quantity_ordered' => 2.0000,
                'quantity_received' => 2.0000,
                'unit_price' => 185.5000,
                'tax_rate' => 21.00,
                'status' => 'received',
                'metadata' => [
                    'ubicacion_destino' => 'TALLER-MEC',
                    'fecha_recepcion' => now()->subDays(5)->format('Y-m-d')
                ]
            ],
            [
                'vendor_order_id' => VendorOrder::where('reference', 'PO-2024-003')->first()->id,
                'vendor_item_id' => VendorItem::where('sku', 'FLUKE-87V')->first()->id,
                'description' => 'Multímetro para mantenimiento',
                'quantity_ordered' => 1.0000,
                'quantity_received' => 1.0000,
                'unit_price' => 425.0000,
                'tax_rate' => 21.00,
                'status' => 'received',
                'metadata' => [
                    'ubicacion_destino' => 'TALLER-MEC',
                    'fecha_recepcion' => now()->subDays(5)->format('Y-m-d'),
                    'numero_serie' => 'FL87V-2024-001'
                ]
            ]
        ];

        foreach ($orderLines as $lineData) {
            VendorOrderLine::firstOrCreate(
                [
                    'vendor_order_id' => $lineData['vendor_order_id'],
                    'vendor_item_id' => $lineData['vendor_item_id']
                ],
                $lineData
            );
        }

        $this->command->info('✓ VendorProcurementSeeder ejecutado correctamente');
        $this->command->info('✓ Creados proveedores: ' . count($suppliers));
        $this->command->info('✓ Creados items de proveedores: ' . count($vendorItems));
        $this->command->info('✓ Creadas órdenes de compra: ' . count($vendorOrders));
        $this->command->info('✓ Creadas líneas de órdenes: ' . count($orderLines));
        $this->command->info('✓ Datos de compras y proveedores listos para demostración');
    }
}
