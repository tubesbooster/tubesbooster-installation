<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetUsersAndSeedAdmin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        
        // Clear the table
        DB::table('users')->truncate();

        // Insert a new entry with id 1 and name 'admin'
        DB::table('users')->insert([
            'id' => 1,
            'username' => 'admin',
            'email' => 'dummy@tubesbooster.com',
            'password' => 'admin',
            'display_name' => 'Tubesbooster Admin',
            'created_at' => date("Y-m-d")
        ]);

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Optionally, you can define how to revert this migration
        // For example, you could delete the entry with id 1
        DB::table('users')->where('id', 1)->delete();
    }
}
