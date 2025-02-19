<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('cover')->nullable();
            $table->integer('age')->nullable();
            $table->string('languages')->nullable();
            $table->string('education')->nullable();
            $table->integer('ethnicity')->nullable();
            $table->integer('drinking')->nullable();
            $table->integer('smoking')->nullable();
            $table->integer('hair_length')->nullable();
            $table->integer('hair_color')->nullable();
            $table->integer('eye_color')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('cover');
            $table->dropColumn('age');
            $table->dropColumn('languages');
            $table->dropColumn('education');
            $table->dropColumn('ethnicity');
            $table->dropColumn('drinking');
            $table->dropColumn('smoking');
            $table->dropColumn('hair_length');
            $table->dropColumn('hair_color');
            $table->dropColumn('eye_color');
        });
    }
}
