<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'asset_category_id',
        'asset_cost_center_id',
        'asset_location_id',
        'vendor_supplier_id',
        'article_code',
        'label_code',
        'description',
        'status',
        'has_rfid_tag',
        'rfid_tid',
        'rfid_epc',
        'acquired_at',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'has_rfid_tag' => 'boolean',
        'acquired_at' => 'date',
        'attributes' => 'array',
        'metadata' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(AssetCostCenter::class, 'asset_cost_center_id');
    }

    public function location()
    {
        return $this->belongsTo(AssetLocation::class, 'asset_location_id');
    }

    public function supplier()
    {
        return $this->belongsTo(VendorSupplier::class, 'vendor_supplier_id');
    }

    public function events()
    {
        return $this->hasMany(AssetEvent::class);
    }
}
