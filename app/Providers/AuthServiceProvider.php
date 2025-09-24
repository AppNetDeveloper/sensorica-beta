<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\ProductionOrderCallback;
use App\Policies\ProductionOrderCallbackPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        ProductionOrderCallback::class => ProductionOrderCallbackPolicy::class,
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
