<?php

namespace App\Http\Controllers;

use App\Contracts\TabularExporter;
use App\Exports\AssetsExport;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\CreateAssetAction;
use App\Services\Assets\UpdateAssetAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    /**
     * Display a listing of the assets with filtering, search, and pagination.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Asset::class);

        $query = Asset::query()->with(['manufacturer', 'location', 'assignedOfficial']);

        if ($request->filled('type')) {
            $query->type($request->input('type'));
        }

        if ($request->filled('asset_type')) {
            $query->type($request->input('asset_type'));
        }

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $assets = $query->latest('id')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($assets);
        }

        return view('assets.index', [
            'assets' => $assets,
            'filters' => $request->only(['type', 'asset_type', 'status', 'location_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new asset.
     */
    public function create(): View
    {
        $this->authorize('create', Asset::class);

        return view('assets.create', [
            'locations' => Location::orderBy('name')->get(),
            'manufacturers' => Manufacturer::orderBy('name')->get(),
            'officials' => Official::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(
        StoreAssetRequest $request,
        CreateAssetAction $createAction,
        AssignAssetAction $assignAction
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();
        $asset = $createAction->execute($validated, $request->user());

        if (!empty($validated['assigned_official_id'])) {
            $official = Official::find($validated['assigned_official_id']);
            if ($official) {
                $assignAction->execute(
                    asset: $asset,
                    official: $official,
                    actor: $request->user(),
                    remarks: 'Initial assignment on asset creation'
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset created successfully.',
                'asset' => $asset->fresh(['manufacturer', 'location', 'assignedOfficial']),
            ], 201);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    /**
     * Display the specified asset.
     */
    public function show(Request $request, Asset $asset): View|JsonResponse
    {
        $this->authorize('view', $asset);

        $asset->load([
            'manufacturer',
            'location',
            'assignedOfficial',
            'assignments.official',
            'assignments.assignedBy',
            'attachments',
            'file',
        ]);

        if ($request->wantsJson()) {
            return response()->json($asset);
        }

        return view('assets.show', ['asset' => $asset]);
    }

    /**
     * Show the form for editing the specified asset.
     */
    public function edit(Asset $asset): View
    {
        $this->authorize('update', $asset);

        return view('assets.edit', [
            'asset' => $asset,
            'locations' => Location::orderBy('name')->get(),
            'manufacturers' => Manufacturer::orderBy('name')->get(),
            'officials' => Official::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified asset in storage.
     */
    public function update(
        UpdateAssetRequest $request,
        Asset $asset,
        UpdateAssetAction $action
    ): RedirectResponse|JsonResponse {
        $updatedAsset = $action->execute($asset, $request->validated(), $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset updated successfully.',
                'asset' => $updatedAsset->fresh(['manufacturer', 'location', 'assignedOfficial']),
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    /**
     * Remove the specified asset from storage.
     */
    public function destroy(Request $request, Asset $asset): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Asset deleted successfully.']);
        }

        return redirect()->route('assets.index')
            ->with('success', 'Asset deleted successfully.');
    }

    /**
     * Export assets to an Excel spreadsheet.
     */
    public function export(Request $request, TabularExporter $exporter): BinaryFileResponse
    {
        $this->authorize('export', Asset::class);

        $query = Asset::query();

        if ($request->filled('type')) {
            $query->type($request->input('type'));
        }

        if ($request->filled('asset_type')) {
            $query->type($request->input('asset_type'));
        }

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $filename = 'assets-' . now()->format('Y-m-d-His') . '.xlsx';

        return $exporter->download(new AssetsExport($query), $filename);
    }
}
