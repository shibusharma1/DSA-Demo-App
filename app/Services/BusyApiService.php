<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BusyApiService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.busy.base_url'), 'http://127.0.0.1:981');
        $this->username = config('services.busy.username', 'a');
        $this->password = config('services.busy.password', 'a');
    }

    public function request(array $headers): array
    {
        try {
            $headers['UserName'] = $this->username;
            $headers['Pwd'] = $this->password;

            Log::channel('busy')->info('BUSY Request', [
                'url' => $this->baseUrl,
                'headers' => $headers,
            ]);

            // $response = Http::withHeaders($headers)->get($this->baseUrl);
            $response = Http::withHeaders($headers)->get('http://127.0.0.1:981');

            Log::channel('busy')->info('BUSY Response', [
                'status' => $response->status(),
                'result' => $response->header('Result'),
                'description' => $response->header('Description'),
            ]);

            return [
                'success' => $response->successful() && $response->header('Result') === 'T',
                'status' => $response->status(),
                'result' => $response->header('Result'),
                'description' => $response->header('Description'),
                'body' => $response->body(),
            ];
        } catch (Exception $e) {
            Log::channel('busy')->error('BUSY Exception', [
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

    /**
     * Execute SQL Query (SC=1).
     */
    public function executeQuery(string $query): array
    {
        return $this->request([
            'SC' => 1,
            'Qry' => $query,
        ]);
    }

    /**
     * Create Master (SC=5).
     */
    public function createMaster(int $masterType, string $xml): array
    {
        return $this->request([
            'SC' => 5,
            'MasterType' => $masterType,
            'MasterXML' => $xml,
        ]);
    }

    /**
     * Create Voucher (SC=2).
     */
    public function createVoucher(int $voucherType, string $xml): array
    {
        return $this->request([
            'SC' => 2,
            'VchType' => $voucherType,
            'VchXML' => $xml,
        ]);
    }

    /**
     * Fetch Master XML (SC=9).
     */
    public function getMaster(int $masterCode): array
    {
        return $this->request([
            'SC' => 9,
            'MasterCode' => $masterCode,
        ]);
    }


    /**
     * Connector health check.
     */
    public function healthCheck(): bool
    {
        $response = $this->executeQuery("SELECT TOP 5 * FROM MASTER1");
        return $response['success'];
    }

    /**
     * Fetch BUSY customers.
     */
    // public function getCustomers(): array
    // {
    // This is for receipt
    //     $query = "SELECT * FROM TRAN1 WHERE VchType = 14";
    //     return $this->executeQuery($query);
    // }
    // public function getCustomers(): array
    // {
    //     //Account Group
    //     $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 1 AND ParentGrp = 102";
    //     return $this->executeQuery($query);
    // }
    // public function getCustomers(): array
    // {
    //     $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 2 AND PARENTGRP = 116";
    //     $xml = $this->getMaster(1167);
    //     Log::info("This is the complete XML", [$xml]);
    //     return $this->executeQuery($query);
    // }

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
    public function getCustomers(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 2 AND PARENTGRP = 116";
        $response = $this->executeQuery($query);
        if (!($response['success'] ?? false)) {
            return $response;
        }
        $body = $response['body'] ?? '';
        if (trim($body) === '') {
            return $response;
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        if ($xml === false) {
            Log::channel('busy')->error('Failed to parse BUSY customer list', [
                'body' => $body,
                'errors' => libxml_get_errors(),
            ]);
            libxml_clear_errors();
            return $response;
        }
        $xml->registerXPathNamespace('z', '#RowsetSchema');
        $rows = $xml->xpath('//z:row') ?: [];
        $completeParties = [];
        foreach ($rows as $row) {
            $attributes = $row->attributes();
            $masterCode = trim((string) ($attributes['Code'] ?? ''));
            $name = trim((string) ($attributes['Name'] ?? ''));
            if ($masterCode === '' || $name === '') {
                continue;
            }
            /* Get complete customer/account details using BUSY GetMasterXML.*/
            $masterResponse = $this->getMaster((int) $masterCode);
            if (!($masterResponse['success'] ?? false)) {
                Log::channel('busy')->warning('Failed to fetch complete BUSY party', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'description' => $masterResponse['description'] ?? null,
                ]);
                /*
             * Keeping the basic MASTER1 data even if complete XML fails.*/
                $completeParties[] = [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'mobile' => null,
                    'email' => null,
                    'whatsapp_no' => null,
                    'gst_no' => null,
                    'address1' => null,
                    'address2' => null,
                    'telephone' => null,
                    'fax' => null,
                    'country' => null,
                    'state' => null,
                    'city' => null,
                    'area' => null,
                    'contact' => null,
                    'it_pan' => null,
                    'it_ward' => null,
                    'tin_no' => null,
                    'transport' => null,
                    'station' => null,
                    'account_no' => null,
                    'parent_group' => null,
                    'supplier_type' => null,
                    'credit_days_sale' => null,
                    'credit_days_purchase' => null,
                    'price_level' => null,
                    'price_level_purchase' => null,
                    'tax_type' => null,
                    'cheque_print_name' => null,
                    'reverse_charge_type' => null,
                    'input_type' => null,
                    'status' => (($attributes['DeactiveMaster'] ?? '') === 'True') ? 'Inactive' : 'Active',
                ];
                continue;
            }
            $masterXml = trim($masterResponse['body'] ?? '');
            if ($masterXml === '') {
                continue;
            }
            /* Parse complete Account XML.*/
            libxml_use_internal_errors(true);
            $account = simplexml_load_string($masterXml);
            if ($account === false) {
                Log::channel('busy')->warning('Failed to parse BUSY Master XML', [
                    'master_code' => $masterCode,
                    'name' => $name,
                    'body' => $masterXml,
                    'errors' => libxml_get_errors(),
                ]);
                libxml_clear_errors();
                continue;
            }
            $address = $account->Address;
            $completeParties[] = [
            /* MASTER1 information*/
                'master_code' => $masterCode,
                'name' => trim((string) ($account->Name ?: $name)),
                'address1' => trim((string) ($address->Address1 ?? '')) ?: null,
                'address2' => trim((string) ($address->Address2 ?? '')) ?: null,
                'telephone' => trim((string) ($address->TelNo ?? '')) ?: null,
                'fax' => trim((string) ($address->Fax ?? '')) ?: null,
                'email' => trim((string) ($address->Email ?? '')) ?: null,
                'mobile' => trim((string) ($address->Mobile ?? '')) ?: null,
                'whatsapp_no' => trim((string) ($address->WhatsAppNo ?? '')) ?: null,
                'contact' => trim((string) ($address->Contact ?? '')) ?: null,
                'it_pan' => trim((string) ($address->ITPAN ?? '')) ?: null,
                'it_ward' => trim((string) ($address->ITWard ?? '')) ?: null,
                'st37' => trim((string) ($address->ST37 ?? '')) ?: null,
                'tin_no' => trim((string) ($address->TINNo ?? '')) ?: null,
                'gst_no' => trim((string) ($address->GSTNo ?? '')) ?: null,
                'country' => trim((string) ($address->CountryName ?? '')) ?: null,
                'state' => trim((string) ($address->StateName ?? '')) ?: null,
                'city' => trim((string) ($address->CityName ?? '')) ?: null,
                'area' => trim((string) ($address->AreaName ?? '')) ?: null,
                'cont_dept_name' => trim((string) ($address->ContDeptName ?? '')) ?: null,
                'transport' => trim((string) ($address->Transport ?? '')) ?: null,
                'station' => trim((string) ($address->Station ?? '')) ?: null,
                'account_no' => trim((string) ($address->AccNo ?? '')) ?: null,
                'tmp_master_code' => trim((string) ($address->TmpMasterCode ?? '')) ?: null,
                'c3' => trim((string) ($address->C3 ?? '')) ?: null,
                'bank_name' => trim((string) ($address->C4 ?? '')) ?: null,
                'ifsc_code' => trim((string) ($address->C5 ?? '')) ?: null,
                'swift_code' => trim((string) ($address->C8 ?? '')) ?: null,
                'parent_group' => trim((string) ($account->ParentGroup ?? '')) ?: null,
                'op_bal' => trim((string) ($account->OPBal ?? '')) ?: null,
                'py_bal' => trim((string) ($account->PYBal ?? '')) ?: null,
                'bill_by_bill_balancing' => trim((string) ($account->BillByBillBalancing ?? '')) ?: null,
                'supplier_type' => trim((string) ($account->SupplierType ?? '')) ?: null,
                'credit_days_sale' => trim((string) ($account->CreditDaysForSale ?? '')) ?: null,
                'credit_days_purchase' => trim((string) ($account->CreditDaysForPurc ?? '')) ?: null,
                'price_level' => trim((string) ($account->PriceLevel ?? '')) ?: null,
                'price_level_purchase' => trim((string) ($account->PriceLevelForPurc ?? '')) ?: null,
                'tax_type' => trim((string) ($account->TaxType ?? '')) ?: null,
                'tmp_code' => trim((string) ($account->tmpCode ?? '')) ?: null,
                'tmp_parent_group_code' => trim((string) ($account->tmpParentGrpCode ?? '')) ?: null,
                'cheque_print_name' => trim((string) ($account->ChequePrintName ?? '')) ?: null,
                'reverse_charge_type' => trim((string) ($account->ReverseChargeType ?? '')) ?: null,
                'input_type' => trim((string) ($account->InputType ?? '')) ?: null,
                'status' => (($attributes['DeactiveMaster'] ?? '') === 'True') ? 'Inactive' : 'Active',
            ];
        }
        Log::channel('busy')->info('Complete BUSY Parties', [
            'count' => count($completeParties),
            'parties' => $completeParties,
        ]);
        /* Only the body is changed to contain the complete party data.*/
        return [
            ...$response,
            'parties' => $completeParties,
        ];
    }

    public function getTaxes(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 25 AND PARENTGRP = 0";
        return $this->executeQuery($query);
    }

    public function getUnits(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 8 AND PARENTGRP = 0";
        return $this->executeQuery($query);
    }

    /**
     * Find BUSY customer by name.
     */
    public function findCustomerByName(string $name): array
    {
        $name = str_replace("'", "''", trim($name));
        $query = "SELECT TOP 1 * FROM MASTER1 WHERE MASTERTYPE = 2 AND PARENTGRP = 116 AND NAME = '{$name}'";
        return $this->executeQuery($query);
    }
    /**
     * Fetch BUSY item categories.
     */
    public function getItemCategories(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 5 AND PARENTGRP = 0";
        return $this->executeQuery($query);
    }

    /**
     * Fetch BUSY products/items.
     */
    public function getItems(): array
    {
        $query = "SELECT * FROM MASTER1 WHERE MASTERTYPE = 6 AND PARENTGRP = 401";
        return $this->executeQuery($query);
    }

    /**
     * Find BUSY product by name.
     */
    public function findItemByName(string $name): array
    {
        $name = str_replace("'", "''", trim($name));
        $query = "SELECT TOP 1 * FROM MASTER1 WHERE MASTERTYPE = 6 AND PARENTGRP = 401 AND NAME = '{$name}'";
        return $this->executeQuery($query);
    }

    /**
     * Find voucher by reference number.
     */
    public function findVoucherByRefNo(string $refNo, string $voucherTable = 'SALE_VOUCHER'): array
    {
        $refNo = str_replace("'", "''", trim($refNo));
        $query = "SELECT TOP 1 VOUCHERID,REFNO FROM {$voucherTable} WHERE REFNO = '{$refNo}'";
        return $this->executeQuery($query);
    }

    /**
     * Check whether a customer/ledger exists in BUSY.
     */
    public function ledgerExists(string $name): bool
    {
        $result = $this->findCustomerByName($name);
        if (!($result['success'] ?? false)) {
            return false;
        }

        return str_contains($result['body'] ?? '', '<NAME>') || str_contains($result['body'] ?? '', $name);
    }
}
