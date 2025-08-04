<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineAvailability extends Model
{
    use HasFactory;
    
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'line_availability';
    
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'production_line_id',
        'shift_list_id',
        'day_of_week',
        'active'
    ];
    
    /**
     * Los atributos que deben ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'day_of_week' => 'integer'
    ];
    
    /**
     * Obtiene la línea de producción asociada a esta disponibilidad.
     */
    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }
    
    /**
     * Obtiene el turno asociado a esta disponibilidad.
     */
    public function shiftList()
    {
        return $this->belongsTo(ShiftList::class);
    }
}
