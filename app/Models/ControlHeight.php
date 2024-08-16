<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControlHeight extends Model
{
    use HasFactory;

    protected $fillable = ['height_value', 'modbus_id'];

    // Definir la relación con el modelo Modbus
    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }
}
