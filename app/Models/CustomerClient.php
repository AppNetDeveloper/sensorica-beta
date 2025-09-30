<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'route_name_id',
        'name',
        'address',
        'phone',
        'email',
        'tax_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function routeName()
    {
        return $this->belongsTo(RouteName::class);
    }

    /**
     * Pedidos pendientes de entrega:
     * 1. Pedidos finalizados (finished_at != null) pero no entregados (actual_delivery_date = null)
     * 2. Pedidos NO finalizados (finished_at = null) pero con delivery_date programada o pasada
     */
    public function pendingDeliveries()
    {
        return $this->hasMany(OriginalOrder::class)
            ->whereNull('actual_delivery_date')
            ->where(function ($query) {
                $query->where(function ($q) {
                    // Caso 1: Pedidos finalizados pero no entregados
                    $q->whereNotNull('finished_at');
                })
                ->orWhere(function ($q) {
                    // Caso 2: Pedidos no finalizados con delivery_date programada
                    $q->whereNull('finished_at')
                      ->whereNotNull('delivery_date');
                });
            })
            ->orderByRaw('CASE WHEN finished_at IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByDesc('finished_at')
            ->orderBy('delivery_date');
    }
}
