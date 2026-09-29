<?php
namespace App\Services\busy;

use App\Services\BusyApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusyToDsaTaxes
{
    private string $taxTable = 'tax_types';

    public function __construct(
        private BusyApiService $busyApiService
    ) {}

    public function fetchTaxes(int $company_id): array
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
            /* Get complete taxes from BUSY.*/
            $response = $this->busyApiService->getTaxes();

            Log::channel('busy')->info('BUSY Tax Data', [
                'company_id' => $company_id,
                'response' => $response,
            ]);

            if (!($response['success'] ?? false)) {
                throw new \RuntimeException($response['description'] ?? 'BUSY tax fetch failed.');
            }

            /* getTaxes() now returns complete tax data. */
            $taxes = $response['taxes'] ?? [];
            $result['fetched'] = count($taxes);

            /* Create or update DSA taxes.*/
            $sync = $this->createOrUpdateDsaTaxes($taxes, $company_id);
            $result['inserted'] = $sync['inserted'];
            $result['updated'] = $sync['updated'];
            $result['skipped'] = $sync['skipped'];
            /* Deactivate DSA taxes that no longer exist in BUSY. */
            $result['deactivated'] = $this->deactivateMissingTaxes($taxes, $company_id);

            $result['success'] = true;

            Log::channel('busy')->info('Tax Pull Completed', [
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
            Log::channel('busy')->error('BUSY Tax Sync Failed', [
                'company_id' => $company_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $result;
        } finally {
            $result['duration_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        }
    }

    private function createOrUpdateDsaTaxes(array $taxes, int $company_id): array {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($taxes as $tax) {
            $busyTaxId = trim((string) ($tax['master_code'] ?? ''));
            $name = trim((string) ($tax['name'] ?? ''));
            if ($busyTaxId === '' || $name === '') {
                $skipped++;
                continue;
            }

            $percent = $this->extractTaxPercent($name);
            $data = [
                'name' => $name,
                'display_name' => $name,
                'percent' => $percent,
                'default_flag' => 0,
                'updated_at' => now(),
            ];

            $existing = DB::table($this->taxTable)
                ->where('company_id', $company_id)
                ->where('busytax_id', $busyTaxId)
                ->first();

            if ($existing) {
                DB::table($this->taxTable)
                    ->where('id', $existing->id)
                    ->update($data);
                $updated++;
            } else {
                DB::table($this->taxTable)->insert([
                    'company_id' => $company_id,
                    'busytax_id' => $busyTaxId,
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

    private function deactivateMissingTaxes(array $taxes, int $company_id): int {
        $busyTaxIds = collect($taxes)
            ->pluck('master_code')
            ->filter()
            ->map(fn ($id) => trim((string) $id))
            ->unique()
            ->values()
            ->toArray();

        if (empty($busyTaxIds)) {
            return 0;
        }
    
        return DB::table($this->taxTable)
            ->where('company_id', $company_id)
            ->whereNotNull('busytax_id')
            ->whereNotIn('busytax_id', $busyTaxIds)
            ->update([
                'status' => 'Inactive',
                'updated_at' => now(),
            ]);
    }

    private function extractTaxPercent(string $name): float
    {
        $name = trim($name);
        if ($name === '') {
            return 0.0;
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*%/', $name, $matches)) {
            return (float) $matches[1];
        }
        return 0.0;
    }
}