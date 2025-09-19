<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CleanOldSensorCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sensorcounts:clean {--days=30 : Days to keep, older rows will be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete rows in sensor_counts older than N days (default 30)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        if ($days <= 0) {
            $days = 30;
        }

        $threshold = now()->subDays($days);

        try {
            $this->info("Cleaning sensor_counts older than {$days} days (before {$threshold})...");

            $deleted = DB::table('sensor_counts')
                ->where('created_at', '<', $threshold)
                ->delete();

            $this->info("Deleted {$deleted} rows from sensor_counts.");
            Log::info('sensorcounts:clean completed', ['days' => $days, 'deleted' => $deleted]);
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error cleaning sensor_counts: ' . $e->getMessage());
            Log::error('sensorcounts:clean error', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }
    }
}
