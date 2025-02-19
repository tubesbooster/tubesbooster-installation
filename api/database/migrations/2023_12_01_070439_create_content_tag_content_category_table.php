<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentTagContentCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_tag_content_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_tag_id');
            $table->unsignedBigInteger('content_category_id');
            $table->timestamps();

            $table->foreign('content_tag_id')->references('id')->on('content_tags')->onDelete('cascade');
            $table->foreign('content_category_id')->references('id')->on('content_categories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_tag_content_category');
    }
}
