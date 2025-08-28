<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'production_line_id',
        'start_datetime',
        'end_datetime',
        'annotations',
        'operator_id',
        'user_id',
        'operator_annotations',
        'accumulated_maintenance_seconds',
        'accumulated_maintenance_seconds_stoped',
        'production_line_stop',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'accumulated_maintenance_seconds' => 'integer',
        'accumulated_maintenance_seconds_stoped' => 'integer',
        'production_line_stop' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function causes()
    {
        return $this->belongsToMany(MaintenanceCause::class, 'maintenance_cause_maintenance', 'maintenance_id', 'maintenance_cause_id')
            ->withTimestamps();
    }

    public function parts()
    {
        return $this->belongsToMany(MaintenancePart::class, 'maintenance_part_maintenance', 'maintenance_id', 'maintenance_part_id')
            ->withTimestamps();
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function operator()
    {
        return $this->belongsTo(Operator::class, 'operator_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
