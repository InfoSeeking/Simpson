<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConnections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('connections', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('initiator_id')->unsigned();
            $table->integer('recipient_id')->unsigned();
            $table->integer('intermediary_id')->unsigned()->nullable();
            $table->integer('project_id')->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('connections');
    }
}
