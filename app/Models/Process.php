<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Process extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'sequence', 'description'];
    
    /**
     * Obtener las líneas de producción asociadas a este proceso
     */
    public function productionLines()
    {
        return $this->belongsToMany(ProductionLine::class, 'production_line_process')
            ->withPivot('order')
            ->orderBy('production_line_process.order');
    }
    
    /**
     * Obtener el siguiente proceso en la secuencia
     */
    public function nextProcess()
    {
        return self::where('sequence', '>', $this->sequence)
                   ->orderBy('sequence', 'asc')
                   ->first();
    }
    
    /**
     * Obtener el proceso anterior en la secuencia
     */
    public function previousProcess()
    {
        return self::where('sequence', '<', $this->sequence)
                   ->orderBy('sequence', 'desc')
                   ->first();
    }
}
