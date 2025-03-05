<?php
namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\PartnerPrice;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()->get();
        if ($users->count() == 0) {
            $user = [
                [
                    'country_id' => null,
                    'first_name' => 'Super',
                    'middle_name' => '',
                    'last_name' => 'Admin',
                    'username' => 'admin',
                    'user_type' => 'admin',
                    'email' => 'superadmin@gmail.com',
                    'password' => Hash::make(123456),
                    'is_real' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'sub_domain_prefix' => '',
                ],
                [
                    'country_id' => 1,
                    'first_name' => 'John',
                    'middle_name' => 'Alexander',
                    'last_name' => 'Smith',
                    'username' => 'london',
                    'user_type' => 'partner',
                    'email' => 'london@gmail.com',
                    'password' => Hash::make(123456),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'is_real' => 0,
                    'sub_domain_prefix' => 'uk',
                ],
                [
                    'country_id' => 2,
                    'first_name' => 'Israel',
                    'middle_name' => null,
                    'last_name' => null,
                    'username' => 'israel',
                    'user_type' => 'partner',
                    'email' => 'israel@a.com',
                    'password' => Hash::make(123456),
                    'created_at' => now(),
                    'updated_at' => now(),
                    'is_real' => 1,
                    'sub_domain_prefix' => 'il',
                ],
            ];
            User::query()->insert($user);
            $usersData = User::where('user_type', 'partner')->get(); // Get only 'partner' users

            foreach ($usersData as $user) {
                // Get the country code and currency id based on the user's country_id
                $country = Country::find($user->country_id);
                $currency = Currency::where('code', $country->currency)->first();

                // Set the price based on the country_id
                $price = null;
                if ($user->country_id == 1) {
                    $price = 9.9; // Price for country_id = 1
                } elseif ($user->country_id == 2) {
                    $price = 20.99; // Price for country_id = 2
                }

                // If price is set, check if PartnerPrice already exists and update if necessary
                if ($price !== null) {
                    $existingPrice = PartnerPrice::where('partner_id', $user->id)
                        ->where('country_code', $country->code)
                        ->first();

                    // If a PartnerPrice exists, update it, otherwise create a new one
                    if ($existingPrice) {
                        $existingPrice->update([
                            'price' => $price,
                            'currency_id' => $currency->id,
                            'updated_at' => now(),
                        ]);
                    } else {
                        PartnerPrice::create([
                            'partner_id' => $user->id,
                            'country_code' => $country->code,
                            'price' => $price,
                            'currency_id' => $currency->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
