<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAlbumContentCategoryPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('album_content_category', function (Blueprint $table) {
            $table->unsignedBigInteger('album_id')->index();
            $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
            $table->unsignedBigInteger('content_category_id')->index();
            $table->foreign('content_category_id')->references('id')->on('content_categories')->onDelete('cascade');
            $table->primary(['album_id', 'content_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('album_content_category');
    }
}
