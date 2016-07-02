<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProjectList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects', function(Blueprint $table) {
            $table->string('state')->default('unstarted');
            $table->integer('nextProject')->default(null);
            $table->integer('prevProject')->default(null);
            $table->string('scenario_name')->default('untitled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects', function(Blueprint $table) {
            $table->dropColumn('state');
            $table->dropColumn('nextProject');
            $table->dropColumn('prevProject');
            $table->dropColumn('scenario_name');
        });
    }
}
