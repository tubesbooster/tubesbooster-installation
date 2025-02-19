<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContentTagVideoPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_tag_video', function (Blueprint $table) {
            $table->unsignedBigInteger('content_tag_id')->index();
            $table->foreign('content_tag_id')->references('id')->on('content_tags')->onDelete('cascade');
            $table->unsignedBigInteger('video_id')->index();
            $table->foreign('video_id')->references('id')->on('videos')->onDelete('cascade');
            $table->primary(['content_tag_id', 'video_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_tag_video');
    }
}
