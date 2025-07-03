<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class OriginalOrderArticle extends Model
{
    protected $table = 'original_order_articles';
    
    protected $fillable = [
        'original_order_process_id',
        'codigo_articulo',
        'descripcion_articulo',
        'grupo_articulo',
        'in_stock',
    ];
    
    protected $casts = [
        'in_stock' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saved(function (self $article) {
            // Si el artículo tiene un proceso padre, actualizamos su estado de stock
            if ($article->originalOrderProcess) {
                $article->originalOrderProcess->updateStockStatus();
            }
        });

        static::deleted(function (self $article) {
            // Si se elimina un artículo, también actualizamos el estado de stock del proceso padre
            if ($article->originalOrderProcess) {
                $article->originalOrderProcess->updateStockStatus();
            }
        });
    }
    
    /**
     * Relación con el proceso del pedido original
     */
    public function originalOrderProcess(): BelongsTo
    {
        return $this->belongsTo(OriginalOrderProcess::class);
    }
}
