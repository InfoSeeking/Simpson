<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAnswers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('answers', function(Blueprint $table) {
            $table->integer('question_id')->unsigned();
            $table->integer('position')->unsigned();
            $table->integer('answered')->default(0)->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('answers', function(Blueprint $table) {
            $table->dropColumn('question_id');
            $table->dropColumn('position');
        });
    }
}
