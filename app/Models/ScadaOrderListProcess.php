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
        'operator_id',
    ];
    protected static function booted()
    {
        static::updating(function ($model) {
            // Verificar si el campo `used` está cambiando de 0 a 1
            if ($model->isDirty('used') && $model->used == 1 && $model->getOriginal('used') == 0) {
                // Buscar la última línea en `scada_operator_logs`
                $lastOperatorLog = ScadaOperatorLog::latest('created_at')->first();

                if ($lastOperatorLog) {
                    // Asignar el `operator_id` al modelo actual
                    $model->operator_id = $lastOperatorLog->operator_id;
                }
            }
        });
    }
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
        /**
     * Relación con Operator
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }
    
}
