<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ProductionOrderCallback;
use App\Models\VendorItem;
use App\Models\VendorOrder;
use App\Models\VendorSupplier;
use App\Models\AssetCostCenter;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\Asset;
use App\Models\AssetReceipt;
use App\Policies\ProductionOrderCallbackPolicy;
use App\Policies\VendorItemPolicy;
use App\Policies\VendorOrderPolicy;
use App\Policies\VendorSupplierPolicy;
use App\Policies\AssetCostCenterPolicy;
use App\Policies\AssetCategoryPolicy;
use App\Policies\AssetLocationPolicy;
use App\Policies\AssetPolicy;
use App\Policies\AssetReceiptPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        ProductionOrderCallback::class => ProductionOrderCallbackPolicy::class,
        VendorSupplier::class => VendorSupplierPolicy::class,
        VendorItem::class => VendorItemPolicy::class,
        VendorOrder::class => VendorOrderPolicy::class,
        AssetCostCenter::class => AssetCostCenterPolicy::class,
        AssetCategory::class => AssetCategoryPolicy::class,
        AssetLocation::class => AssetLocationPolicy::class,
        Asset::class => AssetPolicy::class,
        AssetReceipt::class => AssetReceiptPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Define basic gates if permissions package isn't handling them
        Gate::define('callbacks.view', fn($user) => $user->hasRole('admin') || $user->hasPermissionTo('callbacks.view'));
        Gate::define('callbacks.update', fn($user) => $user->hasRole('admin') || $user->hasPermissionTo('callbacks.update'));
        Gate::define('callbacks.delete', fn($user) => $user->hasRole('admin') || $user->hasPermissionTo('callbacks.delete'));
        Gate::define('callbacks.force', fn($user) => $user->hasRole('admin') || $user->hasPermissionTo('callbacks.force'));
    }
}
