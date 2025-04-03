<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimalSensorTime extends Model
{
    use HasFactory;

    protected $table = 'optimal_sensor_times';

    protected $fillable = [
        'sensor_id',
        'sensor_type',
        'model_product',
        'product_list_id',
        'production_line_id',
        'optimal_time',
        'modbus_id'
    ];

    // 🔗 Relaciones

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function productList()
    {
        return $this->belongsTo(ProductList::class);
    }
}
