<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'tax_id',
        'email',
        'phone',
        'contact_name',
        'payment_terms',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(VendorItem::class);
    }

    public function orders()
    {
        return $this->hasMany(VendorOrder::class);
    }
}
