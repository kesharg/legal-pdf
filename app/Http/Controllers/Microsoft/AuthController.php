<?php
// Copyright (c) Microsoft Corporation.
// Licensed under the MIT License.

namespace App\Http\Controllers\Microsoft;

use App\Http\Controllers\Controller;
use App\TokenStore\TokenCache;
use Illuminate\Http\Request;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;

class AuthController extends Controller
{
    public function signin()
    {
        // Initialize the OAuth client
        $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId' => config('azure.appId'),
            'clientSecret' => config('azure.appSecret'),
            'redirectUri' => config('azure.redirectUri'),
            'urlAuthorize' => config('azure.authority') . config('azure.authorizeEndpoint'),
            'urlAccessToken' => config('azure.authority') . config('azure.tokenEndpoint'),
            'urlResourceOwnerDetails' => '',
            'scopes' => config('azure.scopes')
        ]);

        $authUrl = $oauthClient->getAuthorizationUrl();

        // Save client state so we can validate in callback
        session(['oauthState' => $oauthClient->getState()]);

        info("Away URL : {$authUrl}");
        // Redirect to AAD signin page
        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        // Validate state
        $expectedState = session('oauthState');

        info("Expected oAuth State. {$expectedState}");

        $request->session()->forget('oauthState');
        $providedState = $request->query('state');

        if (!isset($expectedState)) {
            return redirect('/');
        }

        if (!isset($providedState) || $expectedState != $providedState) {
            info("Invalid Auth State. {$expectedState} - {$providedState}");
            return redirect('/')
                ->with('error', 'Invalid auth state')
                ->with('errorDetail', 'The provided auth state did not match the expected value');
        }

// Authorization code should be in the "code" query param
        $authCode = $request->query('code');
        if (isset($authCode)) {
            // Initialize the OAuth client
            $oauthClient = new \League\OAuth2\Client\Provider\GenericProvider([
                'clientId' => config('services.azure.clientId'),
                'clientSecret' => config('services.azure.clientSecret'),
                'redirectUri' => config('services.azure.redirectUri'),
                'urlAuthorize' => config('services.azure.authority') . config('services.azure.authorizeEndpoint'),
                'urlAccessToken' => config('services.azure.authority') . config('services.azure.tokenEndpoint'),
                'urlResourceOwnerDetails' => '',
                'scopes' => config('services.azure.scopes')
            ]);

            try {
                // Make the token request
                $accessToken = $oauthClient->getAccessToken('authorization_code', [
                    'code' => $authCode
                ]);

                $graph = new \Microsoft\Graph\Graph();
                $graph->setAccessToken($accessToken->getToken());
                $user = $graph->createRequest('GET', '/me?$select=displayName,mail,mailboxSettings,userPrincipalName')
                    ->setReturnType(\Microsoft\Graph\Model\User::class)
                    ->execute();

                if (!session()->has('userEmail')) {
                    $tokenCache = new TokenCache();
                    $tokenCache->storeTokens($accessToken, $user);
                }

                $data = getSessionDataFromRedis();

                $makeToken = [
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'tokenExpires' => $accessToken->getExpires(),
                    'userName' => $user->getDisplayName(),
                    'userEmail' => null !== $user->getMail() ? $user->getMail() : $user->getUserPrincipalName(),
                    'userTimeZone' => $user->getMailboxSettings()->getTimeZone()
                ];
                if ($data) {
                    // Check if 'main_token' exists and update accordingly
                    if (isset($data['main_token']) && !empty($data['main_token'])) {
                        if (isset($data['main_token']['userEmail']) && $data['main_token']['userEmail'] == $makeToken['userEmail']) {
                            // If the email matches, update the main_token
                            $data['main_token'] = $makeToken;
                        } else {
                            $data['pdf_token'] = $makeToken;  // Store as pdf_token if main_token email differs
                        }
                    } else {
                        // If no main_token exists, set the newly generated token as the main_token
                        $data['main_token'] = $makeToken;
                    }
                } else {
                    $data['main_token'] = $makeToken;
                    $data['microsoft_token'] = true;
                    //$authKey = $makeToken['accessToken'];
                }

                setSessionDataInRedis($data);

                return redirect('/');
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
                info("Failed to login Microsoft : " . json_encode(errorArray($e)));
                return redirect('/')
                    ->with('error', 'Error requesting access token')
                    ->with('errorDetail', json_encode($e->getResponseBody()));
            }
        }

        info("Error Found : " . $request->query('error_description'));

        return redirect('/')
            ->with('error', $request->query('error'))
            ->with('errorDetail', $request->query('error_description'));
    }

    public function signout()
    {
        try {

            removeSessionDataFromRedis();

            // Set the cookie to expire immediately
            session()->invalidate();

            // Redirect the user to the home page after successful logout
            return redirect('/');

        } catch (\Exception $e) {

            // Redirect back with an error message if logout fails
            return redirect()->to('/');
        }
    }
}
