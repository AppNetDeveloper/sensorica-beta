<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\OriginalOrder;
use App\Models\OrderFieldMapping;
use App\Models\Process;
use App\Models\OriginalOrderProcess;
use App\Models\OriginalOrderArticle;
use App\Models\RouteName;
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
        // Verificar si ya hay una instancia en ejecución usando un archivo de bloqueo
        $lockFile = storage_path('app/orders_check.lock');
        
        if (file_exists($lockFile)) {
            $lockTime = file_get_contents($lockFile);
            $lockAge = time() - (int)$lockTime;
            
            // Si el bloqueo tiene menos de 30 minutos, consideramos que otra instancia está en ejecución
            if ($lockAge < 1800) {
                $this->error('🔒 Ya existe una instancia del comando en ejecución desde hace ' . round($lockAge/60) . ' minutos');
                $this->line('Si crees que es un error, elimina manualmente el archivo: ' . $lockFile);
                return 1; // Salir con código de error
            } else {
                // El bloqueo es viejo (más de 30 minutos), probablemente un proceso que falló
                $this->error('⚠️ Se encontró un bloqueo antiguo (> 30 min). Eliminando automáticamente.');
                // Eliminar el archivo de bloqueo antiguo
                if (@unlink($lockFile)) {
                    $this->line('🗑️ Bloqueo antiguo eliminado. Continuando con la ejecución.');
                } else {
                    $this->error('❌ No se pudo eliminar el archivo de bloqueo antiguo. Verifica los permisos.');
                    $this->line('Intenta eliminar manualmente: ' . $lockFile);
                    return 1; // Salir con código de error si no se puede eliminar el bloqueo
                }
                // Continuamos y crearemos un nuevo bloqueo
            }
        }
        
        // Crear archivo de bloqueo con timestamp actual
        file_put_contents($lockFile, time());
        
        // Registrar función para eliminar el bloqueo al finalizar (incluso si hay errores)
        register_shutdown_function(function() use ($lockFile) {
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        });
        
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

                                // Verificar si la orden ya está finalizada
                                if ($existingOrder->finished_at) {
                                    $this->logLine("⏭️ La orden ya está finalizada (finished_at: {$existingOrder->finished_at}). Omitiendo verificaciones de stock y fecha de entrega.", 'info');
                                } else {
                                    // Verificar si necesitamos actualizar el campo in_stock
                                    // Buscar el campo in_stock en los datos mapeados (viene de la API)
                                    if (isset($mappedData['in_stock'])) {
                                        $inStockFromApi = $mappedData['in_stock'];
                                        $this->logLine("🔍 Verificando estado de stock en la API: {$inStockFromApi}");
                                        
                                        // Convertir a entero para comparación consistente
                                        if (is_string($inStockFromApi)) {
                                            // Si es string, convertir valores comunes a booleano
                                            $trueValues = ['yes', 'y', 'true', '1', 'ok', 'si', 'sí', 'Si'];
                                            $inStockFromApi = in_array(strtolower(trim($inStockFromApi)), $trueValues) ? 1 : 0;
                                        } else {
                                            // Si no es string, convertir a entero (0 o 1)
                                            $inStockFromApi = $inStockFromApi ? 1 : 0;
                                        }
                                        
                                        // Comparar con el valor actual
                                        if ($existingOrder->in_stock != $inStockFromApi) {
                                            $this->logLine("🔄 Actualizando in_stock de {$existingOrder->in_stock} a {$inStockFromApi} para el pedido {$orderId}", 'info');
                                            $existingOrder->in_stock = $inStockFromApi;
                                            $existingOrder->save();
                                            $this->logLine("✅ Campo in_stock actualizado correctamente", 'info');
                                        } else {
                                            $this->logLine("✓ Estado de stock sin cambios: {$existingOrder->in_stock}", 'info');
                                        }
                                    } else {
                                        $this->logLine("ℹ️ No se encontró información de stock en la API", 'info');
                                    }
                                    
                                    // Verificar si necesitamos actualizar la fecha de entrega
                                    if (isset($mappedData['delivery_date']) && $mappedData['delivery_date']) {
                                        $deliveryDateFromApi = $mappedData['delivery_date'];
                                        $currentDeliveryDate = $existingOrder->delivery_date ? $existingOrder->delivery_date->format('Y-m-d') : null;
                                        
                                        if ($deliveryDateFromApi != $currentDeliveryDate) {
                                            $this->logLine("🔄 Actualizando delivery_date de '{$currentDeliveryDate}' a '{$deliveryDateFromApi}' para el pedido {$orderId}", 'info');
                                            $existingOrder->delivery_date = $deliveryDateFromApi;
                                            $existingOrder->save();
                                            $this->logLine("✅ Campo delivery_date actualizado correctamente", 'info');
                                        }
                                    }
                                    
                                    // Verificar si necesitamos actualizar la fecha de pedido ERP
                                    if (isset($mappedData['fecha_pedido_erp'])) {
                                        $fechaPedidoErpFromApi = $mappedData['fecha_pedido_erp'];
                                        $currentFechaPedidoErp = $existingOrder->fecha_pedido_erp ? $existingOrder->fecha_pedido_erp->format('Y-m-d') : null;
                                        
                                        if ($fechaPedidoErpFromApi != $currentFechaPedidoErp) {
                                            $this->logLine("🔄 Actualizando fecha_pedido_erp de '{$currentFechaPedidoErp}' a '{$fechaPedidoErpFromApi}' para el pedido {$orderId}", 'info');
                                            $existingOrder->fecha_pedido_erp = $fechaPedidoErpFromApi;
                                            $existingOrder->save();
                                            $this->logLine("✅ Campo fecha_pedido_erp actualizado correctamente", 'info');
                                        }
                                    } else {
                                        $this->logLine("ℹ️ No se encontró información de fecha_pedido_erp en la API para el pedido {$orderId}", 'info');
                                    }

                                    // Sincronizar campos adicionales (Dirección, Teléfono, CIF/NIF, Referencia de Pedido)
                                    $additionalFields = [
                                        'address' => 'Dirección',
                                        'phone' => 'Teléfono',
                                        'cif_nif' => 'CIF/NIF',
                                        'ref_order' => 'Referencia de Pedido',
                                    ];

                                    $pendingUpdates = [];
                                    foreach ($additionalFields as $field => $label) {
                                        if (array_key_exists($field, $mappedData)) {
                                            $incomingValue = $mappedData[$field];

                                            if (is_string($incomingValue)) {
                                                $incomingValue = trim($incomingValue);
                                            }

                                            if ($incomingValue === '') {
                                                $incomingValue = null;
                                            }

                                            $currentValue = $existingOrder->{$field};

                                            if ($currentValue !== $incomingValue) {
                                                $currentLabel = $currentValue === null ? 'null' : $currentValue;
                                                $incomingLabel = $incomingValue === null ? 'null' : $incomingValue;
                                                $this->logLine("🔄 Actualizando {$label} de '{$currentLabel}' a '{$incomingLabel}' para el pedido {$orderId}", 'info');
                                                $pendingUpdates[$field] = $incomingValue;
                                            }
                                        }
                                    }

                                    if (!empty($pendingUpdates)) {
                                        $existingOrder->fill($pendingUpdates);
                                        $existingOrder->save();
                                        $this->logLine('✅ Campos adicionales actualizados correctamente', 'info');
                                    }
                                }

                                // Verificar y actualizar el stock de los artículos existentes
                                $this->logLine("🔍 Verificando stock de artículos para la orden {$orderId}...");
                                
                                // Pequeño sleep para distribuir la carga en el servidor
                                usleep(100000); // 100ms de pausa entre verificaciones de órdenes
                                
                                try {
                                    // Obtener los detalles de la orden desde la API
                                    $detailUrl = str_replace('{order_id}', $orderId, $customer->order_detail_url);
                                    $this->logLine("  🌐 Obteniendo detalles de la orden desde: {$detailUrl}");
                                    
                                    // Sleep adicional antes de cada llamada HTTP a la API
                                    usleep(100000); // 100ms de pausa antes de cada llamada HTTP
                                    
                                    $response = Http::timeout(30)->get($detailUrl);
                                    
                                    if (!$response->successful()) {
                                        $this->logError("  ❌ Error al obtener detalles del pedido {$orderId}: HTTP {$response->status()}");
                                        continue;
                                    }
                                    
                                    $orderDetails = $response->json();
                                    
                                    if ($orderDetails && isset($orderDetails['grupos'])) {
                                        $this->logLine("✅ Detalles de orden obtenidos correctamente");
                                        $totalArticulosActualizados = 0;
                                        
                                        // Recorrer los grupos y artículos
                                        foreach ($orderDetails['grupos'] as $grupo) {
                                            if (isset($grupo['articulos'])) {
                                                foreach ($grupo['articulos'] as $articulo) {
                                                    try {
                                                        // Verificar que el artículo tiene código
                                                        if (!isset($articulo['CodigoArticulo'])) {
                                                            continue;
                                                        }
                                                        
                                                        // Mapear los datos del artículo
                                                        $articleData = $this->mapArticleData($customer, $articulo);
                                                        
                                                        if ($articleData) {
                                                            // Si no existe in_stock en los datos, asumimos que está en stock (1)
                                                            if (!isset($articleData['in_stock'])) {
                                                                $articleData['in_stock'] = 1;
                                                                $this->logLine("ℹ️ No se encontró información de stock para el artículo {$articleData['codigo_articulo']}, asumiendo en stock (1)");
                                                            }
                                                            
                                                            // Buscar el artículo en la base de datos
                                                            $existingArticle = OriginalOrderArticle::where('codigo_articulo', $articleData['codigo_articulo'])
                                                                ->whereHas('originalOrderProcess', function($query) use ($existingOrder) {
                                                                    $query->where('original_order_id', $existingOrder->id);
                                                                })
                                                                ->first();
                                                            
                                                            if ($existingArticle && $existingArticle->in_stock !== $articleData['in_stock']) {
                                                                $this->logLine("🔄 Actualizando stock del artículo {$articleData['codigo_articulo']} de {$existingArticle->in_stock} a {$articleData['in_stock']}");
                                                                $existingArticle->in_stock = $articleData['in_stock'];
                                                                $existingArticle->save();
                                                                $totalArticulosActualizados++;
                                                            }
                                                        }
                                                    } catch (\Exception $e) {
                                                        $this->logError("❌ Error al procesar artículo: " . $e->getMessage());
                                                        // Continuamos con el siguiente artículo
                                                        continue;
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if ($totalArticulosActualizados > 0) {
                                            $this->logLine("✅ Se actualizó el stock de {$totalArticulosActualizados} artículos", 'info');
                                        } else {
                                            $this->logLine("ℹ️ No se encontraron cambios en el stock de los artículos", 'info');
                                        }
                                    } else {
                                        $this->logLine("⚠️ No se pudieron obtener detalles de la orden para verificar stock de artículos", 'warning');
                                    }
                                } catch (\Exception $e) {
                                    $this->logError("❌ Error al verificar stock de artículos: " . $e->getMessage());
                                    // Continuamos con el resto del proceso
                                }
                                
                                // Actualizar los detalles del pedido, no reprocesar procesos
                                $this->logLine("🔄 Actualizando detalles de la orden existente...");
                                $this->processOrderDetails($customer, $existingOrder, $orderId);
                                
                                $this->logLine("  ℹ️ Orden existente - no se reprocesan los procesos");
                                
                            } else {
                                $this->logLine("✗ El order_id {$orderId} NO EXISTE en la base de datos", 'comment');
                                $this->logLine("→ Procesando y creando nuevo pedido...", 'info');
                                $this->logLine("📝 Registrando nueva orden en logs...");
                                
                                // Pequeño sleep para distribuir la carga en el servidor durante la creación
                                usleep(100000); // 100ms de pausa entre creaciones de órdenes
                                
                                try {
                                    // Agregar customer_id a los datos mapeados
                                    $mappedData['customer_id'] = $customer->id;
                                    
                                    $this->logLine("✅ Validando campos requeridos...");
                                    // Validar que tengamos los campos mínimos requeridos
                                    if (empty($mappedData['order_id'])) {
                                        $this->logWarning("  ⚠️ No se puede crear el pedido: falta order_id");
                                        continue;
                                    }
                                    
                                    // Verificar el campo fecha_pedido_erp
                                    if (!isset($mappedData['fecha_pedido_erp'])) {
                                        $this->logLine("  ℹ️ No se encontró información de fecha_pedido_erp en la API para el pedido {$orderId}, se establecerá como null", 'info');
                                        // Asegurarnos de que el campo sea null explícitamente
                                        $mappedData['fecha_pedido_erp'] = null;
                                    } else {
                                        $this->logLine("  ✅ Campo fecha_pedido_erp encontrado: '{$mappedData['fecha_pedido_erp']}'", 'info');
                                    }
                                    
                                    // Resolver route_name_id si se proporciona en los datos mapeados
                                    if (isset($mappedData['route_name']) && !empty($mappedData['route_name'])) {
                                        $this->logLine("  🛣️ Procesando route_name: '{$mappedData['route_name']}'");
                                        $routeNameId = $this->resolveRouteName($customer, $mappedData['route_name']);
                                        $mappedData['route_name_id'] = $routeNameId;
                                        // Remover route_name del array ya que no es un campo de la tabla
                                        unset($mappedData['route_name']);
                                    }
                                    
                                    // Crear el nuevo pedido en la base de datos
                                    $newOrder = OriginalOrder::create($mappedData);
                                    
                                    $this->logLine("  ✅ Pedido {$orderId} creado exitosamente (ID: {$newOrder->id})", 'info');

                                    
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
        // LIMPIEZA DE ÓRDENES SIN PROCESOS
        // ================================================================
        $this->newLine();
        $this->info('🧽 Iniciando limpieza de órdenes sin procesos asociados...');
        
        try {
            // Buscar órdenes sin procesos asociados y que no estén procesadas
            $ordersWithoutProcesses = OriginalOrder::where('processed', 0)
                ->whereDoesntHave('processes')
                ->get();
            $count = $ordersWithoutProcesses->count();
            $this->logLine("Consulta optimizada: solo revisando órdenes con processed=0");
            
            if ($count > 0) {
                $this->line("🗑️ Encontradas {$count} órdenes sin procesos asociados");
                
                // Registrar en logs antes de eliminar
                $orderIds = $ordersWithoutProcesses->pluck('order_id')->toArray();
                $this->logLine("Eliminando órdenes sin procesos: " . implode(', ', $orderIds));
                
                // Eliminar las órdenes
                foreach ($ordersWithoutProcesses as $order) {
                    $this->logLine("  🗑️ Eliminando orden {$order->order_id} (ID: {$order->id})");
                    $order->delete();
                }
                
                $this->info("✅ Se eliminaron {$count} órdenes sin procesos asociados");
            } else {
                $this->line("ℹ️ No se encontraron órdenes sin procesos asociados");
            }
        } catch (\Exception $e) {
            $this->error("❌ Error al limpiar órdenes sin procesos: " . $e->getMessage());
            Log::error('Error al limpiar órdenes sin procesos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

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
        // ================================================================
        // Eliminar el archivo de bloqueo al finalizar (complementa el registro de shutdown_function)
        $this->line('🔓 Eliminando archivo de bloqueo...');
        try{
            if (file_exists($lockFile)) {
                unlink($lockFile);
                $this->line('✅ Archivo de bloqueo eliminado con éxito.');
            } else {
                $this->line('ℹ️ El archivo de bloqueo ya no existe.');
            }
        }catch(\Exception $e){
            $this->error('❌ Ocurrió un error al eliminar el archivo .lock.');
            $this->error($e->getMessage());
            Log::error("Fallo al eliminar el archivo .lock desde orders:check: " . $e->getMessage());
            
            // Intento alternativo: usar otros métodos para eliminar el archivo
            try{
                // Intentar con system para usar comandos del sistema
                system("rm -f {$lockFile}");
                
                // Verificar si el archivo fue eliminado
                if (!file_exists($lockFile)) {
                    $this->line('✅ Archivo de bloqueo eliminado con éxito usando comando del sistema.');
                } else {
                    // Último recurso: cambiar permisos y luego eliminar
                    @chmod($lockFile, 0777); // Dar todos los permisos
                    if (@unlink($lockFile)) {
                        $this->line('✅ Archivo de bloqueo eliminado con éxito después de cambiar permisos.');
                    } else {
                        $this->error('❌ No se pudo eliminar el archivo de bloqueo.');
                        Log::error("No se pudo eliminar el archivo de bloqueo después de múltiples intentos.");
                    }
                }
            }catch(\Exception $e2){
                $this->error('❌ Error en el intento alternativo de eliminación.');
                $this->error($e2->getMessage());
                Log::error("Segundo fallo al gestionar el archivo .lock: " . $e2->getMessage());
            }
        }
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
            $processCreateData = [
                'original_order_id' => $order->id,
                'process_id' => $process->id,
                'time' => $calculatedTime,
                'grupo_numero'      => $processData['grupo_numero'] ?? null,
                'created' => 0,
                'finished' => 0,
                'finished_at' => null
            ];
            
            // Añadir campos box y units_box (con valor por defecto 0 si no están mapeados)
            if (isset($processData['box'])) {
                $processCreateData['box'] = $processData['box'];
                $this->logLine("    📦 Campo 'box' mapeado: {$processData['box']}");
            } else {
                $processCreateData['box'] = 0;
                $this->logLine("    📦 Campo 'box' no mapeado, usando valor por defecto: 0");
            }
            
            if (isset($processData['units_box'])) {
                $processCreateData['units_box'] = $processData['units_box'];
                $this->logLine("    📦 Campo 'units_box' mapeado: {$processData['units_box']}");
            } else {
                $processCreateData['units_box'] = 0;
                $this->logLine("    📦 Campo 'units_box' no mapeado, usando valor por defecto: 0");
            }
            
            // Añadir campo number_of_pallets (con valor 0 si no está mapeado)
            if (isset($processData['number_of_pallets'])) {
                $processCreateData['number_of_pallets'] = $processData['number_of_pallets'];
                $this->logLine("    🔢 Campo 'number_of_pallets' mapeado: {$processData['number_of_pallets']}");
            } else {
                $processCreateData['number_of_pallets'] = 0;
                $this->logLine("    🔢 Campo 'number_of_pallets' no mapeado, usando valor por defecto: 0");
            }
            
            $createdProcess = OriginalOrderProcess::create($processCreateData);
            
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
            
            $this->logLine("        🔍 Verificando si el artículo ya existe en este proceso...");
            
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
                
                // Verificar si necesitamos actualizar el campo in_stock
                if (isset($articleData['in_stock']) && $existingArticle->in_stock !== $articleData['in_stock']) {
                    try {
                        $this->logLine("        🔄 Actualizando estado de stock del artículo de {$existingArticle->in_stock} a {$articleData['in_stock']}");
                        $existingArticle->in_stock = $articleData['in_stock'];
                        $existingArticle->save();
                        $this->logLine("        ✅ Estado de stock del artículo actualizado correctamente");
                        return true; // Retornamos true porque hemos actualizado el artículo
                    } catch (\Exception $e) {
                        $this->logError("        ❌ Error al actualizar el stock del artículo: " . $e->getMessage());
                        return false;
                    }
                }
                
                return false; // No se actualizó nada
            }
            
            $this->logLine("        💾 Creando nuevo artículo...");
            
            // Si no existe in_stock en los datos, asumimos que está en stock (1)
            if (!isset($articleData['in_stock'])) {
                $articleData['in_stock'] = 1;
                $this->logLine("        ℹ️ No se encontró información de stock para el artículo, asumiendo en stock (1)");
            }
            
            // Crear el artículo con manejo de errores detallado
            try {
                $article = new OriginalOrderArticle([
                    'original_order_process_id' => $processId,
                    'codigo_articulo' => $articleData['codigo_articulo'],
                    'descripcion_articulo' => $articleData['descripcion_articulo'] ?? '',
                    'grupo_articulo' => $articleData['grupo_articulo'] ?? '',
                    'in_stock' => $articleData['in_stock'] ?? 1, // Ya hemos asegurado que existe este valor
                ]);
                
                if (!$article->save()) {
                    $this->logError("        ❌ Error al guardar el artículo");
                    if (method_exists($article, 'getErrors')) {
                        $this->logLine("        📝 Errores: " . json_encode($article->getErrors()));
                    }
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

    /**
     * Resuelve o crea un RouteName basado en el nombre proporcionado
     */
    private function resolveRouteName(Customer $customer, ?string $routeName): ?int
    {
        if (empty($routeName)) {
            return null;
        }

        // Buscar ruta existente para este cliente
        $route = RouteName::where('customer_id', $customer->id)
            ->where('name', $routeName)
            ->first();

        if (!$route) {
            // Crear nueva ruta
            $route = RouteName::create([
                'customer_id' => $customer->id,
                'name' => $routeName,
                'note' => 'Creada automáticamente desde comando CheckOrdersFromApi',
                'days_mask' => 0, // Sin días específicos por defecto
                'active' => true,
            ]);

            $this->logLine("  ✅ Nueva ruta creada: '{$routeName}' (ID: {$route->id})");
        } else {
            $this->logLine("  ✓ Ruta existente encontrada: '{$routeName}' (ID: {$route->id})");
        }

        return $route->id;
    }
}
