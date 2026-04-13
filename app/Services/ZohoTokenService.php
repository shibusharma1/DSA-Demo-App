<?php

namespace App\Services;

use App\Models\IntegrationAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZohoTokenService
{
    public function refreshToken(IntegrationAccount $account): array
    {
        try {
            $response = Http::asForm()->post(
                config('services.zoho.accounts_url') . '/oauth/v2/token',
                [
                    'refresh_token' => $account->refresh_token,
                    'client_id'     => $account->client_id,
                    'client_secret' => $account->client_secret,
                    'grant_type'    => 'refresh_token',
                ]
            );

            if ($response->failed()) {
                Log::error('Zoho token refresh failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return ['success' => false];
            }

            $data = $response->json();

            $account->update([
                'access_token' => $data['access_token'],
                'expires_in'   => $data['expires_in'],
                'access_token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
                'token_response' => $data,
            ]);

            return ['success' => true, 'access_token' => $data['access_token']];
        } catch (\Exception $e) {
            Log::error('Zoho refresh exception', ['error' => $e->getMessage()]);
            return ['success' => false];
        }
    }
}
