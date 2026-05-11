<?php

namespace App\Services;

use Spatie\WebhookServer\WebhookCall;
use Illuminate\Support\Facades\Log;

class IntegrationHubService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.integration_hub.url') ?? 'http://127.0.0.1:8001';
       
    }

    public function fireEvent(string $eventType, string $entityType, int $entityId, array $payload): bool
    {
        try {
            WebhookCall::create()
                ->url($this->baseUrl . '/api/v1/events')
                ->payload([
                    'connectors'  => ['erpnext'],
                    'company_id'  => 1,
                    'user_id'     => 1,
                    'event_type'  => $eventType,
                    'entity_type' => $entityType,
                    'entity_id'   => $entityId,
                    'payload'     => $payload,
                ])
                ->useSecret('password')
                ->dispatch();

            Log::channel('frappy')->info('data dispatched');
            return true;
        } catch (\Exception $e) {
            Log::error('Integration Hub unreachable', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
