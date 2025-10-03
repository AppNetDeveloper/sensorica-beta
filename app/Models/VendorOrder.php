<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'vendor_supplier_id',
        'requested_by',
        'approved_by',
        'reference',
        'status',
        'currency',
        'total_amount',
        'requested_at',
        'expected_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'expected_at' => 'datetime',
        'metadata' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(VendorSupplier::class, 'vendor_supplier_id');
    }

    public function lines()
    {
        return $this->hasMany(VendorOrderLine::class);
    }

    public function assetReceipts()
    {
        return $this->hasMany(AssetReceipt::class);
    }

    public function documents()
    {
        return $this->hasMany(VendorOrderDocument::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
