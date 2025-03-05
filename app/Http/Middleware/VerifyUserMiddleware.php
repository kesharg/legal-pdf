<?php

namespace App\Http\Middleware;

use Closure;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class VerifyUserMiddleware
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
        $excludedRoutes = [
            'oauth/gmail/callback', // Google callback route
            'oauth/gmail/logout',  // Google logout route
            'oauth/microsoft/callback', // Microsoft callback route
            'oauth/microsoft/logout'  // Microsoft logout route
        ];

        if (in_array($request->path(), $excludedRoutes)) {
            return $next($request);
        }


        try {
            $cookieLifetime = 60 * 24 * 30; // 30 days
            $authKey = $request->cookie('auth_key');

            if (!$authKey) {
                // Generate unique key if cookie is missing
                $authKey = Str::uuid()->toString();
            }

            $data = getSessionDataFromRedis();
        
            if ($data) {

                // Handle Gmail tokensKJk
                if (isset($data['microsoft_token'])) {
                    if (!session()->has('microsoft_session')) {
                        session()->put('microsoft_session', $data['main_token']);
                        session()->save();
                    }
                } else if (!session()->has('gmail_session')) {
                    session()->put('gmail_session', $data['main_token']);
                    session()->save();

                }
            }
            // Update the auth_key cookie
            cookie()->queue(cookie('auth_key', $authKey, $cookieLifetime));
        } catch (\Exception $e) {
            Log::error('Token Refresh Middleware Error:', ['message' => $e->getMessage()]);
        }
        return $next($request);
    }
}
