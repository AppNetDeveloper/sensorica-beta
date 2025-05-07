<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftHistory extends Model
{
    use HasFactory;

    protected $table = 'shift_history';

    protected $fillable = [
        'production_line_id',
        'shift_list_id',
        'type',
        'action',
        'description',
        'operator_id' ,
        'on_time',
        'down_time',
        'production_stops_time',
        'slow_time',
        'theoretical_end_time',
        'real_end_time',
        'oee',
        'prepair_time',
    ];

    // Valores por defecto al crear un modelo
    protected $attributes = [
        'on_time'               => 0,
        'down_time'             => 0,
        'production_stops_time' => 0,
        'slow_time'             => 0,
        'theoretical_end_time'  => 0,
        'real_end_time'         => 0,
        'oee'                   => 0.00,
        'prepair_time'          => 0,
    ];

    // Castings para asegurar el tipo adecuado
    protected $casts = [
        'on_time'               => 'integer',
        'down_time'             => 'integer',
        'production_stops_time' => 'integer',
        'slow_time'             => 'integer',
        'theoretical_end_time'  => 'integer',
        'real_end_time'         => 'integer',
        'oee'                   => 'float',
        'prepair_time'          => 'integer',
    ];

    /**
     * Relación con la línea de producción.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }
    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
    public function shiftList()
    {
        return $this->belongsTo(ShiftList::class, 'shift_list_id');
    }
}
