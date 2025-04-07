<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
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
        'name', // nombre del sensor
        'token', // código único que se le aloja a cada sensor
        'production_line_id', // el id de la línea de producción para asociar el sensor
        'barcoder_id', // asociamos el sensor con el barcoder
        'sensor_type', // 0 es sensor de conteo, 1 es sensor de consumibles, 2 de materia prima, 3 de avería en proceso
        'optimal_production_time', // tiempo óptimo para este orderId para cada caja/paquete/malla
        'reduced_speed_time_multiplier', // tiempo que es puesto como velocidad lenta, superior a este es parada
        'json_api', // valor de la API para obtener el valor del sensor, si no se pone nada es por defecto value
        'mqtt_topic_sensor', // valor del tópico para recibir el valor del sensor
        'count_total', // contador total 
        'count_total_0', // contador total 0
        'count_total_1', // contador total 1
        'count_shift_0', // contador de turno 0
        'count_shift_1', // contador de turno 1  
        'count_order_0', // contador de pedido 0
        'count_order_1', // contador de pedido 1
        'mqtt_topic_1', // tópico para el valor 1
        'function_model_0', // función para el valor 0
        'function_model_1', // función para el valor 1
        'invers_sensors', // si el sensor es inverso o no
        'downtime_count', // tiempo no productivo, se debe resetear en cada cambio de turno o pedido
        'unic_code_order', // código de orden único, uso interno
        'shift_type', // tipo de turno
        'productName', // nombre del producto o mejor dicho el id  client_id en productList
        'count_week_0', // contador de la semana 0
        'count_week_1', // contador de la semana 1
        'min_correction_percentage', //vamos que  empieza a corregir por ejemplo 80% de la velocidad real si es por debajo  de 20 % de la velocidad optima la actual corrige
        'max_correction_percentage', // despues de correcion se correccion por ejemplo 98% de la velocidad real si es por encima de 90 % de la velocidad optima la actual corrige
         
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
     * Relación con la tabla Barcode.
     */
    public function barcoder()
    {
        return $this->belongsTo(Barcode::class, 'barcoder_id');
    }
    /**
     * Relación con la tabla sensor_counts.
     */
    public function sensorCounts()
    {
        return $this->hasMany(SensorCount::class, 'sensor_id');
    }
    /**
     * Define la relación con el modelo ProductList.
     *
     * Un sensor pertenece a un ProductList (a través de productName <-> client_id).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function productList()
    {
        // Ajusta 'productName' y 'client_id' si los nombres de las columnas son diferentes.
        return $this->belongsTo(ProductList::class, 'productName', 'client_id');
    }
    /**
     * Métodos del ciclo de vida del modelo para reiniciar Supervisor
     * cuando se actualizan ciertos campos.
     */
    protected static function boot()
    {
        parent::boot();
    
        // Generar un token único antes de crear el registro
        static::creating(function ($sensor) {
            $sensor->token = Str::uuid(); // O Str::random(32) para un token aleatorio de 32 caracteres
        });
    
        // Evento 'updating' para detectar cambios
        static::updating(function ($sensor) {
            if ($sensor->isDirty(['mqtt_topic_sensor', 'mqtt_topic_1'])) {
                self::restartSupervisor();
            }
        });
    
        // Evento 'deleted' para reiniciar supervisor
        static::deleted(function ($sensor) {
            self::restartSupervisor();
        });
    }
    /**
     * generar codigo unico .
     */
    public function generateUniqueCode()
    {
        $lineId = $this->production_line_id;
        $sensorId = $this->id;
        $timestamp = Carbon::now()->format('YmdHis');
    
        return "{$lineId}_{$sensorId}_{$timestamp}";
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

