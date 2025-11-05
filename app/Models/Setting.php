<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'value', 'created_by'];

    /**
     * Obtener o crear configuración global (prioriza .env sobre BD)
     */
    public static function getGlobal($key, $default = null)
    {
        return Cache::remember("setting_global_{$key}", 3600, function () use ($key, $default) {
            // Prioridad 1: .env (si existe)
            $envValue = env($key, null);
            if ($envValue !== null) {
                return $envValue;
            }

            // Prioridad 2: Base de datos
            $setting = self::where('name', $key)
                ->where('created_by', 0)
                ->first();

            if ($setting) {
                return $setting->value;
            }

            // Prioridad 3: Default proporcionado
            return $default;
        });
    }

    /**
     * Establecer configuración global
     */
    public static function setGlobal($key, $value)
    {
        Cache::forget("setting_global_{$key}");
        
        return self::updateOrCreate(
            ['name' => $key, 'created_by' => 0],
            ['value' => $value]
        );
    }

    /**
     * Toggle boolean value (actualiza .env y BD)
     */
    public static function toggleGlobal($key, $default = false)
    {
        $current = self::getGlobal($key, $default);
        $newValue = $current === 'true' || $current === true || $current === '1' ? 'false' : 'true';

        // Actualizar .env
        self::updateEnvValue($key, $newValue);

        // Actualizar también la base de datos como respaldo
        self::setGlobal($key, $newValue);

        // Limpiar cache para forzar relectura
        Cache::forget("setting_global_{$key}");

        return $newValue === 'true';
    }

    /**
     * Actualizar o crear un valor en el archivo .env
     */
    public static function updateEnvValue($key, $value)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        // Buscar si la variable ya existe
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            // Reemplazar valor existente
            $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
        } else {
            // Añadir nueva variable al final
            $envContent .= "\n{$key}={$value}";
        }

        // Guardar el archivo
        file_put_contents($envPath, $envContent);

        // Limpiar todos los caches relacionados con configuración (¡ES CRUCIAL!)
        try {
            // Limpiar cache de configuración
            \Artisan::call('config:clear');

            // Limpiar cache de aplicación
            \Artisan::call('cache:clear');

            // Limpiar cache específico de settings
            \Cache::forget("setting_global_{$key}");

            // Forzar la recarga de dotenv
            if (function_exists('\Dotenv\Dotenv')) {
                $dotenv = \Dotenv\Dotenv::createMutable(base_path());
                $dotenv->load();
            }

            // Limpiar cualquier cache compilado
            if (file_exists(base_path('bootstrap/cache/config.php'))) {
                unlink(base_path('bootstrap/cache/config.php'));
            }

        } catch (\Exception $e) {
            // Si hay error limpiando cache, intentarlo con system como fallback
            if (function_exists('system')) {
                system('cd ' . base_path() . ' && php artisan config:clear > /dev/null 2>&1');
                system('cd ' . base_path() . ' && php artisan cache:clear > /dev/null 2>&1');
            }
        }
    }

    public function setting()
    {
      return $this->hasMany(\App\Models\LoginSecurity::class, 'id');
    }
}
