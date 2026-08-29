<?php

namespace App\Services\DataQuality;

use App\Models\Asset;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Authorization\OrganizationalVisibility;
use Illuminate\Database\Eloquent\Builder;

class InventoryDataQualityService
{
    /**
     * Compute the data quality and completeness score (0-100%) for a single asset.
     *
     * @return array<string, mixed>
     */
    public function evaluateAsset(Asset $asset): array
    {
        $checks = [
            'asset_tag' => [
                'label' => 'Asset Tag',
                'passed' => ! empty($asset->asset_tag),
                'weight' => 15,
            ],
            'serial_number' => [
                'label' => 'Serial Number',
                'passed' => ! empty($asset->serial_number),
                'weight' => 15,
            ],
            'category' => [
                'label' => 'Asset Category',
                'passed' => ! empty($asset->asset_category_id),
                'weight' => 10,
            ],
            'manufacturer' => [
                'label' => 'Manufacturer / Make',
                'passed' => ! empty($asset->manufacturer_id) || ! empty($asset->manufacturer_name_legacy),
                'weight' => 10,
            ],
            'location' => [
                'label' => 'Physical Location',
                'passed' => ! empty($asset->location_id),
                'weight' => 15,
            ],
            'custodian' => [
                'label' => 'Assigned Custodian',
                'passed' => ($asset->status->value === 'in_use') ? ! empty($asset->assigned_official_id) : true,
                'weight' => 10,
            ],
            'acquisition' => [
                'label' => 'Purchase / Acquisition Record',
                'passed' => ! empty($asset->purchase_cost) || ! empty($asset->purchase_date) || $asset->acquisitions()->exists(),
                'weight' => 10,
            ],
            'support' => [
                'label' => 'Warranty / AMC Coverage',
                'passed' => ! empty($asset->warranty_expiry) || ! empty($asset->amc_end) || $asset->agreements()->exists(),
                'weight' => 10,
            ],
            'verification' => [
                'label' => 'Physical Verification (Last 1 Year)',
                'passed' => $asset->verificationItems()->where('verified_at', '>=', now()->subYear())->where('result', 'verified')->exists(),
                'weight' => 5,
            ],
        ];

        $earnedScore = 0;
        $totalWeight = 0;

        foreach ($checks as $key => $check) {
            $totalWeight += $check['weight'];
            if ($check['passed']) {
                $earnedScore += $check['weight'];
            }
        }

        $percentage = $totalWeight > 0 ? (int) round(($earnedScore / $totalWeight) * 100) : 0;

        return [
            'asset_id' => $asset->id,
            'asset_tag' => $asset->asset_tag,
            'score' => $percentage,
            'checks' => $checks,
        ];
    }

    /**
     * Compute organizational unit aggregate data quality metrics.
     *
     * @return array<string, mixed>
     */
    public function evaluateUnit(OrganizationalUnit $unit, ?User $user = null): array
    {
        $query = Asset::query()->where('organizational_unit_id', $unit->id);
        if ($user) {
            $query = app(OrganizationalVisibility::class)->apply($query, $user);
        }

        $totalAssets = $query->count();
        if ($totalAssets === 0) {
            return [
                'total_assets' => 0,
                'tag_coverage_pct' => 100,
                'serial_coverage_pct' => 100,
                'location_coverage_pct' => 100,
                'support_coverage_pct' => 100,
                'verification_coverage_pct' => 100,
                'average_score' => 100,
            ];
        }

        $withTags = (clone $query)->whereNotNull('asset_tag')->where('asset_tag', '!=', '')->count();
        $withSerials = (clone $query)->whereNotNull('serial_number')->where('serial_number', '!=', '')->count();
        $withLocations = (clone $query)->whereNotNull('location_id')->count();
        $withSupport = (clone $query)->where(function ($q) {
            $q->whereNotNull('warranty_expiry')
                ->orWhereNotNull('amc_end')
                ->orWhereHas('agreements');
        })->count();
        $withVerification = (clone $query)->whereHas('verificationItems', function ($q) {
            $q->where('verified_at', '>=', now()->subYear())
                ->where('result', 'verified');
        })->count();

        $tagPct = (int) round(($withTags / $totalAssets) * 100);
        $serialPct = (int) round(($withSerials / $totalAssets) * 100);
        $locationPct = (int) round(($withLocations / $totalAssets) * 100);
        $supportPct = (int) round(($withSupport / $totalAssets) * 100);
        $verificationPct = (int) round(($withVerification / $totalAssets) * 100);

        $avgScore = (int) round(($tagPct + $serialPct + $locationPct + $supportPct + $verificationPct) / 5);

        return [
            'total_assets' => $totalAssets,
            'tag_coverage_pct' => $tagPct,
            'serial_coverage_pct' => $serialPct,
            'location_coverage_pct' => $locationPct,
            'support_coverage_pct' => $supportPct,
            'verification_coverage_pct' => $verificationPct,
            'average_score' => $avgScore,
        ];
    }
}
