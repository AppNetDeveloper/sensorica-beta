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
     * Obtener o crear configuración global (created_by = 0 para global)
     */
    public static function getGlobal($key, $default = null)
    {
        return Cache::remember("setting_global_{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('name', $key)
                ->where('created_by', 0)
                ->first();
            
            if ($setting) {
                return $setting->value;
            }
            
            // Fallback a .env si no existe en BD
            return env($key, $default);
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
     * Toggle boolean value
     */
    public static function toggleGlobal($key, $default = false)
    {
        $current = self::getGlobal($key, $default);
        $newValue = $current === 'true' || $current === true || $current === '1' ? 'false' : 'true';
        
        self::setGlobal($key, $newValue);
        
        return $newValue === 'true';
    }

    public function setting()
    {
      return $this->hasMany(\App\Models\LoginSecurity::class, 'id');
    }
}
