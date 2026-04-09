<?php
// app/Services/IntegrationAccountService.php
namespace App\Services;

use App\Models\IntegrationAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IntegrationAccountService
{
    public function saveTokenResponse(array $data): IntegrationAccount
    {
        $tokenResponse = $data['token_response'];
        $expiresIn     = $tokenResponse['expires_in'] ?? null;

        $accessTokenExpiresAt = $expiresIn
            ? Carbon::now()->addSeconds((int) $expiresIn)
            : null;

        Log::channel('frappy')->info('Saving token response', [
            'provider'     => $data['provider'],
            'service_type' => $data['service_type'] ?? null,
            'expires_at'   => $accessTokenExpiresAt,
        ]);

        $account = IntegrationAccount::updateOrCreate(
            [
                'company_id'      => $data['company_id'] ?? null,
                'provider'        => $data['provider'],
                'service_type'    => $data['service_type'] ?? null,
                'organization_id' => $data['organization_id'] ?? null,
            ],
            [
                'user_id'      => $data['user_id'] ?? null,
                'account_id'   => $data['account_id'] ?? null,
                'client_id'    => $data['client_id'] ?? null,
                'client_secret' => $data['client_secret'] ?? null,
                'auth_url'     => $data['auth_url'] ?? null,
                'token_url'    => $data['token_url'] ?? null,
                'redirect_uri' => $data['redirect_uri'] ?? null,
                'api_base_url' => $data['api_base_url'] ?? null,

                'access_token'  => $tokenResponse['access_token'] ?? null,
                'refresh_token' => $tokenResponse['refresh_token'] ?? null,
                'id_token'      => $tokenResponse['id_token'] ?? null,
                'token_type'    => $tokenResponse['token_type'] ?? null,
                'scope'         => $tokenResponse['scope'] ?? null,

                'expires_in'             => $expiresIn,
                'access_token_expires_at' => $accessTokenExpiresAt,

                'token_response' => $tokenResponse,
                'settings'       => $data['settings'] ?? null,
                'meta'           => $data['meta'] ?? null,

                'is_active'  => true,
                'revoked_at' => null,
            ]
        );

        Log::channel('frappy')->info('Token saved successfully', [
            'account_id' => $account->id,
            'provider'   => $account->provider,
        ]);

        return $account;
    }

    public function getActiveAccount(string $provider, string $serviceType = null): ?IntegrationAccount
    {
        return IntegrationAccount::where('provider', $provider)
            ->where('is_active', true)
            ->when($serviceType, fn($q) => $q->where('service_type', $serviceType))
            ->whereNull('revoked_at')
            ->latest()
            ->first();
    }


    //This is the function required for the API
    public function findActiveAccount(
        int $companyId,
        int $userId,
        string $provider,
        ?string $serviceType = null
    ): ?IntegrationAccount {

        return IntegrationAccount::query()
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('provider', $provider)
            ->when($serviceType, fn($q) => $q->where('service_type', $serviceType))
            ->where('is_active', true)
            ->latest()
            ->first();
    }
}
