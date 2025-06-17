<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OrderFieldMapping;
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
        // Obtener clientes con URLs configuradas y que tengan mapeos definidos
        $customers = Customer::whereNotNull('order_listing_url')
            ->whereNotNull('order_detail_url')
            ->whereHas('fieldMappings')
            ->with('fieldMappings')
            ->get();

        if ($customers->isEmpty()) {
            $this->warn('No se encontraron clientes con las URLs configuradas.');
            return 0;
        }

        $bar = $this->output->createProgressBar($customers->count());
        $bar->start();

        foreach ($customers as $customer) {
            try {
                $this->newLine();
                $this->info("Procesando cliente: {$customer->name}");
                
                // Llamar a la API para obtener los pedidos
                $response = Http::get($customer->order_listing_url);
                
                if (!$response->successful()) {
                    $this->error("Error al obtener pedidos para el cliente {$customer->name}: " . $response->status());
                    $bar->advance();
                    continue;
                }

                $orders = $response->json();
                
                if (empty($orders)) {
                    $this->warn("No se encontraron pedidos para el cliente: {$customer->name}");
                    $bar->advance();
                    continue;
                }

                $this->info(sprintf("Se encontraron %d pedidos para el cliente %s", count($orders), $customer->name));
                
                foreach ($orders as $order) {
                    try {
                        // Obtener el mapeo de campos para este cliente
                        $mappedData = [];
                        $hasErrors = false;
                        
                        foreach ($customer->fieldMappings as $mapping) {
                            $sourceValue = data_get($order, $mapping->source_field);
                            
                            // Verificar campos requeridos
                            if ($mapping->is_required && empty($sourceValue)) {
                                $this->warn("Campo requerido no encontrado: {$mapping->source_field}");
                                $hasErrors = true;
                                continue;
                            }
                            
                            // Aplicar transformaciones
                            $mappedData[$mapping->target_field] = $mapping->applyTransformations($sourceValue);
                        }
                        
                        if ($hasErrors) {
                            $this->warn('Pedido omitido por falta de campos requeridos');
                            continue;
                        }
                        
                        // Verificar si el pedido ya existe usando los campos mapeados
                        $query = OriginalOrder::query();
                        foreach ($mappedData as $field => $value) {
                            $query->where($field, $value);
                        }
                        
                        $exists = $query->exists();
                        $orderId = $mappedData['order_id'] ?? 'N/A';
                        
                        if ($exists) {
                            $this->line("<fg=green>✓</> El pedido {$orderId} ya existe en la base de datos");
                        } else {
                            $this->line("<fg=yellow>✗</> El pedido {$orderId} no existe en la base de datos");
                            // Aquí podríamos crear el pedido automáticamente si lo deseamos
                        }
                    } catch (\Exception $e) {
                        $this->error("Error procesando pedido: " . $e->getMessage());
                        Log::error('Error procesando pedido', [
                            'customer_id' => $customer->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("Error procesando cliente {$customer->name}: " . $e->getMessage());
                Log::error('Error en comando CheckOrdersFromApi', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Proceso completado.');
        
        return 0;
    }
}
