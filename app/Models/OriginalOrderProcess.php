<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OriginalOrderProcess extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'original_order_id',
        'process_id',
        'time',
        'created',
        'finished',
        'finished_at'
    ];
    
    protected $casts = [
        'time' => 'decimal:2',
        'created' => 'boolean',
        'finished' => 'boolean',
        'finished_at' => 'datetime'
    ];

    protected $dates = [
        'finished_at'
    ];

    /**
     * Obtener los artículos asociados a este proceso de pedido
     */
    public function articles(): HasMany
    {
        return $this->hasMany(OriginalOrderArticle::class, 'original_order_process_id');
    }

    public function originalOrder()
    {
        return $this->belongsTo(OriginalOrder::class);
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::saving(function ($pivot) {
            // Si el campo 'finished' ha cambiado, actualizamos 'finished_at' automáticamente.
            if ($pivot->isDirty('finished')) {
                $pivot->finished_at = $pivot->finished ? now() : null;
            }
        });

        static::saved(function (OriginalOrderProcess $pivot) {
            // Si el estado 'finished' cambió, actualizamos el estado general de la orden.
            if ($pivot->wasChanged('finished')) {
                if ($originalOrder = $pivot->originalOrder) {
                    $originalOrder->updateFinishedStatus();
                }
            }
        });
    }

    // Ensure the pivot model knows about the parent relationship key if it's non-standard
    // In this case, 'original_order_id' is standard for an originalOrder() relationship.
    // public function getParentKeyName()
    // {
    //     return 'original_order_id'; 
    // }

    // If you need to access the OriginalOrder model instance from the pivot model instance:
    // public function originalOrder()
    // {
    //     return $this->belongsTo(OriginalOrder::class, 'original_order_id');
    // }

    // If you need to access the Process model instance from the pivot model instance:
    // public function process()
    // {
    //     return $this->belongsTo(Process::class);
    // }
}
