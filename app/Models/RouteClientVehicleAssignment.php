<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteClientVehicleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'route_name_id',
        'customer_client_id',
        'fleet_vehicle_id',
        'assignment_date',
        'day_of_week',
        'sort_order',
        'notes',
        'active',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'day_of_week' => 'integer',
        'sort_order' => 'integer',
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

    public function customerClient()
    {
        return $this->belongsTo(CustomerClient::class);
    }

    public function fleetVehicle()
    {
        return $this->belongsTo(FleetVehicle::class);
    }

    public function orderAssignments()
    {
        return $this->hasMany(RouteOrderAssignment::class);
    }
}
