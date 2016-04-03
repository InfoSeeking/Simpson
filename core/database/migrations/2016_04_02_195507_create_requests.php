<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requests', function(Blueprint $table){
            $table->increments('id');
            $table->integer('initiator_id')->unsigned();
            $table->integer('recipient_id')->unsigned();
            $table->integer('intermediary_id')->unsigned()->nullable();
            $table->integer('project_id')->unsigned();
            $table->string('type');
            $table->string('state')->default('open');
            $table->string('answer_id')->nullable();
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
        Schema::drop('requests');
    }
}
