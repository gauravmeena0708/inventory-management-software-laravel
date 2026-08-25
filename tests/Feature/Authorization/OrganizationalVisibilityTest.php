<?php

namespace Tests\Feature\Authorization;

use App\Enums\OrganizationalUnitType;
use App\Models\Asset;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use App\Services\Organization\OrganizationalHierarchyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_scope_excludes_parent_children_siblings_unrelated_and_missing_ownership(): void
    {
        $units = $this->hierarchy();
        $user = User::factory()->viewer()->create();
        $user->organizationalUnits()->attach($units['division']->id, ['read_scope' => 'local']);
        $assets = $this->assetsFor($units);

        $visibleIds = Asset::query()->visibleTo($user)->pluck('id');

        $this->assertEqualsCanonicalizing([$assets['division']->id], $visibleIds->all());
        $this->assertFalse($user->can('view', $assets['head']));
        $this->assertTrue($user->can('view', $assets['division']));
        $this->assertFalse($user->can('view', $assets['child']));
        $this->assertFalse($user->can('view', $assets['sibling']));
        $this->assertFalse($user->can('view', $assets['unrelated']));
        $this->assertFalse($user->can('view', $assets['unowned']));
    }

    public function test_descendant_scope_reads_self_and_children_but_not_parent_or_sibling(): void
    {
        $units = $this->hierarchy();
        $user = User::factory()->auditor()->create();
        $user->organizationalUnits()->attach($units['division']->id, ['read_scope' => 'descendants']);
        $assets = $this->assetsFor($units);

        $visibleIds = Asset::query()->visibleTo($user)->pluck('id');

        $this->assertEqualsCanonicalizing([
            $assets['division']->id,
            $assets['child']->id,
        ], $visibleIds->all());
    }

    public function test_overlapping_memberships_are_deduplicated(): void
    {
        $units = $this->hierarchy();
        $user = User::factory()->viewer()->create();
        $user->organizationalUnits()->attach($units['head']->id, ['read_scope' => 'descendants']);
        $user->organizationalUnits()->attach($units['division']->id, ['read_scope' => 'local']);
        $assets = $this->assetsFor($units);

        $visibleIds = Asset::query()->visibleTo($user)->pluck('id');

        $this->assertCount(4, $visibleIds);
        $this->assertSame(4, $visibleIds->unique()->count());
        $this->assertEqualsCanonicalizing([
            $assets['head']->id,
            $assets['division']->id,
            $assets['child']->id,
            $assets['sibling']->id,
        ], $visibleIds->all());
    }

    public function test_expired_inactive_and_deleted_memberships_or_owners_fail_closed(): void
    {
        $units = $this->hierarchy();
        $expiredUser = User::factory()->viewer()->create();
        $expiredUser->organizationalUnits()->attach($units['division']->id, [
            'read_scope' => 'descendants',
            'valid_until' => now()->subMinute(),
        ]);
        $inactiveUser = User::factory()->viewer()->create();
        $inactiveUser->organizationalUnits()->attach($units['sibling']->id, ['read_scope' => 'local']);
        $inactiveAsset = Asset::factory()->create(['organizational_unit_id' => $units['sibling']->id]);
        $units['sibling']->update(['is_active' => false]);
        $deletedUser = User::factory()->viewer()->create();
        $deletedUser->organizationalUnits()->attach($units['unrelated']->id, ['read_scope' => 'local']);
        $deletedAsset = Asset::factory()->create(['organizational_unit_id' => $units['unrelated']->id]);
        $units['unrelated']->delete();

        $this->assertDatabaseCount('assets', 2);
        $this->assertTrue(Asset::query()->visibleTo($expiredUser)->doesntExist());
        $this->assertFalse($inactiveUser->can('view', $inactiveAsset));
        $this->assertFalse($deletedUser->can('view', $deletedAsset));
    }

    public function test_administrator_sees_all_actively_owned_assets_but_not_orphans_or_inactive_owners(): void
    {
        $units = $this->hierarchy();
        $admin = User::factory()->admin()->create();
        $assets = $this->assetsFor($units);
        $units['sibling']->update(['is_active' => false]);

        $visibleIds = Asset::query()->visibleTo($admin)->pluck('id');

        $this->assertContains($assets['head']->id, $visibleIds);
        $this->assertContains($assets['division']->id, $visibleIds);
        $this->assertContains($assets['child']->id, $visibleIds);
        $this->assertContains($assets['unrelated']->id, $visibleIds);
        $this->assertNotContains($assets['sibling']->id, $visibleIds);
        $this->assertNotContains($assets['unowned']->id, $visibleIds);
    }

    public function test_visibility_uses_the_explicit_user_not_ambient_authentication(): void
    {
        $units = $this->hierarchy();
        $ambientUser = User::factory()->admin()->create();
        $explicitUser = User::factory()->viewer()->create();
        $explicitUser->organizationalUnits()->attach($units['division']->id, ['read_scope' => 'local']);
        $assets = $this->assetsFor($units);
        $this->actingAs($ambientUser);

        $service = new OrganizationalVisibility;

        $this->assertEqualsCanonicalizing(
            [$assets['division']->id],
            $service->apply(Asset::query(), $explicitUser)->pluck('id')->all()
        );
    }

    /**
     * @return array<string, OrganizationalUnit>
     */
    private function hierarchy(): array
    {
        $service = new OrganizationalHierarchyService;
        $root = $service->createUnit([
            'code' => 'EPFO',
            'name' => 'EPFO',
            'unit_type' => OrganizationalUnitType::ROOT,
        ]);
        $head = $service->createUnit([
            'code' => 'HO',
            'name' => 'Head Office',
            'unit_type' => OrganizationalUnitType::HEAD_OFFICE,
            'parent_id' => $root->id,
        ]);
        $division = $service->createUnit([
            'code' => 'IS',
            'name' => 'IS Division',
            'unit_type' => OrganizationalUnitType::DIVISION,
            'parent_id' => $head->id,
        ]);
        $child = $service->createUnit([
            'code' => 'IS-OPS',
            'name' => 'IS Operations Section',
            'unit_type' => OrganizationalUnitType::SECTION,
            'parent_id' => $division->id,
        ]);
        $sibling = $service->createUnit([
            'code' => 'HR',
            'name' => 'HR Division',
            'unit_type' => OrganizationalUnitType::DIVISION,
            'parent_id' => $head->id,
        ]);
        $unrelated = $service->createUnit([
            'code' => 'NORTH',
            'name' => 'North Zone',
            'unit_type' => OrganizationalUnitType::ZONAL_OFFICE,
            'parent_id' => $root->id,
        ]);

        return compact('root', 'head', 'division', 'child', 'sibling', 'unrelated');
    }

    /**
     * @param  array<string, OrganizationalUnit>  $units
     * @return array<string, Asset>
     */
    private function assetsFor(array $units): array
    {
        return [
            'head' => Asset::factory()->create(['organizational_unit_id' => $units['head']->id]),
            'division' => Asset::factory()->create(['organizational_unit_id' => $units['division']->id]),
            'child' => Asset::factory()->create(['organizational_unit_id' => $units['child']->id]),
            'sibling' => Asset::factory()->create(['organizational_unit_id' => $units['sibling']->id]),
            'unrelated' => Asset::factory()->create(['organizational_unit_id' => $units['unrelated']->id]),
            'unowned' => Asset::factory()->create(['organizational_unit_id' => null]),
        ];
    }
}
