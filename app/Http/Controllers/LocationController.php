<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\SpatialMap;
use App\Services\Organization\OrganizationalNavigation;
use App\Services\Spatial\PhysicalHierarchyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(Request $request, OrganizationalNavigation $navigation): View|JsonResponse
    {
        $this->authorize('viewAny', Location::class);

        $query = $navigation->locationsQuery($request->user())
            ->withCount([
                'officials' => fn ($officialQuery) => $officialQuery->visibleTo($request->user()),
            ]);

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sublocation', 'like', "%{$term}%")
                    ->orWhere('building', 'like', "%{$term}%")
                    ->orWhere('floor', 'like', "%{$term}%");
            });
        }

        $sites = $navigation->sites($request->user());
        if ($request->filled('site_id')) {
            $query->whereIn('site_id', $sites->pluck('id')->all())
                ->where('site_id', $request->integer('site_id'));
        }

        $locations = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($locations);
        }

        return view('locations.index', [
            'locations' => $locations,
            'sites' => $sites,
            'filters' => $request->only(['search', 'site_id']),
        ]);
    }

    /**
     * Show the form for creating a new location.
     */
    public function create(Request $request, OrganizationalNavigation $navigation): View
    {
        $this->authorize('create', Location::class);

        return view('locations.create', [
            'sites' => $navigation->sites($request->user(), true),
            'parentLocations' => $navigation->locations($request->user(), true),
        ]);
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(
        StoreLocationRequest $request,
        PhysicalHierarchyService $hierarchy,
        OrganizationalNavigation $navigation
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $this->assertAuthorizedSelection($request, $validated, $navigation);
        $location = $hierarchy->createLocation($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Location created successfully.',
                'location' => $location,
            ], 201);
        }

        return redirect()->route('locations.show', $location)
            ->with('success', 'Location created successfully.');
    }

    /**
     * Display the specified location.
     */
    public function show(Request $request, Location $location): View|JsonResponse
    {
        $this->authorize('view', $location);

        $location->load([
            'site',
            'parent',
            'officials' => fn ($query) => $query->visibleTo($request->user()),
            'attachments' => fn ($query) => $query->visibleTo($request->user()),
        ]);

        if ($request->wantsJson()) {
            return response()->json($location);
        }

        $spatialMaps = collect();
        if ($request->user()->can('viewAny', SpatialMap::class)) {
            $spatialMaps = $location->spatialMaps()
                ->current()
                ->orderBy('map_type')
                ->get();
        }

        return view('locations.show', [
            'location' => $location,
            'spatialMaps' => $spatialMaps,
            'canManageSpatialMaps' => $request->user()->can('create', [SpatialMap::class, $location]),
            'breadcrumbs' => $this->breadcrumbs($location, $request),
        ]);
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(Request $request, Location $location, OrganizationalNavigation $navigation): View
    {
        $this->authorize('update', $location);

        return view('locations.edit', [
            'location' => $location,
            'sites' => $navigation->sites($request->user(), true),
            'parentLocations' => $navigation->locations($request->user(), true)
                ->reject(fn (Location $candidate): bool => $candidate->id === $location->id),
        ]);
    }

    /**
     * Update the specified location in storage.
     */
    public function update(
        UpdateLocationRequest $request,
        Location $location,
        PhysicalHierarchyService $hierarchy,
        OrganizationalNavigation $navigation
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $this->assertAuthorizedSelection($request, $validated, $navigation);

        if (array_key_exists('parent_id', $validated)
            && (int) ($validated['parent_id'] ?? 0) !== (int) ($location->parent_id ?? 0)) {
            $newParent = filled($validated['parent_id'])
                ? $navigation->locationsQuery($request->user(), true)->findOrFail($validated['parent_id'])
                : null;
            $hierarchy->moveLocation($location, $newParent);
        }

        unset($validated['parent_id']);
        if ($location->parent_id) {
            unset($validated['site_id']);
        }
        $location->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Location updated successfully.',
                'location' => $location->fresh(),
            ]);
        }

        return redirect()->route('locations.show', $location)
            ->with('success', 'Location updated successfully.');
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy(Request $request, Location $location): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $location);

        $location->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Location deleted successfully.']);
        }

        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }

    /** @param array<string, mixed> $data */
    private function assertAuthorizedSelection(
        Request $request,
        array $data,
        OrganizationalNavigation $navigation
    ): void {
        if (! empty($data['site_id'])
            && ! $navigation->sites($request->user(), true)->contains('id', (int) $data['site_id'])) {
            abort(403);
        }

        if (! empty($data['parent_id'])
            && ! $navigation->locationsQuery($request->user(), true)->whereKey($data['parent_id'])->exists()) {
            abort(403);
        }
    }

    /** @return Collection<int, Location> */
    private function breadcrumbs(Location $location, Request $request): Collection
    {
        $ids = collect(explode('/', trim((string) $location->path, '/')))
            ->filter(fn (string $id): bool => ctype_digit($id))
            ->map(fn (string $id): int => (int) $id);

        if ($ids->isEmpty()) {
            return collect([$location]);
        }

        $visible = Location::query()
            ->visibleTo($request->user())
            ->whereIn('id', $ids->all())
            ->get()
            ->keyBy('id');

        return $ids->map(fn (int $id) => $visible->get($id))->filter()->values();
    }
}
