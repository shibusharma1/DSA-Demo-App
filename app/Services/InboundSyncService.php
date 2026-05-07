<?php
// DSA: app/Services/InboundSyncService.php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class InboundSyncService
{
    // Maps connector slug → column name on your DSA tables
    private array $idColumnMap = [
        'zoho'       => 'zoho_id',
        'erpnext'    => 'erpnext_id',
        'tally'      => 'tally_id',
        'quickbooks' => 'quickbooks_id',
        'busy'       => 'busy_id',
        'sap'        => 'sap_id',
    ];

    public function handle(
        string $source,
        string $eventType,
        string $entityType,
        int    $entityId,
        bool   $isNew,
        array  $payload
    ): array {
        Log::info('InboundSyncService.handle called', [
            'source'      => $source,
            'event_type'  => $eventType,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'is_new'      => $isNew,
            'payload'     => $payload,
        ]);

        try {
            return match ($entityType) {
                'customer' => $this->syncCustomer($source, $eventType, $entityId, $isNew, $payload),
                'invoice'  => $this->syncInvoice($source, $eventType, $entityId, $isNew, $payload),
                'payment'  => $this->syncPayment($source, $eventType, $entityId, $isNew, $payload),
                default    => [
                    'success'   => false,
                    'entity_id' => 0,
                    'message'   => "Unsupported entity type: {$entityType}",
                ],
            };
        } catch (\Exception $e) {
            Log::error('InboundSyncService exception', [
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
                'entity_type' => $entityType,
                'source'      => $source,
            ]);
            return [
                'success'   => false,
                'entity_id' => 0,
                'message'   => $e->getMessage(),
            ];
        }
    }

    private function syncCustomer(
        string $source,
        string $eventType,
        int    $entityId,
        bool   $isNew,
        array  $payload
    ): array {
        $idColumn = $this->idColumnMap[$source] ?? null;

        // Extract remote ID — try all possible keys
        $remoteId = $payload[$source . '_id']
            ?? $payload['remote_id']
            ?? $payload['zoho_id']
            ?? $payload['erpnext_id']
            ?? null;

        Log::info('syncCustomer', [
            'id_column' => $idColumn,
            'remote_id' => $remoteId,
            'is_new'    => $isNew,
            'entity_id' => $entityId,
        ]);

        // ── DELETE ────────────────────────────────────────────────
        if (str_contains($eventType, '.deleted')) {
            if ($entityId) {
                \App\Models\Customer::find($entityId)?->delete();
            }
            return ['success' => true, 'entity_id' => $entityId, 'message' => 'Customer deleted'];
        }

        // ── CREATE ────────────────────────────────────────────────
        if ($isNew) {
            // Prevent duplicate — check if this remote ID already exists
            if ($idColumn && $remoteId) {
                $existing = \App\Models\Customer::where($idColumn, $remoteId)->first();
                if ($existing) {
                    Log::info('Customer already exists, skipping create', [
                        $idColumn => $remoteId,
                        'dsa_id'  => $existing->id,
                    ]);
                    return [
                        'success'   => true,
                        'entity_id' => $existing->id,
                        'message'   => 'Already exists',
                    ];
                }
            }

            $createData = [
                'name'  => $payload['name']  ?? $payload['contact_name'] ?? 'Unknown',
                'email' => $payload['email'] ?? $payload['email_id']     ?? null,
                'phone' => $payload['phone'] ?? $payload['mobile_no']    ?? null,
            ];

            // Save the remote ID immediately on create
            if ($idColumn && $remoteId) {
                $createData[$idColumn] = $remoteId;
            }

            Log::info('Creating customer', $createData);

            $customer = \App\Models\Customer::create($createData);

            Log::info('Customer created from inbound', [
                'dsa_id'    => $customer->id,
                $idColumn   => $remoteId,
            ]);

            return [
                'success'   => true,
                'entity_id' => $customer->id, // ← Integration Hub saves this in SyncMap
                'message'   => 'Customer created',
            ];
        }

        // ── UPDATE ────────────────────────────────────────────────
        $customer = \App\Models\Customer::find($entityId);

        if (!$customer) {
            Log::warning('Customer not found for update', ['entity_id' => $entityId]);
            return [
                'success'   => false,
                'entity_id' => 0,
                'message'   => "Customer #{$entityId} not found",
            ];
        }

        $updateData = [];
        if (!empty($payload['name']))         $updateData['name']  = $payload['name'];
        if (!empty($payload['contact_name'])) $updateData['name']  = $payload['contact_name'];
        if (!empty($payload['email']))        $updateData['email'] = $payload['email'];
        if (!empty($payload['email_id']))     $updateData['email'] = $payload['email_id'];
        if (!empty($payload['phone']))        $updateData['phone'] = $payload['phone'];
        if (!empty($payload['mobile_no']))    $updateData['phone'] = $payload['mobile_no'];

        // Save remote ID if not already saved
        if ($idColumn && $remoteId && !$customer->{$idColumn}) {
            $updateData[$idColumn] = $remoteId;
        }

        if (!empty($updateData)) {
            $customer->update($updateData);
        }

        Log::info('Customer updated from inbound', [
            'dsa_id'  => $customer->id,
            'updated' => $updateData,
        ]);

        return ['success' => true, 'entity_id' => $customer->id, 'message' => 'Customer updated'];
    }

    private function syncInvoice(
        string $source,
        string $eventType,
        int    $entityId,
        bool   $isNew,
        array  $payload
    ): array {
        $idColumn = $this->idColumnMap[$source] ?? null;
        $remoteId = $payload[$source . '_id'] ?? $payload['remote_id'] ?? null;

        if (str_contains($eventType, '.deleted')) {
            \App\Models\Invoice::find($entityId)?->delete();
            return ['success' => true, 'entity_id' => $entityId, 'message' => 'Invoice deleted'];
        }

        if ($isNew) {
            if ($idColumn && $remoteId) {
                $existing = \App\Models\Invoice::where($idColumn, $remoteId)->first();
                if ($existing) {
                    return ['success' => true, 'entity_id' => $existing->id, 'message' => 'Already exists'];
                }
            }

            $createData = [
                'invoice_number' => $payload['invoice_number'] ?? null,
                'total'          => $payload['total']          ?? 0,
                'date'           => $payload['date']           ?? now()->toDateString(),
                'status'         => $payload['status']         ?? 'draft',
            ];

            if ($idColumn && $remoteId) {
                $createData[$idColumn] = $remoteId;
            }

            $invoice = \App\Models\Invoice::create($createData);

            return ['success' => true, 'entity_id' => $invoice->id, 'message' => 'Invoice created'];
        }

        $invoice = \App\Models\Invoice::find($entityId);
        if (!$invoice) {
            return ['success' => false, 'entity_id' => 0, 'message' => "Invoice #{$entityId} not found"];
        }

        $updateData = [];
        if (!empty($payload['total']))  $updateData['total']  = $payload['total'];
        if (!empty($payload['status'])) $updateData['status'] = $payload['status'];
        if ($idColumn && $remoteId && !$invoice->{$idColumn}) {
            $updateData[$idColumn] = $remoteId;
        }

        if (!empty($updateData)) {
            $invoice->update($updateData);
        }

        return ['success' => true, 'entity_id' => $invoice->id, 'message' => 'Invoice updated'];
    }

    private function syncPayment(
        string $source,
        string $eventType,
        int    $entityId,
        bool   $isNew,
        array  $payload
    ): array {
        $idColumn = $this->idColumnMap[$source] ?? null;
        $remoteId = $payload[$source . '_id'] ?? $payload['remote_id'] ?? null;

        if ($isNew) {
            if ($idColumn && $remoteId) {
                $existing = \App\Models\Payment::where($idColumn, $remoteId)->first();
                if ($existing) {
                    return ['success' => true, 'entity_id' => $existing->id, 'message' => 'Already exists'];
                }
            }

            $createData = [
                'amount' => $payload['amount'] ?? 0,
                'date'   => $payload['date']   ?? now()->toDateString(),
            ];

            if ($idColumn && $remoteId) {
                $createData[$idColumn] = $remoteId;
            }

            $payment = \App\Models\Payment::create($createData);

            return ['success' => true, 'entity_id' => $payment->id, 'message' => 'Payment created'];
        }

        $payment = \App\Models\Payment::find($entityId);
        if (!$payment) {
            return ['success' => false, 'entity_id' => 0, 'message' => "Payment #{$entityId} not found"];
        }

        $updateData = [];
        if (!empty($payload['amount'])) $updateData['amount'] = $payload['amount'];
        if ($idColumn && $remoteId && !$payment->{$idColumn}) {
            $updateData[$idColumn] = $remoteId;
        }

        if (!empty($updateData)) {
            $payment->update($updateData);
        }

        return ['success' => true, 'entity_id' => $payment->id, 'message' => 'Payment updated'];
    }
}