<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreManufacturerRequest;
use App\Http\Requests\UpdateManufacturerRequest;
use App\Models\Manufacturer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManufacturerController extends Controller
{
    /**
     * Display a listing of manufacturers.
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->canViewInventory()) {
            abort(403, 'Unauthorized to view manufacturers.');
        }

        $query = Manufacturer::query();

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('support_contact', 'like', "%{$term}%");
            });
        }

        $manufacturers = $query->orderBy('name')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($manufacturers);
        }

        return view('manufacturers.index', [
            'manufacturers' => $manufacturers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new manufacturer.
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to create manufacturers.');
        }

        return view('manufacturers.create');
    }

    /**
     * Store a newly created manufacturer in storage.
     */
    public function store(StoreManufacturerRequest $request): RedirectResponse|JsonResponse
    {
        $manufacturer = Manufacturer::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Manufacturer created successfully.',
                'manufacturer' => $manufacturer,
            ], 201);
        }

        return redirect()->route('manufacturers.show', $manufacturer)
            ->with('success', 'Manufacturer created successfully.');
    }

    /**
     * Display the specified manufacturer.
     */
    public function show(Request $request, Manufacturer $manufacturer): View|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->canViewInventory()) {
            abort(403, 'Unauthorized to view manufacturers.');
        }

        $manufacturer->load('attachments');

        if ($request->wantsJson()) {
            return response()->json($manufacturer);
        }

        return view('manufacturers.show', ['manufacturer' => $manufacturer]);
    }

    /**
     * Show the form for editing the specified manufacturer.
     */
    public function edit(Request $request, Manufacturer $manufacturer): View
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to edit manufacturers.');
        }

        return view('manufacturers.edit', ['manufacturer' => $manufacturer]);
    }

    /**
     * Update the specified manufacturer in storage.
     */
    public function update(UpdateManufacturerRequest $request, Manufacturer $manufacturer): RedirectResponse|JsonResponse
    {
        $manufacturer->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Manufacturer updated successfully.',
                'manufacturer' => $manufacturer->fresh(),
            ]);
        }

        return redirect()->route('manufacturers.show', $manufacturer)
            ->with('success', 'Manufacturer updated successfully.');
    }

    /**
     * Remove the specified manufacturer from storage.
     */
    public function destroy(Request $request, Manufacturer $manufacturer): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && !$user->hasRole(UserRole::ADMIN, UserRole::INVENTORY_MANAGER)) {
            abort(403, 'Unauthorized to delete manufacturers.');
        }

        $manufacturer->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Manufacturer deleted successfully.']);
        }

        return redirect()->route('manufacturers.index')
            ->with('success', 'Manufacturer deleted successfully.');
    }
}
