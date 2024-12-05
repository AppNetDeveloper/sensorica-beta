<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderStat;  // Asegúrate de importar el modelo OrderStat
use Illuminate\Support\Facades\Log;
use App\Models\Scada;

class Barcode extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'name',
        'token',
        'mqtt_topic_barcodes',
        'machine_id',
        'ope_id',
        'order_notice',
        'last_barcode',
        'ip_zerotier',
        'user_ssh',
        'port_ssh',
        'ip_barcoder',
        'user_ssh_password',
        'port_barcoder',
        'conexion_type',
        'iniciar_model',
        'sended',
    ];

    protected static function boot()
    {
        parent::boot();

        // Evento 'updating' para detectar cambios en 'order_notice'
        static::updating(function ($barcode) {
            if ($barcode->isDirty('order_notice')) {
                self::processOrderNotice($barcode);
            }

            if ($barcode->isDirty([
                'ip_barcoder',
                'port_barcoder',
            ])) {
                self::restartSupervisor();
            }
        });

        static::created(function ($barcode) {
            self::restartSupervisor();
        });

        static::deleted(function ($barcode) {
            self::restartSupervisor();
        });
    }

    /**
     * Método para procesar el campo 'order_notice' cuando cambia y asi se genera nueva linea de order_stats donde se guardan todos los infos del orden en curso
     */
    protected static function processOrderNotice($barcode)
    {
        // Decodificar el JSON almacenado en 'order_notice'
        $orderNotice = json_decode($barcode->order_notice, true);

        if ($orderNotice && isset($orderNotice['orderId'], $orderNotice['quantity'])) {

            $orderId = (string)$orderNotice['orderId'];
            $units = $orderNotice['quantity'];
            $box = $orderNotice['refer']['groupLevel'][0]['total'];
            $productionLineId = $barcode->production_line_id;

            // Verificar si el barcoder_id ya existe en la tabla 'scada'
            $scada = Scada::where('barcoder_id', $barcode->id)->first();

            if (!$scada) {
                OrderStat::create([
                    'production_line_id' => $productionLineId,
                    'order_id' => $orderId,
                    'box' => $box,
                    'units_box' => $units,
                    'units' => $box * $units,
                    'units_per_minute_real' => null,
                    'units_per_minute_theoretical' => null,
                    'seconds_per_unit_real' => null,
                    'seconds_per_unit_theoretical' => null,
                    'units_made_real' => 0,
                    'units_made_theoretical' => 0,
                    'sensor_stops_count' => 0,
                    'sensor_stops_time' => 0,
                    'production_stops_time' => 0,
                    'units_made' => 0,
                    'units_pending' => 0,
                    'units_delayed' => 0,
                    'slow_time' => 0,
                    'oee' => null,
                ]);
            } else {
                // Si existe 'scada', proceder a crear un nuevo 'ScadaOrder'

                // Obtener el último valor de 'orden' en 'scada_order' para asignar un nuevo orden secuencial
                $lastOrder = ScadaOrder::max('orden');
                $newOrder = $lastOrder ? $lastOrder + 1 : 1;

                // Crear un nuevo registro en 'ScadaOrder' con los datos de la orden
                $scadaOrder = ScadaOrder::create([
                    'scada_id' => $scada->id,
                    'production_line_id' => $productionLineId,
                    'barcoder_id' => $barcode->id,
                    'box' => $box,
                    'units_box' => $units,
                    'units' => $box * $units,
                    'order_id' => $orderId,
                    'json' => $orderNotice, // Almacenar el JSON original
                    'status' => 0, // Estado inicial
                    'orden' => $newOrder, // Asignar el nuevo orden
                ]);

                // Obtener la capacidad de la mezcladora en metros cúbicos desde 'scada'
                $mixerCapacity = $scada->mixer_m3;

                // Inicializar variables para el volumen total y la detección de densidades faltantes
                $totalVolume = 0;
                $missingDensity = false;

                // Arreglo para almacenar los materiales procesados
                $materialVolumes = [];

                // Recorrer cada material en 'groupLevel' del JSON
                foreach ($orderNotice['refer']['groupLevel'] as $material) {
                    // Procesar solo materiales donde 'measure' es 'Kg'
                    if ($material['measure'] === 'Kg') {
                        // Intentar encontrar el material en 'scada_material_type' usando 'client_id'
                        $materialType = ScadaMaterialType::where('client_id', $material['id'])->first();

                        if (!$materialType) {
                            // Si no se encuentra por 'client_id', buscar por 'name'
                            $materialType = ScadaMaterialType::where('name', $material['name'])->first();

                            if ($materialType) {
                                // Si se encuentra por 'name', actualizar 'client_id' en la base de datos solo si esl clint_id es null
                                if ($materialType->client_id === null) {
                                    $materialType->client_id = $material['id'];
                                    $materialType->save();
                                }else {
                                    $scadaOrder->update(['status' => 5]);
                                    Log::warning("Material : {$material['name']} (ID: {$material['id']}) No encontrado con id de cliente en NULL para poder ser actualizado. ScadaOrder actualizado a estado 5.");
                                    return; // Salir del método
                                }

                            } else {
                                // Si no se encuentra, actualizar estado de 'scadaOrder' a 5 (incidencia) y registrar en el log
                                $scadaOrder->update(['status' => 5]);
                                Log::warning("Material no encontrado: {$material['name']} (ID: {$material['id']}). ScadaOrder actualizado a estado 5.");
                                return; // Salir del método
                            }
                        } else {
                            // Si se encontró por 'client_id', verificar que el 'name' coincida
                            if ($materialType->name !== $material['name']) {
                                // Si el nombre no coincide, actualizar estado a 5 y registrar en el log
                                $scadaOrder->update(['status' => 5]);
                                Log::warning("ID y nombre del material no coinciden: ID {$material['id']} tiene nombre {$materialType->name}, se esperaba {$material['name']}. ScadaOrder actualizado a estado 5.");
                                return; // Salir del método
                            }
                        }

                        // Obtener la densidad del material desde 'scada_material_type'
                        $density = $materialType->density;

                        if (!$density || $density == 0) {
                            // Si la densidad es nula o cero, actualizar estado a 5 y registrar en el log
                            $scadaOrder->update(['status' => 5]);
                            Log::warning("Densidad faltante para material: {$material['name']}. ScadaOrder actualizado a estado 5.");
                            return; // Salir del método
                        }

                        // Calcular el volumen del material en metros cúbicos: volumen = peso total (kg) / densidad (kg/m3)
                        $materialVolume = $material['total'] / $density;

                        // Sumar el volumen del material al volumen total
                        $totalVolume += $materialVolume;

                        // Almacenar los datos del material para uso posterior
                        $material['volume'] = $materialVolume; // Volumen en m3
                        $material['density'] = $density; // Densidad en kg/m3
                        $material['material_type'] = $materialType; // Objeto 'ScadaMaterialType'
                        $material['value'] = $material['total']; // Peso total en kg
                        $materialVolumes[] = $material; // Agregar al arreglo de materiales
                    } else {
                        // Si 'measure' no es 'Kg', ignorar este material y continuar con el siguiente
                        continue;
                    }
                }

                // Verificar si no hay materiales válidos después del procesamiento
                if (empty($materialVolumes)) {
                    // Si no hay materiales para procesar, actualizar estado a 5 y registrar en el log
                    $scadaOrder->update(['status' => 5]);
                    Log::warning("No hay materiales válidos para procesar. ScadaOrder actualizado a estado 5.");
                    return; // Salir del método
                }

                // Calcular el número de lotes necesarios, redondeando hacia arriba
                $batches = ceil($totalVolume / $mixerCapacity);
                Log::info("Total volumen de la orden: {$totalVolume} m3, dividido por capacidad de mezcladora {$mixerCapacity} m3 = Batches: {$batches}.");

                // Procesar cada lote
                for ($i = 0; $i < $batches; $i++) {
                    // Determinar el volumen del lote actual
                    if ($i < $batches - 1) {
                        // Para todos los lotes excepto el último, usar la capacidad máxima de la mezcladora
                        $batchVolume = $mixerCapacity;
                    } else {
                        // Para el último lote, usar el volumen restante
                        $batchVolume = $totalVolume - ($mixerCapacity * ($batches - 1));
                    }

                    // Crear una nueva lista de orden para materiales automáticos (process = 0)
                    $autoList = ScadaOrderList::create([
                        'scada_order_id' => $scadaOrder->id,
                        'process' => 0, // 0 = automático
                    ]);

                    // Crear una nueva lista de orden para materiales manuales (process = 1)
                    $manualList = ScadaOrderList::create([
                        'scada_order_id' => $scadaOrder->id,
                        'process' => 1, // 1 = manual
                    ]);

                    // Procesar cada material almacenado en 'materialVolumes'
                    foreach ($materialVolumes as $material) {
                        // Obtener el objeto 'ScadaMaterialType' del material
                        $materialType = $material['material_type'];

                        // Calcular el volumen del material en este lote: proporcional al volumen del lote actual
                        $materialBatchVolume = $material['volume'] * ($batchVolume / $totalVolume);

                        // Convertir el volumen del material en peso (kg): peso = volumen (m3) * densidad (kg/m3)
                        $materialBatchWeight = $materialBatchVolume * $material['density'];

                        // Redondear el peso a 4 decimales
                        $materialBatchWeight = round($materialBatchWeight, 4);

                        // Determinar si el material es automático o manual según 'service_type' (0 = automático, 1 = manual)
                        if ($materialType->service_type == 0) {
                            // Material automático, crear entrada en 'ScadaOrderListProcess' correspondiente
                            ScadaOrderListProcess::create([
                                'scada_order_list_id' => $autoList->id, // ID de la lista automática
                                'scada_material_type_id' => $materialType->id, // ID del material
                                'orden' => $material['level'], // Nivel u orden del material
                                'measure' => $material['measure'], // Unidad de medida (ej. 'Kg')
                                'value' => $materialBatchWeight, // Peso en kg para este lote
                            ]);
                        } else {
                            // Material manual, crear entrada en 'ScadaOrderListProcess' correspondiente
                            ScadaOrderListProcess::create([
                                'scada_order_list_id' => $manualList->id, // ID de la lista manual
                                'scada_material_type_id' => $materialType->id, // ID del material
                                'orden' => $material['level'], // Nivel u orden del material
                                'measure' => $material['measure'], // Unidad de medida
                                'value' => $materialBatchWeight, // Peso en kg para este lote
                            ]);
                        }
                    }
                }
            }
        }
    }


    /**
     * Método para reiniciar el Supervisor.
     */
    protected static function restartSupervisor()
    {
        try {
            exec('sudo /usr/bin/supervisorctl restart all', $output, $returnVar);

            if ($returnVar === 0) {
                Log::channel('supervisor')->info("Supervisor reiniciado exitosamente.");
            } else {
                Log::channel('supervisor')->error("Error al reiniciar supervisor: " . implode("\n", $output));
            }
        } catch (\Exception $e) {
            Log::channel('supervisor')->error("Excepción al reiniciar supervisor: " . $e->getMessage());
        }
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
