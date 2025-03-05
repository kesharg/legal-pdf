<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Check if the countries table already has any records
        if (DB::table('countries')->count() == 0) {
            $countries = [
                ['name' => 'United Kingdom', 'code' => 'GB', 'flag' => '🇬🇧', 'sub_domain_prefix' => 'uk', 'language_code' => 'en', 'currency' => 'GBP'],
                ['name' => 'Israel', 'code' => 'IL', 'flag' => '🇮🇱', 'sub_domain_prefix' => 'il', 'language_code' => 'he', 'currency' => 'ILS'],
            ];

            DB::table('countries')->insert($countries);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}
