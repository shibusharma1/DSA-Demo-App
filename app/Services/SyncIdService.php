<?php
// DSA: app/Services/SyncIdService.php
namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class SyncIdService
{
    // Map connector slug → column name
    private array $columnMap = [
        'zoho'        => 'zoho_id',
        'erpnext'     => 'erpnext_id',
        'tally'       => 'tally_id',
        'quickbooks'  => 'quickbooks_id',
        'busy'        => 'busy_id',
        'sap'         => 'sap_id',
    ];

    // Map entity type → model class
    private array $modelMap = [
        'customer'    => Customer::class,
        // 'invoice'     => \App\Models\Invoice::class,
        // 'payment'     => \App\Models\Payment::class,
        // 'sales_order' => \App\Models\SalesOrder::class,
        // 'item'        => \App\Models\Item::class,
    ];

    public function save(
        string $connectorSlug,
        string $entityType,
        int    $entityId,
        string $remoteId,
        int    $companyId
    ): bool {
        $column = $this->columnMap[$connectorSlug] ?? null;
        $model  = $this->modelMap[$entityType]     ?? null;

        if (!$column) {
            Log::warning('SyncIdService: unknown connector slug', [
                'connector_slug' => $connectorSlug,
            ]);
            return false;
        }

        if (!$model) {
            Log::warning('SyncIdService: unknown entity type', [
                'entity_type' => $entityType,
            ]);
            return false;
        }

        Log::info($model);
        $record = $model::find($entityId);

        if (!$record) {
            Log::warning('SyncIdService: record not found', [
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
            ]);
            return false;
        }

        $record->update([$column => $remoteId]);

        Log::info('SyncIdService: remote ID saved', [
            'entity_type'    => $entityType,
            'entity_id'      => $entityId,
            'connector_slug' => $connectorSlug,
            'column'         => $column,
            'remote_id'      => $remoteId,
        ]);

        return true;
    }
}
