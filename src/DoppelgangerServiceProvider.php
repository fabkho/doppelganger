<?php

namespace fabkho\doppelganger;

use Illuminate\Support\ServiceProvider;

class DoppelgangerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/doppelganger.php' => config_path('doppelganger.php'),
            ], 'doppelganger-config');
        }
    }

    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/doppelganger.php', 'doppelganger'
        );

        // Register main service
        $this->app->singleton(Doppelganger::class, function ($app) {
            return new Doppelganger();
        });
    }
}
