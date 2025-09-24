<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutePlanDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_plan_id',
        'day_of_week',
        'date',
        'name',
        'notes',
        'active',
    ];

    protected $casts = [
        'date' => 'date',
        'active' => 'boolean',
    ];

    public function routePlan(): BelongsTo
    {
        return $this->belongsTo(RoutePlan::class);
    }
}
