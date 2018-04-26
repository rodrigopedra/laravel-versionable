<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class CreateVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $connection = $this->getConnectionName();

        Schema::connection( $connection )->create( 'versions', function ( Blueprint $table ) {
            $table->increments( 'version_id' );

            $table->morphs( 'versionable' );

            $table->integer( 'user_id' )->nullable();
            $table->binary( 'model_data' );
            $table->string( 'reason', 100 )->nullable();

            $table->timestamps();
        } );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $connection = $this->getConnectionName();

        Schema:: connection( $connection )->drop( 'versions' );
    }

    private function getConnectionName()
    {
        $defaultConnection = Config::get( 'database.default' );

        return Config::get( 'versionable.connection', $defaultConnection );
    }
}