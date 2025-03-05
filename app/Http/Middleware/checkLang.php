<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Support\Str;

class checkLang
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only proceed if 'lang' and 'lang-direction' are not already set in the session
        if (!session()->has('lang') || !session()->has('lang-direction')) {
            try {
                // Retrieve user's location details via GeoIP
                $geoDetails = geoip($request->ip());
                $countryCode = Str::lower($geoDetails->iso_code);
                $country = Country::where('code', $countryCode)->first();

                if ($country) {
                    // Fetch the associated language
                    $language = Language::where('code', Str::lower($country->language_code))->first();

                    if ($language) {
                        session()->put('lang-direction', $language->direction);
                    }

                    session()->put('lang', $country->language_code);
                } else {
                    // Default to English if country is not found
                    session()->put('lang', 'en');
                    session()->put('lang-direction', 'ltr');
                }
            } catch (\Exception $e) {
                // Handle errors (e.g., GeoIP service failure)
                session()->put('lang', 'en');
                session()->put('lang-direction', 'ltr');
            }
            session()->save();
        }
        return $next($request);
    }
}
