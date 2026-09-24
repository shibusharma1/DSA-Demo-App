<?php

namespace App\Services\busy;

use App\Models\PartyBusy;
use App\Services\BusyApiService;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Log;
use Throwable;

class DsaToBusyParty
{
    private const MASTER_TYPE_ACCOUNT = 2;

    public function __construct(
        private readonly BusyApiService $busyApiService
    ) {}

    /**
     * Create a new DSA party in BUSY.
     */
    public function createParty(PartyBusy $party): array
    {
        try {
            $xml = $this->buildAccountXml($party);

            Log::channel('busy')->info(
                'BUSY Party Create XML Generated',
                [
                    'party_id' => $party->id,
                    'party_name' => $party->company_name,
                    'xml' => $xml,
                ]
            );

            $response = $this->busyApiService->createMaster(
                self::MASTER_TYPE_ACCOUNT,
                $xml
            );

            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description']
                        ?? 'BUSY customer creation failed.'
                );
            }

            /*
             * BUSY may return the master code in the response header.
             */
            $busyPartyId = $response['master_code'] ?? null;

            /*
             * Fallback:
             * If BUSY does not return the code directly,
             * search by the account name.
             */
            if (!$busyPartyId) {
                $busyPartyId = $this->findCreatedMasterCode(
                    $party->company_name
                );
            }

            if (!$busyPartyId) {
                throw new \RuntimeException(
                    'BUSY accepted the customer request, but no BUSY Master ID was returned.'
                );
            }

            $party->update([
                'busyparty_id' => (string) $busyPartyId,
                'busy_sync_status' => 'Synced',
                'busy_sync_message' => null,
                'busy_synced_at' => now(),
            ]);

            Log::channel('busy')->info(
                'BUSY Party Created Successfully',
                [
                    'party_id' => $party->id,
                    'party_name' => $party->company_name,
                    'busyparty_id' => $busyPartyId,
                ]
            );

