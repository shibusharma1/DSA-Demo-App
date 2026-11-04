<?php

namespace App\Services\busy;

use App\Services\BusyApiService;
// use Attribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
// use App\Services\QuickBooks\Resolvers\CountryResolver;
//manjit dai update
// use Illuminate\Support\Str;
// use App\PermissionCategory;
// use Illuminate\Http\Request;
// use Spatie\Permission\Models\Role;
// use Spatie\Permission\Models\Permission;
// use App\PartyType;

// use App\Country;
use Throwable;

class BusyToDSAParty
{
    private string $partiesTable = 'parties_busy';

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
            // $countryId = (!empty($party['country'])) ? CountryResolver::resolveId($party['country']) : null;

            // if ($countryId) {
            //     $country_info = Country::find($countryId);
            //     $phone_code = $country_info->phonecode ?? ''; // Or whatever field you need
            // }
            Log::info('Party Type checking',[$party['parent_group']]);
            // Manjit dai update
            // if (!empty($party['parent_group'])) {
            //     $partyType = PartyType::firstOrCreate(
            //         [
            //             'company_id' => $company_id,
            //             'name'       => $party['parent_group'],
            //         ],
            //         [
            //             'short_name' => Str::upper(Str::substr($party['parent_group'], 0, 2)),
            //             'parent_id'  => 0,
            //         ]
            //     );

            //     if ($partyType->wasRecentlyCreated) {

            //         $permissionCategory = new PermissionCategory;

            //         $permissionCategory->company_id               = $company_id;
            //         $permissionCategory->permission_model_id      = $partyType->id;
            //         $permissionCategory->permission_model         = 'PartyType';
            //         $permissionCategory->permission_category_type = 'Company';
            //         $permissionCategory->name                     = str_replace(' ', '_', $partyType->name);
            //         $permissionCategory->display_name             = 'Party Type: ' . $partyType->name;
            //         $permissionCategory->indexing_priority        = 10;
            //         $permissionCategory->save();

            //         $partyTypePermissions = [
            //             $this->addPermission($partyType, $permissionCategory, 'create', $company_id),
            //             $this->addPermission($partyType, $permissionCategory, 'view', $company_id),
            //             $this->addPermission($partyType, $permissionCategory, 'update', $company_id),
            //             $this->addPermission($partyType, $permissionCategory, 'delete', $company_id),
            //             $this->addPermission($partyType, $permissionCategory, 'status', $company_id),
            //         ];
            //         $role = Role::whereCompanyId($company_id)->whereName('Full Access')->whereGuardName('web')->first();
            //         if ($role && !empty($partyTypePermissions)) {
            //             $role->givePermissionTo($partyTypePermissions);
            //         }
            //     }
            // } else {
            //     $partyType = '';
            // }

            $data = [
                'company_name' => $name,
                // Manjit dai Update
                // 'client_type' => $partyType->id ?? 0,
                'name' => !empty($party['contact']) ? trim((string) $party['contact']) : null,
                'phone' => !empty($party['telephone']) ? trim((string) $party['telephone']) : null,
                'mobile' => !empty($party['mobile']) ? trim((string) $party['mobile']) : null,
                'fax' => !empty($party['fax']) ? trim((string) $party['fax']) : null,
                'email' => !empty($party['email']) ? trim((string) $party['email']) : null,
                'address_1' => $party['address1'] ?? null,
                'address_2' => $party['address2'] ?? null,
                // 'country' => $party['country'] ?? null,
                // 'country' => $countryId ?? null,
                'country' => 10,
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
