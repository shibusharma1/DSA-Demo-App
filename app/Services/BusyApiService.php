<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusyApiService
{
    private const DEFAULT_BASE_URL = 'http://127.0.0.1:981';
    private const DEFAULT_USERNAME = 's';
    private const DEFAULT_PASSWORD = 's';
    private const SALE_VOUCHER_TYPE = 9;

    private string $baseUrl;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) config('services.busy.base_url', self::DEFAULT_BASE_URL),
            '/'
        );
        $this->username = (string) config('services.busy.username', self::DEFAULT_USERNAME);
        $this->password = (string) config('services.busy.password', self::DEFAULT_PASSWORD);
    }

    /**
     * Send a request to BUSY and normalize the response.
     *
     * BUSY can return HTTP 200 even when the business operation fails.
     * Therefore Result=T is required for a successful response.
     */
    public function request(array $headers): array
    {
        try {
            $headers = $this->withAuthentication($headers);
            $this->logRequest($headers);

            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->get($this->baseUrl);

            $result = strtoupper((string) $response->header('Result'));
            $description = trim((string) $response->header('Description'));
            $body = $response->body();

            $this->logResponse($response->status(), $result, $description, $body);

            return [
                'success' => $response->successful() && $result === 'T',
                'status' => $response->status(),
                'result' => $result,
                'description' => $description,
                'body' => $body,
            ];
        } catch (Exception $e) {
            Log::channel('busy_logs')->error('BUSY Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'result' => 'F',
                'description' => $e->getMessage(),
                'body' => null,
            ];
        }
    }

    private function withAuthentication(array $headers): array
    {
        return [
            ...$headers,
            'UserName' => $this->username,
            'Pwd' => $this->password,
        ];
    }

    private function logRequest(array $headers): void
    {
        Log::channel('busy_logs')->info('BUSY Request', [
            'url' => $this->baseUrl,
            'headers' => $headers,
        ]);
    }

    private function logResponse(
        int $status,
        string $result,
        string $description,
        string $body
    ): void {
        Log::channel('busy_logs')->info('BUSY Response', [
            'status' => $status,
            'result' => $result,
            'description' => $description,
            'body' => $body,
        ]);
    }

    // -------------------------------------------------------------------------
    // Core BUSY API operations
    // -------------------------------------------------------------------------

    /** Execute SQL Query (SC=1). */
    public function executeQuery(string $query): array
    {
        return $this->request([
            'SC' => 1,
            'Qry' => $query,
        ]);
    }

    /** Create Master (SC=5). */
    public function createMaster(int $masterType, string $xml): array
    {
        return $this->request([
            'SC' => 5,
            'MasterType' => $masterType,
            'MasterXML' => $xml,
        ]);
    }

    /**
     * Modify existing Account Master by BUSY Master Code.
     *
     * BUSY:
     * SC = 6
     * MasterCode = existing BUSY master code
     * MasterXML = account XML
     */
    public function modifyMaster(int $masterCode, string $xml): array
    {
        return $this->request([
            'SC' => 6,
            'MasterCode' => $masterCode,
            'MasterXML' => $xml,
        ]);
    }

    /** Create Voucher (SC=2). */
    public function createVoucher(int $voucherType, string $xml): array
    {
        return $this->request([
            'SC' => 2,
            'VchType' => $voucherType,
            'VchXML' => $xml,
        ]);
    }

    /**
     * Modify Voucher from XML by Voucher Code.
     *
     * BUSY:
     * SC = 4
     */
    public function modifyVoucherByCode(int $voucherType, string|int $voucherCode, string $xml): array
    {
        return $this->request([
            'SC' => 4,
            'VchType' => $voucherType,
            'VchXML' => $xml,
            'VchCode' => $voucherCode,
        ]);
    }

    /** Fetch Master XML (SC=9). */
    public function getMaster(int $masterCode): array
    {
        return $this->request([
            'SC' => 9,
            'MasterCode' => $masterCode,
        ]);
    }

    /** Connector health check.*/
    public function healthCheck(): bool
    {
        $response = $this->executeQuery("SELECT TOP 5 * FROM MASTER1");
        return $response['success'];
    }

    // -------------------------------------------------------------------------
    // Master data
    // -------------------------------------------------------------------------

    /**
     * Fetch BUSY customers with complete master details.
     *
     * Legacy flow:
     * 1. Fetch all customer accounts from MASTER1.
     * 2. Get the MasterCode of each customer.
     * 3. Fetch complete XML using GetMasterXML (SC=9).
     * 4. Return the original MASTER1 response structure,
     *    but with complete customer data attached.
     */
    // public function getCustomers(): array
    // {
    //     //Manjit dai Update
    //     $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 2 AND PARENTGRP = 116 OR PARENTGRP = 117";
    //     $response = $this->executeQuery($query);
    //     if (!($response['success'] ?? false)) {
    //         return $response;
    //     }
    //     $body = $response['body'] ?? '';
    //     if (trim($body) === '') {
    //         return $response;
    //     }
    //     libxml_use_internal_errors(true);
    //     $xml = simplexml_load_string($body);
    //     if ($xml === false) {
    //         Log::channel('busy_logs')->error('Failed to parse BUSY customer list', [
    //             'body' => $body,
    //             'errors' => libxml_get_errors(),
    //         ]);
    //         libxml_clear_errors();
    //         return $response;
    //     }
    //     $xml->registerXPathNamespace('z', '#RowsetSchema');
    //     $rows = $xml->xpath('//z:row') ?: [];
    //     $completeParties = [];
    //     foreach ($rows as $row) {
    //         $attributes = $row->attributes();
    //         $masterCode = trim((string) ($attributes['Code'] ?? ''));
    //         $name = trim((string) ($attributes['Name'] ?? ''));
    //         if ($masterCode === '' || $name === '') {
    //             continue;
    //         }
    //         /* Get complete customer/account details using BUSY GetMasterXML.*/
    //         $masterResponse = $this->getMaster((int) $masterCode);
    //         if (!($masterResponse['success'] ?? false)) {
    //             Log::channel('busy_logs')->warning('Failed to fetch complete BUSY party', [
    //                 'master_code' => $masterCode,
    //                 'name' => $name,
    //                 'description' => $masterResponse['description'] ?? null,
    //             ]);
    //             /*
    //          * Keeping the basic MASTER1 data even if complete XML fails.*/
    //             $completeParties[] = [
    //                 'master_code' => $masterCode,
    //                 'name' => $name,
    //                 'mobile' => null,
    //                 'email' => null,
    //                 'whatsapp_no' => null,
    //                 'gst_no' => null,
    //                 'address1' => null,
    //                 'address2' => null,
    //                 'telephone' => null,
    //                 'fax' => null,
    //                 'country' => null,
    //                 'state' => null,
    //                 'city' => null,
    //                 'area' => null,
    //                 'contact' => null,
    //                 'it_pan' => null,
    //                 'it_ward' => null,
    //                 'tin_no' => null,
    //                 'transport' => null,
    //                 'station' => null,
    //                 'account_no' => null,
    //                 'parent_group' => null,
    //                 'supplier_type' => null,
    //                 'credit_days' => null,
    //                 'credit_days_purchase' => null,
    //                 'price_level' => null,
    //                 'price_level_purchase' => null,
    //                 'tax_type' => null,
    //                 'cheque_print_name' => null,
    //                 'reverse_charge_type' => null,
    //                 'input_type' => null,
    //                 'status' => (($attributes['DeactiveMaster'] ?? '') === 'True') ? 'Inactive' : 'Active',
    //             ];
    //             continue;
    //         }
    //         $masterXml = trim($masterResponse['body'] ?? '');
    //         if ($masterXml === '') {
    //             continue;
    //         }
    //         /* Parse complete Account XML.*/
    //         libxml_use_internal_errors(true);
    //         $account = simplexml_load_string($masterXml);
    //         if ($account === false) {
    //             Log::channel('busy_logs')->warning('Failed to parse BUSY Master XML', [
    //                 'master_code' => $masterCode,
    //                 'name' => $name,
    //                 'body' => $masterXml,
    //                 'errors' => libxml_get_errors(),
    //             ]);
    //             libxml_clear_errors();
    //             continue;
    //         }
    //         $address = $account->Address;
    //         $completeParties[] = [
    //             /* MASTER1 information*/
    //             'master_code' => $masterCode,
    //             'name' => trim((string) ($account->Name ?: $name)),
    //             'address1' => trim((string) ($address->Address1 ?? '')) ?: null,
    //             'address2' => trim((string) ($address->Address2 ?? '')) ?: null,
    //             'telephone' => trim((string) ($address->TelNo ?? '')) ?: null,
    //             'fax' => trim((string) ($address->Fax ?? '')) ?: null,
    //             'email' => trim((string) ($address->Email ?? '')) ?: null,
    //             'mobile' => trim((string) ($address->Mobile ?? '')) ?: null,
    //             'whatsapp_no' => trim((string) ($address->WhatsAppNo ?? '')) ?: null,
    //             'contact' => trim((string) ($address->Contact ?? '')) ?: null,
    //             'it_pan' => trim((string) ($address->ITPAN ?? '')) ?: null,
    //             'it_ward' => trim((string) ($address->ITWard ?? '')) ?: null,
    //             'st37' => trim((string) ($address->ST37 ?? '')) ?: null,
    //             'tin_no' => trim((string) ($address->TINNo ?? '')) ?: null,
    //             'gst_no' => trim((string) ($address->GSTNo ?? '')) ?: null,
    //             'country' => trim((string) ($address->CountryName ?? '')) ?: null,
    //             'state' => trim((string) ($address->StateName ?? '')) ?: null,
    //             'city' => trim((string) ($address->CityName ?? '')) ?: null,
    //             'area' => trim((string) ($address->AreaName ?? '')) ?: null,
    //             'cont_dept_name' => trim((string) ($address->ContDeptName ?? '')) ?: null,
    //             'transport' => trim((string) ($address->Transport ?? '')) ?: null,
    //             'station' => trim((string) ($address->Station ?? '')) ?: null,
    //             'account_no' => trim((string) ($address->AccNo ?? '')) ?: null,
    //             'tmp_master_code' => trim((string) ($address->TmpMasterCode ?? '')) ?: null,
    //             'c3' => trim((string) ($address->C3 ?? '')) ?: null,
    //             'bank_name' => trim((string) ($address->C4 ?? '')) ?: null,
    //             'ifsc_code' => trim((string) ($address->C5 ?? '')) ?: null,
    //             'swift_code' => trim((string) ($address->C8 ?? '')) ?: null,
    //             'parent_group' => trim((string) ($account->ParentGroup ?? '')) ?: null,
    //             'op_bal' => trim((string) ($account->OPBal ?? '')) ?: null,
    //             'py_bal' => trim((string) ($account->PYBal ?? '')) ?: null,
    //             'bill_by_bill_balancing' => trim((string) ($account->BillByBillBalancing ?? '')) ?: null,
    //             'supplier_type' => trim((string) ($account->SupplierType ?? '')) ?: null,
    //             'credit_days' => trim((string) ($account->CreditDaysForSale ?? '')) ?: null,
    //             'credit_days_purchase' => trim((string) ($account->CreditDaysForPurc ?? '')) ?: null,
    //             'price_level' => trim((string) ($account->PriceLevel ?? '')) ?: null,
    //             'price_level_purchase' => trim((string) ($account->PriceLevelForPurc ?? '')) ?: null,
    //             'tax_type' => trim((string) ($account->TaxType ?? '')) ?: null,
    //             'tmp_code' => trim((string) ($account->tmpCode ?? '')) ?: null,
    //             'tmp_parent_group_code' => trim((string) ($account->tmpParentGrpCode ?? '')) ?: null,
    //             'cheque_print_name' => trim((string) ($account->ChequePrintName ?? '')) ?: null,
    //             'reverse_charge_type' => trim((string) ($account->ReverseChargeType ?? '')) ?: null,
    //             'input_type' => trim((string) ($account->InputType ?? '')) ?: null,
    //             'status' => (($attributes['DeactiveMaster'] ?? '') === 'True') ? 'Inactive' : 'Active',
    //         ];
    //     }
    //     Log::channel('busy_logs')->info('Complete BUSY Parties', [
    //         'count' => count($completeParties),
    //         'parties' => $completeParties,
    //     ]);
    //     /* Only the body is changed to contain the complete party data.*/
    //     return [
    //         ...$response,
    //         'parties' => $completeParties,
    //     ];
    // }

    //new get customer
    public function getCustomers(): array
    {
        // Manjit dai Update
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 2";
        // $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 2 AND PARENTGRP IN (116, 117)";

        $response = $this->executeQuery($query);

        if (!($response['success'] ?? false)) {
            return $response;
        }

        $body = trim((string) ($response['body'] ?? ''));

        if ($body === '') {
            return [
                ...$response,
                'success' => false,
                'description' => 'Empty BUSY customer list response.',
                'parties' => [],
            ];
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET);

        if ($xml === false) {
            Log::channel('busy_logs')->error('Failed to parse BUSY customer list', [
                'errors' => array_map(
                    fn($error) => trim($error->message),
                    libxml_get_errors()
                ),
            ]);

            libxml_clear_errors();

            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to parse BUSY customer list XML.',
                'parties' => [],
            ];
        }

        $xml->registerXPathNamespace('z', '#RowsetSchema');

        $rows = $xml->xpath('//z:row');

        if ($rows === false || ($rows === [] && $xml->xpath('//z:row') === false)) {
            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to read BUSY customer rows.',
                'parties' => [],
            ];
        }

        // A response without the expected rowset is not a valid list.
        $rowset = $xml->xpath('//rs:data');

        if ($rowset === false || count($rowset) === 0) {
            return [
                ...$response,
                'success' => false,
                'description' => 'BUSY customer rowset is missing.',
                'parties' => [],
            ];
        }

        $completeParties = [];
        $seenMasterCodes = [];

        foreach ($rows as $row) {
            $attributes = $row->attributes();

            $masterCode = trim((string) ($attributes['Code'] ?? ''));
            $name = trim((string) ($attributes['Name'] ?? ''));

            // Do not silently lose a row from the missing-ID comparison.
            if ($masterCode === '' || !ctype_digit($masterCode) || (int) $masterCode <= 0 || $name === '') {
                Log::channel('busy_logs')->error('Invalid BUSY customer row', [
                    'master_code' => $masterCode,
                    'name' => $name,
                ]);

                return [
                    ...$response,
                    'success' => false,
                    'description' => 'BUSY customer list contains an invalid row.',
                    'parties' => [],
                ];
            }

            if (isset($seenMasterCodes[$masterCode])) {
                return [
                    ...$response,
                    'success' => false,
                    'description' => 'BUSY customer list contains duplicate master codes.',
                    'parties' => [],
                ];
            }

            $seenMasterCodes[$masterCode] = true;

            $status = strcasecmp(
                trim((string) ($attributes['DeactiveMaster'] ?? 'False')),
                'True'
            ) === 0 ? 'Inactive' : 'Active';

            // Basic data is always available from MASTER1.
            $party = [
                'master_code' => $masterCode,
                'name' => $name,
                'status' => $status,
                'details_loaded' => false,
            ];

            // Preserve useful basic fields when SC=9 fails.
            $basicFields = [
                'alias' => 'Alias',
                'print_name' => 'PrintName',
                'parent_group' => 'ParentGrp',
                'tmp_parent_group_code' => 'ParentGrp',
            ];

            foreach ($basicFields as $key => $attributeName) {
                $value = trim((string) ($attributes[$attributeName] ?? ''));
                $party[$key] = $value !== '' ? $value : null;
            }

            // Get complete customer/account details using BUSY GetMasterXML.
            $masterResponse = $this->getMaster((int) $masterCode);

            if (!($masterResponse['success'] ?? false)) {
                Log::channel('busy_logs')->warning('Failed to fetch complete BUSY party', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'description' => $masterResponse['description'] ?? null,
                ]);

                $completeParties[] = $party;
                continue;
            }

            $masterXml = trim((string) ($masterResponse['body'] ?? ''));

            if ($masterXml === '') {
                Log::channel('busy_logs')->warning('Empty BUSY Master XML', [
                    'master_code' => $masterCode,
                    'name' => $name,
                ]);

                $completeParties[] = $party;
                continue;
            }

            libxml_clear_errors();

            $account = simplexml_load_string(
                $masterXml,
                'SimpleXMLElement',
                LIBXML_NONET
            );

            if ($account === false || $account->getName() !== 'Account') {
                Log::channel('busy_logs')->warning('Failed to parse BUSY Master XML', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'errors' => array_map(
                        fn($error) => trim($error->message),
                        libxml_get_errors()
                    ),
                ]);

                libxml_clear_errors();
                $completeParties[] = $party;
                continue;
            }

            // Verify SC=9 returned the requested master, when an ID is present.
            $xmlMasterCode = trim((string) ($account->tmpCode ?? ''));

            if ($xmlMasterCode !== '' && $xmlMasterCode !== $masterCode) {
                Log::channel('busy_logs')->warning('BUSY Master XML code mismatch', [
                    'requested_code' => $masterCode,
                    'returned_code' => $xmlMasterCode,
                ]);

                $completeParties[] = $party;
                continue;
            }

            $address = $account->Address;

            $fieldMap = [
                'name' => [$account, 'Name'],
                'alias' => [$account, 'Alias'],
                'print_name' => [$account, 'PrintName'],
                'parent_group' => [$account, 'ParentGroup'],
                'op_bal' => [$account, 'OPBal'],
                'py_bal' => [$account, 'PYBal'],
                'bill_by_bill_balancing' => [$account, 'BillByBillBalancing'],
                'supplier_type' => [$account, 'SupplierType'],
                'credit_days' => [$account, 'CreditDaysForSale'],
                'credit_days_purchase' => [$account, 'CreditDaysForPurc'],
                'price_level' => [$account, 'PriceLevel'],
                'price_level_purchase' => [$account, 'PriceLevelForPurc'],
                'tax_type' => [$account, 'TaxType'],
                'tmp_code' => [$account, 'tmpCode'],
                'tmp_parent_group_code' => [$account, 'tmpParentGrpCode'],
                'cheque_print_name' => [$account, 'ChequePrintName'],
                'reverse_charge_type' => [$account, 'ReverseChargeType'],
                'input_type' => [$account, 'InputType'],

                'address1' => [$address, 'Address1'],
                'address2' => [$address, 'Address2'],
                'address3' => [$address, 'Address3'],
                'address4' => [$address, 'Address4'],
                'telephone' => [$address, 'TelNo'],
                'fax' => [$address, 'Fax'],
                'email' => [$address, 'Email'],
                'mobile' => [$address, 'Mobile'],
                'whatsapp_no' => [$address, 'WhatsAppNo'],
                'contact' => [$address, 'Contact'],
                'cont_dept_name' => [$address, 'ContDeptName'],
                'it_pan' => [$address, 'ITPAN'],
                'it_ward' => [$address, 'ITWard'],
                'st37' => [$address, 'ST37'],
                'tin_no' => [$address, 'TINNo'],
                'gst_no' => [$address, 'GSTNo'],
                'country' => [$address, 'CountryName'],
                'state' => [$address, 'StateName'],
                'city' => [$address, 'CityName'],
                'area' => [$address, 'AreaName'],
                'transport' => [$address, 'Transport'],
                'station' => [$address, 'Station'],
                'account_no' => [$address, 'AccNo'],
                'tmp_master_code' => [$address, 'TmpMasterCode'],
                'c3' => [$address, 'C3'],
                'bank_name' => [$address, 'C4'],
                'ifsc_code' => [$address, 'C5'],
                'swift_code' => [$address, 'C8'],
            ];

            foreach ($fieldMap as $key => [$element, $xmlField]) {
                // Missing XML element: do not overwrite basic MASTER1 data.
                if (!isset($element->{$xmlField})) {
                    continue;
                }

                $value = trim((string) $element->{$xmlField});

                // Present but empty means the value was cleared in BUSY.
                $party[$key] = $value !== '' ? $value : null;
            }

            if (empty($party['name'])) {
                $party['name'] = $name;
            }

            // Status comes from MASTER1, not SyncStatus.
            $party['status'] = $status;
            $party['details_loaded'] = true;

            $completeParties[] = $party;
        }

        libxml_clear_errors();

        Log::channel('busy_logs')->info('Complete BUSY Parties', [
            'count' => count($completeParties),
            'details_loaded' => count(array_filter(
                $completeParties,
                fn($party) => $party['details_loaded']
            )),
            'details_failed' => count(array_filter(
                $completeParties,
                fn($party) => !$party['details_loaded']
            )),
        ]);

        return [
            ...$response,
            'parties' => $completeParties,
        ];
    }
    //end new get customer


    /**
     * Fetch BUSY taxes with complete master details.
     *
     * Flow:
     * 1. Fetch all tax masters from MASTER1.
     * 2. Extract MasterCode and Name.
     * 3. Fetch complete XML for each tax using GetMasterXML (SC=9).
     * 4. Extract TaxRateCentral from BranchEntries.
     * 5. Keep basic MASTER1 data if individual master fetching or parsing fails.
     * 6. Return the original response structure with complete tax data attached.
     */
    // Dai update
    public function getTaxes(): array
    {
        // Step 1: Fetch all tax masters.
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 25 AND PARENTGRP = 0";

        $response = $this->executeQuery($query);

        if (!($response['success'] ?? false)) {
            return $response;
        }

        $body = trim((string) ($response['body'] ?? ''));

        if ($body === '') {
            return [
                ...$response,
                'taxes' => [],
            ];
        }

        // Step 2: Parse MASTER1 response.
        libxml_use_internal_errors(true);

        $xml = simplexml_load_string($body);

        if ($xml === false) {
            Log::channel('busy_logs')->error('Failed to parse BUSY tax list', [
                'errors' => libxml_get_errors(),
            ]);

            libxml_clear_errors();

            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to parse BUSY tax list XML.',
                'taxes' => [],
            ];
        }

        $xml->registerXPathNamespace('z', '#RowsetSchema');

        $rows = $xml->xpath('//z:row') ?: [];

        $completeTaxes = [];

        // Step 3: Fetch complete details for every tax master.
        foreach ($rows as $row) {

            $attributes = $row->attributes();

            $masterCode = trim((string) ($attributes['Code'] ?? ''));
            $name = trim((string) ($attributes['Name'] ?? ''));

            $masterType = trim((string) ($attributes['MasterType'] ?? ''));
            $parentGroup = trim((string) ($attributes['ParentGrp'] ?? ''));

            $status = (($attributes['DeactiveMaster'] ?? '') === 'True')
                ? 'Inactive'
                : 'Active';

            if ($masterCode === '' || $name === '') {
                continue;
            }

            // Step 4: Fetch complete master XML using SC=9.
            $masterResponse = $this->getMaster((int) $masterCode);

            if (!($masterResponse['success'] ?? false)) {

                Log::channel('busy_logs')->warning('Failed to fetch complete BUSY tax master', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'description' => $masterResponse['description'] ?? null,
                ]);

                // Keep basic MASTER1 information.
                $completeTaxes[] = [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'master_type' => $masterType,
                    'parent_group' => $parentGroup,
                    'tax_rate_central' => null,
                    'tax_type' => null,
                    'status' => $status,
                ];

                continue;
            }

            $masterXml = trim((string) ($masterResponse['body'] ?? ''));

            if ($masterXml === '') {

                Log::channel('busy_logs')->warning('Empty BUSY tax master XML', [
                    'master_code' => $masterCode,
                    'name' => $name,
                ]);

                $completeTaxes[] = [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'master_type' => $masterType,
                    'parent_group' => $parentGroup,
                    'tax_rate_central' => null,
                    'tax_type' => null,
                    'status' => $status,
                ];

                continue;
            }

            // Step 5: Parse complete tax master XML.
            $tax = simplexml_load_string($masterXml);

            if ($tax === false) {

                Log::channel('busy_logs')->warning('Failed to parse BUSY tax master XML', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'errors' => libxml_get_errors(),
                ]);

                libxml_clear_errors();

                // Keep basic MASTER1 information.
                $completeTaxes[] = [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'master_type' => $masterType,
                    'parent_group' => $parentGroup,
                    'tax_rate_central' => null,
                    'tax_type' => null,
                    'status' => $status,
                ];

                continue;
            }

            // Step 6: Extract TaxRateCentral from nested XML.
            $taxRateCentral = trim((string) (
                $tax->BranchEntries
                ->BranchTaxDetail
                ->DateWiseInfo
                ->DateWiseTaxInfo
                ->TaxRateCentral ?? ''
            ));

            $taxRateCentral = is_numeric($taxRateCentral)
                ? (float) $taxRateCentral
                : null;

            // Step 7: Store complete tax information.
            $completeTaxes[] = [
                'master_code' => $masterCode,
                'name' => trim((string) ($tax->Name ?? $name)) ?: $name,
                'master_type' => $masterType,
                'parent_group' => $parentGroup,

                // Actual central tax rate from BUSY XML.
                'tax_rate_central' => $taxRateCentral,

                'tax_type' => trim(
                    (string) ($tax->TaxType ?? '')
                ) ?: null,

                'status' => $status,
            ];
        }

        libxml_clear_errors();

        Log::channel('busy_logs')->info('Complete BUSY Taxes Fetched', [
            'count' => count($completeTaxes),
            'taxes' => $completeTaxes,
        ]);

        // Step 8: Return original response with complete tax data.
        return [
            ...$response,
            'taxes' => $completeTaxes,
        ];
    }

    public function getUnits(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 8 AND PARENTGRP = 0";
        return $this->executeQuery($query);
    }

    /** Find BUSY customer by name. */
    public function findCustomerByName(string $name): array
    {
        $name = str_replace("'", "''", trim($name));
        $query = "SELECT TOP 1 * FROM MASTER1 WHERE MASTERTYPE = 2 AND PARENTGRP = 116 AND NAME = '{$name}'";
        return $this->executeQuery($query);
    }
    /** Fetch BUSY item categories. */
    public function getItemCategories(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 5 AND PARENTGRP = 0";
        return $this->executeQuery($query);
    }

    /** Fetch BUSY products/items.*/
    // public function getItems(): array
    // {
    //     // New dai
    //     $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 6";
    //     $response = $this->executeQuery($query);
    //     if (!($response['success'] ?? false)) {
    //         return $response;
    //     }
    //     $body = trim($response['body'] ?? '');
    //     if ($body === '') {
    //         return [
    //             ...$response,
    //             'items' => [],
    //         ];
    //     }
    //     libxml_use_internal_errors(true);
    //     $xml = simplexml_load_string($body);
    //     if ($xml === false) {
    //         Log::channel('busy_logs')->error('Failed to parse BUSY item list', [
    //             'body' => $body,
    //             'errors' => libxml_get_errors(),
    //         ]);
    //         libxml_clear_errors();
    //         return [
    //             ...$response,
    //             'items' => [],
    //         ];
    //     }

    //     $xml->registerXPathNamespace('z', '#RowsetSchema');
    //     $rows = $xml->xpath('//z:row') ?: [];
    //     $completeItems = [];
    //     foreach ($rows as $row) {
    //         $attributes = $row->attributes();
    //         $masterCode = trim((string) ($attributes['Code'] ?? ''));
    //         $name = trim((string) ($attributes['Name'] ?? ''));
    //         $unitId = trim((string) ($attributes['CM1'] ?? ''));
    //         if ($masterCode === '' || $name === '') {
    //             continue;
    //         }
    //         // Fetch complete item details using BUSY GetMasterXML.
    //         $masterResponse = $this->getMaster((int) $masterCode);
    //         if (!($masterResponse['success'] ?? false)) {
    //             Log::channel('busy_logs')->warning('Failed to fetch complete BUSY item', [
    //                 'master_code' => $masterCode,
    //                 'name' => $name,
    //                 'description' => $masterResponse['description'] ?? null,
    //             ]);

    //             // Keep the basic MASTER1 data if complete XML is unavailable.
    //             $completeItems[] = [
    //                 'master_code' => $masterCode,
    //                 'name' => $name,
    //                 'alias' => null,
    //                 'print_name' => null,
    //                 'parent_group' => null,
    //                 'unit_name' => $unitId,
    //                 'mrp' => null,
    //                 'sale_price' => null,
    //                 'purchase_price' => null,
    //                 'price_level' => null,
    //                 'price_level_purchase' => null,
    //                 'tax_type' => null,
    //                 'status' => (($attributes['DeactiveMaster'] ?? '') === 'True') ? 'Inactive' : 'Active',
    //                 'short_desc' => null,
    //             ];
    //             continue;
    //         }

    //         $masterXml = trim($masterResponse['body'] ?? '');
    //         if ($masterXml === '') {
    //             continue;
    //         }
    //         $item = simplexml_load_string($masterXml);
    //         if ($item === false) {
    //             Log::channel('busy_logs')->warning('Failed to parse BUSY Master XML for item', [
    //                 'master_code' => $masterCode,
    //                 'name' => $name,
    //                 'body' => $masterXml,
    //                 'errors' => libxml_get_errors(),
    //             ]);
    //             libxml_clear_errors();
    //             continue;
    //         }

    //         $completeItems[] = [
    //             'master_code' => $masterCode,
    //             'name' => trim((string) ($item->Name ?? $name)) ?: $name,
    //             'alias' => trim((string) ($item->Alias ?? '')) ?: null,
    //             //manjit dai update
    //             'product_code' => trim((string) ($item->Alias ?? '')) ?: null,
    //             'print_name' => trim((string) ($item->PrintName ?? '')) ?: null,
    //             'parent_group' => trim((string) ($item->ParentGroup ?? '')) ?: null,
    //             'unit_name' => trim((string) ($item->CM1 ?? $unitId)) ?: null,
    //             'mrp' => trim((string) ($item->MRP ?? '')) ?: null,
    //             'sale_price' => trim((string) ($item->SalePrice ?? '')) ?: null,
    //             'purchase_price' => trim((string) ($item->PurchasePrice ?? '')) ?: null,
    //             'price_level' => trim((string) ($item->PriceLevel ?? '')) ?: null,
    //             'price_level_purchase' => trim((string) ($item->PriceLevelForPurc ?? '')) ?: null,
    //             'tax_type' => trim((string) ($item->TaxType ?? '')) ?: null,
    //             'status' => (($attributes['DeactiveMaster'] ?? '') === 'True') ? 'Inactive' : 'Active',
    //             'short_desc' => implode(' ', array_filter([
    //                 trim((string) ($item->Address->Address1 ?? '')),
    //                 trim((string) ($item->Address->Address2 ?? '')),
    //                 trim((string) ($item->Address->Address3 ?? '')),
    //                 trim((string) ($item->Address->Address4 ?? '')),
    //             ])) ?: null,
    //         ];
    //     }

    //     Log::channel('busy_logs')->info('Complete BUSY Items', [
    //         'count' => count($completeItems),
    //         'items' => $completeItems,
    //     ]);

    //     return [
    //         ...$response,
    //         'items' => $completeItems,
    //     ];
    // }
