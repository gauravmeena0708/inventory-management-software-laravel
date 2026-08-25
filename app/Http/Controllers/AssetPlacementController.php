<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Location;
use App\Services\Assets\AssetPlacementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetPlacementController extends Controller
{
    public function store(
        Request $request,
        Asset $asset,
        AssetPlacementService $service
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'position_x' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'position_y' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'position_z' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'rack_start_unit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rack_unit_height' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $location = Location::query()->findOrFail($validated['location_id']);
        unset($validated['location_id']);

        $placement = $service->placeAsset(
            $asset,
            $location,
            $request->user(),
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset relocated successfully.',
                'placement' => $placement->fresh(['location', 'placedBy']),
                'asset' => $asset->fresh(['location', 'currentPlacement']),
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset relocated successfully.');
    }
}
