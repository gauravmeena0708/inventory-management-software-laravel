<?php

namespace Tests\Feature\Organization;

use App\Enums\OrganizationalUnitType;
use App\Exceptions\InvalidOrganizationalContext;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use App\Services\Organization\OrganizationalHierarchyService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class OrganizationalMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_user_can_have_multiple_memberships()
    {
        $user = User::factory()->create();
        $unit1 = OrganizationalUnit::factory()->create();
        $unit2 = OrganizationalUnit::factory()->create();

        $user->organizationalUnits()->attach($unit1->id, ['read_scope' => 'local', 'write_scope' => 'local']);
        $user->organizationalUnits()->attach($unit2->id, ['read_scope' => 'descendants', 'write_scope' => 'none']);

        $this->assertCount(2, $user->organizationalUnits);
        $this->assertCount(2, $user->activeOrganizationalUnits);
    }

    public function test_expired_memberships_are_not_active()
    {
        $user = User::factory()->create();
        $unit1 = OrganizationalUnit::factory()->create(); // Active
        $unit2 = OrganizationalUnit::factory()->create(); // Expired
        $unit3 = OrganizationalUnit::factory()->create(); // Future

        $user->organizationalUnits()->attach($unit1->id);
        $user->organizationalUnits()->attach($unit2->id, ['valid_until' => now()->subDay()]);
        $user->organizationalUnits()->attach($unit3->id, ['valid_from' => now()->addDay()]);

        $this->assertCount(3, $user->organizationalUnits);
        $this->assertCount(1, $user->activeOrganizationalUnits);
        $this->assertEquals($unit1->id, $user->activeOrganizationalUnits->first()->id);
    }

    public function test_setting_authorized_default_context()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($unit->id);

        $contextService = new OrganizationalContext;
        $result = $contextService->setDefaultUnit($user, $unit->id);

        $this->assertTrue($result);
        $this->assertEquals($unit->id, $user->fresh()->default_organizational_unit_id);
    }

    public function test_setting_unauthorized_default_context_throws_exception()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        // User is not attached to unit

        $contextService = new OrganizationalContext;

        $this->expectException(InvalidOrganizationalContext::class);
        $this->expectExceptionMessage('User is not an active member of this organizational unit.');

        $contextService->setDefaultUnit($user, $unit->id);
    }

    public function test_setting_expired_default_context_throws_exception()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($unit->id, ['valid_until' => now()->subDay()]);

        $contextService = new OrganizationalContext;

        $this->expectException(InvalidOrganizationalContext::class);
        $this->expectExceptionMessage('User is not an active member of this organizational unit.');

        $contextService->setDefaultUnit($user, $unit->id);
    }

    public function test_set_active_context_in_session()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($unit->id);

        $contextService = new OrganizationalContext;
        $contextService->setActiveContext($user, $unit->id);

        $this->assertEquals($unit->id, Session::get(OrganizationalContext::SESSION_KEY));
    }

    public function test_get_active_context_returns_session_first()
    {
        $user = User::factory()->create();
        $unitSession = OrganizationalUnit::factory()->create();
        $unitDefault = OrganizationalUnit::factory()->create();

        $user->organizationalUnits()->attach([$unitSession->id, $unitDefault->id]);
        $user->default_organizational_unit_id = $unitDefault->id;
        $user->save();

        Session::put(OrganizationalContext::SESSION_KEY, $unitSession->id);

        $contextService = new OrganizationalContext;
        $activeContext = $contextService->getActiveContext($user);

        $this->assertEquals($unitSession->id, $activeContext->id);
    }

    public function test_get_active_context_falls_back_to_default_if_no_session()
    {
        $user = User::factory()->create();
        $unitDefault = OrganizationalUnit::factory()->create();

        $user->organizationalUnits()->attach($unitDefault->id);
        $user->default_organizational_unit_id = $unitDefault->id;
        $user->save();

        $contextService = new OrganizationalContext;
        $activeContext = $contextService->getActiveContext($user);

        $this->assertEquals($unitDefault->id, $activeContext->id);
    }

    public function test_get_active_context_returns_null_if_no_session_or_default()
    {
        $user = User::factory()->create();

        $contextService = new OrganizationalContext;
        $activeContext = $contextService->getActiveContext($user);

        $this->assertNull($activeContext);
    }

    public function test_inactive_unit_membership_is_not_active(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create(['is_active' => false]);
        $user->organizationalUnits()->attach($unit->id);

        $this->assertCount(0, $user->activeOrganizationalUnits);
    }

    public function test_setting_unauthorized_active_context_throws_specific_exception(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(InvalidOrganizationalContext::class);

        (new OrganizationalContext)->setActiveContext($user, $unit->id);
    }

    public function test_stale_session_context_is_cleared_and_default_is_used(): void
    {
        $user = User::factory()->create();
        $defaultUnit = OrganizationalUnit::factory()->create();
        $unauthorizedUnit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($defaultUnit->id);
        $user->default_organizational_unit_id = $defaultUnit->id;
        $user->save();
        Session::put(OrganizationalContext::SESSION_KEY, $unauthorizedUnit->id);

        $context = (new OrganizationalContext)->getActiveContext($user->fresh());

        $this->assertSame($defaultUnit->id, $context?->id);
        $this->assertFalse(Session::has(OrganizationalContext::SESSION_KEY));
    }

    public function test_duplicate_membership_is_rejected(): void
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($unit->id);

        $this->expectException(QueryException::class);

        $user->organizationalUnits()->attach($unit->id);
    }

    public function test_overlapping_ancestor_and_descendant_memberships_remain_explicit(): void
    {
        $hierarchy = new OrganizationalHierarchyService;
        $root = $hierarchy->createUnit([
            'name' => 'EPFO',
            'code' => 'EPFO',
            'unit_type' => OrganizationalUnitType::ROOT,
        ]);
        $headOffice = $hierarchy->createUnit([
            'name' => 'Head Office',
            'code' => 'HO',
            'unit_type' => OrganizationalUnitType::HEAD_OFFICE,
            'parent_id' => $root->id,
        ]);
        $division = $hierarchy->createUnit([
            'name' => 'IS Division',
            'code' => 'HO-IS',
            'unit_type' => OrganizationalUnitType::DIVISION,
            'parent_id' => $headOffice->id,
        ]);
        $user = User::factory()->inventoryManager()->create();
        $user->organizationalUnits()->attach($headOffice->id, [
            'read_scope' => 'descendants',
            'write_scope' => 'none',
        ]);
        $user->organizationalUnits()->attach($division->id, [
            'read_scope' => 'local',
            'write_scope' => 'local',
        ]);

        $memberships = $user->activeOrganizationalUnits()->get();

        $this->assertCount(2, $memberships);
        $this->assertTrue($memberships->contains($headOffice));
        $this->assertTrue($memberships->contains($division));
    }

    public function test_administrator_still_requires_membership_to_select_a_context(): void
    {
        $administrator = User::factory()->admin()->create();
        $unit = OrganizationalUnit::factory()->create();

        $this->expectException(InvalidOrganizationalContext::class);

        (new OrganizationalContext)->setActiveContext($administrator, $unit->id);
    }

    public function test_head_office_and_is_division_managers_have_separate_contexts(): void
    {
        $hierarchy = new OrganizationalHierarchyService;
        $root = $hierarchy->createUnit([
            'name' => 'EPFO',
            'code' => 'EPFO',
            'unit_type' => OrganizationalUnitType::ROOT,
        ]);
        $headOffice = $hierarchy->createUnit([
            'name' => 'Head Office',
            'code' => 'HO',
            'unit_type' => OrganizationalUnitType::HEAD_OFFICE,
            'parent_id' => $root->id,
        ]);
        $mainEstablishment = $hierarchy->createUnit([
            'name' => 'Head Office Main Establishment',
            'code' => 'HO-MAIN',
            'unit_type' => OrganizationalUnitType::DIVISION,
            'parent_id' => $headOffice->id,
        ]);
        $isDivision = $hierarchy->createUnit([
            'name' => 'IS Division',
            'code' => 'HO-IS',
            'unit_type' => OrganizationalUnitType::DIVISION,
            'parent_id' => $headOffice->id,
        ]);

        $headOfficeManager = User::factory()->inventoryManager()->create();
        $isManager = User::factory()->inventoryManager()->create();
        $headOfficeManager->organizationalUnits()->attach($mainEstablishment->id, [
            'read_scope' => 'descendants',
            'write_scope' => 'local',
        ]);
        $isManager->organizationalUnits()->attach($isDivision->id, [
            'read_scope' => 'descendants',
            'write_scope' => 'local',
        ]);

        $context = new OrganizationalContext;
        $context->setActiveContext($headOfficeManager, $mainEstablishment->id);
        $this->assertSame($mainEstablishment->id, Session::get(OrganizationalContext::SESSION_KEY));
        $context->clearActiveContext();
        $context->setActiveContext($isManager, $isDivision->id);
        $this->assertSame($isDivision->id, Session::get(OrganizationalContext::SESSION_KEY));

        $this->expectException(InvalidOrganizationalContext::class);
        $context->setActiveContext($headOfficeManager, $isDivision->id);
    }
}
