<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_receipt_id',
        'vendor_order_line_id',
        'asset_id',
        'quantity_received',
        'unit_cost',
        'metadata',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function receipt()
    {
        return $this->belongsTo(AssetReceipt::class, 'asset_receipt_id');
    }

    public function vendorOrderLine()
    {
        return $this->belongsTo(VendorOrderLine::class, 'vendor_order_line_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
