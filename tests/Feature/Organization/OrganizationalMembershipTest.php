<?php

namespace Tests\Feature\Organization;

use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
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

        $contextService = new OrganizationalContext();
        $result = $contextService->setDefaultUnit($user, $unit->id);

        $this->assertTrue($result);
        $this->assertEquals($unit->id, $user->fresh()->default_organizational_unit_id);
    }

    public function test_setting_unauthorized_default_context_throws_exception()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        // User is not attached to unit

        $contextService = new OrganizationalContext();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("User is not an active member of this organizational unit.");

        $contextService->setDefaultUnit($user, $unit->id);
    }

    public function test_setting_expired_default_context_throws_exception()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($unit->id, ['valid_until' => now()->subDay()]);

        $contextService = new OrganizationalContext();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("User is not an active member of this organizational unit.");

        $contextService->setDefaultUnit($user, $unit->id);
    }

    public function test_set_active_context_in_session()
    {
        $user = User::factory()->create();
        $unit = OrganizationalUnit::factory()->create();
        $user->organizationalUnits()->attach($unit->id);

        $contextService = new OrganizationalContext();
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

        $contextService = new OrganizationalContext();
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

        $contextService = new OrganizationalContext();
        $activeContext = $contextService->getActiveContext($user);

        $this->assertEquals($unitDefault->id, $activeContext->id);
    }

    public function test_get_active_context_returns_null_if_no_session_or_default()
    {
        $user = User::factory()->create();

        $contextService = new OrganizationalContext();
        $activeContext = $contextService->getActiveContext($user);

        $this->assertNull($activeContext);
    }
}
