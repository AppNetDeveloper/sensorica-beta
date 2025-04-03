<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorHistory extends Model
{
    use HasFactory;

    protected $table = 'sensor_history';

    protected $fillable = [
        'sensor_id',
        'count_shift_1',
        'count_shift_0',
        'count_order_0',
        'count_order_1',
        'downtime_count',
        'unic_code_order',
        'orderId',
        'optimal_production_time'
    ];

    /**
     * Relación con el modelo Sensor.
     */
    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }
}