//new prodct code



    /** Fetch BUSY products/items.*/
    public function getItems(): array
    {
        // New dai
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 6";

        $response = $this->executeQuery($query);

        if (!($response['success'] ?? false)) {
            return $response;
        }

        $body = trim((string) ($response['body'] ?? ''));

        if ($body === '') {
            Log::channel('busy_logs')->error('Empty BUSY item list response');

            return [
                ...$response,
                'success' => false,
                'description' => 'Empty BUSY item list response.',
                'items' => [],
            ];
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_string(
            $body,
            'SimpleXMLElement',
            LIBXML_NONET
        );

        if ($xml === false) {
            Log::channel('busy_logs')->error('Failed to parse BUSY item list', [
                'errors' => array_map(
                    fn($error) => trim($error->message),
                    libxml_get_errors()
                ),
            ]);

            libxml_clear_errors();

            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to parse BUSY item list XML.',
                'items' => [],
            ];
        }

        $xml->registerXPathNamespace('z', '#RowsetSchema');
        $xml->registerXPathNamespace(
            'rs',
            'urn:schemas-microsoft-com:rowset'
        );

        // Ensure the expected BUSY rowset exists.
        $rowset = $xml->xpath('//rs:data');

        if ($rowset === false || count($rowset) === 0) {
            Log::channel('busy_logs')->error('BUSY item rowset missing');

            return [
                ...$response,
                'success' => false,
                'description' => 'BUSY item rowset is missing.',
                'items' => [],
            ];
        }

        $rows = $xml->xpath('//z:row');

        if ($rows === false) {
            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to read BUSY item rows.',
                'items' => [],
            ];
        }

        $completeItems = [];
        $seenMasterCodes = [];

        foreach ($rows as $row) {
            $attributes = $row->attributes();

            $masterCode = trim((string) ($attributes['Code'] ?? ''));
            $name = trim((string) ($attributes['Name'] ?? ''));
            $unitId = trim((string) ($attributes['CM1'] ?? ''));

            // Do not silently skip an invalid product row.
            if (
                $masterCode === '' ||
                !ctype_digit($masterCode) ||
                (int) $masterCode <= 0 ||
                $name === ''
            ) {
                Log::channel('busy_logs')->error('Invalid BUSY item row', [
                    'master_code' => $masterCode,
                    'name' => $name,
                ]);

                return [
                    ...$response,
                    'success' => false,
                    'description' => 'BUSY item list contains an invalid row.',
                    'items' => [],
                ];
            }

            if (isset($seenMasterCodes[$masterCode])) {
                Log::channel('busy_logs')->error('Duplicate BUSY item master code', [
                    'master_code' => $masterCode,
                ]);

                return [
                    ...$response,
                    'success' => false,
                    'description' => 'BUSY item list contains duplicate master codes.',
                    'items' => [],
                ];
            }

            $seenMasterCodes[$masterCode] = true;

            $status = strcasecmp(
                trim((string) ($attributes['DeactiveMaster'] ?? 'False')),
                'True'
            ) === 0 ? 'Inactive' : 'Active';

            // Basic MASTER1 information.
            // Always keep the product even if SC=9 fails.
            $alias = trim((string) ($attributes['Alias'] ?? ''));
            $printName = trim((string) ($attributes['PrintName'] ?? ''));
            $parentGroup = trim((string) ($attributes['ParentGrp'] ?? ''));

            $itemData = [
                'master_code' => $masterCode,
                'name' => $name,
                'alias' => $alias !== '' ? $alias : null,
                'product_code' => $alias !== '' ? $alias : null,
                'print_name' => $printName !== '' ? $printName : null,
                'parent_group' => $parentGroup !== '' ? $parentGroup : null,
                'unit_name' => $unitId !== '' ? $unitId : null,
                'mrp' => null,
                'sale_price' => null,
                'purchase_price' => null,
                'price_level' => null,
                'price_level_purchase' => null,
                'tax_type' => null,
                'status' => $status,
                'short_desc' => null,

                // New flag for safe create/update.
                'details_loaded' => false,
            ];

            // Fetch complete item details using BUSY GetMasterXML.
            $masterResponse = $this->getMaster((int) $masterCode);

            if (!($masterResponse['success'] ?? false)) {
                Log::channel('busy_logs')->warning('Failed to fetch complete BUSY item', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'description' => $masterResponse['description'] ?? null,
                ]);

                $completeItems[] = $itemData;
                continue;
            }

            $masterXml = trim((string) ($masterResponse['body'] ?? ''));

            if ($masterXml === '') {
                Log::channel('busy_logs')->warning('Empty BUSY Master XML for item', [
                    'master_code' => $masterCode,
                    'name' => $name,
                ]);

                $completeItems[] = $itemData;
                continue;
            }

            libxml_clear_errors();

            // Handle invalid UTF-8 returned by BUSY.
            if (!mb_check_encoding($masterXml, 'UTF-8')) {
                $cleanXml = iconv('UTF-8', 'UTF-8//IGNORE', $masterXml);

                if ($cleanXml === false || $cleanXml === '') {
                    Log::channel('busy_logs')->warning('Unable to normalize BUSY item XML', [
                        'master_code' => $masterCode,
                    ]);

                    $completeItems[] = $itemData;
                    continue;
                }

                $masterXml = $cleanXml;
            }
            
            $item = simplexml_load_string(
                $masterXml,
                'SimpleXMLElement',
                LIBXML_NONET
            );

            if ($item === false) {
                Log::channel('busy_logs')->warning('Failed to parse BUSY Master XML for item', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'errors' => array_map(
                        fn($error) => trim($error->message),
                        libxml_get_errors()
                    ),
                ]);

                libxml_clear_errors();

                $completeItems[] = $itemData;
                continue;
            }

            // Validate the returned master XML structure.
            // BUSY item XML is expected to have Item as its root.
            if ($item->getName() !== 'Item') {
                Log::channel('busy_logs')->warning('Unexpected BUSY item XML root', [
                    'master_code' => $masterCode,
                    'root' => $item->getName(),
                ]);

                $completeItems[] = $itemData;
                continue;
            }

            // Validate master identity if BUSY supplies tmpCode.
            $xmlMasterCode = trim((string) ($item->tmpCode ?? ''));

            if (
                $xmlMasterCode !== '' &&
                $xmlMasterCode !== $masterCode
            ) {
                Log::channel('busy_logs')->warning('BUSY item master code mismatch', [
                    'requested_code' => $masterCode,
                    'returned_code' => $xmlMasterCode,
                ]);

                $completeItems[] = $itemData;
                continue;
            }

            // Parse complete item information.
            // Keep all legacy field names.
            $fieldMap = [
                'name' => 'Name',
                'alias' => 'Alias',
                'print_name' => 'PrintName',
                'parent_group' => 'ParentGroup',
                'unit_name' => 'CM1',
                'mrp' => 'MRP',
                'sale_price' => 'SalePrice',
                'purchase_price' => 'PurchasePrice',
                'price_level' => 'PriceLevel',
                'price_level_purchase' => 'PriceLevelForPurc',
                // 'tax_type' => 'TaxType',
                'tax_type' => 'tmpTaxCategoryCode',
            ];

            foreach ($fieldMap as $key => $xmlField) {
                if (!isset($item->{$xmlField})) {
                    // Missing XML element: retain basic MASTER1 value.
                    continue;
                }

                $value = trim((string) $item->{$xmlField});

                // Explicit empty XML value becomes null.
                $itemData[$key] = $value !== '' ? $value : null;
            }

            if (empty($itemData['name'])) {
                $itemData['name'] = $name;
            }

            // Keep product_code mapped from Alias as in legacy code.
            $itemData['product_code'] = $itemData['alias'];

            // Build short description from address fields.
            $addressFields = [
                'Address1',
                'Address2',
                'Address3',
                'Address4',
            ];

            $shortDesc = [];
            $hasAddressField = false;

            foreach ($addressFields as $addressField) {
                if (isset($item->Address->{$addressField})) {
                    $hasAddressField = true;

                    $value = trim(
                        (string) $item->Address->{$addressField}
                    );

                    if ($value !== '') {
                        $shortDesc[] = $value;
                    }
                }
            }

            if ($hasAddressField) {
                $itemData['short_desc'] = implode(' ', $shortDesc) ?: null;
            }

            // Status always comes from MASTER1.
            $itemData['status'] = $status;

            // Complete item XML parsed successfully.
            $itemData['details_loaded'] = true;

            $completeItems[] = $itemData;
        }

        libxml_clear_errors();

        Log::channel('busy_logs')->info('Complete BUSY Items', [
            'count' => count($completeItems),
            'details_loaded' => count(array_filter(
                $completeItems,
                fn($item) => $item['details_loaded']
            )),
            'details_failed' => count(array_filter(
                $completeItems,
                fn($item) => !$item['details_loaded']
            )),
            'items' => $completeItems,
        ]);

        return [
            ...$response,
            'items' => $completeItems,
        ];
    }

