<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierOrder extends Model
{
    protected $table = 'supplier_orders';
    
    protected $fillable = [
        'supplier_order_id',
        'order_line',
        'quantity',
        'unit',
        'barcode',
        'refer_id',
        'control_weight_id', // Se añade este campo
    ];

    public function reference()
    {
        return $this->belongsTo(SupplierOrderReference::class, 'refer_id', 'id');
    }
    
    // Relación para obtener el registro individual asignado (si se usa control_weight_id)
    public function controlWeight()
    {
        return $this->belongsTo(ControlWeight::class, 'control_weight_id');
    }

    // Relación para obtener todos los registros de control_weight asociados (usada para consolidar palets)
    public function controlWeights()
    {
        return $this->hasMany(\App\Models\ControlWeight::class, 'supplier_order_id');
    }
}
