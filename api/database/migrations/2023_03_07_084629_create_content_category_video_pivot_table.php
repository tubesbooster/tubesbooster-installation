<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContentCategoryVideoPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_category_video', function (Blueprint $table) {
            $table->unsignedBigInteger('content_category_id')->index();
            $table->foreign('content_category_id')->references('id')->on('content_categories')->onDelete('cascade');
            $table->unsignedBigInteger('video_id')->index();
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('cascade');
            $table->primary(['content_category_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('content_category_video', function (Blueprint $table) {
            $table->dropForeign(['content_category_id']);
        });
        Schema::table('content_category_video', function (Blueprint $table) {
            $table->dropForeign(['video_id']);
        });
        Schema::dropIfExists('content_category_video');
    }
}
