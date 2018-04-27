<?php

namespace RodrigoPedra\LaravelVersionable;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class LaravelVersionableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishMigrations();
        }
    }

    public function register()
    {
        //
    }

    private function publishMigrations()
    {
        $this->requireMigrationFiles();

        if (class_exists( 'CreateVersionsTable', false )) {
            return;
        }

        $timestamp = date( 'Y_m_d_His', time() );

        $path = database_path( 'migrations/' . $timestamp . '_create_versions_table.php' );

        $this->publishes( [
            __DIR__ . '/../../database/migrations/create_versions_table.php.stub' => $path,
        ], 'migrations' );
    }

    private function requireMigrationFiles()
    {
        /** @var \Illuminate\Database\Migrations\Migrator $migrator */
        $migrator = App::make( 'migrator' );

        $paths = [
            database_path( 'migrations' ),
        ];

        $files = $migrator->getMigrationFiles( $paths );

        $migrator->requireFiles( $files );
    }
}
