<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelsTable extends Migration
{
    public function up()
    {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('banner_wide')->nullable();
            $table->string('banner_square')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->string('title')->nullable();
            $table->string('short_description')->nullable();
            $table->string('description')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('channels');
    }
}