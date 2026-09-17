<?php

namespace App\Services\busy;

use App\Models\Collection;
use App\Models\CollectionType;
use App\Models\Employee;
use App\Services\BusyApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusyToDsaCollections
{
    public function __construct(
        private readonly BusyApiService $busyApiService,
    ) {}

    public function fetchCollections(int $companyId): array
    {
        $result = [
            'success' => false,
            'company_id' => $companyId,
            'fetched' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'error' => null,
        ];

        try {
            $response = $this->busyApiService->getCollections($companyId);

            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description'] ?? 'BUSY collection fetch failed.'
                );
            }

            $vouchers = $response['collections'] ?? [];
            $result['fetched'] = count($vouchers);
            foreach ($vouchers as $voucher) {
                $externalId = trim((string) ($voucher['busy_vch_code'] ?? ''));
                if ($externalId === '') {
                    $result['skipped']++;
                    continue;
                }
                $rows = $voucher['rows'] ?? [];
                // A voucher may contain multiple customer ledger rows.
                $customerRows = $this->findCustomerRows($companyId, $rows);

                if (empty($customerRows)) {
                    Log::channel('busy')->warning(
                        'Receipt skipped: customer ledger not mapped',
                        [
                            'company_id' => $companyId,
                            'busy_vch_code' => $externalId,
                            'rows' => $rows,
                        ]
                    );
                    $result['skipped']++;
                    continue;
                }

                foreach ($customerRows as $customerData) {
                    $customerRow = $customerData['row'];
                    $client = $customerData['client'];
                    $amount = abs((float) ($customerRow['amount'] ?? 0));
                    if ($amount <= 0) {
                        Log::channel('busy')->warning(
                            'Receipt customer row skipped: zero amount',
                            [
                                'company_id' => $companyId,
                                'busy_vch_code' => $externalId,
                                'master_code' => $customerRow['master_code'] ?? null,
                            ]
                        );
                        $result['skipped']++;
                        continue;
                    }
                    $paymentLedger = $this->findPaymentLedger($rows, (string) $customerRow['master_code']);
                    /* The current TRAN2 rows do not include a resolved ledger name. Keep the existing Cash fallback for now.Do not treat this as reliable Cash/Bank detection.*/
                    $paymentMethod = 'Cash';
                    // $admin = Employee::where('company_id', $companyId)
                    //     ->where('is_admin', '1')
                    //     ->orderBy('created_at')
                    //     ->first();
                    // $collectionType = CollectionType::where('company_id', $companyId)
                    //     ->where('name', $paymentMethod)
                    //     ->first();
                    $timestamp = now();
                    $payload = [
                        'company_id' => $companyId,
                        // 'employee_id' => $admin?->id,
                        'client_id' => $client->id,

                        'payment_received' => $amount,
                        'payment_date' => $voucher['voucher_date'] ?? now()->toDateString(),
                        'payment_method' => $paymentMethod,
                        // 'collection_types_id' => $collectionType?->id,
                        'collection_types_id' => 1,

                        'payment_note' => $this->buildPaymentNote($voucher,$customerRow),
                        'payment_status_note' => null,

                        // Not available reliably from the current TRAN2 data.
                        'cheque_no' => null,
                        'cheque_date' => null,
                        'due_payment' => 0,
                        'include_in_credit' => 0,
                        'bank_id' => null,
                        'bank_name' => null,

                        'status' => 'Active',
                        'updated_at' => $timestamp,
                    ];
                    Log::info("Data to be saved in the collection", ['data' => $payload]);
                    DB::transaction(function () use (
                        $companyId,
                        $client,
                        $externalId,
                        $payload,
                        $timestamp,
                        &$result
                    ) {
                        $collection = Collection::firstOrNew([
                            'company_id' => $companyId,
                            'client_id' => $client->id,
                            'busycollection_id' => $externalId,
                        ]);
                        $isNew = !$collection->exists;
                        $collection->fill($payload);
                        if ($isNew) {
                            $collection->created_at = $timestamp;
                        }
                        $collection->save();
                        if ($isNew) {
                            $result['inserted']++;
                        } else {
                            $result['updated']++;
                        }
                    });
                }
            }
            $result['success'] = true;
            Log::channel('busy')->info(
                'BUSY collection sync completed',
                $result
            );
            return $result;
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            Log::channel('busy')->error('BUSY collection sync failed', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $result;
        }
    }

    /** Return every TRAN2 row whose ledger code maps to a DSA client.*/
    private function findCustomerRows(int $companyId, array $rows): array
    {
        $matches = [];
        foreach ($rows as $row) {
            $masterCode = trim((string) ($row['master_code'] ?? ''));
            if ($masterCode === '') {
                continue;
            }
            
            $client = DB::table('parties_busy')
                ->where('company_id', $companyId)
                ->where('busyparty_id', $masterCode)
                ->first();
            if ($client) {
                $matches[] = [
                    'row' => $row,
                    'client' => $client,
                ];
            }
        }
        return $matches;
    }

    /** Return a non-customer ledger row, if present. */
    private function findPaymentLedger(array $rows, string $customerMasterCode): ?array {
        foreach ($rows as $row) {
            if (trim((string) ($row['master_code'] ?? '')) !== trim($customerMasterCode)) {
                return $row;
            }
        }
        return null;
    }

    /** Include voucher and customer-row narration in the payment note.*/
    private function buildPaymentNote(array $voucher, array $customerRow): ?string {
        $parts = [];
        if (!empty($voucher['voucher_no'])) {
            $parts[] = 'BUSY Voucher: ' . trim((string) $voucher['voucher_no']);
        }
        $narration = trim((string) ($customerRow['short_narration'] ?? ''));
        if ($narration !== '') {
            $parts[] = $narration;
        }
        return empty($parts) ? null : implode(' — ', $parts);
    }
}
