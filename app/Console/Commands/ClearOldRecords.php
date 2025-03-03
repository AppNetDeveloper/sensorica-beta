<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearOldRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:old-records';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old records from various tables based on the CLEAR_DB_DAY environment variable';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting cleanup process in a loop...');

        // Get the number of days from the environment variable
        $days = env('CLEAR_DB_DAY', 30); // Default to 30 days if not set

        while (true) {
            $this->info('Running cleanup cycle...');

            // Calculate the cutoff date
            $cutoffDate = Carbon::now()->subDays($days);

            // Tables to clean up with their respective date fields
            $tables = [
                'sensor_counts' => 'created_at',
                'control_weights' => 'created_at',
                'control_heights' => 'created_at',
                'downtime_sensors' => 'created_at',
                'live_traffic_monitors' => 'created_at',
            ];

            foreach ($tables as $table => $dateField) {
                $this->info("[" . Carbon::now()->toDateTimeString() . "]Cleaning up table: {$table}...");

                $deletedRows = DB::table($table)
                    ->where($dateField, '<', $cutoffDate)
                    ->delete();

                $this->info("[" . Carbon::now()->toDateTimeString() . "]Deleted {$deletedRows} rows from {$table}.");
            }

            $this->info('Cleanup cycle completed. Sleeping for 1 hour...');
            
            // Wait for 1 hour before the next cycle
            sleep(3600);
        }

        return Command::SUCCESS;
    }
}
