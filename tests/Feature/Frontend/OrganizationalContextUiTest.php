<?php

namespace Tests\Feature\Frontend;

use App\Enums\LocationType;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Services\Organization\OrganizationalContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrganizationalContextUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_membership_user_can_switch_only_to_an_active_membership(): void
    {
        $manager = User::factory()->inventoryManager()->create();
        $ndc = $this->unit('NDC', 'National Data Centre');
        $regional = $this->unit('RO-DL', 'Delhi Regional Office');
        $unauthorized = $this->unit('RO-MH', 'Mumbai Regional Office');
        $manager->organizationalUnits()->attach($ndc->id, ['read_scope' => 'local', 'write_scope' => 'local']);
        $manager->organizationalUnits()->attach($regional->id, ['read_scope' => 'local', 'write_scope' => 'none']);
        $manager->default_organizational_unit_id = $ndc->id;
        $manager->save();

        $ndcSite = $this->site($ndc, 'NDC-SITE', 'NDC Campus');
        $regionalSite = $this->site($regional, 'DELHI-SITE', 'Delhi Campus');
        $this->location($ndcSite, 'NDC Building', LocationType::BUILDING);
        $this->location($regionalSite, 'Delhi Building', LocationType::BUILDING);

        $this->actingAs($manager)
            ->get(route('organization.hierarchy'))
            ->assertOk()
            ->assertSee('data-testid="organizational-context-switcher"', false)
            ->assertSee('National Data Centre')
            ->assertSee('Delhi Regional Office')
            ->assertDontSee('Mumbai Regional Office')
            ->assertSee('NDC Campus')
            ->assertDontSee('Delhi Campus');

        $this->actingAs($manager)
            ->post(route('organizational-context.update'), ['organizational_unit_id' => $regional->id])
            ->assertRedirect()
            ->assertSessionHas(OrganizationalContext::SESSION_KEY, $regional->id);

        $this->actingAs($manager)
            ->get(route('organization.hierarchy'))
            ->assertOk()
            ->assertSee('Delhi Campus')
            ->assertDontSee('NDC Campus');

        $this->actingAs($manager)
            ->postJson(route('organizational-context.update'), ['organizational_unit_id' => $unauthorized->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('organizational_unit_id');
        $this->assertSame($regional->id, session(OrganizationalContext::SESSION_KEY));
    }

    public function test_hierarchy_tree_breadcrumbs_search_and_no_map_fallback_are_available(): void
    {
        $manager = User::factory()->inventoryManager()->create();
        $unit = $this->unit('NDC-OPS', 'NDC Operations');
        $manager->organizationalUnits()->attach($unit->id, ['read_scope' => 'local', 'write_scope' => 'local']);
        $manager->default_organizational_unit_id = $unit->id;
        $manager->save();
        $site = $this->site($unit, 'NDC-PRIMARY', 'Primary NDC Site');
        $building = $this->location($site, 'Main Building', LocationType::BUILDING);
        $floor = $this->location($site, 'First Floor', LocationType::FLOOR, $building);
        $room = $this->location($site, 'Server Room', LocationType::ROOM, $floor);
        $rack = $this->location($site, 'Rack A01', LocationType::RACK, $room);

        $this->actingAs($manager)
            ->get(route('organization.hierarchy'))
            ->assertOk()
            ->assertSeeTextInOrder(['Primary NDC Site', 'Main Building', 'First Floor', 'Server Room', 'Rack A01'])
            ->assertSee('role="tree"', false)
            ->assertSee('Structured location list');

        $this->actingAs($manager)
            ->get(route('organization.hierarchy', ['search' => 'Rack A01']))
            ->assertOk()
            ->assertSee('Rack A01')
            ->assertDontSee('Main Building');

        $this->actingAs($manager)
            ->get(route('locations.show', $rack))
            ->assertOk()
            ->assertSeeTextInOrder(['Hierarchy', 'Primary NDC Site', 'Main Building', 'First Floor', 'Server Room', 'Rack A01'])
            ->assertSee('No current map is available')
            ->assertSee('structured tree or searchable list');
    }

    public function test_selectors_and_controls_are_filtered_while_server_policies_remain_authoritative(): void
    {
        $manager = User::factory()->inventoryManager()->create();
        $writableUnit = $this->unit('NDC-W', 'Writable NDC Unit');
        $readOnlyUnit = $this->unit('NDC-R', 'Read Only NDC Unit');
        $manager->organizationalUnits()->attach($writableUnit->id, ['read_scope' => 'local', 'write_scope' => 'local']);
        $manager->organizationalUnits()->attach($readOnlyUnit->id, ['read_scope' => 'local', 'write_scope' => 'none']);
        $manager->default_organizational_unit_id = $writableUnit->id;
        $manager->save();
        $writableSite = $this->site($writableUnit, 'WRITE-SITE', 'Writable Site');
        $readOnlySite = $this->site($readOnlyUnit, 'READ-SITE', 'Read Only Site');
        $writableLocation = $this->location($writableSite, 'Writable Building', LocationType::BUILDING);
        $readOnlyLocation = $this->location($readOnlySite, 'Read Only Building', LocationType::BUILDING);

        $this->actingAs($manager)
            ->get(route('locations.create'))
            ->assertOk()
            ->assertViewHas('sites', fn ($sites): bool => $sites->modelKeys() === [$writableSite->id])
            ->assertViewHas('parentLocations', fn ($locations): bool => $locations->modelKeys() === [$writableLocation->id]);

        $viewer = User::factory()->viewer()->create();
        $viewer->organizationalUnits()->attach($writableUnit->id, ['read_scope' => 'local', 'write_scope' => 'none']);

        $this->actingAs($viewer)
            ->get(route('locations.index'))
            ->assertOk()
            ->assertDontSee('Add location')
            ->assertDontSee(route('locations.edit', $writableLocation), false);

        $this->actingAs($viewer)
            ->get(route('locations.show', $writableLocation))
            ->assertOk()
            ->assertDontSee('Edit location')
            ->assertDontSee('Upload privately');

        $this->actingAs($viewer)
            ->put(route('locations.update', $writableLocation), ['name' => 'Unauthorized rename'])
            ->assertForbidden();
        $this->actingAs($viewer)
            ->post(route('locations.spatial-maps.store', $writableLocation), [
                'map_type' => 'image',
                'file' => UploadedFile::fake()->image('map.png'),
            ])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('locations.store'), [
                'name' => 'Injected room',
                'site_id' => $readOnlySite->id,
                'parent_id' => $readOnlyLocation->id,
                'location_type' => LocationType::ROOM->value,
            ])
            ->assertForbidden();
    }

    private function unit(string $code, string $name): OrganizationalUnit
    {
        $unit = OrganizationalUnit::factory()->create([
            'code' => $code,
            'name' => $name,
            'path' => null,
            'is_active' => true,
        ]);
        $unit->update(['path' => '/'.$unit->id.'/']);

        return $unit;
    }

    private function site(OrganizationalUnit $unit, string $code, string $name): Site
    {
        $site = Site::create(['code' => $code, 'name' => $name, 'is_active' => true]);
        $site->organizationalUnits()->attach($unit->id);

        return $site;
    }

    private function location(
        Site $site,
        string $name,
        LocationType $type,
        ?Location $parent = null
    ): Location {
        $location = Location::factory()->create([
            'site_id' => $site->id,
            'parent_id' => $parent?->id,
            'name' => $name,
            'location_type' => $type,
            'path' => null,
            'is_active' => true,
        ]);
        $location->update([
            'path' => $parent
                ? rtrim($parent->path, '/').'/'.$location->id.'/'
                : '/'.$location->id.'/',
        ]);

        return $location;
    }
}
