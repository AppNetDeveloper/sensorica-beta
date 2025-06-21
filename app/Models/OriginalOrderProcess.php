<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class OriginalOrderProcess extends Pivot
{
    use HasFactory;

    protected $fillable = [
        'original_order_id',
        'process_id',
        'time',
        'created',
        'finished',
        'finished_at',
        'grupo_numero'
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

    protected $table = 'original_order_processes';

    /**
     * The "booted" method of the model.
     * Se encarga de la lógica interna del propio modelo.
     *
     * @return void
     */
    protected static function booted()
    {
        Log::info("OriginalOrderProcess booted");
        
        static::saving(function (self $pivot) {
            if ($pivot->isDirty('finished')) {
                $pivot->finished_at = $pivot->finished ? now() : null;
            }
        });

        // Evento DESPUÉS de guardar: notifica a la orden padre.
        static::saved(function (self $pivot) {
            // ¡CAMBIO CLAVE! Se ha eliminado la condición "if ($pivot->wasChanged('finished'))".
            // Ahora, siempre que se guarde un proceso (ya sea nuevo o actualizado),
            // se notificará al padre para que verifique su estado.
            
            Log::info("Proceso {$pivot->id} guardado. Notificando a la orden padre {$pivot->original_order_id} para que verifique su estado.");
            
            // La lógica de notificación se mantiene.
            $pivot->originalOrder?->updateFinishedStatus();
        });
    }
    
    public function articles(): HasMany
    {
        return $this->hasMany(OriginalOrderArticle::class, 'original_order_process_id');
    }

    public function originalOrder(): BelongsTo
    {
        return $this->belongsTo(OriginalOrder::class, 'original_order_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }
}
