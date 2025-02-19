<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->tinyInteger('relationship')->default(0);
            $table->tinyInteger('tattoos')->default(2);
            $table->tinyInteger('piercings')->default(2);
            $table->tinyInteger('career_status')->default(1);
            $table->tinyInteger('status')->default(1);
            $table->string('career_start')->default("");
            $table->string('career_end')->default("");
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
