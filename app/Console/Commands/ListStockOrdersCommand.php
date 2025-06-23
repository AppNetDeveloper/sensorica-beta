<?php

// Define el espacio de nombres para la clase, una práctica estándar en Laravel para organizar el código.
namespace App\Console\Commands;

// Importa los modelos y traits necesarios para el comando.
use App\Models\OriginalOrder; // Modelo Eloquent para interactuar con la tabla 'original_orders'.
use App\Concerns\ConsoleLoggableCommand; // Un trait personalizado para facilitar el logging.
use Illuminate\Console\Command; // La clase base para todos los comandos de Artisan.
use Illuminate\Support\Facades\DB; // Fachada para interactuar con la base de datos directamente si es necesario.

// Define la clase del comando, que hereda de la clase base Command de Laravel.
class ListStockOrdersCommand extends Command
{
    // Utiliza el trait ConsoleLoggableCommand para añadir métodos de log (ej. logInfo, logError).
    use ConsoleLoggableCommand;

    /**
     * El nombre y la firma del comando de consola.
     * Este es el nombre que se usará para ejecutar el comando desde la terminal.
     * Ejemplo: php artisan orders:list-stock
     * @var string
     */
    protected $signature = 'orders:list-stock';

    /**
     * La descripción del comando de consola.
     * Esta descripción se muestra cuando se ejecuta 'php artisan list'.
     * @var string
     */
    protected $description = 'Lista todas las órdenes originales en stock, no finalizadas y no procesadas, procesando la secuencia más baja por grupo.';

    /**
     * Crea una nueva instancia del comando.
     * El constructor es llamado cuando se crea un objeto de esta clase.
     * @return void
     */
    public function __construct()
    {
        // Llama al constructor de la clase padre (Command) para asegurar la inicialización correcta.
        parent::__construct();
    }

    /**
     * Ejecuta la lógica del comando de consola.
     * Este es el método principal que se ejecuta cuando se llama al comando.
     * @return int Código de salida (0 para éxito, 1 para error).
     */
    public function handle()
    {
        // Registra un mensaje informativo al inicio de la ejecución.
        $this->logInfo('Iniciando la búsqueda de órdenes en stock...');

        try {
            // Inicia una consulta Eloquent para obtener las órdenes.
            $orders = OriginalOrder::where('in_stock', 1) // Condición: la orden debe estar en stock.
                ->whereNull('finished_at') // Condición: la orden no debe estar finalizada.
                ->where('processed', 0) // Condición: la orden no debe haber sido procesada previamente.
                ->with([
                    'customer', // Carga ansiosa (Eager Loading) de la relación con el cliente para evitar N+1 queries.
                    'orderProcesses.process', // Carga la relación 'orderProcesses' y, anidada, la relación 'process' de cada uno.
                    'orderProcesses.articles' // Carga la relación 'articles' de cada 'orderProcess'.
                ])
                ->orderBy('delivery_date', 'asc') // Ordena los resultados por fecha de entrega ascendente.
                ->get(); // Ejecuta la consulta y obtiene una colección de resultados.

            // Cuenta el número de órdenes encontradas.
            $count = $orders->count();
            // Registra cuántas órdenes se encontraron.
            $this->logInfo("Se encontraron {$count} órdenes en stock que no están finalizadas ni procesadas.");

            // Si no se encontraron órdenes, informa al usuario y termina la ejecución.
            if ($count === 0) {
                $this->info('No se encontraron órdenes que cumplan los criterios.');
                return 0; // Retorna 0 indicando que el comando se ejecutó sin errores.
            }

            // Muestra un encabezado en la consola.
            $this->info("\n=== ÓRDENES EN STOCK ===\n");
            
            // Itera sobre cada orden encontrada.
            foreach ($orders as $order) {
                // Llama a un método separado para procesar y mostrar la información de cada orden.
                $this->displayOrderInfo($order);
            }

            // Registra un mensaje indicando que el comando ha finalizado con éxito.
            $this->logInfo('Comando completado exitosamente.');
            return 0; // Retorna 0 para éxito.

        } catch (\Exception $e) {
            // Si ocurre cualquier excepción durante el proceso, se captura aquí.
            $errorMessage = 'Error buscando órdenes en stock: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine();
            // Registra el mensaje de error en el log.
            $this->logError($errorMessage);
            
            // Muestra el error en la consola para el usuario.
            $this->error($errorMessage);
            
            // Registra el stack trace completo para facilitar la depuración.
            $this->logError('Stack trace: ' . $e->getTraceAsString());
            
            return 1; // Retorna 1 indicando que ocurrió un error.
        }
    }

