<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Consumable;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Asset::class);

        $assets = Asset::query()->visibleTo($request->user());

        return view('reports.inventory', [
            'totalAssets' => (clone $assets)->count(),
            'availableAssets' => (clone $assets)->inStock()->count(),
            'assignedAssets' => (clone $assets)->inUse()->count(),
            'maintenanceAssets' => (clone $assets)->where('status', 'under_maintenance')->count(),
            'lowStockConsumables' => Consumable::query()->lowStock()->count(),
            'locations' => Location::query()->visibleTo($request->user())->orderBy('name')->get(),
        ]);
    }
}
