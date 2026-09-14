<?php

namespace App\Services\busy;

use App\Services\BusyApiService;
use Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Resolvers\CountryResolver;
use App\Country;
use Throwable;

class BusyToDSAParty
{
    private string $partiesTable = 'clients';

    public function __construct(
        private BusyApiService $busyApiService
    ) {}

    // public function fetchParties(int $company_id): array
    // {
    //     $startedAt = microtime(true);
    //     $result = [
    //         'success' => false,
    //         'company_id' => $company_id,
    //         'fetched' => 0,
    //         'inserted' => 0,
    //         'updated' => 0,
    //         'skipped' => 0,
    //         'deactivated' => 0,
    //         'duration_ms' => 0,
    //         'error' => null,
    //     ];

    //     try {
    //         $response = $this->busyApiService->getCustomers();
    //         Log::info("Party data",[$response]);
    //         if (!($response['success'] ?? false)) {
    //             throw new \RuntimeException($response['description'] ?? 'BUSY party fetch failed.');
    //         }
    //         $parties = $this->parseBusyParties($response['body'] ?? '');
    //         $result['fetched'] = count($parties);
    //         $sync = $this->createOrUpdateDsaParties($parties, $company_id);
    //         $result['inserted'] = $sync['inserted'];
    //         $result['updated'] = $sync['updated'];
    //         $result['skipped'] = $sync['skipped'];
    //         $result['deactivated'] = $this->deactivateMissingParties($parties, $company_id);
    //         $result['success'] = true;
    //         Log::channel('busy')->info('Party Pull Completed', [
    //             'company_id' => $company_id,
    //             'fetched' => $result['fetched'],
    //             'inserted' => $result['inserted'],
    //             'updated' => $result['updated'],
    //             'skipped' => $result['skipped'],
    //             'deactivated' => $result['deactivated'],
    //         ]);
    //         return $result;
    //     } catch (Throwable $e) {
    //         $result['error'] = $e->getMessage();
    //         Log::channel('busy')->error('BUSY Party Sync Failed', [
    //             'company_id' => $company_id,
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);
    //         return $result;
    //     } finally {
    //         $result['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
    //     }
    // }
    public function fetchParties(int $company_id): array
    {
        $startedAt = microtime(true);
        $result = [
            'success' => false,
            'company_id' => $company_id,
            'fetched' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'deactivated' => 0,
            'duration_ms' => 0,
            'error' => null,
        ];
        try {
            $response = $this->busyApiService->getCustomers();
            Log::info("Party data", [$response]);
            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description'] ?? 'BUSY party fetch failed.'
                );
            }
            /* getCustomers() now returns complete party data.*/
            $parties = $response['parties'] ?? [];
            $result['fetched'] = count($parties);
            $sync = $this->createOrUpdateDsaParties($parties, $company_id);
            $result['inserted'] = $sync['inserted'];
            $result['updated'] = $sync['updated'];
            $result['skipped'] = $sync['skipped'];
            $result['deactivated'] = $this->deactivateMissingParties($parties, $company_id);
            $result['success'] = true;
            Log::channel('busy')->info('Party Pull Completed', [
                'company_id' => $company_id,
                'fetched' => $result['fetched'],
                'inserted' => $result['inserted'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
                'deactivated' => $result['deactivated'],
            ]);
            return $result;
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            Log::channel('busy')->error('BUSY Party Sync Failed', [
                'company_id' => $company_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $result;
        } finally {
            $result['duration_ms'] = (int) round(
                (microtime(true) - $startedAt) * 1000
            );
        }
    }

    // private function parseBusyParties(string $body): array
    // {
    //     $body = trim($body);
    //     if ($body === '') {
    //         return [];
    //     }
    //     libxml_use_internal_errors(true);
    //     $xml = simplexml_load_string($body);
    //     if ($xml === false) {
    //         Log::channel('busy')->error('Failed to parse BUSY party XML', [
    //             'body' => $body,
    //             'errors' => libxml_get_errors(),
    //         ]);
    //         libxml_clear_errors();
    //         return [];
    //     }
    //     $xml->registerXPathNamespace('z', '#RowsetSchema');
    //     $rows = $xml->xpath('//z:row') ?: [];
    //     $parties = [];
    //     foreach ($rows as $row) {
    //         $attributes = $row->attributes();
    //         $masterCode = trim((string) ($attributes['Code'] ?? ''));
    //         $name = trim((string) ($attributes['Name'] ?? ''));
    //         $mobile = trim((string) ($attributes['Name'] ?? ''));
    //         $gst_no = trim((string) ($attributes['Name'] ?? ''));
    //         $address1 = trim((string) ($attributes['Name'] ?? ''));
    //         $address2 = trim((string) ($attributes['Name'] ?? ''));
    //         $state = trim((string) ($attributes['Name'] ?? ''));
    //         $status = ($attributes['DeactiveMaster'] ?? '') === 'True' ? 'Inactive' : 'Active';
    //         if ($masterCode === '' || $name === '') {
    //             continue;
    //         }
    //         $parties[] = [
    //             'master_code' => $masterCode,
    //             'name' => $name,
    //             'mobile' => $mobile,
    //             'gst_no' => $gst_no,
    //             'address1' => $address1,
    //             'address2' => $address2,
    //             'state' => $state,
    //             'status' => $status,
    //         ];
    //     }

    //     Log::channel('busy')->info('Parsed BUSY Parties', [
    //         'count' => count($parties),
    //         'parties' => $parties,
    //     ]);

    //     return $parties;
    // }

    // private function createOrUpdateDsaParties(array $parties, int $company_id): array
    // {
    //     $inserted = 0;
    //     $updated = 0;
    //     $skipped = 0;

    //     foreach ($parties as $party) {
    //         $busyPartyId = trim((string) ($party['code'] ?? ''));
    //         $name = trim((string) ($party['name'] ?? ''));

    //         if ($busyPartyId === '' || $name === '') {
    //             $skipped++;
    //             continue;
    //         }

    //         $data = [
    //             'name' => $name,
    //             'mobile' => $party['mobile'] ?? null,
    //             'gst_no' => $party['gst_no'] ?? null,
    //             'address1' => $party['address1'] ?? null,
    //             'address2' => $party['address2'] ?? null,
    //             'state' => $party['state'] ?? null,
    //             'updated_at' => now(),
    //         ];

    //         $existing = DB::table($this->partiesTable)
    //             ->where('company_id', $company_id)
    //             ->where('busyparty_id', $busyPartyId)
    //             ->first();

    //         if ($existing) {
    //             DB::table($this->partiesTable)
    //                 ->where('id', $existing->id)
    //                 ->update($data);
    //             $updated++;
    //         } else {
    //             DB::table($this->partiesTable)->insert([
    //                 'company_id' => $company_id,
    //                 'busyparty_id' => $busyPartyId,
    //                 ...$data,
    //                 'created_at' => now(),
    //             ]);
    //             $inserted++;
    //         }
    //     }

    //     return [
    //         'inserted' => $inserted,
    //         'updated' => $updated,
    //         'skipped' => $skipped,
    //     ];
    // }
    private function createOrUpdateDsaParties(array $parties, int $company_id): array
    {
        // Log::info("Bhai sb data aa gaya yaha", [$parties]);
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        foreach ($parties as $party) {
            /* BUSY Master Code */
            $busyPartyId = trim((string) ($party['master_code'] ?? ''));
            $name = trim((string) ($party['name'] ?? ''));
            if ($busyPartyId === '' || $name === '') {
                $skipped++;
                continue;
            }
            $countryId = (!empty($party['country'])) ? CountryResolver::resolveId($party['country']) : null;

            if ($countryId) {
                $country_info = Country::find($countryId);
                $phone_code = $country_info->phonecode ?? ''; // Or whatever field you need
            }
            $data = [
                'company_name' => $name,
                'name' => !empty($party['contact']) ? trim((string) $party['contact']) : null,
                'phone' => !empty($party['telephone']) ? trim((string) $party['telephone']) : null,
                'mobile' => !empty($party['mobile']) ? trim((string) $party['mobile']) : null,
                'fax' => !empty($party['fax']) ? trim((string) $party['fax']) : null,
                'email' => !empty($party['email']) ? trim((string) $party['email']) : null,
                'address_1' => $party['address1'] ?? null,
                'address_2' => $party['address2'] ?? null,
                // 'country' => $party['country'] ?? null,
                'country' => $countryId ?? null,
                'phonecode' => $phone_code ?? '',
                'pan' => !empty($party['it_pan']) ? trim((string) $party['it_pan']) : null,
                // 'gst_no' => !empty($party['gst_no']) ? trim((string) $party['gst_no']) : null,
                // '' => !empty($party['']) ? trim((string) $party['']) : null,
                'credit_days' => is_numeric($party['credit_days_sale'] ?? null) ? (int) $party['credit_days_sale'] : null,
                'opening_balance' => is_numeric($party['op_bal'] ?? null) ? (float) $party['op_bal'] : null,
                'closing_balance' => is_numeric($party['py_bal'] ?? null) ? (float) $party['py_bal'] : null,
                'status' => !empty($party['status']) ? trim((string) $party['status']) : null,
                'updated_at' => now(),
            ];

            $existing = DB::table($this->partiesTable)
                ->where('company_id', $company_id)
                ->where('busyparty_id', $busyPartyId)
                ->first();

            if ($existing) {
                DB::table($this->partiesTable)
                    ->where('id', $existing->id)
                    ->update($data);
                $updated++;
            } else {
                DB::table($this->partiesTable)->insert([
                    'company_id' => $company_id,
                    'busyparty_id' => $busyPartyId,
                    ...$data,
                    'created_at' => now(),
                ]);
                $inserted++;
            }
        }
        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    private function deactivateMissingParties(array $parties, int $company_id): int
    {
        $busyPartyIds = collect($parties)
            ->pluck('master_code')
            ->filter()
            ->map(fn($id) => trim((string) $id))
            ->unique()
            ->values()
            ->toArray();

        if (empty($busyPartyIds)) {
            return 0;
        }

        return DB::table($this->partiesTable)
            ->where('company_id', $company_id)
            ->whereNotNull('busyparty_id')
            ->whereNotIn(
                'busyparty_id',
                $busyPartyIds
            )
            ->update([
                'status' => 'Inactive',
                'updated_at' => now(),
            ]);
    }
}
