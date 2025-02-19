<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UsersAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable();
            $table->integer('status')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('birth_date')->nullable();
            $table->integer('gender')->nullable();
            $table->integer('relationship')->nullable();
            $table->integer('orientation')->nullable();
            $table->string('website')->nullable();
            $table->string('about_me')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
