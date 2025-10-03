<?php

namespace App\Policies;

use App\Models\AssetCostCenter;
use App\Models\Customer;
use App\Models\User;

class AssetCostCenterPolicy
{
    public function viewAny(User $user, Customer $customer): bool
    {
        return $user->can('asset-cost-centers-view');
    }

    public function view(User $user, AssetCostCenter $assetCostCenter): bool
    {
        return $user->can('asset-cost-centers-view');
    }

    public function create(User $user, Customer $customer): bool
    {
        return $user->can('asset-cost-centers-create');
    }

    public function update(User $user, AssetCostCenter $assetCostCenter): bool
    {
        return $user->can('asset-cost-centers-edit');
    }

    public function delete(User $user, AssetCostCenter $assetCostCenter): bool
    {
        return $user->can('asset-cost-centers-delete');
    }
}
