<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductList extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', //id del tipo de confeccion, este id viene del erp 
        'name', //nombre de la confeccion
        'optimal_production_time', //tiempo optimo para la fabricacion
        'box_kg', //kg por cada caja fabricados.
    ];

    // Relationship with Client
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function rfidReadings()
    {
        return $this->hasMany(ProductListRfid::class);
    }
    // Relación inversa con OrderStat (opcional)
    public function orderStats()
    {
        return $this->hasMany(OrderStat::class, 'production_line_id');
    }
}
