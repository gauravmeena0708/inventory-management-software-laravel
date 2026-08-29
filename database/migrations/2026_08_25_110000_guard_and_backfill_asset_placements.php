<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_placements', function (Blueprint $table) {
            $table->boolean('open_marker')->nullable()->after('removed_at');
            $table->string('source', 32)->default('application')->after('placed_by');
        });

        $now = now();

        DB::table('asset_placements')
            ->whereNotNull('removed_at')
            ->update(['open_marker' => null]);

        DB::table('asset_placements')
            ->whereNull('removed_at')
            ->select('asset_id')
            ->distinct()
            ->orderBy('asset_id')
            ->chunkById(500, function ($assetRows) use ($now): void {
                foreach ($assetRows as $assetRow) {
                    $openIds = DB::table('asset_placements')
                        ->where('asset_id', $assetRow->asset_id)
                        ->whereNull('removed_at')
                        ->orderByDesc('placed_at')
                        ->orderByDesc('id')
                        ->pluck('id');

                    $currentId = $openIds->shift();

                    if ($currentId) {
                        DB::table('asset_placements')
                            ->where('id', $currentId)
                            ->update(['open_marker' => true]);
                    }

                    if ($openIds->isNotEmpty()) {
                        DB::table('asset_placements')
                            ->whereIn('id', $openIds)
                            ->update([
                                'removed_at' => $now,
                                'open_marker' => null,
                            ]);
                    }
                }
            }, 'asset_id');

        DB::table('assets')
            ->whereNotNull('location_id')
            ->orderBy('id')
            ->chunkById(500, function ($assets) use ($now): void {
                foreach ($assets as $asset) {
                    $hasCurrentPlacement = DB::table('asset_placements')
                        ->where('asset_id', $asset->id)
                        ->where('open_marker', true)
                        ->exists();

                    if (! $hasCurrentPlacement) {
                        DB::table('asset_placements')->insert([
                            'asset_id' => $asset->id,
                            'location_id' => $asset->location_id,
                            'placed_at' => $asset->updated_at ?? $asset->created_at ?? $now,
                            'removed_at' => null,
                            'open_marker' => true,
                            'placed_by' => null,
                            'source' => 'migration_backfill',
                            'remarks' => 'Current placement backfilled from assets.location_id.',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            });

        Schema::table('asset_placements', function (Blueprint $table) {
            $table->unique(
                ['asset_id', 'open_marker'],
                'asset_placements_one_open_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('asset_placements', function (Blueprint $table) {
            $table->dropUnique('asset_placements_one_open_unique');
        });

        DB::table('asset_placements')
            ->where('source', 'migration_backfill')
            ->delete();

        Schema::table('asset_placements', function (Blueprint $table) {
            $table->dropColumn(['open_marker', 'source']);
        });
    }
};
