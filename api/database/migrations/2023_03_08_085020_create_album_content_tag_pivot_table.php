<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAlbumContentTagPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('album_content_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('album_id')->index();
            $table->foreign('album_id')->references('id')->on('albums')->onDelete('cascade');
            $table->unsignedBigInteger('content_tag_id')->index();
            $table->foreign('content_tag_id')->references('id')->on('content_tags')->onDelete('cascade');
            $table->primary(['album_id', 'content_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('album_content_tag');
    }
}
