<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToContentCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('content_categories', function (Blueprint $table) {
            $table->string('slug')->nullable();
            $table->string('description')->nullable();
            $table->integer('parent_id')->nullable();
            $table->integer('status')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_keywords')->nullable();
            $table->string('seo_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('content_categories', function (Blueprint $table) {
            $table->dropColumn('slug');
            $table->dropColumn('description');
            $table->dropColumn('parent_id');
            $table->dropColumn('status');
            $table->dropColumn('thumbnail');
            $table->dropColumn('seo_title');
            $table->dropColumn('seo_keywords');
            $table->dropColumn('seo_description');
        });
    }
}
