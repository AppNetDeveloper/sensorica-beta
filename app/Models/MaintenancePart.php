<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenancePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'production_line_id',
        'name',
        'code',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function maintenances()
    {
        return $this->belongsToMany(Maintenance::class, 'maintenance_part_maintenance', 'maintenance_part_id', 'maintenance_id');
    }
}
