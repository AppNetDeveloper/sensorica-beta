<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Log;

trait LoggableCommand
{
    protected function logInfo($message)
    {
        Log::info($message);
        $this->info($message);
    }

    protected function logLine($message, $style = null, $verbosity = null)
    {
        Log::info($message);
        $this->line($message, $style, $verbosity);
    }

    protected function logWarning($message, $verbosity = null)
    {
        Log::warning($message);
        $this->warn($message, $verbosity);
    }

    protected function logError($message, $verbosity = null)
    {
        Log::error($message);
        $this->error($message, $verbosity);
    }
}
