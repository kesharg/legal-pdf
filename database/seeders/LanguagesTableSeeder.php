<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Check if the languages table already has any records
        if (DB::table('languages')->count() == 0) {
            $languages = [
                ['name' => 'English', 'code' => 'en', 'direction' => 'ltr'],
                ['name' => 'Hebrew', 'code' => 'he', 'direction' => 'rtl'],
            ];

            foreach ($languages as $language) {
                // Check if the language already exists in the database
                $exists = DB::table('languages')
                    ->where('code', $language['code'])
                    ->exists();

                if (!$exists) {
                    // Insert the language if it doesn't exist
                    Language::create($language);
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    }
}
