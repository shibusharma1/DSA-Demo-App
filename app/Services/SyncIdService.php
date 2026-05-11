<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
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
        'payment'     => Payment::class,
        'sales_order' => Order::class,
        'item'        => Product::class,
    ];

    public function save(
        string $connectorSlug,
        string $entityType,
        int    $entityId,
        string $remoteId,
        int    $companyId,

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

        $record = $model::find($entityId);

        if (!$record) {
            Log::warning('SyncIdService: record not found', [
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
            ]);
            return false;
        }

        $record->update([$column => $remoteId]);

        return true;
    }
}
