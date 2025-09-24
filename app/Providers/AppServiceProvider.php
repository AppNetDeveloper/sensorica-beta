<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Services\MqttService;
use App\Models\ProductionOrder;
use App\Observers\ProductionOrderObserver;
use App\Models\OriginalOrder;
use App\Observers\OriginalOrderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Registrar MqttService como un singleton
        $this->app->singleton(MqttService::class, function ($app) {
            return new MqttService();
            return new \App\Services\UtilityService;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Schema::defaultStringLength(191);
        
        // Registrar el observer para ProductionOrder
        ProductionOrder::observe(ProductionOrderObserver::class);

        // Registrar el observer para OriginalOrder
        OriginalOrder::observe(OriginalOrderObserver::class);
    }
}
