<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStat extends Model
{
    use HasFactory;

    // Especifica la tabla asociada a este modelo.
    protected $table = 'order_stats';

    // Permite la asignación masiva de estos campos.
    protected $fillable = [
        'production_line_id',
        'order_id',
        'box',
        'units_box',
        'units',
        'units_per_minute_real',
        'units_per_minute_theoretical',
        'seconds_per_unit_real',
        'seconds_per_unit_theoretical',
        'units_made_real',
        'units_made_theoretical',
        'sensor_stops_count',
        'sensor_stops_time',
        'production_stops_time',
        'units_made',
        'units_pending',
        'units_delayed',
        'slow_time',
        'fast_time',
        'out_time',
        'theoretical_end_time',
        'real_end_time',
        'oee',
        'weights_0_shiftNumber',
        'weights_0_shiftKg',
        'weights_0_orderNumber',
        'weights_0_orderKg',
        'weights_1_shiftNumber',
        'weights_1_shiftKg',
        'weights_1_orderNumber',
        'weights_1_orderKg',
        'weights_2_shiftNumber',
        'weights_2_shiftKg',
        'weights_2_orderNumber',
        'weights_2_orderKg',
        'weights_3_shiftNumber',
        'weights_3_shiftKg',
        'weights_3_orderNumber',
        'weights_3_orderKg',
    ];

    // Asegúrate de que se gestionen las marcas de tiempo automáticamente.
    public $timestamps = true;
}
