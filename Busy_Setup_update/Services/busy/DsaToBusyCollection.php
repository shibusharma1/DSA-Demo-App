<?php

namespace App\Services\busy;

use App\Models\Collection;
use App\Services\BusyApiService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class DsaToBusyCollection
{
    public function __construct(
        protected BusyApiService $busyApiService
    ) {}

    public function sync(Collection $collection): array
    {
        try {
            if (!$collection->company_id) {
                throw new RuntimeException('Collection company is missing.');
            }

            $client = $collection->client;

            if (!$client) {
                throw new RuntimeException('Collection client was not found.');
            }

            $busyPartyCode = trim((string) ($client->busyparty_id ?? ''));

            if ($busyPartyCode === '') {
                throw new RuntimeException('Selected party is not synchronized with BUSY.');
            }

            $xml = $this->buildReceiptXml($collection, $client);

            Log::channel('busy')->info(
                'BUSY Collection Receipt XML',
                [
                    'collection_id' => $collection->id,
                    'busy_party_id' => $busyPartyCode,
                    'xml' => $xml,
                ]
            );

            if (!empty($collection->busycollection_id)) {
                $response = $this->busyApiService->modifyVoucherByCode(14, $collection->busycollection_id, $xml);

                if (!($response['success'] ?? false)) {
                    $this->markFailed($collection, $response['description'] ?? 'BUSY receipt modification failed.');
                    return [
                        'success' => false,
                        'collection_id' => $collection->id,
                        'busycollection_id' => $collection->busycollection_id,
                        'message' => $response['description'] ?? 'BUSY receipt modification failed.',
                    ];
                }

                $this->markSynced($collection, $collection->busycollection_id);

                return [
                    'success' => true,
                    'collection_id' => $collection->id,
                    'busycollection_id' =>
                     $collection->busycollection_id,
                    'message' =>
                    'BUSY receipt updated successfully.',
                ];
            }

            /* Create new Receipt. */
            $response = $this->busyApiService->createVoucher(14, $xml);

            if (!($response['success'] ?? false)) {
                $this->markFailed($collection, $response['description'] ?? 'BUSY receipt creation failed.');

                return [
                    'success' => false,
                    'collection_id' => $collection->id,
                    'message' => $response['description'] ?? 'BUSY receipt creation failed.',
                ];
            }

            /* Try to obtain BUSY voucher code. */
            $voucherCode = $response['voucher_code'] ?? $response['vch_code'] ?? $response['VchCode'] ?? null;
            if ($voucherCode === null && !empty($response['body'])) {
                $body = trim((string) $response['body']);
                if (ctype_digit($body)) {
                    $voucherCode = $body;
                }
            }

            if (!$voucherCode) {
                $this->markFailed($collection, 'BUSY created the receipt but did not return a voucher code.');
                return [
                    'success' => false,
                    'collection_id' => $collection->id,
                    'message' =>
                    'BUSY created the receipt but did not return a voucher code.',
                ];
            }

            /* Verify that BUSY actually created accounting entries.*/
            $verification = $this->verifyReceipt($voucherCode);

            if (!($verification['success'] ?? false)) {
                Log::channel('busy')->error(
                    'BUSY Receipt Verification Failed',
                    [
                        'collection_id' => $collection->id,
                        'busy_voucher_code' => $voucherCode,
                        'message' => $verification['message'] ?? null,
                        'tran1' => $verification['tran1'] ?? null,
                        'tran2' => $verification['tran2'] ?? null,
                    ]
                );

                $this->markFailed($collection, $verification['message'] ?? 'BUSY receipt verification failed.');

                return [
                    'success' => false,
                    'collection_id' => $collection->id,
                    'busycollection_id' => $voucherCode,
                    'message' => $verification['message'] ?? 'BUSY receipt verification failed.',
                ];
            }

            $this->markSynced($collection, $voucherCode);

            return [
                'success' => true,
                'collection_id' => $collection->id,
                'busycollection_id' => $voucherCode,
                'message' => 'BUSY receipt created successfully.',
            ];
        } catch (Throwable $e) {
            Log::channel('busy')->error(
                'BUSY Collection Sync Failed',
                [
                    'collection_id' => $collection->id ?? null,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            $this->markFailed($collection, $e->getMessage());
            return [
                'success' => false,
                'collection_id' => $collection->id ?? null,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function buildReceiptXml(Collection $collection, $client): string {
        /* 1. Resolve party*/
        $partyName = trim((string) ($client->company_name ?? $client->name ?? ''));

        if ($partyName === '') {
            throw new RuntimeException('BUSY party name is missing.');
        }

        /* 2. Resolve receiving account */
        $receivingAccount = trim($this->resolveReceivingAccount($collection));

        if ($receivingAccount === '') {
            throw new RuntimeException('BUSY receiving account is missing.');
        }

        /* 3. Amount */
        $amount = round((float) $collection->payment_received, 2);

        if ($amount <= 0) {
            throw new RuntimeException('Collection amount must be greater than zero.');
        }

        /* 4. Date */
        $date = $collection->payment_date ? $collection->payment_date->format('d-m-Y') : now()->format('d-m-Y');

        /* 5. Narration */
        $narration = trim((string) ($collection->payment_note ?? ''));

        /* 6. Voucher series  */
        $series = trim((string) ($collection->busy_voucher_series ?: config('services.busy.voucher_series','Main')));

        /* 7. Voucher number */
        $voucherNo = trim((string) ($collection->busy_voucher_no ?? ''));

        /* 8. Root Receipt */
        $xml = new SimpleXMLElement('<Receipt/>');

        $this->addNode($xml, 'VchSeriesName', $series);
        $this->addNode($xml, 'Date', $date);
        $this->addNode($xml, 'VchType', '14');
        $this->addNode($xml, 'StockUpdationDate', $date);
        $this->addNode($xml, 'VchNo', $voucherNo);
        $this->addNode($xml, 'AutoVchNo', '');
        $this->addNode($xml, 'STPTName', '');

        /* 9. Receipt header accounts */
        $this->addNode($xml, 'MasterName1', $partyName);
        $this->addNode($xml, 'MasterName2', $receivingAccount);
        $this->addNode($xml, 'TranCurName', 'Rs.');
        $this->addNode($xml, 'InputType', '1');

        /* 10. Other information */
        $other = $xml->addChild('VchOtherInfoDetails');
        $other->addChild('OFInfo');
        $this->addNode($other, 'Narration1', $narration);
        $this->addNode($other, 'GrDate', $date);
        $this->addNode($other, 'ChequeNo', $collection->cheque_no ?? '');
        $this->addNode($other, 'ChequeDate', $collection->cheque_date ? $collection->cheque_date->format('d-m-Y') : '');

        /* 11. ACCOUNTING ENTRIES
         * Receipt:
         * Cash/Bank      Dr
         * Customer       Cr
         * Example:
         * Cash           Dr 14
         * Customer       Cr 14
         */
        $entries = $xml->addChild('AccEntries');

        /* Debit: Cash / Bank */
        $debit = $entries->addChild('AccDetail');
        $this->addNode($debit, 'Date', $date);
        $this->addNode($debit, 'VchType', '14');
        $this->addNode($debit, 'SrNo', '1');
        $this->addNode($debit, 'AccountName', $receivingAccount);

        /* AmountType = 1 :Debit side */
        $this->addNode($debit, 'AmountType', '1');
        $this->addNode($debit, 'AmtMainCur', number_format($amount, 2, '.', ''));
        $this->addNode($debit, 'ShortNar', $narration);

        /* Credit: Customer */
        $credit = $entries->addChild('AccDetail');
        $this->addNode($credit, 'Date', $date);
        $this->addNode($credit, 'VchType', '14');
        $this->addNode($credit, 'SrNo', '2');
        $this->addNode($credit, 'AccountName', $partyName);

        /* AmountType = 2 :Credit side */
        $this->addNode($credit, 'AmountType', '2');
        $this->addNode($credit, 'AmtMainCur', number_format($amount, 2, '.', ''));
        $this->addNode($credit, 'CashFlow', number_format($amount, 2, '.', ''));
        $this->addNode($credit, 'ShortNar', $narration);

        /* 12. Normalize before sending through HTTP headers */
        return $this->normalizeBusyXml($xml->asXML());
    }

    protected function verifyReceipt(string|int $voucherCode): array {

        $voucherCode = (int) $voucherCode;

        if ($voucherCode <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid BUSY voucher code.',
            ];
        }

        /* Check voucher header */
        $tran1 = $this->busyApiService->executeQuery("SELECT * FROM TRAN1 WHERE VchCode = {$voucherCode}");

        if (!($tran1['success'] ?? false)) {
            return [
                'success' => false,
                'message' =>
                'BUSY TRAN1 verification failed.',
            ];
        }

        /*Check accounting entries  */
        $tran2 = $this->busyApiService->executeQuery("SELECT * FROM TRAN2 WHERE VchCode = {$voucherCode}");

        if (!($tran2['success'] ?? false)) {
            return [
                'success' => false,
                'message' =>
                'BUSY TRAN2 verification failed.',
            ];
        }

        $tran1Body = (string) ($tran1['body'] ?? '');

        $tran2Body = (string) ($tran2['body'] ?? '');

        /* We only consider the voucher valid when TRAN2 actually contains accounting rows. */
        if (stripos($tran2Body, '<z:row') === false) {
            return [
                'success' => false,
                'message' =>
                'BUSY receipt was created but no accounting entries were created in TRAN2.',
                'tran1' => $tran1Body,
                'tran2' => $tran2Body,
            ];
        }

        return [
            'success' => true,
            'message' =>
            'BUSY receipt and accounting entries verified.',
            'tran1' => $tran1Body,
            'tran2' => $tran2Body,
        ];
    }

    /* Resolve Cash / Bank account. */
    protected function resolveReceivingAccount(Collection $collection): string
    {
        /* If a bank is selected, use the bank's BUSY name. */
        if ($collection->bank_id && $collection->bank) {
            return trim((string) ($collection->bank->busy_name ?? $collection->bank->name ?? ''));
        }

        /* Existing collection type. */
        if ($collection->collectionType) {
            $typeName = trim((string) $collection->collectionType->name);
            if ($typeName !== '') {
                return $typeName;
            }
        }

        /* Payment method from the form. */
        if (!empty($collection->payment_method)) {
            return trim((string) $collection->payment_method);
        }

        /* Default shown in your UI. */
        return 'Cash';
    }

    /* Add XML node safely. */
    protected function addNode(SimpleXMLElement $parent, string $name, mixed $value): void
    {
        $parent->addChild($name, htmlspecialchars($this->value($value), ENT_XML1 | ENT_COMPAT, 'UTF-8'));
    }

    /* Convert null to blank. */
    protected function value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim((string) $value);
    }

    /* BUSY receives VchXML through an HTTP header.
     * Guzzle does not allow XML declaration/newline characters
     * inside a header value, so keep the XML as one single-line
     * header-safe string.
     */
    protected function normalizeBusyXml(string $xml): string
    {
        // Remove XML declaration:
        $xml = preg_replace('/<\?xml[^>]*\?>/i', '', $xml);

        // Remove line breaks, tabs and carriage returns.
        $xml = str_replace(["\r", "\n", "\t"], '', $xml);

        // Remove unnecessary whitespace between XML tags.
        $xml = preg_replace('/>\s+</', '><', $xml);
        return trim($xml);
    }

    /* Mark collection as successfully synced. */
    protected function markSynced(Collection $collection, ?string $voucherCode = null): void
    {
        $data = [
            'busy_sync_status' => 'synced',
            'busy_sync_message' =>
            'Receipt synchronized with BUSY successfully.',
            'busy_synced_at' => now(),
        ];

        if (!empty($voucherCode)) {
            $data['busycollection_id'] = $voucherCode;
        }

        $collection->update($data);
    }

    /* Mark collection as failed. */
    protected function markFailed(Collection $collection, string $message): void
    {
        $collection->update([
            'busy_sync_status' => 'failed',
            'busy_sync_message' => $message,
        ]);
    }
}
