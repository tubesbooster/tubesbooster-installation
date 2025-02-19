<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlbumsAddColumnSlug extends Migration
{
    public function up()
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->string('slug')->default('null');
        });
    }
    
    public function down()
    {
        Schema::table('albums', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
