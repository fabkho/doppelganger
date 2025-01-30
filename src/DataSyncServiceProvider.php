<?php

namespace YourVendor\DataSync;

use Illuminate\Support\ServiceProvider;

class DataSyncServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/data-sync.php' => config_path('data-sync.php'),
            ], 'data-sync-config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/data-sync.php', 'data-sync'
        );
    }
}
