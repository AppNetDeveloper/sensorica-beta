<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResetWeeklyCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:weekly-counts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset count_week_0 and count_week_1 to 0 every Monday at 00:00';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Resetting weekly counts...');

        while (true) {
            $now = Carbon::now();

            // Check if it is Monday and time is 00:00
            if ($now->isMonday() && $now->hour === 0 && $now->minute === 0) {
                // Reset counts in modbuses
                DB::table('modbuses')->update([
                    'count_week_0' => 0,
                    'count_week_1' => 0,
                ]);

                // Reset counts in sensors
                DB::table('sensors')->update([
                    'count_week_0' => 0,
                    'count_week_1' => 0,
                ]);

                $this->info('Weekly counts reset successfully.');
                // Wait for a minute to prevent multiple executions within the same minute
                sleep(60);
            }

            // Wait for a short duration before checking again
            sleep(10);
        }
    }
}
