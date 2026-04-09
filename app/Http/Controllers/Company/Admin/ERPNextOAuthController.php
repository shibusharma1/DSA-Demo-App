<?php


namespace App\Http\Controllers\Company\Admin;

use App\Http\Controllers\Controller;
use App\Services\IntegrationAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class ERPNextOAuthController extends Controller
{
    public function __construct(
        private IntegrationAccountService $accountService
    ) {}

    // Show the connect page with the OAuth button
    public function showConnect()
    {
        $codeVerifier  = $this->generateCodeVerifier();
        $codeChallenge = $this->generateCodeChallenge($codeVerifier);
        $state         = Str::random(40);

        Session::put('erpnext_code_verifier', $codeVerifier);
        Session::put('erpnext_oauth_state', $state);

        $redirectUri = config('erpnext.redirect_uri');
        $baseUrl     = config('erpnext.base_url');

        $params = http_build_query([
            'client_id'             => config('erpnext.client_id'),
            'response_type'         => 'code',
            'scope'                 => config('erpnext.scopes'),
            'redirect_uri'          => $redirectUri,
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        $authUrl    = "{$baseUrl}/api/method/frappe.integrations.oauth2.authorize?{$params}";
        $redirectUri = $redirectUri;

        // Check if already connected
        $account = $this->accountService->getActiveAccount('erpnext', 'erpnext_cloud');

        Log::channel('frappy')->info('ERPNext connect page loaded', [
            'already_connected' => (bool) $account,
            'auth_url'          => $authUrl,
        ]);

        return view('erpnext.connect', compact('authUrl', 'redirectUri', 'account'));
    }

    // Handle OAuth callback from Frappe
    public function callback(Request $request)
    {
        Log::channel('frappy')->info('ERPNext OAuth callback received', [
            'params' => $request->all(),
        ]);

        $code  = $request->query('code');
        $state = $request->query('state');
        $error = $request->query('error');

        if ($error) {
            Log::channel('frappy')->error('ERPNext OAuth error in callback', [
                'error'       => $error,
                'description' => $request->query('error_description'),
            ]);
            return $this->renderCallbackResponse([
                'success' => false,
                'error'   => $error,
                'message' => $request->query('error_description', 'Authentication failed'),
            ]);
        }

        // Validate state to prevent CSRF
        $savedState = Session::get('erpnext_oauth_state');
        if (!$state || $state !== $savedState) {
            Log::channel('frappy')->error('ERPNext OAuth state mismatch', [
                'received' => $state,
                'expected' => $savedState,
            ]);
            return $this->renderCallbackResponse([
                'success' => false,
                'error'   => 'invalid_state',
                'message' => 'Invalid state parameter. Please try again.',
            ]);
        }

        $codeVerifier = Session::get('erpnext_code_verifier');
        if (!$codeVerifier) {
            return $this->renderCallbackResponse([
                'success' => false,
                'error'   => 'no_verifier',
                'message' => 'Code verifier not found. Please try again.',
            ]);
        }

        $baseUrl     = config('erpnext.base_url');
        $redirectUri = config('erpnext.redirect_uri');

        try {
            $response = Http::asForm()->post(
                "{$baseUrl}/api/method/frappe.integrations.oauth2.get_token",
                [
                    'client_id'     => config('erpnext.client_id'),
                    'client_secret' => config('erpnext.client_secret'),
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                    'redirect_uri'  => $redirectUri,
                    'code_verifier' => $codeVerifier,
                ]
            );

            Log::channel('frappy')->info('ERPNext token exchange response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            if (!$response->successful()) {
                Log::channel('frappy')->error('ERPNext token exchange failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return $this->renderCallbackResponse([
                    'success' => false,
                    'error'   => 'token_exchange_failed',
                    'message' => 'Failed to get access token from ERPNext',
                    'details' => $response->json(),
                ]);
            }

            $tokens = $response->json();

            // Save tokens to integration_accounts table
            $this->accountService->saveTokenResponse([
                'company_id'      => 1,
                'user_id'         => 1,
                'provider'        => 'erpnext',
                'service_type'    => 'erpnext_cloud',
                'organization_id' => $baseUrl,
                'client_id'       => config('erpnext.client_id'),
                'client_secret'   => config('erpnext.client_secret'),
                'redirect_uri'    => $redirectUri,
                'api_base_url'    => $baseUrl,
                'token_response'  => $tokens,
                'settings'        => ['accounts_url' => $baseUrl],
            ]);

            // Clean up session
            Session::forget(['erpnext_code_verifier', 'erpnext_oauth_state']);

            // Also update connector_configs for the Integration Hub pipeline
            // $this->updateConnectorConfig($tokens, $baseUrl);

            Log::channel('frappy')->info('ERPNext OAuth completed successfully');

            return $this->renderCallbackResponse([
                'success' => true,
                'message' => 'Successfully connected to ERPNext!',
            ]);
        } catch (\Exception $e) {
            Log::channel('frappy')->error('ERPNext OAuth exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->renderCallbackResponse([
                'success' => false,
                'error'   => 'exception',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // Disconnect ERPNext
    public function disconnect()
    {
        $account = $this->accountService->getActiveAccount('erpnext', 'erpnext_cloud');

        if ($account) {
            $account->update([
                'is_active'  => false,
                'revoked_at' => now(),
            ]);

            Log::channel('frappy')->info('ERPNext disconnected', [
                'account_id' => $account->id,
            ]);
        }

        // Also deactivate in connector_configs
        // \App\Models\ConnectorConfig::where('connector_slug', 'erpnext')
        //     ->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Disconnected from ERPNext']);
    }

    // Health check
    public function ping()
    {
        $erp = app(\App\Services\ERPNextApiService::class);

        try {
            $response = $erp->get(config('erpnext.base_url') . '/api/v2/method/ping');

            Log::channel('frappy')->info('ERPNext ping', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);

            return response()->json([
                'success'   => $response->successful(),
                'status'    => $response->status(),
                'response'  => $response->json(),
            ]);
        } catch (\Exception $e) {
            Log::channel('frappy')->error('ERPNext ping failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // -------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------

    // private function updateConnectorConfig(array $tokens, string $baseUrl): void
    // {
    //     \App\Models\ConnectorConfig::updateOrCreate(
    //         [
    //             'tenant_id'      => 'dsa',
    //             'connector_slug' => 'erpnext',
    //         ],
    //         [
    //             'is_active'   => true,
    //             'credentials' => [
    //                 'access_token'  => $tokens['access_token'],
    //                 'refresh_token' => $tokens['refresh_token'] ?? null,
    //                 'base_url'      => $baseUrl,
    //             ],
    //             'settings'    => ['api_base_url' => $baseUrl],
    //             'entity_map'  => ['customer', 'invoice', 'payment', 'sales_order', 'item'],
    //         ]
    //     );

    //     Log::channel('frappy')->info('ConnectorConfig updated for erpnext');
    // }

    private function renderCallbackResponse(array $data): \Illuminate\Http\Response
    {
        $json    = json_encode($data);
        $message = $data['success']
            ? 'Authentication successful! Closing window...'
            : 'Authentication failed: ' . ($data['message'] ?? 'Unknown error');

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <title>ERPNext Authentication</title>
            <script>
                try {
                    if (window.opener) {
                        window.opener.postMessage({$json}, window.location.origin);
                    }
                } catch(e) {
                    console.error('postMessage failed:', e);
                }
                setTimeout(function() { window.close(); }, 1500);
            </script>
            <style>
                body { font-family: sans-serif; display: flex; justify-content: center;
                       align-items: center; min-height: 100vh; margin: 0;
                       background: #f5f5f5; }
                .box { background: white; padding: 30px; border-radius: 8px;
                       text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .success { color: #155724; }
                .error   { color: #721c24; }
            </style>
        </head>
        <body>
            <div class="box">
                <p class="{$this->statusClass($data['success'])}">{$message}</p>
            </div>
        </body>
        </html>
        HTML;

        return response($html);
    }

    private function statusClass(bool $success): string
    {
        return $success ? 'success' : 'error';
    }

    private function generateCodeVerifier(int $length = 64): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }

    private function generateCodeChallenge(string $codeVerifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }
}
