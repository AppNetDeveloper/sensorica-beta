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
        'optimalproductionTime_sensorType_0',
        'optimalproductionTime_sensorType_1',
        'optimalproductionTime_sensorType_2',
        'optimalproductionTime_sensorType_3',
        'optimalproductionTime_sensorType_4',
        'optimalproductionTime_rfid',
        'optimalproductionTime_weight',
        'optimalproductionTime_weight_1',
        'optimalproductionTime_weight_2',
        'optimalproductionTime_weight_3',
        'optimalproductionTime_weight_4',
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
