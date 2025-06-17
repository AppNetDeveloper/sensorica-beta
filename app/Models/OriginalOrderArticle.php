<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OriginalOrderArticle extends Model
{
    protected $table = 'original_order_articles';
    
    protected $fillable = [
        'original_order_process_id',
        'codigo_articulo',
        'descripcion_articulo',
        'grupo_articulo',
    ];
    
    /**
     * Relación con el proceso del pedido original
     */
    public function originalOrderProcess(): BelongsTo
    {
        return $this->belongsTo(OriginalOrderProcess::class);
    }
}
