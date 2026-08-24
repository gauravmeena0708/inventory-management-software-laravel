<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignAssetRequest;
use App\Models\Asset;
use App\Models\Official;
use App\Services\Assets\AssignAssetAction;
use App\Services\Assets\DecommissionAssetAction;
use App\Services\Assets\ReturnAssetAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetAssignmentController extends Controller
{
    /**
     * Assign the asset to an official.
     */
    public function assign(
        AssignAssetRequest $request,
        Asset $asset,
        AssignAssetAction $action
    ): RedirectResponse|JsonResponse {
        $this->authorize('assign', $asset);

        $official = Official::findOrFail($request->validated('official_id'));

        $assignment = $action->execute(
            asset: $asset,
            official: $official,
            actor: $request->user(),
            remarks: $request->validated('remarks'),
            conditionOut: $request->validated('condition_out'),
            assignedAt: $request->validated('assigned_at')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset assigned successfully.',
                'assignment' => $assignment->fresh(['asset', 'official', 'assignedBy']),
                'asset' => $asset->fresh(['assignedOfficial']),
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', "Asset successfully assigned to {$official->name}.");
    }

    /**
     * Store method alias for assign (supporting resource-style post).
     */
    public function store(
        AssignAssetRequest $request,
        Asset $asset,
        AssignAssetAction $action
    ): RedirectResponse|JsonResponse {
        return $this->assign($request, $asset, $action);
    }

    /**
     * Return an asset into stock and close active assignment.
     */
    public function return(
        Request $request,
        Asset $asset,
        ReturnAssetAction $action
    ): RedirectResponse|JsonResponse {
        $this->authorize('return', $asset);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string'],
            'condition_in' => ['nullable', 'string'],
            'returned_at' => ['nullable', 'date'],
        ]);

        $assignment = $action->execute(
            asset: $asset,
            actor: $request->user(),
            remarks: $validated['remarks'] ?? null,
            conditionIn: $validated['condition_in'] ?? null,
            returnedAt: $validated['returned_at'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset returned successfully.',
                'assignment' => $assignment,
                'asset' => $asset->fresh(),
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset successfully returned to inventory stock.');
    }

    /**
     * Decommission an asset and close open assignments.
     */
    public function decommission(
        Request $request,
        Asset $asset,
        DecommissionAssetAction $action
    ): RedirectResponse|JsonResponse {
        $this->authorize('decommission', $asset);

        $validated = $request->validate([
            'reason' => ['nullable', 'string'],
            'decommissioned_at' => ['nullable', 'date'],
        ]);

        $decommissionedAsset = $action->execute(
            asset: $asset,
            actor: $request->user(),
            reason: $validated['reason'] ?? null,
            decommissionedAt: $validated['decommissioned_at'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset decommissioned successfully.',
                'asset' => $decommissionedAsset,
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset successfully decommissioned.');
    }
}
