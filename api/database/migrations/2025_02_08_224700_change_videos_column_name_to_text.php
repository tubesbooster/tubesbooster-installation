<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeVideosColumnNameToText extends Migration
{
    public function up()
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->text('name')->change();
        });
    }
};
