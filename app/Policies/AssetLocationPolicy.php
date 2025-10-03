<?php

namespace App\Policies;

use App\Models\AssetLocation;
use App\Models\Customer;
use App\Models\User;

class AssetLocationPolicy
{
    public function viewAny(User $user, Customer $customer): bool
    {
        return $user->can('asset-locations-view');
    }

    public function view(User $user, AssetLocation $assetLocation): bool
    {
        return $user->can('asset-locations-view');
    }

    public function create(User $user, Customer $customer): bool
    {
        return $user->can('asset-locations-create');
    }

    public function update(User $user, AssetLocation $assetLocation): bool
    {
        return $user->can('asset-locations-edit');
    }

    public function delete(User $user, AssetLocation $assetLocation): bool
    {
        return $user->can('asset-locations-delete');
    }
}
