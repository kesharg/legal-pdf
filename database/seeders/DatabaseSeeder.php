<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LanguagesTableSeeder::class,
            CountriesTableSeeder::class,
            CurrencyTableSeeder::class,
            UserTableSeeder::class,
            // CountryStateSeeder::class,
            // CustomerSeeder::class,
            LocalizationsTableSeeder::class,
            FlagSeeder::class
        ]);
    }
}
