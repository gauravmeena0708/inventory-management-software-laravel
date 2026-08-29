<?php

namespace App\Http\Controllers;

use App\Contracts\AttachmentStore;
use App\Enums\SpatialMapType;
use App\Models\Asset;
use App\Models\Location;
use App\Models\SpatialMap;
use App\Services\Assets\AssetPlacementService;
use App\Services\Spatial\SpatialMapService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpatialMapController extends Controller
{
    public function store(
        Request $request,
        Location $location,
        SpatialMapService $maps
    ): RedirectResponse|JsonResponse {
        $this->authorize('create', [SpatialMap::class, $location]);

        $validated = $this->validateUpload($request, true);
        $map = $maps->upload(
            $location,
            $request->file('file'),
            $request->user(),
            SpatialMapType::from($validated['map_type']),
            $this->metadata($validated)
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Spatial map uploaded successfully.',
                'map' => $this->mapSummary($map),
            ], 201);
        }

        return redirect()->route('spatial-maps.show', $map)
            ->with('success', 'Spatial map uploaded successfully.');
    }

    public function show(Request $request, SpatialMap $spatialMap): View|JsonResponse
    {
        $this->authorize('view', $spatialMap);

        $payload = $this->mapPayload($spatialMap, $request);

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return view('spatial-maps.show', [
            'map' => $spatialMap,
            'payload' => $payload,
        ]);
    }

    public function content(SpatialMap $spatialMap): StreamedResponse
    {
        $this->authorize('view', $spatialMap);

        if (! in_array($spatialMap->map_type, [SpatialMapType::IMAGE, SpatialMapType::SVG], true)) {
            abort(404);
        }

        $attachment = $spatialMap->attachment;
        if (! $attachment) {
            abort(404);
        }

        $disk = Storage::disk($attachment->disk ?: 'private');
        if (! $disk->exists($attachment->path)) {
            abort(404);
        }

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function download(
        SpatialMap $spatialMap,
        AttachmentStore $attachments
    ): StreamedResponse {
        $this->authorize('view', $spatialMap);

        if (! $spatialMap->attachment) {
            abort(404);
        }

        return $attachments->retrieve($spatialMap->attachment);
    }

    public function supersede(
        Request $request,
        SpatialMap $spatialMap,
        SpatialMapService $maps
    ): RedirectResponse|JsonResponse {
        $this->authorize('update', $spatialMap);

        $validated = $this->validateUpload($request, false);
        $map = $maps->supersede(
            $spatialMap,
            $request->file('file'),
            $request->user(),
            $this->metadata($validated)
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Spatial map superseded successfully.',
                'map' => $this->mapSummary($map),
            ], 201);
        }

        return redirect()->route('spatial-maps.show', $map)
            ->with('success', 'Spatial map superseded successfully.');
    }

    public function destroy(
        Request $request,
        SpatialMap $spatialMap,
        SpatialMapService $maps
    ): RedirectResponse|JsonResponse {
        $this->authorize('delete', $spatialMap);

        $location = $spatialMap->location;
        $maps->delete($spatialMap);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Spatial map deleted successfully.']);
        }

        return redirect()->route('locations.show', $location)
            ->with('success', 'Spatial map deleted successfully.');
    }

    public function relocate(
        Request $request,
        SpatialMap $spatialMap,
        Asset $asset,
        AssetPlacementService $placements
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $spatialMap);

        $validated = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'position_x' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'position_y' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'position_z' => ['nullable', 'numeric', 'between:-999999.99,999999.99'],
            'rack_start_unit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rack_unit_height' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $destination = $this->mapLocations($spatialMap, $request)
            ->firstWhere('id', (int) $validated['location_id']);

        if (! $destination) {
            abort(404);
        }

        unset($validated['location_id']);
        $placement = $placements->placeAsset(
            $asset,
            $destination,
            $request->user(),
            $validated
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Asset relocated through the placement service.',
                'placement' => $placement->fresh(['location']),
            ]);
        }

        return redirect()->route('spatial-maps.show', $spatialMap)
            ->with('success', 'Asset relocated successfully.');
    }

    /** @return array<string, mixed> */
    private function validateUpload(Request $request, bool $requireType): array
    {
        return $request->validate([
            'file' => ['required', 'file', 'max:'.SpatialMapService::MAX_3D_KILOBYTES],
            'map_type' => [$requireType ? 'required' : 'sometimes', Rule::enum(SpatialMapType::class)],
            'checksum' => ['nullable', 'string', 'regex:/\A[a-fA-F0-9]{64}\z/'],
            'width' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'height' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'scale' => ['nullable', 'numeric', 'gt:0', 'max:999999999'],
            'origin_x' => ['nullable', 'numeric', 'between:-999999999,999999999'],
            'origin_y' => ['nullable', 'numeric', 'between:-999999999,999999999'],
            'origin_z' => ['nullable', 'numeric', 'between:-999999999,999999999'],
            'rotation' => ['nullable', 'numeric', 'between:-360,360'],
            'coordinate_system' => ['nullable', 'string', 'max:64', 'required_with:scale,calibration'],
            'calibration' => ['nullable', 'array', 'max:20'],
            'calibration.control_points' => ['nullable', 'array', 'max:20'],
            'calibration.control_points.*.pixel_x' => ['required_with:calibration.control_points', 'numeric'],
            'calibration.control_points.*.pixel_y' => ['required_with:calibration.control_points', 'numeric'],
            'calibration.control_points.*.local_x' => ['required_with:calibration.control_points', 'numeric'],
            'calibration.control_points.*.local_y' => ['required_with:calibration.control_points', 'numeric'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function metadata(array $validated): array
    {
        unset($validated['file'], $validated['map_type']);

        return $validated;
    }

    /** @return array<string, mixed> */
    private function mapPayload(SpatialMap $map, Request $request): array
    {
        $locations = $this->mapLocations($map, $request);
        $locationIds = $locations->pluck('id');
        $assets = Asset::query()
            ->visibleTo($request->user())
            ->whereIn('location_id', $locationIds)
            ->with('currentPlacement')
            ->orderBy('asset_tag')
            ->get(['id', 'asset_tag', 'name', 'location_id']);

        return [
            'map' => $this->mapSummary($map),
            'locations' => $locations->map(fn (Location $location): array => [
                'id' => $location->id,
                'parent_id' => $location->parent_id,
                'name' => $location->name,
                'code' => $location->code,
                'type' => $location->location_type?->value,
                'is_restricted' => $location->is_restricted,
                'coordinates' => [
                    'x' => $location->getRawOriginal('local_x'),
                    'y' => $location->getRawOriginal('local_y'),
                    'z' => $location->getRawOriginal('local_z'),
                ],
                'pixel' => $this->projectToPixels($map, $location->local_x, $location->local_y),
            ])->values(),
            'assets' => $assets->map(function (Asset $asset) use ($map): array {
                $placement = $asset->currentPlacement;

                return [
                    'id' => $asset->id,
                    'asset_tag' => $asset->asset_tag,
                    'name' => $asset->name,
                    'location_id' => $asset->location_id,
                    'coordinates' => [
                        'x' => $placement?->position_x,
                        'y' => $placement?->position_y,
                        'z' => $placement?->position_z,
                    ],
                    'pixel' => $this->projectToPixels($map, $placement?->position_x, $placement?->position_y),
                ];
            })->values(),
        ];
    }

    /** @return Collection<int, Location> */
    private function mapLocations(SpatialMap $map, Request $request): Collection
    {
        $root = $map->location;

        return Location::query()
            ->visibleTo($request->user())
            ->where('site_id', $root->site_id)
            ->where(function (Builder $query) use ($root): void {
                $query->whereKey($root->id);
                if (filled($root->path)) {
                    $query->orWhere('path', 'like', $root->path.'%');
                }
            })
            ->orderBy('path')
            ->orderBy('name')
            ->get();
    }

    /** @return array<string, mixed> */
    private function mapSummary(SpatialMap $map): array
    {
        return [
            'id' => $map->id,
            'location_id' => $map->location_id,
            'map_type' => $map->map_type->value,
            'version' => $map->version,
            'width' => $map->width,
            'height' => $map->height,
            'scale' => $map->scale,
            'origin_x' => $map->origin_x,
            'origin_y' => $map->origin_y,
            'origin_z' => $map->origin_z,
            'rotation' => $map->rotation,
            'coordinate_system' => $map->coordinate_system,
            'calibration' => $map->calibration,
            'is_current' => $map->is_current,
            'content_url' => in_array($map->map_type, [SpatialMapType::IMAGE, SpatialMapType::SVG], true)
                ? route('spatial-maps.content', $map)
                : null,
            'download_url' => route('spatial-maps.download', $map),
        ];
    }

    /** @return array{x: float, y: float}|null */
    private function projectToPixels(SpatialMap $map, mixed $x, mixed $y): ?array
    {
        if ($x === null || $y === null || ! $map->scale || (float) $map->scale <= 0) {
            return null;
        }

        $x = ((float) $x - (float) ($map->origin_x ?? 0)) / (float) $map->scale;
        $y = ((float) $y - (float) ($map->origin_y ?? 0)) / (float) $map->scale;
        $angle = deg2rad((float) ($map->rotation ?? 0));

        return [
            'x' => round(($x * cos($angle)) + ($y * sin($angle)), 3),
            'y' => round((-$x * sin($angle)) + ($y * cos($angle)), 3),
        ];
    }
}
