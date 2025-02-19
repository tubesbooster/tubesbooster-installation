<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyColumnsInModels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('models', function (Blueprint $table) {
            $table->integer('relationship')->default(0)->nullable()->change();
            $table->integer('tattoos')->default(2)->nullable()->change();
            $table->integer('piercings')->default(2)->nullable()->change();
            $table->integer('career_status')->default(1)->nullable()->change();
            $table->integer('status')->default(1)->nullable()->change();
            $table->string('career_start')->default("")->nullable()->change();
            $table->string('career_end')->default("")->nullable()->change();
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
