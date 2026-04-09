<?php
// app/Services/ERPTokenService.php
namespace App\Services;

use App\Models\IntegrationAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ERPTokenService
{
    private IntegrationAccountService $accountService;

    public function __construct(IntegrationAccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function getToken(): array
    {
        $account = $this->accountService->getActiveAccount('erpnext', 'erpnext_cloud');

        if (!$account) {
            Log::channel('frappy')->error('No active ERPNext account found in database');
            return ['success' => false, 'message' => 'ERPNext not connected. Please connect first.'];
        }

        if (!$account->isTokenExpired()) {
            Log::channel('frappy')->debug('Using cached access token', [
                'expires_at' => $account->access_token_expires_at,
            ]);
            return ['success' => true, 'access_token' => $account->access_token];
        }

        Log::channel('frappy')->info('Access token expired. Refreshing...', [
            'account_id' => $account->id,
        ]);

        return $this->refreshToken($account);
    }

    private function refreshToken(IntegrationAccount $account): array
    {
        try {
            $baseUrl = $account->api_base_url ?? config('erpnext.base_url');

            $response = Http::asForm()->post(
                "{$baseUrl}/api/method/frappe.integrations.oauth2.get_token",
                [
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $account->refresh_token,
                    'client_id'     => $account->client_id ?? config('erpnext.client_id'),
                    'client_secret' => $account->client_secret ?? config('erpnext.client_secret'),
                ]
            );

            Log::channel('frappy')->info('Token refresh response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if ($response->failed()) {
                Log::channel('frappy')->error('Token refresh failed', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);
                return ['success' => false, 'message' => 'Failed to refresh ERPNext token'];
            }

            $data      = $response->json();
            $expiresIn = $data['expires_in'] ?? 3600;

            $account->update([
                'access_token'           => $data['access_token'],
                'refresh_token'          => $data['refresh_token'] ?? $account->refresh_token,
                'expires_in'             => $expiresIn,
                'access_token_expires_at'=> Carbon::now()->addSeconds((int) $expiresIn),
                'token_response'         => $data,
            ]);

            Log::channel('frappy')->info('Token refreshed and saved', [
                'account_id' => $account->id,
            ]);

            return ['success' => true, 'access_token' => $data['access_token']];

        } catch (\Exception $e) {
            Log::channel('frappy')->error('Exception during token refresh', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}