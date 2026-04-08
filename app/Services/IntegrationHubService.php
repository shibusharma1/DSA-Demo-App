<?php
// In your DSA application:
// app/Services/IntegrationHubService.php

namespace App\Services;

// use Illuminate\Support\Facades\Http;
use Spatie\WebhookServer\WebhookCall;
use Illuminate\Support\Facades\Log;

class IntegrationHubService
{
    private string $baseUrl;
    private string $secret;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.integration_hub.url') ?? 'http://127.0.0.1:8001';
        $this->secret  = config('services.integration_hub.secret') ?? '';
        $this->apiKey  = config('services.integration_hub.api_key') ?? '';
    }

    public function fireEvent(string $eventType, string $entityType, int $entityId, array $payload): bool
    {
        // $body      = json_encode(compact('eventType', 'entityType', 'entityId', 'payload'));
        $ts        = time();
        // $signature = hash_hmac('sha256', "{$ts}.{$body}", $this->secret);

        try {

            //===============================================
            //Using Http request
            //===============================================
            // $response = Http::withHeaders([
            //     // 'X-DSA-Signature' => $signature,
            //     'X-DSA-Timestamp' => (string) $ts,
            //     // 'X-API-Key'       => $this->apiKey,
            //     'Content-Type'    => 'application/json',
            // ])->post($this->baseUrl . '/api/v1/events', [
            //     'connectors'  => ['erpnext'],
            //     'event_type'  => $eventType,
            //     'entity_type' => $entityType,
            //     'entity_id'   => $entityId,
            //     'payload'     => $payload,
            // ]);

            // Log::info("checking how many times the this function is called.");

            // if ($response->successful()) {
            //     Log::info('Integration Hub accepted event', [
            //         'event_type' => $eventType,
            //         'entity_id'  => $entityId,
            //         'event_id'   => $response->json('event_id'),
            //     ]);
            //     return true;
            // }

            // Log::warning('Integration Hub rejected event', [
            //     'status'   => $response->status(),
            //     'response' => $response->body(),
            // ]);
            // return false;

            //===============================================
            //Using Http request
            //===============================================
            WebhookCall::create()
                ->url($this->baseUrl.'/api/v1/events')
                ->payload([
                    'connectors'  => ['erpnext'],
                    'event_type'  => $eventType,
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'payload'     => $payload,
                ])
                ->useSecret('password')
                ->dispatch();

            Log::info("web hook dispatched");

            return true;
        } catch (\Exception $e) {
            // CRITICAL: never let IH failure break DSA
            // Just log and return false — DSA continues normally
            Log::error('Integration Hub unreachable', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
