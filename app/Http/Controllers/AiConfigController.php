<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiConfigRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AiConfigController extends Controller
{
    /**
     * Crea una nueva instancia del controlador.
     * Aplica el middleware de rol para restringir el acceso.
     */
    public function __construct()
    {
        // Requerir autenticación para todas las acciones
        $this->middleware('auth');
        // Requerir permiso específico para actualizar la configuración de IA (desactivado temporalmente)
        // $this->middleware('permission:ia-config.update')->only(['update']);
    }

    /**
     * Muestra el formulario de configuración de AI.
     */
    public function index(): View
    {
        $aiUrl = env('AI_URL', '');
        $aiToken = env('AI_TOKEN', '');

        return view('ia_prompts.config', compact('aiUrl', 'aiToken'));
    }

    /**
     * Actualiza la configuración de AI en el archivo .env.
     */
    public function update(AiConfigRequest $request): RedirectResponse
    {
        $aiUrl = $request->input('ai_url');
        $aiToken = $request->input('ai_token');

        $envPath = base_path('.env');
        $cacheCommandsExecuted = [];
        $cacheErrors = [];

        try {
            // Actualizar AI_URL
            $this->updateEnvironmentValue($envPath, 'AI_URL', $aiUrl);

            // Actualizar AI_TOKEN
            $this->updateEnvironmentValue($envPath, 'AI_TOKEN', $aiToken);

            // Ejecutar comandos de limpieza de cache después de actualizar exitosamente
            try {
                Artisan::call('cache:clear');
                $cacheCommandsExecuted[] = 'cache:clear';
                Log::info('Cache cleared successfully after AI config update');
            } catch (\Exception $e) {
                $cacheErrors[] = 'cache:clear: ' . $e->getMessage();
                Log::error('Error clearing cache after AI config update: ' . $e->getMessage());
            }

            try {
                Artisan::call('config:clear');
                $cacheCommandsExecuted[] = 'config:clear';
                Log::info('Config cleared successfully after AI config update');
            } catch (\Exception $e) {
                $cacheErrors[] = 'config:clear: ' . $e->getMessage();
                Log::error('Error clearing config after AI config update: ' . $e->getMessage());
            }

            try {
                Artisan::call('view:clear');
                $cacheCommandsExecuted[] = 'view:clear';
                Log::info('View cache cleared successfully after AI config update');
            } catch (\Exception $e) {
                $cacheErrors[] = 'view:clear: ' . $e->getMessage();
                Log::error('Error clearing view cache after AI config update: ' . $e->getMessage());
            }

            // Construir mensaje de éxito
            $successMessage = 'Configuración de AI actualizada correctamente.';
            
            if (!empty($cacheCommandsExecuted)) {
                $successMessage .= ' Se han ejecutado los siguientes comandos de limpieza: ' . implode(', ', $cacheCommandsExecuted) . '.';
            }

            // Si hay errores de cache, agregarlos como una advertencia
            if (!empty($cacheErrors)) {
                $successMessage .= ' Advertencia: algunos comandos de limpieza fallaron: ' . implode('; ', $cacheErrors);
            }

            Log::info('AI Config updated successfully by user: ' . (Auth::check() ? Auth::id() : 'unknown'));

            return redirect()->route('ia_prompts.index')->with('success', $successMessage);
            
        } catch (\Exception $e) {
            Log::error('Error updating AI config: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar la configuración: ' . $e->getMessage());
        }
    }

    /**
     * Actualiza o añade una variable específica en el archivo .env de manera robusta.
     *
     * @param string $envPath
     * @param string $key
     * @param string $value
     * @throws \Exception
     */
    private function updateEnvironmentValue(string $envPath, string $key, string $value): void
    {
        if (!file_exists($envPath)) {
            throw new \Exception("El archivo .env no existe.");
        }

        if (!is_writable($envPath)) {
            throw new \Exception("El archivo .env no es escribible.");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $updated = false;

        foreach ($lines as $index => $line) {
            // Verificar si la línea es la variable exacta (ignorando comentarios y espacios)
            if (preg_match("/^#?\s*{$key}\s*=\s*(.*)$/", $line, $matches)) {
                $lines[$index] = "{$key}={$value}";
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            // Añadir al final si no existe
            $lines[] = "{$key}={$value}";
        }

        $content = implode("\n", $lines) . "\n";

        if (file_put_contents($envPath, $content) === false) {
            throw new \Exception("Error al escribir en el archivo .env.");
        }
    }
}