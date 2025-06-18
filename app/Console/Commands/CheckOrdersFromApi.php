<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OrderFieldMapping;
use App\Models\Process;
use App\Models\OriginalOrderProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckOrdersFromApi extends Command
{
    protected $signature = 'orders:check';
    protected $description = 'Verifica pedidos desde la API y los compara con la base de datos local';

    public function handle()
    {
        $this->info('=== Iniciando verificación de pedidos desde API ===');
        
        // Conectarse a la DB y obtener clientes con las 2 URLs configuradas
        $customers = Customer::whereNotNull('order_listing_url')
            ->whereNotNull('order_detail_url')
            ->whereHas('fieldMappings')
            ->with('fieldMappings')
            ->get();

        if ($customers->isEmpty()) {
            $this->warn('No se encontraron clientes con las URLs configuradas y mapeos definidos.');
            return 0;
        }

        $this->info("Encontrados {$customers->count()} clientes para procesar");
        
        foreach ($customers as $customer) {
            $this->newLine();
            $this->info("=== Procesando cliente: {$customer->name} ===");
            $this->info("URL Listado: {$customer->order_listing_url}");
            $this->info("URL Detalle: {$customer->order_detail_url}");
            
            try {
                // Llamar al order_listing_url
                $this->info("Llamando a la API de listado de pedidos...");
                $response = Http::timeout(30)->get($customer->order_listing_url);
                
                if (!$response->successful()) {
                    $this->error("Error al obtener pedidos para el cliente {$customer->name}: HTTP {$response->status()}");
                    Log::error("Error API para cliente {$customer->name}", [
                        'customer_id' => $customer->id,
                        'url' => $customer->order_listing_url,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    continue;
                }

                $orders = $response->json();
                
                if (empty($orders)) {
                    $this->warn("No se encontraron pedidos para el cliente: {$customer->name}");
                    Log::info("Sin pedidos para cliente {$customer->name}");
                    continue;
                }

                $this->info("Encontrados " . count($orders) . " pedidos en la API");
                
                // Procesar cada pedido aplicando los mapeos
                foreach ($orders as $index => $order) {
                    $this->info("--- Procesando pedido " . ($index + 1) . " ---");
                    
                    try {
                        // Aplicar mapeos de campos
                        $mappedData = [];
                        $orderId = null;
                        
                        foreach ($customer->fieldMappings as $mapping) {
                            $sourceValue = data_get($order, $mapping->source_field);
                            
                            // Aplicar transformaciones
                            $transformedValue = $mapping->applyTransformations($sourceValue);
                            $mappedData[$mapping->target_field] = $transformedValue;
                            
                            // Capturar el order_id para los logs
                            if ($mapping->target_field === 'order_id') {
                                $orderId = $transformedValue;
                            }
                            
                            $this->line("  Mapeo: {$mapping->source_field} -> {$mapping->target_field} = '{$transformedValue}'");
                        }
                        
                        // Verificar si el order_id existe en la base de datos
                        if ($orderId) {
                            $existingOrder = OriginalOrder::where('order_id', $orderId)->first();
                            
                            if ($existingOrder) {
                                $this->line("<fg=green>✓ El order_id {$orderId} EXISTE en la base de datos (ID: {$existingOrder->id})</>");
                                
                                Log::info("Order ID {$orderId} ya existe", [
                                    'customer_id' => $customer->id,
                                    'customer_name' => $customer->name,
                                    'order_id' => $orderId,
                                    'existing_order_id' => $existingOrder->id
                                ]);
                                
                                // Solo actualizar los detalles del pedido, no reprocesar procesos
                                $this->processOrderDetails($customer, $existingOrder, $orderId);
                                
                                $this->line("  ℹ️ Orden existente - no se reprocesan los procesos");
                                
                            } else {
                                $this->line("<fg=yellow>✗ El order_id {$orderId} NO EXISTE en la base de datos</>");
                                $this->line("<fg=blue>→ Procesando y creando nuevo pedido...</>");
                                
                                try {
                                    // Agregar customer_id a los datos mapeados
                                    $mappedData['customer_id'] = $customer->id;
                                    
                                    // Validar que tengamos los campos mínimos requeridos
                                    if (empty($mappedData['order_id'])) {
                                        $this->warn("  ⚠️ No se puede crear el pedido: falta order_id");
                                        continue;
                                    }
                                    
                                    // Crear el nuevo pedido en la base de datos
                                    $newOrder = OriginalOrder::create($mappedData);
                                    
                                    $this->line("<fg=green>  ✅ Pedido {$orderId} creado exitosamente (ID: {$newOrder->id})</>");
                                    
                                    Log::info("Order ID {$orderId} creado", [
                                        'customer_id' => $customer->id,
                                        'customer_name' => $customer->name,
                                        'order_id' => $orderId,
                                        'new_order_id' => $newOrder->id,
                                        'mapped_data' => $mappedData
                                    ]);
                                    
                                    // Llamar al order_detail_url para obtener detalles del nuevo pedido
                                    $this->processOrderDetails($customer, $newOrder, $orderId);
                                    
                                    // Procesar los detalles para crear procesos
                                    $processResult = $this->processOrderProcesses($customer, $newOrder);
                                    if ($processResult === -1) {
                                        $this->warn("  🗑️ Orden {$orderId} eliminada por falta de procesos válidos");
                                        continue; // Continuar con el siguiente pedido
                                    }
                                    
                                } catch (\Exception $e) {
                                    $this->error("  ❌ Error creando pedido {$orderId}: " . $e->getMessage());
                                    Log::error("Error creando order {$orderId}", [
                                        'customer_id' => $customer->id,
                                        'customer_name' => $customer->name,
                                        'order_id' => $orderId,
                                        'mapped_data' => $mappedData,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }
                            }
                        } else {
                            $this->warn("  No se pudo obtener order_id del mapeo");
                            Log::warning("Sin order_id para cliente {$customer->name}", [
                                'customer_id' => $customer->id,
                                'mapped_data' => $mappedData
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        $this->error("Error procesando pedido: " . $e->getMessage());
                        Log::error('Error procesando pedido individual', [
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'pedido_index' => $index,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("Error procesando cliente {$customer->name}: " . $e->getMessage());
                Log::error('Error procesando cliente', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->newLine();
        $this->info('=== Proceso completado ===');
        
        return 0;
    }

    private function processOrderDetails(Customer $customer, OriginalOrder $order, string $orderId)
    {
        try {
            // Construir la URL reemplazando {order_id} con el valor real
            $detailUrl = str_replace('{order_id}', $orderId, $customer->order_detail_url);
            
            $this->info("  → Llamando a la API de detalles: {$detailUrl}");
            $response = Http::timeout(30)->get($detailUrl);
            
            if (!$response->successful()) {
                $this->error("  ❌ Error al obtener detalles del pedido {$orderId}: HTTP {$response->status()}");
                Log::error("Error API para detalles de pedido {$orderId}", [
                    'customer_id' => $customer->id,
                    'order_id' => $orderId,
                    'url' => $detailUrl,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return;
            }

            $orderDetails = $response->json();
            
            if (empty($orderDetails)) {
                $this->warn("  ⚠️ No se encontraron detalles para el pedido {$orderId}");
                return;
            }
            
            // Guardar todo el JSON en order_details
            $order->order_details = $orderDetails;
            $order->save();
            
            $this->line("<fg=green>  ✅ Detalles del pedido {$orderId} guardados exitosamente</>");
            $this->line("     Elementos encontrados: " . count($orderDetails));
            
            Log::info("Detalles del pedido {$orderId} guardados", [
                'customer_id' => $customer->id,
                'order_id' => $orderId,
                'order_db_id' => $order->id,
                'details_count' => count($orderDetails)
            ]);
            
        } catch (\Exception $e) {
            $this->error("  ❌ Error procesando detalles del pedido {$orderId}: " . $e->getMessage());
            Log::error("Error procesando detalles del pedido {$orderId}", [
                'customer_id' => $customer->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function processOrderProcesses(Customer $customer, OriginalOrder $order)
    {
        $this->line("  → Procesando detalles para crear procesos del pedido {$order->order_id}...");
        
        $orderDetails = $order->order_details;
        if (!is_array($orderDetails) || !isset($orderDetails['grupos'])) {
            $this->warn("    ⚠️ No se encontraron grupos en los detalles del pedido");
            return 0;
        }
        
        $totalProcessesCreated = 0;
        
        foreach ($orderDetails['grupos'] as $index => $grupo) {
            $this->line("    Procesando grupo " . ($index + 1) . "...");
            $processesCreated = $this->processGroupItems($customer, $grupo, $order);
            $totalProcessesCreated += $processesCreated;
        }
        
        // Verificar si la orden tiene al menos un proceso válido
        if ($totalProcessesCreated == 0) {
            $existingProcesses = $order->processes()->count();
            if ($existingProcesses == 0) {
                $this->warn("  ⚠️ La orden {$order->order_id} no tiene procesos válidos de fabricación");
                $this->warn("  🗑️ Eliminando orden {$order->order_id} por falta de procesos válidos...");
                
                try {
                    $order->delete();
                    $this->info("  ✅ Orden {$order->order_id} eliminada exitosamente");
                    return -1; // Indicador de que la orden fue eliminada
                } catch (\Exception $e) {
                    $this->error("  ❌ Error al eliminar la orden {$order->order_id}: " . $e->getMessage());
                }
            } else {
                $this->line("  ℹ️ La orden {$order->order_id} ya tiene {$existingProcesses} procesos existentes");
            }
        }
        
        $this->info("  ✅ Procesos creados: {$totalProcessesCreated}");
        return $totalProcessesCreated;
    }
    
    private function processGroupItems(Customer $customer, array $grupo, OriginalOrder $order)
    {
        $processedItems = 0;
        
        // Procesar cada item en el grupo
        foreach ($grupo as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $this->line("      → Analizando '{$key}' (" . count($value) . " elementos)");
                
                // Verificar si hay mapeos configurados para este item
                $mappings = $customer->processFieldMappings->filter(function ($mapping) use ($key) {
                    return strpos($mapping->source_field, "grupos[*].{$key}[*].") === 0;
                });
                
                if (!$mappings->isEmpty()) {
                    $this->line("        ✅ Encontrados " . $mappings->count() . " mapeos para '{$key}'");
                    
                    foreach ($value as $index => $item) {
                        $this->line("        Procesando {$key}[{$index}]:");
                        $processData = $this->mapProcessData($customer, $item, $key);
                        if ($processData) {
                            $created = $this->createOrderProcess($order, $processData);
                            if ($created) {
                                $processedItems++;
                            }
                        } else {
                            $this->line("        ⚠️ No se pudo mapear el item");
                        }
                    }
                } else {
                    $this->line("        → No hay mapeos configurados para '{$key}'");
                }
            }
        }
        
        return $processedItems;
    }
    
    private function mapProcessData(Customer $customer, array $item, string $type)
    {
        $mappedData = [];
        
        // Aplicar mapeos de campos de procesos
        foreach ($customer->processFieldMappings as $mapping) {
            $sourceField = $mapping->source_field;
            
            // Verificar si el mapeo es para este tipo
            if (strpos($sourceField, "grupos[*].{$type}[*].") === 0) {
                // Extraer el campo real del source_field
                $fieldName = str_replace("grupos[*].{$type}[*].", '', $sourceField);
                
                // Obtener el valor del item
                $sourceValue = data_get($item, $fieldName);
                
                if ($sourceValue !== null) {
                    // Aplicar transformaciones
                    $transformedValue = $mapping->applyTransformations($sourceValue);
                    $mappedData[$mapping->target_field] = $transformedValue;
                    
                    $this->line("      → Mapeo: {$fieldName} -> {$mapping->target_field} = '{$transformedValue}'");
                } else {
                    $this->line("      ⚠️ Campo '{$fieldName}' no encontrado en el item");
                }
            }
        }
        
        return !empty($mappedData) ? $mappedData : null;
    }
    
    private function createOrderProcess(OriginalOrder $order, array $processData)
    {
        try {
            // Verificar que tenemos process_id
            if (empty($processData['process_id'])) {
                $this->warn("    ⚠️ No se pudo obtener process_id del mapeo");
                return false;
            }
            
            // Buscar el proceso por code
            $process = Process::where('code', $processData['process_id'])->first();
            
            if (!$process) {
                $this->warn("    ⚠️ No se encontró proceso con código: {$processData['process_id']}");
                return false;
            }
            
            // Calcular el tiempo aplicando factor de corrección
            $rawTime = $processData['time'] ?? 0;
            $calculatedTime = $rawTime * $process->factor_correccion;
            
            // Verificar si ya existe este proceso para esta orden
            $existingProcess = OriginalOrderProcess::where('original_order_id', $order->id)
                                                 ->where('process_id', $process->id)
                                                 ->first();
            
            if ($existingProcess) {
                $this->line("    → Proceso {$process->code} ya existe para esta orden");
                return false;
            }
            
            // Crear el proceso
            OriginalOrderProcess::create([
                'original_order_id' => $order->id,
                'process_id' => $process->id,
                'time' => $calculatedTime,
                'created' => 0,
                'finished' => 0,
                'finished_at' => null
            ]);
            
            $this->line("    ✅ Proceso creado: {$process->code} (tiempo: {$rawTime} * {$process->factor_correccion} = {$calculatedTime})");
            
            return true;
            
        } catch (\Exception $e) {
            $this->error("    ❌ Error creando proceso: " . $e->getMessage());
            return false;
        }
    }
}
