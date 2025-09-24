<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'default_route_name_id',
        'vehicle_type', // furgoneta, camion, etc
        'plate',        // matrícula
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'capacity_kg',
        'fuel_type',
        'itv_expires_at',
        'insurance_expires_at',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'weight_kg' => 'float',
        'length_cm' => 'float',
        'width_cm' => 'float',
        'height_cm' => 'float',
        'capacity_kg' => 'float',
        'itv_expires_at' => 'date',
        'insurance_expires_at' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function defaultRouteName()
    {
        return $this->belongsTo(RouteName::class, 'default_route_name_id');
    }

    // Computed: volume in cubic meters (m3) from cm dimensions
    public function getVolumeM3Attribute(): ?float
    {
        $l = (float)($this->length_cm ?? 0);
        $w = (float)($this->width_cm ?? 0);
        $h = (float)($this->height_cm ?? 0);
        if ($l <= 0 || $w <= 0 || $h <= 0) {
            return null;
        }
        // cm^3 to m^3 => divide by 1,000,000
        return round(($l * $w * $h) / 1000000, 3);
    }
}
