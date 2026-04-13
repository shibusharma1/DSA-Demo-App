<?php

namespace App\Http\Controllers\Company\Admin;

use App\Http\Controllers\Controller;
use App\Services\IntegrationAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ZohoOAuthController extends Controller
{
    public function __construct(
        private IntegrationAccountService $accountService
    ) {}

    /**
     * Redirect to Zoho OAuth
     */
    public function redirect()
    {
        $state = Str::random(40);
        Session::put('zoho_oauth_state', $state);

        $params = http_build_query([
            'client_id'     => config('services.zoho.client_id'),
            'response_type' => 'code',
            'redirect_uri'  => config('services.zoho.redirect'),
            'scope'         => 'ZohoBooks.fullaccess.all',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        $authUrl = config('services.zoho.accounts_url') . '/oauth/v2/auth?' . $params;

        Log::info('Zoho OAuth redirect', [
            'auth_url' => $authUrl,
        ]);

        return redirect($authUrl);
    }

    /**
     * Handle Zoho OAuth callback
     */
    public function callback(Request $request)
    {
        Log::info('Zoho callback received', [
            'query' => $request->all(),
        ]);

        // ❌ Handle errors from Zoho
        if ($request->has('error')) {
            return response()->json([
                'success' => false,
                'error'   => $request->get('error'),
                'message' => $request->get('error_description', 'Zoho auth failed'),
            ], 400);
        }

        // ✅ Validate state
        $state = $request->get('state');
        $savedState = Session::get('zoho_oauth_state');

        if (!$state || $state !== $savedState) {
            Log::error('Zoho state mismatch', [
                'received' => $state,
                'expected' => $savedState,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid state',
            ], 400);
        }

        $code = $request->get('code');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Authorization code missing',
            ], 400);
        }

        try {
            // 🔥 STEP 1: Exchange code for tokens
            $tokenResponse = Http::asForm()->post(
                config('services.zoho.accounts_url') . '/oauth/v2/token',
                [
                    'client_id'     => config('services.zoho.client_id'),
                    'client_secret' => config('services.zoho.client_secret'),
                    'redirect_uri'  => config('services.zoho.redirect'),
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                ]
            );

            if ($tokenResponse->failed()) {
                Log::error('Zoho token exchange failed', [
                    'status' => $tokenResponse->status(),
                    'body'   => $tokenResponse->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Token exchange failed',
                ], 500);
            }

            $tokens = $tokenResponse->json();

            Log::info('Zoho tokens received', [
                'has_access_token'  => isset($tokens['access_token']),
                'has_refresh_token' => isset($tokens['refresh_token']),
            ]);

            // 🔥 STEP 2: Fetch organization_id
            $orgResponse = Http::withToken($tokens['access_token'])
                ->get(config('services.zoho.api_base') . '/books/v3/organizations');

            if ($orgResponse->failed()) {
                Log::error('Zoho organization fetch failed', [
                    'status' => $orgResponse->status(),
                    'body'   => $orgResponse->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch organization',
                ], 500);
            }

            $organizations = $orgResponse->json('organizations', []);

            $organizationId = $organizations[0]['organization_id'] ?? null;

            if (!$organizationId) {
                Log::error('No organization found in Zoho response', [
                    'response' => $orgResponse->json(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No organization found',
                ], 500);
            }

            Log::info('Zoho organization fetched', [
                'organization_id' => $organizationId,
            ]);

            // 🔥 STEP 3: Save to DB
            $account = $this->accountService->saveTokenResponse([
                'company_id'      => 1,
                'user_id'         => 1,
                'provider'        => 'zoho',
                'service_type'    => 'zoho_books',
                'organization_id' => $organizationId,

                'client_id'       => config('services.zoho.client_id'),
                'client_secret'   => config('services.zoho.client_secret'),
                'redirect_uri'    => config('services.zoho.redirect'),
                'api_base_url'    => config('services.zoho.api_base'),

                'token_response'  => $tokens,

                'settings' => [
                    'accounts_url' => config('services.zoho.accounts_url'),
                ],
            ]);

            // 🔥 Clean session
            Session::forget('zoho_oauth_state');

            Log::info('Zoho account saved successfully', [
                'account_id' => $account->id,
            ]);

            return response()->json([
                'success'         => true,
                'message'         => 'Zoho connected successfully',
                'organization_id' => $organizationId,
                'account_id'      => $account->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Zoho OAuth exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect Zoho
     */
    public function disconnect()
    {
        $account = $this->accountService->getActiveAccount('zoho', 'zoho_books');

        if ($account) {
            $account->update([
                'is_active'  => false,
                'revoked_at' => now(),
            ]);

            Log::info('Zoho disconnected', [
                'account_id' => $account->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Zoho disconnected successfully',
        ]);
    }
}