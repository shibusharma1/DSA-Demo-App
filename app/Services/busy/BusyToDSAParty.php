<?php

namespace App\Services\busy;

use App\Services\BusyApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusyToDSAParty
{
    private string $partiesTable = 'parties';

    public function __construct(
        private BusyApiService $busyApiService
    ) {}

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
            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description'] ?? 'BUSY party fetch failed.'
                );
            }
            $parties = $this->parseBusyParties(
                $response['body'] ?? ''
            );
            $result['fetched'] = count($parties);
            $sync = $this->createOrUpdateDsaParties(
                $parties,
                $company_id
            );
            $result['inserted'] = $sync['inserted'];
            $result['updated'] = $sync['updated'];
            $result['skipped'] = $sync['skipped'];
            $result['deactivated'] = $this->deactivateMissingParties(
                $parties,
                $company_id
            );
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

    private function parseBusyParties(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return [];
        }
        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $decoded = $decoded['data'];
        } elseif (isset($decoded['customers']) && is_array($decoded['customers'])) {
            $decoded = $decoded['customers'];
        } elseif (isset($decoded['parties']) && is_array($decoded['parties'])) {
            $decoded = $decoded['parties'];
        } elseif (isset($decoded['result']) && is_array($decoded['result'])) {
            $decoded = $decoded['result'];
        }
        if (!is_array($decoded)) {
            return [];
        }
        $parties = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $masterCode = $item['master_code'] ?? $item['MasterCode']  ?? $item['MASTER_CODE']  ?? null;
            $name = $item['name'] ?? $item['Name'] ?? $item['NAME'] ?? null;
            $mobile = $item['mobile']  ?? $item['Mobile']
                ?? $item['MOBILE']
                ?? null;

            $gstNo = $item['gst_no']
                ?? $item['GST_NO']
                ?? $item['GSTIN']
                ?? $item['Gstin']
                ?? null;

            $address1 = $item['address1']
                ?? $item['Address1']
                ?? $item['ADDRESS1']
                ?? null;

            $address2 = $item['address2']
                ?? $item['Address2']
                ?? $item['ADDRESS2']
                ?? null;

            $state = $item['state']
                ?? $item['State']
                ?? $item['STATE']
                ?? null;

            if (
                $masterCode === null ||
                trim((string) $name) === ''
            ) {
                continue;
            }

            $parties[] = [
                'master_code' => trim((string) $masterCode),
                'name' => trim((string) $name),
                'mobile' => $mobile !== null
                    ? trim((string) $mobile)
                    : null,
                'gst_no' => $gstNo !== null
                    ? trim((string) $gstNo)
                    : null,
                'address1' => $address1 !== null
                    ? trim((string) $address1)
                    : null,
                'address2' => $address2 !== null
                    ? trim((string) $address2)
                    : null,
                'state' => $state !== null
                    ? trim((string) $state)
                    : null,
            ];
        }

        return $parties;
    }

    private function createOrUpdateDsaParties(
        array $parties,
        int $company_id
    ): array {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($parties as $party) {
            $busyPartyId = trim(
                (string) ($party['master_code'] ?? '')
            );

            $name = trim(
                (string) ($party['name'] ?? '')
            );

            if ($busyPartyId === '' || $name === '') {
                $skipped++;
                continue;
            }

            $data = [
                'name' => $name,
                'mobile' => $party['mobile'] ?? null,
                'gst_no' => $party['gst_no'] ?? null,
                'address1' => $party['address1'] ?? null,
                'address2' => $party['address2'] ?? null,
                'state' => $party['state'] ?? null,
                'updated_at' => now(),
            ];

            $existing = DB::table($this->partiesTable)
                ->where('company_id', $company_id)
                ->where('busy_party_id', $busyPartyId)
                ->first();

            if ($existing) {
                DB::table($this->partiesTable)
                    ->where('id', $existing->id)
                    ->update($data);

                $updated++;
            } else {
                DB::table($this->partiesTable)->insert([
                    'company_id' => $company_id,
                    'busy_party_id' => $busyPartyId,
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

    private function deactivateMissingParties(
        array $parties,
        int $company_id
    ): int {
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
            ->whereNotNull('busy_party_id')
            ->whereNotIn(
                'busy_party_id',
                $busyPartyIds
            )
            ->update([
                'status' => 'Inactive',
                'updated_at' => now(),
            ]);
    }
}
