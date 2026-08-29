<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Consumable;
use App\Models\Location;
use App\Models\ReportDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Asset::class);

        $baseAssetsQuery = Asset::query()->visibleTo($request->user());

        // Fetch all assets in scope for high-fidelity pivot table computations
        $allScopeAssets = (clone $baseAssetsQuery)
            ->with(['category', 'location', 'assignedOfficial'])
            ->get();

        // 1. Build Type x Status Pivot Matrix
        $typePivot = [];
        $statusKeys = [
            'in_stock' => 'In Stock',
            'in_use' => 'In Use',
            'under_maintenance' => 'Maintenance',
            'in_transit' => 'In Transit',
            'pending_disposal' => 'Pending Disposal',
            'decommissioned' => 'Decommissioned / Disposed',
        ];

        $columnTotals = array_fill_keys(array_keys($statusKeys), 0);
        $totalAssetsCount = 0;
        $totalAssetsValuation = 0.0;

        foreach (AssetType::cases() as $type) {
            $typeAssets = $allScopeAssets->filter(function (Asset $a) use ($type) {
                return ($a->asset_type instanceof AssetType ? $a->asset_type->value : $a->asset_type) === $type->value;
            });

            $rowCounts = [
                'in_stock' => $typeAssets->where('status', AssetStatus::IN_STOCK)->count(),
                'in_use' => $typeAssets->where('status', AssetStatus::IN_USE)->count(),
                'under_maintenance' => $typeAssets->where('status', AssetStatus::UNDER_MAINTENANCE)->count(),
                'in_transit' => $typeAssets->where('status', AssetStatus::IN_TRANSIT)->count(),
                'pending_disposal' => $typeAssets->where('status', AssetStatus::PENDING_DISPOSAL)->count(),
                'decommissioned' => $typeAssets->filter(fn ($a) => in_array($a->status, [AssetStatus::DECOMMISSIONED, AssetStatus::DISPOSED]))->count(),
            ];

            $rowTotal = $typeAssets->count();
            $rowValuation = (float) $typeAssets->sum('purchase_cost');

            foreach ($rowCounts as $key => $cnt) {
                $columnTotals[$key] += $cnt;
            }
            $totalAssetsCount += $rowTotal;
            $totalAssetsValuation += $rowValuation;

            $typePivot[] = [
                'type' => $type,
                'label' => $type->label(),
                'counts' => $rowCounts,
                'total' => $rowTotal,
                'valuation' => $rowValuation,
            ];
        }

        // 2. Build Category x Status Pivot Matrix
        $categories = AssetCategory::query()->orderBy('name')->get();
        $categoryPivot = [];
        foreach ($categories as $category) {
            $catAssets = $allScopeAssets->where('asset_category_id', $category->id);
            if ($catAssets->count() === 0) {
                continue;
            }

            $categoryPivot[] = [
                'category' => $category,
                'name' => $category->name,
                'family' => $category->broad_family,
                'code' => $category->code,
                'counts' => [
                    'in_stock' => $catAssets->where('status', AssetStatus::IN_STOCK)->count(),
                    'in_use' => $catAssets->where('status', AssetStatus::IN_USE)->count(),
                    'under_maintenance' => $catAssets->where('status', AssetStatus::UNDER_MAINTENANCE)->count(),
                    'in_transit' => $catAssets->where('status', AssetStatus::IN_TRANSIT)->count(),
                    'pending_disposal' => $catAssets->where('status', AssetStatus::PENDING_DISPOSAL)->count(),
                    'decommissioned' => $catAssets->filter(fn ($a) => in_array($a->status, [AssetStatus::DECOMMISSIONED, AssetStatus::DISPOSED]))->count(),
                ],
                'total' => $catAssets->count(),
                'valuation' => (float) $catAssets->sum('purchase_cost'),
            ];
        }

        // 3. Filtered asset list for paginated exploration
        $filteredAssetsQuery = (clone $baseAssetsQuery)
            ->with(['category', 'location', 'assignedOfficial', 'manufacturer']);

        if ($request->filled('type')) {
            $filteredAssetsQuery->where('asset_type', $request->string('type'));
        }

        if ($request->filled('category_id')) {
            $filteredAssetsQuery->where('asset_category_id', $request->integer('category_id'));
        }

        if ($request->filled('status')) {
            $filteredAssetsQuery->where('status', $request->string('status'));
        }

        if ($request->filled('location_id')) {
            $filteredAssetsQuery->where('location_id', $request->integer('location_id'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search')->trim()->toString();
            $filteredAssetsQuery->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('asset_tag', 'like', "%{$term}%")
                    ->orWhere('serial_number', 'like', "%{$term}%")
                    ->orWhere('model_number', 'like', "%{$term}%");
            });
        }

        $filteredAssets = $filteredAssetsQuery->orderBy('asset_tag')->paginate(15)->withQueryString();

        // 4. Consumables
        $consumables = Consumable::query()
            ->with('entries')
            ->orderBy('name')
            ->limit(10)
            ->get();

        // 5. Official report catalog
        $reportDefinitions = ReportDefinition::active()->get();

        return view('reports.inventory', [
            'totalAssets' => $totalAssetsCount,
            'availableAssets' => $columnTotals['in_stock'],
            'assignedAssets' => $columnTotals['in_use'],
            'maintenanceAssets' => $columnTotals['under_maintenance'],
            'lowStockConsumables' => Consumable::query()->lowStock()->count(),
            'totalValuation' => $totalAssetsValuation,
            'locations' => Location::query()->visibleTo($request->user())->orderBy('name')->get(),
            'categories' => $categories,
            'statusKeys' => $statusKeys,
            'columnTotals' => $columnTotals,
            'typePivot' => $typePivot,
            'categoryPivot' => $categoryPivot,
            'filteredAssets' => $filteredAssets,
            'consumables' => $consumables,
            'reportDefinitions' => $reportDefinitions,
            'filters' => $request->only(['type', 'category_id', 'status', 'location_id', 'search']),
        ]);
    }
}
