<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatOperator extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'order_stats_operators';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'order_stat_id',
        'shift_history_id',
        'operator_id',
        'time_spent',
        'notes',
    ];

    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'time_spent' => 'integer',
    ];

    /**
     * Obtiene la estadística de orden asociada.
     */
    public function orderStat()
    {
        return $this->belongsTo(OrderStat::class);
    }

    /**
     * Obtiene el historial de turno asociado.
     */
    public function shiftHistory()
    {
        return $this->belongsTo(ShiftHistory::class);
    }

    /**
     * Obtiene el operario asociado.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }
}
