<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModbusHistory extends Model
{
    use HasFactory;

    protected $table = 'modbus_history';

    protected $fillable = [
        'modbus_id',
        'rec_box_shift',
        'rec_box',
        'downtime_count',
        'unic_code_order',
        'total_kg_order',
        'total_kg_shift',
    ];

    /**
     * Relación con el modelo Modbus.
     */
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }
}
