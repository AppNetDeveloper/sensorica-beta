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
        'box',
        'units_box',
        'number_of_pallets',
        'created',
        // 'finished',        // Se gestiona por lógica interna
        // 'finished_at',     // Se gestiona por lógica interna
        'grupo_numero',
        'in_stock'
    ];
    
    protected $casts = [
        'time' => 'decimal:2',
        'box' => 'integer',
        'units_box' => 'integer',
        'number_of_pallets' => 'integer',
        'created' => 'boolean',
        'finished' => 'boolean',
        'finished_at' => 'datetime',
        'in_stock' => 'integer'
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
                if ($pivot->finished) {
                    // Solo permitir marcar como finalizado si existe al menos una ProductionOrder en status 2
                    $hasFinishedProductionOrder = $pivot->productionOrders()->where('status', 2)->exists();
                    if ($hasFinishedProductionOrder) {
                        $pivot->finished_at = now();
                    } else {
                        // Bloquear el acabado si no hay ninguna orden de producción finalizada
                        $pivot->finished = false;
                        $pivot->finished_at = null;
                        Log::warning("OriginalOrderProcess {$pivot->id}: intento de marcar finished sin ProductionOrder en status 2. Operación bloqueada.");
                    }
                } else {
                    // Si se desmarca finished, limpiar la fecha
                    $pivot->finished_at = null;
                    $pivot->finished = false;
                }
            }
            
            // Si se está actualizando manualmente el campo in_stock, forzamos la recarga de la relación
            if ($pivot->isDirty('in_stock') && !$pivot->exists) {
                $pivot->load('articles');
            }
        });

        // Evento DESPUÉS de guardar: notifica a la orden padre.
        static::saved(function (self $pivot) {
            // Si el proceso tiene artículos, actualizamos su estado de stock primero
            if ($pivot->relationLoaded('articles') && $pivot->articles->isNotEmpty()) {
                $pivot->updateStockStatus();
            }
            
            // Actualizar el estado de finalización de la orden
            $pivot->originalOrder?->updateFinishedStatus();
            
            // Actualizar el estado de stock de la orden
            $pivot->originalOrder?->updateInStockStatus();
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
    
    /**
     * Obtener las órdenes de producción asociadas a este proceso
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class, 'original_order_process_id');
    }

    /**
     * Archivos públicos asociados a este proceso original
     */
    public function files(): HasMany
    {
        return $this->hasMany(OriginalOrderProcessFile::class, 'original_order_process_id');
    }

    /**
     * Actualiza el estado de stock del proceso basado en sus artículos
     * - Si algún artículo tiene in_stock = 0, el proceso tendrá in_stock = 0
     * - Si todos los artículos tienen in_stock = 1 o NULL, el proceso tendrá in_stock = 1
     * - Si no hay artículos, el proceso mantendrá su valor actual
     * 
     * @return void
     */
    public function updateStockStatus(): void
    {
        // Si el proceso no tiene artículos, no hacemos nada (mantenemos el valor actual)
        if ($this->articles->isEmpty()) {
            return;
        }

        // Verificamos si hay al menos un artículo sin stock (in_stock = 0)
        $hasOutOfStock = $this->articles->contains(function ($article) {
            return $article->in_stock === 0;
        });

        // Actualizamos el estado del proceso
        $newStatus = $hasOutOfStock ? 0 : 1;
        
        // Solo actualizamos si el valor ha cambiado para evitar bucles de actualización
        if ($this->in_stock !== $newStatus) {
            $this->in_stock = $newStatus;
            
            // Guardamos sin disparar eventos para evitar bucles
            $this->saveQuietly();
            
            Log::info("Actualizado estado de stock del proceso {$this->id} a {$newStatus} basado en sus artículos");
            
            // Si el proceso tiene una orden padre, podríamos notificarla aquí si es necesario
            // $this->originalOrder?->updateRelatedStockStatus();
        }
    }
}
