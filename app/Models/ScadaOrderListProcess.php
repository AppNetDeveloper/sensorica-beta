<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScadaOrderListProcess extends Model
{
    use HasFactory;

    protected $table = 'scada_order_list_process';

    protected $fillable = [
        'scada_order_list_id',
        'scada_material_type_id',
        'orden',
        'measure',
        'value', // Nuevo campo para almacenar el valor de los kg
        'used',
    ];

    protected $casts = [
        'orden' => 'integer',
        'value' => 'decimal:2', // Formatea automáticamente el valor a 2 decimales
    ];

    public function scadaOrderList()
    {
        return $this->belongsTo(ScadaOrderList::class, 'scada_order_list_id');
    }

    public function scadaMaterialType()
    {
        return $this->belongsTo(ScadaMaterialType::class, 'scada_material_type_id');
    }
    public function material()
    {
        return $this->belongsTo(ScadaMaterialType::class, 'scada_material_type_id');
    }
}
