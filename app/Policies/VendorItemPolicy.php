<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorItem;

class VendorItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendor-items-view');
    }

    public function view(User $user, VendorItem $item): bool
    {
        return $user->can('vendor-items-view') && $this->sameCustomer($user, $item->customer_id);
    }

    public function create(User $user): bool
    {
        return $user->can('vendor-items-create');
    }

    public function update(User $user, VendorItem $item): bool
    {
        return $user->can('vendor-items-edit') && $this->sameCustomer($user, $item->customer_id);
    }

    public function delete(User $user, VendorItem $item): bool
    {
        return $user->can('vendor-items-delete') && $this->sameCustomer($user, $item->customer_id);
    }

    protected function sameCustomer(User $user, ?int $customerId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $customerId === null || $user->customer_id === $customerId;
    }
}
