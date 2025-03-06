<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlWeight extends Model
{
    protected $fillable = [
        'modbus_id',
        'last_control_weight',
        'last_dimension',
        'last_box_number',
        'last_box_shift',
        'last_barcoder',
        'last_final_barcoder',
        'box_m3',
        'supplier_order_id', // Se añade para la relación
    ];

    public function modbus()
    {
        return $this->belongsTo(Modbus::class);
    }
    
    // Nueva relación con SupplierOrder
    public function supplierOrder()
    {
        return $this->belongsTo(SupplierOrder::class, 'supplier_order_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::saving(function ($controlWeight) {
            $modbus = $controlWeight->modbus;

            if ($modbus) {
                // Verificar si los campos existen y no son nulos o ceros
                if (
                    isset($modbus->box_width, $modbus->box_length, $controlWeight->last_dimension) &&
                    $modbus->box_width > 0 &&
                    $modbus->box_length > 0 &&
                    $controlWeight->last_dimension > 0
                ) {
                    // Calcular box_m3
                    $controlWeight->box_m3 = ($modbus->box_width * $modbus->box_length * $controlWeight->last_dimension) / 1000;
                } else {
                    // Establecer box_m3 en null o 0
                    $controlWeight->box_m3 = null; // O puedes usar 0
                }
            } else {
                // Modbus no encontrado, manejar el error según corresponda
                // Por ejemplo, lanzar una excepción o dejar box_m3 en null
                $controlWeight->box_m3 = null;
            }
        });
    }
}
