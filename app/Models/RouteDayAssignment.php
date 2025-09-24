<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RouteDayAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'route_name_id',
        'fleet_vehicle_id',
        'assignment_date',
        'day_of_week',
        'notes',
        'active',
    ];

    protected $casts = [
        'assignment_date' => 'date',
        'day_of_week' => 'integer',
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

    public function fleetVehicle()
    {
        return $this->belongsTo(FleetVehicle::class);
    }
}
