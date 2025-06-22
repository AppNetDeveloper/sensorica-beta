<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OrderFieldMapping;
use App\Models\Process;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderArticle;
use App\Concerns\ConsoleLoggableCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class CheckOrdersFromApi extends Command
{
    use ConsoleLoggableCommand;
    protected $signature = 'orders:check';
    protected $description = 'Verifica pedidos desde la API y los compara con la base de datos local';

    public function handle()
    {
        $this->logInfo('=== Iniciando verificación de pedidos desde API ===');
        $this->logLine('📊 Conectando a la base de datos...');
        
        // Conectarse a la DB y obtener clientes con las 2 URLs configuradas
        $customers = Customer::whereNotNull('order_listing_url')
            ->whereNotNull('order_detail_url')
            ->whereHas('fieldMappings')
            ->with('fieldMappings')
            ->get();

        if ($customers->isEmpty()) {
            $this->logWarning('❌ No se encontraron clientes con las URLs configuradas y mapeos definidos.');
            $this->logLine('💡 Verifica que los clientes tengan configuradas las URLs de API y mapeos de campos.');
            return 0;
        }

        $this->logInfo("✅ Encontrados {$customers->count()} clientes para procesar");
        
        foreach ($customers as $customer) {
            $this->newLine();
            $this->logInfo("=== Procesando cliente: {$customer->name} ===");
            $this->logLine("🔗 URL Listado: {$customer->order_listing_url}");
            $this->logLine("🔗 URL Detalle: {$customer->order_detail_url}");
            
            try {
                // Llamar al order_listing_url
                $this->logLine("🌐 Conectando a la API de listado de pedidos...");
                $response = Http::timeout(30)->get($customer->order_listing_url);
                
                if (!$response->successful()) {
                    $this->logError("❌ Error al obtener pedidos para el cliente {$customer->name}: HTTP {$response->status()}");
                    $this->logLine("📝 Registrando error en logs del sistema...");
                    Log::error("Error API para cliente {$customer->name}", [
                        'customer_id' => $customer->id,
                        'url' => $customer->order_listing_url,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    continue;
                }

                $this->logLine("✅ Respuesta exitosa de la API");
                $orders = $response->json();
                
                if (empty($orders)) {
                    $this->logWarning("⚠️ No se encontraron pedidos para el cliente: {$customer->name}");
                    $this->logLine("📝 Registrando ausencia de pedidos en logs...");
                    Log::info("Sin pedidos para cliente {$customer->name}");
                    continue;
                }

                $this->logInfo("📦 Encontrados " . count($orders) . " pedidos en la API");
                $this->logLine("🔄 Iniciando procesamiento de pedidos...");
                
                // Procesar cada pedido aplicando los mapeos
                foreach ($orders as $index => $order) {
                    $this->logInfo("--- Procesando pedido " . ($index + 1) . " ---");
                    
                    try {
                        $this->logLine("🔍 Aplicando mapeos de campos...");
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
                            
                            $this->logLine("  Mapeo: {$mapping->source_field} -> {$mapping->target_field} = '{$transformedValue}'");
                        }
                        
                        // Verificar si el order_id existe en la base de datos
                        if ($orderId) {
                            $this->logLine("🔍 Verificando si la orden {$orderId} existe en la base de datos...");
                            $existingOrder = OriginalOrder::where('order_id', $orderId)->first();
                            
                            if ($existingOrder) {
                                $this->logLine("✓ El order_id {$orderId} EXISTE en la base de datos (ID: {$existingOrder->id})", 'info');
                                $this->logLine("📝 Registrando orden existente en logs...");
                                
                                Log::info("Order ID {$orderId} ya existe", [
                                    'customer_id' => $customer->id,
                                    'customer_name' => $customer->name,
                                    'order_id' => $orderId,
                                    'existing_order_id' => $existingOrder->id
                                ]);
                                
                                // Verificar si necesitamos actualizar el campo in_stock
                                if ($existingOrder->in_stock == 0) {
                                    $this->logLine("🔍 Verificando si el pedido está en stock en la API...");
                                    
                                    // Buscar el campo in_stock en los datos mapeados (viene de la API)
                                    $inStockFromApi = $mappedData['in_stock'] ?? null;
                                    
                                    if ($inStockFromApi === 'Si' || $inStockFromApi === true) {
                                        $this->logLine("🔄 Actualizando in_stock a 1 para el pedido {$orderId}", 'info');
                                        $existingOrder->in_stock = 1;
                                        $existingOrder->save();
                                        $this->logLine("✅ Campo in_stock actualizado correctamente", 'info');
                                    }
                                }
                                
                                // Actualizar los detalles del pedido, no reprocesar procesos
                                $this->logLine("🔄 Actualizando detalles de la orden existente...");
                                $this->processOrderDetails($customer, $existingOrder, $orderId);
                                
                                $this->logLine("  ℹ️ Orden existente - no se reprocesan los procesos");
                                
                            } else {
                                $this->logLine("✗ El order_id {$orderId} NO EXISTE en la base de datos", 'comment');
                                $this->logLine("→ Procesando y creando nuevo pedido...", 'info');
                                $this->logLine("📝 Registrando nueva orden en logs...");
                                
                                try {
                                    // Agregar customer_id a los datos mapeados
                                    $mappedData['customer_id'] = $customer->id;
                                    
                                    $this->logLine("✅ Validando campos requeridos...");
                                    // Validar que tengamos los campos mínimos requeridos
                                    if (empty($mappedData['order_id'])) {
                                        $this->logWarning("  ⚠️ No se puede crear el pedido: falta order_id");
                                        continue;
                                    }
                                    
                                    // Crear el nuevo pedido en la base de datos
                                    $newOrder = OriginalOrder::create($mappedData);
                                    
                                    $this->logLine("  ✅ Pedido {$orderId} creado exitosamente (ID: {$newOrder->id})", 'info');
                                    
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
                                        $this->logWarning("  🗑️ Orden {$orderId} eliminada por falta de procesos válidos");
                                        continue; // Continuar con el siguiente pedido
                                    }
                                    
                                } catch (\Exception $e) {
                                    $this->logError("  ❌ Error creando pedido {$orderId}: " . $e->getMessage());
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
                            $this->logWarning("  No se pudo obtener order_id del mapeo");
                            Log::warning("Sin order_id para cliente {$customer->name}", [
                                'customer_id' => $customer->id,
                                'mapped_data' => $mappedData
                            ]);
                        }
                        
                    } catch (\Exception $e) {
                        $this->logError("Error procesando pedido: " . $e->getMessage());
                        $this->logLine("📝 Registrando error de procesamiento en logs...");
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
                $this->logError("Error procesando cliente {$customer->name}: " . $e->getMessage());
                $this->logLine("📝 Registrando error crítico en logs...");
                Log::error('Error procesando cliente', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->newLine();
        $this->info('=== Proceso completado exitosamente ===');
        $this->line('📊 Generando resumen de la ejecución...');
        $this->line('✅ Todos los clientes han sido procesados');
        $this->line('📝 Logs detallados disponibles en el sistema de logs de Laravel');
        $this->line('💡 Para revisar errores específicos, consulta los logs del sistema');
        $this->newLine();
        $this->line('🎉 Comando CheckOrdersFromApi finalizado correctamente');

                // ================================================================
        // INICIO DE LA MODIFICACIÓN: Llamar al segundo comando
        // ================================================================
        $this->newLine();
        $this->info('✅ Verificación de pedidos completada. Ejecutando ahora: orders:list-stock');

        try {
            // Ejecuta el comando 'orders:list-stock'
            Artisan::call('orders:list-stock');
            
            // Opcional: puedes capturar y mostrar la salida del comando ejecutado
            $salidaDelComando = Artisan::output();
            $this->line("--- Salida de orders:list-stock ---");
            $this->line($salidaDelComando);
            $this->line("------------------------------------");

        } catch (\Exception $e) {
            $this->error('❌ Ocurrió un error al ejecutar el comando orders:list-stock.');
            $this->error($e->getMessage());
            Log::error("Fallo al ejecutar orders:list-stock desde orders:check: " . $e->getMessage());
        }
        // ================================================================
        // FIN DE LA MODIFICACIÓN
        // ================================================================
        
        return 0;
    }

    private function processOrderDetails(Customer $customer, OriginalOrder $order, string $orderId)
    {
        try {
            // Construir la URL reemplazando {order_id} con el valor real
            $detailUrl = str_replace('{order_id}', $orderId, $customer->order_detail_url);
            
            $this->logInfo("  → Llamando a la API de detalles: {$detailUrl}");
            $this->logLine("  🌐 Enviando petición HTTP para obtener detalles...");
            $response = Http::timeout(30)->get($detailUrl);
            
            if (!$response->successful()) {
                $this->logError("  ❌ Error al obtener detalles del pedido {$orderId}: HTTP {$response->status()}");
                $this->logLine("  📝 Registrando error de API en logs...");
                Log::error("Error API para detalles de pedido {$orderId}", [
                    'customer_id' => $customer->id,
                    'order_id' => $orderId,
                    'url' => $detailUrl,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return;
            }

            $this->logLine("  ✅ Respuesta exitosa de la API de detalles");
            $orderDetails = $response->json();
            
            if (empty($orderDetails)) {
                $this->logWarning("  ⚠️ No se encontraron detalles para el pedido {$orderId}");
                $this->logLine("  📝 Registrando ausencia de detalles en logs...");
                return;
            }
            
            $this->logLine("  💾 Guardando detalles en la base de datos...");
            // Guardar todo el JSON en order_details
            $order->order_details = $orderDetails;
            $order->save();
            
            $this->logLine("  ✅ Detalles del pedido {$orderId} guardados exitosamente", 'info');
            $this->logLine("     Elementos encontrados: " . count($orderDetails));
            $this->logLine("  📝 Registrando éxito en logs del sistema...");
            
            Log::info("Detalles del pedido {$orderId} guardados", [
                'customer_id' => $customer->id,
                'order_id' => $orderId,
                'order_db_id' => $order->id,
                'details_count' => count($orderDetails)
            ]);
            
        } catch (\Exception $e) {
            $this->logError("  ❌ Error procesando detalles del pedido {$orderId}: " . $e->getMessage());
            $this->logLine("  📝 Registrando excepción en logs...");
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
        $this->logLine("  → Procesando detalles para crear procesos del pedido {$order->order_id}...");
        $this->logLine("  🔍 Analizando estructura de datos de la orden...");
        
        $orderDetails = $order->order_details;
        if (!is_array($orderDetails) || !isset($orderDetails['grupos'])) {
            $this->logWarning("    ⚠️ No se encontraron grupos en los detalles del pedido");
            $this->logLine("    📝 Estructura de datos inválida - registrando en logs...");
            Log::warning("Estructura de datos inválida para orden {$order->order_id}", [
                'customer_id' => $customer->id,
                'order_id' => $order->order_id,
                'order_details_structure' => is_array($orderDetails) ? array_keys($orderDetails) : 'not_array'
            ]);
            return 0;
        }
        
        $this->logLine("  ✅ Estructura válida encontrada con " . count($orderDetails['grupos']) . " grupos");
        $totalProcessesCreated = 0;
        
        foreach ($orderDetails['grupos'] as $index => $grupo) {
            $grupoNum = $grupo['grupoNum'] ?? ($index + 1);
            $this->logLine("\n    =======================================");
            $this->logLine("    🏷️  PROCESANDO GRUPO {$grupoNum}");
            $this->logLine("    =======================================");
            $this->logLine("    🔍 Contenido del grupo:");
            $this->logLine("       - Artículos: " . (isset($grupo['articulos']) ? count($grupo['articulos']) : '0'));
            $this->logLine("       - Servicios: " . (isset($grupo['servicios']) ? count($grupo['servicios']) : '0'));
            $this->logLine("    🔄 Iniciando procesamiento...");
            
            try {
                $processesCreated = $this->processGroupItems($customer, $grupo, $order);
                $totalProcessesCreated += $processesCreated;
                $this->logLine("    ✅ Procesamiento del grupo {$grupoNum} completado");
                $this->logLine("    📊 Procesos creados en este grupo: {$processesCreated}");
            } catch (\Exception $e) {
                $this->logError("    ❌ Error procesando grupo {$grupoNum}: " . $e->getMessage());
                Log::error("Error procesando grupo {$grupoNum}", [
                    'order_id' => $order->order_id,
                    'grupo' => $grupo,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            $this->logLine("    =======================================\n");
        }
        
        $this->logLine("  📊 Total de procesos creados en esta sesión: {$totalProcessesCreated}");
        
        // Verificar si la orden tiene al menos un proceso válido
        if ($totalProcessesCreated == 0) {
            $this->logLine("  🔍 Verificando procesos existentes en la base de datos...");
            $existingProcesses = $order->processes()->count();
            if ($existingProcesses == 0) {
                $this->logWarning("  ⚠️ La orden {$order->order_id} no tiene procesos válidos de fabricación");
                $this->logWarning("  🗑️ Eliminando orden {$order->order_id} por falta de procesos válidos...");
                $this->logLine("  📝 Registrando eliminación en logs...");
                
                try {
                    Log::warning("Eliminando orden sin procesos válidos", [
                        'customer_id' => $customer->id,
                        'order_id' => $order->order_id,
                        'order_db_id' => $order->id,
                        'reason' => 'no_valid_processes'
                    ]);
                    
                    $order->delete();
                    $this->logInfo("  ✅ Orden {$order->order_id} eliminada exitosamente");
                    return -1; // Indicador de que la orden fue eliminada
                } catch (\Exception $e) {
                    $this->logError("  ❌ Error al eliminar la orden {$order->order_id}: " . $e->getMessage());
                    $this->logLine("  📝 Registrando error de eliminación en logs...");
                    Log::error("Error eliminando orden {$order->order_id}", [
                        'customer_id' => $customer->id,
                        'order_id' => $order->order_id,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $this->logLine("  ℹ️ La orden {$order->order_id} ya tiene {$existingProcesses} procesos existentes");
                $this->logLine("  📝 Orden conservada por tener procesos previos...");
            }
        }
        
        $this->logInfo("  ✅ Procesos creados: {$totalProcessesCreated}");
        return $totalProcessesCreated;
    }
    
    private function processGroupItems(Customer $customer, array $grupo, OriginalOrder $order)
    {
        $processedItems = 0;
        $grupoNum = $grupo['grupoNum'] ?? 'N/A';
        
        $this->logLine("      🔍 Analizando estructura del grupo {$grupoNum}...");
        $this->logLine("      📝 Claves disponibles: " . implode(', ', array_keys($grupo)));
        
        foreach ($grupo as $key => $value) {
            if (is_array($value)) {
                $this->logLine("\n      → Analizando '{$key}' (" . count($value) . " elementos)");
                
                // Procesar servicios (procesos)
                if ($key === 'servicios' || strpos($key, 'servicio') !== false) {
                    $this->logLine("        🔧 Identificado como SERVICIOS - procesando como procesos...");
                    $this->logLine("        🔄 Iniciando procesamiento de " . count($value) . " servicios...");
                    $processedItems += $this->processServices($customer, $value, $key, $order, $grupo);
                    $this->logLine("        ✅ Procesados " . count($value) . " servicios en el grupo {$grupoNum}");
                }
                // Procesar artículos
                elseif ($key === 'articulos' || strpos($key, 'articulo') !== false) {
                    $this->logLine("        📦 Identificado como ARTÍCULOS - procesando como materiales...");
                    $this->logLine("        🔄 Iniciando procesamiento de " . count($value) . " artículos...");
                    $this->processArticles($customer, $value, $key, $order, $grupo);
                    $this->logLine("        ✅ Procesados " . count($value) . " artículos en el grupo {$grupoNum}");
                }
                else {
                    $this->logLine("        🔍 Verificando mapeos configurados para '{$key}'...");
                    // Verificar si hay mapeos configurados para este item (otros tipos)
                    $mappings = $customer->processFieldMappings->filter(function ($mapping) use ($key) {
                        return strpos($mapping->source_field, "grupos[*].{$key}[*].") === 0;
                    });
                    
                    if (!$mappings->isEmpty()) {
                        $this->logLine("        ✅ Encontrados " . $mappings->count() . " mapeos para '{$key}'");
                        $this->logLine("        🔄 Procesando elementos con mapeos personalizados...");
                        
                        foreach ($value as $index => $item) {
                            $this->logLine("        Procesando {$key}[{$index}]:");
                            $this->logLine("          🔍 Aplicando mapeos de proceso...");
                            $processData = $this->mapProcessData($customer, $item, $key);
                            if ($processData) {
                                $this->logLine("          ✅ Datos mapeados correctamente");
                                $created = $this->createOrderProcess($order, $processData);
                                if ($created) {
                                    $processedItems++;
                                    $this->logLine("          ✅ Proceso creado exitosamente");
                                } else {
                                    $this->logLine("          ❌ Error al crear el proceso");
                                }
                            } else {
                                $this->logLine("        ⚠️ No se pudo mapear el item");
                                $this->logLine("          📝 Registrando fallo de mapeo en logs...");
                            }
                        }
                    } else {
                        $this->logLine("        → No hay mapeos configurados para '{$key}'");
                        $this->logLine("          ℹ️ Elemento ignorado por falta de configuración");
                    }
                }
            } else {
                $this->logLine("      → '{$key}': valor simple (no es array) - ignorado");
            }
        }
        
        $this->logLine("      📊 Total de procesos procesados en este grupo: {$processedItems}");
        return $processedItems;
    }

    /**
     * Procesa servicios (procesos) de un grupo
     */
    private function processServices(Customer $customer, array $servicios, string $key, OriginalOrder $order, array $grupo)
    {
        $processedItems = 0;
        
        $this->logLine("        🔍 Verificando mapeos configurados para servicios...");
        $this->logLine("        🔍 Grupo actual: " . ($grupo['grupoNum'] ?? 'N/A') . ", Total de servicios: " . count($servicios));
        // Verificar si hay mapeos configurados para servicios
        $mappings = $customer->processFieldMappings->filter(function ($mapping) use ($key) {
            return strpos($mapping->source_field, "grupos[*].{$key}[*].") === 0;
        });
        
        if ($mappings->isEmpty()) {
            $this->logLine("        ⚠️ No hay mapeos configurados para servicios en '{$key}'. Se omitirán los servicios.");
            $this->logLine("        ℹ️ Asegúrate de configurar los mapeos de procesos en la configuración del cliente");
            return 0;
        }
        
        $this->logLine("        ✅ Encontrados " . $mappings->count() . " mapeos para '{$key}'");
        
        // Verificar si los servicios están en un array numérico o asociativo
        $isNumericArray = array_keys($servicios) === range(0, count($servicios) - 1);
        
        if (!$isNumericArray) {
            // Si es un array asociativo, convertirlo a numérico para procesarlo correctamente
            $servicios = [$servicios];
            $this->logLine("        🔄 Se detectó un array asociativo, convirtiendo a array numérico para procesar");
        }
        
        $this->logLine("        🔄 Iniciando procesamiento de " . count($servicios) . " servicios...");
        
        foreach ($servicios as $index => $servicio) {
            $this->logLine("        📋 Procesando servicio [{$index}]:");
            $this->logLine("          🔍 Datos del servicio: " . json_encode($servicio));
            $this->logLine("          🔍 Código de artículo: " . ($servicio['CodigoArticulo'] ?? 'No definido'));
            
            // Verificar si el servicio tiene un código de servicio
            if (!isset($servicio['CodigoArticulo'])) {
                $this->logLine("          ⚠️ El servicio no tiene un código de artículo, se omitirá");
                $this->logLine("          📝 Datos del servicio: " . json_encode($servicio));
                continue;
            }
            
            $this->logLine("          🔍 Aplicando mapeos de proceso...");
            $this->logLine("          🔄 Mapeando datos del servicio...");
            $processData = $this->mapProcessData($customer, $servicio, $key);
            $this->logLine("          🔄 Datos mapeados: " . json_encode($processData));
            
            if ($processData) {
                // Asegurarse de que el grupo esté incluido en los datos del proceso
                if (isset($grupo['grupoNum']) && !isset($processData['grupo_numero'])) {
                    $processData['grupo_numero'] = (string)$grupo['grupoNum'];
                    $this->logLine("          🔄 Añadiendo grupo_numero: " . $processData['grupo_numero']);
                }
                
                $this->logLine("          ✅ Datos mapeados correctamente");
                $this->logLine("          💾 Creando proceso en la base de datos...");
                
                $createdProcess = $this->createOrderProcess($order, $processData);
                
                if ($createdProcess && is_object($createdProcess)) {
                    $processedItems++;
                    $this->logLine("          ✅ Proceso creado exitosamente (ID: {$createdProcess->id})");
                    
                    // Procesar artículos asociados a este proceso si existen en el mismo grupo
                    if (isset($grupo['articulos']) && is_array($grupo['articulos'])) {
                        $this->logLine("          📦 Procesando " . count($grupo['articulos']) . " artículos asociados a este proceso...");
                        $articlesCreated = $this->processArticlesForProcess($customer, $grupo['articulos'], $createdProcess, $grupo);
                        $this->logLine("          ✅ Se crearon {$articlesCreated} artículos para este proceso");
                    } else {
                        $this->logLine("          ℹ️ No hay artículos asociados en este grupo");
                    }
                    
                    // Verificar si hay artículos en el grupo raíz
                    $orderDetails = $order->order_details;
                    if (isset($orderDetails['articulos']) && is_array($orderDetails['articulos'])) {
                        $this->logLine("          📦 Procesando artículos del grupo raíz...");
                        $articlesCreated = $this->processArticlesForProcess($customer, $orderDetails['articulos'], $createdProcess, $grupo);
                        $this->logLine("          ✅ Se crearon {$articlesCreated} artículos del grupo raíz");
                    }
                } else {
                    $this->logLine("          ❌ Error al crear el proceso");
                    $this->logLine("          📝 Registrando error en logs...");
                    Log::error("Error al crear proceso", [
                        'order_id' => $order->id,
                        'process_data' => $processData,
                        'grupo' => $grupo
                    ]);
                }
            } else {
                $this->logLine("          ⚠️ No se pudo mapear el servicio");
                $this->logLine("          📝 Datos del servicio no válidos o mapeo fallido");
                $this->logLine("          📝 Datos del servicio: " . json_encode($servicio));
            }
        }
        
        $this->logLine("        📊 Total de servicios procesados: {$processedItems}");
        return $processedItems;
    }

    /**
     * Procesa artículos independientes (sin asociar a un proceso específico)
     */
    private function processArticles(Customer $customer, array $articulos, string $key, OriginalOrder $order, array $grupo)
    {
        // Verificar si hay mapeos configurados para artículos
        $mappings = $customer->articleFieldMappings->filter(function ($mapping) use ($key) {
            return strpos($mapping->source_field, "grupos[*].{$key}[*].") === 0;
        });
        
        if (!$mappings->isEmpty()) {
            $this->line("        ✅ Encontrados " . $mappings->count() . " mapeos de artículos para '{$key}'");
            $this->line("        ⚠️ Artículos independientes detectados - se requiere asociación con proceso");
        } else {
            $this->line("        → No hay mapeos de artículos configurados para '{$key}'");
        }
    }

    /**
     * Procesa artículos asociados a un proceso específico
     */
    private function processArticlesForProcess(Customer $customer, array $articulos, $process, array $grupo)
    {
        $this->logLine("          🔍 Verificando mapeos configurados para artículos...");
        
        // Verificar si hay mapeos configurados para artículos
        $mappings = $customer->articleFieldMappings->filter(function ($mapping) {
            return strpos($mapping->source_field, "grupos[*].articulos[*].") === 0;
        });
        
        if ($mappings->isEmpty()) {
            $this->logLine("          → No hay mapeos de artículos configurados");
            $this->logLine("          ℹ️ Los artículos no se procesarán por falta de configuración");
            return 0;
        }
        
        $this->logLine("          ✅ Encontrados " . $mappings->count() . " mapeos de artículos");
        $this->logLine("          → Procesando " . count($articulos) . " artículos para el proceso {$process->id}");
        $createdArticles = 0;
        
        // Verificar si los artículos están en un array numérico o asociativo
        $isNumericArray = array_keys($articulos) === range(0, count($articulos) - 1);
        
        if (!$isNumericArray) {
            // Si es un array asociativo, convertirlo a numérico para procesarlo correctamente
            $articulos = [$articulos];
            $this->logLine("          🔄 Se detectó un array asociativo, convirtiendo a array numérico para procesar");
        }
        
        foreach ($articulos as $index => $articulo) {
            $this->logLine("            📦 Procesando artículo [{$index}]:");
            
            // Verificar si el artículo tiene un campo CodigoArticulo
            if (!isset($articulo['CodigoArticulo'])) {
                $this->logLine("              ⚠️ El artículo no tiene un código de artículo, se omitirá");
                $this->logLine("              📝 Datos del artículo: " . json_encode($articulo));
                continue;
            }
            
            $this->logLine("              🔍 Aplicando mapeos de artículo...");
            $articleData = $this->mapArticleData($customer, $articulo);
            
            if ($articleData) {
                // Asegurarse de que el grupo esté incluido en los datos del artículo
                if (isset($grupo['grupoNum']) && !isset($articleData['grupo_articulo'])) {
                    $articleData['grupo_articulo'] = (string)$grupo['grupoNum'];
                    $this->logLine("              🔄 Añadiendo grupo_articulo: " . $articleData['grupo_articulo']);
                }
                
                $this->logLine("              ✅ Datos de artículo mapeados correctamente");
                $this->logLine("              💾 Creando artículo en la base de datos...");
                
                $created = $this->createOrderArticle($process->id, $articleData, $grupo);
                
                if ($created) {
                    $createdArticles++;
                    $this->logLine("              ✅ Artículo creado exitosamente");
                } else {
                    $this->logLine("              ❌ Error al crear el artículo");
                    $this->logLine("              📝 Registrando error en logs...");
                    
                    Log::error("Error al crear artículo para el proceso", [
                        'process_id' => $process->id,
                        'article_data' => $articleData,
                        'grupo' => $grupo
                    ]);
                }
            } else {
                $this->logLine("              ⚠️ No se pudo mapear el artículo");
                $this->logLine("              📝 Datos del artículo no válidos o mapeo fallido");
                $this->logLine("              📝 Datos del artículo: " . json_encode($articulo));
            }
        }
        
        $this->logLine("          ✅ Artículos creados para proceso: {$createdArticles}");
        if ($createdArticles > 0) {
            $this->logLine("          📝 Registrando éxito de artículos en logs...");
        }
        
        return $createdArticles;
    }
    
    private function mapProcessData(Customer $customer, array $item, string $type)
    {
        $mappedData = [];
        $this->logLine("          🔎 Iniciando mapeo para tipo: {$type}");
        $this->logLine("          🔎 Total de mapeos disponibles: " . $customer->processFieldMappings->count());
        
        // Aplicar mapeos de campos de procesos
        foreach ($customer->processFieldMappings as $mapping) {
            $sourceField = $mapping->source_field;
            $this->logLine("          🔄 Procesando mapeo: {$sourceField} (Target: {$mapping->target_field})");
            
            // Verificar si el mapeo es para este tipo
            if (strpos($sourceField, "grupos[*].{$type}[*].") !== 0) {
                $this->logLine("          ⏩ Saltando - No coincide con el tipo actual");
                continue;
            }
            
            // Verificar si el mapeo es para este tipo
            if (strpos($sourceField, "grupos[*].{$type}[*].") === 0) {
                // Extraer el campo real del source_field
                $fieldName = str_replace("grupos[*].{$type}[*].", '', $sourceField);
                
                // Obtener el valor del item
                $sourceValue = data_get($item, $fieldName);
                
                // Si no encontramos el valor, intentar con el grupo específico
                if ($sourceValue === null && isset($item['GrupoNum'])) {
                    $grupoNum = $item['GrupoNum'];
                    $this->logLine("      🔄 Intentando con grupo específico: grupos[{$grupoNum}].{$type}[*].{$fieldName}");
                    $sourceValue = data_get($item, $fieldName);
                }
                
                if ($sourceValue !== null) {
                    // Aplicar transformaciones
                    $transformedValue = $mapping->applyTransformations($sourceValue);
                    
                    // Guardar el valor original
                    $mappedData[$mapping->target_field] = $transformedValue;
                    

                    
                    $this->logLine("      → Mapeo: {$fieldName} -> {$mapping->target_field} = '{$transformedValue}'");
                } else {
                    $this->logLine("      ⚠️ Campo '{$fieldName}' no encontrado en el item");
                    $this->logLine("      ℹ️ Estructura del item: " . json_encode(array_keys($item)));
                }
            } else {
                $this->logLine("      ⏭️ Mapeo no coincide con el tipo '{$type}': {$sourceField}");
            }
        }
        
        return !empty($mappedData) ? $mappedData : null;
    }
    
    private function createOrderProcess(OriginalOrder $order, array $processData)
    {
        try {
            // Verificar que tenemos process_id
            if (empty($processData['process_id'])) {
                $this->logWarning("    ⚠️ No se pudo obtener process_id del mapeo");
                return false;
            }
            
            // Usar el código de búsqueda si está disponible, de lo contrario usar el process_id normal
            // En createOrderProcess
            $processCode = $processData['process_id'];
            $this->logLine("    🔍 Buscando proceso con código: {$processCode}");
            
            // Buscar el proceso por code
            $process = Process::where('code', $processCode)->first();
            
            if (!$process) {
                $this->logWarning("    ⚠️ No se encontró proceso con código: {$processCode}");
                return false;
            }
            
            // Calcular el tiempo aplicando factor de corrección
            $rawTime = $processData['time'] ?? 0;
            $calculatedTime = $rawTime * $process->factor_correccion;
            
            // Verificar si ya existe este proceso para esta orden en el mismo grupo
            $existingProcess = OriginalOrderProcess::where('original_order_id', $order->id)
                                                 ->where('process_id', $process->id)
                                                 ->where('grupo_numero', $processData['grupo_numero'] ?? null)
                                                 ->first();
            
            if ($existingProcess) {
                $this->logLine("    → Proceso {$process->code} ya existe para esta orden en el grupo {$processData['grupo_numero']}");
                return false;
            }
            
            // Crear el proceso
            $createdProcess = OriginalOrderProcess::create([
                'original_order_id' => $order->id,
                'process_id' => $process->id,
                'time' => $calculatedTime,
                'created' => 0,
                'finished' => 0,
                'finished_at' => null
            ]);
            
            // Verificar que el proceso fue creado con ID
            if (!$createdProcess->id) {
                // Intentar obtener el proceso recién creado
                $createdProcess = OriginalOrderProcess::where('original_order_id', $order->id)
                    ->where('process_id', $process->id)
                    ->latest('id')
                    ->first();
            }
            
            $this->logLine("    ✅ Proceso creado: {$process->code} (tiempo: {$rawTime} * {$process->factor_correccion} = {$calculatedTime})");
            
            return $createdProcess;
            
        } catch (\Exception $e) {
            $this->logError("    ❌ Error creando proceso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapea los datos de un artículo usando los mapeos configurados
     */
    private function mapArticleData(Customer $customer, array $item)
    {
        $mappedData = [];
        
        foreach ($customer->articleFieldMappings as $mapping) {
            // Extraer el nombre del campo del source_field
            $fieldPath = $mapping->source_field;
            
            // Remover el prefijo "grupos[*].articulos[*]." para obtener el campo real
            $fieldName = str_replace('grupos[*].articulos[*].', '', $fieldPath);
            
            if (isset($item[$fieldName])) {
                $value = $item[$fieldName];
                
                // Aplicar transformación si existe
                $transformedValue = $mapping->applyTransformation($value);
                
                $mappedData[$mapping->target_field] = $transformedValue;
                
                $this->logLine("      → Mapeo: {$fieldName} -> {$mapping->target_field} = '{$transformedValue}'");
            }
        }
        
        return !empty($mappedData) ? $mappedData : null;
    }

    /**
     * Crea un artículo asociado a un proceso
     */
    private function createOrderArticle($processId, array $articleData, array $grupo = [])
    {
        try {
            $this->logLine("        🔍 Validando datos del artículo...");
            
            // Verificar que tenemos los datos mínimos requeridos
            if (empty($articleData['codigo_articulo'])) {
                $this->logWarning("        ⚠️ Artículo sin código - se omite");
                $this->logLine("        📝 Datos del artículo: " . json_encode($articleData));
                return false;
            }
            
            // Verificar que el processId es válido
            if (empty($processId)) {
                $this->logError("        ❌ ID de proceso inválido");
                return false;
            }
            
            $this->logLine("        🔍 Verificando si el artículo ya existe en este grupo...");
            
            // Construir la consulta para verificar duplicados
            $query = OriginalOrderArticle::where('original_order_process_id', $processId)
                ->where('codigo_articulo', $articleData['codigo_articulo']);
            
            // Si hay un grupo definido, incluirlo en la verificación de duplicados
            if (isset($articleData['grupo_articulo'])) {
                $query->where('grupo_articulo', $articleData['grupo_articulo']);
                $this->logLine("        🔍 Buscando duplicados para código: {$articleData['codigo_articulo']} en grupo: {$articleData['grupo_articulo']}");
            } else {
                $this->logLine("        🔍 Buscando duplicados para código: {$articleData['codigo_articulo']} (sin grupo específico)");
            }
            
            $existingArticle = $query->first();
            
            if ($existingArticle) {
                $grupoInfo = isset($articleData['grupo_articulo']) ? " en grupo: {$articleData['grupo_articulo']}" : "";
                $this->logLine("        → Artículo {$articleData['codigo_articulo']}{$grupoInfo} ya existe en este proceso (ID: {$existingArticle->id})");
                return false;
            }
            
            $this->logLine("        💾 Creando nuevo artículo...");
            
            // Crear el artículo con manejo de errores detallado
            try {
                $article = new OriginalOrderArticle([
                    'original_order_process_id' => $processId,
                    'codigo_articulo' => $articleData['codigo_articulo'],
                    'descripcion_articulo' => $articleData['descripcion_articulo'] ?? '',
                    'grupo_articulo' => $articleData['grupo_articulo'] ?? '',
                ]);
                
                if (!$article->save()) {
                    $this->logError("        ❌ Error al guardar el artículo");
                    $this->logLine("        📝 Errores: " . json_encode($article->getErrors()));
                    return false;
                }
                
                $this->logLine("        ✅ Artículo creado exitosamente: {$article->codigo_articulo} (ID: {$article->id})");
                return true;
                
            } catch (\Exception $e) {
                $this->logError("        ❌ Error al guardar el artículo: " . $e->getMessage());
                if (isset($e->errorInfo)) {
                    $this->logLine("        📝 Error SQL: " . json_encode($e->errorInfo));
                }
                return false;
            }
            
        } catch (\Exception $e) {
            $this->logError("        ❌ Error inesperado al crear artículo: " . $e->getMessage());
            $this->logLine("        📝 Trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
