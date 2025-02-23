<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
            'key' => 'socialMedia',
            'value' => '{"1":{"name":"whatsapp","url":""},"2":{"name":"pinterest","url":""},"3":{"name":"instagram","url":""},"4":{"name":"reddit","url":""},"5":{"name":"facebook","url":""},"6":{"name":"tiktok","url":""},"7":{"name":"x","url":""}}'
        ]);
        DB::table('settings')->insert([
            'key' => 'mainMenu',
            'value' => '{"1":{"name":"Videos","type":1,"page":"a","target":"_self","status":1},"2":{"name":"Galleries","type":1,"page":"b","target":"_self","status":1},"3":{"name":"Channels","type":1,"page":"c","target":"_self","status":1},"4":{"name":"Models","type":1,"page":"d","target":"_self","status":1},"5":{"name":"Categories","type":1,"page":"e","target":"_self","status":1},"6":{"name":"Tags","type":1,"page":"f","target":"_self","status":1}}'
        ]); 
        DB::table('settings')->insert([
            'key' => 'siteKeywords',
            'value' => ''
        ]); 
    }
}
