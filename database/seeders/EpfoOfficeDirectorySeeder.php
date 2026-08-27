<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Organization\OrganizationalHierarchyService;
use App\Services\Spatial\PhysicalHierarchyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class EpfoOfficeDirectorySeeder extends Seeder
{
    /**
     * Seed the normalized non-Guest House EPFO office directory snapshot.
     */
    public function run(
        OrganizationalHierarchyService $hierarchy,
        PhysicalHierarchyService $physicalHierarchy
    ): void {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('The EPFO office directory seeder is restricted to local and testing environments.');
        }

        $this->call(EpfoNdcHierarchySeeder::class);

        $payload = $this->payload();
        $root = OrganizationalUnit::query()->where('code', 'EPFO')->active()->firstOrFail();
        $password = Hash::make('password');
        $unitsBySourceKey = [];
        $directoryUnitIds = [];

        foreach ($payload['offices'] as $office) {
            $parent = $office['parent_source_key']
                ? ($unitsBySourceKey[$office['parent_source_key']] ?? null)
                : $root;

            if (! $parent) {
                throw new RuntimeException("Missing parent {$office['parent_source_key']} for {$office['name']}.");
            }

            $type = OrganizationalUnitType::from($office['unit_type']);
            $unit = OrganizationalUnit::withTrashed()->where('code', $office['code'])->first();

            if (! $unit) {
                $unit = $hierarchy->createUnit([
                    'code' => $office['code'],
                    'name' => $office['name'],
                    'unit_type' => $type->value,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'metadata' => $this->metadata($office),
                ]);
            } else {
                if ($unit->trashed()) {
                    $unit->restore();
                }

                if ($unit->unit_type !== $type) {
                    throw new RuntimeException("Office code {$office['code']} conflicts with an existing organizational type.");
                }

                if (! $unit->is_active) {
                    $unit->forceFill(['is_active' => true])->save();
                }

                if ($unit->parent_id !== $parent->id) {
                    $hierarchy->moveUnit($unit, $parent);
                }

                $unit->forceFill([
                    'name' => $office['name'],
                    'metadata' => $this->metadata($office),
                    'is_active' => true,
                ])->save();
            }

            $unitsBySourceKey[$office['source_key']] = $unit;
            $directoryUnitIds[] = $unit->id;

            $this->seedSiteAndLocation($unit, $office, $physicalHierarchy);
            $this->seedManager($unit, $office, $password);
        }

        $this->deactivateSupersededDemoUnits($directoryUnitIds, $root->id);
        $this->grantAdministratorDirectoryAccess($root);
    }

    /**
     * @return array{record_count: int, offices: list<array<string, mixed>>}
     */
    private function payload(): array
    {
        $path = __DIR__.'/data/epfo-offices.json';
        $payload = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        if (($payload['record_count'] ?? null) !== 304 || count($payload['offices'] ?? []) !== 304) {
            throw new RuntimeException('The EPFO office directory snapshot must contain exactly 304 offices.');
        }

        return $payload;
    }

    /** @param array<string, mixed> $office */
    private function metadata(array $office): array
    {
        return [
            'directory_source' => 'pf-contacts',
            'directory_source_key' => $office['source_key'],
            'directory_category' => $office['category'],
            'contact_email' => $office['manager_email'],
            'source_office_email' => $office['source_office_email'],
            'address' => $office['address'],
            'demo' => false,
        ];
    }

    /** @param array<string, mixed> $office */
    private function seedSiteAndLocation(
        OrganizationalUnit $unit,
        array $office,
        PhysicalHierarchyService $physicalHierarchy
    ): void {
        $site = Site::withTrashed()->where('code', $office['site_code'])->first();

        if (! $site) {
            $site = $physicalHierarchy->createSite([
                'code' => $office['site_code'],
                'name' => $office['name'],
                'address' => $office['address'] ?: null,
                'timezone' => 'Asia/Kolkata',
                'is_active' => true,
            ]);
        } else {
            if ($site->trashed()) {
                $site->restore();
            }

            $site->forceFill([
                'name' => $office['name'],
                'address' => $office['address'] ?: null,
                'timezone' => 'Asia/Kolkata',
                'is_active' => true,
            ])->save();
        }

        $unit->sites()->syncWithoutDetaching([$site->id]);

        $location = Location::withTrashed()
            ->where('site_id', $site->id)
            ->where('code', $office['location_code'])
            ->first();

        if (! $location) {
            $physicalHierarchy->createLocation([
                'site_id' => $site->id,
                'code' => $office['location_code'],
                'name' => $office['name'],
                'location_type' => LocationType::OTHER->value,
                'floor' => 'Office',
                'description' => $office['address'] ?: 'EPFO office directory location.',
                'is_active' => true,
            ]);

            return;
        }

        if ($location->trashed()) {
            $location->restore();
        }

        $location->forceFill([
            'name' => $office['name'],
            'floor' => 'Office',
            'description' => $office['address'] ?: 'EPFO office directory location.',
            'is_active' => true,
        ])->save();
    }

    /** @param array<string, mixed> $office */
    private function seedManager(OrganizationalUnit $unit, array $office, string $password): void
    {
        $manager = User::query()->updateOrCreate(
            ['email' => $office['manager_email']],
            [
                'name' => $office['name'].' Inventory Manager',
                'password' => $password,
                'role' => UserRole::INVENTORY_MANAGER,
                'email_verified_at' => now(),
            ]
        );

        $manager->organizationalUnits()->sync([
            $unit->id => [
                'read_scope' => 'local',
                'write_scope' => 'local',
                'valid_from' => now(),
                'valid_until' => null,
            ],
        ]);

        $manager->forceFill(['default_organizational_unit_id' => $unit->id])->save();
    }

    /** @param list<int> $directoryUnitIds */
    private function deactivateSupersededDemoUnits(array $directoryUnitIds, int $rootId): void
    {
        OrganizationalUnit::query()
            ->active()
            ->whereKeyNot($rootId)
            ->whereNotIn('id', $directoryUnitIds)
            ->get()
            ->filter(fn (OrganizationalUnit $unit): bool => data_get($unit->metadata, 'demo') === true)
            ->each(fn (OrganizationalUnit $unit) => $unit->forceFill(['is_active' => false])->save());
    }

    private function grantAdministratorDirectoryAccess(OrganizationalUnit $root): void
    {
        User::query()->where('role', UserRole::ADMIN->value)->each(function (User $admin) use ($root): void {
            $admin->organizationalUnits()->syncWithoutDetaching([
                $root->id => [
                    'read_scope' => 'descendants',
                    'write_scope' => 'descendants',
                    'valid_from' => now(),
                    'valid_until' => null,
                ],
            ]);

            $admin->forceFill(['default_organizational_unit_id' => $root->id])->save();
        });
    }
}
