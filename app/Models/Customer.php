<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\OriginalOrder;
use App\Models\OrderFieldMapping;
use App\Models\ProductionLine;
use App\Models\ProcessFieldMapping;
use App\Models\ArticleFieldMapping;

class Customer extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'token_zerotier',
        'order_listing_url',
        'order_detail_url'
    ];

    /**
     * Obtiene los mapeos de campos para este cliente
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(OrderFieldMapping::class);
    }

    /**
     * Obtiene los mapeos de campos de procesos para este cliente
     */
    public function processFieldMappings(): HasMany
    {
        return $this->hasMany(ProcessFieldMapping::class);
    }

    /**
     * Obtiene los mapeos de campos de artículos para este cliente
     */
    public function articleFieldMappings(): HasMany
    {
        return $this->hasMany(ArticleFieldMapping::class);
    }

    /**
     * Los atributos que deberían estar ocultos para los arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token', // Normalmente no querrás exponer el token públicamente
    ];

    public function originalOrders()
    {
        return $this->hasMany(OriginalOrder::class);
    }

    // Define la relación con la tabla production_lines si es necesario
    public function productionLines()
    {
        return $this->hasMany(ProductionLine::class);
    }
}
