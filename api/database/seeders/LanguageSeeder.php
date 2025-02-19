<?php

  

namespace Database\Seeders;

  

use Illuminate\Database\Seeder;

use App\Models\Language;

  

class LanguageSeeder extends Seeder

{

    /**

     * Run the database seeds.

     *

     * @return void

     */

    public function run()

    {

        Language::truncate();

  

        $languages = [
            ['name' => 'English'],
            ['name' => 'Albanian'],
            ['name' => 'Arabic'],
            ['name' => 'Chinese'],
            ['name' => 'Croatian'],
            ['name' => 'Czech'],
            ['name' => 'Finnish'],
            ['name' => 'French'],
            ['name' => 'German'],
            ['name' => 'Greek'],
            ['name' => 'Hindi'],
            ['name' => 'Hungarian'],
            ['name' => 'Indonesian'],
            ['name' => 'Italian'],
            ['name' => 'Japanese'],
            ['name' => 'Korean'],
            ['name' => 'Nederlands'],
            ['name' => 'Norwegian'],
            ['name' => 'Polish'],
            ['name' => 'Portuguese'],
            ['name' => 'Romanian'],
            ['name' => 'Russian'],
            ['name' => 'Serbian'],
            ['name' => 'Slovak'],
            ['name' => 'Spanish'],
            ['name' => 'Swedish'],
            ['name' => 'Thai'],
            ['name' => 'Turkish'],
            ['name' => 'Ukrainian'],
            ['name' => 'Vietnamese'],
            ['name' => 'Other'],
        ];

          

        foreach ($languages as $key => $value) {

            Language::create($value);

        }

    }

}