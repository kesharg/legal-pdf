<?php

namespace App\Http\Controllers\Admin\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PartnerStoreRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Models\User;
use App\Models\PartnerPrice;
use App\Services\Models\User\PartnerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    public function index(Request $request, PartnerService $partnerService)
    {
        $partners = $partnerService->getAll(true);
        return view('dashboard.version1.partners.index', compact('partners'));
    }

    public function create()
    {
        $data['countries']  = Country::with('language', 'currencies')->get();
        // return $data;
        return view('dashboard.version1.partners.add_partner')->with($data);
    }

    public function store(PartnerStoreRequest $request, PartnerService $partnerService)
    {
        $input = $request->all();
        $input['user_type'] = 'partner';  // Ensure this user is a partner.

        try {
            // Start a transaction to ensure atomicity
            DB::beginTransaction();

            // Store the partner
            $partner = $partnerService->store($input);

            // After storing the partner, insert the price into partner_prices
            if (isset($input['price']) && $partner) {
                $country = Country::find($input['country_id']);
                $currency = Currency::where('code', $input['currency_code'])->first();

                // Create partner price
                DB::table('partner_prices')->insert([
                    'partner_id' => $partner->id,  // Use the partner's ID
                    'price' => $input['price'],
                    'country_code' => $country->code,
                    'currency_id' => $currency->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Commit the transaction if both operations succeed
            DB::commit();

            // Flash success message
            flashMessage("Partner created successfully");

            // Redirect to the partner index page
            return to_route("admin.partners.index");
        } catch (\Throwable $e) {
            // Rollback the transaction in case of any error
            DB::rollBack();

            // Log the error
            commonLog("Failed to store partner", errorArray($e));

            // Optionally display error message
            ddError($e);
        }
    }


    public function edit($partner)
    {
        // return $partner;
        $data['countries']  = Country::with('language', 'currencies')->get();
        $data["user"]       = User::with('prices')->where('id', $partner)->first();
        // return $data;
        unset($data["user"]->password);
        $totalPrice = $data["user"]->prices->sum('price');
        $data["user"]->total_price = $totalPrice;
        return view("dashboard.version1.partners.edit_partner")->with($data);
    }


    public function update(Request $request, $partner)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $partner,
                'mobile_no' => 'nullable|string|max:20',
                'username' => 'required|string|max:255|unique:users,username,' . $partner,
                'password' => 'nullable|string|min:8',
                'is_active' => 'required|boolean',
                'country_id' => 'required|exists:countries,id',
                'sub_domain_prefix' => 'nullable|string|max:255',
                'currency' => 'nullable|string|max:255',
                'currency_code' => 'nullable|string|max:255',
                'language' => 'nullable|string|max:255',
                'language_code' => 'nullable|string|max:255',
                'company_email' => 'nullable|email|max:255',
                'company_name' => 'nullable|string|max:255',
                'company_no' => 'nullable|string|max:20',
                'company_website' => 'nullable|max:255',
                'company_address' => 'nullable|string|max:255',
                'price' => 'required|numeric',
            ]);

            $user = User::findOrFail($partner);

            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'mobile_no' => $validated['mobile_no'],
                'username' => $validated['username'],
                'password' => $validated['password'] ? bcrypt($validated['password']) : $user->password, // Encrypt password if provided
                'is_active' => $validated['is_active'],
                'country_id' => $validated['country_id'],
                'sub_domain_prefix' => $validated['sub_domain_prefix'],
                'currency' => $validated['currency'],
                'currency_code' => $validated['currency_code'],
                'language' => $validated['language'],
                'language_code' => $validated['language_code'],
                'company_email' => $validated['company_email'],
                'company_name' => $validated['company_name'],
                'company_no' => $validated['company_no'],
                'company_website' => $validated['company_website'],
                'company_address' => $validated['company_address'],
            ]);
            $country = Country::find($validated['country_id']);
            $currency = Currency::where('code', $validated['currency_code'])->first();

            PartnerPrice::updateOrCreate(
                ['partner_id' => $partner],
                [
                    'price' => $validated['price'],
                    'country_code' => $country->code,
                    'currency_id' => $currency->id,
                ]
            );

            flashMessage("Partner Account deleted successfully");

            return to_route("admin.partners.index");
        } catch (\Throwable $e) {
            DB::rollBack();
            commonLog("Failed to store partner", errorArray($e));

            ddError($e);
        }
    }




    public function destroy(Request $request, User $partner)
    {
        try {

            $partner->delete();

            flashMessage("Partner Account deleted successfully");

            return to_route("admin.partners.index");
        } catch (\Throwable $e) {
            commonLog("Failed to Delete Partner", errorArray($e));

            ddError($e);
        }
    }
}