    /**
     * Guarda un mensaje MQTT en un archivo para su procesamiento posterior.
     * Simula la publicación en un topic MQTT.
     *
     * @param string $topic El topic al que se "publicaría" el mensaje.
     * @param string $message El contenido del mensaje (normalmente un JSON).
     * @return void
     */
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Prepara un array con los datos a guardar.
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(), // Añade una marca de tiempo actual.
            ];
            // Convierte el array a formato JSON.
            $jsonData = json_encode($data);
            // Sanitiza el nombre del topic para usarlo como parte del nombre de archivo (reemplaza '/' por '_').
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Genera un ID único basado en el tiempo actual en milisegundos para evitar colisiones de nombres.
            $uniqueId = round(microtime(true) * 1000);

            // Define la ruta y el nombre del archivo para el primer "servidor".
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            // Si el directorio no existe, lo crea recursivamente con los permisos adecuados.
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            // Escribe el contenido JSON en el archivo.
            file_put_contents($fileName1, $jsonData . PHP_EOL);

            // Repite el proceso para un segundo "servidor" (simulando redundancia).
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);

        } catch (\Exception $e) {
            // Si hay un error al escribir el archivo, lo registra en el log de Laravel y en el log del comando.
            \Log::error("Error al guardar el mensaje MQTT en archivo: " . $e->getMessage());
            $this->logError("Error al guardar el mensaje MQTT en archivo: " . $e->getMessage());
        }
    }

    /**
     * Genera la estructura JSON para un proceso y la "publica".
     *
     * @param \App\Models\OriginalOrder $order La orden padre.
     * @param \App\Models\OriginalOrderProcess $orderProcess El proceso específico a serializar.
     * @return array El JSON generado como un array asociativo.
     */
    protected function generateProcessJson($order, $orderProcess)
    {
        // Obtiene todos los procesos del mismo grupo para construir la descripción completa.
        $groupProcesses = \App\Models\OriginalOrderProcess::where('original_order_id', $order->id)
            ->where('grupo_numero', $orderProcess->grupo_numero)
            ->with('process') // Carga la relación 'process' para acceder a sus datos.
            ->get();
        
        // Mapea los procesos del grupo para obtener solo las descripciones.
        $processDescriptions = $groupProcesses->map(function($proc) {
            return $proc->process ? $proc->process->description : null;
        })->filter()->values()->toArray(); // Elimina nulos, reindexa y convierte a array.
        
        // Une todas las descripciones en un único string separado por comas.
        $formattedDescriptions = implode(', ', $processDescriptions);
        
        // Construye la estructura principal del array que se convertirá en JSON.
        $json = [
            'orderId' => (string)$order->order_id,
            'customerOrderId' => "",
            'customerReferenceId' => "",
            'barcode' => (string)\Illuminate\Support\Str::uuid(), // Genera un UUID único para el código de barras.
            'quantity' => 0,
            'unit' => 'Cajas',
            'isAuto' => 0,
            'theoretical_time' => (float)$orderProcess->time,
            'process_id' => $orderProcess->process_id,
            'process_code' => $orderProcess->process->code ?? '',
            'process_category' => $orderProcess->process->description ?? '',
            'delivery_date' => $order->delivery_date,
            'original_order_id' => $order->id,
            'original_order_process_id' => $orderProcess->id,
            'grupo_numero' => $orderProcess->grupo_numero,
            'processes_to_do' => $formattedDescriptions, // El string con todas las descripciones del grupo.
            'refer' => [
                '_id' => "",
                'company_name' => $order->customer ? $order->customer->name : 'N/A',
                'id' => $order->customer_id ? (string)$order->customer_id : "",
                'families' => "",
                'customerId' => $order->client_number ? $order->client_number : "",
                'eanCode' => (string)\Illuminate\Support\Str::uuid(),
                'rfidCode' => (string)\Illuminate\Support\Str::uuid(),
                'descrip' => $orderProcess->process ? $orderProcess->process->name : "",
                'value' => 0,
                'magnitude' => 'Masa',
                'envase' => $orderProcess->process ? $orderProcess->process->name : "",
                'envase_value' => "",
                'measure' => 'Kg',
                'groupLevel' => [
                    [
                        'id' => "",
                        'level' => 1,
                        'uds' => 0,
                        'total' => 0,
                        'measure' => 'Kg',
                        'eanCode' => "",
                        'envase' => ""
                    ]
                ],
                'standardTime' => [
                    [
                        'value' => 0,
                        'totalTime' => (float)$orderProcess->time,
                        'magnitude1' => 'Uds/hr',
                        'measure1' => 'uds',
                        'magnitude2' => "",
                        'measure2' => "",
                        'machineId' => []
                    ]
                ]
            ]
        ];
        
        // Convierte el array a JSON y luego lo decodifica de nuevo para asegurar un formato de array asociativo limpio.
        $jsonData = json_decode(json_encode($json), true);
        
        try {
            // Llama al método para "publicar" (guardar en archivo) el JSON del proceso.
            $this->publishMqttMessage('barcoder/prod_order_notice', json_encode($jsonData));
            // Actualiza el estado del proceso a 'created' = 1 para no volver a generarlo.
            $orderProcess->update(['created' => 1]);
            
            // Si la orden padre todavía no está marcada como procesada, la marca.
            if ($order->processed == 0) {
                $order->update(['processed' => 1]);
            }
            
        } catch (\Exception $e) {
            // Captura y muestra cualquier error durante la publicación o actualización.
            $this->error("Error al publicar el JSON del proceso o actualizar el estado: " . $e->getMessage());
        }
        
        // Devuelve el JSON generado.
        return $jsonData;
    }

    /**
     * Muestra la información de una orden individual, aplicando la lógica de agrupación.
     *
     * @param \App\Models\OriginalOrder $order La orden a procesar.
     * @return void
     */
    protected function displayOrderInfo($order)
    {
        try {
            // Pausa la ejecución por 500ms para no saturar el sistema de archivos (o el broker MQTT real).
            usleep(500000);
            
            // Publica un mensaje inicial con la información general de la orden.
            $this->publishMqttMessage('barcoder/prod_order_notice', json_encode([
                'order_id' => $order->id,
                'customer' => $order->customer ? $order->customer->name : 'N/A',
                'delivery_date' => $order->delivery_date ? $order->delivery_date->format('Y-m-d') : 'N/A',
                'timestamp' => now()->toDateTimeString()
            ]));
            
        } catch (\Exception $e) {
            $this->error("Error al publicar el aviso inicial de la orden: " . $e->getMessage());
        }

        // Muestra información básica de la orden en la consola.
        $this->info("\n" . str_repeat('=', 80));
        $this->info("ORDER ID: {$order->id} | Customer: " . ($order->customer ? $order->customer->name : 'N/A') . " | Delivery: " . ($order->delivery_date ? $order->delivery_date->format('Y-m-d') : 'N/A'));
        $this->info(str_repeat('-', 80));

        // Comprueba si la orden tiene procesos asociados.
        if ($order->orderProcesses->isNotEmpty()) {

            // --- INICIO DE LA LÓGICA DE AGRUPACIÓN MODIFICADA ---

            // 1. Agrupa todos los procesos de la orden por su 'grupo_numero'.
            // El resultado es una colección donde cada clave es un 'grupo_numero' y el valor es otra colección con los procesos de ese grupo.
            $groupsByGrupoNumero = $order->orderProcesses->groupBy('grupo_numero');

            // 2. Prepara una colección vacía donde se guardarán los procesos a ejecutar.
            $processesToExecute = collect();

            // 3. Itera sobre cada grupo de procesos (uno por cada 'grupo_numero').
            foreach ($groupsByGrupoNumero as $processesInGroup) {
                // 4. Dentro de cada grupo, ordena los procesos por la secuencia de su proceso relacionado (de menor a mayor).
                // Si un proceso no tiene secuencia, se le asigna 999 para que quede al final.
                // 'first()' obtiene solo el primer elemento de la colección ordenada, es decir, el de menor secuencia.
                $lowestSequenceProcessInGroup = $processesInGroup
                    ->sortBy(function ($orderProcess) {
                        return $orderProcess->process->sequence ?? 999;
                    })
                    ->first();
                
                // 5. Si se encontró un proceso (el grupo no estaba vacío), se añade a la colección final.
                if ($lowestSequenceProcessInGroup) {
                    $processesToExecute->push($lowestSequenceProcessInGroup);
                }
            }
            
            // --- FIN DE LA LÓGICA DE AGRUPACIÓN MODIFICADA ---

            // Muestra una tabla en la consola con los procesos seleccionados (uno por grupo).
            $this->info("\nPROCESOS A EJECUTAR (Secuencia más baja por cada grupo):");
            $this->table(
                ['Grupo', 'Pivot ID', 'Process ID', 'Code', 'Name', 'Sequence', 'Time', 'Created'],
                $processesToExecute->map(function ($orderProcess) {
                    return [
                        $orderProcess->grupo_numero,
                        $orderProcess->id,
                        $orderProcess->process_id,
                        $orderProcess->process->code ?? 'N/A',
                        $orderProcess->process->name ?? 'N/A',
                        $orderProcess->process->sequence ?? 'N/A',
                        $orderProcess->time,
                        $orderProcess->created ? 'Yes' : 'No',
                    ];
                })
            );

            // Itera sobre la colección final de procesos a ejecutar para generar su JSON y mostrar sus artículos.
            $processesJson = [];
            foreach ($processesToExecute as $orderProcess) {
                // Llama al método que genera y publica el JSON.
                $processJson = $this->generateProcessJson($order, $orderProcess);
                $processesJson[] = $processJson;
                
                // Si el proceso tiene artículos asociados, los muestra en otra tabla.
                if ($orderProcess->articles->isNotEmpty()) {
                    $this->info("\nArtículos para Proceso ID {$orderProcess->id} (Grupo: {$orderProcess->grupo_numero}, Nombre: " . ($orderProcess->process->name ?? 'N/A') . "):");
                    $this->table(
                        ['Código', 'Descripción', 'Grupo Artículo'],
                        $orderProcess->articles->map(function ($article) {
                            return [
                                $article->codigo_articulo,
                                $article->descripcion_articulo,
                                $article->grupo_articulo
                            ];
                        })
                    );
                }
            }
            
            // Si se generaron JSONs, los muestra en la consola.
            if (!empty($processesJson)) {
                $this->info("\nJSON GENERADO PARA MQTT:");
                foreach ($processesJson as $index => $json) {
                    $this->info("\n--- JSON para Proceso del Grupo " . ($json['grupo_numero'] ?? 'N/A') . " ---");
                    // Muestra el JSON con formato legible.
                    $this->info(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                }
            }
        } else {
            // Si la orden no tiene procesos, lo indica.
            $this->info("\nNo se encontraron procesos para esta orden.");
        }

        // Muestra una línea final para separar las órdenes.
        $this->info(str_repeat('=', 80) . "\n");
    }
}
