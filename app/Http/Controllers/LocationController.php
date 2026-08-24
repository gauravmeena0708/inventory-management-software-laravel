<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->canViewInventory()) {
            abort(403, 'Unauthorized to view locations.');
        }

        $query = Location::query()->withCount('officials');

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sublocation', 'like', "%{$term}%")
                    ->orWhere('building', 'like', "%{$term}%")
                    ->orWhere('floor', 'like', "%{$term}%");
            });
        }

        $locations = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($locations);
        }

        return view('locations.index', [
            'locations' => $locations,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new location.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to create locations.');
        }

        return view('locations.create');
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(StoreLocationRequest $request): RedirectResponse|JsonResponse
    {
        $location = Location::create($request->validated());

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
        $user = $request->user();
        if ($user && !$user->canViewInventory()) {
            abort(403, 'Unauthorized to view locations.');
        }

        $location->load(['officials', 'attachments']);

        if ($request->wantsJson()) {
            return response()->json($location);
        }

        return view('locations.show', ['location' => $location]);
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(Request $request, Location $location): View
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to edit locations.');
        }

        return view('locations.edit', ['location' => $location]);
    }

    /**
     * Update the specified location in storage.
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse|JsonResponse
    {
        $location->update($request->validated());

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
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to delete locations.');
        }

        $location->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Location deleted successfully.']);
        }

        return redirect()->route('locations.index')
            ->with('success', 'Location deleted successfully.');
    }
}
