<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vendor_supplier_id',
        'sku',
        'name',
        'description',
        'unit_of_measure',
        'unit_price',
        'lead_time_days',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(VendorSupplier::class, 'vendor_supplier_id');
    }

    public function orderLines()
    {
        return $this->hasMany(VendorOrderLine::class);
    }
}
