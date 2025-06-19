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

    protected function displayOrderInfo($order)
    {
        // Display basic order info
        $this->info("\n" . str_repeat('=', 80));
        $this->info("ORDER ID: {$order->id} | Customer: " . ($order->customer ? $order->customer->name : 'N/A') . " | Delivery: " . ($order->delivery_date ? $order->delivery_date->format('Y-m-d') : 'N/A'));
        $this->info(str_repeat('-', 80));

        // Display processes
        if ($order->orderProcesses->isNotEmpty()) {
            $this->info("\nPROCESSES:");
            $this->table(
                ['Pivot ID', 'Process ID', 'Code', 'Name', 'Sequence', 'Time', 'Created', 'Finished', 'Finished At'],
                $order->orderProcesses->map(function ($orderProcess) {
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

            // Display articles for each process
            foreach ($order->orderProcesses as $orderProcess) {
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
        } else {
            $this->info("\nNo processes found for this order.");
        }

        $this->info(str_repeat('=', 80) . "\n");
    }
}
