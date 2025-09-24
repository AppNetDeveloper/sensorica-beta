<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'route_name_id',
        'name',
        'address',
        'phone',
        'email',
        'tax_id',
        'active',
    ];

    protected $casts = [
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
}
