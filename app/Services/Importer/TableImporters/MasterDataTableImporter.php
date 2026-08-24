<?php

namespace App\Services\Importer\TableImporters;

use App\Models\Devcat;
use App\Models\FileRecord;
use App\Models\Location;
use App\Models\Manufacturer;

class MasterDataTableImporter extends BaseTableImporter
{
    /**
     * Execute the master data import routine.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    public function import(?callable $progressCallback = null): array
    {
        $this->importLocations($progressCallback);
        $this->importManufacturers($progressCallback);
        $this->importDevcats($progressCallback);
        $this->importFiles($progressCallback);

        return $this->counts;
    }

    /**
     * Import legacy locations.
     */
    protected function importLocations(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('locations')) {
            return;
        }

        $query = $this->legacyQuery('locations')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $seat = $rowArray['seat'] ?? null;
                $point = $rowArray['point'] ?? null;
                $floor = $rowArray['floor'] ?? null;
                $pin = $rowArray['pin'] ?? null;

                $name = trim(($seat ?? '') . ($point ? " ({$point})" : ''));
                if (blank($name)) {
                    $name = "Location #{$rowArray['id']}";
                }

                $attributes = [
                    'name' => $name,
                    'sublocation' => $point ?: $seat,
                    'floor' => $floor ? (string) $floor : null,
                    'seat' => $seat,
                    'point' => $point,
                    'pin' => $pin,
                    'description' => $pin ? "PIN: {$pin}" : null,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Location::on($this->targetConnection)->updateOrCreate(
                    ['id' => $rowArray['id']],
                    $attributes
                );

                $this->incrementCount('locations');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('locations', $processed, $total);
            }
        });
    }

    /**
     * Import legacy manufacturers.
     */
    protected function importManufacturers(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('manufacturers')) {
            return;
        }

        $query = $this->legacyQuery('manufacturers')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $this->recordAnomaly('manufacturers', 'blank_name', "Manufacturer #{$rowArray['id']} has no name.", $rowArray);
                    $name = "Manufacturer #{$rowArray['id']}";
                }

                $supportContact = $rowArray['link1'] ?? ($rowArray['link2'] ?? ($rowArray['link3'] ?? null));

                $attributes = [
                    'name' => $name,
                    'website' => $rowArray['website'] ?? null,
                    'support_contact' => $supportContact,
                    'address' => $rowArray['address'] ?? null,
                    'city' => $rowArray['city'] ?? null,
                    'state' => $rowArray['state'] ?? null,
                    'pin' => $rowArray['pin'] ?? null,
                    'description' => $rowArray['description'] ?? null,
                    'link1' => $rowArray['link1'] ?? null,
                    'link2' => $rowArray['link2'] ?? null,
                    'link3' => $rowArray['link3'] ?? null,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Manufacturer::on($this->targetConnection)->updateOrCreate(
                    ['id' => $rowArray['id']],
                    $attributes
                );

                $this->incrementCount('manufacturers');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('manufacturers', $processed, $total);
            }
        });
    }

    /**
     * Import legacy devcats (developer categories).
     */
    protected function importDevcats(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('devcats')) {
            return;
        }

        $query = $this->legacyQuery('devcats')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $name = "Category #{$rowArray['id']}";
                }

                $attributes = [
                    'name' => $name,
                    'salary' => isset($rowArray['salary']) ? (string) $rowArray['salary'] : null,
                    'exp' => isset($rowArray['exp']) ? (string) $rowArray['exp'] : null,
                    'dev' => isset($rowArray['dev']) ? (int) $rowArray['dev'] : null,
                    'collab' => isset($rowArray['collab']) ? (int) $rowArray['collab'] : null,
                    'qualification' => $rowArray['qualification'] ?? null,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Devcat::on($this->targetConnection)->updateOrCreate(
                    ['id' => $rowArray['id']],
                    $attributes
                );

                $this->incrementCount('devcats');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('devcats', $processed, $total);
            }
        });
    }

    /**
     * Import legacy file registry records.
     */
    protected function importFiles(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('files')) {
            return;
        }

        $query = $this->legacyQuery('files')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $name = "File #{$rowArray['id']}";
                }

                $attributes = [
                    'name' => $name,
                    'legacy_id' => $rowArray['id'],
                    'efile_number' => isset($rowArray['efile']) ? (string) $rowArray['efile'] : ($rowArray['efile_number'] ?? null),
                    'physical_name' => $rowArray['physical'] ?? ($rowArray['physical_name'] ?? null),
                    'physical_number' => isset($rowArray['pnumber']) ? (string) $rowArray['pnumber'] : ($rowArray['physical_number'] ?? null),
                    'subject' => $rowArray['subject'] ?? null,
                    'division' => $rowArray['division'] ?? null,
                    'opened_at' => $rowArray['opened'] ?? ($rowArray['opened_at'] ?? null),
                    'efile' => $rowArray['efile'] ?? null,
                    'physical' => $rowArray['physical'] ?? null,
                    'pnumber' => $rowArray['pnumber'] ?? null,
                    'opened' => $rowArray['opened'] ?? null,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                FileRecord::on($this->targetConnection)->updateOrCreate(
                    ['legacy_id' => $rowArray['id']],
                    $attributes
                );

                $this->incrementCount('files');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('files', $processed, $total);
            }
        });
    }
}
