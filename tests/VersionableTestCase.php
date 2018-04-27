<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class VersionableTestCase extends PHPUnitTestCase
{
    const DB_CONNECTION_NAME = 'default';

    public function setUp()
    {
        $this->configureDatabase();
        $this->migrateUsersTable();
        $this->configureAppFacade();
        $this->configureRequestFacade();
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
        ], self::DB_CONNECTION_NAME );
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

            $table->string( 'action', 20 );
            $table->integer( 'user_id' )->nullable();
            $table->binary( 'model_data' );
            $table->string( 'reason', 100 )->nullable();

            $table->text( 'url' )->nullable();
            $table->ipAddress( 'ip_address' )->nullable();
            $table->string( 'user_agent' )->nullable();

            $table->timestamps();
        } );
    }

    private function configureAppFacade()
    {
        App::shouldReceive( 'runningInConsole' )
            ->andReturn( false );
    }

    private function configureRequestFacade()
    {
        Request::shouldReceive( 'fullUrl' )
            ->andReturn( 'http://example.com' );

        Request::shouldReceive( 'ip' )
            ->andReturn( '127.0.0.1' );

        Request::shouldReceive( 'header' )
            ->with( 'User-Agent' )
            ->andReturn( 'Test Browser User Agent' );
    }
}
