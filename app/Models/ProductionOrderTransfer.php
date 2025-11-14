<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionOrderTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id_source',
        'production_order_id_target',
        'from_customer_id',
        'to_customer_id',
        'original_order_id_source',
        'original_order_id_target',
        'original_order_process_id_source',
        'original_order_process_id_target',
        'transferred_by',
        'transferred_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    /**
     * Tarjeta origen (production_order)
     */
    public function productionOrderSource()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id_source');
    }

    /**
     * Tarjeta destino (production_order)
     */
    public function productionOrderTarget()
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id_target');
    }

    /**
     * Customer origen
     */
    public function fromCustomer()
    {
        return $this->belongsTo(Customer::class, 'from_customer_id');
    }

    /**
     * Customer destino
     */
    public function toCustomer()
    {
        return $this->belongsTo(Customer::class, 'to_customer_id');
    }

    /**
     * OriginalOrder origen
     */
    public function originalOrderSource()
    {
        return $this->belongsTo(OriginalOrder::class, 'original_order_id_source');
    }

    /**
     * OriginalOrder destino
     */
    public function originalOrderTarget()
    {
        return $this->belongsTo(OriginalOrder::class, 'original_order_id_target');
    }

    /**
     * Proceso origen
     */
    public function originalOrderProcessSource()
    {
        return $this->belongsTo(OriginalOrderProcess::class, 'original_order_process_id_source');
    }

    /**
     * Proceso destino
     */
    public function originalOrderProcessTarget()
    {
        return $this->belongsTo(OriginalOrderProcess::class, 'original_order_process_id_target');
    }

    /**
     * Usuario que realizó la transferencia
     */
    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
