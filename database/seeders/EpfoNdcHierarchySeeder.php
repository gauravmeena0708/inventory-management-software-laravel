<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Enums\OrganizationalUnitType;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Services\Organization\OrganizationalHierarchyService;
use Illuminate\Database\Seeder;

class EpfoNdcHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(OrganizationalHierarchyService $hierarchyService): void
    {
        $root = OrganizationalUnit::where('code', 'EPFO')->first();
        if (! $root) {
            $root = $hierarchyService->createUnit([
                'code' => 'EPFO',
                'name' => 'Employees\' Provident Fund Organisation',
                'unit_type' => OrganizationalUnitType::ROOT->value,
                'is_active' => true,
            ]);
        }

        $ndc = OrganizationalUnit::where('code', 'NDC')->first();
        if (! $ndc) {
            $ndc = $hierarchyService->createUnit([
                'code' => 'NDC',
                'name' => 'National Data Center',
                'unit_type' => OrganizationalUnitType::NDC->value,
                'parent_id' => $root->id,
                'is_active' => true,
            ]);
        }

        $ndcHq = Site::firstOrCreate(
            ['code' => 'NDC_HQ'],
            [
                'name' => 'NDC Headquarters',
                'is_active' => true,
            ]
        );

        if (! $ndc->sites()->where('sites.id', $ndcHq->id)->exists()) {
            $ndc->sites()->attach($ndcHq->id);
        }

        $store = Location::firstOrCreate(
            ['site_id' => $ndcHq->id, 'code' => 'NDC_MAIN_STORE'],
            [
                'name' => 'NDC Main Store',
                'floor' => 'Ground',
                'location_type' => LocationType::STORE,
                'is_active' => true,
            ]
        );

        if (blank($store->path)) {
            $store->forceFill(['path' => '/'.$store->id.'/'])->save();
        }
    }
}
