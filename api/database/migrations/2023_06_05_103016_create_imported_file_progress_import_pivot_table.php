<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateImportedFileProgressImportPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_file_progress_import', function (Blueprint $table) {
            $table->unsignedBigInteger('imported_file_id')->index();
            $table->foreign('imported_file_id')->references('id')->on('imported_files')->onDelete('cascade');
            
            $table->unsignedBigInteger('progress_import_id')->index();
            $table->foreign('progress_import_id')->references('id')->on('progress_import')->onDelete('cascade');
            
            // Specify a shorter name for the composite primary key
            $table->primary(['imported_file_id', 'progress_import_id'], 'file_progress_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imported_file_progress_import');
    }
}
