<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrabberItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grabber_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grabber_id');
            $table->string('url')->nullable();
            $table->text('message')->nullable();
            $table->integer('status')->default(2);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('grabber_id')->references('id')->on('grabbers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
