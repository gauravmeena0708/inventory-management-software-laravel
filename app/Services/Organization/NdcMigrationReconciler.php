<?php

namespace App\Services\Organization;

use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Entry;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\Stock\LegacyLocationStockMigrator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NdcMigrationReconciler
{
    public function __construct(
        protected bool $dryRun = false,
        protected bool $resume = false,
    ) {}

    /**
     * Apply or simulate the idempotent NDC mapping.
     *
     * Resume mode differs from a normal re-apply by leaving already-correct
     * memberships untouched and processing only unresolved records.
     *
     * @return array<string, int|string>
     */
    public function reconcile(): array
    {
        $connection = DB::connection();
        $connection->beginTransaction();

        try {
            [$ndcUnit, $ndcSite] = $this->baseline();

            $stats = [
                'mode' => $this->dryRun ? 'dry-run' : ($this->resume ? 'resume' : 'apply'),
                'locations_mapped' => 0,
                'location_paths_rebuilt' => 0,
                'assets_backfilled' => 0,
                'users_assigned' => 0,
                'users_skipped' => 0,
            ];

            $locations = Location::query()->withTrashed()->orderBy('id')->lockForUpdate()->get();

            foreach ($locations->whereNull('site_id') as $location) {
                $parentSiteId = $location->parent_id
                    ? $locations->firstWhere('id', $location->parent_id)?->site_id
                    : null;

                $location->site_id = $parentSiteId ?? $ndcSite->id;
                $location->save();
                $stats['locations_mapped']++;
            }

            $locations = Location::query()->withTrashed()->orderBy('id')->get();
            $expectedPaths = $this->expectedPaths($locations);

            foreach ($locations as $location) {
                $expected = $expectedPaths[$location->id] ?? null;

                if ($expected !== null && $location->path !== $expected) {
                    $location->path = $expected;
                    $location->save();
                    $stats['location_paths_rebuilt']++;
                }
            }

            Asset::query()
                ->whereNull('organizational_unit_id')
                ->orderBy('id')
                ->lockForUpdate()
                ->each(function (Asset $asset) use ($ndcUnit, &$stats): void {
                    $asset->organizational_unit_id = $ndcUnit->id;
                    $asset->save();
                    $stats['assets_backfilled']++;
                });

            User::query()->orderBy('id')->lockForUpdate()->each(
                function (User $user) use ($ndcUnit, &$stats): void {
                    $scopes = $this->scopesFor($user);
                    $membership = DB::table('organizational_unit_user')
                        ->where('organizational_unit_id', $ndcUnit->id)
                        ->where('user_id', $user->id)
                        ->first();
                    $alreadyCorrect = $membership !== null
                        && $membership->read_scope === $scopes['read_scope']
                        && $membership->write_scope === $scopes['write_scope']
                        && $user->default_organizational_unit_id !== null;

                    if ($this->resume && $alreadyCorrect) {
                        $stats['users_skipped']++;

                        return;
                    }

                    DB::table('organizational_unit_user')->updateOrInsert(
                        [
                            'organizational_unit_id' => $ndcUnit->id,
                            'user_id' => $user->id,
                        ],
                        [
                            ...$scopes,
                            'updated_at' => now(),
                            'created_at' => $membership?->created_at ?? now(),
                        ]
                    );

                    if ($user->default_organizational_unit_id === null) {
                        $user->default_organizational_unit_id = $ndcUnit->id;
                        $user->save();
                    }

                    $stats['users_assigned']++;
                }
            );

            $ndcStore = Location::query()
                ->where('site_id', $ndcSite->id)
                ->where('code', 'NDC_MAIN_STORE')
                ->firstOrFail();
            $stockStats = app(LegacyLocationStockMigrator::class)->migrateTo($ndcStore);
            $stats['stock_balances_created'] = $stockStats['balances_created'];
            $stats['stock_entries_copied'] = $stockStats['entries_copied'];
            $stats['stock_discrepancies'] = count($stockStats['discrepancies']);
            $stats['stock_conflicts'] = count($stockStats['conflicts']);

            if ($this->dryRun) {
                $connection->rollBack();
            } else {
                $connection->commit();
            }

            return $stats;
        } catch (\Throwable $exception) {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * Inspect the target only. This method performs no writes.
     *
     * @return array{
     *     is_clean: bool,
     *     baseline_missing: list<string>,
     *     unmapped_locations: list<int>,
     *     unmapped_assets: list<int>,
     *     unmapped_users: list<int>,
     *     invalid_location_paths: list<int>,
     *     unmapped_consumable_stock: list<int>,
     *     unmigrated_legacy_entries: list<int>,
     *     stock_balance_discrepancies: list<int>
     * }
     */
    public function verify(): array
    {
        $ndcUnit = OrganizationalUnit::query()->where('code', 'NDC')->first();
        $ndcSite = Site::query()->where('code', 'NDC_HQ')->first();
        $missing = [];

        if ($ndcUnit === null) {
            $missing[] = 'organizational_units:NDC';
        }

        if ($ndcSite === null) {
            $missing[] = 'sites:NDC_HQ';
        }

        if ($ndcUnit !== null && $ndcSite !== null && ! $ndcUnit->sites()->whereKey($ndcSite->id)->exists()) {
            $missing[] = 'organizational_unit_site:NDC:NDC_HQ';
        }

        $ndcStore = $ndcSite
            ? Location::query()->where('site_id', $ndcSite->id)->where('code', 'NDC_MAIN_STORE')->first()
            : null;

        if ($ndcStore === null) {
            $missing[] = 'locations:NDC_MAIN_STORE';
        }

        $locations = Location::query()->withTrashed()->orderBy('id')->get();
        $expectedPaths = $this->expectedPaths($locations);
        $invalidPaths = $locations
            ->filter(function (Location $location) use ($expectedPaths): bool {
                $expected = $expectedPaths[$location->id] ?? null;

                return $expected === null || $expected !== $location->path;
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $users = User::query()->orderBy('id')->get();
        $unmappedUsers = $users->filter(function (User $user) use ($ndcUnit): bool {
            if ($ndcUnit === null || $user->default_organizational_unit_id === null) {
                return true;
            }

            $membership = DB::table('organizational_unit_user')
                ->where('organizational_unit_id', $ndcUnit->id)
                ->where('user_id', $user->id)
                ->first();
            $expected = $this->scopesFor($user);

            return $membership === null
                || $membership->read_scope !== $expected['read_scope']
                || $membership->write_scope !== $expected['write_scope'];
        })->pluck('id');

        $report = [
            'is_clean' => false,
            'baseline_missing' => $missing,
            'unmapped_locations' => Location::query()->withTrashed()->whereNull('site_id')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'unmapped_assets' => Asset::query()->whereNull('organizational_unit_id')->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'unmapped_users' => $unmappedUsers->map(fn ($id): int => (int) $id)->values()->all(),
            'invalid_location_paths' => $invalidPaths,
            'unmapped_consumable_stock' => $ndcStore
                ? Consumable::query()
                    ->whereDoesntHave('stockBalances', fn ($query) => $query->where('location_id', $ndcStore->id))
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all()
                : Consumable::query()->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'unmigrated_legacy_entries' => Entry::query()
                ->whereNotIn('id', StockTransaction::query()->whereNotNull('legacy_entry_id')->select('legacy_entry_id'))
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all(),
            'stock_balance_discrepancies' => Consumable::query()
                ->orderBy('id')
                ->get(['id', 'in_stock'])
                ->filter(fn (Consumable $consumable): bool => (int) $consumable->in_stock !== (int) StockBalance::where('consumable_id', $consumable->id)->sum('quantity'))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
        ];

        $report['is_clean'] = collect($report)
            ->except('is_clean')
            ->every(fn (array $items): bool => $items === []);

        return $report;
    }

    /**
     * @return array{0: OrganizationalUnit, 1: Site}
     */
    private function baseline(): array
    {
        $ndcUnit = OrganizationalUnit::query()->where('code', 'NDC')->first();
        $ndcSite = Site::query()->where('code', 'NDC_HQ')->first();

        if ($ndcUnit === null || $ndcSite === null) {
            throw new RuntimeException('NDC unit or NDC_HQ site not found. Run EpfoNdcHierarchySeeder first.');
        }

        return [$ndcUnit, $ndcSite];
    }

    /**
     * @return array{read_scope: string, write_scope: string}
     */
    private function scopesFor(User $user): array
    {
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from((string) $user->role);

        return [
            'read_scope' => 'descendants',
            'write_scope' => match ($role) {
                UserRole::ADMIN => 'descendants',
                UserRole::INVENTORY_MANAGER,
                UserRole::STOCK_OPERATOR,
                UserRole::FINANCE_OPERATOR => 'local',
                UserRole::AUDITOR,
                UserRole::VIEWER => 'none',
            },
        ];
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return array<int, string|null>
     */
    private function expectedPaths(Collection $locations): array
    {
        $byId = $locations->keyBy('id');
        $paths = [];

        foreach ($locations as $location) {
            $this->expectedPath($location, $byId, $paths, []);
        }

        return $paths;
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @param  array<int, string|null>  $paths
     * @param  array<int, true>  $visiting
     */
    private function expectedPath(Location $location, Collection $locations, array &$paths, array $visiting): ?string
    {
        if (array_key_exists($location->id, $paths)) {
            return $paths[$location->id];
        }

        if (isset($visiting[$location->id])) {
            return $paths[$location->id] = null;
        }

        if ($location->parent_id === null) {
            return $paths[$location->id] = "/{$location->id}/";
        }

        $visiting[$location->id] = true;
        /** @var Location|null $parent */
        $parent = $locations->get($location->parent_id);

        if ($parent === null || $parent->site_id !== $location->site_id) {
            return $paths[$location->id] = null;
        }

        $parentPath = $this->expectedPath($parent, $locations, $paths, $visiting);

        return $paths[$location->id] = $parentPath === null
            ? null
            : $parentPath.$location->id.'/';
    }
}
