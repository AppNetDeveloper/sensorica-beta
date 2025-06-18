<?php

namespace App\Concerns;

use Illuminate\Support\Facades\App;

trait ConsoleLoggableCommand
{
    /**
     * Prepara el mensaje con el formato de log estándar.
     * [timestamp] entorno.NIVEL: mensaje
     */
    private function formatMessage(string $level, string $message): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $environment = App::environment();

        return "[{$timestamp}] {$environment}.{$level}: {$message}";
    }

    /**
     * Muestra un mensaje de información en la consola con formato de log.
     */
    protected function logInfo($message)
    {
        $formattedMessage = $this->formatMessage('INFO', $message);
        $this->info($formattedMessage);
    }

    /**
     * Muestra una línea de texto en la consola con formato de log.
     */
    protected function logLine($message, $style = null, $verbosity = null)
    {
        // 'line' no tiene un nivel semántico como info o error,
        // pero lo formateamos con INFO para consistencia con el original.
        $formattedMessage = $this->formatMessage('INFO', $message);
        $this->line($formattedMessage, $style, $verbosity);
    }

    /**
     * Muestra un mensaje de advertencia en la consola con formato de log.
     */
    protected function logWarning($message, $verbosity = null)
    {
        $formattedMessage = $this->formatMessage('WARNING', $message);
        // El método `warn` en los comandos de Artisan ya usa un estilo de advertencia.
        $this->warn($formattedMessage, $verbosity);
    }

    /**
     * Muestra un mensaje de error en la consola con formato de log.
     */
    protected function logError($message, $verbosity = null)
    {
        $formattedMessage = $this->formatMessage('ERROR', $message);
        // El método `error` en los comandos de Artisan ya usa un estilo de error.
        $this->error($formattedMessage, $verbosity);
    }
}