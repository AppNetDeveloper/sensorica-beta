<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'week_start_date',
        'name',
        'active',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Días que componen este plan semanal (1..7)
     */
    public function days(): HasMany
    {
        return $this->hasMany(RoutePlanDay::class);
    }
}
