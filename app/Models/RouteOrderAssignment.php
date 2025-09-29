<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteOrderAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_client_vehicle_assignment_id',
        'original_order_id',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relación con la asignación cliente-vehículo
     */
    public function routeClientVehicleAssignment()
    {
        return $this->belongsTo(RouteClientVehicleAssignment::class);
    }

    /**
     * Relación con la orden original
     */
    public function originalOrder()
    {
        return $this->belongsTo(OriginalOrder::class);
    }
}
