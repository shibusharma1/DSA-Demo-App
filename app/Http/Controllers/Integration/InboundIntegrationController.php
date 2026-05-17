<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\InboundSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundIntegrationController extends Controller
{
    public function __construct(private InboundSyncService $syncService) {}

    public function handle(Request $request)
    {
        Log::info('=== DSA Inbound received ===', $request->all());

        $validated = $request->validate([
            'event_type'  => ['required', 'string'],
            'entity_type' => ['required', 'string'],
            'entity_id'   => ['nullable', 'string'],
            'source'      => ['required', 'string'],
            'is_new'      => ['required', 'boolean'],
            'payload'     => ['required', 'array'],
        ]);

        $result = $this->syncService->handle(
            source:     $validated['source'],
            eventType:  $validated['event_type'],
            entityType: $validated['entity_type'],
            entityId:   $validated['entity_id'] ?? null,
            isNew:      (bool) $validated['is_new'],
            payload:    $validated['payload'],
        );

        Log::info('=== DSA Inbound result ===', $result);

        if ($result['success']) {
            return response()->json([
                'success'   => true,
                'entity_id' => $result['entity_id'],
                'message'   => $result['message'],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 422);
    }
}