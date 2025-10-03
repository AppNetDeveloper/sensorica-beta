<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\User;

class AssetPolicy
{
    public function viewAny(User $user, Customer $customer): bool
    {
        return $user->can('assets-view');
    }

    public function view(User $user, Asset $asset): bool
    {
        return $user->can('assets-view');
    }

    public function create(User $user, Customer $customer): bool
    {
        return $user->can('assets-create');
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->can('assets-edit');
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->can('assets-delete');
    }

    public function printLabel(User $user, Asset $asset): bool
    {
        return $user->can('assets-print-label');
    }
}
