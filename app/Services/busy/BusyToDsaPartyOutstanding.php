<?php

namespace App\Services\busy;

use App\Services\BusyApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusyToDsaPartyOutstanding
{
    public function __construct(
        private readonly BusyApiService $busyApiService,
    ) {}

    public function fetchPartyOutstanding(int $companyId): array
    {
        $result = [
            'success' => false,
            'company_id' => $companyId,
            'fetched' => 0,
            'matched' => 0,
            'updated' => 0,
            'skipped' => 0,
            'error' => null,
        ];

        try {
            $response = $this->busyApiService->getCustomerOutstanding();
            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description']
                        ?? 'BUSY customer outstanding fetch failed.'
                );
            }
            $outstandings = $response['outstandings'] ?? [];
            $result['fetched'] = count($outstandings);
            foreach ($outstandings as $outstanding) {
                $masterCode = trim(
                    (string) ($outstanding['master_code'] ?? '')
                );
                $name = trim(
                    (string) ($outstanding['name'] ?? '')
                );
                $amount = (float) (
                    $outstanding['due_amount'] ?? 0
                );
                if ($masterCode === '') {
                    $result['skipped']++;
                    Log::channel('busy')->warning(
                        'BUSY outstanding skipped: master code missing',
                        [
                            'company_id' => $companyId,
                            'name' => $name,
                            'due_amount' => $amount,
                        ]
                    );
                    continue;
                }
                /*
                 * Match BUSY customer with DSA party
                 * parties_busy.busyparty_id
                 * contains BUSY MasterCode.
                 */
                $party = DB::table('parties_busy')
                    ->where('company_id', $companyId)
                    ->where('busyparty_id', $masterCode)
                    ->first();
                if (!$party) {
                    $result['skipped']++;
                    Log::channel('busy')->warning(
                        'BUSY outstanding skipped: party not mapped',
                        [
                            'company_id' => $companyId,
                            'master_code' => $masterCode,
                            'name' => $name,
                            'due_amount' => $amount,
                        ]
                    );
                    continue;
                }
                $result['matched']++;
                DB::table('parties_busy')->where('id', $party->id)->update(['due_amount' => round($amount, 2),'updated_at' => now(),]);
                $result['updated']++;
                Log::channel('busy')->info(
                    'BUSY customer outstanding updated',
                    [
                        'company_id' => $companyId,
                        'party_id' => $party->id,
                        'busyparty_id' => $masterCode,
                        'party_name' => $name,
                        'due_amount' => round($amount, 2),
                    ]
                );
            }
            $result['success'] = true;
            Log::channel('busy')->info(
                'BUSY customer outstanding sync completed',
                $result
            );
            return $result;
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            Log::channel('busy')->error(
                'BUSY customer outstanding sync failed',
                [
                    'company_id' => $companyId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return $result;
        }
    }
}