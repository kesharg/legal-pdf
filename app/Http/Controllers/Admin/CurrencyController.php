<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::get();
        return view('dashboard.version1.currencies.index', compact('currencies'));
    }

    public function edit($id)
    {
        $data['currency'] = Currency::where('id', $id)->first();

        return view("dashboard.version1.currencies.edit")->with($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency'    => 'required|string',
            'code' => 'required|string|unique:currencies,code|size:3',
            'symbol'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        try {
            DB::beginTransaction();
            $data = $request->all();
            $data['currency'] = Str::title($data['currency']);
            $data['code'] = Str::upper($data['code']);
            Currency::create($data);
            DB::commit();
            flashMessage("Currency created successfully");
            return to_route("admin.currencies.index");
        } catch (\Throwable $e) {
            DB::rollBack();
            commonLog("Failed to store currency", errorArray($e));
            ddError($e);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'currency'    => 'required|string',
            'code' => 'required|string|unique:currencies,code,' . $id . '|size:3',
            'symbol'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        try {
            DB::beginTransaction();
            $currency = Currency::findOrFail($id);
            $data = $request->all();
            $data['currency'] = Str::title($data['currency']);
            $data['code'] = Str::upper($data['code']);
            $currency->update($data);
            DB::commit();
            flashMessage("Currency updated successfully");
            return to_route("admin.currencies.index");
        } catch (\Throwable $e) {
            DB::rollBack();
            commonLog("Failed to update currency", errorArray($e));
            ddError($e);
        }
    }

    protected function validateCurrency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency'    => 'required|string',
            'code'        => 'required|string',
            'symbol'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
    }

    public function destroy(Request $request, Currency $currency)
    {
        try{

            $currency->delete();

            flashMessage("Currency deleted successfully");

            return to_route("admin.currencies.index");
        }
        catch(\Throwable $e){
            commonLog("Failed to Delete Currency", errorArray($e));

            ddError($e);
        }
    }
}
