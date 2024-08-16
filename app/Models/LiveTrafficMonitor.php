<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveTrafficMonitor extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'live_traffic_monitors';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'modbus_id',
        'value',
    ];

    // Relación con la tabla Modbus
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }
}
