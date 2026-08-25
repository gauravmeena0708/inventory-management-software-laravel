<?php

namespace App\Services\Organization;

use App\Models\Asset;
use App\Models\Location;
use App\Models\OrganizationalUnit;
use App\Models\Site;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NdcMigrationReconciler
{
    protected bool $dryRun;
    protected bool $verify;
    
    public function __construct(bool $dryRun = false, bool $verify = false)
    {
        $this->dryRun = $dryRun;
        $this->verify = $verify;
    }

    public function reconcile(): array
    {
        return DB::transaction(function () {
            $stats = [
                'locations_mapped' => 0,
                'assets_backfilled' => 0,
                'users_assigned' => 0,
            ];

            // Get the newly seeded NDC unit and NDC_HQ site
            $ndcUnit = OrganizationalUnit::where('code', 'NDC')->first();
            $ndcHqSite = Site::where('code', 'NDC_HQ')->first();

            if (!$ndcUnit || !$ndcHqSite) {
                throw new \Exception("NDC unit or NDC_HQ site not found. Please run EpfoNdcHierarchySeeder first.");
            }

            // 1. Map existing locations (no parent, no site) to NDC_HQ site
            $locations = Location::whereNull('parent_id')
                ->whereNull('site_id')
                ->get();

            if ($this->verify) {
                Log::info("Found {$locations->count()} locations to map.");
            }

            foreach ($locations as $location) {
                if (!$this->dryRun) {
                    $location->site_id = $ndcHqSite->id;
                    $location->save();
                }
                $stats['locations_mapped']++;
            }

            // 2. Backfill organizational_unit_id on assets
            $assets = Asset::whereNull('organizational_unit_id')->get();
            
            if ($this->verify) {
                Log::info("Found {$assets->count()} assets to backfill.");
            }

            foreach ($assets as $asset) {
                if (!$this->dryRun) {
                    $asset->organizational_unit_id = $ndcUnit->id;
                    $asset->save();
                }
                $stats['assets_backfilled']++;
            }

            // 3. Assign users to NDC unit
            $users = User::all();
            
            if ($this->verify) {
                Log::info("Found {$users->count()} users to assign to NDC unit.");
            }

            foreach ($users as $user) {
                // Determine scopes
                $readScope = 'descendants';
                $writeScope = 'none';

                if (in_array($user->role->value ?? (string)$user->role, [
                    UserRole::ADMIN->value, 
                    UserRole::INVENTORY_MANAGER->value,
                    UserRole::STOCK_OPERATOR->value,
                    UserRole::FINANCE_OPERATOR->value
                ])) {
                    $writeScope = 'descendants';
                }

                if (!$this->dryRun) {
                    // Attach or update pivot
                    $exists = DB::table('organizational_unit_user')
                        ->where('organizational_unit_id', $ndcUnit->id)
                        ->where('user_id', $user->id)
                        ->exists();

                    if (!$exists) {
                        DB::table('organizational_unit_user')->insert([
                            'organizational_unit_id' => $ndcUnit->id,
                            'user_id' => $user->id,
                            'read_scope' => $readScope,
                            'write_scope' => $writeScope,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('organizational_unit_user')
                            ->where('organizational_unit_id', $ndcUnit->id)
                            ->where('user_id', $user->id)
                            ->update([
                                'read_scope' => $readScope,
                                'write_scope' => $writeScope,
                                'updated_at' => now(),
                            ]);
                    }

                    // Set default organizational unit id on user if not set
                    if (is_null($user->default_organizational_unit_id)) {
                        $user->default_organizational_unit_id = $ndcUnit->id;
                        $user->save();
                    }
                }
                $stats['users_assigned']++;
            }

            if ($this->dryRun) {
                DB::rollBack();
            }

            return $stats;
        });
    }
}
