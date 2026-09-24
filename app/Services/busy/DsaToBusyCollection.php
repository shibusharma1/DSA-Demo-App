<?php

namespace App\Services\busy;

use App\Constants\BusyVoucherType;
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

    /**
     * Create or update a collection in BUSY.
     */
    public function sync(Collection $collection): array
    {
        try {

            /*
             * Make sure the collection belongs to a company.
             */
            if (!$collection->company_id) {
                throw new RuntimeException(
                    'Collection company is missing.'
                );
            }

            /*
             * Existing DSA client.
             */
            $client = $collection->client;

            if (!$client) {
                throw new RuntimeException(
                    'Collection client was not found.'
                );
            }

            /*
             * BUSY party code must exist.
             *
             * Your existing client structure already uses
             * busyparty_id for BUSY synchronization.
             */
            $busyPartyCode = trim(
                (string) ($client->busyparty_id ?? '')
            );

            if ($busyPartyCode === '') {
                throw new RuntimeException(
                    'Selected party is not synchronized with BUSY.'
                );
            }

            /*
             * Create Receipt XML.
             */
            $xml = $this->buildReceiptXml(
                $collection,
                $client
            );

            Log::channel('busy')->info(
                'BUSY Collection Receipt XML',
                [
                    'collection_id' => $collection->id,
                    'busy_party_id' => $busyPartyCode,
                    'xml' => $xml,
                ]
            );

            /*
             * If BUSY voucher code already exists,
             * modify the existing receipt.
             */
            if (!empty($collection->busycollection_id)) {

                $response = $this->busyApiService
                    ->modifyVoucherByCode(
                        14,
                        $collection->busycollection_id,
                        $xml
                    );

                if (!($response['success'] ?? false)) {

                    $this->markFailed(
                        $collection,
                        $response['description']
                            ?? 'BUSY receipt modification failed.'
                    );

                    return [
                        'success' => false,
                        'collection_id' => $collection->id,
                        'busycollection_id' =>
                        $collection->busycollection_id,
                        'message' =>
                        $response['description']
                            ?? 'BUSY receipt modification failed.',
                    ];
                }

                $this->markSynced(
                    $collection,
                    $collection->busycollection_id
                );

                return [
                    'success' => true,
                    'collection_id' => $collection->id,
                    'busycollection_id' =>
                    $collection->busycollection_id,
                    'message' =>
                    'BUSY receipt updated successfully.',
                ];
            }

            /*
             * Create new Receipt.
             */
            $response = $this->busyApiService->createVoucher(
                14,
                $xml
            );

            if (!($response['success'] ?? false)) {

                $this->markFailed(
                    $collection,
                    $response['description']
                        ?? 'BUSY receipt creation failed.'
                );

                return [
                    'success' => false,
                    'collection_id' => $collection->id,
                    'message' =>
                    $response['description']
                        ?? 'BUSY receipt creation failed.',
                ];
            }

            /*
             * Try to obtain BUSY voucher code.
             */
            $voucherCode =
                $response['voucher_code']
                ?? $response['vch_code']
                ?? $response['VchCode']
                ?? null;

            if ($voucherCode === null && !empty($response['body'])) {
                $body = trim((string) $response['body']);

                if (ctype_digit($body)) {
                    $voucherCode = $body;
                }
                
            }

            /*
             * If BUSY does not return VchCode in the response,
             * keep the collection synced but don't invent an ID.
             */
            $this->markSynced(
                $collection,
                $voucherCode
            );

            return [
                'success' => true,
                'collection_id' => $collection->id,
                'busycollection_id' => $voucherCode,
                'message' =>
                'BUSY receipt created successfully.',
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

            $this->markFailed(
                $collection,
                $e->getMessage()
            );

            return [
                'success' => false,
                'collection_id' => $collection->id ?? null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build BUSY Receipt XML.
     */
    protected function buildReceiptXml(
        Collection $collection,
        $client
    ): string {

        /*
         * Existing client name.
         *
         * Prefer company_name because this is the party/account name.
         */
        $partyName =
            $client->company_name
            ?? $client->name
            ?? $client->busyparty_id
            ?? '';

        /*
         * Payment account.
         *
         * Cash is the default because the collection UI
         * currently defaults to Cash.
         */
        $receivingAccount = $this->resolveReceivingAccount(
            $collection
        );

        /*
         * BUSY date format.
         */
        $date = $collection->payment_date
            ? $collection->payment_date->format('d-m-Y')
            : now()->format('d-m-Y');

        /*
         * Amount.
         */
        $amount = $this->value(
            $collection->payment_received
        );

        /*
         * Narration.
         */
        $narration = $collection->payment_note ?? '';

        /*
         * Voucher series.
         *
         * Keep configurable.
         */
        $series = $collection->busy_voucher_series
            ?: config('services.busy.voucher_series', 'Main');

        /*
         * Voucher number.
         *
         * Empty means BUSY can generate it according
         * to the configured series.
         */
        $voucherNo = $collection->busy_voucher_no ?? '';

        $xml = new SimpleXMLElement(
            '<Receipt/>'
        );

        $this->addNode(
            $xml,
            'VchSeriesName',
            $series
        );

        $this->addNode(
            $xml,
            'Date',
            $date
        );

        $this->addNode(
            $xml,
            'VchType',
            14
        );

        $this->addNode(
            $xml,
            'StockUpdationDate',
            $date
        );

        $this->addNode(
            $xml,
            'VchNo',
            $voucherNo
        );

        $this->addNode(
            $xml,
            'AutoVchNo',
            ''
        );

        /*
         * Receipt does not require stock.
         */
        $this->addNode(
            $xml,
            'STPTName',
            ''
        );

        /*
         * Party receiving/giving account.
         */
        $this->addNode(
            $xml,
            'MasterName1',
            $partyName
        );

        /*
         * Cash / Bank account.
         */
        $this->addNode(
            $xml,
            'MasterName2',
            $receivingAccount
        );

        $this->addNode(
            $xml,
            'TranCurName',
            'Rs.'
        );

        $this->addNode(
            $xml,
            'InputType',
            '1'
        );

        /*
         * Other information.
         */
        $other = $xml->addChild(
            'VchOtherInfoDetails'
        );

        $other->addChild(
            'OFInfo'
        );

        $this->addNode(
            $other,
            'Narration1',
            $narration
        );

        $this->addNode(
            $other,
            'GrDate',
            $date
        );

        /*
         * Cheque information.
         *
         * These are included only when available.
         */
        $this->addNode(
            $other,
            'ChequeNo',
            $collection->cheque_no ?? ''
        );

        $this->addNode(
            $other,
            'ChequeDate',
            $collection->cheque_date
                ? $collection->cheque_date->format('d-m-Y')
                : ''
        );

        /*
         * Receipt amount.
         */
        $entries = $xml->addChild(
            'AccountEntries'
        );

        $entry = $entries->addChild(
            'AccountDetail'
        );

        $this->addNode(
            $entry,
            'MasterName',
            $partyName
        );

        $this->addNode(
            $entry,
            'Amount',
            $amount
        );

        $this->addNode(
            $entry,
            'DrCr',
            'Credit'
        );

        /*
         * Cash/Bank side.
         */
        $bankEntry = $entries->addChild(
            'AccountDetail'
        );

        $this->addNode(
            $bankEntry,
            'MasterName',
            $receivingAccount
        );

        $this->addNode(
            $bankEntry,
            'Amount',
            $amount
        );

        $this->addNode(
            $bankEntry,
            'DrCr',
            'Debit'
        );

        // return $xml->asXML();
        return $this->normalizeBusyXml($xml->asXML());
    }

    /**
     * Resolve Cash / Bank account.
     */
    protected function resolveReceivingAccount(
        Collection $collection
    ): string {

        /*
         * If a bank is selected, use the bank's BUSY name.
         */
        if ($collection->bank_id && $collection->bank) {

            return trim(
                (string) (
                    $collection->bank->busy_name
                    ?? $collection->bank->name
                    ?? ''
                )
            );
        }

        /*
         * Existing collection type.
         */
        if ($collection->collectionType) {

            $typeName = trim(
                (string) $collection->collectionType->name
            );

            if ($typeName !== '') {
                return $typeName;
            }
        }

        /*
         * Payment method from the form.
         */
        if (!empty($collection->payment_method)) {

            return trim(
                (string) $collection->payment_method
            );
        }

        /*
         * Default shown in your UI.
         */
        return 'Cash';
    }

    /**
     * Add XML node safely.
     */
    protected function addNode(
        SimpleXMLElement $parent,
        string $name,
        mixed $value
    ): void {

        $parent->addChild(
            $name,
            htmlspecialchars(
                $this->value($value),
                ENT_XML1 | ENT_COMPAT,
                'UTF-8'
            )
        );
    }

    /**
     * Convert null to blank.
     */
    protected function value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * BUSY receives VchXML through an HTTP header.
     *
     * Guzzle does not allow XML declaration/newline characters
     * inside a header value, so keep the XML as one single-line
     * header-safe string.
     */
    protected function normalizeBusyXml(string $xml): string
    {
        // Remove XML declaration:
        $xml = preg_replace(
            '/<\?xml[^>]*\?>/i',
            '',
            $xml
        );

        // Remove line breaks, tabs and carriage returns.
        $xml = str_replace(
            ["\r", "\n", "\t"],
            '',
            $xml
        );

        // Remove unnecessary whitespace between XML tags.
        $xml = preg_replace(
            '/>\s+</',
            '><',
            $xml
        );

        return trim($xml);
    }



    /**
     * Mark collection as successfully synced.
     */
    protected function markSynced(
        Collection $collection,
        ?string $voucherCode = null
    ): void {

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

    /**
     * Mark collection as failed.
     */
    protected function markFailed(
        Collection $collection,
        string $message
    ): void {

        $collection->update([
            'busy_sync_status' => 'failed',
            'busy_sync_message' => $message,
        ]);
    }
}
