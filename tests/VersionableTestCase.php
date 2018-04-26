<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class VersionableTestCase extends PHPUnitTestCase
{
    public function setUp()
    {
        $this->configureDatabase();
        $this->migrateUsersTable();
    }

    protected function configureDatabase()
    {
        $db = new DB;
        $db->addConnection( [
            'driver'    => 'sqlite',
            'database'  => ':memory:',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ] );
        $db->setEventDispatcher( new Dispatcher( new Container ) );
        $db->bootEloquent();
        $db->setAsGlobal();
    }

    public function migrateUsersTable()
    {
        DB::schema()->create( 'users', function ( $table ) {
            $table->increments( 'id' );
            $table->string( 'name' );
            $table->string( 'email' );
            $table->string( 'password' );
            $table->datetime( 'last_login' )->nullable();
            $table->timestamps();
            $table->softDeletes();
        } );

        DB::schema()->create( 'versions', function ( $table ) {
            $table->increments( 'version_id' );

            $table->morphs( 'versionable' );

            $table->integer( 'user_id' )->nullable();
            $table->binary( 'model_data' );
            $table->string( 'reason', 100 )->nullable();

            $table->timestamps();
        } );
    }
}