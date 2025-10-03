<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\OriginalOrder;
use App\Models\OrderFieldMapping;
use App\Models\ProductionLine;
use App\Models\ProcessFieldMapping;
use App\Models\ArticleFieldMapping;
use App\Models\CustomerCallbackMapping;
use App\Models\VendorSupplier;
use App\Models\VendorItem;
use App\Models\VendorOrder;
use App\Models\AssetCostCenter;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\Asset;
use App\Models\AssetReceipt;

class Customer extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'token',
        'token_zerotier',
        'order_listing_url',
        'order_detail_url',
        'callback_finish_process',
        'callback_url'
    ];

    /**
     * Obtiene los mapeos de campos para este cliente
     */
    public function fieldMappings(): HasMany
    {
        return $this->hasMany(OrderFieldMapping::class);
    }

    /**
     * Obtiene los mapeos de campos de procesos para este cliente
     */
    public function processFieldMappings(): HasMany
    {
        return $this->hasMany(ProcessFieldMapping::class);
    }

    /**
     * Obtiene los mapeos de campos de artículos para este cliente
     */
    public function articleFieldMappings(): HasMany
    {
        return $this->hasMany(ArticleFieldMapping::class);
    }

    /**
     * Obtiene los mapeos de campos de callback para este cliente
     */
    public function callbackFieldMappings(): HasMany
    {
        return $this->hasMany(CustomerCallbackMapping::class);
    }

    /**
     * Los atributos que deberían estar ocultos para los arrays.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token', // Normalmente no querrás exponer el token públicamente
    ];

    /**
     * Los atributos que deberían ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'callback_finish_process' => 'boolean',
    ];

    public function originalOrders()
    {
        return $this->hasMany(OriginalOrder::class);
    }

    // Define la relación con la tabla production_lines si es necesario
    public function productionLines()
    {
        return $this->hasMany(ProductionLine::class);
    }

    public function vendorSuppliers(): HasMany
    {
        return $this->hasMany(VendorSupplier::class);
    }

    public function vendorItems(): HasMany
    {
        return $this->hasMany(VendorItem::class);
    }

    public function vendorOrders(): HasMany
    {
        return $this->hasMany(VendorOrder::class);
    }

    public function assetCostCenters(): HasMany
    {
        return $this->hasMany(AssetCostCenter::class);
    }

    public function assetCategories(): HasMany
    {
        return $this->hasMany(AssetCategory::class);
    }

    public function assetLocations(): HasMany
    {
        return $this->hasMany(AssetLocation::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetReceipts(): HasMany
    {
        return $this->hasMany(AssetReceipt::class);
    }
}
