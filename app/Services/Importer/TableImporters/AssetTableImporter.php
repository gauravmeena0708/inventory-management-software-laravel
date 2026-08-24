<?php

namespace App\Services\Importer\TableImporters;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\Official;

class AssetTableImporter extends BaseTableImporter
{
    /**
     * Cache for resolved manufacturers by name.
     *
     * @var array<string, int|null>
     */
    protected array $manufacturerCache = [];

    /**
     * Cache for resolved file records.
     *
     * @var array<string, int|null>
     */
    protected array $fileCache = [];

    /**
     * Execute the asset import routine across all legacy asset tables.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    public function import(?callable $progressCallback = null): array
    {
        $this->importDesktops($progressCallback);
        $this->importLaptops($progressCallback);
        $this->importServers($progressCallback);
        $this->importDevices($progressCallback);
        $this->importStorages($progressCallback);

        return $this->counts;
    }

    /**
     * Import legacy desktops.
     */
    protected function importDesktops(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('desktops')) {
            return;
        }

        $query = $this->legacyQuery('desktops')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $brand = trim($rowArray['brand'] ?? '');
                $category = trim($rowArray['category'] ?? '');
                $serial = trim($rowArray['serial'] ?? '');

                $name = trim("{$brand} {$category}");
                if (blank($name)) {
                    $name = "Desktop #{$legacyId}";
                }

                $manufacturerId = $this->resolveManufacturerId($brand);
                $locationId = $this->validateLocationId($rowArray['location_id'] ?? null, 'desktops', $legacyId);
                $fileRef = $rowArray['file'] ?? null;
                $fileId = $this->resolveFileId($fileRef);

                $active = $rowArray['active'] ?? true;
                $status = ($active === false || $active === 0 || $active === '0')
                    ? AssetStatus::DECOMMISSIONED
                    : AssetStatus::IN_STOCK;

                $specs = array_filter([
                    'category' => $category ?: null,
                    'processor' => $rowArray['processor'] ?? null,
                    'ram' => $rowArray['ram'] ?? null,
                    'hdd' => $rowArray['hdd'] ?? null,
                ], fn ($val) => $val !== null);

