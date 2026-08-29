<?php

namespace App\Services\Bulk;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Enums\LifecycleEventType;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Services\Assets\RecordLifecycleEventAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BulkAssetImportService
{
    public function __construct(
        private readonly RecordLifecycleEventAction $recordLifecycleEvent
    ) {}

    /**
     * Validate an array of row payloads for bulk asset creation.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{valid: bool, errors: array<int, array<string, string>>}
     */
    public function validateRows(array $rows, OrganizationalUnit $unit): array
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:255'],
                'asset_tag' => ['required', 'string', 'max:100'],
                'serial_number' => ['nullable', 'string', 'max:191'],
                'category_code' => ['nullable', 'string', 'exists:asset_categories,code'],
                'purchase_cost' => ['nullable', 'numeric', 'min:0'],
                'purchase_date' => ['nullable', 'date'],
                'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            ]);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors()->toArray();
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Process validated rows and import assets in a database transaction with a full reconciliation report.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{total: int, created: int, skipped: int, errors: array<int, string>, assets: array<int, int>}
     */
    public function import(array $rows, OrganizationalUnit $unit, User $actor): array
    {
        return DB::transaction(function () use ($rows, $unit, $actor) {
            $createdIds = [];
            $skipped = 0;
            $errors = [];

            // Pre-load category map
            $categoriesByCode = AssetCategory::all()->keyBy('code');

            foreach ($rows as $index => $row) {
                $assetTag = trim($row['asset_tag'] ?? '');

                if (empty($assetTag)) {
                    $errors[$index] = 'Missing required asset tag.';
                    $skipped++;
                    continue;
                }

                // Check for duplicate tag in unit
                $existing = Asset::where('asset_tag', $assetTag)->first();
                if ($existing) {
                    $errors[$index] = "Asset with tag '{$assetTag}' already exists (ID: {$existing->id}).";
                    $skipped++;
                    continue;
                }

                $category = isset($row['category_code']) ? $categoriesByCode->get($row['category_code']) : null;

                $asset = Asset::create([
                    'organizational_unit_id' => $unit->id,
                    'name' => $row['name'],
                    'asset_tag' => $assetTag,
                    'serial_number' => $row['serial_number'] ?? null,
                    'asset_category_id' => $category?->id,
                    'asset_type' => $row['asset_type'] ?? AssetType::OTHER->value,
                    'location_id' => $row['location_id'] ?? null,
                    'purchase_cost' => $row['purchase_cost'] ?? null,
                    'purchase_date' => $row['purchase_date'] ?? null,
                    'status' => AssetStatus::IN_STOCK,
                    'remarks' => $row['remarks'] ?? 'Bulk imported via CSV.',
                ]);

                // Record REGISTERED lifecycle event
                $this->recordLifecycleEvent->execute(
                    $asset,
                    LifecycleEventType::REGISTERED,
                    $actor,
                    [
                        'to_status' => AssetStatus::IN_STOCK,
                        'to_organizational_unit_id' => $unit->id,
                        'reference_type' => 'BulkAssetImport',
                        'remarks' => "Bulk import row #{$index}",
                        'metadata' => [
                            'import_row' => $index,
                            'asset_tag' => $assetTag,
                        ],
                    ]
                );

                $createdIds[] = $asset->id;
            }

            return [
                'total' => count($rows),
                'created' => count($createdIds),
                'skipped' => $skipped,
                'errors' => $errors,
                'assets' => $createdIds,
            ];
        });
    }
}
