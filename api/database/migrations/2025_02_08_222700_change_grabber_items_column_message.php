<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeGrabberItemsColumnMessage extends Migration
{
    public function up()
    {
        Schema::table('grabber_items', function (Blueprint $table) {
            $table->mediumText('message')->change();
        });
    }
};
