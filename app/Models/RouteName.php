<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteName extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'note',
        'days_mask',
        'active',
    ];

    protected $casts = [
        'days_mask' => 'integer',
        'active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerClients(): HasMany
    {
        return $this->hasMany(CustomerClient::class);
    }

    /**
     * Check if this route occurs on a given weekday (1=Mon..7=Sun)
     */
    public function occursOnWeekday(int $weekday): bool
    {
        $mask = 1 << max(0, min(6, $weekday - 1));
        return ((int)($this->days_mask ?? 0) & $mask) !== 0;
    }
}
