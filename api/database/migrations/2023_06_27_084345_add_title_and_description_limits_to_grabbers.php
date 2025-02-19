<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTitleAndDescriptionLimitsToGrabbers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grabbers', function (Blueprint $table) {
            Schema::table('grabbers', function (Blueprint $table) {
                $table->integer('title_limit')->nullable();
                $table->integer('description_limit')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grabbers', function (Blueprint $table) {
            Schema::table('grabbers', function (Blueprint $table) {
                $table->dropColumn('title_limit');
                $table->dropColumn('description_limit');
            });
        });
    }
}
