<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrabbersTable extends Migration
{
    public function up()
    {
        Schema::create('grabbers', function (Blueprint $table) {
            $table->id();
            $table->string('platform');
            $table->integer('status');
            $table->integer('importType');
            $table->integer('type');
            $table->integer('duplicated');
            $table->text('data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grabbers');
    }
}