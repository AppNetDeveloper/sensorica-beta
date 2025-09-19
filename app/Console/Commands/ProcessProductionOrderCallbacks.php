<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\ProductionOrderCallback;
use Throwable;

class ProcessProductionOrderCallbacks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'callbacks:process {--once : Process a single cycle then exit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending production order callbacks and dispatch HTTP requests with retries.';

    /**
     * Max attempts before giving up (configurable via env CALLBACK_MAX_ATTEMPTS)
     * @var int
     */
    protected int $maxAttempts;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->maxAttempts = (int) env('CALLBACK_MAX_ATTEMPTS', 20);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting callbacks processor');

        // Run one cycle and exit if --once provided
        if ($this->option('once')) {
            $this->processCycle();
            return 0;
        }

        // Endless loop, supervised by Supervisor
        while (true) {
            try {
                $this->processCycle();
            } catch (Throwable $e) {
                Log::error('callbacks:process unhandled error', ['error' => $e->getMessage()]);
            }

            // Sleep 10 seconds before next cycle
            sleep(10);
        }
    }

    /**
     * Process a single cycle: pick pending or retryable callbacks and send them
     */
    protected function processCycle(): void
    {
        $now = now();

        // Simple backoff schedule (seconds) by attempts index: 0,1,2,3,4+
        $backoff = [0 => 0, 1 => 30, 2 => 60, 3 => 300, 4 => 900];

        // Fetch a batch of callbacks: status 0 (pending) or 2 (error) that are due for retry
        $callbacks = ProductionOrderCallback::query()
            ->where(function ($q) use ($now, $backoff) {
                // Pending
                $q->where('status', 0)
                  ->where('attempts', '<', $this->maxAttempts)
                  ->orWhere(function ($q2) use ($now, $backoff) {
                      // Error/retryable
                      $q2->where('status', 2)
                         ->where('attempts', '<', $this->maxAttempts)
                         ->where(function ($q3) use ($now, $backoff) {
                             // Due based on backoff: attempts determine wait time
                             $q3->whereNull('last_attempt_at')
                                ->orWhereRaw('TIMESTAMPDIFF(SECOND, last_attempt_at, ?) >= CASE 
                                    WHEN attempts >= 4 THEN ?
                                    WHEN attempts = 3 THEN ?
                                    WHEN attempts = 2 THEN ?
                                    WHEN attempts = 1 THEN ?
                                    ELSE 0 END', [
                                        $now,
                                        $backoff[4], $backoff[3], $backoff[2], $backoff[1]
                                ]);
                         });
                  });
            })
            ->orderBy('id')
            ->limit(50)
            ->get();

        if ($callbacks->isEmpty()) {
            $this->line('No pending callbacks');
            return;
        }

        foreach ($callbacks as $cb) {
            $this->processOne($cb);
        }
    }

    /**
     * Process a single callback row
     */
    protected function processOne(ProductionOrderCallback $cb): void
    {
        $cb->attempts = ($cb->attempts ?? 0) + 1;
        $cb->last_attempt_at = now();
        $cb->save();

        // Stop processing once attempts reach 20
        if ($cb->attempts >= $this->maxAttempts) {
            $cb->status = 2; // keep as error but capped
            $cb->error_message = 'Max attempts reached (' . $this->maxAttempts . '). Callback ignored until manual intervention.';
            $cb->save();
            Log::warning('Callback reached max attempts and will be ignored', ['id' => $cb->id]);
            return;
        }

        try {
            $response = Http::timeout(10)->acceptJson()->asJson()->post($cb->callback_url, $cb->payload ?? []);

            if ($response->successful()) {
                $cb->status = 1; // success
                $cb->success_at = now();
                $cb->error_message = null;
                $cb->save();
                Log::info('Callback success', ['id' => $cb->id, 'url' => $cb->callback_url]);
                return;
            }

            // Non-2xx considered error
            $cb->status = 2; // error/retry
            $cb->error_message = 'HTTP ' . $response->status() . ' - ' . substr($response->body(), 0, 500);
            $cb->save();
            Log::warning('Callback failed HTTP', ['id' => $cb->id, 'status' => $response->status()]);
        } catch (Throwable $e) {
            $cb->status = 2; // error/retry
            $cb->error_message = substr($e->getMessage(), 0, 500);
            $cb->save();
            Log::error('Callback exception', ['id' => $cb->id, 'error' => $e->getMessage()]);
        }
    }
}
