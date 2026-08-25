<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyOrganizationalScoping extends Command
{
    protected $signature = 'epfo:verify-organizational-scoping {--json : Emit machine-readable output}';

    protected $description = 'Fail unless active inventory records are safely mapped to the organizational hierarchy.';

    public function handle(): int
    {
        $checks = [
            'active_assets_without_owner' => $this->countActiveAssetsWithoutOwner(),
            'active_locations_without_site' => $this->countActiveLocationsWithoutSite(),
            'active_units_without_path' => $this->countActiveUnitsWithoutPath(),
            'users_without_active_membership' => $this->countUsersWithoutActiveMembership(),
            'users_with_invalid_default_unit' => $this->countUsersWithInvalidDefaultUnit(),
            'assets_with_multiple_open_placements' => $this->countDuplicateOpenPlacements(),
            'locations_with_multiple_current_maps' => $this->countDuplicateCurrentMaps(),
        ];

        $total = array_sum($checks);
        $result = [
            'ok' => $total === 0,
            'issues' => $checks,
            'total_issues' => $total,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(
                ['Check', 'Issues'],
                collect($checks)->map(fn (int $count, string $check): array => [$check, $count])->values()->all()
            );

            $total === 0
                ? $this->info('Organizational scoping verification passed.')
                : $this->error("Organizational scoping verification failed with {$total} issue(s).");
        }

        return $total === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function countActiveAssetsWithoutOwner(): int
    {
        if (! Schema::hasTable('assets') || ! Schema::hasColumn('assets', 'organizational_unit_id')) {
            return 0;
        }

        $query = DB::table('assets')->whereNull('organizational_unit_id');

        if (Schema::hasColumn('assets', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->count();
    }

    private function countActiveLocationsWithoutSite(): int
    {
        if (! Schema::hasTable('locations') || ! Schema::hasColumn('locations', 'site_id')) {
            return 0;
        }

        $query = DB::table('locations')->whereNull('site_id');

        if (Schema::hasColumn('locations', 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->count();
    }

    private function countActiveUnitsWithoutPath(): int
    {
        if (! Schema::hasTable('organizational_units')) {
            return 0;
        }

        return DB::table('organizational_units')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('path')->orWhere('path', ''))
            ->count();
    }

    private function countUsersWithoutActiveMembership(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('organizational_unit_user')) {
            return 0;
        }

        $now = now();

        return DB::table('users')
            ->whereNotExists(function ($query) use ($now): void {
                $query->selectRaw('1')
                    ->from('organizational_unit_user as membership')
                    ->join('organizational_units as unit', 'unit.id', '=', 'membership.organizational_unit_id')
                    ->whereColumn('membership.user_id', 'users.id')
                    ->where('unit.is_active', true)
                    ->whereNull('unit.deleted_at')
                    ->where(fn ($dates) => $dates->whereNull('membership.valid_from')->orWhere('membership.valid_from', '<=', $now))
                    ->where(fn ($dates) => $dates->whereNull('membership.valid_until')->orWhere('membership.valid_until', '>=', $now));
            })
            ->count();
    }

    private function countUsersWithInvalidDefaultUnit(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'default_organizational_unit_id')) {
            return 0;
        }

        $now = now();

        return DB::table('users')
            ->whereNotNull('default_organizational_unit_id')
            ->whereNotExists(function ($query) use ($now): void {
                $query->selectRaw('1')
                    ->from('organizational_unit_user as membership')
                    ->join('organizational_units as unit', 'unit.id', '=', 'membership.organizational_unit_id')
                    ->whereColumn('membership.user_id', 'users.id')
                    ->whereColumn('membership.organizational_unit_id', 'users.default_organizational_unit_id')
                    ->where('unit.is_active', true)
                    ->whereNull('unit.deleted_at')
                    ->where(fn ($dates) => $dates->whereNull('membership.valid_from')->orWhere('membership.valid_from', '<=', $now))
                    ->where(fn ($dates) => $dates->whereNull('membership.valid_until')->orWhere('membership.valid_until', '>=', $now));
            })
            ->count();
    }

    private function countDuplicateOpenPlacements(): int
    {
        if (! Schema::hasTable('asset_placements')) {
            return 0;
        }

        $openColumn = Schema::hasColumn('asset_placements', 'open_marker') ? 'open_marker' : null;
        $query = DB::table('asset_placements')->select('asset_id');

        $openColumn
            ? $query->where($openColumn, true)
            : $query->whereNull('removed_at');

        return DB::query()->fromSub(
            $query->groupBy('asset_id')->havingRaw('COUNT(*) > 1'),
            'duplicate_open_placements'
        )->count();
    }

    private function countDuplicateCurrentMaps(): int
    {
        if (! Schema::hasTable('spatial_maps')) {
            return 0;
        }

        $duplicates = DB::table('spatial_maps')
            ->select(['location_id', 'map_type'])
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->groupBy(['location_id', 'map_type'])
            ->havingRaw('COUNT(*) > 1');

        return DB::query()->fromSub($duplicates, 'duplicate_current_maps')->count();
    }
}
