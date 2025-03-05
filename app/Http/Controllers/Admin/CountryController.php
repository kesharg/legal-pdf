<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrackModelStoreRequest;
use App\Http\Requests\Admin\DistrackModelUpdateRequest;
use App\Http\Requests\Admin\CountryStoreRequest;
use App\Http\Requests\Admin\CountryUpdateRequest;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Flag;
use App\Models\Language;
use App\Models\State;
use App\Models\User;
use App\Services\Models\Country\CountryService;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(Request $request, CountryService $countryService)
    {
        $currencies = Currency::get();
        $languages = Language::get();
        $flags = Flag::get();
        $options = [
            'flags'         => $flags,
            'currencies'    => $currencies,
            'languages'     => $languages
        ];
        $countries = $countryService->getAll(true);
        return view('dashboard.version1.countries.index', compact('countries', 'options'));
    }

    public function create(Request $request)
    {
        $data['countries']  = Country::query()->get();
        $data['currencies']  = Currency::query()->get();
        $data['languages']  = Language::query()->get();

        return view('dashboard.version1.countries.add')->with($data);
    }

    public function store(CountryStoreRequest $request, CountryService $countryService)
    {
        $input = $request->all();

        try{
            \DB::beginTransaction();
            $country = $countryService->store($input);
            \DB::commit();


            flashMessage("Country created successfully");

            return to_route("admin.countries.index");
        }
        catch(\Throwable $e){
            \DB::rollBack();
            commonLog("Failed to store country", errorArray($e));

            ddError($e);

        }
    }

    public function edit(Country $country)
    {
        $data['country'] = $country;
        $data['currencies']  = Currency::query()->get();
        $data['flags']  = Flag::query()->get();
        $data['languages']  = Language::query()->get();

        return view("dashboard.version1.countries.edit")->with($data);
    }

    public function update(CountryUpdateRequest $request, Country $country, CountryService $countryService)
    {
        try{
            \DB::beginTransaction();
            $country = $countryService->update($country, $request->all());
            \DB::commit();


            flashMessage("Country updated successfully");

            return to_route("admin.countries.index");

        }
        catch(\Throwable $e){
            \DB::rollBack();
            commonLog("Failed to store country", errorArray($e));

            ddError($e);
        }
    }

    public function destroy(Request $request, Country $country)
    {
        try{
            if($country->users()->exists()){
                flashMessage("Country could not be deleted, because it is associated with users");
                return to_route("admin.countries.index");

            }
            $country->delete();

            flashMessage("Country deleted successfully");

            return to_route("admin.countries.index");
        }
        catch(\Throwable $e){
            commonLog("Failed to Delete Country", errorArray($e));

            ddError($e);
        }
    }
    public function getStateByCountry(Request $request, $country_id)
    {
        return State::where('country_id', $request->country_id)->get();
    }
}
