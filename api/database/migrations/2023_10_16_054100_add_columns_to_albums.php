<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToAlbums extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->integer('type')->nullable();;
            $table->integer('views')->nullable();;
            $table->string('description')->nullable();
            $table->string('date_scheduled')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('views');
            $table->dropColumn('description');
            $table->dropColumn('date_scheduled');
        });
    }
}
