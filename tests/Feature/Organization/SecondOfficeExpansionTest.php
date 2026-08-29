<?php

namespace Tests\Feature\Organization;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\LocationType;
use App\Enums\SpatialMapType;
use App\Enums\UserRole;
use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\SpatialMap;
use App\Models\StockBalance;
use App\Models\StockTransaction;
use App\Models\User;
use App\Services\Assets\CreateAssetAction;
use App\Services\Assets\UpdateAssetAction;
use App\Services\Spatial\PhysicalHierarchyService;
use App\Services\Stock\LocationStockService;
use Database\Seeders\EpfoExpansionDemoSeeder;
use Database\Seeders\EpfoNdcHierarchySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecondOfficeExpansionTest extends TestCase
{
    use RefreshDatabase;

    private User $secondOfficeManager;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        User::factory()->admin()->create(['email' => 'admin@inventory.local']);
        $this->seed(EpfoNdcHierarchySeeder::class);
        $this->seed(EpfoExpansionDemoSeeder::class);

        $this->secondOfficeManager = $this->managerFor(
            $this->secondUnit(),
            'second.office.manager@example.test'
        );
        $this->secondOfficeManager->forceFill([
            'default_organizational_unit_id' => $this->secondUnit()->id,
        ])->save();
    }

    public function test_second_office_is_loaded_from_configuration_through_public_services_and_is_idempotent(): void
    {
        $root = OrganizationalUnit::where('code', 'EPFO')->firstOrFail();
        $unit = $this->secondUnit();
        $site = $this->secondSite();
        $building = $this->secondLocation('NORTH-DEMO-BUILDING');
        $floor = $this->secondLocation('NORTH-DEMO-FLOOR-1');
        $store = $this->secondStore();
        $manager = $this->secondManager();
        $asset = $this->secondAsset();
        $balance = StockBalance::where('location_id', $store->id)->firstOrFail();

        $this->assertSame($root->id, $unit->parent_id);
        $this->assertTrue($unit->sites->contains($site));
        $this->assertSame($site->id, $building->site_id);
        $this->assertSame($building->id, $floor->parent_id);
        $this->assertSame($floor->id, $store->parent_id);
        $this->assertSame($unit->id, $manager->default_organizational_unit_id);
        $this->assertSame($unit->id, $asset->organizational_unit_id);
        $this->assertSame($store->id, $asset->location_id);
        $this->assertSame(20, $balance->quantity);
        $this->assertNotNull($asset->currentPlacement);

        $source = file_get_contents(database_path('seeders/EpfoExpansionDemoSeeder.php'));
        $this->assertIsString($source);
        $this->assertStringNotContainsString('NDC', strtoupper($source));

        $this->seed(EpfoExpansionDemoSeeder::class);

        $this->assertSame(1, OrganizationalUnit::where('code', 'ZO-NORTH-DEMO')->count());
        $this->assertSame(1, Site::where('code', 'NORTH-DEMO-SITE')->count());
        $this->assertSame(3, Location::where('site_id', $site->id)->count());
        $this->assertSame(1, Asset::where('asset_tag', 'NORTH-DEMO-ASSET-001')->count());
        $this->assertSame(1, StockBalance::where('location_id', $store->id)->count());
        $this->assertSame(1, StockTransaction::where('idempotency_key', 'north-demo-opening-stock')->count());
    }

    public function test_offices_are_read_isolated_while_ancestor_read_down_and_write_grants_are_explicit(): void
    {
        $ndc = $this->ndcUnit();
        $second = $this->secondUnit();
        $ndcManager = $this->managerFor($ndc, 'ndc.manager@example.test');
        $ndcAsset = $this->createNdcAsset($ndcManager);
        $secondManager = $this->secondManager();
        $secondAsset = $this->secondAsset();

        $this->assertEquals([$ndcAsset->id], Asset::visibleTo($ndcManager)->pluck('id')->all());
        $this->assertEquals([$secondAsset->id], Asset::visibleTo($secondManager)->pluck('id')->all());

        $ancestorManager = $this->managerFor(
            OrganizationalUnit::where('code', 'EPFO')->firstOrFail(),
            'ancestor.manager@example.test',
            'descendants',
            'local'
        );

        $this->assertEqualsCanonicalizing(
            [$ndcAsset->id, $secondAsset->id],
            Asset::visibleTo($ancestorManager)->pluck('id')->all()
        );
        $this->assertFalse(Gate::forUser($ancestorManager)->allows('update', $secondAsset));

        $ancestorManager->organizationalUnits()->updateExistingPivot(
            OrganizationalUnit::where('code', 'EPFO')->value('id'),
            ['write_scope' => 'descendants']
        );
        $ancestorManager->unsetRelation('activeOrganizationalUnits');

        $this->assertTrue(Gate::forUser($ancestorManager)->allows('update', $secondAsset));
    }

    public function test_asset_and_stock_transfer_workflows_cross_the_office_boundary_with_both_sides_authorized(): void
    {
        $ndc = $this->ndcUnit();
        $second = $this->secondUnit();
        $ndcManager = $this->managerFor($ndc, 'ndc.transfer@example.test');
        $secondManager = $this->secondManager();
        $ndcAsset = $this->createNdcAsset($ndcManager);
        $oldPlacement = $ndcAsset->currentPlacement;
        $secondStore = $this->secondStore();

        $transferManager = $this->managerFor(
            OrganizationalUnit::where('code', 'EPFO')->firstOrFail(),
            'transfer.manager@example.test',
            'descendants',
            'descendants'
        );
        $transferredAsset = app(UpdateAssetAction::class)->execute($ndcAsset, [
            'organizational_unit_id' => $second->id,
            'location_id' => $secondStore->id,
        ], $transferManager);

        $this->assertSame($second->id, $transferredAsset->organizational_unit_id);
        $this->assertSame($secondStore->id, $transferredAsset->location_id);
        $this->assertNotNull($oldPlacement?->fresh()->removed_at);
        $this->assertSame($secondStore->id, $transferredAsset->currentPlacement?->location_id);

        $consumable = Consumable::where('sku', 'NORTH-DEMO-PAPER')->firstOrFail();
        $ndcStore = $this->ndcStore();
        $stock = app(LocationStockService::class);
        $stock->purchase($consumable, $ndcStore, 10, $ndcManager, 'ndc-transfer-opening');
        $transaction = $stock->transfer(
            $consumable,
            $ndcStore,
            $secondStore,
            4,
            $ndcManager,
            $secondManager,
            'second-office-transfer'
        );

        $this->assertSame(6, StockBalance::where('location_id', $ndcStore->id)->value('quantity'));
        $this->assertSame(24, StockBalance::where('location_id', $secondStore->id)->value('quantity'));
        $this->assertSame($ndcManager->id, $transaction->recorded_by);
        $this->assertSame($secondManager->id, $transaction->accepted_by);
    }

    public function test_dashboard_export_maps_attachments_and_audit_history_remain_office_scoped(): void
    {
        $ndcManager = $this->managerFor($this->ndcUnit(), 'ndc.scope@example.test');
        $ndcAsset = $this->createNdcAsset($ndcManager);
        $secondManager = $this->secondManager();
        $secondAsset = $this->secondAsset();

        $ndcAttachment = $this->attachmentFor($ndcAsset, 'ndc-private.pdf');
        $secondAttachment = $this->attachmentFor($secondAsset, 'second-private.pdf');
        $ndcMap = $this->uploadMap($ndcManager, $this->ndcBuilding(), 'ndc-floor.png');
        $secondMap = $this->uploadMap($secondManager, $this->secondLocation('NORTH-DEMO-BUILDING'), 'second-floor.png');

        $dashboard = $this->actingAs($secondManager)
            ->getJson(route('dashboard'))
            ->assertOk()
            ->assertJsonPath('total_assets', 1)
            ->assertJsonPath('assets_in_stock', 1);

        $activitySubjectIds = collect($dashboard->json('recent_activities'))
            ->pluck('subject_id')
            ->unique()
            ->values();
        $this->assertNotEmpty($activitySubjectIds);
        $this->assertEqualsCanonicalizing([$secondAsset->id], $activitySubjectIds->all());

        $export = new AssetsExport(Asset::query()->visibleTo($secondManager), $secondManager);
        $this->assertEquals([$secondAsset->id], $export->query()->pluck('assets.id')->all());

        $this->actingAs($secondManager)
            ->getJson(route('spatial-maps.show', $secondMap))
            ->assertOk()
            ->assertJsonFragment(['asset_tag' => $secondAsset->asset_tag])
            ->assertJsonMissing(['asset_tag' => $ndcAsset->asset_tag]);
        $this->actingAs($secondManager)
            ->getJson(route('spatial-maps.show', $ndcMap))
            ->assertForbidden();

        $visibleAttachmentIds = Attachment::query()
            ->visibleTo($secondManager)
            ->pluck('id');
        $this->assertTrue($visibleAttachmentIds->contains($secondAttachment->id));
        $this->assertTrue($visibleAttachmentIds->contains($secondMap->attachment_id));
        $this->assertFalse($visibleAttachmentIds->contains($ndcAttachment->id));
        $this->assertFalse($visibleAttachmentIds->contains($ndcMap->attachment_id));
        $this->actingAs($secondManager)
            ->get(route('attachments.download', $ndcAttachment))
            ->assertForbidden();
    }

    private function ndcUnit(): OrganizationalUnit
    {
        return OrganizationalUnit::where('code', 'NDC')->firstOrFail();
    }

    private function secondUnit(): OrganizationalUnit
    {
        return OrganizationalUnit::where('code', 'ZO-NORTH-DEMO')->firstOrFail();
    }

    private function secondSite(): Site
    {
        return Site::where('code', 'NORTH-DEMO-SITE')->firstOrFail();
    }

    private function secondLocation(string $code): Location
    {
        return Location::where('code', $code)->firstOrFail();
    }

    private function secondStore(): Location
    {
        return $this->secondLocation('NORTH-DEMO-STORE');
    }

    private function secondManager(): User
    {
        return $this->secondOfficeManager;
    }

    private function secondAsset(): Asset
    {
        return Asset::where('asset_tag', 'NORTH-DEMO-ASSET-001')->firstOrFail();
    }

    private function managerFor(
        OrganizationalUnit $unit,
        string $email,
        string $readScope = 'local',
        string $writeScope = 'local'
    ): User {
        $manager = User::factory()->create([
            'email' => $email,
            'role' => UserRole::INVENTORY_MANAGER,
        ]);
        $manager->organizationalUnits()->attach($unit->id, [
            'read_scope' => $readScope,
            'write_scope' => $writeScope,
        ]);

        return $manager;
    }

    private function ndcBuilding(): Location
    {
        $site = Site::where('code', 'NDC_HQ')->firstOrFail();
        $physical = app(PhysicalHierarchyService::class);
        $building = Location::where('code', 'NDC-EXPANSION-BUILDING')->first();

        return $building ?: $physical->createLocation([
            'site_id' => $site->id,
            'code' => 'NDC-EXPANSION-BUILDING',
            'name' => 'NDC Expansion Proof Building',
            'floor' => 'Ground',
            'location_type' => LocationType::BUILDING,
            'is_active' => true,
        ]);
    }

    private function ndcStore(): Location
    {
        $physical = app(PhysicalHierarchyService::class);
        $building = $this->ndcBuilding();
        $floor = Location::where('code', 'NDC-EXPANSION-FLOOR')->first();
        if (! $floor) {
            $floor = $physical->createLocation([
                'site_id' => $building->site_id,
                'parent_id' => $building->id,
                'code' => 'NDC-EXPANSION-FLOOR',
                'name' => 'NDC Expansion Proof Floor',
                'floor' => '1',
                'location_type' => LocationType::FLOOR,
                'is_active' => true,
            ]);
        }

        $store = Location::where('code', 'NDC-EXPANSION-STORE')->first();

        return $store ?: $physical->createLocation([
            'site_id' => $floor->site_id,
            'parent_id' => $floor->id,
            'code' => 'NDC-EXPANSION-STORE',
            'name' => 'NDC Expansion Proof Store',
            'floor' => '1',
            'location_type' => LocationType::STORE,
            'is_active' => true,
        ]);
    }

    private function createNdcAsset(User $manager): Asset
    {
        return app(CreateAssetAction::class)->execute([
            'asset_tag' => 'NDC-EXPANSION-ASSET-001',
            'name' => 'NDC Expansion Proof Laptop',
            'asset_type' => AssetType::LAPTOP,
            'status' => AssetStatus::IN_STOCK,
            'serial_number' => 'NDC-EXPANSION-SERIAL-001',
            'location_id' => $this->ndcStore()->id,
            'organizational_unit_id' => $this->ndcUnit()->id,
        ], $manager);
    }

    private function attachmentFor(Asset $asset, string $name): Attachment
    {
        return $asset->attachments()->create([
            'disk' => 'private',
            'path' => 'attachments/'.$name,
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size' => 123,
            'checksum' => hash('sha256', $name),
        ]);
    }

    private function uploadMap(User $user, Location $location, string $name): SpatialMap
    {
        $this->actingAs($user)->postJson(route('locations.spatial-maps.store', $location), [
            'file' => UploadedFile::fake()->image($name, 100, 100),
            'map_type' => SpatialMapType::IMAGE->value,
            'scale' => 1,
            'coordinate_system' => 'LOCAL_METRES',
        ])->assertCreated();

        return SpatialMap::where('location_id', $location->id)->current()->firstOrFail();
    }
}
