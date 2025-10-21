<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionLineWaitTimeHistory extends Model
{
    use HasFactory;

    protected $table = 'production_line_wait_time_history';

    protected $fillable = [
        'production_line_id',
        'order_count',
        'wait_time_mean',
        'wait_time_median',
        'wait_time_min',
        'wait_time_max',
        'captured_at',
    ];

    protected $casts = [
        'order_count' => 'integer',
        'wait_time_mean' => 'decimal:2',
        'wait_time_median' => 'decimal:2',
        'wait_time_min' => 'decimal:2',
        'wait_time_max' => 'decimal:2',
        'captured_at' => 'datetime',
    ];

    /**
     * Relación con la línea de producción
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
}
