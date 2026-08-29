<?php

namespace App\Services\Spatial;

use App\Contracts\AttachmentStore;
use App\Enums\SpatialMapType;
use App\Models\Attachment;
use App\Models\Location;
use App\Models\SpatialMap;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class SpatialMapService
{
    public const MAX_2D_KILOBYTES = 20 * 1024;

    public const MAX_3D_KILOBYTES = 100 * 1024;

    public function __construct(private readonly AttachmentStore $attachments) {}

    /**
     * Create the first current version for a location and map type.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function upload(
        Location $location,
        UploadedFile $file,
        User $user,
        SpatialMapType $type,
        array $metadata
    ): SpatialMap {
        return $this->createVersion($location, $file, $user, $type, $metadata);
    }

    /**
     * Replace the current map while retaining the previous version and file.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function supersede(
        SpatialMap $map,
        UploadedFile $file,
        User $user,
        array $metadata
    ): SpatialMap {
        return $this->createVersion(
            $map->location,
            $file,
            $user,
            $map->map_type,
            $metadata,
            $map
        );
    }

    public function delete(SpatialMap $map): void
    {
        DB::transaction(function () use ($map): void {
            $lockedMap = SpatialMap::query()->lockForUpdate()->findOrFail($map->getKey());
            $attachment = $lockedMap->attachment;

            $lockedMap->delete();

            if ($attachment) {
                $this->attachments->delete($attachment);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function createVersion(
        Location $location,
        UploadedFile $file,
        User $user,
        SpatialMapType $type,
        array $metadata,
        ?SpatialMap $superseded = null
    ): SpatialMap {
        $metadata = $this->validateFileAndMetadata($file, $type, $metadata);
        $storedAttachment = null;

        try {
            return DB::transaction(function () use (
                $location,
                $file,
                $user,
                $type,
                $metadata,
                $superseded,
                &$storedAttachment
            ): SpatialMap {
                $lockedLocation = Location::query()->lockForUpdate()->findOrFail($location->getKey());
                $current = SpatialMap::query()
                    ->where('location_id', $lockedLocation->id)
                    ->where('map_type', $type->value)
                    ->current()
                    ->lockForUpdate()
                    ->first();

                if ($superseded === null && $current) {
                    throw ValidationException::withMessages([
                        'map' => 'A current map of this type already exists. Use the supersede endpoint.',
                    ]);
                }

                if ($superseded !== null && (! $current || $current->id !== $superseded->id)) {
                    throw ValidationException::withMessages([
                        'map' => 'Only the current map version may be superseded.',
                    ]);
                }

                $version = ((int) SpatialMap::withTrashed()
                    ->where('location_id', $lockedLocation->id)
                    ->where('map_type', $type->value)
                    ->max('version')) + 1;

                // AttachmentStore owns all private-file writes. It is initially
                // attached to the location, then re-parented to the persisted map
                // in this transaction to satisfy both non-null foreign keys.
                $storedAttachment = $this->attachments->store($file, $lockedLocation, $user, 'private');

                $map = SpatialMap::create([
                    'location_id' => $lockedLocation->id,
                    'attachment_id' => $storedAttachment->id,
                    'map_type' => $type,
                    'version' => $version,
                    'width' => $metadata['width'] ?? null,
                    'height' => $metadata['height'] ?? null,
                    'scale' => $metadata['scale'] ?? null,
                    'origin_x' => $metadata['origin_x'] ?? null,
                    'origin_y' => $metadata['origin_y'] ?? null,
                    'origin_z' => $metadata['origin_z'] ?? null,
                    'rotation' => $metadata['rotation'] ?? null,
                    'coordinate_system' => $metadata['coordinate_system'] ?? null,
                    'calibration' => $metadata['calibration'] ?? null,
                    'is_current' => true,
                    'uploaded_by' => $user->id,
                ]);

                $storedAttachment->update([
                    'attachable_type' => $map->getMorphClass(),
                    'attachable_id' => $map->id,
                    'mime_type' => $this->normalizedMimeType($file, $type),
                ]);

                if ($current) {
                    $current->update(['is_current' => false]);
                }

                return $map->fresh(['location', 'uploadedBy']);
            }, 3);
        } catch (Throwable $exception) {
            // Database rollback cannot roll back a filesystem write.
            if ($storedAttachment instanceof Attachment) {
                $this->attachments->delete($storedAttachment);
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function validateFileAndMetadata(
        UploadedFile $file,
        SpatialMapType $type,
        array $metadata
    ): array {
        if (! $file->isValid()) {
            throw ValidationException::withMessages(['file' => 'The map upload is not valid.']);
        }

        $maxBytes = ($type === SpatialMapType::THREE_D_MODEL
            ? self::MAX_3D_KILOBYTES
            : self::MAX_2D_KILOBYTES) * 1024;

        if (($file->getSize() ?: 0) > $maxBytes) {
            throw ValidationException::withMessages(['file' => 'The map file exceeds the permitted size.']);
        }

        $path = $file->getRealPath();
        $contents = $path && is_file($path) ? file_get_contents($path) : $file->getContent();

        if (! is_string($contents)) {
            throw ValidationException::withMessages(['file' => 'The map file could not be inspected.']);
        }

        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));
        $extension = strtolower($file->getClientOriginalExtension());

        match ($type) {
            SpatialMapType::IMAGE => $this->validateRaster($contents, $mime, $metadata),
            SpatialMapType::SVG => $this->validateSvg($contents, $mime, $extension),
            SpatialMapType::GEOJSON => $this->validateGeoJson($contents, $mime, $extension),
            SpatialMapType::THREE_D_MODEL => $this->validateThreeDimensionalModel($contents, $mime, $extension),
        };

        $checksum = hash('sha256', $contents);
        if (isset($metadata['checksum']) && ! hash_equals(strtolower((string) $metadata['checksum']), $checksum)) {
            throw ValidationException::withMessages(['checksum' => 'The supplied checksum does not match the uploaded file.']);
        }

        if ($type === SpatialMapType::IMAGE) {
            $dimensions = @getimagesizefromstring($contents);
            if ($dimensions !== false) {
                $metadata['width'] = $dimensions[0];
                $metadata['height'] = $dimensions[1];
            }
        }

        unset($metadata['checksum']);

        return $metadata;
    }

    /** @param array<string, mixed> $metadata */
    private function validateRaster(string $contents, string $mime, array $metadata): void
    {
        $dimensions = @getimagesizefromstring($contents);
        $permitted = ['image/jpeg', 'image/png', 'image/webp'];

        if ($dimensions === false || ! in_array($mime, $permitted, true)) {
            throw ValidationException::withMessages(['file' => 'Raster maps must be valid JPEG, PNG, or WebP images.']);
        }

        if (isset($metadata['width']) && (int) $metadata['width'] !== $dimensions[0]) {
            throw ValidationException::withMessages(['width' => 'The supplied width does not match the image.']);
        }

        if (isset($metadata['height']) && (int) $metadata['height'] !== $dimensions[1]) {
            throw ValidationException::withMessages(['height' => 'The supplied height does not match the image.']);
        }
    }

    private function validateSvg(string $contents, string $mime, string $extension): void
    {
        $isSvg = $extension === 'svg'
            && in_array($mime, ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'], true)
            && preg_match('/<svg\b/i', $contents) === 1;
        $unsafe = preg_match('/<(?:script|foreignObject|iframe|object|embed)\b|\bon[a-z]+\s*=|(?:href|src)\s*=\s*["\']\s*(?:https?:|\/\/|data:)|<!DOCTYPE|<!ENTITY/i', $contents) === 1;

        if (! $isSvg || $unsafe) {
            throw ValidationException::withMessages(['file' => 'SVG maps must be valid and must not contain executable or external content.']);
        }
    }

    private function validateGeoJson(string $contents, string $mime, string $extension): void
    {
        if (! in_array($extension, ['geojson', 'json'], true)
            || ! in_array($mime, ['application/json', 'application/geo+json', 'text/plain'], true)) {
            throw ValidationException::withMessages(['file' => 'GeoJSON maps must be JSON or GeoJSON files.']);
        }

        try {
            $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['file' => 'The GeoJSON map is not valid JSON.']);
        }

        $types = ['FeatureCollection', 'Feature', 'GeometryCollection', 'Polygon', 'MultiPolygon'];
        if (! is_array($data) || ! in_array($data['type'] ?? null, $types, true)) {
            throw ValidationException::withMessages(['file' => 'The GeoJSON map must contain a supported GeoJSON object.']);
        }
    }

    private function validateThreeDimensionalModel(string $contents, string $mime, string $extension): void
    {
        $permittedMimes = [
            'model/gltf+json',
            'model/gltf-binary',
            'application/json',
            'application/octet-stream',
            'text/plain',
        ];

        if (! in_array($extension, ['gltf', 'glb'], true) || ! in_array($mime, $permittedMimes, true)) {
            throw ValidationException::withMessages(['file' => 'Reserved 3D maps must be valid glTF or GLB files.']);
        }

        if ($extension === 'glb' && ! str_starts_with($contents, 'glTF')) {
            throw ValidationException::withMessages(['file' => 'The GLB file header is invalid.']);
        }

        if ($extension === 'gltf') {
            try {
                $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw ValidationException::withMessages(['file' => 'The glTF model is not valid JSON.']);
            }

            if (! is_array($data) || ! isset($data['asset']['version'])) {
                throw ValidationException::withMessages(['file' => 'The glTF model metadata is missing.']);
            }
        }
    }

    private function normalizedMimeType(UploadedFile $file, SpatialMapType $type): string
    {
        return match ($type) {
            SpatialMapType::IMAGE => strtolower((string) $file->getMimeType()),
            SpatialMapType::SVG => 'image/svg+xml',
            SpatialMapType::GEOJSON => 'application/geo+json',
            SpatialMapType::THREE_D_MODEL => strtolower($file->getClientOriginalExtension()) === 'glb'
                ? 'model/gltf-binary'
                : 'model/gltf+json',
        };
    }
}
