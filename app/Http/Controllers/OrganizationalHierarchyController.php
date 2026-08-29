<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Services\Organization\OrganizationalContext;
use App\Services\Organization\OrganizationalNavigation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationalHierarchyController extends Controller
{
    public function index(
        Request $request,
        OrganizationalNavigation $navigation,
        OrganizationalContext $context
    ): View {
        $this->authorize('viewAny', Location::class);

        $unitIds = $navigation->contextUnitIds($request->user());
        $unitQuery = OrganizationalUnit::query()->whereIn('id', $unitIds->all());

        if ($request->filled('search')) {
            $term = $request->string('search')->trim()->toString();
            $unitQuery->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('unit_type', 'like', "%{$term}%");
            });
        }

        $units = $unitQuery
            ->with('parent')
            ->orderBy('path')
            ->orderBy('name')
            ->get();

        $sites = $navigation->sites($request->user());
        $locationQuery = $navigation->locationsQuery($request->user())
            ->with(['site', 'parent']);

        if ($request->filled('site_id')) {
            $allowedSiteIds = $sites->pluck('id');
            $locationQuery->whereIn('site_id', $allowedSiteIds->all())
                ->where('site_id', $request->integer('site_id'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search')->trim()->toString();
            $locationQuery->where(function (Builder $query) use ($term): void {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('building', 'like', "%{$term}%")
                    ->orWhere('floor', 'like', "%{$term}%")
                    ->orWhere('sublocation', 'like', "%{$term}%");
            });
        }

        $locations = $locationQuery->orderBy('path')->orderBy('name')->get();

        return view('organization.hierarchy', [
            'activeContext' => $context->getActiveContext($request->user()),
            'units' => $units,
            'sites' => $sites,
            'locations' => $locations,
            'filters' => $request->only(['site_id', 'search']),
        ]);
    }
}
