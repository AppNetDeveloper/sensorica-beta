<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_order_id',
        'vendor_item_id',
        'description',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'tax_rate',
        'status',
        'metadata',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:4',
        'quantity_received' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(VendorOrder::class, 'vendor_order_id');
    }

    public function item()
    {
        return $this->belongsTo(VendorItem::class, 'vendor_item_id');
    }

    public function receiptLines()
    {
        return $this->hasMany(AssetReceiptLine::class);
    }

    public function getQuantityPendingAttribute()
    {
        if ($this->relationLoaded('receiptLines')) {
            $received = $this->receiptLines->sum('quantity_received');
        } else {
            $received = $this->receiptLines()->sum('quantity_received');
        }

        return max(0, ($this->quantity_ordered ?? 0) - $received);
    }
}
