<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceCause extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
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
}
