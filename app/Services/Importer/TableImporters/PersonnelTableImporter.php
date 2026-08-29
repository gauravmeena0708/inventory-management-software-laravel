<?php

namespace App\Services\Importer\TableImporters;

use App\Enums\UserRole;
use App\Models\Devcat;
use App\Models\Developer;
use App\Models\Location;
use App\Models\Official;
use App\Models\User;

class PersonnelTableImporter extends BaseTableImporter
{
    /**
     * Execute the personnel import routine.
     *
     * @param (callable(string $stage, int $processed, int $total): void)|null $progressCallback
     * @return array<string, int>
     */
    public function import(?callable $progressCallback = null): array
    {
        $this->importUsers($progressCallback);
        $this->importOfficials($progressCallback);
        $this->importDevelopers($progressCallback);

        return $this->counts;
    }

    /**
     * Import legacy users, assigning viewer role.
     */
    protected function importUsers(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('users')) {
            return;
        }

        $query = $this->legacyQuery('users')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $email = strtolower(trim($rowArray['email'] ?? ''));

                if (blank($email)) {
                    $this->recordAnomaly('users', 'missing_email', "Legacy user #{$rowArray['id']} has no email address.", $rowArray);
                    continue;
                }

                $existingUser = User::on($this->targetConnection)->where('email', $email)->first();

                if ($existingUser) {
                    // Update user without overwriting existing role
                    $existingUser->update([
                        'name' => $rowArray['name'] ?? $existingUser->name,
                    ]);
                } else {
                    $user = new User();
                    $user->setConnection($this->targetConnection);
                    $user->id = $rowArray['id'] ?? null;
                    $user->name = $rowArray['name'] ?? 'Imported User';
                    $user->email = $email;
                    // Preserve original hashed password directly
                    $user->setRawAttributes(array_merge($user->getAttributes(), [
                        'password' => $rowArray['password'] ?? bcrypt(str()->random(32)),
                    ]));
                    $user->role = UserRole::VIEWER;
                    if (isset($rowArray['created_at'])) {
                        $user->created_at = $rowArray['created_at'];
                    }
                    if (isset($rowArray['updated_at'])) {
                        $user->updated_at = $rowArray['updated_at'];
                    }
                    $user->save();
                }

                $this->incrementCount('users');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('users', $processed, $total);
            }
        });
    }

    /**
     * Import legacy officials and validate location references.
     */
    protected function importOfficials(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('officials')) {
            return;
        }

        $query = $this->legacyQuery('officials')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $name = "Official #{$rowArray['id']}";
                }

                $locationId = $rowArray['location_id'] ?? null;
                if ($locationId !== null) {
                    $locationExists = Location::on($this->targetConnection)->where('id', $locationId)->exists();
                    if (!$locationExists) {
                        $this->recordAnomaly(
                            'officials',
                            'missing_foreign_key',
                            "Official #{$rowArray['id']} references missing location ID {$locationId}.",
                            ['official_id' => $rowArray['id'], 'location_id' => $locationId]
                        );
                        $locationId = null;
                    }
                }

                $attributes = [
                    'name' => $name,
                    'title' => $rowArray['title'] ?? null,
                    'designation' => $rowArray['designation'] ?? null,
                    'department' => $rowArray['department'] ?? null,
                    'email' => $rowArray['email'] ?? null,
                    'phone' => $rowArray['phone'] ?? null,
                    'location_id' => $locationId,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Official::on($this->targetConnection)->updateOrCreate(
                    ['id' => $rowArray['id']],
                    $attributes
                );

                $this->incrementCount('officials');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('officials', $processed, $total);
            }
        });
    }

    /**
     * Import legacy developers with encrypted profile fields.
     */
    protected function importDevelopers(?callable $progressCallback = null): void
    {
        if (!$this->hasLegacyTable('developers')) {
            return;
        }

        $query = $this->legacyQuery('developers')->orderBy('id');
        $total = $query->count();
        $processed = 0;

        $query->chunk($this->chunkSize, function ($rows) use (&$processed, $total, $progressCallback) {
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $name = trim($rowArray['name'] ?? '');
                if (blank($name)) {
                    $name = "Developer #{$rowArray['id']}";
                }

                $categoryId = $rowArray['category_id'] ?? null;
                if ($categoryId !== null) {
                    $categoryExists = Devcat::on($this->targetConnection)->where('id', $categoryId)->exists();
                    if (!$categoryExists) {
                        $this->recordAnomaly(
                            'developers',
                            'missing_foreign_key',
                            "Developer #{$rowArray['id']} references missing category ID {$categoryId}.",
                            ['developer_id' => $rowArray['id'], 'category_id' => $categoryId]
                        );
                        $categoryId = null;
                    }
                }

                $reportingId = $rowArray['reporting_id'] ?? null;
                if ($reportingId !== null) {
                    $userExists = User::on($this->targetConnection)->where('id', $reportingId)->exists();
                    if (!$userExists) {
                        $this->recordAnomaly(
                            'developers',
                            'missing_foreign_key',
                            "Developer #{$rowArray['id']} references missing reporting user ID {$reportingId}.",
                            ['developer_id' => $rowArray['id'], 'reporting_id' => $reportingId]
                        );
                        $reportingId = null;
                    }
                }

                $rawStatus = $rowArray['status'] ?? null;
                $status = ($rawStatus === 1 || $rawStatus === '1' || $rawStatus === true || $rawStatus === 'active')
                    ? 'active'
                    : 'discontinued';

                $remarks = $rowArray['remark'] ?? ($rowArray['remarks'] ?? null);

                $attributes = [
                    'name' => $name,
                    'reporting_id' => $reportingId,
                    'category_id' => $categoryId,
                    'phone' => $rowArray['phone'] ?? null,
                    'email' => $rowArray['email'] ?? null,
                    'salary' => isset($rowArray['salary']) ? (string) $rowArray['salary'] : null,
                    'status' => $status,
                    'remarks' => $remarks,
                ];

                if (isset($rowArray['created_at'])) {
                    $attributes['created_at'] = $rowArray['created_at'];
                }
                if (isset($rowArray['updated_at'])) {
                    $attributes['updated_at'] = $rowArray['updated_at'];
                }

                Developer::on($this->targetConnection)->updateOrCreate(
                    ['id' => $rowArray['id']],
                    $attributes
                );

                $this->incrementCount('developers');
                $processed++;
            }

            if ($progressCallback) {
                $progressCallback('developers', $processed, $total);
            }
        });
    }
}
