<?php

namespace App\Services;

use App\Models\IntegrationAccount;
use Illuminate\Support\Facades\Log;

class IntegrationTokenResolverService
{
    public function __construct(
        private IntegrationAccountService $accountService,
        private ERPTokenService $erpTokenService,
        private ZohoTokenService $zohoTokenService
    ) {}

    public function resolve(
        int $companyId,
        int $userId,
        string $provider,
        ?string $serviceType = null
    ): ?IntegrationAccount {

        $account = $this->accountService->findActiveAccount(
            companyId: $companyId,
            userId: $userId,
            provider: $provider,
            serviceType: $serviceType
        );

        if (!$account) {
            return null;
        }

        // Provider-specific refresh logic
        if ($provider === 'erpnext') {
            $token = $this->erpTokenService->getToken();

            if (!$token['success']) {
                Log::channel('frappy')->error('ERPNext token resolution failed', [
                    'message' => $token['message'],
                ]);

                return null;
            }

            // refresh token may update DB, so re-fetch latest
            return $account->fresh();
        }
        if ($provider === 'zoho') {

            if ($account->isTokenExpired()) {

                $token = $this->zohoTokenService->refreshToken($account);

                if (!$token['success']) {
                    Log::error('Zoho token refresh failed');
                    return null;
                }
            }

            return $account->fresh();
        }

        // Other providers can be added here later
        return $account;
    }
}
