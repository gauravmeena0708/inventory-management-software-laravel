<?php

namespace Tests\Feature\Spatial;

use App\Enums\LocationType;
use App\Enums\UserRole;
use App\Models\Asset;
use App\Models\AssetPlacement;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\SpatialMap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpatialMapSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_spatial_map_schema_reserves_version_calibration_and_three_dimensional_foundation(): void
    {
        $this->assertTrue(\Schema::hasColumns('spatial_maps', [
            'location_id',
            'attachment_id',
            'map_type',
            'version',
            'width',
            'height',
            'scale',
            'origin_x',
            'origin_y',
            'origin_z',
            'rotation',
            'coordinate_system',
            'calibration',
            'is_current',
            'uploaded_by',
            'deleted_at',
        ]));

        [$manager, , $location] = $this->officeContext(UserRole::INVENTORY_MANAGER);
        $file = UploadedFile::fake()->createWithContent(
            'reserved.gltf',
            json_encode(['asset' => ['version' => '2.0']], JSON_THROW_ON_ERROR)
        );

        $this->actingAs($manager)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => $file,
            'map_type' => '3d_model',
            'coordinate_system' => 'LOCAL_METRES',
            'origin_z' => 4.25,
        ])->assertCreated()
            ->assertJsonPath('map.map_type', '3d_model')
            ->assertJsonPath('map.origin_z', '4.250000');

        $this->assertDatabaseHas('spatial_maps', [
            'location_id' => $location->id,
            'map_type' => '3d_model',
            'version' => 1,
        ]);
    }

    public function test_manager_uploads_a_calibrated_private_map_with_server_verified_checksum(): void
    {
        [$manager, , $location] = $this->officeContext(UserRole::INVENTORY_MANAGER);
        $file = UploadedFile::fake()->image('ndc-floor.png', 400, 200);
        $checksum = hash('sha256', $file->getContent());

        $response = $this->actingAs($manager)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => $file,
            'map_type' => 'image',
            'checksum' => $checksum,
            'scale' => 0.05,
            'origin_x' => 10,
            'origin_y' => 20,
            'origin_z' => 2,
            'rotation' => 1.5,
            'coordinate_system' => 'NDC_LOCAL_METRES',
            'calibration' => [
                'control_points' => [
                    ['pixel_x' => 0, 'pixel_y' => 0, 'local_x' => 10, 'local_y' => 20],
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('map.width', 400)
            ->assertJsonPath('map.height', 200)
            ->assertJsonPath('map.coordinate_system', 'NDC_LOCAL_METRES')
            ->assertJsonMissingPath('map.attachment_id')
            ->assertJsonMissingPath('map.path')
            ->assertJsonMissingPath('map.disk')
            ->assertJsonMissingPath('map.checksum');

        $map = SpatialMap::firstOrFail();
        $attachment = $map->attachment;

        $this->assertSame('private', $attachment->disk);
        $this->assertSame($checksum, $attachment->checksum);
        $this->assertSame(SpatialMap::class, $attachment->attachable_type);
        $this->assertSame($map->id, $attachment->attachable_id);
        Storage::disk('private')->assertExists($attachment->path);
    }

    public function test_upload_rejects_bad_checksum_type_confusion_and_executable_svg(): void
    {
        [$manager, , $location] = $this->officeContext(UserRole::INVENTORY_MANAGER);

        $this->actingAs($manager)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => UploadedFile::fake()->image('floor.png'),
            'map_type' => 'image',
            'checksum' => str_repeat('0', 64),
        ])->assertUnprocessable()->assertJsonValidationErrors('checksum');

        $this->actingAs($manager)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => UploadedFile::fake()->createWithContent('not-an-image.png', 'plain text'),
            'map_type' => 'image',
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $unsafeSvg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';
        $this->actingAs($manager)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => UploadedFile::fake()->createWithContent('unsafe.svg', $unsafeSvg),
            'map_type' => 'svg',
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('spatial_maps', 0);
        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_map_versions_are_explicitly_superseded_and_old_private_file_is_retained(): void
    {
        [$manager, , $location] = $this->officeContext(UserRole::INVENTORY_MANAGER);
        $first = $this->uploadRaster($manager, $location, 'floor-v1.png');
        $firstPath = $first->attachment->path;

        $this->actingAs($manager)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => UploadedFile::fake()->image('duplicate-current.png'),
            'map_type' => 'image',
        ])->assertUnprocessable()->assertJsonValidationErrors('map');

        $response = $this->actingAs($manager)->postJson(route('spatial-maps.supersede', $first), [
            'file' => UploadedFile::fake()->image('floor-v2.png', 800, 600),
            'scale' => 0.1,
            'coordinate_system' => 'LOCAL_METRES',
        ])->assertCreated()->assertJsonPath('map.version', 2);

        $second = SpatialMap::findOrFail($response->json('map.id'));
        $this->assertFalse($first->fresh()->is_current);
        $this->assertTrue($second->is_current);
        $this->assertSame($first->map_type, $second->map_type);
        Storage::disk('private')->assertExists($firstPath);
        Storage::disk('private')->assertExists($second->attachment->path);
    }

    public function test_viewer_cannot_infer_map_file_or_restricted_coordinates_from_routes_or_location_json(): void
    {
        [$manager, $unit, $location] = $this->officeContext(UserRole::INVENTORY_MANAGER, true);
        $map = $this->uploadRaster($manager, $location);
        $privatePath = $map->attachment->path;
        $viewer = User::factory()->viewer()->create();
        $viewer->organizationalUnits()->attach($unit->id, [
            'read_scope' => 'local',
            'write_scope' => 'none',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('locations.show', $location))
            ->assertOk()
            ->assertJsonMissingPath('local_x')
            ->assertJsonMissingPath('local_y')
            ->assertJsonMissingPath('local_z')
            ->assertJsonMissingPath('geometry_geojson');

        foreach (['spatial-maps.show', 'spatial-maps.content', 'spatial-maps.download'] as $route) {
            $this->actingAs($viewer)
                ->get(route($route, $map))
                ->assertForbidden()
                ->assertDontSee($privatePath);
        }

        $this->actingAs($viewer)->postJson(route('spatial-maps.supersede', $map), [
            'file' => UploadedFile::fake()->image('stolen.png'),
        ])->assertForbidden()->assertDontSee($privatePath);
    }

    public function test_authorized_map_payload_contains_only_scoped_hierarchy_and_safe_asset_markers(): void
    {
        [$manager, $unit, $root] = $this->officeContext(UserRole::INVENTORY_MANAGER, true);
        $child = Location::factory()->create([
            'site_id' => $root->site_id,
            'parent_id' => $root->id,
            'path' => $root->path.'999/',
            'name' => 'Restricted rack',
            'location_type' => LocationType::RACK,
            'local_x' => 15,
            'local_y' => 25,
            'local_z' => 3,
            'is_restricted' => true,
            'is_active' => true,
        ]);
        $child->update(['path' => $root->path.$child->id.'/']);
        $localAsset = Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $child->id,
            'ip_address' => '10.10.10.10',
        ]);
        AssetPlacement::create([
            'asset_id' => $localAsset->id,
            'location_id' => $child->id,
            'position_x' => 15,
            'position_y' => 25,
            'position_z' => 3,
            'placed_at' => now(),
            'open_marker' => true,
            'source' => 'application',
        ]);

        [$otherManager, $otherUnit, $otherLocation] = $this->officeContext(UserRole::INVENTORY_MANAGER);
        $otherAsset = Asset::factory()->create([
            'organizational_unit_id' => $otherUnit->id,
            'location_id' => $otherLocation->id,
        ]);

        $map = $this->uploadRaster($manager, $root, 'calibrated.png', [
            'scale' => 1,
            'origin_x' => 10,
            'origin_y' => 20,
            'coordinate_system' => 'LOCAL_METRES',
        ]);

        $response = $this->actingAs($manager)
            ->getJson(route('spatial-maps.show', $map))
            ->assertOk()
            ->assertJsonFragment(['asset_tag' => $localAsset->asset_tag])
            ->assertJsonMissing(['asset_tag' => $otherAsset->asset_tag])
            ->assertJsonMissing(['ip_address' => '10.10.10.10'])
            ->assertJsonFragment(['name' => 'Restricted rack'])
            ->assertJsonPath('assets.0.pixel.x', 5)
            ->assertJsonPath('assets.0.pixel.y', 5);

        $json = $response->getContent();
        $this->assertStringNotContainsString($map->attachment->path, $json);
        $this->assertStringNotContainsString($map->attachment->checksum, $json);
        $this->assertNotSame($manager->id, $otherManager->id);
    }

    public function test_map_relocation_delegates_to_placement_service_and_policy_checks(): void
    {
        [$manager, $unit, $root] = $this->officeContext(UserRole::INVENTORY_MANAGER);
        $destination = Location::factory()->create([
            'site_id' => $root->site_id,
            'parent_id' => $root->id,
            'path' => $root->path.'destination/',
            'name' => 'Destination rack',
            'location_type' => LocationType::RACK,
            'is_active' => true,
        ]);
        $destination->update(['path' => $root->path.$destination->id.'/']);
        $asset = Asset::factory()->create([
            'organizational_unit_id' => $unit->id,
            'location_id' => $root->id,
        ]);
        $oldPlacement = AssetPlacement::create([
            'asset_id' => $asset->id,
            'location_id' => $root->id,
            'placed_at' => now()->subDay(),
            'open_marker' => true,
            'source' => 'application',
        ]);
        $map = $this->uploadRaster($manager, $root);

        $this->actingAs($manager)->patchJson(route('spatial-maps.assets.relocate', [$map, $asset]), [
            'location_id' => $destination->id,
            'position_x' => 12.5,
            'position_y' => 8.25,
            'position_z' => 4,
        ])->assertOk()->assertJsonPath('placement.location_id', $destination->id);

        $this->assertSame($destination->id, $asset->fresh()->location_id);
        $this->assertNotNull($oldPlacement->fresh()->removed_at);
        $this->assertDatabaseHas('asset_placements', [
            'asset_id' => $asset->id,
            'location_id' => $destination->id,
            'position_x' => 12.5,
            'position_y' => 8.25,
            'position_z' => 4,
            'open_marker' => true,
        ]);
    }

    public function test_authorized_content_and_download_are_private_and_delete_removes_the_file(): void
    {
        [$manager, , $location] = $this->officeContext(UserRole::INVENTORY_MANAGER);
        $map = $this->uploadRaster($manager, $location);
        $path = $map->attachment->path;

        $contentResponse = $this->actingAs($manager)
            ->get(route('spatial-maps.content', $map))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $cacheDirectives = collect(explode(',', (string) $contentResponse->headers->get('Cache-Control')))
            ->map(fn (string $directive): string => trim($directive));
        $this->assertTrue($cacheDirectives->contains('private'));
        $this->assertTrue($cacheDirectives->contains('no-store'));

        $this->actingAs($manager)
            ->get(route('spatial-maps.download', $map))
            ->assertOk()
            ->assertDownload('floor.png');

        $this->actingAs($manager)
            ->deleteJson(route('spatial-maps.destroy', $map))
            ->assertOk();

        $this->assertSoftDeleted('spatial_maps', ['id' => $map->id]);
        $this->assertSoftDeleted('attachments', ['id' => $map->attachment_id]);
        Storage::disk('private')->assertMissing($path);
    }

    /**
     * @return array{0: User, 1: OrganizationalUnit, 2: Location}
     */
    private function officeContext(UserRole $role, bool $restricted = false): array
    {
        $unit = OrganizationalUnit::factory()->create();
        $site = Site::create([
            'code' => 'SITE-'.fake()->unique()->bothify('####'),
            'name' => fake()->company(),
            'is_active' => true,
        ]);
        $site->organizationalUnits()->attach($unit->id);
        $location = Location::factory()->create([
            'site_id' => $site->id,
            'name' => 'Mapped floor',
            'location_type' => LocationType::FLOOR,
            'path' => null,
            'local_x' => 10,
            'local_y' => 20,
            'local_z' => 2,
            'geometry_geojson' => ['type' => 'Polygon', 'coordinates' => []],
            'is_restricted' => $restricted,
            'is_active' => true,
        ]);
        $location->update(['path' => '/'.$location->id.'/']);
        $user = User::factory()->create(['role' => $role]);
        $user->organizationalUnits()->attach($unit->id, [
            'read_scope' => 'local',
            'write_scope' => $role === UserRole::INVENTORY_MANAGER ? 'local' : 'none',
        ]);

        return [$user, $unit, $location];
    }

    /** @param array<string, mixed> $metadata */
    private function uploadRaster(
        User $user,
        Location $location,
        string $name = 'floor.png',
        array $metadata = []
    ): SpatialMap {
        $this->actingAs($user)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => UploadedFile::fake()->image($name, 100, 100),
            'map_type' => 'image',
            ...$metadata,
        ])->assertCreated();

        return SpatialMap::query()
            ->where('location_id', $location->id)
            ->current()
            ->latest('version')
            ->firstOrFail();
    }
}
