<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductionOrderCallback;

class ProductionOrderCallbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('callbacks.view');
    }

    public function view(User $user, ProductionOrderCallback $callback): bool
    {
        return $user->can('callbacks.view');
    }

    public function update(User $user, ProductionOrderCallback $callback): bool
    {
        return $user->can('callbacks.update');
    }

    public function delete(User $user, ProductionOrderCallback $callback): bool
    {
        return $user->can('callbacks.delete');
    }

    public function force(User $user, ProductionOrderCallback $callback): bool
    {
        return $user->can('callbacks.force');
    }
}
