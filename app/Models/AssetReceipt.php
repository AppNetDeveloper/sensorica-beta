<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vendor_order_id',
        'reference',
        'received_at',
        'received_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vendorOrder()
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function lines()
    {
        return $this->hasMany(AssetReceiptLine::class);
    }
}
