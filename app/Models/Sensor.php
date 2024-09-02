<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    /**
     * Los atributos que son asignables.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'production_line_id',
        'barcoder_id',
        'json_api',
        'mqtt_topic_sensor',
        'count_total',
        'count_total_0',
        'count_total_1',
        'count_shift_0',
        'count_shift_1',
        'count_order_0',
        'count_order_1',
        'time_00',
        'time_01',
        'time_11',
        'time_10',
        'best_time_00',
        'best_time_01',
        'best_time_11',
        'best_time_10',
        'mqtt_topic_0',
        'mqtt_topic_1',
        'function_model_0',
        'function_model_1',
    ];

    /**
     * Relación con la tabla ProductionLine.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * Relación con la tabla Barcode.
     */
    public function barcoder()
    {
        return $this->belongsTo(Barcode::class, 'barcoder_id');
    }
}

