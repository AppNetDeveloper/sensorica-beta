<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Sensor extends Model
{
    use HasFactory;

    /**
     * Habilitar el manejo automático de timestamps.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Los atributos que son asignables.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'production_line_id',
        'barcoder_id',
        'json_api',
        'mqtt_topic_sensor',
        'count_total',
        'count_total_0',
        'count_total_1',
        'count_shift_0',
        'count_shift_1',
        'count_order_0',
        'count_order_1',
        'time_00',
        'time_01',
        'time_11',
        'time_10',
        'best_time_00',
        'best_time_01',
        'best_time_11',
        'best_time_10',
        'mqtt_topic_0',
        'mqtt_topic_1',
        'function_model_0',
        'function_model_1',
    ];

    /**
     * Relación con la tabla ProductionLine.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con la tabla ControlWeight.
     */
    public function controlWeights()
    {
        return $this->hasMany(ControlWeight::class);
    }

    /**
     * Relación con la tabla ControlHeight.
     */
    public function controlHeights()
    {
        return $this->hasMany(ControlHeight::class);
    }

    /**
     * Relación con la tabla Modbus.
     */
    public function modbuses()
    {
        return $this->hasMany(Modbus::class);
    }

    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($sensor) {
            if ($sensor->isDirty([
                'mqtt_topic_sensor', 
                'mqtt_topic_0', 
                'mqtt_topic_1',
            ])) {
                self::restartSupervisor();
            }
        });

        static::created(function ($sensor) {
            self::restartSupervisor();
        });

        static::deleted(function ($sensor) {
            self::restartSupervisor();
        });
    }

    /**
     * Método para reiniciar el Supervisor.
     */
    protected static function restartSupervisor()
    {
        try {
            // Usa sudo para ejecutar supervisorctl sin contraseña
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
