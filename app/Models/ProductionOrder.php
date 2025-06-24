<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log; // Asegúrate de importar la clase Log
use Illuminate\Support\Facades\DB; // Importar Facade DB para la consulta
use Illuminate\Support\Facades\Cache;

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
        'orden', // Manufacturing order
        'theoretical_time', // Theoretical process time
        'process_category', // Process category
        'delivery_date',
        'customerId', // Customer ID
        'original_order_id', // Reference to original order
        'original_order_process_id', // Reference to original order process
        'grupo_numero', // Group number
        'processes_to_do', // Number of processes to do
        'processes_done' // Number of processes completed
    ];
    
    /**
     * Get the original order that owns the production order.
     */
    public function originalOrder()
    {
        return $this->belongsTo(\App\Models\OriginalOrder::class, 'original_order_id');
    }

    /**
     * Get the original order process that this production order is associated with.
     */
    public function originalOrderProcess()
    {
        return $this->belongsTo(\App\Models\OriginalOrderProcess::class, 'original_order_process_id');
    }

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
        'theoretical_time' => 'float', // Si lo guardas como float
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
    
        // --- Evento `creating` (se mantiene exactamente igual) ---
        // Se ejecuta una sola vez, antes de que un nuevo registro se inserte en la BD.
        static::creating(function ($model) {
            // Asignar valor incremental al campo `orden`
            $lastOrder = self::max('orden');
            $model->orden = $lastOrder !== null ? $lastOrder + 1 : 0;
            
            // Asignar status predeterminado si no viene uno
            $model->status = $model->status ?? 0; // 0: Pendiente (predeterminado)
            
            // Lógica para archivar una orden existente con el mismo order_id
            $existingOrder = self::where('order_id', $model->order_id)->first();
            if ($existingOrder) {
                $existingOrder->order_id = $existingOrder->order_id . '-' . $existingOrder->process_category;
               // $existingOrder->status = 2;
                $existingOrder->save();
            }
        });
    
        // --- Evento `saved` (con la lógica MQTT corregida) ---
        // Se ejecuta después de guardar (crear o actualizar) el modelo.
        static::saved(function ($model) {
            // Disparador: solo actuar si el campo 'status' ha sido modificado y es 1 o 2.
            if ($model->isDirty('status') && in_array($model->status, [2])) {
                
                // Lógica de negocio que se mantiene: actualizar el proceso original si finaliza.
                if ($model->status == 2 && $model->original_order_process_id) {
                    $originalOrderProcess = \App\Models\OriginalOrderProcess::find($model->original_order_process_id);
                    if ($originalOrderProcess) {
                        $originalOrderProcess->update(['finished' => 1, 'finished_at' => now()]);
                    }
                }

            }
        });
    }

}

