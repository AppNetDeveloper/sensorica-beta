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
     * @return array
     */
    protected function generateProcessJson($order, $orderProcess)
    {
        $json = [
            'orderId' => (string)$order->id,
            'customerOrderId' => "",
            'customerReferenceId' => "",
            'barcode' => (string)\Illuminate\Support\Str::uuid(),
            'quantity' => 0,
            'unit' => 'Cajas',
            'isAuto' => 0,
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
                        'value' => (float)$orderProcess->time,
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
        return json_decode(json_encode($json), true);
    }

    protected function displayOrderInfo($order)
    {
        // Display basic order info
        $this->info("\n" . str_repeat('=', 80));
        $this->info("ORDER ID: {$order->id} | Customer: " . ($order->customer ? $order->customer->name : 'N/A') . " | Delivery: " . ($order->delivery_date ? $order->delivery_date->format('Y-m-d') : 'N/A'));
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
