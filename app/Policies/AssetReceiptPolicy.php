<?php

namespace App\Policies;

use App\Models\AssetReceipt;
use App\Models\Customer;
use App\Models\User;

class AssetReceiptPolicy
{
    public function viewAny(User $user, Customer $customer): bool
    {
        return $user->can('asset-receipts-view');
    }

    public function view(User $user, AssetReceipt $assetReceipt): bool
    {
        return $user->can('asset-receipts-view');
    }

    public function create(User $user, Customer $customer): bool
    {
        return $user->can('asset-receipts-create');
    }

    public function update(User $user, AssetReceipt $assetReceipt): bool
    {
        return $user->can('asset-receipts-edit');
    }

    public function delete(User $user, AssetReceipt $assetReceipt): bool
    {
        return $user->can('asset-receipts-delete');
    }
}
