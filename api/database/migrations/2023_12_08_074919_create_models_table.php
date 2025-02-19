<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('stage_name')->nullable();
            $table->string('birth_date')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->integer('gender')->nullable();
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->string('measurements')->nullable();
            $table->integer('hair_color')->nullable();
            $table->integer('eye_color')->nullable();
            $table->integer('ethnicity')->nullable();
            $table->string('site_name')->nullable();
            $table->string('url')->nullable();
            $table->string('biography')->nullable();
            $table->integer('orientation')->nullable();
            $table->string('interests')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('social_media')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('models');
    }
}
