<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorOrder;

class VendorOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendor-orders-view');
    }

    public function view(User $user, VendorOrder $order): bool
    {
        return $user->can('vendor-orders-view') && $this->sameCustomer($user, $order->customer_id);
    }

    public function create(User $user): bool
    {
        return $user->can('vendor-orders-create');
    }

    public function update(User $user, VendorOrder $order): bool
    {
        return $user->can('vendor-orders-edit') && $this->sameCustomer($user, $order->customer_id);
    }

    public function delete(User $user, VendorOrder $order): bool
    {
        return $user->can('vendor-orders-delete') && $this->sameCustomer($user, $order->customer_id);
    }

    protected function sameCustomer(User $user, ?int $customerId): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $customerId === null || $user->customer_id === $customerId;
    }
}
