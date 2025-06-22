<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'production_orders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'production_line_id',
        'original_production_line_id',
        'barcoder_id',
        'order_id',
        'json',
        'status',
        'box',
        'units_box',
        'units',
        'orden', // Orden de fabricación
        'theoretical_time', // Tiempo teórico del proceso
        'process_category', // Categoría del proceso
        'delivery_date'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'json' => 'array',
        'processed' => 'boolean',
        'orden' => 'integer',
        'delivery_date' => 'datetime',
        'status' => 'integer', // Es importante mantener el cast a integer
    ];

    /**
     * Obtener la línea de producción original de la orden
     */
    public function originalProductionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'original_production_line_id');
    }

    /**
     * Get the production line associated with the order.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Get the barcode associated with the order.
     */
    public function barcode()
    {
        return $this->belongsTo(Barcode::class);
    }

    /**
     * Get the barcode scans associated with the order.
     */
    public function barcodeScans()
    {
        return $this->hasMany(BarcodeScan::class);
    }
    
    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Evento antes de crear un registro
        static::creating(function ($model) {
            // Asignar valor incremental al campo `orden`
            $lastOrder = self::max('orden');
            $model->orden = $lastOrder !== null ? $lastOrder + 1 : 0;

            // Verificar si el order_id ya existe
            $existingOrder = self::where('order_id', $model->order_id)->first();

            if ($existingOrder) {
                // Si existe, marcar como incidencia
                $model->status = 5; // 5: Con incidencias
                $model->order_id = $model->order_id . 'Duplicado';
            } else {
                // Estado predeterminado si no hay duplicados
                $model->status = $model->status ?? 0; // 0: Pendiente (predeterminado)
            }
        });
    }
}
