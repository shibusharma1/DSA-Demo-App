<?php
// DSA: app/Http/Controllers/Integration/SyncIdController.php
namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SyncIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncIdController extends Controller
{
    public function __construct(private SyncIdService $syncIdService) {}

    public function handle(Request $request)
    {
        $validated = $request->validate([
            'connector_slug' => ['required', 'string'],
            'entity_type'    => ['required', 'string'],
            'entity_id'      => ['required', 'integer'],
            'remote_id'      => ['required', 'string'],
            'company_id'     => ['required', 'integer'],
        ]);

        Log::info('SyncId received from Integration Hub', $validated);

        $saved = $this->syncIdService->save(
            connectorSlug: $validated['connector_slug'],
            entityType:    $validated['entity_type'],
            entityId:      (int) $validated['entity_id'],
            remoteId:      $validated['remote_id'],
            companyId:     (int) $validated['company_id'],
        );

        if ($saved) {
            return response()->json([
                'success' => true,
                'message' => "{$validated['connector_slug']} ID saved for {$validated['entity_type']} #{$validated['entity_id']}",
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Record not found or unknown connector',
        ], 422);
    }
}