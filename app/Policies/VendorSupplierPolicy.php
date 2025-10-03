<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorSupplier;

class VendorSupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendor-suppliers-view');
    }

    public function view(User $user, VendorSupplier $supplier): bool
    {
        return $user->can('vendor-suppliers-view');
    }

    public function create(User $user): bool
    {
        return $user->can('vendor-suppliers-create');
    }

    public function update(User $user, VendorSupplier $supplier): bool
    {
        return $user->can('vendor-suppliers-edit');
    }

    public function delete(User $user, VendorSupplier $supplier): bool
    {
        return $user->can('vendor-suppliers-delete');
    }
}
