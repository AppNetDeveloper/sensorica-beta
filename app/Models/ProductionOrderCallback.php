<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrderCallback extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'customer_id',
        'callback_url',
        'payload',
        'status',
        'attempts',
        'last_attempt_at',
        'success_at',
        'error_message'
    ];

    protected $casts = [
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
        'success_at' => 'datetime',
    ];

    /**
     * Relación con ProductionOrder
     */
    public function productionOrder()
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Relación con Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Genera el payload del callback basado en los mappings del cliente
     */
    public static function generatePayload(ProductionOrder $productionOrder, Customer $customer)
    {
        $payload = [];
        
        foreach ($customer->callbackFieldMappings as $mapping) {
            $value = null;
            
            // Manejar el campo especial processes_code
            if ($mapping->source_field === 'processes_code') {
                $value = self::getProcessCode($productionOrder);
            } else {
                // Obtener valor directo del ProductionOrder
                $value = $productionOrder->{$mapping->source_field} ?? null;
            }
            
            // Aplicar transformación si existe
            if ($mapping->transformation && !is_null($value)) {
                $value = $mapping->applyTransformation($value);
            }
            
            // Agregar al payload
            $payload[$mapping->target_field] = $value;
        }
        
        return $payload;
    }

    /**
     * Obtiene el código del proceso desde original_order_process_id
     */
    private static function getProcessCode(ProductionOrder $productionOrder)
    {
        if (!$productionOrder->original_order_process_id) {
            return null;
        }

        // Buscar el OriginalOrderProcess
        $originalOrderProcess = \App\Models\OriginalOrderProcess::find($productionOrder->original_order_process_id);
        
        if (!$originalOrderProcess || !$originalOrderProcess->process_id) {
            return null;
        }

        // Buscar el Process y obtener su code
        $process = \App\Models\Process::find($originalOrderProcess->process_id);
        
        return $process ? $process->code : null;
    }
}
