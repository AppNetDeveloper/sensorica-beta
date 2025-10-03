<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCustomFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear activos con campos personalizados adicionales usando el campo 'attributes'

        // 1. Activo con campos personalizados para mantenimiento
        $maintenanceAsset = Asset::where('article_code', 'TAL-001')->first();
        if ($maintenanceAsset) {
            $maintenanceAsset->update([
                'attributes' => array_merge($maintenanceAsset->attributes ?? [], [
                    'fecha_ultima_revision' => now()->subMonths(3)->format('Y-m-d'),
                    'proxima_revision' => now()->addMonths(3)->format('Y-m-d'),
                    'intervalo_mantenimiento_meses' => 6,
                    'tecnico_responsable' => 'Roberto García',
                    'estado_mantenimiento' => 'Bueno',
                    'observaciones_tecnicas' => 'Funciona correctamente, sin problemas detectados',
                    'historial_reparaciones' => [
                        ['fecha' => now()->subMonths(8)->format('Y-m-d'), 'descripcion' => 'Cambio de batería', 'costo' => 45.00],
                        ['fecha' => now()->subMonths(14)->format('Y-m-d'), 'descripcion' => 'Limpieza general', 'costo' => 25.00]
                    ]
                ])
            ]);
        }

        // 2. Activo con campos personalizados para maquinaria pesada
        $machineryAsset = Asset::where('article_code', 'TOR-001')->first();
        if ($machineryAsset) {
            $machineryAsset->update([
                'attributes' => array_merge($machineryAsset->attributes ?? [], [
                    'fecha_instalacion' => now()->subYears(2)->format('Y-m-d'),
                    'fecha_ultima_calibracion' => now()->subMonths(1)->format('Y-m-d'),
                    'proxima_calibracion' => now()->addMonths(5)->format('Y-m-d'),
                    'horas_operacion_diarias' => 8,
                    'horas_operacion_totales' => 2847,
                    'eficiencia_actual' => 94.5,
                    'consumo_energetico_kwh' => 45.2,
                    'nivel_ruido_db' => 78,
                    'requisitos_seguridad' => ['EPP obligatorio', 'Formación específica requerida', 'Inspección mensual'],
                    'documentacion_tecnica' => [
                        'manual_operacion' => 'Disponible',
                        'planos_electricos' => 'Disponible',
                        'certificado_ce' => 'Disponible',
                        'garantia_extendida' => 'Sí, hasta ' . now()->addYears(1)->format('Y-m-d')
                    ]
                ])
            ]);
        }

        // 3. Activo con campos personalizados para equipo de oficina
        $officeAsset = Asset::where('article_code', 'LAP-001')->first();
        if ($officeAsset) {
            $officeAsset->update([
                'attributes' => array_merge($officeAsset->attributes ?? [], [
                    'usuario_actual' => 'Ana Martínez',
                    'departamento_actual' => 'Recursos Humanos',
                    'fecha_asignacion' => now()->subMonths(2)->format('Y-m-d'),
                    'licencias_software' => [
                        'Microsoft Office 365' => 'Licencia activa hasta 2025',
                        'Antivirus ESET' => 'Licencia activa hasta 2025',
                        'Adobe Creative Suite' => 'No aplica'
                    ],
                    'configuracion_actual' => [
                        'wallpaper' => 'Corporativo estándar',
                        'configuracion_red' => 'DHCP automático',
                        'impresoras_instaladas' => ['Impresora oficina', 'Impresora color'],
                        'perifericos_conectados' => ['Mouse inalámbrico', 'Teclado externo']
                    ],
                    'estado_seguridad' => 'Actualizado',
                    'fecha_ultima_actualizacion' => now()->subDays(7)->format('Y-m-d'),
                    'proxima_revision_it' => now()->addMonths(3)->format('Y-m-d')
                ])
            ]);
        }

        // 4. Activo con campos personalizados para mobiliario
        $furnitureAsset = Asset::where('article_code', 'SILL-001')->first();
        if ($furnitureAsset) {
            $furnitureAsset->update([
                'attributes' => array_merge($furnitureAsset->attributes ?? [], [
                    'fecha_compra' => now()->subMonths(3)->format('Y-m-d'),
                    'proveedor' => 'Oficina Confort S.L.',
                    'garantia_hasta' => now()->addYears(2)->format('Y-m-d'),
                    'estado_actual' => 'Excelente',
                    'ultima_limpieza' => now()->subWeeks(1)->format('Y-m-d'),
                    'proxima_limpieza' => now()->addWeeks(3)->format('Y-m-d'),
                    'usuario_actual' => 'Lucía Fernández',
                    'ajustes_realizados' => [
                        'altura_respaldo' => 'Máxima',
                        'profundidad_asiento' => 'Media',
                        'apoyo_lumbar' => 'Activado',
                        'apoyo_cabeza' => 'Activado'
                    ],
                    'mantenimiento_realizado' => [
                        ['fecha' => now()->subMonths(1)->format('Y-m-d'), 'tipo' => 'Limpieza profunda', 'realizado_por' => 'Servicio limpieza']
                    ]
                ])
            ]);
        }

        // 5. Activo con campos personalizados para herramientas de precisión
        $precisionAsset = Asset::where('article_code', 'MULT-001')->first();
        if ($precisionAsset) {
            $precisionAsset->update([
                'attributes' => array_merge($precisionAsset->attributes ?? [], [
                    'certificacion_calibracion' => 'ISO 17025',
                    'fecha_ultima_calibracion' => now()->subMonths(6)->format('Y-m-d'),
                    'proxima_calibracion' => now()->addMonths(6)->format('Y-m-d'),
                    'laboratorio_calibracion' => 'Laboratorio Metrológico Nacional',
                    'incertidumbre_medicion' => '±0.02%',
                    'rango_medicion_voltaje' => '0-1000V',
                    'rango_medicion_corriente' => '0-10A',
                    'rango_medicion_resistencia' => '0-40MΩ',
                    'estandar_referencia' => 'Fluke 5700A',
                    'certificado_numero' => 'CERT-2024-04567',
                    'requisitos_almacenamiento' => [
                        'temperatura' => '20°C ± 2°C',
                        'humedad' => '45% ± 10%',
                        'evitar_exposicion' => ['luz solar directa', 'campos magnéticos', 'vibraciones']
                    ],
                    'procedimiento_uso' => [
                        'Verificar calibración antes de usar',
                        'Dejar estabilizar 5 minutos',
                        'Usar cables originales',
                        'Almacenar en estuche protector'
                    ]
                ])
            ]);
        }

        // 6. Activo con campos personalizados para equipo especializado
        $specialAsset = Asset::where('article_code', 'IMP-001')->first();
        if ($specialAsset) {
            $specialAsset->update([
                'attributes' => array_merge($specialAsset->attributes ?? [], [
                    'fecha_instalacion' => now()->subYears(1)->format('Y-m-d'),
                    'tecnico_instalador' => 'Equipo 3D Solutions',
                    'configuracion_inicial' => [
                        'temperatura_nozzle' => '220°C',
                        'temperatura_cama' => '60°C',
                        'velocidad_impresion' => '50mm/s',
                        'altura_capa' => '0.2mm',
                        'material_predeterminado' => 'PLA'
                    ],
                    'consumibles_actuales' => [
                        'filamento_pla_blanco' => '850g restantes',
                        'filamento_abs_negro' => '1200g restantes',
                        'filamento_petg_transparente' => '500g restantes'
                    ],
                    'mantenimiento_programado' => [
                        'semanal' => ['limpieza_nozzle', 'calibracion_cama'],
                        'mensual' => ['limpieza_general', 'verificacion_componentes'],
                        'trimestral' => ['mantenimiento_profesional', 'actualizacion_software']
                    ],
                    'estado_componentes' => [
                        'nozzle' => 'Bueno',
                        'cama_calefactada' => 'Bueno',
                        'ventiladores' => 'Requiere limpieza',
                        'correas' => 'Bueno',
                        'motores' => 'Excelente'
                    ],
                    'horas_uso_semanales' => 35,
                    'horas_uso_totales' => 1247,
                    'eficiencia_actual' => 87.3,
                    'incidencias_reportadas' => [
                        ['fecha' => now()->subWeeks(2)->format('Y-m-d'), 'tipo' => 'Atasco filamento', 'solucion' => 'Limpieza y recalibración', 'tiempo_parada' => '2 horas']
                    ]
                ])
            ]);
        }

        $this->command->info('✓ Asset custom fields seeder ejecutado correctamente');
        $this->command->info('✓ Agregados campos personalizados a activos existentes');
        $this->command->info('✓ Campos incluyen mantenimiento, calibración, usuarios, configuración, etc.');
    }
}
