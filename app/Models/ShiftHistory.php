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
        'type',
        'action',
        'description',
    ];

    /**
     * Relación con la línea de producción.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'production_line_id');
    }
}
