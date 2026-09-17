<?php

namespace App\Services\busy;

use App\Services\BusyApiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BusyToDsaItem
{
    private string $productsTable = 'products';
    private string $unitsTable = 'unit_types';
    private string $categoriesTable = 'item_categories';
    private array $categoryCache = [];
    private array $unitCache = [];

    public function __construct(
        private BusyApiService $busyApiService
    ) {}
    public function fetchProducts(int $company_id): array
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
            $response = $this->busyApiService->getItems();

            if (!($response['success'] ?? false)) {
                throw new \RuntimeException(
                    $response['description'] ?? 'BUSY item fetch failed.'
                );
            }

            // getItems() already returns parsed item data.
            $items = $response['items'] ?? [];

            $result['fetched'] = count($items);

            $sync = $this->createOrUpdateDsaProducts($items, $company_id);

            $result['inserted'] = $sync['inserted'];
            $result['updated'] = $sync['updated'];
            $result['skipped'] = $sync['skipped'];

            $result['deactivated'] = $this->deactivateMissingProducts(
                $items,
                $company_id
            );

            $result['success'] = true;

            Log::channel('busy')->info('Item Pull Completed', [
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

            Log::channel('busy')->error('BUSY Item Sync Failed', [
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

    private function createOrUpdateDsaProducts(array $items, int $company_id): array
    {
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        Log::info("Unit test", [$items]);

        foreach ($items as $item) {
            $busyProductId = trim((string) ($item['master_code'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            if ($busyProductId === '' || $name === '') {
                $skipped++;
                continue;
            }
            $unitCode = trim((string) ($item['unit_name'] ?? ''));
            $unitId = $unitCode !== '' ? $this->ensureUnitId($unitCode, $company_id) : null;
            $categoryName = trim((string) ($item['parent_group'] ?? ''));
            $categoryId = $categoryName !== '' ? $this->ensureCategoryId($categoryName, $company_id) : null;
            $mrp = $this->parseNumeric($item['sale_price'] ?? null);
            $data = [
                'product_name' => $name,
                'product_code' => null,
                'category_id' => $categoryId,
                'brand' => null,
                'unit' => $unitId,
                'mrp' => $mrp,
                'details' => null,
                'short_desc' => $item['short_desc'] ?? null,
                'status' => $item['status'] ?? 'Active',
                'updated_at' => now(),
            ];

            $existing = DB::table($this->productsTable)
                ->where('company_id', $company_id)
                ->where('busyproduct_id', $busyProductId)
                ->first();

            if ($existing) {
                DB::table($this->productsTable)
                    ->where('id', $existing->id)
                    ->update($data);

                $updated++;
            } else {
                DB::table($this->productsTable)->insert([
                    'company_id' => $company_id,
                    'busyproduct_id' => $busyProductId,
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

    private function ensureUnitId(string $unitCode, int $company_id): ?int
    {
        $unit = $this->normalizeName($unitCode);
        if ($unit === '') {
            return null;
        }
        $key = mb_strtolower($unit);
        if (isset($this->unitCache[$company_id][$key])) {
            return (int) $this->unitCache[$company_id][$key];
        }
        // First check BUSY unit ID
        $existing = DB::table($this->unitsTable)
            ->where('company_id', $company_id)
            ->where('busyunit_id', $unit)
            ->first();
        if ($existing) {
            return $this->unitCache[$company_id][$key] = (int) $existing->id;
        }
        // Then check unit name
        $existing = DB::table($this->unitsTable)
            ->where('company_id', $company_id)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->first();
        if ($existing) {
            return $this->unitCache[$company_id][$key] = (int) $existing->id;
        }
        // Create unit
        $id = DB::table($this->unitsTable)->insertGetId([
            'company_id' => $company_id,
            'name' => $unit,
            'symbol' => $unit,
            'busyunit_id' => $unit,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->unitCache[$company_id][$key] = (int) $id;
    }

    private function ensureCategoryId(string $categoryName, int $company_id): ?int
    {
        $category = $this->normalizeName($categoryName);
        if ($category === '') {
            return null;
        }
        $key = mb_strtolower($category);
        if (isset($this->categoryCache[$company_id][$key])) {
            return (int) $this->categoryCache[$company_id][$key];
        }
        $existing = DB::table($this->categoriesTable)
            ->where('company_id', $company_id)
            ->whereRaw('LOWER(name) = ?', [$key])
            ->first();
        if ($existing) {
            return $this->categoryCache[$company_id][$key] = (int) $existing->id;
        }
        $id = DB::table($this->categoriesTable)->insertGetId([
            'company_id' => $company_id,
            'name' => $category,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $this->categoryCache[$company_id][$key] = (int) $id;
    }
    
    private function deactivateMissingProducts(array $items, int $company_id): int
    {
        $busyProductIds = collect($items)
            ->pluck('master_code')
            ->filter()
            ->map(fn($id) => trim((string) $id))
            ->unique()
            ->values()
            ->toArray();

        if (empty($busyProductIds)) {
            return 0;
        }

        return DB::table($this->productsTable)
            ->where('company_id', $company_id)
            ->whereNotNull('busyproduct_id')
            ->whereNotIn('busyproduct_id', $busyProductIds)
            ->update([
                'status' => 'Inactive',
                'updated_at' => now(),
            ]);
    }

    private function normalizeName(?string $name): string
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));
        return $name;
    }

    private function parseNumeric($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '', $value);
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        if ($value === '' || $value === '-' || $value === '.') {
            return null;
        }

        return (float) $value;
    }
}
