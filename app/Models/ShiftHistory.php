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
        'operator_id' // Agregamos esta columna
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
