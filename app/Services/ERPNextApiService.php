<?php
// app/Services/ERPNextApiService.php
namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ERPNextApiService
{
    private ERPTokenService $tokenService;
    private string $baseUrl;

    public function __construct(ERPTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
        $this->baseUrl      = config('erpnext.base_url');
    }

    private function token(): string
    {
        $result = $this->tokenService->getToken();

        if (!$result['success']) {
            Log::channel('frappy')->error('Cannot get ERPNext token', [
                'message' => $result['message'],
            ]);
            throw new \RuntimeException($result['message']);
        }

        return $result['access_token'];
    }

    public function resource(string $doctype): string
    {
        return "{$this->baseUrl}/api/resource/{$doctype}";
    }

    public function get(string $url, array $params = []): Response
    {
        Log::channel('frappy')->debug('ERPNext GET', ['url' => $url, 'params' => $params]);

        $response = Http::withToken($this->token())->get($url, $params);

        Log::channel('frappy')->debug('ERPNext GET Response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        return $response;
    }

    public function post(string $url, array $data = []): Response
    {
        Log::channel('frappy')->debug('ERPNext POST', ['url' => $url, 'data' => $data]);

        $response = Http::withToken($this->token())->post($url, $data);

        Log::channel('frappy')->info('ERPNext POST Response', [
            'status'  => $response->status(),
            'success' => $response->successful(),
            'body'    => $response->json(),
        ]);

        return $response;
    }

    public function put(string $url, array $data = []): Response
    {
        Log::channel('frappy')->debug('ERPNext PUT', ['url' => $url, 'data' => $data]);

        $response = Http::withToken($this->token())->put($url, $data);

        Log::channel('frappy')->info('ERPNext PUT Response', [
            'status'  => $response->status(),
            'success' => $response->successful(),
            'body'    => $response->json(),
        ]);

        return $response;
    }

    public function delete(string $url): Response
    {
        Log::channel('frappy')->debug('ERPNext DELETE', ['url' => $url]);

        $response = Http::withToken($this->token())->delete($url);

        Log::channel('frappy')->info('ERPNext DELETE Response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        return $response;
    }

    public function extractError(Response $response): string
    {
        $json = $response->json();

        $errorMessage = $json['exception'] ?? 'Something went wrong';

        if (!empty($json['_server_messages'])) {
            $serverMessages = json_decode($json['_server_messages'], true);
            if (!empty($serverMessages[0])) {
                $decoded = json_decode($serverMessages[0], true);
                if (!empty($decoded['message'])) {
                    $errorMessage = strip_tags($decoded['message']);
                }
            }
        }

        Log::channel('frappy')->error('ERPNext API Error', [
            'status'  => $response->status(),
            'message' => $errorMessage,
            'body'    => $json,
        ]);

        return $errorMessage;
    }
}