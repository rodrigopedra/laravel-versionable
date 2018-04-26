<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Support\ServiceProvider;

class VersionableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes( [ __DIR__ . '/../config.php' => config_path( 'versionable.php' ) ] );

            $this->loadMigrationsFrom( __DIR__ . '/../migrations' );
        }
    }

    public function register()
    {
        $this->mergeConfigFrom( __DIR__ . '/../config.php', 'versionable' );
    }
}
