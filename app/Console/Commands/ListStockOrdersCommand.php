<?php

namespace App\Console\Commands;

use App\Models\OriginalOrder;
use App\Concerns\ConsoleLoggableCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ListStockOrdersCommand extends Command
{
    use ConsoleLoggableCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:list-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all original orders that are in stock, not finished, and not processed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->logInfo('Starting to fetch stock orders...');

        try {
            // Query orders with the specified conditions and eager load all necessary relationships
            $orders = OriginalOrder::where('in_stock', 1)
                ->whereNull('finished_at')
                ->where('processed', 0)
                ->with([
                    'customer',
                    'processes', // This loads the processes with pivot data
                    'orderProcesses.process', // Load process with sequence
                    'orderProcesses.articles' // Load articles through orderProcesses relationship
                ])
                ->orderBy('delivery_date', 'asc')
                ->get();

            $count = $orders->count();
            $this->logInfo("Found {$count} orders in stock that are not finished and not processed.");

            if ($count === 0) {
                $this->info('No orders found matching the criteria.');
                return 0;
            }

            // Display orders in a table
            $this->info("\n=== ORDERS IN STOCK ===\n");
            
            foreach ($orders as $order) {
                $this->displayOrderInfo($order);
            }

            $this->logInfo('Command completed successfully.');
            return 0;

        } catch (\Exception $e) {
            $errorMessage = 'Error fetching stock orders: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
            // Log the error message
            $this->logError($errorMessage);
            
            // Display error to console
            $this->error($errorMessage);
            
            // Log the full stack trace as a separate error
            $this->logError('Stack trace: ' . $e->getTraceAsString());
            
            return 1;
        }
    }

    /**
     * Generate JSON structure for a process
     *
     * @param \App\Models\OriginalOrder $order
     * @param \App\Models\OriginalOrderProcess $orderProcess
     * @param int $processNumber
     * @return array
     */
    private function publishMqttMessage($topic, $message)
    {
        try {
            // Prepare data to store, including timestamp
            $data = [
                'topic'     => $topic,
                'message'   => $message,
                'timestamp' => now()->toDateTimeString(),
            ];
        
            // Convert to JSON
            $jsonData = json_encode($data);
        
            // Sanitize topic to avoid subfolder creation
            $sanitizedTopic = str_replace('/', '_', $topic);
            // Generate unique ID using microtime
            $uniqueId = round(microtime(true) * 1000); // milliseconds
        
            // Save to server 1
            $fileName1 = storage_path("app/mqtt/server1/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName1))) {
                mkdir(dirname($fileName1), 0755, true);
            }
            file_put_contents($fileName1, $jsonData . PHP_EOL);
        
            // Save to server 2
            $fileName2 = storage_path("app/mqtt/server2/{$sanitizedTopic}_{$uniqueId}.json");
            if (!file_exists(dirname($fileName2))) {
                mkdir(dirname($fileName2), 0755, true);
            }
            file_put_contents($fileName2, $jsonData . PHP_EOL);
        } catch (\Exception $e) {
            \Log::error("Error storing MQTT message in file: " . $e->getMessage());
        }
    }

    protected function generateProcessJson($order, $orderProcess, $processNumber = 1)
    {
        // Get all processes with the same order_id and grupo_numero
        $groupProcesses = \App\Models\OriginalOrderProcess::where('original_order_id', $order->id)
            ->where('grupo_numero', $orderProcess->grupo_numero)
            ->with('process') // Eager load the process relationship
            ->get();
        
        // Extract and format process descriptions
        $processDescriptions = $groupProcesses->map(function($proc) {
            return $proc->process ? $proc->process->description : null;
        })->filter()->values()->toArray();
        
        // Join descriptions with comma and space
        $formattedDescriptions = implode(', ', $processDescriptions);
        
        $json = [
            'orderId' => (string)$order->order_id,
            'customerOrderId' => "",
            'customerReferenceId' => "",
            'barcode' => (string)\Illuminate\Support\Str::uuid(),
            'quantity' => 0,
            'unit' => 'Cajas',
            'isAuto' => 0,
            'theoretical_time' => (float)$orderProcess->time,
            'process_id' => $orderProcess->process_id,
            'process_code' => $orderProcess->process->code ?? '',
            'process_category' => $orderProcess->process->description ?? '',
            'delivery_date' => $order->delivery_date,
            'original_order_id' => $order->id,
            'grupo_numero' => $orderProcess->grupo_numero,
            'processes_to_do' => $formattedDescriptions,
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
                        'value' => 0, // Default value set to 0
                        'totalTime' => (float)$orderProcess->time, // Original time value in new field
                        'magnitude1' => 'Uds/hr',
                        'measure1' => 'uds',
                        'magnitude2' => "",
                        'measure2' => "",
                        'machineId' => []
                    ]
                ]
            ]
        ];
        
        // Convert to JSON string and back to array to ensure proper encoding
        $jsonData = json_decode(json_encode($json), true);
        
        try {
            // Publish the process JSON to MQTT
            $this->publishMqttMessage('barcoder/prod_order_notice', json_encode($jsonData));
            
            // Mark this specific process as created
            $orderProcess->update(['created' => 1]);
            
            // Ensure the parent order is marked as processed
            if ($order->processed == 0) {
                $order->update(['processed' => 1]);
            }
            
        } catch (\Exception $e) {
            $this->error("Error publishing process JSON or updating status: " . $e->getMessage());
        }
        
        return $jsonData;
    }

    protected function displayOrderInfo($order)
    {
        try {
            // Add a small delay to prevent overwhelming the MQTT server
            usleep(500000); // 00ms delay
            
            // Publish MQTT message with order info
            $this->publishMqttMessage('barcoder/prod_order_notice', json_encode([
                'order_id' => $order->id,
                'customer' => $order->customer ? $order->customer->name : 'N/A',
                'delivery_date' => $order->delivery_date ? $order->delivery_date->format('Y-m-d') : 'N/A',
                'timestamp' => now()->toDateTimeString()
            ]));
            
            // Mark the order as processed
            $order->update(['processed' => 1]);
            // Note: We don't mark processes as created here anymore
            // Each process will be marked as created individually in generateProcessJson
            
        } catch (\Exception $e) {
            $this->error("Error updating order status: " . $e->getMessage());
        }

        // Display basic order info
        $this->info("\n" . str_repeat('=', 80));
        $this->info("ORDER ID: {$order->id} | Customer: " . ($order->customer ? $order->customer->name : 'N/A') . " | Delivery: " . ($order->delivery_date ? $order->delivery_date->format('Y-m-d') : 'N/A'));
        $this->info("MQTT Topic: barcoder/prod_order_notice");
        $this->info(str_repeat('-', 80));

        // Display processes with lowest sequence
        if ($order->orderProcesses->isNotEmpty()) {
            // Group processes by their sequence
            $groupedBySequence = $order->orderProcesses->groupBy(function ($item) {
                return $item->process->sequence ?? 999; // Default to high number if sequence is not set
            })->sortKeys();
            
            // Get the lowest sequence number
            $lowestSequence = $groupedBySequence->keys()->first();
            
            // Get all processes with the lowest sequence
            $lowestSequenceProcesses = $groupedBySequence->get($lowestSequence, collect());
            
            $this->info("\nPROCESSES (Lowest Sequence: {$lowestSequence}):");
            $this->table(
                ['Pivot ID', 'Process ID', 'Code', 'Name', 'Sequence', 'Time', 'Created', 'Finished', 'Finished At'],
                $lowestSequenceProcesses->map(function ($orderProcess) {
                    return [
                        $orderProcess->id,
                        $orderProcess->process_id,
                        $orderProcess->process->code ?? 'N/A',
                        $orderProcess->process->name ?? 'N/A',
                        $orderProcess->process->sequence ?? 'N/A',
                        $orderProcess->time,
                        $orderProcess->created ? 'Yes' : 'No',
                        $orderProcess->finished ? 'Yes' : 'No',
                        $orderProcess->finished_at ? $orderProcess->finished_at->format('Y-m-d H:i') : 'N/A'
                    ];
                })
            );

            // Display articles for each process with lowest sequence
            $processesJson = [];
            
            foreach ($lowestSequenceProcesses as $orderProcess) {
                // Generate JSON for this process
                $processJson = $this->generateProcessJson($order, $orderProcess);
                $processesJson[] = $processJson;
                
                if ($orderProcess->articles->isNotEmpty()) {
                    $this->info("\nArticles for Process ID {$orderProcess->id} (" . ($orderProcess->process->name ?? 'N/A') . "):");
                    $this->table(
                        ['Code', 'Description', 'Group'],
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
            
            // Display the generated JSON
            if (!empty($processesJson)) {
                $this->info("\nGENERATED JSON:");
                foreach ($processesJson as $index => $json) {
                    $this->info("\nProcess " . ($index + 1) . " JSON:");
                    $this->info(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                    
                    // Also show a more compact version for easier reading
                    $this->info("\nCompact JSON (copy-paste friendly):");
                    $this->info(str_replace('    ', ' ', json_encode($json)));
                }
            }
        } else {
            $this->info("\nNo processes found for this order.");
        }

        $this->info(str_repeat('=', 80) . "\n");
    }
}
