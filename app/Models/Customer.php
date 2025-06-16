<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OriginalOrder;

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
