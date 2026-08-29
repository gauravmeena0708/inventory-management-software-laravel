<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Exports\AssetsExport;
use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Location;
use App\Models\Official;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class DataLeakageTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_list_search_detail_and_dashboard_do_not_leak_another_office(): void
    {
        [$user, $localAsset, $otherAsset] = $this->twoOfficeContext();

        $this->actingAs($user)
            ->getJson(route('assets.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $localAsset->id);

        $this->actingAs($user)
            ->getJson(route('assets.index', ['search' => $otherAsset->serial_number]))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($user)
            ->getJson(route('assets.show', $localAsset))
            ->assertOk()
            ->assertJsonPath('id', $localAsset->id);

        $this->actingAs($user)
            ->getJson(route('assets.show', $otherAsset))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('dashboard'))
            ->assertOk()
            ->assertJsonPath('total_assets', 1)
            ->assertJsonPath('assets_in_stock', 1);
    }

    public function test_asset_export_query_is_scoped_to_the_requesting_user(): void
    {
        [$user, $localAsset, $otherAsset] = $this->twoOfficeContext();
        $user->update(['role' => UserRole::AUDITOR]);
        $localAsset->update(['location_id' => $otherAsset->location_id]);
        Carbon::setTestNow('2026-08-25 12:34:56');
        Excel::fake();

        $this->actingAs($user)
            ->get(route('assets.export'))
            ->assertOk();

        Excel::assertDownloaded('assets-2026-08-25-123456.xlsx', function (AssetsExport $export) use ($localAsset): bool {
            $exportedAsset = $export->query()->first();

            return $exportedAsset?->id === $localAsset->id
                && $exportedAsset->location === null;
        });
    }

    public function test_asset_attachments_inherit_visibility_and_hide_private_storage_metadata(): void
    {
        [$user, $localAsset, $otherAsset] = $this->twoOfficeContext();
        $localAttachment = $this->attachmentFor($localAsset, 'local.pdf');
        $otherAttachment = $this->attachmentFor($otherAsset, 'other.pdf');

        $response = $this->actingAs($user)->getJson(route('assets.show', $localAsset));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'attachments')
            ->assertJsonPath('attachments.0.id', $localAttachment->id)
            ->assertJsonMissingPath('attachments.0.path')
            ->assertJsonMissingPath('attachments.0.disk')
            ->assertJsonMissingPath('attachments.0.checksum');

        $this->assertTrue(Attachment::query()->visibleTo($user)->whereKey($localAttachment->id)->exists());
        $this->assertFalse(Attachment::query()->visibleTo($user)->whereKey($otherAttachment->id)->exists());

        $this->actingAs($user)
            ->get(route('attachments.download', $otherAttachment))
            ->assertForbidden();
    }

    public function test_locations_and_officials_inherit_site_visibility(): void
    {
        [$user, $localAsset, $otherAsset] = $this->twoOfficeContext();
        $localLocation = $localAsset->location;
        $otherLocation = $otherAsset->location;
        $localOfficial = Official::factory()->create([
            'name' => 'Local official',
            'location_id' => $localLocation->id,
        ]);
        $otherOfficial = Official::factory()->create([
            'name' => 'Other official',
            'location_id' => $otherLocation->id,
        ]);

        $this->actingAs($user)
            ->getJson(route('locations.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $localLocation->id);

        $this->actingAs($user)
            ->getJson(route('locations.show', $otherLocation))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('officials.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $localOfficial->id);

        $this->actingAs($user)
            ->getJson(route('officials.show', $otherOfficial))
            ->assertForbidden();
    }

    public function test_audit_activity_feed_contains_only_visible_asset_subjects(): void
    {
        [$user, $localAsset, $otherAsset] = $this->twoOfficeContext();
        $user->update(['role' => UserRole::AUDITOR]);

        $activities = collect(
            $this->actingAs($user)
                ->getJson(route('dashboard'))
                ->assertOk()
                ->json('recent_activities')
        );

        $this->assertNotEmpty($activities);
        $this->assertEqualsCanonicalizing(
            [$localAsset->id],
            $activities->pluck('subject_id')->unique()->values()->all()
        );
        $this->assertNotContains($otherAsset->id, $activities->pluck('subject_id'));
    }

    /**
     * @return array{0: User, 1: Asset, 2: Asset}
     */
    private function twoOfficeContext(): array
    {
        $localUnit = OrganizationalUnit::factory()->create(['code' => 'LOCAL']);
        $otherUnit = OrganizationalUnit::factory()->create(['code' => 'OTHER']);
        $user = User::factory()->viewer()->create();
        $user->organizationalUnits()->attach($localUnit->id, ['read_scope' => 'local']);
        $localSite = $this->siteFor($localUnit, 'LOCAL-SITE');
        $otherSite = $this->siteFor($otherUnit, 'OTHER-SITE');
        $localLocation = Location::factory()->create(['site_id' => $localSite->id]);
        $otherLocation = Location::factory()->create(['site_id' => $otherSite->id]);
        $localAsset = Asset::factory()->inStock()->create([
            'name' => 'Local asset',
            'organizational_unit_id' => $localUnit->id,
            'location_id' => $localLocation->id,
        ]);
        $otherAsset = Asset::factory()->inStock()->create([
            'name' => 'Other office asset',
            'organizational_unit_id' => $otherUnit->id,
            'location_id' => $otherLocation->id,
        ]);

        return [$user, $localAsset, $otherAsset];
    }

    private function siteFor(OrganizationalUnit $unit, string $code): Site
    {
        $site = Site::create([
            'code' => $code,
            'name' => $code,
            'is_active' => true,
        ]);
        $site->organizationalUnits()->attach($unit->id);

        return $site;
    }

    private function attachmentFor(Asset $asset, string $name): Attachment
    {
        return $asset->attachments()->create([
            'disk' => 'private',
            'path' => "attachments/{$name}",
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size' => 123,
            'checksum' => str_repeat('a', 64),
        ]);
    }
}
