<?php

namespace App\Policies;

use App\Models\AssetCategory;
use App\Models\Customer;
use App\Models\User;

class AssetCategoryPolicy
{
    public function viewAny(User $user, Customer $customer): bool
    {
        return $user->can('asset-categories-view');
    }

    public function view(User $user, AssetCategory $assetCategory): bool
    {
        return $user->can('asset-categories-view');
    }

    public function create(User $user, Customer $customer): bool
    {
        return $user->can('asset-categories-create');
    }

    public function update(User $user, AssetCategory $assetCategory): bool
    {
        return $user->can('asset-categories-edit');
    }

    public function delete(User $user, AssetCategory $assetCategory): bool
    {
        return $user->can('asset-categories-delete');
    }
}
