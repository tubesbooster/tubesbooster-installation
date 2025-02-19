<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdContentCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ad_content_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ad_id');
            $table->unsignedBigInteger('content_category_id');
            $table->timestamps();

            $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            $table->foreign('content_category_id')->references('id')->on('content_categories')->onDelete('cascade');
        });
    }

    public function down()
    {
    }
}
