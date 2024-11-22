<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MonitorConnection extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'name',
        'mqtt_topic',
        'production_line_id',
        'last_status',
    ];

    /**
     * Relación con la línea de producción.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
    public function statuses()
    {
        return $this->hasMany(MonitorConnectionStatus::class);
    }

    /**
     * Boot del modelo para manejar eventos.
     */
    protected static function boot()
    {
        parent::boot();

        // Reinicia Supervisor si se actualizan ciertos campos
        static::updating(function ($monitorConnection) {
            if ($monitorConnection->isDirty(['mqtt_topic'])) {
                self::restartSupervisor();
            }
        });

        // Reinicia Supervisor cuando se crea un registro
        static::created(function ($monitorConnection) {
            self::restartSupervisor();
        });

        // Reinicia Supervisor cuando se elimina un registro
        static::deleted(function ($monitorConnection) {
            self::restartSupervisor();
        });
    }

    /**
     * Método para reiniciar el Supervisor.
     */
    protected static function restartSupervisor()
    {
        try {
            exec('sudo /usr/bin/supervisorctl restart all', $output, $returnVar);

            if ($returnVar === 0) {
                Log::channel('supervisor')->info("Supervisor reiniciado exitosamente.");
            } else {
                Log::channel('supervisor')->error("Error al reiniciar supervisor: " . implode("\n", $output));
            }
        } catch (\Exception $e) {
            Log::channel('supervisor')->error("Excepción al reiniciar supervisor: " . $e->getMessage());
        }
    }
}