//end new product code
    // -------------------------------------------------------------------------
    // Voucher / transaction queries
    // -------------------------------------------------------------------------

    /** Fetch BUSY receipt vouchers (VchType = 14).
     * TRAN2 may contain multiple rows for one receipt; group by VchCode.
     * Each row carries company_id and busy_vch_code for traceability.*/
    public function getCollections(int $companyId): array
    {
        $query = "SELECT * FROM TRAN2 WHERE VchType = 14";
        $response = $this->executeQuery($query);
        if (!($response['success'] ?? false)) {
            return $response;
        }
        $body = trim((string) ($response['body'] ?? ''));
        if ($body === '') {
            return [...$response, 'collections' => []];
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            Log::channel('busy_logs')->error('Failed to parse BUSY receipt XML', [
                'errors' => libxml_get_errors(),
            ]);
            libxml_clear_errors();
            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to parse BUSY receipt XML.',
                'collections' => [],
            ];
        }
        $xml->registerXPathNamespace('z', '#RowsetSchema');
        $rows = $xml->xpath('//z:row') ?: [];
        $vouchers = [];
        foreach ($rows as $row) {
            $a = $row->attributes();
            $vchCode = trim((string) ($a['VchCode'] ?? ''));
            $masterCode = trim((string) ($a['MasterCode1'] ?? ''));
            if ($vchCode === '' || $masterCode === '') {
                continue;
            }
            $vouchers[$vchCode] ??= [
                'company_id' => $companyId,
                'busy_vch_code' => $vchCode,
                'voucher_type' => (int) ($a['VchType'] ?? 14),
                'voucher_date' => $this->busyDate((string) ($a['Date'] ?? '')),
                'voucher_no' => trim((string) ($a['VchNo'] ?? '')),
                'voucher_series_code' => trim((string) ($a['VchSeriesCode'] ?? '')),
                'rows' => [],
            ];
            $vouchers[$vchCode]['rows'][] = [
                'company_id' => $companyId,
                'busy_vch_code' => $vchCode,
                'master_code' => $masterCode,
                'amount' => is_numeric((string) ($a['Value1'] ?? null))
                    ? (float) $a['Value1']
                    : 0.0,
                'short_narration' => trim((string) ($a['ShortNar'] ?? '')),
                'sr_no' => (int) ($a['SrNo'] ?? 0),
            ];
        }
        return [
            ...$response,
            'collections' => array_values($vouchers),
        ];
    }

    /** Convert BUSY's date string to Y-m-d; return null for invalid/empty values.*/
    private function busyDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '1899-12-30')) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
    // Lookup helpers
    /** Find BUSY product by name. */
    public function findItemByName(string $name): array
    {
        $name = str_replace("'", "''", trim($name));
        $query = "SELECT TOP 1 * FROM MASTER1 WHERE MASTERTYPE = 6 AND PARENTGRP = 401 AND NAME = '{$name}'";
        return $this->executeQuery($query);
    }

    /** Find voucher by reference number. */
    public function findVoucherByRefNo(string $refNo, string $voucherTable = 'SALE_VOUCHER'): array
    {
        $refNo = str_replace("'", "''", trim($refNo));
        $query = "SELECT TOP 1 VOUCHERID,REFNO FROM {$voucherTable} WHERE REFNO = '{$refNo}'";
        return $this->executeQuery($query);
    }

    /** Check whether a customer/ledger exists in BUSY. */
    public function ledgerExists(string $name): bool
    {
        $result = $this->findCustomerByName($name);
        if (!($result['success'] ?? false)) {
            return false;
        }
        return str_contains($result['body'] ?? '', '<NAME>') || str_contains($result['body'] ?? '', $name);
    }

    // Outstanding / financial data
    /**
     * Fetch current customer-wise outstanding amount from BUSY.
     * Customers are identified from MASTER1:
     * MASTERTYPE = 2
     * PARENTGRP = 116 (Sundry Debtors)
     * The result is grouped by MasterCode1 and returned as:
     * [
     *     [
     *         'master_code' => '123',
     *         'name' => 'ABC Customer',
     *         'outstanding_amount' => 25000.00,
     *     ],
     * ]
     */

    public function getCustomerOutstanding(): array
    {
        $query = "SELECT M.Code AS MasterCode, M.Name AS Name, SUM(T.Value1) AS OutstandingAmount FROM MASTER1 M LEFT JOIN TRAN2 T ON T.MasterCode1 = M.Code WHERE M.MASTERTYPE = 2 AND M.PARENTGRP = 116 GROUP BY M.Code, M.Name";

        Log::channel('busy_logs')->info('BUSY Customer Outstanding Query', [
            'query' => $query,
        ]);

        $response = $this->executeQuery($query);
        if (!($response['success'] ?? false)) {
            Log::channel('busy_logs')->error('BUSY Customer Outstanding Query Failed', [
                'description' => $response['description'] ?? 'Unknown error',
                'response' => $response,
            ]);
            return [
                ...$response,
                'outstandings' => [],
            ];
        }

        $body = trim((string) ($response['body'] ?? ''));
        if ($body === '') {
            Log::channel('busy_logs')->warning('BUSY Customer Outstanding returned empty response.');
            return [
                ...$response,
                'outstandings' => [],
            ];
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            $errors = libxml_get_errors();
            Log::channel('busy_logs')->error(
                'Failed to parse BUSY customer outstanding XML',
                [
                    'body' => $body,
                    'errors' => $errors,
                ]
            );
            libxml_clear_errors();
            return [
                ...$response,
                'success' => false,
                'description' => 'Unable to parse BUSY customer outstanding XML.',
                'outstandings' => [],
            ];
        }
        libxml_clear_errors();
        /* BUSY returns query results using RowsetSchema.*/
        $xml->registerXPathNamespace('z', '#RowsetSchema');
        $rows = $xml->xpath('//z:row') ?: [];
        $outstandings = [];
        foreach ($rows as $row) {
            $attributes = $row->attributes();
            $masterCode = trim((string) ($attributes['MasterCode'] ?? ''));
            $name = trim((string) ($attributes['Name'] ?? ''));
            if ($masterCode === '') {
                continue;
            }
            $rawAmount = trim((string) ($attributes['OutstandingAmount'] ?? '0'));
            /* Handle empty / NULL values returned by BUSY.*/
            if ($rawAmount === '') {
                $rawAmount = '0';
            }
            /* Remove commas if BUSY returns values such as: 1,25,000.50 */
            $rawAmount = str_replace(',', '', $rawAmount);
            $amount = is_numeric($rawAmount) ? (float) $rawAmount : 0.00;
            $outstandings[] = [
                'master_code' => $masterCode,
                'name' => $name,
                'due_amount' => round($amount, 2),
            ];
        }
        Log::channel('busy_logs')->info(
            'BUSY Customer Outstanding Fetched',
            [
                'count' => count($outstandings),
                'outstandings' => $outstandings,
            ]
        );

        return [
            ...$response,
            'success' => true,
            'outstandings' => $outstandings,
        ];
    }

    // Sales vouchers
    /**Create a BUSY sale voucher. BUSY: SC=2, VchType=9.*/
    public function createSaleVoucher(string $voucherXml): array
    {
        return $this->request([
            'SC' => 2,
            'VchType' => self::SALE_VOUCHER_TYPE,
            'VchXml' => $voucherXml,
        ]);
    }

    /** Modify an existing BUSY sale voucher. BUSY: SC=3, VchType=9, ModifyKey=3.*/
    public function modifySaleVoucher(string $voucherXml): array
    {
        return $this->request([
            'SC' => 3,
            'VchType' => self::SALE_VOUCHER_TYPE,
            'VchXml' => $voucherXml,
            'ModifyKey' => 3,
        ]);
    }
}