            return [
                'success' => true,
                'action' => 'created',
                'party_id' => $party->id,
                'busyparty_id' => (string) $busyPartyId,
                'message' => 'Customer created and saved to BUSY successfully.',
                'error' => null,
            ];
        } catch (Throwable $e) {

            $party->update([
                'busy_sync_status' => 'Failed',
                'busy_sync_message' => $e->getMessage(),
            ]);

            Log::channel('busy')->error(
                'BUSY Party Creation Failed',
                [
                    'party_id' => $party->id,
                    'party_name' => $party->company_name,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return [
                'success' => false,
                'action' => 'created',
                'party_id' => $party->id,
                'busyparty_id' => null,
                'message' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Update an existing DSA party in BUSY.
     */
    public function updateParty(PartyBusy $party): array
    {
        try {
            if (empty($party->busyparty_id)) {
                return $this->createParty($party);
            }

            if (!ctype_digit((string) $party->busyparty_id)) {
                throw new \RuntimeException(
                    'Invalid BUSY Master ID for this customer.'
                );
            }

            $xml = $this->buildAccountXml($party);

            Log::channel('busy')->info(
                'BUSY Party Modify XML Generated',
                [
                    'party_id' => $party->id,
                    'party_name' => $party->company_name,
                    'busyparty_id' => $party->busyparty_id,
                    'xml' => $xml,
                ]
            );

            $response = $this->busyApiService->modifyMaster(
                (int) $party->busyparty_id,
                $xml
            );

            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description']
                        ?? 'BUSY customer update failed.'
                );
            }

            $party->update([
                'busy_sync_status' => 'Synced',
                'busy_sync_message' => null,
                'busy_synced_at' => now(),
            ]);

            Log::channel('busy')->info(
                'BUSY Party Updated Successfully',
                [
                    'party_id' => $party->id,
                    'party_name' => $party->company_name,
                    'busyparty_id' => $party->busyparty_id,
                ]
            );

            return [
                'success' => true,
                'action' => 'updated',
                'party_id' => $party->id,
                'busyparty_id' => (string) $party->busyparty_id,
                'message' => 'Customer updated and saved to BUSY successfully.',
                'error' => null,
            ];
        } catch (Throwable $e) {

            $party->update([
                'busy_sync_status' => 'Failed',
                'busy_sync_message' => $e->getMessage(),
            ]);

            Log::channel('busy')->error(
                'BUSY Party Update Failed',
                [
                    'party_id' => $party->id,
                    'party_name' => $party->company_name,
                    'busyparty_id' => $party->busyparty_id,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );

            return [
                'success' => false,
                'action' => 'updated',
                'party_id' => $party->id,
                'busyparty_id' => $party->busyparty_id,
                'message' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build the complete BUSY Account XML.
     *
     * All supported fields are included.
     * Empty local fields are sent as empty XML values.
     */
    private function buildAccountXml(PartyBusy $party): string
    {
        $xml = new DOMDocument(
            '1.0',
            'UTF-8'
        );

        $xml->formatOutput = false;

        $account = $xml->createElement('Account');

        $xml->appendChild($account);

        /*
         * -------------------------------------------------------------
         * BASIC ACCOUNT
         * -------------------------------------------------------------
         */

        $this->appendText(
            $xml,
            $account,
            'Name',
            $this->value(
                $party->company_name
                    ?: $party->name
            )
        );

        $this->appendText(
            $xml,
            $account,
            'Alias',
            $this->value($party->alias)
        );

        $this->appendText(
            $xml,
            $account,
            'PrintName',
            $this->value(
                $party->print_name
                    ?: $party->company_name
                    ?: $party->name
            )
        );

        $this->appendText(
            $xml,
            $account,
            'ParentGroup',
            $this->value($party->parent_group)
        );

        $this->appendText(
            $xml,
            $account,
            'BillByBillBalancing',
            $party->bill_by_bill_balancing
                ? 'True'
                : 'False'
        );

        /*
         * -------------------------------------------------------------
         * ADDRESS
         * -------------------------------------------------------------
         */

        $address = $xml->createElement('Address');

        $this->appendText(
            $xml,
            $address,
            'Address1',
            $this->value($party->address_1)
        );

        $this->appendText(
            $xml,
            $address,
            'Address2',
            $this->value($party->address_2)
        );

        $this->appendText(
            $xml,
            $address,
            'Address3',
            $this->value($party->address_3)
        );

        $this->appendText(
            $xml,
            $address,
            'Address4',
            $this->value($party->address_4)
        );

        $this->appendText(
            $xml,
            $address,
            'Mobile',
            $this->value($party->mobile)
        );

        $this->appendText(
            $xml,
            $address,
            'WhatsAppNo',
            $this->value($party->whatsapp_no)
        );

        $this->appendText(
            $xml,
            $address,
            'TelNo',
            $this->value($party->phone)
        );

        $this->appendText(
            $xml,
            $address,
            'Fax',
            $this->value($party->fax)
        );

        $this->appendText(
            $xml,
            $address,
            'Email',
            $this->value($party->email)
        );

        $this->appendText(
            $xml,
            $address,
            'Contact',
            $this->value($party->contact ?: $party->name)
        );

        $this->appendText(
            $xml,
            $address,
            'ContDeptName',
            $this->value($party->cont_dept_name)
        );

        $this->appendText(
            $xml,
            $address,
            'ITPAN',
            $this->value($party->pan)
        );

        $this->appendText(
            $xml,
            $address,
            'ITWard',
            $this->value($party->it_ward)
        );

        $this->appendText(
            $xml,
            $address,
            'ST37',
            $this->value($party->st37)
        );

        $this->appendText(
            $xml,
            $address,
            'TINNo',
            $this->value($party->tin_no)
        );

        $this->appendText(
            $xml,
            $address,
            'GSTNo',
            $this->value($party->gst_no)
        );

        $countryName = '';

        if ($party->countryInfo) {
            $countryName = (string) (
                $party->countryInfo->name
                ?? ''
            );
        }

        $this->appendText(
            $xml,
            $address,
            'CountryName',
            $countryName
        );

        $this->appendText(
            $xml,
            $address,
            'StateName',
            $this->value($party->state)
        );

        $this->appendText(
            $xml,
            $address,
            'CityName',
            $this->value($party->city)
        );

        $this->appendText(
            $xml,
            $address,
            'AreaName',
            $this->value($party->area)
        );

        $this->appendText(
            $xml,
            $address,
            'Transport',
            $this->value($party->transport ?? null)
        );

        $this->appendText(
            $xml,
            $address,
            'Station',
            $this->value($party->station ?? null)
        );

        $this->appendText(
            $xml,
            $address,
            'AccNo',
            $this->value($party->account_no)
        );

        $this->appendText(
            $xml,
            $address,
            'TmpMasterCode',
            $this->value($party->tmp_master_code)
        );

        $this->appendText(
            $xml,
            $address,
            'C3',
            $this->value($party->c3)
        );

        $this->appendText(
            $xml,
            $address,
            'C4',
            $this->value(
                $party->bank_name ?: $party->c4
            )
        );

        $this->appendText(
            $xml,
            $address,
            'C5',
            $this->value(
                $party->ifsc_code ?: $party->c5
            )
        );

        $this->appendText(
            $xml,
            $address,
            'C8',
            $this->value(
                $party->swift_code ?: $party->c8
            )
        );

        /*
         * Official reference contains <OF/>.
         */
        $address->appendChild(
            $xml->createElement('OF')
        );

        $account->appendChild($address);

        /*
         * -------------------------------------------------------------
         * ACCOUNT CONFIGURATION
         * -------------------------------------------------------------
         */

        $this->appendText(
            $xml,
            $account,
            'SupplierType',
            $this->value($party->supplier_type)
        );

        $this->appendText(
            $xml,
            $account,
            'CreditDaysForSale',
            $this->numericValue(
                $party->credit_days_sale
            )
        );

        $this->appendText(
            $xml,
            $account,
            'CreditDaysForPurc',
            $this->numericValue(
                $party->credit_days_purchase
            )
        );

        $this->appendText(
            $xml,
            $account,
            'PriceLevel',
            $this->value($party->price_level)
        );

        $this->appendText(
            $xml,
            $account,
            'PriceLevelForPurc',
            $this->value(
                $party->price_level_purchase
            )
        );

        $this->appendText(
            $xml,
            $account,
            'TaxType',
            $this->value($party->tax_type)
        );

        $this->appendText(
            $xml,
            $account,
            'TypeOfDealerGST',
            $this->value(
                $party->type_of_dealer_gst
            )
        );

        $this->appendText(
            $xml,
            $account,
            'ChequePrintName',
            $this->value(
                $party->cheque_print_name
            )
        );

        $this->appendText(
            $xml,
            $account,
            'ReverseChargeType',
            $this->value(
                $party->reverse_charge_type
            )
        );

        $this->appendText(
            $xml,
            $account,
            'InputType',
            $this->value(
                $party->input_type
            )
        );

        /*
         * BUSY account balance fields.
         *
         * These are included because your existing getMaster()
         * parser already reads OPBal/PYBal.
         */
        $this->appendText(
            $xml,
            $account,
            'OPBal',
            $this->numericValue(
                $party->opening_balance
            )
        );

        $this->appendText(
            $xml,
            $account,
            'PYBal',
            $this->numericValue(
                $party->closing_balance
            )
        );

        /*
         * Temporary fields used by your existing BUSY reader.
         */
        $this->appendText(
            $xml,
            $account,
            'tmpCode',
            $this->value($party->tmp_code)
        );

        $this->appendText(
            $xml,
            $account,
            'tmpParentGrpCode',
            $this->value(
                $party->tmp_parent_group_code
            )
        );

        return $xml->saveXML(
            $xml->documentElement
        );
    }

    /**
     * Find MasterCode after successful creation when BUSY
     * did not return it directly.
     */
    private function findCreatedMasterCode(
        ?string $name
    ): ?string {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $response = $this->busyApiService
            ->findCustomerByName($name);

        if (!($response['success'] ?? false)) {
            return null;
        }

        $body = trim(
            (string) ($response['body'] ?? '')
        );

        if ($body === '') {
            return null;
        }

        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($body);

        if ($xml === false) {
            libxml_clear_errors();

            return null;
        }

        $xml->registerXPathNamespace(
            'z',
            '#RowsetSchema'
        );

        $rows = $xml->xpath('//z:row') ?: [];

        foreach ($rows as $row) {
            $attributes = $row->attributes();

            $code = trim(
                (string) (
                    $attributes['Code'] ?? ''
                )
            );

            if ($code !== '') {
                return $code;
            }
        }

        return null;
    }

    /**
     * Safely convert a value to XML text.
     */
    private function value(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    /**
     * Numeric XML value.
     */
    private function numericValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (!is_numeric($value)) {
            return '';
        }

        return number_format(
            (float) $value,
            4,
            '.',
            ''
        );
    }

    /**
     * Append XML text node safely.
     */
    private function appendText(
        DOMDocument $xml,
        DOMElement $parent,
        string $name,
        string $value
    ): void {
        $element = $xml->createElement($name);

        $element->appendChild(
            $xml->createTextNode($value)
        );

        $parent->appendChild($element);
    }
}