                $attributes = [
                    'name' => $name,
                    'asset_type' => AssetType::DESKTOP,
                    'serial_number' => $serial ?: null,
                    'manufacturer_id' => $manufacturerId,
                    'manufacturer_name_legacy' => $brand ?: null,
                    'location_id' => $locationId,
                    'status' => $status,
                    'legacy_file_reference' => $fileRef,
                    'file_id' => $fileId,
                    'purchase_date' => $rowArray['purchased'] ?? null,
                    'specifications' => $specs ?: null,
                    'legacy_payload' => $rowArray,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Asset::on($this->targetConnection)->updateOrCreate(
                    ['legacy_source' => 'desktop', 'legacy_id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('assets_desktop');
                $this->incrementCount('assets');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('desktops', $processed, $total);
            }
        });
    }

    /**
     * Import legacy laptops and create initial assignment history where applicable.
     */
    protected function importLaptops(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('laptops')) {
            return;
        }

        $query = $this->legacyQuery('laptops')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $brand = trim($rowArray['brand'] ?? '');
                $category = trim($rowArray['category'] ?? '');
                $serial = trim($rowArray['serial'] ?? '');

                $name = trim("{$brand} {$category}");
                if (blank($name)) {
                    $name = "Laptop #{$legacyId}";
                }

                $manufacturerId = $this->resolveManufacturerId($brand);
                $officialId = $this->validateOfficialId($rowArray['official_id'] ?? null, 'laptops', $legacyId);
                $fileRef = $rowArray['file'] ?? null;
                $fileId = $this->resolveFileId($fileRef);

                $active = $rowArray['active'] ?? true;
                if ($officialId !== null) {
                    $status = AssetStatus::IN_USE;
                } elseif ($active === false || $active === 0 || $active === '0') {
                    $status = AssetStatus::DECOMMISSIONED;
                } else {
                    $status = AssetStatus::IN_STOCK;
                }

                $specs = array_filter([
                    'category' => $category ?: null,
                    'processor' => $rowArray['processor'] ?? null,
                    'ram' => $rowArray['ram'] ?? null,
                    'hdd' => $rowArray['hdd'] ?? null,
                ], fn ($val) => $val !== null);

                $attributes = [
                    'name' => $name,
                    'asset_type' => AssetType::LAPTOP,
                    'serial_number' => $serial ?: null,
                    'manufacturer_id' => $manufacturerId,
                    'manufacturer_name_legacy' => $brand ?: null,
                    'assigned_official_id' => $officialId,
                    'status' => $status,
                    'legacy_file_reference' => $fileRef,
                    'file_id' => $fileId,
                    'purchase_date' => $rowArray['purchased'] ?? null,
                    'specifications' => $specs ?: null,
                    'legacy_payload' => $rowArray,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                $asset = Asset::on($this->targetConnection)->updateOrCreate(
                    ['legacy_source' => 'laptop', 'legacy_id' => $legacyId],
                    $attributes
                );

                // Create initial assignment record if laptop is assigned to an official
                if ($officialId !== null) {
                    $existingAssignment = AssetAssignment::on($this->targetConnection)
                        ->where('asset_id', $asset->id)
                        ->where('official_id', $officialId)
                        ->whereNull('returned_at')
                        ->first();

                    if (!$existingAssignment) {
                        AssetAssignment::on($this->targetConnection)->create([
                            'asset_id' => $asset->id,
                            'official_id' => $officialId,
                            'assigned_by' => null,
                            'assigned_at' => $asset->purchase_date ? $asset->purchase_date->startOfDay() : ($asset->created_at ?? now()),
                            'returned_at' => null,
                            'source' => 'legacy_import',
                            'remarks' => 'Imported initial assignment from legacy laptop record',
                        ]);
                        $this->incrementCount('asset_assignments');
                    }
                }

                $this->incrementCount('assets_laptop');
                $this->incrementCount('assets');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('laptops', $processed, $total);
            }
        });
    }

    /**
     * Import legacy servers.
     */
    protected function importServers(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('servers')) {
            return;
        }

        $query = $this->legacyQuery('servers')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $description = trim($rowArray['description'] ?? '');
                $serial = trim($rowArray['asset_sr'] ?? ($rowArray['serial'] ?? ''));
                $assetTag = trim($rowArray['asset_tag'] ?? '');

                $name = $description ?: ($assetTag ?: "Server #{$legacyId}");

                $specs = array_filter([
                    'support_covered' => $rowArray['covered'] ?? null,
                    'address' => $rowArray['address'] ?? null,
                    'city' => $rowArray['city'] ?? null,
                    'state' => $rowArray['state'] ?? null,
                    'country' => $rowArray['country'] ?? null,
                    'terms' => $rowArray['terms'] ?? null,
                    'duplicated' => $rowArray['duplicated'] ?? null,
                    'timeout' => $rowArray['timeout'] ?? null,
                ], fn ($val) => $val !== null);

                $attributes = [
                    'name' => $name,
                    'asset_type' => AssetType::SERVER,
                    'serial_number' => $serial ?: null,
                    'asset_tag' => $assetTag ?: null,
                    'location_text_legacy' => $rowArray['location'] ?? null,
                    'description' => $description ?: null,
                    'purchase_date' => $rowArray['purchase_date'] ?? null,
                    'purchase_cost' => isset($rowArray['purchase_cost']) ? (float) $rowArray['purchase_cost'] : null,
                    'end_of_sale' => $rowArray['end_of_sale'] ?? null,
                    'end_of_support' => $rowArray['end_of_support'] ?? null,
                    'amc_end' => $rowArray['contract_expiry'] ?? null,
                    'amc_cost' => isset($rowArray['amc_cost']) ? (float) $rowArray['amc_cost'] : null,
                    'contract_type' => $rowArray['contract_type'] ?? null,
                    'contract_reference' => $rowArray['contracthash'] ?? null,
                    'status' => AssetStatus::IN_USE,
                    'specifications' => $specs ?: null,
                    'legacy_payload' => $rowArray,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Asset::on($this->targetConnection)->updateOrCreate(
                    ['legacy_source' => 'server', 'legacy_id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('assets_server');
                $this->incrementCount('assets');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('servers', $processed, $total);
            }
        });
    }

    /**
     * Import legacy devices (switches / network hardware).
     */
    protected function importDevices(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('devices')) {
            return;
        }

        $query = $this->legacyQuery('devices')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $oem = trim($rowArray['oem'] ?? '');
                $partcode = trim($rowArray['partcode'] ?? '');
                $serial = trim($rowArray['serial'] ?? '');

                $name = trim("{$oem} Switch" . ($partcode ? " ({$partcode})" : ''));
                if (blank($name)) {
                    $name = "Device #{$legacyId}";
                }

                $manufacturerId = $this->resolveManufacturerId($oem);
                $locationText = trim(($rowArray['location'] ?? '') . ' ' . ($rowArray['sublocation'] ?? ''));

                $specs = array_filter([
                    'oem' => $oem ?: null,
                    'sublocation' => $rowArray['sublocation'] ?? null,
                ], fn ($val) => $val !== null);

                $attributes = [
                    'name' => $name,
                    'asset_type' => AssetType::SWITCH,
                    'serial_number' => $serial ?: null,
                    'part_code' => $partcode ?: null,
                    'manufacturer_id' => $manufacturerId,
                    'manufacturer_name_legacy' => $oem ?: null,
                    'location_text_legacy' => $locationText ?: null,
                    'purchase_date' => $rowArray['purchase_date'] ?? null,
                    'purchase_cost' => isset($rowArray['cost']) ? (float) $rowArray['cost'] : null,
                    'end_of_support' => $rowArray['support_date'] ?? null,
                    'amc_start' => $rowArray['amc_start'] ?? null,
                    'amc_end' => $rowArray['amc_end'] ?? null,
                    'amc_cost' => isset($rowArray['amc_cost']) ? (float) $rowArray['amc_cost'] : null,
                    'remarks' => $rowArray['remark'] ?? null,
                    'status' => AssetStatus::IN_USE,
                    'specifications' => $specs ?: null,
                    'legacy_payload' => $rowArray,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Asset::on($this->targetConnection)->updateOrCreate(
                    ['legacy_source' => 'device', 'legacy_id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('assets_device');
                $this->incrementCount('assets');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('devices', $processed, $total);
            }
        });
    }

    /**
     * Import legacy storages.
     */
    protected function importStorages(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('storages')) {
            return;
        }

        $query = $this->legacyQuery('storages')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $legacyId = (int) $rowArray['id'];
                $description = trim($rowArray['description'] ?? '');
                $serial = trim($rowArray['serial'] ?? '');
                $type = trim($rowArray['type'] ?? '');

                $name = $description ?: "Storage #{$legacyId}";
                $locationId = $this->validateLocationId($rowArray['location_id'] ?? null, 'storages', $legacyId);

                $specs = array_filter([
                    'storage_type' => $type ?: null,
                ], fn ($val) => $val !== null);

                $attributes = [
                    'name' => $name,
                    'asset_type' => AssetType::STORAGE,
                    'serial_number' => $serial ?: null,
                    'description' => $description ?: null,
                    'location_id' => $locationId,
                    'location_text_legacy' => $rowArray['location'] ?? null,
                    'warranty_expiry' => $rowArray['warranty_end'] ?? null,
                    'status' => AssetStatus::IN_USE,
                    'specifications' => $specs ?: null,
                    'legacy_payload' => $rowArray,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Asset::on($this->targetConnection)->updateOrCreate(
                    ['legacy_source' => 'storage', 'legacy_id' => $legacyId],
                    $attributes
                );

                $this->incrementCount('assets_storage');
                $this->incrementCount('assets');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('storages', $processed, $total);
            }
        });
    }

    /**
     * Resolve manufacturer ID from name.
     */
    protected function resolveManufacturerId(?string $brand): ?int
    {
        if (blank($brand)) {
            return null;
        }

        $key = strtolower(trim($brand));
        if (array_key_exists($key, $this->manufacturerCache)) {
            return $this->manufacturerCache[$key];
        }

        $manufacturer = Manufacturer::on($this->targetConnection)
            ->where('name', $brand)
            ->orWhere('name', 'like', $brand)
            ->first();

        $id = $manufacturer?->id;
        $this->manufacturerCache[$key] = $id;

        return $id;
    }

    /**
     * Validate location ID reference.
     */
    protected function validateLocationId(?int $locationId, string $table, int $recordId): ?int
    {
        if ($locationId === null) {
            return null;
        }

        $exists = Location::on($this->targetConnection)->where('id', $locationId)->exists();
        if (!$exists) {
            $this->recordAnomaly(
                $table,
                'missing_foreign_key',
                "{$table} #{$recordId} references non-existent location ID {$locationId}.",
                ['table' => $table, 'id' => $recordId, 'location_id' => $locationId]
            );
            return null;
        }

        return $locationId;
    }

    /**
     * Validate official ID reference.
     */
    protected function validateOfficialId(?int $officialId, string $table, int $recordId): ?int
    {
        if ($officialId === null) {
            return null;
        }

        $exists = Official::on($this->targetConnection)->where('id', $officialId)->exists();
        if (!$exists) {
            $this->recordAnomaly(
                $table,
                'missing_foreign_key',
                "{$table} #{$recordId} references non-existent official ID {$officialId}.",
                ['table' => $table, 'id' => $recordId, 'official_id' => $officialId]
            );
            return null;
        }

        return $officialId;
    }

    /**
     * Resolve file registry record ID from reference.
     */
    protected function resolveFileId(?string $fileRef): ?int
    {
        if (blank($fileRef)) {
            return null;
        }

        $key = trim($fileRef);
        if (array_key_exists($key, $this->fileCache)) {
            return $this->fileCache[$key];
        }

        $fileRecord = null;
        if (is_numeric($key)) {
            $fileRecord = FileRecord::on($this->targetConnection)
                ->where('id', (int) $key)
                ->orWhere('legacy_id', (int) $key)
                ->first();
        }

        if (!$fileRecord) {
            $fileRecord = FileRecord::on($this->targetConnection)
                ->where('name', $key)
                ->orWhere('physical_name', $key)
                ->orWhere('efile_number', $key)
                ->first();
        }

        $id = $fileRecord?->id;
        $this->fileCache[$key] = $id;

        return $id;
    }
}
